@extends('layouts.theme')

@section('title', 'Lifetime Value de clientes')

@section('page_header')
    @include('core::components.card', ['title' => 'Ecommerce - Reportes'])
@endsection

@section('content')
    @include('core::components.alerts')

    {{-- Report navigation --}}
    <div class="card mb-3">
        <div class="card-body py-2">
            <ul class="nav nav-pills nav-fill">
                <li class="nav-item"><a class="nav-link" href="{{ route('ecommerce.reports.index') }}">Resumen</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('ecommerce.reports.comparison') }}">Comparativa</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('ecommerce.reports.funnel') }}">Embudo</a></li>
                <li class="nav-item"><a class="nav-link active" href="{{ route('ecommerce.reports.customer-ltv') }}">LTV clientes</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('ecommerce.reports.abandoned-carts') }}">Carritos abandonados</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('ecommerce.reports.search') }}">Busquedas</a></li>
            </ul>
        </div>
    </div>

    {{-- Summary stats --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-4">
            <div class="card h-100">
                <div class="card-body">
                    <p class="text-muted small mb-1">LTV promedio</p>
                    <h3 class="mb-0 fw-bold">${{ number_format($stats['avg_ltv'], 2) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="card h-100">
                <div class="card-body">
                    <p class="text-muted small mb-1">Ordenes promedio por cliente</p>
                    <h3 class="mb-0 fw-bold">{{ number_format($stats['avg_orders'], 1) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="card h-100">
                <div class="card-body">
                    <p class="text-muted small mb-1">Ingresos totales</p>
                    <h3 class="mb-0 fw-bold">${{ number_format($stats['total_revenue'], 2) }}</h3>
                </div>
            </div>
        </div>
    </div>

    {{-- Customers table --}}
    <div class="card">
        <div class="card-header p-4 border-bottom">
            <h6 class="fw-bold mb-0">Clientes por valor de vida</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="px-4">Cliente</th>
                            <th>Email</th>
                            <th class="text-end">Ordenes</th>
                            <th class="text-end">LTV</th>
                            <th class="text-end">Ticket promedio</th>
                            <th>Primera compra</th>
                            <th>Ultima compra</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($customers as $c)
                            <tr>
                                <td class="px-4"><strong>{{ $c->name }}</strong></td>
                                <td class="text-muted small">{{ $c->email }}</td>
                                <td class="text-end">{{ $c->orders_count }}</td>
                                <td class="text-end fw-bold text-success">${{ number_format($c->ltv, 2) }}</td>
                                <td class="text-end">${{ number_format($c->aov, 2) }}</td>
                                <td class="small text-muted">{{ \Carbon\Carbon::parse($c->first_order_at)->format('d/m/Y') }}</td>
                                <td class="small text-muted">{{ \Carbon\Carbon::parse($c->last_order_at)->format('d/m/Y') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">Sin datos de clientes</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($customers->hasPages())
            <div class="card-footer bg-white border-top">
                {{ $customers->links() }}
            </div>
        @endif
    </div>
@endsection
