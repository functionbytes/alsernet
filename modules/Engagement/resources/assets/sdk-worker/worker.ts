/// <reference lib="webworker" />
declare const self: ServiceWorkerGlobalScope;

const SDK_QUEUE_DB = 'hd_lc_sdk';
const SDK_QUEUE_STORE = 'events';

self.addEventListener('install', () => {
  void self.skipWaiting();
});

self.addEventListener('activate', (event: ExtendableEvent) => {
  event.waitUntil(self.clients.claim());
});

interface SyncEvent extends ExtendableEvent {
  tag: string;
}

self.addEventListener('sync', (event: Event) => {
  const ev = event as SyncEvent;
  if (ev.tag === 'chat-sync-events') {
    ev.waitUntil(flushQueuedEvents());
  }
});

async function flushQueuedEvents(): Promise<void> {
  const events = await readAllFromIDB();
  if (events.length === 0) return;

  // Group events by their target endpoint (encoded in event metadata)
  const grouped = new Map<string, Array<{ id: number; payload: unknown }>>();
  for (const item of events) {
    const url = (item as unknown as { __url?: string }).__url ?? '';
    if (!url) continue;
    if (!grouped.has(url)) grouped.set(url, []);
    grouped.get(url)!.push(item);
  }

  for (const [url, items] of grouped) {
    try {
      const res = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          events: items.map((i) => (i as unknown as { payload: unknown }).payload),
        }),
      });
      if (res.ok) {
        await removeFromIDB(items.map((i) => i.id));
      }
    } catch {
      // keep events for next sync
    }
  }
}

function openDb(): Promise<IDBDatabase> {
  return new Promise((resolve, reject) => {
    const req = indexedDB.open(SDK_QUEUE_DB, 1);
    req.onupgradeneeded = () => {
      const db = req.result;
      if (!db.objectStoreNames.contains(SDK_QUEUE_STORE)) {
        db.createObjectStore(SDK_QUEUE_STORE, { keyPath: 'id', autoIncrement: true });
      }
    };
    req.onsuccess = () => resolve(req.result);
    req.onerror = () => reject(req.error);
  });
}

function readAllFromIDB(): Promise<Array<{ id: number; payload: unknown }>> {
  return new Promise((resolve) => {
    openDb().then((db) => {
      const tx = db.transaction(SDK_QUEUE_STORE, 'readonly');
      const store = tx.objectStore(SDK_QUEUE_STORE);
      const req = store.getAll();
      req.onsuccess = () => resolve((req.result ?? []) as Array<{ id: number; payload: unknown }>);
      req.onerror = () => resolve([]);
    }).catch(() => resolve([]));
  });
}

function removeFromIDB(ids: number[]): Promise<void> {
  return new Promise((resolve) => {
    openDb().then((db) => {
      const tx = db.transaction(SDK_QUEUE_STORE, 'readwrite');
      const store = tx.objectStore(SDK_QUEUE_STORE);
      ids.forEach((id) => store.delete(id));
      tx.oncomplete = () => resolve();
      tx.onerror = () => resolve();
    }).catch(() => resolve());
  });
}

export {}; // mark as module
