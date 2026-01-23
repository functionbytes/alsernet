@extends('layouts.theme')

@section('content')

    @include('core::components.card', ['title' => 'Registro de cambios'])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <!-- Filters Card -->
        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-6">
                        <label for="search" class="form-label">Buscar</label>
                        <input type="text" class="form-control" id="search" name="search"
                               placeholder="Buscar en registros de cambios"
                               value="{{ request('search') }}">
                    </div>
                    <div class="col-md-6">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-1"></i>Buscar
                        </button>
                        <a href="{{ route('activity.logs') }}" class="btn btn-secondary">
                            <i class="fas fa-redo me-1"></i>Limpiar
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Activity Logs Table -->
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="fw-bold">Fecha</th>
                            <th class="fw-bold">Usuario</th>
                            <th class="fw-bold">Acción</th>
                            <th class="fw-bold">Modelo</th>
                            <th class="fw-bold">Descripción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($activities as $activity)
                            <tr>
                                <td>
                                    <small class="text-muted">
                                        {{ $activity->created_at->format('d/m/Y H:i:s') }}
                                    </small>
                                </td>
                                <td>
                                    @if($activity->causer)
                                        <small>{{ $activity->causer->name ?? 'Sistema' }}</small>
                                    @else
                                        <small class="text-muted">Sistema</small>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-{{ $activity->event === 'created' ? 'success' : ($activity->event === 'updated' ? 'info' : 'danger') }}">
                                        {{ ucfirst($activity->event) }}
                                    </span>
                                </td>
                                <td>
                                    <small class="text-muted">{{ class_basename($activity->subject_type) ?? 'N/A' }}</small>
                                </td>
                                <td>
                                    <small>{{ $activity->description }}</small>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    No hay registros de cambios
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($activities->hasPages())
                <div class="card-footer bg-light">
                    {{ $activities->links() }}
                </div>
            @endif
        </div>

    </div>

@endsection
