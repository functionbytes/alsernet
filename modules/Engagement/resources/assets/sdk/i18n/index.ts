type Locale = 'es' | 'en' | 'pt' | 'fr';

const messages: Record<Locale, Record<string, string>> = {
  es: {
    'consent.required': 'Se requiere consentimiento. Llama a chat.setConsent(true).',
    'consent.blocked': 'Tracking bloqueado — consentimiento no otorgado',
    'init.error': 'Error al inicializar el SDK',
    'init.failed': 'Falló la inicialización',
    'realtime.connected': 'Tiempo real conectado',
    'realtime.error': 'Error de conexión en tiempo real',
    'realtime.skipping': 'Tiempo real omitido — configuración faltante',
    'platform.detected': 'Plataforma detectada',
    'platform.cart_updated': 'Carrito actualizado',
    'widget.loading': 'Cargando widget de chat…',
    'widget.error': 'No se pudo cargar el widget',
  },
  en: {
    'consent.required': 'Consent required. Call chat.setConsent(true).',
    'consent.blocked': 'Tracking blocked — consent not granted',
    'init.error': 'SDK initialization error',
    'init.failed': 'Init failed',
    'realtime.connected': 'Realtime connected',
    'realtime.error': 'Realtime connection error',
    'realtime.skipping': 'Realtime skipped — config missing',
    'platform.detected': 'Platform detected',
    'platform.cart_updated': 'Cart updated',
    'widget.loading': 'Loading chat widget…',
    'widget.error': 'Could not load widget',
  },
  pt: {
    'consent.required': 'Consentimento necessário. Chame chat.setConsent(true).',
    'consent.blocked': 'Rastreamento bloqueado — consentimento não concedido',
    'init.error': 'Erro ao inicializar o SDK',
    'init.failed': 'Falha na inicialização',
    'realtime.connected': 'Tempo real conectado',
    'realtime.error': 'Erro de conexão em tempo real',
    'realtime.skipping': 'Tempo real ignorado — configuração ausente',
    'platform.detected': 'Plataforma detectada',
    'platform.cart_updated': 'Carrinho atualizado',
    'widget.loading': 'Carregando widget…',
    'widget.error': 'Não foi possível carregar o widget',
  },
  fr: {
    'consent.required': 'Consentement requis. Appelez chat.setConsent(true).',
    'consent.blocked': 'Suivi bloqué — consentement non accordé',
    'init.error': 'Erreur d\'initialisation du SDK',
    'init.failed': 'Échec de l\'initialisation',
    'realtime.connected': 'Temps réel connecté',
    'realtime.error': 'Erreur de connexion en temps réel',
    'realtime.skipping': 'Temps réel ignoré — configuration manquante',
    'platform.detected': 'Plateforme détectée',
    'platform.cart_updated': 'Panier mis à jour',
    'widget.loading': 'Chargement du widget…',
    'widget.error': 'Impossible de charger le widget',
  },
};

let currentLocale: Locale = 'es';

export function setLocale(locale: string): void {
  const normalized = locale.toLowerCase().split('-')[0];
  if (normalized in messages) {
    currentLocale = normalized as Locale;
  }
}

export function detectLocale(): void {
  const browserLang = navigator.language || 'es';
  setLocale(browserLang);
}

export function t(key: string, fallback?: string): string {
  return messages[currentLocale]?.[key] ?? messages.es[key] ?? fallback ?? key;
}

export function getCurrentLocale(): Locale {
  return currentLocale;
}
