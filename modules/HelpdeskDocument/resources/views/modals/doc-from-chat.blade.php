{{--
    Document module · Modal Cargar desde galería del chat.

    Uso:
        @include('helpdeskdocument::modals.doc-from-chat', [
            'id'        => 'docFromChat_xyz',    // default: 'doc-from-chat'
            'order_ref' => '829575',             // opcional, muestra info banner
            'files'     => $demoChatFiles,       // opcional, modo demo con archivos estaticos
        ])
--}}
@php
    $id       = $id       ?? 'doc-from-chat';
    $orderRef = $order_ref ?? null;
    $files    = $files    ?? [];
@endphp

@include('helpdeskdocument::modals._assets')

<div class="docs-backdrop" data-docs-modal-backdrop-for="{{ $id }}"></div>
<div id="{{ $id }}" class="docs-modal docs-modal-md" role="dialog" aria-modal="true">
    <div class="docs-head">
        <div class="docs-head-icon"><i class="fa-solid fa-cloud-arrow-up"></i></div>
        <div class="docs-head-text">
            <div class="docs-head-label">DOCUMENTOS · IMPORTAR</div>
            <div class="docs-head-title">Cargar desde galería del chat</div>
        </div>
        <button type="button" class="docs-close" aria-label="Cerrar">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>
    <div class="docs-body">
        @if($orderRef)
            <div class="docs-minfo">
                <i class="fa-solid fa-circle-info"></i>
                <div>Selecciona los archivos compartidos en el chat para asociar al expediente <b>#{{ $orderRef }}</b>.</div>
            </div>
        @endif
        <div class="docs-field">
            <label class="flabel">Categoría destino</label>
            <select class="docs-cg-category form-select form-select-sm">
                <option value="dni_front">DNI frontal</option>
                <option value="dni_back">DNI trasera</option>
                <option value="proforma">Factura proforma</option>
                <option value="contract">Contrato firmado</option>
                <option value="other" selected>Otro — sin categoría</option>
            </select>
        </div>
        <div class="docs-field">
            <label class="flabel">
                Archivos disponibles en el chat
                <span class="hint docs-cg-count"></span>
            </label>
            <div class="docs-cg-gallery">
                @if(count($files))
                    <div class="docs-cg-grid">
                        @foreach($files as $i => $f)
                            @php
                                $fType = $f['type'] ?? 'file';
                                $icon = match(true) {
                                    in_array($fType, ['image']) => 'fa-regular fa-image',
                                    $fType === 'pdf'           => 'fa-regular fa-file-pdf',
                                    $fType === 'video'         => 'fa-solid fa-video',
                                    default                    => 'fa-regular fa-file-lines',
                                };
                                $on = !empty($f['selected']) ? ' on' : '';
                            @endphp
                            <button type="button" class="docs-cg-pick{{ $on }}" data-cg-key="{{ $i }}">
                                <i class="{{ $icon }} preview"></i>
                                <span class="check"><i class="fa-solid fa-check"></i></span>
                                <span class="lbl">{{ $f['name'] ?? 'Archivo' }}</span>
                            </button>
                        @endforeach
                    </div>
                @else
                    <div class="docs-cg-loading">
                        <i class="fas fa-spinner fa-spin"></i> Cargando…
                    </div>
                @endif
            </div>
        </div>
        <div class="d-flex flex-column gap-1 mt-1">
            <label class="docs-check">
                <input type="checkbox" class="docs-cg-notify" checked>
                Notificar al cliente cuando se carguen los documentos
            </label>
            <label class="docs-check">
                <input type="checkbox" class="docs-cg-keep-copy">
                Mantener copia original en el chat
            </label>
        </div>
    </div>
    <div class="docs-foot">
        <button type="button" class="btn btn-primary"
                data-docs-cg-import-btn
                data-empty-label="Selecciona archivos"
                data-label-template="Importar {n} archivo(s)"
                disabled>
            Selecciona archivos
        </button>
        <input type="file" class="docs-cg-file-input d-none" multiple accept="image/*,application/pdf">
        <button type="button" class="btn btn-outline-secondary docs-cg-upload-device">
            <i class="fas fa-upload me-1"></i> Subir desde mi equipo
        </button>
        <button type="button" class="btn btn-outline-secondary docs-close">Cerrar</button>
    </div>
</div>

