import { getConfig } from './config';
import { emit } from './eventBus';
import { track } from '../modules/tracking/ecommerce';
import { transport } from '../services/transport';

let sessionStarted = false;
let sessionEndTimer: ReturnType<typeof setTimeout> | null = null;
const INACTIVITY_TIMEOUT = 30 * 60 * 1000; // 30 min

export function startSession(): void {
  if (sessionStarted) return;
  sessionStarted = true;

  const config = getConfig();
  if (config.autoTrack.sessionLifecycle) {
    track('session_start', {
      language: navigator.language,
      timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
      viewport: { width: window.innerWidth, height: window.innerHeight },
    });
  }

  document.addEventListener('visibilitychange', onVisibilityChange);
  window.addEventListener('beforeunload', onBeforeUnload);
  resetInactivityTimer();
}

function onVisibilityChange(): void {
  if (document.visibilityState === 'hidden') {
    transport.flush();
    endSession();
  } else {
    resetInactivityTimer();
  }
}

function onBeforeUnload(): void {
  transport.flushSync();
  endSession();
}

function endSession(): void {
  if (!sessionStarted) return;
  sessionStarted = false;

  const config = getConfig();
  if (config.autoTrack.sessionLifecycle) {
    track('session_end', {});
  }

  emit('session:end', {});
}

function resetInactivityTimer(): void {
  if (sessionEndTimer) clearTimeout(sessionEndTimer);
  sessionEndTimer = setTimeout(() => {
    endSession();
  }, INACTIVITY_TIMEOUT);
}
