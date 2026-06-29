/**
 * mc-segment-counter — SMS segment counter (GSM-7 / UCS-2 / cost preview).
 *
 * Mounts on any `<textarea data-mc-segment-counter>`. Computes segment count
 * + encoding + cost in real time as the user types and updates the sibling
 * `.mc-segment-counter` UI structure. Idempotent: re-applying the helper
 * to an already-bound element is a no-op.
 *
 * Encoding rules (carrier-standard, used by Twilio / Klaviyo / Brevo):
 *   - GSM-7 (basic Latin + a few extensions). 160 chars per segment for
 *     single-segment messages, 153 for multi-segment (7 chars eaten by
 *     UDH continuation header per segment).
 *   - GSM-7 has 9 extension characters that count as 2 chars each:
 *     ^ { } [ ] ~ \ | € (form feed 0x0C also extension but not typed).
 *   - As soon as ONE non-GSM-7 character appears anywhere, the WHOLE
 *     message upgrades to UCS-2 (UTF-16). 70 chars per segment for
 *     single-segment, 67 for multi-segment.
 *   - One emoji = 1 char in JS string length BUT may be a surrogate
 *     pair (2 UTF-16 code units) so we count `codePointAt`-based length.
 *
 * Data attributes (declarative API — match the rest of the mc-* family):
 *   - data-mc-segment-counter        — mount marker (REQUIRED)
 *   - data-channel="sms"             — reserved for future WA/TG variants
 *                                       (currently only SMS is supported)
 *   - data-cost-per-segment="0.0079" — decimal cost (default 0 → no cost line)
 *   - data-currency="USD"            — ISO-4217 currency code (default USD)
 *   - data-max-segments="10"         — soft cap; warns at this threshold
 *                                       (default 10, mirrors Twilio default)
 *   - data-counter-id="custom-id"    — optional ID for the inserted counter
 *                                       node (so external code can reference)
 *
 * Public read-only state on the input (DOM properties):
 *   inputEl.dataset.encoding   — "gsm-7" | "ucs-2"
 *   inputEl.dataset.chars      — total char count (extensions counted as 2)
 *   inputEl.dataset.segments   — segment count
 *   inputEl.dataset.cost       — computed cost (decimal string, currency-aware)
 *
 * Emits `mc-segment-counter:update` CustomEvent on the input on every change
 * with `detail = { encoding, chars, segments, cost, currency, maxSegments,
 * isOverLimit }`. Consumers (cost-preview footer cards, etc.) listen and
 * update their own surfaces.
 */
