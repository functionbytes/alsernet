@extends('layouts.theme')

@section('title', $pageTitle)

@section('page_header')
    @include('core::components.card', ['title' => $pageTitle])
@endsection

@section('content')

    <div class="row">

        {{-- Columna principal --}}
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <h5 class="mb-1">Limpiar datos de prueba</h5>
                    <p class="card-subtitle mb-4">
                        Elimina registros generados durante pruebas de sincronización.
                        Úsalo para dejar el entorno limpio antes de una sincronización real.
                    </p>

                    {{-- Warning --}}
                    <div class="alert alert-success d-flex gap-2 align-items-start mb-4 py-3">
                        <div>
                            <strong>Acción irreversible.</strong>
                            Los registros eliminados no se pueden recuperar.
                        </div>
                    </div>

                    {{-- Cards de grupos --}}
                    <label class="fw-semibold small text-uppercase text-muted mb-3 d-block"
                           style="letter-spacing:.06em;">Selecciona los grupos a limpiar</label>

                    <div class="row g-2" id="cleanup-groups">
                        @foreach($counts as $key => $group)
                        <div class="col-12">
                            <label for="check-{{ $key }}"
                                   class="cleanup-card d-block rounded border p-3"
                                   style="cursor:pointer;transition:border-color .15s,background .15s;">
                                <input type="checkbox" class="cleanup-check visually-hidden"
                                       id="check-{{ $key }}" value="{{ $key }}">

                                <div class="d-flex gap-3 align-items-center">

                                    <div class="flex-grow-1 min-w-0">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="fw-semibold" style="font-size:.88rem;">{{ $group['label'] }}</span>
                                            <span class="badge rounded-pill ms-2 flex-shrink-0 bg-secondary-subtle text-secondary"
                                                  id="badge-total-{{ $key }}">
                                                {{ number_format($group['total']) }}
                                            </span>
                                        </div>
                                        <div class="d-flex flex-wrap gap-1" id="badges-{{ $key }}">
                                            @foreach($group['tables'] as $tableName => $count)
                                                <span class="badge bg-light text-muted border" style="font-size:.68rem;">
                                                    {{ $tableName }}:&nbsp;<span class="fw-semibold text-dark">{{ number_format($count) }}</span>
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>

                                </div>
                            </label>
                        </div>
                        @endforeach
                    </div>
                </div>

                <div class="card-footer">
                    <button id="btn-cleanup" type="button" class="btn btn-primary w-100 mb-2" disabled>
                        Limpiar seleccionados
                    </button>
                    <p id="cleanup-selection-info" class="text-muted small text-center mb-0">
                        Selecciona al menos un grupo para continuar
                    </p>
                </div>
            </div>

        </div>

        {{-- Sidebar de ayuda --}}
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-body">
                    <h6 class="mb-3">¿Qué se elimina?</h6>
                    <ul class="small text-muted mb-0 ps-3">
                        <li class="mb-2"><strong>Contenido IA</strong> — textos generados, logs, costos y versiones de contenido</li>
                        <li class="mb-2"><strong>Productos</strong> — modelos sincronizados, atributos, chats y precios</li>
                        <li class="mb-2"><strong>Proveedores</strong> — proveedores importados y sus fuentes de datos</li>
                        <li class="mb-0"><strong>Categorías</strong> — deportes, familias y subfamilias sincronizadas</li>
                    </ul>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-body">
                    <h6 class="mb-3">Cuándo usarlo</h6>
                    <p class="small text-muted mb-2">
                        Después de ejecutar pruebas de sincronización con datos reales del ERP,
                        usa este panel para limpiar y empezar desde cero.
                    </p>
                    <p class="small text-muted mb-0">
                        Los datos de <strong>configuración</strong> (prompts, horarios, endpoints)
                        <u>no se eliminan</u> en ningún caso.
                    </p>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h6 class="mb-3">Orden recomendado</h6>
                    <ol class="small text-muted mb-0 ps-3">
                        <li class="mb-1">Limpia <strong>Contenido IA</strong> y <strong>Productos</strong></li>
                        <li class="mb-1">Ejecuta la sincronización de categorías</li>
                        <li class="mb-1">Ejecuta la sincronización de proveedores</li>
                        <li class="mb-0">Ejecuta la sincronización de modelos</li>
                    </ol>
                </div>
            </div>

            <div class="card">
                <div class="card-body d-flex flex-column gap-2">
                    <a href="{{ route('settings.suppliers.sync.test.index') }}" class="btn btn-secondary w-100">
                        Panel de pruebas
                    </a>
                    <a href="{{ route('settings.suppliers.sync.index') }}" class="btn btn-outline-secondary w-100">
                        Volver a sincronización
                    </a>
                </div>
            </div>
        </div>

    </div>

    {{-- Modal resultado --}}
    <div class="modal fade" id="cleanup-result-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-bottom px-4 py-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="d-flex align-items-center justify-content-center rounded-circle flex-shrink-0"
                              style="width:32px;height:32px;background:var(--pb-primary-soft);border:1px solid var(--pb-primary-border);">
                            <i class="fas fa-check small" style="color:var(--pb-primary-dark);"></i>
                        </span>
                        <h6 class="modal-title fw-bold mb-0">Limpieza completada</h6>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body px-4 py-3">
                    <div id="cleanup-result-body" class="row g-2"></div>
                </div>
                <div class="modal-footer border-top px-4 py-3">
                    <button type="button" class="btn btn-primary w-100" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal de confirmación --}}
    <div class="modal fade" id="cleanup-confirm-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-bottom px-4 py-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="d-flex align-items-center justify-content-center rounded-circle bg-danger-subtle flex-shrink-0"
                              style="width:32px;height:32px;">
                            <i class="fas fa-triangle-exclamation text-danger small"></i>
                        </span>
                        <h6 class="modal-title fw-bold mb-0">Confirmar limpieza</h6>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body px-4 py-3">
                    <p class="text-muted small mb-2">Se eliminarán <strong>TODOS</strong> los registros de:</p>
                    <p id="confirm-groups-list" class="fw-semibold mb-3"></p>
                    <div class="d-flex align-items-start gap-2 p-2 rounded bg-danger-subtle">
                        <i class="fas fa-circle-exclamation text-danger mt-1 flex-shrink-0 small"></i>
                        <p class="small text-danger mb-0">Esta acción no se puede deshacer.</p>
                    </div>
                </div>
                <div class="modal-footer border-top px-4 py-3 d-flex flex-column gap-2">
                    <button id="confirm-cleanup-btn" type="button" class="btn btn-primary w-100">
                        Sí, limpiar datos
                    </button>
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    const csrf = $('meta[name="csrf-token"]').attr('content');

    $(document).on('change', '.cleanup-check', function () {
        const $card   = $(this).closest('.cleanup-card');
        const checked = $(this).is(':checked');

        $card.css({
            borderColor: checked ? 'var(--pb-primary)' : '',
            background:  checked ? 'var(--pb-primary-soft)' : '',
        });
        updateBtn();
    });

    function updateBtn() {
        const $sel = $('.cleanup-check:checked');
        const n    = $sel.length;
        $('#btn-cleanup').prop('disabled', n === 0);
        if (n === 0) {
            $('#cleanup-selection-info').text('Selecciona al menos un grupo para continuar');
        } else {
            const names = $sel.map(function () {
                return $(this).closest('.cleanup-card').find('.fw-semibold').first().text().trim();
            }).get().join(', ');
            $('#cleanup-selection-info').html(`<strong>${n} grupo(s) seleccionado(s):</strong> ${names}`);
        }
    }

    const confirmModal = new bootstrap.Modal(document.getElementById('cleanup-confirm-modal'));
    let _pendingGroups = [];

    $('#btn-cleanup').on('click', function () {
        const groups = $('.cleanup-check:checked').map(function () { return $(this).val(); }).get();
        if (!groups.length) return;
        _pendingGroups = groups;

        const names = $('.cleanup-check:checked').map(function () {
            return $(this).closest('.cleanup-card').find('.fw-semibold').first().text().trim();
        }).get().join(', ');

        $('#confirm-groups-list').text(names);
        confirmModal.show();
    });

    $('#confirm-cleanup-btn').on('click', function () {
        confirmModal.hide();
        const groups = _pendingGroups;
        if (!groups.length) return;

        $('#btn-cleanup').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Limpiando…');

        fetch('{{ route("settings.suppliers.sync.cleanup.run") }}', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body:    JSON.stringify({ groups }),
        })
        .then(r => r.json())
        .then(data => {
            if (!data.success) {
                toastr.error(data.message || 'Error al limpiar');
                $('#btn-cleanup').prop('disabled', false).html('Limpiar seleccionados');
                return;
            }

            toastr.success(data.message);

            // Actualizar contadores
            Object.keys(data.counts).forEach(key => {
                const g = data.counts[key];
                $(`#badge-total-${key}`)
                    .removeClass('bg-secondary-subtle text-secondary')
                    .addClass('bg-secondary-subtle text-secondary')
                    .text(g.total.toLocaleString());
                let i = 0;
                Object.entries(g.tables).forEach(([name, count]) => {
                    $(`#badges-${key} .badge`).eq(i++).find('.fw-semibold').text(count.toLocaleString());
                });
                $(`#check-${key}`).prop('checked', false).trigger('change');
            });

            // Mostrar resultado en modal
            let html = '';
            Object.entries(data.deleted).forEach(([group, tables]) => {
                const total = Object.values(tables).reduce((a,b) => a+b, 0);
                const label = data.counts[group]?.label || group;
                html += `<div class="col-12">
                    <div class="p-3 rounded border d-flex align-items-center gap-3"
                         style="background:var(--pb-primary-soft);border-color:var(--pb-primary-border) !important;">
                        <i class="fas fa-check-circle" style="color:var(--pb-primary);font-size:1.1rem;flex-shrink:0;"></i>
                        <div>
                            <div class="fw-semibold small" style="color:var(--pb-primary-dark);">${label}</div>
                            <div class="text-muted small">${total.toLocaleString()} registros eliminados</div>
                        </div>
                    </div>
                </div>`;
            });
            $('#cleanup-result-body').html(html);
            new bootstrap.Modal(document.getElementById('cleanup-result-modal')).show();

            $('#btn-cleanup').prop('disabled', true).html('Limpiar seleccionados');
        })
        .catch(() => {
            toastr.error('Error de red');
            $('#btn-cleanup').prop('disabled', false).html('Limpiar seleccionados');
        });
    });
});
</script>
@endpush
