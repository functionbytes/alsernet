import { api } from '../../services/apiClient';
import { emit } from '../../core/eventBus';
import { logger } from '../../utils/logger';
import { debounce } from '../../utils/debounce';

let _context: Record<string, unknown> = {};

interface ContextResponse {
  success: boolean;
  data: { context: Record<string, unknown>; score: number; segment: string };
}

const syncDebounced = debounce(async function syncContext() {
  try {
    const res = await api.post<ContextResponse>('sdk/context', { context: _context });
    if (res.success) {
      emit('score:changed', { score: res.data.score, segment: res.data.segment });
    }
  } catch (err) {
    logger.error('context sync failed', err);
  }
}, 500);

export function setContext(patch: Record<string, unknown>): void {
  for (const [k, v] of Object.entries(patch)) {
    if (v === null) {
      delete _context[k];
    } else {
      _context[k] = v;
    }
  }
  logger.log('context updated:', _context);
  syncDebounced();
}

export function getContext(): Record<string, unknown> {
  return { ..._context };
}
