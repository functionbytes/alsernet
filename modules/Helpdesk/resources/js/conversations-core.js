/**
 * Bandeja v4 - Helpdesk conversations interactions
 * jQuery + Bootstrap 5.3 (sin React/Babel)
 *
 * Parte de un split de public/vendor/helpdesk/conversations.js (7.138 líneas)
 * en varios archivos por responsabilidad. Ver conversations-core.js para los
 * helpers compartidos (openModal/closeModal/escapeHtml/refreshInboxList/...)
 * expuestos en window para que el resto de archivos los usen tal cual.
 */

(function ($) {
    'use strict';


    // Convierte la sintaxis de formato de WhatsApp (*negrita*, _cursiva_,
    // ~tachado~, ```monoespaciado```) a HTML. Opera sobre texto YA escapado
    // (escapeHtml) para no reintroducir HTML crudo. Los marcadores deben
    // pegar a contenido no-espacio para evitar falsos positivos.
    function renderWhatsAppMarkup(s) {
        return (s == null ? '' : s)
            .replace(/```([^\s`][^`]*?)```/g, '<code>$1</code>')
            .replace(/(^|[^\w*])\*([^\s*][^*]*?)\*(?!\w)/g, '$1<strong>$2</strong>')
            .replace(/(^|[^\w_])_([^\s_][^_]*?)_(?!\w)/g, '$1<em>$2</em>')
            .replace(/(^|[^\w~])~([^\s~][^~]*?)~(?!\w)/g, '$1<s>$2</s>');
    }

    // Cierra los menús flotantes del inbox (más opciones, ordenar, adjuntar).
    // Vive aquí (no en conversations-extras.js, donde estaba originalmente el resto de
    // su sección "Attach menu toggle") porque un listener global de click en <body>
    // (conversations-list.js) puede dispararse antes de que conversations-extras.js
    // termine de cargar, y closeAllMenus debe existir desde el primer momento.
    function closeAllMenus() {
        $('#bv-more-menu, #bv-attach-menu, #bv-sort-menu, .rsp-more-menu').removeClass('on');
        $('#bv-btn-sort, .rsp-more-toggle').attr('aria-expanded', 'false');
    }

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Exponer de inmediato (no dependen del DOM) para el resto de archivos del inbox.
    window.escapeHtml = escapeHtml;
    window.renderWhatsAppMarkup = renderWhatsAppMarkup;
    window.closeAllMenus = closeAllMenus;

    $(function () {
        // ─── Auto-scroll del thread al final ──────────────────────────
        function scrollThreadToBottom(smooth) {
            const $body = $('.bv-th-body');
            if (!$body.length) return;
            const target = $body[0].scrollHeight;
            if (smooth) {
                $body[0].scrollTo({ top: target, behavior: 'smooth' });
            } else {
                $body[0].scrollTop = target;
            }
        }
        window.scrollThreadToBottom = scrollThreadToBottom;

        // Al abrir el thread, ir al final inmediatamente (sin animación)
        scrollThreadToBottom(false);

        // Volver a hacer scroll cuando carguen imágenes/videos (cambian el alto del contenedor)
        $('.bv-th-inner img, .bv-th-inner video').each(function () {
            const el = this;
            if (el.tagName === 'IMG' && !el.complete) {
                $(el).one('load error', () => scrollThreadToBottom(false));
            } else if (el.tagName === 'VIDEO') {
                $(el).one('loadedmetadata', () => scrollThreadToBottom(false));
            }
        });


        // ─── Modales ─────────────────────────────────────────────────
        // UI-04: foco que se restaura al cerrar el modal.
        let lastFocusedBeforeModal = null;

        const MODAL_FOCUSABLE = 'a[href], button:not([disabled]), textarea:not([disabled]), '
            + 'input:not([disabled]):not([type=hidden]), select:not([disabled]), [tabindex]:not([tabindex="-1"])';

        // UI-04: añade semántica ARIA de diálogo sin alterar el flujo de apertura.
        function applyModalA11y($modal, name) {
            const $dialog = $modal.find('.bv-modal-dialog').first();
            const $d = $dialog.length ? $dialog : $modal;
            $modal.attr('role', 'presentation');
            $d.attr('role', 'dialog').attr('aria-modal', 'true');
            const $title = $d.find('.bv-modal-title').first();
            if ($title.length) {
                let titleId = $title.attr('id');
                if (!titleId) {
                    titleId = 'bv-modal-title-' + name;
                    $title.attr('id', titleId);
                }
                $d.attr('aria-labelledby', titleId);
            }
            // UI-04: garantiza aria-label en el botón de cierre de los 72 partials bv-modal
            // que aún no lo tienen en su Blade (evita editarlos uno a uno).
            $modal.find('.bv-modal-close').each(function () {
                if (!$(this).attr('aria-label')) {
                    $(this).attr('aria-label', 'Cerrar');
                }
            });
            return $d;
        }

        // Carga diferida de scripts de modal (auditoría 17-sep-2026): la mayoría de los
        // ~65 modales del inbox no se abren en la mayor parte de las sesiones, pero antes
        // se descargaban SIEMPRE en cada carga de página. window.BvLazyModalScripts (mapa
        // nombre → URL con su filemtime, generado en modals.blade.php) sólo cubre esos
        // modales; los de uso frecuente (status/priority/filter/edit-contact/shortcuts) y
        // los que abren su script fuera del flujo openModal (command-palette, email-viewer,
        // _commerce-js) siguen cargando su <script> de forma síncrona, sin entrar aquí.
        const loadedModalScripts = window.BvLoadedModalScripts || new Set();
        window.BvLoadedModalScripts = loadedModalScripts;

        function ensureModalScript(name, onReady) {
            const src = (window.BvLazyModalScripts || {})[name];
            if (!src || loadedModalScripts.has(name)) {
                onReady();
                return;
            }
            const script = document.createElement('script');
            script.src = src;
            script.defer = true;
            script.onload = script.onerror = function () {
                // Se marca como cargado incluso si falla (onerror) para no reintentar
                // la descarga en cada apertura y dejar el modal inutilizable en bucle.
                loadedModalScripts.add(name);
                onReady();
            };
            document.head.appendChild(script);
        }

        function openModal(name) {
            const $modal = $(`[data-bv-modal-name="${name}"]`);
            if (!$modal.length) { return; }
            lastFocusedBeforeModal = document.activeElement;
            ensureModalScript(name, function () {
                const $dialog = applyModalA11y($modal, name);
                $modal.addClass('on');
                $('body').css('overflow', 'hidden');
                $(document).trigger('bv:modal:open', [name]);
                // UI-04: mover el foco dentro del diálogo (focus-trap).
                if (!$dialog.attr('tabindex')) { $dialog.attr('tabindex', '-1'); }
                $dialog.trigger('focus');
            });
        }

        function closeModal($modal) {
            $modal.removeClass('on');
            if ($modal.hasClass('bv-lightbox')) {
                const v = document.getElementById('bv-lightbox-video');
                if (v) { v.pause(); v.removeAttribute('src'); v.load(); }
                const a = document.getElementById('bv-lightbox-audio');
                if (a) { a.pause(); a.removeAttribute('src'); a.load(); }
                const img = document.getElementById('bv-lightbox-img');
                if (img) { img.removeAttribute('src'); img.classList.remove('bv-lb-error'); }
            }
            if ($('.bv-modal.on').length === 0) {
                $('body').css('overflow', '');
                // UI-04: restaurar el foco al elemento que abrió el modal.
                if (lastFocusedBeforeModal && document.contains(lastFocusedBeforeModal)) {
                    try { lastFocusedBeforeModal.focus(); } catch (err) { /* noop */ }
                }
                lastFocusedBeforeModal = null;
            }
        }

        // UI-04: focus-trap — Tab cicla dentro del modal abierto.
        $(document).on('keydown', function (e) {
            if (e.key !== 'Tab') { return; }
            const $open = $('.bv-modal.on').last();
            if (!$open.length) { return; }
            const $dialog = $open.find('.bv-modal-dialog').first();
            const $scope = $dialog.length ? $dialog : $open;
            const $focusable = $scope.find(MODAL_FOCUSABLE).filter(':visible');
            if (!$focusable.length) { return; }
            const first = $focusable.first()[0];
            const last = $focusable.last()[0];
            const active = document.activeElement;
            if (e.shiftKey && (active === first || active === $scope[0])) {
                e.preventDefault();
                last.focus();
            } else if (!e.shiftKey && active === last) {
                e.preventDefault();
                first.focus();
            }
        });

        // Abrir modal por click en data-bv-modal
        $(document).on('click', '[data-bv-modal]', function (e) {
            const name = $(this).data('bv-modal');
            if (name) {
                e.preventDefault();
                closeAllMenus();
                openModal(name);
            }
        });

        // Soporte de teclado para triggers no-<button> (ej. filas con role="button").
        $(document).on('keydown', '[data-bv-modal][role="button"]', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                $(this).trigger('click');
            }
        });

        // Cerrar modal por click en data-bv-close o backdrop
        // (soporta data-bv-open="{name}" para encadenar la apertura de otro modal, ej. "Volver al historial")
        $(document).on('click', '[data-bv-close]', function () {
            closeModal($(this).closest('.bv-modal'));
            const openName = $(this).attr('data-bv-open');
            if (openName) {
                openModal(openName);
            }
        });

        $(document).on('click', '.bv-modal', function (e) {
            if (e.target === this) {
                closeModal($(this));
            }
        });

        // ESC cierra el modal abierto
        $(document).on('keydown', function (e) {
            if (e.key === 'Escape') {
                const $open = $('.bv-modal.on').last();
                if ($open.length) {
                    closeModal($open);
                }
            }
        });


        function setRtabUrl(tab) {
            try {
                const u = new URL(window.location.href);
                u.searchParams.set('rtab', tab);
                history.replaceState(history.state, '', u.toString());
            } catch (_) {}
        }
        function getRtabFromUrl() {
            try { return new URL(window.location.href).searchParams.get('rtab') || null; } catch (_) { return null; }
        }

        // ─── Helpers AJAX inbox ──────────────────────────────────────
        const inboxParams = ['channel', 'unread', 'mine', 'urgent', 'vip', 'archived',
            'priority', 'status', 'group', 'tag', 'viewId', 'search', 'selected', 'sort',
            'assignee', 'date', 'rtab'];

        function readInboxFiltersFromUrl() {
            const u = new URL(window.location.href);
            const out = {};
            inboxParams.forEach(p => {
                const v = u.searchParams.get(p);
                if (v !== null && v !== '') out[p] = v;
            });
            return out;
        }

        let lastInboxRefresh = 0;
        let pendingInboxRefresh = null;

        function refreshInboxList(params, opts = {}) {
            const now = Date.now();
            const since = now - lastInboxRefresh;
            if (!opts.force && since < 1500) {
                clearTimeout(pendingInboxRefresh);
                pendingInboxRefresh = setTimeout(() => refreshInboxList(params, { force: true }), 1500 - since);
                return;
            }
            lastInboxRefresh = now;

            // Preserve rtab across inbox navigation
            const rtab = getRtabFromUrl();
            if (rtab) { params = params || {}; if (!params.rtab) params.rtab = rtab; }
            const qs = $.param(params || {});
            const newUrl = '/panel/helpdesk/conversations' + (qs ? '?' + qs : '');
            history.pushState({}, '', newUrl);

            $.ajax({
                url: '/panel/helpdesk/conversations/list' + (qs ? '?' + qs : ''),
                method: 'GET',
                dataType: 'json',
                headers: { 'Accept': 'application/json' },
            })
                .done(resp => {
                    if (resp.html) {
                        const $temp = $('<div>').html(resp.html);
                        const $newConvList = $temp.find('.bv-conv-list');
                        if ($newConvList.length) {
                            $('.bv-conv-list').html($newConvList.html());
                        }
                    }
                    if (resp.counts) {
                        ['total', 'unread', 'mine', 'urgent'].forEach(k => {
                            if (resp.counts[k] !== undefined) {
                                $('[data-counter="' + k + '"]').text(resp.counts[k]);
                            }
                        });
                    }
                })
                .fail(() => { if (window.toastr) toastr.error('No se pudo refrescar la lista'); });
        }

        function applyInboxFilters(updates) {
            const next = { ...readInboxFiltersFromUrl(), ...updates };
            Object.keys(next).forEach(k => {
                if (next[k] === null || next[k] === '' || next[k] === false || next[k] === '0') {
                    delete next[k];
                }
            });
            refreshInboxList(next, { force: true });
            updateFilterBadge();
        }

        // Badge de filtros activos sobre el botón de filtro de la barra
        function updateFilterBadge() {
            const u = new URL(window.location.href);
            const keys = ['channel', 'status', 'priority', 'tag', 'mine', 'unread', 'urgent', 'vip', 'assignee', 'group', 'archived'];
            let n = 0;
            keys.forEach(k => {
                const v = u.searchParams.get(k);
                if (v !== null && v !== '' && v !== '0') {
                    n += String(v).split(',').filter(Boolean).length;
                }
            });
            const $b = $('#bvFilterBadge');
            if (n > 0) { $b.text(n).prop('hidden', false); }
            else { $b.text('').prop('hidden', true); }
        }
        updateFilterBadge();

        // Expose so inline scripts (e.g. filter modal) can call it
        window.applyInboxFilters = applyInboxFilters;


        // Exponer helpers compartidos con conversations-list.js / conversations-thread.js /
        // conversations-panel.js / conversations-extras.js (se cargan después de este archivo).
        window.openModal = openModal;
        window.closeModal = closeModal;
        window.applyModalA11y = applyModalA11y;
        window.refreshInboxList = refreshInboxList;
        window.readInboxFiltersFromUrl = readInboxFiltersFromUrl;
        window.getRtabFromUrl = getRtabFromUrl;
        window.setRtabUrl = setRtabUrl;
        window.bvSetLastFocusedBeforeModal = function (el) { lastFocusedBeforeModal = el; };

    });
})(jQuery);

