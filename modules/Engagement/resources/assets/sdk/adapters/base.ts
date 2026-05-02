import type {
  PlatformAdapter,
  PlatformName,
  CartSnapshot,
  CustomerSnapshot,
  ProductSnapshot,
} from '../types';

export abstract class BaseAdapter implements PlatformAdapter {
  abstract readonly name: PlatformName;

  abstract detect(): boolean;

  getCart(): CartSnapshot | null {
    return null;
  }

  getCustomer(): CustomerSnapshot | null {
    return null;
  }

  getProduct(): ProductSnapshot | null {
    return null;
  }

  bindHooks(_onChange: (kind: 'cart' | 'product' | 'customer') => void): void {
    // override in subclasses
  }

  protected readGlobal<T>(path: string): T | null {
    const parts = path.split('.');
    let cur: unknown = window;
    for (const p of parts) {
      if (cur && typeof cur === 'object' && p in (cur as Record<string, unknown>)) {
        cur = (cur as Record<string, unknown>)[p];
      } else {
        return null;
      }
    }
    return (cur ?? null) as T | null;
  }

  protected toNumber(v: unknown): number {
    if (typeof v === 'number') return v;
    if (typeof v === 'string') return parseFloat(v.replace(/[^\d.-]/g, '')) || 0;
    return 0;
  }
}
