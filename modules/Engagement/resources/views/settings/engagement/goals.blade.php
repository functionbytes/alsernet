@extends('layouts.theme')
@section('title', 'Objetivos de conversión — LiveChat')

@push('css')
<style>
    .lc-header { background: linear-gradient(135deg, #b10100 0%, #7b0000 100%); color: white; padding: 1.5rem 2rem; border-radius: 12px; margin-bottom: 1.5rem; }
    .lc-header h4 { font-weight: 700; margin: 0 0 .25rem; }
    .lc-header p  { opacity: .9; margin: 0; font-size: .9rem; }
    .funnel-step { padding: .75rem 1rem; border-left: 4px solid #b10100; background: #f9f9f9; margin-bottom: .25rem; border-radius: 0 6px 6px 0; }
    .funnel-step .step-name { font-weight: 600; }
    .funnel-step .step-stats { font-size: .85rem; color: #666; }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">
    <div class="lc-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h4><i class="fas fa-bullseye me-2"></i>Objetivos de conversión</h4>
                <p>Define qué cuenta como conversión y visualiza el funnel</p>
            </div>
            <a href="{{ route('settings.helpdesk-livechat.index') }}" class="btn btn-light btn-sm"><i class="fas fa-arrow-left me-1"></i>Volver</a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body d-flex align-items-center gap-3 flex-wrap">
            <label class="fw-semibold mb-0">Inbox:</label>
            <select id="inboxSelector" class="form-select w-auto">
                @foreach($inboxes as $inbox)<option value="{{ $inbox->id }}">{{ $inbox->name }}</option>@endforeach
            </select>
            @can('engagement.goals.create')
            <button class="btn btn-primary ms-auto" id="btnNew"><i class="fas fa-plus me-1"></i>Nuevo objetivo</button>
            @endcan
        </div>
    </div>

    <div class="card"><div class="card-body p-0"><div id="goalsGrid"></div></div></div>
</div>

<div class="modal fade" id="goalModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="modalTitle">Nuevo objetivo</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" id="goalId">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label fw-semibold">Nombre <span class="text-danger">*</span></label>
                        <input type="text" id="goalName" class="form-control" placeholder="Ej: Compra completada">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Estado</label>
                        <select id="goalActive" class="form-select"><option value="1">Activo</option><option value="0">Inactivo</option></select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Tipo <span class="text-danger">*</span></label>
                        <select id="goalType" class="form-select">
                            <option value="event">Evento específico</option>
                            <option value="url_visit">Visita a URL</option>
                            <option value="segment_change">Cambio de segmento</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Valor monetario</label>
                        <input type="number" step="0.01" id="goalValue" class="form-control" value="0">
                    </div>
                    <div class="col-12" id="extraEvent">
                        <label class="form-label fw-semibold">Nombre del evento</label>
                        <input type="text" id="goalEventName" class="form-control" placeholder="ej: purchase">
                    </div>
                    <div class="col-12 d-none" id="extraUrl">
                        <label class="form-label fw-semibold">Patrón de URL (substring)</label>
                        <input type="text" id="goalUrlPattern" class="form-control" placeholder="ej: /thank-you">
                    </div>
                    <div class="col-12 d-none" id="extraSegment">
                        <label class="form-label fw-semibold">Segmento objetivo</label>
                        <select id="goalSegment" class="form-select"><option value="cold">Cold</option><option value="warm">Warm</option><option value="hot">Hot</option></select>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Pasos del funnel (opcional)</label>
                        <input type="text" id="goalSteps" class="form-control" placeholder="evento1, evento2, evento3 (separados por coma)">
                        <small class="text-muted">Permite visualizar la conversión paso a paso.</small>
                    </div>
                </div>
            </div>
            <div class="modal-footer flex-column gap-2">
                <button class="btn btn-primary w-100" id="btnSave">Guardar</button>
                <button class="btn btn-outline-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="funnelModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Análisis de funnel</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body" id="funnelBody"></div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
$(function () {
    const routes = {
        index:   '{{ route('settings.engagement.goals.index') }}',
        store:   '{{ route('settings.engagement.goals.store') }}',
        update:  (id) => '{{ route('settings.engagement.goals.update', ['conversionGoal' => '__ID__']) }}'.replace('__ID__', id),
        destroy: (id) => '{{ route('settings.engagement.goals.destroy', ['conversionGoal' => '__ID__']) }}'.replace('__ID__', id),
        funnel:  (id) => '{{ route('settings.engagement.goals.funnel', ['conversionGoal' => '__ID__']) }}'.replace('__ID__', id),
    };
    const csrf = $('meta[name="csrf-token"]').attr('content');

    const grid = $('#goalsGrid').dxDataGrid({
        dataSource: { load: () => $.get(routes.index, { inbox_id: $('#inboxSelector').val() }).then(r => r.data) },
        columns: [
            { dataField: 'name', caption: 'Nombre' },
            { dataField: 'type', caption: 'Tipo', width: 140 },
            { dataField: 'value', caption: 'Valor €', width: 100 },
            { dataField: 'is_active', caption: 'Estado', width: 100,
              cellTemplate: (el, info) => $(el).html(info.value ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-secondary">Inactivo</span>') },
            { caption: 'Acciones', width: 140, alignment: 'center',
              cellTemplate: (el, info) => $(el).html(`
                <div class="dropdown">
                  <button class="btn btn-sm btn-light" data-bs-toggle="dropdown"><i class="fas fa-ellipsis-vertical"></i></button>
                  <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="#" data-action="funnel" data-id="${info.data.id}">Ver funnel</a></li>
                    <li><a class="dropdown-item" href="#" data-action="edit" data-id="${info.data.id}">Editar</a></li>
                    <li><a class="dropdown-item" href="#" data-action="delete" data-id="${info.data.id}">Eliminar</a></li>
                  </ul>
                </div>`) },
        ],
        showBorders: true, paging: { pageSize: 20 }, searchPanel: { visible: true },
        noDataText: 'No hay objetivos.',
    }).dxDataGrid('instance');

    $('#inboxSelector').on('change', () => grid.refresh());

    $('#goalType').on('change', function () {
        const t = $(this).val();
        $('#extraEvent').toggleClass('d-none', t !== 'event');
        $('#extraUrl').toggleClass('d-none', t !== 'url_visit');
        $('#extraSegment').toggleClass('d-none', t !== 'segment_change');
    });

    function openModal(data = null) {
        $('#goalId').val(data?.id ?? '');
        $('#modalTitle').text(data ? 'Editar objetivo' : 'Nuevo objetivo');
        $('#goalName').val(data?.name ?? '');
        $('#goalType').val(data?.type ?? 'event').trigger('change');
        $('#goalEventName').val(data?.event_name ?? '');
        $('#goalUrlPattern').val(data?.url_pattern ?? '');
        $('#goalSegment').val(data?.target_segment ?? 'hot');
        $('#goalValue').val(data?.value ?? 0);
        $('#goalSteps').val((data?.funnel_steps ?? []).join(', '));
        $('#goalActive').val(data?.is_active ? '1' : '0');
        $('#goalModal').modal('show');
    }

    $('#btnNew').on('click', () => openModal());

    $(document).on('click', '[data-action="edit"]', function (e) {
        e.preventDefault();
        const id = $(this).data('id');
        $.get(routes.index, { inbox_id: $('#inboxSelector').val() }).done(r => {
            const g = r.data.find(x => x.id == id); if (g) openModal(g);
        });
    });

    $(document).on('click', '[data-action="delete"]', function (e) {
        e.preventDefault();
        if (!confirm('¿Eliminar?')) return;
        $.ajax({ url: routes.destroy($(this).data('id')), method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrf } })
            .done(() => { grid.refresh(); toastr.success('Eliminado.'); })
            .fail(() => toastr.error('Error.'));
    });

    $(document).on('click', '[data-action="funnel"]', function (e) {
        e.preventDefault();
        const id = $(this).data('id');
        $.get(routes.funnel(id)).done(r => {
            const html = r.data.length === 0
                ? '<p class="text-muted text-center py-3">Este objetivo no tiene pasos de funnel.</p>'
                : r.data.map(s => `
                    <div class="funnel-step">
                        <div class="step-name">${s.step}</div>
                        <div class="step-stats">${s.visitors} visitantes · ${s.drop_pct}% drop</div>
                    </div>
                `).join('');
            $('#funnelBody').html(html);
            $('#funnelModal').modal('show');
        });
    });

    $('#btnSave').on('click', function () {
        const steps = $('#goalSteps').val().split(',').map(s => s.trim()).filter(Boolean);
        const payload = {
            inbox_id: parseInt($('#inboxSelector').val()),
            name: $('#goalName').val().trim(),
            type: $('#goalType').val(),
            event_name: $('#goalEventName').val().trim() || null,
            url_pattern: $('#goalUrlPattern').val().trim() || null,
            target_segment: $('#goalType').val() === 'segment_change' ? $('#goalSegment').val() : null,
            value: parseFloat($('#goalValue').val()) || 0,
            funnel_steps: steps,
            is_active: $('#goalActive').val() === '1',
        };
        const id = $('#goalId').val();
        const url = id ? routes.update(id) : routes.store;
        const method = id ? 'PUT' : 'POST';

        $.ajax({ url, method, data: JSON.stringify(payload), contentType: 'application/json', headers: { 'X-CSRF-TOKEN': csrf } })
            .done(() => { $('#goalModal').modal('hide'); grid.refresh(); toastr.success('Guardado.'); })
            .fail(xhr => toastr.error(xhr.responseJSON?.message ?? 'Error.'));
    });
});
</script>
@endpush
