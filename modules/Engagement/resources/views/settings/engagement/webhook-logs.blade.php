@extends('layouts.theme')
@section('title', 'Logs de webhooks — LiveChat')

@push('css')
<style>
    .lc-header { background: linear-gradient(135deg, #90bb13 0%, #7b0000 100%); color: white; padding: 1.5rem 2rem; border-radius: 12px; margin-bottom: 1.5rem; }
    .lc-header h4 { font-weight: 700; margin: 0 0 .25rem; }
    .lc-header p  { opacity: .9; margin: 0; font-size: .9rem; }
    .status-received  { background: #cfe2ff; color: #084298; }
    .status-processed { background: #d1e7dd; color: #0f5132; }
    .status-failed    { background: #fff3cd; color: #664d03; }
    .status-dead      { background: #f8d7da; color: #842029; }
    .status-badge { padding: .15rem .5rem; border-radius: 4px; font-size: .75rem; font-weight: 600; text-transform: uppercase; }
    .json-preview { font-family: monospace; font-size: .8rem; max-height: 300px; overflow: auto; background: #f7f7f7; padding: .75rem; border-radius: 6px; }
</style>
@endpush

@section('content')
    <div class="lc-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h4><i class="fas fa-list-check me-2"></i>Logs de webhooks</h4>
                <p>Auditoría y reintento manual de webhooks recibidos</p>
            </div>
            <a href="{{ route('settings.engagement.platforms.page') }}" class="btn btn-light btn-sm"><i class="fas fa-arrow-left me-1"></i>Integraciones</a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body d-flex align-items-center gap-3 flex-wrap">
            <label class="fw-semibold mb-0">Estado:</label>
            <select id="statusFilter" class="form-select w-auto">
                <option value="">Todos</option>
                <option value="received">Received</option>
                <option value="processed">Processed</option>
                <option value="failed">Failed</option>
                <option value="dead">Dead-letter</option>
            </select>
            <label class="fw-semibold mb-0 ms-3">Plataforma:</label>
            <select id="platformFilter" class="form-select w-auto">
                <option value="">Todas</option>
                <option value="prestashop">PrestaShop</option>
                <option value="shopify">Shopify</option>
                <option value="woocommerce">WooCommerce</option>
                <option value="custom">Custom</option>
            </select>
            <button class="btn btn-primary ms-auto" id="btnRefresh"><i class="fas fa-rotate-right me-1"></i>Actualizar</button>
        </div>
    </div>

    <div class="card"><div class="card-body p-0"><div id="logsGrid"></div></div></div>

<div class="modal fade" id="payloadModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Detalle del webhook</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body" id="payloadBody"></div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
$(function () {
    const routes = {
        index: '{{ route('settings.engagement.webhook-logs.index') }}',
        show:  (id) => '{{ route('settings.engagement.webhook-logs.show', ['webhookLog' => '__ID__']) }}'.replace('__ID__', id),
        retry: (id) => '{{ route('settings.engagement.webhook-logs.retry', ['webhookLog' => '__ID__']) }}'.replace('__ID__', id),
    };
    const csrf = $('meta[name="csrf-token"]').attr('content');

    const grid = $('#logsGrid').dxDataGrid({
        dataSource: { load: () => $.get(routes.index, {
            status: $('#statusFilter').val(), platform: $('#platformFilter').val(),
        }).then(r => r.data) },
        columns: [
            { dataField: 'created_at', caption: 'Fecha', width: 160, calculateCellValue: r => new Date(r.created_at).toLocaleString() },
            { dataField: 'platform', caption: 'Plataforma', width: 110 },
            { dataField: 'topic', caption: 'Topic' },
            { dataField: 'attempts', caption: 'Intentos', width: 90 },
            { dataField: 'status', caption: 'Estado', width: 110,
              cellTemplate: (el, info) => $(el).html(`<span class="status-badge status-${info.value}">${info.value}</span>`) },
            { dataField: 'last_error', caption: 'Último error',
              cellTemplate: (el, info) => $(el).text(info.value ? info.value.slice(0, 80) : '') },
            { caption: 'Acciones', width: 130, alignment: 'center',
              cellTemplate: (el, info) => {
                const canRetry = ['failed', 'dead'].includes(info.data.status);
                const retryBtn = canRetry ? `<li><a class="dropdown-item" href="#" data-action="retry" data-id="${info.data.id}">Reintentar</a></li>` : '';
                $(el).html(`<div class="dropdown">
                  <button class="btn btn-sm btn-light" data-bs-toggle="dropdown"><i class="fas fa-ellipsis-vertical"></i></button>
                  <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="#" data-action="view" data-id="${info.data.id}">Ver payload</a></li>
                    ${retryBtn}
                  </ul>
                </div>`);
              } },
        ],
        showBorders: true, paging: { pageSize: 25 }, searchPanel: { visible: true },
        noDataText: 'No hay logs.',
    }).dxDataGrid('instance');

    $('#btnRefresh, #statusFilter, #platformFilter').on('change click', () => grid.refresh());

    $(document).on('click', '[data-action="view"]', function (e) {
        e.preventDefault();
        $.get(routes.show($(this).data('id'))).done(r => {
            const d = r.data;
            $('#payloadBody').html(`
                <div class="mb-3"><strong>ID:</strong> ${d.id} · <strong>${d.platform}</strong> / ${d.topic}</div>
                <div class="mb-3"><strong>Estado:</strong> <span class="status-badge status-${d.status}">${d.status}</span> · ${d.attempts} intentos</div>
                ${d.last_error ? `<div class="alert alert-warning"><strong>Último error:</strong><br>${d.last_error}</div>` : ''}
                <h6 class="mt-3">Payload:</h6>
                <pre class="json-preview">${JSON.stringify(d.payload, null, 2)}</pre>
            `);
            $('#payloadModal').modal('show');
        });
    });

    $(document).on('click', '[data-action="retry"]', function (e) {
        e.preventDefault();
        if (!confirm('¿Reintentar este webhook?')) return;
        $.ajax({ url: routes.retry($(this).data('id')), method: 'POST', headers: { 'X-CSRF-TOKEN': csrf } })
            .done(() => { grid.refresh(); toastr.success('Reintento programado.'); })
            .fail(xhr => toastr.error(xhr.responseJSON?.message ?? 'Error.'));
    });
});
</script>
@endpush
