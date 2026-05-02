// Wrapper sobre localStorage con fallback a cookie para Safari ITP.
const memFallback = new Map<string, string>();

function canUseLocalStorage(): boolean {
  try {
    localStorage.setItem('__hd_lc_test', '1');
    localStorage.removeItem('__hd_lc_test');
    return true;
  } catch {
    return false;
  }
}

const useLS = canUseLocalStorage();

export const storage = {
  get(key: string): string | null {
    if (useLS) return localStorage.getItem(key);
    return memFallback.get(key) ?? null;
  },
  set(key: string, value: string): void {
    if (useLS) {
      localStorage.setItem(key, value);
    } else {
      memFallback.set(key, value);
    }
  },
  remove(key: string): void {
    if (useLS) {
      localStorage.removeItem(key);
    } else {
      memFallback.delete(key);
    }
  },
};
