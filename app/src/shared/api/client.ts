import axios, { type AxiosInstance } from 'axios';
import { Notify } from 'quasar';

const STORAGE_API_KEY = 'custom_api_url';

function normalizarApiUrl(url: string): string {
  const limpio = url.trim().replace(/\/+$/, '');
  if (!limpio) return '';
  return limpio.endsWith('/api/v1') ? limpio : `${limpio}/api/v1`;
}

export function resolveApiBase(): string {
  if (typeof window !== 'undefined') {
    const custom = localStorage.getItem(STORAGE_API_KEY);
    if (custom && /^https?:\/\//i.test(custom)) {
      return normalizarApiUrl(custom);
    }
  }

  const configured = import.meta.env.API_URL || __API_URL__ || 'http://127.0.0.1:8000/api/v1';
  if (
    typeof window !== 'undefined' &&
    window.location.hostname &&
    window.location.hostname !== 'localhost' &&
    window.location.hostname !== '127.0.0.1' &&
    (configured.includes('127.0.0.1') || configured.includes('localhost'))
  ) {
    return `http://${window.location.hostname}:8000/api/v1`;
  }
  return configured;
}

export function getCustomApiUrl(): string {
  return typeof window !== 'undefined' ? localStorage.getItem(STORAGE_API_KEY) || '' : '';
}

export function setCustomApiUrl(url: string): string {
  const normalizada = normalizarApiUrl(url);
  if (normalizada) {
    localStorage.setItem(STORAGE_API_KEY, normalizada);
    api.defaults.baseURL = normalizada;
  } else {
    localStorage.removeItem(STORAGE_API_KEY);
    api.defaults.baseURL = resolveApiBase();
  }
  return api.defaults.baseURL || '';
}

const apiBase = resolveApiBase();

function getServerOrigin(): string {
  const base = api.defaults.baseURL || apiBase;
  return base.replace(/\/api\/v1\/?$/, '').replace(/\/+$/, '');
}

export function resolverUrlArchivo(url?: string | null): string {
  if (!url || typeof url !== 'string') return '';
  const trimmed = url.trim();
  if (!trimmed) return '';
  const origin = getServerOrigin();

  if (trimmed.startsWith('/storage/')) {
    return `${origin}/api/v1${trimmed}`;
  }
  if (trimmed.startsWith('/')) {
    return `${origin}${trimmed}`;
  }
  // Reescribir localhost / 127.0.0.1 / esquema http<->https al servidor activo para rutas de archivos del backend
  const m = trimmed.match(/^https?:\/\/[^/]+((?:\/api\/v1)?\/(?:storage|comprobantes|entregas)\/.+)$/i);
  if (m && m[1]) {
    const path = m[1].startsWith('/storage/') ? `/api/v1${m[1]}` : m[1];
    return `${origin}${path}`;
  }
  return trimmed;
}

const CAMPOS_ARCHIVO = new Set(['url', 'qr_imagen_url', 'comprobante_url', 'foto_entrega_url']);

function normalizarUrlsEnRespuesta(data: unknown): void {
  if (!data || typeof data !== 'object') return;
  if (Array.isArray(data)) {
    for (const item of data) {
      normalizarUrlsEnRespuesta(item);
    }
    return;
  }
  const obj = data as Record<string, unknown>;
  for (const key of Object.keys(obj)) {
    const val = obj[key];
    if (CAMPOS_ARCHIVO.has(key) && typeof val === 'string') {
      obj[key] = resolverUrlArchivo(val);
    } else if (val && typeof val === 'object') {
      normalizarUrlsEnRespuesta(val);
    }
  }
}

export const APP_TARGET: 'cliente' | 'negocio' = (
  import.meta.env.APP_TARGET ||
  __APP_TARGET__ ||
  'cliente'
) as 'cliente' | 'negocio';

export const APP_VERSION = '1.0.0';

export const api: AxiosInstance = axios.create({
  baseURL: apiBase,
  timeout: 15000,
  headers: {
    Accept: 'application/json',
    'X-App': APP_TARGET,
    'X-App-Version': APP_VERSION,
  },
});

// Interceptor para agregar el token Sanctum
api.interceptors.request.use((config) => {
  const token = localStorage.getItem(`auth_token_${APP_TARGET}`);
  if (token && config.headers) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Interceptor de errores y normalización automática de URLs de imágenes/archivos
api.interceptors.response.use(
  (response) => {
    if (response.data && typeof response.data === 'object' && !(response.data instanceof Blob)) {
      normalizarUrlsEnRespuesta(response.data);
    }
    return response;
  },
  (error) => {
    if (error.response) {
      const status = error.response.status;
      const data = error.response.data;

      if (status === 401) {
        localStorage.removeItem(`auth_token_${APP_TARGET}`);
        localStorage.removeItem(`auth_user_${APP_TARGET}`);
        if (APP_TARGET === 'negocio' && !window.location.hash.includes('/login')) {
          window.location.hash = '#/login';
        }
      } else if (status === 426) {
        Notify.create({
          type: 'negative',
          message: data?.error || 'Actualización requerida. Por favor actualiza la app.',
          position: 'top',
          timeout: 0,
          actions: [{ label: 'OK', color: 'white' }],
        });
      } else if (status >= 500) {
        Notify.create({
          type: 'negative',
          message: 'Error en el servidor. Intente nuevamente en unos momentos.',
          position: 'bottom',
        });
      }
    } else {
      Notify.create({
        type: 'warning',
        message: 'No hay conexión con el servidor.',
        position: 'bottom',
      });
    }

    return Promise.reject(error);
  }
);

export default api;
