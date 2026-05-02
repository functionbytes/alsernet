@extends('layouts.theme')
@section('title', 'Audit log — LiveChat')

@push('css')
<style>
    .lc-header { background: linear-gradient(135deg, #b10100 0%, #7b0000 100%); color: white; padding: 1.5rem 2rem; border-radius: 12px; margin-bottom: 1.5rem; }
    .lc-header h4 { font-weight: 700; margin: 0 0 .25rem; }
    .lc-header p  { opacity: .9; margin: 0; font-size: .9rem; }
    .action-badge { font-size: .7rem; padding: .15rem .5rem; border-radius: 4px; font-weight: 600; text-transform: uppercase; }
    .action-created { background: #d1e7dd; color: #0f5132; }
    .action-updated { background: #cfe2ff; color: #084298; }
    .action-deleted { background: #f8d7da; color: #842029; }
    .action-rotated_secret { background: #fff3cd; color: #664d03; }
    .action-retried { background: #e2e3e5; color: #41464b; }
    .json-preview { font-family: monospace; font-size: .8rem; max-height: 400px; overflow: auto; background: #f7f7f7; padding: .75rem; border-radius: 6px; }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">
    <div class="lc-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h4><i class="fas fa-clipboard-list me-2"></i>Audit log</h4>
                <p>Quién hizo qué y cuándo en triggers, personalizations, integraciones, automation y goals</p>
            </div>
            <a href="{{ route('settings.helpdesk-livechat.index') }}" class="btn btn-light btn-sm"><i class="fas fa-arrow-left me-1"></i>Volver</a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body d-flex align-items-center gap-3 flex-wrap">
            <label class="fw-semibold mb-0">Entidad:</label>
            <select id="entityFilter" class="form-select w-auto">
                <option value="">Todas</option>
                <option value="trigger">Triggers</option>
                <option value="personalization">Personalizations</option>
                <option value="integration">Integraciones</option>
                <option value="automation_flow">Automation flows</option>
                <option value="conversion_goal">Conversion goals</option>
                <option value="webhook_log">Webhook logs</option>
            </select>
            <label class="fw-semibold mb-0 ms-3">Acción:</label>
            <select id="actionFilter" class="form-select w-auto">
                <option value="">Todas</option>
                <option value="created">Created</option>
                <option value="updated">Updated</option>
                <option value="deleted">Deleted</option>
                <option value="rotated_secret">Rotated secret</option>
                <option value="retried">Retried</option>
            </select>
            <button class="btn btn-primary ms-auto" id="btnRefresh"><i class="fas fa-rotate-right me-1"></i>Actualizar</button>
        </div>
    </div>

    <div class="card"><div class="card-body p-0"><div id="auditGrid"></div></div></div>
</div>

<div class="modal fade" id="changesModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Detalle del cambio</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body" id="changesBody"></div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
$(function () {
    const url = '{{ route('settings.engagement.audit-logs.index') }}';

    const grid = $('#auditGrid').dxDataGrid({
        dataSource: { load: () => $.get(url, {
            entity_type: $('#entityFilter').val(),
            action: $('#actionFilter').val(),
        }).then(r => r.data) },
        columns: [
            { dataField: 'created_at', caption: 'Fecha', width: 160, calculateCellValue: r => new Date(r.created_at).toLocaleString() },
            { dataField: 'user.name', caption: 'Usuario', width: 160 },
            { dataField: 'action', caption: 'Acción', width: 130,
              cellTemplate: (el, info) => $(el).html(`<span class="action-badge action-${info.value}">${info.value}</span>`) },
            { dataField: 'entity_type', caption: 'Entidad', width: 150 },
            { dataField: 'entity_id', caption: 'ID', width: 80 },
            { dataField: 'ip_address', caption: 'IP', width: 130 },
            { caption: 'Acciones', width: 100, alignment: 'center',
              cellTemplate: (el, info) => $(el).html(`<button class="btn btn-sm btn-light" data-action="view" data-id="${info.data.id}"><i class="fas fa-eye"></i></button>`) },
        ],
        showBorders: true, paging: { pageSize: 25 }, searchPanel: { visible: true },
        noDataText: 'No hay logs de auditoría.',
    }).dxDataGrid('instance');

    $('#btnRefresh, #entityFilter, #actionFilter').on('change click', () => grid.refresh());

    $(document).on('click', '[data-action="view"]', function () {
        const id = $(this).data('id');
        $.get(url).done(r => {
            const log = r.data.find(x => x.id == id);
            if (!log) return;
            $('#changesBody').html(`
                <div class="mb-2"><strong>${log.entity_type} #${log.entity_id}</strong> · <span class="action-badge action-${log.action}">${log.action}</span></div>
                <div class="text-muted mb-3">${new Date(log.created_at).toLocaleString()} · ${log.user?.name ?? '—'} (${log.ip_address ?? ''})</div>
                <h6>Changes:</h6>
                <pre class="json-preview">${log.changes ? JSON.stringify(log.changes, null, 2) : '(sin detalle)'}</pre>
            `);
            $('#changesModal').modal('show');
        });
    });
});
</script>
@endpush
