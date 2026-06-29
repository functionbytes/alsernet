import { BaseAdapter } from './base';
import type { CartSnapshot, CustomerSnapshot, PlatformName, ProductSnapshot } from '../types';

interface PrestaShopGlobal {
  cart?: {
    products?: Array<{ id_product: string | number; name: string; quantity: number; price: number }>;
    totals?: { total?: { amount: number } };
    currency?: { iso_code: string };
  };
  customer?: {
    id?: number;
    email?: string;
    firstname?: string;
    lastname?: string;
  };
  page?: {
    page_name?: string;
    body_classes?: string[];
    canonical?: string;
  };
  product?: {
    id_product: string | number;
    name: string;
    price_amount: number;
    category?: string;
    cover?: { medium?: { url: string } };
  };
}

declare global {
  interface Window {
    prestashop?: PrestaShopGlobal;
  }
}

export class PrestashopAdapter extends BaseAdapter {
  readonly name: PlatformName = 'prestashop';

  detect(): boolean {
    return typeof window.prestashop === 'object' && window.prestashop !== null;
  }

  getCart(): CartSnapshot | null {
    const ps = window.prestashop;
    if (!ps?.cart) return null;

    const products = (ps.cart.products ?? []).map((p) => ({
      id: String(p.id_product),
      name: p.name,
      quantity: p.quantity,
      price: this.toNumber(p.price),
    }));

    return {
      items: products.reduce((acc, p) => acc + p.quantity, 0),
      value: this.toNumber(ps.cart.totals?.total?.amount),
      currency: ps.cart.currency?.iso_code ?? 'EUR',
      products,
    };
  }

  getCustomer(): CustomerSnapshot | null {
    const c = window.prestashop?.customer;
    if (!c?.email) return null;

    const name = [c.firstname, c.lastname].filter(Boolean).join(' ').trim();

    return {
      id: c.id ? String(c.id) : undefined,
      email: c.email,
      name: name || undefined,
    };
  }

  getProduct(): ProductSnapshot | null {
    const p = window.prestashop?.product;
    if (!p) return null;

    return {
      id: String(p.id_product),
      name: p.name,
      price: this.toNumber(p.price_amount),
      category: p.category,
      imageUrl: p.cover?.medium?.url,
      url: window.location.href,
    };
  }

  bindHooks(onChange: (kind: 'cart' | 'product' | 'customer') => void): void {
    const triggerCart = () => onChange('cart');

    document.addEventListener('updateCart', triggerCart);
    document.addEventListener('updatedCart', triggerCart);
    document.addEventListener('updateProductList', () => onChange('product'));

    if (window.prestashop && typeof (window.prestashop as unknown as { on?: Function }).on === 'function') {
      (window.prestashop as unknown as { on: (event: string, cb: () => void) => void })
        .on('updateCart', triggerCart);
    }
  }
}
