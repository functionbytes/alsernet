{{-- Modal: Exportar contactos (#82 ve-export-contacts) --}}
<div class="bv-modal" data-bv-modal-name="export-contacts">
    <div class="bv-modal-dialog md">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box"><i class="fas fa-file-export"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">DATOS · EXPORTAR</span>
                <div class="bv-modal-title">Exportar contactos</div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            <div class="bv-form-field">
                <label class="bv-form-label">Qué exportar</label>
                <div class="bv-opt-list" id="exportContactsScope">
                    <button type="button" class="bv-opt on" data-scope="all">
                        <div class="bv-opt__ic"><i class="fas fa-users"></i></div>
                        <div class="bv-opt__body"><span class="bv-opt__t">Todos los contactos</span></div>
                        <div class="bv-opt__radio"></div>
                    </button>
                    <button type="button" class="bv-opt" data-scope="vip">
                        <div class="bv-opt__ic"><i class="fas fa-star"></i></div>
                        <div class="bv-opt__body"><span class="bv-opt__t">Solo VIP</span></div>
                        <div class="bv-opt__radio"></div>
                    </button>
                    <button type="button" class="bv-opt" data-scope="filtered">
                        <div class="bv-opt__ic"><i class="fas fa-filter"></i></div>
                        <div class="bv-opt__body"><span class="bv-opt__t">Aplicar filtros actuales</span></div>
                        <div class="bv-opt__radio"></div>
                    </button>
                    <button type="button" class="bv-opt" data-scope="selected">
                        <div class="bv-opt__ic"><i class="far fa-square-check"></i></div>
                        <div class="bv-opt__body"><span class="bv-opt__t">Solo seleccionados</span></div>
                        <div class="bv-opt__radio"></div>
                    </button>
                </div>
            </div>

            <div class="bv-form-field">
                <label class="bv-form-label">Formato</label>
                <div class="bv-export-opt-list" id="exportContactsFormat">
                    <button type="button" class="bv-export-opt on" data-fmt="csv">
                        <div class="bv-export-opt__ic"><i class="far fa-file-lines"></i></div>
                        <div class="bv-export-opt__body">
                            <span class="bv-export-opt__t">CSV</span>
                            <span class="bv-export-opt__s">Compatible con Excel, Sheets, etc.</span>
                        </div>
                        <span class="bv-export-opt__ext">.CSV</span>
                    </button>
                    <button type="button" class="bv-export-opt" data-fmt="xlsx">
                        <div class="bv-export-opt__ic"><i class="fas fa-table"></i></div>
                        <div class="bv-export-opt__body">
                            <span class="bv-export-opt__t">Excel</span>
                            <span class="bv-export-opt__s">Con formato y hojas separadas</span>
                        </div>
                        <span class="bv-export-opt__ext">.XLSX</span>
                    </button>
                    <button type="button" class="bv-export-opt" data-fmt="vcf">
                        <div class="bv-export-opt__ic"><i class="far fa-address-card"></i></div>
                        <div class="bv-export-opt__body">
                            <span class="bv-export-opt__t">vCard</span>
                            <span class="bv-export-opt__s">Para importar a la agenda</span>
                        </div>
                        <span class="bv-export-opt__ext">.VCF</span>
                    </button>
                </div>
            </div>

            <label class="bv-check">
                <input type="checkbox" id="exportContactsNotes" checked>
                Incluir notas internas
            </label>
            <label class="bv-check">
                <input type="checkbox" id="exportContactsHistory">
                Incluir historial de conversaciones
            </label>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="bv-export-contacts-dl">Descargar archivo</button>
            <button class="btn-secondary" data-bv-close>Cancelar</button>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
(function ($) {
    'use strict';

    var _ecScope = 'all';
    var _ecFmt   = 'csv';

    function closeBvModal(name) {
        $('[data-bv-modal-name="' + name + '"]').removeClass('on');
        if ($('.bv-modal.on').length === 0) { $('body').css('overflow', ''); }
    }

    $(document).on('bv:modal:open', function (e, name) {
        if (name !== 'export-contacts') { return; }
        _ecScope = 'all';
        _ecFmt   = 'csv';
        $('#exportContactsScope .bv-opt').removeClass('on');
        $('#exportContactsScope .bv-opt[data-scope="all"]').addClass('on');
        $('#exportContactsFormat .bv-export-opt').removeClass('on');
        $('#exportContactsFormat .bv-export-opt[data-fmt="csv"]').addClass('on');
        $('#exportContactsNotes').prop('checked', true);
        $('#exportContactsHistory').prop('checked', false);
    });

    $(document).on('click', '#exportContactsScope .bv-opt', function () {
        $('#exportContactsScope .bv-opt').removeClass('on');
        $(this).addClass('on');
        _ecScope = $(this).data('scope');
    });

    $(document).on('click', '#exportContactsFormat .bv-export-opt', function () {
        $('#exportContactsFormat .bv-export-opt').removeClass('on');
        $(this).addClass('on');
        _ecFmt = $(this).data('fmt');
    });

    $(document).on('click', '#bv-export-contacts-dl', function () {
        var $btn = $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Generando…');

        $.ajax({
            url: '/panel/helpdesk/contacts/export',
            method: 'POST',
            data: {
                scope:            _ecScope,
                format:           _ecFmt,
                include_notes:    $('#exportContactsNotes').is(':checked') ? 1 : 0,
                include_history:  $('#exportContactsHistory').is(':checked') ? 1 : 0,
            },
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), 'Accept': 'application/json' }
        }).done(function (resp) {
            closeBvModal('export-contacts');
            var url = resp.url || (resp.data && resp.data.url);
            if (url) {
                window.location.href = url;
            } else {
                if (window.toastr) { toastr.success('Exportación en proceso · recibirás un email'); }
            }
        }).fail(function (xhr) {
            var msg = xhr?.responseJSON?.message || 'Error al exportar';
            if (window.toastr) { toastr.error(msg); }
        }).always(function () {
            $btn.prop('disabled', false).text('Descargar archivo');
        });
    });

}(window.jQuery));
</script>
@endpush
@endonce
