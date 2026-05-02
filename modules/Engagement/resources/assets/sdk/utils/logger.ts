import { isInitialized, getConfig } from '../core/config';

function isDebug(): boolean {
  try {
    return isInitialized() && getConfig().debug;
  } catch {
    return false;
  }
}

export const logger = {
  log(...args: unknown[]): void {
    if (isDebug()) console.log('[chat]', ...args);
  },
  warn(...args: unknown[]): void {
    if (isDebug()) console.warn('[chat]', ...args);
  },
  error(...args: unknown[]): void {
    console.error('[chat]', ...args);
  },
};
