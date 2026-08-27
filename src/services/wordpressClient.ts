const apiUrl = import.meta.env.WORDPRESS_API_URL?.replace(/\/$/, '');
const timeoutMs = Number(import.meta.env.WORDPRESS_API_TIMEOUT_MS ?? 8000);

const cache = new Map<string, Promise<unknown>>();

export function isWordPressEnabled(): boolean {
  return Boolean(apiUrl);
}

export async function getFromWordPress<T>(path: string, params: Record<string, string | number | boolean> = {}): Promise<T | null> {
  if (!apiUrl) return null;

  const url = createWordPressUrl(path);
  Object.entries(params).forEach(([key, value]) => url.searchParams.set(key, String(value)));

  const cacheKey = url.toString();
  if (!cache.has(cacheKey)) {
    cache.set(cacheKey, fetchWithTimeout<T>(url));
  }

  try {
    return (await cache.get(cacheKey)) as T;
  } catch (error) {
    console.warn(`[wordpress] ${path} failed:`, error instanceof Error ? error.message : error);
    return null;
  }
}

function createWordPressUrl(path: string): URL {
  const cleanPath = path.replace(/^\//, '').replace(/\/$/, '');
  const url = new URL(apiUrl ?? 'http://localhost');
  const restRoute = url.searchParams.get('rest_route');

  if (restRoute) {
    url.searchParams.set('rest_route', `${restRoute.replace(/\/$/, '')}/${cleanPath}/`);
    return url;
  }

  if (cleanPath.startsWith('milapro/v1/') && apiUrl?.endsWith('/wp/v2')) {
    return new URL(`${apiUrl.replace(/\/wp\/v2$/, '')}/${cleanPath}`);
  }

  return new URL(`${apiUrl}/${cleanPath}`);
}

export async function getPaginatedFromWordPress<T>(path: string, params: Record<string, string | number | boolean> = {}): Promise<T[] | null> {
  const firstPage = await getFromWordPress<T[]>(path, { ...params, per_page: 100, page: 1 });
  if (!firstPage) return null;

  // Current catalog size fits in one page. This wrapper keeps pagination centralized for future growth.
  return firstPage;
}

async function fetchWithTimeout<T>(url: URL): Promise<T> {
  const controller = new AbortController();
  const timeout = setTimeout(() => controller.abort(), timeoutMs);

  try {
    const response = await fetch(url, { signal: controller.signal });
    if (!response.ok) throw new Error(`${response.status} ${response.statusText}`);
    return (await response.json()) as T;
  } finally {
    clearTimeout(timeout);
  }
}

export function stripHtml(value = ''): string {
  return value.replace(/<[^>]*>/g, '').replace(/\s+/g, ' ').trim();
}

export function decodeHtml(value = ''): string {
  return value
    .replace(/&#8211;/g, '-')
    .replace(/&#8217;/g, "'")
    .replace(/&#8220;|&#8221;/g, '"')
    .replace(/&amp;/g, '&')
    .replace(/&nbsp;/g, ' ')
    .trim();
}

export function wpImageUrl(image: unknown): string | undefined {
  if (!image) return undefined;
  if (typeof image === 'string') return image;
  if (typeof image !== 'object') return undefined;
  const candidate = image as { url?: string; source_url?: string; sizes?: Record<string, string | { url?: string }> };
  if (candidate.sizes?.large && typeof candidate.sizes.large === 'object') return candidate.sizes.large.url;
  if (typeof candidate.sizes?.large === 'string') return candidate.sizes.large;
  return candidate.url ?? candidate.source_url;
}

export function wpBoolean(value: unknown, fallback = false): boolean {
  if (value === undefined || value === null || value === '') return fallback;
  if (value === false || value === 0 || value === '0' || value === 'false') return false;
  return true;
}
