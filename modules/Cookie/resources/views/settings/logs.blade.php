@extends('layouts.theme')

@section('title', 'Registros de consentimiento')

@section('content')

    @include('core::components.card', ['title' => 'Registros de consentimiento de cookies'])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">

            {{-- Header --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Registros de consentimiento</h5>
                        <p class="small mb-0 text-muted">Historial de decisiones de consentimiento de cookies de los visitantes</p>
                    </div>
                    <div class="ms-auto">
                        <div class="btn-group">
                            <button type="button" class="btn bg-primary-subtle text-primary dropdown-toggle"
                                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                Acciones
                            </button>
                            <div class="dropdown-menu dropdown-menu-end">
                                <a class="dropdown-item" href="{{ route('settings.cookie.export') }}">Exportar CSV</a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="{{ route('settings.cookie.index') }}">Volver a configuración</a>
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
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['total']) }}</h4>
                                <small class="text-muted">En el sistema</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Aceptar todo</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['accept_all']) }}</h4>
                                <small class="text-muted">Consentimiento total</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Rechazar todo</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['reject_all']) }}</h4>
                                <small class="text-muted">Sin consentimiento</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Personalizado</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['custom']) }}</h4>
                                <small class="text-muted">Selección parcial</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Filters --}}
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('settings.cookie.logs') }}">
                    <div class="row g-2 align-items-end">
                        <div class="col-12 col-md">
                            <input type="search" name="search" class="form-control"
                                   placeholder="Buscar por usuario o acción..."
                                   value="{{ request('search') }}">
                        </div>
                        <div class="col-6 col-md-auto">
                            <input type="date" name="from" class="form-control"
                                   title="Desde" value="{{ request('from') }}">
                        </div>
                        <div class="col-6 col-md-auto">
                            <input type="date" name="to" class="form-control"
                                   title="Hasta" value="{{ request('to') }}">
                        </div>
                        <div class="col-auto d-flex gap-2">
                            <button type="submit" class="btn btn-primary px-3">Buscar</button>
                            @if(request()->hasAny(['search', 'from', 'to']))
                                <a href="{{ route('settings.cookie.logs') }}" class="btn btn-outline-secondary">Limpiar</a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>

            {{-- Table --}}
            <div class="card-body">
                <div class="mb-3 d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1 fw-bold">Listado de registros</h6>
                        <p class="text-muted mb-0">{{ number_format($logs->total()) }} registros encontrados</p>
                    </div>
                </div>

                @if($logs->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Fecha</th>
                                    <th>Usuario</th>
                                    <th>Acción</th>
                                    <th>Categorías aceptadas</th>
                                    <th>Versión</th>
                                    <th>Sesión</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($logs as $log)
                                    <tr>
                                        <td>
                                            <div class="small">{{ $log->created_at->format('d/m/Y H:i') }}</div>
                                            <small class="text-muted">{{ $log->created_at->diffForHumans() }}</small>
                                        </td>
                                        <td>
                                            @if($log->user)
                                                <div class="small fw-semibold">{{ $log->user->name }}</div>
                                                <small class="text-muted">{{ $log->user->email }}</small>
                                            @else
                                                <span class="text-muted">Anónimo</span>
                                            @endif
                                        </td>
                                        <td>
                                            @php
                                                $badge = match($log->action) {
                                                    'accept_all' => ['success', 'fa-check-circle', 'Aceptar todo'],
                                                    'reject_all' => ['danger',  'fa-times-circle', 'Rechazar todo'],
                                                    default      => ['warning', 'fa-sliders',       'Personalizado'],
                                                };
                                            @endphp
                                            <span class="badge bg-{{ $badge[0] }}-subtle text-{{ $badge[0] }}">
                                                <i class="fas {{ $badge[1] }} me-1"></i>{{ $badge[2] }}
                                            </span>
                                        </td>
                                        <td>
                                            @if(!empty($log->accepted_categories))
                                                <div class="d-flex flex-wrap gap-1">
                                                    @foreach($log->accepted_categories as $cat)
                                                        <span class="badge bg-light text-dark border">{{ $cat }}</span>
                                                    @endforeach
                                                </div>
                                            @else
                                                <span class="text-muted">Ninguna</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border">v{{ $log->version }}</span>
                                        </td>
                                        <td>
                                            <code class="small text-muted">{{ substr($log->session_id, 0, 12) }}…</code>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-cookie-bite fa-3x text-muted opacity-50 mb-3 d-block"></i>
                        <h6 class="text-muted mb-1">
                            {{ request()->hasAny(['search', 'from', 'to']) ? 'No se encontraron resultados' : 'No hay registros de consentimiento todavía' }}
                        </h6>
                        @if(request()->hasAny(['search', 'from', 'to']))
                            <a href="{{ route('settings.cookie.logs') }}" class="btn btn-sm btn-outline-secondary mt-2">Limpiar filtros</a>
                        @endif
                    </div>
                @endif
            </div>

            @if($logs->hasPages())
                <div class="card-footer">{{ $logs->links() }}</div>
            @endif

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
});
</script>
@endpush
