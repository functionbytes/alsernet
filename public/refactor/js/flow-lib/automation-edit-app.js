/**
 * Automation Edit App — Acelle business layer for /automations/{uid}/edit.
 *
 * Mirrors demo-app.js (TYPES, factories, rendering callbacks, Tree+Renderer
 * bootstrapping) but every mutation goes through REST. Per Rule 4 the server
 * returns the full flow snapshot; client calls tree.load(flow) → renderer.draw(tree).
 * No partial DOM updates, no client-side state caching of node data.
 *
 * Sidebar replaces the demo's prompt() hack — each node type has a dedicated
 * config form. Click node → open sidebar → edit → Save → PATCH → re-render.
 *
 * Library (tree.js / renderer.js) stays opaque about types, server, or Acelle.
 */
(function (global) {
  'use strict';

  // ─── Types ─────────────────────────────────────────────────────────────

  const TYPES = {
    trigger:    { icon: 'alt_route',        label: 'TRIGGER',    outputs: [null]        },
    send:       { icon: 'forward_to_inbox', label: 'SEND EMAIL', outputs: [null]        },
    wait:       { icon: 'timer',            label: 'WAIT',       outputs: [null]        },
    // wait_until is hidden from the add-modal picker — users get to it via the
    // Wait sidebar's mode toggle. The entry must remain in TYPES so existing
    // wait_until nodes already on the canvas still render their card label.
    wait_until: { icon: 'event',            label: 'WAIT UNTIL', outputs: [null], hiddenFromAdd: true },
    condition:  { icon: 'call_split',       label: 'CONDITION',  outputs: ['yes', 'no'] },
    operation:  { icon: 'checklist_rtl',    label: 'OPERATION',  outputs: [null]        },
    webhook:    { icon: 'webhook',          label: 'WEBHOOK',    outputs: [null]        },
  };

  // Default `data` payload sent to POST /nodes/new/{type}. Server validates per-type.
  const DEFAULTS = {
    send:       { title: 'Send email' },
    wait:       { title: 'Wait', duration: 'PT1H' },
    wait_until: { title: 'Wait until' },
    condition:  { title: 'Condition', kind: 'open', timeout: 'P1D' },
    operation:  { title: 'Operation', kind: 'tag' },
    webhook:    { title: 'Webhook', method: 'POST' },
  };

  const DAYS = [
    { value: '1', label: 'M' }, { value: '2', label: 'T' }, { value: '3', label: 'W' },
    { value: '4', label: 'T' }, { value: '5', label: 'F' }, { value: '6', label: 'S' },
    { value: '0', label: 'S' },
  ];

  const DURATION_PRESETS = [
    { iso: 'PT5M',  label: '5 min' },
    { iso: 'PT15M', label: '15 min' },
    { iso: 'PT1H',  label: '1 hour' },
    { iso: 'PT6H',  label: '6 hours' },
    { iso: 'P1D',   label: '1 day' },
    { iso: 'P3D',   label: '3 days' },
    { iso: 'P1W',   label: '1 week' },
    { iso: 'P2W',   label: '2 weeks' },
    { iso: 'P1M',   label: '1 month' },
  ];

  // Per-mode copy for the unified Wait sidebar — single source of truth used
  // by renderWaitForm (initial render) and the mode-toggle click handler
  // (re-render on mode switch). Hint strings contain inline <kbd> chips
  // matching the Operation sidebar's tag-input hint style; the toggle handler
  // assigns them via innerHTML, not textContent.
  const WAIT_MODE_TEXTS = {
    duration: {
      title: 'Duration',
      desc:  'Pause the flow for a fixed amount of time after this step.',
      hint:  'Type <kbd>30m</kbd>, <kbd>2 weeks</kbd>, or an ISO 8601 like <kbd>PT45M</kbd>.',
    },
    until: {
      title: 'Wait until',
      desc:  'Pause the flow until a specific date and time.',
      hint:  'Type <kbd>next monday</kbd>, <kbd>tomorrow 9am</kbd>, or a datetime like <kbd>2026-12-31T18:00</kbd>.',
    },
  };

  // `avatar` (single letter) + `avatarVariant` (1..6) feed `.mc-avatar-{n}`
  // colour rotation; `meta` is the one-line description rendered under the
  // option name in the list-switcher dropdown.
  const OPERATION_KINDS = [
    { value: 'tag',        label: 'Add tag(s)',    avatar: 'A', avatarVariant: 3, meta: 'Tag the subscriber for downstream segmentation.' },
    { value: 'remove_tag', label: 'Remove tag(s)', avatar: 'R', avatarVariant: 5, meta: 'Strip the named tags from the subscriber.' },
    { value: 'copy',       label: 'Copy to list',  avatar: 'C', avatarVariant: 1, meta: 'Also add the subscriber to another mailing list.' },
    { value: 'move',       label: 'Move to list',  avatar: 'M', avatarVariant: 6, meta: 'Move from this automation\'s list to another list.' },
    { value: 'update',     label: 'Update field',  avatar: 'U', avatarVariant: 4, meta: 'Set a custom field value on the subscriber.' },
  ];

  const HTTP_METHODS = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];
  const FIELD_OPERATORS = [
    { value: 'equals',     label: 'equals' },
    { value: 'not_equals', label: 'does not equal' },
    { value: 'contains',   label: 'contains' },
  ];

  // ─── Card content ───────────────────────────────────────────────────────

  function cardContent(node) {
    const cfg = TYPES[node.type] || { icon: 'circle', label: (node.type || 'NODE').toUpperCase() };
    const subtitle = summarize(node);
    // Server-side `validation: {valid:false, message}` decoration → inline
    // warning chip in the card body. The wrapper `.fl-node.is-invalid` class
    // (set by the renderer) draws the outer ring; this adds the actionable
    // hint inside so the user knows WHY it's flagged.
    const invalid = node.validation && node.validation.valid === false;
    const warnHtml = invalid
      ? `<div class="fl-card-warn">
           <span class="material-symbols-rounded">warning</span>
           <span>${escapeHtml(node.validation.message || 'Not configured')}</span>
         </div>`
      : '';
    const root = el('div', 'fl-card');
    root.innerHTML = `
      <div class="fl-card-icon"><span class="material-symbols-rounded">${cfg.icon}</span></div>
      <div class="fl-card-body">
        <div class="fl-card-kind">${escapeHtml(cfg.label)}</div>
        <div class="fl-card-title">${escapeHtml(node.data?.title || cfg.label)}</div>
        ${subtitle ? `<div class="fl-card-sub">${escapeHtml(subtitle)}</div>` : ''}
        ${warnHtml}
      </div>`;
    return root;
  }

  function outputs(node) { return TYPES[node.type]?.outputs || [null]; }

  function summarize(node) {
    const d = node.data || {};
    switch (node.type) {
      case 'trigger':    return labelForKey(d.key) || '(not configured)';
      case 'send':       return d.emailSubject || labelForEmail(d.emailUid) || '(no email)';
      case 'wait':       return d.duration ? humanizeDuration(d.duration) : '';
      case 'wait_until': return d.until || '';
      case 'condition': {
        const parts = [];
        parts.push(d.kind === 'click' ? 'Clicked' : 'Opened');
        if (d.emailUid)  parts.push(labelForEmail(d.emailUid));
        if (d.timeout)   parts.push('· timeout ' + humanizeDuration(d.timeout));
        return parts.join(' ');
      }
      case 'operation': {
        const kindLabel = (OPERATION_KINDS.find(k => k.value === d.kind) || {}).label || d.kind || '';
        if (d.kind === 'tag' || d.kind === 'remove_tag') return kindLabel + ': ' + ((d.tags || []).join(', ') || '(none)');
        if (d.kind === 'copy' || d.kind === 'move')      return kindLabel + ': ' + (labelForList(d.targetListUid) || '(no list)');
        if (d.kind === 'update')                          return kindLabel + ': ' + (labelForField(d.fieldUid) || '(no field)');
        return kindLabel;
      }
      case 'webhook':    return (d.method || 'POST') + ' ' + (d.url || labelForHttpConfig(d.httpConfigUid) || '(not configured)');
    }
    return '';
  }

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

  // ─── Wait sidebar suggesters ──────────────────────────────────────────
  // Pure functions — given the user's typed query, return a list of
  // SuggestItem `{value, label, sub, group?}` for the dropdown. No DOM,
  // no ctx, no fetch — testable in isolation. The DynamicDropdownControl
  // (public/refactor/js/flow-lib/dynamic-dropdown-control.js) calls these
  // from its onKeyChange callback in mountWaitSuggest.
  //
  // Ambiguity rules documented in the inline hints under each suggest input.
  // Most relevant: bare "1 m" maps to PT1M (minute wins); use "1 mo" / "1 month"
  // for month — minute is by far the more common Wait config.

  const DURATION_QUICK_PICKS = [
    { value: 'PT5M',  label: '5 minutes' },
    { value: 'PT15M', label: '15 minutes' },
    { value: 'PT30M', label: '30 minutes' },
    { value: 'PT1H',  label: '1 hour' },
    { value: 'PT6H',  label: '6 hours' },
    { value: 'P1D',   label: '1 day' },
    { value: 'P3D',   label: '3 days' },
    { value: 'P1W',   label: '1 week' },
    { value: 'P2W',   label: '2 weeks' },
    { value: 'P1M',   label: '1 month' },
  ];

  // Map of unit-prefix → ISO builder. Order-sensitive longest-first matches first.
  // 'mo'/'month' must be tested BEFORE 'm'/'min'/'minute' so "1 mo" doesn't get
  // greedy-matched as minute.
  const DURATION_UNITS = [
    { rx: /^(?:mo|months?)$/i,            iso: n => 'P'  + n + 'M' },
    { rx: /^(?:y|yrs?|years?)$/i,         iso: n => 'P'  + n + 'Y' },
    { rx: /^(?:w|wks?|weeks?)$/i,         iso: n => 'P'  + n + 'W' },
    { rx: /^(?:d|days?)$/i,               iso: n => 'P'  + n + 'D' },
    { rx: /^(?:h|hrs?|hours?)$/i,         iso: n => 'PT' + n + 'H' },
    { rx: /^(?:m|mins?|minutes?)$/i,      iso: n => 'PT' + n + 'M' },
    { rx: /^(?:s|secs?|seconds?)$/i,      iso: n => 'PT' + n + 'S' },
  ];

  function durationSuggester(query) {
    const q = String(query || '').trim();

    if (q === '') {
      return DURATION_QUICK_PICKS.map(p => ({
        value: p.value, label: p.label, sub: p.value, group: 'Common waits'
      }));
    }

    // Verbatim ISO 8601 duration (P[..]T[..]).
    if (/^P(?:\d+[YMWD])*(?:T(?:\d+[HMS])+)?$/i.test(q)) {
      return [{ value: q.toUpperCase(), label: humanizeDuration(q.toUpperCase()), sub: 'ISO 8601' }];
    }

    // "30m", "30 min", "2 weeks", "1 mo"
    const unitMatch = q.match(/^(\d+)\s*([a-z]+)$/i);
    if (unitMatch) {
      const n = unitMatch[1];
      const unit = unitMatch[2];
      const found = DURATION_UNITS.find(u => u.rx.test(unit));
      if (found) {
        const iso = found.iso(n);
        return [{ value: iso, label: humanizeDuration(iso), sub: iso }];
      }
      return [];
    }

    // Bare number ("5") → all common units
    const numMatch = q.match(/^(\d+)$/);
    if (numMatch) {
      const n = numMatch[1];
      return [
        { value: 'PT' + n + 'M', label: n + ' minute' + (n === '1' ? '' : 's'), sub: 'PT' + n + 'M' },
        { value: 'PT' + n + 'H', label: n + ' hour'   + (n === '1' ? '' : 's'), sub: 'PT' + n + 'H' },
        { value: 'P'  + n + 'D', label: n + ' day'    + (n === '1' ? '' : 's'), sub: 'P'  + n + 'D' },
        { value: 'P'  + n + 'W', label: n + ' week'   + (n === '1' ? '' : 's'), sub: 'P'  + n + 'W' },
      ];
    }

    return [];
  }

  // ── Until-mode suggester ──
  // Resolves intent strings ("today 5pm", "next monday at 9am") against `now`
  // (defaults to current time). Returns ISO `YYYY-MM-DDTHH:mm` strings to match
  // the existing `<input type=datetime-local>` value shape (no timezone — server
  // interprets in the customer's tz on save).

  const WEEKDAYS = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];

  function pad2(n) { return n < 10 ? '0' + n : '' + n; }
  function isoLocal(d) {
    return d.getFullYear() + '-' + pad2(d.getMonth() + 1) + '-' + pad2(d.getDate())
         + 'T' + pad2(d.getHours()) + ':' + pad2(d.getMinutes());
  }
  function humanDate(d) {
    const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    const hh = d.getHours(), mm = d.getMinutes();
    const ampm = hh >= 12 ? 'PM' : 'AM';
    const h12 = ((hh + 11) % 12) + 1;
    return months[d.getMonth()] + ' ' + d.getDate() + ', ' + d.getFullYear()
         + ' at ' + h12 + ':' + pad2(mm) + ' ' + ampm;
  }
  function atTime(base, h, m) {
    const d = new Date(base.getTime());
    d.setHours(h, m || 0, 0, 0);
    return d;
  }
  function addDays(base, n) {
    const d = new Date(base.getTime());
    d.setDate(d.getDate() + n);
    return d;
  }
  function nextWeekday(base, wantDow) {
    let delta = (wantDow - base.getDay() + 7) % 7;
    if (delta === 0) delta = 7;  // "next Monday" is always strictly future
    return addDays(base, delta);
  }
  function untilItem(date, label) {
    return { value: isoLocal(date), label: label, sub: humanDate(date) };
  }

  // Token-based parser. Spec lives in docs/datetime-input-helper/README.md §2.
  // Be permissive about word order — `"mon 8"` and `"8 mon"` produce the same
  // suggestions. Unknown tokens are ignored as long as at least one is recognized.

  // Single keyword vocabulary — every recognized word goes through the same
  // exact-or-prefix matcher (MIN_PREFIX_LEN). No more hard-coded `tok === ...`
  // chains. Order matters: in case of ambiguity (e.g. "mon" prefixes both
  // "monday" AND "month"), the FIRST entry wins. Weekdays come first so
  // "mon"/"wed"/etc. resolve to the conventional weekday meaning, and "mont"
  // (which only prefixes "month") resolves to the period unambiguously.
  const KEYWORDS = [
    // Weekdays (full names — prefix matching covers "mon", "tue", "wed", "thu",
    // "fri", "sat", "sun", "mond", "tues", etc.).
    { word: 'sunday',    payload: { kind: 'weekday', dow: 0 } },
    { word: 'monday',    payload: { kind: 'weekday', dow: 1 } },
    { word: 'tuesday',   payload: { kind: 'weekday', dow: 2 } },
    { word: 'wednesday', payload: { kind: 'weekday', dow: 3 } },
    { word: 'thursday',  payload: { kind: 'weekday', dow: 4 } },
    { word: 'friday',    payload: { kind: 'weekday', dow: 5 } },
    { word: 'saturday',  payload: { kind: 'weekday', dow: 6 } },
    // Common-variant aliases (exact-match wins before prefix-match).
    { word: 'tues',      payload: { kind: 'weekday', dow: 2 } },
    { word: 'weds',      payload: { kind: 'weekday', dow: 3 } },
    { word: 'thur',      payload: { kind: 'weekday', dow: 4 } },
    { word: 'thurs',     payload: { kind: 'weekday', dow: 4 } },

    // Prefix words. `next` and `end` both modify period semantics (and `next`
    // also modifies weekday/hour combos). Without either, period tokens surface
    // BOTH "Next X" and "End of X" variants — see buildDays §period branch.
    { word: 'next',      payload: { kind: 'prefix', value: 'next' } },
    { word: 'end',       payload: { kind: 'endmod' } },

    // Relative days — "tom", "tod", "tmr", "tmrw" all hit via exact-or-prefix.
    { word: 'today',     payload: { kind: 'relday', value: 'today' } },
    { word: 'tomorrow',  payload: { kind: 'relday', value: 'tomorrow' } },
    { word: 'tmrw',      payload: { kind: 'relday', value: 'tomorrow' } },
    { word: 'tmr',       payload: { kind: 'relday', value: 'tomorrow' } },

    // Time literals
    { word: 'noon',      payload: { kind: 'time', hour: 12, minute: 0, ampmHint: 'pm' } },
    { word: 'midnight',  payload: { kind: 'time', hour: 0,  minute: 0, ampmHint: null } },

    // Periods (used with "next" → "Next week" / "Next month")
    { word: 'week',      payload: { kind: 'period', value: 'week' } },
    { word: 'month',     payload: { kind: 'period', value: 'month' } },
  ];

  const MIN_PREFIX_LEN = 2;  // 1-char would over-match; 2 catches "mo"/"to"/"ne".

  // Returns an ARRAY of matched payloads (may be empty).
  // - Exact match → single payload.
  // - Prefix match → all keywords whose word starts with tok, but filtered to
  //   the kind of the FIRST match (so "mo" prefers Monday over month —
  //   weekday wins because it appears first in the KEYWORDS table).
  // - Same-kind matches that collapse to the same payload (e.g. "tue" hits
  //   both "tuesday" and "tues", both dow=2) are deduped by the caller.
  function matchKeyword(tok) {
    for (const kw of KEYWORDS) {
      if (kw.word === tok) return [kw.payload];
    }
    if (tok.length < MIN_PREFIX_LEN) return [];
    const all = KEYWORDS.filter(kw => kw.word.startsWith(tok)).map(kw => kw.payload);
    if (all.length === 0) return [];
    const firstKind = all[0].kind;
    return all.filter(p => p.kind === firstKind);
  }

  // Returns {hour: 0..23, minute: 0..59, ampmHint: 'am'|'pm'|null} or null.
  // Only handles numeric forms — keyword forms (noon/midnight) live in KEYWORDS.
  //
  // Minute parsing accepts 1 OR 2 digits AND a trailing-colon-with-no-digits:
  //   "11"     → :00 (no colon)
  //   "11:"    → :00 (still typing — same as bare hour)
  //   "11:3"   → :30 (1 digit = TENS place, conventional like Google Calendar)
  //   "11:30"  → :30 (2 digits exact)
  //   "11:6"   → null (tens=60 → out of range)
  // Keeps the dropdown stable mid-keystroke as user types "11" → "11:" → "11:3" → "11:30".
  function parseTimeToken(tok) {
    const m = tok.match(/^(\d{1,2})(?::(\d{1,2})?)?(am|pm)?$/);
    if (!m) return null;
    let hour = parseInt(m[1], 10);
    let minute = 0;
    if (m[2]) {
      minute = parseInt(m[2], 10);
      if (m[2].length === 1) minute *= 10;  // "3" → 30, "5" → 50, "0" → 0
    }
    const ampm = m[3] || null;
    if (minute > 59) return null;
    if (ampm) {
      if (hour < 1 || hour > 12) return null;
      if (ampm === 'pm' && hour < 12) hour += 12;
      if (ampm === 'am' && hour === 12) hour = 0;
      return { hour, minute, ampmHint: ampm };
    }
    if (hour < 0 || hour > 23) return null;
    return { hour, minute, ampmHint: null };
  }

  // Returns an ARRAY of {kind, ...} entries (caller iterates).
  function classifyToken(tok) {
    const kws = matchKeyword(tok);
    if (kws.length > 0) return kws;
    const t = parseTimeToken(tok);
    if (t) return [{ kind: 'time', ...t }];
    return [{ kind: 'unknown' }];
  }

  function expandHour(t, multiDay) {
    // Unambiguous cases: ampm hint, midnight, or 13..23.
    if (t.ampmHint || t.hour === 0 || t.hour > 12) {
      return [{ hour: t.hour, minute: t.minute, label: formatHour(t.hour, t.minute) }];
    }
    // Bare 1..12 — when applied across MULTIPLE days, default to AM only (else
    // 7 weekdays × 2 hours = 14 noisy items). For a single day, expand to both
    // AM and PM — past-time pruning will drop whichever is already past, so e.g.
    // "today 9" at 10 AM still shows "Today at 9:00 PM".
    const am = t.hour === 12 ? 0 : t.hour;
    const pm = t.hour === 12 ? 12 : t.hour + 12;
    if (multiDay) {
      return [{ hour: am, minute: t.minute, label: formatHour(am, t.minute) }];
    }
    return [
      { hour: am, minute: t.minute, label: formatHour(am, t.minute) },
      { hour: pm, minute: t.minute, label: formatHour(pm, t.minute) },
    ];
  }

  function formatHour(h, m) {
    const ampm = h >= 12 ? 'PM' : 'AM';
    const h12 = ((h + 11) % 12) + 1;
    return h12 + ':' + pad2(m) + ' ' + ampm;
  }

  function defaultQuickPicks(now) {
    const items = [];
    const today5pm = atTime(now, 17, 0);
    if (today5pm.getTime() > now.getTime()) items.push(untilItem(today5pm, 'Today at 5:00 PM'));
    items.push(untilItem(atTime(addDays(now, 1), 9, 0),  'Tomorrow at 9:00 AM'));
    items.push(untilItem(atTime(addDays(now, 1), 18, 0), 'Tomorrow at 6:00 PM'));
    items.push(untilItem(atTime(nextWeekday(now, 1), 9, 0), 'Next Monday at 9:00 AM'));
    items.push(untilItem(atTime(nextWeekday(now, 0), 23, 59), 'End of week (Sunday)'));
    const endMonth = new Date(now.getFullYear(), now.getMonth() + 1, 0, 23, 59, 0, 0);
    items.push(untilItem(endMonth, 'End of month'));
    return items;
  }

  function untilSuggester(query, opts) {
    const now = (opts && opts.now) || new Date();
    const q = String(query || '').trim();
    if (q === '') return defaultQuickPicks(now);

    // ISO datetime short-circuit (verbatim, even if past — user explicitly typed it).
    if (/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(?::\d{2})?$/.test(q)) {
      const d = new Date(q);
      if (!isNaN(d.getTime())) return [untilItem(d, q)];
      return [];
    }

    const tokens = q.toLowerCase().split(/\s+/).filter(Boolean);
    const intent = { prefix: null, endMod: false, weekdays: [], hours: [], reldays: [], periods: [] };
    let recognized = false;
    const pushUnique = (arr, v) => { if (arr.indexOf(v) < 0) arr.push(v); };

    tokens.forEach(tok => {
      classifyToken(tok).forEach(c => {
        switch (c.kind) {
          case 'prefix':  intent.prefix = c.value; recognized = true; break;
          case 'endmod':  intent.endMod = true; recognized = true; break;
          case 'relday':  pushUnique(intent.reldays,  c.value); recognized = true; break;
          case 'weekday': pushUnique(intent.weekdays, c.dow);   recognized = true; break;
          case 'time':    intent.hours.push(c); recognized = true; break;
          case 'period':  pushUnique(intent.periods, c.value);  recognized = true; break;
          case 'unknown': /* ignore */ break;
          default:
            // HARD RULE 2 — token-classify discriminator must be exhaustive
            throw new Error('Unhandled token kind: ' + c.kind);
        }
      });
    });

    if (!recognized) return [];

    // ── Helpers for "End of week" / "End of month" entries (fixedTime: true
    // bypasses the hour cross-product because the boundary already encodes 23:59).
    const endOfWeek = () => ({
      date: atTime(nextWeekday(now, 0), 23, 59),
      label: 'End of week (Sunday)',
      fixedTime: true,
    });
    const endOfMonth = () => ({
      date: new Date(now.getFullYear(), now.getMonth() + 1, 0, 23, 59, 0, 0),
      label: 'End of month',
      fixedTime: true,
    });

    // Build day list per §2.2 precedence rules.
    const ALL_DOWS = [0,1,2,3,4,5,6];
    let days = [];
    let daysFromFallback = false;  // true = bare-hour today+tomorrow (no day signal)

    if (intent.reldays.length > 0) {
      // "to" → both today + tomorrow (multi-match); "tom" → tomorrow only;
      // "tod" → today only.
      if (intent.reldays.indexOf('today')    >= 0) days.push({ date: now,             label: 'Today' });
      if (intent.reldays.indexOf('tomorrow') >= 0) days.push({ date: addDays(now, 1), label: 'Tomorrow' });
    }
    else if (intent.weekdays.length > 0) days = intent.weekdays.map(dow => ({ date: nextWeekday(now, dow), label: 'Next ' + WEEKDAYS[dow] }));
    else if (intent.periods.length > 0) {
      // Period token present. Modifier decides which variants surface:
      //   no modifier      → BOTH "Next X" + "End of X"
      //   prefix === next  → only "Next X"
      //   endMod === true  → only "End of X"
      // "End of X" entries carry fixedTime: true so the hour cross-product
      // doesn't override their 23:59 boundary.
      const wantNext = intent.prefix === 'next' || (!intent.prefix && !intent.endMod);
      const wantEnd  = intent.endMod || (!intent.prefix && !intent.endMod);
      if (intent.periods.indexOf('week') >= 0) {
        if (wantNext) days.push({ date: addDays(now, 7), label: 'Next week (7 days from now)' });
        if (wantEnd)  days.push(endOfWeek());
      }
      if (intent.periods.indexOf('month') >= 0) {
        if (wantNext) {
          const m = new Date(now.getFullYear(), now.getMonth() + 1, now.getDate(), 0, 0, 0, 0);
          days.push({ date: m, label: 'Next month' });
        }
        if (wantEnd)  days.push(endOfMonth());
      }
    } else if (intent.endMod) {
      // "end" alone (no period) → both End of week + End of month.
      days.push(endOfWeek(), endOfMonth());
    } else if (intent.prefix === 'next' && intent.hours.length > 0) {
      days = ALL_DOWS.map(dow => ({ date: nextWeekday(now, dow), label: 'Next ' + WEEKDAYS[dow] }));
    } else if (intent.prefix === 'next') {
      days = ALL_DOWS.map(dow => ({ date: nextWeekday(now, dow), label: 'Next ' + WEEKDAYS[dow] }));
      days.push({ date: addDays(now, 7), label: 'Next week (7 days from now)' });
      const m = new Date(now.getFullYear(), now.getMonth() + 1, now.getDate(), 0, 0, 0, 0);
      days.push({ date: m, label: 'Next month' });
    } else if (intent.hours.length > 0) {
      // Bare hours, no day signal — fall back to today + tomorrow.
      // Mark as "fallback" so multi-day AM-only rule does NOT apply (each day
      // here is exploratory, user wants both AM/PM variants per day).
      days = [{ date: now, label: 'Today' }, { date: addDays(now, 1), label: 'Tomorrow' }];
      daysFromFallback = true;
    }

    if (days.length === 0) return [];

    // Build hour list per §2.2. `multiDay` flag controls AM/PM expansion of
    // bare 1..12 hours — single day expands both, multi-day = AM only.
    // Fallback today+tomorrow does NOT count as multi-day (it's exploratory).
    const multiDay = days.length > 1 && !daysFromFallback;
    const DEFAULT_HOURS_3 = [
      { hour: 9,  minute: 0, label: '9:00 AM' },
      { hour: 12, minute: 0, label: '12:00 PM' },
      { hour: 18, minute: 0, label: '6:00 PM' },
    ];
    let hours;
    if (intent.hours.length > 0) {
      hours = intent.hours.flatMap(t => expandHour(t, multiDay));
    } else if (intent.reldays.length === 1) {
      // Single relday → 3 default times. Multi-relday (e.g. "to") still uses 3.
      hours = DEFAULT_HOURS_3;
    } else if (intent.reldays.length > 1) {
      hours = DEFAULT_HOURS_3;
    } else if (intent.weekdays.length === 1) {
      // Single weekday with no hour → 3 default times (parity with relday).
      hours = DEFAULT_HOURS_3;
    } else {
      // Multi-weekday or "next" alone → just 9 AM (else 7 × 3 = 21 items).
      hours = [{ hour: 9, minute: 0, label: '9:00 AM' }];
    }

    // Cross-product, prune past, sort ascending by ISO value.
    // `fixedTime: true` days (End of week / End of month) bypass the hour
    // cross-product — their boundary already encodes 23:59.
    const items = [];
    days.forEach(d => {
      if (d.fixedTime) {
        if (d.date.getTime() >= now.getTime()) {
          items.push({ value: isoLocal(d.date), label: d.label, sub: humanDate(d.date) });
        }
        return;
      }
      hours.forEach(h => {
        const date = atTime(d.date, h.hour, h.minute);
        if (date.getTime() < now.getTime()) return;  // §2.9 prune past
        items.push({
          value: isoLocal(date),
          label: d.label + ' at ' + h.label,
          sub: humanDate(date),
        });
      });
    });
    items.sort((a, b) => a.value < b.value ? -1 : (a.value > b.value ? 1 : 0));
    return items;
  }

  // ── dateSuggester / timeSuggester ──
  // Date-only and time-of-day siblings of untilSuggester. Reuse the same
  // classifyToken / parseTimeToken / KEYWORDS vocabulary so the same words
  // (mon, tom, end, noon, …) resolve consistently across all date/time inputs
  // in the automation editor (trigger sidebar specific-date / at time / before).

  function dateOnlyIso(d) {
    return d.getFullYear() + '-' + pad2(d.getMonth() + 1) + '-' + pad2(d.getDate());
  }
  function humanDateShort(d) {
    const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    return months[d.getMonth()] + ' ' + d.getDate() + ', ' + d.getFullYear();
  }
  function dateItem(date, label) {
    return { value: dateOnlyIso(date), label: label, sub: humanDateShort(date) };
  }
  function timeItem(hour, minute) {
    const v = pad2(hour) + ':' + pad2(minute);
    return { value: v, label: formatHour(hour, minute), sub: v };
  }

  function dateSuggester(query, opts) {
    const now = (opts && opts.now) || new Date();
    const q = String(query || '').trim();

    if (q === '') {
      return [
        dateItem(now,                 'Today'),
        dateItem(addDays(now, 1),     'Tomorrow'),
        dateItem(nextWeekday(now, 1), 'Next Monday'),
        dateItem(addDays(now, 7),     'In 1 week'),
        dateItem(new Date(now.getFullYear(), now.getMonth() + 1, 0), 'End of month'),
      ];
    }

    // Verbatim Y-m-d short-circuit
    if (/^\d{4}-\d{2}-\d{2}$/.test(q)) {
      const d = new Date(q + 'T00:00:00');
      if (!isNaN(d.getTime())) return [{ value: q, label: q, sub: humanDateShort(d) }];
      return [];
    }

    // Reuse classifyToken — but ignore the `time` kind (date-only output)
    const tokens = q.toLowerCase().split(/\s+/).filter(Boolean);
    const intent = { prefix: null, endMod: false, weekdays: [], reldays: [], periods: [] };
    let recognized = false;
    const pushUnique = (arr, v) => { if (arr.indexOf(v) < 0) arr.push(v); };
    tokens.forEach(tok => {
      classifyToken(tok).forEach(c => {
        switch (c.kind) {
          case 'prefix':  intent.prefix = c.value; recognized = true; break;
          case 'endmod':  intent.endMod = true; recognized = true; break;
          case 'relday':  pushUnique(intent.reldays,  c.value); recognized = true; break;
          case 'weekday': pushUnique(intent.weekdays, c.dow); recognized = true; break;
          case 'period':  pushUnique(intent.periods,  c.value); recognized = true; break;
          // 'time' / 'unknown' ignored
        }
      });
    });
    if (!recognized) return [];

    // Build day list — same precedence rules as untilSuggester §2.2.
    const ALL_DOWS = [0,1,2,3,4,5,6];
    let days = [];

    if (intent.reldays.length > 0) {
      if (intent.reldays.indexOf('today')    >= 0) days.push({ date: now,             label: 'Today' });
      if (intent.reldays.indexOf('tomorrow') >= 0) days.push({ date: addDays(now, 1), label: 'Tomorrow' });
    } else if (intent.weekdays.length > 0) {
      days = intent.weekdays.map(dow => ({ date: nextWeekday(now, dow), label: 'Next ' + WEEKDAYS[dow] }));
    } else if (intent.periods.length > 0) {
      const wantNext = intent.prefix === 'next' || (!intent.prefix && !intent.endMod);
      const wantEnd  = intent.endMod || (!intent.prefix && !intent.endMod);
      if (intent.periods.indexOf('week') >= 0) {
        if (wantNext) days.push({ date: addDays(now, 7),                                  label: 'Next week (7 days from now)' });
        if (wantEnd)  days.push({ date: nextWeekday(now, 0),                              label: 'End of week (Sunday)' });
      }
      if (intent.periods.indexOf('month') >= 0) {
        if (wantNext) days.push({ date: new Date(now.getFullYear(), now.getMonth() + 1, now.getDate()), label: 'Next month' });
        if (wantEnd)  days.push({ date: new Date(now.getFullYear(), now.getMonth() + 1, 0),             label: 'End of month' });
      }
    } else if (intent.endMod) {
      days.push({ date: nextWeekday(now, 0),                              label: 'End of week (Sunday)' });
      days.push({ date: new Date(now.getFullYear(), now.getMonth() + 1, 0), label: 'End of month' });
    } else if (intent.prefix === 'next') {
      days = ALL_DOWS.map(dow => ({ date: nextWeekday(now, dow), label: 'Next ' + WEEKDAYS[dow] }));
      days.push({ date: addDays(now, 7),                                                       label: 'Next week (7 days from now)' });
      days.push({ date: new Date(now.getFullYear(), now.getMonth() + 1, now.getDate()),        label: 'Next month' });
    }

    if (days.length === 0) return [];

    // Date-only past prune: compare midnight-of-day vs today's midnight.
    const todayMs = new Date(now.getFullYear(), now.getMonth(), now.getDate()).getTime();
    const items = days
      .filter(d => new Date(d.date.getFullYear(), d.date.getMonth(), d.date.getDate()).getTime() >= todayMs)
      .map(d => dateItem(d.date, d.label));
    items.sort((a, b) => a.value < b.value ? -1 : (a.value > b.value ? 1 : 0));
    return items;
  }

  function timeSuggester(query) {
    const q = String(query || '').trim();

    if (q === '') {
      // Common scheduling slots — typical "send at" times.
      return [
        timeItem(9,  0),
        timeItem(12, 0),
        timeItem(15, 0),
        timeItem(18, 0),
        timeItem(21, 0),
      ];
    }

    // Verbatim HH:MM short-circuit
    const exact = q.match(/^(\d{2}):(\d{2})$/);
    if (exact) {
      const hh = parseInt(exact[1], 10), mm = parseInt(exact[2], 10);
      if (hh >= 0 && hh <= 23 && mm >= 0 && mm <= 59) return [timeItem(hh, mm)];
      return [];
    }

    // Single-token time parse via shared classifier.
    const matches = classifyToken(q.toLowerCase()).filter(c => c.kind === 'time');
    if (matches.length === 0) return [];
    return matches.flatMap(t => expandHour(t, false).map(h => timeItem(h.hour, h.minute)));
  }

  // Display helpers — turn opaque uids into human labels using ctx lookups.
  function labelForKey(v)        { return v ? ((ctx.triggerKeys.find(k => k.value === v) || {}).label || v) : ''; }
  function labelForEmail(v)      { return v ? ((ctx.emails.find(e => e.value === v) || {}).label || v) : ''; }
  function labelForList(v)       { return v ? ((ctx.mailLists.find(l => l.value === v) || {}).label || v) : ''; }
  function labelForField(v)      { return v ? ((ctx.listFields.find(f => f.value === v) || {}).label || v) : ''; }
  function labelForHttpConfig(v) { return v ? ((ctx.httpConfigs.find(h => h.value === v) || {}).label || v) : ''; }

  // ─── Module state ──────────────────────────────────────────────────────

  let tree, renderer, ctx, urls;

  // ─── REST plumbing ──────────────────────────────────────────────────────

  async function api(method, url, body) {
    const opts = {
      method,
      credentials: 'same-origin',
      headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': ctx.csrfToken },
    };
    if (body !== undefined) {
      opts.headers['Content-Type'] = 'application/json';
      opts.body = JSON.stringify(body);
    }
    const res = await fetch(url, opts);
    // Read once as text so we can log the raw body when JSON parse fails —
    // historically a 200 response with HTML body (debug bar, dev-tooling
    // overlay, route hijack) silently surfaced as the cryptic "HTTP 200" toast.
    const raw = await res.text();
    let json;
    try {
      json = raw === '' ? {} : JSON.parse(raw);
    } catch (e) {
      console.error('[flow-edit] api(): non-JSON response', { url, status: res.status, raw: raw.slice(0, 500) });
      throw new Error(`HTTP ${res.status} — non-JSON body (see console)`);
    }
    if (!res.ok || !json.ok) {
      throw new Error(json.error || `HTTP ${res.status}`);
    }
    return json;
  }

  // Single point of truth for applying a server flow: load + redraw. (Rule 4)
  function applyFlow(flowJson) {
    tree.load(flowJson);
    renderer.draw(tree);
  }

  // ─── Click handlers ────────────────────────────────────────────────────

  function handleClickedSlot(slot, ev) {
    addModal.open({
      onPick: async (type) => {
        try {
          const body = { data: { ...(DEFAULTS[type] || {}) } };
          if (slot.kind === 'edge')       body.insertOnEdge = slot.edgeId;
          else if (slot.kind === 'after') body.insertAfter  = { nodeId: slot.source, branch: slot.branch };
          else                            return;   // 'asRoot' never happens (automation always has trigger)
          const url = urls.create.replace('__TYPE__', encodeURIComponent(type));
          const res = await api('POST', url, body);
          applyFlow(res.flow);
          // Open sidebar on the freshly created node so the user can configure immediately.
          if (res.node) sidebar.open(tree.node(res.node.id));
          else          toast(`Added ${type}`, 'ok');
        } catch (e) { toast(e.message, 'error'); }
      },
    });
  }

  function handleClickedNode(node, ev) {
    sidebar.open(node);
  }

  // ─── Sidebar ────────────────────────────────────────────────────────────

  const sidebar = (function () {
    let active = null;        // { node, panel, backdrop, formState }

    function open(node) {
      close();
      const backdrop = el('div', 'fl-sidebar-backdrop');
      const panel    = el('aside', 'fl-sidebar');
      panel.innerHTML = sidebarShell(node);
      document.body.appendChild(backdrop);
      document.body.appendChild(panel);
      // Force layout, then add open class for slide-in.
      requestAnimationFrame(() => panel.classList.add('fl-sidebar-open'));

      active = { node, panel, backdrop, formState: { ...(node.data || {}) } };
      wire(panel, backdrop, node);
      // Focus title input for fast editing.
      const titleInput = panel.querySelector('[data-field="title"]');
      if (titleInput) setTimeout(() => titleInput.select(), 240);
      document.addEventListener('keydown', onKey);
    }

    function close() {
      if (!active) return;
      document.removeEventListener('keydown', onKey);
      active.panel.classList.remove('fl-sidebar-open');
      const { panel, backdrop } = active;
      active = null;
      setTimeout(() => { panel.remove(); backdrop.remove(); }, 220);
    }

    function onKey(e) {
      if (e.key === 'Escape') close();
      else if (e.key === 'Enter' && (e.metaKey || e.ctrlKey)) doSave();
    }

    function wire(panel, backdrop, node) {
      backdrop.addEventListener('click', close);
      panel.querySelector('.fl-sidebar-close').addEventListener('click', close);
      // Cancel + Save buttons are intentionally absent for Send-node sidebars
      // (see sidebarShell — every stage has its own save affordance).
      panel.querySelector('.fl-sidebar-cancel')?.addEventListener('click', close);
      panel.querySelector('.fl-sidebar-save')?.addEventListener('click', doSave);

      // Trigger picker — both "Choose a trigger" (state A) and "Change" (state B)
      // open the same card-grid modal. Picking a card swaps formState.key and
      // re-renders the trigger body (summary + sub-form). Re-binding only the
      // newly-rendered picker buttons avoids stacking listeners on close/save.
      bindTriggerPicker(panel);

      // Operation kind change → re-render sub-form
      const opSel = panel.querySelector('[data-on-change="operation-kind"]');
      if (opSel) {
        opSel.addEventListener('change', (e) => {
          captureForm(panel);
          active.formState.kind = e.target.value;
          const sub = panel.querySelector('[data-operation-subform]');
          if (sub) {
            sub.innerHTML = renderOperationSubform(e.target.value, active.formState);
            wireSubFormHandlers(sub);
          }
        });
      }

      wireSubFormHandlers(panel);

      // Delete actions live inside the footer's "Actions" dropdown menu.
      // The items are <a href="#"> so the host's mc-dropdown CSS works (matches
      // rui/ui showcase pattern); preventDefault stops the URL navigation.
      panel.querySelectorAll('[data-act^="delete-"]').forEach(btn => {
        btn.addEventListener('click', (e) => {
          e.preventDefault();
          doDelete(btn.dataset.act === 'delete-cascade' ? 'cascade' : 'shift');
        });
      });
      // Initialize the host's McDropdown for the just-rendered footer dropdown.
      // The sidebar mounts on <body> after the page-level McDropdown.init() ran,
      // so we re-init for this freshly-injected node.
      if (window.McDropdown) {
        try { window.McDropdown.init(); } catch (_) { /* harmless */ }
      }

      // Per-row Edit / action buttons + Stage 1/2 wizard buttons render
      // dynamically — delegate from the panel so we don't re-bind on each
      // re-hydrate.
      panel.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-act]');
        if (!btn || !panel.contains(btn)) return;
        switch (btn.dataset.act) {
          case 'send-stage1-save':     doSendStage1Save(panel);     break;
          case 'send-stage1-back':     doSendStage1ReEnter(panel);  break;
          case 'goto-stage':           doGotoStage(panel, btn.dataset.stage); break;
          case 'open-email-editor':    doOpenEmailEditor();         break;
          case 'open-email-test':      doOpenEmailTest();           break;
          case 'open-template-picker': doOpenTemplatePicker(btn.dataset.tplTab || 'gallery'); break;
          case 'open-email-preview':   doOpenEmailPreview();        break;
          case 'copy-trigger-endpoint': doCopyTriggerEndpoint(panel); break;
        }
      });

      // (Removed: previous version flipped `.mc-radio-card.active` on toggle
      // change to tint the bg. Per design — no highlight on ON state.)

      // mc-list-switcher interactivity (used by Operation kind picker — see
      // renderListSwitcher()). The `.open` class lives on the inner
      // `[data-switcher-shell]` (`.mc-list-switcher` itself) — that's what the
      // showcase CSS toggles to reveal the dropdown. The outer
      // `[data-mc-switcher]` wrapper just groups the read-only display + the
      // shell so we can find sibling DOM (avatar / name / hidden input).
      panel.addEventListener('click', (e) => {
        const trigger = e.target.closest('[data-list-switcher-trigger]');
        const item    = e.target.closest('.mc-list-switcher-item');
        const inDrop  = e.target.closest('.mc-list-switcher-dropdown');
        const wrapper = (trigger || item || inDrop)?.closest('[data-mc-switcher]');
        const shell   = wrapper?.querySelector('[data-switcher-shell]');
        // Close any other open switchers when interacting with a different one.
        panel.querySelectorAll('[data-switcher-shell].open').forEach(s => {
          if (s !== shell) s.classList.remove('open');
        });
        if (trigger) {
          e.preventDefault();
          shell?.classList.toggle('open');
          return;
        }
        if (item && wrapper) {
          e.preventDefault();
          const value  = item.dataset.switcherValue;
          const label  = item.dataset.switcherLabel;
          const meta   = item.dataset.switcherMeta || '';
          const hidden = wrapper.querySelector('input[type="hidden"][data-field]');
          if (hidden && hidden.value !== value) {
            hidden.value = value;
            hidden.dispatchEvent(new Event('change', { bubbles: true }));
          }
          wrapper.querySelectorAll('.mc-list-switcher-item').forEach(o => {
            o.classList.toggle('active', o === item);
            const chk = o.querySelector('.mc-list-switcher-check');
            if (chk) chk.style.visibility = (o === item) ? '' : 'hidden';
          });
          const trgName = wrapper.querySelector('[data-switcher-name]');
          const trgMeta = wrapper.querySelector('[data-switcher-meta]');
          if (trgName) trgName.textContent = label;
          if (trgMeta) trgMeta.textContent = meta;
          // Field Select also carries a typed icon — swap the trigger glyph
          // and color tint so the read-only display reflects the new field's
          // type. Other switchers (Action picker) don't have icon slots, so
          // this is a no-op for them.
          const iconSlot = wrapper.querySelector('[data-switcher-icon-slot]');
          if (iconSlot) {
            const iconName = item.dataset.switcherIcon;
            const iconCol  = item.dataset.switcherIconColor;
            if (iconName) {
              iconSlot.innerHTML = `<span class="mc-vendor-icon mc-vendor-icon-sm" style="--vendor-color:${iconCol || 'var(--chart-6)'}">
                <span class="material-symbols-rounded">${iconName}</span>
              </span>`;
            }
          }
          shell?.classList.remove('open');
        }
      });
      // Search input → filter items inside its switcher.
      panel.addEventListener('input', (e) => {
        const search = e.target.closest('[data-list-switcher-search]');
        if (!search) return;
        const wrapper = search.closest('[data-mc-switcher]');
        if (!wrapper) return;
        const q = search.value.toLowerCase().trim();
        wrapper.querySelectorAll('.mc-list-switcher-item').forEach(it => {
          const name = it.dataset.name || '';
          it.classList.toggle('mc-list-switcher-hidden', q && name.indexOf(q) === -1);
        });
      });
      // Click outside any switcher inside the panel closes them.
      panel.addEventListener('click', (e) => {
        if (e.target.closest('[data-mc-switcher]')) return;
        panel.querySelectorAll('[data-switcher-shell].open').forEach(s => s.classList.remove('open'));
      });

      // Send sidebar Stage 2 / Stage 3 — fetch live settings then decide.
      if (node.type === 'send' && node.data?.emailUid) {
        hydrateSendWizard(panel, node);
      }
      // First-time Stage 1 (no emailUid) is rendered synchronously inside
      // `renderSendForm`; upgrade its identity-select wrappers now.
      if (node.type === 'send' && !node.data?.emailUid) {
        initIdentitySelectsIn(panel);
      }
    }

    // Re-binds JUST the trigger-picker buttons inside `panel`. Safe to call after
    // re-rendering the trigger body — doesn't touch close/save/delete listeners.
    function bindTriggerPicker(panel) {
      panel.querySelectorAll('[data-act="open-trigger-picker"]').forEach(btn => {
        if (btn.dataset.boundTriggerPicker === '1') return; // dedupe
        btn.dataset.boundTriggerPicker = '1';
        btn.addEventListener('click', () => {
          captureForm(panel);
          triggerPickerModal.open({
            currentKey: active.formState.key || null,
            onPick: (key) => {
              active.formState.key = key;
              const hidden = panel.querySelector('input[data-field="key"]');
              if (hidden) hidden.value = key;

              // Re-render the trigger body — wipes any leftover per-key sub-form.
              const body = panel.querySelector('.fl-sidebar-body');
              if (body) {
                body.innerHTML = renderTriggerForm(active.formState);
                wireSubFormHandlers(body);
                bindTriggerPicker(panel);
              }
            },
          });
        });
      });
    }

    function wireSubFormHandlers(scope) {
      // Duration preset buttons (still used by Condition's `timeout` field)
      scope.querySelectorAll('.fl-preset[data-preset-target]').forEach(btn => {
        btn.addEventListener('click', () => {
          const target = btn.dataset.presetTarget;
          const input  = scope.querySelector(`[data-field="${target}"]`);
          if (input) input.value = btn.dataset.presetValue;
          scope.querySelectorAll(`.fl-preset[data-preset-target="${target}"]`)
              .forEach(b => b.classList.toggle('active', b === btn));
        });
      });

      // Smart date / time / duration inputs (trigger sidebar specific-date,
      // at time, birthday before — anywhere a date/time/duration is captured).
      // Markers added on the per-form template; idempotent on re-render.
      scope.querySelectorAll('[data-smart-input]').forEach(input => {
        upgradeInputToSmart(input, input.dataset.smartInput);
      });

      // Wait sidebar — mount DynamicDropdownControl on the active mode's slot, wire mode toggle.
      scope.querySelectorAll('[data-wait-suggest-slot]').forEach(slot => {
        mountWaitSuggest(slot, false);
      });
      const waitToggle = scope.querySelector('[data-wait-mode-toggle]');
      if (waitToggle) {
        waitToggle.querySelectorAll('[data-wait-mode]').forEach(btn => {
          btn.addEventListener('click', () => {
            const newMode = btn.dataset.waitMode;
            const slot = scope.querySelector('[data-wait-suggest-slot]');
            if (!slot || slot.dataset.waitModeCurrent === newMode) return;
            slot.dataset.waitModeCurrent = newMode;
            waitToggle.querySelectorAll('[data-wait-mode]').forEach(b => {
              const on = b === btn;
              b.classList.toggle('is-active', on);
              b.setAttribute('aria-checked', String(on));
            });
            const lbl  = scope.querySelector('[data-wait-suggest-label]');
            const hint = scope.querySelector('[data-wait-suggest-hint]');
            const desc = scope.querySelector('[data-wait-mode-desc]');
            const txt  = WAIT_MODE_TEXTS[newMode];
            if (lbl)  lbl.textContent  = txt.title;
            if (desc) desc.textContent = txt.desc;
            if (hint) hint.innerHTML   = txt.hint;  // hint contains <kbd> chips
            // No auto-conversion datetime <-> duration (lossy + surprising).
            // Clear stored seeds so re-mount uses the mode's prefill quick-pick.
            slot.dataset.initialDuration = '';
            slot.dataset.initialUntil    = '';
            mountWaitSuggest(slot, true);
          });
        });
      }

      // Tag inputs (chip-style)
      scope.querySelectorAll('.fl-tag-input').forEach(box => {
        const input = box.querySelector('input');
        if (!input) return;
        const submit = () => {
          const v = input.value.trim();
          if (!v) return;
          const pill = el('span', 'fl-tag-pill');
          pill.innerHTML = `${escapeHtml(v)} <button type="button" aria-label="Remove">×</button>`;
          pill.querySelector('button').addEventListener('click', () => pill.remove());
          box.insertBefore(pill, input);
          input.value = '';
        };
        input.addEventListener('keydown', (e) => {
          if (e.key === 'Enter' || e.key === ',') { e.preventDefault(); submit(); }
          if (e.key === 'Backspace' && !input.value) {
            const pills = box.querySelectorAll('.fl-tag-pill');
            if (pills.length) pills[pills.length - 1].remove();
          }
        });
        input.addEventListener('blur', submit);
      });
    }

    function captureForm(panel) {
      panel.querySelectorAll('[data-field]').forEach(el => {
        const f = el.dataset.field;
        if (el.type !== 'checkbox') active.formState[f] = el.value;
      });
    }

    // Upgrades an EXISTING <input> in the DOM to the Smart Date/Time picker:
    // wraps it in a flex row, adds a calendar/clock icon button (when a native
    // picker exists for the kind), attaches DDC with the right suggester +
    // help text, and installs select-on-focus.
    //
    // kind = 'date'     → <input type=date> picker; dateSuggester
    //      | 'time'     → <input type=time> picker; timeSuggester
    //      | 'duration' → no native picker;          durationSuggester
    //
    // Idempotent — bails early if the input is already DDC-attached. Used by
    // wireSubFormHandlers to upgrade any [data-smart-input] in the panel.
    const SMART_INPUT_CFG = {
      date: {
        nativeType:  'date',
        icon:        'calendar_month',
        iconTitle:   'Pick a date',
        placeholder: 'today, next monday, 2026-12-31…',
        helpHtml:    'Type <kbd>today</kbd>, <kbd>tomorrow</kbd>, <kbd>next monday</kbd>, <kbd>2026-12-31</kbd>, or pick from the calendar.',
        emptyText:   'No matches — try "next monday" or a date like "2026-12-31".',
        suggester:   () => dateSuggester,
      },
      time: {
        nativeType:  'time',
        icon:        'schedule',
        iconTitle:   'Pick a time',
        placeholder: '9am, noon, 3:30pm, 18:00…',
        helpHtml:    'Type <kbd>9am</kbd>, <kbd>noon</kbd>, <kbd>3:30pm</kbd>, <kbd>18:00</kbd>, or pick from the clock.',
        emptyText:   'No matches — try "9am", "noon", or "18:00".',
        suggester:   () => timeSuggester,
      },
      duration: {
        nativeType:  null,
        icon:        null,
        placeholder: '30m, 2 weeks, P1D, PT45M…',
        helpHtml:    'Type <kbd>30m</kbd>, <kbd>2 weeks</kbd>, <kbd>1 day</kbd>, or an ISO <kbd>PT45M</kbd>.',
        emptyText:   'No matches — try "30m", "2 weeks", or an ISO like "PT45M".',
        suggester:   () => durationSuggester,
      },
    };

    function upgradeInputToSmart(input, kind) {
      if (input.__ddc) return;  // idempotent
      const cfg = SMART_INPUT_CFG[kind];
      if (!cfg) throw new Error('Unhandled smart input kind: ' + kind);  // HARD RULE 2

      // Force text type so we can render free-form values; placeholder added.
      if (input.type !== 'text') input.type = 'text';
      if (cfg.placeholder && !input.placeholder) input.placeholder = cfg.placeholder;

      // Wrap input in flex row so the icon-button + dropdown anchor inside it.
      const parent = input.parentNode;
      const row = el('div', 'fl-input-row');
      parent.insertBefore(row, input);
      row.appendChild(input);

      if (cfg.nativeType) {
        const pickBtn = el('button', 'fl-input-icon-btn');
        pickBtn.type = 'button';
        pickBtn.title = cfg.iconTitle;
        pickBtn.setAttribute('aria-label', cfg.iconTitle);
        pickBtn.innerHTML = '<span class="material-symbols-rounded">' + cfg.icon + '</span>';
        const native = el('input', 'fl-hidden-picker');
        native.type = cfg.nativeType;
        row.appendChild(pickBtn);
        row.appendChild(native);

        pickBtn.addEventListener('click', () => {
          const cur = input.value.trim();
          if (cfg.nativeType === 'date' && /^\d{4}-\d{2}-\d{2}$/.test(cur)) native.value = cur;
          if (cfg.nativeType === 'time' && /^\d{2}:\d{2}$/.test(cur))       native.value = cur;
          if (typeof native.showPicker === 'function') {
            try { native.showPicker(); }
            catch (err) {
              // HARD RULE 1 — surface error rather than silently swallow.
              console.warn('[upgradeInputToSmart] showPicker failed, falling back to focus:', err);
              native.focus();
            }
          } else {
            native.focus();  // older browsers
          }
        });
        native.addEventListener('change', () => {
          if (!native.value) return;
          input.value = native.value;
          input.dispatchEvent(new Event('input', { bubbles: true }));
          if (input.__ddc) input.__ddc.hideDropdown();
        });
      }

      window.DynamicDropdownControl.attach(input, {
        onKeyChange: (query, ctrl, { source }) => {
          if (source === 'focus') {
            ctrl.showDropdown([], cfg.helpHtml, { html: true });
          } else {
            ctrl.showDropdown(cfg.suggester()(query), cfg.emptyText);
          }
        },
      });

      input.addEventListener('focus', () => {
        setTimeout(() => input.select(), 0);
      });
    }

    // Mount (or remount) the Wait sidebar's mode slot:
    //   1. create the <input> (Acelle owns the input — DDC just binds behavior)
    //   2. attach DynamicDropdownControl with an onKeyChange callback that
    //      computes suggestions via the appropriate Acelle suggester
    // `force=true` tears down the prior DDC instance first (used after mode toggle).
    function mountWaitSuggest(slot, force) {
      const mode = slot.dataset.waitModeCurrent;
      if (force) {
        const prevInput = slot.querySelector('input');
        if (prevInput && prevInput.__ddc) prevInput.__ddc.destroy();
        slot.innerHTML = '';
      }

      // Per-mode wiring (suggester + field name + prefill + empty msg +
      // placeholder + helpHtml shown on focus before user types).
      // HARD RULE 2 — exhaustive switch on the mode discriminator.
      // helpHtml strings use <kbd> chips for keyword highlights; matches the
      // Operation sidebar's Tags input hint style.
      let cfg;
      switch (mode) {
        case 'duration':
          cfg = {
            field: 'duration',
            initial: slot.dataset.initialDuration || 'PT1H',
            placeholder: 'e.g. 30m, 2 weeks, PT1H',
            suggester: durationSuggester,
            emptyText: 'No matches — try "30m", "2 weeks", or an ISO like "PT45M".',
            helpHtml:
              'Type a duration like <kbd>30m</kbd>, <kbd>2 weeks</kbd>, ' +
              '<kbd>1 hour</kbd>, or an ISO <kbd>PT1H</kbd>.',
          };
          break;
        case 'until':
          cfg = {
            field: 'until',
            initial: slot.dataset.initialUntil || isoLocal(atTime(addDays(new Date(), 1), 9, 0)),
            placeholder: 'e.g. next Monday, tomorrow 9am, 2026-12-31T18:00',
            suggester: untilSuggester,
            emptyText: 'No matches — try "next Monday", "tomorrow 9am", or a datetime.',
            helpHtml:
              'Type <kbd>today</kbd>, <kbd>tomorrow</kbd>, <kbd>next monday</kbd>, ' +
              '<kbd>8pm</kbd>, <kbd>11:30am</kbd> — or pick from the calendar.',
          };
          break;
        default:
          throw new Error('Unhandled wait mode: ' + mode);
      }

      // Acelle owns the <input> — collectFormData reads `[data-field]` on it.
      // For 'until' mode we wrap input + native datetime picker icon in a flex
      // row so users can either type ("next mon 9am") or click the calendar
      // icon to open the OS-native picker (escape hatch for non-typers).
      const input = el('input', 'fl-input');
      input.type = 'text';
      input.value = cfg.initial;
      input.placeholder = cfg.placeholder;
      input.setAttribute('data-field', cfg.field);

      if (mode === 'until') {
        const row = el('div', 'fl-input-row');
        const pickBtn = el('button', 'fl-input-icon-btn');
        pickBtn.type = 'button';
        pickBtn.title = 'Pick date and time';
        pickBtn.setAttribute('aria-label', 'Pick date and time');
        pickBtn.innerHTML = '<span class="material-symbols-rounded">calendar_month</span>';
        // Hidden datetime-local: clicking calendarBtn calls .showPicker() on it,
        // and on `change` we copy the picked value back to the visible input.
        const native = el('input', 'fl-hidden-picker');
        native.type = 'datetime-local';
        row.appendChild(input);
        row.appendChild(pickBtn);
        row.appendChild(native);
        slot.appendChild(row);

        pickBtn.addEventListener('click', () => {
          const cur = input.value.trim();
          if (/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}/.test(cur)) native.value = cur.slice(0, 16);
          if (typeof native.showPicker === 'function') {
            try { native.showPicker(); }
            catch (err) {
              // HARD RULE 1 — surface error rather than silently swallow.
              console.warn('[mountWaitSuggest] showPicker failed, falling back to focus:', err);
              native.focus();
            }
          } else {
            native.focus();  // older browsers — best-effort
          }
        });
        native.addEventListener('change', () => {
          if (!native.value) return;
          input.value = native.value;
          input.dispatchEvent(new Event('input', { bubbles: true }));
          if (input.__ddc) input.__ddc.hideDropdown();
        });
      } else {
        slot.appendChild(input);
      }

      // DDC handles UI: open/close, render items, keyboard nav.
      // Acelle handles "what to suggest" inside onKeyChange — this is where
      // an alternative implementation could fetch() from a server endpoint.
      // On `source: 'focus'` (just opened, no typing yet) show a static help
      // hint instead of running the suggester on the seeded value (which would
      // echo the current ISO datetime back as a fake suggestion). On
      // `source: 'input'` (user typed) run the real suggester.
      window.DynamicDropdownControl.attach(input, {
        onKeyChange: (query, ctrl, { source }) => {
          if (source === 'focus') {
            // helpHtml is HTML — pass {html: true} so DDC renders <kbd> chips.
            // Source is hard-coded, not user input, so no XSS surface.
            ctrl.showDropdown([], cfg.helpHtml, { html: true });
          } else {
            ctrl.showDropdown(cfg.suggester(query), cfg.emptyText);
          }
        },
      });

      // Select-all on focus so a click into the field is enough to start
      // overwriting (no manual select needed). Defer one tick: a mouse click
      // sets the caret AFTER 'focus' fires, which would wipe the selection
      // immediately if we called select() synchronously.
      input.addEventListener('focus', () => {
        setTimeout(() => input.select(), 0);
      });
    }

    // For wait/wait_until nodes, resolve which backend type the sidebar's
    // current mode selection should produce. Returns null for non-wait nodes.
    function resolveWaitTargetType(panel, node) {
      if (node.type !== 'wait' && node.type !== 'wait_until') return null;
      const slot = panel.querySelector('[data-wait-suggest-slot]');
      if (!slot) return null;
      switch (slot.dataset.waitModeCurrent) {
        case 'duration': return 'wait';
        case 'until':    return 'wait_until';
        default: throw new Error('Unhandled wait mode: ' + slot.dataset.waitModeCurrent);
      }
    }

    async function doSave() {
      if (!active) return;
      const { panel, node } = active;

      // Send nodes have no bottom Save button (see sidebarShell). The
      // Ctrl/Cmd+Enter shortcut still routes here, so:
      //   - Stage 1 (no emailUid) → keyboard-save the setup form.
      //   - Stage 2/3 (emailUid set) → no-op. The generic PATCH path below
      //     would collect zero email fields and FlowMutator REPLACE would
      //     drop emailUid, orphaning the AutomationEmail (incident 2026-05-21).
      if (node.type === 'send') {
        if (!node.data?.emailUid) return doSendStage1Save(panel);
        return;
      }

      const data = collectFormData(panel, node);
      try {
        let res;
        if (node.type === 'trigger') {
          const triggerKey = data.key;
          if (!triggerKey) throw new Error('Pick a trigger event first');
          delete data.key;
          res = await api('PATCH', urls.trigger, { triggerKey, data });
        } else {
          // Wait sidebar — when the user toggled mode, send `type` so the
          // backend swaps node type via FlowMutator::replaceNodeWithType.
          const body = { data };
          const targetType = resolveWaitTargetType(panel, node);
          if (targetType && targetType !== node.type) body.type = targetType;
          res = await api('PATCH', urls.update.replace('__ID__', encodeURIComponent(node.id)), body);
        }
        applyFlow(res.flow);
        toast('Saved', 'ok');
        close();
      } catch (e) { toast(e.message, 'error'); }
    }

    // Stage 1 save — collects subject / from / reply / tracking + DKIM /
    // skipFailed. Two paths based on whether an email already exists:
    //   - First-time setup (no emailUid)  → POST createEmailForNode → Stage 2.
    //   - Re-edit from Stage 3 (emailUid) → PATCH emailSettingsSave (PRESERVES
    //     the bound template + content; createEmailForNode would wipe it as
    //     part of its idempotent orphan cleanup).
    async function doSendStage1Save(panel) {
      if (!active) return;
      const node = active.node;
      const v = (sel) => panel.querySelector(sel);
      const txt = (name) => v(`[data-field="email.${name}"]`)?.value.trim();
      const chk = (name) => v(`[data-field="email.${name}"]`)?.checked;

      const subject    = txt('subject');
      const fromName   = txt('fromName');
      const fromEmail  = txt('fromEmail');
      const replyTo    = txt('replyTo') || null;

      if (!subject)   return toast('Subject is required',     'error');
      if (!fromName)  return toast('From name is required',   'error');
      if (!fromEmail) return toast('From email is required',  'error');

      const body = {
        subject, fromName, fromEmail, replyTo,
        trackOpen:  chk('trackOpen'),
        trackClick: chk('trackClick'),
        signDkim:   chk('signDkim'),
        skipFailed: chk('skipFailed'),
      };

      try {
        const isFirstTime = !node.data?.emailUid;
        const url = (isFirstTime ? urls.emailCreate : urls.emailSettingsSave)
                      .replace('__ID__', encodeURIComponent(node.id));
        const method = isFirstTime ? 'POST' : 'PATCH';
        const res = await api(method, url, body);
        if (Array.isArray(res.emails)) ctx.emails = res.emails;
        applyFlow(res.flow);
        toast(isFirstTime
          ? 'Email setup saved — pick a template next'
          : 'Settings saved', 'ok');
        try { sidebar.open(tree.node(node.id)); } catch (_) { sidebar.close(); }
      } catch (e) { toast(e.message, 'error'); }
    }

    // Re-enter Stage 1 form pre-filled from cached live settings. Used by:
    //   - "Edit" buttons on Overview rows (subject / from / reply / tracking)
    //   - The Setup step in the indicator pill (data-act="goto-stage")
    // Action delegation lives on the panel so re-rendering the slot doesn't
    // require re-binding handlers.
    function doSendStage1ReEnter(panel) {
      const slot = panel.querySelector('[data-send-wizard]');
      if (!slot) return;
      let settings = {};
      try { settings = JSON.parse(slot.dataset.lastSettings || '{}'); } catch (_) {}
      const flags = { hasEmail: true, hasContent: !!settings.hasContent };
      slot.innerHTML = renderStageIndicator('setup', flags) + renderSendStage1Form(settings);
      slot.dataset.currentStage = 'setup';
      initIdentitySelectsIn(slot);
    }

    // Stage navigation — clicked from the indicator pill. Re-renders the
    // wizard body for the requested stage using the cached settings; never
    // re-fetches (so it's snappy and doesn't lose unsaved Stage 1 input).
    // Locked steps (template w/o emailUid, overview w/o content) never render
    // a button so we don't need to guard here — the rule lives in
    // renderStageIndicator().
    function doGotoStage(panel, stage) {
      const slot = panel.querySelector('[data-send-wizard]');
      if (!slot) return;
      let settings = {};
      try { settings = JSON.parse(slot.dataset.lastSettings || '{}'); } catch (_) {}
      switch (stage) {
        case 'setup':    doSendStage1ReEnter(panel); return;
        case 'template':
          slot.innerHTML = renderSendStage2(settings);
          slot.dataset.currentStage = 'template';
          return;
        case 'overview':
          slot.innerHTML = renderSendStage3Summary(settings);
          slot.dataset.currentStage = 'overview';
          return;
        default:
          throw new Error('Unhandled stage: ' + stage);
      }
    }

    function doOpenEmailEditor() {
      if (!active) return;
      const emailUid = active.node?.data?.emailUid;
      if (!emailUid) return toast('No email to edit', 'error');
      const url = urls.emailEdit.replace('__EMAIL_UID__', encodeURIComponent(emailUid));
      window.open(url, '_blank', 'noopener');
    }

    function doOpenTemplatePicker(initialTab) {
      if (!active) return;
      const node = active.node;
      templatePickerModal.open({
        nodeId: node.id,
        initialTab: initialTab || 'gallery',
        onApplied: () => {
          // Re-hydrate AND stay on the Template stage. User opened the picker
          // from Stage 2 (Template tab); auto-jumping them to Overview right
          // after a pick disrupts the "I'm choosing templates" flow. They can
          // click the Overview tab themselves when ready.
          if (active && active.node.id === node.id) {
            hydrateSendWizard(active.panel, node, { stayOnTemplate: true });
          }
        },
      });
    }

    function doOpenEmailPreview() {
      if (!active) return;
      const node = active.node;
      const url = urls.emailPreview.replace('__ID__', encodeURIComponent(node.id));
      emailPreviewModal.open({ url });
    }

    // Copy the API 3.0 trigger's POST endpoint to the clipboard. Falls back to
    // a manual select-and-prompt if the Clipboard API is unavailable (older
    // browsers / non-https origins).
    async function doCopyTriggerEndpoint(panel) {
      const input = panel.querySelector('[data-trigger-api-endpoint]');
      if (!input) return;
      const value = input.value;
      try {
        if (navigator.clipboard?.writeText) {
          await navigator.clipboard.writeText(value);
        } else {
          input.select();
          document.execCommand('copy');
        }
        toast('Endpoint copied to clipboard', 'ok');
      } catch (e) {
        toast('Could not copy — select the URL and use Ctrl+C', 'error');
      }
    }

    function doOpenEmailTest() {
      if (!active) return;
      const node = active.node;
      formModal.open({
        title: 'Send test email',
        fields: [
          { name: 'to', label: 'Recipient', type: 'email', required: true,
            placeholder: 'me@example.com',
            hint: 'A one-off copy of this email goes to this address. Counts toward your credit (matches Campaign send-test behavior).' },
        ],
        submitText: 'Send test',
        busyText: 'Sending...',
        onSubmit: async (values) => {
          try {
            const url = urls.emailTest.replace('__ID__', encodeURIComponent(node.id));
            const res = await api('POST', url, values);
            formModal.close();
            toast(res.message || 'Test sent', 'ok');
          } catch (e) { toast(e.message, 'error'); }
        },
      });
    }

    // doReplaceEmailForNode removed — feature dropped in 2026-05-02 cleanup.
    // Email is bound to the Send node 1:1 via Send-node delete cascade
    // (cleanupOrphanEmails in AutomationFlowController::deleteNode); admins
    // who want a fresh email replace the Send node itself (Actions → Delete).

    async function doDelete(mode) {
      if (!active) return;
      const node = active.node;
      const word = mode === 'cascade' ? 'cascade-delete' : 'delete';
      if (!confirm(`${word} "${node.data?.title || node.type}"?`)) return;
      try {
        const url = urls.delete.replace('__ID__', encodeURIComponent(node.id)) + `?mode=${mode}`;
        const res = await api('DELETE', url);
        applyFlow(res.flow);
        toast('Deleted', 'ok');
        close();
      } catch (e) { toast(e.message, 'error'); }
    }

    return { open, close };
  })();

  function collectFormData(panel, node) {
    const data = {};
    panel.querySelectorAll('[data-field]').forEach(el => {
      const f = el.dataset.field;
      if (el.type === 'checkbox') return;
      const val = el.value;
      data[f] = val === '' ? null : val;
    });
    // Array fields by name
    const checkedDows = Array.from(panel.querySelectorAll('input[name="daysOfWeek"]:checked')).map(i => i.value);
    if (panel.querySelector('input[name="daysOfWeek"]')) data.daysOfWeek = checkedDows;
    const checkedDoms = Array.from(panel.querySelectorAll('input[name="daysOfMonth"]:checked')).map(i => i.value);
    if (panel.querySelector('input[name="daysOfMonth"]')) data.daysOfMonth = checkedDoms;
    // Tag pills
    panel.querySelectorAll('.fl-tag-input').forEach(box => {
      const f = box.dataset.field;
      if (!f) return;
      data[f] = Array.from(box.querySelectorAll('.fl-tag-pill'))
        .map(p => p.firstChild.textContent.trim())
        .filter(Boolean);
    });
    // Drop nulls so server's `sometimes|nullable` keeps existing values out of payload
    return data;
  }

  // ─── Sidebar shell + per-type forms ────────────────────────────────────

  function sidebarShell(node) {
    const cfg = TYPES[node.type] || { icon: 'circle', label: node.type };
    const isRoot = tree.isRoot(node.id);
    // Send-node sidebar is a wizard where every stage already has its own
    // save (Stage 1 "Save & Continue", Stage 2 picker/Apply, Stage 3 row
    // actions). The shared header title input and bottom Save are dropped
    // for Send to avoid the past trap where a generic PATCH /nodes/{id}
    // collected only `title` and let FlowMutator's REPLACE semantics strip
    // `emailUid`, orphaning the bound AutomationEmail (incident 2026-05-21).
    const isSend = node.type === 'send';
    return `
      <header class="fl-sidebar-header">
        <div class="fl-sidebar-icon"><span class="material-symbols-rounded">${cfg.icon}</span></div>
        <div class="fl-sidebar-titles">
          <div class="fl-sidebar-kind">${escapeHtml(cfg.label)}</div>
          ${isSend ? '' : `<input type="text" class="fl-sidebar-title-input" data-field="title"
                 value="${escapeAttr(node.data?.title || '')}" placeholder="Untitled">`}
        </div>
        <button type="button" class="fl-sidebar-close" aria-label="Close">
          <span class="material-symbols-rounded">close</span>
        </button>
      </header>
      <div class="fl-sidebar-body">${renderForm(node)}</div>
      <footer class="fl-sidebar-footer">
        ${isRoot ? '' : `
          <div class="mc-dropdown" data-dropdown>
            <button type="button" class="mc-btn mc-btn-secondary mc-btn-sm" data-dropdown-trigger>
              Actions <span class="material-symbols-rounded">expand_less</span>
            </button>
            <div class="mc-dropdown-menu mc-dropdown-menu-up" style="min-width:240px;white-space:nowrap">
              <a class="mc-dropdown-item mc-dropdown-item-danger" href="#" data-act="delete-shift"
                 title="Delete this node — children reconnect to the parent">
                <span class="material-symbols-rounded">delete</span>
                Delete (keep children)
              </a>
              <a class="mc-dropdown-item mc-dropdown-item-danger" href="#" data-act="delete-cascade"
                 title="Delete this node and everything below it">
                <span class="material-symbols-rounded">delete_sweep</span>
                Delete with children
              </a>
            </div>
          </div>
        `}
        <div class="grow"></div>
        ${isSend ? '' : `
          <button type="button" class="fl-btn fl-sidebar-cancel">Cancel</button>
          <button type="button" class="fl-btn fl-btn-primary fl-sidebar-save">Save</button>
        `}
      </footer>
    `;
  }

  function renderForm(node) {
    const d = node.data || {};
    switch (node.type) {
      case 'trigger':    return renderTriggerForm(d);
      case 'send':       return renderSendForm(d);
      // Wait + WaitUntil share one unified form. Backend node type stays
      // distinct (wait stores data.duration, wait_until stores data.until);
      // the form picks initial mode from node.type and the mode toggle can
      // switch between them via PATCH {type: ...} (see doSave).
      case 'wait':       return renderWaitForm(d, node);
      case 'wait_until': return renderWaitForm(d, node);
      case 'condition':  return renderConditionForm(d);
      case 'operation':  return renderOperationForm(d);
      case 'webhook':    return renderWebhookForm(d);
    }
    return '';
  }

  // Trigger sidebar — two states by `d.key` presence.
  //   State A (no key)  → callout + "Choose a trigger" button → opens picker modal.
  //   State B (has key) → summary card (icon + name + desc) + Change button + per-key sub-form.
  // The 12-card grid lives in a modal (triggerPickerModal) so the sidebar
  // stays focused on the configured trigger and its options.
  function renderTriggerForm(d) {
    const hidden = `<input type="hidden" data-field="key" value="${escapeAttr(d.key || '')}">`;

    if (!d.key) {
      return `
        ${hidden}
        <div class="fl-wizard-callout">
          <div class="fl-wizard-callout-title">Pick what starts this automation</div>
          <div class="fl-wizard-callout-desc">A trigger is the event that puts a subscriber into the workflow — a new sign-up, a tag added, a recurring schedule, …</div>
        </div>
        <div class="fl-field">
          <button type="button" class="fl-btn fl-btn-primary" data-act="open-trigger-picker">
            Choose a trigger
          </button>
        </div>
      `;
    }

    const meta = ctx.triggerKeys.find(k => k.value === d.key) || { label: d.key, desc: '', icon: 'bolt' };
    return `
      ${hidden}
      <div class="mc-action-rows fl-trigger-action-rows">
        <div class="mc-action-row">
          <div class="mc-action-row-thumb" style="background:var(--chart-3-bg)">
            <span class="material-symbols-rounded" style="font-size:28px;color:var(--chart-3);font-variation-settings:'FILL' 0,'wght' 400,'GRAD' 0,'opsz' 24">${escapeHtml(meta.icon || 'bolt')}</span>
          </div>
          <div class="mc-action-row-content">
            <div class="mc-action-row-title">${escapeHtml(meta.label)}</div>
            ${meta.desc ? `<div class="mc-action-row-desc">${escapeHtml(meta.desc)}</div>` : ''}
          </div>
          <button type="button" class="mc-btn mc-btn-secondary mc-btn-sm" data-act="open-trigger-picker" style="flex-shrink:0">
            Change
          </button>
        </div>
      </div>
      <div class="fl-trigger-subform" data-trigger-subform>${renderTriggerSubform(d.key, d)}</div>
    `;
  }

  function renderTriggerSubform(key, d) {
    switch (key) {
      case 'weekly-recurring':      return weeklyForm(d);
      case 'monthly-recurring':     return monthlyForm(d);
      case 'specific-date':         return specificDateForm(d);
      case 'say-happy-birthday':
      case 'subscriber-added-date': return birthdayForm(d);
      case 'tag-based':
      case 'remove-tag':            return tagBasedForm(d);
      case 'attribute-update':      return attributeUpdateForm(d);
      case 'woo-abandoned-cart':    return wooForm(d);
      case 'api-3-0':               return apiV3Form(d);
      // Welcome / goodbye fire on list subscribe / unsubscribe events; no
      // per-row config needed beyond picking the trigger.
      case 'welcome-new-subscriber':
      case 'say-goodbye-subscriber':
        return triggerInfoCard('How it works',
          key === 'welcome-new-subscriber'
            ? 'Subscribers enter this workflow the moment they confirm their subscription to the bound mailing list. No further configuration needed.'
            : 'Subscribers enter this workflow the moment they unsubscribe from the bound mailing list. No further configuration needed.');
      default: return '';
    }
  }

  // API 3.0 trigger — workflow is started by an external HTTP POST. The only
  // "config" the operator needs is the endpoint URL their app should hit.
  // Read-only field with a Copy button + a short usage hint.
  function apiV3Form(d) {
    const endpoint = ctx.triggerConfig?.apiEndpoint || '';
    return `
      <div class="fl-field">
        <span class="fl-field-label">POST endpoint</span>
        <div style="display:flex;gap:6px;align-items:stretch">
          <input type="text" class="fl-input" readonly value="${escapeAttr(endpoint)}"
                 style="font-family:'JetBrains Mono', ui-monospace, monospace; font-size:12px"
                 data-trigger-api-endpoint
                 onclick="this.select()">
          <button type="button" class="mc-btn mc-btn-secondary mc-btn-sm" data-act="copy-trigger-endpoint"
                  title="Copy endpoint URL" style="flex-shrink:0">
            <span class="material-symbols-rounded" style="font-size:16px;vertical-align:middle">content_copy</span>
            Copy
          </button>
        </div>
        <div class="fl-field-hint">
          POST a subscriber payload (with at least <code>EMAIL</code>) to this URL — Acelle will create / find the contact
          on the bound list and start the workflow for them. Authenticate with the customer API token in the
          <code>Authorization: Bearer …</code> header.
        </div>
      </div>
    `;
  }

  // Generic info card for triggers that have no configurable options. Keeps
  // the sidebar from looking empty + tells the operator "this is how it
  // fires, you don't need to do anything else".
  function triggerInfoCard(title, body) {
    return `
      <div class="fl-wizard-callout" style="background:#F8FAFC">
        <div class="fl-wizard-callout-title">${escapeHtml(title)}</div>
        <div class="fl-wizard-callout-desc">${escapeHtml(body)}</div>
      </div>
    `;
  }

  function weeklyForm(d) {
    const days = (d.daysOfWeek || []).map(String);
    return `
      <div class="fl-field">
        <span class="fl-field-label">Days of week</span>
        <div class="fl-checkbox-row">
          ${DAYS.map(x => `
            <label><input type="checkbox" name="daysOfWeek" value="${x.value}" ${days.includes(x.value) ? 'checked' : ''}>${x.label}</label>
          `).join('')}
        </div>
      </div>
      ${atField(d.at)}
    `;
  }

  function monthlyForm(d) {
    const dom = (d.daysOfMonth || []).map(String);
    let cells = '';
    for (let i = 1; i <= 31; i++) {
      const v = String(i);
      cells += `<label><input type="checkbox" name="daysOfMonth" value="${v}" ${dom.includes(v) ? 'checked' : ''}>${v}</label>`;
    }
    return `
      <div class="fl-field">
        <span class="fl-field-label">Days of month</span>
        <div class="fl-month-grid">${cells}</div>
      </div>
      ${atField(d.at)}
    `;
  }

  function specificDateForm(d) {
    return `
      <div class="fl-field">
        <span class="fl-field-label">Date</span>
        <input type="date" class="fl-input" data-field="date" data-smart-input="date" value="${escapeAttr(d.date || '')}">
      </div>
      ${atField(d.at)}
    `;
  }

  function birthdayForm(d) {
    return `
      ${fieldUidPicker(d.fieldUid, 'Date field')}
      <div class="fl-field">
        <span class="fl-field-label">Send before</span>
        <input type="text" class="fl-input" data-field="before" data-smart-input="duration" value="${escapeAttr(d.before || '')}" placeholder="P0D for same day, P1D for 1 day before">
      </div>
      ${atField(d.at)}
    `;
  }

  function tagBasedForm(d) {
    return `
      <div class="fl-field">
        <span class="fl-field-label">Tags</span>
        ${tagInput('tags', d.tags || [])}
        <div class="fl-field-hint">Press Enter or comma to add. Backspace to remove.</div>
      </div>
    `;
  }

  function attributeUpdateForm(d) {
    return `
      ${fieldUidPicker(d.fieldUid, 'Field')}
      <div class="fl-field">
        <span class="fl-field-label">Operator</span>
        <select class="fl-select" data-field="operator">
          ${FIELD_OPERATORS.map(o => `<option value="${o.value}" ${o.value === d.operator ? 'selected' : ''}>${o.label}</option>`).join('')}
        </select>
      </div>
      <div class="fl-field">
        <span class="fl-field-label">Value</span>
        <input type="text" class="fl-input" data-field="value" value="${escapeAttr(d.value || '')}">
      </div>
    `;
  }

  function wooForm(d) {
    return `
      <div class="fl-field">
        <span class="fl-field-label">Woo store URL</span>
        <input type="url" class="fl-input" data-field="connectUrl" value="${escapeAttr(d.connectUrl || '')}" placeholder="https://shop.example.com">
      </div>
    `;
  }

  function atField(val) {
    return `
      <div class="fl-field">
        <span class="fl-field-label">At time</span>
        <input type="time" class="fl-input" data-field="at" data-smart-input="time" value="${escapeAttr(val || '09:00')}">
      </div>
    `;
  }

  // Render one row of the email summary list (settings-nav style — icon in
  // colored 36px box on the left, label/value stacked, action button on the
  // right). Mirrors the rui/ui "Settings Nav" component pattern.
  function renderEmailSummaryRow(icon, label, valueHtml, opts) {
    opts = opts || {};
    const cls = opts.danger ? ' is-danger' : (opts.warning ? ' is-warning' : '');
    const action = opts.action
      ? `<button type="button" class="mc-btn mc-btn-secondary mc-btn-sm fl-email-summary-action"
                 data-act="${escapeAttr(opts.action.act)}"${opts.action.title ? ` title="${escapeAttr(opts.action.title)}"` : ''}>
           ${escapeHtml(opts.action.label)}
         </button>`
      : '';
    return `
      <li class="fl-email-summary-row${cls}">
        <span class="fl-email-summary-icon">
          <span class="material-symbols-rounded">${escapeHtml(icon)}</span>
        </span>
        <div class="fl-email-summary-text">
          <div class="fl-email-summary-label">${escapeHtml(label)}</div>
          <div class="fl-email-summary-value">${valueHtml}</div>
        </div>
        ${action}
      </li>`;
  }

  // Fetch the full email settings + decorations for a Send node and replace
  // the skeleton rows in [data-email-summary] with real content.
  // Fetch live email settings + decide which Send-wizard stage to render.
  // Stage 1 is rendered synchronously in renderSendForm (no emailUid case);
  // here we handle the Stage 2 vs Stage 3 split based on `hasContent`.
  async function hydrateSendWizard(panel, node, opts = {}) {
    const slot = panel.querySelector('[data-send-wizard]');
    if (!slot) return;
    try {
      const url = urls.emailSettingsShow.replace('__ID__', encodeURIComponent(node.id));
      const res = await api('GET', url);
      const s = res.settings || {};
      // Default landing: Overview if content exists, otherwise Template tab.
      // `opts.stayOnTemplate` overrides the auto-jump so picking a template
      // keeps the user on the Template stage (the mental context they were
      // already in). Without this they'd be yanked to Overview the instant a
      // template is applied — jarring when they're still browsing options.
      const goToOverview = !opts.stayOnTemplate && s.hasContent;
      slot.innerHTML = goToOverview ? renderSendStage3Summary(s) : renderSendStage2(s);
      slot.dataset.currentStage = goToOverview ? 'overview' : 'template';
      // Stash settings so stage navigation (Setup / Template / Overview tabs)
      // can re-render without re-fetching, and Stage 1 re-entry can pre-fill
      // from the live values, not the stale node.data.
      slot.dataset.lastSettings = JSON.stringify(s);
    } catch (e) {
      slot.innerHTML = `
        <ul class="fl-email-summary">
          <li class="fl-email-summary-row is-danger">
            <span class="fl-email-summary-icon material-symbols-rounded">error_outline</span>
            <div class="fl-email-summary-text">
              <div class="fl-email-summary-label">Failed to load</div>
              <div class="fl-email-summary-value">${escapeHtml(e.message || 'Network error')}</div>
            </div>
          </li>
        </ul>`;
    }
  }

  // Send — 3-stage wizard inside the sidebar:
  //   Stage 1 "Setup"     (no emailUid)         → full sender form
  //   Stage 2 "Template"  (email but no content) → pick template / paste HTML
  //   Stage 3 "Done"      (email + content)     → settings-nav summary
  // `renderSendForm` does the synchronous part (Stage 1 OR skeleton);
  // hydrateSendWizard() decides Stage 2 vs Stage 3 based on the live
  // `hasContent` flag returned by the settings endpoint.
  function renderSendForm(d) {
    if (!d.emailUid) {
      return renderStageIndicator('setup') + renderSendStage1Form();
    }
    // Stage 2 or 3 — defer to hydrate (needs hasContent from server).
    return `
      <div data-send-wizard>
        <div class="fl-stage-skeleton">
          <span class="fl-summary-skeleton"></span>
          <span class="fl-summary-skeleton" style="width:40%"></span>
        </div>
      </div>
    `;
  }

  // 3-step indicator pill — Setup → Template → Overview. Clickable: each step
  // becomes a button when its precondition is met (e.g. Template requires the
  // email row to exist; Overview requires content). Locked steps render plain.
  // `opts` = { hasEmail, hasContent } — defaults derived in callers.
  function renderStageIndicator(current, opts) {
    opts = opts || {};
    const hasEmail   = !!opts.hasEmail;
    const hasContent = !!opts.hasContent;
    const steps = [
      { id: 'setup',    label: 'Setup',    enabled: true },
      { id: 'template', label: 'Template', enabled: hasEmail },
      { id: 'overview', label: 'Overview', enabled: hasEmail && hasContent },
    ];
    const idx = steps.findIndex(s => s.id === current);
    return `
      <div class="fl-stage-indicator">
        ${steps.map((s, i) => {
          const isActive   = i === idx;
          const isDone     = i < idx;
          const isClick    = s.enabled && !isActive;
          const cls = [
            'fl-stage-step',
            isDone ? 'is-done' : '',
            isActive ? 'is-active' : '',
            isClick ? 'is-clickable' : '',
            !s.enabled ? 'is-locked' : '',
          ].filter(Boolean).join(' ');
          const tag  = isClick ? 'button' : 'span';
          const attr = isClick
            ? `type="button" data-act="goto-stage" data-stage="${escapeAttr(s.id)}"`
            : '';
          return `<${tag} class="${cls}" ${attr}>
            <span class="fl-stage-dot"><span class="material-symbols-rounded">check</span></span>
            ${escapeHtml(s.label)}
          </${tag}>`;
        }).join('<span class="fl-stage-divider"></span>')}
      </div>
    `;
  }

  // Toggle-card row — borrowed from rui/ui's `mc-radio-cards` + `mc-switch`
  // pattern (see showcase "Radio Cards"). Same visual chrome as radio cards
  // but with checkbox semantics — each card toggles independently. Active
  // tint is added via the panel-level `change` listener that flips
  // `.is-active` on the closest `.mc-radio-card`.
  function renderToggleCard(field, checked, title, desc) {
    // No `.active` class on ON state — user wanted no highlight (the global
    // `.mc-radio-card.active` rule tints the bg). Padding zeroed on the X axis
    // so the switch lines up with the sidebar body's left edge (matches the
    // form inputs above/below).
    return `
      <label class="mc-radio-card" style="padding-left:0;padding-right:0">
        <span class="mc-switch" style="flex:none">
          <input type="checkbox" class="mc-switch-input" data-field="${escapeAttr(field)}" ${checked ? 'checked' : ''}>
          <span class="mc-switch-slider"></span>
        </span>
        <div class="mc-radio-card-content">
          <div class="mc-radio-card-title">${escapeHtml(title)}</div>
          <div class="mc-radio-card-desc">${escapeHtml(desc)}</div>
        </div>
      </label>`;
  }

  // Wrap the rui/ui mc-identity-select primitive so the Stage 1 form can use
  // it for From email + Reply-to, just like the campaign editor (verified
  // sender autocomplete + per-domain validation, see showcase #identity-select).
  // The inner <input> keeps `data-field=...` so doSendStage1Save reads it
  // through the same selector path as the rest of the form.
  function renderIdentitySelect(opts) {
    const id          = opts.id;
    const field       = opts.field;
    const value       = opts.value || '';
    const linkTarget  = opts.linkTarget || '';
    const placeholder = opts.placeholder || '';
    return `
      <div class="mc-identity-select fl-identity-select" id="${escapeAttr(id)}"
           data-mc-identity-select
           data-url="${escapeAttr(urls.identityDropbox || '')}"
           data-name="${escapeAttr(field)}"
           data-value="${escapeAttr(value)}"
           ${linkTarget ? `data-link-target="${escapeAttr(linkTarget)}"` : ''}>
        <input type="email"
               class="mc-form-input mc-identity-select-input fl-input"
               data-field="${escapeAttr(field)}"
               value="${escapeAttr(value)}"
               placeholder="${escapeAttr(placeholder)}"
               autocomplete="new-password" autocorrect="off" autocapitalize="off"
               spellcheck="false" data-lpignore="true" data-form-type="other" data-1p-ignore>
      </div>
    `;
  }

  // After (re)rendering Stage 1 — upgrade any unenhanced identity-select
  // wrappers in `root`. McIdentitySelect.initAll skips wrappers that already
  // have `data-mc-identity-initialized`, so this is safe to call repeatedly.
  function initIdentitySelectsIn(root) {
    if (!root || !window.McIdentitySelect) return;
    try { window.McIdentitySelect.initAll(root); } catch (_) { /* harmless */ }
  }

  // Stage 1 — full sender setup (subject / from / reply / track / DKIM / skip).
  // Server-side createEmailForNode now accepts all of these.
  function renderSendStage1Form(prefill) {
    const def = ctx.emailDefaults || {};
    const v = prefill || {};
    const subject    = v.subject    ?? '';
    const fromName   = v.fromName   ?? def.fromName  ?? '';
    const fromEmail  = v.fromEmail  ?? def.fromEmail ?? '';
    const replyTo    = v.replyTo    ?? '';
    const trackOpen  = v.trackOpen  ?? true;
    const trackClick = v.trackClick ?? true;
    const signDkim   = v.signDkim   ?? true;
    const skipFailed = v.skipFailed ?? false;
    return `
      <div class="fl-wizard-callout">
        <div class="fl-wizard-callout-title">Set up the email</div>
        <div class="fl-wizard-callout-desc">Subject, sender identity, and tracking. After saving you'll pick a template next.</div>
      </div>
      <div class="fl-field">
        <span class="fl-field-label">Subject *</span>
        <input type="text" class="fl-input" data-field="email.subject" value="${escapeAttr(subject)}" placeholder="e.g. Welcome to {{ list.name }}">
      </div>
      <div class="fl-field">
        <span class="fl-field-label">From name *</span>
        <input type="text" class="fl-input" data-field="email.fromName" value="${escapeAttr(fromName)}">
      </div>
      <div class="fl-field">
        <span class="fl-field-label">From email *</span>
        ${renderIdentitySelect({
          id:        'fl-send-from-email',
          field:     'email.fromEmail',
          value:     fromEmail,
          linkTarget:'fl-send-reply-to',
        })}
      </div>
      <div class="fl-field">
        <span class="fl-field-label">Reply-to (optional)</span>
        ${renderIdentitySelect({
          id:          'fl-send-reply-to',
          field:       'email.replyTo',
          value:       replyTo,
          placeholder: 'defaults to From email',
        })}
      </div>
      <div class="fl-field">
        <span class="fl-field-label">Tracking & delivery</span>
        <div class="mc-radio-cards" data-mc-toggle-cards>
          ${renderToggleCard('email.trackOpen',  trackOpen,  'Track opens',  'Count when subscribers open the email — needed for opens-based Condition nodes downstream.')}
          ${renderToggleCard('email.trackClick', trackClick, 'Track clicks', 'Rewrite links so clicks are recorded — needed for click-based Conditions and link reports.')}
          ${renderToggleCard('email.signDkim',   signDkim,   'Sign with DKIM', 'Sign outgoing messages with the DKIM key configured for the sending domain. Improves deliverability.')}
          ${renderToggleCard('email.skipFailed', skipFailed, 'Skip failed messages', 'Continue the flow even if this email fails to deliver to a particular subscriber.')}
        </div>
      </div>
      <div class="fl-field">
        <button type="button" class="fl-btn fl-btn-primary" data-act="send-stage1-save">
          Save & Continue →
        </button>
      </div>
    `;
  }

  // Stage 2 — Template tab. Two variants:
  //   - Picker (no content yet): single "Browse templates" CTA.
  //   - Manage (has content):    thumbnail + Edit content / Change / Preview.
  // Picking / pasting a template re-hydrates and lands on Overview.
  function renderSendStage2(s) {
    const flags = { hasEmail: true, hasContent: !!s.hasContent };
    if (!s.hasContent) {
      return renderStageIndicator('template', flags) + `
        <div class="fl-wizard-callout">
          <div class="fl-wizard-callout-title">Step 2 — Pick the email template</div>
          <div class="fl-wizard-callout-desc">Choose a starter template from the gallery, or switch to the "Paste HTML" tab inside to use your own design. You can refine in the visual builder afterwards.</div>
        </div>
        <div class="fl-field">
          <button type="button" class="fl-btn fl-btn-primary" data-act="open-template-picker" data-tpl-tab="gallery">
            <span class="material-symbols-rounded" style="font-size:16px;vertical-align:middle">grid_view</span>
            Browse templates
          </button>
          <div class="fl-field-hint">System templates, your saved templates, or paste raw HTML — all in one picker.</div>
        </div>
      `;
    }
    return renderStageIndicator('template', flags) + renderSendStage2Manage(s);
  }

  // Stage 2 manage variant — shown when the email already has content. Lets
  // admins re-open the visual editor, switch to a different template, or
  // preview the rendered output without leaving the sidebar.
  function renderSendStage2Manage(s) {
    const thumbInner = s.thumbUrl
      ? `<img class="fl-template-thumb-img" src="${escapeAttr(s.thumbUrl)}" alt="Email preview">`
      : `<div class="fl-template-thumb-placeholder"><span class="material-symbols-rounded">image</span></div>`;
    return `
      <div class="fl-wizard-callout">
        <div class="fl-wizard-callout-title">Design your email</div>
        <div class="fl-wizard-callout-desc">Edit the design in the visual builder, swap to a different starter template, or preview the rendered email body.</div>
      </div>
      <button type="button" class="fl-template-thumb is-clickable" data-act="open-email-preview"
              title="Click to preview the rendered email">
        ${thumbInner}
        <span class="fl-template-thumb-overlay">
          <span class="material-symbols-rounded">visibility</span>
          Preview
        </span>
      </button>
      <div class="fl-template-meta">
        Last edited ${escapeHtml(s.lastEditedAt || '—')}${s.isCustomHtml ? ' · custom HTML' : ''}
      </div>
      <div class="fl-template-actions">
        <button type="button" class="fl-btn fl-btn-primary" data-act="open-email-editor">
          <span class="material-symbols-rounded" style="font-size:16px;vertical-align:middle">edit</span>
          Edit content
        </button>
        <button type="button" class="fl-btn fl-btn-ghost" data-act="open-template-picker" data-tpl-tab="gallery">
          <span class="material-symbols-rounded" style="font-size:16px;vertical-align:middle">grid_view</span>
          Change template
        </button>
      </div>
    `;
  }

  // Stage 3 — Overview. Read-only review of the configured email envelope.
  // Each envelope field renders as an `mc-action-row` (rui/ui showcase pattern):
  // colored thumb + icon, label as title, value as desc. Action stack at the
  // bottom: Preview email → Send test. Per-row Edit buttons are intentionally
  // gone — the Setup tab in the stage indicator is the edit path.
  function renderSendStage3Summary(s) {
    const flags    = { hasEmail: true, hasContent: !!s.hasContent };
    const subject  = s.subject  || '(untitled)';
    const fromName = s.fromName || '—';
    const fromEmail = s.fromEmail || '—';
    const replyTo  = s.replyTo  || '';
    const chip = (on, label) => `
      <span class="fl-overview-chip ${on ? 'is-on' : 'is-off'}">
        <span class="material-symbols-rounded">${on ? 'check_circle' : 'radio_button_unchecked'}</span>
        ${escapeHtml(label)}
      </span>`;
    // mc-action-row tuned for sidebar width: 40×40 thumb instead of 64×52,
    // tighter padding, value as desc text.
    const actionRow = (icon, color, title, valueHtml, isWarn) => `
      <div class="mc-action-row" style="padding:12px 14px;gap:12px;align-items:flex-start">
        <div class="mc-action-row-thumb" style="background:${color};width:40px;height:40px;border-radius:8px">
          <span class="material-symbols-rounded" style="color:${color.replace('-bg','')};font-size:20px">${icon}</span>
        </div>
        <div class="mc-action-row-content">
          <div class="mc-action-row-title" style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;color:var(--fl-text-muted);margin-bottom:4px">${escapeHtml(title)}</div>
          <div class="mc-action-row-desc" style="font-size:13px;color:${isWarn ? '#B45309' : 'var(--fl-text)'};line-height:1.4;word-break:break-word">${valueHtml}</div>
        </div>
      </div>`;
    const replyValue = replyTo
      ? escapeHtml(replyTo)
      : `<span style="display:inline-flex;align-items:center;gap:4px">
           <span class="material-symbols-rounded" style="font-size:14px;font-variation-settings:'FILL' 1,'wght' 600,'GRAD' 0,'opsz' 24">warning</span>
           Not set — replies go back to the sender
         </span>`;
    return renderStageIndicator('overview', flags) + `
      <div class="mc-action-rows" style="margin-top:6px;margin-bottom:14px">
        ${actionRow('subject',     'var(--chart-1-bg)',  'Subject',    `<strong style="font-weight:600">${escapeHtml(subject)}</strong>`, false)}
        ${actionRow('badge',       'var(--chart-2-bg)',  'From name',  escapeHtml(fromName), false)}
        ${actionRow('alternate_email','var(--chart-3-bg)','From email', escapeHtml(fromEmail), false)}
        ${actionRow('reply',       'var(--chart-6-bg)',  'Reply to',   replyValue, !replyTo)}
      </div>
      <div class="fl-field" style="margin-bottom:14px">
        <span class="fl-field-label">Tracking &amp; delivery</span>
        <div class="fl-overview-chips" style="margin-top:6px;margin-bottom:0">
          ${chip(!!s.trackOpen,  'Track opens')}
          ${chip(!!s.trackClick, 'Track clicks')}
          ${chip(!!s.signDkim,   'DKIM signed')}
          ${chip(!!s.skipFailed, 'Skip failures')}
        </div>
      </div>
      <div class="fl-overview-test">
        <button type="button" class="fl-btn fl-btn-ghost" data-act="open-email-preview" style="margin-bottom:8px">
          <span class="material-symbols-rounded" style="font-size:16px;vertical-align:middle">visibility</span>
          Preview email
        </button>
        <button type="button" class="fl-btn fl-btn-ghost" data-act="open-email-test">
          <span class="material-symbols-rounded" style="font-size:16px;vertical-align:middle">send</span>
          Send a test email
        </button>
        <div class="fl-overview-test-hint">Verify content and deliverability before activating.</div>
      </div>
    `;
  }

  // Wait — unified form for both `wait` (duration) and `wait_until` (datetime).
  // Mode toggle lets the user switch between the two without leaving the
  // sidebar; on Save, doSave() detects the swap and includes `type` in the
  // PATCH body so the backend swaps node type via FlowMutator::replaceNodeWithType.
  function renderWaitForm(d, node) {
    const mode = node && node.type === 'wait_until' ? 'until' : 'duration';
    const txt = WAIT_MODE_TEXTS[mode];
    return `
      <div class="fl-field">
        <span class="fl-field-label">How long should we wait?</span>
        <div class="fl-mode-toggle" role="radiogroup" data-wait-mode-toggle>
          <button type="button" class="fl-mode-toggle-btn ${mode === 'duration' ? 'is-active' : ''}"
                  role="radio" aria-checked="${mode === 'duration'}" data-wait-mode="duration">
            <span class="material-symbols-rounded">timer</span>
            <span>For a duration</span>
          </button>
          <button type="button" class="fl-mode-toggle-btn ${mode === 'until' ? 'is-active' : ''}"
                  role="radio" aria-checked="${mode === 'until'}" data-wait-mode="until">
            <span class="material-symbols-rounded">event</span>
            <span>Until a date / time</span>
          </button>
        </div>
        <div class="fl-field-hint" data-wait-mode-desc>${escapeHtml(txt.desc)}</div>
      </div>
      <div class="fl-field">
        <span class="fl-field-label" data-wait-suggest-label>${txt.title}</span>
        <div data-wait-suggest-slot
             data-wait-mode-current="${mode}"
             data-initial-duration="${escapeAttr(d.duration || '')}"
             data-initial-until="${escapeAttr(d.until || '')}"></div>
        <div class="fl-field-hint" data-wait-suggest-hint>${txt.hint}</div>
      </div>
    `;
  }

  // Condition
  function renderConditionForm(d) {
    return `
      <div class="fl-field">
        <span class="fl-field-label">Condition kind</span>
        <select class="fl-select" data-field="kind">
          <option value="open"  ${d.kind === 'open'  ? 'selected' : ''}>Email opened</option>
          <option value="click" ${d.kind === 'click' ? 'selected' : ''}>Email clicked</option>
        </select>
      </div>
      <div class="fl-field">
        <span class="fl-field-label">Email to check</span>
        ${selectFromList('emailUid', ctx.emails, d.emailUid)}
      </div>
      <div class="fl-field">
        <span class="fl-field-label">Email link UID (click only)</span>
        <input type="text" class="fl-input" data-field="emailLinkUid" value="${escapeAttr(d.emailLinkUid || '')}" placeholder="leave blank for any link">
      </div>
      ${durationForm(d.timeout, 'timeout', 'Timeout (how long to wait for the event)')}
    `;
  }

  // Operation
  function renderOperationForm(d) {
    const current = OPERATION_KINDS.find(k => k.value === d.kind) || OPERATION_KINDS[0];
    return `
      <div class="fl-field">
        <span class="fl-field-label">Action</span>
        <div class="fl-field-hint" style="margin-top:-2px;margin-bottom:8px">
          Choose an action that applies to contacts who reach this step.
        </div>
        ${renderListSwitcher({
          name: 'kind',
          options: OPERATION_KINDS,
          selected: current.value,
          onChangeAttr: 'operation-kind',
          searchPlaceholder: 'Search actions…',
        })}
      </div>
      <div data-operation-subform>${renderOperationSubform(d.kind, d)}</div>
    `;
  }

  // List-switcher picker — same chrome as /rui/ui's `mc-list-switcher`
  // showcase: the current selection renders as a read-only label (avatar +
  // name) and the dropdown trigger is a tiny icon-only button next to it.
  // Hidden input carries the value; the standard `change` event is dispatched
  // on selection so existing `[data-on-change="…"]` listeners (e.g.
  // `operation-kind`) keep working.
  function renderListSwitcher({ name, options, selected, onChangeAttr, searchPlaceholder }) {
    const sel = options.find(o => o.value === selected) || options[0];
    const placeholder = searchPlaceholder || 'Search…';
    return `
<div data-mc-switcher style="display:flex;align-items:flex-start;gap:10px;position:relative">
        <input type="hidden" data-field="${escapeAttr(name)}" value="${escapeAttr(sel.value)}"
               ${onChangeAttr ? `data-on-change="${escapeAttr(onChangeAttr)}"` : ''}>
        <!-- Read-only display of the current selection: title + description -->
        <div style="flex:1;min-width:0">
          <div data-switcher-name style="font-size:13px;font-weight:600;color:var(--fl-text);overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${escapeHtml(sel.label)}</div>
          <div data-switcher-meta style="font-size:11px;color:var(--fl-text-muted);margin-top:2px;line-height:1.35">${escapeHtml(sel.meta || '')}</div>
        </div>
        <div class="mc-list-switcher" data-switcher-shell>
          <button type="button" class="mc-list-switcher-trigger" data-list-switcher-trigger
                  title="Change action" style="gap:4px;padding:4px 8px;font-size:12px">
            Choose action
            <span class="material-symbols-rounded">unfold_more</span>
          </button>
          <div class="mc-list-switcher-dropdown" style="right:0;left:auto">
            <div class="mc-list-switcher-search">
              <span class="material-symbols-rounded">search</span>
              <input type="text" placeholder="${escapeAttr(placeholder)}" data-list-switcher-search>
            </div>
            <div class="mc-list-switcher-items" data-list-switcher-items>
              ${options.map(opt => `
                <a href="#" class="mc-list-switcher-item ${opt.value === sel.value ? 'active' : ''}"
                   data-name="${escapeAttr(opt.label.toLowerCase() + ' ' + (opt.meta || '').toLowerCase())}"
                   data-switcher-value="${escapeAttr(opt.value)}"
                   data-switcher-label="${escapeAttr(opt.label)}"
                   data-switcher-meta="${escapeAttr(opt.meta || '')}">
                  <div class="mc-avatar mc-avatar-sm mc-avatar-${opt.avatarVariant || 1}">${escapeHtml(opt.avatar || opt.label.charAt(0))}</div>
                  <div class="mc-list-switcher-item-content">
                    <div class="mc-list-switcher-item-name">${escapeHtml(opt.label)}</div>
                    ${opt.meta ? `<div class="mc-list-switcher-item-meta">${escapeHtml(opt.meta)}</div>` : ''}
                  </div>
                  <span class="material-symbols-rounded mc-list-switcher-check" style="${opt.value === sel.value ? '' : 'visibility:hidden'}">check</span>
                </a>
              `).join('')}
            </div>
          </div>
        </div>
      </div>
    `;
  }

  function renderOperationSubform(kind, d) {
    switch (kind) {
      case 'tag':
      case 'remove_tag': {
        const verb = kind === 'tag' ? 'apply to' : 'remove from';
        return `
        <div class="fl-field">
          <span class="fl-field-label">Tags</span>
          ${tagInput('tags', d.tags || [])}
          <div class="fl-field-hint">
            Choose the tags to ${verb} contacts who reach this stage.
            Type a tag and press <kbd>Enter</kbd> or <kbd>,</kbd> to add it; click <kbd>×</kbd> to remove.
          </div>
        </div>
      `;
      }
      case 'copy':
      case 'move': return `
        <div class="fl-field">
          <span class="fl-field-label">Target list</span>
          ${selectFromList('targetListUid', ctx.mailLists, d.targetListUid)}
        </div>
      `;
      case 'update': return `
        ${fieldUidPicker(d.fieldUid, 'Field to update')}
        <div class="fl-field">
          <span class="fl-field-label">New value</span>
          <input type="text" class="fl-input" data-field="value" value="${escapeAttr(d.value || '')}">
        </div>
      `;
    }
    return '';
  }

  // Webhook
  function renderWebhookForm(d) {
    return `
      <div class="fl-field">
        <span class="fl-field-label">HTTP config (optional preset)</span>
        ${selectFromList('httpConfigUid', ctx.httpConfigs, d.httpConfigUid)}
        <div class="fl-field-hint">Pick a saved HTTP config, or fill in URL + method directly below.</div>
      </div>
      <div class="fl-field">
        <span class="fl-field-label">Method</span>
        <select class="fl-select" data-field="method">
          ${HTTP_METHODS.map(m => `<option value="${m}" ${m === (d.method || 'POST') ? 'selected' : ''}>${m}</option>`).join('')}
        </select>
      </div>
      <div class="fl-field">
        <span class="fl-field-label">URL</span>
        <input type="url" class="fl-input" data-field="url" value="${escapeAttr(d.url || '')}" placeholder="https://api.example.com/event">
      </div>
    `;
  }

  // Form primitives

  function selectFromList(field, items, value) {
    if (!items.length) {
      return `
        <select class="fl-select" data-field="${field}" disabled>
          <option value="">— no options available —</option>
        </select>
      `;
    }
    return `
      <select class="fl-select" data-field="${field}">
        <option value="" ${!value ? 'selected' : ''}>— none —</option>
        ${items.map(o => `<option value="${escapeAttr(o.value)}" ${o.value === value ? 'selected' : ''}>${escapeHtml(o.label)}</option>`).join('')}
      </select>
    `;
  }

  // Field-type → { icon, color } map. Drives the icon rendered next to each
  // option in the Field Select dropdown so admins can scan the list visually.
  // Color names map to .mc-vendor-icon CSS variables (var(--chart-N)).
  // Unknown types fall back to a neutral data_object icon.
  const FIELD_TYPE_META = {
    text:        { icon: 'short_text',         color: 'var(--chart-1)' },
    textarea:    { icon: 'notes',              color: 'var(--chart-1)' },
    email:       { icon: 'alternate_email',    color: 'var(--chart-3)' },
    number:      { icon: 'pin',                color: 'var(--chart-4)' },
    date:        { icon: 'event',              color: 'var(--chart-2)' },
    datetime:    { icon: 'schedule',           color: 'var(--chart-2)' },
    dropdown:    { icon: 'list_alt',           color: 'var(--chart-5)' },
    select:      { icon: 'list_alt',           color: 'var(--chart-5)' },
    multiselect: { icon: 'checklist',          color: 'var(--chart-5)' },
    checkbox:    { icon: 'check_box',          color: 'var(--chart-2)' },
    radio:       { icon: 'radio_button_checked', color: 'var(--chart-3)' },
    country:     { icon: 'public',             color: 'var(--chart-3)' },
    state:       { icon: 'map',                color: 'var(--chart-3)' },
    url:         { icon: 'link',               color: 'var(--chart-6)' },
    phone:       { icon: 'call',               color: 'var(--chart-4)' },
    currency:    { icon: 'payments',           color: 'var(--chart-4)' },
  };
  function fieldTypeMeta(type) {
    return FIELD_TYPE_META[type] || { icon: 'data_object', color: 'var(--chart-6)' };
  }

  // Field Select — list-switcher chrome (mirrors renderListSwitcher's DOM
  // hooks so the same panel-level click/search delegation works), but each
  // option renders a typed icon (mc-vendor-icon-sm) instead of a letter
  // avatar. The current selection's name is shown read-only on the left;
  // a small "Choose field" button on the right opens the searchable list.
  // `options` shape: [{ value, label, type }]. Unknown types fall back to a
  // neutral icon.
  function renderFieldSelect({ name, options, selected, searchPlaceholder }) {
    const placeholder = searchPlaceholder || 'Search fields…';
    const sel  = options.find(o => o.value === selected) || options[0];
    if (!sel) {
      // No fields — degrade to a disabled native select so the form still
      // renders (e.g. a brand-new list with no custom fields beyond email).
      return `
        <select class="fl-select" data-field="${escapeAttr(name)}" disabled>
          <option value="">— no fields available —</option>
        </select>
      `;
    }
    const typeMeta = fieldTypeMeta(sel.type);
    const iconBox = (type) => {
      const m = fieldTypeMeta(type);
      return `<span class="mc-vendor-icon mc-vendor-icon-sm" style="--vendor-color:${m.color}">
                <span class="material-symbols-rounded">${m.icon}</span>
              </span>`;
    };
    return `
<div data-mc-switcher data-mc-field-select style="display:flex;align-items:center;gap:10px;position:relative">
        <input type="hidden" data-field="${escapeAttr(name)}" value="${escapeAttr(sel.value)}">
        <div data-switcher-current style="flex:1;min-width:0;display:flex;align-items:center;gap:8px">
          <span data-switcher-icon-slot>${iconBox(sel.type)}</span>
          <div style="min-width:0;flex:1">
            <div data-switcher-name style="font-size:13px;font-weight:600;color:var(--fl-text);overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${escapeHtml(sel.label)}</div>
            <div data-switcher-meta style="font-size:11px;color:var(--fl-text-muted);margin-top:1px;line-height:1.35">${escapeHtml(sel.type || '')}</div>
          </div>
        </div>
        <div class="mc-list-switcher" data-switcher-shell>
          <button type="button" class="mc-list-switcher-trigger" data-list-switcher-trigger
                  title="Choose field" style="gap:4px;padding:4px 8px;font-size:12px">
            Choose field
            <span class="material-symbols-rounded">unfold_more</span>
          </button>
          <div class="mc-list-switcher-dropdown" style="right:0;left:auto">
            <div class="mc-list-switcher-search">
              <span class="material-symbols-rounded">search</span>
              <input type="text" placeholder="${escapeAttr(placeholder)}" data-list-switcher-search>
            </div>
            <div class="mc-list-switcher-items" data-list-switcher-items>
              ${options.map(opt => `
                <a href="#" class="mc-list-switcher-item ${opt.value === sel.value ? 'active' : ''}"
                   data-name="${escapeAttr((opt.label || '').toLowerCase() + ' ' + (opt.type || '').toLowerCase())}"
                   data-switcher-value="${escapeAttr(opt.value)}"
                   data-switcher-label="${escapeAttr(opt.label)}"
                   data-switcher-meta="${escapeAttr(opt.type || '')}"
                   data-switcher-icon="${escapeAttr(fieldTypeMeta(opt.type).icon)}"
                   data-switcher-icon-color="${escapeAttr(fieldTypeMeta(opt.type).color)}">
                  ${iconBox(opt.type)}
                  <div class="mc-list-switcher-item-content">
                    <div class="mc-list-switcher-item-name">${escapeHtml(opt.label)}</div>
                    <div class="mc-list-switcher-item-meta">${escapeHtml(opt.type || '')}</div>
                  </div>
                  <span class="material-symbols-rounded mc-list-switcher-check" style="${opt.value === sel.value ? '' : 'visibility:hidden'}">check</span>
                </a>
              `).join('')}
            </div>
          </div>
        </div>
      </div>
    `;
  }

  function fieldUidPicker(value, label) {
    return `
      <div class="fl-field">
        <span class="fl-field-label">${escapeHtml(label)}</span>
        ${renderFieldSelect({ name: 'fieldUid', options: ctx.listFields, selected: value })}
      </div>
    `;
  }

  function durationForm(value, field, label) {
    return `
      <div class="fl-field">
        <span class="fl-field-label">${escapeHtml(label)}</span>
        <div class="fl-presets">
          ${DURATION_PRESETS.map(p => `
            <button type="button" class="fl-preset ${p.iso === value ? 'active' : ''}" data-preset-target="${field}" data-preset-value="${p.iso}">${p.label}</button>
          `).join('')}
        </div>
        <input type="text" class="fl-input" data-field="${field}" value="${escapeAttr(value || '')}" placeholder="ISO-8601 (PT5M, P1D, P2W…)">
      </div>
    `;
  }

  function tagInput(field, values) {
    return `
      <div class="fl-tag-input" data-field="${field}">
        ${values.map(v => `<span class="fl-tag-pill">${escapeHtml(v)} <button type="button" aria-label="Remove">×</button></span>`).join('')}
        <input type="text" placeholder="Add tag…">
      </div>
    `;
  }

  // ─── Add-node modal ────────────────────────────────────────────────────

  const addModal = (function () {
    function open({ onPick }) {
      close();
      const overlay = el('div', 'fl-modal-overlay');
      const types = Object.entries(TYPES)
        .filter(([t, cfg]) => t !== 'trigger' && !cfg.hiddenFromAdd)
        .map(([t, cfg]) => `
          <button class="fl-modal-type" data-type="${t}">
            <span class="material-symbols-rounded">${cfg.icon}</span>
            <span>${cfg.label}</span>
          </button>`).join('');
      overlay.innerHTML = `
        <div class="fl-modal" role="dialog">
          <div class="fl-modal-title">Add node</div>
          <div class="fl-modal-grid">${types}</div>
          <div class="fl-modal-actions">
            <button class="fl-btn-cancel">Cancel</button>
          </div>
        </div>`;
      document.body.appendChild(overlay);
      overlay.querySelectorAll('.fl-modal-type').forEach(btn => {
        btn.addEventListener('click', () => { close(); onPick(btn.dataset.type); });
      });
      overlay.querySelector('.fl-btn-cancel').addEventListener('click', close);
      overlay.addEventListener('click', e => { if (e.target === overlay) close(); });
    }
    function close() { document.querySelectorAll('.fl-modal-overlay').forEach(n => n.remove()); }
    return { open, close };
  })();

  // Trigger picker modal — 12 trigger-event cards in a 3-col grid (legacy
  // "Set up trigger" parity). Click a card to highlight it, then Confirm
  // commits the choice. The Confirm button stays disabled until something is
  // picked so the user can never confirm an empty selection.
  const triggerPickerModal = (function () {
    function open({ currentKey, onPick }) {
      close();
      // Local "draft" selection — independent of the live node.data.key so
      // Cancel discards without side effects. Seeds with the current key so
      // re-opening from "Change" shows the existing pick highlighted.
      let draftKey = currentKey || null;

      const overlay = el('div', 'fl-modal-overlay');
      const cards = (ctx.triggerKeys || []).map(k => `
        <button type="button" class="fl-trigger-card${k.value === draftKey ? ' is-selected' : ''}"
                data-trigger-card="${escapeAttr(k.value)}">
          <span class="fl-trigger-card-icon">
            <span class="material-symbols-rounded">${escapeHtml(k.icon || 'bolt')}</span>
          </span>
          <span class="fl-trigger-card-text">
            <span class="fl-trigger-card-name">${escapeHtml(k.label)}</span>
            ${k.desc ? `<span class="fl-trigger-card-desc">${escapeHtml(k.desc)}</span>` : ''}
          </span>
        </button>
      `).join('');
      overlay.innerHTML = `
        <div class="fl-modal fl-modal-trigger-picker" role="dialog">
          <div class="fl-modal-title">Choose a trigger</div>
          <div class="fl-modal-subtitle">
            Your automation can be triggered based on various conditions —
            list events, recurring schedules, contact attribute changes, or
            an external API call. Pick the one that best matches when this
            workflow should start.
          </div>
          <div class="fl-modal-body">
            <div class="fl-trigger-grid fl-trigger-grid-3col">${cards}</div>
          </div>
          <div class="fl-modal-actions">
            <button type="button" class="fl-btn-cancel">Cancel</button>
            <button type="button" class="fl-btn fl-btn-primary" data-trigger-confirm
                    ${draftKey ? '' : 'disabled'}>OK</button>
          </div>
        </div>`;
      document.body.appendChild(overlay);

      const confirmBtn = overlay.querySelector('[data-trigger-confirm]');
      const cardEls    = overlay.querySelectorAll('[data-trigger-card]');

      overlay.querySelector('.fl-btn-cancel').addEventListener('click', close);
      overlay.addEventListener('click', e => { if (e.target === overlay) close(); });

      // Card click: highlight only — actual commit waits for Confirm.
      cardEls.forEach(card => {
        card.addEventListener('click', () => {
          draftKey = card.dataset.triggerCard;
          cardEls.forEach(c => c.classList.toggle('is-selected', c === card));
          confirmBtn.disabled = false;
        });
        // Double-click as a "pick + confirm" shortcut for power users.
        card.addEventListener('dblclick', () => {
          draftKey = card.dataset.triggerCard;
          confirm();
        });
      });

      confirmBtn.addEventListener('click', confirm);

      function confirm() {
        if (!draftKey) return;
        const picked = draftKey;
        close();
        onPick(picked);
      }
    }
    function close() { document.querySelectorAll('.fl-modal-overlay').forEach(n => n.remove()); }
    return { open, close };
  })();

  // Template picker modal — large modal mirroring the Campaign template step.
  // 2 tabs: "Templates" (paginated gallery, system + customer toggle) and
  // "Paste HTML" (raw HTML textarea). Picking a template or saving HTML calls
  // the bound onApplied callback so the sidebar can re-hydrate.
  const templatePickerModal = (function () {
    let _state = null;

    function open({ nodeId, onApplied, initialTab }) {
      close();
      const tab = initialTab === 'html' ? 'html' : 'gallery';
      _state = { nodeId, onApplied, from: 'system', page: 1, tab };

      const overlay = el('div', 'fl-modal-overlay');
      overlay.innerHTML = `
        <div class="fl-modal fl-modal-template-picker" role="dialog">
          <div class="fl-modal-title">Pick template</div>
          <div class="fl-template-picker-tabs">
            <button type="button" class="fl-tpl-tab ${tab === 'gallery' ? 'is-active' : ''}" data-tpl-tab="gallery">Templates</button>
            <button type="button" class="fl-tpl-tab ${tab === 'html'    ? 'is-active' : ''}" data-tpl-tab="html">Paste HTML</button>
          </div>
          <div class="fl-modal-body" data-tpl-body>
            <div class="fl-tpl-loading">Loading…</div>
          </div>
          <div class="fl-modal-actions">
            <button type="button" class="fl-btn-cancel">Close</button>
          </div>
        </div>`;
      document.body.appendChild(overlay);
      overlay.querySelector('.fl-btn-cancel').addEventListener('click', close);
      overlay.addEventListener('click', e => { if (e.target === overlay) close(); });
      overlay.querySelectorAll('[data-tpl-tab]').forEach(t => {
        t.addEventListener('click', () => {
          overlay.querySelectorAll('[data-tpl-tab]').forEach(x => x.classList.toggle('is-active', x === t));
          _state.tab = t.dataset.tplTab;
          _state.tab === 'gallery' ? renderGallery() : renderCustomHtml();
        });
      });

      tab === 'gallery' ? renderGallery() : renderCustomHtml();
    }

    async function renderGallery() {
      if (!_state) return;
      const body = document.querySelector('[data-tpl-body]');
      if (!body) return;
      body.innerHTML = `
        <div class="fl-tpl-toolbar">
          <div class="fl-tpl-segments" data-tpl-from>
            <button type="button" class="fl-tpl-seg ${_state.from === 'system' ? 'is-active' : ''}" data-from="system">System</button>
            <button type="button" class="fl-tpl-seg ${_state.from === 'mine'   ? 'is-active' : ''}" data-from="mine">My templates</button>
          </div>
        </div>
        <div class="fl-tpl-loading">Loading…</div>`;
      body.querySelectorAll('[data-from]').forEach(b => {
        b.addEventListener('click', () => { _state.from = b.dataset.from; _state.page = 1; renderGallery(); });
      });

      try {
        const url = urls.emailTemplateGallery.replace('__ID__', encodeURIComponent(_state.nodeId))
          + `?from=${encodeURIComponent(_state.from)}&page=${_state.page}`;
        const res = await api('GET', url);
        const grid = (res.items || []).map(t => `
          <button type="button" class="fl-tpl-card" data-tpl-pick="${escapeAttr(t.uid)}">
            <span class="fl-tpl-card-thumb">
              ${t.thumbUrl
                ? `<img src="${escapeAttr(t.thumbUrl)}" alt="${escapeAttr(t.name)}">`
                : `<span class="material-symbols-rounded">image</span>`}
            </span>
            <span class="fl-tpl-card-name">${escapeHtml(t.name || '(untitled)')}</span>
          </button>
        `).join('');

        const pager = res.lastPage > 1
          ? `<div class="fl-tpl-pager">
               <button type="button" class="fl-tpl-pg" data-pg-prev ${res.page <= 1 ? 'disabled' : ''}>Prev</button>
               <span class="fl-tpl-pg-info">Page ${res.page} of ${res.lastPage}</span>
               <button type="button" class="fl-tpl-pg" data-pg-next ${res.page >= res.lastPage ? 'disabled' : ''}>Next</button>
             </div>`
          : '';

        body.querySelector('.fl-tpl-loading').outerHTML = `
          ${(res.items || []).length === 0
            ? '<div class="fl-tpl-empty">No templates in this collection.</div>'
            : `<div class="fl-tpl-grid">${grid}</div>${pager}`}
        `;

        body.querySelectorAll('[data-tpl-pick]').forEach(card => {
          card.addEventListener('click', () => doChoose(card.dataset.tplPick));
        });
        body.querySelector('[data-pg-prev]')?.addEventListener('click', () => { _state.page = Math.max(1, _state.page - 1); renderGallery(); });
        body.querySelector('[data-pg-next]')?.addEventListener('click', () => { _state.page = res.page + 1; renderGallery(); });
      } catch (e) {
        body.querySelector('.fl-tpl-loading').textContent = e.message || 'Failed to load templates';
      }
    }

    function renderCustomHtml() {
      const body = document.querySelector('[data-tpl-body]');
      if (!body) return;
      body.innerHTML = `
        <div class="fl-field">
          <span class="fl-field-label">Raw HTML</span>
          <textarea class="fl-input fl-tpl-html" rows="14" placeholder="<!DOCTYPE html><html>..."></textarea>
          <div class="fl-field-hint">Personalization tags like {{SUBSCRIBER_FIRST_NAME}} are still substituted at send time.</div>
        </div>
        <div class="fl-field">
          <button type="button" class="fl-btn fl-btn-primary" data-tpl-save-html>Save HTML</button>
        </div>`;
      body.querySelector('[data-tpl-save-html]').addEventListener('click', async () => {
        const html = body.querySelector('.fl-tpl-html').value;
        if (!html.trim()) return toast('Paste some HTML first', 'error');
        try {
          const url = urls.emailCustomHtml.replace('__ID__', encodeURIComponent(_state.nodeId));
          await api('POST', url, { html });
          if (_state.onApplied) _state.onApplied();
          close();
          toast('Custom HTML applied', 'ok');
        } catch (e) { toast(e.message, 'error'); }
      });
    }

    async function doChoose(templateUid) {
      try {
        const url = urls.emailTemplateChoose.replace('__ID__', encodeURIComponent(_state.nodeId));
        await api('POST', url, { template_uid: templateUid });
        if (_state.onApplied) _state.onApplied();
        close();
        toast('Template applied', 'ok');
      } catch (e) { toast(e.message, 'error'); }
    }

    function close() { document.querySelectorAll('.fl-modal-overlay').forEach(n => n.remove()); _state = null; }
    return { open, close };
  })();

  // Email preview modal — large iframe that loads the rendered email body
  // from `GET /nodes/{id}/email/preview`. Click thumbnail → open. Iframe
  // sandbox blocks scripts in the email content (defense-in-depth: the
  // template HTML can be admin-pasted custom HTML).
  const emailPreviewModal = (function () {
    function open({ url }) {
      close();
      const overlay = el('div', 'fl-modal-overlay');
      overlay.innerHTML = `
        <div class="fl-modal fl-modal-email-preview" role="dialog">
          <div class="fl-modal-title">
            Email preview
            <button type="button" class="fl-modal-close" aria-label="Close">
              <span class="material-symbols-rounded">close</span>
            </button>
          </div>
          <div class="fl-modal-body">
            <iframe class="fl-email-preview-frame" sandbox="allow-same-origin"
                    src="${escapeAttr(url)}"
                    title="Email preview"></iframe>
          </div>
        </div>`;
      document.body.appendChild(overlay);
      overlay.addEventListener('click', e => { if (e.target === overlay) close(); });
      overlay.querySelector('.fl-modal-close').addEventListener('click', close);
    }
    function close() { document.querySelectorAll('.fl-modal-overlay').forEach(n => n.remove()); }
    return { open, close };
  })();

  // Generic form modal — used by Send sidebar (settings / test) and topbar
  // (Trigger now). Caller passes a list of fields + submit handler. Stays
  // generic-on-purpose so we don't grow N modal types.
  const formModal = (function () {
    function open({ title, fields, submitText, onSubmit, busyText }) {
      close();
      const overlay = el('div', 'fl-modal-overlay');
      const fieldsHtml = (fields || []).map(renderField).join('');
      overlay.innerHTML = `
        <div class="fl-modal fl-modal-form" role="dialog">
          <div class="fl-modal-title">${escapeHtml(title || 'Edit')}</div>
          <div class="fl-modal-body">${fieldsHtml}</div>
          <div class="fl-modal-actions">
            <button type="button" class="fl-btn-cancel">Cancel</button>
            <button type="button" class="fl-btn fl-btn-primary fl-modal-submit">${escapeHtml(submitText || 'Save')}</button>
          </div>
        </div>`;
      document.body.appendChild(overlay);
      overlay.querySelector('.fl-btn-cancel').addEventListener('click', close);
      overlay.addEventListener('click', e => { if (e.target === overlay) close(); });
      const submitBtn = overlay.querySelector('.fl-modal-submit');
      submitBtn.addEventListener('click', async () => {
        const values = {};
        overlay.querySelectorAll('[data-name]').forEach(input => {
          values[input.dataset.name] = input.type === 'checkbox' ? input.checked : input.value;
        });
        submitBtn.disabled = true;
        const originalText = submitBtn.textContent;
        if (busyText) submitBtn.textContent = busyText;
        try {
          await onSubmit(values);
        } finally {
          submitBtn.disabled = false;
          submitBtn.textContent = originalText;
        }
      });
      // Auto-focus first input.
      const first = overlay.querySelector('input:not([type=hidden]):not([type=checkbox])');
      if (first) setTimeout(() => first.focus(), 100);
    }
    function close() { document.querySelectorAll('.fl-modal-overlay').forEach(n => n.remove()); }
    function renderField(f) {
      const v = f.value ?? '';
      if (f.type === 'checkbox') {
        return `
          <label class="fl-modal-checkbox">
            <input type="checkbox" data-name="${escapeAttr(f.name)}" ${v ? 'checked' : ''}>
            <span>${escapeHtml(f.label)}</span>
          </label>`;
      }
      return `
        <div class="fl-field">
          <span class="fl-field-label">${escapeHtml(f.label)}${f.required ? ' *' : ''}</span>
          <input type="${escapeAttr(f.type || 'text')}" class="fl-input"
                 data-name="${escapeAttr(f.name)}"
                 value="${escapeAttr(String(v ?? ''))}"
                 placeholder="${escapeAttr(f.placeholder || '')}">
          ${f.hint ? `<div class="fl-field-hint">${escapeHtml(f.hint)}</div>` : ''}
        </div>`;
    }
    return { open, close };
  })();

  // ─── Toast ─────────────────────────────────────────────────────────────
  // Delegate to McNotify (bottom-right toast container, same family used by
  // every other refactor screen) so the editor doesn't ship its own
  // visually-divergent corner notification.
  function toast(msg, type) {
    const mapped = type === 'ok' ? 'success' : (type || 'info');
    if (window.McNotify && typeof window.McNotify[mapped] === 'function') {
      window.McNotify[mapped](msg);
      return;
    }
    // Fallback if McNotify isn't on the page (defensive — should never hit).
    console[type === 'error' ? 'error' : 'log'](msg);
  }

  // ─── Boot ──────────────────────────────────────────────────────────────

  function init({ container, initialData, csrfToken, ctx: appCtx, urls: appUrls }) {
    if (!appUrls)   throw new Error('AutomationEditApp.init: urls required');
    if (!csrfToken) throw new Error('AutomationEditApp.init: csrfToken required');
    ctx = {
      csrfToken,
      triggerKeys:    appCtx?.triggerKeys    || [],
      emails:         appCtx?.emails         || [],
      mailLists:      appCtx?.mailLists      || [],
      listFields:     appCtx?.listFields     || [],
      httpConfigs:    appCtx?.httpConfigs    || [],
      emailDefaults:  appCtx?.emailDefaults  || {},
      triggerConfig:  appCtx?.triggerConfig  || {},
    };
    urls = appUrls;
    tree = new Tree(initialData);
    renderer = new Renderer({
      container,
      cardContent, outputs,
      onNodeClick: handleClickedNode,
      onSlotClick: handleClickedSlot,
    });
    renderer.draw(tree);
    return { tree, renderer, sidebar, formModal, toast, urls };
  }

  // ─── Helpers ────────────────────────────────────────────────────────────

  function el(tag, className) {
    const e = document.createElement(tag);
    if (className) e.className = className;
    return e;
  }
  function escapeHtml(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, c => ({
      '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[c]));
  }
  function escapeAttr(s) { return escapeHtml(s); }

  global.AutomationEditApp = { init, TYPES };
})(window);
