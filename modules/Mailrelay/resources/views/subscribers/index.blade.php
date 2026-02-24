@extends('layouts.theme')

@section('title', 'Suscriptores')

@section('content')
<div class="widget-content searchable-container list">

    @include('core::components.alerts')

    {{-- Main Card --}}
    <div class="card">
        {{-- Header Section --}}
        <div class="card-header p-4 border-bottom border-light">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1 fw-bold">Gestión de suscriptores</h5>
                    <p class="small mb-0 text-muted">Administra y monitorea tu lista de suscriptores</p>
                </div>
                <a href="{{ route('mailrelay.subscribers.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus-circle me-2"></i>Agregar suscriptor
                </a>
            </div>
        </div>

        {{-- Statistics Cards --}}
        <div class="card-body border-bottom">
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="card bg-light-secondary stat-card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-start justify-content-between">
                                <div>
                                    <h6 class="card-title text-primary mb-2">Total suscriptores</h6>
                                    <h4 class="mb-1 fw-bold">{{ $stats['total'] ?? 0 }}</h4>
                                    <small class="text-muted">Registrados</small>
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
                                    <h6 class="card-title mb-2">Activos</h6>
                                    <h4 class="mb-1 fw-bold">{{ $stats['active'] ?? 0 }}</h4>
                                    <small class="text-muted">Verificados</small>
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
                                    <h6 class="card-title text-warning mb-2">Pendientes</h6>
                                    <h4 class="mb-1 fw-bold">{{ $stats['pending'] ?? 0 }}</h4>
                                    <small class="text-muted">Por verificar</small>
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
                                    <h6 class="card-title text-info mb-2">Sincronizados</h6>
                                    <h4 class="mb-1 fw-bold">{{ $stats['synced'] ?? 0 }}</h4>
                                    <small class="text-muted">Con Mailrelay</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Search & Filter --}}
        <div class="card-body border-bottom">
            <form method="GET" action="{{ route('mailrelay.subscribers.index') }}">
                <div class="row g-2">
                    <div class="col-md-3">
                        <div class="input-group">
                            <span class="input-group-text bg-white">
                                <i class="fas fa-search"></i>
                            </span>
                            <input type="search" name="search" class="form-control" placeholder="Email o nombre..." value="{{ request('search') }}">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <select name="status" class="form-select">
                            <option value="">Todos los estados</option>
                            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Activos</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pendientes</option>
                            <option value="unsubscribed" {{ request('status') == 'unsubscribed' ? 'selected' : '' }}>Desuscritos</option>
                            <option value="bounced" {{ request('status') == 'bounced' ? 'selected' : '' }}>Rebotados</option>
                            <option value="banned" {{ request('status') == 'banned' ? 'selected' : '' }}>Bloqueados</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="sync_status" class="form-select">
                            <option value="">Estado de sincronización</option>
                            <option value="synced" {{ request('sync_status') == 'synced' ? 'selected' : '' }}>Sincronizados</option>
                            <option value="not_synced" {{ request('sync_status') == 'not_synced' ? 'selected' : '' }}>No sincronizados</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="validation" class="form-select">
                            <option value="">Validación</option>
                            <option value="validated" {{ request('validation') == 'validated' ? 'selected' : '' }}>Validados</option>
                            <option value="not_validated" {{ request('validation') == 'not_validated' ? 'selected' : '' }}>No validados</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1">
                            <i class="fas fa-search me-2"></i>Buscar
                        </button>
                        @if(request()->hasAny(['search', 'status', 'sync_status', 'validation']))
                        <a href="{{ route('mailrelay.subscribers.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-times"></i>
                        </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>

        {{-- Subscribers Table --}}
        <div class="card-body">
            @if($subscribers->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover search-table align-middle text-nowrap mb-0">
                        <thead class="header-item">
                            <tr>
                                <th>Email</th>
                                <th>Nombre</th>
                                <th>Estado</th>
                                <th>Validación</th>
                                <th>Sincronización</th>
                                <th>Fecha de suscripción</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($subscribers as $subscriber)
                            <tr class="search-items">
                                <td>
                                    <span class="fw-bold">{{ $subscriber->email }}</span>
                                </td>
                                <td>{{ $subscriber->name ?? '-' }}</td>
                                <td>
                                    @switch($subscriber->status->value ?? $subscriber->status)
                                        @case('active')
                                            <span class="badge bg-success-subtle text-success rounded-3 py-2 fw-semibold fs-2">
                                                <i class="fas fa-check-circle"></i> Activo
                                            </span>
                                            @break
                                        @case('pending')
                                            <span class="badge bg-warning-subtle text-warning rounded-3 py-2 fw-semibold fs-2">
                                                <i class="fas fa-clock"></i> Pendiente
                                            </span>
                                            @break
                                        @case('unsubscribed')
                                            <span class="badge bg-secondary-subtle text-secondary rounded-3 py-2 fw-semibold fs-2">
                                                <i class="fas fa-times-circle"></i> Desuscrito
                                            </span>
                                            @break
                                        @case('bounced')
                                            <span class="badge bg-danger-subtle text-danger rounded-3 py-2 fw-semibold fs-2">
                                                <i class="fas fa-exclamation-triangle"></i> Rebotado
                                            </span>
                                            @break
                                        @case('banned')
                                            <span class="badge bg-dark rounded-3 py-2 fw-semibold fs-2">
                                                <i class="fas fa-ban"></i> Bloqueado
                                            </span>
                                            @break
                                    @endswitch
                                </td>
                                <td>
                                    @if($subscriber->validated_at)
                                        <span class="badge bg-info-subtle text-info rounded-3 py-2 fw-semibold fs-2">
                                            <i class="fas fa-shield-alt"></i> Validado
                                        </span>
                                    @else
                                        <span class="badge bg-light-secondary text-muted rounded-3 py-2 fw-semibold fs-2">
                                            <i class="fas fa-shield-alt"></i> No validado
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @if($subscriber->mailrelay_id)
                                        <span class="badge bg-primary-subtle text-primary rounded-3 py-2 fw-semibold fs-2">
                                            <i class="fas fa-cloud-check"></i> Sincronizado
                                        </span>
                                        <small class="d-block text-muted">ID: {{ $subscriber->mailrelay_id }}</small>
                                    @else
                                        <span class="badge bg-light-secondary text-muted rounded-3 py-2 fw-semibold fs-2">
                                            <i class="fas fa-cloud-slash"></i> No sincronizado
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @if($subscriber->subscribed_at)
                                        <small>{{ $subscriber->subscribed_at->format('M d, Y') }}</small>
                                        <small class="d-block text-muted">{{ $subscriber->subscribed_at->diffForHumans() }}</small>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="dropdown dropstart">
                                        <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fa-duotone fa-solid fa-ellipsis"></i>
                                        </a>
                                        <ul class="dropdown-menu">
                                            <li>
                                                <a class="dropdown-item" href="{{ route('mailrelay.subscribers.show', $subscriber) }}">
                                                    Ver detalles
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="{{ route('mailrelay.subscribers.edit', $subscriber) }}">
                                                    Editar
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <a class="dropdown-item confirm-delete"
                                                   data-href="{{ route('mailrelay.subscribers.destroy', $subscriber) }}">
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
                    <div class="d-flex flex-column align-items-center">
                        <div class="round-48 rounded-circle bg-light-subtle text-muted mb-3 d-flex align-items-center justify-content-center">
                            <i class="fas fa-inbox fs-7"></i>
                        </div>
                        <h6 class="mb-1">No se encontraron suscriptores</h6>
                        <p class="text-muted mb-3">
                            @if(request()->hasAny(['search', 'status', 'sync_status', 'validation']))
                                No se encontraron resultados con los filtros aplicados
                            @else
                                Agrega tu primer suscriptor para comenzar
                            @endif
                        </p>
                        <a href="{{ route('mailrelay.subscribers.create') }}" class="btn btn-sm btn-primary">
                            <i class="fas fa-plus"></i> Agregar primer suscriptor
                        </a>
                    </div>
                </div>
            @endif
        </div>

        {{-- Pagination --}}
        @if($subscribers->hasPages())
            <div class="card-footer bg-white border-top">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="result-body">
                        <span>Mostrando {{ $subscribers->firstItem() }}-{{ $subscribers->lastItem() }} de {{ $subscribers->total() }} resultados</span>
                    </div>
                    <nav>
                        {{ $subscribers->links() }}
                    </nav>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
