import type { SdkEventName, VisitorIdentity } from './types';
import { setConfig } from './core/config';
import { getOrCreateVisitorId, getSessionToken, setSessionToken, setCustomerId, getStoredConsent, storeConsent, clearConsent } from './core/session';
import { on as busOn, emit } from './core/eventBus';
import { startSession } from './core/lifecycle';
import { eventQueue } from './services/queue';
import { transport } from './services/transport';
import { api } from './services/apiClient';
import { initPageViewTracking } from './modules/tracking/pageView';
import { track as trackEvent } from './modules/tracking/ecommerce';
import { identify as identifyUser } from './modules/identify/identify';
import { setContext as setContextData } from './modules/context/context';
import { initLocalScorer, updateScore } from './modules/scoring/localScorer';
import { loadTriggerRules as engineLoad, startTriggerEngine as engineStart } from './modules/triggers/engine';
import { loadPersonalizationRules } from './modules/personalization/domMutator';
import { openWidget, closeWidget, initWidgetBridge } from './modules/chat/widgetBridge';
import { initRealtime } from './services/realtime';
import { registerServiceWorker, syncPendingEvents } from './services/serviceWorker';
import { fetchRecommendations } from './modules/recommender/client';
import { detectPlatform, getActivePlatformName, platformApi } from './core/platformDetector';
import { captureUTM } from './modules/attribution/utm';
import { logger } from './utils/logger';

interface InitOptions {
  token: string;
  apiUrl?: string;
  debug?: boolean;
  consent?: boolean;
  autoTrack?: { pageView?: boolean; sessionLifecycle?: boolean };
  batchInterval?: number;
  batchSize?: number;
}

interface InitApiResponse {
  success: boolean;
  data: {
    visitorId: string;
    sessionToken: string;
    customerId: number | null;
    score: number;
    segment: 'cold' | 'warm' | 'hot';
    triggers: unknown[];
    personalizations: unknown[];
    wsChannel: string;
    config?: {
      realtime?: {
        driver: string;
        key: string;
        host: string;
        port: number;
        scheme: string;
      };
    };
  };
}

let consentGranted = false;
let pendingInit: (() => void) | null = null;

async function doInit(opts: InitOptions): Promise<void> {
  setConfig(opts);
  await eventQueue.init();

  const visitorId = getOrCreateVisitorId();
  const existingSession = getSessionToken();
  const utm = captureUTM();

  try {
    const res = await api.post<InitApiResponse>('sdk/init', {
      visitorId,
      page: {
        url: window.location.href,
        title: document.title,
        referrer: document.referrer || undefined,
      },
      device: {
        userAgent: navigator.userAgent,
        language: navigator.language,
        timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
        viewport: { width: window.innerWidth, height: window.innerHeight },
      },
      attribution: utm ?? undefined,
    });

    if (!res.success) throw new Error('init failed');

    const { sessionToken, customerId, score, segment, triggers, personalizations } = res.data;

    setSessionToken(sessionToken);
    if (customerId) setCustomerId(customerId);

    updateScore(score, segment);
    engineLoad(triggers as Parameters<typeof engineLoad>[0]);
    loadPersonalizationRules(personalizations as Parameters<typeof loadPersonalizationRules>[0]);

    initLocalScorer();
    engineStart();
    initPageViewTracking();
    startSession();

    initRealtime(res.data.wsChannel, res.data.config?.realtime ?? null);

    void registerServiceWorker().then(() => syncPendingEvents());

    initPlatformBridge();

    emit('ready', { sessionToken, visitorId });
    logger.log('ready — score:', score, 'segment:', segment);
  } catch (err) {
    logger.error('init error:', err);
    emit('error', { code: 'INIT_FAILED', message: String(err) });
  }
}

