/// <reference types="astro/client" />

interface ImportMetaEnv {
  readonly WORDPRESS_API_URL?: string;
  readonly WORDPRESS_SITE_URL?: string;
  readonly WORDPRESS_API_TIMEOUT_MS?: string;
}

interface ImportMeta {
  readonly env: ImportMetaEnv;
}
