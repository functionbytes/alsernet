import { logger } from '../utils/logger';

const SW_PATH = '/build-helpdesklivechat/sdk-worker.js';

let registration: ServiceWorkerRegistration | null = null;

export async function registerServiceWorker(): Promise<void> {
  if (!('serviceWorker' in navigator)) {
    logger.log('serviceWorker: not supported in this browser');
    return;
  }

  try {
    registration = await navigator.serviceWorker.register(SW_PATH, { scope: '/' });
    logger.log('serviceWorker: registered', registration.scope);
  } catch (err) {
    logger.warn('serviceWorker: registration failed', err);
  }
}

export async function syncPendingEvents(): Promise<void> {
  if (!registration || !('sync' in registration)) return;

  try {
    await (registration as unknown as { sync: { register: (tag: string) => Promise<void> } })
      .sync
      .register('chat-sync-events');
    logger.log('serviceWorker: background sync requested');
  } catch (err) {
    logger.warn('serviceWorker: sync request failed', err);
  }
}
