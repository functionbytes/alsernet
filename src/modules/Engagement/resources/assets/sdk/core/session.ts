import { storage } from '../services/storage';

const VISITOR_ID_KEY = '__hd_lc_vid';
const SESSION_TOKEN_KEY = '__hd_lc_st';
const CUSTOMER_ID_KEY = '__hd_lc_cid';
const CONSENT_KEY = '__hd_lc_consent';
const CONSENT_TS_KEY = '__hd_lc_consent_ts';

export function getOrCreateVisitorId(): string {
  let id = storage.get(VISITOR_ID_KEY);
  if (!id) {
    id = crypto.randomUUID();
    storage.set(VISITOR_ID_KEY, id);
  }
  return id;
}

export function getSessionToken(): string | null {
  return storage.get(SESSION_TOKEN_KEY);
}

export function setSessionToken(token: string): void {
  storage.set(SESSION_TOKEN_KEY, token);
}

export function getCustomerId(): number | null {
  const v = storage.get(CUSTOMER_ID_KEY);
  return v ? parseInt(v, 10) : null;
}

export function setCustomerId(id: number): void {
  storage.set(CUSTOMER_ID_KEY, String(id));
}

export function clearSession(): void {
  storage.remove(SESSION_TOKEN_KEY);
  storage.remove(CUSTOMER_ID_KEY);
}

export function getStoredConsent(): boolean | null {
  const v = storage.get(CONSENT_KEY);
  if (v === '1') return true;
  if (v === '0') return false;
  return null;
}

export function storeConsent(granted: boolean): void {
  storage.set(CONSENT_KEY, granted ? '1' : '0');
  storage.set(CONSENT_TS_KEY, new Date().toISOString());
}

export function getConsentTimestamp(): string | null {
  return storage.get(CONSENT_TS_KEY);
}

export function clearConsent(): void {
  storage.remove(CONSENT_KEY);
  storage.remove(CONSENT_TS_KEY);
}
