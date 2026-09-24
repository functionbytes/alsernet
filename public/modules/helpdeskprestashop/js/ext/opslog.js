/**
 * HelpdeskPrestashop · extensión "opslog"
 * Pantallas de operación: 37 "Registro del puente" y 38 "Eventos recibidos".
 * Sin dependencias (fetch nativo): son pantallas de administración, fuera del
 * inbox, y no cuentan con PscStore ni HDCommerce.
 */
(function () {
    'use strict';

    var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

    function esc(v) {
        return String(v == null ? '' : v).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    // "1.284": es-ES no agrupa los números de 4 cifras, el diseño sí.
    function num(n) {
        if (n === null || n === undefined || isNaN(n)) return '—';
        return String(Math.round(n)).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    function when(iso) {
        var d = new Date(iso);
        if (isNaN(d.getTime())) return '';
        var pad = function (x) { return (x < 10 ? '0' : '') + x; };
        var time = pad(d.getHours()) + ':' + pad(d.getMinutes()) + ':' + pad(d.getSeconds());
        var today = new Date();
        if (d.toDateString() === today.toDateString()) return time;
        return pad(d.getDate()) + '/' + pad(d.getMonth() + 1) + ' ' + time.slice(0, 5);
    }

    function request(method, url, extraHeaders) {
        var headers = { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' };
        Object.keys(extraHeaders || {}).forEach(function (k) { headers[k] = extraHeaders[k]; });
        return fetch(url, {
            method: method,
            credentials: 'same-origin',
            headers: headers
        }).then(function (res) {
            return res.json().catch(function () { return {}; }).then(function (body) {
                if (!res.ok || body.success === false) {
                    var err = new Error(body.message || 'No se ha podido completar la operación.');
                    err.status = res.status;
                    throw err;
                }
                return body;
            });
        });
    }

    // Una clave por pulsación: si el servidor la derivase del minuto, una
    // segunda pulsación (tras un lote de 50) recibiría la respuesta guardada
    // del primer lote y no devolvería nada más a la cola.
    function idemKey() {
        if (window.crypto && typeof window.crypto.randomUUID === 'function') return window.crypto.randomUUID();
        return 'opslog-' + Date.now().toString(36) + '-' + Math.random().toString(36).slice(2, 12);
    }

    /* ─── 37 · Registro del puente ───────────────────────────────── */

    var RESULT = {
        ok: { cls: 'psc-tag--done', label: 'ok' },
        cache: { cls: 'psc-tag--cache', label: 'caché' },
        timeout: { cls: 'psc-tag--timeout', label: 'timeout' },
        error: { cls: 'psc-tag--blocked', label: 'error' },
        rejected: { cls: 'psc-tag--closed', label: 'rechazada' }
    };

    function initBridge(root) {
        var state = { hours: 24, result: 'all' };
        var rowsEl = root.querySelector('#pscOpslogRows');
        var queueEl = root.querySelector('#pscOpslogQueue');
        var byActionEl = root.querySelector('#pscOpslogByAction');
        var requeueBtn = root.querySelector('#pscOpslogRequeue');
        var warmBtn = root.querySelector('#pscOpslogWarm');
        var flash = root.querySelector('#pscOpslogFlash');
        var canRetry = root.getAttribute('data-can-retry') === '1';
        var seq = 0;

        function kpi(name, value) {
            var el = root.querySelector('[data-kpi="' + name + '"]');
            if (el) el.textContent = value;
        }

        function say(msg, good) {
            if (!flash) return;
            flash.textContent = msg;
            flash.className = 'psc-opslog-flash ' + (good ? 'is-good' : 'is-warn');
            flash.hidden = false;
        }

        function renderRows(rows) {
            if (!rows.length) {
                rowsEl.innerHTML = '<div class="psc-state psc-state--compact"><span class="t">Sin llamadas</span>' +
                    '<span class="s">No hay llamadas con este filtro en la ventana elegida.</span></div>';
                return;
            }
            rowsEl.innerHTML = rows.map(function (r) {
                var res = RESULT[r.result] || RESULT.ok;
                var cls = 'psc-logrow' + (r.result === 'timeout' ? ' is-slow' : '') + (r.result === 'error' ? ' is-error' : '');
                var title = 'HTTP ' + r.status + (r.result === 'cache' ? ' · servida desde la caché del puente' : '') +
                    (r.error ? ' · ' + r.error : '') + (r.id_customer ? ' · cliente PS ' + r.id_customer : '');
                return '<div class="' + cls + '" title="' + esc(title) + '">' +
                    '<span class="act">' + esc(r.action) + '</span>' +
                    '<span class="when">' + esc(when(r.at)) + '</span>' +
                    '<span class="ms">' + num(r.ms) + '</span>' +
                    '<span class="res"><span class="psc-tag ' + res.cls + '">' + res.label + '</span></span>' +
                    '</div>';
            }).join('');
        }

        function renderQueue(q) {
            var html = '' +
                '<div class="psc-row"><span class="k">En cola</span><span class="v mono">' + num(q.pending) + '</span></div>' +
                '<div class="psc-row"><span class="k">Listos para el próximo envío</span><span class="v mono">' + num(q.due) + '</span></div>' +
                '<div class="psc-row' + (q.dead ? '' : ' psc-row--good') + '"><span class="k">Fallidos (sin más reintentos)</span><span class="v mono">' + num(q.dead) + '</span></div>';
            if (q.recent_dead && q.recent_dead.length) {
                html += '<div class="psc-opslog-dead">' + q.recent_dead.map(function (d) {
                    return '<div class="psc-opslog-dead-row"><span class="nm">' + esc(d.event) + '</span>' +
                        '<span class="mt">' + esc(d.destination) + ' · ' + num(d.attempts) + ' intentos · ' + esc(when(d.at)) + '</span></div>';
                }).join('') + '</div>';
            } else if (!q.dead) {
                html += '<span class="psc-opslog-hint">Ningún webhook ha agotado sus reintentos.</span>';
            }
            queueEl.innerHTML = html;
            // Solo hay algo que reintentar si hay webhooks muertos: las llamadas
            // del panel al puente son síncronas y no tienen cola.
            if (requeueBtn) requeueBtn.hidden = !(canRetry && q.dead > 0);
        }

        function renderByAction(list) {
            if (!list.length) {
                byActionEl.innerHTML = '<span class="psc-opslog-hint">Sin llamadas en la ventana.</span>';
                return;
            }
            byActionEl.innerHTML = list.map(function (a) {
                var title = a.cache_hits ? num(a.cache_hits) + ' de ' + num(a.calls) + ' desde caché' : '';
                return '<div class="psc-opslog-actrow"' + (title ? ' title="' + esc(title) + '"' : '') + '>' +
                    '<span class="nm">' + esc(a.action) + '</span>' +
                    '<span class="c">' + num(a.calls) + '</span>' +
                    '<span class="ms">' + num(a.avg_ms) + ' ms</span>' +
                    (a.failures ? '<span class="psc-tag psc-tag--timeout">' + num(a.failures) + '</span>' : '<span class="psc-opslog-actrow-ok"></span>') +
                    '</div>';
            }).join('');
        }

        function renderError(msg) {
            var html = '<div class="psc-note psc-note--warn"><span class="psc-note-txt">' + esc(msg) + '</span>' +
                '<button type="button" class="psc-note-act" data-opslog-retry>Reintentar</button></div>';
            rowsEl.innerHTML = html;
            queueEl.innerHTML = '<span class="psc-opslog-hint">Sin datos del puente.</span>';
            byActionEl.innerHTML = '<span class="psc-opslog-hint">Sin datos del puente.</span>';
            ['calls', 'avg', 'failures', 'rejected'].forEach(function (k) { kpi(k, '—'); });
            kpi('calls-note', 'al puente');
            if (requeueBtn) requeueBtn.hidden = true;
        }

        function load() {
            var mine = ++seq;
            rowsEl.innerHTML = '<div class="psc-opslog-skel"><div class="psc-skel"></div><div class="psc-skel"></div><div class="psc-skel"></div></div>';
            var url = root.getAttribute('data-url') + '?hours=' + state.hours + '&result=' + encodeURIComponent(state.result);
            request('GET', url).then(function (body) {
                if (mine !== seq) return;
                var d = body.data || {};
                var s = d.stats || {};
                var label = root.querySelector('[data-kpi-label="calls"]');
                if (label) label.textContent = 'Llamadas ' + (state.hours === 1 ? '1 h' : (state.hours === 24 ? '24 h' : '7 días'));
                kpi('calls', num(s.calls));
                kpi('avg', s.avg_ms === null || s.avg_ms === undefined ? '—' : num(s.avg_ms) + ' ms');
                var hits = d.cache_tracked ? (parseInt(s.cache_hits, 10) || 0) : null;
                kpi('calls-note', hits ? num(hits) + ' desde caché' : 'al puente');
                // Con la marca, la media ya excluye las respuestas de caché; sin
                // ella (tienda sin el upgrade 1.2.5) las mezcla y se dice.
                kpi('avg-note', d.cache_tracked
                    ? (hits ? 'sin contar ' + num(hits) + (hits === 1 ? ' respuesta' : ' respuestas') + ' de caché' : 'llamadas reales')
                    : 'incluye respuestas de caché');
                kpi('failures', num(s.failures));
                kpi('failures-note', num(s.errors) + ' errores · ' + num(s.timeouts) + ' timeouts');
                kpi('rejected', num(s.rejected));
                var chip = root.querySelector('#pscOpslogCacheChip');
                if (chip) {
                    chip.hidden = !d.cache_tracked;
                    chip.textContent = hits ? 'Caché · ' + num(hits) : 'Caché';
                }
                var note = root.querySelector('#pscOpslogCacheNote');
                if (note) note.hidden = !!d.cache_tracked;
                renderRows(d.rows || []);
                renderQueue(d.queue || { pending: 0, due: 0, dead: 0, recent_dead: [] });
                renderByAction(d.by_action || []);
            }).catch(function (err) {
                if (mine !== seq) return;
                renderError(err.message || 'No se ha podido leer el registro del puente.');
            });
        }

        root.querySelectorAll('.psc-opslog-window [data-hours]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                state.hours = parseInt(btn.getAttribute('data-hours'), 10) || 24;
                root.querySelectorAll('.psc-opslog-window [data-hours]').forEach(function (b) { b.classList.toggle('is-on', b === btn); });
                load();
            });
        });

        root.querySelectorAll('.psc-opslog-filter [data-result]').forEach(function (chip) {
            chip.addEventListener('click', function () {
                state.result = chip.getAttribute('data-result');
                root.querySelectorAll('.psc-opslog-filter [data-result]').forEach(function (c) { c.classList.toggle('is-on', c === chip); });
                load();
            });
        });

        root.addEventListener('click', function (e) {
            if (e.target.closest('[data-opslog-retry]')) load();
        });

        var reload = root.querySelector('#pscOpslogReload');
        if (reload) reload.addEventListener('click', load);

        function busy(btn, on, label) {
            btn.disabled = on;
            if (on) {
                btn.setAttribute('data-label', btn.textContent);
                btn.textContent = label;
            } else if (btn.getAttribute('data-label')) {
                btn.textContent = btn.getAttribute('data-label');
            }
        }

        if (requeueBtn) {
            requeueBtn.addEventListener('click', function () {
                busy(requeueBtn, true, 'Reintentando…');
                request('POST', root.getAttribute('data-requeue-url'), { 'Idempotency-Key': idemKey() }).then(function (body) {
                    say(body.message || 'Webhooks devueltos a la cola.', true);
                    if (body.data && body.data.queue) renderQueue(body.data.queue);
                }).catch(function (err) {
                    say(err.message, false);
                }).then(function () { busy(requeueBtn, false); });
            });
        }

        if (warmBtn) {
            warmBtn.addEventListener('click', function () {
                busy(warmBtn, true, 'Encolando…');
                request('POST', root.getAttribute('data-warm-url')).then(function (body) {
                    say(body.message || 'Calentado en cola.', true);
                    var hint = root.querySelector('#pscOpslogWarmHint');
                    if (hint) hint.textContent = 'Último calentado lanzado hace unos segundos.';
                }).catch(function (err) {
                    say(err.message, false);
                }).then(function () { busy(warmBtn, false); });
            });
        }

        load();
    }

    /* ─── 38 · Eventos recibidos ─────────────────────────────────── */

    function initEvents(root) {
        var select = root.querySelector('#pscOpslogEventSelect');
        if (select) {
            select.addEventListener('change', function () {
                root.querySelector('#pscOpslogEventFilter').submit();
            });
        }

        root.addEventListener('click', function (e) {
            var btn = e.target.closest('.psc-opslog-reprocess');
            if (!btn || btn.disabled) return;
            var row = btn.closest('[data-event-row]');
            btn.disabled = true;
            btn.textContent = 'Reprocesando…';

            request('POST', btn.getAttribute('data-url')).then(function (body) {
                var d = body.data || {};
                var note = row.querySelector('.psc-opslog-evt-note') || document.createElement('span');
                note.className = 'psc-opslog-evt-note ' + (d.status === 'processed' ? 'is-good' : '');
                note.textContent = body.message || '';
                row.querySelector('.info').appendChild(note);

                if (d.status === 'processed') {
                    row.classList.remove('is-pending');
                    var mt = row.querySelector('.info .mt');
                    if (mt) mt.textContent = d.subject + ' · ' + (d.customer_name ? d.customer_name : 'cliente vinculado');
                    var tag = document.createElement('span');
                    tag.className = 'psc-tag psc-tag--cache';
                    tag.textContent = 'Procesado';
                    btn.replaceWith(tag);
                } else {
                    btn.disabled = false;
                    btn.textContent = 'Reprocesar';
                }
            }).catch(function (err) {
                var note = row.querySelector('.psc-opslog-evt-note') || document.createElement('span');
                note.className = 'psc-opslog-evt-note is-warn';
                note.textContent = err.message;
                row.querySelector('.info').appendChild(note);
                btn.disabled = false;
                btn.textContent = 'Reprocesar';
            });
        });
    }

    function boot() {
        var bridge = document.getElementById('pscOpslogBridge');
        if (bridge) initBridge(bridge);
        var events = document.getElementById('pscOpslogEvents');
        if (events) initEvents(events);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
