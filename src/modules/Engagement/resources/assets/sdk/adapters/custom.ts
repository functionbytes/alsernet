import { BaseAdapter } from './base';
import type { CartSnapshot, CustomerSnapshot, PlatformName, ProductSnapshot } from '../types';

interface CustomGlobal {
  cart?: CartSnapshot;
  customer?: CustomerSnapshot;
  product?: ProductSnapshot;
}

declare global {
  interface Window {
    __alsernet_chat?: CustomGlobal;
  }
}

/**
 * Custom adapter — fallback when no e-commerce platform is detected.
 * Reads from window.__alsernet_chat which the merchant sets manually
 * via chat.platform.set({ cart, customer, product }).
 */
export class CustomAdapter extends BaseAdapter {
  readonly name: PlatformName = 'custom';

  private listeners: Array<(kind: 'cart' | 'product' | 'customer') => void> = [];

  detect(): boolean {
    return true; // always last in the priority chain — fallback
  }

  getCart(): CartSnapshot | null {
    return window.__alsernet_chat?.cart ?? null;
  }

  getCustomer(): CustomerSnapshot | null {
    return window.__alsernet_chat?.customer ?? null;
  }

  getProduct(): ProductSnapshot | null {
    return window.__alsernet_chat?.product ?? null;
  }

  bindHooks(onChange: (kind: 'cart' | 'product' | 'customer') => void): void {
    this.listeners.push(onChange);
  }

  /**
   * Public setter — called by chat.platform.set(...) from merchant code.
   */
  set(data: Partial<CustomGlobal>): void {
    window.__alsernet_chat = {
      ...(window.__alsernet_chat ?? {}),
      ...data,
    };

    if (data.cart)     this.listeners.forEach((cb) => cb('cart'));
    if (data.product)  this.listeners.forEach((cb) => cb('product'));
    if (data.customer) this.listeners.forEach((cb) => cb('customer'));
  }
}

export const customAdapterInstance = new CustomAdapter();
