@extends('layouts.theme')

@section('title', 'Historial de auditorías SEO')

@section('page_header')
    @include('core::components.card', ['title' => 'Historial de auditorías SEO'])
@endsection

@section('content')
    <div class="widget-content searchable-container list">
        @include('core::components.alerts')

        <div class="card">
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Registros de auditoría</h5>
                        <p class="small mb-0 text-muted">Historial completo de análisis SEO realizados</p>
                    </div>
                    <div class="ms-auto">
                        <div class="btn-group">
                            <button type="button" class="btn bg-primary-subtle text-primary dropdown-toggle"
                                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                Acciones
                            </button>
                            <div class="dropdown-menu dropdown-menu-end">
                                <a class="dropdown-item" href="{{ route('settings.seo.audit.index') }}">Volver a auditoría</a>
                                <div class="dropdown-divider"></div>
                                <button class="dropdown-item" type="button" data-bs-toggle="modal" data-bs-target="#clear-modal">Limpiar todo</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Stats Cards --}}
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Total</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['total_audits']) }}</h4>
                                <small class="text-muted">Total auditorías</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Score promedio</h6>
                                <h4 class="mb-1 fw-bold">{{ $stats['avg_score'] }}</h4>
                                <small class="text-muted">Puntuación media</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Grado A</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['grade_a']) }}</h4>
                                <small class="text-muted">Auditorías grado A</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Grado F</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['grade_f']) }}</h4>
                                <small class="text-muted">Auditorías grado F</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Filters --}}
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('settings.seo.audit.history') }}" id="filter-form">
                    <div class="d-flex flex-column flex-lg-row gap-3 align-items-stretch">
                        <div class="flex-fill">
                            <div class="input-group h-100">
                                <span class="input-group-text bg-white border-end-1">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="search" name="search" class="form-control border-start-0 ps-0"
                                       placeholder="Buscar por URL..."
                                       value="{{ request('search') }}">
                            </div>
                        </div>
                        <div class="flex-shrink-0" style="min-width: 180px;">
                            <select name="grade" class="form-select select2 h-100">
                                <option value="">Todos los grados</option>
                                @foreach(['A', 'B', 'C', 'D', 'F'] as $g)
                                    <option value="{{ $g }}" {{ request('grade') === $g ? 'selected' : '' }}>Grado {{ $g }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="d-flex gap-2 flex-shrink-0">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-1"></i>
                            </button>
                            @if(request('search') || request('grade'))
                                <a href="{{ route('settings.seo.audit.history') }}" class="btn btn-outline-secondary" title="Limpiar filtros">
                                    <i class="fas fa-times"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>

            {{-- Table --}}
            <div class="card-body">
                @if($logs->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="3%"><input type="checkbox" id="select-all" class="form-check-input"></th>
                                    <th>Título SEO</th>
                                    <th>Ultima auditoría</th>
                                    <th class="text-center">Score</th>
                                    <th class="text-center">Grado</th>
                                    <th class="text-center">Problemas</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($logs as $log)
                                    @php
                                        $score = $log->score ?? 0;
                                        if ($score >= 90) { $badgeBg = 'bg-success-subtle'; $badgeText = 'text-success'; }
                                        elseif ($score >= 75) { $badgeBg = 'bg-primary-subtle'; $badgeText = 'text-primary'; }
                                        elseif ($score >= 60) { $badgeBg = 'bg-warning-subtle'; $badgeText = 'text-warning'; }
                                        elseif ($score >= 40) { $badgeBg = 'bg-danger-subtle'; $badgeText = 'text-danger'; }
                                        else { $badgeBg = 'bg-danger-subtle'; $badgeText = 'text-danger'; }
                                    @endphp
                                    <tr data-log-id="{{ $log->id }}">
                                        <td><input type="checkbox" class="form-check-input bulk-checkbox" value="{{ $log->id }}"></td>
                                        <td>
                                            @if($log->seoMeta)
                                                <span class="fw-semibold">{{ $log->seoMeta->display_title ?? "Meta #{$log->seo_meta_id}" }}</span>
                                                <br>
                                                <small class="text-muted">{{ $log->url }}</small>
                                            @else
                                                <small class="text-muted">{{ $log->url }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                {{ $log->audited_at?->format('d/m/Y H:i') ?? '—' }}
                                            </small>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge {{ $badgeBg }} {{ $badgeText }}">{{ $score }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge {{ $badgeBg }} {{ $badgeText }}">{{ $log->grade ?? '—' }}</span>
                                        </td>
                                        <td class="text-center">
                                            @if($log->issues_count > 0)
                                                <span class="badge bg-warning-subtle text-warning">{{ $log->issues_count }}</span>
                                            @else
                                                <span class="badge bg-success-subtle text-success">0</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a href="#" class="text-muted" data-bs-toggle="dropdown">
                                                    <i class="fa fa-ellipsis-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    @if($log->seoMeta)
                                                        <li>
                                                            <a class="dropdown-item" href="{{ route('settings.seo.audit.history.meta', $log->seoMeta) }}">
                                                                Ver historial de meta
                                                            </a>
                                                        </li>
                                                        <li><hr class="dropdown-divider"></li>
                                                    @endif
                                                    <li>
                                                        <a class="dropdown-item delete-btn"
                                                           href="#"
                                                           data-bs-toggle="modal"
                                                           data-bs-target="#delete-modal"
                                                           data-url="{{ route('settings.seo.audit.history.destroy', $log) }}"
                                                           data-title="Eliminar auditoría: {{ $log->url }}">
                                                            Eliminar
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5">
                        <div class="d-flex flex-column align-items-center">
                            <div class="round-48 rounded-circle bg-light-subtle text-muted mb-3 d-flex align-items-center justify-content-center">
                                <i class="fas fa-history fs-7"></i>
                            </div>
                            <h6 class="mb-1">
                                @if(request('search') || request('grade'))
                                    No se encontraron auditorías
                                @else
                                    No hay auditorías registradas
                                @endif
                            </h6>
                            <p class="text-muted mb-3">
                                @if(request('search') || request('grade'))
                                    No hay resultados para los criterios de búsqueda
                                @else
                                    Realiza tu primera auditoría SEO para ver el historial
                                @endif
                            </p>
                            @if(!request('search') && !request('grade'))
                                <a href="{{ route('settings.seo.audit.index') }}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-search me-1"></i> Realizar primera auditoría
                                </a>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            @if($logs->hasPages())
                <div class="card-footer">{{ $logs->links() }}</div>
            @endif
        </div>
    </div>

    @include('core::components.delete')

    {{-- Bulk toolbar --}}
    <div id="bulk-toolbar" class="position-fixed bottom-0 start-50 translate-middle-x mb-4 d-none" style="z-index:1050;">
        <button type="button" class="btn btn-primary shadow-lg px-4" data-bs-toggle="modal" data-bs-target="#bulk-modal">
            <span data-bulk-count>0</span> seleccionado(s) &mdash; Aplicar acción
        </button>
    </div>

    {{-- Bulk modal --}}
    <div class="modal fade" id="bulk-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Acción masiva</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">Se aplicará la acción sobre <strong><span data-bulk-count>0</span> entrada(s)</strong>.</p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Acción</label>
                        <select id="bulk-action-select" class="form-select">
                            <option value="">Seleccionar acción...</option>
                            <option value="delete">Eliminar</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button id="bulk-apply-btn" type="button" class="btn btn-primary w-100 mb-1">Aplicar</button>
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal limpiar todo --}}
    <div id="clear-modal" class="modal fade">
        <div class="modal-dialog modal-md modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center px-4 pb-4">
                    <div class="mb-3">
                        <i class="fas fa-exclamation-triangle fa-3x text-warning"></i>
                    </div>
                    <h4 class="mb-2">Limpiar todo el historial</h4>
                    <p class="text-muted mb-4">Se eliminarán todos los registros de auditoría. Esta acción no se puede deshacer.</p>
                    <div class="d-grid gap-2">
                        <form method="POST" action="{{ route('settings.seo.audit.history.clear') }}">
                            @csrf
                            <button type="submit" class="btn btn-danger w-100">
                                <i class="fas fa-trash me-2"></i> Confirmar y limpiar
                            </button>
                        </form>
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
$(document).ready(function () {
    // Delete modal
    $(document).on('click', '.delete-btn', function () {
        $('#delete-modal .modal-title').text($(this).data('title'));
        $('#delete-form').attr('action', $(this).data('url'));
    });

    @if(session('success'))
        toastr.success('{{ session('success') }}', 'Éxito');
    @endif
    @if(session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif

    // ── Select2 on filter ────────────────────────────────────────────
    $('select[name="grade"]').select2({ width: '100%' });

    // ── Bulk actions ──────────────────────────────────────────────────
    const bulk = window.BulkActions.init({ checkbox: '.bulk-checkbox' });

    $('#bulk-action-select').select2({ dropdownParent: $('#bulk-modal'), width: '100%' });

    $('#bulk-modal').on('hide.bs.modal', function () {
        $('#bulk-action-select').val('').trigger('change');
        $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
        bulk.reset();
    });

    $('#bulk-apply-btn').on('click', function () {
        var action = $('#bulk-action-select').val();
        var ids = bulk.getIds();

        if (!action) { toastr.warning('Selecciona una acción.'); return; }
        if (!ids.length) { toastr.warning('Selecciona al menos una entrada.'); return; }

        var $btn = $(this);

        const doAction = function () {
            $btn.prop('disabled', true).text('Procesando...');
            $.ajax({
                url: '{{ route("settings.seo.audit.history.bulk-action") }}',
                method: 'POST',
                data: JSON.stringify({ action: action, ids: ids, _token: $('meta[name="csrf-token"]').attr('content') }),
                contentType: 'application/json',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: function (res) {
                    $('#bulk-modal').modal('hide');
                    toastr.success(res.count + ' entrada(s) eliminadas.');
                    setTimeout(function () { location.reload(); }, 800);
                },
                error: function (xhr) {
                    toastr.error(xhr.responseJSON?.message ?? 'Error al procesar.');
                    $btn.prop('disabled', false).text('Aplicar');
                }
            });
        };

        if (action === 'delete') {
            window.__confirm('¿Eliminar ' + ids.length + ' entrada(s)?', doAction);
        } else {
            doAction();
        }
    });
});
</script>
@endpush
