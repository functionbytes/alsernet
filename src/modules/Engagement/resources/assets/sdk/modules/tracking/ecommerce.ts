import { transport } from '../../services/transport';
import { getActivePlatformName } from '../../core/platformDetector';

export function track(eventName: string, properties: Record<string, unknown> = {}): void {
  const platform = getActivePlatformName();
  const enriched = platform && platform !== 'unknown' && !('platform' in properties)
    ? { ...properties, platform }
    : properties;

  transport.push({
    name: eventName,
    properties: enriched,
    ts: new Date().toISOString(),
  });
}

export function trackProductView(product: {
  id: string;
  name?: string;
  price?: number;
  currency?: string;
  category?: string;
  imageUrl?: string;
}): void {
  track('product_view', product as Record<string, unknown>);
}

export function trackAddToCart(item: {
  id: string;
  price: number;
  currency: string;
  quantity: number;
}): void {
  track('add_to_cart', item as Record<string, unknown>);
}

export function trackRemoveFromCart(item: { id: string; quantity: number }): void {
  track('remove_from_cart', item as Record<string, unknown>);
}

export function trackCheckoutStart(data: {
  cartValue: number;
  itemsCount: number;
  currency: string;
}): void {
  track('checkout_start', data as Record<string, unknown>);
}

export function trackPurchase(data: {
  orderId: string;
  total: number;
  currency: string;
  items: Array<{ id: string; qty: number; price: number }>;
}): void {
  track('purchase', data as Record<string, unknown>);
}