const chatApi = {
  init(opts: InitOptions): void {
    // Restore previously-given consent if present in localStorage
    const stored = getStoredConsent();
    const initialConsent = opts.consent ?? stored ?? false;

    if (initialConsent === false) {
      pendingInit = () => {
        consentGranted = true;
        storeConsent(true);
        doInit(opts);
      };
      logger.log('consent required — call setConsent(true) to activate');
      return;
    }
    consentGranted = true;
    if (stored !== true) storeConsent(true);
    doInit(opts);
    drainCommandQueue();
  },

  identify(user: VisitorIdentity): void {
    withConsent(() => identifyUser(user));
  },

  track(eventName: string, properties?: Record<string, unknown>): void {
    withConsent(() => trackEvent(eventName, properties ?? {}));
  },

  setContext(ctx: Record<string, unknown>): void {
    withConsent(() => setContextData(ctx));
  },

  setConsent(granted: boolean): void {
    consentGranted = granted;
    if (granted) {
      storeConsent(true);
    } else {
      clearConsent();
    }
    if (granted && pendingInit) {
      const fn = pendingInit;
      pendingInit = null;
      fn();
      drainCommandQueue();
    }
    logger.log('consent set:', granted);
  },

  open(): void {
    withConsent(() => openWidget());
  },

  close(): void {
    closeWidget();
  },

  getRecommendations(): Promise<import('./modules/recommender/client').RecommendedProduct[]> {
    return fetchRecommendations();
  },

  platform: platformApi,

  on(event: SdkEventName | string, handler: (payload: unknown) => void): void {
    busOn(event, handler);
  },
};

function withConsent(fn: () => void): void {
  if (consentGranted) {
    fn();
  } else {
    logger.warn('tracking blocked — consent not granted');
  }
}

/**
 * Detects the active e-commerce platform, takes initial snapshots of
 * cart/customer/product, and binds platform-native events so that
 * cart updates auto-emit setContext() + track('cart_updated').
 */
function initPlatformBridge(): void {
  const adapter = detectPlatform();
  emit('platform:detected', { name: adapter.name });

  const flushSnapshot = () => {
    const cart = adapter.getCart();
    const product = adapter.getProduct();
    const customer = adapter.getCustomer();

    if (cart) {
      setContextData({
        platform: adapter.name,
        cartValue: cart.value,
        cartItems: cart.items,
        cartCurrency: cart.currency,
      });
      emit('platform:cart_updated', cart);
    }

    if (product) {
      trackEvent('product_view', {
        platform: adapter.name,
        productId: product.id,
        productName: product.name,
        price: product.price,
        currency: product.currency,
        category: product.category,
      });
    }

    if (customer?.email) {
      identifyUser(customer);
    }
  };

  flushSnapshot();

  adapter.bindHooks((kind) => {
    if (kind === 'cart') {
      const cart = adapter.getCart();
      if (cart) {
        setContextData({
          cartValue: cart.value,
          cartItems: cart.items,
          cartCurrency: cart.currency,
        });
        trackEvent('cart_updated', {
          platform: adapter.name,
          value: cart.value,
          items: cart.items,
        });
        emit('platform:cart_updated', cart);
      }
    } else if (kind === 'product') {
      const product = adapter.getProduct();
      if (product) {
        trackEvent('product_view', {
          platform: adapter.name,
          productId: product.id,
          productName: product.name,
          price: product.price,
        });
      }
    } else if (kind === 'customer') {
      const customer = adapter.getCustomer();
      if (customer?.email) {
        identifyUser(customer);
      }
    }
  });

  logger.log('platform bridge active for', getActivePlatformName());
}

// Resolves chat('method', ...) and chat('namespace', 'method', ...) forms.
function dispatch(args: unknown[]): unknown {
  const [first, ...rest] = args as [string, ...unknown[]];
  const target = (chatApi as unknown as Record<string, unknown>)[first];

  if (typeof target === 'function') {
    return (target as (...a: unknown[]) => unknown).apply(chatApi, rest);
  }

  if (target && typeof target === 'object' && rest.length > 0) {
    const [sub, ...subRest] = rest as [string, ...unknown[]];
    const fn = (target as Record<string, unknown>)[sub];
    if (typeof fn === 'function') {
      return (fn as (...a: unknown[]) => unknown).apply(target, subRest);
    }
  }
  return undefined;
}

// Drain command queue created by the inline loader stub
function drainCommandQueue(): void {
  const stub = (window as any).chat;
  if (stub?.q && Array.isArray(stub.q)) {
    const cmds: IArguments[] = [...stub.q];
    stub.q = [];
    for (const args of cmds) {
      dispatch(Array.from(args));
    }
  }
}

// Replace the stub with the real API
(window as any).chat = new Proxy(chatApi, {
  apply(_target, _thisArg, args: unknown[]) {
    return dispatch(args);
  },
});

// Drain any queued calls from before the SDK loaded
drainCommandQueue();

export default chatApi;
