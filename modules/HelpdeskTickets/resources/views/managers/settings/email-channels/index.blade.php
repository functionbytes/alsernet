@extends('layouts.theme')

@section('title', 'Canales de correo')

@section('page_header')
    @include('core::components.card', ['title' => 'Canales de correo'])
@endsection

@section('content')

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">

            {{-- Header --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Canales de correo</h5>
                        <p class="small mb-0 text-muted">Cada buzon conectado es un canal independiente: los correos que le lleguen se convierten en tickets o en respuestas de seguimiento</p>
                    </div>
                    <div class="ms-auto">
                        <a href="{{ route('manager.helpdesk.settings.email-channels.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> Nuevo canal
                        </a>
                    </div>
                </div>
            </div>

            {{-- Stats --}}
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-6 col-md-4">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Total</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['total']) }}</h4>
                                <small class="text-muted">Canales configurados</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Generando tickets</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['creating_tickets']) }}</h4>
                                <small class="text-muted">Habilitados</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Con errores</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['with_errors']) }}</h4>
                                <small class="text-muted">Ultima sincronizacion fallida</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Search --}}
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('manager.helpdesk.settings.email-channels.index') }}" id="filterForm">
                    <div class="d-flex gap-2 align-items-center">
                        <div class="flex-fill">
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-1">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="search" name="search" class="form-control -0 ps-0"
                                       placeholder="Buscar por nombre, usuario o servidor..."
                                       value="{{ $search }}">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary flex-shrink-0">
                            <i class="fas fa-search"></i>
                        </button>
                        @if($search !== '')
                            <a href="{{ route('manager.helpdesk.settings.email-channels.index') }}"
                               class="btn btn-outline-secondary flex-shrink-0" title="Limpiar">
                                <i class="fas fa-times"></i>
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            {{-- Table --}}
            <div class="card-body">
                @if($connections->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle text-nowrap" id="channels-table">
                            <thead class="table-light">
                                <tr>
                                    <th width="3%"><input type="checkbox" id="select-all" class="form-check-input"></th>
                                    <th>Canal</th>
                                    <th>Servidor</th>
                                    <th class="text-center">Tickets</th>
                                    <th class="text-center">Respuestas</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($connections as $c)
                                    @php
                                        $hasError = ! empty($c['last_error']);
                                        $neverChecked = empty($c['last_checked_at']);
                                    @endphp
                                    <tr data-id="{{ $c['id'] }}">
                                        <td><input type="checkbox" class="form-check-input bulk-checkbox" value="{{ $c['id'] }}"></td>
                                        <td>
                                            <span class="fw-semibold">{{ $c['name'] }}</span>
                                            @if($c['is_default'] ?? false)
                                                <span class="badge bg-primary-subtle text-primary ms-1" title="Se usa para responder tickets sin correo entrante (formularios, widget, alta manual)">Por defecto</span>
                                            @endif
                                            <div><small class="text-muted">{{ $c['username'] }}</small></div>
                                            @if(! empty($c['sync_since']))
                                                <div><small class="text-muted"><i class="fas fa-calendar-day me-1"></i>Sincroniza desde {{ \Illuminate\Support\Carbon::parse($c['sync_since'])->format('d/m/Y') }}</small></div>
                                            @endif
                                        </td>
                                        <td>
                                            <code class="bg-light px-2 py-1 rounded small">{{ $c['host'] }}:{{ $c['port'] }}</code>
                                            <div class="mt-1">
                                                @if(! empty($c['smtp_host']))
                                                    <small class="text-muted"><i class="fas fa-paper-plane me-1"></i>SMTP {{ $c['smtp_host'] }}:{{ $c['smtp_port'] ?? 465 }}</small>
                                                @else
                                                    <small class="text-muted">Sin SMTP &mdash; responde por el correo por defecto</small>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            @if($c['create_tickets'] ?? false)
                                                <span class="badge bg-success-subtle text-success">Si</span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary">No</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($c['create_replies'] ?? false)
                                                <span class="badge bg-success-subtle text-success">Si</span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary">No</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            {{-- Solo el estado. El "hace N horas" que iba debajo
                                                 sobraba: en un canal que falla desde ayer lo unico
                                                 que decia era cuando se intento por ultima vez, y
                                                 se leia como si el error acabara de ocurrir. El
                                                 mensaje real sigue en el title del badge. --}}
                                            @if($neverChecked)
                                                <span class="badge bg-secondary-subtle text-secondary">Sin sincronizar</span>
                                            @elseif($hasError)
                                                <span class="badge bg-warning-subtle text-warning" title="{{ $c['last_error'] }}">Error</span>
                                            @else
                                                <span class="badge bg-success-subtle text-success">OK</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a href="#" class="text-muted" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('manager.helpdesk.settings.email-channels.edit', $c['id']) }}">
                                                            Editar
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item btn-sync-channel" href="#" data-id="{{ $c['id'] }}">
                                                            Sincronizar ahora
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a class="dropdown-item delete-btn" href="#"
                                                           data-bs-toggle="modal"
                                                           data-bs-target="#delete-modal"
                                                           data-url="{{ route('manager.helpdesk.settings.email-channels.destroy', $c['id']) }}"
                                                           data-title="Eliminar canal: {{ $c['name'] }}">
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
                        <i class="fas fa-envelope-open-text fa-3x mb-3 text-muted opacity-50"></i>
                        <h5 class="fw-bold mb-2">
                            @if($search !== '')
                                No se encontraron resultados
                            @else
                                No hay canales de correo configurados
                            @endif
                        </h5>
                        <p class="text-muted mb-4">
                            @if($search !== '')
                                No hay resultados para "{{ $search }}"
                            @else
                                Agrega una cuenta IMAP para que sus correos entrantes generen tickets automaticamente
                            @endif
                        </p>
                        @if($search !== '')
                            <a href="{{ route('manager.helpdesk.settings.email-channels.index') }}" class="btn btn-secondary">Limpiar filtros</a>
                        @else
                            <a href="{{ route('manager.helpdesk.settings.email-channels.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i> Nuevo canal
                            </a>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Pagination --}}
            @if($connections->hasPages())
                <div class="card-footer bg-white border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted small">
                            Mostrando {{ $connections->firstItem() }} - {{ $connections->lastItem() }} de {{ $connections->total() }}
                        </div>
                        <div>
                            {{ $connections->appends(request()->input())->links() }}
                        </div>
                    </div>
                </div>
            @endif

        </div>
    </div>

    @include('core::components.delete')

    {{-- Bulk toolbar flotante --}}
    <div id="bulk-toolbar" class="position-fixed bottom-0 start-50 translate-middle-x mb-4 d-none hdt-floating-bar">
        <button type="button" class="btn btn-primary shadow-lg px-4" data-bs-toggle="modal" data-bs-target="#bulk-modal">
            <span data-bulk-count>0</span> seleccionado(s) &mdash; Aplicar accion
        </button>
    </div>

    {{-- Bulk modal --}}
    <div class="modal fade" id="bulk-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Accion masiva</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">Se aplicara la accion sobre <strong><span data-bulk-count>0</span> canal(es)</strong>.</p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Accion</label>
                        <select id="bulk-action-select" class="form-select select2">
                            <option value="">Seleccionar accion...</option>
                            <option value="activate">Generar tickets: activar</option>
                            <option value="deactivate">Generar tickets: desactivar</option>
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

@endsection

@push('css')
    <link rel="stylesheet" href="{{ asset('modules/helpdesktickets/css/helpdesktickets-ui.css') }}?v={{ @filemtime(public_path('modules/helpdesktickets/css/helpdesktickets-ui.css')) }}">
@endpush

@push('scripts')
<script src="{{ asset('core/js/bulk.js?v=2') }}"></script>
{{-- Solo datos: la lógica entera vive en email-channels-index.js. --}}
<script>
window.hdtEmailChannelsIndexConfig = {
    syncUrlBase: @json(url('panel/helpdesk/settings/tickets/email-channels')),
    bulkActionUrl: @json(route('manager.helpdesk.settings.email-channels.bulk-action')),
    successMessage: @json(session('success')),
    errorMessage: @json(session('error')),
};
</script>
<script src="{{ asset('modules/helpdesktickets/js/email-channels-index.js') }}"></script>
@endpush
