export interface SdkConfig {
  token: string;
  apiUrl: string;
  debug: boolean;
  consent: boolean;
  autoTrack: {
    pageView: boolean;
    sessionLifecycle: boolean;
  };
  batchInterval: number;
  batchSize: number;
}

export interface VisitorIdentity {
  id?: string;
  name?: string;
  email?: string;
  phone?: string;
  attributes?: Record<string, unknown>;
}

export interface TrackingEvent {
  name: string;
  properties?: Record<string, unknown>;
  ts: string;
}

export interface SdkState {
  initialized: boolean;
  consentGranted: boolean;
  visitorId: string | null;
  sessionToken: string | null;
  customerId: number | null;
  score: number;
  segment: 'cold' | 'warm' | 'hot';
  wsChannel: string | null;
}

export interface TriggerRule {
  id: number;
  name: string;
  conditions: TriggerConditionGroup;
  action: TriggerAction;
  priority: number;
  firesPerSession: number;
}

export interface TriggerConditionGroup {
  operator: 'AND' | 'OR';
  rules: TriggerCondition[];
}

export interface TriggerCondition {
  type: 'time_on_page' | 'scroll' | 'url' | 'score' | 'segment' | 'context' | 'event';
  operator: '>=' | '>' | '<=' | '<' | '==' | '!=' | 'contains';
  value: unknown;
  key?: string;
}

export interface TriggerAction {
  type: 'open_chat' | 'show_banner' | 'redirect' | 'callback';
  html?: string;
  selector?: string;
  url?: string;
  name?: string;
  payload?: unknown;
}

export interface PersonalizationRule {
  id: number;
  selector: string;
  conditions: TriggerConditionGroup | null;
  mutation: DomMutation;
}

export interface DomMutation {
  op: 'text' | 'attribute' | 'insert_before' | 'insert_after' | 'class';
  value?: string;
  name?: string;
  html?: string;
  add?: string[];
  remove?: string[];
}

export type SdkEventName =
  | 'ready'
  | 'score:changed'
  | 'trigger:fired'
  | 'recommendations:updated'
  | 'platform:detected'
  | 'platform:cart_updated'
  | 'chat:opened'
  | 'chat:closed'
  | 'error';

export type PlatformName = 'prestashop' | 'shopify' | 'woocommerce' | 'custom' | 'unknown';

export interface CartSnapshot {
  items: number;
  value: number;
  currency: string;
  products?: Array<{ id: string; name: string; quantity: number; price: number }>;
}

export interface CustomerSnapshot {
  id?: string;
  email?: string;
  name?: string;
  phone?: string;
  attributes?: Record<string, unknown>;
}

export interface ProductSnapshot {
  id: string;
  name: string;
  price: number;
  currency?: string;
  category?: string;
  imageUrl?: string;
  url?: string;
}

export interface PlatformAdapter {
  readonly name: PlatformName;
  detect(): boolean;
  getCart(): CartSnapshot | null;
  getCustomer(): CustomerSnapshot | null;
  getProduct(): ProductSnapshot | null;
  bindHooks(onChange: (kind: 'cart' | 'product' | 'customer') => void): void;
}

export type EventHandler = (payload: unknown) => void;
