@extends('layouts.theme')

@section('title', 'Registro de auditoría')

@section('content')
<div class="widget-content searchable-container list">
    @include('core::components.alerts')

    {{-- Main Card --}}
    <div class="card">
        {{-- Card Header --}}
        <div class="card-header p-4 border-bottom border-light text-light">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1 fw-bold">Registro de auditoría</h5>
                    <p class="small mb-0 text-black">Seguimiento de todas las actividades y cambios en el sistema</p>
                </div>
                <div class="d-flex gap-2">
                    @if(request()->hasAny(['event', 'user_id', 'start_date', 'end_date']))
                        <a href="{{ route('settings.chat.audits.index') }}" class="btn btn-secondary"
                           title="Limpiar todos los filtros aplicados">
                            <i class="fas fa-times me-1"></i> Limpiar filtros
                        </a>
                    @endif
                    <button type="button" class="btn btn-primary" onclick="window.location.reload()"
                            title="Actualizar la lista de registros">
                        <i class="fas fa-sync-alt me-1"></i> Actualizar
                    </button>
                </div>
            </div>
        </div>

        {{-- Status Cards --}}
        <div class="card-body border-bottom">
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="card bg-light-secondary h-100">
                        <div class="card-body position-relative">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="card-title mb-2">Registros totales</h6>
                                    <h2 >{{ $logs->total() }}</h2>
                                    <small class="text-muted">Total de eventos registrados</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card bg-light-secondary h-100">
                        <div class="card-body position-relative">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="card-title mb-2">Eventos de creación</h6>
                                    <h2 >{{ $createdCount }}</h2>
                                    <small class="text-muted">Recursos nuevos creados</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card bg-light-secondary h-100">
                        <div class="card-body position-relative">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="card-title mb-2">Eventos de actualización</h6>
                                    <h2 >{{ $updatedCount }}</h2>
                                    <small class="text-muted">Modificaciones realizadas</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card bg-light-secondary h-100">
                        <div class="card-body position-relative">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="card-title mb-2">Eventos de eliminación</h6>
                                    <h2 >{{ $deletedCount }}</h2>
                                    <small class="text-muted">Recursos eliminados</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tab Content (Single Tab) --}}
        <div class="card-body">
            {{-- Filter Button Section --}}
            <div class="mb-4 d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="mb-1 fw-bold">Registros de auditoría</h6>
                    <p class="text-muted small mb-0">
                        @if(request()->hasAny(['event', 'user_id', 'start_date', 'end_date']))
                            <span class="badge bg-primary me-2">
                                <i class="fas fa-filter me-1"></i> Filtros activos
                            </span>
                        @endif
                        Historial completo de actividades del sistema
                    </p>
                </div>
                <div>
                    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#filtersModal">
                        <i class="fas fa-sliders me-1"></i> Filtros avanzados
                    </button>
                </div>
            </div>

            {{-- Info Panel --}}
            <div class="alert alert-info border-0 bg-info-subtle d-flex justify-content-between align-items-center mb-3">
                <div class="d-flex gap-4 align-items-center">
                    <div>
                        <span class="badge bg-white text-primary me-2">
                            <i class="fas fa-info-circle me-1"></i> Información
                        </span>
                        <small class="text-muted">Los registros de auditoría se conservan por 90 días</small>
                    </div>
                    <div class="vr"></div>
                    <div>
                        <span class="badge bg-white text-secondary me-2">
                            <i class="fas fa-shield-alt me-1"></i> Seguridad
                        </span>
                        <small class="text-muted">Todos los cambios son rastreados</small>
                    </div>
                </div>
                <div>
                    <small class="text-muted">
                        <i class="fas fa-database me-1"></i>
                        <strong id="recordCount">{{ $logs->total() }}</strong> registros totales
                    </small>
                </div>
            </div>

            {{-- Table Section --}}
            <div class="mb-4">
                <div class="table-responsive">
                    <table class="table table-hover" id="auditsTable">
                        <thead class="table-light">
                            <tr>
                                <th>Fecha y hora</th>
                                <th>Usuario</th>
                                <th>Evento</th>
                                <th>Entidad</th>
                                <th>IP</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="auditsTableBody">
                            @forelse($logs as $log)
                                <tr>
                                    <td>
                                        <small class="text-muted">
                                            {{ $log->created_at->format('d/m/Y') }}<br>
                                            <strong>{{ $log->created_at->format('H:i:s') }}</strong>
                                        </small>
                                    </td>
                                    <td>
                                        @if($log->user)
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center"
                                                     style="width: 32px; height: 32px; flex-shrink: 0; font-weight: 600; font-size: 14px;">
                                                    {{ strtoupper(substr($log->user->name, 0, 1)) }}
                                                </div>
                                                <div>
                                                    <strong>{{ $log->user->name }}</strong><br>
                                                    <small class="text-muted">{{ $log->user->email }}</small>
                                                </div>
                                            </div>
                                        @else
                                            <span class="badge bg-info-subtle text-info">
                                                <i class="fas fa-cog me-1"></i> Sistema
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $eventTranslations = [
                                                'created' => 'Creado',
                                                'updated' => 'Actualizado',
                                                'deleted' => 'Eliminado',
                                                'restored' => 'Restaurado',
                                                'force_deleted' => 'Eliminado permanente',
                                            ];
                                            $eventColors = [
                                                'created' => 'success',
                                                'updated' => 'primary',
                                                'deleted' => 'danger',
                                                'restored' => 'info',
                                                'force_deleted' => 'danger',
                                            ];
                                            $eventLabel = $eventTranslations[$log->event] ?? ucfirst(str_replace('_', ' ', $log->event));
                                            $color = $eventColors[$log->event] ?? 'secondary';
                                        @endphp
                                        <span class="badge bg-{{ $color }}">{{ $eventLabel }}</span>
                                    </td>
                                    <td>
                                        @if($log->auditable)
                                            <span class="text-muted small">{{ class_basename($log->auditable_type) }}</span><br>
                                            <strong>{{ $log->auditable->name ?? $log->auditable->title ?? '#' . $log->auditable->id }}</strong>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <small class="text-muted font-monospace">{{ $log->ip_address ?? '-' }}</small>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-primary waves-effect"
                                                    data-log-id="{{ $log->id }}"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#auditDetailsModal">
                                                <i class="fas fa-eye"></i> Detalles
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">
                                        <i class="fas fa-circle-exclamation"></i> No hay registros de auditoría disponibles
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Pagination Footer --}}
            @if($logs->hasPages())
                <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                    <div class="text-muted small">
                        Mostrando <strong>{{ $logs->firstItem() }}</strong> a <strong>{{ $logs->lastItem() }}</strong>
                        de <strong>{{ $logs->total() }}</strong> registros
                    </div>
                    <div>
                        {{ $logs->appends(request()->query())->links() }}
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- Filters Modal --}}
<div class="modal fade" id="filtersModal" tabindex="-1" aria-labelledby="filtersModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="filtersModalLabel">
                    Filtros avanzados
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form method="GET" action="{{ route('settings.chat.audits.index') }}" id="filtersForm">
                <div class="modal-body">
                    <p class="text-muted mb-4">
                        Utiliza los filtros para refinar la búsqueda de registros de auditoría. Puedes combinar múltiples criterios para encontrar eventos específicos.
                    </p>

                    <div class="row g-4">
                        <div class="col-12">
                            <label for="event" class="form-label fw-semibold">
                                Tipo de evento
                            </label>
                            <select name="event" id="event" class="form-control select2">
                                <option value="">Todos los eventos</option>
                                @forelse($events as $event)
                                    <option value="{{ $event }}" {{ request('event') == $event ? 'selected' : '' }}>
                                        {{ ucfirst(str_replace('_', ' ', $event)) }}
                                    </option>
                                @empty
                                    <option value="" disabled>No hay eventos registrados</option>
                                @endforelse
                            </select>
                            <small class="text-muted d-block mt-1">
                                Filtra por el tipo de acción realizada: creación, actualización, eliminación, etc.
                            </small>
                        </div>

                        <div class="col-12">
                            <label for="user_id" class="form-label fw-semibold">
                                Usuario
                            </label>
                            <select name="user_id" id="user_id" class="form-control select2">
                                <option value="">Todos los usuarios</option>
                                @forelse($users as $user)
                                    <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                        {{ $user->full_name }}
                                    </option>
                                @empty
                                    <option value="" disabled>No hay usuarios disponibles</option>
                                @endforelse
                            </select>
                            <small class="text-muted d-block mt-1">
                                Muestra solo los eventos realizados por el usuario seleccionado.
                            </small>
                        </div>

                        <div class="col-12">
                            <label for="start_date" class="form-label fw-semibold">
                                Fecha de inicio
                            </label>
                            <input type="date" name="start_date" id="start_date" class="form-control"
                                   value="{{ request('start_date') }}">
                            <small class="text-muted d-block mt-1">
                                Fecha inicial del período a consultar. Los registros desde esta fecha serán incluidos.
                            </small>
                        </div>

                        <div class="col-12">
                            <label for="end_date" class="form-label fw-semibold">
                                Fecha de fin
                            </label>
                            <input type="date" name="end_date" id="end_date" class="form-control"
                                   value="{{ request('end_date') }}">
                            <small class="text-muted d-block mt-1">
                                Fecha final del período a consultar. Los registros hasta esta fecha serán incluidos.
                            </small>
                        </div>
                    </div>

                    @if(request()->hasAny(['event', 'user_id', 'start_date', 'end_date']))
                        <div class="alert alert-info border-0 bg-info-subtle mt-4">
                            <div class="d-flex align-items-start gap-2">
                                <i class="fas fa-info-circle fs-5"></i>
                                <div>
                                    <strong>Filtros activos:</strong>
                                    <ul class="mb-0 mt-2">
                                        @if(request('event'))
                                            <li>Evento: <strong>{{ ucfirst(str_replace('_', ' ', request('event'))) }}</strong></li>
                                        @endif
                                        @if(request('user_id'))
                                            @php
                                                $selectedUser = $users->firstWhere('id', request('user_id'));
                                            @endphp
                                            <li>Usuario: <strong>{{ $selectedUser->full_name ?? request('user_id') }}</strong></li>
                                        @endif
                                        @if(request('start_date'))
                                            <li>Desde: <strong>{{ request('start_date') }}</strong></li>
                                        @endif
                                        @if(request('end_date'))
                                            <li>Hasta: <strong>{{ request('end_date') }}</strong></li>
                                        @endif
                                    </ul>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary w-100 mb-2">
                        <i class="fas fa-filter me-1"></i> Aplicar filtros
                    </button>
                    @if(request()->hasAny(['event', 'user_id', 'start_date', 'end_date']))
                        <a href="{{ route('settings.chat.audits.index') }}" class="btn btn-secondary w-100 mb-1">
                            <i class="fas fa-times me-1"></i> Limpiar filtros
                        </a>
                    @endif
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Audit Details Modal --}}
<div class="modal fade" id="auditDetailsModal" tabindex="-1" aria-labelledby="auditDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="auditDetailsModalLabel">
                    Detalles del registro de auditoría
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div id="auditDetailsContent">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Cerrar
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    /* Select2 styles for modal */
    #filtersModal .select2-container {
        width: 100% !important;
    }

    #filtersModal .select2-selection {
        height: 38px !important;
        padding: 0.375rem 0.75rem;
        border: 1px solid #dee2e6;
        border-radius: 0.375rem;
        background-color: #fff;
    }

    #filtersModal .select2-selection__rendered {
        line-height: 38px !important;
        padding-left: 0;
        color: #495057;
    }

    #filtersModal .select2-selection__arrow {
        height: 38px !important;
    }

    #filtersModal .select2-selection__placeholder {
        color: #6c757d;
    }

    /* Dropdown must be above modal */
    .select2-container--open {
        z-index: 9999 !important;
    }

    .select2-dropdown {
        z-index: 10000 !important;
        border: 1px solid #dee2e6;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    }

    .select2-container--open .select2-dropdown--below {
        border-top: 1px solid #dee2e6;
    }

    .select2-results__option--highlighted {
        background-color: #b10100 !important;
        color: white !important;
    }

    .select2-results__option {
        padding: 6px 12px;
    }
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    // Initialize Select2 for filter dropdowns when modal is shown
    $('#filtersModal').on('shown.bs.modal', function() {
        // Destroy any existing Select2 instances first
        if ($('#event').hasClass('select2-hidden-accessible')) {
            $('#event').select2('destroy');
        }
        if ($('#user_id').hasClass('select2-hidden-accessible')) {
            $('#user_id').select2('destroy');
        }

        // Initialize Select2 with proper configuration for modals
        $('#event').select2({
            width: '100%',
            dropdownParent: $('#filtersModal'),
            placeholder: 'Seleccione un evento',
            allowClear: true,
            language: {
                noResults: function() {
                    return 'No se encontraron resultados';
                }
            }
        });

        $('#user_id').select2({
            width: '100%',
            dropdownParent: $('#filtersModal'),
            placeholder: 'Seleccione un usuario',
            allowClear: true,
            language: {
                noResults: function() {
                    return 'No se encontraron resultados';
                }
            }
        });
    });

    // Destroy Select2 when modal is hidden to prevent conflicts
    $('#filtersModal').on('hidden.bs.modal', function() {
        if ($('#event').hasClass('select2-hidden-accessible')) {
            $('#event').select2('destroy');
        }
        if ($('#user_id').hasClass('select2-hidden-accessible')) {
            $('#user_id').select2('destroy');
        }
    });

    // Toastr configuration
    toastr.options = {
        closeButton: true,
        progressBar: true,
        positionClass: "toast-bottom-right",
        timeOut: 3000,
        extendedTimeOut: 1000,
        tapToDismiss: false
    };

    // Show details function
    function showDetails(logId) {
        const $content = $('#auditDetailsContent');

        // Show loading
        const $loadingDiv = $('<div>').addClass('text-center py-4');
        const $spinner = $('<div>').addClass('spinner-border text-primary').attr('role', 'status');
        $spinner.append($('<span>').addClass('visually-hidden').text('Cargando...'));
        $loadingDiv.append($spinner);
        $content.empty().append($loadingDiv);

        // Fetch details via AJAX (you'll need to implement the endpoint)
        $.ajax({
            url: '/settings/chat/audits/' + logId,
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                $content.empty();

                if (data.success) {
                    const log = data.log;

                    // Build detail view
                    let html = '<div class="mb-3">';
                    html += '<h6 class="fw-bold">Información general</h6>';
                    html += '<div class="row g-3">';
                    html += '<div class="col-md-6"><strong>Evento:</strong> ' + log.event + '</div>';
                    html += '<div class="col-md-6"><strong>Usuario:</strong> ' + (log.user ? log.user.name : 'Sistema') + '</div>';
                    html += '<div class="col-md-6"><strong>Fecha:</strong> ' + log.created_at + '</div>';
                    html += '<div class="col-md-6"><strong>IP:</strong> ' + (log.ip_address || '-') + '</div>';
                    html += '</div>';
                    html += '</div>';

                    if (log.user_agent) {
                        html += '<div class="mb-3">';
                        html += '<h6 class="fw-bold">Navegador</h6>';
                        html += '<small class="text-muted">' + log.user_agent + '</small>';
                        html += '</div>';
                    }

                    if (log.old_values || log.new_values) {
                        html += '<div class="mb-3">';
                        html += '<h6 class="fw-bold">Cambios realizados</h6>';
                        html += '<div class="row">';

                        if (log.old_values) {
                            html += '<div class="col-md-6">';
                            html += '<h6 class="text-danger small">Valores anteriores</h6>';
                            html += '<div class="bg-light-secondary p-3 rounded">';
                            html += '<pre class="mb-0" style="font-size: 12px;">' + JSON.stringify(log.old_values, null, 2) + '</pre>';
                            html += '</div>';
                            html += '</div>';
                        }

                        if (log.new_values) {
                            html += '<div class="col-md-' + (log.old_values ? '6' : '12') + '">';
                            html += '<h6 class="text-success small">Valores nuevos</h6>';
                            html += '<div class="bg-light-secondary p-3 rounded">';
                            html += '<pre class="mb-0" style="font-size: 12px;">' + JSON.stringify(log.new_values, null, 2) + '</pre>';
                            html += '</div>';
                            html += '</div>';
                        }

                        html += '</div>';
                        html += '</div>';
                    }

                    $content.html(html);
                } else {
                    $content.html('<div class="alert alert-danger">' + (data.message || 'Error al cargar detalles') + '</div>');
                }
            },
            error: function(xhr, status, error) {
                $content.empty();
                const $alert = $('<div>').addClass('alert alert-danger');
                $alert.append($('<i>').addClass('fas fa-exclamation-triangle me-2'));
                $alert.append(document.createTextNode('Error al cargar detalles: ' + error));
                $content.append($alert);
            }
        });
    }

    // Event delegation for detail buttons
    $(document).on('click', '[data-bs-target="#auditDetailsModal"]', function() {
        const logId = $(this).data('log-id');
        showDetails(logId);
    });

    // Session notifications
    @if(session('success'))
        toastr.success('{{ session('success') }}');
    @endif

    @if(session('error'))
        toastr.error('{{ session('error') }}');
    @endif

    @if(session('warning'))
        toastr.warning('{{ session('warning') }}');
    @endif

    @if(session('info'))
        toastr.info('{{ session('info') }}');
    @endif
});
</script>
@endpush
