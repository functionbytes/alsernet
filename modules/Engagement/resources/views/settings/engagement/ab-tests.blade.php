@extends('layouts.theme')

@section('title', 'AB Tests — Engagement')

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
                <h4><i class="fas fa-flask me-2"></i>AB Tests</h4>
                <p>Crea tests A/B para reglas de activación, personalizaciones y contenido</p>
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
                @can('engagement.ab_tests.create')
                <button class="btn btn-primary" id="btnNewTest">
                    <i class="fas fa-plus me-1"></i>Nuevo test
                </button>
                @endcan
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div id="abtestsGrid"></div>
        </div>
    </div>


{{-- Edit Modal --}}
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editModalTitle">Editar test</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="editTestId">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label fw-semibold">Nombre</label>
                        <input type="text" id="editTestName" class="form-control">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Descripción</label>
                        <input type="text" id="editTestDescription" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Muestra</label>
                        <input type="number" id="editTestSample" class="form-control" min="10">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Variantes</label>
                        <div id="editVariantsContainer"></div>
                        <button type="button" class="btn btn-sm btn-outline-secondary mt-2" id="btnAddVariant">
                            <i class="fas fa-plus"></i> Agregar variante
                        </button>
                    </div>
                </div>
            </div>
            <div class="modal-footer flex-column gap-2">
                <button type="button" class="btn btn-primary w-100" id="btnSaveEdit">Guardar cambios</button>
                <button type="button" class="btn btn-outline-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </div>
</div>

{{-- Stats Modal --}}
<div class="modal fade" id="statsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="statsModalTitle">Resultados</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr><th>Variante</th><th>Peso</th><th>Impresiones</th><th>Conversiones</th><th>Tasa</th></tr>
                        </thead>
                        <tbody id="statsModalBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
