@extends('layouts.theme')

@section('title', 'Reglas de activación — LiveChat')

@push('css')
<style>
    .lc-header {
        background: linear-gradient(135deg, #b10100 0%, #7b0000 100%);
        color: white;
        padding: 1.5rem 2rem;
        border-radius: 12px;
        margin-bottom: 1.5rem;
    }
    .lc-header h4 { font-weight: 700; margin: 0 0 .25rem; }
    .lc-header p  { opacity: .9; margin: 0; font-size: .9rem; }
    .condition-row + .condition-row { margin-top: .5rem; }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">

    {{-- Header --}}
    <div class="lc-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h4><i class="fas fa-bolt me-2"></i>Reglas de activación</h4>
                <p>Define cuándo abrir el chat, mostrar banners o redirigir visitantes según su comportamiento</p>
            </div>
            <a href="{{ route('settings.helpdesk-livechat.index') }}" class="btn btn-light btn-sm">
                <i class="fas fa-arrow-left me-1"></i>Volver a ajustes
            </a>
        </div>
    </div>

    {{-- Inbox selector --}}
    <div class="card mb-3">
        <div class="card-body d-flex align-items-center gap-3 flex-wrap">
            <label class="fw-semibold mb-0">Inbox:</label>
            <select id="inboxSelector" class="form-select w-auto">
                @foreach($inboxes as $inbox)
                    <option value="{{ $inbox->id }}">{{ $inbox->name }}</option>
                @endforeach
            </select>
            @can('engagement.triggers.create')
            <button class="btn btn-primary ms-auto" id="btnNewTrigger">
                <i class="fas fa-plus me-1"></i>Nueva regla
            </button>
            @endcan
        </div>
    </div>

    {{-- DataGrid --}}
    <div class="card">
        <div class="card-body p-0">
            <div id="triggersGrid"></div>
        </div>
    </div>

</div>

{{-- Modal --}}
<div class="modal fade" id="triggerModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="triggerModalTitle">Nueva regla</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="triggerRuleId">

                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label fw-semibold">Nombre <span class="text-danger">*</span></label>
                        <input type="text" id="triggerName" class="form-control" placeholder="Ej: Abrir chat después de 30 s">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Descripción</label>
                        <input type="text" id="triggerDescription" class="form-control" placeholder="Opcional">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Prioridad</label>
                        <input type="number" id="triggerPriority" class="form-control" value="50" min="0" max="100">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Disparos por sesión</label>
                        <input type="number" id="triggerFires" class="form-control" value="1" min="1">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Estado</label>
                        <select id="triggerActive" class="form-select">
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>

                    {{-- Conditions --}}
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label fw-semibold mb-0">Condiciones</label>
                            <div class="d-flex gap-2 align-items-center">
                                <select id="conditionsOperator" class="form-select form-select-sm w-auto">
                                    <option value="AND">Todas (AND)</option>
                                    <option value="OR">Cualquiera (OR)</option>
                                </select>
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="btnAddCondition">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                        <div id="conditionsContainer"></div>
                    </div>

                    {{-- Action --}}
                    <div class="col-12">
                        <label class="form-label fw-semibold">Acción</label>
                        <select id="actionType" class="form-select mb-2">
                            <option value="open_chat">Abrir chat</option>
                            <option value="show_banner">Mostrar banner</option>
                            <option value="redirect">Redirigir</option>
                            <option value="callback">Callback JS</option>
                        </select>
                        <div id="actionExtras"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer flex-column gap-2">
                <button type="button" class="btn btn-primary w-100" id="btnSaveTrigger">Guardar regla</button>
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
        index:   '{{ route('settings.engagement.triggers.index') }}',
        store:   '{{ route('settings.engagement.triggers.store') }}',
        update:  (id) => '{{ route('settings.engagement.triggers.update', ['triggerRule' => '__ID__']) }}'.replace('__ID__', id),
        destroy: (id) => '{{ route('settings.engagement.triggers.destroy', ['triggerRule' => '__ID__']) }}'.replace('__ID__', id),
    };
    const csrf = $('meta[name="csrf-token"]').attr('content');

    // ---- DataGrid ----
    const grid = $('#triggersGrid').dxDataGrid({
        dataSource: {
            load: () => $.get(routes.index, { inbox_id: getInboxId() }).then(r => r.data),
        },
        columns: [
            { dataField: 'name', caption: 'Nombre' },
            { dataField: 'priority', caption: 'Prioridad', width: 100 },
            { dataField: 'fires_per_session', caption: 'Disparos', width: 100 },
            { dataField: 'action.type', caption: 'Acción', width: 160 },
            { dataField: 'is_active', caption: 'Estado', width: 100,
              cellTemplate: (el, info) => $(el).html(
                info.value
                  ? '<span class="badge bg-success">Activo</span>'
                  : '<span class="badge bg-secondary">Inactivo</span>'
              ) },
            { caption: 'Acciones', width: 120, alignment: 'center',
              cellTemplate: (el, info) => {
                const wrap = $('<div class="dropdown">');
                wrap.html(`
                  <button class="btn btn-sm btn-light" data-bs-toggle="dropdown"><i class="fas fa-ellipsis-vertical"></i></button>
                  <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="#" data-action="edit" data-id="${info.data.id}">Editar</a></li>
                    <li><a class="dropdown-item" href="#" data-action="delete" data-id="${info.data.id}">Eliminar</a></li>
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

    function getInboxId() {
        return $('#inboxSelector').val();
    }

    $('#inboxSelector').on('change', () => grid.refresh());

    // ---- Conditions builder ----
    function conditionRow(data = {}) {
        const types = ['time_on_page', 'scroll', 'score', 'segment', 'url', 'context'];
        const ops   = ['>=', '>', '<=', '<', '==', '!=', 'contains'];
        return $(`<div class="condition-row d-flex gap-2 align-items-center flex-wrap">
            <select class="form-select form-select-sm w-auto cond-type">
                ${types.map(t => `<option ${data.type===t?'selected':''}>${t}</option>`).join('')}
            </select>
            <select class="form-select form-select-sm w-auto cond-op">
                ${ops.map(o => `<option ${data.operator===o?'selected':''}>${o}</option>`).join('')}
            </select>
            <input type="text" class="form-control form-control-sm cond-value" style="max-width:140px" placeholder="Valor" value="${data.value ?? ''}">
            <input type="text" class="form-control form-control-sm cond-key" style="max-width:120px" placeholder="Key (ctx)" value="${data.key ?? ''}">
            <button type="button" class="btn btn-sm btn-outline-danger cond-remove"><i class="fas fa-trash"></i></button>
        </div>`);
    }

    $('#btnAddCondition').on('click', () => {
        const row = conditionRow();
        row.find('.cond-remove').on('click', () => row.remove());
        $('#conditionsContainer').append(row);
    });

    function buildConditions() {
        const rules = [];
        $('#conditionsContainer .condition-row').each(function () {
            rules.push({
                type: $(this).find('.cond-type').val(),
                operator: $(this).find('.cond-op').val(),
                value: $(this).find('.cond-value').val(),
                key: $(this).find('.cond-key').val() || undefined,
            });
        });
        return { operator: $('#conditionsOperator').val(), rules };
    }

    function loadConditions(group) {
        $('#conditionsContainer').empty();
        $('#conditionsOperator').val(group.operator ?? 'AND');
        (group.rules ?? []).forEach(r => {
            const row = conditionRow(r);
            row.find('.cond-remove').on('click', () => row.remove());
            $('#conditionsContainer').append(row);
        });
    }

    // ---- Action extras ----
    $('#actionType').on('change', function () {
        const t = $(this).val();
        let html = '';
        if (t === 'show_banner') {
            html = `<input type="text" id="actionSelector" class="form-control mb-2" placeholder="Selector CSS (ej: .hero)">
                    <textarea id="actionHtml" class="form-control" rows="3" placeholder="HTML del banner"></textarea>`;
        } else if (t === 'redirect') {
            html = `<input type="url" id="actionUrl" class="form-control" placeholder="https://...">`;
        } else if (t === 'callback') {
            html = `<input type="text" id="actionName" class="form-control mb-2" placeholder="Nombre de función global">`;
        }
        $('#actionExtras').html(html);
    });

    function buildAction() {
        const type = $('#actionType').val();
        const action = { type };
        if (type === 'show_banner') {
            action.selector = $('#actionSelector').val();
            action.html = $('#actionHtml').val();
        } else if (type === 'redirect') {
            action.url = $('#actionUrl').val();
        } else if (type === 'callback') {
            action.name = $('#actionName').val();
        }
        return action;
    }

    function loadAction(action) {
        $('#actionType').val(action.type).trigger('change');
        if (action.type === 'show_banner') {
            $('#actionSelector').val(action.selector ?? '');
            $('#actionHtml').val(action.html ?? '');
        } else if (action.type === 'redirect') {
            $('#actionUrl').val(action.url ?? '');
        } else if (action.type === 'callback') {
            $('#actionName').val(action.name ?? '');
        }
    }

    // ---- Modal open ----
    function openModal(data = null) {
        $('#triggerRuleId').val(data?.id ?? '');
        $('#triggerModalTitle').text(data ? 'Editar regla' : 'Nueva regla');
        $('#triggerName').val(data?.name ?? '');
        $('#triggerDescription').val(data?.description ?? '');
        $('#triggerPriority').val(data?.priority ?? 50);
        $('#triggerFires').val(data?.fires_per_session ?? 1);
        $('#triggerActive').val(data?.is_active ? '1' : '0');
        loadConditions(data?.conditions ?? { operator: 'AND', rules: [] });
        loadAction(data?.action ?? { type: 'open_chat' });
        $('#triggerModal').modal('show');
    }

    $('#btnNewTrigger').on('click', () => openModal());

    $(document).on('click', '[data-action="edit"]', function (e) {
        e.preventDefault();
        const id = $(this).data('id');
        $.get(routes.index, { inbox_id: getInboxId() }).done(r => {
            const rule = r.data.find(x => x.id == id);
            if (rule) openModal(rule);
        });
    });

    // ---- Save ----
    $('#btnSaveTrigger').on('click', function () {
        const id = $('#triggerRuleId').val();
        const payload = {
            inbox_id: getInboxId(),
            name: $('#triggerName').val().trim(),
            description: $('#triggerDescription').val().trim() || null,
            priority: parseInt($('#triggerPriority').val()),
            fires_per_session: parseInt($('#triggerFires').val()),
            is_active: $('#triggerActive').val() === '1',
            conditions: buildConditions(),
            action: buildAction(),
        };

        const url    = id ? routes.update(id) : routes.store;
        const method = id ? 'PUT' : 'POST';

        $.ajax({
            url, method,
            data: JSON.stringify(payload),
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': csrf },
        }).done(() => {
            $('#triggerModal').modal('hide');
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
});
</script>
@endpush
