import type { CapacitorConfig } from '@capacitor/cli';

const appTarget = process.env.APP_TARGET || 'cliente';

const isNegocio = appTarget === 'negocio';

const config: CapacitorConfig = {
  appId: isNegocio ? 'com.tuempresa.tienda.negocio' : 'com.tuempresa.tienda',
  appName: isNegocio ? 'Tienda Negocio' : 'Tienda Express',
  webDir: 'dist/spa',
  server: {
    androidScheme: 'http',
    cleartext: true,
  },
  plugins: {
    SplashScreen: {
      launchShowDuration: 1200,
      backgroundColor: isNegocio ? '#0f172a' : '#4f46e5',
    },
  },
};

export default config;