// ═══════════════════════════════════════════════════════════════════
// Push notifications (Web Notification API) — extraído de inbox/index.blade.php.
// Expone window.showInboxPushNotif, usado por el listener global de la bandeja
// (conversations-list.js) y por el listener por conversación (conversations-thread.js),
// ambos cargados después de este archivo. Wire-up del botón #bv-notif-toggle vive
// aquí porque ese botón está en el chrome global del inbox (statusbar de
// inbox/index.blade.php), no en un partial de lista/hilo/panel concreto.
// ═══════════════════════════════════════════════════════════════════
(function () {
    'use strict';

    const STORAGE_KEY = 'bv:notif:enabled';
    const SESSION_KEY = 'bv:notif:asked';

    function notifEnabled() {
        return localStorage.getItem(STORAGE_KEY) !== 'false';
    }

    function setNotifEnabled(val) {
        localStorage.setItem(STORAGE_KEY, val ? 'true' : 'false');
    }

    // Update bell button appearance
    function syncBellUI() {
        const $btn = $('#bv-notif-toggle');
        const enabled = notifEnabled() && Notification.permission === 'granted';
        $btn.find('i').toggleClass('fa-bell', enabled).toggleClass('fa-bell-slash', !enabled);
        $btn.attr('title', enabled ? 'Desactivar notificaciones' : 'Activar notificaciones');
    }

    // Ask for permission (once per session, via a friendly toast)
    function requestPermissionOnce() {
        if (!window.Notification) return;
        if (Notification.permission !== 'default') return;
        if (sessionStorage.getItem(SESSION_KEY)) return;
        sessionStorage.setItem(SESSION_KEY, '1');

    }

    $(document).on('click', '#bv-notif-allow', function (e) {
        e.preventDefault();
        Notification.requestPermission().then(syncBellUI);
    });

    // Bell toggle button
    $(document).on('click', '#bv-notif-toggle', function () {
        if (!window.Notification) {
            toastr && toastr.warning('Tu navegador no soporta notificaciones.');
            return;
        }
        if (Notification.permission === 'denied') {
            toastr && toastr.warning('Las notificaciones están bloqueadas. Desbloquéalas en la configuración del navegador.');
            return;
        }
        if (Notification.permission === 'default') {
            Notification.requestPermission().then(function (perm) {
                if (perm === 'granted') {
                    setNotifEnabled(true);
                }
                syncBellUI();
            });
            return;
        }
        // Toggle enabled state
        setNotifEnabled(!notifEnabled());
        syncBellUI();
        const msg = notifEnabled() ? 'Notificaciones activadas.' : 'Notificaciones desactivadas.';
    });

    // Show a native push notification for an incoming message
    window.showInboxPushNotif = function (convId, customerName, messagePreview, avatarUrl) {
        if (!notifEnabled()) return;
        if (!window.Notification || Notification.permission !== 'granted') return;

        const opts = {
            body: messagePreview || 'Nuevo mensaje recibido',
            tag: 'helpdesk-' + convId,    // replace existing notif for same conv
            requireInteraction: false,
        };
        if (avatarUrl) opts.icon = avatarUrl;

        const notif = new Notification(customerName || 'Nuevo mensaje', opts);
        notif.onclick = function () {
            window.focus();
            const url = '/panel/helpdesk/conversations?selected=' + convId;
            window.location.href = url;
            notif.close();
        };
    };

    // Initialize bell UI on load
    $(function () {
        syncBellUI();
        // Ask only after first user interaction (1s delay avoids spamming on load)
        setTimeout(function () {
            $(document).one('click keydown', requestPermissionOnce);
        }, 1000);
    });

    // ─── Latido de presencia del agente (QA 18-sep-2026) ──────────────────
    // Vivía dentro de modals/away-mode.js, pero ese modal se carga en diferido
    // (auditoría 17-sep-2026: window.BvLazyModalScripts) — su JS solo se
    // descarga la primera vez que el agente abre "Cambiar disponibilidad". El
    // resultado: mientras nadie abriera ese modal, AgentPresenceService::
    // getOnlineAgents() nunca veía un heartbeat de nadie, así que "X agentes
    // en línea" marcaba 0 siempre, con agentes reales usando el inbox. Movido
    // aquí (core, se carga siempre) para que el latido arranque con la
    // página, sin depender de que se abra ese modal.
    function bvPresenceHeartbeat() {
        $.ajax({
            url: '/panel/helpdesk/presence/heartbeat',
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), 'Accept': 'application/json' },
        });
    }
    bvPresenceHeartbeat();
    setInterval(bvPresenceHeartbeat, 60000);
    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) { bvPresenceHeartbeat(); }
    });
})();
