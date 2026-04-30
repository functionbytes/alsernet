@extends('layouts.theme')

@section('title', 'Webhooks')

@section('content')

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Webhooks</h5>
                        <p class="small mb-0 text-muted">Envía notificaciones de eventos a URLs externas en tiempo real</p>
                    </div>
                    <div class="d-flex gap-2">
                        @if(request('search'))
                            <a href="{{ route('settings.chat.webhooks.index') }}" class="btn btn-secondary">
                                Limpiar búsqueda
                            </a>
                        @endif
                        <a href="{{ route('settings.chat.webhooks.create') }}" class="btn btn-primary">
                            Nuevo webhook
                        </a>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title text-primary mb-2">Total</h6>
                                        <h4 class="mb-1 fw-bold">{{ $webhooks->total() }}</h4>
                                        <small class="text-muted">Webhooks configurados</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title text-info mb-2">Cuenta</h6>
                                        <h4 class="mb-1 fw-bold">{{ $accountCount }}</h4>
                                        <small class="text-muted">Nivel de cuenta</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title text-warning mb-2">Buzón</h6>
                                        <h4 class="mb-1 fw-bold">{{ $inboxCount }}</h4>
                                        <small class="text-muted">Nivel de buzón</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search Section -->
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('settings.chat.webhooks.index') }}">
                    <div class="row align-items-center g-2">
                        <div class="col-md-9">
                            <div class="input-group">
                                <span class="input-group-text bg-white">
                                    <i class="fa fa-magnifying-glass"></i>
                                </span>
                                <input type="search" name="search" class="form-control"
                                       placeholder="Buscar por nombre o URL..."
                                       value="{{ request('search') }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary w-100">
                                Buscar
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Webhooks List -->
            <div class="card-body">
                @if($webhooks->count() > 0)
                    <div class="alert alert-info mb-3">
                        <i class="fa fa-circle-info me-2"></i>
                        Los webhooks reciben notificaciones POST cuando ocurren eventos seleccionados en el sistema
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Nombre</th>
                                    <th>URL</th>
                                    <th class="text-center">Tipo</th>
                                    <th class="text-center">Eventos</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($webhooks as $webhook)
                                    <tr>
                                        <td>
                                            <div>
                                                <strong>{{ $webhook->name }}</strong>
                                                @if($webhook->inbox)
                                                    <br>
                                                    <small class="text-muted">
                                                        <i class="fa fa-inbox me-1"></i>{{ $webhook->inbox->name }}
                                                    </small>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <code class="bg-light px-2 py-1 rounded small">{{ Str::limit($webhook->url, 50) }}</code>
                                        </td>
                                        <td class="text-center">
                                            @if($webhook->webhook_type === 0)
                                                <span class="badge bg-primary-subtle text-primary">
                                                    Cuenta
                                                </span>
                                            @else
                                                <span class="badge bg-info-subtle text-info">
                                                    Buzón
                                                </span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-info-subtle text-info">
                                                {{ count($webhook->subscriptions ?? []) }} eventos
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fa fa-ellipsis-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item"
                                                           href="{{ route('settings.chat.webhooks.show', $webhook) }}">
                                                            Ver detalles
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item"
                                                           href="{{ route('settings.chat.webhooks.edit', $webhook) }}">
                                                            Editar
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <button type="button"
                                                                class="dropdown-item text-danger delete-btn"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#delete-modal"
                                                                data-url="{{ route('settings.chat.webhooks.destroy', $webhook) }}"
                                                                data-title="Eliminar webhook: {{ $webhook->name }}">
                                                            Eliminar
                                                        </button>
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
                                <i class="fa fa-webhook fs-7"></i>
                            </div>
                            <h6 class="mb-1">No hay webhooks para mostrar</h6>
                            <p class="text-muted mb-3">
                                @if(request('search'))
                                    No se encontraron resultados para "{{ request('search') }}"
                                @else
                                    Crea tu primer webhook para recibir notificaciones de eventos
                                @endif
                            </p>
                            @if(!request('search'))
                                <a href="{{ route('settings.chat.webhooks.create') }}" class="btn btn-sm btn-primary">
                                    <i class="fa fa-plus"></i> Crear primer webhook
                                </a>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            <!-- Pagination -->
            @if($webhooks->hasPages())
                <div class="card-footer bg-white border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted small">
                            Mostrando <strong>{{ $webhooks->firstItem() }}</strong> a <strong>{{ $webhooks->lastItem() }}</strong>
                            de <strong>{{ $webhooks->total() }}</strong> webhooks
                        </div>
                        <nav aria-label="Page navigation">
                            {{ $webhooks->links() }}
                        </nav>
                    </div>
                </div>
            @endif
        </div>
    </div>

    @include('core::components.delete')

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Delete modal functionality
    $('.delete-btn').on('click', function() {
        const deleteUrl = $(this).data('url');
        const deleteTitle = $(this).data('title');

        $('#delete-modal .modal-title').text(deleteTitle);
        $('#delete-form').attr('action', deleteUrl);
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
