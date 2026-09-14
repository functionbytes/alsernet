'use strict';

/**
 * Gestión de tickets — /panel/helpdesk/tickets
 * Fase A: shell de 3 columnas, listado, tabs de estado, filtros, bulk
 * actions y una vista de detalle básica (solo con los datos ya cargados
 * en #tkt-data, sin AJAX todavía). El hilo completo, la trazabilidad, el
 * modo Kanban con arrastrar/soltar y el panel lateral de 8 pestañas llegan
 * en las fases siguientes — ver el plan de "Gestión de tickets".
 *
 * Mismo patrón de namespace/hidratación que emails.js (bandeja "Emails
 * enviados"): un único objeto TKA = {state, urls}, initTicketsApp() lee
 * #tkt-data, y las funciones render* son puras sobre TKA.state.
 */

    var TKA = {
        state: {
            tickets: [],
            tabCounts: {},
            filter: 'open',
            selected: null,
            currentUserId: null,
            bulk: {},
            listRefreshTimer: null,
            listRefreshInFlight: false,
            listRefreshQueued: false,
            listRefreshInterval: null,
            splitLayout: null,
            listDensity: 'normal',
            mobilePane: 'list',
            draftSaveTimer: null,
            threadHistoryExpanded: false,
            slaTimer: null,
            undoAction: null,
            undoTimer: null,
            networkOnline: typeof navigator === 'undefined' || navigator.onLine !== false,
            offlineQueue: [],
            offlineAttachmentQueue: [],
            realtimeRetryTimer: null,
            realtimeRetryCount: 0,
            realtimeLastEventAt: null,
            realtimeEventCount: 0,
            realtimeErrorCount: 0,
            threadSearch: '',
            threadSearchTimer: null,
            threadPage: 1,
            threadHasMore: false,
            threadTotal: 0,
            threadPerPage: 30,
            threadFilters: { sender: '', type: 'all', from: '', to: '', channel: '' },
            threadLoadingMore: false,
            editConflictMessage: '',
            editConflictFields: [],
            slaNotified: {},
            offlineRetryTimer: null,
        },
        urls: {},

        // Cada cuánto se comprueba si el ticket abierto tiene algo nuevo.
        // Tres segundos es un intervalo cómodo porque lo que se pide es la
        // sonda /pulse (dos agregados), no el hilo entero. Se puede subir desde
        // la vista si algún día hay muchos agentes a la vez.
        pulseIntervalMs: 3000,
    };

    // Preferencias visuales del split. Se guardan por navegador y usuario para
    // que cada agente pueda dar más espacio a la conversación o a la gestión
    // sin convertirlo en una configuración global del helpdesk.
    var TKT_SPLIT_LAYOUT_KEY = 'helpdesk.tickets.split-layout.v1';
    var TKT_SPLIT_DEFAULTS = { listWidth: 380, sideWidth: 316, sideCollapsed: false };
    var TKT_LIST_DENSITY_KEY = 'helpdesk.tickets.list-density.v1';
    var TKT_MOBILE_PANE_KEY = 'helpdesk.tickets.mobile-pane.v1';
    var TKT_DRAFT_KEY_PREFIX = 'helpdesk.tickets.draft.v1.';
    var TKT_OFFLINE_QUEUE_KEY = 'helpdesk.tickets.offline-replies.v1';
    var TKT_OFFLINE_DB_NAME = 'helpdesk-tickets-offline-v1';
    var TKT_OFFLINE_DB_STORE = 'replies';
    var TKT_OFFLINE_MAX_ATTEMPTS = 8;
    var TKT_OFFLINE_RETRY_BASE_MS = 5000;
    var TKT_OFFLINE_RETENTION_MS = 24 * 60 * 60 * 1000;
    var TKT_OFFLINE_MAX_ENTRIES = 50;
    var TKT_ATTACHMENT_MAX_BYTES = 10 * 1024 * 1024;
    var TKT_ATTACHMENT_EXTENSIONS = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'gif', 'zip', 'rar', 'txt'];
    var TKT_THREAD_VISIBLE_LIMIT = 12;

    function clampSplitWidth(value, min, max) {
        var n = parseInt(value, 10);
        if (isNaN(n)) n = min;
        return Math.max(min, Math.min(max, n));
    }

    function readSplitLayout() {
        var out = $.extend({}, TKT_SPLIT_DEFAULTS);
        try {
            var raw = window.localStorage.getItem(TKT_SPLIT_LAYOUT_KEY);
            if (raw) $.extend(out, JSON.parse(raw));
        } catch (e) { /* localStorage bloqueado o dato antiguo: usa defaults */ }

        out.listWidth = clampSplitWidth(out.listWidth, 320, 520);
        out.sideWidth = clampSplitWidth(out.sideWidth, 290, 460);
        out.sideCollapsed = out.sideCollapsed === true;
        return out;
    }

    function persistSplitLayout() {
        var layout = TKA.state.splitLayout;
        if (!layout) return;
        try { window.localStorage.setItem(TKT_SPLIT_LAYOUT_KEY, JSON.stringify(layout)); } catch (e) { /* opcional */ }
    }

    function applySplitLayout() {
        var layout = TKA.state.splitLayout || readSplitLayout();
        TKA.state.splitLayout = layout;
        var split = document.querySelector('.tkt-split');
        var side = document.getElementById('tkt-side');
        var toggle = document.getElementById('tkt-side-toggle');
        if (!split) return;

        split.style.setProperty('--tkt-list-width', layout.listWidth + 'px');
        split.style.setProperty('--tkt-side-width', layout.sideWidth + 'px');
        split.classList.toggle('side-collapsed', layout.sideCollapsed);
        if (side) side.classList.toggle('is-collapsed', layout.sideCollapsed);
        if (toggle) {
            toggle.setAttribute('aria-pressed', layout.sideCollapsed ? 'true' : 'false');
            toggle.setAttribute('aria-label', layout.sideCollapsed ? 'Mostrar panel de gestión' : 'Ocultar panel de gestión');
            toggle.title = layout.sideCollapsed ? 'Mostrar panel de gestión' : 'Ocultar panel de gestión';
            toggle.innerHTML = '<i class="fa-solid ' + (layout.sideCollapsed ? 'fa-angles-left' : 'fa-angles-right') + '"></i>';
        }
    }

    function setSideCollapsed(collapsed) {
        if (!TKA.state.splitLayout) TKA.state.splitLayout = readSplitLayout();
        TKA.state.splitLayout.sideCollapsed = !!collapsed;
        applySplitLayout();
        persistSplitLayout();
    }

    function resizeSplit(target, movement) {
        if (!TKA.state.splitLayout) TKA.state.splitLayout = readSplitLayout();
        if (TKA.state.splitLayout.sideCollapsed) return;

        if (target === 'list') {
            TKA.state.splitLayout.listWidth = clampSplitWidth(
                TKA.state.splitLayout.listWidth + movement, 320, 520
            );
        } else {
            // El segundo divisor se mueve en sentido contrario al ancho del
            // panel lateral: arrastrarlo a la derecha da más espacio al hilo.
            TKA.state.splitLayout.sideWidth = clampSplitWidth(
                TKA.state.splitLayout.sideWidth - movement, 290, 460
            );
        }
        applySplitLayout();
    }

    function initSplitLayout() {
        TKA.state.splitLayout = readSplitLayout();
        applySplitLayout();

        $('#tkt-side-toggle').off('click.tktSplit').on('click.tktSplit', function () {
            setSideCollapsed(!TKA.state.splitLayout.sideCollapsed);
        });

        var $handles = $('.tkt-split-resizer');
        $handles.off('.tktSplit').on('pointerdown.tktSplit', function (ev) {
            if (TKA.state.splitLayout && TKA.state.splitLayout.sideCollapsed) return;
            var handle = this;
            if (handle.setPointerCapture) handle.setPointerCapture(ev.pointerId);
            $(handle).addClass('dragging');
            $('body').addClass('tkt-split-resizing');
            TKA.state.splitDrag = { target: $(handle).data('resize-target'), x: ev.clientX };
            ev.preventDefault();
        }).on('dblclick.tktSplit', function () {
            if (!TKA.state.splitLayout) TKA.state.splitLayout = readSplitLayout();
            var target = $(this).data('resize-target');
            if (target === 'list') TKA.state.splitLayout.listWidth = TKT_SPLIT_DEFAULTS.listWidth;
            if (target === 'side') TKA.state.splitLayout.sideWidth = TKT_SPLIT_DEFAULTS.sideWidth;
            applySplitLayout();
            persistSplitLayout();
        }).on('keydown.tktSplit', function (ev) {
            var amount = 0;
            if (ev.key === 'ArrowRight') amount = 16;
            if (ev.key === 'ArrowLeft') amount = -16;
            if (!amount) return;
            resizeSplit($(this).data('resize-target'), amount);
            persistSplitLayout();
            ev.preventDefault();
        });

        $(document).off('pointermove.tktSplit pointerup.tktSplit pointercancel.tktSplit')
            .on('pointermove.tktSplit', function (ev) {
                var drag = TKA.state.splitDrag;
                if (!drag) return;
                var movement = ev.clientX - drag.x;
                drag.x = ev.clientX;
                resizeSplit(drag.target, movement);
            })
            .on('pointerup.tktSplit pointercancel.tktSplit', function () {
                if (!TKA.state.splitDrag) return;
                $('.tkt-split-resizer').removeClass('dragging');
                $('body').removeClass('tkt-split-resizing');
                TKA.state.splitDrag = null;
                persistSplitLayout();
            });
    }

    function readPreference(key, fallback) {
        try {
            var value = window.localStorage.getItem(key);
            return value == null ? fallback : value;
        } catch (e) {
            return fallback;
        }
    }

    function writePreference(key, value) {
        try { window.localStorage.setItem(key, value); } catch (e) { /* opcional */ }
    }

    function readOfflineQueue() {
        try {
            var raw = window.localStorage.getItem(TKT_OFFLINE_QUEUE_KEY);
            var parsed = raw ? JSON.parse(raw) : [];
            return Array.isArray(parsed) ? trimOfflineQueue(parsed.map(normalizeOfflineEntry).filter(function (entry) {
                return !offlineEntryExpired(entry);
            })) : [];
        } catch (e) {
            return [];
        }
    }

    function normalizeOfflineEntry(entry) {
        entry = entry || {};
        return $.extend({
            createdAt: new Date().toISOString(),
            attempts: 0,
            nextAttemptAt: null,
            lastError: '',
            dead: false,
        }, entry);
    }

    function offlineEntryExpired(entry) {
        var createdAt = Date.parse(entry && entry.createdAt);
        return Number.isFinite(createdAt) && Date.now() - createdAt > TKT_OFFLINE_RETENTION_MS;
    }

    function trimOfflineQueue(queue) {
        queue = Array.isArray(queue) ? queue : [];
        return queue.length > TKT_OFFLINE_MAX_ENTRIES ? queue.slice(-TKT_OFFLINE_MAX_ENTRIES) : queue;
    }

    function writeOfflineQueue(queue) {
        var normalized = (queue || []).map(normalizeOfflineEntry).filter(function (entry) {
            return !offlineEntryExpired(entry);
        });
        var remainingSlots = Math.max(0, TKT_OFFLINE_MAX_ENTRIES - (TKA.state.offlineAttachmentQueue || []).length);
        TKA.state.offlineQueue = remainingSlots > 0 ? trimOfflineQueue(normalized).slice(-remainingSlots) : [];
        try {
            if (TKA.state.offlineQueue.length) {
                window.localStorage.setItem(TKT_OFFLINE_QUEUE_KEY, JSON.stringify(TKA.state.offlineQueue));
            } else {
                window.localStorage.removeItem(TKT_OFFLINE_QUEUE_KEY);
            }
        } catch (e) { /* localStorage opcional */ }
        updateOfflineQueueStatus();
    }

    function openOfflineDb() {
        if (!window.indexedDB) return Promise.reject(new Error('IndexedDB no disponible'));
        return new Promise(function (resolve, reject) {
            var request = window.indexedDB.open(TKT_OFFLINE_DB_NAME, 1);
            request.onupgradeneeded = function () {
                if (!request.result.objectStoreNames.contains(TKT_OFFLINE_DB_STORE)) {
                    request.result.createObjectStore(TKT_OFFLINE_DB_STORE, { keyPath: 'id' });
                }
            };
            request.onsuccess = function () { resolve(request.result); };
            request.onerror = function () { reject(request.error || new Error('No se pudo abrir la cola offline')); };
        });
    }

    function readOfflineAttachmentQueue() {
        return openOfflineDb().then(function (db) {
            return new Promise(function (resolve, reject) {
                var request = db.transaction(TKT_OFFLINE_DB_STORE, 'readonly').objectStore(TKT_OFFLINE_DB_STORE).getAll();
                request.onsuccess = function () { resolve(request.result || []); };
                request.onerror = function () { reject(request.error); };
            });
        });
    }

    function putOfflineAttachmentReply(entry) {
        return openOfflineDb().then(function (db) {
            return new Promise(function (resolve, reject) {
                var request = db.transaction(TKT_OFFLINE_DB_STORE, 'readwrite').objectStore(TKT_OFFLINE_DB_STORE).put(entry);
                request.onsuccess = function () { resolve(true); };
                request.onerror = function () { reject(request.error); };
            });
        });
    }

    function deleteOfflineAttachmentReply(id) {
        return openOfflineDb().then(function (db) {
            return new Promise(function (resolve, reject) {
                var request = db.transaction(TKT_OFFLINE_DB_STORE, 'readwrite').objectStore(TKT_OFFLINE_DB_STORE).delete(id);
                request.onsuccess = function () { resolve(true); };
                request.onerror = function () { reject(request.error); };
            });
        });
    }

    function pruneOfflineAttachmentQueue(queue) {
        var normalized = (queue || []).map(normalizeOfflineEntry);
        var expired = normalized.filter(offlineEntryExpired);
        var live = normalized.filter(function (entry) { return !offlineEntryExpired(entry); });
        var remainingSlots = Math.max(0, TKT_OFFLINE_MAX_ENTRIES - (TKA.state.offlineQueue || []).length);
        var overflow = live.length > remainingSlots ? live.slice(0, live.length - remainingSlots) : [];
        var kept = remainingSlots > 0 ? live.slice(-remainingSlots) : [];

        return Promise.all(expired.concat(overflow).map(function (entry) {
            return deleteOfflineAttachmentReply(entry.id).catch(function () { return false; });
        })).then(function () { return kept; });
    }

    function updateOfflineQueueStatus() {
        var $queue = $('#tkt-status-queue');
        if (!$queue.length) return;
        var count = (TKA.state.offlineQueue || []).length;
        var attachmentCount = (TKA.state.offlineAttachmentQueue || []).length;
        var mailCount = Number(TKA.state.mailQueueCount || 0);
        var pendingEntries = (TKA.state.offlineQueue || []).concat(TKA.state.offlineAttachmentQueue || []);
        var failedCount = pendingEntries.filter(function (entry) { return entry.dead === true; }).length;
        var parts = [];
        if (count) parts.push(count + (count === 1 ? ' respuesta pendiente' : ' respuestas pendientes'));
        if (attachmentCount) parts.push(attachmentCount + (attachmentCount === 1 ? ' adjunto pendiente' : ' adjuntos pendientes'));
        if (failedCount) parts.push(failedCount + (failedCount === 1 ? ' reintento bloqueado' : ' reintentos bloqueados'));
        if (mailCount) parts.push('cola de correo: ' + mailCount);
        $queue.prop('hidden', parts.length === 0);
        $queue.attr('aria-label', parts.join(' · ') || 'Sin respuestas pendientes');
        $queue.html('<i class="fa-regular fa-envelope"></i> ' + parts.join(' · '));
        $queue.attr('title', parts.length ? 'Abrir cola de respuestas pendientes' : 'Sin respuestas pendientes');
    }

    function setNetworkState(online, detail) {
        TKA.state.networkOnline = !!online;
        var $bar = $('#tkt-status-bar');
        var $dot = $('#tkt-status-conn-dot');
        var $text = $('#tkt-status-conn-text');
        $bar.toggleClass('is-offline', !online).toggleClass('is-online', !!online);
        $dot.removeClass('on connecting off').addClass(online ? 'on' : 'off');
        $text.text(online ? (detail || 'Conectado') : 'Sin conexión');
        $dot.attr('title', online
            ? ($dot.data('realtime-last') || 'Tiempo real pendiente de eventos')
            : 'Sin conexión con el servidor');
        updateOfflineQueueStatus();
    }

    function markRealtimeEvent(label) {
        TKA.state.realtimeLastEventAt = Date.now();
        TKA.state.realtimeEventCount = (TKA.state.realtimeEventCount || 0) + 1;
        var stamp = new Date(TKA.state.realtimeLastEventAt).toLocaleTimeString('es-ES');
        var text = (label || 'Evento recibido') + ' · ' + stamp;
        var $dot = $('#tkt-status-conn-dot');
        $dot.data('realtime-last', text).attr('title', text);
        $('#tkt-status-bar').attr('data-realtime-last', text);
    }

    function scheduleRealtimeRetry(ticketId) {
        if (!ticketId || TKA.state.realtimeRetryTimer) return;
        var attempt = TKA.state.realtimeRetryCount || 0;
        var delay = Math.min(15000, Math.max(1000, Math.pow(2, attempt) * 1000));
        TKA.state.realtimeRetryCount = attempt + 1;
        TKA.state.realtimeRetryTimer = setTimeout(function () {
            TKA.state.realtimeRetryTimer = null;
            var current = TKA.state.currentTicket;
            if (current && String(current.id) === String(ticketId)) joinTicketPresence(ticketId);
        }, delay);
    }

    function initNetworkResilience() {
        TKA.state.offlineQueue = readOfflineQueue();
        readOfflineAttachmentQueue().then(function (queue) {
            return pruneOfflineAttachmentQueue(queue);
        }).then(function (queue) {
            TKA.state.offlineAttachmentQueue = queue;
            updateOfflineQueueStatus();
            if (TKA.state.networkOnline) flushOfflineReplies();
        }).catch(function () { TKA.state.offlineAttachmentQueue = []; });
        setNetworkState(TKA.state.networkOnline);

        if (TKA.state.networkListenersBound) return;
        TKA.state.networkListenersBound = true;
        window.addEventListener('offline', function () {
            setNetworkState(false);
            if (window.toastr) toastr.warning('Se perdió la conexión. Las respuestas de texto se guardarán para reintentarlas.');
        });
        window.addEventListener('online', function () {
            setNetworkState(true, 'Conexión recuperada');
            TKA.state.realtimeRetryCount = 0;
            if (TKA.state.currentTicket) joinTicketPresence(TKA.state.currentTicket.id);
            flushOfflineReplies();
            queueTicketListRefresh('connection-restored', TKA.state.currentTicket, { freshCounts: true, preserveBulk: true });
        });
        if (TKA.state.networkOnline) flushOfflineReplies();
    }

    function hideUndo() {
        clearTimeout(TKA.state.undoTimer);
        TKA.state.undoTimer = null;
        TKA.state.undoAction = null;
        $('#tkt-undo').prop('hidden', true);
    }

    function offerUndo(label, callback) {
        clearTimeout(TKA.state.undoTimer);
        TKA.state.undoAction = { callback: callback };
        $('#tkt-undo-text').text(label);
        $('#tkt-undo').prop('hidden', false);
        TKA.state.undoTimer = setTimeout(hideUndo, 8000);
    }

    function applyListDensity(compact) {
        var isCompact = !!compact;
        TKA.state.listDensity = isCompact ? 'compact' : 'normal';
        $('.tkt-split-list').toggleClass('is-compact', isCompact);
        var $button = $('#tkt-list-density');
        if ($button.length) {
            $button.attr('aria-pressed', isCompact ? 'true' : 'false')
                .attr('aria-label', isCompact ? 'Usar lista normal' : 'Usar lista compacta')
                .attr('title', isCompact ? 'Usar lista normal' : 'Usar lista compacta')
                .html('<i class="fa-solid ' + (isCompact ? 'fa-expand' : 'fa-compress') + '"></i>');
        }
    }

    function initListDensity() {
        applyListDensity(readPreference(TKT_LIST_DENSITY_KEY, 'normal') === 'compact');
    }

    function applyMobilePane(which) {
        which = ['list', 'detail', 'side'].indexOf(which) !== -1 ? which : 'list';
        TKA.state.mobilePane = which;
        var $split = $('.tkt-split');
        $split.removeClass('mobile-list mobile-detail mobile-side').addClass('mobile-' + which);
        $('#tkt-mobile-nav [data-mobile-pane]').each(function () {
            var on = $(this).data('mobile-pane') === which;
            $(this).toggleClass('on', on).attr('aria-selected', on ? 'true' : 'false');
        });
        writePreference(TKT_MOBILE_PANE_KEY, which);
    }

    function initMobilePane() {
        applyMobilePane(readPreference(TKT_MOBILE_PANE_KEY, 'list'));
    }

    function openMobilePane(which) {
        applyMobilePane(which);
    }

    function draftStorageKey(ticket) {
        return TKT_DRAFT_KEY_PREFIX + (ticket && ticket.id != null ? String(ticket.id) : '');
    }

    function readComposerDraft(ticket) {
        if (!ticket || ticket.id == null) return null;
        try {
            var raw = window.localStorage.getItem(draftStorageKey(ticket));
            if (!raw) return null;
            var draft = JSON.parse(raw);
            return draft && typeof draft.body === 'string' && draft.body.trim()
                ? draft
                : null;
        } catch (e) {
            return null;
        }
    }

    function updateDraftStatus($composer, draft, savedNow) {
        var $status = $composer && $composer.find('#tkt-draft-status');
        if (!$status || !$status.length) return;
        var hasDraft = !!(draft && draft.body && draft.body.trim());
        $status.toggleClass('saved', hasDraft).text(hasDraft
            ? (savedNow ? 'Borrador guardado' : 'Borrador recuperado')
            : '');
        $status.attr('aria-label', hasDraft ? 'Borrador guardado automáticamente' : '');
    }

    function clearComposerDraft(ticket) {
        if (!ticket || ticket.id == null) return;
        try { window.localStorage.removeItem(draftStorageKey(ticket)); } catch (e) { /* opcional */ }
        updateDraftStatus($('#tkt-composer'), null, false);
    }

    function saveComposerDraft(ticket, body, mode) {
        if (!ticket || ticket.id == null) return;
        var clean = String(body || '').trim();
        try {
            if (!clean) {
                window.localStorage.removeItem(draftStorageKey(ticket));
                updateDraftStatus($('#tkt-composer'), null, false);
                return;
            }
            if (clean) {
                var draft = { body: String(body || ''), mode: mode === 'note' ? 'note' : 'reply', updatedAt: new Date().toISOString() };
                window.localStorage.setItem(draftStorageKey(ticket), JSON.stringify(draft));
                updateDraftStatus($('#tkt-composer'), draft, true);
            }
        } catch (e) { /* los borradores son una mejora opcional */ }
    }

    function scheduleComposerDraftSave() {
        var $body = $('#tkt-reply-body');
        var ticket = TKA.state.currentTicket;
        var $composer = $('#tkt-composer');
        if (!$body.length || !ticket || !$composer.length) return;
        clearTimeout(TKA.state.draftSaveTimer);
        TKA.state.draftSaveTimer = setTimeout(function () {
            saveComposerDraft(ticket, $body.val(), $composer.attr('data-mode'));
        }, 500);
    }

    function stopSlaClock() {
        if (TKA.state.slaTimer) clearInterval(TKA.state.slaTimer);
        TKA.state.slaTimer = null;
    }

    function formatSlaRemaining(ms) {
        var past = ms < 0;
        var total = Math.abs(Math.round(ms / 60000));
        var days = Math.floor(total / 1440);
        var hours = Math.floor((total % 1440) / 60);
        var minutes = total % 60;
        var text = days ? days + ' d ' + hours + ' h' : (hours ? hours + ' h ' + minutes + ' min' : minutes + ' min');
        return past ? 'hace ' + text : 'quedan ' + text;
    }

    function updateSlaClock(ticket) {
        if (!ticket || !ticket.sla_due_at) return;
        var due = new Date(ticket.sla_due_at).getTime();
        if (isNaN(due)) return;
        var diff = due - Date.now();
        var label = formatSlaRemaining(diff);
        var breach = diff < 0;
        var kind = breach ? 'breach' : (ticket.sla_kind === 'warn' ? 'warn' : 'ok');
        var $live = $('[data-sla-live]');
        $live.text(label)
            .toggleClass('breach', breach)
            .toggleClass('warn', kind === 'warn' && !breach)
            .attr('title', new Date(due).toLocaleString('es-ES'));
        $('[data-sla-banner]').attr('data-sla-state', kind).toggleClass('breach', breach).toggleClass('warn', kind === 'warn' && !breach);
        $('[data-sla-label]').text(kind === 'breach' ? 'SLA vencido' : (kind === 'warn' ? 'SLA en riesgo' : 'SLA dentro de plazo'));
        $('[data-sla-chip]').toggleClass('tkt-chip-ok', kind === 'ok').toggleClass('tkt-chip-warn', kind === 'warn').toggleClass('tkt-chip-danger', kind === 'breach');
        if (TKA.state.slaLastKind && TKA.state.slaLastKind !== kind && (kind === 'warn' || kind === 'breach')) {
            notifySlaTransition(ticket, kind);
        }
        if (TKA.state.slaLastKind === 'warn' && kind === 'breach') {
            queueTicketListRefresh('sla-breach', ticket, { freshCounts: true, preserveBulk: true });
        }
        TKA.state.slaLastKind = kind;
    }

    function notifySlaTransition(ticket, kind) {
        var key = String(ticket.id) + ':' + kind;
        if (TKA.state.slaNotified[key]) return;
        TKA.state.slaNotified[key] = true;
        var title = kind === 'breach' ? 'SLA vencido' : 'SLA en riesgo';
        var message = '#' + ticket.id + ' · ' + (ticket.subject || 'Ticket') + (kind === 'breach' ? ' requiere atención inmediata.' : ' se acerca al límite de respuesta.');
        if (window.toastr) {
            (kind === 'breach' ? toastr.error : toastr.warning)(message, title);
        }
        // No se solicita permiso automáticamente: solo se usa la notificación
        // del sistema si el agente ya la concedió en la configuración del
        // navegador. Así la pantalla no dispara un prompt inesperado.
        if (typeof window.Notification === 'function' && window.Notification.permission === 'granted') {
            try { new window.Notification(title, { body: message, tag: key }); } catch (e) { /* opcional */ }
        }
    }

    function startSlaClock(ticket) {
        stopSlaClock();
        if (!ticket || !ticket.sla_due_at) return;
        TKA.state.slaLastKind = ticket.sla_kind || null;
        updateSlaClock(ticket);
        TKA.state.slaTimer = setInterval(function () {
            if (!document.hidden && TKA.state.currentTicket === ticket) updateSlaClock(ticket);
        }, 30000);
    }

    // Elemento que abrió el modal; se recupera al cerrar para no dejar el
    // foco perdido en <body>. Solo hay un modal dinámico visible a la vez.
    var tktModalReturnFocus = null;

    function escapeHtml(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    /**
     * Identificador del cliente en cada plataforma externa, una fila por
     * plataforma y con el mismo aspecto que Email o Teléfono.
     *
     * Antes cada plataforma era una caja aparte repitiendo email y teléfono,
     * que ya salen arriba en la ficha: dos veces el mismo dato y un bloque que
     * rompía la lectura de la columna. Lo único que la plataforma aporta y no
     * está en ningún otro sitio es su identificador.
     *
     * Sale de datos locales: no cuesta ninguna llamada al ERP.
     */
    function platformsHtml(customer) {
        var list = (customer && customer.platforms) || [];

        return list.map(function (p) {
            return '<div class="tkt-kv"><span class="k">' + escapeHtml(p.label) + '</span>' +
                '<span class="v mono">' + escapeHtml(p.id) + '</span></div>';
        }).join('');
    }

    /**
     * Aviso "el cliente no está en gestión" para la tarjeta Cliente.
     *
     * customer.erp_missing llega null mientras la búsqueda automática en el ERP
     * no haya corrido: "todavía no se ha buscado" no es una noticia que darle
     * al agente. Solo cuando ya corrió y falló se pinta el aviso, con el
     * reintento que salta el enfriamiento.
     */
    function erpMissingHtml(customer) {
        var info = customer && customer.erp_missing;

        if (!info) { return ''; }

        return '<div class="tkt-erp-missing"' +
                (info.relink_url ? ' data-relink-url="' + escapeHtml(info.relink_url) + '"' : '') + '>' +
                '<span class="tkt-erp-missing-label"><i class="fa-regular fa-circle-question"></i> ' + escapeHtml(info.label) + '</span>' +
                (info.relink_url ? '<button type="button" class="tkt-btn tkt-btn-xs" data-tkt-erp-relink>Reintentar</button>' : '') +
            '</div>';
    }

    // Auto-crece el textarea de respuesta hasta max-height:120px (definido
    // inline junto al propio <textarea>) — antes se quedaba fijo en su
    // altura mínima (~2 líneas) al insertar una plantilla larga, sin ningún
    // indicio visual de que había más contenido debajo del scroll.
    function autoResizeTextarea(el) {
        if (!el) return;
        el.style.height = 'auto';
        el.style.height = Math.min(el.scrollHeight, 120) + 'px';
    }

    function initials(name) {
        var parts = String(name || '').trim().split(/\s+/).filter(Boolean);
        if (!parts.length) return '?';
        return (parts[0][0] + (parts[1] ? parts[1][0] : '')).toUpperCase();
    }

    // Compartido por la tarjeta "Portal de cliente" del panel lateral y el
    // modal 32 — antes duplicado byte a byte en los dos sitios.
    function tktCopyToClipboard(text, successMsg) {
        navigator.clipboard.writeText(text).then(function () {
            if (window.toastr) toastr.success(successMsg || 'Copiado'); else window.alert(successMsg || 'Copiado');
        }).catch(function () {
            window.prompt('Copia el texto:', text);
        });
    }

    // Claves de origen tal como están en la BD. Conviven varias formas para
    // lo mismo ('form'/'formulario'/'web_form') por datos de distintas épocas:
    // se mapean todas en vez de normalizar la columna, que obligaría a una
    // migración de datos por una etiqueta.
    var ORIGIN_LABELS = {
        email: 'Email', widget: 'Widget', wa: 'WhatsApp', whatsapp: 'WhatsApp',
        fb: 'Facebook', facebook: 'Facebook', ig: 'Instagram', instagram: 'Instagram',
        agent: 'Agente', manual: 'Manual', api: 'API', phone: 'Teléfono',
        form: 'Formulario', formulario: 'Formulario', web_form: 'Formulario',
        prestashop: 'PrestaShop', recurring: 'Recurrente', scheduled: 'Programado',
        import: 'Importado', chat: 'Chat', portal: 'Portal',
        // Ticket abierto automáticamente desde el flujo omnicanal
        // Conversación→Ticket (ChatFlow/Social) — valor real en BD que
        // faltaba en este mapa: salía el slug en crudo ("conversation") en
        // vez de una etiqueta, visto en el listado real al añadir el canal
        // a la fila (QA visual 14-sep-2026).
        conversation: 'Conversación',
    };

    // Icono por canal para el chip de origen de la cabecera del detalle
    // (rediseño canal/estado/prioridad — auditoría UI 14-sep-2026). Mismas
    // claves que ORIGIN_LABELS; 'fa-tag' de reserva para un origen sin mapear.
    var ORIGIN_ICON = {
        email: 'fa-regular fa-envelope', widget: 'fa-regular fa-comment-dots',
        wa: 'fa-brands fa-whatsapp', whatsapp: 'fa-brands fa-whatsapp',
        fb: 'fa-brands fa-facebook', facebook: 'fa-brands fa-facebook',
        ig: 'fa-brands fa-instagram', instagram: 'fa-brands fa-instagram',
        agent: 'fa-regular fa-user', manual: 'fa-regular fa-user',
        api: 'fa-solid fa-code', phone: 'fa-solid fa-phone',
        form: 'fa-regular fa-file-lines', formulario: 'fa-regular fa-file-lines', web_form: 'fa-regular fa-file-lines',
        prestashop: 'fa-solid fa-cart-shopping', recurring: 'fa-solid fa-rotate', scheduled: 'fa-regular fa-calendar',
        import: 'fa-solid fa-file-import', chat: 'fa-solid fa-comments', portal: 'fa-solid fa-door-open',
        conversation: 'fa-solid fa-comments',
    };

    var STATUS_LABEL_FALLBACK = {
        open: 'Abierto', progress: 'En curso', pending: 'En espera', resolved: 'Resuelto', closed: 'Cerrado',
    };

    var PRIORITY_LABELS = { low: 'Baja', normal: 'Normal', high: 'Alta', urgent: 'Urgente' };
    function priorityLabel(slug) {
        return PRIORITY_LABELS[slug] || slug;
    }

    // Mismo criterio de color que Ticket::PRIORITY_COLORS (backend) y el
    // mockup (chip de prioridad con color propio, no siempre gris) — bug de
    // diseño encontrado en QA visual: el chip de prioridad en la cabecera
    // del detalle usaba tkt-chip-muted fijo, así que "Alta"/"Urgente" no se
    // distinguían de "Baja"/"Normal" de un vistazo.
    // Meses abreviados en español, para la fecha de la fila del listado.
    var MONTH_SHORT = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];

    // En el mockup "Alta" y "Urgente" comparten exactamente el mismo chip
    // (fondo #18181b, texto #fff) — es el único elemento de la pantalla que
    // usa el negro sólido, y por eso destaca sobre los grises del resto.
    // "Baja"/"Normal" van en gris, sin peso visual.
    var PRIORITY_CHIP_CLASS = { low: 'tkt-chip-muted', normal: 'tkt-chip-muted', high: 'tkt-chip-danger', urgent: 'tkt-chip-danger' };
    function priorityChipClass(slug) {
        return PRIORITY_CHIP_CLASS[slug] || 'tkt-chip-muted';
    }

    // Clase de chip por estado real (open/pending/resolved/closed) — antes
    // el chip de los tickets relacionados era siempre gris sin importar si
    // seguían abiertos o ya se habían cerrado.
    // Los cinco chips de estado del mockup: Abierto #f5f6f8/#3f3f46 (info),
    // Pendiente y Cerrado #f5f6f8/#52525b (muted), Resuelto #eef5d9/#5b7a0d
    // (ok) y Sin asignar #e4e4e7/#18181b (warn).
    // 'new' (Nuevo) no hace falta como clave propia: t.status_slug ya llega
    // pre-agrupado desde el backend (Ticket::canonicalStatusSlug() colapsa
    // 'new' dentro de 'open', ver comentario ahí) — así que un ticket Nuevo
    // usa 'open'/tkt-chip-info igual que uno Abierto. La primera versión de
    // este fix añadía una clave 'new' aparte que nunca se llegaba a leer:
    // el bug real no era la clave que faltaba, era que --tkt-info-bg y
    // --tkt-subtle resultaron ser literalmente el mismo hex (ver esas
    // variables) — "Abierto" y "gris sin más" quedaban indistinguibles a
    // la vista pese a ser dos clases CSS distintas. Se corrige ahí, no aquí.
    var STATUS_CHIP_CLASS = { open: 'tkt-chip-info', progress: 'tkt-chip-info', pending: 'tkt-chip-muted', resolved: 'tkt-chip-ok', closed: 'tkt-chip-muted', unassigned: 'tkt-chip-warn' };
    function statusChipClass(slug) {
        return STATUS_CHIP_CLASS[slug] || 'tkt-chip-muted';
    }

    var FILE_ICON_EXT_MAP = {
        pdf: 'fa-file-pdf',
        doc: 'fa-file-word', docx: 'fa-file-word',
        xls: 'fa-file-excel', xlsx: 'fa-file-excel', csv: 'fa-file-excel',
        jpg: 'fa-file-image', jpeg: 'fa-file-image', png: 'fa-file-image', gif: 'fa-file-image', webp: 'fa-file-image',
        zip: 'fa-file-zipper', rar: 'fa-file-zipper',
    };
    function fileIconClass(filename) {
        var ext = String(filename || '').split('.').pop().toLowerCase();
        return 'fa-solid ' + (FILE_ICON_EXT_MAP[ext] || 'fa-file');
    }

    // ═══════════ Bootstrap ═══════════
    function initTicketsApp() {
        var $data = $('#tkt-data');
        if (!$data.length) return;

        TKA.state.tickets = safeJson($data.attr('data-tickets'), []);
        TKA.state.tabCounts = safeJson($data.attr('data-tab-counts'), {});
        TKA.state.currentUserId = $data.attr('data-user-id') ? parseInt($data.attr('data-user-id'), 10) : null;
        TKA.state.filter = $data.attr('data-initial-filter') || 'all';
        TKA.state.view = $data.attr('data-initial-view') || 'list';
        TKA.state.statuses = safeJson($data.attr('data-statuses'), []);
        TKA.state.categories = safeJson($data.attr('data-categories'), []);
        TKA.state.groups = safeJson($data.attr('data-groups'), []);
        TKA.state.agentsFull = safeJson($data.attr('data-agents-full'), []);
        TKA.state.cannedReplies = safeJson($data.attr('data-canned-replies'), []);
        TKA.state.ticketTemplates = safeJson($data.attr('data-ticket-templates'), []);
        // Settings → Helpdesk · Tickets → Funcionalidades (14-sep-2026):
        // {feature_x_enabled: bool} resuelto server-side (TicketFeatures::resolved()).
        // featureEnabled() abajo defaultea a true si una key no llega —
        // mismo criterio "visible mientras no exista fila en BD" que ya usa
        // helpdesk_ticket_feature_enabled() en PHP.
        TKA.state.features = safeJson($data.attr('data-features'), {});
        var configuredAttachmentMax = parseInt($data.attr('data-attachment-max-bytes'), 10);
        if (Number.isFinite(configuredAttachmentMax) && configuredAttachmentMax > 0) {
            TKT_ATTACHMENT_MAX_BYTES = configuredAttachmentMax;
        }
        var configuredAttachmentExtensions = safeJson($data.attr('data-attachment-extensions'), []);
        if (Array.isArray(configuredAttachmentExtensions) && configuredAttachmentExtensions.length) {
            TKT_ATTACHMENT_EXTENSIONS = configuredAttachmentExtensions.map(function (extension) {
                return String(extension).toLowerCase().replace(/^\./, '');
            });
        }
        TKA.state.closeReasons = safeJson($data.attr('data-close-reasons'), []);
        TKA.state.closeRootCauses = safeJson($data.attr('data-close-root-causes'), []);
        var selectedId = $data.attr('data-selected-id');
        TKA.state.selected = selectedId ? parseInt(selectedId, 10) : null;

        TKA.urls.bulk = $data.attr('data-bulk-url');
        TKA.urls.index = $data.attr('data-index-url');
        TKA.urls.emailsIndex = $data.attr('data-emails-index-url');
        TKA.urls.notesStoreTemplate = $data.attr('data-notes-store-url-template');
        TKA.urls.viewsStore = $data.attr('data-views-store-url');
        TKA.urls.contactsSyncTemplate = $data.attr('data-contacts-sync-url-template');
        TKA.urls.macrosList = $data.attr('data-macros-list-url');
        TKA.urls.emailsStore = $data.attr('data-emails-store-url');
        TKA.urls.typingTemplate = $data.attr('data-typing-url-template');
        TKA.urls.mailResendTemplate = $data.attr('data-mail-resend-url-template');
        TKA.urls.mailDataTemplate = $data.attr('data-mail-data-url-template');
        TKA.urls.exportTemplate = $data.attr('data-export-url-template');
        TKA.urls.exportEstimate = $data.attr('data-export-estimate-url') || null;
        TKA.urls.automationsIndex = $data.attr('data-automations-index-url');
        TKA.urls.slaPolicies = $data.attr('data-sla-policies-url');
        TKA.urls.ticketTemplatesIndex = $data.attr('data-ticket-templates-index-url');
        TKA.urls.ticketCreate = $data.attr('data-ticket-create-url');
        TKA.urls.settingsSnapshot = $data.attr('data-settings-snapshot-url');
        TKA.urls.emailChannels = $data.attr('data-email-channels-url');
        TKA.urls.recurring = $data.attr('data-recurring-url');
        TKA.urls.cannedUpdateTemplate = $data.attr('data-canned-update-url-template');
        TKA.urls.cannedDuplicateTemplate = $data.attr('data-canned-duplicate-url-template');
        TKA.state.senders = safeJson($data.attr('data-senders'), []);
        TKA.urls.contactsMergeSearchTemplate = $data.attr('data-contacts-merge-search-url-template');
        TKA.urls.contactsMergePreviewTemplate = $data.attr('data-contacts-merge-preview-url-template');
        TKA.urls.activityAudit = $data.attr('data-activity-audit-url') || null;
        TKA.urls.notificationsIndex = $data.attr('data-notifications-index-url') || null;
        TKA.urls.ticketSearch = $data.attr('data-ticket-search-url') || null;
        TKA.urls.notifPrefs = $data.attr('data-notif-prefs-url') || null;
        TKA.urls.notifPrefsUpdate = $data.attr('data-notif-prefs-update-url') || null;
        TKA.urls.notifTeamChannels = $data.attr('data-notif-team-channels-url') || null;
        TKA.urls.notifTeamChannelsTest = $data.attr('data-notif-team-channels-test-url') || null;
        TKA.urls.recurringOps = $data.attr('data-recurring-ops-url') || null;
        TKA.urls.recurringStore = $data.attr('data-recurring-store-url') || null;
        TKA.urls.recurringUpdateTemplate = $data.attr('data-recurring-update-url-template') || null;
        TKA.urls.recurringToggleTemplate = $data.attr('data-recurring-toggle-url-template') || null;
        TKA.urls.mailboxes = $data.attr('data-mailboxes-url') || null;
        TKA.urls.mailboxBehaviorTemplate = $data.attr('data-mailbox-behavior-url-template') || null;
        TKA.urls.mailboxTestTemplate = $data.attr('data-mailbox-test-url-template') || null;
        TKA.urls.workloadOverview = $data.attr('data-workload-overview-url') || null;
        TKA.urls.workloadAssignment = $data.attr('data-workload-assignment-url') || null;
        TKA.urls.presenceOverview = $data.attr('data-presence-overview-url') || null;
        TKA.urls.agentPresenceHeartbeat = $data.attr('data-agent-presence-heartbeat-url') || null;
        TKA.urls.agentPresenceAgents = $data.attr('data-agent-presence-agents-url') || null;
        TKA.urls.slaCalendar = $data.attr('data-sla-calendar-url') || null;
        TKA.urls.slaPauseStatus = $data.attr('data-sla-pause-status-url') || null;
        TKA.urls.automationsList = $data.attr('data-automations-list-url') || null;
        TKA.urls.automationsStore = $data.attr('data-automations-store-url') || null;
        TKA.urls.automationsPreview = $data.attr('data-automations-preview-url') || null;
        TKA.urls.automationsToggleTemplate = $data.attr('data-automations-toggle-url-template') || null;
        TKA.urls.contactsMergeExecuteTemplate = $data.attr('data-contacts-merge-execute-url-template');
        TKA.urls.ops = $data.attr('data-ops-url');
        TKA.urls.macrosIndex = $data.attr('data-macros-index-url');
        TKA.urls.ticketStore = $data.attr('data-ticket-store-url');
        TKA.urls.duplicatesPreview = $data.attr('data-duplicates-preview-url');
        TKA.state.customers = safeJson($data.attr('data-customers'), []);
        TKA.urls.queueRetry = $data.attr('data-queue-retry-url');
        TKA.urls.queueFlush = $data.attr('data-queue-flush-url');
        TKA.urls.workload = $data.attr('data-workload-url');
        TKA.urls.reputation = $data.attr('data-reputation-url');
        TKA.urls.aiAutoApply = $data.attr('data-ai-auto-apply-url');
        TKA.urls.workloadDistribute = $data.attr('data-workload-distribute-url');
        TKA.urls.ticketTemplates = safeJson($data.attr('data-ticket-url-templates'), {});

        // PERF-09: toListRow() ya no manda las ~27 URLs de acción por fila,
        // solo el id — las plantillas viajan una sola vez en
        // data-ticket-url-templates (Ticket::listRowUrlTemplates()). Se
        // expanden aquí, una vez, sustituyendo '__TICKET__' por el id real,
        // para que el resto del archivo siga leyendo t.url_update,
        // t.url_summary, etc. exactamente igual que antes.
        TKA.state.tickets = TKA.state.tickets.map(hydrateTicketUrls);
        // El payload del SSR ya viene filtrado por la pestaña activa
        // (applyQuickFilter en el controlador), igual que el de cada refetch:
        // volver a cribarlo en cliente sobraría. Ver visibleTickets().
        TKA.state.serverFiltered = true;

        initSplitLayout();
        initListDensity();
        initMobilePane();
        initNetworkResilience();
        bindEvents();
        renderTabs();

        fetchOpsQueueHint();
        bindStatusBar();

        // Selects estáticos ya presentes en el DOM al cargar la página: la
        // barra de filtros (Origen/Categoría/Agente/Prioridad/Etiquetas) y
        // los del modal "Más filtros" (éste último oculto pero ya en el DOM,
        // no inyectado por JS). Los generados dinámicamente (panel Gestión,
        // modales de bulk, etc.) se inicializan en su propio render.
        initSelect2();

        // El listado se hidrata de forma síncrona desde #tkt-data (sin AJAX
        // real todavía — ver cabecera del archivo), pero el esqueleto sigue
        // el mismo criterio que tendría un refetch real: visible mientras
        // se prepara la lista, oculto en cuanto está pintada.
        $('#tkt-skeleton').addClass('on');
        $('#tkt-list').hide();
        renderList();
        $('#tkt-skeleton').removeClass('on');
        $('#tkt-list').show();

        // El blade ya prepara data-initial-view desde ?view= (mismo
        // criterio que data-initial-filter arriba) pero antes nunca se leía
        // — el toggle Lista/Kanban se perdía siempre al recargar. Solo se
        // aplica si difiere de 'list' (el estado por defecto ya renderizado
        // por el blade), evitando un toggle visual innecesario en el caso
        // común.
        if (TKA.state.view === 'kanban') applyViewMode('kanban');

        if (TKA.state.selected) {
            var pre = TKA.state.tickets.find(function (t) { return String(t.id) === String(TKA.state.selected); });
            if (pre) selectTicket(pre);
        }
    }

    function safeJson(raw, fallback) {
        if (!raw) return fallback;
        try { return JSON.parse(raw); } catch (e) { return fallback; }
    }

    // Settings → Helpdesk · Tickets → Funcionalidades (14-sep-2026). Mismo
    // criterio que el backend (helpdesk_ticket_feature_enabled()): si la key
    // no llegó en TKA.state.features (grupo sin fila en BD todavía), se
    // considera activada — nunca oculta algo por una ausencia de datos.
    function featureEnabled(slug) {
        var key = 'feature_' + slug + '_enabled';
        var features = TKA.state.features || {};

        return !Object.prototype.hasOwnProperty.call(features, key) || Boolean(features[key]);
    }

    // Mensaje de error de una respuesta $.ajax fallida — patrón repetido en
    // ~20 sitios de core.js y modal-*.js como
    // apiErrorMessage(xhr, 'texto genérico'),
    // que ignora xhr.responseJSON.errors: ante un 422 de validación sin
    // 'message' de nivel superior, el agente veía el texto genérico en vez
    // de qué campo falló (14-sep-2026, auditoría de calidad JS). Todos los
    // ficheros de este directorio son <script> clásicos concatenados en el
    // mismo scope global, core.js siempre primero (ver manifest.json), así
    // que cualquier modal-*.js puede llamar a esto sin declarar nada propio.
    function apiErrorMessage(xhr, fallback) {
        var body = xhr && xhr.responseJSON;
        if (body && body.message) return body.message;

        if (body && body.errors) {
            var firstField = Object.keys(body.errors)[0];
            var firstMsg = firstField && body.errors[firstField] && body.errors[firstField][0];
            if (firstMsg) return firstMsg;
        }

        return fallback;
    }

    // Mismo bloque de "no se pudo cargar" repetido igual en varios modales
    // (cola de jobs, carga por agente…) — 14-sep-2026, auditoría de calidad JS.
    function renderModalError($backdrop, msg) {
        $backdrop.find('.tkt-modal-body').html('<div class="tkt-empty-box">' + escapeHtml(msg) + '</div>');
    }

    // Completa un ticket de TKA.state.tickets con sus URLs de acción a
    // partir de TKA.urls.ticketTemplates (ver initTicketsApp()) — las plantillas
    // con un segundo placeholder de sub-recurso (macro/nota/seguimiento/
    // conversación paralela) quedan con ese placeholder intacto, igual que
    // antes de PERF-09, listas para el .replace('__MACRO__', ...) etc. que
    // ya hace cada acción.
    function hydrateTicketUrls(t) {
        var templates = TKA.urls.ticketTemplates || {};
        for (var key in templates) {
            if (Object.prototype.hasOwnProperty.call(templates, key)) {
                t[key] = templates[key].replace('__TICKET__', t.id);
            }
        }
        return t;
    }

    // ═══════════ Filtro de tabs (mismo criterio que el toolbar htk-pill anterior) ═══════════
    function passesFilter(t) {
        var f = TKA.state.filter;
        if (f === 'all') return true;
        if (f === 'mine') return t.assignee && t.assignee.id === TKA.state.currentUserId;
        if (f === 'unassigned') return !t.assignee;
        if (f === 'urgent') return t.priority === 'urgent' || t.sla_kind === 'breach';
        if (f === 'sla_risk') return t.sla_kind === 'warn' || t.sla_kind === 'breach';
        // Vistas "Desde PrestaShop" / "Desde email" del mockup. El formulario
        // público de PrestaShop entra como 'formulario' y el alias 'web_form'
        // que también acepta Ticket::sourceSlug().
        if (f === 'from_presta') return t.source === 'formulario' || t.source === 'web_form';
        if (f === 'from_email') return t.source === 'email';
        if (f === 'pending') return t.status_slug === 'pending';
        if (f === 'resolved') return t.status_slug === 'resolved';
        if (f === 'open') return t.status_slug === 'open' || t.status_slug === 'progress';
        return t.status_slug === f;
    }

    // El criterio de las pestañas lo aplica AHORA el servidor
    // (TicketsCrudController::applyQuickFilter), tanto en el render inicial
    // como en cada refetch. Cribar además en cliente sería filtrar dos veces
    // sobre datos que ya vienen filtrados: la página trae 50 tickets que YA
    // cumplen la pestaña, y volver a pasarlos por passesFilter() solo podría
    // quitar filas de más si los dos criterios divergieran. passesFilter se
    // conserva como red de seguridad para el caso en que el refetch falle y
    // el estado local cambie de pestaña sin datos nuevos (ver refetchList).
    function visibleTickets() {
        return TKA.state.serverFiltered
            ? TKA.state.tickets
            : TKA.state.tickets.filter(passesFilter);
    }

    // ═══════════ Refetch del listado (AJAX contra el mismo endpoint) ═══════
    //
    // Antes cada cambio de filtro, pestaña, orden o página recargaba la
    // pantalla entera: ~350 KB de HTML y los tres paneles reconstruidos. El
    // endpoint del listado devuelve las mismas filas en JSON (~90 KB) cuando
    // se le pide con Accept: application/json, así que aquí solo se repintan
    // la lista, los badges y el pie.
    //
    // La URL se actualiza con pushState para que el enlace siga siendo
    // compartible y F5 devuelva exactamente lo mismo (el SSR aplica los
    // mismos filtros). Si la petición falla por lo que sea, se cae a la
    // navegación clásica a esa misma URL: nunca se queda la pantalla mostrando
    // datos que no corresponden a los filtros elegidos.
    function refetchList(params, opts) {
        opts = opts || {};
        params = params || {};
        var requestParams = $.extend({}, params);
        if (opts.freshCounts) requestParams.fresh_counts = 1;
        var qs = $.param(params);
        var requestQs = $.param(requestParams);
        var url = TKA.urls.index + (qs ? '?' + qs : '');
        var requestUrl = TKA.urls.index + (requestQs ? '?' + requestQs : '');
        var previousSignature = listSignature(TKA.state.tickets, TKA.state.tabCounts);
        var shouldRender = !opts.skipIfUnchanged;

        if (!opts.silent) {
            $('#tkt-skeleton').addClass('on');
            $('#tkt-list').hide();
        }

        $.ajax({ url: requestUrl, dataType: 'json', headers: { Accept: 'application/json' } })
            .done(function (res) {
                var nextTickets = (res.tickets || []).map(hydrateTicketUrls);
                var nextCounts = res.tab_counts || TKA.state.tabCounts;
                var nextSignature = listSignature(nextTickets, nextCounts);
                shouldRender = !opts.skipIfUnchanged || previousSignature !== nextSignature;

                TKA.state.tickets = nextTickets;
                TKA.state.tabCounts = nextCounts;
                TKA.state.serverFiltered = true;
                if (!opts.preserveBulk) TKA.state.bulk = {};
                else {
                    Object.keys(TKA.state.bulk).forEach(function (id) {
                        if (!TKA.state.tickets.some(function (t) { return String(t.id) === String(id); })) {
                            delete TKA.state.bulk[id];
                        }
                    });
                }
                TKA.state.newTicketCount = 0;
                TKA.state.reassignedCount = 0;
                $('#tkt-new-banner').remove();

                if (shouldRender) {
                    renderStatusCounts();
                    syncFilterControls(params);
                    renderTabs();
                    renderList();
                    renderFoot(res.pagination || {});
                    renderBulkBar();
                    // En modo Kanban la lista está oculta y las columnas se pintan
                    // aparte: sin esto, un cambio de pestaña desde el Kanban dejaba
                    // las tarjetas viejas en pantalla (renderList solo toca #tkt-list).
                    if (TKA.state.view === 'kanban') applyViewMode('kanban');
                }

                TKA.state.listSignature = nextSignature;

                if (window.history && window.history.pushState) {
                    if (opts.pushHistory !== false) window.history.pushState({ tktRefetch: true }, '', url);
                }
                if (opts.onDone) opts.onDone(res, { changed: shouldRender });
            })
            .fail(function () {
                // Antes recargaba la página entera sin avisar ante CUALQUIER
                // fallo de red (un blip transitorio mientras el agente
                // filtraba/paginaba perdía selección múltiple y filtros sin
                // aplicar, sin que nada explicara por qué la pantalla se
                // refrescó sola) — 14-sep-2026, auditoría de calidad JS.
                if (window.toastr) toastr.error('No se pudo actualizar el listado, recargando la página…');
                window.location = url;
            })
            .always(function () {
                if (!opts.silent) {
                    $('#tkt-skeleton').removeClass('on');
                    if (TKA.state.view !== 'kanban') $('#tkt-list').show();
                }
                if (opts.onAlways) opts.onAlways();
            });
    }

    // Firma estable de los datos que realmente afectan a una fila o a sus
    // contadores. Se usa para que el sondeo de respaldo no repinte la bandeja
    // cada 15 segundos si nadie ha cambiado nada.
    function listSignature(tickets, counts) {
        var rows = (tickets || []).map(function (t) {
            return [
                t.id, t.updated_at, t.status_id, t.status_slug, t.priority,
                t.category_id, t.group_id, t.assignee ? t.assignee.id : '',
                t.unread_count, t.snoozed_until, t.archived_at,
                t.last_message_at, t.last_message_snippet, t.sla_kind,
            ].join(':');
        }).join('|');
        var countPart = Object.keys(counts || {}).sort().map(function (key) {
            return key + ':' + counts[key];
        }).join('|');
        return rows + '||' + countPart;
    }

    function ticketSignature(t) {
        return t ? [
            t.id, t.updated_at, t.status_id, t.status_slug, t.priority,
            t.category_id, t.group_id, t.assignee ? t.assignee.id : '',
            t.unread_count, t.snoozed_until, t.archived_at,
        ].join(':') : '';
    }

    // Punto único para mantener la lista después de una mutación. Coalescea
    // respuestas locales y eventos Echo que llegan juntos, conserva el ticket
    // seleccionado y no modifica el historial del navegador durante una
    // actualización automática.
    function queueTicketListRefresh(reason, ticket, opts) {
        opts = opts || {};
        var state = TKA.state;
        state.listRefreshReason = reason || 'ticket-updated';
        state.listRefreshRefreshDetail = state.listRefreshRefreshDetail || !!opts.refreshDetail;
        state.listRefreshForceDetail = state.listRefreshForceDetail || !!opts.forceDetail;
        state.listRefreshFreshCounts = state.listRefreshFreshCounts || !!opts.freshCounts;
        state.listRefreshSilent = opts.silent !== false;
        state.listRefreshPreserveBulk = opts.preserveBulk !== false;
        state.listRefreshTicket = ticket || state.listRefreshTicket || null;

        if (state.listRefreshTimer) clearTimeout(state.listRefreshTimer);
        state.listRefreshTimer = setTimeout(function () {
            state.listRefreshTimer = null;
            if (state.listRefreshInFlight) {
                state.listRefreshQueued = true;
                return;
            }

            state.listRefreshInFlight = true;
            state.listRefreshQueued = false;

            var current = state.currentTicket;
            var selectedId = state.selected;
            var beforeCurrentSignature = ticketSignature(current);
            var refreshDetail = !!state.listRefreshRefreshDetail;
            var forceDetail = !!state.listRefreshForceDetail;
            var freshCounts = !!state.listRefreshFreshCounts;
            var refreshTicket = state.listRefreshTicket;

            state.listRefreshRefreshDetail = false;
            state.listRefreshForceDetail = false;
            state.listRefreshFreshCounts = false;
            state.listRefreshTicket = null;

            var page = new URLSearchParams(window.location.search).get('page') || 1;
            refetchList(currentListParams({ page: page }), {
                silent: state.listRefreshSilent,
                pushHistory: false,
                freshCounts: freshCounts,
                preserveBulk: state.listRefreshPreserveBulk,
                skipIfUnchanged: !forceDetail,
                onDone: function (res, meta) {
                    if (!refreshDetail || !selectedId || !current || String(current.id) !== String(selectedId)) return;

                    var fresh = state.tickets.find(function (t) { return String(t.id) === String(selectedId); });
                    var next = fresh || refreshTicket || current;
                    var afterCurrentSignature = ticketSignature(next);

                    if (fresh) state.currentTicket = fresh;
                    else if (refreshTicket && String(refreshTicket.id) === String(selectedId)) {
                        $.extend(current, refreshTicket);
                        state.currentTicket = current;
                    }

                    if (forceDetail || beforeCurrentSignature !== afterCurrentSignature || (meta && meta.changed && refreshTicket)) {
                        renderDetail(state.currentTicket);
                        renderSidePanel(state.currentTicket);
                    }
                },
                onAlways: function () {
                    state.listRefreshInFlight = false;
                    state.listRefreshSilent = true;
                    state.listRefreshPreserveBulk = true;
                    if (state.listRefreshQueued) {
                        state.listRefreshQueued = false;
                        queueTicketListRefresh('queued-update', state.currentTicket, { freshCounts: true });
                    }
                },
            });
        }, opts.delay == null ? 100 : opts.delay);
    }

    // Respaldo cuando no hay Echo/Reverb disponible. La petición es la misma
    // consulta JSON del listado, pero solo repinta si cambia la firma.
    function startTicketListRefresh() {
        if (TKA.state.listRefreshInterval) clearInterval(TKA.state.listRefreshInterval);
        TKA.state.listRefreshInterval = setInterval(function () {
            if (document.hidden || $('#tkt-modal-backdrop.on').length || !TKA.state.networkOnline || navigator.onLine === false) return;
            queueTicketListRefresh('poll', TKA.state.currentTicket, {
                refreshDetail: true,
                freshCounts: true,
                silent: true,
                preserveBulk: true,
                delay: 0,
            });
        }, 15000);
    }

    // Los dos formularios de filtro pintan el MISMO estado desde ángulos
    // distintos (los cinco chips rápidos de la barra y los dieciséis campos del
    // modal), así que tras un refetch hay que dejarlos a los dos diciendo lo
    // que de verdad está aplicado. Sin esto: filtras "Prioridad: Alta" con el
    // chip, abres "Más filtros" —que sigue mostrando la prioridad con la que
    // cargó la página— y al aplicar pisas el cambio con el valor viejo.
    //
    // change.select2 y no change a secas: es el evento con el que select2
    // repinta su widget, y no lo escucha el handler que dispara el submit, así
    // que sincronizar no provoca otro refetch.
    function syncFilterControls(params) {
        $('#tkt-filter-form, #htk-filters-form').find('select, textarea, input').each(function () {
            if (!this.name || this.type === 'submit' || this.type === 'button') return;

            var value = Object.prototype.hasOwnProperty.call(params, this.name) ? params[this.name] : '';

            if (this.type === 'checkbox') {
                this.checked = value !== '' && value !== '0';
                return;
            }
            if ($(this).val() === value) return;

            $(this).val(value);
            if ($(this).data('select2')) {
                $(this).trigger('change.select2');
                $(this).next('.select2-container').toggleClass('on', !!value);
            }
        });

        // El chip de fecha no es un <input>: su texto lo pinta el Blade y lo
        // repinta onApply, pero si el rango se ha quitado desde el otro
        // formulario hay que devolverlo a su estado de reposo.
        var hasRange = !!(params.created_from || params.created_to);
        $('#tkt-daterange-open').closest('.tkt-fdate').toggleClass('on', hasRange);
        if (!hasRange) {
            $('#tkt-daterange-open .tkt-fchip-value').text('Cualquier fecha');
            $('#htk-f-created-range .tkt-daterange-text').removeClass('on').text('Cualquier fecha');
        }
    }

    // Tickets nuevos mientras la pantalla está abierta.
    //
    // TicketCreated ya se emitía a 'helpdesk.tickets' desde el principio, pero
    // el canal no estaba autorizado (ver registerBroadcastChannels en el
    // ServiceProvider), así que el evento iba a un canal que nadie escuchaba y
    // la única forma de ver un ticket recién entrado era recargar a mano.
    //
    // No se mete la fila en la lista por su cuenta: puede no encajar en los
    // filtros activos, y meterla igualmente sería mentir sobre lo que la
    // pantalla dice estar mostrando. Se avisa y se deja que el agente decida —
    // el refetch respeta filtros, pestaña, orden y página.
    function listenForNewTickets() {
        if (typeof window.Echo === 'undefined') return;

        try {
            var channel = window.Echo.private('helpdesk.tickets');
            channel
                .listen('.ticket.created', function () {
                    queueTicketListRefresh('ticket-created', null, { freshCounts: true });
                })
                .listen('.message.added', function (e) {
                    // Un correo entrante también se emite en el canal global
                    // de la bandeja. El canal de presencia del ticket refresca
                    // el hilo; este listener refresca fila, contadores y orden
                    // sin esperar al polling de 15 s.
                    var incomingTicket = e && e.ticket_id ? findTicketById(e.ticket_id) : null;
                    queueTicketListRefresh('message-added', incomingTicket, {
                        freshCounts: true,
                        preserveBulk: true,
                        refreshDetail: !!(e && e.ticket_id && isSelectedTicket(e.ticket_id)),
                    });
                })
                .listen('.assigned', function (e) {
                    queueTicketListRefresh('ticket-assigned', e && e.ticket_id ? findTicketById(e.ticket_id) : null, { freshCounts: true });
                })
                .listen('.unassigned', function (e) {
                    queueTicketListRefresh('ticket-unassigned', e && e.ticket_id ? findTicketById(e.ticket_id) : null, { freshCounts: true });
                })
                .listen('.ticket.status.changed', function (e) {
                    var statusTicket = e && e.ticket_id ? findTicketById(e.ticket_id) : null;
                    var selectedTicket = e && e.ticket_id && isSelectedTicket(e.ticket_id) ? TKA.state.currentTicket : null;
                    var changedTicket = statusTicket || selectedTicket;
                    if (changedTicket && e && e.new_status) {
                        applyLocalTicketField(changedTicket, 'status_id', e.new_status.id);
                        changedTicket.status_slug = canonicalClientStatusSlug(e.new_status.slug);
                        changedTicket.status_name = e.new_status.name;
                    }
                    queueTicketListRefresh('ticket-status-changed', changedTicket, {
                        freshCounts: true,
                        refreshDetail: !!(e && e.ticket_id && isSelectedTicket(e.ticket_id)),
                        forceDetail: !!(e && e.ticket_id && isSelectedTicket(e.ticket_id)),
                    });
                })
                .listen('.ticket.updated', function (e) {
                    var id = e && e.ticket && e.ticket.id ? e.ticket.id : null;
                    var updatedTicket = id ? findTicketById(id) : null;
                    var currentUpdatedTicket = id && isSelectedTicket(id) ? TKA.state.currentTicket : null;
                    var changedUpdatedTicket = updatedTicket || currentUpdatedTicket;
                    if (currentUpdatedTicket && $('#tkt-reply-body').val().trim()) {
                        showEditConflict('Otro agente actualizó este ticket mientras redactabas. Revisa el hilo antes de enviar.');
                    }
                    if (changedUpdatedTicket && e.ticket) {
                        if (Object.prototype.hasOwnProperty.call(e.ticket, 'priority')) {
                            applyLocalTicketField(changedUpdatedTicket, 'priority', e.ticket.priority);
                        }
                        if (Object.prototype.hasOwnProperty.call(e.ticket, 'status_id')) {
                            applyLocalTicketField(changedUpdatedTicket, 'status_id', e.ticket.status_id);
                        }
                    }
                    queueTicketListRefresh('ticket-updated', changedUpdatedTicket, {
                        freshCounts: true,
                        refreshDetail: !!(id && isSelectedTicket(id)),
                    });
                });
        } catch (e) {
            // Sin Reverb levantado en este entorno la pantalla sigue siendo
            // usable: el sondeo periódico de la bandeja cubre los cambios.
        }
    }

    function findTicketById(id) {
        return TKA.state.tickets.find(function (t) { return String(t.id) === String(id); }) || null;
    }

    function isSelectedTicket(id) {
        return id != null && String(TKA.state.selected) === String(id);
    }

    // Pie de la lista ("1–50 de 66 tickets" + las dos flechas). Mismo markup
    // que pinta el Blade en la primera carga — ver el comentario junto a
    // .tkt-list-foot en index.blade.php.
    function renderFoot(p) {
        if (!p || typeof p.total === 'undefined') return;

        $('#tkt-foot-range').text((p.from || 0) + '–' + (p.to || 0) + ' de ' + p.total + ' tickets');
        $('#tkt-count').text(p.total + ' tickets');

        var prev = p.prev_url
            ? '<a href="' + escapeHtml(p.prev_url) + '" rel="prev" aria-label="Página anterior"><i class="fa-solid fa-chevron-left"></i></a>'
            : '<span class="off" aria-hidden="true"><i class="fa-solid fa-chevron-left"></i></span>';
        var next = p.next_url
            ? '<a href="' + escapeHtml(p.next_url) + '" rel="next" aria-label="Página siguiente"><i class="fa-solid fa-chevron-right"></i></a>'
            : '<span class="off" aria-hidden="true"><i class="fa-solid fa-chevron-right"></i></span>';

        $('#tkt-foot-nav').html(prev + next);
    }

    // Estado actual de la pantalla tal y como viaja en la URL: los campos de
    // los dos formularios de filtro (barra y modal, que ya arrastran como
    // hidden lo que no controlan) más los overrides de quien llama. Los
    // vacíos se descartan para no dejar "source=&tag=&category=" en el enlace.
    function currentListParams(overrides) {
        var params = {};
        new URLSearchParams(window.location.search).forEach(function (v, k) {
            if (v !== '') params[k] = v;
        });

        if (overrides && overrides.$form) {
            overrides.$form.find('select, textarea, input').each(function () {
                // Los hidden de arrastre (quick_filter, sort, ticket…) los pintó
                // el servidor con el estado que había AL CARGAR la página. Tras
                // un refetch ese estado vive en la URL, no en ellos, así que
                // leerlos aquí resucitaría el valor viejo: cambias de pestaña y
                // luego de chip, y volverías a la pestaña anterior. Se ignoran:
                // su única función es que el formulario siga funcionando si el
                // refetch falla y se envía como GET normal.
                // created_from/created_to son la excepción: ahí el hidden no es
                // arrastre, es el valor real que escribe el daterangepicker.
                var isCarryHidden = this.type === 'hidden'
                    && this.name !== 'created_from' && this.name !== 'created_to';
                if (!this.name || isCarryHidden) return;
                if (this.type === 'checkbox') {
                    if (this.checked) params[this.name] = this.value || '1';
                    else delete params[this.name];
                    return;
                }
                var v = $(this).val();
                if (v !== '' && v !== null) params[this.name] = v; else delete params[this.name];
            });
        }

        for (var k in overrides || {}) {
            if (k === '$form') continue;
            if (overrides[k] === null || overrides[k] === '') delete params[k];
            else params[k] = overrides[k];
        }

        // Un cambio de filtro siempre vuelve a la primera página: si estabas
        // en la 3 de "Todos" y filtras por "Resueltos", la 3 puede no existir.
        if (!(overrides && overrides.page)) delete params.page;

        return params;
    }

    // ═══════════ Render: tabs de estado + chips de vistas ═══════════
    // Ambas barras (tabs de estado y chips "Vistas") comparten el mismo
    // filtro activo (TKA.state.filter) — igual que el mockup, donde
    // savedView() también reemplaza el filtro de estado en vez de
    // combinarse con él.
    function renderTabs() {
        $('.tkt-state-tab[data-filter], .tkt-view-pill[data-filter]').each(function () {
            var $t = $(this);
            $t.toggleClass('on', $t.data('filter') === TKA.state.filter);
        });
        // Miga de pan dinámica (comparado contra el mockup: "Helpdesk › Tickets
        // › {tab activo}") — toma el label del propio tab de estado activo
        // (clonado sin su contador .c) para no duplicar el mapeo de nombres en
        // un sitio aparte. Si el filtro activo es un chip de "Vistas"
        // (urgent/mine/sla_risk) en vez de un tab de estado, cae a "Todos"
        // — esos chips tampoco vivían en la miga del mockup.
        var $activeTab = $('.tkt-state-tab[data-filter="' + TKA.state.filter + '"]');
        var crumbLabel = 'Todos';
        if ($activeTab.length) {
            var $clone = $activeTab.clone();
            $clone.find('.c').remove();
            crumbLabel = $clone.text().trim();
        }
        $('#tkt-crumb-tab').text(crumbLabel);
    }

    // Recalcula los contadores de tabs/vistas a partir de TKA.state.tickets
    // (mismo criterio que passesFilter) — necesario tras el Kanban, cuyo
    // "soltar" cambia el estado sin recargar la página (a diferencia de
    // bulk/Gestión, que sí recargan y traen los conteos frescos del
    // servidor). Sin esto, "Pendientes/Resueltos"/"SLA en riesgo" quedaban
    // desactualizados después de arrastrar una tarjeta — bug real
    // encontrado al probar el drag&drop.
    function recomputeTabCounts() {
        // 'all' se preserva del total real que ya trajo el servidor
        // (TKA.state.tabCounts.all, cargado en initTicketsApp()) en vez de
        // recalcularlo como TKA.state.tickets.length: el array del cliente
        // solo contiene la página actual, así que "recalcularlo" aquí hacía
        // que el tab "Todos" bajara (p.ej. de 12 a 11) tras la primera
        // interacción de Kanban sin que el número de tickets reales hubiera
        // cambiado — discrepancia real encontrada en QA.
        var c = { open: 0, urgent: 0, mine: 0, unassigned: 0, pending: 0, resolved: 0, closed: 0, sla_risk: 0, all: TKA.state.tabCounts.all };
        TKA.state.tickets.forEach(function (t) {
            if (t.status_slug === 'open' || t.status_slug === 'progress') c.open++;
            if (t.priority === 'urgent' || t.sla_kind === 'breach') c.urgent++;
            if (t.assignee && t.assignee.id === TKA.state.currentUserId) c.mine++;
            if (!t.assignee) c.unassigned++;
            if (t.status_slug === 'pending') c.pending++;
            if (t.status_slug === 'resolved') c.resolved++;
            if (t.status_slug === 'closed') c.closed++;
            if (t.sla_kind === 'warn' || t.sla_kind === 'breach') c.sla_risk++;
        });
        TKA.state.tabCounts = c;
        Object.keys(c).forEach(function (k) {
            $('.tkt-state-tab[data-filter="' + k + '"] .c, .tkt-view-pill[data-filter="' + k + '"] .mono').text(c[k]);
        });
        $('.tkt-queue-hint').text('SLA en riesgo: ' + c.sla_risk);
        renderStatusCounts();
    }

    // ═══════════ Render: lista ═══════════
    function slaClass(kind) {
        if (kind === 'breach') return 'tkt-sla-breach';
        if (kind === 'warn') return 'tkt-sla-warn';
        return 'tkt-sla-ok';
    }

    function renderRow(t) {
        var isActive = String(TKA.state.selected) === String(t.id);
        var checked = TKA.state.bulk[t.id] ? 'checked' : '';
        var statusRowClass = 's-' + (t.status_slug || 'open');
        var statusLabel = t.status_name || STATUS_LABEL_FALLBACK[t.status_slug] || t.status_slug;
        // "01 sep 10:42": el mockup pone día y mes junto a la hora, no solo la
        // hora — con 30 tickets en pantalla, un "10:42" a secas no dice si es
        // de hoy o de la semana pasada.
        // toLocaleDateString('es-MX', {month:'short'}) devuelve "02-sep" (con
        // guion) en Chrome; el mockup escribe "01 sep 10:42". Se compone a
        // mano para no depender del separador que elija cada navegador.
        var timeLabel = '—';
        if (t.updated_at) {
            var d = new Date(t.updated_at);
            var pad = function (n) { return n < 10 ? '0' + n : '' + n; };
            timeLabel = pad(d.getDate()) + ' ' + MONTH_SHORT[d.getMonth()] +
                ' ' + pad(d.getHours()) + ':' + pad(d.getMinutes());
        }

        // s-unassigned se añade como clase EXTRA (nunca sustituye la del
        // estado real) — sin regla CSS propia hoy.
        // La raya de color por urgencia (sla-breach) se probó y se quitó:
        // con casi toda la bandeja vencida pintaba la lista entera de negro
        // (QA visual 14-sep-2026, ver comentario junto a .tkt-ticket-row en
        // tickets-app.css). En su lugar, sombra en la fila cuando el ticket
        // no se ha abierto todavía (mismo dato que ya usa el cuadrado negro
        // de no-leído, unread_count > 0).
        // Fila rediseñada (auditoría UI 14-sep-2026), en una sola columna:
        //   1) código de ticket · fecha (línea propia, arriba del todo)
        //   2) asunto (línea propia, a todo el ancho)
        //   3) cliente · empresa
        //   4) resumen del último mensaje
        //   5) etiquetas: estado, canal, prioridad, categoría, adjuntos, SLA
        //   6) avatar del agente asignado (o "sin asignar") + no-leído
        var customerLine = t.customer
            ? (t.customer.company ? t.customer.name + ' · ' + t.customer.company : t.customer.name)
            : 'Sin cliente';
        var slaChipCls = t.sla_kind === 'breach' ? 'sla-breach' : (t.sla_kind === 'warn' ? 'sla-warn' : 'sla-ok');
        var $row = $(
            '<div class="tkt-ticket-row ' + statusRowClass + (t.assignee ? '' : ' s-unassigned') + (t.unread_count > 0 ? ' unread' : '') + (isActive ? ' on' : '') + '" data-id="' + t.id + '" tabindex="0" role="listitem" aria-label="Abrir ticket ' + escapeHtml(t.ticket_number + ': ' + (t.subject || 'sin asunto')) + '">' +
            '<label class="tkt-ticket-checkzone">' +
                '<input type="checkbox" class="tkt-ticket-check" data-check="' + t.id + '" aria-label="Seleccionar ticket ' + escapeHtml(t.ticket_number) + '" ' + checked + '>' +
            '</label>' +
            '<div class="tkt-ticket-main">' +
                '<div class="tkt-ticket-topline">' +
                    '<span class="tkt-ticket-id mono">' + escapeHtml(t.ticket_number) + '</span>' +
                    '<span class="tkt-ticket-time mono">' + timeLabel + '</span>' +
                '</div>' +
                '<div class="tkt-ticket-subject">' + escapeHtml(t.subject || '(sin asunto)') + '</div>' +
                '<div class="tkt-ticket-customer">' + escapeHtml(customerLine) + '</div>' +
                (t.last_message_snippet ? '<div class="tkt-ticket-last">' + escapeHtml(t.last_message_snippet) + '</div>' : '') +
                '<div class="tkt-ticket-metarow">' +
                    '<span class="tkt-rchip">' + escapeHtml(statusLabel) + '</span>' +
                    // Canal de origen: iba ausente de esta fila (solo se
                    // pintaba en Kanban/detalle) — mismo mapa ORIGIN_LABELS
                    // de siempre, sin icono, en mono para distinguirse de
                    // las demás etiquetas sin depender de un glifo.
                    '<span class="tkt-rchip origin">' + escapeHtml(ORIGIN_LABELS[t.source] || t.source || '—') + '</span>' +
                    (t.priority && t.priority !== 'normal' && t.priority !== 'low'
                        ? '<span class="tkt-rchip strong">' + escapeHtml(priorityLabel(t.priority)) + '</span>'
                        : '') +
                    // Distinto del SLA de resolución (más abajo): first_response_at
                    // dice si YA se contestó al cliente, sin importar si el
                    // ticket sigue abierto. Es la señal más urgente de la
                    // fila —nadie ha tocado este ticket todavía— así que
                    // comparte el negro sólido de "Urgente", no un tono
                    // propio. No se pinta en cerrados: ahí ya no hay nada
                    // pendiente de contestar (mismo criterio que el aviso de
                    // autoasignación, ver renderSelfAssignBanner()).
                    (!t.first_response_at && t.status_slug !== 'closed'
                        ? '<span class="tkt-rchip strong" title="Nadie ha respondido a este ticket todavía">Sin responder</span>'
                        : '') +
                    (t.category_name ? '<span class="tkt-rchip cat" title="' + escapeHtml(t.category_name) + '">' + escapeHtml(t.category_name) + '</span>' : '') +
                    // Equipo/cola: mismo caso que el canal — ya viaja en el
                    // payload (group_name, con la relación group precargada
                    // en la misma consulta del listado) pero no se pintaba
                    // en ningún sitio de esta fila, así que en bandejas
                    // compartidas por varios equipos no había forma de
                    // triar de un vistazo sin abrir el ticket.
                    (t.group_name ? '<span class="tkt-rchip group" title="' + escapeHtml(t.group_name) + '">' + escapeHtml(t.group_name) + '</span>' : '') +
                    // Antes un clip de icono junto al asunto — sin conteo real
                    // de adjuntos en el backend (has_attachments es booleano),
                    // así que la etiqueta dice "Adjunto" a secas, no un número.
                    (t.has_attachments ? '<span class="tkt-rchip attach" title="Con adjuntos">Adjunto</span>' : '') +
                    // Correo saliente rebotado/fallido — dato que ya viaja en
                    // el payload (last_mail_status, con lastOutboundMail
                    // precargado también en la consulta del listado, no solo
                    // en el detalle) pero hasta ahora no se pintaba en
                    // ningún sitio de esta fila: el agente no se enteraba de
                    // que su respuesta nunca llegó sin abrir el ticket y la
                    // pestaña Correo. Mismo criterio de texto que el chip de
                    // entrega de la cabecera del detalle (deliveryChip en
                    // renderDetail()): "Email rebotado" para failed y
                    // bounced por igual, sin distinguir matices ahí tampoco.
                    (t.last_mail_status === 'failed' || t.last_mail_status === 'bounced'
                        ? '<span class="tkt-rchip strong" title="El último correo saliente no llegó al cliente">Correo rebotado</span>'
                        : '') +
                    // slaRowText() devuelve el guion largo cuando el ticket no
                    // tiene plazo: es un valor "vacío" con forma de texto, así
                    // que un truthy a secas pintaba una etiqueta vacía en casi
                    // todas las filas. Sin plazo, no se pinta nada.
                    // Prefijo "Resolución:" a propósito (bug de confusión
                    // real, QA 14-sep-2026): slaRowText() siempre mide el
                    // plazo de RESOLUCIÓN (sla_resolution_due_at), nunca el
                    // de primera respuesta — un agente que ya respondió veía
                    // "vencido" a secas y asumía que el sistema no se había
                    // enterado de su respuesta. Contestar no para ese reloj;
                    // solo cambiar el ticket a Resuelto/Cerrado lo hace.
                    (t.sla_text && t.sla_text !== '—' ? '<span class="tkt-rchip ' + slaChipCls + '" title="Plazo de resolución — responder no lo detiene, solo resolver o cerrar el ticket">Resolución: ' + escapeHtml(t.sla_text) + '</span>' : '') +
                    '<span class="tkt-ticket-metaend">' +
                        // t.viewers lo rellena pollListPresence() (sondeo
                        // aparte, no viaja en toListRow() por ser dato
                        // efímero) — quién está viendo ESTE ticket ahora
                        // mismo, que puede no ser el asignado. Punto verde
                        // sobre el mismo avatar en vez de uno aparte: la
                        // posición ya dice "esto es sobre esta persona/
                        // ticket", igual que en la cabecera del detalle.
                        (function () {
                            var viewers = t.viewers || [];
                            var dot = viewers.length
                                ? '<span class="tkt-presence-dot" title="' + escapeHtml(viewers.map(function (v) { return v.name; }).join(', ') + (viewers.length === 1 ? ' está viendo este ticket ahora' : ' están viendo este ticket ahora')) + '"></span>'
                                : '';
                            return t.assignee
                                ? '<span class="tkt-avatar" title="' + escapeHtml(t.assignee.name) + '">' + escapeHtml(initials(t.assignee.name)) + dot + '</span>'
                                : '<span class="tkt-avatar unassigned" title="Sin asignar">–' + dot + '</span>';
                        })() +
                        // Sin contador de mensajes: el mockup deja este hueco
                        // vacío y el número repetido en cada fila era ruido.
                        // Se conserva solo el cuadrado de "sin leer" — forma
                        // distinta a propósito del punto verde de presencia
                        // que usa el detalle, para no confundirse con él.
                        (t.unread_count > 0 ? '<span class="tkt-ticket-unread" title="Con mensajes sin leer"></span>' : '') +
                    '</span>' +
                '</div>' +
            '</div>' +
            '</div>'
        );

        $row.find('[data-check]').on('click', function (ev) { ev.stopPropagation(); });
        $row.find('[data-check]').on('change', function () {
            var id = parseInt($(this).data('check'), 10);
            if (this.checked) TKA.state.bulk[id] = true; else delete TKA.state.bulk[id];
            renderBulkBar();
        });
        $row.on('click', function () {
            var t2 = TKA.state.tickets.find(function (x) { return String(x.id) === String(t.id); });
            if (t2) selectTicket(t2);
        });
        $row.on('keydown', function (ev) {
            if (ev.key !== 'Enter' && ev.key !== ' ') return;
            if ($(ev.target).is('input,button,a,select,textarea')) return;
            ev.preventDefault();
            $(this).trigger('click');
        });

        return $row;
    }

    function renderList() {
        var rows = visibleTickets();
        var $list = $('#tkt-list').empty();

        if (!rows.length) {
            // Sin icono ni título: en la columna estrecha de la lista el
            // mockup solo pone las dos frases, y la segunda dice qué hacer.
            $list.append(
                '<div class="tkt-list-empty">' +
                'Ningún ticket coincide con estos filtros.<br>' +
                '<span>Prueba a quitar alguno o amplía el rango de fechas.</span>' +
                '</div>'
            );
        } else {
            // Montar las filas fuera del DOM evita un reflow por ticket cuando
            // el agente cambia de pestaña, vuelve de una reconexión o recibe
            // una página grande del servidor.
            var fragment = document.createDocumentFragment();
            rows.forEach(function (t) { fragment.appendChild(renderRow(t)[0]); });
            $list[0].appendChild(fragment);
        }

        // "6 tickets" a secas, como el mockup — el "en esta página" sobra
        // ahora que el pie de la lista dice el rango exacto.
        $('#tkt-count').text(rows.length + (rows.length === 1 ? ' ticket' : ' tickets'));

        // El pie de paginación ('1–N de N') es un partial Blade estático,
        // renderizado una sola vez por el servidor con el total SIN filtrar
        // — los tabs/chips de estado son 100% client-side, así que quedaba
        // congelado y podía contradecir directamente al empty-state (p.ej.
        // "1–11 de 11" a la vez que "No hay tickets" en el mismo pantallazo,
        // verificado en vivo con el tab "Resueltos"). Se actualiza aquí con
        // el conteo real ya filtrado, mismo criterio que #tkt-count arriba.
        var $footCount = $('.tkt-list-foot > span').first();
        if ($footCount.length) {
            $footCount.text((rows.length ? '1–' + rows.length : '0–0') + ' de ' + rows.length + ' tickets');
        }
    }

    // ═══════════ Kanban (Fase D) ═══════════
    // El Kanban ignora deliberadamente el filtro de tabs activo (a
    // diferencia de la lista): su propósito es visualizar el flujo de
    // trabajo completo entre estados, así que agrupa TODOS los tickets
    // cargados en la página, no solo los del tab seleccionado.
    var KANBAN_COLS = [
        { key: 'unassigned', label: 'Sin asignar', dot: 'unassigned' },
        { key: 'open', label: 'Abiertos', dot: 'open' },
        { key: 'pending', label: 'Pendientes', dot: 'pending' },
        { key: 'resolved', label: 'Resueltos', dot: 'resolved' },
        { key: 'closed', label: 'Cerrados', dot: 'closed' },
    ];

    function bucketFor(t) {
        if (!t.assignee) return 'unassigned';
        if (t.status_slug === 'open' || t.status_slug === 'progress') return 'open';
        if (t.status_slug === 'pending') return 'pending';
        if (t.status_slug === 'resolved') return 'resolved';
        if (t.status_slug === 'closed') return 'closed';
        return 'open';
    }

    function renderKanbanCard(t) {
        // Mismo criterio de chip de prioridad que renderRow() en la vista
        // Lista (solo high/urgent, con el mismo color) — antes la tarjeta
        // Kanban no daba ninguna señal de prioridad, reduciendo su utilidad
        // de triage a simple vista.
        var prioClass = t.priority === 'urgent' ? 'tkt-prio-urgent' : (t.priority === 'high' ? 'tkt-prio-high' : '');
        var prioBadge = (t.priority === 'high' || t.priority === 'urgent')
            ? '<span class="tkt-prio ' + prioClass + '"><span class="tkt-prio-dot"></span>' + escapeHtml(priorityLabel(t.priority)) + '</span>'
            : '';
        var $card = $(
            '<div class="tkt-kcard" draggable="true" data-id="' + t.id + '">' +
                '<div class="tkt-kcard-title">' + escapeHtml(t.subject || '(sin asunto)') + '</div>' +
                '<div class="tkt-kcard-sub tkt-trunc">' + escapeHtml(t.customer ? t.customer.name : 'Sin cliente') + '</div>' +
                '<div class="tkt-kcard-meta">' +
                    '<span class="tkt-chip-mono">' + escapeHtml(t.ticket_number) + '</span>' +
                    '<span class="tkt-kcard-origin">' + escapeHtml(ORIGIN_LABELS[t.source] || t.source || '') + '</span>' +
                    prioBadge +
                    // Mismo criterio que la fila del listado: sin plazo, sin chip.
                    (t.sla_text && t.sla_text !== '—'
                        ? '<span class="tkt-sla tkt-kcard-sla ' + slaClass(t.sla_kind) + '"><span class="tkt-sla-dot"></span>' + escapeHtml(t.sla_text) + '</span>'
                        : '') +
                '</div>' +
            '</div>'
        );
        $card.on('click', function () { selectTicket(t); });
        $card.on('dragstart', function (ev) {
            TKA.state.dragged = t;
            $card.addClass('dragging');
            if (ev.originalEvent.dataTransfer) ev.originalEvent.dataTransfer.effectAllowed = 'move';
        });
        $card.on('dragend', function () { $card.removeClass('dragging'); });
        return $card;
    }

    function renderKanban() {
        var $board = $('#tkt-kanban').empty();
        var buckets = {};
        KANBAN_COLS.forEach(function (c) { buckets[c.key] = []; });
        TKA.state.tickets.forEach(function (t) { buckets[bucketFor(t)].push(t); });

        KANBAN_COLS.forEach(function (col) {
            var items = buckets[col.key];
            var $drop = $('<div class="tkt-kcol-drop" data-bucket="' + col.key + '"></div>');
            items.forEach(function (t) { $drop.append(renderKanbanCard(t)); });

            $drop.on('dragover', function (ev) { ev.preventDefault(); $drop.addClass('over'); });
            $drop.on('dragleave', function () { $drop.removeClass('over'); });
            $drop.on('drop', function (ev) {
                ev.preventDefault();
                $drop.removeClass('over');
                if (TKA.state.dragged) moveTicketToBucket(TKA.state.dragged, col.key);
            });

            var $col = $(
                '<div class="tkt-kcol">' +
                    '<div class="tkt-kcol-head">' +
                        '<span class="tkt-kcol-dot ' + col.dot + '"></span>' +
                        '<span>' + col.label + '</span>' +
                        '<span class="tkt-kcol-count">' + items.length + '</span>' +
                        '<button type="button" class="tkt-kcol-add" data-kcol-add="' + col.key + '" title="Crear un ticket en ' + col.label + '" aria-label="Crear un ticket en ' + col.label + '"><i class="fa-solid fa-plus"></i></button>' +
                    '</div>' +
                '</div>'
            );
            $col.find('[data-kcol-add]').on('click', function () {
                window.location = TKA.urls.ticketCreate + '?status=' + encodeURIComponent($(this).data('kcol-add'));
            });
            $col.append($drop);
            $board.append($col);
        });
    }

    function moveTicketToBucket(t, bucket) {
        var sourceBucket = bucketFor(t);
        if (sourceBucket === bucket) return;

        // bucketFor() prioriza "sin asignar" sobre el estado real (a
        // propósito, ver el comentario de la función) — así que soltar un
        // ticket sin agente sobre una columna de estado real mutaba
        // status_id en el backend en silencio sin que la tarjeta se moviera
        // NUNCA visualmente (sigue sin agente, vuelve a "Sin asignar" en el
        // siguiente renderKanban()). Se bloquea aquí, ANTES de disparar el
        // PUT, con feedback explícito — el caso inverso (soltar cualquier
        // ticket sobre "Sin asignar") sigue funcionando igual que antes.
        if (sourceBucket === 'unassigned' && bucket !== 'unassigned') {
            if (window.toastr) toastr.warning('Asigna un agente antes de poder cambiar el estado desde Kanban.');
            else window.alert('Asigna un agente antes de poder cambiar el estado desde Kanban.');
            renderKanban();
            return;
        }

        if (bucket === 'unassigned') {
            patchTicketSilent(t, 'assignee_id', '', function () { t.assignee = null; renderKanban(); });
            return;
        }

        var targetStatus = (TKA.state.statuses || []).find(function (s) { return s.slug === bucket; });
        if (!targetStatus) {
            if (window.toastr) toastr.error('No existe un estado "' + bucket + '" configurado.');
            renderKanban();
            return;
        }
        patchTicketSilent(t, 'status_id', targetStatus.id, function () {
            t.status_id = targetStatus.id;
            t.status_slug = bucket;
            t.status_name = targetStatus.name;
            renderKanban();
        });
    }

    // Variante de patchTicket() sin recargar la página entera — el Kanban
    // necesita mover la tarjeta al soltar sin perder el modo/posición de
    // scroll, a diferencia de los selects de la Gestión, que sí recargan.
    function patchTicketSilent(t, field, value, onSuccess) {
        var data = { _method: 'PUT' };
        data[field] = value;
        $.ajax({
            url: t.url_update,
            method: 'POST',
            data: data,
            headers: { Accept: 'application/json' },
            success: function () {
                if (window.toastr) toastr.success('Ticket actualizado');
                onSuccess();
                queueTicketListRefresh('ticket-field-updated', t, {
                    freshCounts: true,
                    refreshDetail: true,
                    forceDetail: true,
                });
                recomputeTabCounts();
                renderTabs();
                renderList();
            },
            error: function (xhr) {
                var msg = apiErrorMessage(xhr, 'No se pudo mover el ticket');
                if (window.toastr) toastr.error(msg); else window.alert(msg);
                renderKanban();
            },
        });
    }

    // ═══════════ Vistas guardadas: guardado rápido (Fase D) ═══════════
    // Modal propio en vez de window.prompt() — bug de diseño real: era el
    // único punto del app bar principal que rompía el estilo con el diálogo
    // nativo del navegador, sin marca ni control sobre texto/validación.
    // `source` opcional: un objeto {clave: valor} con los filtros a guardar.
    // Sin él se leen de la URL (el caso del botón "+ guardar vista" de la
    // barra de vistas); el modal de filtros pasa los suyos porque los campos
    // recién tocados todavía no están en la querystring y guardaría los
    // anteriores.
    function saveCurrentView(source) {
        var $backdrop = openModal(modalShell({
            icon: 'fa-regular fa-bookmark',
            title: 'Guardar vista',
            width: 'sm',
            body:
                '<div class="tkt-field">' +
                    '<label class="tkt-label">Nombre<span class="req">*</span><span class="hint">guarda el origen/categoría/agente/prioridad activos</span></label>' +
                    '<input type="text" class="tkt-input" id="tkt-save-view-name" maxlength="255" placeholder="Ej: Urgentes de facturación">' +
                '</div>' +
                '<label class="tkt-check">' +
                    '<input type="checkbox" id="tkt-save-view-shared">' +
                    '<span>Compartir con el equipo<span class="hint">el resto de agentes la verá en su barra de vistas</span></span>' +
                '</label>',
            foot: '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-save-view-confirm">Guardar</button>' +
                  '<button type="button" class="tkt-btn" data-modal-close>Cancelar</button>',
        }));

        $backdrop.find('#tkt-save-view-name').trigger('focus');
        $backdrop.on('keydown', '#tkt-save-view-name', function (ev) {
            if (ev.key === 'Enter') { ev.preventDefault(); $backdrop.find('#tkt-save-view-confirm').trigger('click'); }
        });

        $backdrop.on('click', '#tkt-save-view-confirm', function () {
            var name = ($backdrop.find('#tkt-save-view-name').val() || '').trim();
            if (!name) { if (window.toastr) toastr.error('Escribe un nombre para la vista'); return; }
            var shared = $backdrop.find('#tkt-save-view-shared').is(':checked');

            var params = source
                ? { get: function (k) { return source[k] || null; } }
                : new URLSearchParams(window.location.search);

            // Solo las claves que TicketView::applyFilters() sabe traducir:
            // guardar cualquier otra la dejaría en la BD sin efecto y la
            // vista mentiría sobre lo que filtra.
            var filters = {};
            if (params.get('source')) filters.source = params.get('source');
            if (params.get('category')) filters.category_id = params.get('category');
            if (params.get('status')) filters.status_id = params.get('status');
            if (params.get('group')) filters.group_id = params.get('group');
            if (params.get('assignee') === 'me') filters.mine = true;
            else if (params.get('assignee') === 'unassigned') filters.unassigned = true;
            else if (params.get('assignee')) filters.assignee_id = params.get('assignee');
            if (params.get('priority')) filters.priority = params.get('priority');
            if (params.get('tag')) filters.tags = params.get('tag').split(',').map(function (x) { return x.trim(); }).filter(Boolean);
            if (params.get('sla_status') === 'breach') filters.sla_breach = true;
            if (params.get('created_from')) filters.created_from = params.get('created_from');
            if (params.get('created_to')) filters.created_to = params.get('created_to');
            if (params.get('archived')) filters.is_archived = true;

            closeModal();
            $.ajax({
                url: TKA.urls.viewsStore,
                method: 'POST',
                data: { name: name, filters: filters, shared: shared ? 1 : 0 },
                headers: { Accept: 'application/json' },
                success: function () {
                    if (window.toastr) toastr.success('Vista guardada');
                    window.location.reload();
                },
                error: function (xhr) {
                    var msg = apiErrorMessage(xhr, 'No se pudo guardar la vista');
                    if (window.toastr) toastr.error(msg); else window.alert(msg);
                },
            });
        });
    }

    // Resincroniza las integraciones (ERP/PrestaShop) del cliente del ticket
    // seleccionado — mismo backend real que la ficha de Contactos 360
    // (ContactAggregatorService::syncIntegrations vía la ruta contacts.sync).
    // Sin ticket seleccionado, sin cliente vinculado o con el módulo
    // HelpdeskContacts desactivado, se avisa honestamente en vez de fingir
    // progreso.
    function syncCurrentCustomer($trigger) {
        var d = TKA.state.currentDetail;
        var customer = d && d.customer;

        if (!customer || !customer.id || !TKA.urls.contactsSyncTemplate) {
            var warnMsg = 'Este ticket no tiene un cliente vinculado con integraciones que sincronizar.';
            if (window.toastr) toastr.warning(warnMsg); else window.alert(warnMsg);
            return;
        }

        // El botón que lo dispara puede ser el de la cabecera del panel o el de
        // la tarjeta "Integraciones": el spinner va en el que se ha pulsado.
        var $btn = ($trigger && $trigger.length) ? $trigger : $('#tkt-sync');
        // fa-spin (utilidad de FontAwesome, ya cargado globalmente) — el
        // icono fa-rotate sugiere giro pero antes nunca lo hacía; en una red
        // local rápida el único feedback (opacity:.5 del [disabled]) es casi
        // imperceptible antes de que aparezca el toast final.
        $btn.prop('disabled', true).find('i').addClass('fa-spin');

        $.ajax({
            url: TKA.urls.contactsSyncTemplate.replace('__CUSTOMER__', customer.id),
            method: 'POST',
            headers: { Accept: 'application/json' },
            success: function (res) {
                var integrations = (res && res.data && res.data.integrations) || [];
                var connected = integrations.filter(function (i) { return i.connected; }).map(function (i) { return i.label; });
                var msg = connected.length
                    ? 'Sincronizado con ' + connected.join(', ') + '.'
                    : 'Sincronizado: sin integraciones externas encontradas para este cliente.';
                if (window.toastr) toastr.success(msg); else window.alert(msg);
            },
            error: function (xhr) {
                var msg = apiErrorMessage(xhr, 'No se pudo sincronizar las integraciones del cliente.');
                if (window.toastr) toastr.error(msg); else window.alert(msg);
            },
            complete: function () {
                $btn.prop('disabled', false).find('i').removeClass('fa-spin');
            },
        });
    }

    // ═══════════ Modales genéricos (design system del mockup) ═══════════
    // A diferencia del mockup original (que clona plantillas estáticas ya
    // pre-renderizadas en el HTML), aquí cada "openXxxModal()" construye su
    // propio HTML con datos reales del ticket seleccionado y lo pasa a
    // openModal(). Reemplaza los window.prompt()/confirm() encadenados que
    // se usaban como solución provisional en Aplazar/Vincular/Fusionar/
    // Etiquetar/Conversación paralela. Se cierra con la X (o cualquier
    // elemento con [data-modal-close]), clic en el fondo, o Escape.
    function openModal(html) {
        var active = document.activeElement;
        var hadModal = $('#tkt-modal-backdrop').length > 0;
        var returnFocus = tktModalReturnFocus;
        if (!hadModal && active && active !== document.body) {
            returnFocus = active;
        }
        closeModal(false);
        tktModalReturnFocus = returnFocus;
        var $backdrop = $('<div class="tkt-modal-backdrop on" id="tkt-modal-backdrop"></div>').html(html);
        // Se añade dentro de .tkt (no de body): las variables --tkt-* solo
        // se definen bajo ese selector — fuera de él el modal se renderiza
        // sin fondo/borde/sombra (transparente). position:fixed hace que
        // igualmente cubra toda la ventana pese a no ser hijo de <body>.
        $('.tkt').append($backdrop);
        $('body').css('overflow', 'hidden');
        $backdrop.on('mousedown', function (ev) {
            if (ev.target === this) closeModal();
        });
        $backdrop.on('click', '[data-modal-close]', function () { closeModal(); });
        $(document).on('keydown.tktModal', function (ev) {
            if (ev.key === 'Escape') closeModal();
        });
        var $dialog = $backdrop.find('.tkt-modal').first();
        $backdrop.on('keydown.tktModalTrap', function (ev) {
            if (ev.key !== 'Tab' || !$dialog.length) return;
            var $focusable = $dialog.find('a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [contenteditable="true"], [tabindex]:not([tabindex="-1"])').filter(':visible');
            if (!$focusable.length) {
                ev.preventDefault();
                $dialog.trigger('focus');
                return;
            }
            var first = $focusable.get(0);
            var last = $focusable.get($focusable.length - 1);
            if (ev.shiftKey && document.activeElement === first) {
                ev.preventDefault();
                last.focus();
            } else if (!ev.shiftKey && document.activeElement === last) {
                ev.preventDefault();
                first.focus();
            }
        });
        // Todo modal dinámico (bulk actions, seguidores, conversación
        // paralela…) trae sus <select> ya con las opciones finales en el
        // html pasado a openModal() — un único punto de inicialización
        // cubre cualquier modal presente y futuro sin tener que acordarse
        // de llamarlo en cada función que abre uno.
        initSelect2($backdrop);

        // Foco automático en el primer campo editable — encontrado probando
        // los atajos de teclado uno por uno: "A" abría "Asignar ticket" pero
        // dejaba el foco en <body>, así que había que hacer clic a mano en
        // "Buscar agente…" antes de poder teclear. Un único punto (como
        // initSelect2 arriba) cubre cualquier modal presente y futuro. Solo
        // input/textarea (nunca <select>): select2 oculta el <select> real
        // con aria-hidden y monta un elemento nuevo al lado — ese
        // ':visible' ya lo descarta solo, pero enfocarlo igualmente no
        // abriría el desplegable visual.
        var $autofocus = $backdrop.find('input, textarea').filter(':visible:not(:disabled)').first();
        if ($autofocus.length) {
            $autofocus.trigger('focus');
        } else if ($dialog.length) {
            $dialog.trigger('focus');
        }

        return $backdrop;
    }

    function closeModal(restoreFocus) {
        $('#tkt-modal-backdrop').remove();
        $(document).off('keydown.tktModal');
        $('body').css('overflow', '');
        var target = tktModalReturnFocus;
        tktModalReturnFocus = null;
        if (restoreFocus !== false && target && document.contains(target)) {
            $(target).trigger('focus');
        }
    }

    // Sustituye a los window.confirm() encadenados que quedaban sueltos
    // (cancelar seguimiento, eliminar nota, desvincular ticket, bulk actions
    // directas) — bug de diseño real encontrado en QA: eran los únicos
    // puntos de la pantalla que rompían el estilo con el diálogo nativo del
    // navegador, sin marca ni control sobre el texto/botones.
    function openConfirmModal(opts) {
        var $backdrop = openModal(modalShell({
            icon: opts.icon || 'fa-solid fa-triangle-exclamation',
            iconClass: opts.danger ? 'danger' : '',
            title: opts.title,
            width: 'sm',
            body: '<p style="margin:0;font-size:var(--tkt-t-md);color:var(--tkt-text-soft)">' + escapeHtml(opts.message) + '</p>',
            foot: '<button type="button" class="tkt-btn ' + (opts.danger ? 'tkt-btn-danger' : 'tkt-btn-primary') + '" id="tkt-confirm-ok">' + escapeHtml(opts.confirmLabel || 'Confirmar') + '</button>' +
                  '<button type="button" class="tkt-btn" data-modal-close>Cancelar</button>',
        }));
        $backdrop.on('click', '#tkt-confirm-ok', function () {
            closeModal();
            opts.onConfirm();
        });
    }

    // Para modales ESTÁTICOS ya presentes en el blade (el formulario "Más
    // filtros" trae los <option selected> ya resueltos por el servidor, no
    // tiene sentido reconstruirlo con openModal()/HTML por JS) — mismo
    // comportamiento de apertura/cierre (fondo, ✕, Escape) que los dinámicos.
    function bindStaticModal(openSel, backdropSel, closeSel) {
        var $backdrop = $(backdropSel);
        var $dialog = $backdrop.find('.tkt-modal').first();
        var returnFocus = null;
        $(openSel).on('click', function () {
            returnFocus = document.activeElement;
            $backdrop.addClass('on');
            $('body').css('overflow', 'hidden');
            var $autofocus = $dialog.find('input, textarea, select').filter(':visible:not(:disabled)').first();
            if ($autofocus.length) $autofocus.trigger('focus');
            else if ($dialog.length) $dialog.trigger('focus');
        });
        function close() {
            $backdrop.removeClass('on');
            $('body').css('overflow', '');
            if (returnFocus && document.contains(returnFocus)) $(returnFocus).trigger('focus');
            returnFocus = null;
        }
        $(closeSel).on('click', close);
        $backdrop.on('mousedown', function (ev) {
            if (ev.target === this) close();
        });
        $(document).on('keydown.' + backdropSel.replace(/[^\w]/g, ''), function (ev) {
            if (ev.key === 'Escape' && $backdrop.hasClass('on')) close();
        });
    }

    // Cabecera + pie estándar de modal (mismo markup en los ~15 modales
    // reales que se han ido añadiendo) — evita repetir el boilerplate.
    function modalShell(opts) {
        var iconCls = opts.iconClass ? ' ' + opts.iconClass : '';
        return '' +
            '<div class="tkt-modal' + (opts.width ? ' w-' + opts.width : '') + '" id="tkt-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="tkt-modal-title" tabindex="-1">' +
                '<div class="tkt-modal-head">' +
                    '<div class="tkt-modal-icon' + iconCls + '"><i class="' + opts.icon + '"></i></div>' +
                    '<div class="tkt-fill">' +
                        (opts.kicker ? '<div class="tkt-modal-kicker">' + escapeHtml(opts.kicker) + '</div>' : '') +
                        // titleChip: el nº de ticket en negro junto al título,
                        // como en las cabeceras de modal del mockup.
                        '<div class="tkt-modal-title" id="tkt-modal-title">' + escapeHtml(opts.title) +
                            (opts.titleChip ? '<span class="tkt-chip-id">' + escapeHtml(opts.titleChip) + '</span>' : '') +
                        '</div>' +
                    '</div>' +
                    '<button type="button" class="tkt-modal-close" data-modal-close aria-label="Cerrar ventana"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>' +
                '</div>' +
                '<div class="tkt-modal-body">' + opts.body + '</div>' +
                (opts.foot ? '<div class="tkt-modal-foot' + (opts.footTwoCol ? ' two-col' : '') + '">' + opts.foot + '</div>' : '') +
            '</div>';
    }

    // ═══════════ Render: detalle básico (Fase A — sin AJAX todavía) ═══════════
    function chip(text, cls) {
        return '<span class="tkt-chip ' + (cls || 'tkt-chip-muted') + '">' + escapeHtml(text) + '</span>';
    }

    // Botón "Crear un ticket" del estado vacío del detalle: mismo destino
    // que el "Nuevo ticket" de la barra superior.
    $(document).on('click', '#tkt-empty-create', function () {
        openNewTicketModal();
    });

    // El botón "Nuevo ticket" y el del estado vacío abren el modal 35. Los
    // dos conservan su href a /tickets/create: si el JS no cargara, el enlace
    // sigue llevando al formulario completo.
    $(document).on('click', '#tkt-new-ticket', function (ev) {
        ev.preventDefault();
        openNewTicketModal();
    });

    function selectTicket(t) {
        var previousTicket = TKA.state.currentTicket;
        if (!previousTicket || String(previousTicket.id) !== String(t.id)) {
            TKA.state.threadHistoryExpanded = false;
            TKA.state.currentDetail = null;
            TKA.state.threadSearch = '';
            TKA.state.threadPage = 1;
            TKA.state.threadHasMore = false;
            TKA.state.threadTotal = 0;
            TKA.state.threadFilters = { sender: '', type: 'all', from: '', to: '', channel: '' };
            TKA.state.threadLoadingMore = false;
            TKA.state.editConflictMessage = '';
            TKA.state.editConflictFields = [];
        }
        TKA.state.selected = t.id;
        $('.tkt-ticket-row').removeClass('on');
        $('.tkt-ticket-row[data-id="' + t.id + '"]').addClass('on');
        // Deep-link real: al seleccionar un ticket, la URL de ESTA MISMA
        // pantalla lo referencia (?ticket=id) — igual que Helpdesk hace con
        // ?selected= en Conversaciones. No se navega a otra página/paradigma
        // distinto (la vieja ficha /tickets/{id}/full).
        if (window.history && window.history.replaceState) {
            var params = new URLSearchParams(window.location.search);
            params.set('ticket', t.id);
            window.history.replaceState(null, '', window.location.pathname + '?' + params.toString());
        }
        renderDetail(t);
        renderSidePanel(t);
        if (window.matchMedia && window.matchMedia('(max-width: 1180px)').matches) openMobilePane('detail');
    }

    /**
     * Fecha corta para la cabecera: "hoy 10:42" cuando es de hoy y
     * "30 ago 10:42" en cualquier otro caso — el formato del mockup. Con el
     * día entero delante, la línea de contexto no cabía.
     */
    function shortDateTime(iso) {
        var d = new Date(iso);
        if (isNaN(d)) return '';

        var hhmm = d.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
        var hoy = new Date();
        var esHoy = d.toDateString() === hoy.toDateString();

        return esHoy
            ? 'hoy ' + hhmm
            : d.toLocaleDateString('es-ES', { day: '2-digit', month: 'short' }) + ' ' + hhmm;
    }

    // Texto "Creado ... · <agente o sin asignar>" de la cabecera del
    // detalle — función propia para poder refrescarlo tras cambiar el
    // agente asignado sin tener que repintar toda la cabecera.
    function detailMetaText(t) {
        // "Creado … · última actualización … · <agente>", como el mockup. La
        // fecha de actualización solo se añade si aporta algo: en un ticket
        // recién creado coincide con la de creación y repetirla es ruido.
        var parts = ['Creado ' + (t.created_at_human || '—')];

        if (t.updated_at && t.updated_at !== t.created_at) {
            parts.push('última actualización ' + shortDateTime(t.updated_at));
        }

        parts.push(t.assignee ? t.assignee.name : 'sin asignar');

        return parts.join(' · ');
    }

    function renderDetail(t) {
        // Un cambio de estado/prioridad puede llegar mientras el agente está
        // escribiendo. Guardamos el nodo completo, no solo el texto: así no
        // se pierde el modo "Nota interna", el cursor ni los File objects del
        // input de adjuntos al reconstruir la cabecera del detalle.
        var $preservedComposer = $('#tkt-composer');
        if ($preservedComposer.length && TKA.state.currentTicket &&
            String(TKA.state.currentTicket.id) === String(t.id)) {
            TKA.state.preservedComposer = $preservedComposer.detach();
        } else {
            TKA.state.preservedComposer = null;
        }

        $('#tkt-detail-empty').hide();
        // .css('display','flex') en vez de .show(): #tkt-detail necesita
        // ser flex-column (cabecera fija + panes con scroll interno propio,
        // ver layout de altura completa en tickets-app.css) — jQuery.show()
        // restaura el display "por defecto" del tag (block), no el flex
        // que pide la hoja de estilos.
        var $d = $('#tkt-detail').css('display', 'flex');

        var statusLabel = t.status_name || STATUS_LABEL_FALLBACK[t.status_slug] || t.status_slug;
        // El SLA vencido/en riesgo ya NO vive como chip en la fila de
        // clasificación (bug de diseño real, QA visual 14-sep-2026): ahí
        // competía en el mismo gris apagado que "Urgente"/"Manual" y la
        // alerta más crítica de la cabecera pasaba desapercibida. Ahora sale
        // como franja aparte (slaBanner, ver tkt-detail-sla-banner en
        // tickets-app.css), fuera de la fila de chips. En plazo no hay nada
        // que avisar, así que sigue siendo un chip discreto — una franja por
        // cada ticket sin problema sería ruido, no alerta. Mismo criterio que
        // la fila del listado: slaRowText() devuelve el guion largo cuando el
        // ticket no tiene plazo, y "SLA —" no dice nada.
        var slaChip = '';
        var slaBanner = '';
        if (t.sla_kind === 'breach') {
            slaBanner = '<div class="tkt-detail-sla-banner breach" data-sla-banner data-sla-state="breach">' +
                '<i class="fa-solid fa-triangle-exclamation"></i>' +
                '<b data-sla-label>SLA vencido</b>' +
                (t.sla_due_at ? '<span class="dim">· <span data-sla-live></span></span>' : (t.sla_text && t.sla_text !== '—' ? '<span class="dim">· hace ' + escapeHtml(t.sla_text.replace(/ vencido$/, '')) + '</span>' : '')) +
                '<button type="button" class="tkt-detail-sla-banner-link" id="tkt-sla-banner-link">Ver política SLA <i class="fa-solid fa-chevron-right"></i></button>' +
            '</div>';
        } else if (t.sla_kind === 'warn') {
            slaBanner = '<div class="tkt-detail-sla-banner warn" data-sla-banner data-sla-state="warn">' +
                '<i class="fa-regular fa-clock"></i>' +
                '<b data-sla-label>SLA en riesgo</b>' +
                (t.sla_due_at ? '<span class="dim">· <span data-sla-live></span></span>' : (t.sla_text && t.sla_text !== '—' ? '<span class="dim">· quedan ' + escapeHtml(t.sla_text) + '</span>' : '')) +
                '<button type="button" class="tkt-detail-sla-banner-link" id="tkt-sla-banner-link">Ver política SLA <i class="fa-solid fa-chevron-right"></i></button>' +
            '</div>';
        } else if (t.sla_text && t.sla_text !== '—') {
            slaChip = t.sla_due_at
                ? '<span class="tkt-chip tkt-chip-ok" data-sla-chip><i class="fa-regular fa-clock"></i><span data-sla-live></span></span>'
                : chip('SLA ' + t.sla_text, 'tkt-chip-ok');
        }

        // Posición dentro de la lista visible ("1 de 6" en el mockup), para
        // la barra de navegación de la cabecera.
        var visible = visibleTickets();
        var pos = visible.findIndex(function (x) { return x.id === t.id; });
        var positionLabel = pos >= 0 ? (pos + 1) + ' de ' + visible.length : '';

        // Chip de entrega del último correo. Solo se pinta cuando hay un dato
        // real que mostrar: el mockup lo enseña siempre porque su ticket de
        // ejemplo tiene correo enviado, pero un ticket de widget o WhatsApp
        // no tiene ninguna entrega que reportar.
        var deliveryChip = t.last_mail_status === 'delivered' || t.last_mail_status === 'sent'
            ? '<span class="tkt-chip-delivery"><i class="fa-solid fa-circle-check"></i> Email entregado</span>'
            : (t.last_mail_status === 'failed' || t.last_mail_status === 'bounced'
                ? '<span class="tkt-chip-delivery" style="background:var(--tkt-danger-bg);color:#fff"><i class="fa-solid fa-circle-exclamation"></i> Email rebotado</span>'
                : '');

        $d.html(
            '<div class="tkt-detail-head">' +
                '<div class="tkt-detail-navbar">' +
                    '<span class="tkt-cap">Tickets · Detalle</span>' +
                    '<span class="tkt-detail-position" id="tkt-detail-position">' + escapeHtml(positionLabel) + '</span>' +
                    '<span class="tkt-detail-nav">' +
                        '<button type="button" id="tkt-detail-prev" title="Anterior (K)" aria-label="Ticket anterior"' + (pos <= 0 ? ' disabled' : '') + '><i class="fa-solid fa-chevron-up"></i></button>' +
                        '<button type="button" id="tkt-detail-next" title="Siguiente (J)" aria-label="Ticket siguiente"' + (pos < 0 || pos >= visible.length - 1 ? ' disabled' : '') + '><i class="fa-solid fa-chevron-down"></i></button>' +
                    '</span>' +
                '</div>' +
                '<div class="tkt-detail-head-row">' +
                    '<div class="tkt-detail-title-col">' +
                        '<div class="tkt-detail-title">' + escapeHtml(t.subject || '(sin asunto)') + '</div>' +
                        '<div class="tkt-detail-chips">' +
                            deliveryChip +
                            '<span class="tkt-chip-id">' + escapeHtml(t.ticket_number) + '</span>' +
                            '<span class="tkt-chip ' + statusChipClass(t.status_slug) + '"><span class="tkt-chip-dot"></span>' + escapeHtml(statusLabel) + '</span>' +
                            (t.priority ? '<span class="tkt-chip ' + priorityChipClass(t.priority) + '"><i class="fa-solid fa-flag"></i>' + escapeHtml(priorityLabel(t.priority)) + '</span>' : '') +
                            '<span class="tkt-chip-channel"><i class="' + (ORIGIN_ICON[t.source] || 'fa-solid fa-tag') + '"></i>' + escapeHtml(ORIGIN_LABELS[t.source] || t.source || '—') + '</span>' +
                            slaChip +
                            (t.has_attachments ? '<span class="tkt-chip-att"><i class="fa-solid fa-paperclip"></i> 1</span>' : '') +
                        '</div>' +
                        '<div class="tkt-detail-context">' +
                            (t.customer ? '<span class="who"><i class="fa-regular fa-user"></i>' + escapeHtml(t.customer.name) + '</span>' : '<span class="who"><i class="fa-regular fa-user"></i>Sin cliente</span>') +
                            (t.customer && t.customer.email ? '<span class="sep">·</span><span class="mail">' + escapeHtml(t.customer.email) + '</span>' : '') +
                            '<span class="sep">·</span>' +
                            '<span id="tkt-detail-meta">' + escapeHtml(detailMetaText(t)) + '</span>' +
                            '<span id="tkt-detail-customer-quick" class="tkt-detail-customer-quick" hidden></span>' +
                        '</div>' +
                    '</div>' +
                    '<div class="tkt-detail-actions">' +
                        // Quién más está en el ticket, en burbujas tipo Drive.
                        // El banner de texto sigue existiendo, pero el agente
                        // mira la cabecera, no una franja bajo las pestañas.
                        '<span class="tkt-presence" id="tkt-presence" hidden></span>' +
                        '<button type="button" class="tkt-btn-icon" id="tkt-goto-state" title="Cambiar estado" aria-label="Cambiar estado"><i class="fa-solid fa-arrow-right-arrow-left"></i></button>' +
                        '<button type="button" class="tkt-btn-icon" id="tkt-goto-assign" title="Asignar" aria-label="Asignar"><i class="fa-solid fa-user-plus"></i></button>' +
                        '<button type="button" class="tkt-btn-icon" id="tkt-goto-actions" title="Más acciones" aria-label="Más acciones"><i class="fa-solid fa-ellipsis"></i></button>' +
                        '<button type="button" class="tkt-btn-icon" id="tkt-open-action-palette" title="Paleta de acciones (⌘⇧P)" aria-label="Abrir paleta de acciones"><i class="fa-solid fa-command"></i></button>' +
                    '</div>' +
                '</div>' +
                slaBanner +
                '<div class="tkt-dtabs">' +
                    (featureEnabled('tab_mail') ? '<button type="button" class="tkt-dtab" data-dtab="mail"><i class="fa-regular fa-envelope"></i> Correo<span class="tkt-dcount" data-badge="mail"></span></button>' : '') +
                    '<button type="button" class="tkt-dtab on" data-dtab="thread"><i class="fa-solid fa-comments"></i> Hilo<span class="tkt-dcount" data-badge="thread"></span></button>' +
                    (featureEnabled('tab_trace') ? '<button type="button" class="tkt-dtab" data-dtab="trace"><i class="fa-solid fa-route"></i> Traza<span class="tkt-dcount" data-badge="trace"></span></button>' : '') +
                    (featureEnabled('tab_activity') ? '<button type="button" class="tkt-dtab" data-dtab="activity"><i class="fa-solid fa-wave-square"></i> Actividad<span class="tkt-dcount" data-badge="activity"></span></button>' : '') +
                    (featureEnabled('tab_files') ? '<button type="button" class="tkt-dtab" data-dtab="files"><i class="fa-solid fa-paperclip"></i> Adjuntos<span class="tkt-dcount" data-badge="files"></span></button>' : '') +
                '</div>' +
            '</div>' +
            '<div class="tkt-banner-warn tkt-banner-presence tkt-detail-banner" id="tkt-collision-banner"  role="status"><i class="fa-solid fa-users"></i><span id="tkt-collision-text" class="tkt-flex1"></span></div>' +
            '<div class="tkt-banner-warn tkt-banner-presence tkt-detail-banner" id="tkt-typing-indicator"  role="status"><i class="fa-solid fa-pen"></i><span id="tkt-typing-text" class="tkt-flex1"></span></div>' +
            '<div class="tkt-banner-warn tkt-detail-banner tkt-edit-conflict" id="tkt-edit-conflict" role="status" hidden><i class="fa-solid fa-shield-halved"></i><span id="tkt-edit-conflict-text" class="tkt-flex1"></span><button type="button" class="tkt-btn tkt-btn-mini" id="tkt-edit-conflict-review">Revisar cambios</button><button type="button" class="tkt-banner-dismiss" id="tkt-edit-conflict-dismiss" aria-label="Ocultar aviso"><i class="fa-solid fa-xmark"></i></button></div>' +
            // Relleno por renderSelfAssignBanner(): placeholder fijo en vez de
            // insertarlo con .after() (como el de duplicados) para que el
            // orden con el resto de banners no dependa de quién se pintó primero.
            '<div id="tkt-selfassign-banner" hidden></div>' +
            '<div class="tkt-banner-ai tkt-detail-banner" id="tkt-ai-banner" ><i class="fa-solid fa-wand-magic-sparkles"></i>' +
                '<span class="tkt-banner-ai-main">' +
                    '<span class="tkt-banner-ai-head"><span class="tkt-banner-ai-title">Resumen IA</span>' +
                        '<span class="tkt-banner-ai-chips" id="tkt-ai-banner-chips"></span></span>' +
                    '<span id="tkt-ai-banner-text"></span>' +
                '</span>' +
                '<button type="button" class="tkt-btn tkt-btn-mini" id="tkt-ai-expand">Ampliar</button></div>' +
            '<div class="tkt-pane" id="tkt-dpane-mail" hidden></div>' +
            '<div class="tkt-pane" id="tkt-dpane-thread"><div class="tkt-skeleton"></div></div>' +
            '<div class="tkt-pane" id="tkt-dpane-trace" hidden></div>' +
            '<div class="tkt-pane" id="tkt-dpane-activity" hidden></div>' +
            '<div class="tkt-pane" id="tkt-dpane-files" hidden></div>'
        );

        joinTicketPresence(t.id);
        startTicketPulse(t);
        startPresenceHeartbeat(t);
        startSlaClock(t);
        renderSelfAssignBanner(t);

        // El icono de estado abre el modal 36; los otros dos siguen llevando
        // el foco al campo correspondiente del panel Gestión.
        $('#tkt-goto-state').on('click', function () { openChangeStatusModal(t); });
        $('#tkt-open-action-palette').on('click', function () { openActionPalette(t); });
        $('#tkt-edit-conflict-dismiss').on('click', function () {
            TKA.state.editConflictMessage = '';
            $('#tkt-edit-conflict').attr('hidden', true);
        });
        $('#tkt-edit-conflict-review').on('click', openConflictReviewModal);

        // "Ver política SLA" de la franja de alerta abre el mismo modal 28
        // que ya usa la acción "Calendario y SLA" del panel Gestión — lee el
        // ticket seleccionado de TKA.state, no hace falta pasárselo.
        $('#tkt-sla-banner-link').on('click', openSlaCalendarModal);

        $('#tkt-goto-assign, #tkt-goto-actions').on('click', function () {
            var focusId = this.id === 'tkt-goto-assign' ? 'tkt-sg-assignee-wrap' : 'tkt-sg-actions';
            // Los tres viven dentro del panel "Gestión" del lateral: hay que abrirlo primero.
            if (TKA.state.sideTab !== 'gestion') selectSideTab('gestion');
            var $target = $('#' + focusId);
            if ($target.length) {
                $target[0].scrollIntoView({ block: 'center', behavior: 'smooth' });
                if ($target.is('select')) {
                    // select2 oculta el <select> nativo con
                    // aria-hidden="true" (select2-hidden-accessible) —
                    // enfocarlo directamente (.trigger('focus')) hacía que
                    // Chrome bloqueara el foco con un warning real
                    // ("Blocked aria-hidden on an element because its
                    // descendant retained focus"), sin mover el foco a
                    // ningún control usable. select2('open') abre el propio
                    // combobox visible, que sí es accesible por teclado —
                    // efecto secundario: también da un affordance mucho más
                    // visible que el simple anillo de foco del navegador.
                    if ($target.data('select2')) $target.select2('open');
                    else $target.trigger('focus');
                }
                else $target.addClass('tkt-highlight-flash').one('animationend', function () { $(this).removeClass('tkt-highlight-flash'); });
            }
        });

        $d.find('[data-dtab]').on('click', function () {
            if ($(this).is('[disabled]')) return;
            selectDetailTab($(this).data('dtab'));
        });

        // Chevrones de la barra "N de M": mismo salto que los atajos J/K,
        // que ya existían pero solo por teclado.
        $('#tkt-ai-expand').on('click', function () { openAiSummaryModal(t); });
        $('#tkt-collision-banner').on('click', '[data-collision-open]', function () {
            openCollisionModal(t, TKA.state.presenceUsers || []);
        });

        $('#tkt-detail-prev').on('click', function () { moveSelection(-1); });
        $('#tkt-detail-next').on('click', function () { moveSelection(1); });

        fetchDetailData(t);
        fetchAiSummary(t);
    }

    // Banner "Resumen IA ·" — TicketMailAiSummaryService::summarize(Ticket),
    // cacheado 10 min por el propio servicio. Igual que el resto de bloques
    // de IA en este proyecto: si no hay API key configurada o no hay
    // contexto suficiente, el servicio devuelve null y el banner
    // simplemente no aparece (nunca un resumen inventado).
    function fetchAiSummary(t) {
        $('#tkt-ai-banner').hide();
        if (!t.url_summary) return;
        $.getJSON(t.url_summary).done(function (res) {
            if (TKA.state.currentTicket !== t) return; // el agente ya cambió de ticket
            if (res && res.summary) {
                // Chips del mockup: cuántos correos resume y en qué idioma
                // escribe el cliente. El mockup añade "confianza 86 %", que
                // el servicio de resumen no devuelve — no se inventa.
                var d = TKA.state.currentDetail;
                var chips = '';
                var nMails = d && d.mails ? d.mails.length : 0;
                if (nMails) chips += '<span class="tkt-banner-ai-chip">' + nMails + (nMails === 1 ? ' correo' : ' correos') + '</span>';
                var lang = d && d.customer && d.customer.language;
                if (lang) chips += '<span class="tkt-banner-ai-chip">' + escapeHtml(String(lang).toUpperCase()) + '</span>';

                $('#tkt-ai-banner-chips').html(chips);
                $('#tkt-ai-banner-text').text(res.summary);
                $('#tkt-ai-banner').show();
            }
        });
    }

    function selectDetailTab(which) {
        $('#tkt-detail [data-dtab]').each(function () {
            $(this).toggleClass('on', $(this).data('dtab') === which);
        });
        ['mail', 'thread', 'trace', 'activity', 'files'].forEach(function (k) {
            $('#tkt-dpane-' + k).prop('hidden', k !== which);
        });
    }

    function renderCustomerQuickSummary(ticket, customer) {
        var $quick = $('#tkt-detail-customer-quick');
        if (!$quick.length || !customer) return;
        var facts = [];
        if (customer.tickets_count != null) facts.push(customer.tickets_count + ' tickets');
        if (customer.avg_csat != null) facts.push('CSAT ' + customer.avg_csat);
        if (customer.is_banned) facts.push('bloqueado');
        if (!facts.length) return;
        $quick.html('<button type="button" id="tkt-detail-customer-open" title="Abrir resumen del cliente"><i class="fa-regular fa-address-card"></i> ' + escapeHtml(facts.join(' · ')) + '</button>').removeAttr('hidden');
        $quick.off('click.tktCustomer').on('click.tktCustomer', '#tkt-detail-customer-open', function () {
            if (TKA.state.sideTab !== 'cliente') selectSideTab('cliente');
            if (window.matchMedia && window.matchMedia('(max-width: 1180px)').matches) openMobilePane('side');
            var el = document.getElementById('tkt-side-content');
            if (el) el.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
        });
    }

    // ═══════════ Detalle: Hilo / Trazabilidad / Actividad / Archivos / Correo ═══════════
    function threadFilterState(filters) {
        filters = filters || TKA.state.threadFilters || {};
        return {
            sender: String(filters.sender || '').trim(),
            type: String(filters.type || 'all'),
            from: String(filters.from || '').trim(),
            to: String(filters.to || '').trim(),
            channel: String(filters.channel || '').trim(),
        };
    }

    function appendQueryParams(url, params) {
        var parts = [];
        Object.keys(params || {}).forEach(function (key) {
            if (params[key] !== undefined && params[key] !== null && String(params[key]) !== '') {
                parts.push(encodeURIComponent(key) + '=' + encodeURIComponent(params[key]));
            }
        });
        return parts.length ? url + (url.indexOf('?') === -1 ? '?' : '&') + parts.join('&') : url;
    }

    function mergeThreadItems(existing, incoming) {
        var seen = {};
        return (existing || []).concat(incoming || []).filter(function (item) {
            var key = threadItemKey(item);
            if (seen[key]) return false;
            seen[key] = true;
            return true;
        }).sort(function (a, b) {
            var at = new Date(a.created_at || 0).getTime() || 0;
            var bt = new Date(b.created_at || 0).getTime() || 0;
            return at === bt ? (Number(a.id || 0) - Number(b.id || 0)) : at - bt;
        });
    }

    function fetchDetailData(t, opts) {
        opts = opts || {};
        var requestedThreadSearch = opts.threadSearch !== undefined
            ? String(opts.threadSearch || '').trim()
            : String(TKA.state.threadSearch || '').trim();
        var requestedFilters = threadFilterState(opts.threadFilters || TKA.state.threadFilters);
        var requestedPage = opts.threadPage !== undefined ? Math.max(1, Number(opts.threadPage) || 1) : 1;
        var dataUrl = appendQueryParams(t.url_data, {
            thread_search: requestedThreadSearch,
            thread_sender: requestedFilters.sender,
            thread_type: requestedFilters.type === 'all' ? '' : requestedFilters.type,
            thread_from: requestedFilters.from,
            thread_to: requestedFilters.to,
            thread_channel: requestedFilters.channel,
            thread_page: requestedPage,
            thread_per_page: TKA.state.threadPerPage || 30,
        });

        $.getJSON(dataUrl)
            .done(function (d) {
                if (TKA.state.currentTicket !== t || String(TKA.state.threadSearch || '').trim() !== requestedThreadSearch || JSON.stringify(threadFilterState()) !== JSON.stringify(requestedFilters)) return;
                var previousDetail = TKA.state.currentDetail;
                var previousThread = previousDetail && previousDetail.thread ? previousDetail.thread : [];
                var nextThread = d.thread || [];
                var appendingOlder = !!opts.appendThread && requestedPage > 1;
                if (appendingOlder) nextThread = mergeThreadItems(nextThread, previousThread);
                TKA.state.threadRefreshMeta = {
                    existing: !!previousDetail,
                    changed: appendingOlder ? false : threadDataChanged(previousThread, nextThread),
                    newMessages: appendingOlder ? 0 : countNewThreadMessages(previousThread, nextThread),
                    prepended: appendingOlder,
                };
                TKA.state.threadPage = Number(d.thread_page || requestedPage);
                TKA.state.threadHasMore = !!d.thread_has_more;
                TKA.state.threadTotal = Number(d.thread_total || nextThread.length);
                TKA.state.threadFilters = requestedFilters;
                TKA.state.threadLoadingMore = false;
                d.thread = nextThread;
                TKA.state.currentDetail = d;
                renderCustomerQuickSummary(t, d.customer);
                // El propio endpoint (TicketDetailDataController::data())
                // marca leído todo el hilo para este agente como efecto
                // secundario de abrirlo (TicketRead::markAllReadFor()) — la
                // fila de la lista tenía el dato viejo desde que se cargó,
                // así que se corrige aquí sin esperar a un refetch completo.
                // Solo esta fila, no renderList() entero: repintar la lista
                // completa en cada apertura de ticket devolvería el scroll
                // arriba cada vez, y aquí no hace falta — nada más cambia.
                if (t.unread_count > 0) {
                    t.unread_count = 0;
                    var $oldRow = $('.tkt-ticket-row[data-id="' + t.id + '"]');
                    if ($oldRow.length) $oldRow.replaceWith(renderRow(t));
                }
                renderThreadPane(d.thread || []);
                renderActivityPane(d.activity || [], d.activity_total_count);
                renderFilesPane(d.files || [], t);
                renderMailPane(d.mail);
                renderTracePane(d.trace || [], d.mail, d.trace_meta);
                renderActiveSidePane();
                updateDetailTabBadges(d);
                checkDuplicates(t);
                TKA.state.threadRefreshMeta = null;
                if (TKA.state.editConflictMessage) showEditConflict(TKA.state.editConflictMessage, true);
            })
            .fail(function () {
                TKA.state.threadLoadingMore = false;
                if (opts.appendThread) {
                    if (window.toastr) toastr.error('No se pudieron cargar los mensajes anteriores.');
                    return;
                }
                showDetailError(t);
            });
    }

    function threadItemKey(item) {
        if (!item) return '';
        return String(item.id || [item.type, item.created_at, item.sender_name, item.body].join('|'));
    }

    function threadDataChanged(previous, next) {
        previous = previous || [];
        next = next || [];
        if (previous.length !== next.length) return true;
        if (!previous.length && !next.length) return false;
        return threadItemKey(previous[previous.length - 1]) !== threadItemKey(next[next.length - 1]);
    }

    function countNewThreadMessages(previous, next) {
        var oldKeys = {};
        (previous || []).forEach(function (item) { oldKeys[threadItemKey(item)] = true; });
        return (next || []).filter(function (item) {
            return item && item.type === 'message' && !item.is_internal && !oldKeys[threadItemKey(item)];
        }).length;
    }

    // Fallo al cargar el detalle. Sustituye al panel entero, no solo a la
    // pestaña Hilo: dejar las otras cuatro con los datos del ticket anterior
    // mientras la cabecera ya muestra el nuevo es peor que no mostrar nada.
    function showDetailError(t) {
        var $d = $('#tkt-detail');
        $d.html(
            '<div class="tkt-empty-state tkt-detail-error">' +
                '<div class="tkt-empty-icon"><i class="fa-solid fa-plug-circle-xmark"></i></div>' +
                '<div class="tkt-empty-title">No se pudo cargar el hilo</div>' +
                '<div class="tkt-empty-text">No pudimos recuperar la conversación de este ticket. Puedes reintentar o revisar el estado de la cola.</div>' +
                '<div class="tkt-badge-row">' +
                    '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-retry-detail">Reintentar</button>' +
                    '<button type="button" class="tkt-btn" id="tkt-error-queue">Ver la cola</button>' +
                '</div>' +
            '</div>'
        );
        $d.find('#tkt-retry-detail').on('click', function () { selectTicket(t); });
        $d.find('#tkt-error-queue').on('click', function () { openQueueModal(); });
    }

    // Badges numéricos del riel de iconos del detalle (Correo/Hilo/
    // Trazabilidad/Actividad/Archivos) — cuentan lo que realmente llegó en
    // data(), sin pedir nada nuevo al backend.
    function updateDetailTabBadges(d) {
        var activityCount = (d.activity || []).length;
        var activityTruncated = d.activity_total_count && d.activity_total_count > activityCount;
        var counts = {
            mail: d.mail ? 1 : 0,
            thread: (d.thread || []).length,
            trace: (d.trace || []).length,
            activity: activityTruncated ? (activityCount + '+') : activityCount,
            files: (d.files || []).length,
        };
        Object.keys(counts).forEach(function (key) {
            $('#tkt-detail [data-badge="' + key + '"]').text(counts[key] || '');
        });

        // El chip de adjuntos de la cabecera se pinta antes de que lleguen
        // estos datos, y con información incompleta: has_attachments solo mira
        // el ÚLTIMO mensaje, así que un ticket con ficheros en mensajes
        // anteriores no lo enseñaba, y cuando lo enseñaba era con un "1" fijo.
        // Aquí ya se conoce el número real de ficheros del ticket: se corrige,
        // se crea si faltaba, o se retira si al final no había ninguno.
        var $chips = $('#tkt-detail .tkt-detail-chips');
        var $att = $chips.find('.tkt-chip-att');

        if (counts.files > 0) {
            var html = '<i class="fa-solid fa-paperclip"></i> ' + counts.files;
            if ($att.length) {
                $att.html(html);
            } else {
                $chips.append('<span class="tkt-chip-att">' + html + '</span>');
            }
        } else {
            $att.remove();
        }
    }

    // Antes solo 6 claves: priority_changed/category_changed (ambos creados
    // de verdad por TicketUpdateService) y system/note (TicketMergeService,
    // TicketSideConversationService) caían todos en el genérico fa-circle-info
    // — imposible distinguir de un vistazo "cambió la prioridad" de "se fusionó
    // un duplicado" (auditoría de tipos reales del hilo, 14-sep-2026).
    var EVENT_ICONS = {
        message: 'fa-comment', internal_note: 'fa-lock', status_change: 'fa-arrow-right-arrow-left',
        assigned: 'fa-user-check', unassigned: 'fa-user-xmark', closed: 'fa-lock', reopened: 'fa-rotate-left',
        priority_changed: 'fa-arrow-up-short-wide', category_changed: 'fa-tags',
        system: 'fa-gear', note: 'fa-comment-dots',
    };

    // Clave de día (año-mes-día local) para agrupar el hilo — separada del
    // texto mostrado porque "Hoy" no sirve para comparar.
    function threadDayKey(isoDate) {
        var d = new Date(isoDate);
        return d.getFullYear() + '-' + d.getMonth() + '-' + d.getDate();
    }

    function threadDayLabel(isoDate) {
        var d = new Date(isoDate);
        var today = new Date();
        if (threadDayKey(isoDate) === threadDayKey(today)) return 'Hoy';
        return new Intl.DateTimeFormat('es-ES', { day: '2-digit', month: 'short', year: 'numeric' }).format(d);
    }

    // Etiquetas de canal del hilo: el ticket guarda el origen en clave
    // ('email', 'form'…) y el mockup lo enseña en cristiano.
    // (el mapa de canales es el mismo ORIGIN_LABELS de arriba)

    /**
     * Chips de la cabecera de un mensaje: quién es (rol) y por dónde entró
     * (canal).
     *
     * Antes había un tercer chip de dirección (Entrante/Saliente) — se quitó
     * (14-sep-2026, feedback visual directo sobre el mockup: "muchos datos
     * ahí, no necesarios") porque es pura redundancia matemática con el rol:
     * direction sale de isFromAgent() igual que role, así que "Cliente"
     * SIEMPRE es "Entrante" y "Agente" SIEMPRE "Saliente" — ningún mensaje
     * real puede combinar Cliente+Saliente. El color de fondo de la tarjeta
     * (blanco/verde) ya distingue lo mismo una tercera vez.
     *
     * Cada chip restante se omite si su dato no viene: un ticket creado a
     * mano no tiene canal, y pintar "—" en su lugar solo añade ruido.
     */
    function threadChips(it) {
        var out = '';

        // El rol "Sistema" lleva su propio tono (antes idéntico al resto):
        // con el bug de role_system ya corregido en el backend, una burbuja
        // de Sistema (auto-respuesta, notificación) ahora sí puede aparecer,
        // y sin nada que la distinga se confunde con un chip cualquiera.
        if (it.role) {
            out += '<span class="tkt-tchip' + (it.role === 'Sistema' ? ' system' : '') + '">' + escapeHtml(it.role) + '</span>';
        }

        // Solo texto: con icono + rol + canal + hora la cabecera quedaba
        // recargada para lo que aporta (14-sep-2026, feedback sobre el
        // canvas de diseño). El icono vivía además duplicado con el de
        // ORIGIN_ICON en la cabecera del detalle del ticket.
        if (it.channel) {
            out += '<span class="tkt-tchip">' + escapeHtml(ORIGIN_LABELS[it.channel] || it.channel) + '</span>';
        }

        // Solo el caso confirmado por metadata (AutoResponseTicketCommand);
        // macros/programadas/automatizaciones no dejan rastro distinguible
        // hoy (ver comentario de TicketDetailDataController::data()).
        if (it.is_auto) {
            out += '<span class="tkt-tchip auto" title="Enviado automáticamente, sin intervención de un agente">Automático</span>';
        }

        return out;
    }

    /**
     * Acciones por mensaje. "Reenviar" solo en los salientes: reenviar algo
     * que escribió el cliente no significa nada.
     */
    function threadMsgActions(it) {
        // Sin correo detrás no hay nada que reenviar ni ninguna fuente que
        // enseñar (nota interna, mensaje de widget, evento): en vez de pintar
        // botones que darían error al pulsarlos, no se pintan.
        if (!it.mail_id) return '';

        var out = '<span class="tkt-tmsg-actions">';

        if (it.direction === 'outbound') {
            out += '<button type="button" class="tkt-btn-icon tkt-tmsg-act" data-thread-resend="' + it.mail_id + '" title="Reenviar este correo"><i class="fa-solid fa-rotate-right"></i></button>';
        }

        out += '<button type="button" class="tkt-btn-icon tkt-tmsg-act" data-thread-source="' + it.mail_id + '" title="Ver original"><i class="fa-solid fa-code"></i></button>';

        return out + '</span>';
    }

    /**
     * Línea de traza bajo un mensaje saliente ("entregado 10:42:11"). Solo se
     * pinta cuando el envío consta entregado o aceptado: sin confirmación no
     * se afirma nada.
     */
    function threadDelivery(it) {
        if (!it.delivery) return '';

        // Antes threadDelivery() del backend devolvía null también para
        // failed/bounced -- un correo que rebotó se veía IDÉNTICO a uno sin
        // ninguna confirmación todavía, el agente solo se enteraba abriendo
        // la pestaña Correo y pulsando "Ver rebote" (14-sep-2026). Ahora el
        // backend manda {label, at, error, failed} y aquí se pinta en tono
        // de error (negro/blanco, la única escala de "peligro" de esta
        // pantalla — ver --tkt-danger-* en tickets-app.css) con el motivo en
        // el title si el proveedor lo dio.
        var failed = Boolean(it.delivery.failed);
        var icon = failed ? 'fa-triangle-exclamation' : 'fa-check-double';
        var timeText = it.delivery.at ? ' ' + escapeHtml(it.delivery.at) : '';
        var titleAttr = (failed && it.delivery.error) ? ' title="' + escapeHtml(it.delivery.error) + '"' : '';

        return '<div class="tkt-tdelivery' + (failed ? ' error' : '') + '"' + titleAttr + '>' +
            '<i class="fa-solid ' + icon + '"></i> ' +
            escapeHtml(it.delivery.label) + timeText +
        '</div>';
    }

    /**
     * Chips de adjuntos con nombre y peso, enlazados al fichero real.
     *
     * Si el backend no pudo leer el tamaño (fichero purgado o disco caído)
     * llega sin él y el chip se pinta igual: saber que hubo un adjunto sigue
     * siendo información, aunque no se sepa cuánto pesaba.
     */
    function threadAttachments(it) {
        var files = it.attachments || [];
        if (!files.length) return '';

        // Antes: <a href target="_blank"> lisa y llana -- clic y se
        // descargaba/abría el fichero crudo, sin previsualización ni
        // metadatos en la propia app (mockup "ve-file-preview", modal 49).
        // data-att-index identifica CUÁL adjunto de it.attachments es, para
        // que el delegado de clic de renderThreadPane() pueda recuperar el
        // objeto completo (nombre/url/mime/tamaño) sin repetirlo en atributos.
        return '<div class="tkt-tatts"><span class="tkt-cap">Adjuntos</span>' +
            files.map(function (f, idx) {
                return '<span class="tkt-tatt" data-attachment-preview data-item-id="' + it.id + '" data-att-index="' + idx + '" title="' + escapeHtml(f.name) + '">' +
                    '<i class="fa-regular ' + attachmentIcon(f.name) + '"></i>' +
                    '<span class="n">' + escapeHtml(f.name) + '</span>' +
                    (f.size ? '<span class="s">' + escapeHtml(f.size) + '</span>' : '') +
                '</span>';
            }).join('') +
        '</div>';
    }

    /** Icono según extensión, como el mockup (PDF, imagen, hoja de cálculo…). */
    function attachmentIcon(name) {
        var ext = String(name).split('.').pop().toLowerCase();
        if (ext === 'pdf') return 'fa-file-pdf';
        if (['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg'].indexOf(ext) !== -1) return 'fa-file-image';
        if (['xls', 'xlsx', 'csv'].indexOf(ext) !== -1) return 'fa-file-excel';
        if (['doc', 'docx'].indexOf(ext) !== -1) return 'fa-file-word';
        if (['zip', 'rar', '7z'].indexOf(ext) !== -1) return 'fa-file-zipper';
        return 'fa-file-lines';
    }

    // Lleva el hilo hasta el último mensaje. Tres pasadas, cada vez más
    // tarde, en vez de una sola: verificado en vivo (DevTools) que un solo
    // requestAnimationFrame se quedaba ~60px corto — el ancho de columna
    // (sidebar "Gestión" con sus select2) se termina de asentar un instante
    // después, el texto del último mensaje reajusta su wrap y crece, y el
    // scroll ya fijado no se entera. La 2ª pasada (rAF anidado) cubre un
    // reflow dentro del mismo frame; el setTimeout final es la red para lo
    // que llega de verdad asíncrono (select2, fuentes web).
    function scrollThreadToBottom() {
        var el = document.getElementById('tkt-thread-scroll');
        if (!el) return;
        var pin = function () { el.scrollTop = el.scrollHeight; };
        requestAnimationFrame(function () {
            pin();
            requestAnimationFrame(pin);
        });
        setTimeout(pin, 300);
    }

    function threadIsNearBottom(el) {
        return !el || (el.scrollHeight - el.scrollTop - el.clientHeight) <= 72;
    }

    function applyThreadFilter($p, mode, query) {
        mode = mode || 'all';
        query = String(query == null ? '' : query).trim().toLowerCase();
        TKA.state.threadSearch = query;
        $p.find('[data-thread-filter]').removeClass('on');
        $p.find('[data-thread-filter="' + mode + '"]').addClass('on');
        $p.find('[data-thread-kind]').each(function () {
            var kind = $(this).data('thread-kind');
            var visible = mode === 'all'
                || (mode === 'customer' && kind === 'customer')
                || (mode === 'no-notes' && kind !== 'note')
                || (mode === 'no-events' && kind !== 'event');
            var hiddenHistory = $(this).is('[data-thread-old]') && !query;
            var searchText = String($(this).attr('data-thread-search-text') || $(this).text()).toLowerCase();
            $(this).toggle(Boolean(visible) && !hiddenHistory && (!query || searchText.indexOf(query) !== -1));
        });

        $p.find('[data-thread-kind="day"]').each(function () {
            var $siblings = $(this).nextUntil('[data-thread-kind="day"]');
            $(this).toggle($siblings.filter(':visible').length > 0);
        });
        $p.find('[data-thread-expand]').toggle(!query);
        var activeFilters = threadFilterState();
        var advancedActive = Object.keys(activeFilters).some(function (key) {
            return key === 'type' ? activeFilters[key] !== 'all' : Boolean(activeFilters[key]);
        });
        $p.find('[data-thread-no-results]').toggle((Boolean(query) || advancedActive) && $p.find('[data-thread-kind]:not([data-thread-kind="day"]):visible').length === 0);
    }

    function loadOlderThread() {
        var current = TKA.state.currentTicket;
        if (!current || !TKA.state.threadHasMore || TKA.state.threadLoadingMore) return;
        TKA.state.threadLoadingMore = true;
        var nextPage = (Number(TKA.state.threadPage) || 1) + 1;
        fetchDetailData(current, {
            threadPage: nextPage,
            threadFilters: TKA.state.threadFilters,
            threadSearch: TKA.state.threadSearch,
            appendThread: true,
        });
    }

    function renderThreadPane(items) {
        var $p = $('#tkt-dpane-thread');
        var currentTicket = TKA.state.currentTicket;
        var existingScroll = document.getElementById('tkt-thread-scroll');
        var hasExistingThread = !!existingScroll;
        var previousScrollTop = existingScroll ? existingScroll.scrollTop : 0;
        var previousScrollHeight = existingScroll ? existingScroll.scrollHeight : 0;
        var wasAtBottom = !existingScroll || threadIsNearBottom(existingScroll);
        var activeThreadFilter = $p.find('[data-thread-filter].on').data('thread-filter') || 'all';
        var activeThreadSearch = TKA.state.threadSearch || '';
        var activeThreadFilters = threadFilterState(TKA.state.threadFilters);
        var refreshMeta = TKA.state.threadRefreshMeta || {};
        var showNewMarker = hasExistingThread && refreshMeta.existing && refreshMeta.changed && !wasAtBottom && (refreshMeta.newMessages || 0) > 0;
        var newMessageCount = refreshMeta.newMessages || 0;
        // fetchDetailData() también reconstruye este pane cuando llega un
        // correo del cliente. Desacoplamos el composer antes de hacer
        // .html(): jQuery conservará handlers, selección, modo y adjuntos.
        // En la primera carga no existe todavía y se crea abajo normalmente.
        var $preservedComposer = TKA.state.preservedComposer;
        if (!$preservedComposer || !$preservedComposer.length) {
            $preservedComposer = $p.find('#tkt-composer').detach();
        }
        TKA.state.preservedComposer = null;
        // El composer debe quedar fijo abajo aunque el hilo sea largo — antes
        // thread-bar+mensajes+composer compartían el overflow-y:auto de
        // .tkt-pane, así que el composer se desplazaba fuera de la vista al
        // hacer scroll en vez de quedarse visible. Este wrapper aísla el
        // scroll a solo thread-bar+mensajes; composerHtml() se añade después,
        // fuera de él, como hermano (ver #tkt-dpane-thread/.tkt-thread-scroll
        // en tickets-app.css).
        var html = '<div class="tkt-thread-scroll" id="tkt-thread-scroll">';
        var lastDayKey = null;

        // Filtro del hilo (Todo/Solo cliente/Sin notas) — puramente
        // client-side sobre los datos ya cargados, sin refetch. Cada fila
        // lleva su tipo en data-thread-kind para poder ocultarla sin
        // volver a renderizar.
        html += '<div class="tkt-thread-bar">' +
            '<span class="tkt-cap">Conversación completa</span>' +
            '<div class="tkt-seg" id="tkt-thread-filter">' +
                '<button type="button" class="' + (activeThreadFilter === 'all' ? 'on' : '') + '" data-thread-filter="all">Todo</button>' +
                '<button type="button" class="' + (activeThreadFilter === 'customer' ? 'on' : '') + '" data-thread-filter="customer">Solo cliente</button>' +
                '<button type="button" class="' + (activeThreadFilter === 'no-notes' ? 'on' : '') + '" data-thread-filter="no-notes">Sin notas</button>' +
                '<button type="button" class="' + (activeThreadFilter === 'no-events' ? 'on' : '') + '" data-thread-filter="no-events">Sin eventos</button>' +
            '</div>' +
            '<label class="tkt-thread-search-wrap"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><input type="search" class="tkt-thread-search" id="tkt-thread-search" value="' + escapeHtml(activeThreadSearch) + '" placeholder="Buscar en el hilo" aria-label="Buscar en la conversación"></label>' +
            '<button type="button" class="tkt-btn tkt-btn-mini tkt-thread-advanced-toggle" id="tkt-thread-advanced-toggle" aria-expanded="false"><i class="fa-solid fa-sliders" aria-hidden="true"></i> Filtros</button>' +
            '<div class="tkt-thread-advanced" id="tkt-thread-advanced" hidden>' +
                '<label>Remitente<input type="search" id="tkt-thread-filter-sender" value="' + escapeHtml(activeThreadFilters.sender) + '" placeholder="Nombre o email"></label>' +
                '<label>Tipo<select id="tkt-thread-filter-type"><option value="all">Todos</option><option value="customer"' + (activeThreadFilters.type === 'customer' ? ' selected' : '') + '>Cliente</option><option value="agent"' + (activeThreadFilters.type === 'agent' ? ' selected' : '') + '>Agente</option><option value="note"' + (activeThreadFilters.type === 'note' ? ' selected' : '') + '>Nota interna</option><option value="event"' + (activeThreadFilters.type === 'event' ? ' selected' : '') + '>Evento</option></select></label>' +
                '<label>Desde<input type="date" id="tkt-thread-filter-from" value="' + escapeHtml(activeThreadFilters.from) + '"></label>' +
                '<label>Hasta<input type="date" id="tkt-thread-filter-to" value="' + escapeHtml(activeThreadFilters.to) + '"></label>' +
                '<label>Canal<select id="tkt-thread-filter-channel"><option value="">Todos</option><option value="email"' + (activeThreadFilters.channel === 'email' ? ' selected' : '') + '>Email</option><option value="formulario"' + (activeThreadFilters.channel === 'formulario' ? ' selected' : '') + '>Formulario</option><option value="widget"' + (activeThreadFilters.channel === 'widget' ? ' selected' : '') + '>Widget</option><option value="prestashop"' + (activeThreadFilters.channel === 'prestashop' ? ' selected' : '') + '>PrestaShop</option><option value="phone"' + (activeThreadFilters.channel === 'phone' ? ' selected' : '') + '>Teléfono</option></select></label>' +
                '<div class="tkt-thread-advanced-actions"><button type="button" class="tkt-btn tkt-btn-mini" id="tkt-thread-advanced-clear">Limpiar</button><button type="button" class="tkt-btn tkt-btn-mini tkt-btn-primary" id="tkt-thread-advanced-apply">Aplicar</button></div>' +
            '</div>' +
        '</div>';

        if (TKA.state.threadHasMore) {
            var loaded = (Number(TKA.state.threadPage) || 1) * (Number(TKA.state.threadPerPage) || 30);
            var remaining = Math.max(0, (Number(TKA.state.threadTotal) || loaded) - loaded);
            html += '<button type="button" class="tkt-thread-load-more" data-thread-load-more' + (TKA.state.threadLoadingMore ? ' disabled' : '') + '><i class="fa-solid fa-clock-rotate-left"></i> ' + (TKA.state.threadLoadingMore ? 'Cargando…' : 'Cargar mensajes anteriores' + (remaining ? ' · quedan ' + remaining : '')) + '</button>';
        }

        if (!items.length) {
            html += '<div class="tkt-empty-box">Este ticket todavía no tiene mensajes ni eventos.</div>';
        } else {
            items.forEach(function (it) {
                if (it.created_at && threadDayKey(it.created_at) !== lastDayKey) {
                    lastDayKey = threadDayKey(it.created_at);
                    html += '<div class="tkt-thread-day" data-thread-kind="day"><span>' + escapeHtml(threadDayLabel(it.created_at)) + '</span></div>';
                }
                if (it.type === 'message' && !it.is_internal) {
                    var mine = it.from_agent;
                    // Placeholder discreto cuando body es null/vacío y no es
                    // el caso ya cubierto de "adjunto sin texto" (ese llega
                    // aquí con body='(archivo adjunto)', ya no vacío) — antes
                    // se renderizaba una burbuja totalmente en blanco, sin
                    // ninguna pista de qué evento fue (visto en vivo en un
                    // mensaje de Sistema con body:null).
                    //
                    // it.is_html: antes esto era SIEMPRE escapeHtml(it.body),
                    // así que un mensaje con html_body real (cualquier correo
                    // entrante en HTML) salía con las etiquetas literales en
                    // pantalla en vez de renderizarse (detectado 3-sep-2026,
                    // TCK-2026-00093). TicketDetailDataController ya manda
                    // it.body purificado (mismo saneador que la "ficha
                    // completa") cuando is_html es true, así que aquí es
                    // seguro inyectarlo tal cual.
                    var bubbleHtml = it.body ? (it.is_html ? it.body : escapeHtml(it.body)) : '<em class="tkt-mute">(sin contenido)</em>';
                    // TranslateIncomingTicketMessage ya calcula translated_body/
                    // source_language_name para cada mensaje del cliente en un
                    // idioma distinto al del agente (ver TicketDetailDataController),
                    // pero este panel nunca lo mostraba -- el agente no se
                    // enteraba de que había una traducción disponible (detectado
                    // 3-sep-2026 probando el flujo real con un mensaje en inglés).
                    var translationHtml = it.translated_body ?
                        '<div class="tkt-tmsg-translation">' +
                            '<i class="fa-solid fa-language"></i> Traducido automáticamente del ' + escapeHtml(it.source_language_name || '') +
                            '<div class="tkt-tmsg-translation-text">' + escapeHtml(it.translated_body).replace(/\n/g, '<br>') + '</div>' +
                        '</div>' : '';
                    // Un mensaje de "Sistema" (auto-respuesta, notificación
                    // automática) llevaba las mismas iniciales que cualquier
                    // otro remitente — con el fix de role (arriba) ahora se
                    // distingue también por avatar, no solo por el chip.
                    var isSystemSender = it.role === 'Sistema';
                    var avatarInner = isSystemSender ? '<i class="fa-solid fa-gear"></i>' : initials(it.sender_name);
                    html += '<div class="tkt-tmsg" data-thread-kind="' + (mine ? 'agent' : 'customer') + '">' +
                        '<div class="tkt-tmsg-head">' +
                            '<span class="tkt-tmsg-avatar' + (isSystemSender ? ' system' : '') + '">' + avatarInner + '</span>' +
                            '<span class="tkt-tmsg-who">' + escapeHtml(it.sender_name) + '</span>' +
                            threadChips(it) +
                            '<span class="tkt-tmsg-time">' + escapeHtml(it.time || it.created_at_human || '') + '</span>' +
                            threadMsgActions(it) +
                        '</div>' +
                        '<div class="tkt-tmsg-body">' + bubbleHtml + '</div>' +
                        translationHtml +
                        threadAttachments(it) +
                        threadDelivery(it) +
                    '</div>';
                } else if (it.is_internal) {
                    // Nota interna: mismo formato de tarjeta que el mensaje
                    // pero con borde marcado y el aviso de que el cliente no
                    // la ve — es la diferencia que hay que poder leer de un
                    // vistazo antes de escribir algo comprometido.
                    html += '<div class="tkt-tnote" data-thread-kind="note">' +
                        '<div class="tkt-tmsg-head">' +
                            '<span class="tkt-tnote-icon"><i class="fa-solid fa-lock"></i></span>' +
                            '<span class="tkt-tmsg-who">Nota interna · ' + escapeHtml(it.sender_name) + '</span>' +
                            '<span class="tkt-tchip warn">No visible al cliente</span>' +
                            '<span class="tkt-tmsg-time">' + escapeHtml(it.time || it.created_at_human || '') + '</span>' +
                        '</div>' +
                        '<div class="tkt-tmsg-body">' + escapeHtml(it.body || '') + '</div>' +
                        threadAttachments(it) +
                    '</div>';
                } else {
                    // title con el ISO-8601 completo (it.created_at) además
                    // del texto humanizado — sin esto, dos eventos reales y
                    // distintos separados por <1 min (p.ej. dos "Ticket
                    // closed" reales) son indistinguibles a simple vista
                    // porque ambos redondean a la misma etiqueta relativa
                    // ("hace 3 semanas").
                    html += '<div class="tkt-tevent" data-thread-kind="event">' +
                        '<span class="tkt-tevent-icon"><i class="fa-solid ' + (EVENT_ICONS[it.type] || 'fa-circle-info') + '"></i></span>' +
                        '<span class="tkt-tevent-text">' + escapeHtml(it.body || it.type) + '</span>' +
                        // El ISO completo en el title: dos eventos reales
                        // separados por <1 min redondean a la misma etiqueta
                        // relativa y serían indistinguibles.
                        '<span class="tkt-tevent-time" title="' + escapeHtml(it.created_at || '') + '">' + escapeHtml(it.time || it.created_at_human || '') + '</span>' +
                    '</div>';
                }
            });
        }

        if (showNewMarker) {
            html += '<button type="button" class="tkt-thread-new-marker" data-thread-new role="status">' +
                '<i class="fa-solid fa-arrow-down"></i> ' +
                (newMessageCount > 1 ? newMessageCount + ' mensajes nuevos' : 'Mensaje nuevo') +
                '</button>';
        }

        html += '</div>'; // cierra .tkt-thread-scroll

        // Composer del mockup: cuatro pestañas de modo, la franja del
        // borrador sugerido por IA, el textarea y la barra de herramientas.
        // Reusa los mismos endpoints que ya existían
        // (TicketMessagingController::storeMessage con adjuntos multipart,
        // TicketCannedReply para plantillas, MacroApplyController para
        // macros) — cambia la superficie, no el backend. Fuera de
        // .tkt-thread-scroll a propósito: es el hermano fijo que no escrolea.
        html += composerHtml(currentTicket);

        $p.html(html);
        $p.find('[data-thread-kind]:not([data-thread-kind="day"])').each(function () {
            $(this).attr('data-thread-search-text', $(this).text().replace(/\s+/g, ' ').trim().toLowerCase());
        });

        if ($preservedComposer.length && currentTicket &&
            String($preservedComposer.attr('data-ticket-id')) === String(currentTicket.id)) {
            $('#tkt-composer').replaceWith($preservedComposer);
        }

        var $threadScroll = $('#tkt-thread-scroll');
        var $threadItems = $threadScroll.children('.tkt-tmsg, .tkt-tnote, .tkt-tevent');
        if (!TKA.state.threadHistoryExpanded && $threadItems.length > TKT_THREAD_VISIBLE_LIMIT) {
            var collapseCount = $threadItems.length - TKT_THREAD_VISIBLE_LIMIT;
            $threadItems.slice(0, collapseCount).attr('data-thread-old', '1').hide();
            $threadItems.eq(collapseCount).before('<button type="button" class="tkt-thread-expand" data-thread-expand><i class="fa-solid fa-clock-rotate-left"></i> Mostrar ' + collapseCount + ' elementos anteriores</button>');
        }

        // El marcador flotante se posiciona por encima del composer fijo y no
        // altera la altura del hilo. Solo se muestra cuando el agente se aleja
        // del último mensaje.
        var positionLatestMarker = function () {
            var $composer = $p.find('#tkt-composer');
            $p.css('--tkt-composer-height', (($composer.outerHeight() || 0) + 20) + 'px');
        };
        positionLatestMarker();

        // En la primera carga se arranca viendo el mensaje más reciente. En un
        // refresco automático solo se baja si el agente ya estaba abajo; si
        // estaba leyendo el historial, se conserva su posición y aparece un
        // marcador discreto para saltar a lo nuevo.
        if (wasAtBottom || !hasExistingThread) {
            scrollThreadToBottom();
        } else {
            var restoredScroll = document.getElementById('tkt-thread-scroll');
            if (restoredScroll) {
                restoredScroll.scrollTop = refreshMeta.prepended
                    ? previousScrollTop + Math.max(0, restoredScroll.scrollHeight - previousScrollHeight)
                    : Math.min(previousScrollTop, restoredScroll.scrollHeight);
            }
        }

        $('#tkt-thread-filter [data-thread-filter]').on('click', function () {
            var mode = $(this).data('thread-filter');
            applyThreadFilter($p, mode, TKA.state.threadSearch);
        });
        $p.find('.tkt-thread-bar').append('<span class="tkt-thread-no-results" data-thread-no-results style="display:none">No hay coincidencias</span>');
        $p.off('input.tktThreadSearch').on('input.tktThreadSearch', '#tkt-thread-search', function () {
            var value = String(this.value || '').trim();
            applyThreadFilter($p, activeThreadFilter, value);
            clearTimeout(TKA.state.threadSearchTimer);
            TKA.state.threadSearchTimer = setTimeout(function () {
                var current = TKA.state.currentTicket;
                if (current && String(TKA.state.threadSearch || '') === value) {
                    fetchDetailData(current, { threadSearch: value });
                }
            }, 350);
        });
        $p.off('click.tktThreadAdvanced').on('click.tktThreadAdvanced', '#tkt-thread-advanced-toggle', function () {
            var open = !$('#tkt-thread-advanced').prop('hidden');
            $('#tkt-thread-advanced').prop('hidden', open);
            $(this).attr('aria-expanded', open ? 'false' : 'true');
        }).on('click.tktThreadAdvanced', '#tkt-thread-advanced-apply', function () {
            var filters = threadFilterState({
                sender: $('#tkt-thread-filter-sender').val(),
                type: $('#tkt-thread-filter-type').val(),
                from: $('#tkt-thread-filter-from').val(),
                to: $('#tkt-thread-filter-to').val(),
                channel: $('#tkt-thread-filter-channel').val(),
            });
            TKA.state.threadFilters = filters;
            TKA.state.threadPage = 1;
            TKA.state.threadHasMore = false;
            if (TKA.state.currentTicket) fetchDetailData(TKA.state.currentTicket, { threadPage: 1, threadFilters: filters });
        }).on('click.tktThreadAdvanced', '#tkt-thread-advanced-clear', function () {
            var filters = { sender: '', type: 'all', from: '', to: '', channel: '' };
            TKA.state.threadFilters = filters;
            TKA.state.threadPage = 1;
            TKA.state.threadHasMore = false;
            if (TKA.state.currentTicket) fetchDetailData(TKA.state.currentTicket, { threadPage: 1, threadFilters: filters });
        }).on('click.tktThreadAdvanced', '[data-thread-load-more]', function () {
            loadOlderThread();
        });
        applyThreadFilter($p, activeThreadFilter, activeThreadSearch);

        $p.off('click.tktThreadNew').on('click.tktThreadNew', '[data-thread-new]', function () {
            TKA.state.threadUnread = 0;
            $(this).remove();
            $p.find('[data-thread-latest]').remove();
            scrollThreadToBottom();
        });

        $p.on('click.tktThreadNew', '[data-thread-latest]', function () {
            $(this).remove();
            scrollThreadToBottom();
        });
        $p.on('click.tktThreadNew', '[data-thread-expand]', function () {
            TKA.state.threadHistoryExpanded = true;
            $p.find('[data-thread-old]').removeAttr('data-thread-old').show();
            $(this).remove();
            applyThreadFilter($p, activeThreadFilter, TKA.state.threadSearch);
        });

        $threadScroll.off('scroll.tktLatest').on('scroll.tktLatest', function () {
            if (this.scrollTop < 80 && TKA.state.threadHasMore && !TKA.state.threadLoadingMore) loadOlderThread();
            var away = !threadIsNearBottom(this);
            var hasNewMarker = $p.find('[data-thread-new]').length > 0;
            if (away && this.scrollHeight > this.clientHeight && !hasNewMarker && !$p.find('[data-thread-latest]').length) {
                $p.append('<button type="button" class="tkt-thread-latest-marker" data-thread-latest><i class="fa-solid fa-arrow-down"></i> Ir al último mensaje</button>');
                positionLatestMarker();
            } else if (!away) {
                $p.find('[data-thread-latest]').remove();
            }
        });

        // ── Acciones por mensaje del hilo ──
        // Reenviar pide confirmación: manda un correo real al cliente y no hay
        // forma de retirarlo.
        $p.on('click', '[data-thread-resend]', function () {
            var mailId = $(this).data('thread-resend');

            // openConfirmModal, no window.confirm: el diálogo nativo ya se
            // retiró de esta pantalla en QA por romper el estilo y por dejar
            // el navegador bloqueado (ver el comentario de esa función).
            openConfirmModal({
                icon: 'fa-solid fa-rotate-right',
                title: 'Reenviar este correo',
                message: 'Se reenviará a su destinatario original. Es un correo real y no se puede retirar una vez enviado.',
                confirmLabel: 'Reenviar',
                onConfirm: function () {
                    // El CSRF lo añade el $.ajaxSetup global del layout, igual
                    // que en el resto de llamadas de este archivo.
                    $.ajax({
                        url: TKA.urls.mailResendTemplate.replace('__MAIL__', mailId),
                        method: 'POST',
                        headers: { Accept: 'application/json' },
                    }).done(function (res) {
                        var msg = (res && res.message) ? res.message : 'Correo reenviado.';
                        if (window.toastr) toastr.success(msg); else window.alert(msg);
                    }).fail(function (xhr) {
                        var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'No se pudo reenviar el correo.';
                        if (window.toastr) toastr.error(msg); else window.alert(msg);
                    });
                },
            });
        });

        // Ver original: abre el correo en la pestaña "Correo", que ya sabe
        // pintar cabeceras, cuerpo y fuente — en vez de duplicar ese visor.
        $p.on('click', '[data-thread-source]', function () {
            selectDetailTab('mail');
        });

        // Previsualizar adjunto (modal 49 del mockup) — reusa
        // openFilePreviewModal(), que ya existe para la pestaña Adjuntos
        // (renderFilesPane()) y que no tenía ningún punto de entrada desde
        // aquí, el hilo. Se adapta la forma del objeto (bytes en vez del
        // tamaño ya formateado que usa el chip del hilo, url_download en vez
        // de url) en vez de duplicar el modal con su propio CSS/markup.
        $p.on('click', '[data-attachment-preview]', function () {
            var itemId = $(this).data('item-id');
            var idx = $(this).data('att-index');
            var item = items.filter(function (it) { return it.id === itemId; })[0];
            var f = item && (item.attachments || [])[idx];
            if (!f) return;

            openFilePreviewModal(TKA.state.currentTicket, {
                name: f.name,
                url_download: f.url,
                size: f.bytes,
                source: item.from_agent ? 'agent' : 'customer',
                created_at_human: item.created_at_human || item.time,
            });
        });

        if (!$('#tkt-composer').is($preservedComposer)) bindComposer($p);
    }

    // ── Composer del hilo ────────────────────────────────────────────
    // Cuatro modos excluyentes (respuesta / nota interna / plantillas /
    // traducir), la franja del borrador de IA y una barra de herramientas.
    // El modo vive en data-mode del contenedor, no en variables sueltas:
    // sendReply() lo lee para decidir si crea un mensaje público o una nota.

    function composerHtml(t) {
        return '<div class="tkt-composer" id="tkt-composer" data-ticket-id="' + escapeHtml(t && t.id != null ? String(t.id) : '') + '" data-mode="reply">' +
            '<div class="tkt-comp-tabs">' +
                // Con texto, sin icono (mismo criterio que el resto de la
                // pantalla). El indicador de idioma no es un botón, se deja
                // con icono como el resto de chips de estado.
                //
                // Una sola barra: antes "Plantillas" y "Traducir" vivían aquí
                // Y también, duplicados, como "Plantilla"/"Traducir" en una
                // fila de herramientas aparte bajo el textarea — el agente
                // tenía que mirar dos sitios para lo mismo. Ahora toda la
                // barra de acciones vive en un solo lugar, arriba.
                '<button type="button" class="tkt-comp-tab on" data-comp-mode="reply">Respuesta</button>' +
                (featureEnabled('composer_note') ? '<button type="button" class="tkt-comp-tab" data-comp-mode="note">Nota interna</button>' : '') +
                (featureEnabled('composer_templates') ? '<button type="button" class="tkt-comp-tab" data-comp-act="templates">Plantillas</button>' : '') +
                (featureEnabled('composer_translate') ? '<button type="button" class="tkt-comp-tab" data-comp-act="translate">Traducir</button>' : '') +
                (featureEnabled('composer_attach') ? '<label class="tkt-comp-tool" title="Adjuntar archivo">Adjuntar<input type="file" id="tkt-reply-attach" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif,.zip,.rar,.txt" hidden></label><span class="tkt-comp-attach-count" id="tkt-reply-attach-count"></span>' : '') +
                (featureEnabled('composer_macros') ? '<button type="button" class="tkt-comp-tool" data-comp-act="macros">Macros</button>' : '') +
                (featureEnabled('composer_followup') ? '<button type="button" class="tkt-comp-tool" data-comp-act="followup">Automático</button>' : '') +
                (featureEnabled('composer_ai') ? '<button type="button" class="tkt-comp-tool" data-comp-act="ai">IA</button>' : '') +
                (featureEnabled('composer_mention') ? '<button type="button" class="tkt-comp-tool" data-comp-act="mention">Mencionar</button>' : '') +
                '<span class="tkt-comp-lang" id="tkt-comp-lang" hidden><i class="fa-solid fa-language"></i> <span></span></span>' +
                '<span class="tkt-comp-draft-status" id="tkt-draft-status" role="status" aria-live="polite"></span>' +
                '<span class="tkt-comp-send-group">' +
                    (featureEnabled('composer_schedule') ? '<button type="button" class="tkt-comp-schedule" data-comp-act="schedule">Programar</button>' : '') +
                    '<button type="button" class="tkt-comp-send" id="tkt-reply-send">Enviar <span class="tkt-comp-kbd">' + sendShortcutLabel() + '</span></button>' +
                '</span>' +
            '</div>' +
            '<div class="tkt-comp-ai" id="tkt-comp-ai" hidden>' +
                '<span class="tkt-comp-ai-icon"><i class="fa-solid fa-wand-magic-sparkles"></i></span>' +
                '<div class="tkt-comp-ai-main">' +
                    '<div class="tkt-comp-ai-head">' +
                        '<span class="tkt-comp-ai-title">Borrador sugerido</span>' +
                        '<span class="tkt-comp-ai-chip" id="tkt-comp-ai-tpl" hidden></span>' +
                        '<span class="tkt-comp-ai-chip" id="tkt-comp-ai-conf" hidden></span>' +
                    '</div>' +
                    '<div class="tkt-comp-ai-text" id="tkt-comp-ai-text"></div>' +
                '</div>' +
                '<span class="tkt-comp-ai-actions">' +
                    '<button type="button" class="tkt-comp-ai-use" id="tkt-comp-ai-use">Usar</button>' +
                    '<button type="button" class="tkt-comp-ai-edit" id="tkt-comp-ai-edit">Editar</button>' +
                    '<button type="button" class="tkt-comp-ai-drop" id="tkt-comp-ai-drop" aria-label="Descartar el borrador sugerido"><i class="fa-solid fa-xmark"></i></button>' +
                '</span>' +
            '</div>' +
            '<div class="tkt-relative">' +
                '<textarea id="tkt-reply-body" class="tkt-comp-body" rows="3" placeholder="Escribe tu respuesta… (/ para respuestas rápidas, @ para mencionar)" aria-label="Cuerpo de la respuesta o nota interna"></textarea>' +
                // Se abre HACIA ARRIBA (bottom:100%, no top:100% como el de
                // menciones de la nota interna): este composer suele vivir
                // pegado al fondo de la pantalla, un dropdown hacia abajo
                // quedaría cortado por el viewport.
                '<div class="tkt-drop tkt-drop-up" id="tkt-reply-tpl-drop" style="bottom:100%;left:0;right:0;margin-bottom:6px;max-height:260px;overflow:auto" hidden></div>' +
                // "@" para mencionar — el placeholder ya lo anunciaba, como
                // "/", pero el botón "Mencionar" solo insertaba el símbolo
                // sin sugerir a quién. Mismo bindMentionAutocomplete() que ya
                // usan las notas internas del sidebar, mismo criterio
                // "hacia arriba" que el de plantillas de aquí al lado.
                '<div class="tkt-drop tkt-drop-up" id="tkt-reply-mention-drop" style="bottom:100%;left:0;right:0;margin-bottom:6px;max-height:200px;overflow:auto" hidden></div>' +
            '</div>' +
        '</div>';
    }

    // ⌘↵ en Mac, Ctrl+↵ en el resto. El mockup se dibujó en Mac y muestra
    // ⌘↵ fijo; anunciar un atajo que no existe en Windows es peor que
    // apartarse del mockup en dos caracteres.
    function sendShortcutLabel() {
        return /Mac|iPhone|iPad/.test(navigator.platform || navigator.userAgent) ? '⌘↵' : 'Ctrl+↵';
    }

    function composerIsNote() {
        return $('#tkt-composer').attr('data-mode') === 'note';
    }

    function bindComposer($p) {
        var $c = $('#tkt-composer');
        var $body = $('#tkt-reply-body');
        var t = TKA.state.currentTicket;
        var draft = readComposerDraft(t);

        // "/" para respuestas rápidas: el placeholder lo anuncia desde
        // siempre pero nunca estuvo conectado a nada — solo abría el modal
        // completo desde el botón "Plantillas". Arranca con la lista en
        // bruto (misma que usa el modal) y en cuanto responde
        // url_canned_replies se sustituye por la interpolada contra este
        // ticket, igual que hace openTemplatesModal().
        var templatesForSlash = (TKA.state.cannedReplies || []).slice();
        if (t && t.url_canned_replies) {
            $.getJSON(t.url_canned_replies).done(function (resolved) {
                if (resolved) templatesForSlash = resolved;
            });
        }
        bindTemplateAutocomplete($body, $('#tkt-reply-tpl-drop'), function () { return templatesForSlash; });

        // "@" para mencionar: el placeholder lo anuncia igual que "/", pero
        // hasta ahora el botón "Mencionar" solo insertaba el símbolo sin
        // sugerir nombres — la única mención con autocompletado real vivía
        // en la nota interna del sidebar. Mismo bindMentionAutocomplete().
        bindMentionAutocomplete($body, $('#tkt-reply-mention-drop'));

        $c.on('click', '[data-comp-mode]', function () {
            var mode = $(this).data('comp-mode');
            $c.attr('data-mode', mode).toggleClass('is-note', mode === 'note');
            $c.find('[data-comp-mode]').removeClass('on');
            $(this).addClass('on');
            // El botón dice lo que va a pasar de verdad: una nota interna no
            // se "envía" a nadie.
            $('#tkt-reply-send').html(mode === 'note'
                ? '<i class="fa-solid fa-lock"></i> Guardar nota'
                : '<i class="fa-solid fa-paper-plane"></i> Enviar <span class="tkt-comp-kbd">' + sendShortcutLabel() + '</span>');
            $body.attr('placeholder', mode === 'note'
                ? 'Nota interna: el cliente no la verá… (@ para mencionar)'
                : 'Escribe tu respuesta… (/ para respuestas rápidas, @ para mencionar)');
            scheduleComposerDraftSave();
        });

        if (draft && !$body.val().trim()) {
            $body.val(draft.body);
            if (draft.mode === 'note') $c.find('[data-comp-mode="note"]').trigger('click');
            autoResizeTextarea($body[0]);
            $c.addClass('is-expanded');
        }
        updateDraftStatus($c, draft, false);

        $c.on('click', '[data-comp-act]', function () {
            switch ($(this).data('comp-act')) {
                case 'templates': openTemplatesModal(t); break;
                case 'macros': openMacrosModal(t); break;
                case 'followup': openFollowupModal(t); break;
                case 'ai': openAiDraftModal(t); break;
                case 'translate': openTranslateModal(t, false); break;
                case 'mention': tktInsertAtCursor($body, '@'); $body.trigger('focus').trigger('input'); break;
                case 'schedule': openScheduleModal(t); break;
            }
        });

        $('#tkt-reply-attach').on('change', function () {
            var files = Array.prototype.slice.call(this.files || []);
            var invalid = files.find(function (file) {
                var extension = String(file.name || '').split('.').pop().toLowerCase();
                return file.size > TKT_ATTACHMENT_MAX_BYTES || TKT_ATTACHMENT_EXTENSIONS.indexOf(extension) === -1;
            });
            if (files.length > 10 || invalid) {
                this.value = '';
                $('#tkt-reply-attach-count').text('');
                var reason = files.length > 10 ? 'Puedes adjuntar como máximo 10 archivos.' : 'El archivo debe ser un formato permitido y no superar ' + Math.round(TKT_ATTACHMENT_MAX_BYTES / 1024 / 1024 * 100) / 100 + ' MB.';
                if (window.toastr) toastr.warning(reason, 'Adjunto no válido'); else window.alert(reason);
                return;
            }
            var n = files.length;
            $('#tkt-reply-attach-count').text(n ? n + ' archivo(s)' : '');
        });

        $('#tkt-reply-send').on('click', sendReply);

        var typingTimer;
        $body.on('focus.tktComposer', function () {
            $c.addClass('is-expanded');
        });
        $body.on('blur.tktComposer', function () {
            setTimeout(function () {
                if (!$body.val().trim()) $c.removeClass('is-expanded');
            }, 0);
        });
        $body.on('input', function () {
            autoResizeTextarea(this);
            if (this.value.trim()) $c.addClass('is-expanded');
            scheduleComposerDraftSave();
            if (composerIsNote()) return; // una nota interna no es "está escribiendo"
            clearTimeout(typingTimer);
            emitTyping(true);
            typingTimer = setTimeout(function () { emitTyping(false); }, 2500);
        });

        // ⌘/Ctrl+Enter envía. Enter a secas hace salto de línea: una
        // respuesta a un cliente casi nunca es de una sola línea.
        $body.on('keydown', function (ev) {
            if (ev.key === 'Enter' && (ev.metaKey || ev.ctrlKey)) {
                ev.preventDefault();
                sendReply();
            }
        });

        bindComposerAi($c, $body);
        requestAiDraft(TKA.state.currentTicket, false);
    }

    // Las macros no tienen modal propio: se despliegan en un <select> nativo
    // que se crea al vuelo junto al botón. Reusa loadMacrosInto/applyMacro,
    // que ya hablan con MacroApplyController.
    function openComposerMacros($btn) {
        var $existing = $('#tkt-comp-macro');
        if ($existing.length) { $existing.remove(); return; }
        var $sel = $('<select id="tkt-comp-macro" class="tkt-fselect tkt-comp-macro-select" aria-label="Aplicar macro"><option value="">/ macros…</option></select>');
        $btn.after($sel);
        loadMacrosInto($sel);
        $sel.on('change', function () {
            var macroId = $(this).val();
            if (!macroId) return;
            applyMacro(TKA.state.currentTicket, macroId);
            $sel.remove();
        });
    }

    function tktInsertAtCursor($el, text) {
        var el = $el[0];
        var start = el.selectionStart || 0;
        var end = el.selectionEnd || 0;
        el.value = el.value.slice(0, start) + text + el.value.slice(end);
        el.selectionStart = el.selectionEnd = start + text.length;
    }

    function bindComposerAi($c, $body) {
        // "Usar" reemplaza el borrador; "Editar" lo pega y deja el foco
        // dentro para retocarlo. Ninguno de los dos envía nada.
        $('#tkt-comp-ai-use').on('click', function () {
            $body.val(TKA.state.aiDraft || '');
            autoResizeTextarea($body[0]);
            $('#tkt-comp-ai').attr('hidden', true);
        });
        $('#tkt-comp-ai-edit').on('click', function () {
            $body.val(TKA.state.aiDraft || '');
            autoResizeTextarea($body[0]);
            $('#tkt-comp-ai').attr('hidden', true);
            $body.trigger('focus');
        });
        $('#tkt-comp-ai-drop').on('click', function () {
            $('#tkt-comp-ai').attr('hidden', true);
        });
    }

    // Borrador sugerido real (TicketAiSuggestionController::suggestReply →
    // TicketReplySuggestionService). Sin agente de IA configurado el endpoint
    // responde 200 con suggestion:null y aquí simplemente no se muestra nada:
    // la franja es un extra, no puede romper el composer.
    function requestAiDraft(t, refresh) {
        if (!t || !t.url_ai_suggest_reply) return;
        var $box = $('#tkt-comp-ai');
        if (refresh) {
            $box.removeAttr('hidden');
            $('#tkt-comp-ai-text').text('Generando un borrador…');
            $('#tkt-comp-ai-tpl, #tkt-comp-ai-conf').attr('hidden', true);
        }
        $.ajax({
            url: t.url_ai_suggest_reply,
            method: 'POST',
            data: { refresh: refresh ? 1 : 0 },
            headers: { Accept: 'application/json' },
        }).done(function (res) {
            var s = res && res.suggestion;
            if (!s || !s.draft) {
                $box.attr('hidden', true);
                if (refresh && window.toastr) toastr.info((res && res.message) || 'No hay ninguna sugerencia disponible.');
                return;
            }
            TKA.state.aiDraft = s.draft;
            $('#tkt-comp-ai-text').text(s.draft);
            if (s.template && s.template.name) {
                $('#tkt-comp-ai-tpl').text(s.template.kind + ' ' + s.template.name).removeAttr('hidden');
            }
            if (typeof s.confidence === 'number') {
                $('#tkt-comp-ai-conf').text('confianza ' + Math.round(s.confidence * 100) + ' %').removeAttr('hidden');
            }
            if (s.language) {
                $('#tkt-comp-lang').removeAttr('hidden').find('span').text('responde en ' + String(s.language).toUpperCase());
            }
            $box.removeAttr('hidden');
            // La franja recién apareció y encogió el hueco del hilo — solo
            // en la carga automática (no cuando el agente pulsa "Refrescar"
            // a propósito, que no debería moverle la vista sin avisar).
            if (!refresh) scrollThreadToBottom();
        }).fail(function () {
            $box.attr('hidden', true);
            if (refresh && window.toastr) toastr.error('No se pudo generar el borrador.');
        });
    }


    // ── Lista negra desde el ticket ───────────────────────────
    // Ajustes › Lista negra ya dejaba dar de alta reglas a mano, pero el spam
    // se decide con el ticket delante: bloquear ESE correo, TODO el dominio, o
    // las dos cosas, y quitar el ticket de en medio en el mismo gesto.
    function openBlacklistModal(t) {
        var email = (t.customer && t.customer.email) ? String(t.customer.email) : '';

        if (!email || email.indexOf('@') === -1) {
            if (window.toastr) toastr.info('Este ticket no tiene correo de cliente que bloquear.');
            return;
        }

        var dominio = email.split('@').pop();

        var $backdrop = openModal(modalShell({
            icon: 'fa-solid fa-ban', iconClass: 'danger', kicker: 'Remitente · lista negra',
            title: 'Pasar a lista negra', titleChip: t.ticket_number, width: 'lg',
            body: '<div class="tkt-note"><i class="fa-solid fa-circle-info"></i>' +
                    '<span>Los correos que lleguen desde lo que bloquees dejarán de abrir tickets. ' +
                    'La regla se puede quitar luego en <strong>Ajustes › Tickets › Lista negra</strong>.</span>' +
                  '</div>' +
                  '<div class="tkt-cap">Qué se bloquea</div>' +
                  '<label class="tkt-option on" id="tkt-bl-opt-email">' +
                      '<input type="checkbox" id="tkt-bl-email" checked>' +
                      '<span class="tkt-option-body">' +
                          '<span class="tkt-option-title">Solo este correo</span>' +
                          '<span class="tkt-option-sub mono">' + escapeHtml(email) + '</span>' +
                      '</span>' +
                  '</label>' +
                  '<label class="tkt-option" id="tkt-bl-opt-domain">' +
                      '<input type="checkbox" id="tkt-bl-domain">' +
                      '<span class="tkt-option-body">' +
                          '<span class="tkt-option-title">Todo el dominio</span>' +
                          '<span class="tkt-option-sub mono">@' + escapeHtml(dominio) + '</span>' +
                      '</span>' +
                  '</label>' +
                  '<div class="tkt-field"><label for="tkt-bl-reason">Motivo <span class="hint">queda registrado en la regla</span></label>' +
                      '<input type="text" class="tkt-input" id="tkt-bl-reason" maxlength="255" placeholder="Ej: spam reiterado">' +
                  '</div>' +
                  '<label class="tkt-unify-notify"><input type="checkbox" id="tkt-bl-delete" checked> ' +
                      'Eliminar este ticket al bloquear</label>',
            foot: '<button type="button" class="tkt-btn tkt-btn-danger" id="tkt-bl-go">Bloquear</button>' +
                  '<button type="button" class="tkt-btn" data-modal-close>Cancelar</button>',
        }));

        // Resalte de la opción marcada, igual que el resto de .tkt-option.
        $backdrop.on('change', '#tkt-bl-email, #tkt-bl-domain', function () {
            $(this).closest('.tkt-option').toggleClass('on', this.checked);
        });

        $backdrop.on('click', '#tkt-bl-go', function () {
            var $btn = $(this);
            var email = $('#tkt-bl-email').is(':checked');
            var dom = $('#tkt-bl-domain').is(':checked');

            if (!email && !dom) {
                if (window.toastr) toastr.error('Elige si bloquear el correo, el dominio o ambos.');
                return;
            }

            $btn.prop('disabled', true).text('Bloqueando…');

            $.ajax({
                url: t.url_blacklist,
                method: 'POST',
                data: {
                    block_email: email ? 1 : 0,
                    block_domain: dom ? 1 : 0,
                    reason: $('#tkt-bl-reason').val(),
                    delete_ticket: $('#tkt-bl-delete').is(':checked') ? 1 : 0,
                },
                headers: { Accept: 'application/json' },
            }).done(function (res) {
                closeModal();
                if (window.toastr) toastr.success(res.message || 'Remitente bloqueado.');
                // Si el ticket se ha borrado, la fila y el detalle abiertos ya
                // no valen: se recarga, como el resto de acciones destructivas.
                if (res.ticket_deleted) window.location.reload();
            }).fail(function (xhr) {
                var msg = apiErrorMessage(xhr, 'No se pudo bloquear el remitente.');
                if (window.toastr) toastr.error(msg); else window.alert(msg);
                $btn.prop('disabled', false).text('Bloquear');
            });
        });
    }

    // ── Unificar duplicados (v2) ──────────────────────────────
    // El modal 46 (v1) sigue donde estaba: fusiona de uno en uno y es
    // destructivo. Esta versión resuelve el caso de varios correos del mismo
    // cliente el mismo día: resumen de cada ticket, se conserva el más reciente
    // (editable), el resto se cierran —no se borran— y el cliente recibe un
    // correo con el resumen diciéndole en qué número se le responde.
    function openUnifyModal(t) {
        if (!t || !t.url_unify_summary) return;

        var $backdrop = openModal(modalShell({
            icon: 'fa-solid fa-object-group', kicker: 'Duplicados · unificar',
            title: 'Unificar tickets del cliente', titleChip: t.ticket_number, width: '2xl',
            body: '<div class="tkt-skeleton"></div>',
            foot: '<button type="button" class="tkt-btn" data-modal-close>Cancelar</button>',
        }));

        $.getJSON(t.url_unify_summary).done(function (res) {
            var lista = (res && res.tickets) || [];

            if (lista.length < 2) {
                $backdrop.find('.tkt-modal-body').html(
                    '<div class="tkt-empty-box">No hay otros tickets abiertos de este cliente que unificar.</div>'
                );
                return;
            }

            // Se guarda para poder repintar al cambiar el superviviente sin
            // volver a pedir el resumen entero.
            TKA.state.unifyData = res;
            renderUnifyBody($backdrop, t, res, lista, res.suggested_survivor_id);
        }).fail(function () {
            $backdrop.find('.tkt-modal-body').html(
                '<div class="tkt-empty-box">No se pudo cargar el resumen de los tickets.</div>'
            );
        });
    }

    function renderUnifyBody($backdrop, t, res, lista, survivorId, seleccionados) {
        // Qué tickets se unifican. Por defecto todos menos el que se conserva,
        // pero es EDITABLE: si el cliente mandó cuatro correos y tres son de una
        // pregunta y el cuarto de otra, unificar los cuatro sería peor que no
        // unificar nada.
        var marcados = seleccionados || lista
            .filter(function (x) { return String(x.id) !== String(survivorId); })
            .map(function (x) { return String(x.id); });

        function mensajes(x) {
            if (!x.messages_list || !x.messages_list.length) {
                return '<div class="tkt-unify-thread empty">Sin mensajes del cliente en este ticket.</div>';
            }

            return '<div class="tkt-unify-thread">' +
                (x.messages_truncated ? '<div class="tkt-unify-more">Mostrando los últimos ' + x.messages_list.length + ' de ' + x.messages + ' mensajes</div>' : '') +
                x.messages_list.map(function (m) {
                    return '<div class="tkt-unify-msg' + (m.is_agent ? ' is-agent' : '') + '">' +
                        '<div class="h"><b>' + escapeHtml(m.author || '—') + '</b><span>' + escapeHtml(m.at || '') + '</span></div>' +
                        '<div class="b">' + escapeHtml(m.body || '').replace(/\n/g, '<br>') + '</div>' +
                    '</div>';
                }).join('') +
            '</div>';
        }

        function tarjeta(x) {
            var esSuperviviente = String(x.id) === String(survivorId);
            var estaMarcado = marcados.indexOf(String(x.id)) !== -1;

            return '<div class="tkt-unify-card' + (esSuperviviente ? ' is-survivor' : (estaMarcado ? ' is-picked' : '')) + '" data-unify-id="' + x.id + '">' +
                '<div class="tkt-unify-head">' +
                    (esSuperviviente
                        ? '<span class="tkt-unify-badge">Se conserva</span>'
                        : '<label class="tkt-unify-check"><input type="checkbox" class="tkt-unify-pick" value="' + x.id + '"' + (estaMarcado ? ' checked' : '') + '><span>Unificar</span></label>') +
                    '<a class="tkt-unify-num" href="' + escapeHtml(x.url) + '" target="_blank" rel="noopener">' + escapeHtml(x.ticket_number) + '</a>' +
                    '<span class="tkt-chip">' + escapeHtml(x.status || '—') + '</span>' +
                    (x.similarity ? '<span class="tkt-chip">' + Math.round(x.similarity * 100) + ' %</span>' : '') +
                    '<span class="tkt-unify-when">' + escapeHtml(x.created_at || '—') + '</span>' +
                    (esSuperviviente ? '' : '<button type="button" class="tkt-btn tkt-btn-mini tkt-unify-keep-btn" data-keep="' + x.id + '">Conservar este</button>') +
                '</div>' +
                '<div class="tkt-unify-subject">' + escapeHtml(x.subject || '(sin asunto)') + '</div>' +
                '<div class="tkt-unify-meta">' +
                    x.messages + (x.messages === 1 ? ' mensaje' : ' mensajes') +
                    (x.assignee ? ' · ' + escapeHtml(x.assignee) : ' · sin asignar') +
                '</div>' +
                mensajes(x) +
            '</div>';
        }

        $backdrop.find('.tkt-modal-body').html(
            // El texto va dentro de un <span>: .tkt-note es flex, así que sin
            // envolverlo el <strong> se convertía en otra columna y la frase
            // salía partida en tres bloques.
            '<div class="tkt-note"><i class="fa-solid fa-circle-info"></i>' +
                '<span>Se conserva un ticket y los marcados se <strong>cierran</strong> (no se borran): ' +
                'su contenido pasa al que queda y su número seguirá existiendo, enlazado.</span>' +
            '</div>' +
            '<div class="tkt-cap">' + lista.length + ' tickets abiertos de este cliente · últimos ' + (res.window_days || '—') + ' días</div>' +
            lista.map(tarjeta).join('') +
            '<label class="tkt-unify-notify"><input type="checkbox" id="tkt-unify-notify" checked> ' +
                'Avisar al cliente por correo con el resumen y el número que queda</label>'
        );

        pintarPieUnify($backdrop, marcados.length);
    }

    function pintarPieUnify($backdrop, cuantos) {
        $backdrop.find('.tkt-modal-foot').html(
            '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-unify-go"' + (cuantos ? '' : ' disabled') + '>' +
                (cuantos ? 'Unificar ' + cuantos + (cuantos === 1 ? ' ticket' : ' tickets') : 'Selecciona qué unificar') +
            '</button>' +
            '<button type="button" class="tkt-btn" data-modal-close>Cancelar</button>'
        );
    }

    // Marcar/desmarcar un ticket concreto: no repinta el cuerpo (perdería el
    // scroll de los hilos), solo actualiza el resalte y el contador del botón.
    $(document).on('change', '.tkt-unify-pick', function () {
        var $backdrop = $('#tkt-modal-backdrop');
        $(this).closest('.tkt-unify-card').toggleClass('is-picked', this.checked);
        pintarPieUnify($backdrop, $backdrop.find('.tkt-unify-pick:checked').length);
    });

    // Cambiar cuál se conserva sí repinta: el que queda no puede aparecer a la
    // vez en la lista de los que se cierran. Se reaprovecha el resumen ya
    // cargado (TKA.state.unifyData) en vez de volver a pedirlo al servidor.
    $(document).on('click', '.tkt-unify-keep-btn', function () {
        var $backdrop = $('#tkt-modal-backdrop');
        var datos = TKA.state.unifyData;
        if (!$backdrop.length || !datos) return;

        var nuevo = String($(this).data('keep'));
        // Lo que siguiera marcado se respeta, menos el nuevo superviviente.
        var marcados = $backdrop.find('.tkt-unify-pick:checked').map(function () {
            return String(this.value);
        }).get().filter(function (id) { return id !== nuevo; });

        renderUnifyBody($backdrop, TKA.state.currentTicket, datos, datos.tickets, nuevo, marcados);
    });

    $(document).on('click', '#tkt-unify-go', function () {
        var $btn = $(this);
        var t = TKA.state.currentTicket;
        var $backdrop = $('#tkt-modal-backdrop');
        if (!t || !t.url_unify) return;

        var survivorId = $backdrop.find('.tkt-unify-card.is-survivor').data('unify-id');
        var ids = $backdrop.find('.tkt-unify-pick:checked').map(function () {
            return this.value;
        }).get();

        if (!survivorId || !ids.length) return;

        $btn.prop('disabled', true).text('Unificando…');

        $.ajax({
            url: t.url_unify,
            method: 'POST',
            data: { survivor_id: survivorId, ticket_ids: ids, notify_customer: $('#tkt-unify-notify').is(':checked') ? 1 : 0 },
            headers: { Accept: 'application/json' },
        }).done(function (res) {
            closeModal();
            dismissDuplicateBanner();
            if (window.toastr) {
                toastr.success((res.message || 'Tickets unificados.') + (res.notified ? ' Cliente avisado.' : ''));
            }
            // El ticket abierto puede ser uno de los que se acaban de cerrar,
            // así que su fila y su detalle ya no valen: se recarga el listado
            // por la misma vía que el resto de acciones destructivas del panel.
            window.location.reload();
        }).fail(function (xhr) {
            var msg = apiErrorMessage(xhr, 'No se pudieron unificar los tickets.');
            if (window.toastr) toastr.error(msg); else window.alert(msg);
            $btn.prop('disabled', false).text('Unificar');
        });
    });

    function dismissDuplicateBanner() {
        $('#tkt-dupe-banner').remove();
    }

    // ── Aviso "¿quieres asignarte este ticket?" ───────────────
    // Un ticket sin dueño era fácil de dejar pasar: se abre, se lee, se cierra
    // la pestaña sin que nadie quede como responsable. El aviso invita a
    // tomarlo con un clic de verdad — bug de UX real (QA visual 14-sep-2026):
    // "Asignarme" abría el modal genérico de asignación (buscador + lista
    // completa de agentes) en vez de autoasignar, así que el agente tenía
    // que volver a encontrarse a sí mismo en la lista y confirmar otra vez
    // algo que el botón ya decía haber hecho. Ahora usa el mismo
    // patchTicketSilent() que el modal, con TKA.state.currentUserId — sin
    // abrir nada.
    //
    // "Descartado" se recuerda por ticket mientras dura la sesión del panel
    // (TKA.state, no localStorage): si vuelves a este ticket más tarde sigue
    // sin dueño y merece la pena volver a preguntarlo.
    TKA.state.selfAssignDismissed = TKA.state.selfAssignDismissed || {};

    function renderSelfAssignBanner(t) {
        var $box = $('#tkt-selfassign-banner');
        if (!$box.length) return;

        var estado = t.status_slug || 'open';
        var cerrado = estado === 'closed';

        if (t.assignee || cerrado || TKA.state.selfAssignDismissed[t.id]) {
            $box.attr('hidden', true).empty();
            return;
        }

        // Sin la clase .tkt-detail-banner: esa clase es display:none por
        // defecto (banners que solo se muestran vía .show()/.hide() explícito,
        // como colisión/typing) y con ella puesta GANABA sobre el display:flex
        // de .tkt-banner-warn — el banner se insertaba con contenido real pero
        // altura 0, invisible. La visibilidad de #tkt-selfassign-banner ya se
        // controla con el atributo hidden del contenedor, como hace el aviso
        // de duplicados (que tampoco lleva esa clase).
        $box.attr('hidden', false).html(
            '<div class="tkt-banner-warn" role="status">' +
                '<i class="fa-solid fa-user-plus"></i>' +
                '<span class="tkt-banner-text">Este ticket no tiene agente asignado. ¿Quieres asignártelo?</span>' +
                '<button type="button" class="tkt-btn tkt-btn-sm tkt-btn-primary" id="tkt-selfassign-yes">Asignarme</button>' +
                '<button type="button" class="tkt-btn-icon" id="tkt-selfassign-close" aria-label="Descartar el aviso"><i class="fa-solid fa-xmark"></i></button>' +
            '</div>'
        );

        $box.find('#tkt-selfassign-yes').on('click', function () {
            patchTicketSilent(t, 'assignee_id', TKA.state.currentUserId, function () {
                // Revisión de código 14-sep-2026: si el usuario actual no
                // apareciera en agentsFull (rol sin permiso de agente, lista
                // todavía no cargada…), el PATCH al backend igual tiene
                // éxito — sin este 'Tú' de reserva, t.assignee se quedaba
                // sin tocar y la fila seguía mostrando "Sin asignar" pese a
                // estar ya asignada de verdad, hasta el próximo refresco
                // completo de la lista.
                var me = (TKA.state.agentsFull || []).find(function (a) { return a.id === TKA.state.currentUserId; });
                t.assignee = me ? { id: me.id, name: me.name } : { id: TKA.state.currentUserId, name: 'Tú' };
                renderDetail(t);
                renderSidePanel(t);
            });
        });
        $box.find('#tkt-selfassign-close').on('click', function () {
            TKA.state.selfAssignDismissed[t.id] = true;
            $box.attr('hidden', true).empty();
        });
    }

    // Aviso discreto sobre el detalle. Solo aparece si hay candidatos reales;
    // sin ellos no se pinta nada (nunca "0 duplicados").
    function checkDuplicates(t) {
        dismissDuplicateBanner();
        if (!t || !t.url_duplicates) return;

        $.getJSON(t.url_duplicates).done(function (res) {
            var list = (res && res.duplicates) || [];
            if (!list.length) return;

            var $banner = $('<div class="tkt-banner-warn" id="tkt-dupe-banner" role="status">' +
                '<i class="fa-solid fa-clone"></i>' +
                '<span class="tkt-banner-text">Este cliente tiene ' + (list.length === 1 ? 'otro ticket abierto' : list.length + ' tickets abiertos') + ' con un asunto muy parecido.</span>' +
                '<button type="button" class="tkt-btn tkt-btn-sm" id="tkt-dupe-open">Revisar duplicado</button>' +
                '<button type="button" class="tkt-btn tkt-btn-sm tkt-btn-primary" id="tkt-dupe-unify">Unificar</button>' +
                '<button type="button" class="tkt-btn-icon" id="tkt-dupe-close" aria-label="Descartar el aviso"><i class="fa-solid fa-xmark"></i></button>' +
            '</div>');

            $banner.find('#tkt-dupe-open').on('click', function () { openDuplicateModal(t, list, res.window_days); });
            $banner.find('#tkt-dupe-unify').on('click', function () { openUnifyModal(t); });
            $banner.find('#tkt-dupe-close').on('click', dismissDuplicateBanner);
            $('#tkt-collision-banner').after($banner);
        });
    }


    function loadMacros(cb) {
        if (!TKA.urls.macrosList) { cb([]); return; }
        if (TKA.state.macros) { cb(TKA.state.macros); return; }

        $.getJSON(TKA.urls.macrosList).done(function (res) {
            TKA.state.macros = (res && res.macros) || [];
            cb(TKA.state.macros);
        }).fail(function () { cb([]); });
    }

    function loadMacrosInto($select) {
        loadMacros(function (macros) { appendMacroOptions($select, macros); });
    }

    function appendMacroOptions($select, macros) {
        macros.forEach(function (m) {
            $select.append($('<option>', { value: m.id, text: m.name }));
        });
    }

    function applyMacro(t, macroId) {
        if (!t || !t.url_macro_apply_template) return;
        $.ajax({
            url: t.url_macro_apply_template.replace('__MACRO__', macroId),
            method: 'POST',
            headers: { Accept: 'application/json' },
            success: function (resp) {
                if (window.toastr) toastr.success((resp && resp.message) || 'Macro aplicada');
                fetchDetailData(t);
            },
            error: function (xhr) {
                var msg = apiErrorMessage(xhr, 'No se pudo aplicar la macro');
                if (window.toastr) toastr.error(msg); else window.alert(msg);
            },
        });
    }

    // "Marta está redactando una respuesta — ¿enviar igualmente?". Se avisa,
    // no se bloquea: un agente con la pestaña olvidada dejaría el ticket
    // inservible para el resto.
    function confirmCollisionBeforeSend(t, escribiendo) {
        var quien = escribiendo.map(function (u) { return u.name; }).join(', ');
        var verbo = escribiendo.length > 1 ? 'están redactando respuestas' : 'está redactando una respuesta';

        var $backdrop = openModal(modalShell({
            icon: 'fa-solid fa-triangle-exclamation', kicker: 'Bandeja · colisión',
            title: 'Otro agente está respondiendo', titleChip: t.ticket_number, width: 'sm',
            body: '<div class="tkt-note"><i class="fa-solid fa-pen"></i> <strong>' + escapeHtml(quien) + '</strong> ' +
                    escapeHtml(verbo) + ' en este ticket ahora mismo.</div>' +
                '<p class="tkt-presence-hint">Si envías la tuya, el cliente puede recibir dos respuestas seguidas.</p>',
            foot: '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-collision-send-anyway">Enviar igualmente</button>' +
                '<button type="button" class="tkt-btn" data-modal-close>Cancelar</button>',
        }));

        $backdrop.on('click', '#tkt-collision-send-anyway', function () {
            // Bandera de un solo uso: la siguiente respuesta vuelve a preguntar.
            TKA.state.replyCollisionAccepted = true;
            closeModal();
            sendReply();
        });
    }

    function makeIdempotencyKey(t) {
        var random = window.crypto && typeof window.crypto.randomUUID === 'function'
            ? window.crypto.randomUUID()
            : Date.now() + '-' + Math.random().toString(36).slice(2);
        return 'ticket-' + t.id + '-' + random;
    }

    function queueOfflineReply(t, body, isInternal, files, idempotencyKey) {
        var entry = {
            id: idempotencyKey || makeIdempotencyKey(t),
            ticketId: t.id,
            url: t.url_message_store,
            body: body,
            isInternal: isInternal ? 1 : 0,
            createdAt: new Date().toISOString(),
            attempts: 0,
            nextAttemptAt: null,
            lastError: '',
            dead: false,
        };

        if (files && files.length) {
            if ((TKA.state.offlineQueue || []).length + (TKA.state.offlineAttachmentQueue || []).length >= TKT_OFFLINE_MAX_ENTRIES) {
                if (window.toastr) toastr.error('La cola offline está llena. Conéctate y envía las respuestas pendientes antes de añadir otra.');
                return Promise.resolve(false);
            }
            entry.files = Array.prototype.slice.call(files);
            return putOfflineAttachmentReply(entry).then(function () {
                TKA.state.offlineAttachmentQueue = (TKA.state.offlineAttachmentQueue || []).concat([entry]);
                updateOfflineQueueStatus();
                if (window.toastr) toastr.info('Respuesta y adjuntos guardados. Se enviarán al recuperar la conexión.');
                return true;
            }).catch(function () {
                if (window.toastr) toastr.error('No se pudo guardar el adjunto offline. Mantén esta ventana abierta y reintenta con conexión.');
                return false;
            });
        }

        var queue = (TKA.state.offlineQueue || []).slice();
        queue.push(entry);
        writeOfflineQueue(queue);
        if (window.toastr) toastr.info('Respuesta guardada. Se enviará automáticamente al recuperar la conexión.');
        return Promise.resolve(true);
    }

    function attachmentValidationMessage(fileList) {
        var files = Array.prototype.slice.call(fileList || []);
        if (files.length > 10) return 'Puedes adjuntar como máximo 10 archivos.';
        var invalid = files.find(function (file) {
            var extension = String(file.name || '').split('.').pop().toLowerCase();
            return file.size > TKT_ATTACHMENT_MAX_BYTES || TKT_ATTACHMENT_EXTENSIONS.indexOf(extension) === -1;
        });
        return invalid ? 'El archivo debe ser un formato permitido y no superar ' + Math.round(TKT_ATTACHMENT_MAX_BYTES / 1024 / 1024 * 100) / 100 + ' MB.' : '';
    }

    function showAttachmentValidationError(message) {
        if (window.toastr) toastr.warning(message, 'Adjunto no válido'); else window.alert(message);
    }

    function scheduleOfflineFlush(delay) {
        clearTimeout(TKA.state.offlineRetryTimer);
        TKA.state.offlineRetryTimer = setTimeout(function () {
            TKA.state.offlineRetryTimer = null;
            if (TKA.state.networkOnline && navigator.onLine !== false) flushOfflineReplies();
        }, Math.max(1000, delay || TKT_OFFLINE_RETRY_BASE_MS));
    }

    function offlineRetryDelay(entry) {
        var attempts = Math.max(1, Number(entry.attempts) || 1);
        return Math.min(5 * 60 * 1000, TKT_OFFLINE_RETRY_BASE_MS * Math.pow(2, attempts - 1));
    }

    function persistOfflineAttachmentEntry(entry) {
        return putOfflineAttachmentReply(entry).catch(function () { return false; });
    }

    function retryOfflineQueue(id) {
        var writes = [];
        var textQueue = (TKA.state.offlineQueue || []).map(function (entry) {
            if (!id || String(entry.id) === String(id)) {
                entry = normalizeOfflineEntry(entry);
                entry.attempts = 0;
                entry.nextAttemptAt = null;
                entry.lastError = '';
                entry.dead = false;
            }
            return entry;
        });
        var attachmentQueue = (TKA.state.offlineAttachmentQueue || []).map(function (entry) {
            if (!id || String(entry.id) === String(id)) {
                entry = normalizeOfflineEntry(entry);
                entry.attempts = 0;
                entry.nextAttemptAt = null;
                entry.lastError = '';
                entry.dead = false;
                writes.push(persistOfflineAttachmentEntry(entry));
            }
            return entry;
        });
        writeOfflineQueue(textQueue);
        TKA.state.offlineAttachmentQueue = attachmentQueue;
        updateOfflineQueueStatus();
        return Promise.all(writes).then(function () {
            flushOfflineReplies();
        });
    }

    function openOfflineQueueModal() {
        var entries = (TKA.state.offlineQueue || []).map(function (entry) { return $.extend({ kind: 'Respuesta' }, entry); })
            .concat((TKA.state.offlineAttachmentQueue || []).map(function (entry) { return $.extend({ kind: 'Respuesta con adjuntos' }, entry); }));
        var body = entries.length ? '<div class="tkt-offline-queue-list">' + entries.map(function (entry) {
            var status = entry.dead ? 'Bloqueado tras ' + (entry.attempts || TKT_OFFLINE_MAX_ATTEMPTS) + ' intentos' : (entry.nextAttemptAt ? 'Próximo intento ' + new Date(entry.nextAttemptAt).toLocaleTimeString('es-ES') : 'Pendiente');
            return '<div class="tkt-offline-queue-row"><div class="tkt-offline-queue-main"><b>#' + escapeHtml(entry.ticketId) + ' · ' + escapeHtml(entry.kind) + '</b><span>' + escapeHtml(status) + (entry.lastError ? ' · ' + escapeHtml(entry.lastError) : '') + '</span></div><button type="button" class="tkt-btn tkt-btn-mini" data-offline-retry="' + escapeHtml(entry.id) + '">Reintentar</button></div>';
        }).join('') + '</div>' : '<div class="tkt-empty-box">No hay respuestas pendientes.</div>';
        var $backdrop = openModal(modalShell({
            icon: 'fa-solid fa-cloud-arrow-up',
            kicker: 'Persistencia local',
            title: 'Cola offline de respuestas',
            width: 'lg',
            body: body + '<div class="tkt-note"><i class="fa-solid fa-circle-info"></i> Las respuestas se mantienen en este navegador hasta que el servidor confirme su recepción y caducan a las 24 horas. Hay un máximo de ' + TKT_OFFLINE_MAX_ENTRIES + ' pendientes. Los reintentos usan espera progresiva para no saturar la conexión.</div>',
            foot: '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-offline-retry-all">Reintentar todo</button><button type="button" class="tkt-btn" data-modal-close>Cerrar</button>',
        }));
        $backdrop.on('click', '[data-offline-retry]', function () {
            var id = $(this).data('offline-retry');
            retryOfflineQueue(id).then(function () { closeModal(); });
        });
        $backdrop.on('click', '#tkt-offline-retry-all', function () {
            retryOfflineQueue().then(function () { closeModal(); });
        });
    }

    function flushOfflineReplies() {
        if (!TKA.state.networkOnline || TKA.state.offlineFlushInFlight || (!(TKA.state.offlineQueue || []).length && !(TKA.state.offlineAttachmentQueue || []).length)) {
            updateOfflineQueueStatus();
            return;
        }

        TKA.state.offlineFlushInFlight = true;
        var pending = (TKA.state.offlineQueue || []).slice();
        var pendingAttachments = (TKA.state.offlineAttachmentQueue || []).slice();
        var delivered = 0;

        function next() {
            if (!TKA.state.networkOnline || (!pending.length && !pendingAttachments.length)) {
                TKA.state.offlineFlushInFlight = false;
                if (delivered && window.toastr) toastr.success(delivered + (delivered === 1 ? ' respuesta enviada' : ' respuestas enviadas'));
                return;
            }

            var now = Date.now();
            var textIndex = pending.findIndex(function (entry) { return !entry.dead && (!entry.nextAttemptAt || Date.parse(entry.nextAttemptAt) <= now); });
            var attachmentIndex = pendingAttachments.findIndex(function (entry) { return !entry.dead && (!entry.nextAttemptAt || Date.parse(entry.nextAttemptAt) <= now); });
            var isAttachment = textIndex < 0 && attachmentIndex >= 0;
            if (textIndex < 0 && attachmentIndex < 0) {
                var future = pending.concat(pendingAttachments).filter(function (entry) { return !entry.dead && entry.nextAttemptAt; }).map(function (entry) { return Math.max(1000, Date.parse(entry.nextAttemptAt) - Date.now()); });
                TKA.state.offlineFlushInFlight = false;
                updateOfflineQueueStatus();
                if (future.length) scheduleOfflineFlush(Math.min.apply(Math, future));
                return;
            }

            var index = isAttachment ? attachmentIndex : textIndex;
            var entry = normalizeOfflineEntry(isAttachment ? pendingAttachments[index] : pending[index]);
            var formData = new FormData();
            formData.append('body', entry.body);
            formData.append('is_internal', entry.isInternal ? 1 : 0);
            (entry.files || []).forEach(function (file) { formData.append('attachments[]', file, file.name); });
            $.ajax({
                url: entry.url,
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: { Accept: 'application/json' },
                beforeSend: function (xhr) { xhr.setRequestHeader('X-Idempotency-Key', entry.id); },
            }).done(function () {
                if (isAttachment) {
                    pendingAttachments.splice(index, 1);
                    TKA.state.offlineAttachmentQueue = pendingAttachments.slice();
                    deleteOfflineAttachmentReply(entry.id).catch(function () {});
                } else {
                    pending.splice(index, 1);
                }
                delivered++;
                writeOfflineQueue(pending);
                var current = TKA.state.currentTicket;
                if (current && String(current.id) === String(entry.ticketId)) fetchDetailData(current);
                next();
            }).fail(function (xhr) {
                entry.attempts = (entry.attempts || 0) + 1;
                entry.lastError = apiErrorMessage(xhr, xhr.status ? 'Servidor respondió ' + xhr.status : 'No hay conexión');
                entry.dead = entry.attempts >= TKT_OFFLINE_MAX_ATTEMPTS;
                entry.nextAttemptAt = entry.dead ? null : new Date(Date.now() + offlineRetryDelay(entry)).toISOString();
                if (isAttachment) {
                    pendingAttachments[index] = entry;
                    TKA.state.offlineAttachmentQueue = pendingAttachments.slice();
                    persistOfflineAttachmentEntry(entry);
                } else {
                    pending[index] = entry;
                }
                writeOfflineQueue(pending);
                TKA.state.offlineFlushInFlight = false;
                if (xhr.status === 0 || !navigator.onLine) setNetworkState(false);
                // Se conserva la cola ante errores de red o respuestas 5xx.
                // Los 4xx también se dejan visibles para que el agente pueda
                // corregir el problema sin perder una respuesta escrita.
                updateOfflineQueueStatus();
                if (!entry.dead && navigator.onLine !== false) scheduleOfflineFlush(offlineRetryDelay(entry));
                if (entry.dead && window.toastr) toastr.error('Una respuesta quedó bloqueada tras varios intentos. Revisa la cola offline.');
            });
        }

        next();
    }

    function clearComposerAfterQueue(t, isInternal) {
        clearTimeout(TKA.state.draftSaveTimer);
        clearComposerDraft(t);
        $('#tkt-reply-body').val('').css('height', '').trigger('blur');
        $('#tkt-reply-attach').val('');
        $('#tkt-reply-attach-count').text('');
        $('#tkt-composer').removeClass('is-expanded').toggleClass('is-note', isInternal);
    }

    function sendReply() {
        var t = TKA.state.currentTicket;
        var body = $('#tkt-reply-body').val().trim();
        if (!t || !body) return;
        var isInternal = composerIsNote();

        // Colisión: hasta ahora el aviso "Fulano está viendo este ticket" era
        // pasivo y no frenaba nada, así que dos agentes podían contestar lo
        // mismo al cliente con segundos de diferencia. Se pregunta SOLO cuando
        // alguien está redactando de verdad (no por estar mirando) y solo para
        // respuestas visibles: dos notas internas a la vez no molestan a nadie.
        var escribiendo = isInternal ? [] : typingOthers();

        if (escribiendo.length && !TKA.state.replyCollisionAccepted) {
            confirmCollisionBeforeSend(t, escribiendo);
            return;
        }

        TKA.state.replyCollisionAccepted = false;

        var files = document.getElementById('tkt-reply-attach').files;
        var attachmentError = attachmentValidationMessage(files);
        if (attachmentError) {
            $('#tkt-reply-attach').val('');
            $('#tkt-reply-attach-count').text('');
            showAttachmentValidationError(attachmentError);
            return;
        }
        var idempotencyKey = makeIdempotencyKey(t);

        if (!TKA.state.networkOnline || navigator.onLine === false) {
            queueOfflineReply(t, body, isInternal, files, idempotencyKey).then(function (queued) {
                if (queued) clearComposerAfterQueue(t, isInternal);
            });
            return;
        }

        var formData = new FormData();
        formData.append('body', body);
        formData.append('is_internal', isInternal ? 1 : 0);
        for (var i = 0; i < files.length; i++) formData.append('attachments[]', files[i]);

        $.ajax({
            url: t.url_message_store,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: { Accept: 'application/json' },
            beforeSend: function (xhr) { xhr.setRequestHeader('X-Idempotency-Key', idempotencyKey); },
            success: function (resp) {
                if (window.toastr) toastr.success((resp && resp.message) || 'Mensaje enviado');
                emitTyping(false);
                clearTimeout(TKA.state.draftSaveTimer);
                clearComposerDraft(t);
                $('#tkt-reply-body').val('').css('height', '').trigger('blur');
                $('#tkt-reply-attach').val('');
                $('#tkt-reply-attach-count').text('');
                $('#tkt-composer').removeClass('is-expanded').toggleClass('is-note', isInternal);

                // Responder puede haber asignado el ticket o cambiado su
                // estado en el servidor (tickets.assign_on_reply/status_on_reply):
                // sin esto, "Asignado a" seguía enseñando el dueño de ANTES de
                // contestar hasta recargar a mano. Mismo objeto t que ya usa
                // openAssignModal() al asignar a mano (es la referencia que
                // vive en TKA.state.tickets, no una copia).
                //
                // renderSidePanel(t) sin pasar por renderDetail(t): ese sí
                // arregla el chip de estado de la cabecera de un plumazo, pero
                // reconstruye TODO el panel de detalle de golpe — el hilo
                // vuelve un instante al esqueleto de carga aunque
                // fetchDetailData() lo rellene enseguida después. Aquí solo
                // hace falta repintar "Asignado a" y el aviso de autoasignación.
                if (resp && resp.ticket) {
                    $.extend(t, resp.ticket);
                    renderSidePanel(t);
                    renderSelfAssignBanner(t);
                }

                fetchDetailData(t);
            },
            error: function (xhr) {
                if (xhr.status === 0 || !navigator.onLine) {
                    setNetworkState(false);
                    queueOfflineReply(t, body, isInternal, files, idempotencyKey).then(function (queued) {
                        if (queued) clearComposerAfterQueue(t, isInternal);
                    });
                    return;
                }
                var msg = (xhr.responseJSON && (xhr.responseJSON.message || (xhr.responseJSON.errors && Object.values(xhr.responseJSON.errors)[0][0]))) || 'No se pudo enviar el mensaje';
                if (window.toastr) toastr.error(msg); else window.alert(msg);
            },
        });
    }

    // El backend guarda la descripción ya redactada ("Estado cambiado de
    // 'Resuelto' a 'En Espera'"), así que el icono se deduce de esa frase.
    //
    // Se mira el estado DESTINO, no la frase entera: buscar "resuelto" en
    // toda la cadena marcaba en verde un cambio que precisamente SALE de
    // resuelto, porque el estado de origen también aparece en el texto.
    function activityStepType(description) {
        var d = String(description || '').toLowerCase();

        if (d.indexOf('creado') !== -1) return 'created';
        if (d.indexOf('visto') !== -1) return 'viewed';
        if (d.indexOf('elimin') !== -1 || d.indexOf('borrad') !== -1) return 'removed';
        if (d.indexOf('email') !== -1 || d.indexOf('correo') !== -1) return 'sent';
        if (d.indexOf('asign') !== -1) return 'assign';
        if (d.indexOf('priorid') !== -1) return 'priority';

        if (d.indexOf('estado') !== -1) {
            // Todo lo que va tras el último " a " es el estado al que se pasó.
            var destino = d.indexOf(' a ') !== -1 ? d.slice(d.lastIndexOf(' a ') + 3) : d;
            if (destino.indexOf('resuelt') !== -1) return 'resolved';
            if (destino.indexOf('cerrad') !== -1) return 'closed';
            return 'state';
        }

        return 'edited';
    }

    function auditFieldLabel(field) {
        return {
            status_id: 'estado',
            assignee_id: 'asignado a',
            priority: 'prioridad',
            subject: 'asunto',
            customer_id: 'cliente',
            due_at: 'vencimiento',
        }[field] || String(field).replace(/_/g, ' ');
    }

    function auditValue(value) {
        if (value === null || value === undefined || value === '') return '—';
        if (typeof value === 'object') {
            try { return JSON.stringify(value); } catch (e) { return String(value); }
        }
        return String(value);
    }

    function auditChangesHtml(changes) {
        changes = changes || {};
        var oldValues = changes.old || {};
        var newValues = changes.attributes || {};
        var keys = Object.keys(oldValues).concat(Object.keys(newValues)).filter(function (key, index, all) {
            return all.indexOf(key) === index;
        });
        var rows = keys.filter(function (key) {
            return auditValue(oldValues[key]) !== auditValue(newValues[key]);
        }).map(function (key) {
            return '<span class="tkt-audit-change"><b>' + escapeHtml(auditFieldLabel(key)) + '</b>: ' + escapeHtml(auditValue(oldValues[key])) + ' <i class="fa-solid fa-arrow-right" aria-hidden="true"></i> ' + escapeHtml(auditValue(newValues[key])) + '</span>';
        });
        return rows.length ? '<span class="tkt-audit-changes" aria-label="Valores anteriores y nuevos">' + rows.join('') + '</span>' : '';
    }

    function renderActivityPane(activities, totalCount) {
        var $p = $('#tkt-dpane-activity');
        if (!activities || !activities.length) {
            $p.html('<div class="tkt-empty-box">Sin actividad registrada todavía.</div>');
            return;
        }

        var html = '<div class="tkt-pane-head">' +
                '<span class="t">Actividad del ticket</span>' +
                '<span class="s">cambios de estado, asignación y envíos</span>' +
                '<span class="n mono">' + (totalCount || activities.length) + ' eventos</span>' +
            '</div>' +
            '<div class="tkt-card pad">';

        activities.forEach(function (a, i) {
            // "por María García" / "Sistema" / "Cliente": el mockup atribuye
            // cada línea. causer_kind lo manda el backend, porque sin causer
            // registrado la entrada la escribió un proceso, no una persona.
            var actor = a.causer
                ? 'por ' + escapeHtml(a.causer)
                : (a.causer_kind === 'customer' ? 'Cliente' : 'Sistema');

            html += stepHtml({
                type: activityStepType(a.description),
                title: escapeHtml(a.description),
                detail: actor + auditChangesHtml(a.changes),
                time: a.created_at_human || '',
                iso: a.created_at,
                last: i === activities.length - 1,
            });
        });
        html += '</div>';

        // activity_total_count = total real sin el limit(20) del backend —
        // sin este aviso, el recorte a 20 entradas quedaba silencioso (un
        // ticket longevo con 32+ entradas no daba ninguna pista de que
        // faltaban por ver).
        if (totalCount && totalCount > activities.length) {
            html += '<div class="tkt-note"><i class="fa-solid fa-circle-info"></i> Mostrando las ' + activities.length + ' más recientes de ' + totalCount + '.</div>';
        }
        // Pie del mockup: recuerda que esto es la bitácora de auditoría.
        html += '<div class="tkt-note"><i class="fa-solid fa-shield-halved"></i> Cada cambio queda registrado con usuario y fecha en la bitácora de auditoría.</div>';
        $p.html(html);
    }

    // Subida de adjuntos al ticket. Vivía dentro de renderFilesPane como
    // closure, así que solo la pestaña Adjuntos podía usarla; el botón
    // "Adjuntar" del panel de notas necesita exactamente lo mismo.
    function uploadTicketAttachments(t, fileList) {
        if (!t || !fileList || !fileList.length) return;

        var attachmentError = attachmentValidationMessage(fileList);
        if (attachmentError) {
            showAttachmentValidationError(attachmentError);
            return;
        }

        // StoreTicketMessageRequest exige body (min:1) — no acepta adjuntar
        // sin ningún texto, así que se manda un texto mínimo en vez de
        // forzar un string vacío que el backend rechazaría.
        var formData = new FormData();
        formData.append('body', '(archivo adjunto)');
        formData.append('is_internal', 0);
        for (var i = 0; i < fileList.length; i++) formData.append('attachments[]', fileList[i]);
        var idempotencyKey = makeIdempotencyKey(t);

        if (!TKA.state.networkOnline || navigator.onLine === false) {
            queueOfflineReply(t, '(archivo adjunto)', false, fileList, idempotencyKey);
            return;
        }

        $.ajax({
            url: t.url_message_store, method: 'POST', data: formData, processData: false, contentType: false,
            headers: { Accept: 'application/json' },
            beforeSend: function (xhr) { xhr.setRequestHeader('X-Idempotency-Key', idempotencyKey); },
            success: function () {
                if (window.toastr) toastr.success('Archivo adjuntado');
                fetchDetailData(t);
            },
            error: function (xhr) {
                if (xhr.status === 0 || !navigator.onLine) {
                    setNetworkState(false);
                    queueOfflineReply(t, '(archivo adjunto)', false, fileList, idempotencyKey);
                    return;
                }
                var msg = apiErrorMessage(xhr, 'No se pudo adjuntar el archivo');
                if (window.toastr) toastr.error(msg); else window.alert(msg);
            },
        });
    }

    function renderFilesPane(files, t) {
        var $p = $('#tkt-dpane-files');
        files = files || [];
        var totalBytes = files.reduce(function (n, f) { return n + (f.size || 0); }, 0);

        var html = '<div class="tkt-pane-head">' +
                '<span class="t">Adjuntos del ticket</span>' +
                '<span class="s">archivos del hilo y del formulario</span>' +
                '<span class="n mono">' + (files.length
                    ? files.length + (files.length === 1 ? ' archivo' : ' archivos') + (totalBytes ? ' · ' + formatFileSize(totalBytes) : '')
                    : 'sin archivos') + '</span>' +
            '</div>';

        html += files.length
            ? '<div class="tkt-filelist">' + files.map(fileRowHtml).join('') + '</div>'
            : '<div class="tkt-empty-box">Este ticket no tiene archivos adjuntos.</div>';

        // Adjuntar aquí publica un TicketItem sin texto vía el mismo endpoint
        // que la caja de respuesta del Hilo (StoreTicketMessageRequest ya
        // acepta attachments[] sin exigir body).
        html += '<label class="tkt-dropzone" id="tkt-files-drop">' +
                '<i class="fa-solid fa-cloud-arrow-up"></i>' +
                '<span class="t">Arrastra archivos aquí</span>' +
                '<span class="s">o pulsa para seleccionar · máx. ' + formatFileSize(TKT_ATTACHMENT_MAX_BYTES) + ' por archivo</span>' +
                '<input type="file" id="tkt-files-attach-input" multiple accept=".' + TKT_ATTACHMENT_EXTENSIONS.join(',.') + '" hidden>' +
            '</label>';

        // Pie del mockup: el botón explícito (la zona de arrastre sola no se
        // lee como pulsable) y el aviso de retención.
        html += '<div class="tkt-pane-foot">' +
                '<span class="tkt-note-inline"><i class="fa-solid fa-circle-info"></i> Los adjuntos se purgan junto con el contenido del email según la retención configurada.</span>' +
                '<button type="button" class="tkt-btn" id="tkt-files-attach-btn">Adjuntar archivo</button>' +
            '</div>';

        $p.html(html);

        $p.find('#tkt-files-attach-btn').on('click', function () {
            $p.find('#tkt-files-attach-input').trigger('click');
        });

        $p.find('[data-preview]').on('click', function (ev) {
            ev.preventDefault();
            openFilePreviewModal(t, files[parseInt($(this).data('preview'), 10)]);
        });

        function upload(fileList) { uploadTicketAttachments(t, fileList); }

        $p.find('#tkt-files-attach-input').on('change', function () { upload(this.files); this.value = ''; });
        // Arrastrar y soltar, que es lo que promete el copy de la zona.
        $p.find('#tkt-files-drop')
            .on('dragover', function (e) { e.preventDefault(); $(this).addClass('over'); })
            .on('dragleave drop', function () { $(this).removeClass('over'); })
            .on('drop', function (e) { e.preventDefault(); upload(e.originalEvent.dataTransfer.files); });
    }

    function formatFileSize(bytes) {
        if (bytes === null || bytes === undefined) return '';
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
    }

    // Fila de archivo del mockup: icono en cuadro, nombre + origen, tamaño y
    // dos acciones (previsualizar y descargar).
    function fileRowHtml(f, index) {
        var origen = f.source === 'customer' ? 'enviado por el cliente' : 'enviado por un agente';
        var meta = [origen, f.created_at_human].filter(Boolean).join(' · ');
        var previsualizable = f.url_download && PREVIEWABLE.test(f.name || '');

        return '<div class="tkt-filerow">' +
            '<span class="ico"><i class="' + fileIconClass(f.name) + '"></i></span>' +
            '<span class="info">' +
                '<span class="n">' + escapeHtml(f.name) + '</span>' +
                '<span class="s">' + escapeHtml(meta) + '</span>' +
            '</span>' +
            '<span class="size mono">' + escapeHtml(f.size ? formatFileSize(f.size) : '—') + '</span>' +
            '<span class="acts">' +
                (previsualizable
                    ? '<button type="button" class="tkt-btn-icon sm" data-preview="' + index + '" title="Previsualizar" aria-label="Previsualizar ' + escapeHtml(f.name) + '"><i class="fa-regular fa-eye"></i></button>'
                    : '') +
                (f.url_download
                    ? '<a class="tkt-btn-icon sm" href="' + escapeHtml(f.url_download) + '" download title="Descargar" aria-label="Descargar ' + escapeHtml(f.name) + '"><i class="fa-solid fa-download"></i></a>'
                    : '') +
            '</span>' +
        '</div>';
    }

    // Tokens de destinatario del card "Destinatarios": una píldora por
    // dirección, separando por coma la cadena que guarda la columna.
    function recipientTokens(value) {
        var list = String(value || '').split(',').map(function (x) { return x.trim(); }).filter(Boolean);
        if (!list.length) return '<span class="tkt-mailrow-empty">—</span>';
        return list.map(function (addr) {
            return '<span class="tkt-token">' + escapeHtml(addr) + '</span>';
        }).join('');
    }

    function renderMailPane(mail) {
        var $p = $('#tkt-dpane-mail');
        if (!mail) {
            $p.html('<div class="tkt-empty-box">Este ticket no tiene correos asociados.</div>');
            return;
        }

        // Vista "Texto": el body_text real del envío. Si el correo no llevaba
        // parte text/plain se deriva del HTML, indicándolo, en vez de dejar
        // la pestaña vacía.
        var derivedText = String(mail.body_html || '').replace(/<[^>]+>/g, '').replace(/\s+\n/g, '\n').trim();
        var plainText = mail.body_text || derivedText;

        var recipientCount = ['to', 'cc', 'bcc'].reduce(function (n, k) {
            return n + String(mail[k] || '').split(',').filter(function (x) { return x.trim(); }).length;
        }, 0);

        // El mockup ofrece HTML · Texto · Fuente. "Fuente" solo aparece si
        // raw_email tiene el MIME archivado: sintetizar cabeceras que no se
        // guardaron sería inventar datos (mismo criterio que el resto de la
        // pantalla, que deja vacíos honestos en vez de rellenos falsos).
        var hasSource = !!mail.raw_email;

        // Chip "imágenes y links" del mockup: se calcula del HTML real del
        // correo (cuántas <img> y cuántos <a href> lleva), no es un adorno.
        // Es lo que un agente quiere saber antes de fiarse de un preview:
        // si el mensaje depende de recursos remotos.
        var probe = document.createElement('div');
        probe.innerHTML = mail.body_html || '';
        var imgCount = probe.querySelectorAll('img').length;
        var linkCount = probe.querySelectorAll('a[href]').length;
        // Las que llegan desactivadas por el servidor: son las que dispararían
        // una petición al dominio del remitente si se pintaran tal cual.
        var blockedImages = probe.querySelectorAll('img[data-blocked]').length;
        var assetsChip = (imgCount || linkCount)
            ? '<span class="tkt-chip-assets"><i class="fa-solid fa-check"></i> ' +
                [imgCount ? imgCount + (imgCount === 1 ? ' imagen' : ' imágenes') : '',
                 linkCount ? linkCount + (linkCount === 1 ? ' link' : ' links') : '']
                .filter(Boolean).join(' · ') + '</span>'
            : '<span class="tkt-chip-assets plain">sin imágenes ni links</span>';

        // Chip de spam: la puntuación real del filtro del servidor entrante
        // (X-Spam-Score / X-Spam-Status). Si el correo no pasó por ningún
        // filtro, mail.spam llega null y el chip no se pinta — mejor que
        // enseñar un 0 que parecería "verificado y limpio".
        var spamChip = '';
        if (mail.spam) {
            var over = mail.spam.is_spam;
            spamChip = '<span class="tkt-chip-spam' + (over ? ' over' : '') + '" title="Umbral del filtro: ' +
                mail.spam.threshold + '">Spam ' + mail.spam.score + '/' + mail.spam.threshold + '</span>';
        }

        $p.html(
            '<div class="tkt-card">' +
                '<div class="tkt-card-head">' +
                    '<span class="tkt-cap">Destinatarios</span>' +
                    '<span class="tkt-pill-count">' + recipientCount + (recipientCount === 1 ? ' destinatario' : ' destinatarios') + '</span>' +
                    '<button type="button" class="tkt-link-btn" id="tkt-open-delivery">Detalle de entrega</button>' +
                    (mail.status === 'bounced' || mail.status === 'failed'
                        ? '<button type="button" class="tkt-link-btn strong" id="tkt-open-bounce">Ver rebote</button>' : '') +
                '</div>' +
                '<div class="tkt-mailrows">' +
                    '<div class="tkt-mailrow"><span class="k">De</span><span class="v"><span class="mono">' + escapeHtml(mail.from || '—') + '</span></span></div>' +
                    '<div class="tkt-mailrow"><span class="k">Para</span><span class="v">' + recipientTokens(mail.to) + '</span></div>' +
                    (mail.cc ? '<div class="tkt-mailrow"><span class="k">CC</span><span class="v">' + recipientTokens(mail.cc) + '</span></div>' : '') +
                    (mail.bcc ? '<div class="tkt-mailrow"><span class="k">CCO</span><span class="v">' + recipientTokens(mail.bcc) + '</span></div>' : '') +
                    '<div class="tkt-mailrow last"><span class="k">Asunto</span><span class="v">' + escapeHtml(mail.subject || '—') + '</span></div>' +
                '</div>' +
                (mail.message_id || mail.in_reply_to
                    ? '<details class="tkt-tech">' +
                        '<summary><i class="fa-solid fa-chevron-right"></i> Detalles técnicos<span class="mono">Message-ID · In-Reply-To</span></summary>' +
                        '<div class="tkt-tech-body">' +
                            (mail.message_id ? '<div class="tkt-tech-row"><span class="k">Message-ID</span><span class="v mono">' + escapeHtml(mail.message_id) + '</span><button type="button" class="tkt-copy" data-copy="' + escapeHtml(mail.message_id) + '" title="Copiar"><i class="fa-regular fa-copy"></i></button></div>' : '') +
                            (mail.in_reply_to ? '<div class="tkt-tech-row"><span class="k">In-Reply-To</span><span class="v mono">' + escapeHtml(mail.in_reply_to) + '</span></div>' : '') +
                        '</div>' +
                      '</details>'
                    : '') +
            '</div>' +
            '<div class="tkt-card">' +
                '<div class="tkt-card-head">' +
                    '<span class="tkt-cap">Cuerpo del mensaje</span>' +
                    assetsChip +
                    spamChip +
                    // Escritorio/Móvil: acota el ancho del preview a 380px
                    // para ver cómo le llega el correo al cliente en el
                    // teléfono, que es donde se abre la mayoría.
                    '<span class="tkt-seg-mini" role="group" aria-label="Ancho de previsualización">' +
                        '<button type="button" class="on" data-mailwidth="desk">Escritorio</button>' +
                        '<button type="button" data-mailwidth="mob">Móvil</button>' +
                    '</span>' +
                    '<span class="tkt-seg-mini" role="group" aria-label="Formato del cuerpo">' +
                        '<button type="button" class="on" data-mailview="html">HTML</button>' +
                        '<button type="button" data-mailview="text">Texto</button>' +
                        (hasSource ? '<button type="button" data-mailview="source">Fuente</button>' : '') +
                    '</span>' +
                '</div>' +
                // Aviso de imágenes bloqueadas. El backend ya sirve el HTML con
                // los src en data-src (TicketMail::blockRemoteImages), así que
                // aquí no hay que interceptar nada: solo ofrecer restaurarlas.
                (blockedImages
                    ? '<div class="tkt-mail-blocked" id="tkt-mail-blocked">' +
                        '<span>' + blockedImages + (blockedImages === 1 ? ' imagen remota bloqueada' : ' imágenes remotas bloqueadas') +
                        '<span class="tkt-mail-blocked-why">cargarlas le confirma al remitente que has abierto el correo</span></span>' +
                        '<button type="button" class="tkt-btn tkt-btn-xs" id="tkt-mail-show-images">Mostrar imágenes</button>' +
                      '</div>'
                    : '') +
                '<div class="tkt-mail-body" id="tkt-mail-body-html">' + (mail.body_html || '<em>Sin contenido</em>') + '</div>' +
                '<pre class="tkt-mail-body raw" id="tkt-mail-body-text" hidden>' + escapeHtml(plainText || 'Sin contenido') + '</pre>' +
                (hasSource ? '<pre class="tkt-mail-body raw" id="tkt-mail-body-source" hidden>' + escapeHtml(mail.raw_email) + '</pre>' : '') +
            '</div>' +
            ((mail.attachments && mail.attachments.length)
                ? '<div class="tkt-att-strip"><span class="tkt-cap">Adjuntos · ' + mail.attachments.length + '</span>' +
                    mail.attachments.map(function (a) {
                        var name = a.name || a.filename || 'archivo';
                        return '<span class="tkt-att-pill"><i class="' + fileIconClass(name) + '"></i>' + escapeHtml(name) +
                            (a.size ? '<span class="mono">' + formatFileSize(a.size) + '</span>' : '') + '</span>';
                    }).join('') +
                  '</div>'
                : '')
        );

        $p.find('#tkt-open-delivery').on('click', function () {
            var d = TKA.state.currentDetail;
            openDeliveryModal(TKA.state.currentTicket, mail, d && d.trace);
        });
        $p.find('#tkt-open-bounce').on('click', function () {
            openBounceModal(TKA.state.currentTicket, mail);
        });

        // Restaurar las imágenes es decisión del agente y solo afecta a este
        // correo en esta sesión: no se guarda preferencia ninguna, el siguiente
        // que abra vuelve a estar bloqueado.
        $p.find('#tkt-mail-show-images').on('click', function () {
            $('#tkt-mail-body-html').find('img[data-blocked]').each(function () {
                this.setAttribute('src', this.getAttribute('data-src') || '');
                this.removeAttribute('data-blocked');
            });
            $('#tkt-mail-blocked').remove();
        });

        $p.find('[data-mailview]').on('click', function () {
            var which = $(this).data('mailview');
            $p.find('[data-mailview]').removeClass('on');
            $(this).addClass('on');
            $('#tkt-mail-body-html').prop('hidden', which !== 'html');
            $('#tkt-mail-body-text').prop('hidden', which !== 'text');
            $('#tkt-mail-body-source').prop('hidden', which !== 'source');
        });

        $p.find('[data-mailwidth]').on('click', function () {
            var which = $(this).data('mailwidth');
            $p.find('[data-mailwidth]').removeClass('on');
            $(this).addClass('on');
            $p.find('.tkt-mail-body').toggleClass('mobile', which === 'mob');
        });

        $p.find('[data-copy]').on('click', function () {
            var value = $(this).data('copy');
            if (navigator.clipboard) {
                navigator.clipboard.writeText(value).then(function () {
                    if (window.toastr) toastr.success('Message-ID copiado');
                });
            }
        });
    }

    var TRACE_LABELS = { queued: 'Encolado', sent: 'Aceptado por el servidor de correo', bounced: 'Rebotado', failed: 'Fallido', opened: 'Abierto por el destinatario', clicked: 'Enlace clicado por el destinatario' };

    // Icono y tono de cada hito de la línea de tiempo. Verde para lo que
    // salió bien, gris para lo informativo, negro para lo que falló.
    var TRACE_STEP = {
        queued:   { icon: 'fa-inbox',                tone: 'ok'   },
        // Tipos propios de la pestaña Actividad.
        created:  { icon: 'fa-plus',                 tone: 'ok'   },
        state:    { icon: 'fa-arrow-right-arrow-left', tone: 'mute' },
        resolved: { icon: 'fa-check',                tone: 'ok'   },
        closed:   { icon: 'fa-lock',                 tone: 'mute' },
        assign:   { icon: 'fa-user-pen',             tone: 'mute' },
        priority: { icon: 'fa-flag',                 tone: 'mute' },
        edited:   { icon: 'fa-pen',                  tone: 'mute' },
        removed:  { icon: 'fa-trash',                tone: 'bad'  },
        sent:     { icon: 'fa-paper-plane',          tone: 'ok'   },
        delivered:{ icon: 'fa-check',                tone: 'ok'   },
        opened:   { icon: 'fa-envelope-open',        tone: 'mute' },
        clicked:  { icon: 'fa-arrow-pointer',        tone: 'mute' },
        bounced:  { icon: 'fa-arrow-rotate-left',    tone: 'bad'  },
        failed:   { icon: 'fa-triangle-exclamation', tone: 'bad'  },
        viewed:   { icon: 'fa-eye',                  tone: 'mute' },
    };

    // Una fila de la línea de tiempo: círculo + línea vertical, texto
    // principal, detalle y hora a la derecha. La usan Traza y Actividad.
    function stepHtml(opts) {
        var step = TRACE_STEP[opts.type] || { icon: 'fa-circle', tone: 'mute' };
        return '<div class="tkt-step">' +
            '<div class="tkt-step-rail">' +
                '<span class="dot ' + step.tone + '"><i class="fa-solid ' + step.icon + '"></i></span>' +
                '<span class="line' + (opts.last ? ' last' : '') + '"></span>' +
            '</div>' +
            '<div class="tkt-step-body' + (opts.last ? ' last' : '') + '">' +
                '<span class="t">' + opts.title + '</span>' +
                (opts.detail ? '<span class="s">' + opts.detail + '</span>' : '') +
            '</div>' +
            '<span class="tkt-step-time mono"' + (opts.iso ? ' title="' + escapeHtml(opts.iso) + '"' : '') + '>' + escapeHtml(opts.time || '') + '</span>' +
        '</div>';
    }

    function renderTracePane(events, mail, meta) {
        var $p = $('#tkt-dpane-trace');
        if (!events || !events.length) {
            $p.html('<div class="tkt-empty-box">Sin trazabilidad de entrega: este ticket todavía no tiene ningún correo enviado.</div>');
            return;
        }

        var html = '<div class="tkt-pane-head">' +
                '<span class="t">Recorrido del último correo</span>' +
                '<span class="s">del encolado a la entrega</span>' +
                '<span class="n mono">' + events.length + (events.length === 1 ? ' evento' : ' eventos') + '</span>' +
            '</div>' +
            '<div class="tkt-card pad">';

        events.forEach(function (ev, i) {
            html += stepHtml({
                type: ev.type,
                // El backend ya manda el rótulo redactado ('Entregado al
                // destinatario'); TRACE_LABELS queda como respaldo para
                // eventos antiguos que no lo traigan.
                title: escapeHtml(ev.label || TRACE_LABELS[ev.type] || ev.type),
                detail: ev.detail ? escapeHtml(ev.detail) : '',
                time: ev.at_human || ev.at || '',
                iso: ev.at,
                last: i === events.length - 1,
            });
        });
        html += '</div>';

        // Pie del mockup: "Traza SMTP" e "Identificadores" en dos columnas.
        // El mockup enseña ahí relay/IP/TLS/reintentos; esta instalación no
        // guarda ninguno de esos datos (raw_headers está vacío en todo el
        // log y ticket_mails no tiene columnas de transporte), así que se
        // pinta solo lo que el backend pudo calcular de verdad — cada fila
        // sin dato se omite en vez de rellenarse con un ejemplo.
        var smtpRows = (meta && meta.smtp) || [];
        var idRows = (meta && meta.ids) || [];

        if (smtpRows.length || idRows.length) {
            html += '<div class="tkt-duo">';

            if (smtpRows.length) {
                html += '<div class="tkt-card"><div class="tkt-card-head"><span class="tkt-cap">Traza SMTP</span></div>' +
                    '<div class="tkt-side-rows">' +
                    smtpRows.map(function (r, i) {
                        return sideRow(r.k, r.v, { mono: true, last: i === smtpRows.length - 1 });
                    }).join('') +
                    '</div></div>';
            }

            if (idRows.length) {
                html += '<div class="tkt-card"><div class="tkt-card-head"><span class="tkt-cap">Identificadores</span></div>' +
                    '<div class="tkt-side-rows">' +
                    idRows.map(function (r, i) {
                        return sideRow(r.k, r.v, { mono: true, last: i === idRows.length - 1 });
                    }).join('') +
                    '</div></div>';
            }

            html += '</div>';
        } else if (mail) {
            html += '<div class="tkt-duo">' +
                '<div class="tkt-card"><div class="tkt-card-head"><span class="tkt-cap">Identificadores</span></div>' +
                    '<div class="tkt-side-rows">' +
                        sideRow('Message-ID', mail.message_id || '—', { mono: true }) +
                        sideRow('In-Reply-To', mail.in_reply_to || '—', { mono: true, last: true }) +
                    '</div></div>' +
                '<div class="tkt-card"><div class="tkt-card-head"><span class="tkt-cap">Entrega</span></div>' +
                    '<div class="tkt-side-rows">' +
                        sideRow('Estado', mail.status || '—', { mono: true }) +
                        sideRow('Enviado', mail.sent_at_human || '—', { mono: true }) +
                        sideRow('Entregado', mail.delivered_at_human || '—', { mono: true, last: true }) +
                    '</div></div>' +
            '</div>';
        }

        // "Ver mensaje original" del mockup: salta a la pestaña Correo, que
        // ya sabe pintar cabeceras y fuente — no se duplica ese visor aquí.
        if (mail) {
            html += '<button type="button" class="tkt-btn tkt-w-100" data-goto-dtab="mail">' +
                '<i class="fa-solid fa-code"></i> Ver mensaje original</button>';
        }

        $p.html(html);

        $p.find('[data-goto-dtab]').on('click', function () {
            $('[data-dtab="' + $(this).data('goto-dtab') + '"]').trigger('click');
        });
    }

    // ═══════════ Render: panel lateral (8 pestañas, Fase C) ═══════════
    function optionsHtml(list, key, current) {
        var html = '';
        (list || []).forEach(function (item) {
            html += '<option value="' + item.id + '"' + (String(item.id) === String(current) ? ' selected' : '') + '>' + escapeHtml(item.name) + '</option>';
        });
        return html;
    }

    // Todo <select> de la pantalla debe ser select2 (bug de diseño real
    // encontrado en QA: el selector "Agente" era un <select> nativo con
    // decenas de opciones — imposible de usar sin buscador). Un único punto
    // de inicialización, llamado tras cada render() que introduce selects
    // nuevos (estáticos del filtro/modales, o generados por JS en el panel
    // Gestión/modales de bulk). NUNCA theme:'bootstrap-5' (gotcha ya
    // conocido: su CSS no está cargado y rompe el estilo) — select2 clásico,
    // ya cargado globalmente en el layout.
    function initSelect2($scope) {
        var $sel = $scope ? $scope.find('select').addBack('select') : $('select');
        $sel.each(function () {
            var $s = $(this);
            if ($s.data('select2')) return; // ya inicializado, evita doble-init
            if ($s.is('[data-no-select2]')) return;
            // width:'100%' (el que usa create.blade.php) rompía la barra de
            // filtros: dentro de un flex row cada select2 pasaba a ocupar
            // toda la línea y los apilaba verticalmente. 'style' respeta el
            // ancho real del <select> original (auto en la barra de
            // filtros/panel Gestión, 100% dentro de los .tkt-field de los
            // modales porque ahí el <select> ya es block-level de por sí).
            //
            // dropdownParent: por defecto select2 cuelga su panel de <body>,
            // con su propio z-index — por debajo del z-index:9999 de
            // .tkt-modal-backdrop, así que dentro de un modal el desplegable
            // "abría" (el <select> quedaba en estado open) pero se
            // renderizaba TAPADO detrás del propio modal, invisible. Anclarlo
            // al backdrop más cercano lo mete en su mismo stacking context.
            //
            // Bug real de QA (ago-2026): fuera de un modal caía a
            // document.body — el CSS del panel estaba scopeado bajo .tkt como
            // ancestro, así que un panel colgado directo de <body> no matcheaba
            // NINGUNA de esas reglas y se veía como una caja en blanco sin
            // borde ni texto (filtro "Origen" del listado, entre otros). Se
            // ancló entonces al .tkt más cercano, y eso trajo el problema
            // contrario: .tkt lleva overflow:hidden, así que el panel de un
            // <select> de la mitad inferior (panel Gestión) se recortaba en el
            // borde de la pantalla. La salida buena es la tercera:
            // dropdownCssClass marca el panel con una clase propia y el CSS
            // cuelga de ELLA, no de .tkt — así puede volver a <body> sin
            // perder el estilo ni chocar con ningún overflow.
            var $backdrop = $s.closest('.tkt-modal-backdrop');

            // Chips de la barra de filtros (Origen, Etiqueta, Categoría,
            // Agente, Prioridad). Antes eran <select> nativos transparentes
            // superpuestos sobre un <label> pintado a mano (data-no-select2),
            // así que el desplegable lo dibujaba el sistema operativo: sin
            // buscador y con un aspecto distinto en cada navegador. Ahora son
            // select2 como el resto de la pantalla, y templateSelection
            // reconstruye dentro del widget el contenido del chip —etiqueta
            // fija + valor en gris— para no perder el diseño del mockup.
            if ($s.is('[data-fchip-label]')) {
                var chipLabel = String($s.data('fchip-label'));
                $s.select2({
                    width: 'style',
                    // Más alto que el 6 del resto de la pantalla: "Origen" y
                    // "Prioridad" tienen 5-7 opciones y el buscador ahí sobra;
                    // "Agente"/"Etiqueta"/"Categoría" lo pasan de sobra y sí lo
                    // reciben.
                    minimumResultsForSearch: 8,
                    dropdownAutoWidth: true,
                    dropdownParent: $backdrop.length ? $backdrop : $(document.body),
                    dropdownCssClass: 'tkt-s2-drop',
                    templateSelection: function (data) {
                        return $('<span class="tkt-fsel-rendered"></span>')
                            .append($('<span class="tkt-fsel-label"></span>').text(chipLabel))
                            .append($('<span class="tkt-fsel-value"></span>').text(data.text || ''));
                    },
                });
                // .on marca el chip con filtro activo (borde y valor en negro),
                // igual que hacía la clase que pintaba el Blade. Solo cuenta
                // como "activo" si el desplegable tiene un estado de reposo,
                // es decir una <option> vacía del tipo "todos"/"todas": el
                // <select> de orden siempre tiene un valor elegido (SLA por
                // defecto) y marcarlo lo dejaría resaltado a perpetuidad.
                var hasEmptyOption = $s.find('option[value=""]').length > 0;
                $s.next('.select2-container')
                    .addClass('tkt-fsel-c')
                    .toggleClass('tkt-fsel-c--sm', $s.data('fchip-size') === 'sm')
                    .toggleClass('on', hasEmptyOption && !!$s.val());
                return;
            }

            $s.select2({
                width: 'style',
                minimumResultsForSearch: 6,
                dropdownAutoWidth: true,
                dropdownParent: $backdrop.length ? $backdrop : $(document.body),
                dropdownCssClass: 'tkt-s2-drop',
            });
        });
    }

    function renderSidePanel(t) {
        $('#tkt-side').show();
        TKA.state.currentTicket = t;
        TKA.state.currentDetail = null;
        if (!TKA.state.sideTab) TKA.state.sideTab = 'gestion';
        renderActiveSidePane();
    }

    function selectSideTab(which) {
        // Al elegir una pestaña desde el riel reducido, se vuelve a abrir el
        // panel para que el contenido sea visible inmediatamente.
        if (TKA.state.splitLayout && TKA.state.splitLayout.sideCollapsed) {
            setSideCollapsed(false);
        }
        TKA.state.sideTab = which;
        $('#tkt-side-rail [data-side]').each(function () {
            $(this).toggleClass('on', $(this).data('side') === which);
        });
        renderActiveSidePane();
    }

    // Único punto que decide qué pestaña del panel lateral pintar — se
    // llama tanto al seleccionar ticket (Gestión, sin AJAX) como al llegar
    // la respuesta de data() (el resto, que sí depende de ella) y al
    // cambiar de pestaña (usa lo ya cacheado, sin refetch).
    function renderActiveSidePane() {
        var t = TKA.state.currentTicket;
        var d = TKA.state.currentDetail;
        if (!t) return;
        var which = TKA.state.sideTab || 'gestion';
        var $c = $('#tkt-side-content');

        if (which === 'gestion') { renderGestionPane($c, t, d); return; }
        if (!d) { $c.html('<div class="tkt-skeleton"></div>'); return; }
        if (which === 'cliente') return renderClientePane($c, d.customer, t);
        if (which === 'form') return renderFormPane($c, d.form, t);
        if (which === 'correo') return renderCorreoSidePane($c, d.mail, t, d.side_conversations, d.followups, d.mails, d.last_outbound_mail);
        if (which === 'notas') return renderNotasPane($c, d.notes, t);
        if (which === 'tags') return renderTagsPane($c, t, d.ai_suggestion);
        if (which === 'files') return renderArchivosPane($c, d.files);
        if (which === 'tickets') return renderCustomerTicketsPane($c, d.customer_tickets, t);
        if (which === 'hist') return renderHistorialPane($c, d.activity, d.related);
    }

    // Pestaña "Tickets del cliente": el resto del histórico del mismo
    // contacto. Cada fila selecciona ese ticket en la lista si está en la
    // página actual; si no, navega al deep-link ?ticket=N.
    function renderCustomerTicketsPane($c, data, t) {
        if (!t.customer) {
            $c.html('<div class="tkt-empty-box">Este ticket no tiene un cliente asociado, así que no hay histórico que mostrar.</div>');
            return;
        }

        // El backend manda ya los contadores sobre TODO el histórico del
        // cliente y la lista recortada a 20. Antes se contaba aquí sobre las
        // filas recibidas, así que "totales" mentía en cuanto el cliente
        // pasaba de veinte tickets.
        var d = data || {};
        var todos = d.items || [];
        var conteos = d.counts || { total: todos.length, open: 0, resolved: 0 };
        var filtro = 'all';

        function esAbierto(r) {
            return r.status_slug === 'open' || r.status_slug === 'progress' || r.status_slug === 'pending';
        }

        function lista() {
            var list = todos.filter(function (r) {
                if (filtro === 'all') return true;
                return filtro === 'open' ? esAbierto(r) : !esAbierto(r);
            });

            if (!list.length) return '<div class="tkt-empty-box">Sin tickets en este filtro.</div>';

            return list.map(function (r) {
                // Segunda línea del mockup: número · antigüedad · correos ·
                // agente. Cada dato solo si existe, para no dejar separadores
                // sueltos con un guion detrás.
                var meta = [];
                if (r.created_at_human) meta.push(escapeHtml(r.created_at_human));
                if (r.mails_count) meta.push(r.mails_count + (r.mails_count === 1 ? ' correo' : ' correos'));
                if (r.agent_name) meta.push(escapeHtml(r.agent_name));

                // data-ticket-preview y no data-preview: ese atributo ya lo usan
                // los adjuntos del hilo, y su handler se quedaba con el clic.
                return '<button type="button" class="tkt-ctk' + (r.is_current ? ' is-current' : '') + '" data-ticket-preview="' + r.id + '">' +
                        '<span class="line1">' +
                            '<span class="subj">' + escapeHtml(r.subject || '(sin asunto)') + '</span>' +
                            (r.is_current
                                ? '<span class="tkt-ctk-badge current">Actual</span>'
                                : '<span class="tkt-ctk-badge">' + escapeHtml(r.status_name || r.status_slug || '') + '</span>') +
                            '<i class="fa-solid fa-chevron-right tkt-ctk-chevron"></i>' +
                        '</span>' +
                        '<span class="line2">' +
                            '<span class="tkt-ticket-id mono">' + escapeHtml(r.ticket_number) + '</span>' +
                            (meta.length ? '<span class="tkt-ctk-sep"></span><span class="when">' + meta.join(' · ') + '</span>' : '') +
                        '</span>' +
                    '</button>';
            }).join('');
        }

        $c.html(
            '<div class="tkt-side-card">' +
                '<div class="tkt-side-card-head">Tickets del cliente' +
                    (d.label ? '<span class="tkt-spacer tkt-side-tag">' + escapeHtml(d.label) + '</span>' : '') + '</div>' +
                '<div class="tkt-side-card-body">' +
                    '<div class="tkt-stats three">' +
                        '<div class="tkt-stat"><span class="n">' + conteos.total + '</span><span class="l">totales</span></div>' +
                        '<div class="tkt-stat"><span class="n">' + conteos.open + '</span><span class="l">abiertos</span></div>' +
                        '<div class="tkt-stat"><span class="n ok">' + conteos.resolved + '</span><span class="l">resueltos</span></div>' +
                    '</div>' +
                    '<div class="tkt-seg-tabs" id="tkt-ctk-tabs">' +
                        '<button type="button" class="on" data-cfilter="all">Todos</button>' +
                        '<button type="button" data-cfilter="open">Abiertos</button>' +
                        '<button type="button" data-cfilter="closed">Cerrados</button>' +
                    '</div>' +
                '</div>' +
                '<div class="tkt-side-card-body tight" id="tkt-ctk-list">' +
                    (todos.length ? lista() : '<div class="tkt-empty-box">Este cliente no tiene más tickets.</div>') +
                '</div>' +
                // Pie del mockup: cuántas de cuántas se están viendo. Solo
                // aparece cuando de verdad hay más de las que caben.
                (todos.length && conteos.total > todos.length
                    ? '<div class="tkt-ctk-foot">' +
                        '<span class="mono">' + todos.length + ' de ' + conteos.total + '</span>' +
                        (d.url_all ? '<a href="' + escapeHtml(d.url_all) + '">Ver todos →</a>' : '') +
                      '</div>'
                    : (d.url_all && todos.length
                        ? '<div class="tkt-ctk-foot"><a class="tkt-spacer" href="' + escapeHtml(d.url_all) + '">Ver todos →</a></div>'
                        : '')) +
            '</div>' +
            '<div class="tkt-side-card"><div class="tkt-side-card-body">' +
                '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-link-other">Vincular a otro ticket</button>' +
            '</div></div>'
        );

        $c.find('[data-cfilter]').on('click', function () {
            filtro = $(this).data('cfilter');
            $c.find('[data-cfilter]').removeClass('on');
            $(this).addClass('on');
            $c.find('#tkt-ctk-list').html(lista());
        });

        // Clic en una tarjeta: vista rápida en un modal, sin salir del ticket
        // que se está atendiendo. Abrir el otro ticket directamente hacía
        // perder el contexto para una comprobación de dos segundos.
        $c.find('[data-ticket-preview]').on('click', function () {
            var id = parseInt($(this).data('ticket-preview'), 10);
            openTicketPreviewModal(todos.find(function (x) { return x.id === id; }));
        });

        // Reutiliza el mismo modal que el botón "Vincular ticket" del panel
        // de historial: es la misma acción, no hacía falta uno nuevo.
        $c.find('#tkt-link-other').on('click', function () { linkTicketPrompt(TKA.state.currentTicket); });
    }

    /**
     * Vista rápida de otro ticket del cliente, sin salir del que se atiende.
     *
     * La tarjeta de la lista ya trae lo suficiente para la cabecera; el cuerpo
     * del último correo se pide aparte porque el panel lateral no lo carga (y
     * traerlo para los 20 tickets encarecería la apertura de cada ticket por
     * algo que casi nunca se abre).
     *
     * J y K saltan al ticket anterior/siguiente sin cerrar, como en el mockup.
     */
    function openTicketPreviewModal(row) {
        if (!row) { return; }

        var estado = row.is_current ? 'Actual' : (row.status_name || row.status_slug || '—');

        openModal(modalShell({
            icon: 'fa-regular fa-eye',
            kicker: 'TICKET · VISTA RÁPIDA',
            titleChip: row.ticket_number,
            title: 'Vista previa',
            width: 'md',
            body: '<table class="tkt-info-table">' +
                    '<tr><th>Asunto</th><td>' + escapeHtml(row.subject || '(sin asunto)') + '</td></tr>' +
                    '<tr><th>Estado</th><td>' + escapeHtml(estado) + '</td></tr>' +
                    (row.agent_name ? '<tr><th>Agente</th><td>' + escapeHtml(row.agent_name) + '</td></tr>' : '') +
                    (row.created_at_human ? '<tr><th>Creado</th><td>' + escapeHtml(row.created_at_human) + '</td></tr>' : '') +
                    '<tr><th>Correos</th><td>' + (row.mails_count || 0) + '</td></tr>' +
                  '</table>' +
                  '<div class="tkt-preview-body" id="tkt-preview-body"><div class="tkt-skeleton"></div></div>' +
                  '<div class="tkt-note tkt-preview-hint">Navega con <span class="mono">J</span> / <span class="mono">K</span> sin cerrar la vista previa.</div>',
            foot: '<button type="button" class="tkt-btn tkt-btn-primary" data-preview-open="' + row.id + '">Abrir completo</button>' +
                  '<button type="button" class="tkt-btn" data-preview-reply="' + row.id + '">Responder</button>' +
                  '<button type="button" class="tkt-btn" data-modal-close>Cerrar</button>',
        }));

        // Último mensaje del ticket, bajo demanda.
        $.getJSON(TKA.urls.index.replace(/\/?$/, '/') + row.id + '/data')
            .done(function (d) {
                var hilo = (d && d.thread) || [];
                var ultimo = hilo.length ? hilo[hilo.length - 1] : null;
                var texto = ultimo ? String(ultimo.body || '').replace(/<[^>]*>/g, ' ').trim() : '';

                $('#tkt-preview-body').html(texto
                    ? escapeHtml(texto.slice(0, 400)) + (texto.length > 400 ? '…' : '')
                    : '<span class="tkt-faint">Este ticket todavía no tiene mensajes.</span>');
            })
            .fail(function () {
                $('#tkt-preview-body').html('<span class="tkt-faint">No se pudo cargar el contenido del ticket.</span>');
            });
    }

    // J / K: saltar al ticket anterior o siguiente de la lista del cliente sin
    // cerrar la vista previa. Solo con el modal abierto y fuera de un campo de
    // texto, para no secuestrar la escritura.
    $(document).on('keydown', function (e) {
        if (!/^[jkJK]$/.test(e.key)) { return; }
        if ($('.tkt-modal [data-preview-open]').length === 0) { return; }
        if ($(e.target).is('input, textarea, [contenteditable]')) { return; }

        var $items = $('#tkt-ctk-list [data-ticket-preview]');
        if (!$items.length) { return; }

        var actual = parseInt($('.tkt-modal [data-preview-open]').data('preview-open'), 10);
        var ids = $items.map(function () { return parseInt($(this).data('ticket-preview'), 10); }).get();
        var i = ids.indexOf(actual);
        if (i === -1) { return; }

        var siguiente = /[jJ]/.test(e.key) ? i + 1 : i - 1;
        if (siguiente < 0 || siguiente >= ids.length) { return; }

        e.preventDefault();
        $items.eq(siguiente).trigger('click');
    });

    $(document).on('click', '[data-preview-open]', function () {
        window.location = TKA.urls.index + '?ticket=' + $(this).data('preview-open');
    });

    $(document).on('click', '[data-preview-reply]', function () {
        window.location = TKA.urls.index + '?ticket=' + $(this).data('preview-reply') + '&reply=1';
    });

    // CSAT — el ticket ya trae rating/rating_comment/rating_reason/rated_at
    // reales (FeedbackController::submit() los rellena cuando el cliente
    // puntúa desde el enlace firmado del email). Sin valoración: solo se
    // ofrece reenviar si sendCsatSurvey() lo permitiría (mismas 3
    // condiciones ya validadas en el backend); si no aplica ninguna de las
    // dos, la tarjeta simplemente no se muestra (nunca "0 valoraciones").
    function renderCsatCard(csat) {
        if (!csat || (!csat.rating && !csat.can_resend)) return '';

        var body;
        if (csat.rating) {
            var stars = '';
            for (var i = 1; i <= 5; i++) stars += '<i class="fa-solid fa-star" style="color:' + (i <= csat.rating ? 'var(--tkt-warn)' : 'var(--tkt-border-strong)') + ';font-size:13px"></i>';
            body = '<div class="tkt-kv"><span class="k">Puntuación</span><span class="v">' + stars + '</span></div>' +
                '<div class="tkt-kv"><span class="k">Recibida</span><span class="v">' + escapeHtml(csat.rated_at_human || '—') + '</span></div>' +
                (csat.reason ? '<div class="tkt-kv"><span class="k">Motivo</span><span class="v">' + escapeHtml(csat.reason) + '</span></div>' : '') +
                (csat.comment ? '<div class="tkt-note">' + escapeHtml(csat.comment) + '</div>' : '');
        } else {
            body = '<div class="tkt-empty-box">Sin valorar todavía.</div>' +
                '<button type="button" class="tkt-btn" id="tkt-act-csat-resend">Reenviar encuesta de satisfacción</button>';
        }

        return '<div class="tkt-side-card"><div class="tkt-side-card-head">Satisfacción (CSAT)' +
            '<button type="button" class="tkt-btn-icon" id="tkt-open-csat" title="Ver la encuesta completa" aria-label="Ver la encuesta completa"><i class="fa-solid fa-up-right-and-down-left-from-center"></i></button>' +
            '</div><div class="tkt-side-card-body">' + body + '</div></div>';
    }

    /**
     * Qué acciones tienen sentido con este ticket delante.
     *
     * El estado canónico (status_slug) agrupa el catálogo real en cinco:
     * open / progress / pending / resolved / closed — ver
     * Ticket::canonicalStatusSlug(). 'closed_at' se mira aparte porque un
     * ticket puede estar cerrado con un estado del catálogo que no se llame
     * "closed".
     */
    function accionesHtml(t, d) {
        var estado = t.status_slug || 'open';
        // Manda el ESTADO del catálogo, no closed_at: hay tickets marcados
        // "Resuelto" que arrastran un closed_at antiguo (el 13, sin ir más
        // lejos), y mirarlo escondía "Cerrar ticket" en tickets que a ojos del
        // agente están abiertos y resueltos, no cerrados.
        var cerrado = estado === 'closed';
        var resuelto = estado === 'resolved';
        // Un ticket resuelto sigue siendo operable: se cierra, se fusiona o se
        // aplaza. Solo el cerrado queda fuera de esas acciones.
        var vivo = !cerrado;

        // Dividir necesita al menos dos mensajes: con uno solo no hay nada que
        // separar. Si el detalle aún no ha cargado no se esconde el botón —
        // mejor ofrecerlo de más que hacerlo desaparecer al azar.
        var mensajes = (d && d.items) ? d.items.length : null;
        var puedeDividir = vivo && (mensajes === null || mensajes > 1);

        var tieneEmail = !!(t.customer && t.customer.email);

        // Settings → Helpdesk · Tickets → Funcionalidades (14-sep-2026): cada
        // condición se multiplica por featureEnabled() sin tocar la lógica
        // de negocio que ya decidía si el botón aplica al estado del ticket
        // — "Responder al cliente" queda fuera, no se puede apagar.
        var botones = [
            // Responder sigue disponible en un ticket cerrado: es lo que lo
            // reabre en la práctica cuando el cliente vuelve a escribir.
            ['tkt-act-reply', 'Responder al cliente', 'tkt-btn-primary', true],
            [null, 'Marcar como resuelto', '', vivo && !resuelto && featureEnabled('action_resolve'), 'resolve'],
            [null, 'Cerrar ticket', '', vivo && featureEnabled('action_close'), 'close'],
            [null, 'Reabrir ticket', '', (cerrado || resuelto) && featureEnabled('action_reopen'), 'reopen'],
            ['tkt-act-snooze', 'Aplazar seguimiento', '', vivo && featureEnabled('action_snooze')],
            ['tkt-act-merge', 'Fusionar duplicado', '', vivo && featureEnabled('action_merge')],
            ['tkt-act-split', 'Dividir ticket', '', puedeDividir && featureEnabled('action_split')],
            ['tkt-act-portal', 'Ver como el cliente', '', featureEnabled('action_portal')],
            ['tkt-act-blacklist', 'Pasar a lista negra', 'tkt-btn-danger', tieneEmail && featureEnabled('action_blacklist')],
            ['tkt-act-delete', 'Eliminar / Archivar', 'tkt-btn-danger', featureEnabled('action_delete')],
        ];

        return botones.filter(function (b) { return b[3]; }).map(function (b) {
            var id = b[0] ? ' id="' + b[0] + '"' : '';
            var ciclo = b[4] ? ' data-lifecycle="' + b[4] + '"' : '';

            return '<button type="button" class="tkt-btn ' + b[2] + ' tkt-btn-start"' + id + ciclo + '>' +
                escapeHtml(b[1]) + '</button>';
        }).join('');
    }

    function renderGestionPane($c, t, d) {
        var csat = d && d.csat;
        var sla = d && d.sla;
        var asg = (d && d.assignment) || {};
        var agentName = t.assignee ? t.assignee.name : null;

        // Card "Asignado a": el mockup enseña avatar con iniciales, carga del
        // agente y presencia, no un <select> suelto.
        var assigneeCard =
            '<div class="tkt-side-card">' +
                '<div class="tkt-side-card-head">Asignado a' +
                    (asg.group_name ? '<span class="tkt-spacer tkt-side-tag">' + escapeHtml(asg.group_name) + '</span>' : '') +
                '</div>' +
                '<div class="tkt-side-card-body">' +
                    '<button type="button" class="tkt-assignee" id="tkt-sg-assignee-wrap">' +
                        '<span class="av">' + escapeHtml(agentName ? initials(agentName) : '—') + '</span>' +
                        '<span class="who">' +
                            '<span class="n">' + escapeHtml(agentName || 'Sin asignar') + '</span>' +
                            '<span class="s">' + (agentName
                                ? 'Agente' + (asg.agent_open_tickets != null ? ' · ' + asg.agent_open_tickets + (asg.agent_open_tickets === 1 ? ' ticket abierto' : ' tickets abiertos') : '')
                                : 'Nadie lo está atendiendo') + '</span>' +
                        '</span>' +
                        (agentName ? '<span class="tkt-live"><span class="dot"></span>en línea</span>' : '') +
                        '<i class="fa-solid fa-chevron-right"></i>' +
                    '</button>' +
                    '<div class="tkt-side-rows">' +
                        sideRow('equipo', asg.group_name || '—', { mono: true }) +
                        sideRow('seguidores', (asg.watchers_count || 0) + ((asg.watchers_count || 0) === 1 ? ' agente' : ' agentes'), { mono: true }) +
                        sideRow('asignado', asg.assigned_at_human || '—', { mono: true, last: true }) +
                    '</div>' +
                    '<div class="tkt-side-duo">' +
                        (featureEnabled('mgmt_reassign') ? '<button type="button" class="tkt-btn" id="tkt-act-reassign">Reasignar</button>' : '') +
                        (featureEnabled('mgmt_followers') ? '<button type="button" class="tkt-btn" id="tkt-act-followers">Seguidores</button>' : '') +
                    '</div>' +
                '</div>' +
            '</div>';

        // Card SLA con barra de progreso. Solo si hay política aplicada.
        var slaCard = !featureEnabled('mgmt_sla_card') ? '' : sla
            ? '<div class="tkt-side-card">' +
                '<div class="tkt-side-card-head">SLA<span class="tkt-spacer"><button type="button" class="tkt-link-btn" id="tkt-act-slacal">Calendario</button></span></div>' +
                '<div class="tkt-side-card-body tight">' +
                    (sla.percent != null
                        ? '<div class="tkt-sla-bar-row' + (sla.state === 'breach' ? ' over' : '') + '">' +
                            '<span class="tkt-sla-bar"><span style="width:' + sla.percent + '%"></span></span>' +
                            '<span class="tkt-sla-pct mono' + (sla.state === 'breach' ? ' over' : '') + '">' + sla.percent + ' %</span>' +
                          '</div>'
                        : '') +
                    sideRow('Estado', sla.label, { strong: true }) +
                    sideRow('Resolución', sla.resolution, { strong: true }) +
                    sideRow('1ª respuesta', sla.first_response, { strong: true, last: true }) +
                '</div>' +
              '</div>'
            // Sin política aplicada la tarjeta desaparecía entera, y con ella
            // el único acceso al modal "Horario y SLA" — que es justo donde se
            // explica POR QUÉ este ticket no tiene plazos. Hoy ningún ticket
            // de esta instalación tiene sla_policy_id, así que el modal era
            // prácticamente inalcanzable.
            : '<div class="tkt-side-card">' +
                '<div class="tkt-side-card-head">SLA<span class="tkt-spacer"><button type="button" class="tkt-link-btn" id="tkt-act-slacal">Calendario</button></span></div>' +
                '<div class="tkt-side-card-body">' +
                    '<div class="tkt-empty-box">Este ticket no tiene ninguna política de SLA aplicada, así que no se le calculan plazos.</div>' +
                '</div>' +
              '</div>';

        $c.html(
            // Acciones ARRIBA del todo: es a lo que se viene a este panel; los
            // selectores de estado/prioridad/categoría se tocan mucho menos y
            // obligaban a bajar cada vez.
            //
            // Y solo las que APLICAN al estado del ticket: la lista era fija, así
            // que un ticket abierto ofrecía "Reabrir" y uno cerrado ofrecía
            // "Cerrar" y "Marcar como resuelto". Pulsarlas no rompía nada, pero
            // el panel afirmaba cosas falsas sobre el ticket que tienes delante.
            '<div class="tkt-side-card" id="tkt-sg-actions">' +
                '<div class="tkt-side-card-head">Acciones</div>' +
                '<div class="tkt-side-card-body">' + accionesHtml(t, d) + '</div>' +
            '</div>' +
            (!featureEnabled('mgmt_fields') ? '' :
            '<div class="tkt-side-card">' +
                '<div class="tkt-side-card-head">Gestión del ticket<span class="tkt-spacer">' + chip(t.status_name || STATUS_LABEL_FALLBACK[t.status_slug] || t.status_slug, statusChipClass(t.status_slug)) + '</span></div>' +
                // Etiqueta en versalitas ENCIMA del control, a ancho completo.
                '<div class="tkt-side-card-body">' +
                    '<div class="tkt-field"><label for="tkt-sg-state">Estado</label><select id="tkt-sg-state" data-field="status_id">' + optionsHtml(TKA.state.statuses, 'id', t.status_id) + '</select></div>' +
                    '<div class="tkt-field"><label for="tkt-sg-priority">Prioridad</label><select id="tkt-sg-priority" data-field="priority">' +
                        ['low', 'normal', 'high', 'urgent'].map(function (pr) { return '<option value="' + pr + '"' + (pr === t.priority ? ' selected' : '') + '>' + escapeHtml(priorityLabel(pr)) + '</option>'; }).join('') +
                    '</select></div>' +
                    '<div class="tkt-field"><label for="tkt-sg-category">Categoría</label><select id="tkt-sg-category" data-field="category_id"><option value="">—</option>' + optionsHtml(TKA.state.categories, 'id', t.category_id) + '</select></div>' +
                    '<div class="tkt-field"><label for="tkt-sg-group">Equipo</label><select id="tkt-sg-group" data-field="group_id"><option value="">—</option>' + optionsHtml(TKA.state.groups, 'id', t.group_id) + '</select></div>' +
                '</div>' +
            '</div>') +
            assigneeCard +
            slaCard +
            (featureEnabled('mgmt_csat_card') ? renderCsatCard(csat) : '')
        );

        // Tanto la ficha del agente como "Reasignar" abren el modal 37 — salvo
        // que "Reasignar" esté apagado en Funcionalidades: entonces la ficha
        // queda como información de solo lectura, no como atajo alternativo
        // al mismo modal que el toggle acaba de ocultar.
        $c.find(featureEnabled('mgmt_reassign') ? '#tkt-sg-assignee-wrap, #tkt-act-reassign' : '#tkt-act-reassign').on('click', function () { openAssignModal(t); });
        $c.find('#tkt-act-slacal').on('click', openSlaCalendarModal);
        $c.find('#tkt-act-blacklist').on('click', function () { openBlacklistModal(t); });

        $c.find('#tkt-open-csat').on('click', function () { openCsatModal(t); });
        $c.find('#tkt-act-csat-resend').on('click', function () {
            var $btn = $(this).prop('disabled', true).text('Enviando…');
            $.ajax({
                url: t.url_send_csat, method: 'POST', headers: { Accept: 'application/json' },
                success: function (resp) {
                    if (window.toastr) toastr.success((resp && resp.message) || 'Encuesta enviada');
                    $btn.text('Enviada').prop('disabled', true);
                },
                error: function (xhr) {
                    var msg = apiErrorMessage(xhr, 'No se pudo enviar la encuesta');
                    if (window.toastr) toastr.error(msg); else window.alert(msg);
                    $btn.prop('disabled', false).text('Reenviar encuesta de satisfacción');
                },
            });
        });

        $c.find('select[data-field]').on('change', function () {
            patchTicket(t, $(this).data('field'), $(this).val());
        });

        initSelect2($c);

        $c.find('[data-lifecycle]').on('click', function () {
            var action = $(this).data('lifecycle');
            if (action === 'close') { openCloseTicketModal(t); return; }
            runLifecycleAction(t, action);
        });

        $c.find('#tkt-act-reply').on('click', function () { openComposeModal(t); });
        $c.find('#tkt-act-split').on('click', function () { openSplitModal(t); });
        $c.find('#tkt-act-portal').on('click', function () { openPortalModal(t); });
        $c.find('#tkt-act-snooze').on('click', function () { snoozeTicket(t); });
        $c.find('#tkt-act-merge').on('click', function () { mergeTicketPrompt(t); });
        $c.find('#tkt-act-followers').on('click', function () { openFollowersModal(t); });
        $c.find('#tkt-act-delete').on('click', function () { openDeleteModal(t); });
    }

    // Fusionar es DESTRUCTIVO (mueve historial/mails/notas al ticket destino
    // y cierra/elimina este) — el checkbox de confirmación dentro del modal
    // sustituye al window.confirm() encadenado, manteniendo la misma
    // fricción explícita antes de ejecutar. Backend real (MergeTicketRequest,
    // campo merge_into_id).
    // `prefillTarget` opcional: id del ticket destino ya elegido (llega del
    // modal 46, donde el agente ya seleccionó cuál era el duplicado).
    // Buscador de ticket destino compartido por "Fusionar" y "Vincular".
    // Ambos modales pedían el ID numérico a mano con la ayuda "visible en la
    // URL al abrirlo": había que salir a otra pantalla, copiarlo y volver.
    // Escribe el id elegido en el input que se le pase, sin cambiar el
    // contrato de los endpoints (que siguen recibiendo un id).
    function bindTicketSearch($backdrop, opts) {
        var $input = $backdrop.find(opts.input);
        var $results = $backdrop.find(opts.results);
        var excludeId = opts.excludeId;
        var timer;

        if (!TKA.urls.ticketSearch) {
            $results.remove();
            return;
        }

        $input.on('input', function () {
            var q = String(this.value || '').trim();
            clearTimeout(timer);

            // Un id tecleado a mano sigue siendo válido: no se busca por él.
            if (q.length < 2 || /^\d+$/.test(q)) { $results.empty(); return; }

            timer = setTimeout(function () {
                $.getJSON(TKA.urls.ticketSearch, { q: q, exclude_id: excludeId }).done(function (res) {
                    var filas = (res && res.data) || [];
                    ultimaBusqueda = filas;
                    if (!filas.length) {
                        $results.html('<div class="tkt-empty-box">Ningún ticket coincide.</div>');
                        return;
                    }

                    $results.html(filas.map(function (f) {
                        return '<button type="button" class="tkt-pick" data-ticket-pick="' + f.id + '">' +
                            '<span class="who"><span class="n">' + escapeHtml(f.ticket_number) + ' · ' +
                                escapeHtml(f.subject || '(sin asunto)') + '</span>' +
                            '<span class="s">' + escapeHtml([f.customer_name, f.status_name, f.updated_at_human]
                                .filter(Boolean).join(' · ')) + '</span></span></button>';
                    }).join(''));
                });
            }, 250);
        });

        var ultimaBusqueda = [];

        $results.on('click', '[data-ticket-pick]', function () {
            var id = $(this).data('ticket-pick');
            var fila = ultimaBusqueda.find(function (f) { return String(f.id) === String(id); }) || null;
            $input.val(id);
            $results.html('<div class="tkt-note">Ticket destino <strong>' +
                escapeHtml(fila ? fila.ticket_number : '#' + id) + '</strong> seleccionado.</div>');
            if (opts.onPick) opts.onPick(id, fila);
        });
    }

    function mergeTicketPrompt(t, prefillTarget) {
        var $backdrop = openModal(modalShell({
            icon: 'fa-solid fa-code-merge',
            iconClass: 'danger',
            kicker: 'Tickets · fusión',
            titleChip: t.ticket_number,
            title: 'Fusionar tickets',
            // 'sm' (340px) apretaba el panel comparado (.tkt-merge-pair, dos
            // columnas lado a lado) y la lista de resultados de búsqueda.
            width: 'xl',
            body: '' +
                '<div class="tkt-field"><label class="tkt-label">Ticket destino<span class="req">*</span><span class="hint">busca por número o asunto</span></label>' +
                    '<input type="text" class="tkt-input" id="tkt-merge-target" placeholder="Nº de ticket, asunto o ID…" value="' + (prefillTarget ? escapeHtml(String(prefillTarget)) : '') + '">' +
                    '<div class="tkt-pick-list sm" id="tkt-merge-results"></div></div>' +
                // Vista comparada: cuál se conserva y cuál desaparece. Sin
                // esto había que fiarse de un id suelto para una acción que
                // no se puede deshacer.
                '<div class="tkt-merge-pair" id="tkt-merge-pair">' +
                    '<div class="tkt-merge-side losing">' +
                        '<span class="tkt-cap">Se fusiona y cierra</span>' +
                        '<span class="n mono">' + escapeHtml(t.ticket_number) + '</span>' +
                        '<span class="s tkt-trunc">' + escapeHtml(t.subject || '(sin asunto)') + '</span>' +
                    '</div>' +
                    '<i class="fa-solid fa-arrow-right tkt-merge-arrow"></i>' +
                    '<div class="tkt-merge-side winning" id="tkt-merge-winner">' +
                        '<span class="tkt-cap">Se conserva</span>' +
                        '<span class="n mono">—</span>' +
                        '<span class="s">elige el ticket destino</span>' +
                    '</div>' +
                '</div>' +
                // El mockup ofrece "mover correos, adjuntos y notas" como una
                // opción; aquí siempre se mueve todo, porque el ticket origen
                // se cierra y lo que no se moviera se perdería. Se dice qué
                // pasa en vez de fingir una elección que no existe.
                '<div class="tkt-note danger">Se moverán al ticket destino los <strong>mensajes, correos, adjuntos, notas, comentarios, tiempos, seguidores y enlaces</strong> de ' + escapeHtml(t.ticket_number) + '. No se puede deshacer.</div>' +
                '<label class="tkt-check"><input type="checkbox" id="tkt-merge-ack"> Entiendo que esta acción no se puede deshacer</label>',
            foot: '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-merge-confirm" disabled>Fusionar</button>' +
                  '<button type="button" class="tkt-btn" data-modal-close>Cancelar</button>',
        }));

        // Al elegir destino se pinta en la vista comparada, para que quede a
        // la vista qué ticket sobrevive antes de confirmar.
        bindTicketSearch($backdrop, {
            input: '#tkt-merge-target',
            results: '#tkt-merge-results',
            excludeId: t.id,
            onPick: function (id, fila) {
                $backdrop.find('#tkt-merge-winner').html(
                    '<span class="tkt-cap">Se conserva</span>' +
                    '<span class="n mono">' + escapeHtml((fila && fila.ticket_number) || ('#' + id)) + '</span>' +
                    '<span class="s tkt-trunc">' + escapeHtml((fila && fila.subject) || '') + '</span>'
                );
            },
        });

        $backdrop.on('change', '#tkt-merge-ack', function () {
            $('#tkt-merge-confirm').prop('disabled', !this.checked);
        });

        $backdrop.on('click', '#tkt-merge-confirm', function () {
            var input = $('#tkt-merge-target').val().trim();
            if (!/^\d+$/.test(input)) { if (window.toastr) toastr.error('Escribe un ID numérico de ticket'); return; }

            $.ajax({
                url: t.url_merge,
                method: 'POST',
                data: { merge_into_id: input },
                headers: { Accept: 'application/json' },
                success: function () {
                    if (window.toastr) toastr.success('Ticket fusionado');
                    window.location = TKA.urls.index;
                },
                error: function (xhr) {
                    var msg = (xhr.responseJSON && (xhr.responseJSON.message || (xhr.responseJSON.errors && Object.values(xhr.responseJSON.errors)[0][0]))) || 'No se pudo fusionar el ticket';
                    if (window.toastr) toastr.error(msg); else window.alert(msg);
                },
            });
        });
    }

    // Modal "Aplazar seguimiento". Los atajos del mockup calculan la fecha
    // en el cliente y rellenan el mismo campo, así que el backend sigue
    // recibiendo solo snoozed_until (más pause_sla, que sí valida ahora).
    function snoozeTicket(t) {
        var pad = function (n) { return String(n).padStart(2, '0'); };
        var fmt = function (fecha) {
            return fecha.getFullYear() + '-' + pad(fecha.getMonth() + 1) + '-' + pad(fecha.getDate()) +
                'T' + pad(fecha.getHours()) + ':' + pad(fecha.getMinutes());
        };

        // "3 días hábiles": saltando sábados y domingos, que es lo que
        // significa hábil aquí. No se consultan festivos: el calendario
        // laboral vive en el módulo SLA y no viaja a esta pantalla.
        var habiles = function (dias) {
            var fecha = new Date();
            while (dias > 0) {
                fecha.setDate(fecha.getDate() + 1);
                if (fecha.getDay() !== 0 && fecha.getDay() !== 6) dias--;
            }
            fecha.setHours(9, 0, 0, 0);
            return fecha;
        };

        var manana = new Date();
        manana.setDate(manana.getDate() + 1);
        manana.setHours(9, 0, 0, 0);

        var proximaSemana = new Date();
        proximaSemana.setDate(proximaSemana.getDate() + 7);
        proximaSemana.setHours(9, 0, 0, 0);

        var atajos = [
            { label: 'Mañana', sub: 'a las 9:00', value: fmt(manana) },
            { label: 'En 3 días hábiles', sub: 'sin contar el fin de semana', value: fmt(habiles(3)) },
            { label: 'La semana que viene', sub: 'dentro de 7 días', value: fmt(proximaSemana) },
        ];

        var defaultValue = atajos[0].value;

        var $backdrop = openModal(modalShell({
            icon: 'fa-regular fa-clock',
            kicker: 'Ticket · aplazar',
            titleChip: t.ticket_number,
            title: 'Aplazar ticket',
            width: 'md',
            body: '' +
                '<div class="tkt-field"><label class="tkt-label">Cuándo</label>' +
                    atajos.map(function (a, i) {
                        return '<label class="tkt-option' + (i === 0 ? ' on' : '') + '" data-snooze-shortcut="' + a.value + '">' +
                            '<input type="radio" name="tkt-snooze-when" ' + (i === 0 ? 'checked' : '') + ' class="tkt-m0">' +
                            '<span><span class="tkt-option-title">' + a.label + '</span>' +
                            '<br><span class="tkt-option-sub">' + a.sub + '</span></span></label>';
                    }).join('') +
                '</div>' +
                '<div class="tkt-field"><label class="tkt-label">Fecha exacta<span class="req">*</span></label>' +
                    '<input type="datetime-local" class="tkt-input" id="tkt-snooze-until" value="' + defaultValue + '"></div>' +
                // Un ticket aplazado tres días seguía consumiendo su plazo de
                // resolución y volvía marcado como vencido sin que nadie
                // pudiera haberlo trabajado.
                '<label class="tkt-check"><input type="checkbox" id="tkt-snooze-pause-sla" checked> ' +
                    'Pausar el SLA mientras está aplazado</label>' +
                '<div class="tkt-note">El ticket saldrá de las colas activas y volverá a aparecer automáticamente en esta fecha.</div>',
            foot: '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-snooze-confirm">Posponer</button>' +
                  '<button type="button" class="tkt-btn" data-modal-close>Cancelar</button>',
        }));

        $backdrop.on('click', '[data-snooze-shortcut]', function () {
            $backdrop.find('[data-snooze-shortcut]').removeClass('on');
            $(this).addClass('on').find('input').prop('checked', true);
            $backdrop.find('#tkt-snooze-until').val($(this).data('snooze-shortcut'));
        });

        // Tocar la fecha a mano deselecciona el atajo: si no, la interfaz
        // afirmaría "Mañana" con otra fecha puesta.
        $backdrop.on('input', '#tkt-snooze-until', function () {
            $backdrop.find('[data-snooze-shortcut]').removeClass('on').find('input').prop('checked', false);
        });

        $backdrop.on('click', '#tkt-snooze-confirm', function () {
            var input = $('#tkt-snooze-until').val();
            if (!input) { if (window.toastr) toastr.error('Indica fecha y hora'); return; }

            $.ajax({
                url: t.url_snooze,
                method: 'POST',
                data: {
                    snoozed_until: input,
                    pause_sla: $backdrop.find('#tkt-snooze-pause-sla').is(':checked') ? 1 : 0,
                },
                headers: { Accept: 'application/json' },
                success: function (resp) {
                    if (window.toastr) toastr.success((resp && resp.message) || 'Ticket pospuesto');
                    closeModal();
                    queueTicketListRefresh('ticket-snoozed', t, {
                        freshCounts: true,
                        refreshDetail: true,
                        forceDetail: true,
                    });
                },
                error: function (xhr) {
                    var msg = (xhr.responseJSON && (xhr.responseJSON.message || (xhr.responseJSON.errors && Object.values(xhr.responseJSON.errors)[0][0]))) || 'No se pudo posponer el ticket';
                    if (window.toastr) toastr.error(msg); else window.alert(msg);
                },
            });
        });
    }

    // Referencia de pedido que el cliente escribió en el formulario, si la
    // hay. No existe columna 'order_id' en tickets: el dato vive dentro de
    // custom_fields, con nombre distinto según el formulario.
    function ticketOrderRef() {
        var d = TKA.state.currentDetail;
        var fields = (d && d.form && d.form.fields) || null;
        if (!fields) return null;

        var keys = ['order', 'pedido', 'order_id', 'order_reference', 'num_pedido', 'numero_pedido', 'id_order'];
        for (var i = 0; i < keys.length; i++) {
            var v = fields[keys[i]];
            if (v != null && String(v).trim() !== '') return String(v).trim();
        }
        return null;
    }

    function renderClientePane($c, customer, t) {
        if (!customer) {
            $c.html('<div class="tkt-empty-box">Este ticket no tiene un cliente asociado.</div>');
            return;
        }
        var badges = '';
        if (customer.tickets_count != null) badges += '<span class="tkt-tag">' + customer.tickets_count + ' tickets</span>';
        if (customer.avg_csat != null) badges += '<span class="tkt-tag">CSAT ' + customer.avg_csat + '</span>';
        // El mockup remata la fila con "Cuenta al día". Aquí el único estado
        // real de la ficha es si está vetada, así que se dice eso: bloqueada
        // cuando lo está, al día cuando no. No se inventa un estado de
        // crédito que esta app no conoce.
        badges += customer.is_banned
            ? '<span class="tkt-tag tkt-tag-warn">Cuenta bloqueada</span>'
            : '<span class="tkt-tag">Cuenta al día</span>';

        var html = '<div class="tkt-side-card">' +
                '<div class="tkt-side-card-head">Cliente</div>' +
                '<div class="tkt-side-card-body">' +
                    (customer.url_c360
                        ? '<a href="' + customer.url_c360 + '" class="tkt-person tkt-plain-link"><span class="tkt-person-avatar">' + initials(customer.name) + '</span><span class="tkt-fill"><span style="display:block;font-size:12.5px;font-weight:700">' + escapeHtml(customer.name) + '</span><span style="display:block;font-size:11px;color:var(--tkt-text-mute)">' + escapeHtml(customer.company || 'Sin empresa') + (customer.customer_since_year ? ' · cliente desde ' + customer.customer_since_year : '') + '</span></span></a>'
                        : '<div class="tkt-person"><span class="tkt-person-avatar">' + initials(customer.name) + '</span><span style="flex:1;font-size:12.5px;font-weight:700">' + escapeHtml(customer.name) + '</span></div>') +
                    '<div class="tkt-kv"><span class="k">Email</span><span class="v mono">' + escapeHtml(customer.email || '—') + '</span></div>' +
                    '<div class="tkt-kv"><span class="k">Teléfono</span><span class="v mono">' + escapeHtml(customer.phone || '—') + '</span></div>' +
                    // El móvil solo cuando es distinto del fijo: repetir el
                    // mismo número en dos filas no informa de nada.
                    (customer.whatsapp_phone && customer.whatsapp_phone !== customer.phone
                        ? '<div class="tkt-kv"><span class="k">Móvil</span><span class="v mono">' + escapeHtml(customer.whatsapp_phone) + '</span></div>'
                        : '') +
                    '<div class="tkt-kv"><span class="k">Idioma</span><span class="v">' + escapeHtml(customer.language || '—') + '</span></div>' +
                    (customer.country || customer.city
                        ? '<div class="tkt-kv"><span class="k">Ubicación</span><span class="v">' + escapeHtml([customer.city, customer.country].filter(Boolean).join(', ')) + '</span></div>'
                        : '') +
                    platformsHtml(customer) +
                    // "No está en gestión": el resultado de la búsqueda
                    // automática en el ERP, que antes solo vivía en un
                    // Log::info. El backend manda esto solo cuando la búsqueda
                    // ya corrió y falló (CustomerSummaryService::erpMissing()).
                    erpMissingHtml(customer) +
                    (badges ? '<div style="display:flex;flex-wrap:wrap;gap:5px">' + badges + '</div>' : '') +
                '</div>' +
            '</div>';

        // Identidades/fusión de duplicados — ContactsMergeController ya
        // existía (search/preview/execute) sin ningún punto de entrada
        // desde la pantalla de tickets, solo desde Contactos 360.
        if (TKA.urls.contactsMergeSearchTemplate) {
            html += '<div class="tkt-side-card"><div class="tkt-side-card-head">Identidades</div><div class="tkt-side-card-body">' +
                '<button type="button" class="tkt-btn" id="tkt-open-c360">Ficha 360 del cliente</button>' +
                '<button type="button" class="tkt-btn" id="tkt-open-identities">Canales vinculados</button>' +
                '<button type="button" class="tkt-btn" id="tkt-contact-merge-open">Fusionar contacto duplicado</button>' +
            '</div></div>';
        }

        // Tarjeta barata: todos los datos ya viajan en toListRow(), solo
        // faltaba componerlos aquí (mockup: "Detalles del ticket").
        if (t) {
            html += '<div class="tkt-side-card"><div class="tkt-side-card-head">Detalles del ticket</div><div class="tkt-side-card-body">' +
                '<div class="tkt-kv"><span class="k">Creado</span><span class="v">' + escapeHtml(t.created_at_human || '—') + '</span></div>' +
                '<div class="tkt-kv"><span class="k">Actualizado</span><span class="v">' + escapeHtml(formatDateShort(t.updated_at)) + '</span></div>' +
                '<div class="tkt-kv"><span class="k">Origen</span><span class="v">' + escapeHtml(ORIGIN_LABELS[t.source] || t.source || '—') + '</span></div>' +
                // "Pedido #829575" del mockup: sale de los campos del propio
                // formulario cuando el cliente lo indicó; si no lo hay, la
                // fila no se pinta en vez de mostrar un guion.
                (ticketOrderRef() ? '<div class="tkt-kv"><span class="k">Pedido</span><span class="v mono">' + escapeHtml(ticketOrderRef()) + '</span></div>' : '') +
                '<div class="tkt-kv"><span class="k">Categoría</span><span class="v">' + escapeHtml(t.category_name || '—') + '</span></div>' +
            '</div></div>';
        }

        // Tarjeta "Integraciones" del mockup: en qué sistemas externos está
        // dado de alta este contacto y con qué id. Los datos ya los resolvía
        // CustomerSummaryService (vía Contactos 360) y solo se pintaban
        // apelotonados como chips sueltos junto a los contadores.
        var integraciones = customer.integrations || [];
        if (integraciones.length) {
            var conectadas = integraciones.filter(function (i) { return i.connected; }).length;

            html += '<div class="tkt-side-card"><div class="tkt-side-card-head">Integraciones' +
                '<button type="button" class="tkt-side-card-act" id="tkt-int-sync" title="Volver a buscar este cliente en los sistemas externos" aria-label="Resincronizar integraciones"><i class="fa-solid fa-arrows-rotate" aria-hidden="true"></i></button>' +
                '<button type="button" class="tkt-side-card-act" id="tkt-int-open" title="Ver los canales vinculados del cliente" aria-label="Ver canales vinculados"><i class="fa-solid fa-up-right-from-square" aria-hidden="true"></i></button>' +
                '<span class="tkt-side-card-count">' + conectadas + '</span>' +
                '</div><div class="tkt-side-card-body tight">';
            integraciones.forEach(function (i) {
                // Mismo icono por plataforma que el panel del inbox; cualquier
                // integración que no sea tienda ni ERP cae en el enchufe genérico.
                var icon = i.platform === 'prestashop' ? 'fa-cart-shopping'
                    : (i.platform === 'erp' ? 'fa-clipboard-list' : 'fa-plug');

                html += '<div class="tkt-integration' + (i.connected ? '' : ' is-disconnected') + '">' +
                    '<div class="ico"><i class="fa-solid ' + icon + '" aria-hidden="true"></i></div>' +
                    '<div class="meta">' +
                        '<span class="name">' + escapeHtml(i.label || i.platform || 'Integración') + '</span>' +
                        '<span class="id mono">ID: ' + escapeHtml(i.externalId || 'sin vincular') + '</span>' +
                    '</div>' +
                    '<span class="status">' + (i.connected ? 'Conectado' : 'Desconectado') + '</span>' +
                '</div>';
            });
            html += '</div></div>';
        }

        // "Ver como el cliente" del mockup, versión segura: enlace firmado
        // de solo lectura (SharedTicketController), sin suplantar la
        // sesión del agente — ver el porqué en el docblock del controller.
        if (t && t.url_shared_ticket) {
            html += '<div class="tkt-side-card"><div class="tkt-side-card-head">Portal de cliente</div><div class="tkt-side-card-body">' +
                '<div class="tkt-note">Enlace de solo lectura, válido 30 días. El cliente ve el hilo de mensajes, no las notas internas.</div>' +
                '<button type="button" class="tkt-btn" id="tkt-shared-link-copy" data-url="' + escapeHtml(t.url_shared_ticket) + '">Copiar enlace para el cliente</button>' +
            '</div></div>';
        }

        $c.html(html);

        $c.find('#tkt-shared-link-copy').on('click', function () {
            tktCopyToClipboard($(this).data('url'), 'Enlace copiado');
        });

        // Los dos botones de la cabecera de "Integraciones" reutilizan lo que ya
        // existía en la pantalla: la resincronización real contra ERP/PrestaShop
        // (misma ruta que la ficha de Contactos 360) y el modal de canales
        // vinculados del cliente.
        $c.find('#tkt-int-sync').on('click', function () { syncCurrentCustomer($(this)); });
        $c.find('#tkt-int-open').on('click', function () {
            openIdentitiesModal(t, TKA.state.currentDetail ? TKA.state.currentDetail.identities : [], customer);
        });

        $c.find('#tkt-open-c360').on('click', function () { openCustomer360Modal(t, customer); });
        $c.find('#tkt-open-identities').on('click', function () {
            openIdentitiesModal(t, TKA.state.currentDetail ? TKA.state.currentDetail.identities : [], customer);
        });
        $c.find('#tkt-contact-merge-open').on('click', function () { openContactMergeModal(customer); });
    }

    // Fusionar contacto duplicado — ContactsMergeController::search()/
    // preview()/execute() ya existían (los usa Contactos 360), aquí solo se
    // conecta desde la pantalla de tickets. winner = el contacto de este
    // ticket; loser = el duplicado elegido, que se fusiona DENTRO del
    // winner (irreversible, por eso el checkbox de confirmación).
    function openContactMergeModal(customer) {
        var $backdrop = openModal(modalShell({
            icon: 'fa-solid fa-code-merge',
            kicker: customer.name,
            title: 'Fusionar contacto duplicado',
            width: 'md',
            body: '' +
                '<div class="tkt-field"><label class="tkt-label">Buscar el contacto duplicado</label>' +
                    '<input type="text" class="tkt-input" id="tkt-merge-search-input" placeholder="Nombre, email o teléfono…"></div>' +
                '<div id="tkt-merge-search-results"></div>' +
                '<div id="tkt-merge-preview"></div>',
            foot: '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-merge-execute-btn" disabled>Fusionar</button>' +
                  '<button type="button" class="tkt-btn" data-modal-close>Cancelar</button>',
        }));

        var searchUrl = TKA.urls.contactsMergeSearchTemplate.replace('__CUSTOMER__', customer.id);
        var selectedLoserId = null;
        var searchTimer;

        $backdrop.find('#tkt-merge-search-input').on('input', function () {
            var q = $(this).val().trim();
            clearTimeout(searchTimer);
            if (q.length < 2) { $backdrop.find('#tkt-merge-search-results').empty(); return; }
            searchTimer = setTimeout(function () {
                $.getJSON(searchUrl, { q: q, exclude_id: customer.id }).done(function (res) {
                    var results = (res && res.data) || [];
                    $backdrop.find('#tkt-merge-search-results').html(
                        results.length
                            ? results.map(function (r) {
                                return '<div class="tkt-line tkt-pointer" data-loser-id="' + r.id + '" >' +
                                    '<span class="tkt-trunc tkt-fill">' + escapeHtml(r.name || r.email || ('#' + r.id)) + '</span>' +
                                    '<span class="mono tkt-meta-xs">' + escapeHtml(r.email || r.phone || '') + '</span>' +
                                '</div>';
                            }).join('')
                            : '<div class="tkt-empty-box">Sin coincidencias.</div>'
                    );
                });
            }, 300);
        });

        $backdrop.on('click', '[data-loser-id]', function () {
            selectedLoserId = $(this).data('loser-id');
            $backdrop.find('[data-loser-id]').removeClass('on');
            $(this).addClass('on');

            var previewUrl = TKA.urls.contactsMergePreviewTemplate.replace('__CUSTOMER__', customer.id);
            $.getJSON(previewUrl, { loser_id: selectedLoserId }).done(function (res) {
                if (!res || !res.success) { $backdrop.find('#tkt-merge-preview').html('<div class="tkt-empty-box">No se pudo cargar la vista previa.</div>'); return; }
                var w = res.data.winner, l = res.data.loser;
                $backdrop.find('#tkt-merge-preview').html(
                    '<div class="tkt-note warn">Se fusionará <strong>' + escapeHtml(l.name || l.email) + '</strong> (' + l.total_conversations + ' conversaciones) dentro de <strong>' + escapeHtml(w.name || w.email) + '</strong>. Esta acción no se puede deshacer.</div>' +
                    '<label class="tkt-check"><input type="checkbox" id="tkt-merge-ack"> Entiendo que esta acción no se puede deshacer</label>'
                );
                $backdrop.find('#tkt-merge-execute-btn').prop('disabled', true);
            });
        });

        $backdrop.on('change', '#tkt-merge-ack', function () {
            $backdrop.find('#tkt-merge-execute-btn').prop('disabled', !this.checked);
        });

        $backdrop.on('click', '#tkt-merge-execute-btn', function () {
            if (!selectedLoserId) return;
            var executeUrl = TKA.urls.contactsMergeExecuteTemplate.replace('__CUSTOMER__', customer.id);
            $.ajax({
                url: executeUrl,
                method: 'POST',
                data: { loser_id: selectedLoserId },
                headers: { Accept: 'application/json' },
                success: function (resp) {
                    if (window.toastr) toastr.success((resp && resp.message) || 'Contactos fusionados');
                    closeModal();
                },
                error: function (xhr) {
                    var msg = apiErrorMessage(xhr, 'No se pudo fusionar');
                    if (window.toastr) toastr.error(msg); else window.alert(msg);
                },
            });
        });

        return $backdrop;
    }

    function formatDateShort(iso) {
        if (!iso) return '—';
        try {
            return new Date(iso).toLocaleString('es-ES', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
        } catch (e) {
            return iso;
        }
    }

    function renderFormPane($c, form, t) {
        if (!form) {
            $c.html('<div class="tkt-empty-box">Este ticket no trae campos de formulario. Origen: <strong>' + escapeHtml(ORIGIN_LABELS[t.source] || t.source || '—') + '</strong>.</div>');
            return;
        }

        function kvRows(list) {
            return (list || []).map(function (r) {
                return '<div class="tkt-kv"><span class="k">' + escapeHtml(r.label) + '</span>' +
                    '<span class="v">' + escapeHtml(r.value) + '</span></div>';
            }).join('');
        }

        // El backend separa lo que rellenó el cliente de las claves técnicas
        // del envío; si no manda esa separación (payload antiguo), se cae al
        // volcado plano de siempre para no dejar la pestaña vacía.
        var submitted = form.submitted && form.submitted.length
            ? form.submitted
            : Object.keys(form.fields || {}).map(function (k) {
                return { label: k, value: String(form.fields[k]) };
            });

        // Badge con el origen real (widget/formulario/PrestaShop según lo
        // que ya reporta sourceSlug()) — antes la cabecera no distinguía de
        // dónde viene el formulario.
        var html = '';

        // Cita destacada: lo que el cliente escribió con sus palabras,
        // separado de los campos con valor de lista.
        if (form.message) {
            html += '<div class="tkt-side-card">' +
                '<div class="tkt-side-card-head">Datos del formulario' +
                    '<span class="tkt-spacer">' + chip(ORIGIN_LABELS[t.source] || t.source, 'tkt-chip-muted') + '</span></div>' +
                '<div class="tkt-side-card-body">' +
                    '<blockquote class="tkt-form-quote">' + escapeHtml(form.message) + '</blockquote>' +
                '</div></div>';
        }

        html += '<div class="tkt-side-card"><div class="tkt-side-card-head">' +
                (form.message ? 'Campos enviados' : 'Datos del formulario') +
                (form.message
                    ? '<span class="tkt-spacer mono tkt-faint">' + submitted.length + '</span>'
                    : '<span class="tkt-spacer">' + chip(ORIGIN_LABELS[t.source] || t.source, 'tkt-chip-muted') + '</span>') +
            '</div>' +
            '<div class="tkt-side-card-body">' +
                (submitted.length ? kvRows(submitted) : '<div class="tkt-empty-box">Sin campos capturados.</div>') +
            '</div></div>';

        // "Trazabilidad del envío": página, IP, campaña y RGPD. Ninguno de
        // los formularios de esta instalación los guarda hoy, así que lo
        // habitual es que esta tarjeta no aparezca — se pinta solo si el
        // formulario capturó alguno, en vez de enseñar cuatro guiones.
        if (form.trace && form.trace.length) {
            html += '<div class="tkt-side-card"><div class="tkt-side-card-head">Trazabilidad del envío</div>' +
                '<div class="tkt-side-card-body">' + kvRows(form.trace) + '</div></div>';
        }

        if (form.attachments && form.attachments.length) {
            html += '<div class="tkt-side-card"><div class="tkt-side-card-head">Adjuntos del formulario' +
                    '<span class="tkt-spacer mono tkt-faint">' + form.attachments.length + '</span></div>' +
                '<div class="tkt-side-card-body tight">' +
                form.attachments.map(function (a) {
                    return '<a class="tkt-form-file" href="' + a.url_download + '">' +
                        '<i class="fa-regular fa-file"></i>' +
                        '<span class="n tkt-trunc">' + escapeHtml(a.name) + '</span>' +
                        '<span class="s mono">' + escapeHtml(a.size_human || '') + '</span></a>';
                }).join('') +
                '</div></div>';
        }

        html += '<div class="tkt-side-card"><div class="tkt-side-card-body">' +
                '<button type="button" class="tkt-btn" id="tkt-form-full">Ver completo</button>' +
                '<button type="button" class="tkt-btn" id="tkt-form-copy">Copiar datos</button>' +
            '</div></div>';

        $c.html(html);

        $c.find('#tkt-form-full').on('click', function () { openFormDataModal(t, form, submitted); });

        $c.find('#tkt-form-copy').on('click', function () {
            var texto = submitted.concat(form.trace || []).map(function (r) {
                return r.label + ': ' + r.value;
            }).join('\n');

            navigator.clipboard.writeText(texto).then(function () {
                if (window.toastr) toastr.success('Datos del formulario copiados');
            }).catch(function () {
                window.prompt('Copia los datos:', texto);
            });
        });
    }

    // "Ver completo" del panel de formulario: los mismos campos sin los
    // recortes que impone la columna estrecha del panel lateral.
    function openFormDataModal(t, form, submitted) {
        function tableRows(list) {
            return (list || []).map(function (r) {
                return '<tr><th>' + escapeHtml(r.label) + '</th><td>' + escapeHtml(r.value) + '</td></tr>';
            }).join('');
        }

        openModal(modalShell({
            icon: 'fa-regular fa-rectangle-list',
            kicker: 'PrestaShop · formulario',
            titleChip: t.ticket_number,
            title: 'Datos del formulario',
            width: 'lg',
            body: (form.message ? '<blockquote class="tkt-form-quote">' + escapeHtml(form.message) + '</blockquote>' : '') +
                '<table class="tkt-info-table">' + tableRows(submitted) + '</table>' +
                (form.trace && form.trace.length
                    ? '<div class="tkt-cap tkt-cap-spaced">Trazabilidad del envío</div>' +
                      '<table class="tkt-info-table">' + tableRows(form.trace) + '</table>'
                    : ''),
            foot: '<button type="button" class="tkt-btn" data-modal-close>Cerrar</button>',
        }));
    }

    function renderCorreoSidePane($c, mail, t, sideConversations, followups, mails, lastOutboundMail) {
        var d = TKA.state.currentDetail;
        var customer = d && d.customer;
        var ai = d && d.ai_suggestion;
        var esFormulario = t.source === 'formulario' || t.source === 'web_form';

        // "Origen del correo" del mockup: distingue el correo de formulario
        // (campos ya mapeados) del general (texto libre al buzón).
        var html = '<div class="tkt-side-card">' +
            '<div class="tkt-side-card-head">Origen del correo' +
                '<span class="tkt-spacer tkt-side-tag">' + escapeHtml(ORIGIN_LABELS[t.source] || t.source || '—') + '</span></div>' +
            '<div class="tkt-side-card-body tight">' +
                '<div class="tkt-origin-opt' + (esFormulario ? ' on' : '') + '">' +
                    '<span class="n">Correo de formulario</span><span class="s">campos estructurados · PrestaShop</span></div>' +
                '<div class="tkt-origin-opt' + (esFormulario ? '' : ' on') + '">' +
                    '<span class="n">Correo general</span><span class="s">texto libre al buzón de soporte</span></div>' +
            '</div></div>';

        // "Idioma y traducción": el idioma del contacto es un dato real de la
        // ficha; si no lo tiene, se dice, en vez de inventar un porcentaje de
        // confianza como el del mockup.
        html += '<div class="tkt-side-card">' +
            '<div class="tkt-side-card-head">Idioma y traducción' +
                (customer && customer.language ? '<span class="tkt-spacer tkt-side-tag">' + escapeHtml(String(customer.language).toUpperCase()) + '</span>' : '') +
            '</div>' +
            '<div class="tkt-side-card-body tight">' +
                sideRow('Escribe en', (customer && customer.language) ? customer.language : 'sin detectar', { mono: true }) +
                sideRow('Responder en', 'su idioma', { mono: true, last: true }) +
            '</div>' +
            '<div class="tkt-side-card-body">' +
                // Estado REAL de la traducción automática. El mockup los
                // dibuja como interruptores del panel, pero son ajustes
                // globales del módulo HelpdeskTranslate: se muestran en solo
                // lectura y con enlace a su pantalla, porque cambiarlos
                // desde aquí los cambiaría para toda la empresa sin avisar.
                (d && d.translation
                    ? sideRow('Responder en el idioma del cliente', d.translation.outgoing ? 'Activado' : 'Desactivado') +
                        sideRow('Traducir los correos entrantes', d.translation.incoming ? 'Activado' : 'Desactivado', { last: true }) +
                        (d.translation.url_settings
                            ? '<a class="tkt-link-btn" href="' + d.translation.url_settings + '">Ajustes de traducción →</a>'
                            : '')
                    : '') +
                '<button type="button" class="tkt-btn" id="tkt-open-translate">Traducir una respuesta</button>' +
            '</div></div>';

        // "Sugerido por IA": solo con una sugerencia real pendiente.
        if (ai && (ai.category || ai.priority)) {
            html += '<div class="tkt-side-card">' +
                '<div class="tkt-side-card-head">Sugerido por IA' +
                    '<span class="tkt-spacer"><button type="button" class="tkt-link-btn" id="tkt-open-tagging-correo">Revisar</button></span></div>' +
                '<div class="tkt-side-card-body tight">' +
                    (ai.category ? sideRow('categoría', ai.category.name, { strong: true }) : '') +
                    (ai.priority ? sideRow('prioridad', priorityLabel(ai.priority), { strong: true, last: true }) : '') +
                '</div></div>';
        }

        html += '<div class="tkt-side-card"><div class="tkt-side-card-head">Último correo del ticket</div><div class="tkt-side-card-body">';
        html += mail
            ? '<div class="tkt-kv"><span class="k">Estado</span><span class="v">' + escapeHtml(mail.status) + '</span></div>' +
              '<div class="tkt-kv"><span class="k">Enviado</span><span class="v">' + escapeHtml(mail.created_at_human) + '</span></div>' +
              '<div class="tkt-kv"><span class="k">Aperturas</span><span class="v">' + (mail.opens_count > 0 ? mail.opens_count + (mail.last_opened_human ? ' · última ' + escapeHtml(mail.last_opened_human) : '') : 'Sin abrir') + '</span></div>'
            : '<div class="tkt-empty-box">Sin correos asociados a este ticket.</div>';
        html += '</div></div>';
        html += '<div class="tkt-side-card"><div class="tkt-side-card-head">Envíos</div><div class="tkt-side-card-body">' +
            '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-compose-mail">Redactar email</button>' +
            '<button type="button" class="tkt-btn" id="tkt-mails-list-open">Correos del ticket' + (mails ? ' <span class="mono tkt-faint">(' + mails.length + ')</span>' : '') + '</button>' +
            (mail && mail.url_resend
                ? '<button type="button" class="tkt-btn" id="tkt-resend-mail">Reenviar último correo</button>'
                : '<button type="button" class="tkt-btn" disabled title="Sin correos que reenviar">Reenviar último correo</button>') +
        '</div></div>';

        // Conversación paralela — hilo privado con un compañero o un
        // contacto externo, invisible para el cliente. Reusa
        // TicketSideConversationsController (store/addMessage), que ya
        // existía sin ninguna UI de agente que lo consumiera.
        var sideList = (sideConversations || []).map(function (s) {
            return '<div class="tkt-line tkt-pointer" data-side-id="' + s.id + '" >' +
                '<span class="tkt-trunc tkt-fill">' + escapeHtml(s.subject) + '</span>' +
                '<span class="tkt-chip tkt-chip-muted">' + escapeHtml(s.status) + '</span>' +
                '<span class="mono tkt-meta-xs">' + s.message_count + '</span>' +
            '</div>';
        }).join('');
        html += '<div class="tkt-side-card"><div class="tkt-side-card-head">Conversación paralela<span class="tkt-spacer mono tkt-faint">' + (sideConversations ? sideConversations.length : 0) + '</span></div><div class="tkt-side-card-body">' +
            (sideList || '<div class="tkt-empty-box">Sin conversaciones paralelas todavía.</div>') +
            '<button type="button" class="tkt-btn" id="tkt-side-new">Nueva conversación paralela</button>' +
        '</div></div>';

        // Seguimientos programados (TicketFollowup) — recordatorios propios
        // sobre este ticket, notificados por el comando programado
        // helpdesk:send-due-ticket-followups. Ya funcionaba en la ficha
        // antigua show.blade.php; aquí solo se porta la UI.
        var FOLLOWUP_STATE = {
            sent: { label: 'avisado', cls: 'ok' },
            cancelled: { label: 'cancelado', cls: 'muted' },
            pending: { label: 'pendiente', cls: '' },
        };

        var pasosPendientes = (followups || []).filter(function (f) { return f.state === 'pending'; }).length;

        var followupsList = (followups || []).map(function (f) {
            var estado = FOLLOWUP_STATE[f.state] || FOLLOWUP_STATE.pending;
            return '<div class="tkt-line tkt-followup' + (f.state !== 'pending' ? ' done' : '') + '" data-followup-id="' + f.id + '">' +
                (f.step ? '<span class="tkt-step-num sm">' + f.step + '</span>' : '') +
                '<span class="tkt-trunc tkt-fill">' + escapeHtml(f.scheduled_at_human || '') +
                    (f.note ? ' — ' + escapeHtml(f.note) : '') + '</span>' +
                '<span class="tkt-chip tkt-chip-' + (estado.cls || 'muted') + '">' + estado.label + '</span>' +
                // Un paso ya avisado o cancelado no se puede cancelar otra vez.
                (f.state === 'pending'
                    ? '<button type="button" class="tkt-btn-icon" data-followup-cancel="' + f.id + '" title="Cancelar"><i class="fa-solid fa-xmark"></i></button>'
                    : '') +
            '</div>';
        }).join('');

        html += '<div class="tkt-side-card"><div class="tkt-side-card-head">Seguimientos<span class="tkt-spacer mono tkt-faint">' + (followups ? followups.length : 0) + '</span></div><div class="tkt-side-card-body">' +
            (followupsList || '<div class="tkt-empty-box">Sin seguimientos programados.</div>') +
            // El aviso de "se detiene si responde" solo tiene sentido con
            // pasos vivos por delante.
            (pasosPendientes && followups.some(function (f) { return f.cancel_if_customer_replies; })
                ? '<div class="tkt-note"><i class="fa-solid fa-circle-info"></i> La secuencia se detiene sola si el cliente responde.</div>'
                : '') +
            '<button type="button" class="tkt-btn" id="tkt-followup-new">Programar seguimiento</button>' +
            (pasosPendientes > 1
                ? '<button type="button" class="tkt-btn" id="tkt-followup-cancel-all">Cancelar la secuencia</button>'
                : '') +
        '</div></div>';

        $c.html(html);

        $c.find('#tkt-followup-new').on('click', function () { openFollowupModal(t); });
        $c.find('#tkt-followup-cancel-all').on('click', function () {
            openConfirmModal({
                icon: 'fa-regular fa-bell-slash',
                title: 'Cancelar la secuencia',
                message: 'Se cancelarán todos los pasos que aún no se han avisado.',
                confirmLabel: 'Cancelar la secuencia',
                danger: true,
                onConfirm: function () {
                    $.ajax({
                        url: t.url_followups_destroy_all,
                        method: 'DELETE',
                        headers: { Accept: 'application/json' },
                        success: function (resp) {
                            if (window.toastr) toastr.success((resp && resp.message) || 'Secuencia cancelada');
                            fetchDetailData(t);
                        },
                        error: function () { if (window.toastr) toastr.error('No se pudo cancelar la secuencia'); },
                    });
                },
            });
        });
        $c.find('[data-followup-cancel]').on('click', function () { cancelFollowup(t, $(this).data('followup-cancel')); });

        // openResendMailModal necesita el ticket y el correo SALIENTE: 'mail'
        // es el más reciente sea cual sea su dirección, y reenviar uno
        // entrante lo mandaría a la propia bandeja de soporte.
        $c.find('#tkt-resend-mail').on('click', function () { openResendMailModal(t, lastOutboundMail || mail); });

        $c.find('#tkt-side-new').on('click', function () { newSideConversation(t); });
        $c.find('[data-side-id]').on('click', function () { addSideConversationMessage(t, $(this).data('side-id')); });
        $c.find('#tkt-compose-mail').on('click', function () { openComposeModal(t); });
        $c.find('#tkt-open-translate').on('click', function () {
            // El texto puede estar en el composer del hilo (lo habitual) o en
            // el modal de redactar. Antes solo miraba el segundo y mandaba a
            // abrir "Redactar email" aunque hubiera una respuesta escrita.
            if ($('#tkt-reply-body').val() && $('#tkt-reply-body').val().trim()) {
                openTranslateModal(t, false);
                return;
            }

            if (!composeDraft || composeDraft.ticketId !== t.id || !composeDraft.body) {
                openComposeModal(t);
                if (window.toastr) toastr.info('Escribe la respuesta y pulsa el icono de idioma para traducirla');
                return;
            }

            openTranslateModal(t, true);
        });
        $c.find('#tkt-open-tagging-correo').on('click', function () {
            openAiTaggingModal(t, TKA.state.currentDetail ? TKA.state.currentDetail.ai_suggestion : null);
        });
        $c.find('#tkt-mails-list-open').on('click', function () { openMailsListModal(t, mails); });
    }

    // Seguidores reales (TicketWatcher) — antes solo existía auto-seguirse
    // sin ninguna lista visible; ahora también se puede añadir a un
    // compañero (TicketLifecycleController::watch ampliado para aceptar
    // user_id, con permiso helpdesk.tickets.update de por medio).
    function openFollowersModal(t) {
        var d = TKA.state.currentDetail;
        var watchers = (d && d.watchers) || [];

        function rowsHtml() {
            if (!watchers.length) return '<div class="tkt-empty-box">Nadie sigue este ticket todavía.</div>';
            return watchers.map(function (w) {
                // Qué recibe cada seguidor. Antes seguir un ticket no
                // mandaba ningún aviso, así que no había nada que elegir;
                // ahora NotifyTicketWatchers respeta estas dos casillas.
                return '<div class="tkt-follower">' +
                    '<div class="tkt-line"><span class="tkt-flex1">' + escapeHtml(w.name) +
                        (w.is_me ? ' <span class="tkt-chip tkt-chip-muted">tú</span>' : '') + '</span>' +
                        '<button type="button" class="tkt-btn-icon" data-follower-remove="' + w.user_id + '" title="Quitar"><i class="fa-solid fa-xmark"></i></button></div>' +
                    '<div class="tkt-follower-prefs">' +
                        '<label class="tkt-check sm"><input type="checkbox" data-follower-pref="replies" data-user="' + w.user_id + '"' +
                            (w.notify_customer_replies !== false ? ' checked' : '') + '> respuestas del cliente</label>' +
                        '<label class="tkt-check sm"><input type="checkbox" data-follower-pref="notes" data-user="' + w.user_id + '"' +
                            (w.notify_internal_notes !== false ? ' checked' : '') + '> notas internas</label>' +
                    '</div>' +
                '</div>';
            }).join('');
        }

        var $backdrop = openModal(modalShell({
            icon: 'fa-solid fa-users',
            kicker: 'Ticket · seguidores',
            titleChip: t.ticket_number,
            title: 'Seguidores del ticket',
            width: 'md',
            body: '<div id="tkt-followers-list">' + rowsHtml() + '</div>' +
                '<div class="tkt-field"><label class="tkt-label">Añadir compañero</label>' +
                    '<select class="tkt-select" id="tkt-follower-add"><option value="">Selecciona un agente…</option>' + optionsHtml(TKA.state.agentsFull, 'id', '') + '</select></div>',
            foot: '<button type="button" class="tkt-btn" data-modal-close>Cerrar</button>',
        }));

        function refresh() {
            $backdrop.find('#tkt-followers-list').html(rowsHtml());
        }

        $backdrop.on('change', '[data-follower-pref]', function () {
            var userId = $(this).data('user');
            var $fila = $backdrop.find('[data-user="' + userId + '"]');
            var replies = $fila.filter('[data-follower-pref="replies"]').is(':checked');
            var notes = $fila.filter('[data-follower-pref="notes"]').is(':checked');

            $.ajax({
                url: t.url_watch,
                method: 'POST',
                data: {
                    user_id: userId,
                    notify_customer_replies: replies ? 1 : 0,
                    notify_internal_notes: notes ? 1 : 0,
                },
                headers: { Accept: 'application/json' },
                success: function () {
                    watchers.forEach(function (w) {
                        if (w.user_id !== userId) return;
                        w.notify_customer_replies = replies;
                        w.notify_internal_notes = notes;
                    });
                    if (d) d.watchers = watchers;
                    if (window.toastr) toastr.success('Preferencias de aviso guardadas');
                },
                error: function () {
                    if (window.toastr) toastr.error('No se pudieron guardar las preferencias');
                },
            });
        });

        $backdrop.on('click', '[data-follower-remove]', function () {
            var userId = $(this).data('follower-remove');
            $.ajax({
                url: t.url_unwatch, method: 'DELETE', data: { user_id: userId }, headers: { Accept: 'application/json' },
                success: function () {
                    watchers = watchers.filter(function (w) { return w.user_id !== userId; });
                    refresh();
                    if (d) d.watchers = watchers;
                },
                error: function (xhr) {
                    var msg = apiErrorMessage(xhr, 'No se pudo quitar el seguidor');
                    if (window.toastr) toastr.error(msg); else window.alert(msg);
                },
            });
        });

        $backdrop.on('change', '#tkt-follower-add', function () {
            var agentId = parseInt($(this).val(), 10);
            if (!agentId) return;
            var agent = (TKA.state.agentsFull || []).find(function (a) { return a.id === agentId; });
            $.ajax({
                url: t.url_watch, method: 'POST', data: { user_id: agentId }, headers: { Accept: 'application/json' },
                success: function () {
                    if (!watchers.some(function (w) { return w.user_id === agentId; })) {
                        watchers.push({ user_id: agentId, name: agent ? agent.name : ('Usuario #' + agentId), is_me: false });
                    }
                    refresh();
                    if (d) d.watchers = watchers;
                    $('#tkt-follower-add').val('');
                },
                error: function (xhr) {
                    var msg = apiErrorMessage(xhr, 'No se pudo añadir el seguidor');
                    if (window.toastr) toastr.error(msg); else window.alert(msg);
                },
            });
        });
    }

    // Eliminar (soft-delete real) o archivar — dos acciones distintas del
    // backend (TicketsCrudController::destroy / TicketLifecycleController::
    // archive), presentadas como opciones excluyentes en un mismo modal en
    // vez de dos botones sueltos, con la fricción de un checkbox para la
    // irreversible.
    function openDeleteModal(t) {
        var $backdrop = openModal(modalShell({
            icon: 'fa-solid fa-trash',
            iconClass: 'danger',
            kicker: t.ticket_number,
            title: 'Eliminar ticket',
            width: 'md',
            body: '' +
                '<label class="tkt-option on" data-delete-option="archive"><input type="radio" name="tkt-delete-mode" value="archive" checked class="tkt-m0">' +
                    '<span><span class="tkt-option-title">Archivar</span><br><span class="tkt-option-sub">Se oculta de las vistas activas, se puede restaurar</span></span></label>' +
                '<label class="tkt-option" data-delete-option="destroy"><input type="radio" name="tkt-delete-mode" value="destroy" class="tkt-m0">' +
                    '<span><span class="tkt-option-title">Eliminar</span><br><span class="tkt-option-sub">Borrado (recuperable solo desde la papelera técnica)</span></span></label>' +
                '<label class="tkt-check"><input type="checkbox" id="tkt-delete-ack"> Entiendo lo que va a pasar</label>',
            foot: '<button type="button" class="tkt-btn tkt-btn-danger" id="tkt-delete-confirm" disabled>Confirmar</button>' +
                  '<button type="button" class="tkt-btn" data-modal-close>Cancelar</button>',
        }));

        $backdrop.on('click', '[data-delete-option]', function () {
            $backdrop.find('[data-delete-option]').removeClass('on');
            $(this).addClass('on').find('input').prop('checked', true);
        });
        $backdrop.on('change', '#tkt-delete-ack', function () {
            $('#tkt-delete-confirm').prop('disabled', !this.checked);
        });

        $backdrop.on('click', '#tkt-delete-confirm', function () {
            var mode = $backdrop.find('input[name="tkt-delete-mode"]:checked').val();
            var url = mode === 'archive' ? t.url_archive : t.url_destroy;
            $.ajax({
                url: url,
                method: mode === 'archive' ? 'POST' : 'DELETE',
                headers: { Accept: 'application/json' },
                success: function (resp) {
                    if (window.toastr) toastr.success((resp && resp.message) || 'Hecho');
                    window.location = TKA.urls.index;
                },
                error: function (xhr) {
                    var msg = apiErrorMessage(xhr, 'No se pudo completar la acción');
                    if (window.toastr) toastr.error(msg); else window.alert(msg);
                },
            });
        });
    }

    // Pill "Cola" — OpsHealthService::cached() ya alimentaba el dashboard de
    // reports; aquí se reusa tal cual (mismas 3 sondas: profundidad de
    // colas, dead-letter, breaches SLA última hora). "Entregabilidad"/
    // "Avisos" siguen fuera de alcance — no hay sonda real para esos.
    function openQueueModal() {
        var $backdrop = openModal(modalShell({
            icon: 'fa-solid fa-layer-group',
            kicker: 'Operación · Cola',
            title: 'Cola y reintentos',
            width: 'md',
            body: '<div class="tkt-empty-box">Cargando…</div>',
            foot: '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-queue-retry-all">Reintentar todos</button>' +
                  '<button type="button" class="tkt-btn" id="tkt-queue-flush">Purgar dead-letters</button>' +
                  '<button type="button" class="tkt-btn" data-modal-close>Cerrar</button>',
        }));

        function load() {
            $.getJSON(TKA.urls.ops).done(function (res) {
                var s = res && res.snapshot;
                if (!s) { renderModalError($backdrop, 'No se pudo cargar el estado de las colas.'); return; }

                var queueRows = Object.keys(s.queues || {}).map(function (name) {
                    return '<div class="tkt-line"><span class="tkt-line-main">' + escapeHtml(name) + '</span><span class="mono">' + s.queues[name] + '</span></div>';
                }).join('') || '<div class="tkt-empty-box">Sin colas configuradas.</div>';

                // Jobs en dead-letter, uno a uno y reintentables por separado:
                // en un incidente casi nunca hay que reintentarlos todos, solo
                // los que fallaron por la causa ya resuelta.
                var failed = s.failed_jobs_sample || [];
                var failedRows = failed.length
                    ? failed.map(function (j) {
                        return '<div class="tkt-qjob">' +
                            '<div class="tkt-qjob-main">' +
                                '<div class="tkt-qjob-name">' + escapeHtml(j.job) + '<span class="tkt-chip-mono">' + escapeHtml(j.queue) + '</span></div>' +
                                '<div class="tkt-qjob-error" title="' + escapeHtml(j.error) + '">' + escapeHtml(j.error) + '</div>' +
                            '</div>' +
                            '<button type="button" class="tkt-btn tkt-btn-sm" data-queue-retry="' + escapeHtml(j.uuid) + '">Reintentar</button>' +
                        '</div>';
                    }).join('')
                    : '<div class="tkt-note ok">No hay jobs en dead-letter.</div>';

                var html = '' +
                    '<div class="tkt-kpi3">' +
                        '<div><div class="v">' + (s.queue_total ?? 0) + '</div><div class="l">En cola</div></div>' +
                        '<div><div class="v">' + (s.failed_jobs ?? '—') + '</div><div class="l">Dead-letter</div></div>' +
                        '<div><div class="v">' + (s.sla_breaches_last_hour ?? '—') + '</div><div class="l">SLA vencido (1h)</div></div>' +
                    '</div>' +
                    '<div class="tkt-field"><label class="tkt-label">Jobs fallidos</label>' + failedRows + '</div>' +
                    '<div class="tkt-field"><label class="tkt-label">Por cola</label>' + queueRows + '</div>' +
                    (s.unassigned_sla_warning > 0 ? '<div class="tkt-note warn">' + s.unassigned_sla_warning + ' ticket(s) sin asignar con SLA próximo a vencer.</div>' : '') +
                    '<div class="tkt-modal-note">Actualizado ' + escapeHtml(s.generated_at || '') + '</div>';

                $backdrop.find('.tkt-modal-body').html(html);
                $backdrop.find('#tkt-queue-retry-all, #tkt-queue-flush').prop('disabled', failed.length === 0);
            }).fail(function () {
                renderModalError($backdrop, 'No se pudo cargar el estado de las colas.');
            });
        }

        function retry(uuid, label) {
            $.ajax({
                url: TKA.urls.queueRetry,
                method: 'POST',
                data: uuid ? { uuid: uuid } : {},
                headers: { Accept: 'application/json' },
            }).done(function (resp) {
                if (window.toastr) toastr.success((resp && resp.message) || label);
                load();
            }).fail(function (xhr) {
                var msg = apiErrorMessage(xhr, xhr.status === 403
                    ? 'Hace falta permiso de ajustes del módulo para reencolar jobs.'
                    : 'No se pudieron reencolar los jobs.');
                if (window.toastr) toastr.error(msg); else window.alert(msg);
            });
        }

        $backdrop.on('click', '[data-queue-retry]', function () { retry($(this).data('queue-retry'), 'Job reencolado.'); });
        $backdrop.on('click', '#tkt-queue-retry-all', function () { retry(null, 'Jobs reencolados.'); });

        // Purgar borra los jobs para siempre: se confirma antes, igual que
        // el resto de acciones destructivas de esta pantalla.
        $backdrop.on('click', '#tkt-queue-flush', function () {
            openConfirmModal({
                icon: 'fa-solid fa-trash',
                danger: true,
                title: 'Purgar la dead-letter',
                message: 'Se eliminarán todos los jobs fallidos. No se pueden recuperar ni reintentar después.',
                confirmLabel: 'Purgar',
                onConfirm: function () {
                    $.ajax({
                        url: TKA.urls.queueFlush,
                        method: 'POST',
                        headers: { Accept: 'application/json' },
                    }).done(function (resp) {
                        if (window.toastr) toastr.success((resp && resp.message) || 'Dead-letter purgada.');
                        openQueueModal();
                    }).fail(function (xhr) {
                        var msg = apiErrorMessage(xhr, xhr.status === 403
                            ? 'Hace falta permiso de ajustes del módulo para purgar la cola.'
                            : 'No se pudo purgar la cola de fallidos.');
                        if (window.toastr) toastr.error(msg); else window.alert(msg);
                    });
                },
            });
        });

        load();
    }

    // Pill "Carga" — AssignmentService::getAgentWorkload() ya existía (la
    // usa la asignación automática al crear ticket) pero sin ningún punto
    // de entrada HTTP para verla desde aquí. Sin "capacidad"/"% ocupación":
    // no hay ninguna columna de capacidad máxima configurada por agente.
    function openWorkloadModal() {
        var data = null;
        var vista = 'agents';        // 'agents' | 'teams'
        var verTodos = false;        // incluir agentes sin carga
        var busqueda = '';
        var equipoAbierto = null;    // id del equipo desplegado
        var confirmandoReparto = false;
        var confirmTimer = null;

        var $backdrop = openModal(modalShell({
            icon: 'fa-solid fa-scale-balanced',
            kicker: 'Equipo · carga',
            title: 'Reparto y capacidad',
            width: 'xl',
            body: '<div class="tkt-empty-box">Cargando…</div>',
            foot: '<button type="button" class="tkt-btn" data-modal-close>Cerrar</button>',
        }));

        // ── helpers de pintado ───────────────────────────────────────────────

        function plural(n, singular, prural) {
            return n + ' ' + (n === 1 ? singular : prural);
        }

        // La barra no representa ocupación sobre una capacidad (no existe), sino
        // la carga de cada agente respecto al más cargado. En 10 tramos porque el
        // ancho tiene que salir de una clase: en este proyecto no se escriben
        // estilos inline.
        function barra(abiertos, maximo) {
            if (!maximo || abiertos <= 0) return '';
            var tramo = Math.max(1, Math.ceil((abiertos / maximo) * 10));
            return '<div class="tkt-wl-bar"><span class="tkt-wl-bar-fill lvl-' + tramo + '"></span></div>';
        }

        function filaAgente(a, maximo) {
            var sub = plural(a.open_tickets, 'abierto', 'abiertos');
            if (a.at_risk > 0) sub += ' · <span class="tkt-wl-risk">' + plural(a.at_risk, 'en riesgo', 'en riesgo') + '</span>';

            return '<div class="tkt-wl-item' + (a.at_risk > 0 ? ' risk' : '') + '">' +
                '<div class="tkt-line">' +
                    '<span class="tkt-wl-av">' + escapeHtml(initials(a.name)) + '</span>' +
                    '<span class="tkt-line-main">' +
                        '<span class="tkt-line-title tkt-trunc">' + escapeHtml(a.name) + '</span>' +
                        '<span class="tkt-line-sub">' + sub + '</span>' +
                    '</span>' +
                    // "No recibe reparto" es el dato que faltaba: explica por qué
                    // un agente cargado no baja nunca (vacaciones, ausencia, turno
                    // fuera de horario o sin el rol).
                    (a.eligible ? '' : chip('no recibe reparto', 'tkt-chip-warn')) +
                    '<span class="tkt-chip tkt-chip-muted mono">' + a.open_tickets + '</span>' +
                '</div>' +
                barra(a.open_tickets, maximo) +
            '</div>';
        }

        function agentesFiltrados() {
            var q = busqueda.trim().toLowerCase();

            return (data.agents || []).filter(function (a) {
                if (q && a.name.toLowerCase().indexOf(q) === -1) return false;
                if (!verTodos && !q && a.open_tickets === 0 && a.at_risk === 0) return false;
                return true;
            });
        }

        function vistaAgentes() {
            if (!(data.agents || []).length) {
                return '<div class="tkt-empty-box">No hay agentes disponibles ni tickets asignados.</div>';
            }

            var visibles = agentesFiltrados();
            var maximo = (data.agents[0] && data.agents[0].open_tickets) || 0;
            var ocultos = (data.agents || []).length - visibles.length;

            var lista = visibles.length
                ? visibles.map(function (a) { return filaAgente(a, maximo); }).join('')
                : '<div class="tkt-empty-box">Ningún agente coincide con la búsqueda.</div>';

            var pie = '';
            if (ocultos > 0 && !busqueda) {
                pie = '<button type="button" class="tkt-link-btn tkt-wl-more" id="tkt-wl-toggle-all">' +
                    (verTodos ? 'Ocultar los agentes sin tickets abiertos' : 'Mostrar ' + ocultos + ' agente(s) sin tickets abiertos') +
                    '</button>';
            }

            return lista + pie;
        }

        function vistaEquipos() {
            var equipos = (data.teams || []).slice();
            var porId = {};
            (data.agents || []).forEach(function (a) { porId[a.id] = a; });

            // "Sin equipo" no viene del servidor (no existe como grupo): se compone
            // aquí con los agentes cuyo team_ids está vacío, para no inventar un
            // equipo que nadie ha creado.
            var huerfanos = (data.agents || []).filter(function (a) { return !a.team_ids.length; });
            if (huerfanos.length) {
                equipos.push({
                    id: 0,
                    name: 'Sin equipo',
                    is_active: true,
                    agents: huerfanos.length,
                    open_tickets: huerfanos.reduce(function (t, a) { return t + a.open_tickets; }, 0),
                    at_risk: huerfanos.reduce(function (t, a) { return t + a.at_risk; }, 0),
                    agent_ids: huerfanos.map(function (a) { return a.id; }),
                });
            }

            if (!equipos.length) {
                return '<div class="tkt-empty-box">No hay equipos con agentes asignados. Los grupos se gestionan en Ajustes → Grupos.</div>';
            }

            var maximo = equipos.reduce(function (m, e) { return Math.max(m, e.open_tickets); }, 0);

            return equipos.map(function (e) {
                var sub = plural(e.agents, 'agente', 'agentes') + ' · ' + plural(e.open_tickets, 'abierto', 'abiertos');
                if (e.at_risk > 0) sub += ' · <span class="tkt-wl-risk">' + e.at_risk + ' en riesgo</span>';

                var miembros = '';
                if (equipoAbierto === e.id) {
                    var filas = e.agent_ids
                        .map(function (id) { return porId[id]; })
                        .filter(function (a) { return a && (a.open_tickets > 0 || a.at_risk > 0 || verTodos); })
                        .map(function (a) { return filaAgente(a, (data.agents[0] && data.agents[0].open_tickets) || 0); })
                        .join('');
                    miembros = '<div class="tkt-wl-members">' +
                        (filas || '<div class="tkt-empty-box">Ningún miembro tiene tickets abiertos.</div>') +
                        '</div>';
                }

                return '<div class="tkt-wl-item' + (e.at_risk > 0 ? ' risk' : '') + '">' +
                    '<button type="button" class="tkt-line tkt-w-100 tkt-wl-team" data-team="' + e.id + '">' +
                        '<span class="tkt-line-main">' +
                            '<span class="tkt-line-title tkt-trunc">' + escapeHtml(e.name) +
                                (e.is_active ? '' : ' ' + chip('inactivo', 'tkt-chip-muted')) + '</span>' +
                            '<span class="tkt-line-sub">' + sub + '</span>' +
                        '</span>' +
                        '<i class="fa-solid ' + (equipoAbierto === e.id ? 'fa-chevron-up' : 'fa-chevron-down') + ' tkt-wl-caret"></i>' +
                        '<span class="tkt-chip tkt-chip-muted mono">' + e.open_tickets + '</span>' +
                    '</button>' +
                    barra(e.open_tickets, maximo) +
                    miembros +
                '</div>';
            }).join('');
        }

        function resumenHtml() {
            var conCarga = (data.agents || []).filter(function (a) { return a.open_tickets > 0; }).length;
            var disponibles = (data.agents || []).filter(function (a) { return a.eligible; }).length;
            var enRiesgo = (data.agents || []).reduce(function (t, a) { return t + a.at_risk; }, 0);
            var estrategia = etiquetaEstrategia(data.assignment.strategy);

            return '<div class="tkt-kv-grid">' +
                '<span>sin asignar</span><span>' + plural(data.unassigned.total, 'ticket', 'tickets') + '</span>' +
                '<span>en riesgo</span><span>' + enRiesgo + ' en ' + plural(conCarga, 'agente', 'agentes') + '</span>' +
                '<span>agentes disponibles</span><span>' + disponibles + '</span>' +
                // El mockup pone aquí "capacidad por agente: 12 abiertos". No hay
                // ninguna capacidad de tickets configurada en la base, así que se
                // dice eso en vez de calcular un porcentaje inventado.
                (data.capacity === null ? '<span>capacidad por agente</span><span>no configurada</span>' : '') +
                '<span>reparto</span><span>' + escapeHtml(estrategia) + (data.assignment.enabled ? '' : ' (desactivado)') + '</span>' +
            '</div>';
        }

        function etiquetaEstrategia(valor) {
            var found = (data.assignment.strategies || []).filter(function (s) { return s.value === valor; })[0];
            return found ? found.label : valor;
        }

        function ajustesHtml() {
            var a = data.assignment;

            if (!data.can_manage) {
                // Sin permiso de ajustes se ve la configuración, no se toca.
                return '<div class="tkt-field">' +
                    '<label class="tkt-label">Reparto automático</label>' +
                    '<div class="tkt-note">' +
                        (a.enabled ? 'Activado' : 'Desactivado') + ' · ' + escapeHtml(etiquetaEstrategia(a.strategy)) +
                        '. Cambiarlo requiere permiso de configuración de tickets.' +
                    '</div>' +
                '</div>';
            }

            var opciones = (a.strategies || []).map(function (s) {
                return '<button type="button" class="tkt-option' + (s.value === a.strategy ? ' on' : '') + '" data-strategy="' + escapeHtml(s.value) + '">' +
                    '<span class="tkt-fill">' +
                        '<span class="tkt-option-title">' + escapeHtml(s.label) +
                            // Una estrategia que el módulo Helpdesk ofrece pero que
                            // el lado de tickets no sabe ejecutar dejaría los
                            // tickets sin asignar en silencio: se avisa.
                            (s.supported ? '' : ' ' + chip('no aplica a tickets', 'tkt-chip-warn')) +
                        '</span>' +
                        '<span class="tkt-option-sub">' + escapeHtml(s.description) + '</span>' +
                    '</span>' +
                '</button>';
            }).join('');

            return '<div class="tkt-field">' +
                '<label class="tkt-label">Reparto automático</label>' +
                '<label class="tkt-check"><input type="checkbox" id="tkt-wl-enabled"' + (a.enabled ? ' checked' : '') + '> ' +
                    'Repartir automáticamente los nuevos tickets</label>' +
                '<div class="tkt-wl-strategies" id="tkt-wl-strategies">' + opciones + '</div>' +
                '<label class="tkt-check"><input type="checkbox" id="tkt-wl-language"' + (a.language_routing ? ' checked' : '') + '> ' +
                    'Preferir agentes que hablen el idioma del ticket</label>' +
                '<div class="tkt-note"><i class="fa-solid fa-circle-info"></i> El interruptor y la estrategia son ajustes globales del helpdesk: ' +
                    'los comparten los tickets y las conversaciones. La preferencia por idioma solo afecta a tickets.</div>' +
                '<button type="button" class="tkt-btn tkt-btn-primary tkt-w-100" id="tkt-wl-save">Guardar reparto</button>' +
            '</div>';
        }

        // Avisos que hacen honesta la cifra del botón: se ve ANTES de pulsar
        // cuántos se reparten de verdad y por qué el resto no.
        function avisosRepartoHtml() {
            var u = data.unassigned;
            var avisos = [];

            if (u.total === 0) {
                return '<div class="tkt-note ok">No hay tickets sin asignar ahora mismo.</div>';
            }

            if (u.closed > 0) {
                avisos.push(plural(u.closed, 'ticket cerrado', 'tickets cerrados') + ' sin asignar entran también en el reparto: ' +
                    'el endpoint no los excluye.');
            }

            // El reparto pide el pool de agentes filtrando por la categoría del
            // ticket; hoy ese filtro revienta y el ticket se queda sin asignar sin
            // ningún error visible. Decirlo aquí evita el "he pulsado y no ha
            // pasado nada".
            if (data.assignment.category_pool_available === false && u.with_category > 0) {
                avisos.push(plural(u.with_category, 'ticket tiene', 'tickets tienen') + ' categoría y el reparto no encuentra agentes ' +
                    'para ellos: el filtro por categoría no está operativo.');
            }

            if (u.total > u.per_run_limit) {
                avisos.push('Se reparten como máximo ' + u.per_run_limit + ' por pulsación.');
            }

            if (!avisos.length) {
                return '<div class="tkt-note"><i class="fa-solid fa-circle-info"></i> Se repartirán ' + u.total +
                    ' ticket(s) por carga, ponderando la prioridad.</div>';
            }

            return '<div class="tkt-note warn"><i class="fa-solid fa-triangle-exclamation"></i><span>' +
                avisos.join(' ') + '</span></div>';
        }

        function pieHtml() {
            var u = data.unassigned;
            var repartir = '';

            if (data.can_distribute) {
                repartir = u.total > 0
                    ? '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-workload-distribute">Repartir ' + u.total + ' sin asignar</button>'
                    : '<button type="button" class="tkt-btn tkt-btn-primary" disabled>No hay tickets sin asignar</button>';
            }

            return repartir +
                '<button type="button" class="tkt-btn" id="tkt-wl-view">' +
                    (vista === 'agents' ? 'Ver por equipos' : 'Ver por agentes') +
                '</button>' +
                '<button type="button" class="tkt-btn" data-modal-close>Cerrar</button>';
        }

        function render() {
            var titulo = vista === 'agents' ? 'Carga por agente' : 'Carga por equipo';
            var pieDeLista = vista === 'agents'
                ? '<div class="tkt-cap">Ordenado de más a menos carga. La barra es relativa al agente con más tickets abiertos, no a una capacidad.</div>'
                : '<div class="tkt-cap">Carga sumada de los miembros de cada grupo. Pulsa un equipo para ver quién la lleva.</div>';

            var buscador = vista === 'agents'
                ? '<input type="text" class="tkt-input" id="tkt-wl-search" placeholder="Buscar agente…" value="' + escapeHtml(busqueda) + '">'
                : '';

            $backdrop.find('.tkt-modal-body').html(
                '<div class="tkt-field">' +
                    '<label class="tkt-label">' + titulo + '</label>' +
                    buscador +
                    '<div class="tkt-wl-list">' + (vista === 'agents' ? vistaAgentes() : vistaEquipos()) + '</div>' +
                    pieDeLista +
                '</div>' +
                resumenHtml() +
                avisosRepartoHtml() +
                ajustesHtml()
            );

            $backdrop.find('.tkt-modal-foot').html(pieHtml());
        }

        // Repinta SOLO la lista. Buscar y desplegar equipos pasan por aquí: un
        // render() completo reconstruiría el <input> en cada tecla y el foco se
        // perdería a la primera letra.
        function renderLista() {
            $backdrop.find('.tkt-wl-list').html(vista === 'agents' ? vistaAgentes() : vistaEquipos());
        }

        function load() {
            $.getJSON(TKA.urls.workloadOverview).done(function (res) {
                if (!res || !res.agents) {
                    renderModalError($backdrop, 'No se pudo cargar la carga por agente.');
                    return;
                }
                data = res;
                render();
            }).fail(function () {
                renderModalError($backdrop, 'No se pudo cargar la carga por agente.');
            });
        }

        // ── eventos ──────────────────────────────────────────────────────────

        $backdrop.on('click', '#tkt-wl-view', function () {
            vista = vista === 'agents' ? 'teams' : 'agents';
            equipoAbierto = null;
            render();
        });

        $backdrop.on('click', '#tkt-wl-toggle-all', function () {
            verTodos = !verTodos;
            renderLista();
        });

        $backdrop.on('click', '.tkt-wl-team', function () {
            var id = parseInt($(this).data('team'), 10);
            equipoAbierto = equipoAbierto === id ? null : id;
            renderLista();
        });

        $backdrop.on('input', '#tkt-wl-search', function () {
            busqueda = $(this).val() || '';
            renderLista();
        });

        $backdrop.on('click', '#tkt-wl-strategies .tkt-option', function () {
            $backdrop.find('#tkt-wl-strategies .tkt-option').removeClass('on');
            $(this).addClass('on');
        });

        $backdrop.on('click', '#tkt-wl-save', function () {
            var $btn = $(this).prop('disabled', true).text('Guardando…');

            $.ajax({
                url: TKA.urls.workloadAssignment,
                method: 'POST',
                headers: { Accept: 'application/json' },
                data: {
                    enabled: $backdrop.find('#tkt-wl-enabled').is(':checked') ? 1 : 0,
                    strategy: $backdrop.find('#tkt-wl-strategies .tkt-option.on').data('strategy') || data.assignment.strategy,
                    language_routing: $backdrop.find('#tkt-wl-language').is(':checked') ? 1 : 0,
                },
                success: function (resp) {
                    if (resp && resp.assignment) data.assignment = resp.assignment;
                    if (window.toastr) toastr.success((resp && resp.message) || 'Reparto automático guardado.');
                    render();
                },
                error: function (xhr) {
                    var msg = apiErrorMessage(xhr, 'No se pudo guardar el reparto.');
                    if (window.toastr) toastr.error(msg); else window.alert(msg);
                    $btn.prop('disabled', false).text('Reintentar');
                },
            });
        });

        // Reparto en dos pulsaciones en lugar de un modal de confirmación: abrir
        // otro modal cierra éste (openModal() cierra el anterior) y se perdería la
        // vista con la que el usuario acaba de decidir. La primera pulsación deja
        // el botón diciendo exactamente qué va a pasar.
        $backdrop.on('click', '#tkt-workload-distribute', function () {
            var $btn = $(this);

            if (!confirmandoReparto) {
                confirmandoReparto = true;
                $btn.addClass('tkt-wl-confirm').text('Confirmar: repartir ' + data.unassigned.total + ' ticket(s)');
                confirmTimer = window.setTimeout(function () {
                    confirmandoReparto = false;
                    $btn.removeClass('tkt-wl-confirm').text('Repartir ' + data.unassigned.total + ' sin asignar');
                }, 6000);
                return;
            }

            window.clearTimeout(confirmTimer);
            confirmandoReparto = false;
            $btn.prop('disabled', true).removeClass('tkt-wl-confirm').text('Repartiendo…');

            $.ajax({
                url: TKA.urls.workloadDistribute,
                method: 'POST',
                headers: { Accept: 'application/json' },
                success: function (resp) {
                    if (window.toastr) toastr.success((resp && resp.message) || 'Hecho');
                    load();
                },
                error: function (xhr) {
                    var msg = apiErrorMessage(xhr, 'No se pudo repartir');
                    if (window.toastr) toastr.error(msg); else window.alert(msg);
                    $btn.prop('disabled', false).text('Reintentar');
                },
            });
        });

        load();
    }

    // Exportar respeta el filtro/búsqueda actual (TicketExportController ya
    // reusa TicketFilter) — el modal solo elige el formato, ambos ya
    // soportados; no hay selección de columnas en el backend, no se inventa.
    function openExportModal() {
        var seleccionados = Object.keys(TKA.state.bulk || {}).map(Number);
        // Los filtros de esta pantalla viven en el querystring, así que
        // reenviarlo es lo que hace que "exportar el filtro actual" exporte
        // de verdad lo que se está viendo. Antes el modal lo prometía en el
        // texto y llamaba a la URL pelada, sin ningún filtro.
        var filtrosActuales = window.location.search.replace(/^\?/, '');
        var columnas = null;

        function scopeActual() {
            return $('input[name="tkt-export-scope"]:checked').val() || 'filter';
        }

        function paramsDeAlcance() {
            var scope = scopeActual();
            var params = new URLSearchParams(scope === 'filter' ? filtrosActuales : '');
            params.set('scope', scope);
            if (scope === 'selection') params.set('ids', seleccionados.join(','));

            return params;
        }

        var $modal = openModal(modalShell({
            icon: 'fa-solid fa-download',
            kicker: 'Tickets · exportar',
            title: 'Exportar tickets',
            width: 'lg',
            body: '<div class="tkt-field"><label class="tkt-label">Alcance</label>' +
                    '<label class="tkt-option' + (seleccionados.length ? ' on' : '') + '" data-scope-option>' +
                        '<input type="radio" name="tkt-export-scope" value="selection"' +
                        (seleccionados.length ? ' checked' : ' disabled') + ' class="tkt-m0">' +
                        '<span><span class="tkt-option-title">Selección (' + seleccionados.length + ')</span>' +
                        '<br><span class="tkt-option-sub">' + (seleccionados.length
                            ? 'solo los tickets marcados en el listado'
                            : 'no hay ninguno marcado') + '</span></span></label>' +
                    '<label class="tkt-option' + (seleccionados.length ? '' : ' on') + '" data-scope-option>' +
                        '<input type="radio" name="tkt-export-scope" value="filter"' +
                        (seleccionados.length ? '' : ' checked') + ' class="tkt-m0">' +
                        '<span><span class="tkt-option-title">Filtro actual</span>' +
                        '<br><span class="tkt-option-sub">lo que se está viendo ahora en el listado</span></span></label>' +
                    '<label class="tkt-option" data-scope-option>' +
                        '<input type="radio" name="tkt-export-scope" value="all" class="tkt-m0">' +
                        '<span><span class="tkt-option-title">Todo el histórico</span>' +
                        '<br><span class="tkt-option-sub">ignora los filtros de pantalla</span></span></label>' +
                '</div>' +
                '<div class="tkt-field"><label class="tkt-label">Formato</label>' +
                    '<label class="tkt-option on" data-export-option="csv"><input type="radio" name="tkt-export-format" value="csv" checked class="tkt-m0"><span class="tkt-option-title">CSV</span></label>' +
                    '<label class="tkt-option" data-export-option="pdf"><input type="radio" name="tkt-export-format" value="pdf" class="tkt-m0"><span class="tkt-option-title">PDF</span></label>' +
                '</div>' +
                '<div class="tkt-field" id="tkt-export-cols-wrap">' +
                    '<label class="tkt-label">Columnas<span class="hint">solo CSV</span></label>' +
                    '<div class="tkt-colgrid" id="tkt-export-cols"><span class="tkt-note">Cargando…</span></div>' +
                '</div>' +
                '<div class="tkt-note" id="tkt-export-count">Calculando cuántos tickets se exportarán…</div>',
            foot: '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-export-confirm">Exportar</button>' +
                  '<button type="button" class="tkt-btn" data-modal-close>Cancelar</button>',
        }));

        // Cuántas filas van a salir, ANTES de descargarlas: una exportación
        // de 20 000 y otra de 12 no se piden igual.
        function estimar() {
            if (!TKA.urls.exportEstimate) { $modal.find('#tkt-export-count').remove(); return; }

            var params = paramsDeAlcance();
            $modal.find('#tkt-export-count').text('Calculando…');

            $.getJSON(TKA.urls.exportEstimate + '?' + params.toString()).done(function (res) {
                var n = (res && res.total) || 0;
                $modal.find('#tkt-export-count').html('<i class="fa-solid fa-circle-info"></i> Se exportarán <strong>' +
                    n + '</strong> ' + (n === 1 ? 'ticket' : 'tickets') + '.');

                if (columnas || !res || !res.columns) return;
                columnas = res.columns;
                $modal.find('#tkt-export-cols').html(columnas.map(function (c) {
                    return '<label class="tkt-check sm"><input type="checkbox" value="' + c.key + '"' +
                        (c.default ? ' checked' : '') + '> ' + escapeHtml(c.label) + '</label>';
                }).join(''));
            }).fail(function () {
                $modal.find('#tkt-export-count').text('No se pudo calcular el total.');
            });
        }

        estimar();

        $modal.on('change', '[name="tkt-export-scope"]', function () {
            $modal.find('[data-scope-option]').removeClass('on');
            $(this).closest('[data-scope-option]').addClass('on');
            estimar();
        });

        $modal.on('click', '[data-export-option]', function () {
            $modal.find('[data-export-option]').removeClass('on');
            $(this).addClass('on').find('input').prop('checked', true);
            // Las columnas solo las respeta el CSV; en PDF la plantilla es fija.
            $modal.find('#tkt-export-cols-wrap').toggle($(this).data('export-option') === 'csv');
        });

        $modal.on('click', '#tkt-export-confirm', function () {
            var format = $modal.find('input[name="tkt-export-format"]:checked').val();
            var params = paramsDeAlcance();

            if (format === 'csv') {
                var elegidas = $modal.find('#tkt-export-cols input:checked').map(function () { return this.value; }).get();
                if (!elegidas.length) { if (window.toastr) toastr.error('Elige al menos una columna'); return; }
                params.set('columns', elegidas.join(','));
            }

            window.location = TKA.urls.exportTemplate.replace('__FORMAT__', format) + '?' + params.toString();
            closeModal();
        });
    }


    function openFollowupModal(t) {
        var d = TKA.state.currentDetail;
        var yaProgramados = ((d && d.followups) || []).filter(function (f) { return f.state === 'pending'; });

        // Plantillas de secuencia del mockup: en vez de teclear tres fechas,
        // se elige el ritmo y se calculan los pasos. Los días son relativos a
        // ahora, que es como se piensa un seguimiento ("recuérdamelo en dos
        // días y otra vez a la semana").
        var RITMOS = [
            { key: 'single', label: 'Un solo recordatorio', sub: 'en 2 días', dias: [2] },
            { key: 'short', label: 'Seguimiento corto', sub: 'a los 2 y 5 días', dias: [2, 5] },
            { key: 'long', label: 'Seguimiento largo', sub: 'a los 3, 7 y 14 días', dias: [3, 7, 14] },
        ];

        var pad = function (n) { return String(n).padStart(2, '0'); };
        var enDias = function (dias) {
            var f = new Date();
            f.setDate(f.getDate() + dias);
            f.setHours(9, 0, 0, 0);
            return f;
        };
        var fmtLocal = function (f) {
            return f.getFullYear() + '-' + pad(f.getMonth() + 1) + '-' + pad(f.getDate()) +
                'T' + pad(f.getHours()) + ':' + pad(f.getMinutes());
        };
        var fmtHumano = function (f) {
            return f.toLocaleString('es-ES', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' });
        };

        function pasosHtml(ritmo) {
            return ritmo.dias.map(function (dias, i) {
                var fecha = enDias(dias);
                return '<div class="tkt-step-row">' +
                    '<span class="tkt-step-num">' + (i + 1) + '</span>' +
                    '<span class="tkt-step-when">' + escapeHtml(fmtHumano(fecha)) + '</span>' +
                    '<span class="tkt-step-rel">' + (dias === 1 ? 'mañana' : 'en ' + dias + ' días') + '</span>' +
                    '<input type="hidden" data-step-at="' + fmtLocal(fecha) + '">' +
                '</div>';
            }).join('');
        }

        var $backdrop = openModal(modalShell({
            icon: 'fa-regular fa-bell',
            kicker: 'Automatización · seguimientos',
            titleChip: t.ticket_number,
            title: 'Secuencia de seguimiento',
            width: 'lg',
            body: '' +
                (yaProgramados.length
                    ? '<div class="tkt-note"><i class="fa-solid fa-circle-info"></i> Este ticket ya tiene ' +
                        yaProgramados.length + (yaProgramados.length === 1 ? ' paso pendiente' : ' pasos pendientes') +
                        '. Los nuevos se añaden a los que hay.</div>'
                    : '') +
                '<div class="tkt-field"><label class="tkt-label">Ritmo</label>' +
                    RITMOS.map(function (r, i) {
                        return '<label class="tkt-option' + (i === 0 ? ' on' : '') + '" data-ritmo="' + r.key + '">' +
                            '<input type="radio" name="tkt-followup-ritmo" value="' + r.key + '"' +
                            (i === 0 ? ' checked' : '') + ' class="tkt-m0">' +
                            '<span><span class="tkt-option-title">' + r.label + '</span>' +
                            '<br><span class="tkt-option-sub">' + r.sub + '</span></span></label>';
                    }).join('') +
                '</div>' +
                '<div class="tkt-field"><label class="tkt-label">Pasos</label>' +
                    '<div id="tkt-followup-steps">' + pasosHtml(RITMOS[0]) + '</div></div>' +
                '<div class="tkt-field"><label class="tkt-label">Nota <span class="hint">opcional, se repite en cada paso</span></label>' +
                    '<input type="text" class="tkt-input" id="tkt-followup-note" maxlength="1000" placeholder="Motivo del recordatorio…"></div>' +
                // Plantilla del mockup: si se elige una, cada paso manda ese
                // correo de verdad al cliente (además del recordatorio interno
                // de siempre) al vencer -- ver SendDueTicketFollowupsCommand.
                // optionsHtml() no sirve aquí: da por hecho item.name, y
                // TicketCannedReply usa item.title.
                '<div class="tkt-field"><label class="tkt-label">Plantilla <span class="hint">opcional — si se elige, se envía al cliente</span></label>' +
                    '<select class="tkt-select" id="tkt-followup-template" data-no-select2><option value="">Sin plantilla (solo recordatorio interno)</option>' +
                        (TKA.state.cannedReplies || []).map(function (r) {
                            return '<option value="' + r.id + '">' + escapeHtml(r.title) + '</option>';
                        }).join('') +
                    '</select></div>' +
                '<label class="tkt-check"><input type="checkbox" id="tkt-followup-stop" checked> ' +
                    'Detener la secuencia si el cliente responde</label>',
            foot: '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-followup-confirm">Programar</button>' +
                  '<button type="button" class="tkt-btn" data-modal-close>Cancelar</button>',
            footTwoCol: false,
        }));

        $backdrop.on('click', '[data-ritmo]', function () {
            $backdrop.find('[data-ritmo]').removeClass('on');
            $(this).addClass('on').find('input').prop('checked', true);
            var ritmo = RITMOS.find(function (r) { return r.key === $(this).data('ritmo'); }.bind(this));
            $backdrop.find('#tkt-followup-steps').html(pasosHtml(ritmo));
        });

        $backdrop.on('click', '#tkt-followup-confirm', function () {
            var plantilla = $('#tkt-followup-template').val() || null;
            var pasos = $backdrop.find('[data-step-at]').map(function () {
                return { scheduled_at: $(this).data('step-at'), note: $('#tkt-followup-note').val() || null, canned_reply_id: plantilla };
            }).get();

            if (!pasos.length) { if (window.toastr) toastr.error('Elige un ritmo'); return; }

            $.ajax({
                url: t.url_followups_store,
                method: 'POST',
                data: {
                    steps: pasos,
                    cancel_if_customer_replies: $('#tkt-followup-stop').is(':checked') ? 1 : 0,
                },
                headers: { Accept: 'application/json' },
                success: function (resp) {
                    if (window.toastr) toastr.success((resp && resp.message) || 'Secuencia programada');
                    closeModal();
                    fetchDetailData(t);
                },
                error: function (xhr) {
                    var msg = apiErrorMessage(xhr, 'No se pudo programar el seguimiento');
                    if (window.toastr) toastr.error(msg); else window.alert(msg);
                },
            });
        });
    }

    function cancelFollowup(t, followupId) {
        openConfirmModal({
            icon: 'fa-regular fa-clock',
            title: 'Cancelar seguimiento',
            message: '¿Cancelar este seguimiento programado?',
            confirmLabel: 'Cancelar seguimiento',
            danger: true,
            onConfirm: function () {
                $.ajax({
                    url: t.url_followup_destroy_template.replace('__FOLLOWUP__', followupId),
                    method: 'DELETE',
                    headers: { Accept: 'application/json' },
                    success: function () {
                        if (window.toastr) toastr.success('Seguimiento cancelado');
                        fetchDetailData(t);
                    },
                    error: function (xhr) {
                        var msg = apiErrorMessage(xhr, 'No se pudo cancelar el seguimiento');
                        if (window.toastr) toastr.error(msg); else window.alert(msg);
                    },
                });
            },
        });
    }

    // Modal "Nueva conversación paralela" — campos y nombres exactos de
    // StoreSideConversationRequest (subject, participant_type: team|
    // external_email, participant_user_id, participant_email, body).
    function newSideConversation(t) {
        var $backdrop = openModal(modalShell({
            icon: 'fa-solid fa-people-arrows',
            kicker: t.ticket_number,
            title: 'Conversación paralela',
            width: 'md',
            body: '' +
                '<div class="tkt-field"><label class="tkt-label">Asunto<span class="req">*</span></label>' +
                    '<input type="text" class="tkt-input" id="tkt-side-subject" maxlength="255"></div>' +
                '<div class="tkt-field"><label class="tkt-label">Participante<span class="req">*</span></label>' +
                    '<select class="tkt-select" id="tkt-side-type"><option value="team">Compañero de equipo</option><option value="external_email">Email externo</option></select></div>' +
                '<div class="tkt-field" id="tkt-side-team-field"><label class="tkt-label">Compañero<span class="req">*</span></label>' +
                    '<select class="tkt-select" id="tkt-side-user"><option value="">Selecciona un compañero…</option>' + optionsHtml(TKA.state.agentsFull, 'id', '') + '</select></div>' +
                '<div class="tkt-field" id="tkt-side-email-field" hidden><label class="tkt-label">Email externo<span class="req">*</span></label>' +
                    '<input type="email" class="tkt-input" id="tkt-side-email"></div>' +
                '<div class="tkt-field"><label class="tkt-label">Mensaje inicial<span class="req">*</span></label>' +
                    '<textarea class="tkt-input" id="tkt-side-body" maxlength="20000" style="min-height:90px"></textarea></div>',
            foot: '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-side-confirm">Crear</button>' +
                  '<button type="button" class="tkt-btn" data-modal-close>Cancelar</button>',
        }));

        $backdrop.on('change', '#tkt-side-type', function () {
            var isTeam = $(this).val() === 'team';
            $('#tkt-side-team-field').prop('hidden', !isTeam);
            $('#tkt-side-email-field').prop('hidden', isTeam);
        });

        $backdrop.on('click', '#tkt-side-confirm', function () {
            var subject = $('#tkt-side-subject').val().trim();
            var type = $('#tkt-side-type').val();
            var userId = $('#tkt-side-user').val();
            var email = $('#tkt-side-email').val().trim();
            var body = $('#tkt-side-body').val().trim();

            if (!subject || !body || (type === 'team' && !userId) || (type === 'external_email' && !email)) {
                if (window.toastr) toastr.error('Rellena todos los campos obligatorios'); else window.alert('Rellena todos los campos obligatorios');
                return;
            }

            $.ajax({
                url: t.url_side_conversations_store,
                method: 'POST',
                data: {
                    subject: subject,
                    participant_type: type,
                    participant_user_id: type === 'team' ? userId : undefined,
                    participant_email: type === 'external_email' ? email : undefined,
                    body: body,
                },
                headers: { Accept: 'application/json' },
                success: function () {
                    if (window.toastr) toastr.success('Conversación paralela creada');
                    closeModal();
                    fetchDetailData(t);
                },
                error: function (xhr) {
                    var msg = (xhr.responseJSON && (xhr.responseJSON.message || (xhr.responseJSON.errors && Object.values(xhr.responseJSON.errors)[0][0]))) || 'No se pudo crear la conversación paralela';
                    if (window.toastr) toastr.error(msg); else window.alert(msg);
                },
            });
        });
    }

    function addSideConversationMessage(t, sideId) {
        var $backdrop = openModal(modalShell({
            icon: 'fa-regular fa-comment-dots',
            title: 'Responder en la conversación paralela',
            width: 'sm',
            body: '<div class="tkt-field"><label class="tkt-label">Mensaje<span class="req">*</span></label><textarea class="tkt-input" id="tkt-side-msg-body" rows="4" placeholder="Escribe el mensaje…"></textarea></div>',
            foot: '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-side-msg-confirm">Enviar</button>' +
                  '<button type="button" class="tkt-btn" data-modal-close>Cancelar</button>',
        }));
        $backdrop.find('#tkt-side-msg-body').trigger('focus');

        $backdrop.on('click', '#tkt-side-msg-confirm', function () {
            var body = ($backdrop.find('#tkt-side-msg-body').val() || '').trim();
            if (!body) { if (window.toastr) toastr.error('Escribe un mensaje'); return; }
            closeModal();
            $.ajax({
                url: t.url_side_conversations_message_template.replace('__SIDE__', sideId),
                method: 'POST',
                data: { body: body },
                headers: { Accept: 'application/json' },
                success: function () {
                    if (window.toastr) toastr.success('Mensaje enviado');
                    fetchDetailData(t);
                },
                error: function (xhr) {
                    var msg = (xhr.responseJSON && (xhr.responseJSON.message || (xhr.responseJSON.errors && Object.values(xhr.responseJSON.errors)[0][0]))) || 'No se pudo enviar el mensaje';
                    if (window.toastr) toastr.error(msg); else window.alert(msg);
                },
            });
        });
    }

    var NOTE_COLORS = ['yellow', 'blue', 'green', 'red', 'purple', 'orange'];
    var NOTE_COLOR_LABELS = { yellow: 'Amarillo', blue: 'Azul', green: 'Verde', red: 'Rojo', purple: 'Morado', orange: 'Naranja' };

    // Autocompletado @mención genérico sobre un <textarea> — el backend
    // (MentionService::notifyMentions) hace matching por texto sobre
    // "TRIM(firstname+' '+lastname) LIKE 'nombre%'", así que insertar el
    // nombre completo real (no un @handle inventado) es lo que garantiza
    // que la notificación real se dispare al guardar.
    function bindMentionAutocomplete($textarea, $drop) {
        $textarea.on('input', function () {
            var val = this.value;
            var pos = this.selectionStart;
            var atIndex = val.lastIndexOf('@', pos - 1);
            var textSinceAt = atIndex > -1 ? val.slice(atIndex + 1, pos) : null;
            if (atIndex === -1 || textSinceAt === null || /[\n@]/.test(textSinceAt)) {
                $drop.prop('hidden', true).empty();
                return;
            }

            var matches = (TKA.state.agentsFull || []).filter(function (a) {
                return a.name.toLowerCase().indexOf(textSinceAt.toLowerCase()) === 0;
            }).slice(0, 6);

            if (!matches.length) { $drop.prop('hidden', true).empty(); return; }

            // .prop('hidden', ...) en vez de .show()/.hide(): el atributo
            // HTML hidden lleva !important en el reset de Bootstrap — un
            // simple display:block inline (lo que hace .show()) no lo gana.
            $drop.html(matches.map(function (a) {
                return '<button type="button" class="tkt-drop-item" data-mention-name="' + escapeHtml(a.name) + '">' + escapeHtml(a.name) + '</button>';
            }).join('')).prop('hidden', false);

            $drop.off('click').on('click', '[data-mention-name]', function () {
                var name = $(this).data('mention-name');
                var before = val.slice(0, atIndex);
                var after = val.slice(pos);
                var newVal = before + '@' + name + ' ' + after;
                $textarea.val(newVal);
                $drop.prop('hidden', true).empty();
                $textarea.trigger('focus');
            });
        });

        $textarea.on('blur', function () {
            // pequeño delay para que el click en el dropdown se registre antes de ocultarlo
            setTimeout(function () { $drop.prop('hidden', true).empty(); }, 150);
        });
    }

    // Autocompletado "/" de plantillas sobre el composer del hilo — mismo
    // patrón que bindMentionAutocomplete() de arriba, pero:
    //   - filtra por título O short_code (no solo por prefijo del nombre),
    //   - un espacio corta la búsqueda (el código no lleva espacios, y así
    //     una barra suelta en medio de una frase normal no dispara nada),
    //   - inserta el CONTENIDO de la plantilla, no su nombre,
    //   - se navega con el teclado (↑/↓ + Enter), no solo con el ratón —
    //     escribir "/algo" y quedarse sin soltar el teclado para elegir era
    //     justo lo que "/ para respuestas rápidas" prometía.
    // getTemplates() es una función (no un array) porque la lista arranca en
    // bruto y se sustituye por la interpolada contra el ticket en cuanto
    // responde el fetch — leerla en cada tecleo evita quedarse con la
    // primera versión cacheada.
    function bindTemplateAutocomplete($textarea, $drop, getTemplates) {
        // Estado del desplegable abierto ahora mismo (null si está cerrado):
        // qué coincidencias muestra, en qué posición del texto se insertará
        // el resultado y cuál está resaltada para Enter/clic.
        var state = null;

        function render() {
            $drop.html(state.matches.map(function (r, i) {
                return '<button type="button" class="tkt-drop-item' + (i === state.index ? ' on' : '') + '" data-tpl-index="' + i + '">' +
                    '<span class="tkt-shortcode mono">' + (r.short_code ? escapeHtml('/' + String(r.short_code).replace(/^\/+/, '')) : '—') + '</span>' +
                    '<span class="tkt-trunc">' + escapeHtml(r.title) + '</span>' +
                '</button>';
            }).join('')).prop('hidden', false);
        }

        function close() {
            state = null;
            $drop.prop('hidden', true).empty();
        }

        function pick(index) {
            var reply = state && state.matches[index];
            if (!reply) return;
            var val = $textarea.val();
            var before = val.slice(0, state.slashIndex);
            var after = val.slice(state.pos);
            $textarea.val(before + reply.content + after);
            close();
            $textarea.trigger('focus').trigger('input');
            if (typeof autoResizeTextarea === 'function') autoResizeTextarea($textarea[0]);
        }

        $textarea.on('input', function () {
            var val = this.value;
            var pos = this.selectionStart;
            var slashIndex = val.lastIndexOf('/', pos - 1);
            var textSinceSlash = slashIndex > -1 ? val.slice(slashIndex + 1, pos) : null;
            if (slashIndex === -1 || textSinceSlash === null || /[\n/\s]/.test(textSinceSlash)) {
                close();
                return;
            }

            var q = textSinceSlash.toLowerCase();
            var matches = (getTemplates() || []).filter(function (r) {
                return !q || String(r.title).toLowerCase().indexOf(q) !== -1 ||
                    String(r.short_code || '').replace(/^\/+/, '').toLowerCase().indexOf(q) !== -1;
            }).slice(0, 8);

            if (!matches.length) { close(); return; }

            state = { matches: matches, slashIndex: slashIndex, pos: pos, index: 0 };
            render();
        });

        // Delegado en $drop (no en cada item): render() reemplaza el HTML en
        // cada tecleo, un .on() por item se perdería con el elemento viejo.
        $drop.on('click', '[data-tpl-index]', function () {
            pick(parseInt($(this).data('tpl-index'), 10));
        });
        $drop.on('mouseenter', '[data-tpl-index]', function () {
            if (!state) return;
            state.index = parseInt($(this).data('tpl-index'), 10);
            render();
        });

        $textarea.on('keydown', function (ev) {
            if (!state) return;
            if (ev.key === 'ArrowDown') {
                ev.preventDefault();
                state.index = (state.index + 1) % state.matches.length;
                render();
            } else if (ev.key === 'ArrowUp') {
                ev.preventDefault();
                state.index = (state.index - 1 + state.matches.length) % state.matches.length;
                render();
            } else if (ev.key === 'Enter') {
                // Sin Mayús/Ctrl/Cmd: esas combinaciones siguen su curso
                // normal (salto de línea, enviar) en vez de elegir a ciegas.
                if (ev.shiftKey || ev.metaKey || ev.ctrlKey) return;
                ev.preventDefault();
                pick(state.index);
            } else if (ev.key === 'Escape') {
                ev.preventDefault();
                close();
            }
        });

        $textarea.on('blur', function () {
            // pequeño delay para que el click en el dropdown se registre antes de ocultarlo
            setTimeout(close, 150);
        });
    }

    // Agentes mencionados en las notas del ticket (@nombre). El backend no
    // guarda las menciones en una tabla aparte, así que se extraen del propio
    // texto — que es donde el autocompletado del editor las escribe.
    // El mockup rotula "0 / 2000", pero StoreTicketNoteRequest valida
    // max:5000: se usa el límite real para que el contador no prometa un
    // tope distinto del que aplica el servidor.
    var NOTE_MAX_LENGTH = 5000;

    function mentionsFrom(notes) {
        var found = {};
        (notes || []).forEach(function (n) {
            // El backend resuelve las menciones con el MISMO MentionService
            // que decide a quién notificar, así que estos son los agentes que
            // de verdad recibieron el aviso. El parseo en crudo queda como
            // respaldo: enseñaba el "@loquesea" literal aunque no existiera
            // ningún agente con ese nombre.
            if (n.mentions) {
                // Lista presente aunque venga vacía: el backend YA analizó
                // esta nota y no encontró a nadie. Volver al parseo en crudo
                // aquí convertía cualquier correo del texto
                // ("compras@construcinsa.mx") en una mención inventada.
                n.mentions.forEach(function (m) {
                    var prev = found[m.name];
                    // "mencionado hace 22 min": la mención no tiene fecha
                    // propia, la hereda de la nota donde se escribió; con
                    // varias, la más reciente.
                    found[m.name] = {
                        count: (prev ? prev.count : 0) + 1,
                        when: prev && prev.when ? prev.when : n.created_at_human,
                    };
                });
                return;
            }

            var m = String(n.body || '').match(/@[\wÁÉÍÓÚáéíóúÑñ.\-]+/g);
            if (m) m.forEach(function (x) {
                var prev = found[x];
                found[x] = { count: (prev ? prev.count : 0) + 1, when: prev && prev.when ? prev.when : n.created_at_human };
            });
        });
        return Object.keys(found).map(function (k) {
            return { name: k, count: found[k].count, when: found[k].when };
        });
    }

    function renderNotasPane($c, notes, t) {
        var menciones = mentionsFrom(notes);
        var list = (notes || []).map(function (n) {
            var colorDots = NOTE_COLORS.map(function (c) {
                // role/tabindex/aria-label + manejador de teclado propio —
                // antes era un <span> plano sin ninguno de los tres,
                // inalcanzable por teclado y sin nombre accesible (solo
                // color de fondo, indistinguible para un lector de
                // pantalla).
                return '<span class="tkt-note-color-dot' + (n.color === c ? ' on' : '') + '" data-note-color="' + n.id + '" data-color="' + c + '" role="button" tabindex="0" aria-label="' + NOTE_COLOR_LABELS[c] + '" style="background:var(--tkt-note-' + c + ',' + c + ')"></span>';
            }).join('');
            return '<div class="tkt-note-card' + (n.is_pinned ? ' pinned' : '') + '"><span class="tkt-person-avatar" style="width:22px;height:22px;font-size:9px">' + initials(n.author_name) + '</span>' +
                '<div class="tkt-fill">' +
                    '<div style="display:flex;align-items:center;gap:6px">' +
                        '<span style="font-size:10.5px;color:var(--tkt-text-faint);flex:1;min-width:0">' + escapeHtml(n.author_name) + ' · ' + escapeHtml(n.created_at_human) + (n.is_pinned ? ' · <strong>fijada</strong>' : '') + '</span>' +
                        '<button type="button" class="tkt-btn-icon" data-note-pin="' + n.id + '" title="' + (n.is_pinned ? 'Desfijar' : 'Fijar') + '" style="width:20px;height:20px"><i class="fa-solid fa-thumbtack" style="font-size:9px"></i></button>' +
                        '<button type="button" class="tkt-btn-icon" data-note-delete="' + n.id + '" title="Eliminar" style="width:20px;height:20px"><i class="fa-solid fa-trash" style="font-size:9px"></i></button>' +
                    '</div>' +
                    (n.title ? '<div style="font-size:11.5px;font-weight:700">' + escapeHtml(n.title) + '</div>' : '') +
                    '<div style="font-size:11.5px;line-height:1.5">' + escapeHtml(n.body) + '</div>' +
                    '<div style="display:flex;gap:4px;margin-top:4px">' + colorDots + '</div>' +
                '</div></div>';
        }).join('');

        $c.html(
            '<div class="tkt-side-card"><div class="tkt-side-card-head">Nota interna</div><div class="tkt-side-card-body">' +
                '<div class="tkt-relative">' +
                    '<textarea id="tkt-new-note" class="tkt-w-100" style="padding:9px 10px;border:1px solid var(--tkt-border);border-radius:7px;font-size:11.5px;min-height:66px" placeholder="Escribe una nota que solo verá el equipo… (usa @ para mencionar)"></textarea>' +
                    '<div class="tkt-drop" id="tkt-note-mention-drop" style="top:100%;left:0;right:0;max-height:160px;overflow:auto" hidden></div>' +
                '</div>' +
                // Barra del mockup: las tres acciones sobre el texto a la
                // izquierda y el contador a la derecha. "Mencionar" inserta
                // la @ y dispara el mismo autocompletado que ya escuchaba
                // por teclado, que hasta ahora no se anunciaba en ninguna
                // parte de la interfaz.
                '<div class="tkt-note-tools">' +
                    '<button type="button" class="tkt-comp-tool" id="tkt-note-macro" title="Insertar una macro">Macro</button>' +
                    '<label class="tkt-comp-tool" title="Adjuntar un archivo al ticket">Adjuntar<input type="file" id="tkt-note-attach" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif,.zip,.rar,.txt" hidden></label>' +
                    '<button type="button" class="tkt-comp-tool" id="tkt-note-mention" title="Mencionar a un compañero">Mencionar</button>' +
                    '<label class="tkt-note-pin-toggle"><input type="checkbox" id="tkt-new-note-pin"> Fijar</label>' +
                    '<span class="tkt-note-count mono" id="tkt-note-counter">0 / ' + NOTE_MAX_LENGTH + '</span>' +
                '</div>' +
                '<div class="tkt-note-actions">' +
                    '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-note-save">Guardar nota</button>' +
                    '<button type="button" class="tkt-btn" id="tkt-note-discard">Descartar</button>' +
                '</div>' +
            '</div></div>' +
            '<div class="tkt-side-card"><div class="tkt-side-card-head">Notas del ticket<span class="tkt-spacer mono tkt-faint">' + (notes ? notes.length : 0) + '</span></div><div class="tkt-side-card-body">' +
                (list || '<div class="tkt-empty-box">Sin notas todavía.</div>') +
            '</div></div>' +
            // Card "Menciones" del mockup: a quién se ha llamado en las notas.
            // Solo aparece si hay alguna: una card vacía permanente sería ruido.
            (menciones.length
                ? '<div class="tkt-side-card"><div class="tkt-side-card-head">Menciones' +
                    '<span class="tkt-spacer mono tkt-faint">' + menciones.length + '</span></div>' +
                  '<div class="tkt-side-card-body"><div class="tkt-chiprow">' +
                    menciones.map(function (m) {
                        return '<span class="tkt-rchip">' + escapeHtml(m.name) +
                            (m.count > 1 ? ' ·' + m.count : '') +
                            (m.when ? '<span class="tkt-rchip-when">' + escapeHtml(m.when) + '</span>' : '') +
                        '</span>';
                    }).join('') +
                  '</div></div></div>'
                : '')
        );

        var $noteInput = $c.find('#tkt-new-note');
        var $counter = $c.find('#tkt-note-counter');

        $noteInput.attr('maxlength', NOTE_MAX_LENGTH).on('input', function () {
            var used = this.value.length;
            $counter.text(used + ' / ' + NOTE_MAX_LENGTH).toggleClass('near', used > NOTE_MAX_LENGTH * 0.9);
        });

        $c.find('#tkt-note-mention').on('click', function () {
            // Inserta la @ en la posición del cursor y deja el foco dentro,
            // para que el autocompletado ya enganchado reaccione igual que
            // si se hubiera tecleado.
            var el = $noteInput.get(0);
            if (!el) return;
            var pos = el.selectionStart != null ? el.selectionStart : el.value.length;
            el.value = el.value.slice(0, pos) + '@' + el.value.slice(pos);
            el.focus();
            el.setSelectionRange(pos + 1, pos + 1);
            $noteInput.trigger('input').trigger('keyup');
        });

        $c.find('#tkt-note-macro').on('click', function () { openComposerMacros($(this)); });

        $c.find('#tkt-note-attach').on('change', function () {
            uploadTicketAttachments(t, this.files);
            this.value = '';
        });

        $c.find('#tkt-note-discard').on('click', function () {
            if (!$noteInput.val()) return;
            openConfirmModal({
                icon: 'fa-regular fa-note-sticky',
                title: 'Descartar la nota',
                message: 'Se perderá lo que has escrito. No se puede deshacer.',
                confirmLabel: 'Descartar',
                danger: true,
                onConfirm: function () {
                    $noteInput.val('').trigger('input');
                    $c.find('#tkt-new-note-pin').prop('checked', false);
                },
            });
        });

        $c.find('#tkt-note-save').on('click', function () { createNote(t); });
        $c.find('[data-note-pin]').on('click', function () { toggleNotePin(t, $(this).data('note-pin')); });
        $c.find('[data-note-delete]').on('click', function () { deleteNote(t, $(this).data('note-delete')); });
        bindMentionAutocomplete($c.find('#tkt-new-note'), $c.find('#tkt-note-mention-drop'));
        $c.find('[data-note-color]').on('click', function () { setNoteColor(t, $(this).data('note-color'), $(this).data('color')); });
        $c.find('[data-note-color]').on('keydown', function (ev) {
            if (ev.key !== 'Enter' && ev.key !== ' ' && ev.key !== 'Spacebar') return;
            ev.preventDefault();
            setNoteColor(t, $(this).data('note-color'), $(this).data('color'));
        });
    }

    function toggleNotePin(t, noteId) {
        $.ajax({
            url: t.url_note_pin_template.replace('__NOTE__', noteId),
            method: 'POST',
            headers: { Accept: 'application/json' },
            success: function (resp) {
                if (window.toastr) toastr.success((resp && resp.message) || 'Nota actualizada');
                fetchDetailData(t);
            },
            error: function () { if (window.toastr) toastr.error('No se pudo fijar la nota'); },
        });
    }

    function setNoteColor(t, noteId, color) {
        $.ajax({
            url: t.url_note_color_template.replace('__NOTE__', noteId),
            method: 'POST',
            data: { color: color },
            headers: { Accept: 'application/json' },
            success: function () { fetchDetailData(t); },
            error: function () { if (window.toastr) toastr.error('No se pudo cambiar el color'); },
        });
    }

    function deleteNote(t, noteId) {
        openConfirmModal({
            icon: 'fa-solid fa-trash',
            title: 'Eliminar nota interna',
            message: 'No se puede deshacer.',
            confirmLabel: 'Eliminar',
            danger: true,
            onConfirm: function () {
                $.ajax({
                    url: t.url_note_destroy_template.replace('__NOTE__', noteId),
                    method: 'DELETE',
                    headers: { Accept: 'application/json' },
                    success: function () {
                        if (window.toastr) toastr.success('Nota eliminada');
                        fetchDetailData(t);
                    },
                    error: function () { if (window.toastr) toastr.error('No se pudo eliminar la nota'); },
                });
            },
        });
    }

    function renderTagsPane($c, t, aiSuggestion) {
        // Modal 27: la sugerencia de clasificación se revisa en su propio
        // modal antes de aplicarse, no se aplica a ciegas desde el panel.
        $c.off('click.tagging').on('click.tagging', '#tkt-open-tagging', function () { openAiTaggingModal(t, aiSuggestion); });
        var chips = (t.tags || []).map(function (tag) {
            // data-tag-remove vive SOLO en el icono fa-xmark, no en el chip
            // completo — antes un click accidental en cualquier parte del
            // texto de la etiqueta la eliminaba al instante.
            return '<span class="tkt-tag">' + escapeHtml(tag) + ' <i class="fa-solid fa-xmark" data-tag-remove="' + escapeHtml(tag) + '" role="button" tabindex="0" aria-label="Eliminar etiqueta ' + escapeHtml(tag) + '"></i></span>';
        }).join('');

        // "+ añadir" del mockup: el campo sale al pulsarlo, en vez de tener
        // un input permanente ocupando sitio bajo unas etiquetas que la
        // mayoría del tiempo solo se leen.
        var html = '<div class="tkt-side-card"><div class="tkt-side-card-head">Etiquetas' +
                (t.tags && t.tags.length ? '<span class="tkt-spacer mono tkt-faint">' + t.tags.length + '</span>' : '') +
            '</div><div class="tkt-side-card-body">' +
                '<div class="tkt-chiprow">' + chips +
                    '<button type="button" class="tkt-tag-add-btn" id="tkt-tag-add-btn"><i class="fa-solid fa-plus"></i> añadir</button>' +
                '</div>' +
                '<input type="text" id="tkt-tag-add" class="tkt-w-100 tkt-tag-input" placeholder="Nueva etiqueta y Enter…" hidden>' +
            '</div></div>';

        // Sugerencias ya calculadas por TicketAiService (ai_suggested_category_id/
        // ai_suggested_priority) — sin porcentaje de confianza porque el
        // backend no lo guarda; el mockup lo muestra pero sería un dato
        // inventado. Solo aparece si hay una sugerencia real pendiente.
        if (aiSuggestion && (aiSuggestion.category || aiSuggestion.priority)) {
            html += '<div class="tkt-side-card"><div class="tkt-side-card-head"><i class="fa-solid fa-wand-magic-sparkles"></i> Sugerido por IA</div><div class="tkt-side-card-body">';
            if (aiSuggestion.category) {
                html += '<div class="tkt-row"><span style="flex:1;font-size:11.5px">Categoría: <strong>' + escapeHtml(aiSuggestion.category.name) + '</strong></span>' +
                    '<button type="button" class="tkt-btn tkt-btn-sm" data-ai-apply="category">Aplicar</button></div>';
            }
            if (aiSuggestion.priority) {
                html += '<div class="tkt-row"><span style="flex:1;font-size:11.5px">Prioridad: <strong>' + escapeHtml(priorityLabel(aiSuggestion.priority)) + '</strong></span>' +
                    '<button type="button" class="tkt-btn tkt-btn-sm" data-ai-apply="priority">Aplicar</button></div>';
            }
            html += '<button type="button" class="tkt-btn tkt-w-100" id="tkt-open-tagging">Revisar clasificación sugerida</button>';
            html += '</div></div>';
        }

        html += '<a href="' + TKA.urls.automationsIndex + '" class="tkt-btn">Reglas de etiquetado automático</a>';

        $c.html(html);

        // Mismo patrón de confirmación que ya usa el borrado de notas
        // (openConfirmModal, aviso de que no se puede deshacer) — antes un
        // solo click eliminaba la etiqueta al instante, sin fricción.
        $c.find('[data-tag-remove]').on('click', function () {
            var tag = $(this).data('tag-remove');
            openConfirmModal({
                icon: 'fa-solid fa-tag',
                danger: true,
                title: 'Eliminar etiqueta',
                message: 'Se eliminará la etiqueta "' + tag + '" de este ticket. No se puede deshacer.',
                confirmLabel: 'Eliminar',
                onConfirm: function () { patchTags(t, null, tag); },
            });
        });
        $c.find('[data-tag-remove]').on('keydown', function (ev) {
            if (ev.key !== 'Enter' && ev.key !== ' ' && ev.key !== 'Spacebar') return;
            ev.preventDefault();
            $(this).trigger('click');
        });
        $c.find('#tkt-tag-add-btn').on('click', function () {
            $(this).hide();
            $c.find('#tkt-tag-add').prop('hidden', false).trigger('focus');
        });
        $c.find('#tkt-tag-add').on('keydown', function (ev) {
            if (ev.key !== 'Enter' || !this.value.trim()) return;
            patchTags(t, this.value.trim(), null);
        });
        $c.find('[data-ai-apply]').on('click', function () { applyAiSuggestion(t, $(this).data('ai-apply')); });
        $c.find('#tkt-open-tagging').on('click', function () { openAiTaggingModal(t, aiSuggestion); });
    }

    function applyAiSuggestion(t, field) {
        $.ajax({
            url: t.url_ai_apply,
            method: 'POST',
            data: { field: field },
            headers: { Accept: 'application/json' },
            success: function (resp) {
                if (window.toastr) toastr.success((resp && resp.message) || 'Sugerencia aplicada');
                fetchDetailData(t);
            },
            error: function (xhr) {
                var msg = apiErrorMessage(xhr, 'No se pudo aplicar la sugerencia');
                if (window.toastr) toastr.error(msg); else window.alert(msg);
            },
        });
    }

    function renderArchivosPane($c, files) {
        var list = (files || []).map(fileRowHtml).join('');
        // Mismo copy que la pestaña "Archivos" del centro (renderFilesPane)
        // para el mismo caso — antes decían cosas distintas ("Sin archivos
        // adjuntos." aquí vs. "Este ticket no tiene archivos adjuntos." allá).
        $c.html('<div class="tkt-side-card"><div class="tkt-side-card-head">Archivos del ticket<span class="tkt-spacer mono tkt-faint">' + (files ? files.length : 0) + '</span></div><div class="tkt-side-card-body">' + (list || '<div class="tkt-empty-box">Este ticket no tiene archivos adjuntos.</div>') + '</div></div>');
    }

    function renderHistorialPane($c, activity, related) {
        var all = activity || [];
        var total = (TKA.state.currentDetail && TKA.state.currentDetail.activity_total_count) || all.length;
        var histFilter = 'all';

        // Los tres filtros del mockup. "Envíos" son los eventos de correo,
        // "Cambios" el resto (estado, prioridad, asignación…).
        function histRows() {
            var list = all.filter(function (a) {
                if (histFilter === 'all') return true;
                var tipo = activityStepType(a.description);
                return histFilter === 'mail' ? tipo === 'sent' : tipo !== 'sent';
            }).slice(0, 8);

            if (!list.length) return '<div class="tkt-empty-box">Sin eventos en este filtro.</div>';

            return list.map(function (a, i) {
                return stepHtml({
                    type: activityStepType(a.description),
                    title: escapeHtml(a.description),
                    detail: a.causer ? escapeHtml(a.causer) : '',
                    time: a.created_at_human || '',
                    last: i === list.length - 1,
                });
            }).join('');
        }

        var recent = '<div class="tkt-seg-tabs" id="tkt-hist-tabs">' +
                '<button type="button" class="on" data-hfilter="all">Todo</button>' +
                '<button type="button" data-hfilter="mail">Envíos</button>' +
                '<button type="button" data-hfilter="change">Cambios</button>' +
                // "Auditoría" del mockup: sale de la pantalla a la bitácora
                // del módulo Activity ya acotada a ESTE ticket. No es un
                // cuarto filtro de la lista de arriba, por eso va suelto.
                (TKA.urls.activityAudit
                    ? '<a class="tkt-seg-out" href="' + TKA.urls.activityAudit +
                        '?subject_type=' + encodeURIComponent('Modules\\HelpdeskTickets\\Models\\Ticket') +
                        '&subject_id=' + encodeURIComponent(TKA.state.currentTicket ? TKA.state.currentTicket.id : '') +
                        '" target="_blank" rel="noopener">Auditoría</a>'
                    : '') +
            '</div>' +
            '<div id="tkt-hist-rows">' + histRows() + '</div>' +
            // "Ver las 23 acciones del ticket →": lleva a la pestaña
            // Actividad del detalle, que ya lista el historial completo.
            (total > Math.min(all.length, 8)
                ? '<button type="button" class="tkt-link-btn tkt-w-100" data-goto-activity>Ver las ' + total + ' acciones del ticket →</button>'
                : '');
        var relatedHtml = (related || []).map(function (r) {
            return '<div class="tkt-line">' +
                '<a href="' + TKA.urls.index + '?ticket=' + r.id + '" style="text-decoration:none;color:inherit;display:flex;align-items:center;gap:9px;flex:1;min-width:0">' +
                    '<span class="mono tkt-meta-xs">' + escapeHtml(r.ticket_number) + '</span>' +
                    '<span class="tkt-trunc tkt-fill">' + escapeHtml(r.subject) + '</span>' +
                    (r.link_type ? chip(LINK_TYPE_LABELS[r.link_type] || r.link_type, 'tkt-chip-info') : '') +
                    chip(r.status_name || STATUS_LABEL_FALLBACK[r.status_slug] || '—', statusChipClass(r.status_slug)) +
                '</a>' +
                (r.url_unlink ? '<button type="button" class="tkt-btn-icon" data-unlink="' + r.id + '" data-unlink-url="' + r.url_unlink + '" title="Desvincular"><i class="fa-solid fa-link-slash"></i></button>' : '') +
            '</div>';
        }).join('');

        $c.html(
            '<div class="tkt-side-card"><div class="tkt-side-card-head">Historial reciente' +
                '<span class="tkt-spacer mono tkt-faint">' + Math.min(all.length, 8) + ' de ' + total + '</span></div>' +
                '<div class="tkt-side-card-body">' + (all.length ? recent : '<div class="tkt-empty-box">Sin actividad.</div>') + '</div></div>' +
            '<div class="tkt-side-card"><div class="tkt-side-card-head">Tickets relacionados</div><div class="tkt-side-card-body">' +
                (relatedHtml || '<div class="tkt-empty-box">No hay otros tickets de este cliente.</div>') +
                '<button type="button" class="tkt-btn" id="tkt-link-ticket">Vincular ticket</button>' +
            '</div></div>'
        );

        $c.find('#tkt-link-ticket').on('click', function () { linkTicketPrompt(TKA.state.currentTicket); });
        $c.find('[data-goto-activity]').on('click', function () { $('[data-dtab="activity"]').trigger('click'); });
        $c.find('[data-hfilter]').on('click', function () {
            histFilter = $(this).data('hfilter');
            $c.find('[data-hfilter]').removeClass('on');
            $(this).addClass('on');
            $c.find('#tkt-hist-rows').html(histRows());
        });
        $c.find('[data-unlink]').on('click', function () {
            var unlinkUrl = $(this).data('unlink-url');
            openConfirmModal({
                icon: 'fa-solid fa-link-slash',
                title: 'Desvincular ticket',
                message: 'El enlace entre ambos tickets se eliminará.',
                confirmLabel: 'Desvincular',
                danger: true,
                onConfirm: function () {
                    var t = TKA.state.currentTicket;
                    $.ajax({
                        url: unlinkUrl,
                        method: 'DELETE',
                        headers: { Accept: 'application/json' },
                        success: function () {
                            if (window.toastr) toastr.success('Ticket desvinculado');
                            fetchDetailData(t);
                        },
                        error: function () { if (window.toastr) toastr.error('No se pudo desvincular'); },
                    });
                },
            });
        });
    }

    var LINK_TYPE_LABELS = { related: 'Relacionado', duplicate_of: 'Duplicado de', blocks: 'Bloquea a', blocked_by: 'Bloqueado por' };

    // Versión simplificada del modal "Vincular ticket" del mockup (que
    // busca por texto en vivo): pide el número de ticket por prompt. El
    // backend (LinkTicketRequest→TicketLink, con exists+self-link guard) es
    // el real; solo el picker es más simple.
    var LINK_TYPES = [
        { value: 'related', title: 'Relacionado', sub: 'Los dos tickets tratan del mismo asunto' },
        { value: 'duplicate_of', title: 'Es un duplicado de', sub: 'Este ticket repite el otro' },
        { value: 'blocks', title: 'Bloquea a', sub: 'Este ticket debe cerrarse antes que el otro' },
        { value: 'blocked_by', title: 'Bloqueado por', sub: 'No se puede cerrar hasta que el otro se resuelva' },
    ];

    // El picker de participante en vivo del mockup se sustituye por un ID
    // numérico (mismo criterio que Aplazar/Fusionar) — el backend real
    // (LinkTicketRequest→TicketLink) sí valida existencia y evita
    // auto-enlace. `blocks`/`blocked_by` tienen efecto real:
    // Ticket::openBlockers() impide cerrar el ticket bloqueado.
    function linkTicketPrompt(t) {
        var options = LINK_TYPES.map(function (lt, i) {
            return '<label class="tkt-option' + (i === 0 ? ' on' : '') + ' tkt-pointer" data-link-type-option="' + lt.value + '" >' +
                '<input type="radio" name="tkt-link-type" value="' + lt.value + '"' + (i === 0 ? ' checked' : '') + ' class="tkt-m0">' +
                '<span><span class="tkt-option-title">' + escapeHtml(lt.title) + '</span><br><span class="tkt-option-sub">' + escapeHtml(lt.sub) + '</span></span>' +
            '</label>';
        }).join('');

        var $backdrop = openModal(modalShell({
            icon: 'fa-solid fa-link',
            kicker: t.ticket_number,
            title: 'Vincular ticket',
            width: 'md',
            body: '' +
                '<div class="tkt-field"><label class="tkt-label">Ticket a vincular<span class="req">*</span><span class="hint">busca por número o asunto</span></label>' +
                    '<input type="text" class="tkt-input" id="tkt-link-target" placeholder="Nº de ticket, asunto o ID…">' +
                    '<div class="tkt-pick-list sm" id="tkt-link-results"></div></div>' +
                '<div style="display:flex;flex-direction:column;gap:6px">' + options + '</div>',
            foot: '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-link-confirm">Vincular</button>' +
                  '<button type="button" class="tkt-btn" data-modal-close>Cancelar</button>',
        }));

        bindTicketSearch($backdrop, { input: '#tkt-link-target', results: '#tkt-link-results', excludeId: t.id });

        $backdrop.on('click', '[data-link-type-option]', function () {
            $backdrop.find('[data-link-type-option]').removeClass('on');
            $(this).addClass('on').find('input').prop('checked', true);
        });

        $backdrop.on('click', '#tkt-link-confirm', function () {
            var input = $('#tkt-link-target').val().trim();
            if (!/^\d+$/.test(input)) { if (window.toastr) toastr.error('Escribe un ID numérico de ticket'); return; }
            var linkType = $backdrop.find('input[name="tkt-link-type"]:checked').val();

            $.ajax({
                url: t.url_link,
                method: 'POST',
                data: { linked_ticket_id: input, link_type: linkType },
                headers: { Accept: 'application/json' },
                success: function () {
                    if (window.toastr) toastr.success('Ticket vinculado');
                    closeModal();
                    fetchDetailData(t);
                },
                error: function (xhr) {
                    var msg = (xhr.responseJSON && (xhr.responseJSON.message || (xhr.responseJSON.errors && Object.values(xhr.responseJSON.errors)[0][0]))) || 'No se pudo vincular el ticket';
                    if (window.toastr) toastr.error(msg); else window.alert(msg);
                },
            });
        });
    }

    // ═══════════ Colaboración en vivo (Fase E) ═══════════
    // Reusa tal cual la infraestructura ya existente (canal de presencia
    // ticket.{id} + eventos TicketTyping/TicketViewing, mismo patrón que
    // emails.js) — sin backend nuevo. El indicador de escritura, ausente
    // hasta ahora, ya tiene dónde engancharse: la caja de respuesta real
    // del Hilo (#tkt-reply-body).
    // ---- Refresco automático del hilo -------------------------------------
    //
    // Respaldo del tiempo real, no sustituto. Lo normal es que un mensaje nuevo
    // llegue por websocket (MessageAdded en el canal ticket.{id}); esto cubre
    // los casos en que ese aviso no llega: Reverb caído, el navegador sin
    // conexión, o la cola de broadcasts con retraso.
    //
    function showEditConflict(message, force, fields) {
        TKA.state.editConflictMessage = message || 'El ticket cambió mientras redactabas. Revisa el hilo antes de enviar.';
        if (Array.isArray(fields) && fields.length) TKA.state.editConflictFields = fields;
        var $body = $('#tkt-reply-body');
        var $banner = $('#tkt-edit-conflict');
        if (!$body.length || !$banner.length || (!force && !$body.val().trim())) return;
        $('#tkt-edit-conflict-text').text(TKA.state.editConflictMessage);
        $banner.removeAttr('hidden');
    }

    function openConflictReviewModal() {
        var t = TKA.state.currentTicket;
        var detail = TKA.state.currentDetail || {};
        var thread = detail.thread || [];
        var latestCustomer = null;
        for (var i = thread.length - 1; i >= 0; i--) {
            if (thread[i].type === 'message' && !thread[i].is_internal && !thread[i].from_agent) {
                latestCustomer = thread[i];
                break;
            }
        }

        var remoteText = latestCustomer && latestCustomer.body ? String(latestCustomer.body) : 'No hay un mensaje reciente del cliente para comparar.';
        if (latestCustomer && latestCustomer.is_html) {
            var textNode = document.createElement('div');
            textNode.innerHTML = remoteText;
            remoteText = textNode.textContent || textNode.innerText || '';
        }
        var draft = $('#tkt-reply-body').val() || '';
        var message = TKA.state.editConflictMessage || 'El ticket cambió mientras redactabas. Revisa el hilo antes de enviar.';
        var fields = (TKA.state.editConflictFields || []).map(auditFieldLabel);
        var fieldsHtml = fields.length ? '<div class="tkt-note"><i class="fa-solid fa-list-check"></i><span>Campos implicados: <b>' + escapeHtml(fields.join(', ')) + '</b>. La acción se detuvo hasta que confirmes la versión correcta.</span></div>' : '';
        var $backdrop = openModal(modalShell({
            icon: 'fa-solid fa-code-compare',
            title: 'Revisar cambios del ticket',
            kicker: t ? '#' + t.id : 'Edición concurrente',
            width: 'xl',
            body: '<div class="tkt-note warn"><i class="fa-solid fa-shield-halved"></i><span>' + escapeHtml(message) + '</span></div>' + fieldsHtml +
                '<div class="tkt-conflict-grid">' +
                    '<section><label class="tkt-field-label" for="tkt-conflict-draft">Tu borrador</label><textarea id="tkt-conflict-draft" class="tkt-textarea tkt-conflict-draft" rows="8">' + escapeHtml(draft) + '</textarea><p class="tkt-preview-hint">Puedes editarlo antes de volver a enviarlo.</p></section>' +
                    '<section><div class="tkt-field-label">Último mensaje del ticket</div><div class="tkt-conflict-remote">' + escapeHtml(remoteText).replace(/\n/g, '<br>') + '</div><p class="tkt-preview-hint">Se ha actualizado el hilo. Comprueba aquí el contexto nuevo.</p></section>' +
                '</div>',
            foot: '<button type="button" class="tkt-btn tkt-btn-danger" id="tkt-conflict-discard">Descartar borrador</button>' +
                '<button type="button" class="tkt-btn" id="tkt-conflict-keep">Mantener borrador</button>' +
                '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-conflict-apply">Aplicar edición</button>',
        }));

        $backdrop.on('click', '#tkt-conflict-apply', function () {
            var value = $('#tkt-conflict-draft').val() || '';
            $('#tkt-reply-body').val(value).trigger('input').trigger('focus');
            TKA.state.editConflictMessage = '';
            TKA.state.editConflictFields = [];
            $('#tkt-edit-conflict').attr('hidden', true);
            closeModal();
        });
        $backdrop.on('click', '#tkt-conflict-keep', function () {
            TKA.state.editConflictMessage = '';
            TKA.state.editConflictFields = [];
            $('#tkt-edit-conflict').attr('hidden', true);
            closeModal();
            $('#tkt-reply-body').trigger('focus');
        });
        $backdrop.on('click', '#tkt-conflict-discard', function () {
            var current = TKA.state.currentTicket;
            if (current) clearComposerDraft(current);
            $('#tkt-reply-body').val('').trigger('input').trigger('blur');
            $('#tkt-reply-attach').val('');
            $('#tkt-reply-attach-count').text('');
            TKA.state.editConflictMessage = '';
            TKA.state.editConflictFields = [];
            $('#tkt-edit-conflict').attr('hidden', true);
            closeModal();
        });
    }

    // Hacía falta de verdad: los once eventos de tiempo real del módulo se
    // encolaban en `default`, que ningún worker de webadmin sirve, así que el
    // correo de un cliente con el ticket abierto no aparecía hasta recargar a
    // mano. Aunque eso ya está arreglado, un panel de soporte no puede quedarse
    // mudo porque una cola vaya lenta.
    //
    // Se pregunta a /pulse, que devuelve dos agregados, y solo si algo cambió
    // se pide el detalle completo. Preguntar directamente por /data cada tres
    // segundos sería lanzar el hilo entero —traducciones, adjuntos, correos,
    // relacionados— veinte veces por minuto y por agente.
    function startTicketPulse(t) {
        stopTicketPulse();

        if (!t || !t.url_pulse) return;

        TKA.state.pulseTicketId = t.id;
        TKA.state.pulseLast = null;
        // Ciclos seguidos sin novedad. Alimenta el espaciado de abajo.
        TKA.state.pulseQuiet = 0;

        TKA.state.pulseTimer = setInterval(function () {
            // Con la pestaña en segundo plano no se pregunta: el agente no está
            // mirando, y al volver el navegador dispara 'visibilitychange'.
            if (document.hidden || !TKA.state.networkOnline || navigator.onLine === false) return;

            // Espaciado progresivo: 3s mientras hay movimiento, y hasta 15s en
            // un ticket que lleva un rato quieto. Esta sonda nació cuando los
            // once eventos de tiempo real del módulo se encolaban en `default`,
            // que ningún worker servía (arreglado el 7-sep-2026): ahora el
            // websocket entrega, así que preguntar cada 3 segundos para siempre
            // es gastar 1.200 peticiones por agente y jornada sin necesidad.
            // Un cambio detectado reinicia el contador y vuelve a los 3s.
            var quieto = TKA.state.pulseQuiet || 0;
            var saltar = quieto > 40 ? 4 : (quieto > 20 ? 2 : (quieto > 10 ? 1 : 0));

            if (saltar && (quieto % (saltar + 1)) !== 0) return;

            var current = TKA.state.currentTicket;
            if (!current || current.id !== TKA.state.pulseTicketId) {
                stopTicketPulse();
                return;
            }

            // Sin solapar: si una sonda tarda más que el intervalo, no se
            // encadenan peticiones sobre una red lenta.
            if (TKA.state.pulseInFlight) return;
            TKA.state.pulseInFlight = true;

            $.getJSON(current.url_pulse)
                .done(function (p) {
                    var firma = [p.items, p.last_item_id, p.last_item_at, p.ticket_updated_at, p.status_id].join('|');

                    // La primera respuesta solo fija la referencia: si no, cada
                    // apertura de ticket recargaría el detalle dos veces.
                    if (TKA.state.pulseLast === null) {
                        TKA.state.pulseLast = firma;
                        return;
                    }

                    if (TKA.state.pulseLast !== firma) {
                        TKA.state.pulseLast = firma;
                        TKA.state.pulseQuiet = 0;
                        fetchDetailData(current);
                    } else {
                        TKA.state.pulseQuiet++;
                    }
                })
                .always(function () { TKA.state.pulseInFlight = false; });
        }, TKA.pulseIntervalMs || 3000);
    }

    function stopTicketPulse() {
        if (TKA.state.pulseTimer) {
            clearInterval(TKA.state.pulseTimer);
        }

        TKA.state.pulseTimer = null;
        TKA.state.pulseTicketId = null;
        TKA.state.pulseLast = null;
        TKA.state.pulseInFlight = false;
    }

    // Late contra TicketPresenceController::heartbeat() mientras el detalle
    // está abierto — endpoint que ya existía (lo usa el aviso "colisión" del
    // propio canal de presencia) pero al que ningún JS llamaba todavía
    // (huérfano, QA 14-sep-2026): sin este heartbeat, Cache::get(
    // "helpdesk:ticket:{id}:viewers") está siempre vacío y el listado no
    // tiene forma de saber que este ticket se está viendo ahora mismo.
    // Cada 20s de sobra (el TTL es 60s, la purga a los 35s) — no hace falta
    // la cadencia de 3s de startTicketPulse(), esto no detecta cambios, solo
    // dice "sigo aquí".
    var PRESENCE_HEARTBEAT_MS = 20000;

    function startPresenceHeartbeat(t) {
        stopPresenceHeartbeat();

        if (!t || !t.url_presence_heartbeat) return;

        TKA.state.presenceHeartbeatTicket = t;

        function beat() {
            if (document.hidden || !TKA.state.networkOnline || navigator.onLine === false) return;
            var current = TKA.state.presenceHeartbeatTicket;
            if (!current) return;
            $.post(current.url_presence_heartbeat, { action: 'viewing' });
        }

        beat();
        TKA.state.presenceHeartbeatTimer = setInterval(beat, PRESENCE_HEARTBEAT_MS);
    }

    function stopPresenceHeartbeat() {
        if (TKA.state.presenceHeartbeatTimer) clearInterval(TKA.state.presenceHeartbeatTimer);
        TKA.state.presenceHeartbeatTimer = null;

        var t = TKA.state.presenceHeartbeatTicket;
        TKA.state.presenceHeartbeatTicket = null;

        // Fire-and-forget: avisa "ya no estoy" sin bloquear el cambio de
        // ticket ni fallar si la pestaña se está cerrando.
        if (t && t.url_presence_leave) {
            $.ajax({ url: t.url_presence_leave, method: 'DELETE' });
        }
    }

    // Al volver a la pestaña se comprueba en el acto en vez de esperar al
    // siguiente tic: es justo el momento en que el agente quiere ver si le han
    // contestado.
    document.addEventListener('visibilitychange', function () {
        if (document.hidden) return;

        var t = TKA.state.currentTicket;
        if (t && TKA.state.pulseTimer) fetchDetailData(t);
    });

    function joinTicketPresence(ticketId) {
        if (!ticketId) return;

        // Entrando por URL directa (?ticket=N) el panel pinta el detalle en el
        // arranque, y para entonces el script de Echo todavía no se ha
        // ejecutado. Antes se salía aquí en silencio y ESE ticket se quedaba
        // sin tiempo real durante toda la sesión: no recibía el aviso de
        // mensaje nuevo y solo se enteraba por el sondeo de /pulse, con hasta
        // veinte segundos de retraso. Al cambiar de ticket desde la lista sí
        // funcionaba —Echo ya existía—, y por eso el fallo parecía aleatorio.
        //
        // Se espera a que Echo aparezca en vez de rendirse; el tope evita
        // sondear para siempre en entornos sin Reverb levantado.
        if (typeof window.Echo === 'undefined') {
            TKA.state.presenceWaits = (TKA.state.presenceWaits || 0) + 1;

            if (TKA.state.presenceWaits <= 20) {
                setTimeout(function () {
                    // Si el agente ya se ha movido a otro ticket, este reintento
                    // sobra: se suscribiría al que ya no está abierto.
                    var actual = TKA.state.currentTicket;
                    if (actual && actual.id === ticketId) joinTicketPresence(ticketId);
                }, 500);
            } else {
                setNetworkState(TKA.state.networkOnline, 'Reintentando tiempo real…');
                scheduleRealtimeRetry(ticketId);
            }

            return;
        }

        TKA.state.presenceWaits = 0;

        if (TKA.state.presenceTicketId === ticketId) return;

        leaveTicketPresence();
        TKA.state.presenceTicketId = ticketId;

        try {
            TKA.state.presenceChannel = window.Echo.join('ticket.' + ticketId)
                .here(function (users) {
                    TKA.state.realtimeRetryCount = 0;
                    setNetworkState(true, 'Tiempo real conectado');
                    renderCollisionBanner(users);
                })
                .joining(function () { renderCollisionBanner(); })
                .leaving(function () { renderCollisionBanner(); })
                .error(function () {
                    TKA.state.realtimeErrorCount = (TKA.state.realtimeErrorCount || 0) + 1;
                    TKA.state.presenceChannel = null;
                    TKA.state.presenceTicketId = null;
                    setNetworkState(false);
                    scheduleRealtimeRetry(ticketId);
                })
                .listen('.typing', function (e) {
                    markRealtimeEvent('Actividad de escritura recibida');
                    if (e.userId === TKA.state.currentUserId) return;

                    // Se registra quién escribe (no solo se pinta la franja):
                    // lo necesitan las burbujas y, sobre todo, la confirmación
                    // antes de enviar una respuesta duplicada.
                    TKA.state.typingUsers = TKA.state.typingUsers || {};
                    if (e.isTyping) {
                        TKA.state.typingUsers[e.userId] = { name: e.userName, at: Date.now() };
                    } else {
                        delete TKA.state.typingUsers[e.userId];
                    }
                    renderCollisionBanner();

                    var $ind = $('#tkt-typing-indicator');
                    if (e.isTyping) {
                        $('#tkt-typing-text').html('<strong>' + escapeHtml(e.userName) + '</strong> está redactando una respuesta…');
                        $ind.show();
                    } else {
                        $ind.hide();
                    }
                })
                // Antes, un correo entrante real con el ticket ya abierto no
                // aparecía hasta recargar la página a mano — MessageAdded
                // ahora transmite en este mismo canal (ver el evento en
                // HelpdeskTickets\Events\MessageAdded). El canal ya está
                // scopeado al ticket abierto (se re-suscribe en cada cambio
                // de ticket, ver leaveTicketPresence()), así que cualquier
                // aviso aquí es de ESTE ticket — se vuelve a pedir el
                // detalle completo en vez de reconstruir el hilo a mano con
                // un payload parcial.
                .listen('.message.added', function () {
                    markRealtimeEvent('Mensaje nuevo recibido');
                    var current = TKA.state.currentTicket;
                    if ($('#tkt-reply-body').val().trim()) {
                        showEditConflict('Llegó un mensaje nuevo mientras redactabas. Revisa la conversación antes de enviar.');
                    }
                    // El refetch del listado también devuelve el estado actual
                    // de la fila. Esto cubre el caso en que un cliente responde
                    // a un ticket cerrado y el backend lo reabre antes de crear
                    // el mensaje: no dejamos la cabecera mostrando "Cerrado".
                    if (current) {
                        queueTicketListRefresh('message-added-open-ticket', current, {
                            freshCounts: true,
                            preserveBulk: true,
                            refreshDetail: true,
                        });
                    }
                    playNewMessageSound();
                })
                // Asignar (a mano, al responder, por automatización o en
                // bloque) ahora se transmite en este mismo canal — antes solo
                // se enteraba quien disparaba la acción, y un compañero con
                // este mismo ticket abierto se quedaba viendo al dueño
                // anterior (o el banner de autoasignación sobre un ticket que
                // ya cogió otro) hasta recargar a mano.
                //
                // e.assignee ya trae {id, name} (ver TicketAssigned::
                // broadcastWith) para el pintado inmediato del nombre/avatar,
                // igual que hace apply() en openAssignModal(); fetchDetailData
                // detrás trae lo que solo vive en data() —carga del agente,
                // "asignado hace…"— que el broadcast no repite a propósito.
                .listen('.assigned', function (e) {
                    markRealtimeEvent('Asignación actualizada');
                    var current = TKA.state.currentTicket;
                    if (!current || !e || !e.assignee) return;

                    // Si el ticket era tuyo y deja de serlo entre un aviso y
                    // el anterior, es justo el caso que puede pillar al
                    // agente a media respuesta sin enterarse: un compañero
                    // (o una automatización) te lo acaba de quitar.
                    var eraMio = current.assignee && current.assignee.id === TKA.state.currentUserId;
                    var sigueSiendoMio = e.assignee.id === TKA.state.currentUserId;
                    if (eraMio && !sigueSiendoMio && window.toastr) {
                        toastr.warning('Ahora lo tiene ' + e.assignee.name + '.', 'Te han quitado este ticket');
                    }

                    // El modal de asignar (#tkt-assign-list, abierto a mano o
                    // desde "¿Quieres asignártelo?") trabaja con una lista de
                    // agentes ya caducada en cuanto llega este aviso: seguir
                    // dejándolo abierto invita justo a la carrera que se
                    // quiere evitar —dos agentes pulsando "Asignarme" casi a
                    // la vez, y el segundo se lo roba de vuelta sin darse
                    // cuenta—. Si el segundo agente es quien sigue mirando
                    // este modal, ya se avisó arriba (eraMio nunca aplica
                    // aquí porque el ticket no era suyo); este aviso es el
                    // suyo propio.
                    if ($('#tkt-assign-list').length) {
                        closeModal();
                        if (window.toastr && !sigueSiendoMio) {
                            toastr.info('Alguien se te adelantó: ahora lo tiene ' + e.assignee.name + '.', 'Ticket ya asignado');
                        }
                    }

                    current.assignee = e.assignee;
                    renderSidePanel(current);
                    renderSelfAssignBanner(current);
                    fetchDetailData(current);
                })
                // Seguir/dejar de seguir (TicketLifecycleController::watch()/
                // unwatch()) era completamente silencioso: un compañero con
                // el mismo ticket abierto se quedaba viendo "seguidores: N"
                // desactualizado hasta recargar a mano. Payload vacío a
                // propósito (ver TicketWatcherChanged): fetchDetailData ya
                // trae watchers/watchers_count reales, igual que con
                // .message.added arriba.
                .listen('.watchers.changed', function () {
                    markRealtimeEvent('Seguidores actualizados');
                    var current = TKA.state.currentTicket;
                    if (current) fetchDetailData(current);
                });
        } catch (e) {
            // Sin Echo/Reverb levantado en este entorno: la pantalla sigue
            // funcionando igual, solo sin el aviso de colaboración en vivo.
            TKA.state.presenceChannel = null;
            TKA.state.presenceTicketId = null;
            setNetworkState(false, 'Tiempo real no disponible');
            scheduleRealtimeRetry(ticketId);
        }
    }

    function emitTyping(isTyping) {
        var t = TKA.state.currentTicket;
        if (!t || !TKA.urls.typingTemplate) return;
        $.post(TKA.urls.typingTemplate.replace('__TICKET__', t.id), { is_typing: isTyping ? 1 : 0 });
    }

    function leaveTicketPresence() {
        stopTicketPulse();
        stopPresenceHeartbeat();
        stopSlaClock();

        if (TKA.state.realtimeRetryTimer) clearTimeout(TKA.state.realtimeRetryTimer);
        TKA.state.realtimeRetryTimer = null;
        TKA.state.realtimeRetryCount = 0;

        if (TKA.state.presenceTicketId && typeof window.Echo !== 'undefined') {
            try { window.Echo.leave('ticket.' + TKA.state.presenceTicketId); } catch (e) { /* ignore */ }
        }
        TKA.state.presenceChannel = null;
        TKA.state.presenceTicketId = null;
    }

    // Cuánto se considera "sigue escribiendo" desde el último aviso .typing.
    // El emisor manda isTyping=false a los 2,5s de parar (ver emitTyping), así
    // que esto solo cubre el caso de que ese aviso final se pierda.
    var TYPING_TTL_MS = 15000;

    // Agentes que están redactando AHORA mismo, por id. Alimenta las burbujas,
    // el modal de colisión y la confirmación antes de enviar.
    function typingOthers() {
        var ahora = Date.now();
        var out = [];

        Object.keys(TKA.state.typingUsers || {}).forEach(function (id) {
            var u = TKA.state.typingUsers[id];
            if (!u) return;
            if (ahora - u.at > TYPING_TTL_MS) { delete TKA.state.typingUsers[id]; return; }
            if (Number(id) === TKA.state.currentUserId) return;
            out.push({ id: Number(id), name: u.name });
        });

        return out;
    }

    function isTypingNow(userId) {
        return typingOthers().some(function (u) { return u.id === userId; });
    }

    function renderCollisionBanner(users) {
        if (users) TKA.state.presenceUsers = users;
        var others = (TKA.state.presenceUsers || []).filter(function (u) { return u.id !== TKA.state.currentUserId; });

        renderPresenceBubbles(others);

        if (!others.length) {
            $('#tkt-collision-banner').hide();
            return;
        }

        var names = others.map(function (u) { return u.name; }).join(', ');
        var verb = others.length > 1 ? 'están viendo' : 'está viendo';
        $('#tkt-collision-text').html('<strong>' + escapeHtml(names) + '</strong> ' + verb + ' este ticket.');
        $('#tkt-collision-banner').show();
    }

    // Burbujas de avatar de los agentes presentes (estilo Drive/Docs): se ven
    // en la cabecera, con anillo animado en quien está redactando, y abren el
    // modal "Bandeja compartida" con las acciones sobre esa persona.
    function renderPresenceBubbles(others) {
        var $box = $('#tkt-presence');
        if (!$box.length) return;

        // Quien está escribiendo se muestra SIEMPRE, aunque el canal de
        // presencia no lo liste: .here() puede llegar incompleto (Reverb
        // reconectando, el otro agente entró antes que tú) y precisamente esa
        // persona es la que no puede pasar desapercibida.
        var lista = (others || []).slice();

        typingOthers().forEach(function (u) {
            if (!lista.some(function (p) { return p.id === u.id; })) lista.push(u);
        });

        others = lista;

        if (!others.length) {
            $box.attr('hidden', true).empty();
            return;
        }

        var MAX = 3;
        var html = others.slice(0, MAX).map(function (u) {
            var escribiendo = isTypingNow(u.id);
            var titulo = u.name + (escribiendo ? ' · está redactando una respuesta' : ' · viendo el ticket');

            return '<button type="button" class="tkt-presence-av c' + (u.id % 6) + (escribiendo ? ' is-typing' : '') + '"' +
                ' data-presence-user="' + escapeHtml(String(u.id)) + '"' +
                ' title="' + escapeHtml(titulo) + '" aria-label="' + escapeHtml(titulo) + '">' +
                escapeHtml(initials(u.name)) +
                '</button>';
        }).join('');

        if (others.length > MAX) {
            html += '<button type="button" class="tkt-presence-av more" data-presence-user="all"' +
                ' title="' + escapeHtml(others.map(function (u) { return u.name; }).join(', ')) + '">+' +
                (others.length - MAX) + '</button>';
        }

        $box.html(html).removeAttr('hidden');
    }

    // El aviso de "está escribiendo" caduca por tiempo (TYPING_TTL_MS), no por
    // un evento: si el otro agente cierra la pestaña de golpe, su isTyping=false
    // no llega nunca y su burbuja se quedaría con el anillo para siempre. Este
    // repaso ligero (solo repinta, no pide nada al servidor) la apaga sola.
    setInterval(function () {
        if (document.hidden) return;
        if (!document.getElementById('tkt-presence')) return;
        if (!Object.keys(TKA.state.typingUsers || {}).length) return;

        renderCollisionBanner();
    }, 5000);

    // Presencia en vivo DEL LISTADO — quién está viendo cada fila ahora
    // mismo, sin tener que abrir cada ticket. A diferencia de la presencia
    // del detalle (canal de Reverb, push), esto es sondeo simple contra
    // TicketPresenceController::overview() cada 8s: unirse a un canal de
    // presencia por cada fila visible (hasta 50 a la vez) sería 50
    // conexiones de Reverb solo por tener la lista abierta — el heartbeat
    // que ya escribe startPresenceHeartbeat() (TTL 60s, purga a los 35s)
    // hace que un sondeo cada 8s vaya sobrado de margen.
    var PRESENCE_LIST_POLL_MS = 8000;

    function pollListPresence() {
        if (document.hidden || !TKA.urls.presenceOverview) return;

        var rows = visibleTickets();
        if (!rows.length) return;

        $.getJSON(TKA.urls.presenceOverview, { ids: rows.map(function (t) { return t.id; }).join(',') })
            .done(function (res) {
                var data = (res && res.data) || {};

                rows.forEach(function (t) {
                    var viewers = data[t.id] || [];
                    // Comparar solo por quién está (no por 'at'/timestamp, que
                    // cambia en cada heartbeat aunque sea la misma gente) —
                    // si no, esto repintaría las 27+ filas visibles cada 8s
                    // aunque no haya cambiado nada, perdiendo cualquier
                    // selección de texto o scroll fino que el agente tuviera
                    // en ese momento.
                    var antes = (t.viewers || []).map(function (v) { return v.user_id; }).sort().join(',');
                    var ahora = viewers.map(function (v) { return v.user_id; }).sort().join(',');
                    if (antes === ahora) return;

                    t.viewers = viewers;
                    var $oldRow = $('.tkt-ticket-row[data-id="' + t.id + '"]');
                    if ($oldRow.length) $oldRow.replaceWith(renderRow(t));
                });
            });
    }

    setInterval(pollListPresence, PRESENCE_LIST_POLL_MS);

    // Disponibilidad general del agente (módulo Helpdesk hermano, no
    // HelpdeskTickets) — bug real encontrado el 14-sep-2026: "N agentes en
    // línea" del pie de pantalla daba siempre 0 porque fetchOnlineAgentsCount()
    // (modal-14-emails-del-ticket.js) leía un campo 'status' que el endpoint
    // que llamaba nunca devolvía. El sistema que SÍ calcula presencia de
    // verdad (AgentPresenceService, con heartbeat en Redis) ya existía, pero
    // nadie llamaba a su endpoint de heartbeat — sin ping, TODOS los agentes
    // caducan a 'offline' a los 90s, se hayan puesto disponibles o no desde
    // el modal "Cambiar disponibilidad" o no.
    //
    // A propósito SIN atarlo a ningún ticket (a diferencia de
    // startPresenceHeartbeat): esto es "sigo usando el panel", no "sigo
    // viendo este ticket" — debe latir todo el tiempo que la pantalla esté
    // abierta, aunque no haya ningún detalle seleccionado.
    // AgentPresenceController::heartbeat() ya autoasigna 'available' en el
    // primer latido si el agente seguía 'offline' — no hace falta
    // replicar esa lógica aquí, un POST y ya.
    if (TKA.urls.agentPresenceHeartbeat) {
        var agentPresenceBeat = function () {
            if (document.hidden || !TKA.state.networkOnline || navigator.onLine === false) return;
            $.post(TKA.urls.agentPresenceHeartbeat);
        };
        agentPresenceBeat();
        setInterval(agentPresenceBeat, 60000);
    }

    // Clic en una burbuja → modal de colisión (avisar / asignar / tomar el control).
    $(document).on('click', '[data-presence-user]', function () {
        var t = TKA.state.currentTicket;
        if (t) openCollisionModal(t, TKA.state.presenceUsers || []);
    });

    function patchTags(t, add, remove) {
        var data = {};
        if (add) data.add = add;
        if (remove) data.remove = remove;
        $.ajax({
            url: t.url_tags,
            method: 'PATCH',
            data: data,
            headers: { Accept: 'application/json' },
            success: function (resp) {
                t.tags = resp.tags;
                renderRowTagsIfVisible(t);
                renderTagsPane($('#tkt-side-content'), t, TKA.state.currentDetail ? TKA.state.currentDetail.ai_suggestion : null);
                if (window.toastr) toastr.success('Etiquetas actualizadas');
            },
            error: function () {
                if (window.toastr) toastr.error('No se pudo actualizar las etiquetas');
            },
        });
    }

    function renderRowTagsIfVisible() {
        // Las etiquetas no se muestran en la fila de la lista en esta
        // pantalla (a diferencia del mockup de Emails) — no-op reservado
        // por si una fase posterior las añade a la fila.
    }

    function createNote(t) {
        var $ta = $('#tkt-new-note');
        var body = $ta.val().trim();
        if (!body) return;
        var isPinned = $('#tkt-new-note-pin').is(':checked');
        $.ajax({
            url: TKA.urls.notesStoreTemplate.replace('__TICKET__', t.id),
            method: 'POST',
            data: { ticket_id: t.id, body: body, is_pinned: isPinned ? 1 : 0 },
            headers: { Accept: 'application/json' },
            success: function () {
                if (window.toastr) toastr.success('Nota guardada');
                fetchDetailData(t);
            },
            error: function (xhr) {
                var msg = apiErrorMessage(xhr, 'No se pudo guardar la nota');
                if (window.toastr) toastr.error(msg); else window.alert(msg);
            },
        });
    }

    function canonicalClientStatusSlug(slug) {
        return slug === 'new' ? 'open' : slug;
    }

    function applyLocalTicketField(t, field, value) {
        if (!t) return;
        t[field] = value;

        if (field === 'status_id') {
            var status = (TKA.state.statuses || []).find(function (s) { return String(s.id) === String(value); });
            if (status) {
                t.status_slug = canonicalClientStatusSlug(status.slug);
                t.status_name = status.name;
            }
        } else if (field === 'category_id') {
            var category = (TKA.state.categories || []).find(function (c) { return String(c.id) === String(value); });
            t.category_name = category ? category.name : null;
        } else if (field === 'group_id') {
            var group = (TKA.state.groups || []).find(function (g) { return String(g.id) === String(value); });
            t.group_name = group ? group.name : null;
        }
    }

    function snapshotTicketField(t, field) {
        return {
            field: field,
            value: t ? t[field] : undefined,
            status_slug: t ? t.status_slug : undefined,
            status_name: t ? t.status_name : undefined,
            category_name: t ? t.category_name : undefined,
            group_name: t ? t.group_name : undefined,
        };
    }

    function restoreTicketField(t, snapshot) {
        if (!t || !snapshot) return;
        t[snapshot.field] = snapshot.value;
        if (snapshot.field === 'status_id') {
            t.status_slug = snapshot.status_slug;
            t.status_name = snapshot.status_name;
        }
        if (snapshot.field === 'category_id') t.category_name = snapshot.category_name;
        if (snapshot.field === 'group_id') t.group_name = snapshot.group_name;
    }

    function refreshOptimisticTicket(t) {
        if (!t) return;
        var $row = $('.tkt-ticket-row[data-id="' + t.id + '"]');
        if ($row.length) $row.replaceWith(renderRow(t));
        if (TKA.state.currentTicket && String(TKA.state.currentTicket.id) === String(t.id)) {
            TKA.state.currentTicket = t;
            renderDetail(t);
            renderSidePanel(t);
        }
    }

    function patchTicket(t, field, value) {
        // La ruta es PUT, pero un PUT real por AJAX devuelve 405 en este
        // entorno Docker aunque route:list/OPTIONS lo muestren registrado
        // (gotcha ya documentado del proyecto) — se manda como POST con
        // _method=PUT (spoofing nativo de Laravel), igual que un formulario
        // Blade con @method('PUT').
        var data = { _method: 'PUT' };
        data[field] = value;
        if (t.updated_at) data.client_updated_at = t.updated_at;
        var snapshot = snapshotTicketField(t, field);
        applyLocalTicketField(t, field, value);
        refreshOptimisticTicket(t);
        $.ajax({
            url: t.url_update,
            method: 'POST',
            data: data,
            headers: { Accept: 'application/json' },
            success: function (resp) {
                if (window.toastr) toastr.success('Ticket actualizado');
                if (resp && resp.ticket) $.extend(t, resp.ticket);
                applyLocalTicketField(t, field, value);
                if (!TKA.state.suppressUndoOnce) {
                    var labels = { status_id: 'estado', category_id: 'categoría', group_id: 'equipo', assignee_id: 'asignación', priority: 'prioridad' };
                    offerUndo('Se cambió la ' + (labels[field] || 'propiedad') + '.', function () {
                        TKA.state.suppressUndoOnce = true;
                        patchTicket(t, field, snapshot.value);
                    });
                } else {
                    TKA.state.suppressUndoOnce = false;
                }
                queueTicketListRefresh('ticket-field-updated', t, {
                    freshCounts: true,
                    refreshDetail: true,
                    forceDetail: true,
                });
            },
            error: function (xhr) {
                restoreTicketField(t, snapshot);
                refreshOptimisticTicket(t);
                var msg = apiErrorMessage(xhr, 'No se pudo actualizar el ticket');
                TKA.state.suppressUndoOnce = false;
                if (xhr.status === 409) {
                    if (xhr.responseJSON && xhr.responseJSON.ticket) $.extend(t, xhr.responseJSON.ticket);
                    showEditConflict(msg, true, xhr.responseJSON && xhr.responseJSON.conflict_fields);
                    fetchDetailData(t);
                    if (window.toastr) toastr.warning(msg, 'Cambio simultáneo detectado');
                    return;
                }
                if (window.toastr) toastr.error(msg); else window.alert(msg);
            },
        });
    }

    function runLifecycleAction(t, action, extra) {
        var url = action === 'resolve' ? t.url_resolve : (action === 'close' ? t.url_close : t.url_reopen);
        if (!url) return;
        var targetSlug = action === 'close' ? 'closed' : (action === 'resolve' ? 'resolved' : 'open');
        var targetStatus = (TKA.state.statuses || []).find(function (s) {
            return canonicalClientStatusSlug(s.slug) === targetSlug;
        });
        var snapshot = snapshotTicketField(t, 'status_id');
        if (targetStatus) {
            applyLocalTicketField(t, 'status_id', targetStatus.id);
            refreshOptimisticTicket(t);
        }
        $.ajax({
            url: url,
            method: 'POST',
            headers: { Accept: 'application/json' },
            data: $.extend({}, extra || {}, t.updated_at ? { client_updated_at: t.updated_at } : {}),
            success: function (resp) {
                if (window.toastr) toastr.success((resp && resp.message) || 'Ticket actualizado');
                if (resp && resp.ticket) $.extend(t, resp.ticket);
                if (targetStatus) applyLocalTicketField(t, 'status_id', targetStatus.id);
                var suppressUndo = TKA.state.suppressUndoOnce;
                TKA.state.suppressUndoOnce = false;
                if ((action === 'close' || action === 'resolve') && !suppressUndo) {
                    offerUndo(action === 'close' ? 'Ticket cerrado.' : 'Ticket resuelto.', function () {
                        runLifecycleAction(t, 'reopen');
                    });
                }
                queueTicketListRefresh('ticket-' + action, t, {
                    freshCounts: true,
                    refreshDetail: true,
                    forceDetail: true,
                });
            },
            error: function (xhr) {
                restoreTicketField(t, snapshot);
                refreshOptimisticTicket(t);
                var msg = apiErrorMessage(xhr, 'No se pudo completar la acción');
                TKA.state.suppressUndoOnce = false;
                if (xhr.status === 409) {
                    showEditConflict(msg, true, xhr.responseJSON && xhr.responseJSON.conflict_fields);
                    fetchDetailData(t);
                    if (window.toastr) toastr.warning(msg, 'Cambio simultáneo detectado');
                    return;
                }
                if (window.toastr) toastr.error(msg); else window.alert(msg);
            },
        });
    }

    // Modal "Cerrar ticket": motivos de config('helpdesktickets.close_reasons')
    // (mismas claves/descripciones que el modal "Cerrar conversación" de
    // Conversaciones) + campo libre para "Otro motivo" — mismo patrón radio
    // ya usado en openDeleteModal/openExportModal (label.tkt-option +
    // input radio oculto). El motivo es opcional: cerrar sin elegir ninguno
    // sigue funcionando (equivalente al confirm() que sustituye este modal).
    var CLOSE_REASON_SUB = {
        resolved: 'El cliente quedó satisfecho con la solución',
        duplicated: 'Ya hay otro ticket abierto para este caso',
        spam: 'Mensaje no solicitado o fuera de contexto',
        unresponsive: 'Cerrar por inactividad prolongada',
        other: '',
    };

    function openCloseTicketModal(t) {
        var reasons = TKA.state.closeReasons || [];
        var causas = TKA.state.closeRootCauses || [];
        var optionsHtml = reasons.map(function (r, i) {
            var sub = CLOSE_REASON_SUB[r.key];
            return '<label class="tkt-option' + (i === 0 ? ' on' : '') + '" data-reason-option="' + escapeHtml(r.key) + '">' +
                '<input type="radio" name="tkt-close-reason" value="' + escapeHtml(r.key) + '"' + (i === 0 ? ' checked' : '') + ' class="tkt-m0">' +
                '<span><span class="tkt-option-title">' + escapeHtml(r.label) + '</span>' +
                (sub ? '<br><span class="tkt-option-sub">' + escapeHtml(sub) + '</span>' : '') + '</span></label>';
        }).join('');

        var $modal = openModal(modalShell({
            icon: 'fa-solid fa-lock',
            kicker: 'Ticket · cierre',
            titleChip: t.ticket_number || String(t.id),
            title: 'Motivo de cierre',
            body:
                '<div class="tkt-field">' +
                    '<label class="tkt-label">Motivo (opcional)</label>' +
                    optionsHtml +
                '</div>' +
                '<div class="tkt-field" id="tkt-close-other-wrap" hidden>' +
                    '<label class="tkt-label">Detalle</label>' +
                    '<input type="text" class="tkt-input" id="tkt-close-other-input" maxlength="100" placeholder="Describe el motivo…">' +
                '</div>' +
                // Causa raíz: el motivo dice CÓMO acabó el ticket, esto dice
                // POR QUÉ existió. Es lo que se agrupa en los informes para
                // ver qué genera trabajo repetido.
                (causas.length
                    ? '<div class="tkt-field">' +
                        '<label class="tkt-label">Causa raíz</label>' +
                        '<select class="tkt-fselect tkt-w-100" id="tkt-close-root-cause">' +
                            '<option value="">Sin clasificar</option>' +
                            causas.map(function (c) {
                                return '<option value="' + escapeHtml(c.key) + '">' + escapeHtml(c.label) + '</option>';
                            }).join('') +
                        '</select></div>'
                    : '') +
                '<div class="tkt-field">' +
                    '<label class="tkt-label">Resumen para el informe</label>' +
                    '<textarea class="tkt-input" id="tkt-close-summary" rows="2" maxlength="2000" ' +
                        'placeholder="Qué pasó y cómo se resolvió (opcional)"></textarea>' +
                '</div>' +
                // Cerrar disparaba SIEMPRE la encuesta de satisfacción. En un
                // cierre por spam o duplicado, preguntar sobra.
                '<label class="tkt-check">' +
                    '<input type="checkbox" id="tkt-close-skip-survey"> No enviar la encuesta de satisfacción' +
                '</label>',
            foot: '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-close-confirm">Cerrar ticket</button>' +
                  '<button type="button" class="tkt-btn" data-modal-close>Cancelar</button>',
        }));

        $modal.on('click', '[data-reason-option]', function () {
            $modal.find('[data-reason-option]').removeClass('on');
            $(this).addClass('on').find('input').prop('checked', true);
            var reason = $(this).data('reason-option');
            $modal.find('#tkt-close-other-wrap').prop('hidden', reason !== 'other');
            if (reason === 'other') $modal.find('#tkt-close-other-input').trigger('focus');
        });

        $modal.on('click', '#tkt-close-confirm', function () {
            var reason = $modal.find('input[name="tkt-close-reason"]:checked').val() || '';
            if (reason === 'other') reason = $modal.find('#tkt-close-other-input').val() || '';
            var payload = {
                reason: reason,
                root_cause: $modal.find('#tkt-close-root-cause').val() || '',
                summary: $modal.find('#tkt-close-summary').val() || '',
                skip_survey: $modal.find('#tkt-close-skip-survey').is(':checked') ? 1 : 0,
            };
            closeModal();
            runLifecycleAction(t, 'close', payload);
        });
    }

    // ═══════════ Bulk actions ═══════════
    function renderBulkBar() {
        var ids = Object.keys(TKA.state.bulk).map(Number);
        $('#tkt-bulk-count').text(ids.length + (ids.length === 1 ? ' seleccionado' : ' seleccionados'));
        $('#tkt-bulk-bar').toggleClass('on', ids.length > 0);
        syncSelectAllCheckbox();
    }

    // Resincroniza #tkt-select-all con el estado real de selección — antes
    // solo se actualizaba al pulsar el propio maestro; desmarcar UNA fila
    // individual tras "Seleccionar todo" lo dejaba marcado como si las 11
    // siguieran seleccionadas. Único punto de entrada (llamado desde
    // renderBulkBar(), que ya se invoca tras cualquier mutación de
    // TKA.state.bulk: fila individual, maestro o "Quitar selección").
    function syncSelectAllCheckbox() {
        var $master = $('#tkt-select-all');
        if (!$master.length) return;
        var visible = visibleTickets();
        if (!visible.length) {
            $master.prop('checked', false);
            $master[0].indeterminate = false;
            return;
        }
        var selectedCount = visible.filter(function (t) { return TKA.state.bulk[t.id]; }).length;
        $master[0].checked = selectedCount === visible.length;
        $master[0].indeterminate = selectedCount > 0 && selectedCount < visible.length;
    }

    // Acciones en bloque que necesitan un valor adicional que
    // BulkTicketRequest exige (agent_id/tag/status_id) — cada una abre su
    // propio modal con el control correcto (select de agente/estado, input
    // de texto) en vez de un prompt(). Mismo patrón que
    // openBulkMoveTeamModal (ya existente para assign_group).
    var BULK_EXTRA_CONFIG = {
        assign: {
            icon: 'fa-solid fa-user-check', title: 'Asignar agente', field: 'agent_id', confirmLabel: 'Asignar',
            emptyError: 'Selecciona un agente',
            body: function () {
                return '<div class="tkt-field"><label class="tkt-label">Agente</label><select id="tkt-bulk-extra" class="tkt-select"><option value="">Selecciona un agente…</option>' + optionsHtml(TKA.state.agentsFull, 'id', '') + '</select></div>';
            },
        },
        add_tag: {
            icon: 'fa-solid fa-tag', title: 'Añadir etiqueta', field: 'tag', confirmLabel: 'Añadir',
            emptyError: 'Escribe una etiqueta',
            body: function () {
                return '<div class="tkt-field"><label class="tkt-label">Etiqueta</label><input type="text" class="tkt-input" id="tkt-bulk-extra" maxlength="50" placeholder="Ej: urgente-cliente"></div>';
            },
        },
        change_status: {
            icon: 'fa-solid fa-arrow-right-arrow-left', title: 'Cambiar estado', field: 'status_id', confirmLabel: 'Cambiar',
            emptyError: 'Selecciona un estado',
            body: function () {
                return '<div class="tkt-field"><label class="tkt-label">Estado</label><select id="tkt-bulk-extra" class="tkt-select"><option value="">Selecciona un estado…</option>' + optionsHtml(TKA.state.statuses, 'id', '') + '</select></div>';
            },
        },
    };

    function openBulkExtraModal(action) {
        var ids = Object.keys(TKA.state.bulk).map(Number);
        var config = BULK_EXTRA_CONFIG[action];
        if (!ids.length || !config) return;

        var $modal = openModal(modalShell({
            icon: config.icon,
            kicker: 'Tickets · selección',
            titleChip: ids.length + (ids.length === 1 ? ' ticket' : ' tickets'),
            title: config.title,
            body: config.body(),
            foot: '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-bulk-extra-confirm">' + config.confirmLabel + '</button>' +
                  '<button type="button" class="tkt-btn" data-modal-close>Cancelar</button>',
        }));

        $modal.on('click', '#tkt-bulk-extra-confirm', function () {
            var value = ($('#tkt-bulk-extra').val() || '').toString().trim();
            if (!value) {
                if (window.toastr) toastr.error(config.emptyError); else window.alert(config.emptyError);
                return;
            }
            closeModal();
            var extra = {};
            extra[config.field] = value;
            runBulkAction(action, extra);
        });
    }

    // extra es opcional: las acciones sin valor adicional (cerrar/resolver/
    // reabrir/eliminar) no lo necesitan; las que sí (assign/add_tag/
    // change_status/assign_group) siempre lo pasan ya resuelto desde su
    // modal — ver openBulkExtraModal/openBulkMoveTeamModal.
    //
    // Sin confirm() aquí dentro a propósito — bug real de UX encontrado en
    // QA: al venir de un modal (assign/add_tag/change_status/assign_group)
    // el usuario YA confirmó al pulsar "Asignar"/"Cambiar"/etc., así que un
    // window.confirm() adicional aquí era una doble confirmación con el
    // nombre interno de la acción en crudo ('¿Aplicar "assign" a...?'). La
    // única acción SIN modal previo (resolve/close/delete) confirma en su
    // propio punto de entrada, ver bindEvents().
    function runBulkAction(action, extra) {
        var ids = Object.keys(TKA.state.bulk).map(Number);
        if (!ids.length) return;
        extra = extra || {};

        $.ajax({
            url: TKA.urls.bulk,
            method: 'POST',
            data: $.extend({ ticket_ids: ids, action: action }, extra),
            success: function (resp) {
                if (window.toastr) toastr.success((resp && resp.message) || 'Acción completada');
                queueTicketListRefresh('bulk-' + action, TKA.state.currentTicket, {
                    freshCounts: true,
                    refreshDetail: true,
                    forceDetail: true,
                });
            },
            error: function (xhr) {
                var msg = (xhr.responseJSON && (xhr.responseJSON.message || (xhr.responseJSON.errors && Object.values(xhr.responseJSON.errors)[0][0]))) || 'No se pudo completar la acción';
                if (window.toastr) toastr.error(msg); else window.alert(msg);
            },
        });
    }

    // "Mover a equipo" es la única acción en bloque con un modal real en
    // vez de prompt() — el equipo (TKA.state.groups) ya se usa como
    // <select> en el panel Gestión, así que aquí también se elige de una
    // lista en vez de tener que escribir un ID a ciegas. Ejecuta la misma
    // acción/endpoint que el resto (runBulkAction → BulkTicketsController,
    // action: assign_group, ya soportado en el backend).
    function openBulkMoveTeamModal() {
        var ids = Object.keys(TKA.state.bulk).map(Number);
        if (!ids.length) return;

        var body =
            '<div class="tkt-field">' +
                '<label class="tkt-label">Equipo</label>' +
                '<select id="tkt-bulk-group" class="tkt-select"><option value="">Selecciona un equipo…</option>' + optionsHtml(TKA.state.groups, 'id', '') + '</select>' +
            '</div>';
        var foot =
            '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-bulk-group-confirm">Mover</button>' +
            '<button type="button" class="tkt-btn" data-modal-close>Cancelar</button>';

        var $modal = openModal(modalShell({
            icon: 'fa-solid fa-people-group',
            kicker: ids.length + (ids.length === 1 ? ' ticket seleccionado' : ' tickets seleccionados'),
            title: 'Mover a equipo',
            body: body,
            foot: foot,
        }));

        $modal.on('click', '#tkt-bulk-group-confirm', function () {
            var groupId = $('#tkt-bulk-group').val();
            if (!groupId) {
                if (window.toastr) toastr.error('Selecciona un equipo'); else window.alert('Selecciona un equipo');
                return;
            }
            closeModal();
            runBulkAction('assign_group', { group_id: groupId });
        });
    }

    /**
     * "Vincular a un ticket" del mockup (modal 13, ve-mail-bulk): mueve el
     * hilo COMPLETO de cada ticket seleccionado a uno elegido, igual que
     * mergeTicketPrompt() pero para varios orígenes a la vez. Reusa
     * bindTicketSearch(), el mismo buscador con autocompletado.
     */
    function openBulkLinkToTicketModal() {
        var ids = Object.keys(TKA.state.bulk).map(Number);
        if (!ids.length) return;

        var $modal = openModal(modalShell({
            icon: 'fa-solid fa-link',
            kicker: ids.length + (ids.length === 1 ? ' ticket seleccionado' : ' tickets seleccionados'),
            title: 'Vincular a un ticket',
            width: 'md',
            body: '<div class="tkt-field"><label class="tkt-label">Ticket destino<span class="req">*</span><span class="hint">busca por número o asunto</span></label>' +
                    '<input type="text" class="tkt-input" id="tkt-bulk-link-target" placeholder="Nº de ticket, asunto o ID…">' +
                    '<div class="tkt-pick-list sm" id="tkt-bulk-link-results"></div></div>' +
                '<div class="tkt-note danger">Se moverá el hilo completo (mensajes, correos, adjuntos, notas, comentarios, tiempos, seguidores y enlaces) de cada ticket seleccionado al destino. Los orígenes se cierran y se borran. No se puede deshacer.</div>' +
                '<label class="tkt-check"><input type="checkbox" id="tkt-bulk-link-ack"> Entiendo que esta acción no se puede deshacer</label>',
            foot: '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-bulk-link-confirm" disabled>Vincular</button>' +
                  '<button type="button" class="tkt-btn" data-modal-close>Cancelar</button>',
        }));

        var targetId = null;
        bindTicketSearch($modal, {
            input: '#tkt-bulk-link-target',
            results: '#tkt-bulk-link-results',
            onPick: function (id) { targetId = id; },
        });

        $modal.on('change', '#tkt-bulk-link-ack', function () {
            $('#tkt-bulk-link-confirm').prop('disabled', !this.checked);
        });

        $modal.on('click', '#tkt-bulk-link-confirm', function () {
            if (!targetId) {
                if (window.toastr) toastr.error('Elige un ticket destino de la lista'); else window.alert('Elige un ticket destino de la lista');
                return;
            }
            closeModal();
            runBulkAction('link_to_ticket', { merge_into_id: targetId });
        });
    }

    // Aplica el modo Lista/Kanban al DOM (botón activo, columnas visibles,
    // render del tablero) sin tocar la URL — usado tanto por bindEvents()
    // (clic del usuario, que sí persiste vía history.replaceState) como por
    // initTicketsApp() (aplicar el modo inicial ya reflejado en ?view=/
    // data-initial-view, donde reescribir la URL sería un no-op).
    function applyViewMode(mode) {
        $('#tkt-mode-switch [data-mode]').removeClass('on');
        $('#tkt-mode-switch [data-mode="' + mode + '"]').addClass('on');
        $('#tkt-split-wrap').toggle(mode === 'list');
        $('#tkt-kanban').toggleClass('on', mode === 'kanban');
        if (mode === 'kanban') renderKanban();
    }

    // ═══════════ Eventos ═══════════
    function bindEvents() {
        // Pestañas de estado y chips de vista. Cambiar de pestaña ya no criba
        // en cliente los 50 tickets de la página (que era el motivo de que el
        // badge dijera 340 y la lista mostrara 6): se le pide al servidor la
        // primera página de ESA pestaña.
        $('.tkt-state-tab[data-filter], .tkt-view-pill[data-filter]').on('click', function () {
            var filter = String($(this).data('filter'));
            if (filter === TKA.state.filter) return;

            TKA.state.filter = filter;
            renderTabs();
            refetchList(currentListParams({ quick_filter: filter === 'all' ? null : filter }));
        });

        bindKeyboardShortcuts();

        $('#tkt-mode-switch [data-mode]').on('click', function () {
            applyViewMode($(this).data('mode'));
            // Persiste el modo en la URL (?view=) — mismo patrón de
            // deep-link que ya usa selectTicket() para ?ticket=; el blade ya
            // leía request('view','list') en data-initial-view (initTicketsApp()
            // lo consume abajo), solo faltaba que el JS lo escribiera de
            // vuelta al cambiar de modo. Antes recargar la página siempre
            // volvía a Lista aunque se hubiera cambiado a Kanban.
            if (window.history && window.history.replaceState) {
                var params = new URLSearchParams(window.location.search);
                var mode = $(this).data('mode');
                if (mode === 'kanban') params.set('view', mode); else params.delete('view');
                var qs = params.toString();
                window.history.replaceState(null, '', window.location.pathname + (qs ? '?' + qs : ''));
            }
        });

        // "Plantillas" de la barra superior: en el mockup abre el modal 44
        // (crear desde plantilla), no navega a la pantalla de gestión — esa
        // sigue accesible desde el propio modal.
        $('#tkt-templates-open').on('click', function (e) { e.preventDefault(); openTicketTemplatesModal(); });

        $('#tkt-sync').on('click', function () { syncCurrentCustomer($(this)); });
        $('#tkt-export-open').on('click', openExportModal);

        // Cabecera de la lista: el orden se elige en un <select> (mockup) en
        // vez de alternar un único criterio con un enlace. El servidor ya
        // pagina y ordena, así que se recarga con ?sort= en vez de reordenar
        // en cliente, que solo afectaría a la página visible.
        $('#tkt-sort').on('change', function () {
            refetchList(currentListParams({ sort: this.value }));
        });
        $('#tkt-list-density').on('click', function () {
            var compact = TKA.state.listDensity !== 'compact';
            applyListDensity(compact);
            writePreference(TKT_LIST_DENSITY_KEY, compact ? 'compact' : 'normal');
        });
        $('#tkt-undo-btn').on('click', function () {
            var action = TKA.state.undoAction;
            hideUndo();
            if (action && typeof action.callback === 'function') action.callback();
        });
        $('#tkt-list-refresh').on('click', function () { window.location.reload(); });
        $('#tkt-list-export').on('click', openExportModal);
        $('#tkt-list-trash').on('click', function () {
            var ids = Object.keys(TKA.state.bulk);
            if (!ids.length) {
                if (window.toastr) toastr.info('Selecciona antes los tickets que quieres enviar a la papelera');
                return;
            }
            runBulkAction('delete');
        });
        bindStaticModal('#tkt-filters-modal-open', '#tkt-filters-modal-backdrop', '#tkt-filters-modal-close');

        // "Guardar vista" del modal de filtros: guarda lo escrito en el
        // formulario, no lo que hay en la URL (todavía sin aplicar).
        $('#htk-f-save-view').on('click', function () {
            var values = {};
            $('#htk-filters-form').serializeArray().forEach(function (f) {
                if (f.value !== '') values[f.name] = f.value;
            });
            $('#tkt-filters-modal-backdrop').removeClass('on');
            saveCurrentView(values);
        });

        // Nota: los campos vacíos ("source=", "tag="…) los descarta
        // currentListParams() al construir la URL del refetch, así que ya no
        // hace falta deshabilitarlos antes de enviar. Si el refetch falla y se
        // cae a la navegación clásica, se navega a esa MISMA URL ya limpia.

        // Chips de filtro: al ser select2 ya no llevan onchange inline en el
        // Blade (select2 oculta el <select> original), así que el submit del
        // formulario se engancha aquí. Delegado sobre el formulario para que
        // siga funcionando si algún chip se repinta.
        $('#tkt-filter-form').on('change', '.tkt-fsel', function () {
            this.form.requestSubmit();
        });

        // Los dos formularios de filtro (barra y modal "Más filtros") dejan de
        // navegar: se resuelven por refetch. requestSubmit() de los chips y del
        // daterangepicker pasa por aquí, así que no hay dos caminos distintos.
        $('#tkt-filter-form, #htk-filters-form').on('submit', function (e) {
            e.preventDefault();
            var $form = $(this);
            refetchList(currentListParams({ $form: $form }), {
                onDone: function () { $('#tkt-filters-modal-backdrop').removeClass('on'); },
            });
        });

        // Paginación: las dos flechas del pie llevan la URL ya calculada por el
        // paginador (con todos los filtros dentro), así que basta con pedirla.
        $(document).on('click', '#tkt-foot-nav a', function (e) {
            e.preventDefault();
            var page = new URL(this.href, window.location.origin).searchParams.get('page');
            refetchList(currentListParams({ page: page || 1 }));
        });

        // Volver/avanzar del navegador tras un pushState del refetch: sin esto
        // la URL cambiaba pero la lista se quedaba como estaba.
        $(window).on('popstate', function () {
            window.location.reload();
        });

        listenForNewTickets();
        startTicketListRefresh();

        // Rango de fechas — el chip de la barra y el campo "Creado entre" del
        // modal "Más filtros". Los dos usaban <input type="date">, cuyo
        // calendario lo pinta el navegador con su propia tipografía y su azul
        // de sistema, sin forma de tematizarlo; ahora los dos abren el
        // daterangepicker que el layout del tema ya carga (jQuery + moment +
        // daterangepicker, los mismos de las otras bandejas). En ambos casos
        // el par de <input type="hidden"> del Blade es lo que viaja en el
        // formulario, así que el backend no se entera del cambio.
        //
        // $trigger es un <button> con data-from/data-to (valores actuales) y,
        // opcionalmente, data-from-input/data-to-input (selectores de los
        // hidden; por defecto los del chip de la barra). onApply decide qué
        // hacer después: la barra envía el formulario en el acto, el modal
        // espera a que se pulse "Aplicar filtros".
        function bindDateRange($trigger, opts) {
            if (! $trigger.length || ! $.fn.daterangepicker || typeof moment === 'undefined') return;

            opts = opts || {};
            var from = $trigger.data('from');
            var to = $trigger.data('to');
            var $fromInput = $($trigger.data('from-input') || '#tkt-created-from');
            var $toInput = $($trigger.data('to-input') || '#tkt-created-to');
            // Dentro de un modal el panel tiene que colgar del backdrop: si se
            // queda en <body> lo tapa el propio modal (z-index 9999), el mismo
            // gotcha ya documentado para el dropdown de select2.
            var $backdrop = $trigger.closest('.tkt-modal-backdrop');

            $trigger.daterangepicker({
                parentEl: $backdrop.length ? $backdrop : $(document.body),
                // autoUpdateInput solo aplica a <input>; aquí el elemento es un
                // <button>, así que el texto lo repinta onApply (y el servidor
                // otra vez al recargar, ya con el formato largo "19 ago – 01 sep").
                autoUpdateInput: false,
                opens: 'right',
                alwaysShowCalendars: true,
                startDate: from ? moment(from, 'YYYY-MM-DD') : moment().subtract(29, 'days'),
                endDate: to ? moment(to, 'YYYY-MM-DD') : moment(),
                locale: {
                    applyLabel: 'Aplicar', cancelLabel: 'Limpiar', customRangeLabel: 'Personalizado',
                    format: 'DD/MM/YYYY', separator: ' – ',
                    daysOfWeek: ['Do', 'Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sa'],
                    monthNames: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio',
                                 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'],
                    firstDay: 1,
                },
                ranges: {
                    'Hoy': [moment(), moment()],
                    'Ayer': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                    'Últimos 7 días': [moment().subtract(6, 'days'), moment()],
                    'Últimos 30 días': [moment().subtract(29, 'days'), moment()],
                    'Este mes': [moment().startOf('month'), moment().endOf('month')],
                    'Mes anterior': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
                },
            });

            // El panel se monta fuera del .tkt bajo el que está scopeado todo el
            // CSS de la pantalla (mismo gotcha que el dropdown de select2):
            // esta clase propia es lo que engancha el skin de tickets-app.css.
            var drp = $trigger.data('daterangepicker');
            if (drp && drp.container) drp.container.addClass('tkt-drp');

            $trigger.on('show.daterangepicker', function () { $trigger.attr('aria-expanded', 'true'); });
            $trigger.on('hide.daterangepicker', function () { $trigger.attr('aria-expanded', 'false'); });

            $trigger.on('apply.daterangepicker', function (ev, picker) {
                $fromInput.val(picker.startDate.format('YYYY-MM-DD'));
                $toInput.val(picker.endDate.format('YYYY-MM-DD'));
                if (opts.onApply) opts.onApply(picker, this);
            });

            // "Limpiar" quita el rango. Los hidden vacíos los descarta el
            // handler de submit de arriba, así que la URL queda sin
            // created_from/created_to en vez de con dos parámetros vacíos.
            $trigger.on('cancel.daterangepicker', function () {
                $fromInput.val('');
                $toInput.val('');
                if (opts.onCancel) opts.onCancel(this);
            });
        }

        // Chip de la barra: elegir el rango filtra en el acto, como el resto
        // de chips.
        bindDateRange($('#tkt-daterange-open'), {
            onApply: function (picker, el) {
                $(el).find('.tkt-fchip-value').text(
                    picker.startDate.format('DD/MM/YYYY') + ' – ' + picker.endDate.format('DD/MM/YYYY')
                );
                el.form.requestSubmit();
            },
            onCancel: function (el) { el.form.requestSubmit(); },
        });

        // Campo "Creado entre" del modal: aquí NO se envía nada todavía — el
        // modal tiene su propio botón "Aplicar filtros" y el agente puede
        // seguir tocando otros campos. Solo se repinta el texto del campo.
        bindDateRange($('#htk-f-created-range'), {
            onApply: function (picker, el) {
                $(el).find('.tkt-daterange-text')
                    .addClass('on')
                    .text(picker.startDate.format('DD/MM/YYYY') + ' – ' + picker.endDate.format('DD/MM/YYYY'));
            },
            onCancel: function (el) {
                $(el).find('.tkt-daterange-text').removeClass('on').text('Cualquier fecha');
            },
        });

        $('#tkt-save-view').on('click', saveCurrentView);

        // Riel de operación. Estos siete modales se referenciaban por su
        // NOMBRE y se resolvían con eval() dentro del closure, porque cuando
        // se escribió el riel varias de las funciones todavía no existían y
        // un ReferenceError en la primera cortaba los 120 renglones
        // siguientes del arranque ("seleccionar todo", acciones masivas,
        // atajos). Ya están todas escritas, así que se enganchan
        // directamente: sin eval (que además cae con una CSP estricta) y con
        // el fallo visible en el sitio si alguna se renombra.
        [
            ['#tkt-pill-queue', openQueueModal],
            ['#tkt-pill-mailboxes', openMailboxesModal],
            ['#tkt-pill-escalation', openEscalationModal],
            ['#tkt-pill-recurring', openRecurringModal],
            ['#tkt-pill-workload', openWorkloadModal],
            // En el mockup "Entregabilidad" es data-open="reputation": un
            // único modal con la autenticación del dominio Y las tasas.
            ['#tkt-pill-deliverability', openReputationModal],
            ['#tkt-pill-notices', openNotificationsModal],
        ].forEach(function (pair) {
            $(pair[0]).on('click', pair[1]);
        });

        $('#tkt-side-rail [data-side]').on('click', function () {
            selectSideTab($(this).data('side'));
        });

        $('#tkt-mobile-nav [data-mobile-pane]').on('click', function () {
            openMobilePane($(this).data('mobile-pane'));
        });

        $('#tkt-select-all').on('change', function () {
            var checked = this.checked;
            visibleTickets().forEach(function (t) {
                if (checked) TKA.state.bulk[t.id] = true; else delete TKA.state.bulk[t.id];
            });
            $('.tkt-ticket-check').prop('checked', checked);
            renderBulkBar();
        });

        $('#tkt-bulk-clear').on('click', function () {
            TKA.state.bulk = {};
            $('.tkt-ticket-check').prop('checked', false);
            $('#tkt-select-all').prop('checked', false);
            renderBulkBar();
        });

        // "Exportar" de la barra de selección: el modal ya preselecciona el
        // alcance "Selección" cuando hay tickets marcados.
        $('#tkt-bulk-export').on('click', openExportModal);

        var BULK_DIRECT_LABELS = {
            resolve: { title: 'Marcar como resuelto', message: '¿Marcar como resuelto?', confirmLabel: 'Resolver', danger: false },
            close: { title: 'Cerrar tickets', message: '¿Cerrar los tickets seleccionados?', confirmLabel: 'Cerrar', danger: false },
            delete: { title: 'Eliminar tickets', message: 'Esta acción no se puede deshacer.', confirmLabel: 'Eliminar', danger: true },
            // "Reintentar envío (solo fallidos)" del mockup: no hace falta
            // valor adicional (a diferencia de assign/add_tag/change_status),
            // así que entra por el mismo camino directo que resolver/cerrar,
            // no por BULK_EXTRA_CONFIG. Los tickets sin correo saliente
            // fallido simplemente no cuentan (ver BulkTicketsController).
            retry_failed_mail: { title: 'Reintentar envío', message: 'Solo se reintentan los correos de salida marcados como fallidos.', confirmLabel: 'Reintentar', danger: false },
        };
        $('[data-bulk-action]').on('click', function () {
            var action = $(this).data('bulk-action');
            if (BULK_EXTRA_CONFIG[action]) { openBulkExtraModal(action); return; }

            var ids = Object.keys(TKA.state.bulk).map(Number);
            var l = BULK_DIRECT_LABELS[action] || { title: 'Confirmar acción', message: '¿Aplicar esta acción?', confirmLabel: 'Confirmar', danger: false };
            openConfirmModal({
                icon: l.danger ? 'fa-solid fa-trash' : 'fa-solid fa-check',
                title: l.title,
                message: ids.length + (ids.length === 1 ? ' ticket seleccionado. ' : ' tickets seleccionados. ') + l.message,
                confirmLabel: l.confirmLabel,
                danger: l.danger,
                onConfirm: function () { runBulkAction(action); },
            });
        });

        $('#tkt-bulk-move-team').on('click', openBulkMoveTeamModal);
        $('#tkt-bulk-link-ticket').on('click', openBulkLinkToTicketModal);

        $('#tkt-search').on('keydown', function (ev) {
            if (ev.key !== 'Enter') return;
            var params = new URLSearchParams(window.location.search);
            var val = $(this).val();
            if (val) params.set('search', val); else params.delete('search');
            window.location = TKA.urls.index + '?' + params.toString();
        });
    }

    // Modal de ayuda: SOLO los atajos que bindKeyboardShortcuts() de verdad
    // engancha más abajo — nada aspiracional. "Enviar mensaje" y "Nueva
    // línea" son la excepción: viven en el keydown propio de #tkt-reply-body
    // (ver bindEvents()), no en este atajo global, pero son igual de reales.
    function openShortcutsModal() {
        var cols = [
            ['Navegación', [
                ['Ticket siguiente', ['J']],
                ['Ticket anterior', ['K']],
                ['Vista previa rápida', ['Espacio']],
                ['Nuevo ticket', ['C']],
                ['Buscar en el sistema', ['⌘', 'K']],
                ['Paleta de acciones', ['⌘', '⇧', 'P']],
                ['Mostrar atajos', ['?']],
            ]],
            ['Ticket abierto', [
                ['Responder (foco)', ['R']],
                ['Nota interna (foco)', ['N']],
                ['Asignar agente', ['A']],
                ['Cambiar estado', ['S']],
            ]],
            ['Composer', [
                ['Enviar mensaje', ['⌘', '⏎']],
                ['Nueva línea', ['⏎']],
                ['Mencionar agente', ['@']],
            ]],
            ['Barra de estado', [
                ['Alternar sonido', ['click', 'icono']],
                ['Cerrar este diálogo', ['Esc']],
            ]],
        ];

        var body = '<div class="tkt-cheat-grid">' + cols.map(function (col) {
            var title = col[0];
            var rows = col[1];

            return '<div>' +
                '<div class="tkt-cheat-h">' + escapeHtml(title) + '</div>' +
                rows.map(function (row) {
                    var label = row[0];
                    var keys = row[1];

                    return '<div class="tkt-cheat-row">' +
                        '<span class="tkt-cheat-lbl">' + escapeHtml(label) + '</span>' +
                        '<span class="tkt-cheat-keys">' +
                            keys.map(function (k) { return '<kbd class="tkt-kbd">' + escapeHtml(k) + '</kbd>'; }).join('') +
                        '</span>' +
                    '</div>';
                }).join('') +
            '</div>';
        }).join('') + '</div>';

        openModal(modalShell({
            icon: 'fa-solid fa-keyboard', kicker: 'Ayuda',
            title: 'Atajos de teclado', width: '2xl',
            body: body,
            foot: '<button type="button" class="tkt-btn" data-modal-close>Cerrar</button>',
        }));
    }

    function openActionPalette(t) {
        if (!t) return;
        var actions = [
            ['reply', 'Responder al cliente', 'Escribir una respuesta pública', 'fa-reply'],
            ['note', 'Añadir nota interna', 'Registrar información para el equipo', 'fa-lock'],
            ['status', 'Cambiar estado', 'Mover el ticket a otro estado', 'fa-arrow-right-arrow-left'],
            ['assign', 'Asignar agente', 'Reasignar la atención del ticket', 'fa-user-plus'],
            ['manage', 'Abrir gestión', 'Mostrar estado, prioridad y equipo', 'fa-sliders'],
            ['customer', 'Abrir resumen del cliente', 'Ver datos e histórico del contacto', 'fa-address-card'],
            ['snooze', 'Aplazar seguimiento', 'Programar cuándo volver a verlo', 'fa-clock'],
            ['schedule', 'Programar respuesta', 'Enviar el mensaje en otra fecha', 'fa-calendar'],
            ['portal', 'Ver como el cliente', 'Abrir la vista pública de solo lectura', 'fa-door-open'],
            ['split', 'Dividir ticket', 'Crear una conversación separada', 'fa-code-branch'],
            ['close', 'Cerrar ticket', 'Registrar el motivo y cerrar', 'fa-lock'],
        ];
        var listHtml = actions.map(function (a) {
            return '<button type="button" class="tkt-palette-item" data-palette-action="' + a[0] + '" data-palette-search="' + escapeHtml((a[1] + ' ' + a[2]).toLowerCase()) + '">' +
                '<span class="tkt-palette-icon"><i class="fa-solid ' + a[3] + '"></i></span>' +
                '<span><b>' + escapeHtml(a[1]) + '</b><small>' + escapeHtml(a[2]) + '</small></span>' +
            '</button>';
        }).join('');
        var $modal = openModal(modalShell({
            icon: 'fa-solid fa-command', kicker: 'Acciones rápidas',
            title: 'Paleta de acciones', titleChip: t.ticket_number, width: 'lg',
            body: '<input type="search" class="tkt-input tkt-palette-search" id="tkt-palette-search" placeholder="Buscar una acción…" aria-label="Buscar una acción" autocomplete="off">' +
                '<div class="tkt-palette-list" id="tkt-palette-list">' + listHtml + '</div>',
            foot: '<span class="tkt-palette-hint"><kbd class="tkt-kbd">Esc</kbd> cerrar</span><button type="button" class="tkt-btn" data-modal-close>Cerrar</button>',
        }));

        $modal.on('input', '#tkt-palette-search', function () {
            var query = String($(this).val() || '').toLowerCase().trim();
            $modal.find('[data-palette-action]').each(function () {
                $(this).toggle(!query || String($(this).data('palette-search')).indexOf(query) !== -1);
            });
        });
        $modal.on('click', '[data-palette-action]', function () {
            var action = $(this).data('palette-action');
            closeModal();
            if (action === 'reply' || action === 'note') {
                selectDetailTab('thread');
                $('[data-comp-mode="' + action + '"]').trigger('click');
                if (window.matchMedia && window.matchMedia('(max-width: 1180px)').matches) openMobilePane('detail');
                $('#tkt-reply-body').trigger('focus');
            } else if (action === 'status') openChangeStatusModal(t);
            else if (action === 'assign') openAssignModal(t);
            else if (action === 'manage') {
                selectSideTab('gestion');
                if (window.matchMedia && window.matchMedia('(max-width: 1180px)').matches) openMobilePane('side');
                $('#tkt-sg-actions').addClass('tkt-highlight-flash').one('animationend', function () { $(this).removeClass('tkt-highlight-flash'); });
            } else if (action === 'customer') {
                selectSideTab('cliente');
                if (window.matchMedia && window.matchMedia('(max-width: 1180px)').matches) openMobilePane('side');
            } else if (action === 'snooze') snoozeTicket(t);
            else if (action === 'schedule') openScheduleModal(t);
            else if (action === 'portal') openPortalModal(t);
            else if (action === 'split') openSplitModal(t);
            else if (action === 'close') openCloseTicketModal(t);
        });
        setTimeout(function () { $('#tkt-palette-search').trigger('focus'); }, 0);
    }

    // ═══════════ Atajos de teclado (J/K navegar, C nuevo ticket) ═══════════
    // El mockup también documenta "⌘K" para el buscador, pero el tema base
    // YA usa ⌘K globalmente para el buscador del sistema (barra superior,
    // #gs-input) — bug real encontrado al probar: mi atajo local competía
    // con ese y a veces robaba el foco. Se deja el ⌘K como está (el global),
    // sin duplicarlo aquí; el chip visual junto al buscador local queda solo
    // como texto informativo del propio input, no como atajo real distinto.
    // Bug real probando los atajos uno a uno (J/K/C no comprobaban NINGÚN
    // overlay): con el modal "Asignar ticket" abierto para el TCK-...-113 y
    // el foco fuera de su buscador, "j" cambiaba el detalle de fondo al
    // TCK-...-112 dejando el modal abierto — si el agente completaba la
    // asignación ahí, se la aplicaba al ticket equivocado, uno que ni
    // siquiera veía ya en pantalla. "c" hacía lo mismo pero reemplazando el
    // modal por "Nuevo ticket" en silencio. Mismo problema con el buscador
    // global del header (#gs-dialog, fuera de este módulo): "j"/"k" también
    // cambiaban el ticket de fondo mientras el buscador seguía abierto.
    function anyOverlayOpen() {
        if ($('#tkt-modal-backdrop').length) return true;
        var gs = document.getElementById('gs-dialog');
        return !!(gs && gs.classList.contains('open'));
    }

    function bindKeyboardShortcuts() {
        $(document).on('keydown', function (ev) {
            var tag = (ev.target.tagName || '').toLowerCase();
            var typing = tag === 'input' || tag === 'textarea' || tag === 'select' || ev.target.isContentEditable;

            if ((ev.metaKey || ev.ctrlKey) && ev.shiftKey && (ev.key === 'p' || ev.key === 'P')) {
                if (anyOverlayOpen()) return;
                var paletteTicket = TKA.state.currentTicket;
                if (!paletteTicket) return;
                ev.preventDefault();
                openActionPalette(paletteTicket);
                return;
            }
            if (typing) return;
            // Bug real probando los atajos uno a uno: sin este corte, ⌘K (el
            // buscador global del header, ver header.blade.php) también
            // llegaba aquí como una simple "k" y disparaba "ticket anterior"
            // — cada ⌘K navegaba a otro ticket además de abrir el buscador.
            // Ninguno de estos atajos lleva modificador, así que cualquiera
            // presente significa que es OTRO atajo (del navegador, del SO, o
            // el global de arriba) y no de esta pantalla.
            if (ev.metaKey || ev.ctrlKey || ev.altKey) return;

            if (ev.key === 'j' || ev.key === 'J') {
                if (anyOverlayOpen()) return;
                ev.preventDefault();
                moveSelection(1);
            } else if (ev.key === 'k' || ev.key === 'K') {
                if (anyOverlayOpen()) return;
                ev.preventDefault();
                moveSelection(-1);
            } else if (ev.key === 'c' || ev.key === 'C') {
                if (anyOverlayOpen()) return;
                openNewTicketModal();
            } else if (ev.key === ' ') {
                // Vista previa rápida (modal 12) sobre la fila seleccionada.
                // Solo con la lista enfocada: dentro de un modal el espacio
                // tiene que seguir activando el botón que tenga el foco.
                if (anyOverlayOpen()) return;
                var current = TKA.state.tickets.find(function (x) { return String(x.id) === String(TKA.state.selected); });
                if (!current) return;
                ev.preventDefault();
                openQuickPreviewModal(current);
            } else if (ev.key === '?') {
                // Ayuda: funciona haya o no un ticket abierto, y aunque haya
                // otro modal PROPIO encima (mismo criterio que el propio
                // botón ? de la barra de estado) — pero no si el buscador
                // global ya está abierto, para no apilar dos diálogos que
                // openModal()/closeModal() no controlan.
                var gsForHelp = document.getElementById('gs-dialog');
                if (gsForHelp && gsForHelp.classList.contains('open')) return;
                ev.preventDefault();
                openShortcutsModal();
            } else if (['r', 'R', 'n', 'N', 'a', 'A', 's', 'S'].indexOf(ev.key) !== -1) {
                // Estos cuatro actúan sobre el ticket ABIERTO en el detalle,
                // no sobre la fila seleccionada en la lista (que puede no
                // tener nada abierto todavía) — y no si ya hay un modal
                // encima, para no abrir uno segundo sin que se note cuál.
                var t = TKA.state.currentTicket;
                if (!t || anyOverlayOpen()) return;
                ev.preventDefault();

                if (ev.key === 'r' || ev.key === 'R') {
                    $('[data-comp-mode="reply"]').trigger('click');
                    $('#tkt-reply-body').trigger('focus');
                } else if (ev.key === 'n' || ev.key === 'N') {
                    $('[data-comp-mode="note"]').trigger('click');
                    $('#tkt-reply-body').trigger('focus');
                } else if (ev.key === 'a' || ev.key === 'A') {
                    openAssignModal(t);
                } else if (ev.key === 's' || ev.key === 'S') {
                    openChangeStatusModal(t);
                }
            }
        });
    }

    function moveSelection(delta) {
        var rows = visibleTickets();
        if (!rows.length) return;
        var idx = rows.findIndex(function (t) { return String(t.id) === String(TKA.state.selected); });
        var next = rows[Math.min(Math.max(idx + delta, 0), rows.length - 1)] || rows[0];
        // Si la vista previa rápida está abierta, J/K la repintan con el
        // ticket nuevo en vez de cerrarla — es lo que promete su propio copy.
        var previewOpen = $('#tkt-modal-backdrop #tkt-qp-open').length > 0;
        selectTicket(next);
        var $row = $('.tkt-ticket-row[data-id="' + next.id + '"]');
        if ($row.length) $row[0].scrollIntoView({ block: 'nearest' });
        if (previewOpen) {
            closeModal();
            openQuickPreviewModal(next);
        }
    }

    $(document).ready(initTicketsApp);
    /**
     * Reintento manual desde el aviso de la tarjeta Cliente. Delegado en
     * document porque el panel lateral se repinta entero al cambiar de ticket.
     */
    $(document).on('click', '[data-tkt-erp-relink]', function () {
        var $btn = $(this);
        var url = $btn.closest('.tkt-erp-missing').data('relink-url');

        if (!url || $btn.prop('disabled')) { return; }

        $btn.prop('disabled', true).text('Buscando…');

        $.ajax({
            url: url,
            method: 'POST',
            timeout: 15000,
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || ''
            }
        }).done(function (resp) {
            if (window.toastr) {
                window.toastr.success((resp && resp.message) || 'Buscando el cliente en gestión…');
            }

            // Asíncrono: el resultado llega cuando el trabajo sale de la cola
            // helpdesk-erp, así que aquí solo se confirma el envío.
            $btn.text('Enviado');
        }).fail(function (xhr, textStatus) {
            var msg;

            if (textStatus === 'timeout') {
                msg = 'La petición tardó demasiado.';
            } else if (xhr && xhr.status === 429) {
                msg = 'Demasiados reintentos seguidos, espera un minuto.';
            } else {
                msg = apiErrorMessage(xhr, 'No se pudo pedir la búsqueda.');
            }

            if (window.toastr) { window.toastr.error(msg); }

            $btn.prop('disabled', false).text('Reintentar');
        });
    });
