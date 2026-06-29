import type { EventHandler, SdkEventName } from '../types';

const listeners = new Map<string, Set<EventHandler>>();

export function on(event: SdkEventName | string, handler: EventHandler): void {
  if (!listeners.has(event)) {
    listeners.set(event, new Set());
  }
  listeners.get(event)!.add(handler);
}

export function off(event: string, handler: EventHandler): void {
  listeners.get(event)?.delete(handler);
}

export function emit(event: string, payload: unknown = {}): void {
  listeners.get(event)?.forEach((handler) => {
    try {
      handler(payload);
    } catch {
      // ignore handler errors
    }
  });
}