$(function () {
    const routes = {
        index:     '{{ route('settings.engagement.ab-tests.index') }}',
        store:     '{{ route('settings.engagement.ab-tests.store') }}',
        update:    (id) => '{{ route('settings.engagement.ab-tests.update', ['abTest' => '__ID__']) }}'.replace('__ID__', id),
        show:      (id) => '{{ route('settings.engagement.ab-tests.show', ['abTest' => '__ID__']) }}'.replace('__ID__', id),
        start:     (id) => `/panel/settings/engagement/ab-tests/${id}/start`,
        pause:     (id) => `/panel/settings/engagement/ab-tests/${id}/pause`,
        destroy:   (id) => '{{ route('settings.engagement.ab-tests.destroy', ['abTest' => '__ID__']) }}'.replace('__ID__', id),
        trashed:   '{{ route('settings.engagement.ab-tests.trashed') }}',
        restore:   (id) => '{{ route('settings.engagement.ab-tests.restore', ['id' => '__ID__']) }}'.replace('__ID__', id),
    };
    const csrf = $('meta[name="csrf-token"]').attr('content');
    let showTrashed = false;
    const canCreate = {{ auth()->user()->can('engagement.ab_tests.create') ? 'true' : 'false' }};
    const canUpdate = {{ auth()->user()->can('engagement.ab_tests.update') ? 'true' : 'false' }};
    const canDelete = {{ auth()->user()->can('engagement.ab_tests.delete') ? 'true' : 'false' }};

    const grid = $('#abtestsGrid').dxDataGrid({
        dataSource: {
            load: () => {
                const url = showTrashed ? routes.trashed : routes.index;
                return $.get(url, { inbox_id: getInboxId() }).then(r => r.data);
            },
        },
        columns: [
            { dataField: 'name', caption: 'Nombre' },
            { dataField: 'status', caption: 'Estado', width: 130,
              cellTemplate: (el, info) => {
                  const map = { draft: 'bg-secondary', running: 'bg-success', paused: 'bg-warning text-dark', completed: 'bg-info' };
                  const label = { draft: 'Borrador', running: 'En ejecución', paused: 'Pausado', completed: 'Completado' };
                  $(el).html(`<span class="badge ${map[info.value] || 'bg-secondary'}">${label[info.value] || info.value}</span>`);
              }
            },
            { dataField: 'variants_count', caption: 'Variantes', width: 100 },
            { dataField: 'started_at', caption: 'Inicio', width: 120,
              cellTemplate: (el, info) => $(el).text(info.value ? new Date(info.value).toLocaleDateString() : '—') },
            { dataField: 'sample_size', caption: 'Muestra', width: 110 },
            { caption: 'Acciones', width: 180, alignment: 'center',
              cellTemplate: (el, info) => {
                const t = info.data;
                if (showTrashed) {
                    $(el).html(`<button class="btn btn-sm btn-outline-success" onclick="restoreTest(${t.id})"><i class="fas fa-undo"></i> Restaurar</button>`);
                    return;
                }
                let btns = '';
                if (canUpdate) {
                    if (t.status === 'draft' || t.status === 'paused') {
                        btns += `<button class="btn btn-sm btn-success me-1" onclick="startTest(${t.id})"><i class="fas fa-play"></i></button>`;
                    }
                    if (t.status === 'running') {
                        btns += `<button class="btn btn-sm btn-warning me-1" onclick="pauseTest(${t.id})"><i class="fas fa-pause"></i></button>`;
                    }
                    btns += `<button class="btn btn-sm btn-primary me-1" onclick="editTest(${t.id})"><i class="fas fa-edit"></i></button>`;
                }
                btns += `<button class="btn btn-sm btn-info me-1" onclick="showStats(${t.id})"><i class="fas fa-chart-bar"></i></button>`;
                if (canDelete) {
                    btns += `<button class="btn btn-sm btn-danger" onclick="deleteTest(${t.id})"><i class="fas fa-trash"></i></button>`;
                }
                $(el).html(btns);
              }
            },
        ],
        showBorders: true,
        paging: { pageSize: 20 },
        searchPanel: { visible: true, placeholder: 'Buscar tests...' },
        noDataText: 'No hay tests configurados.',
    }).dxDataGrid('instance');

    function getInboxId() { return $('#inboxSelector').val(); }
    $('#inboxSelector').on('change', () => grid.refresh());

    window.startTest = function (id) {
        $.ajax({ url: routes.start(id), method: 'POST', headers: { 'X-CSRF-TOKEN': csrf } }).done(() => grid.refresh());
    };
    window.pauseTest = function (id) {
        $.ajax({ url: routes.pause(id), method: 'POST', headers: { 'X-CSRF-TOKEN': csrf } }).done(() => grid.refresh());
    };
    window.deleteTest = function (id) {
        if (!confirm('¿Eliminar este test?')) return;
        $.ajax({ url: routes.destroy(id), method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrf } }).done(() => grid.refresh());
    };
    window.editTest = function (id) {
        $.get(routes.show(id)).done(r => {
            const t = r.data;
            $('#editTestId').val(t.id);
            $('#editModalTitle').text('Editar: ' + t.name);
            $('#editTestName').val(t.name);
            $('#editTestDescription').val(t.description ?? '');
            $('#editTestSample').val(t.sample_size ?? 100);
            $('#editVariantsContainer').empty();
            (t.variants ?? []).forEach((v, idx) => addVariantRow(idx, v));
            new bootstrap.Modal(document.getElementById('editModal')).show();
        });
    };

    function addVariantRow(idx, v = {}) {
        const html = `<div class="d-flex gap-2 align-items-center mb-2 variant-row" data-idx="${idx}">
            <input type="text" class="form-control form-control-sm v-name" placeholder="Nombre" value="${v.name ?? ''}">
            <input type="number" class="form-control form-control-sm v-weight" placeholder="Peso" value="${v.weight ?? 50}" style="max-width:80px">
            <input type="text" class="form-control form-control-sm v-msg" placeholder="Mensaje" value="${v.config?.message ?? ''}">
            <button type="button" class="btn btn-sm btn-outline-danger v-remove"><i class="fas fa-times"></i></button>
        </div>`;
        const row = $(html);
        row.find('.v-remove').on('click', () => row.remove());
        $('#editVariantsContainer').append(row);
    }

    $('#btnAddVariant').on('click', () => addVariantRow($('.variant-row').length));

    $('#btnSaveEdit').on('click', function () {
        const id = $('#editTestId').val();
        const variants = [];
        $('.variant-row').each(function () {
            variants.push({
                name: $(this).find('.v-name').val(),
                weight: parseInt($(this).find('.v-weight').val()) || 50,
                config: { message: $(this).find('.v-msg').val() },
            });
        });
        const payload = {
            name: $('#editTestName').val().trim(),
            description: $('#editTestDescription').val().trim() || null,
            sample_size: parseInt($('#editTestSample').val()) || 100,
            variants: variants,
        };
        $.ajax({
            url: routes.update(id), method: 'PUT',
            data: JSON.stringify(payload),
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': csrf },
        }).done(() => {
            bootstrap.Modal.getInstance(document.getElementById('editModal')).hide();
            grid.refresh();
            toastr.success('Test actualizado.');
        }).fail(xhr => {
            toastr.error(xhr.responseJSON?.message || 'Error al actualizar.');
        });
    });

    window.showStats = function (id) {
        $.get(routes.show(id)).done(r => {
            const stats = r.data.variant_stats.map(v => `
                <tr>
                    <td>${escapeHtml(v.name)}</td>
                    <td>${v.weight}%</td>
                    <td>${v.impressions.toLocaleString()}</td>
                    <td>${v.conversions.toLocaleString()}</td>
                    <td><strong>${v.conversion_rate}%</strong></td>
                </tr>
            `).join('');
            $('#statsModalTitle').text('Resultados: ' + r.data.name);
            $('#statsModalBody').html(stats);
            new bootstrap.Modal(document.getElementById('statsModal')).show();
        });
    };

    function escapeHtml(t) { const d = document.createElement('div'); d.textContent = t; return d.innerHTML; }

    window.restoreTest = function (id) {
        $.ajax({ url: routes.restore(id), method: 'POST', headers: { 'X-CSRF-TOKEN': csrf } })
            .done(() => { grid.refresh(); toastr.success('Test restaurado.'); })
            .fail(() => toastr.error('No se pudo restaurar el test.'));
    };

    $('#btnToggleTrashed').on('click', function () {
        showTrashed = !showTrashed;
        $('#trashLabel').text(showTrashed ? 'Activos' : 'Papelera');
        $(this).toggleClass('btn-outline-secondary btn-secondary');
        grid.refresh();
    });

    $('#btnNewTest').on('click', function () {
        const name = prompt('Nombre del test:'); if (!name) return;
        const variants = [];
        for (let i = 1; i <= 2; i++) {
            const vName = prompt(`Nombre variante ${i}:`); if (!vName) return;
            variants.push({ name: vName, config: { message: `Mensaje ${i}` }, weight: 50 });
        }
        $.ajax({
            url: routes.store, method: 'POST',
            data: JSON.stringify({ name, variants, inbox_id: parseInt(getInboxId()) }),
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': csrf },
        }).done(() => grid.refresh());
    });
});
</script>
@endpush
