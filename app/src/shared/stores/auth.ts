import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { api, APP_TARGET } from '../api/client';
import type { User, Rol } from '../types';

export const useAuthStore = defineStore('auth', () => {
  const token = ref<string | null>(localStorage.getItem(`auth_token_${APP_TARGET}`));
  
  let initialUser: User | null = null;
  try {
    const raw = localStorage.getItem(`auth_user_${APP_TARGET}`);
    if (raw && raw !== 'undefined' && raw !== 'null') {
      initialUser = JSON.parse(raw);
    }
  } catch (e) {
    localStorage.removeItem(`auth_user_${APP_TARGET}`);
  }

  const user = ref<User | null>(initialUser);
  const cargando = ref(false);

  const isAuthenticated = computed(() => !!token.value && !!user.value);
  const rol = computed<Rol | null>(() => user.value?.rol || null);
  const esAdmin = computed(() => user.value?.rol === 'admin');
  const esOperador = computed(() => user.value?.rol === 'operador');
  const esRepartidor = computed(() => user.value?.rol === 'repartidor');
  const esPersonal = computed(() => ['admin', 'operador', 'repartidor'].includes(user.value?.rol || ''));

  function guardarSesion(nuevoToken: string, nuevoUsuario: User) {
    token.value = nuevoToken;
    user.value = nuevoUsuario;
    localStorage.setItem(`auth_token_${APP_TARGET}`, nuevoToken);
    localStorage.setItem(`auth_user_${APP_TARGET}`, JSON.stringify(nuevoUsuario));
  }

  function limpiarSesion() {
    token.value = null;
    user.value = null;
    localStorage.removeItem(`auth_token_${APP_TARGET}`);
    localStorage.removeItem(`auth_user_${APP_TARGET}`);
  }

  async function login(credenciales: { telefono: string; password: string }) {
    cargando.value = true;
    try {
      const resp = await api.post('/auth/login', {
        ...credenciales,
        app: APP_TARGET,
        device_name: navigator.userAgent.substring(0, 50),
      });
      guardarSesion(resp.data.token, resp.data.usuario);
      return resp.data;
    } finally {
      cargando.value = false;
    }
  }

  async function registro(datos: { nombre: string; apellido?: string | undefined; telefono: string; email?: string | undefined; password: string }) {
    cargando.value = true;
    try {
      const resp = await api.post('/auth/registro', datos);
      guardarSesion(resp.data.token, resp.data.usuario);
      return resp.data;
    } finally {
      cargando.value = false;
    }
  }

  async function logout() {
    try {
      if (token.value) {
        await api.post('/auth/logout');
      }
    } catch (e) {
      // Ignorar error al cerrar sesión
    } finally {
      limpiarSesion();
    }
  }

  async function refrescarPerfil() {
    if (!token.value) return;
    try {
      const resp = await api.get('/perfil');
      user.value = resp.data.data;
      localStorage.setItem(`auth_user_${APP_TARGET}`, JSON.stringify(resp.data.data));
    } catch (e) {
      // Si falla, el interceptor 401 limpiará la sesión si expiró
    }
  }

  return {
    token,
    user,
    cargando,
    isAuthenticated,
    rol,
    esAdmin,
    esOperador,
    esRepartidor,
    esPersonal,
    guardarSesion,
    limpiarSesion,
    login,
    registro,
    logout,
    refrescarPerfil,
  };
});
