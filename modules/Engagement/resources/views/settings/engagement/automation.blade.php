@extends('layouts.theme')

@section('title', 'Automatización — LiveChat')

@push('css')
<style>
    .lc-header { background: linear-gradient(135deg, #b10100 0%, #7b0000 100%); color: white; padding: 1.5rem 2rem; border-radius: 12px; margin-bottom: 1.5rem; }
    .lc-header h4 { font-weight: 700; margin: 0 0 .25rem; }
    .lc-header p  { opacity: .9; margin: 0; font-size: .9rem; }
    .json-editor { font-family: 'Courier New', monospace; font-size: .85rem; min-height: 220px; }
    .node-row + .node-row, .edge-row + .edge-row { margin-top: .5rem; }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">
    <div class="lc-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h4><i class="fas fa-robot me-2"></i>Flujos de automatización</h4>
                <p>Chatbot flows con nodos message, question, condition, delay y action</p>
            </div>
            <a href="{{ route('settings.helpdesk-livechat.index') }}" class="btn btn-light btn-sm"><i class="fas fa-arrow-left me-1"></i>Volver</a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body d-flex align-items-center gap-3 flex-wrap">
            <label class="fw-semibold mb-0">Inbox:</label>
            <select id="inboxSelector" class="form-select w-auto">
                @foreach($inboxes as $inbox)
                    <option value="{{ $inbox->id }}">{{ $inbox->name }}</option>
                @endforeach
            </select>
            @can('engagement.automation.create')
            <button class="btn btn-primary ms-auto" id="btnNewFlow"><i class="fas fa-plus me-1"></i>Nuevo flujo</button>
            @endcan
        </div>
    </div>

    <div class="card"><div class="card-body p-0"><div id="flowsGrid"></div></div></div>
</div>

<div class="modal fade" id="flowModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="flowModalTitle">Nuevo flujo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="flowId">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label fw-semibold">Nombre <span class="text-danger">*</span></label>
                        <input type="text" id="flowName" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Estado</label>
                        <select id="flowActive" class="form-select"><option value="1">Activo</option><option value="0">Inactivo</option></select>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Descripción</label>
                        <input type="text" id="flowDescription" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Tipo de trigger</label>
                        <select id="flowTriggerType" class="form-select">
                            <option value="manual">Manual (admin lo lanza)</option>
                            <option value="event">Por evento</option>
                            <option value="schedule">Programado</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Evento (si trigger=event)</label>
                        <input type="text" id="flowTriggerEvent" class="form-control" placeholder="ej: cart_abandoned">
                    </div>
                    <div class="col-12">
                        <ul class="nav nav-tabs mt-3" role="tablist">
                            <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-nodes">Nodos</button></li>
                            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-edges">Conexiones</button></li>
                            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-json">JSON crudo</button></li>
                        </ul>
                        <div class="tab-content border border-top-0 p-3" style="border-radius: 0 0 .5rem .5rem">
                            <div class="tab-pane fade show active" id="tab-nodes">
                                <div class="d-flex justify-content-between mb-2">
                                    <small class="text-muted">Cada nodo es un paso del flujo. El primero arranca al activarse.</small>
                                    <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddNode"><i class="fas fa-plus"></i> Nodo</button>
                                </div>
                                <div id="nodesContainer"></div>
                            </div>
                            <div class="tab-pane fade" id="tab-edges">
                                <div class="d-flex justify-content-between mb-2">
                                    <small class="text-muted">Conexiones entre nodos. <code>branch</code> opcional para conditions/questions.</small>
                                    <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddEdge"><i class="fas fa-plus"></i> Conexión</button>
                                </div>
                                <div id="edgesContainer"></div>
                            </div>
                            <div class="tab-pane fade" id="tab-json">
                                <textarea id="rawJsonNodes" class="form-control json-editor mb-2" placeholder="nodes JSON"></textarea>
                                <textarea id="rawJsonEdges" class="form-control json-editor" placeholder="edges JSON"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer flex-column gap-2">
                <button type="button" class="btn btn-primary w-100" id="btnSaveFlow">Guardar flujo</button>
                <button type="button" class="btn btn-outline-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
$(function () {
    const routes = {
        index:   '{{ route('settings.engagement.automation.index') }}',
        store:   '{{ route('settings.engagement.automation.store') }}',
        update:  (id) => '{{ route('settings.engagement.automation.update', ['automationFlow' => '__ID__']) }}'.replace('__ID__', id),
        destroy: (id) => '{{ route('settings.engagement.automation.destroy', ['automationFlow' => '__ID__']) }}'.replace('__ID__', id),
    };
    const csrf = $('meta[name="csrf-token"]').attr('content');

    const grid = $('#flowsGrid').dxDataGrid({
        dataSource: { load: () => $.get(routes.index, { inbox_id: $('#inboxSelector').val() }).then(r => r.data) },
        columns: [
            { dataField: 'name', caption: 'Nombre' },
            { dataField: 'trigger_type', caption: 'Trigger', width: 120 },
            { dataField: 'trigger_event', caption: 'Evento', width: 160 },
            { dataField: 'runs_count', caption: 'Ejecuciones', width: 110 },
            { dataField: 'is_active', caption: 'Estado', width: 100,
              cellTemplate: (el, info) => $(el).html(info.value ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-secondary">Inactivo</span>') },
            { caption: 'Acciones', width: 120, alignment: 'center',
              cellTemplate: (el, info) => $(el).html(`
                <div class="dropdown">
                  <button class="btn btn-sm btn-light" data-bs-toggle="dropdown"><i class="fas fa-ellipsis-vertical"></i></button>
                  <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="#" data-action="edit" data-id="${info.data.id}">Editar</a></li>
                    <li><a class="dropdown-item" href="#" data-action="delete" data-id="${info.data.id}">Eliminar</a></li>
                  </ul>
                </div>`) },
        ],
        showBorders: true, paging: { pageSize: 20 }, searchPanel: { visible: true },
        noDataText: 'No hay flujos configurados.',
    }).dxDataGrid('instance');

    $('#inboxSelector').on('change', () => grid.refresh());

    function nodeRow(data = {}) {
        const types = ['message', 'question', 'delay', 'condition', 'action'];
        return $(`<div class="node-row d-flex gap-2 align-items-start">
            <input class="form-control form-control-sm n-id" style="max-width:90px" placeholder="id" value="${data.id ?? ''}">
            <select class="form-select form-select-sm n-type" style="max-width:130px">
                ${types.map(t => `<option ${data.type===t?'selected':''}>${t}</option>`).join('')}
            </select>
            <textarea class="form-control form-control-sm n-config json-editor" placeholder='{"text":"Hola"}' rows="2">${data.config ? JSON.stringify(data.config) : ''}</textarea>
            <button type="button" class="btn btn-sm btn-outline-danger n-remove"><i class="fas fa-trash"></i></button>
        </div>`);
    }

    function edgeRow(data = {}) {
        return $(`<div class="edge-row d-flex gap-2 align-items-center">
            <input class="form-control form-control-sm e-from" style="max-width:120px" placeholder="from" value="${data.from ?? ''}">
            <i class="fas fa-arrow-right"></i>
            <input class="form-control form-control-sm e-to" style="max-width:120px" placeholder="to" value="${data.to ?? ''}">
            <input class="form-control form-control-sm e-branch" style="max-width:100px" placeholder="branch (opc)" value="${data.branch ?? ''}">
            <button type="button" class="btn btn-sm btn-outline-danger e-remove"><i class="fas fa-trash"></i></button>
        </div>`);
    }

    function openModal(data = null) {
        $('#flowId').val(data?.id ?? '');
        $('#flowModalTitle').text(data ? 'Editar flujo' : 'Nuevo flujo');
        $('#flowName').val(data?.name ?? '');
        $('#flowDescription').val(data?.description ?? '');
        $('#flowActive').val(data?.is_active ? '1' : '0');
        $('#flowTriggerType').val(data?.trigger_type ?? 'manual');
        $('#flowTriggerEvent').val(data?.trigger_event ?? '');

        $('#nodesContainer').empty();
        (data?.nodes ?? []).forEach(n => {
            const row = nodeRow(n);
            row.find('.n-remove').on('click', () => row.remove());
            $('#nodesContainer').append(row);
        });

        $('#edgesContainer').empty();
        (data?.edges ?? []).forEach(e => {
            const row = edgeRow(e);
            row.find('.e-remove').on('click', () => row.remove());
            $('#edgesContainer').append(row);
        });

        $('#rawJsonNodes').val(JSON.stringify(data?.nodes ?? [], null, 2));
        $('#rawJsonEdges').val(JSON.stringify(data?.edges ?? [], null, 2));

        $('#flowModal').modal('show');
    }

    $('#btnNewFlow').on('click', () => openModal());

    $('#btnAddNode').on('click', () => {
        const row = nodeRow();
        row.find('.n-remove').on('click', () => row.remove());
        $('#nodesContainer').append(row);
    });

    $('#btnAddEdge').on('click', () => {
        const row = edgeRow();
        row.find('.e-remove').on('click', () => row.remove());
        $('#edgesContainer').append(row);
    });

    $(document).on('click', '[data-action="edit"]', function (e) {
        e.preventDefault();
        const id = $(this).data('id');
        $.get(routes.index, { inbox_id: $('#inboxSelector').val() }).done(r => {
            const flow = r.data.find(x => x.id == id);
            if (flow) openModal(flow);
        });
    });

    $(document).on('click', '[data-action="delete"]', function (e) {
        e.preventDefault();
        if (!confirm('¿Eliminar flujo?')) return;
        $.ajax({ url: routes.destroy($(this).data('id')), method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrf } })
            .done(() => { grid.refresh(); toastr.success('Eliminado.'); })
            .fail(() => toastr.error('Error al eliminar.'));
    });

    $('#btnSaveFlow').on('click', function () {
        let nodes, edges;
        try {
            nodes = JSON.parse($('#rawJsonNodes').val() || '[]');
            edges = JSON.parse($('#rawJsonEdges').val() || '[]');
        } catch (err) {
            // Build from row inputs
            nodes = $('#nodesContainer .node-row').map(function () {
                let cfg = {};
                try { cfg = JSON.parse($(this).find('.n-config').val() || '{}'); } catch {}
                return {
                    id: $(this).find('.n-id').val(),
                    type: $(this).find('.n-type').val(),
                    config: cfg,
                };
            }).get();
            edges = $('#edgesContainer .edge-row').map(function () {
                const branch = $(this).find('.e-branch').val();
                return {
                    from: $(this).find('.e-from').val(),
                    to: $(this).find('.e-to').val(),
                    ...(branch ? { branch } : {}),
                };
            }).get();
        }

        const id = $('#flowId').val();
        const payload = {
            inbox_id: parseInt($('#inboxSelector').val()),
            name: $('#flowName').val().trim(),
            description: $('#flowDescription').val().trim() || null,
            trigger_type: $('#flowTriggerType').val(),
            trigger_event: $('#flowTriggerEvent').val().trim() || null,
            nodes, edges,
            is_active: $('#flowActive').val() === '1',
        };

        const url    = id ? routes.update(id) : routes.store;
        const method = id ? 'PUT' : 'POST';

        $.ajax({ url, method, data: JSON.stringify(payload), contentType: 'application/json', headers: { 'X-CSRF-TOKEN': csrf } })
            .done(() => { $('#flowModal').modal('hide'); grid.refresh(); toastr.success('Flujo guardado.'); })
            .fail(xhr => {
                const errors = xhr.responseJSON?.errors;
                toastr.error(errors ? Object.values(errors).flat().join('<br>') : 'Error al guardar.');
            });
    });
});
</script>
@endpush
