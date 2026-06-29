import type { PlatformAdapter, PlatformName } from '../types';
import { PrestashopAdapter } from '../adapters/prestashop';
import { ShopifyAdapter } from '../adapters/shopify';
import { WooCommerceAdapter } from '../adapters/woocommerce';
import { customAdapterInstance } from '../adapters/custom';
import { logger } from '../utils/logger';

/**
 * Adapters tried in priority order. Custom is always last (fallback).
 */
const REGISTRY: PlatformAdapter[] = [
  new PrestashopAdapter(),
  new ShopifyAdapter(),
  new WooCommerceAdapter(),
  customAdapterInstance,
];

let active: PlatformAdapter | null = null;

export function detectPlatform(): PlatformAdapter {
  if (active) return active;

  for (const adapter of REGISTRY) {
    try {
      if (adapter.detect()) {
        active = adapter;
        logger.log('platform detected:', adapter.name);
        return adapter;
      }
    } catch (err) {
      logger.warn(`adapter ${adapter.name} detect failed:`, err);
    }
  }

  active = customAdapterInstance;
  return active;
}

export function getActiveAdapter(): PlatformAdapter | null {
  return active;
}

export function getActivePlatformName(): PlatformName {
  return active?.name ?? 'unknown';
}

/**
 * Public API exposed via chat.platform.* — merchant can manually
 * override or feed data when auto-detection isn't enough.
 */
export const platformApi = {
  set: (data: Parameters<typeof customAdapterInstance.set>[0]): void => {
    customAdapterInstance.set(data);
  },
  current: (): PlatformName => getActivePlatformName(),
  cart: () => active?.getCart() ?? null,
  customer: () => active?.getCustomer() ?? null,
  product: () => active?.getProduct() ?? null,
};
