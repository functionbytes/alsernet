@extends('layouts.theme')

@section('title', 'Segmentos avanzados — Engagement')

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
</style>
@endpush

@section('content')

    <div class="lc-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h4><i class="fas fa-users me-2"></i>Segmentos avanzados</h4>
                <p>Crea segmentos de visitantes combinando condiciones de eventos, score, geolocalización y atributos</p>
            </div>
            <a href="{{ route('settings.engagement.index') }}" class="btn btn-light btn-sm">
                <i class="fas fa-arrow-left me-1"></i>Volver a ajustes
            </a>
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
            <div class="ms-auto d-flex gap-2">
                <button class="btn btn-outline-secondary" id="btnToggleTrashed">
                    <i class="fas fa-trash me-1"></i><span id="trashLabel">Papelera</span>
                </button>
                @can('engagement.segments.create')
                <button class="btn btn-primary" id="btnNewSegment">
                    <i class="fas fa-plus me-1"></i>Nuevo segmento
                </button>
                @endcan
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div id="segmentsGrid"></div>
        </div>
    </div>


{{-- Modal --}}
<div class="modal fade" id="segmentModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="segmentModalTitle">Nuevo segmento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="segmentId">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label fw-semibold">Nombre <span class="text-danger">*</span></label>
                        <input type="text" id="segmentName" class="form-control" placeholder="Ej: Visitantes hot">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Descripción</label>
                        <input type="text" id="segmentDescription" class="form-control" placeholder="Opcional">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Estado</label>
                        <select id="segmentActive" class="form-select">
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Condiciones</label>
                        <div id="conditionBuilder"></div>
                        <input type="hidden" id="segmentConditions">
                    </div>
                </div>
            </div>
            <div class="modal-footer flex-column gap-2">
                <button type="button" class="btn btn-primary w-100" id="btnSaveSegment">Guardar segmento</button>
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
        index:     '{{ route('settings.engagement.segments.index') }}',
        store:     '{{ route('settings.engagement.segments.store') }}',
        update:    (id) => '{{ route('settings.engagement.segments.update', ['segment' => '__ID__']) }}'.replace('__ID__', id),
        destroy:   (id) => '{{ route('settings.engagement.segments.destroy', ['segment' => '__ID__']) }}'.replace('__ID__', id),
        trashed:   '{{ route('settings.engagement.segments.trashed') }}',
        restore:   (id) => '{{ route('settings.engagement.segments.restore', ['id' => '__ID__']) }}'.replace('__ID__', id),
    };
    const csrf = $('meta[name="csrf-token"]').attr('content');
    let showTrashed = false;

    let builder = null;

    const grid = $('#segmentsGrid').dxDataGrid({
        dataSource: {
            load: () => {
                const url = showTrashed ? routes.trashed : routes.index;
                return $.get(url, { inbox_id: getInboxId() }).then(r => r.data);
            },
        },
        columns: [
            { dataField: 'name', caption: 'Nombre' },
            { dataField: 'description', caption: 'Descripción', width: 240 },
            { dataField: 'conditions', caption: 'Condiciones', width: 160,
              cellTemplate: (el, info) => {
                  const op = info.value?.operator || 'AND';
                  const count = info.value?.rules?.length || 0;
                  $(el).html(`<span class="badge bg-info">${op}</span> ${count} reglas`);
              }
            },
            { dataField: 'is_active', caption: 'Estado', width: 100,
              cellTemplate: (el, info) => $(el).html(
                info.value
                  ? '<span class="badge bg-success">Activo</span>'
                  : '<span class="badge bg-secondary">Inactivo</span>'
              ) },
            { caption: 'Acciones', width: 120, alignment: 'center',
              cellTemplate: (el, info) => {
                if (showTrashed) {
                    $(el).html(`<button class="btn btn-sm btn-outline-success" data-action="restore" data-id="${info.data.id}"><i class="fas fa-undo"></i> Restaurar</button>`);
                } else {
                    const wrap = $('<div class="dropdown">');
                    wrap.html(`
                      <button class="btn btn-sm btn-light" data-bs-toggle="dropdown"><i class="fas fa-ellipsis-vertical"></i></button>
                      <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="#" data-action="edit" data-id="${info.data.id}">Editar</a></li>
                        <li><a class="dropdown-item text-danger" href="#" data-action="delete" data-id="${info.data.id}">Eliminar</a></li>
                      </ul>`);
                    $(el).append(wrap);
                }
              }
            },
        ],
        showBorders: true,
        paging: { pageSize: 20 },
        searchPanel: { visible: true, placeholder: 'Buscar segmentos...' },
        noDataText: 'No hay segmentos configurados.',
    }).dxDataGrid('instance');

    function getInboxId() { return $('#inboxSelector').val(); }
    $('#inboxSelector').on('change', () => grid.refresh());

    function openModal(data = null) {
        $('#segmentId').val(data?.id ?? '');
        $('#segmentModalTitle').text(data ? 'Editar segmento' : 'Nuevo segmento');
        $('#segmentName').val(data?.name ?? '');
        $('#segmentDescription').val(data?.description ?? '');
        $('#segmentActive').val(data?.is_active ? '1' : '0');

        const conditions = data?.conditions ?? { operator: 'AND', rules: [] };
        if (builder) {
            builder.rules = conditions.rules;
            builder.operator = conditions.operator;
            builder.render();
        } else {
            builder = new ConditionBuilder('#conditionBuilder', {
                initialRules: conditions.rules,
                initialOperator: conditions.operator,
                onChange: (val) => $('#segmentConditions').val(JSON.stringify(val)),
            });
        }
        $('#segmentConditions').val(JSON.stringify(builder.getValue()));
        $('#segmentModal').modal('show');
    }

    $('#btnNewSegment').on('click', () => openModal());

    $(document).on('click', '[data-action="edit"]', function (e) {
        e.preventDefault();
        const id = $(this).data('id');
        $.get(routes.index, { inbox_id: getInboxId() }).done(r => {
            const item = r.data.find(x => x.id == id);
            if (item) openModal(item);
        });
    });

    $('#btnSaveSegment').on('click', function () {
        const id = $('#segmentId').val();
        const conditions = builder ? builder.getValue() : JSON.parse($('#segmentConditions').val() || '{"operator":"AND","rules":[]}');
        const payload = {
            inbox_id: parseInt(getInboxId()),
            name: $('#segmentName').val().trim(),
            description: $('#segmentDescription').val().trim() || null,
            is_active: $('#segmentActive').val() === '1',
            conditions: conditions,
        };

        const url    = id ? routes.update(id) : routes.store;
        const method = id ? 'PUT' : 'POST';

        $.ajax({
            url, method,
            data: JSON.stringify(payload),
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': csrf },
        }).done(() => {
            $('#segmentModal').modal('hide');
            grid.refresh();
            toastr.success('Segmento guardado correctamente.');
        }).fail(xhr => {
            const errors = xhr.responseJSON?.errors;
            if (errors) {
                toastr.error(Object.values(errors).flat().join('<br>'));
            } else {
                toastr.error('Error al guardar el segmento.');
            }
        });
    });

    $(document).on('click', '[data-action="delete"]', function (e) {
        e.preventDefault();
        const id = $(this).data('id');
        if (!confirm('¿Eliminar este segmento?')) return;
        $.ajax({
            url: routes.destroy(id), method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrf },
        }).done(() => {
            grid.refresh();
            toastr.success('Segmento eliminado.');
        }).fail(() => toastr.error('No se pudo eliminar el segmento.'));
    });

    $(document).on('click', '[data-action="restore"]', function (e) {
        e.preventDefault();
        const id = $(this).data('id');
        $.ajax({
            url: routes.restore(id), method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf },
        }).done(() => {
            grid.refresh();
            toastr.success('Segmento restaurado.');
        }).fail(() => toastr.error('No se pudo restaurar el segmento.'));
    });

    $('#btnToggleTrashed').on('click', function () {
        showTrashed = !showTrashed;
        $('#trashLabel').text(showTrashed ? 'Activos' : 'Papelera');
        $(this).toggleClass('btn-outline-secondary btn-secondary');
        grid.refresh();
    });
});
</script>
@endpush
