import type { TrackingEvent } from '../types';
import { api } from './apiClient';
import { eventQueue } from './queue';
import { getConfig } from '../core/config';
import { logger } from '../utils/logger';

let buffer: TrackingEvent[] = [];
let timer: ReturnType<typeof setTimeout> | null = null;
let online = navigator.onLine;

window.addEventListener('online', () => {
  online = true;
  flushOnline();
});

window.addEventListener('offline', () => {
  online = false;
});

async function flushOnline(): Promise<void> {
  const offline = await eventQueue.flush();
  if (offline.length > 0) {
    sendBatch(offline);
  }
}

function scheduleFlush(): void {
  if (timer) return;
  const config = getConfig();
  timer = setTimeout(flush, config.batchInterval);
}

export async function flush(): Promise<void> {
  if (timer) { clearTimeout(timer); timer = null; }
  if (buffer.length === 0) return;

  const batch = [...buffer];
  buffer = [];

  if (!online) {
    for (const e of batch) await eventQueue.push(e);
    return;
  }

  await sendBatch(batch);
}

export function flushSync(): void {
  if (buffer.length === 0) return;
  const batch = [...buffer];
  buffer = [];
  api.beacon('sdk/track', { events: batch });
}

export const transport = {
  push(event: TrackingEvent): void {
    buffer.push(event);
    logger.log('queued event:', event.name, event.properties);
    const config = getConfig();
    if (buffer.length >= config.batchSize) {
      flush();
    } else {
      scheduleFlush();
    }
  },
  flush,
  flushSync,
};

async function sendBatch(events: TrackingEvent[]): Promise<void> {
  try {
    await api.post('sdk/track', { events });
    logger.log(`sent ${events.length} events`);
  } catch {
    for (const e of events) await eventQueue.push(e);
    logger.warn('batch failed, re-queued', events.length, 'events');
  }
}
