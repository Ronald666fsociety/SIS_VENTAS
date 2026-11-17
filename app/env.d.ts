declare namespace NodeJS {
  interface ProcessEnv {
    APP_TARGET?: string;
    API_URL?: string;
    PORT?: string;
  }
}

declare const __APP_TARGET__: string;
declare const __API_URL__: string;

interface ImportMetaEnv {
  readonly APP_TARGET?: string;
  readonly API_URL?: string;
}
