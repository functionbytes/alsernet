import type { PersonalizationRule, DomMutation } from '../../types';
import { evaluateConditionGroup } from '../scoring/localScorer';
import { logger } from '../../utils/logger';
import { debounce } from '../../utils/debounce';

let rules: PersonalizationRule[] = [];

export function loadPersonalizationRules(incoming: PersonalizationRule[]): void {
  rules = incoming;
  applyAll();

  // Re-apply on DOM mutations (SPAs)
  const observer = new MutationObserver(
    debounce(() => applyAll(), 300),
  );
  observer.observe(document.body, { childList: true, subtree: true });
}

function applyAll(): void {
  for (const rule of rules) {
    if (rule.conditions && !evaluateConditionGroup(rule.conditions)) continue;

    const targets = document.querySelectorAll(rule.selector);
    targets.forEach((el) => applyMutation(el as HTMLElement, rule.mutation));
  }
}

function applyMutation(el: HTMLElement, mutation: DomMutation): void {
  try {
    switch (mutation.op) {
      case 'text':
        if (mutation.value !== undefined) el.textContent = mutation.value;
        break;

      case 'attribute':
        if (mutation.name && mutation.value !== undefined) {
          el.setAttribute(mutation.name, mutation.value);
        }
        break;

      case 'insert_before':
        if (mutation.html) {
          const div = document.createElement('div');
          div.innerHTML = mutation.html;
          el.insertAdjacentElement('beforebegin', div.firstElementChild as Element);
        }
        break;

      case 'insert_after':
        if (mutation.html) {
          const div = document.createElement('div');
          div.innerHTML = mutation.html;
          el.insertAdjacentElement('afterend', div.firstElementChild as Element);
        }
        break;

      case 'class':
        if (mutation.add) el.classList.add(...mutation.add);
        if (mutation.remove) el.classList.remove(...mutation.remove);
        break;
    }
  } catch (err) {
    logger.warn('domMutator error on', el, err);
  }
}
