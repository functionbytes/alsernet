import type { TrackingEvent } from '../types';

// Cola offline con IndexedDB + fallback a localStorage.
const LS_KEY = '__hd_lc_queue';
const DB_NAME = 'hd_lc_sdk';
const STORE_NAME = 'events';

let db: IDBDatabase | null = null;
let dbReady = false;

async function openDb(): Promise<IDBDatabase | null> {
  if (!window.indexedDB) return null;
  return new Promise((resolve) => {
    const req = indexedDB.open(DB_NAME, 1);
    req.onupgradeneeded = () => {
      req.result.createObjectStore(STORE_NAME, { autoIncrement: true });
    };
    req.onsuccess = () => { resolve(req.result); };
    req.onerror = () => { resolve(null); };
  });
}

export const eventQueue = {
  async init(): Promise<void> {
    db = await openDb();
    dbReady = true;
  },

  async push(event: TrackingEvent): Promise<void> {
    if (!dbReady || !db) {
      this._lsPush(event);
      return;
    }
    const tx = db.transaction(STORE_NAME, 'readwrite');
    tx.objectStore(STORE_NAME).add(event);
  },

  async flush(): Promise<TrackingEvent[]> {
    if (!dbReady || !db) return this._lsFlush();

    return new Promise((resolve) => {
      const events: TrackingEvent[] = [];
      const keys: IDBValidKey[] = [];
      const tx = db!.transaction(STORE_NAME, 'readwrite');
      const store = tx.objectStore(STORE_NAME);
      const cursor = store.openCursor();
      cursor.onsuccess = (e) => {
        const c = (e.target as IDBRequest<IDBCursorWithValue>).result;
        if (c) {
          events.push(c.value as TrackingEvent);
          keys.push(c.key);
          c.continue();
        } else {
          keys.forEach((k) => store.delete(k));
          resolve(events);
        }
      };
      cursor.onerror = () => resolve([]);
    });
  },

  _lsPush(event: TrackingEvent): void {
    try {
      const raw = localStorage.getItem(LS_KEY);
      const arr: TrackingEvent[] = raw ? JSON.parse(raw) : [];
      arr.push(event);
      localStorage.setItem(LS_KEY, JSON.stringify(arr.slice(-200)));
    } catch { /* ignore */ }
  },

  _lsFlush(): TrackingEvent[] {
    try {
      const raw = localStorage.getItem(LS_KEY);
      localStorage.removeItem(LS_KEY);
      return raw ? JSON.parse(raw) : [];
    } catch {
      return [];
    }
  },
};
