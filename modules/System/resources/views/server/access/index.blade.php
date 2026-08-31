@extends('layouts.theme')

@section('page_header')
    @include('core::components.card', ['title' => 'Registros de Acceso'])
@endsection

@section('content')

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">
            {{-- Header --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Registros de acceso del servidor</h5>
                        <p class="small mb-0 text-muted">Monitorea y gestiona los registros de acceso y actividad del sistema en tiempo real</p>
                    </div>
                    <div class="ms-auto">
                        <div class="btn-group">
                            <button type="button" class="btn bg-primary-subtle text-primary dropdown-toggle"
                                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                Acciones
                            </button>
                            <div class="dropdown-menu dropdown-menu-end">
                                <a class="dropdown-item" href="{{ route('settings.system.access.stats') }}">Estadísticas</a>
                                <a class="dropdown-item" href="{{ route('settings.system.access.download') }}">Descargar</a>
                                <div class="dropdown-divider"></div>
                                <button class="dropdown-item" type="button" onclick="clearLogs()">Limpiar registros</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Stats --}}
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Total registros</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($total) }}</h4>
                                <small class="text-muted">Registros almacenados</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Errores</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format(collect($logs)->where('level', 'ERROR')->count()) }}</h4>
                                <small class="text-muted">Registros de error</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Advertencias</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format(collect($logs)->where('level', 'WARNING')->count()) }}</h4>
                                <small class="text-muted">Registros de advertencia</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Informativos</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format(collect($logs)->where('level', 'INFO')->count()) }}</h4>
                                <small class="text-muted">Registros informativos</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Filters --}}
            <div class="card-body border-bottom">
                @php
                    $activeFilterCount = collect([
                        $source !== 'database',
                        ! empty($level),
                        (int) $limit !== 100,
                    ])->filter()->count();
                    $hasAnyFilter = $activeFilterCount > 0 || $search;
                @endphp
                <form id="access-filter-form" method="GET" action="{{ route('settings.system.access.index') }}">
                    <input type="hidden" name="source" id="filter-source" value="{{ $source }}">
                    <input type="hidden" name="level" id="filter-level" value="{{ $level }}">
                    <input type="hidden" name="limit" id="filter-limit" value="{{ $limit }}">

                    <div class="d-flex align-items-center gap-2">
                        <input type="search" name="search" class="form-control flex-grow-1"
                               placeholder="Buscar en mensaje..."
                               value="{{ $search }}">

                        <button type="button" class="btn btn-secondary position-relative flex-shrink-0"
                                data-bs-toggle="modal" data-bs-target="#access-filter-modal" title="Filtros avanzados">
                            <i class="fas fa-filter"></i>
                            @if($activeFilterCount > 0)
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary"
                                      style="font-size:0.6rem;">{{ $activeFilterCount }}</span>
                            @endif
                        </button>

                        <div class="d-flex gap-1 flex-shrink-0">
                            <button type="submit" class="btn btn-primary" title="Buscar">
                                <i class="fas fa-magnifying-glass"></i>
                            </button>
                            @if($hasAnyFilter)
                                <a href="{{ route('settings.system.access.index') }}"
                                   class="btn btn-secondary" title="Limpiar filtros">
                                    <i class="fas fa-xmark"></i>
                                </a>
                            @endif
                        </div>
                    </div>

                    @if($activeFilterCount > 0)
                        <div class="d-flex gap-2 flex-wrap mt-4">
                            <div>
                                <h6 class="mb-1">Filtrados:</h6>
                            </div>
                            @if($source !== 'database')
                                <span class="badge bg-primary-subtle text-primary py-1 px-2">
                                    Origen: Archivos
                                </span>
                            @endif
                            @if(! empty($level))
                                <span class="badge bg-primary-subtle text-primary py-1 px-2">
                                    Nivel: {{ $level }}
                                </span>
                            @endif
                            @if((int) $limit !== 100)
                                <span class="badge bg-primary-subtle text-primary py-1 px-2">
                                    Registros: {{ $limit }}
                                </span>
                            @endif
                        </div>
                    @endif
                </form>
            </div>

            {{-- Table --}}
            <div class="card-body">
                @if(count($logs) > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    @if($source === 'database')
                                        <th width="3%"><input type="checkbox" id="select-all" class="form-check-input"></th>
                                    @endif
                                    <th>Fecha y hora</th>
                                    <th style="width: 120px;">Nivel</th>
                                    <th>Mensaje</th>
                                    <th class="text-center" style="width: 80px;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($logs as $log)
                                    <tr>
                                        @if($source === 'database')
                                            <td><input type="checkbox" class="form-check-input bulk-checkbox" value="{{ $log['id'] }}"></td>
                                        @endif
                                        <td>
                                            <span class="text-muted">{{ $log['timestamp'] }}</span>
                                        </td>
                                        <td>
                                            @if($log['level'] === 'ERROR')
                                                <span class="badge bg-danger-subtle text-danger">{{ $log['level'] }}</span>
                                            @elseif($log['level'] === 'WARNING')
                                                <span class="badge bg-warning-subtle text-warning">{{ $log['level'] }}</span>
                                            @elseif($log['level'] === 'INFO')
                                                <span class="badge bg-info-subtle text-info">{{ $log['level'] }}</span>
                                            @elseif($log['level'] === 'DEBUG')
                                                <span class="badge bg-secondary-subtle text-secondary">{{ $log['level'] }}</span>
                                            @else
                                                <span class="badge bg-light text-black">{{ $log['level'] }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="text-muted small">{{ Str::limit($log['message'], 100) }}</span>
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a href="#" class="text-muted" data-bs-toggle="dropdown">
                                                    <i class="fa fa-ellipsis-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="javascript:void(0)" onclick="showLogDetail({{ $loop->index }})">
                                                            Ver detalles
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
                                <i class="fas fa-file-alt fs-7"></i>
                            </div>
                            <h6 class="mb-1">No hay registros disponibles</h6>
                            <p class="text-muted mb-0">No se encontraron registros con los filtros seleccionados</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Filter modal --}}
    <div class="modal fade" id="access-filter-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Filtros avanzados</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Origen</label>
                        <select id="modal-source" class="form-control select2-filter-modal">
                            <option value="database" {{ $source === 'database' ? 'selected' : '' }}>Base de datos</option>
                            <option value="file" {{ $source === 'file' ? 'selected' : '' }}>Archivos</option>
                        </select>
                    </div>
                    <div class="mb-3" id="modal-level-wrapper">
                        <label class="form-label fw-semibold">Nivel</label>
                        <select id="modal-level" class="form-control select2-filter-modal">
                            <option value="">Todos los niveles</option>
                            <option value="ERROR" {{ $level === 'ERROR' ? 'selected' : '' }}>ERROR</option>
                            <option value="WARNING" {{ $level === 'WARNING' ? 'selected' : '' }}>WARNING</option>
                            <option value="INFO" {{ $level === 'INFO' ? 'selected' : '' }}>INFO</option>
                            <option value="DEBUG" {{ $level === 'DEBUG' ? 'selected' : '' }}>DEBUG</option>
                        </select>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-semibold">Registros a mostrar</label>
                        <select id="modal-limit" class="form-control select2-filter-modal">
                            <option value="50" {{ (int) $limit === 50 ? 'selected' : '' }}>50 registros</option>
                            <option value="100" {{ (int) $limit === 100 ? 'selected' : '' }}>100 registros</option>
                            <option value="250" {{ (int) $limit === 250 ? 'selected' : '' }}>250 registros</option>
                            <option value="500" {{ (int) $limit === 500 ? 'selected' : '' }}>500 registros</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" id="access-filter-apply-btn" class="btn btn-primary w-100 mb-1">
                        Aplicar filtros
                    </button>
                    <button type="button" id="access-filter-clear-btn" class="btn btn-secondary w-100">
                        Limpiar
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal limpiar registros --}}
    <div id="clearLogsModal" class="modal fade">
        <div class="modal-dialog modal-md modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center px-4 pb-4">
                    <div class="mb-3">
                        <i class="fas fa-exclamation-triangle fa-3x text-warning"></i>
                    </div>
                    <h4 class="mb-2">Limpiar todos los registros</h4>
                    <p class="text-muted mb-4">Se eliminarán permanentemente todos los registros de acceso del sistema. Esta acción no se puede deshacer.</p>
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-danger w-100" id="confirmClearBtn">
                            <i class="fas fa-trash me-2"></i> Confirmar y limpiar
                        </button>
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($source === 'database')
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
                    <p class="text-muted mb-3">Se aplicará la acción sobre <strong><span data-bulk-count>0</span> registro(s)</strong>.</p>
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
    @endif

    {{-- Modal detalles del log --}}
    <div class="modal fade" id="logDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detalles del registro</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label text-muted fw-semibold mb-1">Fecha y hora</label>
                            <p class="fw-semibold mb-0" id="modalTimestamp">-</p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted fw-semibold mb-1">Nivel</label>
                            <div id="modalLevel"></div>
                        </div>
                    </div>

                    <div id="databaseInfo" class="d-none">
                        <hr class="my-3">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label text-muted fw-semibold mb-1">Dirección IP</label>
                                <p class="fw-semibold small mb-0" id="modalIp">-</p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted fw-semibold mb-1">Usuario ID</label>
                                <p class="fw-semibold small mb-0" id="modalUser">-</p>
                            </div>
                        </div>
                        <div class="mt-3">
                            <label class="form-label text-muted fw-semibold mb-1">URL</label>
                            <p class="small text-break mb-0" id="modalUrl">-</p>
                        </div>
                        <div class="mt-3">
                            <label class="form-label text-muted fw-semibold mb-1">Context (JSON)</label>
                            <pre id="modalContext" class="bg-light p-3 rounded small mb-0"
                                 style="white-space: pre-wrap; word-wrap: break-word; max-height: 150px; overflow-y: auto;">-</pre>
                        </div>
                    </div>

                    <hr class="my-3">
                    <div>
                        <label class="form-label text-muted fw-semibold mb-2">Mensaje completo</label>
                        <div class="bg-light p-3 rounded">
                            <pre id="modalMessage" class="mb-0 small"
                                 style="white-space: pre-wrap; word-wrap: break-word;">-</pre>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary w-100 mb-1" onclick="copyMessageToClipboard()">
                        Copiar mensaje
                    </button>
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    const logsData = @json($logs);

    function getLevelBadge(level) {
        var map = {
            'ERROR': '<span class="badge bg-danger-subtle text-danger">ERROR</span>',
            'WARNING': '<span class="badge bg-warning-subtle text-warning">WARNING</span>',
            'INFO': '<span class="badge bg-info-subtle text-info">INFO</span>',
            'DEBUG': '<span class="badge bg-secondary-subtle text-secondary">DEBUG</span>'
        };
        return map[level] || '<span class="badge bg-light text-black">' + level + '</span>';
    }

    window.showLogDetail = function (index) {
        var log = logsData[index];

        $('#modalTimestamp').text(log.timestamp);
        $('#modalLevel').html(getLevelBadge(log.level));
        $('#modalMessage').text(log.message);

        if (log.id) {
            $('#databaseInfo').show();
            $('#modalIp').text(log.ip_address || '-');
            $('#modalUser').text(log.user_id || '-');
            $('#modalUrl').text(log.url || '-');
            $('#modalContext').text(log.context ? JSON.stringify(log.context, null, 2) : '-');
        } else {
            $('#databaseInfo').hide();
        }

        new bootstrap.Modal(document.getElementById('logDetailModal')).show();
    };

    window.copyMessageToClipboard = function () {
        navigator.clipboard.writeText($('#modalMessage').text()).then(function () {
            toastr.success('Mensaje copiado al portapapeles', 'Copiado');
        }).catch(function () {
            toastr.error('Error al copiar al portapapeles', 'Error');
        });
    };

    window.clearLogs = function () {
        new bootstrap.Modal(document.getElementById('clearLogsModal')).show();
    };

    // ── Filter modal ──────────────────────────────────────────────────
    $('.select2-filter-modal').select2({ dropdownParent: $('#access-filter-modal'), width: '100%' });

    function toggleModalLevelField() {
        if ($('#modal-source').val() === 'database') {
            $('#modal-level-wrapper').show();
        } else {
            $('#modal-level-wrapper').hide();
            $('#modal-level').val('').trigger('change');
        }
    }
    toggleModalLevelField();
    $('#modal-source').on('change', toggleModalLevelField);

    $('#access-filter-apply-btn').on('click', function () {
        $('#filter-source').val($('#modal-source').val());
        $('#filter-level').val($('#modal-source').val() === 'database' ? $('#modal-level').val() : '');
        $('#filter-limit').val($('#modal-limit').val());
        $('#access-filter-modal').modal('hide');
        $('#access-filter-form').submit();
    });

    $('#access-filter-clear-btn').on('click', function () {
        $('#modal-source').val('database').trigger('change');
        $('#modal-level').val('').trigger('change');
        $('#modal-limit').val('100').trigger('change');
    });

    $('#confirmClearBtn').on('click', function () {
        var modal = bootstrap.Modal.getInstance(document.getElementById('clearLogsModal'));
        modal.hide();

        var $btn = $(this);
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Limpiando...');

        $.ajax({
            url: '{{ route("settings.system.access.clear") }}',
            type: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (data) {
                $btn.prop('disabled', false).html('<i class="fas fa-trash me-2"></i> Confirmar y limpiar');
                if (data.success) {
                    toastr.success(data.message, 'Registros limpiados');
                    setTimeout(function () { location.reload(); }, 1500);
                } else {
                    toastr.error(data.message, 'Error');
                }
            },
            error: function () {
                $btn.prop('disabled', false).html('<i class="fas fa-trash me-2"></i> Confirmar y limpiar');
                toastr.error('Error al limpiar los registros', 'Error');
            }
        });
    });

    // ── Bulk actions (database source only) ──────────────────────────
    @if($source === 'database')
    var bulk = window.BulkActions.init({ checkbox: '.bulk-checkbox' });

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
        if (!ids.length) { toastr.warning('Selecciona al menos un registro.'); return; }
        if (action === 'delete' && !confirm('¿Eliminar ' + ids.length + ' registro(s)?')) { return; }

        var $btn = $(this);
        $btn.prop('disabled', true).text('Procesando...');

        $.ajax({
            url: '{{ route("settings.system.access.bulk-action") }}',
            method: 'POST',
            data: JSON.stringify({ action: action, ids: ids, _token: $('meta[name="csrf-token"]').attr('content') }),
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                $('#bulk-modal').modal('hide');
                toastr.success(res.count + ' registro(s) eliminados.');
                setTimeout(function () { location.reload(); }, 800);
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message ?? 'Error al procesar.');
                $btn.prop('disabled', false).text('Aplicar');
            }
        });
    });
    @endif
});
</script>
@endpush
