{{-- Modal: Aplicar macro (#61 ve-apply-macro) --}}
<div class="bv-modal" data-bv-modal-name="macro">
    <div class="bv-modal-dialog md">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box"><i class="fas fa-wand-magic-sparkles"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">CHAT · AUTOMATIZACIÓN</span>
                <div class="bv-modal-title">Aplicar macro</div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            <div class="bv-modal-search">
                <i class="fas fa-magnifying-glass"></i>
                <input id="macroSearch" type="text" placeholder="Buscar macro…" autocomplete="off">
            </div>

            <div id="macroList" class="bv-opt-list" style="max-height:300px;overflow-y:auto">
                <div class="bv-cv-loading-msg"><i class="fas fa-spinner fa-spin"></i></div>
            </div>

            {{-- Preview de acciones del macro seleccionado --}}
            <div id="macroPreview" style="display:none">
                <div class="bv-form-label" style="margin-top:12px">Acciones que se ejecutarán</div>
                <div id="macroPreviewActions" class="bv-macro-actions"></div>
            </div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="bv-macro-apply" disabled>Aplicar macro</button>
            <button class="btn-secondary" data-bv-close>Cancelar</button>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
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

    function renderMacros(macros) {
        if (!macros.length) {
            $('#macroList').html('<div class="bv-cv-loading-msg"><i class="fas fa-inbox"></i> Sin macros disponibles</div>');
            return;
        }
        var html = macros.map(function (m) {
            var summary = m.actions_summary || '';
            return '<button class="bv-opt" data-macro-id="' + m.id + '" data-actions=\'' + JSON.stringify(m.actions || []) + '\'>' +
                '<div class="bv-opt__ic"><i class="fas fa-bolt"></i></div>' +
                '<div class="bv-opt__body">' +
                    '<span class="bv-opt__t">' + m.name + '</span>' +
                    (summary ? '<span class="bv-opt__s">' + summary + '</span>' : '') +
                '</div>' +
                '<div class="bv-opt__radio"></div>' +
                '</button>';
        }).join('');
        $('#macroList').html(html);
    }

    function loadMacros() {
        $('#macroList').html('<div class="bv-cv-loading-msg"><i class="fas fa-spinner fa-spin"></i></div>');
        $('#macroPreview').hide();
        _macroId = null;
        $('#bv-macro-apply').prop('disabled', true);

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

    $(document).on('click', '#macroList .bv-opt', function () {
        $('#macroList .bv-opt').removeClass('on');
        $(this).addClass('on');
        _macroId = $(this).data('macro-id');
        $('#bv-macro-apply').prop('disabled', false);

        var actions = $(this).data('actions') || [];
        if (actions.length) {
            var html = actions.map(function (a) {
                return '<div class="bv-macro-action"><i class="fas fa-arrow-right"></i> ' + (a.label || JSON.stringify(a)) + '</div>';
            }).join('');
            $('#macroPreviewActions').html(html);
            $('#macroPreview').show();
        } else {
            $('#macroPreview').hide();
        }
    });

    $(document).on('input', '#macroSearch', function () {
        var q = $(this).val().toLowerCase();
        if (!q) {
            renderMacros(_allMacros);
            return;
        }
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
            var msg = xhr?.responseJSON?.message || 'Error al aplicar macro';
            if (window.toastr) { toastr.error(msg); }
        }).always(function () { $btn.prop('disabled', false); });
    });

}(window.jQuery));
</script>
@endpush
@endonce
