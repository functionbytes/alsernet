import { BaseAdapter } from './base';
import type { CartSnapshot, CustomerSnapshot, PlatformName, ProductSnapshot } from '../types';

interface WooCommerceGlobal {
  storeApiNonce?: string;
  cart?: {
    items: Array<{ id: number; name: string; quantity: number; prices: { price: string } }>;
    items_count: number;
    totals: { total_price: string; currency_code: string };
  };
  customer?: { id: number; email: string; first_name?: string; last_name?: string };
  product?: { id: number; name: string; price: string; category?: string };
}

declare global {
  interface Window {
    wc_cart_fragments_params?: { ajax_url: string };
    woocommerce_params?: { ajax_url: string };
    __alsernet_woo?: WooCommerceGlobal;
  }
}

export class WooCommerceAdapter extends BaseAdapter {
  readonly name: PlatformName = 'woocommerce';

  private cachedCart: CartSnapshot | null = null;

  detect(): boolean {
    if (window.__alsernet_woo) return true;
    if (typeof window.wc_cart_fragments_params === 'object') return true;
    if (typeof window.woocommerce_params === 'object') return true;
    return document.body?.classList?.contains('woocommerce') ?? false;
  }

  getCart(): CartSnapshot | null {
    const exposed = window.__alsernet_woo?.cart;
    if (exposed) {
      return this.fromWoo(exposed);
    }

    void this.fetchCartAsync();
    return this.cachedCart;
  }

  private fromWoo(cart: NonNullable<WooCommerceGlobal['cart']>): CartSnapshot {
    return {
      items: cart.items_count,
      value: this.toNumber(cart.totals.total_price) / 100,
      currency: cart.totals.currency_code,
      products: cart.items.map((i) => ({
        id: String(i.id),
        name: i.name,
        quantity: i.quantity,
        price: this.toNumber(i.prices?.price) / 100,
      })),
    };
  }

  private async fetchCartAsync(): Promise<void> {
    try {
      const res = await fetch('/wp-json/wc/store/v1/cart', {
        credentials: 'same-origin',
        headers: { Accept: 'application/json' },
      });
      if (!res.ok) return;
      const cart = await res.json() as NonNullable<WooCommerceGlobal['cart']>;
      this.cachedCart = this.fromWoo(cart);
    } catch {
      // ignore
    }
  }

  getCustomer(): CustomerSnapshot | null {
    const c = window.__alsernet_woo?.customer;
    if (!c?.email) return null;
    const name = [c.first_name, c.last_name].filter(Boolean).join(' ').trim();
    return {
      id: String(c.id),
      email: c.email,
      name: name || undefined,
    };
  }

  getProduct(): ProductSnapshot | null {
    const p = window.__alsernet_woo?.product;
    if (!p) return null;
    return {
      id: String(p.id),
      name: p.name,
      price: this.toNumber(p.price),
      category: p.category,
      url: window.location.href,
    };
  }

  bindHooks(onChange: (kind: 'cart' | 'product' | 'customer') => void): void {
    // jQuery events emitted by WC
    if ((window as unknown as { jQuery?: unknown }).jQuery) {
      const $ = (window as unknown as { jQuery: { (selector: string | Document): { on: (ev: string, cb: () => void) => void } } }).jQuery;
      $(document.body).on('added_to_cart', () => onChange('cart'));
      $(document.body).on('removed_from_cart', () => onChange('cart'));
      $(document.body).on('updated_cart_totals', () => onChange('cart'));
      $(document.body).on('wc_fragments_refreshed', () => onChange('cart'));
    }

    document.body?.addEventListener('added_to_cart', () => onChange('cart'));
  }
}