@once
@push('scripts')
<script>
(function ($) {
    'use strict';
    if (!window.jQuery) { return; }

    var modalId = '{{ $id }}';

    function $modal() { return $('#' + modalId); }

    function updateImportBtn(n) {
        var $btn = $modal().find('[data-docs-cg-import-btn]');
        $btn.prop('disabled', n === 0).text(n > 0 ? 'Importar ' + n + ' archivo(s)' : 'Selecciona archivos');
    }

@if(count($files) > 0)
    // Modo demo: init contador con archivos preseleccionados
    $(function () {
        var preselected = $modal().find('.docs-cg-pick.on').length;
        $modal().find('.docs-cg-count').text('{{ count($files) }} archivos');
        if (preselected > 0) {
            updateImportBtn(preselected);
        }
    });
@else
    // Modo inbox: carga dinamica desde API
    function loadGallery(customerId, convId) {
        var $m = $modal();
        $m.find('.docs-cg-gallery').html('<div class="docs-cg-loading"><i class="fas fa-spinner fa-spin"></i> Cargando…</div>');
        $m.find('.docs-cg-count').text('');
        updateImportBtn(0);

        if (!customerId) {
            $m.find('.docs-cg-gallery').html('<div class="docs-vd-empty"><i class="fas fa-triangle-exclamation"></i> Sin cliente vinculado</div>');
            return;
        }

        var params = convId ? { conversation: convId } : {};

        $.ajax({
            url: '/panel/helpdesk/customers/' + customerId + '/media',
            method: 'GET',
            data: params,
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), 'Accept': 'application/json' }
        }).done(function (resp) {
            var files = (resp.data || []).filter(function (m) { return m && m.url; });
            $m.find('.docs-cg-count').text(files.length + ' archivos');

            if (!files.length) {
                $m.find('.docs-cg-gallery').html('<div class="docs-vd-empty"><i class="fa-regular fa-images"></i> Sin archivos en esta conversación</div>');
                return;
            }

            var html = files.map(function (m, i) {
                var icon = /\.(png|jpe?g|gif|webp)$/i.test(m.url) ? 'fa-regular fa-image' : 'fa-regular fa-file';
                return '<button type="button" class="docs-cg-pick" data-cg-key="' + i + '" data-url="' + (m.url || '') + '">' +
                    '<i class="' + icon + ' preview"></i>' +
                    '<span class="check"><i class="fa-solid fa-check"></i></span>' +
                    '<span class="lbl">' + (m.name || m.url.split('/').pop() || 'Archivo') + '</span>' +
                '</button>';
            }).join('');
            $m.find('.docs-cg-gallery').html('<div class="docs-cg-grid">' + html + '</div>');
        }).fail(function () {
            $m.find('.docs-cg-gallery').html('<div class="docs-vd-empty"><i class="fas fa-triangle-exclamation"></i> No se pudo cargar la galería</div>');
        });
    }

    // Import handler
    $(document).on('click', '#' + modalId + ' [data-docs-cg-import-btn]', function () {
        var $m = $modal();
        var urls = [];
        $m.find('.docs-cg-pick.on').each(function () {
            var url = $(this).data('url');
            if (url) { urls.push(url); }
        });

        if (!urls.length) { return; }

        var convId = (window.HDCommerce && window.HDCommerce.conversationId) ? window.HDCommerce.conversationId() : null;
        if (!convId) {
            if (window.toastr) { toastr.warning('No hay conversación activa.'); }
            return;
        }

        var $btn = $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Importando…');

        $.ajax({
            url: '/panel/helpdesk/conversations/' + convId + '/documents/import-from-chat',
            method: 'POST',
            data: {
                file_ids:        urls,
                category:        $m.find('.docs-cg-category').val(),
                notify_customer: $m.find('.docs-cg-notify').is(':checked') ? 1 : 0,
                keep_original:   $m.find('.docs-cg-keep-copy').is(':checked') ? 1 : 0,
            },
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), 'Accept': 'application/json' }
        }).done(function (resp) {
            if (window.toastr) { toastr.success(resp.message || 'Archivos importados.'); }
            if (window.DocsModal) { window.DocsModal.close(modalId); }
        }).fail(function (xhr) {
            var msg = (xhr.responseJSON && (xhr.responseJSON.message || Object.values(xhr.responseJSON.errors || {}).flat().join(' '))) || 'Error al importar.';
            if (window.toastr) { toastr.error(msg); }
        }).always(function () {
            $btn.prop('disabled', false).text('Selecciona archivos');
        });
    });

    // Subir desde equipo: dispara el file input oculto
    $(document).on('click', '#' + modalId + ' .docs-cg-upload-device', function () {
        $('#' + modalId).find('.docs-cg-file-input').trigger('click');
    });

    $(document).on('change', '#' + modalId + ' .docs-cg-file-input', function () {
        var $m     = $modal();
        var files  = this.files;
        if (!files || !files.length) { return; }

        var convId = (window.HDCommerce && window.HDCommerce.conversationId) ? window.HDCommerce.conversationId() : null;
        if (!convId) {
            if (window.toastr) { toastr.warning('No hay conversación activa.'); }
            return;
        }

        var $btn = $m.find('.docs-cg-upload-device').prop('disabled', true)
                     .html('<i class="fas fa-spinner fa-spin me-1"></i> Subiendo…');

        var fd = new FormData();
        for (var i = 0; i < files.length; i++) { fd.append('files[]', files[i]); }
        fd.append('category', $m.find('.docs-cg-category').val());
        fd.append('notify_customer', $m.find('.docs-cg-notify').is(':checked') ? '1' : '0');

        $.ajax({
            url: '/panel/helpdesk/conversations/' + convId + '/documents/import-from-chat',
            method: 'POST', data: fd,
            processData: false, contentType: false,
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), 'Accept': 'application/json' }
        }).done(function (resp) {
            if (window.toastr) { toastr.success(resp.message || 'Archivos subidos correctamente.'); }
            if (window.DocsModal) { window.DocsModal.close(modalId); }
        }).fail(function (xhr) {
            var msg = (xhr.responseJSON && (xhr.responseJSON.message || Object.values(xhr.responseJSON.errors || {}).flat().join(' '))) || 'Error al subir.';
            if (window.toastr) { toastr.error(msg); }
        }).always(function () {
            $btn.prop('disabled', false).html('<i class="fas fa-upload me-1"></i> Subir desde mi equipo');
        });
    });

    window.openDocFromChat = function () {
        var customerId = (window.HDCommerce && window.HDCommerce.customerId) ? window.HDCommerce.customerId() : null;
        if (!customerId) {
            if (window.toastr) { toastr.warning('Selecciona una conversación con cliente.'); }
            return;
        }
        var convId = (window.HDCommerce && window.HDCommerce.conversationId) ? window.HDCommerce.conversationId() : null;
        if (window.DocsModal) { window.DocsModal.open(modalId); }
        loadGallery(customerId, convId);
    };
@endif

}(window.jQuery));
</script>
@endpush
@endonce
