import { getConfig } from '../../core/config';
import { emit } from '../../core/eventBus';
import { logger } from '../../utils/logger';

let widgetLoaded = false;
let widgetOpen = false;
const pendingCommands: Array<() => void> = [];

function enqueue(fn: () => void): void {
  if (widgetLoaded) {
    fn();
  } else {
    pendingCommands.push(fn);
  }
}

function drainQueue(): void {
  while (pendingCommands.length > 0) {
    pendingCommands.shift()!();
  }
}

export function initWidgetBridge(): void {
  // The widget exposes window.chatWidget after it loads
  if ((window as any).chatWidget && (window as any).__helpdeskWidgetEmbedLoaded) {
    widgetLoaded = true;
    drainQueue();
    return;
  }

  // Widget not loaded yet — load it now lazily
  loadWidgetBundle();
}

function loadWidgetBundle(): void {
  if (widgetLoaded) return;

  const config = getConfig();
  const base = config.apiUrl;

  const script = document.createElement('script');
  script.src = `${base}/widget/script/${config.token}`;
  script.async = true;
  script.type = 'module';
  script.onload = () => {
    widgetLoaded = true;
    logger.log('widget bundle loaded');
    drainQueue();
  };
  script.onerror = () => {
    logger.error('failed to load widget bundle');
  };
  document.head.appendChild(script);
}

export function openWidget(): void {
  if (!widgetLoaded) {
    initWidgetBridge();
    enqueue(() => openWidget());
    return;
  }

  const cw = (window as any).chatWidget;
  if (cw && typeof cw.open === 'function') {
    cw.open();
  } else if (cw) {
    cw('open');
  }

  widgetOpen = true;
  emit('chat:opened', {});
}

export function closeWidget(): void {
  enqueue(() => {
    const cw = (window as any).chatWidget;
    if (cw && typeof cw.close === 'function') {
      cw.close();
    } else if (cw) {
      cw('close');
    }
    widgetOpen = false;
    emit('chat:closed', {});
  });
}

export function isWidgetOpen(): boolean {
  return widgetOpen;
}
