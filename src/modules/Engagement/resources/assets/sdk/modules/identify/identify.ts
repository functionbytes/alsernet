import type { VisitorIdentity } from '../../types';
import { api } from '../../services/apiClient';
import { setCustomerId } from '../../core/session';
import { emit } from '../../core/eventBus';
import { logger } from '../../utils/logger';

interface IdentifyResponse {
  success: boolean;
  data: { customerId: number; linked: boolean; score: number; segment: string };
}

export async function identify(user: VisitorIdentity): Promise<void> {
  try {
    const res = await api.post<IdentifyResponse>('sdk/identify', user);
    if (res.success) {
      setCustomerId(res.data.customerId);
      emit('score:changed', {
        score: res.data.score,
        segment: res.data.segment,
        previousSegment: null,
      });
      logger.log('identified customer', res.data.customerId);
    }
  } catch (err) {
    logger.error('identify failed', err);
  }
}
