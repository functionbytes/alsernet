@extends('layouts.theme')

@section('title', 'Respuestas Predefinidas')

@section('page_header')
    @include('core::components.card', ['title' => 'Respuestas Predefinidas'])
@endsection

@section('content')

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Respuestas predefinidas disponibles</h5>
                        <p class="small mb-0 text-muted">Gestiona respuestas rápidas para mejorar la eficiencia del equipo</p>
                    </div>
                    <div class="d-flex gap-2">
                        @if(request('search'))
                            <a href="{{ route('manager.helpdesk.settings.ticket-canned-replies.index') }}" class="btn btn-secondary">
                                Limpiar búsqueda
                            </a>
                        @endif
                        <a href="{{ route('manager.helpdesk.settings.ticket-canned-replies.create') }}" class="btn btn-primary">
                            Nueva respuesta
                        </a>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title text-primary mb-2">Total</h6>
                                        <h4 class="mb-1 fw-bold">{{ $stats['total'] }}</h4>
                                        <small class="text-muted">Respuestas configuradas</small>
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
                                        <h6 class="card-title text-success mb-2">Globales</h6>
                                        <h4 class="mb-1 fw-bold">{{ $stats['global'] }}</h4>
                                        <small class="text-muted">Disponibles para todos</small>
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
                                        <h6 class="card-title text-warning mb-2">Personales</h6>
                                        <h4 class="mb-1 fw-bold">{{ $stats['personal'] }}</h4>
                                        <small class="text-muted">Respuestas privadas</small>
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
                                        <h6 class="card-title text-info mb-2">Activas</h6>
                                        <h4 class="mb-1 fw-bold">{{ $stats['active'] }}</h4>
                                        <small class="text-muted">Respuestas habilitadas</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search and Filter Section -->
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('manager.helpdesk.settings.ticket-canned-replies.index') }}">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-text bg-white">
                                    <i class="fas fa-search"></i>
                                </span>
                                <input type="search" name="search" class="form-control" placeholder="Buscar respuestas..." value="{{ request('search') }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select name="type" class="form-select">
                                <option value="">Todos los tipos</option>
                                <option value="global" {{ request('type') == 'global' ? 'selected' : '' }}>Globales</option>
                                <option value="personal" {{ request('type') == 'personal' ? 'selected' : '' }}>Personales</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary w-100">Buscar</button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Replies List -->
            <div class="card-body">
                @if($replies->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                            <tr>
                                <th width="25%">Título</th>
                                <th width="10%">Atajo</th>
                                <th width="20%">Categorías</th>
                                <th width="25%">Contenido</th>
                                <th width="10%" class="text-center">Tipo</th>
                                <th width="5%" class="text-center">Estado</th>
                                <th width="5%" class="text-center">Acciones</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($replies as $reply)
                                <tr>
                                    <td><strong>{{ $reply->title }}</strong></td>
                                    <td>
                                        @if($reply->shortcut)
                                            <code class="bg-light px-2 py-1 rounded">/{{ $reply->shortcut }}</code>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($reply->ticket_categories && count($reply->ticket_categories) > 0)
                                            <span class="badge bg-secondary">{{ count($reply->ticket_categories) }} categoría(s)</span>
                                        @else
                                            <span class="text-muted">Todas</span>
                                        @endif
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ Str::limit(strip_tags($reply->body), 60) }}</small>
                                    </td>
                                    <td class="text-center">
                                        @if($reply->is_global)
                                            <span class="badge bg-success-subtle text-success">Global</span>
                                        @else
                                            <span class="badge bg-info-subtle text-info">Personal</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <form method="POST" action="{{ route('manager.helpdesk.settings.ticket-canned-replies.toggle', $reply->id) }}" class="toggle-form">
                                            @csrf
                                            @method('PATCH')
                                            <div class="form-check form-switch d-inline-block">
                                                <input type="checkbox" class="form-check-input toggle-checkbox" role="switch"
                                                       {{ $reply->is_active ? 'checked' : '' }}
                                                      >
                                            </div>
                                        </form>
                                    </td>
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="fas fa-ellipsis-vertical"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('manager.helpdesk.settings.ticket-canned-replies.edit', $reply->id) }}">
                                                        Editar
                                                    </a>
                                                </li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <form method="POST" action="{{ route('manager.helpdesk.settings.ticket-canned-replies.destroy', $reply->id) }}"
                                                          class="needs-confirm"
                                                          data-confirm-msg="¿Estás seguro de eliminar esta respuesta?">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="dropdown-item">Eliminar</button>
                                                    </form>
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
                                <i class="fas fa-message fs-7"></i>
                            </div>
                            <h6 class="mb-1">No hay respuestas para mostrar</h6>
                            <p class="text-muted mb-3">
                                @if(request('search'))
                                    No se encontraron resultados
                                @else
                                    Crea tu primera respuesta predefinida
                                @endif
                            </p>
                            @if(!request('search'))
                                <a href="{{ route('manager.helpdesk.settings.ticket-canned-replies.create') }}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-plus"></i> Crear Primera Respuesta
                                </a>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            @if($replies->hasPages())
                <div class="card-footer bg-white border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted">
                            Mostrando <strong>{{ $replies->firstItem() }}</strong> a <strong>{{ $replies->lastItem() }}</strong>
                            de <strong>{{ $replies->total() }}</strong> respuestas
                        </div>
                        <nav aria-label="Page navigation">
                            {{ $replies->links() }}
                        </nav>
                    </div>
                </div>
            @endif
        </div>
    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('.toggle-form').on('submit', function() {
        $(this).find('.toggle-checkbox').prop('disabled', true);
    });

    @if (session('success'))
        toastr.success('{{ session('success') }}', 'Éxito');
    @endif

    @if (session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif
});
</script>
@endpush
