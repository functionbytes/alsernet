@extends('layouts.theme')

@section('title', 'URLs del Sitio')

@section('content')
    @include('core::components.card', ['title' => 'URLs del Sitio'])

    <div class="widget-content searchable-container list">
        @include('core::components.alerts')

        <div class="card">
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">URLs del Sitio</h5>
                        <p class="small mb-0 text-muted">Todas las URLs públicas del módulo Pages. Crea redirecciones directamente desde aquí.</p>
                    </div>
                    <div class="ms-auto">
                        <div class="btn-group">
                            <button type="button" class="btn bg-primary-subtle text-primary dropdown-toggle"
                                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                Acciones
                            </button>
                            <div class="dropdown-menu dropdown-menu-end">
                                <a class="dropdown-item" href="{{ route('setting.seo.redirects.index') }}">Ver redirecciones</a>
                                <a class="dropdown-item" href="{{ route('setting.seo.redirects.create') }}">Nueva redirección</a>
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
                                <h6 class="card-title mb-2">Total de páginas</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['total_pages']) }}</h4>
                                <small class="text-muted">URLs indexadas en el módulo</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Con redirección</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['with_redirect']) }}</h4>
                                <small class="text-muted">URLs con redirección configurada</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Redirecciones totales</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['total_redirects']) }}</h4>
                                <small class="text-muted">Configuradas en el sistema</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Marcadas noindex</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['noindex']) }}</h4>
                                <small class="text-muted">Excluidas de buscadores</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Filters --}}
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('setting.seo.page-urls.index') }}" id="filter-form">
                    <div class="d-flex flex-column flex-lg-row gap-3 align-items-stretch">
                        <div class="flex-fill">
                            <div class="input-group h-100">
                                <span class="input-group-text bg-white border-end-1">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="search" name="search" class="form-control border-start-0 ps-0"
                                       placeholder="Buscar por título o slug..."
                                       value="{{ request('search') }}">
                            </div>
                        </div>
                        <div class="flex-shrink-0" style="min-width: 180px;">
                            <select name="status" class="form-select select2 h-100">
                                <option value="">Todos los estados</option>
                                <option value="published" @selected(request('status') === 'published')>Publicadas</option>
                                <option value="draft" @selected(request('status') === 'draft')>Borrador</option>
                                <option value="pending" @selected(request('status') === 'pending')>Pendiente</option>
                            </select>
                        </div>
                        <div class="d-flex gap-2 flex-shrink-0">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-1"></i>
                            </button>
                            @if(request('search') || request('status'))
                                <a href="{{ route('setting.seo.page-urls.index') }}" class="btn btn-outline-secondary" title="Limpiar filtros">
                                    <i class="fas fa-times"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>

            {{-- Table --}}
            <div class="card-body">
                @if($pageRows->isEmpty())
                    <div class="text-center py-5">
                        <div class="d-flex flex-column align-items-center">
                            <div class="round-48 rounded-circle bg-light-subtle text-muted mb-3 d-flex align-items-center justify-content-center">
                                @if(! class_exists(\Modules\Page\Models\Page::class))
                                    <i class="fas fa-exclamation-triangle fs-7"></i>
                                @else
                                    <i class="fas fa-file-alt fs-7"></i>
                                @endif
                            </div>
                            <h6 class="mb-1">
                                @if(! class_exists(\Modules\Page\Models\Page::class))
                                    Módulo Page no disponible
                                @elseif(request('search') || request('status'))
                                    No se encontraron páginas
                                @else
                                    No hay páginas registradas
                                @endif
                            </h6>
                            <p class="text-muted mb-3">
                                @if(! class_exists(\Modules\Page\Models\Page::class))
                                    El módulo de Pages no está instalado o activo en el sistema
                                @elseif(request('search') || request('status'))
                                    No hay resultados para los criterios de búsqueda
                                @else
                                    Crea tu primera página para ver las URLs aquí
                                @endif
                            </p>
                        </div>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="3%"><input type="checkbox" id="select-all" class="form-check-input"></th>
                                    <th>Página / URL</th>
                                    <th class="text-center">Idioma</th>
                                    <th class="text-center">Estado</th>
                                    <th>Redirección configurada</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($pageRows as $row)
                                    <tr>
                                        <td><input type="checkbox" class="form-check-input bulk-checkbox" value="{{ $row['full_path'] }}"></td>
                                        <td>
                                            <div class="fw-semibold">{{ $row['title'] }}</div>
                                            <div class="d-flex align-items-center gap-1 mt-1">
                                                <code class="text-primary small">{{ $row['full_path'] }}</code>
                                                <a href="{{ $row['url'] }}" target="_blank" class="text-muted" title="Abrir en el sitio">
                                                    <i class="fas fa-external-link-alt" style="font-size:.65rem"></i>
                                                </a>
                                            </div>
                                            @if($row['seo_noindex'])
                                                <span class="badge bg-danger-subtle text-danger mt-1" style="font-size:.65rem">
                                                    <i class="fas fa-eye-slash me-1"></i>noindex
                                                </span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($row['locale'])
                                                <span class="badge bg-light text-dark border fw-semibold">
                                                    {{ strtoupper($row['locale']) }}
                                                </span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @php
                                                $statusMap = [
                                                    'published' => ['bg-success text-white', 'Publicada'],
                                                    'draft'     => ['bg-secondary-subtle text-secondary', 'Borrador'],
                                                    'pending'   => ['bg-warning-subtle text-warning', 'Pendiente'],
                                                ];
                                                [$badgeClass, $badgeLabel] = $statusMap[$row['status']] ?? ['bg-light text-muted', $row['status']];
                                            @endphp
                                            <span class="badge {{ $badgeClass }}">{{ $badgeLabel }}</span>
                                        </td>
                                        <td>
                                            @if($row['has_redirect'])
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="badge {{ $row['redirect']->status_code == 301 ? 'bg-success text-white' : 'bg-info text-white' }}">
                                                        {{ $row['redirect']->status_code }}
                                                    </span>
                                                    <code class="text-muted">{{ $row['redirect']->target_path }}</code>
                                                </div>
                                                <small class="text-muted">
                                                    <span class="badge bg-light text-dark text-black">{{ number_format($row['redirect']->hits_count) }}</span> visitas
                                                </small>
                                            @else
                                                <span class="text-muted">Sin redirección</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a href="#" class="text-muted" data-bs-toggle="dropdown">
                                                    <i class="fa fa-ellipsis-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    @if($row['has_redirect'])
                                                        <li>
                                                            <a class="dropdown-item" href="{{ route('setting.seo.redirects.edit', $row['redirect']->id) }}">
                                                                Editar redirección
                                                            </a>
                                                        </li>
                                                    @else
                                                        <li>
                                                            <a class="dropdown-item"
                                                               href="{{ route('setting.seo.redirects.create', ['source_path' => $row['full_path']]) }}">
                                                                Crear redirección
                                                            </a>
                                                        </li>
                                                    @endif
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('pages.edit', $row['page_id']) }}">
                                                            Editar página
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="{{ $row['url'] }}" target="_blank">
                                                            Ver en el sitio
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
                @endif
            </div>

            @if($pages && $pages->hasPages())
                <div class="card-footer">{{ $pages->links() }}</div>
            @endif
        </div>
    </div>

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
                    <p class="text-muted mb-3">Se aplicará la acción sobre <strong><span data-bulk-count>0</span> URL(s)</strong>.</p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Acción</label>
                        <select id="bulk-action-select" class="form-select">
                            <option value="">Seleccionar acción...</option>
                            <option value="create_redirect">Crear redirección a /</option>
                            <option value="delete_redirect">Eliminar redirección</option>
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
@endsection

