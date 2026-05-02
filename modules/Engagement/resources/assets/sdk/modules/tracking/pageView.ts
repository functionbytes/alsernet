import { track } from './ecommerce';
import { getConfig } from '../../core/config';

let lastUrl = '';

function emitPageView(): void {
  const url = window.location.href;
  if (url === lastUrl) return;
  lastUrl = url;

  track('page_view', {
    url,
    title: document.title,
    referrer: document.referrer || undefined,
  });
}

export function initPageViewTracking(): void {
  const config = getConfig();
  if (!config.autoTrack.pageView) return;

  emitPageView();

  // Hook History API for SPAs
  const origPush = history.pushState.bind(history);
  const origReplace = history.replaceState.bind(history);

  history.pushState = function (...args) {
    origPush(...args);
    setTimeout(emitPageView, 0);
  };

  history.replaceState = function (...args) {
    origReplace(...args);
    setTimeout(emitPageView, 0);
  };

  window.addEventListener('popstate', () => setTimeout(emitPageView, 0));
}
