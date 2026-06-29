@extends('layouts.theme')

@section('title', 'Personalización DOM — LiveChat')

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
                <h4><i class="fas fa-paint-brush me-2"></i>Personalización DOM</h4>
                <p>Modifica texto, atributos o clases de elementos según el segmento del visitante</p>
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
                @can('engagement.personalizations.create')
                <button class="btn btn-primary" id="btnNewRule">
                    <i class="fas fa-plus me-1"></i>Nueva regla
                </button>
                @endcan
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div id="personalizationsGrid"></div>
        </div>
    </div>


{{-- Modal --}}
<div class="modal fade" id="personalizationModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="personalizationModalTitle">Nueva regla</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="personalizationId">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label fw-semibold">Nombre <span class="text-danger">*</span></label>
                        <input type="text" id="personalizationName" class="form-control" placeholder="Ej: Botón hot visitors">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Selector CSS <span class="text-danger">*</span></label>
                        <input type="text" id="personalizationSelector" class="form-control" placeholder=".btn-cta, #hero-title, [data-offer]">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Operación</label>
                        <select id="mutationOp" class="form-select">
                            <option value="text">Cambiar texto</option>
                            <option value="attribute">Cambiar atributo</option>
                            <option value="insert_before">Insertar antes</option>
                            <option value="insert_after">Insertar después</option>
                            <option value="class">Añadir/quitar clases</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Estado</label>
                        <select id="personalizationActive" class="form-select">
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>
                    <div class="col-12" id="mutationExtras"></div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Condiciones (opcional)</label>
                        <div id="personalizationConditionBuilder"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer flex-column gap-2">
                <button type="button" class="btn btn-primary w-100" id="btnSaveRule">Guardar regla</button>
                <button type="button" class="btn btn-outline-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script src="/modules/engagement/js/condition-builder.js"></script>
