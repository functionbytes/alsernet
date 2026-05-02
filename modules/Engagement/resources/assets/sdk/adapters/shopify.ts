import { BaseAdapter } from './base';
import type { CartSnapshot, CustomerSnapshot, PlatformName, ProductSnapshot } from '../types';

interface ShopifyGlobal {
  shop?: string;
  currency?: { active: string };
  customer?: { id?: number; email?: string; first_name?: string; last_name?: string };
}

interface ShopifyAnalytics {
  meta?: {
    page?: { pageType?: string };
    product?: { id: number; vendor: string; type: string };
    currency?: string;
  };
}

declare global {
  interface Window {
    Shopify?: ShopifyGlobal;
    ShopifyAnalytics?: ShopifyAnalytics;
    __st?: { p?: string };
    meta?: unknown;
  }
}

export class ShopifyAdapter extends BaseAdapter {
  readonly name: PlatformName = 'shopify';

  private cachedCart: CartSnapshot | null = null;

  detect(): boolean {
    return typeof window.Shopify === 'object' && window.Shopify !== null;
  }

  /**
   * Cart is fetched async via /cart.js — return cached snapshot immediately
   * and refresh in background.
   */
  getCart(): CartSnapshot | null {
    void this.fetchCartAsync();
    return this.cachedCart;
  }

  private async fetchCartAsync(): Promise<void> {
    try {
      const res = await fetch('/cart.js', { credentials: 'same-origin', headers: { Accept: 'application/json' } });
      if (!res.ok) return;
      const cart = await res.json() as {
        item_count: number;
        total_price: number;
        currency: string;
        items: Array<{ id: number; product_id: number; product_title: string; quantity: number; price: number }>;
      };
      this.cachedCart = {
        items: cart.item_count,
        value: cart.total_price / 100,
        currency: cart.currency,
        products: cart.items.map((i) => ({
          id: String(i.product_id),
          name: i.product_title,
          quantity: i.quantity,
          price: i.price / 100,
        })),
      };
    } catch {
      // ignore
    }
  }

  getCustomer(): CustomerSnapshot | null {
    const c = window.Shopify?.customer;
    if (!c?.email) return null;
    const name = [c.first_name, c.last_name].filter(Boolean).join(' ').trim();
    return {
      id: c.id ? String(c.id) : undefined,
      email: c.email,
      name: name || undefined,
    };
  }

  getProduct(): ProductSnapshot | null {
    const meta = window.ShopifyAnalytics?.meta;
    if (!meta?.product) return null;
    return {
      id: String(meta.product.id),
      name: document.title,
      price: 0,
      currency: meta.currency,
      category: meta.product.type,
      url: window.location.href,
    };
  }

  bindHooks(onChange: (kind: 'cart' | 'product' | 'customer') => void): void {
    document.addEventListener('cart:updated', () => onChange('cart'));
    document.addEventListener('cart:added', () => onChange('cart'));

    // Intercept Shopify cart endpoints
    const originalFetch = window.fetch;
    window.fetch = async (...args) => {
      const url = typeof args[0] === 'string' ? args[0] : (args[0] as Request).url;
      const response = await originalFetch.apply(window, args);
      if (url.match(/\/cart\/(add|update|change|clear)/)) {
        await this.fetchCartAsync();
        onChange('cart');
      }
      return response;
    };
  }
}
