@extends('layouts.theme')

@section('title', 'Redirecciones SEO')

@section('page_header')
    @include('core::components.card', ['title' => 'Redirecciones SEO'])
@endsection

@section('content')
    <div class="widget-content searchable-container list">
        @include('core::components.alerts')

        <div class="card">
            <!-- Header Section -->
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Redirecciones SEO</h5>
                        <p class="small mb-0 text-muted">Gestiona las redirecciones URL para mejorar el SEO y la experiencia de usuario</p>
                    </div>
                    <div class="d-flex gap-2">
                        <div class="btn-group">
                            <button type="button" class="btn bg-primary-subtle text-primary dropdown-toggle"
                                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                Acciones
                            </button>
                            <div class="dropdown-menu dropdown-menu-end">
                                <a class="dropdown-item" href="{{ route('settings.seo.redirects.create') }}">
                                    Nueva redireccion
                                </a>
                                <div class="dropdown-divider"></div>
                                <button class="dropdown-item" type="button" id="detect-chains-btn">Detectar cadenas</button>
                                <button class="dropdown-item" type="button" data-bs-toggle="modal" data-bs-target="#clear-cache-modal">Limpiar caché</button>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="{{ route('settings.seo.redirects.export') }}">Exportar CSV</a>
                                <a class="dropdown-item" href="{{ route('settings.seo.redirects.import') }}">Importar CSV</a>
                                <a class="dropdown-item" href="{{ route('settings.seo.redirects.htaccess-import') }}">Importar .htaccess</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status Cards -->
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title mb-2">Total de redirecciones</h6>
                                        <h4 class="mb-1 fw-bold">{{ number_format($stats['total']) }}</h4>
                                        <small class="text-muted">Configuradas en el sistema</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title mb-2">Activas</h6>
                                        <h4 class="mb-1 fw-bold">{{ number_format($stats['active']) }}</h4>
                                        <small class="text-muted">{{ number_format($stats['inactive']) }} inactivas</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title mb-2">Por tipo</h6>
                                        <h4 class="mb-1 fw-bold">{{ number_format($stats['permanent']) }}</h4>
                                        <small class="text-muted">301 permanente / {{ number_format($stats['temporary']) }} 302 temporal</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title mb-2">Total de visitas</h6>
                                        <h4 class="mb-1 fw-bold">{{ number_format($stats['total_hits']) }}</h4>
                                        <small class="text-muted">Redirecciones procesadas</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters Section -->
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('settings.seo.redirects.index') }}" id="filter-form">
                    <div class="d-flex flex-column flex-lg-row gap-3 align-items-stretch">
                        <div class="flex-fill">
                            <div class="input-group h-100">
                                <span class="input-group-text bg-white border-end-1">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="search" name="search" class="form-control -0 ps-0"
                                       placeholder="Buscar por ruta origen o destino..."
                                       value="{{ request('search') }}">
                            </div>
                        </div>
                        <div class="flex-shrink-0" style="min-width: 180px;">
                            <select name="is_active" class="form-select select2 h-100">
                                <option value="">Todos los estados</option>
                                <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Activas</option>
                                <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Inactivas</option>
                            </select>
                        </div>
                        <div class="flex-shrink-0" style="min-width: 160px;">
                            <select name="status_code" class="form-select select2 h-100">
                                <option value="">Todos los códigos</option>
                                <option value="301" {{ request('status_code') === '301' ? 'selected' : '' }}>301 Permanente</option>
                                <option value="302" {{ request('status_code') === '302' ? 'selected' : '' }}>302 Temporal</option>
                                <option value="307" {{ request('status_code') === '307' ? 'selected' : '' }}>307 Temporal</option>
                            </select>
                        </div>
                        <div class="d-flex gap-2 flex-shrink-0">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-1"></i>
                            </button>
                            @if(request()->hasAny(['search', 'status_code', 'is_active']))
                                <a href="{{ route('settings.seo.redirects.index') }}" class="btn btn-outline-secondary" title="Limpiar filtros">
                                    <i class="fas fa-times"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>

            <!-- Redirects List -->
            <div class="card-body">
                @if($redirects->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="30">
                                        <input type="checkbox" class="form-check-input" id="select-all">
                                    </th>
                                    <th>Ruta origen</th>
                                    <th>Ruta destino</th>
                                    <th class="text-center">Codigo</th>
                                    <th class="text-center">Visitas</th>
                                    <th class="text-center">Estado</th>
                                    <th>Creado</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($redirects as $redirect)
                                    <tr>
                                        <td>
                                            <input type="checkbox" class="form-check-input bulk-checkbox" value="{{ $redirect->id }}">
                                        </td>
                                        <td>
                                            <a href="{{ route('settings.seo.redirects.show', $redirect) }}" class="text-decoration-none">
                                                <code class="text-primary">{{ $redirect->source_path }}</code>
                                            </a>
                                            @if($redirect->is_regex)
                                                <span class="badge bg-info ms-1">REGEX</span>
                                            @endif
                                            @if($redirect->is_wildcard)
                                                <span class="badge bg-secondary ms-1">WILDCARD</span>
                                            @endif
                                            @if($redirect->active_until)
                                                <small class="d-block text-muted mt-1">
                                                    <i class="fas fa-clock fa-xs me-1"></i>Expira: {{ $redirect->active_until->format('d/m/Y H:i') }}
                                                </small>
                                            @endif
                                        </td>
                                        <td>
                                            <code class="text-muted">{{ $redirect->target_path }}</code>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge {{ $redirect->isPermanent() ? 'bg-success text-white' : 'bg-info text-white' }}">
                                                {{ $redirect->status_code }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-light text-dark text-black">{{ number_format($redirect->hits_count) }}</span>
                                        </td>
                                        <td class="text-center">
                                            <form action="{{ route('settings.seo.redirects.toggle-active', $redirect) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-sm btn-link p-0 border-0">
                                                    @if($redirect->is_active)
                                                        <span class="badge bg-success text-white">Activo</span>
                                                    @else
                                                        <span class="badge bg-secondary-subtle text-secondary">Inactivo</span>
                                                    @endif
                                                </button>
                                            </form>
                                        </td>
                                        <td>
                                            <span class="text-muted">{{ $redirect->created_at->diffForHumans() }}</span>
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a href="#" class="text-muted" data-bs-toggle="dropdown">
                                                    <i class="fa fa-ellipsis-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('settings.seo.redirects.show', $redirect) }}">
                                                            Ver detalle
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('settings.seo.redirects.edit', $redirect) }}">
                                                            Editar
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item analytics-btn" href="#"
                                                           data-url="{{ route('settings.seo.redirects.analytics', $redirect) }}"
                                                           data-source="{{ $redirect->source_path }}">
                                                            Ver analíticas
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a class="dropdown-item delete-btn"
                                                           data-bs-toggle="modal"
                                                           data-bs-target="#delete-modal"
                                                           data-url="{{ route('settings.seo.redirects.destroy', $redirect) }}"
                                                           data-title="Eliminar: {{ $redirect->source_path }}">
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
                        <div class="mb-4">
                            <i class="fas fa-route fa-4x text-muted opacity-50"></i>
                        </div>
                        <h5 class="text-muted mb-2">No hay redirecciones configuradas</h5>
                        <p class="text-muted mb-4">Comienza creando tu primera redirección para mejorar el SEO de tu sitio</p>
                        <a href="{{ route('settings.seo.redirects.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> Crear primera redireccion
                        </a>
                    </div>
                @endif
            </div>

            @if($redirects->hasPages())
                <div class="card-footer">{{ $redirects->links() }}</div>
            @endif
        </div>
    </div>

    @include('core::components.delete')

    {{-- Floating bulk toolbar --}}
    <div id="bulk-toolbar" class="position-fixed bottom-0 start-50 translate-middle-x mb-4 d-none" style="z-index:1050;">
        <div class="card shadow-lg border-0">
            <div class="card-body py-2 px-4 d-flex align-items-center gap-3">
                <span class="text-muted small"><span data-bulk-count>0</span> seleccionados</span>
                <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#bulk-modal">
                    Eliminar seleccionados
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm" id="bulk-cancel">
                    Cancelar
                </button>
            </div>
        </div>
    </div>

    {{-- Modal eliminacion masiva --}}
    <div id="bulk-modal" class="modal fade">
        <div class="modal-dialog modal-md modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="bulk-delete-form" method="POST" action="{{ route('settings.seo.redirects.bulk-delete') }}">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="ids" id="bulk-delete-ids">
                    <div class="modal-body text-center px-4 pb-2">
                        <div class="display-4 text-warning mb-3">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <h4 class="my-0">¿Eliminar redirecciones seleccionadas?</h4>
                        <p class="text-muted mt-2">Se eliminarán <strong id="bulk-count">0</strong> redirecciones. Esta acción no se puede deshacer.</p>
                    </div>
                    <div class="modal-footer flex-column gap-1 border-0 pt-0">
                        <button type="submit" class="btn btn-danger w-100 mb-1">Confirmar eliminación</button>
                        <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal detectar cadenas --}}
    <div id="chains-modal" class="modal fade">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-link me-2 text-warning"></i>Cadenas de redirecciones</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="chains-loading" class="text-center py-4">
                        <span class="spinner-border text-primary me-2"></span>
                        Analizando redirecciones...
                    </div>
                    <div id="chains-result" class="d-none"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal analíticas de visitas --}}
    <div id="analyticsModal" class="modal fade">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-chart-line me-2 text-primary"></i>Analíticas de visitas — <span id="analytics-source"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="analytics-loading" class="text-center py-4">
                        <span class="spinner-border text-primary me-2"></span>
                        Cargando datos...
                    </div>
                    <div id="analytics-content" class="d-none">
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <div class="card bg-light-secondary text-center py-3">
                                    <div class="fs-4 fw-bold text-primary" id="analytics-total30d">0</div>
                                    <small class="text-muted">Visitas últimos 30 días</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card bg-light-secondary text-center py-3">
                                    <div class="fs-4 fw-bold text-secondary" id="analytics-total-all">0</div>
                                    <small class="text-muted">Total histórico</small>
                                </div>
                            </div>
                        </div>
                        <canvas id="analyticsChart" height="100"></canvas>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal limpiar cache --}}
    <div id="clear-cache-modal" class="modal fade">
        <div class="modal-dialog modal-md modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center px-4 pb-4">
                    <div class="mb-3">
                        <i class="fas fa-sync fa-3x text-primary"></i>
                    </div>
                    <h4 class="mb-2">Limpiar cache de redirecciones</h4>
                    <p class="text-muted mb-4">
                        Se limpiará toda la cache de redirecciones almacenada. Las redirecciones se volverán a cachear automáticamente cuando se utilicen.
                    </p>
                    <div class="d-grid gap-2">
                        <form method="POST" action="{{ route('settings.seo.redirects.clear-cache') }}">
                            @csrf
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-sync me-2"></i>Confirmar y limpiar cache
                            </button>
                        </form>
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            Cancelar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
$(document).ready(function() {
    // Delete modal
    $('.delete-btn').on('click', function() {
        $('#delete-modal .modal-title').text($(this).data('title'));
        $('#delete-form').attr('action', $(this).data('url'));
    });

    // Analytics modal
    var analyticsChart = null;
    $(document).on('click', '.analytics-btn', function(e) {
        e.preventDefault();
        var url = $(this).data('url');
        var source = $(this).data('source');

        $('#analytics-source').text(source);
        $('#analytics-loading').removeClass('d-none');
        $('#analytics-content').addClass('d-none');
        $('#analyticsModal').modal('show');

        $.ajax({
            url: url,
            method: 'GET',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function(res) {
                $('#analytics-total30d').text(res.total_30d.toLocaleString());
                $('#analytics-total-all').text(res.redirect.hits_count.toLocaleString());

                if (analyticsChart) {
                    analyticsChart.destroy();
                }

                var ctx = document.getElementById('analyticsChart').getContext('2d');
                analyticsChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: res.labels,
                        datasets: [{
                            label: 'Visitas',
                            data: res.data,
                            borderColor: '#90bb13',
                            backgroundColor: 'rgba(144,187,19,0.1)',
                            borderWidth: 2,
                            pointRadius: 3,
                            fill: true,
                            tension: 0.3,
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: { legend: { display: false } },
                        scales: {
                            x: { ticks: { maxTicksLimit: 10, font: { size: 11 } } },
                            y: { beginAtZero: true, ticks: { stepSize: 1 } }
                        }
                    }
                });

                $('#analytics-loading').addClass('d-none');
                $('#analytics-content').removeClass('d-none');
            },
            error: function() {
                $('#analyticsModal').modal('hide');
                toastr.error('Error al cargar las analíticas.');
            }
        });
    });

    // Bulk selection
    const $selectAll = $('#select-all');
    const $toolbar = $('#bulk-toolbar');
    const $bulkCount = $('#bulk-count');
    const $bulkIds = $('#bulk-delete-ids');

    function getChecked() {
        return $('.bulk-checkbox:checked');
    }

    function updateBulkState() {
        const selected = getChecked();
        const count = selected.length;

        $toolbar.toggleClass('d-none', count === 0);
        $('[data-bulk-count]').text(count);
        $bulkCount.text(count);
        $bulkIds.val(JSON.stringify(selected.map(function() { return $(this).val(); }).get()));
    }

    $selectAll.on('change', function() {
        $('.bulk-checkbox').prop('checked', $(this).is(':checked'));
        updateBulkState();
    });

    $(document).on('change', '.bulk-checkbox', updateBulkState);

    $('#bulk-cancel').on('click', function() {
        $('.bulk-checkbox, #select-all').prop('checked', false);
        updateBulkState();
    });

    // Detect chains
    $('#detect-chains-btn').on('click', function() {
        var $modal = $('#chains-modal');
        var $loading = $('#chains-loading');
        var $result = $('#chains-result');

        $loading.removeClass('d-none');
        $result.addClass('d-none').empty();
        $modal.modal('show');

        $.ajax({
            url: '{{ route("settings.seo.redirects.detect-chains") }}',
            method: 'GET',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function(res) {
                $loading.addClass('d-none');

                if (res.count === 0) {
                    $result.removeClass('d-none').html(
                        '<div class="alert alert-success border-0 mb-0">' +
                        '<i class="fas fa-check-circle me-2"></i>' +
                        'No se detectaron cadenas de redirecciones. Todo está en orden.' +
                        '</div>'
                    );
                    return;
                }

                var html = '<div class="alert alert-warning border-0 mb-3">' +
                    '<i class="fas fa-exclamation-triangle me-2"></i>' +
                    '<strong>' + res.count + '</strong> cadena(s) de redirecciones detectada(s). Las cadenas reducen el rendimiento SEO.' +
                    '</div>';

                $.each(res.chains, function(i, item) {
                    html += '<div class="card mb-2 border-warning">' +
                        '<div class="card-body py-2 px-3">' +
                        '<small class="text-muted d-block mb-1">Cadena ' + (i + 1) + '</small>' +
                        '<code>' + item.chain.join(' <span class="text-warning">→</span> ') + '</code>' +
                        '</div></div>';
                });

                $result.removeClass('d-none').html(html);
            },
            error: function() {
                $loading.addClass('d-none');
                $result.removeClass('d-none').html(
                    '<div class="alert alert-danger border-0 mb-0">' +
                    '<i class="fas fa-times-circle me-2"></i>' +
                    'Error al detectar cadenas. Intenta de nuevo.' +
                    '</div>'
                );
            }
        });
    });
});
</script>
@endpush
