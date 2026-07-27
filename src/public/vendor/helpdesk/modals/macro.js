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
(function ($) {
    'use strict';

    var _macroId = null;
    var _allMacros = [];

    function getConvId() {
        return $('.bv-composer').data('bv-conversation-id') || null;
    }

    function closeBvModal(name) {
        $('[data-bv-modal-name="' + name + '"]').removeClass('on');
        if ($('.bv-modal.on').length === 0) { $('body').css('overflow', ''); }
    }

    function esc(s) { return $('<span>').text(s == null ? '' : String(s)).html(); }

    function renderMacros(macros) {
        if (!macros.length) {
            $('#macroList').html('<div class="bv-cv-loading-msg"><i class="fas fa-inbox"></i> Sin macros disponibles</div>');
            return;
        }
        var html = macros.map(function (m) {
            var summary = m.actions_summary || '';
            return '<button type="button" class="reason" data-macro-id="' + m.id + '">' +
                '<div class="ic"><i class="fas fa-bolt"></i></div>' +
                '<div class="body">' +
                    '<span class="t">' + esc(m.name) + '</span>' +
                    (summary ? '<span class="s">' + esc(summary) + '</span>' : '') +
                '</div>' +
                '<div class="radio"></div>' +
                '</button>';
        }).join('');
        $('#macroList').html(html);
    }

    function resetSelection() {
        _macroId = null;
        $('#bv-macro-apply').prop('disabled', true);
        $('#macroInfo').addClass('bv-hidden');
        $('#macroPreview').addClass('bv-hidden');
        $('#bv-macro-details').addClass('bv-hidden');
    }

    function loadMacros() {
        $('#macroList').html('<div class="bv-cv-loading-msg"><i class="fas fa-spinner fa-spin"></i></div>');
        resetSelection();

        $.ajax({
            url: '/panel/helpdesk/conversations/macros-picker?sort=used',
            method: 'GET',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
        }).done(function (resp) {
            _allMacros = resp.data || resp.macros || resp || [];
            renderMacros(_allMacros);
        }).fail(function () {
            $('#macroList').html('<div class="bv-cv-loading-msg"><i class="fas fa-triangle-exclamation"></i> Error al cargar</div>');
        });
    }

    $(document).on('bv:modal:open', function (e, name) {
        if (name !== 'macro') { return; }
        $('#macroSearch').val('');
        loadMacros();
    });

    $(document).on('click', '#macroList .reason', function () {
        $('#macroList .reason').removeClass('on');
        $(this).addClass('on');
        _macroId = $(this).data('macro-id');
        $('#bv-macro-apply').prop('disabled', false);

        var macro = _allMacros.find(function (m) { return String(m.id) === String(_macroId); }) || {};
        var name = macro.name || 'seleccionada';
        var count = macro.actions_count || (macro.actions || []).length;
        var plural = count === 1 ? 'acción automática' : 'acciones automáticas';

        $('#macroInfoText').html('La macro <b>' + esc(name) + '</b> aplicará ' + count + ' ' + plural + '. Puedes revisar el detalle antes de ejecutar.');
        $('#macroInfo').removeClass('bv-hidden');

        var actions = macro.actions || [];
        if (actions.length) {
            var html = actions.map(function (a) {
                return '<div class="bv-macro-action"><i class="fas fa-arrow-right"></i> ' + esc(a.label || a) + '</div>';
            }).join('');
            $('#macroPreviewActions').html(html);
            $('#bv-macro-details').removeClass('bv-hidden').text('Ver acciones detalladas');
            $('#macroPreview').addClass('bv-hidden');
        } else {
            $('#bv-macro-details').addClass('bv-hidden');
            $('#macroPreview').addClass('bv-hidden');
        }
    });

    $(document).on('click', '#bv-macro-details', function () {
        var $prev = $('#macroPreview');
        var willShow = $prev.hasClass('bv-hidden');
        $prev.toggleClass('bv-hidden');
        $(this).text(willShow ? 'Ocultar acciones' : 'Ver acciones detalladas');
    });

    $(document).on('input', '#macroSearch', function () {
        var q = $(this).val().toLowerCase();
        if (!q) { renderMacros(_allMacros); return; }
        renderMacros(_allMacros.filter(function (m) {
            return m.name.toLowerCase().indexOf(q) !== -1;
        }));
    });

    $(document).on('click', '#bv-macro-apply', function () {
        if (!_macroId) { return; }
        var convId = getConvId();
        if (!convId) {
            if (window.toastr) { toastr.warning('Sin conversación activa'); }
            return;
        }
        var $btn = $(this).prop('disabled', true);
        $.ajax({
            url: '/panel/helpdesk/conversations/' + convId + '/macros/' + _macroId,
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), 'Accept': 'application/json' }
        }).done(function () {
            closeBvModal('macro');
            if (window.toastr) { toastr.success('Macro aplicado correctamente'); }
        }).fail(function (xhr) {
            var msg = (xhr && xhr.responseJSON && xhr.responseJSON.message) || 'Error al aplicar macro';
            if (window.toastr) { toastr.error(msg); }
        }).always(function () { $btn.prop('disabled', false); });
    });

}(window.jQuery));
