import type { SdkConfig } from '../types';

let _config: SdkConfig | null = null;

export function setConfig(opts: Partial<SdkConfig> & { token: string }): void {
  _config = {
    token: opts.token,
    apiUrl: opts.apiUrl ?? inferApiUrl(),
    debug: opts.debug ?? false,
    consent: opts.consent ?? false,
    autoTrack: {
      pageView: opts.autoTrack?.pageView ?? true,
      sessionLifecycle: opts.autoTrack?.sessionLifecycle ?? true,
    },
    batchInterval: opts.batchInterval ?? 5000,
    batchSize: opts.batchSize ?? 10,
  };
}

export function getConfig(): SdkConfig {
  if (!_config) throw new Error('[chat] SDK not initialized. Call chat.init() first.');
  return _config;
}

export function isInitialized(): boolean {
  return _config !== null;
}

function inferApiUrl(): string {
  const scripts = document.querySelectorAll<HTMLScriptElement>('script[src*="/widget/script/"]');
  if (scripts.length > 0) {
    const url = new URL(scripts[0].src);
    return `${url.protocol}//${url.host}`;
  }
  return window.location.origin;
}
