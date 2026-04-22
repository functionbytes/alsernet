@extends('layouts.theme')

@section('title', 'Errores 404')

@section('content')
    @include('core::components.card', ['title' => 'Errores 404 detectados'])

    <div class="widget-content searchable-container list">
        @include('core::components.alerts')

        <div class="card">
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Errores 404 detectados</h5>
                        <p class="small mb-0 text-muted">URLs que no encontraron contenido y pueden necesitar una redirección</p>
                    </div>
                    <div class="ms-auto">
                        <div class="btn-group">
                            <button type="button" class="btn bg-primary-subtle text-primary dropdown-toggle"
                                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                Acciones
                            </button>
                            <div class="dropdown-menu dropdown-menu-end">
                                <button class="dropdown-item" type="button" id="btn-pattern-view">Ver por patrones</button>
                                <button class="dropdown-item" type="button" id="btn-suggest-redirects">Sugerir redirects automáticos</button>
                                <button class="dropdown-item" type="button" id="btn-create-all-redirects">Crear todos → /</button>
                                <div class="dropdown-divider"></div>
                                <button class="dropdown-item" type="button" data-bs-toggle="modal" data-bs-target="#clear-modal">Limpiar todo</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Stat cards --}}
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">URLs únicas</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['total_unique']) }}</h4>
                                <small class="text-muted">Rutas distintas con 404</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Total hits</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['total_hits']) }}</h4>
                                <small class="text-muted">Visitas a páginas 404</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Sin redirect</h6>
                                <h4 class="mb-1 fw-bold ">{{ number_format($stats['without_redirect']) }}</h4>
                                <small class="text-muted">Pendientes de solucionar</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Últimas 24h</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['recent_24h']) }}</h4>
                                <small class="text-muted">URLs activas recientemente</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Filters --}}
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('setting.seo.404-logs.index') }}" id="filter-form">
                    <div class="d-flex flex-column flex-lg-row gap-3 align-items-stretch">
                        <div class="flex-fill">
                            <div class="input-group h-100">
                                <span class="input-group-text bg-white border-end-1">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="search" name="search" class="form-control border-start-0 ps-0"
                                       placeholder="Buscar por ruta..."
                                       value="{{ request('search') }}">
                            </div>
                        </div>
                        <div class="flex-shrink-0" style="min-width: 180px;">
                            <select name="status" class="form-select select2 h-100">
                                <option value="">Todos los estados</option>
                                <option value="without_redirect" {{ request('status') === 'without_redirect' ? 'selected' : '' }}>Sin redirect</option>
                                <option value="with_redirect" {{ request('status') === 'with_redirect' ? 'selected' : '' }}>Redirigido</option>
                            </select>
                        </div>
                        <div class="d-flex gap-2 flex-shrink-0">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-1"></i>
                            </button>
                            @if(request('search') || request('status'))
                                <a href="{{ route('setting.seo.404-logs.index') }}" class="btn btn-outline-secondary" title="Limpiar filtros">
                                    <i class="fas fa-times"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>

            {{-- Table --}}
            <div class="card-body">
                <div id="pattern-view-container"></div>
                @if($logs->count() > 0)
                    <div id="logs-table-wrapper" class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="3%"><input type="checkbox" id="select-all" class="form-check-input"></th>
                                    <th>Path</th>
                                    <th class="text-center">Hits</th>
                                    <th>Primera vez</th>
                                    <th>Ultima vez</th>
                                    <th>Referer</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($logs as $log)
                                    <tr data-log-id="{{ $log->id }}">
                                        <td><input type="checkbox" class="form-check-input bulk-checkbox" value="{{ $log->id }}"></td>
                                        <td>
                                            <code class="text-primary small">{{ $log->path }}</code>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-secondary text-white">{{ number_format($log->hit_count) }}</span>
                                        </td>
                                        <td>
                                            <small class="text-muted">{{ $log->first_seen_at->format('d/m/Y H:i') }}</small>
                                        </td>
                                        <td>
                                            <small class="text-muted">{{ $log->last_seen_at->diffForHumans() }}</small>
                                        </td>
                                        <td>
                                            @if($log->referer)
                                                <small class="text-muted" title="{{ $log->referer }}">
                                                    {{ Str::limit($log->referer, 40) }}
                                                </small>
                                            @else
                                                <small class="text-muted">—</small>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($log->has_redirect)
                                                <span class="badge bg-success text-white">Redirigido</span>
                                            @else
                                                <span class="badge bg-danger text-white">Sin redirect</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a href="#" class="text-muted" data-bs-toggle="dropdown">
                                                    <i class="fa fa-ellipsis-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <form action="{{ route('setting.seo.404-logs.create-redirect', $log) }}" method="POST">
                                                            @csrf
                                                            <button type="submit" class="dropdown-item">
                                                                Crear redirect
                                                            </button>
                                                        </form>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a class="dropdown-item delete-btn"
                                                           href="#"
                                                           data-bs-toggle="modal"
                                                           data-bs-target="#delete-modal"
                                                           data-url="{{ route('setting.seo.404-logs.destroy', $log) }}"
                                                           data-title="Eliminar: {{ $log->path }}">
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
                                <i class="fas fa-check-circle fs-7"></i>
                            </div>
                            <h6 class="mb-1">
                                @if(request('search') || request('status'))
                                    No se encontraron errores 404
                                @else
                                    No hay errores 404 registrados
                                @endif
                            </h6>
                            <p class="text-muted mb-3">
                                @if(request('search') || request('status'))
                                    No hay resultados para los criterios de búsqueda
                                @else
                                    El sistema registrará automáticamente las URLs que devuelvan 404
                                @endif
                            </p>
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
                            <option value="mark_resolved">Marcar como resuelto</option>
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
                    <h4 class="mb-2">Limpiar todos los registros 404</h4>
                    <p class="text-muted mb-4">Se eliminarán todos los registros de errores 404. Esta acción no se puede deshacer.</p>
                    <div class="d-grid gap-2">
                        <form method="POST" action="{{ route('setting.seo.404-logs.clear') }}">
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

    {{-- Modal sugerir redirects --}}
    <div class="modal fade" id="suggestModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-magic me-2"></i> Sugerencias de redirects</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="suggestions-body">
                    <div class="text-center py-4">
                        <i class="fas fa-spinner fa-spin fa-2x"></i>
                        <p class="mt-2">Analizando rutas...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-success" id="btn-apply-selected-redirects">
                        <i class="fas fa-check me-1"></i> Crear redirects seleccionados
                    </button>
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
    $('select[name="status"]').select2({ width: '100%' });

    // ── Pattern grouping view ─────────────────────────────────────────

    $('#btn-pattern-view').on('click', function () {
        var $btn = $(this);
        var groups = {};

        $('#logs-table-wrapper tbody tr').each(function () {
            var path = $(this).find('td:eq(1) code').text().trim();
            var pattern = path
                .replace(/\/\d+/g, '/{id}')
                .replace(/\/[a-z0-9-]{20,}/gi, '/{slug}');

            if (!groups[pattern]) {
                groups[pattern] = { count: 0, example: path };
            }
            groups[pattern].count++;
        });

        var rows = Object.entries(groups)
            .sort(function (a, b) { return b[1].count - a[1].count; })
            .map(function (entry) {
                return '<tr>' +
                    '<td><code>' + entry[0] + '</code></td>' +
                    '<td><span class="badge bg-secondary">' + entry[1].count + '</span></td>' +
                    '<td><small class="text-muted">' + entry[1].example + '</small></td>' +
                    '</tr>';
            }).join('');

        var html = '<table class="table table-sm table-hover align-middle mb-0">' +
            '<thead class="table-light"><tr><th>Patrón</th><th>Ocurrencias</th><th>Ejemplo</th></tr></thead>' +
            '<tbody>' + rows + '</tbody>' +
            '</table>';

        $('#pattern-view-container').html(html).show();
        $('#logs-table-wrapper').hide();
        $btn.html('<i class="fas fa-list me-1"></i> Ver lista completa').off('click').on('click', function () {
            $('#pattern-view-container').hide();
            $('#logs-table-wrapper').show();
            $btn.html('<i class="fas fa-layer-group me-1"></i> Ver por patrones');
            $btn.off('click').on('click', arguments.callee);
        });
    });

    // ── Suggest redirects modal ───────────────────────────────────────

    $('#btn-suggest-redirects').on('click', function () {
        $('#suggestModal').modal('show');
        $('#suggestions-body').html('<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i></div>');

        $.get('{{ route("setting.seo.404-logs.suggest-redirects") }}', function (data) {
            if (!data.suggestions || data.suggestions.length === 0) {
                $('#suggestions-body').html('<p class="text-muted">No hay sugerencias disponibles. Asegúrate de tener páginas con URLs canónicas configuradas.</p>');
                return;
            }

            var html = '<p class="text-muted">Selecciona las sugerencias que quieres aplicar. Puedes editar el destino antes de confirmar.</p>';
            html += '<div class="table-responsive"><table class="table table-sm"><thead><tr>' +
                '<th><input type="checkbox" id="select-all-suggestions"></th>' +
                '<th>404 Path</th><th>Hits</th><th>Destino sugerido</th><th>Confianza</th>' +
                '</tr></thead><tbody>';

            data.suggestions.forEach(function (s) {
                var badgeClass = s.confidence === 'high' ? 'bg-success' : (s.confidence === 'medium' ? 'bg-warning text-dark' : 'bg-secondary');
                html += '<tr>' +
                    '<td><input type="checkbox" class="suggestion-check" data-log-id="' + s.log_id + '" checked></td>' +
                    '<td><code>' + $('<div>').text(s.path).html() + '</code><br><small class="text-muted">' + s.hit_count + ' hits</small></td>' +
                    '<td>' + s.hit_count + '</td>' +
                    '<td><input type="text" class="form-control form-control-sm suggestion-target" data-log-id="' + s.log_id + '" value="' + $('<div>').text(s.suggested_target).html() + '"></td>' +
                    '<td><span class="badge ' + badgeClass + '">' + s.confidence + ' (' + s.similarity + '%)</span></td>' +
                    '</tr>';
            });

            html += '</tbody></table></div>';
            $('#suggestions-body').html(html);

            $('#select-all-suggestions').on('change', function () {
                $('.suggestion-check').prop('checked', $(this).is(':checked'));
            });
        }).fail(function () {
            $('#suggestions-body').html('<div class="alert alert-danger">Error al cargar sugerencias.</div>');
        });
    });

    $('#btn-apply-selected-redirects').on('click', function () {
        var redirects = [];
        $('.suggestion-check:checked').each(function () {
            var logId = $(this).data('log-id');
            var target = $('.suggestion-target[data-log-id="' + logId + '"]').val();
            if (target) {
                redirects.push({ log_id: logId, target: target });
            }
        });

        if (redirects.length === 0) {
            toastr.warning('No hay sugerencias seleccionadas.');
            return;
        }

        var $btn = $(this);
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Procesando...');

        $.ajax({
            url: '{{ route("setting.seo.404-logs.bulk-create-redirects") }}',
            method: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                redirects: redirects,
            },
            success: function (data) {
                $('#suggestModal').modal('hide');
                toastr.success(data.message);
                setTimeout(function () { location.reload(); }, 1500);
            },
            error: function () {
                toastr.error('Error al crear los redirects.');
            },
        }).always(function () {
            $btn.prop('disabled', false).html('<i class="fas fa-check me-1"></i> Crear redirects seleccionados');
        });
    });

    // ── Bulk create all → / ───────────────────────────────────────────

    $('#btn-create-all-redirects').on('click', function () {
        if (!confirm('¿Crear redirects a / para todos los 404s sin redirect? Podrás editarlos después.')) {
            return;
        }

        var redirects = [];
        $('table tbody tr').each(function () {
            var logId = $(this).data('log-id');
            if (logId) {
                redirects.push({ log_id: logId, target: '/' });
            }
        });

        if (redirects.length === 0) {
            toastr.warning('No hay entradas en la página actual.');
            return;
        }

        $.ajax({
            url: '{{ route("setting.seo.404-logs.bulk-create-redirects") }}',
            method: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                redirects: redirects,
            },
            success: function (data) {
                toastr.success(data.message);
                setTimeout(function () { location.reload(); }, 1500);
            },
            error: function () {
                toastr.error('Error al crear los redirects.');
            },
        });
    });

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
        $btn.prop('disabled', true).text('Procesando...');

        if (action === 'delete') {
            if (!confirm('¿Eliminar ' + ids.length + ' entrada(s)?')) {
                $btn.prop('disabled', false).text('Aplicar');
                return;
            }
        }

        $.ajax({
            url: '{{ route("setting.seo.404-logs.bulk-action") }}',
            method: 'POST',
            data: JSON.stringify({ action: action, ids: ids, _token: $('meta[name="csrf-token"]').attr('content') }),
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                $('#bulk-modal').modal('hide');
                toastr.success(res.count + ' entrada(s) actualizadas.');
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
