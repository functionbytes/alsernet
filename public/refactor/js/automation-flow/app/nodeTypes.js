/**
 * AcelleNodeTypes — Acelle-specific config plugged into FlowEditor.
 *
 * This is the ONE place where Acelle's domain (TriggerKey, ConditionKind, …)
 * meets the generic flow library. Library code never imports this file.
 *
 * Shape: { [type]: { icon, label, placeholderTitle?, summarize?, fields(data, ctx) } }
 *
 * `ctx` is supplied by Acelle blade (server-fetched lists like emails, mail-lists,
 * subscriber list fields).
 */
(function (global) {
  'use strict';

  const TIME_DEFAULT = '09:00';

  const WEEKDAYS = [
    { value: '0', label: 'Sun' }, { value: '1', label: 'Mon' }, { value: '2', label: 'Tue' },
    { value: '3', label: 'Wed' }, { value: '4', label: 'Thu' }, { value: '5', label: 'Fri' },
    { value: '6', label: 'Sat' },
  ];

  const TRIGGER_KEYS = [
    { value: 'welcome-new-subscriber', label: 'New subscriber' },
    { value: 'say-goodbye-subscriber', label: 'Goodbye (unsubscribe)' },
    { value: 'say-happy-birthday',     label: 'Birthday' },
    { value: 'subscriber-added-date',  label: 'Subscription anniversary' },
    { value: 'specific-date',          label: 'Specific date' },
    { value: 'api-3-0',                label: 'API call' },
    { value: 'weekly-recurring',       label: 'Weekly recurring' },
    { value: 'monthly-recurring',      label: 'Monthly recurring' },
    { value: 'tag-based',              label: 'Tag added' },
    { value: 'remove-tag',             label: 'Tag removed' },
    { value: 'attribute-update',       label: 'Subscriber field changed' },
    { value: 'woo-abandoned-cart',     label: 'Abandoned cart (WooCommerce)' },
  ];

  // ---------- field factories ----------

  function fieldFieldUid(d, ctx, name = 'fieldUid', label = 'Subscriber field') {
    return {
      name, label, type: 'select',
      options: (ctx.listFields || []).map(f => ({ value: f.value, label: `${f.label} (${f.type})` })),
      value: d[name] || '',
      placeholder: '— Select field —',
    };
  }
  function fieldTime(d, name = 'at', label = 'Time of day') {
    return { name, label, type: 'time', value: d[name] || TIME_DEFAULT };
  }
  function fieldDaysBefore(d) {
    return {
      name: 'before', label: 'When', type: 'select',
      options: [
        { value: 'P0D',  label: 'On the day'    },
        { value: 'P1D',  label: '1 day before'  },
        { value: 'P2D',  label: '2 days before' },
        { value: 'P3D',  label: '3 days before' },
        { value: 'P7D',  label: '1 week before' },
        { value: 'P14D', label: '2 weeks before'},
        { value: 'P30D', label: '1 month before'},
      ],
      value: d.before || 'P0D',
    };
  }

  const TRIGGER_KEY_FORMS = {
    'welcome-new-subscriber': () => [],
    'say-goodbye-subscriber': () => [],
    'api-3-0':                () => [],
    'say-happy-birthday':     (d, ctx) => [fieldFieldUid(d, ctx), fieldDaysBefore(d), fieldTime(d)],
    'subscriber-added-date':  (d, ctx) => [fieldFieldUid(d, ctx), fieldDaysBefore(d), fieldTime(d)],
    'specific-date':          (d) => [
      { name: 'date', label: 'Date', type: 'date', value: d.date || '' },
      fieldTime(d),
    ],
    'weekly-recurring': (d) => [
      { name: 'daysOfWeek', label: 'Days of week', type: 'multiselect', options: WEEKDAYS,
        value: (d.daysOfWeek || []).map(String) },
      fieldTime(d),
    ],
    'monthly-recurring': (d) => [
      { name: 'daysOfMonth', label: 'Days of month', type: 'multiselect',
        options: Array.from({ length: 31 }, (_, i) => ({ value: String(i + 1), label: String(i + 1) })),
        value: (d.daysOfMonth || []).map(String) },
      fieldTime(d),
    ],
    'tag-based':         (d) => [{ name: 'tags', label: 'Tags (comma-separated)', type: 'tags', value: d.tags || [] }],
    'remove-tag':        (d) => [{ name: 'tags', label: 'Tags (comma-separated)', type: 'tags', value: d.tags || [] }],
    'attribute-update':  (d, ctx) => [
      fieldFieldUid(d, ctx, 'fieldUid', 'Subscriber field'),
      { name: 'operator', label: 'Operator', type: 'select',
        options: [
          { value: 'equals',     label: '=  equals'     },
          { value: 'not_equals', label: '≠ not equals'  },
          { value: 'contains',   label: 'contains'      },
        ],
        value: d.operator || 'equals' },
      { name: 'value', label: 'Value', type: 'text', value: d.value || '' },
    ],
    'woo-abandoned-cart': (d) => [
      { name: 'connectUrl', label: 'Connect URL', type: 'text', value: d.connectUrl || '',
        placeholder: 'https://shop.example.com/wp-json/...' },
    ],
  };

  function operationKindFields(d, ctx) {
    switch (d.kind) {
      case 'tag': case 'remove_tag':
        return [{ name: 'tags', label: 'Tags (comma-separated)', type: 'tags', value: d.tags || [] }];
      case 'copy': case 'move':
        return [
          { name: 'targetListUid', label: 'Target list', type: 'select',
            options: (ctx.mailLists || []).map(l => ({ value: l.value, label: l.label })),
            value: d.targetListUid || '', placeholder: '— Select list —',
            help: (ctx.mailLists || []).length === 0 ? 'No other lists available.' : null },
        ];
      case 'update':
        return [
          { name: 'fieldUid', label: 'Subscriber field', type: 'select',
            options: (ctx.listFields || []).map(f => ({ value: f.value, label: `${f.label} (${f.type})` })),
            value: d.fieldUid || '', placeholder: '— Select field —' },
          { name: 'value', label: 'New value', type: 'text', value: d.value || '' },
        ];
      default: return [];
    }
  }

  // ---------- summarize (shown as subtitle on diagram cards) ----------

  function humanizeDuration(iso) {
    const m = String(iso || '').match(/^P(?:(\d+)Y)?(?:(\d+)M)?(?:(\d+)W)?(?:(\d+)D)?(?:T(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?)?$/);
    if (!m) return iso || '';
    const [, y, mo, w, d, h, mi, s] = m;
    const out = [];
    if (y)  out.push(y + 'y');
    if (mo) out.push(mo + 'mo');
    if (w)  out.push(w + 'w');
    if (d)  out.push(d + 'd');
    if (h)  out.push(h + 'h');
    if (mi) out.push(mi + 'm');
    if (s)  out.push(s + 's');
    return out.join(' ') || '0s';
  }

  // ---------- master nodeTypes config ----------

  const NODE_TYPES = {
    trigger: {
      icon: 'alt_route',
      label: 'TRIGGER',
      placeholderTitle: 'Choose a trigger',
      summarize: (d) => d.key || '',
      insertable: false,  // the trigger is fixed root, never user-inserted
      description: 'Defines when this automation starts.',
      fields: (d, ctx) => [
        { name: 'title', label: 'Display name', type: 'text', value: d.title || '' },
        { name: 'key',   label: 'Trigger type', type: 'select',
          options: TRIGGER_KEYS,
          value: d.key || '',
          placeholder: '— Select trigger type —',
          onChange: 'rerender' },
        ...(TRIGGER_KEY_FORMS[d.key] ? TRIGGER_KEY_FORMS[d.key](d, ctx) : []),
      ],
    },

    send: {
      icon: 'forward_to_inbox',
      label: 'SEND EMAIL',
      summarize: (d) => d.emailSubject || d.emailUid || '',
      fields: (d, ctx) => [
        { name: 'title', label: 'Display name', type: 'text', value: d.title || '' },
        { name: 'emailUid', label: 'Email', type: 'select',
          options: ctx.emails || [],
          value: d.emailUid || '',
          placeholder: '— Select email —',
          help: (ctx.emails || []).length === 0
            ? 'No emails yet — create one for this automation in the email composer.'
            : null },
      ],
    },

    wait: {
      icon: 'timer',
      label: 'WAIT',
      summarize: (d) => d.duration ? humanizeDuration(d.duration) : '',
      fields: (d) => [
        { name: 'title',    label: 'Display name', type: 'text',     value: d.title || '' },
        { name: 'duration', label: 'Wait for',     type: 'duration', value: d.duration || 'PT1H' },
      ],
    },

    wait_until: {
      icon: 'event',
      label: 'WAIT UNTIL',
      summarize: (d) => d.until || '',
      fields: (d) => [
        { name: 'title', label: 'Display name', type: 'text', value: d.title || '' },
        { name: 'until', label: 'Until',        type: 'datetime-local', value: d.until || '' },
      ],
    },

    condition: {
      icon: 'call_split',
      label: 'CONDITION',
      summarize: (d) => {
        const k = d.kind || 'check';
        const t = d.timeout ? ' · timeout ' + humanizeDuration(d.timeout) : '';
        return k + t;
      },
      description: 'Splits the flow into YES / NO branches based on email engagement.',
      fields: (d, ctx) => [
        { name: 'title', label: 'Display name', type: 'text', value: d.title || '' },
        { name: 'kind',  label: 'Check', type: 'select',
          options: [{ value: 'open', label: 'Email opened' }, { value: 'click', label: 'Link clicked' }],
          value: d.kind || 'open' },
        { name: 'emailUid', label: 'Email', type: 'select',
          options: ctx.emails || [],
          value: d.emailUid || '', placeholder: '— Select email —',
          help: (ctx.emails || []).length === 0 ? 'No emails yet.' : null },
        { name: 'timeout', label: 'Timeout (when condition not met)', type: 'duration',
          value: d.timeout || 'P1D' },
      ],
    },

    operation: {
      icon: 'checklist_rtl',
      label: 'OPERATION',
      summarize: (d) => {
        const k = (d.kind || '').toUpperCase();
        if (d.tags && d.tags.length) return `${k}: ${d.tags.join(', ')}`;
        if (d.targetListUid)         return `${k} → ${d.targetListUid}`;
        return k;
      },
      fields: (d, ctx) => [
        { name: 'title', label: 'Display name', type: 'text', value: d.title || '' },
        { name: 'kind',  label: 'Operation', type: 'select',
          options: [
            { value: 'tag',        label: 'Add tags'     },
            { value: 'remove_tag', label: 'Remove tags'  },
            { value: 'copy',       label: 'Copy to list' },
            { value: 'move',       label: 'Move to list' },
            { value: 'update',     label: 'Update field' },
          ],
          value: d.kind || 'tag', onChange: 'rerender' },
        ...operationKindFields(d, ctx),
      ],
    },

    webhook: {
      icon: 'webhook',
      label: 'WEBHOOK',
      summarize: (d) => d.url || '',
      fields: (d) => [
        { name: 'title',  label: 'Display name', type: 'text', value: d.title || '' },
        { name: 'url',    label: 'URL', type: 'text', value: d.url || '',
          placeholder: 'https://example.com/hook' },
        { name: 'method', label: 'Method', type: 'select',
          options: [{ value: 'POST', label: 'POST' }, { value: 'GET', label: 'GET' },
                    { value: 'PUT',  label: 'PUT'  }, { value: 'PATCH', label: 'PATCH' }],
          value: d.method || 'POST' },
      ],
    },
  };

  global.AcelleNodeTypes = NODE_TYPES;
})(window);
