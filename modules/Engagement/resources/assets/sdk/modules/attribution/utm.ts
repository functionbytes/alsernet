import { storage } from '../../services/storage';

const UTM_KEY = '__hd_lc_utm';

interface UTMData {
  utm_source?: string;
  utm_medium?: string;
  utm_campaign?: string;
  utm_term?: string;
  utm_content?: string;
  referrer_host?: string;
  capturedAt: string;
}

export function captureUTM(): UTMData | null {
  const url = new URL(window.location.href);
  const utm: UTMData = { capturedAt: new Date().toISOString() };
  let any = false;

  ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'].forEach((key) => {
    const v = url.searchParams.get(key);
    if (v) {
      (utm as Record<string, string>)[key] = v;
      any = true;
    }
  });

  if (document.referrer) {
    try {
      utm.referrer_host = new URL(document.referrer).hostname;
      any = true;
    } catch {
      // ignore
    }
  }

  if (!any) {
    return getStoredUTM();
  }

  storage.set(UTM_KEY, JSON.stringify(utm));
  return utm;
}

export function getStoredUTM(): UTMData | null {
  const raw = storage.get(UTM_KEY);
  if (!raw) return null;
  try {
    return JSON.parse(raw) as UTMData;
  } catch {
    return null;
  }
}

export function clearUTM(): void {
  storage.remove(UTM_KEY);
}
