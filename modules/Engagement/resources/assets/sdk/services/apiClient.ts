import { getConfig } from '../core/config';
import { getSessionToken } from '../core/session';
import { logger } from '../utils/logger';

const SDK_VERSION = '1.0.0';

async function request<T>(
  method: string,
  path: string,
  body?: unknown,
  retries = 2,
): Promise<T> {
  const config = getConfig();
  const sessionToken = getSessionToken();

  const headers: Record<string, string> = {
    'Content-Type': 'application/json',
    'X-Website-Token': config.token,
    'X-SDK-Version': SDK_VERSION,
  };

  if (sessionToken) {
    headers['X-Session-Token'] = sessionToken;
  }

  for (let attempt = 0; attempt <= retries; attempt++) {
    try {
      const res = await fetch(`${config.apiUrl}/hd/api/${path}`, {
        method,
        headers,
        body: body ? JSON.stringify(body) : undefined,
        keepalive: true,
      });

      if (!res.ok) {
        const err = await res.json().catch(() => ({ message: res.statusText }));
        throw new Error(err.message ?? `HTTP ${res.status}`);
      }

      return res.json() as Promise<T>;
    } catch (err) {
      if (attempt < retries) {
        await sleep(300 * (attempt + 1));
        continue;
      }
      logger.error(`API ${method} ${path} failed:`, err);
      throw err;
    }
  }

  throw new Error('Max retries exceeded');
}

export const api = {
  post<T>(path: string, body: unknown): Promise<T> {
    return request<T>('POST', path, body);
  },
  get<T>(path: string): Promise<T> {
    return request<T>('GET', path);
  },
  beacon(path: string, body: unknown): void {
    const config = getConfig();
    const sessionToken = getSessionToken();
    const url = `${config.apiUrl}/hd/api/${path}`;
    const data = JSON.stringify(body);
    try {
      navigator.sendBeacon(url, new Blob([data], { type: 'application/json' }));
    } catch {
      // silently fail — beacon is best-effort
    }
  },
};

function sleep(ms: number): Promise<void> {
  return new Promise((r) => setTimeout(r, ms));
}
