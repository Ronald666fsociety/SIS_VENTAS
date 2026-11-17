import { Capacitor } from '@capacitor/core';
import { Filesystem, Directory } from '@capacitor/filesystem';
import { Share } from '@capacitor/share';

function blobABase64(blob: Blob): Promise<string> {
  return new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.onloadend = () => {
      const result = String(reader.result || '');
      const idx = result.indexOf(',');
      resolve(idx >= 0 ? result.slice(idx + 1) : result);
    };
    reader.onerror = () => reject(reader.error);
    reader.readAsDataURL(blob);
  });
}

/**
 * Descarga un archivo Blob en navegador o lo guarda/comparte nativamente en Android (Capacitor).
 */
export async function descargarBlobArchivo(blob: Blob, nombreArchivo: string): Promise<void> {
  if (Capacitor.isNativePlatform()) {
    const base64 = await blobABase64(blob);
    const saved = await Filesystem.writeFile({
      path: nombreArchivo,
      data: base64,
      directory: Directory.Cache,
    });
    await Share.share({
      title: nombreArchivo,
      url: saved.uri,
      dialogTitle: `Guardar o abrir ${nombreArchivo}`,
    });
    return;
  }

  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = nombreArchivo;
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
  URL.revokeObjectURL(url);
}
