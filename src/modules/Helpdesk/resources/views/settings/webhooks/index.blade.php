@extends('layouts.theme')

@section('title', 'Webhooks salientes')

@section('page_header')
    @include('core::components.card', ['title' => 'Webhooks salientes'])
@endsection

@section('content')

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">

            {{-- Header --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Webhooks configurados</h5>
                        <p class="small mb-0 text-muted">Notificaciones automaticas a URLs externas cuando ocurren eventos en Helpdesk</p>
                    </div>
                    <a href="{{ route('settings.helpdesk.webhooks.create') }}" class="btn btn-primary">
                        Nuevo webhook
                    </a>
                </div>
            </div>

            {{-- Stats --}}
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <h6 class="card-title text-primary mb-2">Total</h6>
                                <h4 class="mb-1 fw-bold">{{ $stats['total'] }}</h4>
                                <small class="text-muted">Webhooks configurados</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <h6 class="card-title text-success mb-2">Activos</h6>
                                <h4 class="mb-1 fw-bold">{{ $stats['active'] }}</h4>
                                <small class="text-muted">Webhooks habilitados</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <h6 class="card-title text-warning mb-2">Inactivos</h6>
                                <h4 class="mb-1 fw-bold">{{ $stats['inactive'] }}</h4>
                                <small class="text-muted">Webhooks deshabilitados</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <h6 class="card-title text-info mb-2">Envios hoy</h6>
                                <h4 class="mb-1 fw-bold">{{ $stats['deliveries_today'] }}</h4>
                                <small class="text-muted">Entregas realizadas</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Search --}}
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('settings.helpdesk.webhooks.index') }}">
                    <div class="row align-items-center">
                        <div class="col-md-9">
                            <div class="input-group">
                                <span class="input-group-text bg-white">
                                    <i class="fas fa-search"></i>
                                </span>
                                <input type="search" name="search" class="form-control"
                                    placeholder="Buscar por nombre o URL..."
                                    value="{{ request('search') }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary w-100">Buscar</button>
                        </div>
                    </div>
                </form>
            </div>

            {{-- Table --}}
            <div class="card-body">
                @if($webhooks->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Nombre</th>
                                    <th>URL</th>
                                    <th>Eventos</th>
                                    <th class="text-center">Exitos / Fallos</th>
                                    <th>Ultimo envio</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($webhooks as $webhook)
                                    <tr>
                                        <td>
                                            <strong>{{ $webhook->name }}</strong>
                                            @if($webhook->last_error)
                                                <div>
                                                    <small class="text-danger">
                                                        <i class="fas fa-circle-exclamation"></i>
                                                        {{ Str::limit($webhook->last_error, 50) }}
                                                    </small>
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            <code class="small">{{ Str::limit($webhook->url, 45) }}</code>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-wrap gap-1">
                                                @foreach($webhook->events ?? [] as $event)
                                                    <span class="badge bg-secondary-subtle text-secondary">{{ $event }}</span>
                                                @endforeach
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <span class="text-success fw-semibold">{{ number_format($webhook->success_count) }}</span>
                                            <span class="text-muted mx-1">/</span>
                                            <span class="text-danger fw-semibold">{{ number_format($webhook->failure_count) }}</span>
                                        </td>
                                        <td>
                                            @if($webhook->last_triggered_at)
                                                <small class="text-muted">{{ $webhook->last_triggered_at->diffForHumans() }}</small>
                                            @else
                                                <small class="text-muted">Nunca</small>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($webhook->is_active)
                                                <span class="badge bg-success-subtle text-success">Activo</span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary">Inactivo</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('settings.helpdesk.webhooks.edit', $webhook) }}">
                                                            Editar
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <button class="dropdown-item btn-delete"
                                                            data-id="{{ $webhook->id }}"
                                                            data-url="{{ route('settings.helpdesk.webhooks.destroy', $webhook) }}"
                                                            data-name="{{ $webhook->name }}">
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
                                <i class="fas fa-webhook fs-7"></i>
                            </div>
                            <h6 class="mb-1">No hay webhooks configurados</h6>
                            <p class="text-muted mb-3">
                                @if(request('search'))
                                    No se encontraron resultados para "{{ request('search') }}"
                                @else
                                    Crea tu primer webhook para recibir notificaciones en URLs externas
                                @endif
                            </p>
                            @unless(request('search'))
                                <a href="{{ route('settings.helpdesk.webhooks.create') }}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-plus"></i> Crear primer webhook
                                </a>
                            @endunless
                        </div>
                    </div>
                @endif
            </div>

            @if($webhooks->hasPages())
                <div class="card-footer bg-white border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted">
                            Mostrando <strong>{{ $webhooks->firstItem() }}</strong> a <strong>{{ $webhooks->lastItem() }}</strong>
                            de <strong>{{ $webhooks->total() }}</strong> webhooks
                        </div>
                        {{ $webhooks->appends(request()->input())->links() }}
                    </div>
                </div>
            @endif

        </div>
    </div>

    @include('core::components.delete')

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    $(document).on('click', '.btn-delete', function () {
        const url = $(this).data('url');
        const name = $(this).data('name');
        $('#deleteForm').attr('action', url);
        $('#deleteItemName').text(name);
        $('#deleteModal').modal('show');
    });

    @if(session('success'))
        toastr.success('{{ session('success') }}', 'Exito');
    @endif

    @if(session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif
});
</script>
@endpush