<script>
$(function () {
    const routes = {
        index:     '{{ route('settings.engagement.personalizations.index') }}',
        store:     '{{ route('settings.engagement.personalizations.store') }}',
        update:    (id) => '{{ route('settings.engagement.personalizations.update', ['personalizationRule' => '__ID__']) }}'.replace('__ID__', id),
        destroy:   (id) => '{{ route('settings.engagement.personalizations.destroy', ['personalizationRule' => '__ID__']) }}'.replace('__ID__', id),
        trashed:   '{{ route('settings.engagement.personalizations.trashed') }}',
        restore:   (id) => '{{ route('settings.engagement.personalizations.restore', ['id' => '__ID__']) }}'.replace('__ID__', id),
    };
    const csrf = $('meta[name="csrf-token"]').attr('content');
    let showTrashed = false;

    const grid = $('#personalizationsGrid').dxDataGrid({
        dataSource: {
            load: () => {
                const url = showTrashed ? routes.trashed : routes.index;
                return $.get(url, { inbox_id: getInboxId() }).then(r => r.data);
            },
        },
        columns: [
            { dataField: 'name', caption: 'Nombre' },
            { dataField: 'selector', caption: 'Selector CSS', width: 200 },
            { dataField: 'mutation.op', caption: 'Operación', width: 140 },
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
                    return;
                }
                const wrap = $('<div class="dropdown">');
                wrap.html(`
                  <button class="btn btn-sm btn-light" data-bs-toggle="dropdown"><i class="fas fa-ellipsis-vertical"></i></button>
                  <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="#" data-action="edit" data-id="${info.data.id}">Editar</a></li>
                    <li><a class="dropdown-item text-danger" href="#" data-action="delete" data-id="${info.data.id}">Eliminar</a></li>
                  </ul>`);
                $(el).append(wrap);
              }
            },
        ],
        showBorders: true,
        paging: { pageSize: 20 },
        searchPanel: { visible: true, placeholder: 'Buscar reglas...' },
        noDataText: 'No hay reglas configuradas.',
    }).dxDataGrid('instance');

    function getInboxId() { return $('#inboxSelector').val(); }
    $('#inboxSelector').on('change', () => grid.refresh());

    // ---- Mutation extras ----
    $('#mutationOp').on('change', function () {
        const op = $(this).val();
        let html = '';
        if (op === 'text') {
            html = `<input type="text" id="mutValue" class="form-control" placeholder="Nuevo texto">`;
        } else if (op === 'attribute') {
            html = `<div class="d-flex gap-2">
                <input type="text" id="mutAttrName" class="form-control" placeholder="Nombre atributo (ej: href)">
                <input type="text" id="mutValue" class="form-control" placeholder="Valor">
            </div>`;
        } else if (op === 'insert_before' || op === 'insert_after') {
            html = `<textarea id="mutHtml" class="form-control" rows="3" placeholder="HTML a insertar"></textarea>`;
        } else if (op === 'class') {
            html = `<div class="d-flex gap-2">
                <input type="text" id="mutClassAdd" class="form-control" placeholder="Clases a añadir (espacio)">
                <input type="text" id="mutClassRemove" class="form-control" placeholder="Clases a quitar (espacio)">
            </div>`;
        }
        $('#mutationExtras').html(html);
    });

    function buildMutation() {
        const op = $('#mutationOp').val();
        const m = { op };
        if (op === 'text')           m.value = $('#mutValue').val();
        else if (op === 'attribute') { m.name = $('#mutAttrName').val(); m.value = $('#mutValue').val(); }
        else if (op === 'insert_before' || op === 'insert_after') m.html = $('#mutHtml').val();
        else if (op === 'class') {
            const add = $('#mutClassAdd').val().split(' ').filter(Boolean);
            const rem = $('#mutClassRemove').val().split(' ').filter(Boolean);
            if (add.length) m.add = add;
            if (rem.length) m.remove = rem;
        }
        return m;
    }

    function loadMutation(mut) {
        $('#mutationOp').val(mut.op).trigger('change');
        if (mut.op === 'text')           $('#mutValue').val(mut.value ?? '');
        else if (mut.op === 'attribute') { $('#mutAttrName').val(mut.name ?? ''); $('#mutValue').val(mut.value ?? ''); }
        else if (mut.op === 'insert_before' || mut.op === 'insert_after') $('#mutHtml').val(mut.html ?? '');
        else if (mut.op === 'class') { $('#mutClassAdd').val((mut.add ?? []).join(' ')); $('#mutClassRemove').val((mut.remove ?? []).join(' ')); }
    }

    // ---- Condition Builder ----
    let personalizationBuilder = null;

    function initPersonalizationBuilder(conditions) {
        if (personalizationBuilder) {
            personalizationBuilder.rules = conditions?.rules ?? [];
            personalizationBuilder.operator = conditions?.operator ?? 'AND';
            personalizationBuilder.render();
        } else {
            personalizationBuilder = new ConditionBuilder('#personalizationConditionBuilder', {
                initialRules: conditions?.rules ?? [],
                initialOperator: conditions?.operator ?? 'AND',
                onChange: () => {},
            });
        }
    }

    function buildConditions() {
        const val = personalizationBuilder ? personalizationBuilder.getValue() : { operator: 'AND', rules: [] };
        if (! val.rules.length) return null;
        return {
            operator: val.operator,
            rules: val.rules.map(r => ({ type: r.field, operator: r.operator, value: r.value }))
        };
    }

    function loadConditions(group) {
        initPersonalizationBuilder(group ?? null);
    }

    // ---- Open modal ----
    function openModal(data = null) {
        $('#personalizationId').val(data?.id ?? '');
        $('#personalizationModalTitle').text(data ? 'Editar regla' : 'Nueva regla');
        $('#personalizationName').val(data?.name ?? '');
        $('#personalizationSelector').val(data?.selector ?? '');
        $('#personalizationActive').val(data?.is_active ? '1' : '0');
        loadMutation(data?.mutation ?? { op: 'text' });
        loadConditions(data?.conditions ?? null);
        $('#personalizationModal').modal('show');
    }

    $('#btnNewRule').on('click', () => openModal());

    $(document).on('click', '[data-action="edit"]', function (e) {
        e.preventDefault();
        const id = $(this).data('id');
        $.get(routes.index, { inbox_id: getInboxId() }).done(r => {
            const rule = r.data.find(x => x.id == id);
            if (rule) openModal(rule);
        });
    });

    // ---- Save ----
    $('#btnSaveRule').on('click', function () {
        const id = $('#personalizationId').val();
        const payload = {
            inbox_id: getInboxId(),
            name: $('#personalizationName').val().trim(),
            selector: $('#personalizationSelector').val().trim(),
            is_active: $('#personalizationActive').val() === '1',
            mutation: buildMutation(),
            conditions: buildConditions(),
        };

        const url    = id ? routes.update(id) : routes.store;
        const method = id ? 'PUT' : 'POST';

        $.ajax({
            url, method,
            data: JSON.stringify(payload),
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': csrf },
        }).done(() => {
            $('#personalizationModal').modal('hide');
            grid.refresh();
            toastr.success('Regla guardada correctamente.');
        }).fail(xhr => {
            const errors = xhr.responseJSON?.errors;
            if (errors) {
                toastr.error(Object.values(errors).flat().join('<br>'));
            } else {
                toastr.error('Error al guardar la regla.');
            }
        });
    });

    // ---- Delete ----
    $(document).on('click', '[data-action="delete"]', function (e) {
        e.preventDefault();
        const id = $(this).data('id');
        if (!confirm('¿Eliminar esta regla?')) return;
        $.ajax({
            url: routes.destroy(id), method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrf },
        }).done(() => {
            grid.refresh();
            toastr.success('Regla eliminada.');
        }).fail(() => toastr.error('No se pudo eliminar la regla.'));
    });

    $(document).on('click', '[data-action="restore"]', function (e) {
        e.preventDefault();
        const id = $(this).data('id');
        $.ajax({
            url: routes.restore(id), method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf },
        }).done(() => {
            grid.refresh();
            toastr.success('Regla restaurada.');
        }).fail(() => toastr.error('No se pudo restaurar la regla.'));
    });

    $('#btnToggleTrashed').on('click', function () {
        showTrashed = !showTrashed;
        $('#trashLabel').text(showTrashed ? 'Activos' : 'Papelera');
        $(this).toggleClass('btn-outline-secondary btn-secondary');
        grid.refresh();
    });

    // Init mutation extras on load
    $('#mutationOp').trigger('change');
});
</script>
@endpush
