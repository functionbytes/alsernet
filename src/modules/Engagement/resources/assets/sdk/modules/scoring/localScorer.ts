import type { TriggerCondition, TriggerConditionGroup } from '../../types';
import { getContext } from '../context/context';

export interface LocalState {
  timeOnPageSeconds: number;
  scrollDepthPercent: number;
  score: number;
  segment: 'cold' | 'warm' | 'hot';
}

const state: LocalState = {
  timeOnPageSeconds: 0,
  scrollDepthPercent: 0,
  score: 0,
  segment: 'cold',
};

let pageStartedAt = Date.now();
let scrollTimer: ReturnType<typeof setInterval> | null = null;

export function initLocalScorer(): void {
  pageStartedAt = Date.now();

  window.addEventListener('scroll', updateScroll, { passive: true });

  scrollTimer = setInterval(() => {
    state.timeOnPageSeconds = Math.round((Date.now() - pageStartedAt) / 1000);
  }, 1000);
}

function updateScroll(): void {
  const el = document.documentElement;
  const scrolled = el.scrollTop + el.clientHeight;
  const total = el.scrollHeight;
  state.scrollDepthPercent = Math.round((scrolled / total) * 100);
}

export function updateScore(serverScore: number, serverSegment: 'cold' | 'warm' | 'hot'): void {
  state.score = serverScore;
  state.segment = serverSegment;
}

export function getLocalState(): LocalState {
  return { ...state };
}

export function evaluateConditionGroup(group: TriggerConditionGroup): boolean {
  const { operator, rules } = group;
  const results = rules.map((r) => evaluateCondition(r));
  return operator === 'OR' ? results.some(Boolean) : results.every(Boolean);
}

function evaluateCondition(rule: TriggerCondition): boolean {
  const { type, operator, value, key } = rule;
  const context = getContext();

  switch (type) {
    case 'time_on_page':
      return compare(state.timeOnPageSeconds, operator, value as number);
    case 'scroll':
      return compare(state.scrollDepthPercent, operator, value as number);
    case 'score':
      return compare(state.score, operator, value as number);
    case 'segment':
      return state.segment === value;
    case 'url':
      return compare(window.location.href, operator, value as string);
    case 'context':
      return key ? compare(context[key] ?? 0, operator, value) : false;
    default:
      return false;
  }
}

function compare(actual: unknown, op: string, expected: unknown): boolean {
  switch (op) {
    case '>=': return (actual as number) >= (expected as number);
    case '>':  return (actual as number) > (expected as number);
    case '<=': return (actual as number) <= (expected as number);
    case '<':  return (actual as number) < (expected as number);
    case '==': return actual == expected;
    case '!=': return actual != expected;
    case 'contains':
      return String(actual).includes(String(expected));
    default:
      return false;
  }
}
