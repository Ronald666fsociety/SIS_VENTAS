import { Camera, CameraResultType, CameraSource } from '@capacitor/camera';

/**
 * Abre la cámara o galería nativa (Capacitor) y devuelve un File comprimido listo para subir.
 */
export async function capturarFotoNativa(): Promise<File | null> {
  try {
    const photo = await Camera.getPhoto({
      quality: 85,
      allowEditing: false,
      resultType: CameraResultType.DataUrl,
      source: CameraSource.Prompt,
      width: 1400,
    });
    if (!photo.dataUrl) return null;
    const res = await fetch(photo.dataUrl);
    const blob = await res.blob();
    const raw = new File([blob], `foto_${Date.now()}.jpg`, { type: blob.type || 'image/jpeg' });
    return await comprimirImagen(raw);
  } catch {
    return null;
  }
}

/**
 * Comprime y redimensiona una imagen antes de subirla desde celular para evitar
 * rechazos por tamaño (>2MB) y acelerar la carga usando datos móviles.
 */
export async function comprimirImagen(file: File, maxLado = 1400, calidad = 0.84): Promise<File> {
  if (!file.type.startsWith('image/')) return file;

  return new Promise((resolve) => {
    const img = new Image();
    const objectUrl = URL.createObjectURL(file);

    img.onload = () => {
      try {
        const escala = Math.min(1, maxLado / Math.max(img.width, img.height));
        const w = Math.max(1, Math.round(img.width * escala));
        const h = Math.max(1, Math.round(img.height * escala));

        const canvas = document.createElement('canvas');
        canvas.width = w;
        canvas.height = h;
        const ctx = canvas.getContext('2d');
        if (!ctx) {
          URL.revokeObjectURL(objectUrl);
          resolve(file);
          return;
        }

        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, w, h);
        ctx.drawImage(img, 0, 0, w, h);

        canvas.toBlob(
          (blob) => {
            URL.revokeObjectURL(objectUrl);
            if (!blob) {
              resolve(file);
              return;
            }
            const nombreBase = file.name.replace(/\.[^.]+$/, '') || 'imagen';
            resolve(new File([blob], `${nombreBase}.jpg`, { type: 'image/jpeg' }));
          },
          'image/jpeg',
          calidad
        );
      } catch {
        URL.revokeObjectURL(objectUrl);
        resolve(file);
      }
    };

    img.onerror = () => {
      URL.revokeObjectURL(objectUrl);
      resolve(file);
    };

    img.src = objectUrl;
  });
}
