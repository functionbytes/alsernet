@extends('layouts.theme')

@section('title', 'Clientes')

@section('page_header')
    @include('core::components.card', ['title' => 'Clientes'])
@endsection

@section('content')

    @include('core::components.alerts')

    <div class="widget-content searchable-container list">

        <div class="card">

            <div class="card-header p-4 border-bottom">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Clientes</h5>
                        <p class="small mb-0 text-muted">Perfiles unificados sincronizados desde tus tiendas</p>
                    </div>
                    <button type="button" class="btn btn-outline-secondary btn-filter" data-bs-toggle="modal" data-bs-target="#filterModal">
                        <i class="fas fa-filter me-1"></i> Filtros
                        @php
                            $activeFilters = collect([request('store_id'), request('status'), request('country'), request('search')])->filter()->count();
                        @endphp
                        @if($activeFilters > 0)
                            <span class="badge bg-primary ms-1">{{ $activeFilters }}</span>
                        @endif
                    </button>
                </div>
            </div>

            {{-- Search bar --}}
            <div class="card-body border-bottom py-3">
                <form method="GET" action="{{ route('remarketing.customers.index') }}" id="searchForm">
                    <div class="d-flex gap-2">
                        <input type="text" name="search" class="form-control" placeholder="Buscar por email o nombre..."
                               value="{{ request('search') }}">
                        @foreach(request()->except(['search', 'page']) as $key => $val)
                            <input type="hidden" name="{{ $key }}" value="{{ $val }}">
                        @endforeach
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i>
                        </button>
                        @if(request()->hasAny(['search', 'store_id', 'status', 'country']))
                            <a href="{{ route('remarketing.customers.index') }}" class="btn btn-light">
                                <i class="fas fa-times"></i>
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Email</th>
                                <th>Nombre</th>
                                <th>País</th>
                                <th>Estado</th>
                                <th>RFM</th>
                                <th>Última compra</th>
                                <th>Total gastado</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($customers as $customer)
                                <tr>
                                    <td>
                                        <a href="{{ route('remarketing.customers.show', $customer) }}" class="text-decoration-none fw-semibold">
                                            {{ $customer->email }}
                                        </a>
                                    </td>
                                    <td class="small">{{ trim($customer->first_name . ' ' . $customer->last_name) ?: '—' }}</td>
                                    <td class="small">{{ $customer->country ?: '—' }}</td>
                                    <td>
                                        @if($customer->status === 'subscribed')
                                            <span class="badge bg-success-subtle text-success">Suscrito</span>
                                        @elseif($customer->status === 'unsubscribed')
                                            <span class="badge bg-secondary-subtle text-secondary">Dado de baja</span>
                                        @elseif($customer->status === 'bounced')
                                            <span class="badge bg-danger-subtle text-danger">Bounce</span>
                                        @else
                                            <span class="badge bg-light text-muted">{{ $customer->status }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($customer->rfm_score)
                                            <div class="d-flex gap-1">
                                                @php
                                                    $rfm = $customer->rfm_score;
                                                    $segments = [
                                                        'champion' => ['Champions', 'success'],
                                                        'loyal' => ['Fiel', 'primary'],
                                                        'at_risk' => ['En riesgo', 'warning'],
                                                        'lost' => ['Perdido', 'danger'],
                                                    ];
                                                    [$label, $color] = $segments[$rfm] ?? ['—', 'secondary'];
                                                @endphp
                                                <span class="badge bg-{{ $color }}-subtle text-{{ $color }}">{{ $label }}</span>
                                            </div>
                                        @else
                                            <span class="text-muted small">—</span>
                                        @endif
                                    </td>
                                    <td class="small text-muted">{{ $customer->last_order_at?->format('d/m/Y') ?? '—' }}</td>
                                    <td class="small fw-semibold">{{ $customer->total_spent ? number_format($customer->total_spent, 2) . ' €' : '—' }}</td>
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-light" data-bs-toggle="dropdown">
                                                <i class="fas fa-ellipsis-vertical"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('remarketing.customers.show', $customer) }}">Ver perfil</a>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">No se encontraron clientes</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if($customers->hasPages())
                <div class="card-footer bg-white border-top">
                    {{ $customers->appends(request()->input())->links() }}
                </div>
            @endif

        </div>

    </div>

    {{-- Filter modal --}}
    <div class="modal fade" id="filterModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title">Filtros de clientes</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="GET" action="{{ route('remarketing.customers.index') }}">
                    @if(request('search'))
                        <input type="hidden" name="search" value="{{ request('search') }}">
                    @endif
                    <div class="modal-body">
                        <div class="row g-3">

                            <div class="col-12">
                                <label class="form-label">Tienda</label>
                                <select name="store_id" class="form-select">
                                    <option value="">Todas las tiendas</option>
                                    @foreach($stores as $store)
                                        <option value="{{ $store->id }}" {{ request('store_id') == $store->id ? 'selected' : '' }}>
                                            {{ $store->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Estado</label>
                                <select name="status" class="form-select">
                                    <option value="">Todos</option>
                                    <option value="subscribed" {{ request('status') === 'subscribed' ? 'selected' : '' }}>Suscrito</option>
                                    <option value="unsubscribed" {{ request('status') === 'unsubscribed' ? 'selected' : '' }}>Dado de baja</option>
                                    <option value="bounced" {{ request('status') === 'bounced' ? 'selected' : '' }}>Bounce</option>
                                </select>
                            </div>

                            <div class="col-12">
                                <label class="form-label">País</label>
                                <input type="text" name="country" class="form-control" placeholder="ES, MX, US..."
                                       value="{{ request('country') }}">
                            </div>

                            <div class="col-12">
                                <label class="form-label">Segmento RFM</label>
                                <select name="rfm_score" class="form-select">
                                    <option value="">Todos</option>
                                    <option value="champion" {{ request('rfm_score') === 'champion' ? 'selected' : '' }}>Champions</option>
                                    <option value="loyal" {{ request('rfm_score') === 'loyal' ? 'selected' : '' }}>Fieles</option>
                                    <option value="at_risk" {{ request('rfm_score') === 'at_risk' ? 'selected' : '' }}>En riesgo</option>
                                    <option value="lost" {{ request('rfm_score') === 'lost' ? 'selected' : '' }}>Perdidos</option>
                                </select>
                            </div>

                        </div>
                    </div>
                    <div class="modal-footer flex-column">
                        <button type="submit" class="btn btn-primary w-100 mb-2">Aplicar filtros</button>
                        <a href="{{ route('remarketing.customers.index') }}" class="btn btn-light w-100">Limpiar filtros</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection
