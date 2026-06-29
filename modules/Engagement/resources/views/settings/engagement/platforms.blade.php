@extends('layouts.theme')

@section('title', 'Integraciones de plataformas — LiveChat')

@push('css')
<style>
    .lc-header {
        background: linear-gradient(135deg, #90bb13 0%, #7b0000 100%);
        color: white;
        padding: 1.5rem 2rem;
        border-radius: 12px;
        margin-bottom: 1.5rem;
    }
    .lc-header h4 { font-weight: 700; margin: 0 0 .25rem; }
    .lc-header p  { opacity: .9; margin: 0; font-size: .9rem; }
    .platform-badge {
        font-size: .75rem; padding: .15rem .5rem; border-radius: 4px;
        font-weight: 600; text-transform: uppercase;
    }
    .platform-prestashop  { background: #df0067; color: #fff; }
    .platform-shopify     { background: #95bf47; color: #fff; }
    .platform-woocommerce { background: #7f54b3; color: #fff; }
    .platform-erp         { background: #0d6efd; color: #fff; }
    .platform-custom      { background: #6c757d; color: #fff; }
    .secret-input { font-family: monospace; font-size: .85rem; }
    .erp-block { display: none; }
    .erp-block.show-erp { display: block; }
</style>
@endpush

@section('content')

    <div class="lc-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h4><i class="fas fa-plug me-2"></i>Integraciones de plataformas</h4>
                <p>Conecta PrestaShop, Shopify, WooCommerce o un sitio custom — cada una con su webhook firmado HMAC</p>
            </div>
            <a href="{{ route('settings.engagement.index') }}" class="btn btn-light btn-sm">
                <i class="fas fa-arrow-left me-1"></i>Volver a ajustes
            </a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body d-flex align-items-center gap-3 flex-wrap justify-content-end">
            <button class="btn btn-outline-secondary" id="btnToggleTrashed">
                <i class="fas fa-trash me-1"></i><span id="trashLabel">Papelera</span>
            </button>
            @can('engagement.platforms.create')
            <button class="btn btn-primary" id="btnNewIntegration">
                <i class="fas fa-plus me-1"></i>Nueva integración
            </button>
            @endcan
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div id="platformsGrid"></div>
        </div>
    </div>


{{-- Modal nueva/editar --}}
<div class="modal fade" id="platformModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="platformModalTitle">Nueva integración</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="platformId">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Inbox <span class="text-danger">*</span></label>
                        <select id="platformInbox" class="form-select">
                            @foreach($inboxes as $inbox)
                                <option value="{{ $inbox->id }}">{{ $inbox->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Plataforma <span class="text-danger">*</span></label>
                        <select id="platformType" class="form-select">
                            <option value="prestashop">PrestaShop</option>
                            <option value="shopify">Shopify</option>
                            <option value="woocommerce">WooCommerce</option>
                            <option value="erp">ERP propio (Oracle / sistema interno)</option>
                            <option value="custom">Custom</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">URL de la tienda / API base</label>
                        <input type="url" id="platformStoreUrl" class="form-control" placeholder="https://miniestore.com">
                        <div class="form-text" id="storeUrlHint">Para ERP, indica la URL base de la API (ej: https://erp.empresa.local/api/erp).</div>
                    </div>
                    <div class="col-12 erp-block" id="erpBlock">
                        <label class="form-label fw-semibold">Token de autenticación (Bearer Sanctum) <span class="text-danger">*</span></label>
                        <input type="password" id="platformAuthToken" class="form-control secret-input" autocomplete="new-password" placeholder="Pega aquí el token generado en el proyecto ERP">
                        <div class="form-text">Genéralo en el proyecto manager con <code>php artisan engagement:erp-token --user=system@bridge</code>. Se guardará encriptado.</div>
                    </div>
                    <div class="col-12 erp-block" id="erpLookupBlock">
                        <label class="form-label fw-semibold">Estrategia de búsqueda</label>
                        <select id="platformLookupStrategy" class="form-select">
                            <option value="email">Por email (recomendado)</option>
                            <option value="external_id_only">Sólo external_id (no buscar)</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Estado</label>
                        <select id="platformActive" class="form-select">
                            <option value="1">Activa</option>
                            <option value="0">Inactiva</option>
                        </select>
                    </div>
                </div>

                <div id="credentialsBlock" class="mt-4 d-none">
                    <hr>
                    <h6 class="fw-semibold mb-3">Credenciales para la distribución</h6>
                    <div class="alert alert-warning small">
                        <i class="fas fa-triangle-exclamation me-1"></i>
                        Copia el secret ahora — es la única vez que se muestra completo. Si lo pierdes, deberás regenerarlo.
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Webhook URL</label>
                        <div class="input-group">
                            <input type="text" id="webhookUrlInput" class="form-control secret-input" readonly>
                            <button class="btn btn-outline-secondary" type="button" data-copy="#webhookUrlInput"><i class="fas fa-copy"></i></button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Webhook secret</label>
                        <div class="input-group">
                            <input type="text" id="webhookSecretInput" class="form-control secret-input" readonly>
                            <button class="btn btn-outline-secondary" type="button" data-copy="#webhookSecretInput"><i class="fas fa-copy"></i></button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer flex-column gap-2">
                <button type="button" class="btn btn-primary w-100" id="btnSavePlatform">Guardar</button>
                <button type="button" class="btn btn-outline-secondary w-100" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
$(function () {
    const routes = {
        index:        '{{ route('settings.engagement.platforms.index') }}',
        store:        '{{ route('settings.engagement.platforms.store') }}',
        show:         (id) => '{{ route('settings.engagement.platforms.show', ['platformIntegration' => '__ID__']) }}'.replace('__ID__', id),
        update:       (id) => '{{ route('settings.engagement.platforms.update', ['platformIntegration' => '__ID__']) }}'.replace('__ID__', id),
        rotate:       (id) => '{{ route('settings.engagement.platforms.rotate-secret', ['platformIntegration' => '__ID__']) }}'.replace('__ID__', id),
        destroy:      (id) => '{{ route('settings.engagement.platforms.destroy', ['platformIntegration' => '__ID__']) }}'.replace('__ID__', id),
        trashed:      '{{ route('settings.engagement.platforms.trashed') }}',
        restore:      (id) => '{{ route('settings.engagement.platforms.restore', ['id' => '__ID__']) }}'.replace('__ID__', id),
    };
    const csrf = $('meta[name="csrf-token"]').attr('content');
    let showTrashed = false;

    const grid = $('#platformsGrid').dxDataGrid({
        dataSource: {
            load: () => {
                const url = showTrashed ? routes.trashed : routes.index;
                return $.get(url).then(r => r.data);
            },
        },
        columns: [
            { dataField: 'platform', caption: 'Plataforma', width: 140,
              cellTemplate: (el, info) => $(el).html(`<span class="platform-badge platform-${info.value}">${info.value}</span>`) },
            { dataField: 'inbox.name', caption: 'Inbox' },
            { dataField: 'store_url', caption: 'URL tienda' },
            { dataField: 'is_active', caption: 'Estado', width: 100,
              cellTemplate: (el, info) => $(el).html(
                info.value ? '<span class="badge bg-success">Activa</span>' : '<span class="badge bg-secondary">Inactiva</span>'
              ) },
            { dataField: 'last_event_at', caption: 'Último evento', width: 160,
              calculateCellValue: (r) => r.last_event_at ? new Date(r.last_event_at).toLocaleString() : '—' },
            { caption: 'Acciones', width: 140, alignment: 'center',
              cellTemplate: (el, info) => {
                if (showTrashed) {
                    $(el).html(`<button class="btn btn-sm btn-outline-success" data-action="restore" data-id="${info.data.id}"><i class="fas fa-undo"></i> Restaurar</button>`);
                    return;
                }
                const wrap = $('<div class="dropdown">');
                wrap.html(`
                  <button class="btn btn-sm btn-light" data-bs-toggle="dropdown"><i class="fas fa-ellipsis-vertical"></i></button>
                  <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="#" data-action="view" data-id="${info.data.id}">Ver credenciales</a></li>
                    <li><a class="dropdown-item" href="#" data-action="edit" data-id="${info.data.id}">Editar</a></li>
                    <li><a class="dropdown-item" href="#" data-action="rotate" data-id="${info.data.id}">Regenerar secret</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="#" data-action="delete" data-id="${info.data.id}">Eliminar</a></li>
                  </ul>`);
                $(el).append(wrap);
              }
            },
        ],
        showBorders: true,
        paging: { pageSize: 20 },
        searchPanel: { visible: true, placeholder: 'Buscar...' },
        noDataText: 'No hay integraciones configuradas.',
    }).dxDataGrid('instance');

    // ---- Toggle ERP-specific block ----
    function toggleErpBlocks(platform) {
        if (platform === 'erp') {
            $('.erp-block').addClass('show-erp');
            $('#credentialsBlock').addClass('d-none'); // ERP no usa webhook secret
        } else {
            $('.erp-block').removeClass('show-erp');
        }
    }

    $('#platformType').on('change', function () { toggleErpBlocks($(this).val()); });

    // ---- New ----
    $('#btnNewIntegration').on('click', () => {
        $('#platformId').val('');
        $('#platformModalTitle').text('Nueva integración');
        $('#platformInbox').val($('#platformInbox option:first').val());
        $('#platformType').val('prestashop').prop('disabled', false);
        $('#platformStoreUrl').val('');
        $('#platformAuthToken').val('');
        $('#platformLookupStrategy').val('email');
        $('#platformActive').val('1');
        $('#credentialsBlock').addClass('d-none');
        toggleErpBlocks('prestashop');
        $('#platformModal').modal('show');
    });

    // ---- View / Edit ----
    function loadAndOpen(id, mode) {
        $.get(routes.show(id)).done(r => {
            const data = r.data;
            $('#platformId').val(data.id);
            $('#platformModalTitle').text(mode === 'view' ? 'Credenciales' : 'Editar integración');
            $('#platformInbox').val(data.inbox.id);
            $('#platformType').val(data.platform).prop('disabled', true);
            $('#platformStoreUrl').val(data.store_url ?? '');
            $('#platformAuthToken').val('').attr('placeholder', data.config?.auth_token || 'Pega aquí el token generado en el proyecto ERP');
            $('#platformLookupStrategy').val(data.config?.lookup_strategy || 'email');
            $('#platformActive').val(data.is_active ? '1' : '0');
            toggleErpBlocks(data.platform);
            if (data.platform === 'erp') {
                $('#credentialsBlock').addClass('d-none');
            } else {
                $('#webhookUrlInput').val(data.webhook_url);
                $('#webhookSecretInput').val(data.webhook_secret);
                $('#credentialsBlock').removeClass('d-none');
            }
            $('#platformModal').modal('show');
        }).fail(() => toastr.error('No se pudo cargar la integración.'));
    }

    $(document).on('click', '[data-action="view"]', function (e) { e.preventDefault(); loadAndOpen($(this).data('id'), 'view'); });
    $(document).on('click', '[data-action="edit"]', function (e) { e.preventDefault(); loadAndOpen($(this).data('id'), 'edit'); });

    $(document).on('click', '[data-action="rotate"]', function (e) {
        e.preventDefault();
        if (!confirm('¿Regenerar el secret? Las distribuciones existentes dejarán de funcionar hasta actualizarse.')) return;
        const id = $(this).data('id');
        $.ajax({ url: routes.rotate(id), method: 'POST', headers: { 'X-CSRF-TOKEN': csrf } })
            .done(r => {
                toastr.success('Secret regenerado.');
                grid.refresh();
                loadAndOpen(id, 'view');
            })
            .fail(() => toastr.error('Error al regenerar el secret.'));
    });

    $(document).on('click', '[data-action="delete"]', function (e) {
        e.preventDefault();
        if (!confirm('¿Eliminar esta integración?')) return;
        const id = $(this).data('id');
        $.ajax({ url: routes.destroy(id), method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrf } })
            .done(() => { toastr.success('Eliminada.'); grid.refresh(); })
            .fail(() => toastr.error('Error al eliminar.'));
    });

    $(document).on('click', '[data-action="restore"]', function (e) {
        e.preventDefault();
        const id = $(this).data('id');
        $.ajax({ url: routes.restore(id), method: 'POST', headers: { 'X-CSRF-TOKEN': csrf } })
            .done(() => { grid.refresh(); toastr.success('Integración restaurada.'); })
            .fail(() => toastr.error('Error al restaurar.'));
    });

    $('#btnToggleTrashed').on('click', function () {
        showTrashed = !showTrashed;
        $('#trashLabel').text(showTrashed ? 'Activos' : 'Papelera');
        $(this).toggleClass('btn-outline-secondary btn-secondary');
        grid.refresh();
    });

    // ---- Save ----
    $('#btnSavePlatform').on('click', function () {
        const id = $('#platformId').val();
        const platform = $('#platformType').val();
        const payload = {
            inbox_id: parseInt($('#platformInbox').val()),
            platform: platform,
            store_url: $('#platformStoreUrl').val().trim() || null,
            is_active: $('#platformActive').val() === '1',
        };
        if (platform === 'erp') {
            const authToken = $('#platformAuthToken').val().trim();
            payload.config = {
                lookup_strategy: $('#platformLookupStrategy').val(),
            };
            if (authToken) payload.config.auth_token = authToken;
        }
        const url    = id ? routes.update(id) : routes.store;
        const method = id ? 'PUT' : 'POST';

        $.ajax({
            url, method,
            data: JSON.stringify(payload),
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': csrf },
        }).done(r => {
            grid.refresh();
            // Show credentials right after creation
            if (!id && r.data) {
                $('#platformId').val(r.data.id);
                $('#webhookUrlInput').val(r.data.webhook_url);
                $('#webhookSecretInput').val(r.data.webhook_secret);
                $('#credentialsBlock').removeClass('d-none');
                $('#platformType').prop('disabled', true);
                toastr.success('Integración creada. Copia el secret ahora.');
            } else {
                $('#platformModal').modal('hide');
                toastr.success('Guardada.');
            }
        }).fail(xhr => {
            const errors = xhr.responseJSON?.errors;
            toastr.error(errors ? Object.values(errors).flat().join('<br>') : 'Error al guardar.');
        });
    });

    // ---- Copy buttons ----
    $(document).on('click', '[data-copy]', function () {
        const target = $($(this).data('copy'));
        target.select();
        document.execCommand('copy');
        toastr.info('Copiado al portapapeles.');
    });
});
</script>
@endpush
