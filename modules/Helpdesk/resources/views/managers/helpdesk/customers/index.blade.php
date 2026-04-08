@extends('layouts.theme')

@section('title', 'Clientes - Helpdesk')

@section('content')

    @include('core::components.card', ['title' => 'Clientes del Helpdesk'])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <!-- System Settings Card -->
        <div class="card">
            <!-- Header Section -->
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Clientes del Helpdesk</h5>
                        <p class="small mb-0 text-muted">Gestiona la base de clientes, conversaciones y perfiles</p>
                    </div>
                    <div class="d-flex gap-2">
                        @if(request('search'))
                            <a href="{{ route('manager.helpdesk.customers.index', ['tab' => request('tab', 'all')]) }}"
                               class="btn btn-light">
                                <i class="fa fa-times me-1"></i> Limpiar
                            </a>
                        @endif
                        <a href="{{ route('manager.helpdesk.customers.create') }}" class="btn btn-primary">
                            <i class="fa fa-plus me-1"></i> Nuevo Cliente
                        </a>
                        <button type="button" class="btn btn-secondary" onclick="location.reload()">
                            <i class="fa fa-sync me-1"></i> Actualizar
                        </button>
                    </div>
                </div>
            </div>

            <!-- Status Cards -->
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title text-primary mb-2">
                                            Total
                                        </h6>
                                        <h4 class="mb-1 fw-bold">{{ $stats['total'] }}</h4>
                                        <small class="text-muted">Clientes registrados</small>
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
                                        <h6 class="card-title text-success mb-2">
                                            Verificados
                                        </h6>
                                        <h4 class="mb-1 fw-bold">{{ $stats['verified'] }}</h4>
                                        <small class="text-muted">Cuentas confirmadas</small>
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
                                        <h6 class="card-title text-info mb-2">
                                            Nuevos
                                        </h6>
                                        <h4 class="mb-1 fw-bold">{{ $stats['new_this_month'] }}</h4>
                                        <small class="text-muted">Este mes</small>
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
                                        <h6 class="card-title text-warning mb-2">
                                            Conversaciones
                                        </h6>
                                        <h4 class="mb-1 fw-bold">{{ number_format($stats['total_conversations']) }}</h4>
                                        <small class="text-muted">Total de chats</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabs Filter -->
            <div class="card-body border-bottom">
                <div class="mb-3">
                    <h6 class="mb-1 fw-bold">Filtrar por estado</h6>
                    <p class="text-muted mb-0">Selecciona una categoría para ver clientes específicos</p>
                </div>
                <ul class="nav nav-pills gap-2 mb-0" role="tablist">
                    <li class="nav-item" role="presentation">
                        <a class="nav-link {{ !request('tab') || request('tab') === 'all' ? 'active' : '' }}"
                           href="{{ route('manager.helpdesk.customers.index', array_merge(request()->except('tab'), ['tab' => 'all'])) }}">
                            <i class="fa fa-users"></i>
                            <span class="ms-2">Todos</span>
                            <span class="badge bg-white text-dark ms-2">{{ $tabs['all'] }}</span>
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link {{ request('tab') === 'verified' ? 'active' : '' }}"
                           href="{{ route('manager.helpdesk.customers.index', array_merge(request()->except('tab'), ['tab' => 'verified'])) }}">
                            <i class="fa fa-check-circle"></i>
                            <span class="ms-2">Verificados</span>
                            <span class="badge bg-white text-dark ms-2">{{ $tabs['verified'] }}</span>
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link {{ request('tab') === 'active' ? 'active' : '' }}"
                           href="{{ route('manager.helpdesk.customers.index', array_merge(request()->except('tab'), ['tab' => 'active'])) }}">
                            <i class="fa fa-bolt"></i>
                            <span class="ms-2">Activos</span>
                            <span class="badge bg-white text-dark ms-2">{{ $tabs['active'] }}</span>
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link {{ request('tab') === 'banned' ? 'active' : '' }}"
                           href="{{ route('manager.helpdesk.customers.index', array_merge(request()->except('tab'), ['tab' => 'banned'])) }}">
                            <i class="fa fa-ban"></i>
                            <span class="ms-2">Suspendidos</span>
                            <span class="badge bg-white text-dark ms-2">{{ $tabs['banned'] }}</span>
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Search and Table -->
            <div class="card-body">
                <div class="mb-3 d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1 fw-bold">Lista de clientes</h6>
                        <p class="text-muted mb-0">Total: {{ $customers->total() }} clientes</p>
                    </div>
                    <div class="col-md-4">
                        <form method="GET" action="{{ route('manager.helpdesk.customers.index') }}">
                            <input type="hidden" name="tab" value="{{ request('tab', 'all') }}">
                            <div class="input-group">
                                <span class="input-group-text bg-white">
                                    <i class="fa fa-search"></i>
                                </span>
                                <input type="search"
                                       name="search"
                                       class="form-control"
                                       placeholder="Buscar cliente..."
                                       value="{{ request('search') }}">
                                <button type="submit" class="btn btn-primary">
                                    Buscar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="alert alert-info border-0 bg-info-subtle mb-3">
                    <div class="d-flex align-items-start gap-2">
                        <div>
                            <small class="fw-semibold">Información:</small>
                            <small class="d-block">Haz clic en el nombre del cliente para ver sus detalles completos y conversaciones.</small>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Cliente</th>
                                <th>País / Idioma</th>
                                <th>Última actividad</th>
                                <th class="text-center">Conversaciones</th>
                                <th>Estado</th>
                                <th class="text-end pe-4">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($customers as $customer)
                            <tr>
                                <!-- Cliente -->
                                <td class="ps-4">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="flex-shrink-0">
                                            <img src="{{ $customer->getAvatarUrl() }}"
                                                 alt="{{ $customer->name }}"
                                                 class="rounded-circle"
                                                 width="44"
                                                 height="44"
                                                 style="object-fit: cover; border: 2px solid #f0f0f0;">
                                        </div>
                                        <div>
                                            <h6 class="mb-0 fw-semibold">
                                                <a href="{{ route('manager.helpdesk.customers.show', $customer->id) }}" class="text-dark text-decoration-none">
                                                    {{ $customer->name }}
                                                </a>
                                                @if($customer->email_verified_at)
                                                    <i class="fa fa-check-circle text-success ms-1" title="Verificado"></i>
                                                @endif
                                            </h6>
                                            <small class="text-muted d-flex align-items-center gap-1">
                                                <i class="fa fa-envelope fs-5"></i>
                                                {{ $customer->email }}
                                            </small>
                                            @if($customer->phone)
                                                <small class="text-muted d-flex align-items-center gap-1">
                                                    <i class="fa fa-phone fs-5"></i>
                                                    {{ $customer->phone }}
                                                </small>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                <!-- País / Idioma -->
                                <td>
                                    @if($customer->country)
                                        <span class="badge bg-light text-dark">
                                            <i class="fa fa-globe"></i> {{ strtoupper($customer->country) }}
                                        </span>
                                    @endif
                                    @if($customer->language)
                                        <span class="badge bg-light text-dark">
                                            <i class="fa fa-language"></i> {{ strtoupper($customer->language) }}
                                        </span>
                                    @endif
                                    @if(!$customer->country && !$customer->language)
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>

                                <!-- Última Actividad -->
                                <td>
                                    @if($customer->last_seen_at)
                                        <small class="text-muted">
                                            {{ $customer->last_seen_at->diffForHumans() }}
                                        </small>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>

                                <!-- Conversaciones -->
                                <td class="text-center">
                                    <span class="badge bg-primary-subtle text-primary">
                                        {{ $customer->conversations_count ?? 0 }}
                                    </span>
                                </td>

                                <!-- Estado -->
                                <td>
                                    @if($customer->is_banned)
                                        <span class="badge bg-danger-subtle text-danger">
                                            <i class="fa fa-ban"></i> Suspendido
                                        </span>
                                    @else
                                        <span class="badge bg-success-subtle text-success">
                                            <i class="fa fa-check"></i> Activo
                                        </span>
                                    @endif
                                </td>

                                <!-- Acciones -->
                                <td class="text-end pe-4">
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-light" data-bs-toggle="dropdown">
                                            <i class="fa fa-ellipsis-v"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <a class="dropdown-item" href="{{ route('manager.helpdesk.customers.show', $customer->id) }}">
                                                    <i class="fa fa-eye"></i> Ver Detalles
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="{{ route('manager.helpdesk.customers.edit', $customer->id) }}">
                                                    <i class="fa fa-edit"></i> Editar
                                                </a>
                                            </li>
                                            @if(!$customer->is_banned)
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <form method="POST" action="{{ route('manager.helpdesk.customers.ban', $customer->id) }}">
                                                        @csrf
                                                        <button type="submit" class="dropdown-item text-warning"
                                                                onclick="return confirm('¿Suspender este cliente?')">
                                                            <i class="fa fa-ban"></i> Suspender
                                                        </button>
                                                    </form>
                                                </li>
                                            @else
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <form method="POST" action="{{ route('manager.helpdesk.customers.unban', $customer->id) }}">
                                                        @csrf
                                                        <button type="submit" class="dropdown-item text-success">
                                                            <i class="fa fa-check"></i> Reactivar
                                                        </button>
                                                    </form>
                                                </li>
                                            @endif
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <div class="d-flex flex-column align-items-center">
                                        <div class="round-48 rounded-circle bg-light-subtle text-muted mb-3 d-flex align-items-center justify-content-center">
                                            <i class="fa fa-users fs-7"></i>
                                        </div>
                                        <h6 class="mb-1">No se encontraron clientes</h6>
                                        <p class="text-muted mb-0">Intenta ajustar los filtros de búsqueda</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if($customers->hasPages())
                    <div class="mt-3 d-flex justify-content-center">
                        {{ $customers->links() }}
                    </div>
                @endif
            </div>
        </div>

    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    @if (session('success'))
        toastr.success('{{ session('success') }}', 'Éxito');
    @endif

    @if (session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif
});
</script>
@endpush
