@extends('layouts.theme')

@section('title', 'Carritos abandonados')

@section('page_header')
    @include('core::components.card', ['title' => 'Carritos abandonados'])
@endsection

@section('content')

    @include('core::components.alerts')

    <div class="widget-content searchable-container list">

        <div class="card">

            <div class="card-header p-4 border-bottom">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Carritos abandonados</h5>
                        <p class="small mb-0 text-muted">Seguimiento de carritos activos y recuperados</p>
                    </div>
                    <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#filterModal">
                        <i class="fas fa-filter me-1"></i> Filtros
                        @php
                            $activeFilters = collect([request('store_id'), request('status'), request('date_from'), request('date_to')])->filter()->count();
                        @endphp
                        @if($activeFilters > 0)
                            <span class="badge bg-primary ms-1">{{ $activeFilters }}</span>
                        @endif
                    </button>
                </div>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Tienda</th>
                                <th>Email</th>
                                <th>Total</th>
                                <th>Items</th>
                                <th>Estado</th>
                                <th>Abandonado</th>
                                <th>Recuperado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($carts as $cart)
                                <tr>
                                    <td class="small">{{ $cart->store?->name ?? '—' }}</td>
                                    <td class="small">{{ $cart->email ?? '(anónimo)' }}</td>
                                    <td class="small fw-semibold">{{ number_format($cart->total, 2) }} {{ $cart->currency }}</td>
                                    <td class="small text-center">
                                        {{ is_array($cart->items) ? count($cart->items) : 0 }}
                                    </td>
                                    <td>
                                        @php
                                            $cartColors = [
                                                'active'    => ['warning', 'Activo'],
                                                'abandoned' => ['danger',  'Abandonado'],
                                                'recovered' => ['success', 'Recuperado'],
                                                'converted' => ['primary', 'Convertido'],
                                            ];
                                            [$color, $label] = $cartColors[$cart->status] ?? ['secondary', $cart->status];
                                        @endphp
                                        <span class="badge bg-{{ $color }}-subtle text-{{ $color }}">{{ $label }}</span>
                                    </td>
                                    <td class="small text-muted">{{ $cart->abandoned_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                    <td class="small text-muted">{{ $cart->recovered_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">No se encontraron carritos</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if($carts->hasPages())
                <div class="card-footer bg-white border-top">
                    {{ $carts->appends(request()->input())->links() }}
                </div>
            @endif

        </div>

    </div>

    {{-- Filter modal --}}
    <div class="modal fade" id="filterModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title">Filtros de carritos</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="GET" action="{{ route('remarketing.carts.index') }}">
                    <div class="modal-body">
                        <div class="row g-3">

                            <div class="col-12">
                                <label class="form-label">Tienda</label>
                                <select name="store_id" class="form-select">
                                    <option value="">Todas las tiendas</option>
                                    @foreach($stores ?? [] as $store)
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
                                    <option value="active"    {{ request('status') === 'active'    ? 'selected' : '' }}>Activo</option>
                                    <option value="abandoned" {{ request('status') === 'abandoned' ? 'selected' : '' }}>Abandonado</option>
                                    <option value="recovered" {{ request('status') === 'recovered' ? 'selected' : '' }}>Recuperado</option>
                                    <option value="converted" {{ request('status') === 'converted' ? 'selected' : '' }}>Convertido</option>
                                </select>
                            </div>

                            <div class="col-6">
                                <label class="form-label">Fecha desde</label>
                                <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                            </div>
                            <div class="col-6">
                                <label class="form-label">Fecha hasta</label>
                                <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                            </div>

                        </div>
                    </div>
                    <div class="modal-footer flex-column">
                        <button type="submit" class="btn btn-primary w-100 mb-2">Aplicar filtros</button>
                        <a href="{{ route('remarketing.carts.index') }}" class="btn btn-light w-100">Limpiar filtros</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection
