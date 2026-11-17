import { Capacitor } from '@capacitor/core';
import { Geolocation } from '@capacitor/geolocation';

export interface CoordenadasGps {
  lat: number;
  lng: number;
  precision?: number | undefined;
}

async function asegurarPermisoNativo(): Promise<boolean> {
  if (!Capacitor.isNativePlatform()) return true;
  try {
    const estado = await Geolocation.checkPermissions();
    if (estado.location === 'granted' || estado.coarseLocation === 'granted') {
      return true;
    }
    const req = await Geolocation.requestPermissions({
      permissions: ['location', 'coarseLocation'],
    });
    return req.location === 'granted' || req.coarseLocation === 'granted';
  } catch {
    return false;
  }
}

/**
 * Obtiene la ubicación GPS actual usando el plugin nativo @capacitor/geolocation en Android/iOS
 * (pidiendo permisos en tiempo de ejecución) y navigator.geolocation en navegador web.
 */
export async function obtenerGpsActual(timeoutMs = 12000): Promise<CoordenadasGps | null> {
  if (Capacitor.isNativePlatform()) {
    const permitido = await asegurarPermisoNativo();
    if (!permitido) return null;

    try {
      const pos = await Geolocation.getCurrentPosition({
        enableHighAccuracy: true,
        timeout: timeoutMs,
        maximumAge: 5000,
      });
      return {
        lat: pos.coords.latitude,
        lng: pos.coords.longitude,
        precision: pos.coords.accuracy,
      };
    } catch {
      // Segundo intento con menor exigencia (útil en interiores)
      try {
        const pos = await Geolocation.getCurrentPosition({
          enableHighAccuracy: false,
          timeout: 8000,
          maximumAge: 30000,
        });
        return {
          lat: pos.coords.latitude,
          lng: pos.coords.longitude,
          precision: pos.coords.accuracy,
        };
      } catch {
        return null;
      }
    }
  }

  if (typeof navigator === 'undefined' || !navigator.geolocation) {
    return null;
  }

  return new Promise((resolve) => {
    navigator.geolocation.getCurrentPosition(
      (pos) =>
        resolve({
          lat: pos.coords.latitude,
          lng: pos.coords.longitude,
          precision: pos.coords.accuracy,
        }),
      () => {
        navigator.geolocation.getCurrentPosition(
          (pos2) =>
            resolve({
              lat: pos2.coords.latitude,
              lng: pos2.coords.longitude,
              precision: pos2.coords.accuracy,
            }),
          () => resolve(null),
          { enableHighAccuracy: false, timeout: 6000, maximumAge: 30000 }
        );
      },
      { enableHighAccuracy: true, timeout: timeoutMs }
    );
  });
}

/**
 * Observa cambios continuos de posición GPS y devuelve una función para detener la escucha.
 */
export async function iniciarObservadorGps(
  onPos: (coords: CoordenadasGps) => void
): Promise<() => void> {
  if (Capacitor.isNativePlatform()) {
    const permitido = await asegurarPermisoNativo();
    if (!permitido) return () => {};

    const watchId = await Geolocation.watchPosition(
      { enableHighAccuracy: true, timeout: 15000, maximumAge: 5000 },
      (pos, err) => {
        if (err || !pos) return;
        onPos({
          lat: pos.coords.latitude,
          lng: pos.coords.longitude,
          precision: pos.coords.accuracy,
        });
      }
    );

    return () => {
      Geolocation.clearWatch({ id: watchId }).catch(() => {});
    };
  }

  if (typeof navigator === 'undefined' || !navigator.geolocation) {
    return () => {};
  }

  const webWatchId = navigator.geolocation.watchPosition(
    (pos) =>
      onPos({
        lat: pos.coords.latitude,
        lng: pos.coords.longitude,
        precision: pos.coords.accuracy,
      }),
    () => {},
    { enableHighAccuracy: true }
  );

  return () => {
    navigator.geolocation.clearWatch(webWatchId);
  };
}