@push('scripts')
<script>
$(document).ready(function () {
    @if(session('success'))
        toastr.success('{{ session('success') }}', 'Éxito');
    @endif
    @if(session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif

    // ── Select2 on filter ────────────────────────────────────────────
    $('select[name="status"]').select2({ width: '100%' });

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
        var paths = bulk.getIds();

        if (!action) { toastr.warning('Selecciona una acción.'); return; }
        if (!paths.length) { toastr.warning('Selecciona al menos una URL.'); return; }

        var $btn = $(this);
        $btn.prop('disabled', true).text('Procesando...');

        if (action === 'delete_redirect') {
            if (!confirm('¿Eliminar las redirecciones de ' + paths.length + ' URL(s)?')) {
                $btn.prop('disabled', false).text('Aplicar');
                return;
            }
        }

        $.ajax({
            url: '{{ route("setting.seo.page-urls.bulk-action") }}',
            method: 'POST',
            data: JSON.stringify({ action: action, paths: paths, _token: $('meta[name="csrf-token"]').attr('content') }),
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                $('#bulk-modal').modal('hide');
                toastr.success(res.count + ' URL(s) actualizadas.');
                setTimeout(function () { location.reload(); }, 800);
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message ?? 'Error al procesar.');
                $btn.prop('disabled', false).text('Aplicar');
            }
        });
    });
});
</script>
@endpush
