/*!
 * Helpdesk · modal "{name}" del inbox.
 *
 * Extraido de resources/views/helpdesk/inbox/partials/modals/{name}.blade.php,
 * donde vivia inline y se re-descargaba en cada carga del inbox. Sin
 * interpolacion Blade: la config llega por atributos data-* del markup.
 *
 * Convencion del modulo core: su JS se sirve desde public/vendor/helpdesk/ y no
 * tiene copia fuente aparte (igual que conversations.js y kb-suggestions.js).
 */
(function() {
    var SEL = '[data-bv-modal-name="filter"]';

    // El rango de fecha no cuenta como "filtro activo" del usuario
    function countActive() {
        return $(SEL + ' .fl-pill.on:not([data-key="date"])').length;
    }

    function updateCount() {
        var n = countActive();
        var txt = n === 0
            ? 'Sin filtros activos'
            : n + ' filtro' + (n !== 1 ? 's' : '') + ' activo' + (n !== 1 ? 's' : '');
        $('#flActiveCount').text(txt);
    }

    // El rango Desde/Hasta solo se muestra cuando la fecha es "Personalizado"
    function syncDateRange() {
        var isCustom = $(SEL + ' .fl-pill[data-key="date"].on').data('val') === 'custom';
        $(SEL + ' .fl-range').toggleClass('on', !!isCustom);
    }

    // Chips normales: selección múltiple
    $(document).on('click', SEL + ' .fl-pill:not([data-key="date"])', function() {
        $(this).toggleClass('on');
        updateCount();
    });

    // Chips de fecha: selección única + rango solo en "Personalizado"
    $(document).on('click', SEL + ' .fl-pill[data-key="date"]', function() {
        $(SEL + ' .fl-pill[data-key="date"]').removeClass('on');
        $(this).addClass('on');
        syncDateRange();
        if ($(this).data('val') === 'custom') {
            var range = $(SEL + ' .fl-range')[0];
            if (range) {
                range.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
            }
        }
    });

    $(document).on('click', '#flBtnClear', function() {
        $(SEL + ' .fl-pill').removeClass('on');
        $(SEL + ' .fl-range').removeClass('on');
        updateCount();
    });

    $(document).on('click', '.fl-saved', function() {
        var preset = $(this).data('preset');
        $(SEL + ' .fl-pill:not([data-key="date"])').removeClass('on');
        if (preset === 'urgent') {
            $(SEL + ' [data-val="urgent"][data-key="priority"],' + SEL + ' [data-val="unassigned"]').addClass('on');
        } else if (preset === 'unread') {
            $(SEL + ' [data-val="unassigned"]').addClass('on');
        }
        updateCount();
    });

    $(document).on('click', '#flBtnApply', function() {
        var params = {};

        $(SEL + ' .fl-pill.on').each(function() {
            var key = $(this).data('key');
            var val = $(this).data('val');
            if (!key || !val) return;
            if (params[key]) {
                params[key] += ',' + val;
            } else {
                params[key] = val;
            }
        });

        // Close modal
        $(SEL).removeClass('on');
        if ($('.bv-modal.on').length === 0) {
            $('body').css('overflow', '');
        }

        if (typeof applyInboxFilters === 'function') {
            applyInboxFilters(params);
        } else {
            $(document).trigger('bv:filter:apply', [params]);
        }
    });

    // Refleja los filtros de la URL en los chips al abrir el modal
    function syncFromUrl() {
        var u = new URL(window.location.href);
        $(SEL + ' .fl-pill').removeClass('on');
        ['channel', 'status', 'priority', 'tag', 'mine', 'unread', 'urgent', 'vip', 'assignee', 'date'].forEach(function(param) {
            var raw = u.searchParams.get(param);
            if (!raw) { return; }
            raw.split(',').forEach(function(val) {
                $(SEL + ' .fl-pill[data-key="' + param + '"][data-val="' + val + '"]').addClass('on');
            });
        });
        // Fecha por defecto (Hoy) si la URL no trae ninguna
        if (!$(SEL + ' .fl-pill[data-key="date"].on').length) {
            $(SEL + ' .fl-pill[data-key="date"][data-val="today"]').addClass('on');
        }
        syncDateRange();
        updateCount();
    }

    $(document).on('click', '[data-bv-modal="filter"]', function() {
        setTimeout(syncFromUrl, 0);
    });

    // Guardar vista: aplica la selección (actualiza la URL) y abre el modal de guardar vista
    $(document).on('click', '#flBtnSaveView', function(e) {
        e.preventDefault();
        $('#flBtnApply').trigger('click');
        $('#bv-save-view-btn').trigger('click');
    });

    // Estado inicial
    syncDateRange();
    updateCount();
}());
