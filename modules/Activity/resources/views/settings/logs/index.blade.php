@extends('layouts.theme')

@section('title', 'Registro de cambios')

@section('page_header')
    @include('core::components.card', ['title' => 'Registro de cambios'])
@endsection

@section('content')

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">

            {{-- Header --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Registro de cambios</h5>
                        <p class="small mb-0 text-muted">Historial completo de acciones realizadas sobre los modelos del sistema</p>
                    </div>
                    <div class="ms-auto">
                        <div class="btn-group">
                            <button type="button" class="btn bg-primary-subtle text-primary dropdown-toggle"
                                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                Acciones
                            </button>
                            <div class="dropdown-menu dropdown-menu-end">
                                <a class="dropdown-item" href="{{ route('activity.export', request()->query()) }}">Exportar CSV</a>
                                <div class="dropdown-divider"></div>
                                <button id="refresh-stats-btn" type="button" class="dropdown-item">
                                    Refrescar
                                </button>
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
                                <h4 class="mb-1 fw-bold" data-stat="total">{{ number_format($stats['total']) }}</h4>
                                <small class="text-muted">En el sistema</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Creaciones</h6>
                                <h4 class="mb-1 fw-bold" data-stat="created">{{ number_format($stats['created']) }}</h4>
                                <small class="text-muted">Registros creados</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Actualizaciones</h6>
                                <h4 class="mb-1 fw-bold" data-stat="updated">{{ number_format($stats['updated']) }}</h4>
                                <small class="text-muted">Registros modificados</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Eliminaciones</h6>
                                <h4 class="mb-1 fw-bold" data-stat="deleted">{{ number_format($stats['deleted']) }}</h4>
                                <small class="text-muted">Registros eliminados</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Busqueda y filtros --}}
            <div class="card-body border-bottom">
                @php
                    $advancedKeys = ['user_id', 'subject_type', 'event', 'from', 'to'];
                    $activeFilterCount = collect($advancedKeys)->filter(fn ($k) => request()->filled($k))->count();
                    $hasAnyFilter = $activeFilterCount > 0 || request()->filled('search');
                @endphp

                <form method="GET" action="{{ route('activity.logs') }}" id="logs-filter-form">
                    {{-- Los avanzados viajan ocultos: el modal solo escribe en
                         ellos al aplicar, para que cerrar el modal sin aplicar
                         no cambie la busqueda. --}}
                    <input type="hidden" name="user_id"      id="filter-user"    value="{{ request('user_id') }}">
                    <input type="hidden" name="subject_type" id="filter-subject" value="{{ request('subject_type') }}">
                    <input type="hidden" name="event"        id="filter-event"   value="{{ request('event') }}">
                    <input type="hidden" name="from"         id="filter-from"    value="{{ request('from') }}">
                    <input type="hidden" name="to"           id="filter-to"      value="{{ request('to') }}">

                    <div class="d-flex align-items-center gap-2">
                        <input type="search" name="search" class="form-control flex-grow-1"
                               placeholder="Buscar en descripción o en los datos del cambio..."
                               value="{{ request('search') }}">

                        <button type="button" class="btn btn-secondary position-relative flex-shrink-0"
                                data-bs-toggle="modal" data-bs-target="#logs-filter-modal" title="Filtros avanzados">
                            <i class="fas fa-filter"></i>
                            @if($activeFilterCount > 0)
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary act-filter-badge">{{ $activeFilterCount }}</span>
                            @endif
                        </button>

                        <div class="d-flex gap-1 flex-shrink-0">
                            <button type="submit" class="btn btn-primary" title="Buscar">
                                <i class="fas fa-magnifying-glass"></i>
                            </button>
                            @if($hasAnyFilter)
                                <a href="{{ route('activity.logs') }}" class="btn btn-secondary" title="Limpiar filtros">
                                    <i class="fas fa-xmark"></i>
                                </a>
                            @endif
                        </div>
                    </div>

                    @if($activeFilterCount > 0)
                        <div class="d-flex gap-2 flex-wrap mt-4 align-items-center">
                            <h6 class="mb-0">Filtrados:</h6>
                            @if(request('user_id'))
                                @php $causer = $causers->firstWhere('id', request('user_id')); @endphp
                                <span class="badge bg-primary-subtle text-primary py-1 px-2">
                                    Usuario: {{ $causer ? trim($causer->firstname.' '.$causer->lastname) ?: $causer->email : request('user_id') }}
                                </span>
                            @endif
                            @if(request('subject_type'))
                                <span class="badge bg-primary-subtle text-primary py-1 px-2">
                                    Entidad: {{ $subjectTypes[request('subject_type')] ?? class_basename(request('subject_type')) }}
                                </span>
                            @endif
                            @if(request('event'))
                                <span class="badge bg-primary-subtle text-primary py-1 px-2">
                                    Evento: {{ ucfirst(request('event')) }}
                                </span>
                            @endif
                            @if(request('from'))
                                <span class="badge bg-primary-subtle text-primary py-1 px-2">Desde: {{ request('from') }}</span>
                            @endif
                            @if(request('to'))
                                <span class="badge bg-primary-subtle text-primary py-1 px-2">Hasta: {{ request('to') }}</span>
                            @endif
                        </div>
                    @endif
                </form>
            </div>

            {{-- Table --}}
            <div class="card-body">
                @if($activities->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="3%"><input type="checkbox" id="select-all" class="form-check-input"></th>
                                    <th>Usuario</th>
                                    <th>Acción</th>
                                    <th>Modelo</th>
                                    <th>Fecha</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($activities as $activity)
                                    <tr>
                                        <td><input type="checkbox" class="form-check-input bulk-checkbox" value="{{ $activity->id }}"></td>
                                        <td>
                                            <div class="small fw-semibold">{{ $activity->causer?->name ?? 'Sistema' }}</div>
                                            @if($activity->causer?->email)
                                                <small class="text-muted">{{ $activity->causer->email }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            @php
                                                $eventMap = ['created' => 'success', 'updated' => 'primary', 'deleted' => 'danger'];
                                                $color = $eventMap[$activity->event] ?? 'secondary';
                                            @endphp
                                            <span class="badge bg-{{ $color }}-subtle text-{{ $color }}">
                                                {{ $activity->event ?? 'n/a' }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border">{{ class_basename($activity->subject_type ?? '') ?: '-' }}</span>
                                        </td>
                                        <td>
                                            <div class="small">{{ $activity->created_at->format('d/m/Y H:i') }}</div>
                                            <small class="text-muted">{{ $activity->created_at->diffForHumans() }}</small>
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a href="#" class="text-muted" data-bs-toggle="dropdown" data-bs-auto-close="true" data-bs-boundary="viewport">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('activity.logs.show', $activity->id) }}">
                                                            Ver detalle
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
                            <h6 class="mb-1">
                                @if(request()->hasAny(['search', 'from', 'to']))
                                    No se encontraron resultados
                                @else
                                    No hay registros de cambios
                                @endif
                            </h6>
                            <p class="text-muted mb-3">
                                @if(request('search'))
                                    No hay resultados para "{{ request('search') }}"
                                @else
                                    Aún no se han registrado acciones en el sistema
                                @endif
                            </p>
                            @if(request()->hasAny(['search', 'from', 'to']))
                                <a href="{{ route('activity.logs') }}" class="btn btn-sm btn-outline-secondary">Limpiar filtros</a>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            @if($activities->hasPages())
                <div class="card-footer">{{ $activities->links() }}</div>
            @endif

        </div>
    </div>

    {{-- Bulk toolbar flotante --}}
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

    {{-- Filtros avanzados --}}
    <div class="modal fade" id="logs-filter-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Filtros avanzados</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Usuario</label>
                        <select id="modal-user" class="form-control select2-filter-modal">
                            <option value="">Todos los usuarios</option>
                            @foreach($causers as $causer)
                                <option value="{{ $causer->id }}" @selected(request('user_id') == $causer->id)>
                                    {{ trim($causer->firstname.' '.$causer->lastname) ?: $causer->email }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Entidad</label>
                        <select id="modal-subject" class="form-control select2-filter-modal">
                            <option value="">Todas las entidades</option>
                            @foreach($subjectTypes as $type => $label)
                                <option value="{{ $type }}" @selected(request('subject_type') === $type)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Evento</label>
                        <select id="modal-event" class="form-control select2-filter-modal">
                            <option value="">Todos los eventos</option>
                            @foreach($events as $event)
                                <option value="{{ $event }}" @selected(request('event') === $event)>{{ ucfirst($event) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row g-2 mb-0">
                        <div class="col-6">
                            <label class="form-label fw-semibold">Desde</label>
                            <input type="date" id="modal-from" class="form-control" value="{{ request('from') }}">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Hasta</label>
                            <input type="date" id="modal-to" class="form-control" value="{{ request('to') }}">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" id="logs-filter-apply-btn" class="btn btn-primary w-100 mb-1">Aplicar filtros</button>
                    <button type="button" id="logs-filter-clear-btn" class="btn btn-secondary w-100">Limpiar</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('styles')
<style>
    .act-filter-badge { font-size: .6rem; }
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function () {
    // ── Filtros avanzados ────────────────────────────────────────────────
    $('.select2-filter-modal').select2({ dropdownParent: $('#logs-filter-modal'), width: '100%' });

    $('#logs-filter-apply-btn').on('click', function () {
        $('#filter-user').val($('#modal-user').val());
        $('#filter-subject').val($('#modal-subject').val());
        $('#filter-event').val($('#modal-event').val());
        $('#filter-from').val($('#modal-from').val());
        $('#filter-to').val($('#modal-to').val());
        $('#logs-filter-modal').modal('hide');
        $('#logs-filter-form').submit();
    });

    $('#logs-filter-clear-btn').on('click', function () {
        window.location = '{{ route('activity.logs') }}';
    });

    @if(session('success'))
        toastr.success('{{ session('success') }}', 'Éxito');
    @endif
    @if(session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif

    // Bulk actions
    const bulk = window.BulkActions.init({ checkbox: '.bulk-checkbox' });

    $('#bulk-action-select').select2({ dropdownParent: $('#bulk-modal'), width: '100%' });

    $('#bulk-modal').on('hide.bs.modal', function () {
        $('#bulk-action-select').val('').trigger('change');
        $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
        bulk.reset();
    });

    // Refresh stats
    $('#refresh-stats-btn').on('click', function () {
        const $btn  = $(this);
        const $icon = $btn.find('i');
        $btn.prop('disabled', true);
        $icon.addClass('fa-spin');

        $.getJSON('{{ route('activity.logs.stats') }}')
            .done(function (data) {
                $('[data-stat="total"]').text(new Intl.NumberFormat().format(data.total));
                $('[data-stat="created"]').text(new Intl.NumberFormat().format(data.created));
                $('[data-stat="updated"]').text(new Intl.NumberFormat().format(data.updated));
                $('[data-stat="deleted"]').text(new Intl.NumberFormat().format(data.deleted));
                toastr.success('Stats actualizados');
            })
            .fail(function (xhr) {
                toastr.error(xhr.responseJSON?.message ?? 'Error al refrescar.');
            })
            .always(function () {
                $btn.prop('disabled', false);
                $icon.removeClass('fa-spin');
            });
    });

    $('#bulk-apply-btn').on('click', function () {
        const action = $('#bulk-action-select').val();
        const ids    = bulk.getIds();

        if (!action) { toastr.warning('Selecciona una acción.'); return; }
        if (!ids.length) { toastr.warning('Selecciona al menos un registro.'); return; }
        if (action === 'delete' && !confirm('¿Eliminar los ' + ids.length + ' registro(s) seleccionados?')) { return; }

        $('#bulk-apply-btn').prop('disabled', true).text('Procesando...');

        $.ajax({
            url: '{{ route('activity.logs.bulk-action') }}',
            method: 'POST',
            data: JSON.stringify({ action: action, ids: ids, _token: $('meta[name="csrf-token"]').attr('content') }),
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                $('#bulk-modal').modal('hide');
                toastr.success(res.message || res.count + ' registro(s) eliminados.');
                setTimeout(() => location.reload(), 800);
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message ?? 'Error al procesar.');
                $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
            },
        });
    });
});
</script>
@endpush
