@extends('layouts.theme')

@section('title', 'Listas de correo')

@section('content')

    <div class="card">

        {{-- Header --}}
        <div class="card-header p-4 border-bottom">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1 fw-bold">Listas de correo</h5>
                    <p class="small mb-0 text-muted">Gestiona tus listas de suscriptores para envíos de email marketing</p>
                </div>
                <a href="{{ route('manager.maillists.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i> Nueva lista
                </a>
            </div>
        </div>

        {{-- Stats --}}
        <div class="card-body border-bottom">
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="card bg-light-secondary h-100">
                        <div class="card-body">
                            <h6 class="card-title mb-2">Total listas</h6>
                            <h4 class="mb-1 fw-bold">{{ number_format($lists->total()) }}</h4>
                            <small class="text-muted">Listas activas</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-light-secondary h-100">
                        <div class="card-body">
                            <h6 class="card-title mb-2">Suscriptores</h6>
                            <h4 class="mb-1 fw-bold">{{ number_format($lists->sum('subscribers_count')) }}</h4>
                            <small class="text-muted">En esta página</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filters --}}
        <div class="card-body border-bottom">
            <form method="GET" action="{{ route('manager.maillists.index') }}">
                <div class="d-flex gap-3 align-items-stretch">
                    <div class="flex-fill">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-1">
                                <i class="fas fa-search text-muted"></i>
                            </span>
                            <input type="search" name="q" class="form-control -0 ps-0"
                                   placeholder="Buscar lista por nombre…"
                                   value="{{ request('q') }}">
                        </div>
                    </div>
                    <div class="d-flex gap-2 flex-shrink-0">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i>
                        </button>
                        @if(request('q'))
                            <a href="{{ route('manager.maillists.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i>
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>

        {{-- Table --}}
        <div class="card-body">
            @if ($lists->isEmpty())
                <div class="text-center py-5">
                    <div class="d-flex flex-column align-items-center">
                        <div class="round-48 rounded-circle bg-light-subtle text-muted mb-3 d-flex align-items-center justify-content-center">
                            <i class="fas fa-list fs-7"></i>
                        </div>
                        <h6 class="mb-1">
                            @if(request('q'))
                                No se encontraron listas
                            @else
                                Aún no hay listas de correo
                            @endif
                        </h6>
                        <p class="text-muted mb-0">
                            @if(request('q'))
                                No hay resultados para "{{ request('q') }}"
                            @else
                                Crea tu primera lista para empezar a gestionar suscriptores
                            @endif
                        </p>
                        @if(!request('q'))
                            <a href="{{ route('manager.maillists.create') }}" class="btn btn-primary mt-3">
                                <i class="fas fa-plus me-1"></i> Crear primera lista
                            </a>
                        @endif
                    </div>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Nombre</th>
                                <th>From email</th>
                                <th class="text-center">Suscriptores</th>
                                <th>Creada</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($lists as $l)
                                <tr>
                                    <td>
                                        <a href="{{ route('manager.maillists.show', $l->uid) }}" class="fw-semibold text-decoration-none">
                                            {{ $l->name }}
                                        </a>
                                        @if($l->description)
                                            <div class="small text-muted text-truncate" style="max-width:260px">{{ $l->description }}</div>
                                        @endif
                                    </td>
                                    <td><code class="small">{{ $l->from_email }}</code></td>
                                    <td class="text-center">
                                        <span class="badge bg-primary-subtle text-primary">
                                            {{ number_format($l->subscribers_count) }}
                                        </span>
                                    </td>
                                    <td class="text-muted small">{{ $l->created_at?->format('d/m/Y') }}</td>
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <a href="javascript:void(0)" class="text-muted" data-bs-toggle="dropdown">
                                                <i class="fas fa-ellipsis-vertical"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('manager.maillists.show', $l->uid) }}">
                                                        Ver detalle
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('manager.maillists.subscribers.index', $l->uid) }}">
                                                        Suscriptores
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('manager.maillists.edit', $l->uid) }}">
                                                        Editar
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('manager.maillists.fields', $l->uid) }}">
                                                        Campos personalizados
                                                    </a>
                                                </li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('manager.maillists.destroy', $l->uid) }}"
                                                       onclick="return confirm('¿Eliminar lista {{ addslashes($l->name) }}? Esta acción es irreversible.')">
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
            @endif
        </div>

        @if ($lists->hasPages())
            <div class="card-footer">
                {{ $lists->links() }}
            </div>
        @endif

    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    @if(session('success'))
        toastr.success(@json(session('success')));
    @endif
    @if(session('error'))
        toastr.error(@json(session('error')));
    @endif
});
</script>
@endpush
