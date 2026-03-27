@extends('layouts.theme')

@section('title', 'Registro de cambios')

@section('content')

    @include('core::components.card', ['title' => 'Registro de cambios'])

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
                                <h6 class="card-title mb-2">Creaciones</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['created']) }}</h4>
                                <small class="text-muted">Registros creados</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Actualizaciones</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['updated']) }}</h4>
                                <small class="text-muted">Registros modificados</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Eliminaciones</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['deleted']) }}</h4>
                                <small class="text-muted">Registros eliminados</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Search --}}
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('activity.logs') }}">
                    <div class="d-flex gap-2 align-items-center">
                        <div class="input-group flex-grow-1">
                            <span class="input-group-text bg-white">
                                <i class="fas fa-search"></i>
                            </span>
                            <input type="search" name="search" class="form-control"
                                   placeholder="Buscar en descripción..."
                                   value="{{ request('search') }}">
                        </div>
                        @if(request('search'))
                            <a href="{{ route('activity.logs') }}" class="btn btn-outline-secondary" title="Limpiar">
                                <i class="fas fa-times"></i>
                            </a>
                        @endif
                        <button type="submit" class="btn btn-primary" style="min-width:45px;">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
            </div>

            {{-- Table --}}
            <div class="card-body">
                <div class="mb-3 d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1 fw-bold">Listado de registros</h6>
                        <p class="text-muted small mb-0">{{ number_format($activities->total()) }} registros encontrados</p>
                    </div>
                </div>

                @if($activities->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Fecha</th>
                                    <th>Usuario</th>
                                    <th>Acción</th>
                                    <th>Modelo</th>
                                    <th>Descripción</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($activities as $activity)
                                    <tr>
                                        <td>
                                            <div class="small">{{ $activity->created_at->format('d/m/Y H:i') }}</div>
                                            <small class="text-muted">{{ $activity->created_at->diffForHumans() }}</small>
                                        </td>
                                        <td>
                                            <div class="small fw-semibold">{{ $activity->causer?->name ?? 'Sistema' }}</div>
                                            @if($activity->causer?->email)
                                                <small class="text-muted">{{ $activity->causer->email }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            @php
                                                $eventMap = ['created' => 'success', 'updated' => 'primary', 'deleted' => 'primary'];
                                                $color = $eventMap[$activity->event] ?? 'secondary';
                                            @endphp
                                            <span class="badge bg-{{ $color }}-subtle text-{{ $color }}">
                                                {{ $activity->event ?? 'n/a' }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark">{{ class_basename($activity->subject_type ?? '') ?: '-' }}</span>
                                        </td>
                                        <td>
                                            <small class="text-truncate d-block" style="max-width:300px;">{{ $activity->description }}</small>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-inbox fa-3x text-muted opacity-50 mb-3 d-block"></i>
                        <h6 class="text-muted mb-1">
                            {{ request('search') ? 'No se encontraron resultados' : 'No hay registros de cambios' }}
                        </h6>
                        @if(request('search'))
                            <p class="text-muted small mb-3">No hay resultados para "{{ request('search') }}"</p>
                            <a href="{{ route('activity.logs') }}" class="btn btn-sm btn-outline-secondary">Limpiar búsqueda</a>
                        @endif
                    </div>
                @endif
            </div>

            @if($activities->hasPages())
                <div class="card-footer">{{ $activities->links() }}</div>
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
