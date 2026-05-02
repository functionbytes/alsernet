import Pusher from 'pusher-js';
import { updateScore } from '../modules/scoring/localScorer';
import { emit } from '../core/eventBus';
import { logger } from '../utils/logger';

interface RealtimeConfig {
  key: string;
  host: string;
  port: number;
  scheme: string;
}

let pusher: Pusher | null = null;
let subscribed = false;

export function initRealtime(wsChannel: string, config?: RealtimeConfig | null): void {
  if (subscribed) return;

  if (!config?.key || !config?.host) {
    logger.warn('realtime: missing config from backend — skipping WebSocket');
    return;
  }

  try {
    pusher = new Pusher(config.key, {
      wsHost: config.host,
      wsPort: config.port,
      wssPort: config.port,
      forceTLS: config.scheme === 'https',
      enabledTransports: ['ws', 'wss'],
      disableStats: true,
      cluster: 'mt1',
    });

    const channel = pusher.subscribe(wsChannel);

    channel.bind('ScoreThresholdCrossed', (data: {
      score: number;
      segment: 'cold' | 'warm' | 'hot';
      previousSegment: 'cold' | 'warm' | 'hot';
    }) => {
      updateScore(data.score, data.segment);
      emit('score:changed', data);
      logger.log('score updated via realtime:', data.score, data.segment);
    });

    channel.bind('TriggerFired', (data: {
      ruleId: number;
      action: { type: string; html?: string; selector?: string; url?: string; name?: string; payload?: unknown };
    }) => {
      emit('trigger:fired', data);
      logger.log('trigger fired via realtime:', data.ruleId, data.action.type);
    });

    pusher.connection.bind('connected', () => {
      logger.log('realtime connected');
    });

    pusher.connection.bind('error', (err: unknown) => {
      logger.warn('realtime connection error:', err);
    });

    subscribed = true;
  } catch (err) {
    logger.warn('realtime init failed — continuing without WebSocket:', err);
  }
}

export function disconnectRealtime(): void {
  if (pusher) {
    pusher.disconnect();
    pusher = null;
    subscribed = false;
  }
}
