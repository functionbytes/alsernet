import type { TriggerRule } from '../../types';
import { evaluateConditionGroup } from '../scoring/localScorer';
import { emit } from '../../core/eventBus';
import { logger } from '../../utils/logger';

let rules: TriggerRule[] = [];
const firedCounts = new Map<number, number>();
let checkTimer: ReturnType<typeof setInterval> | null = null;

export function loadTriggerRules(incoming: TriggerRule[]): void {
  rules = incoming;
  firedCounts.clear();
}

export function startTriggerEngine(): void {
  if (checkTimer) clearInterval(checkTimer);
  checkTimer = setInterval(checkTriggers, 1000);
}

function checkTriggers(): void {
  for (const rule of rules) {
    const fired = firedCounts.get(rule.id) ?? 0;
    if (fired >= rule.firesPerSession) continue;

    if (evaluateConditionGroup(rule.conditions)) {
      firedCounts.set(rule.id, fired + 1);
      logger.log('trigger fired:', rule.name, rule.action);
      emit('trigger:fired', { ruleId: rule.id, action: rule.action });
      handleAction(rule);
    }
  }
}

function handleAction(rule: TriggerRule): void {
  const { action } = rule;

  switch (action.type) {
    case 'open_chat':
      import('../chat/widgetBridge').then(({ openWidget }) => openWidget());
      break;

    case 'show_banner':
      if (action.html && action.selector) {
        const target = document.querySelector(action.selector);
        if (target) {
          const div = document.createElement('div');
          div.innerHTML = action.html;
          target.insertAdjacentElement('beforebegin', div.firstElementChild as Element);
        }
      }
      break;

    case 'redirect':
      if (action.url) window.location.href = action.url;
      break;

    case 'callback': {
      const cb = action.name ? (window as unknown as Record<string, unknown>)[action.name] : null;
      if (typeof cb === 'function') cb(action.payload);
      break;
    }
  }
}