(function () {
    'use strict';

    if (window.McSegmentCounter && window.McSegmentCounter.__bound) {
        return;
    }

    // GSM-7 basic character set (Annex A, 3GPP TS 23.038). Includes uppercase
    // Greek letters and many Latin-1 supplements. We enumerate the codepoints
    // rather than maintain a regex (more readable + auditable).
    var GSM7_BASIC = new Set();
    [
        // 0x00-0x7F basic Latin minus a few omitted slots + standard set:
        ' ', '!', '"', '#', '$', '%', '&', "'", '(', ')', '*', '+', ',',
        '-', '.', '/', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9',
        ':', ';', '<', '=', '>', '?', '@',
        'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M',
        'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z',
        '_',
        'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm',
        'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z',
        // Latin-1 supplements present in GSM-7:
        '£', '¥', 'è', 'é', 'ù', 'ì', 'ò', 'Ç',
        'Ø', 'ø', 'Å', 'å', '¤',
        '_', 'Æ', 'æ', 'ß', 'É', '¡', '¿',
        // Greek caps GSM-7 supports (lowercase NOT supported):
        'Δ', 'Φ', 'Γ', 'Λ', 'Ω', 'Π', 'Ψ', 'Σ', 'Θ', 'Ξ',
        // Newline + carriage return
        '\n', '\r',
    ].forEach(function (c) { GSM7_BASIC.add(c); });

    // 9 GSM-7 extension chars (each counts as 2 chars on the wire — UDH escape).
    var GSM7_EXTENSION = new Set(['^', '{', '}', '[', ']', '~', '\\', '|', '€']);

    function classifyAndCount(text) {
        if (text === '' || text == null) {
            return { encoding: 'gsm-7', chars: 0, segments: 0, isExtensionUpgrade: false };
        }
        var hasNonGsm = false;
        var charCount = 0;
        // Use codePointAt to handle surrogate pairs correctly (emoji = 1 code point but 2 UTF-16 units)
        var codePoints = Array.from(text);
        for (var i = 0; i < codePoints.length; i++) {
            var ch = codePoints[i];
            if (GSM7_EXTENSION.has(ch)) {
                charCount += 2;
            } else if (GSM7_BASIC.has(ch)) {
                charCount += 1;
            } else {
                hasNonGsm = true;
                break;
            }
        }
        if (hasNonGsm) {
            // Re-count as UCS-2 (each code point = 1 char regardless)
            return {
                encoding: 'ucs-2',
                chars: codePoints.length,
                segments: ucs2Segments(codePoints.length),
                isExtensionUpgrade: false,
            };
        }
        return {
            encoding: 'gsm-7',
            chars: charCount,
            segments: gsm7Segments(charCount),
            isExtensionUpgrade: false,
        };
    }

    function gsm7Segments(chars) {
        if (chars === 0) return 0;
        if (chars <= 160) return 1;
        return Math.ceil(chars / 153);
    }

    function ucs2Segments(chars) {
        if (chars === 0) return 0;
        if (chars <= 70) return 1;
        return Math.ceil(chars / 67);
    }

    function segmentLimit(encoding, segments) {
        if (encoding === 'gsm-7') {
            return segments <= 1 ? 160 : 153 * segments;
        }
        return segments <= 1 ? 70 : 67 * segments;
    }

    function formatCost(cost, currency) {
        try {
            return new Intl.NumberFormat(undefined, {
                style: 'currency',
                currency: currency || 'USD',
                minimumFractionDigits: 4,
                maximumFractionDigits: 4,
            }).format(cost);
        } catch (e) {
            return (currency || 'USD') + ' ' + cost.toFixed(4);
        }
    }

    function buildCounterMarkup(input) {
        var counter = document.createElement('div');
        counter.className = 'mc-segment-counter';
        var customId = input.getAttribute('data-counter-id');
        if (customId) counter.id = customId;
        counter.setAttribute('data-channel', input.getAttribute('data-channel') || 'sms');
        counter.setAttribute('data-encoding', 'gsm-7');
        counter.setAttribute('data-tone', 'muted');
        counter.innerHTML =
            '<div class="mc-segment-counter__row mc-segment-counter__row--primary">' +
                '<div class="mc-segment-counter__progress">' +
                    '<div class="mc-segment-counter__bar" style="width:0%"></div>' +
                '</div>' +
                '<div class="mc-segment-counter__chars">' +
                    '<span class="mc-segment-counter__chars-now">0</span>' +
                    '<span class="mc-segment-counter__chars-sep">/</span>' +
                    '<span class="mc-segment-counter__chars-cap">160</span>' +
                '</div>' +
            '</div>' +
            '<div class="mc-segment-counter__row mc-segment-counter__row--meta">' +
                '<span class="mc-segment-counter__encoding" title="">' +
                    '<span class="mc-segment-counter__encoding-label">GSM-7</span>' +
                '</span>' +
                '<span class="mc-segment-counter__sep" aria-hidden="true">·</span>' +
                '<span class="mc-segment-counter__segments">' +
                    '<span class="mc-segment-counter__segments-count">0</span>' +
                    ' <span class="mc-segment-counter__segments-label">segments</span>' +
                '</span>' +
                '<span class="mc-segment-counter__sep mc-segment-counter__sep--cost" aria-hidden="true">·</span>' +
                '<span class="mc-segment-counter__cost"></span>' +
                '<span class="mc-segment-counter__notice" role="status" aria-live="polite"></span>' +
            '</div>';
        return counter;
    }

    function update(input, counter) {
        var text = input.value || '';
        var costPer = parseFloat(input.getAttribute('data-cost-per-segment') || '0');
        var currency = input.getAttribute('data-currency') || 'USD';
        var maxSeg = parseInt(input.getAttribute('data-max-segments') || '10', 10);

        var result = classifyAndCount(text);
        var capForSegments = segmentLimit(result.encoding, result.segments || 1);
        var widthPct = capForSegments > 0 ? Math.min(100, (result.chars / capForSegments) * 100) : 0;
        var cost = costPer > 0 ? (costPer * result.segments) : 0;

        var tone = 'muted';
        if (result.segments >= maxSeg) tone = 'danger';
        else if (result.segments >= 2) tone = 'warning';
        else if (result.chars > capForSegments * 0.85) tone = 'attention';

        counter.setAttribute('data-encoding', result.encoding);
        counter.setAttribute('data-tone', tone);

        counter.querySelector('.mc-segment-counter__chars-now').textContent = String(result.chars);
        counter.querySelector('.mc-segment-counter__chars-cap').textContent = String(capForSegments);
        counter.querySelector('.mc-segment-counter__bar').style.width = widthPct + '%';
        counter.querySelector('.mc-segment-counter__segments-count').textContent = String(result.segments);
        counter.querySelector('.mc-segment-counter__encoding-label').textContent =
            result.encoding === 'gsm-7' ? 'GSM-7' : 'Unicode';
        counter.querySelector('.mc-segment-counter__encoding').setAttribute(
            'title',
            result.encoding === 'gsm-7'
                ? 'Latin charset — 160 chars per segment (153 in multi-segment messages).'
                : 'Unicode (UCS-2) — non-GSM character detected. 70 chars per segment (67 in multi-segment).'
        );

        var costEl = counter.querySelector('.mc-segment-counter__cost');
        var costSep = counter.querySelector('.mc-segment-counter__sep--cost');
        if (costPer > 0 && result.segments > 0) {
            costEl.textContent = formatCost(cost, currency);
            costEl.style.display = '';
            if (costSep) costSep.style.display = '';
        } else {
            costEl.textContent = '';
            costEl.style.display = 'none';
            if (costSep) costSep.style.display = 'none';
        }

        var notice = counter.querySelector('.mc-segment-counter__notice');
        if (result.segments >= maxSeg) {
            notice.textContent = 'Over the ' + maxSeg + '-segment soft cap — review before sending.';
        } else if (result.encoding === 'ucs-2' && result.chars > 0) {
            notice.textContent = 'Unicode detected — costs ' + result.segments + 'x compared to GSM-7.';
        } else if (result.segments >= 2) {
            notice.textContent = 'Multi-segment message — each segment is billed separately.';
        } else {
            notice.textContent = '';
        }

        input.dataset.encoding = result.encoding;
        input.dataset.chars = String(result.chars);
        input.dataset.segments = String(result.segments);
        input.dataset.cost = cost.toFixed(4);

        input.dispatchEvent(new CustomEvent('mc-segment-counter:update', {
            bubbles: true,
            detail: {
                encoding: result.encoding,
                chars: result.chars,
                segments: result.segments,
                cost: cost,
                currency: currency,
                maxSegments: maxSeg,
                isOverLimit: result.segments >= maxSeg,
            },
        }));
    }

    function mount(input) {
        if (!input || input.__mcSegmentCounterBound) return;
        input.__mcSegmentCounterBound = true;

        var counter = buildCounterMarkup(input);

        // Insert immediately after the input (or after its wrapper if present).
        var anchor = input;
        var parent = input.parentNode;
        if (!parent) return;
        if (input.nextSibling) parent.insertBefore(counter, input.nextSibling);
        else parent.appendChild(counter);

        var handler = function () { update(input, counter); };
        input.addEventListener('input', handler);
        input.addEventListener('change', handler);
        // Initial paint
        update(input, counter);
    }

    function mountAll(root) {
        var scope = root || document;
        var inputs = scope.querySelectorAll('textarea[data-mc-segment-counter]:not([data-mc-segment-counter-init])');
        inputs.forEach(function (input) {
            input.setAttribute('data-mc-segment-counter-init', '1');
            mount(input);
        });
    }

    window.McSegmentCounter = {
        __bound: true,
        mount: mount,
        mountAll: mountAll,
        // Test seam: exported for unit tests / external consumers
        _classify: classifyAndCount,
        _gsm7Segments: gsm7Segments,
        _ucs2Segments: ucs2Segments,
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { mountAll(); });
    } else {
        mountAll();
    }
})();
