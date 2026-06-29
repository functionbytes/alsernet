@extends('layouts.theme')

@section('title', 'Analisis de margen')

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
                <li class="nav-item"><a class="nav-link" href="{{ route('ecommerce.reports.customer-ltv') }}">LTV clientes</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('ecommerce.reports.abandoned-carts') }}">Carritos abandonados</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('ecommerce.reports.search') }}">Busquedas</a></li>
                <li class="nav-item"><a class="nav-link active" href="{{ route('ecommerce.reports.margin') }}">Margen</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('ecommerce.reports.forecast') }}">Pronostico</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('ecommerce.reports.journey') }}">Journey</a></li>
            </ul>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="d-flex gap-2 align-items-end flex-wrap">
                <div>
                    <label class="form-label small mb-1">Periodo</label>
                    <select name="period" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="7"  @selected($period == 7)>Ultimos 7 dias</option>
                        <option value="30" @selected($period == 30)>Ultimos 30 dias</option>
                        <option value="90" @selected($period == 90)>Ultimos 90 dias</option>
                    </select>
                </div>
                <div>
                    <label class="form-label small mb-1">Ordenar por</label>
                    <select name="sort_by" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="margin_amount" @selected($sortBy === 'margin_amount')>Mayor margen</option>
                        <option value="revenue"       @selected($sortBy === 'revenue')>Mayor ingreso</option>
                        <option value="units_sold"    @selected($sortBy === 'units_sold')>Mas vendidos</option>
                    </select>
                </div>
            </form>
        </div>
    </div>

    {{-- Totals KPIs --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <p class="text-muted small mb-1">Ingresos totales</p>
                    <h4 class="mb-0 fw-bold">${{ number_format($totals['revenue'], 2) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <p class="text-muted small mb-1">Costo total</p>
                    <h4 class="mb-0 fw-bold">${{ number_format($totals['cost'], 2) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <p class="text-muted small mb-1">Margen bruto</p>
                    <h4 class="mb-0 fw-bold">${{ number_format($totals['margin'], 2) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <p class="text-muted small mb-1">Margen %</p>
                    <h4 class="mb-0 fw-bold {{ $totals['margin_percent'] >= 30 ? 'text-success' : ($totals['margin_percent'] >= 15 ? 'text-warning' : 'text-danger') }}">
                        {{ $totals['margin_percent'] }}%
                    </h4>
                </div>
            </div>
        </div>
    </div>

    {{-- Products table --}}
    <div class="card">
        <div class="card-header p-4 border-bottom">
            <h6 class="fw-bold mb-0">Margen por producto (top 50 — ultimos {{ $period }} dias)</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Producto</th>
                            <th>SKU</th>
                            <th class="text-end">Unidades</th>
                            <th class="text-end">Ingresos</th>
                            <th class="text-end">Costo</th>
                            <th class="text-end">Margen $</th>
                            <th class="text-end">Margen %</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($products as $p)
                            <tr>
                                <td>{{ $p->name }}</td>
                                <td><code class="small">{{ $p->sku ?? '—' }}</code></td>
                                <td class="text-end">{{ number_format($p->units_sold) }}</td>
                                <td class="text-end">${{ number_format($p->revenue, 2) }}</td>
                                <td class="text-end">
                                    @if($p->cost_per_item)
                                        ${{ number_format($p->total_cost, 2) }}
                                    @else
                                        <span class="text-muted">Sin costo</span>
                                    @endif
                                </td>
                                <td class="text-end fw-semibold">${{ number_format($p->margin_amount, 2) }}</td>
                                <td class="text-end">
                                    <span class="badge {{ $p->margin_percent >= 30 ? 'bg-success' : ($p->margin_percent >= 15 ? 'bg-warning text-dark' : 'bg-danger') }}">
                                        {{ $p->margin_percent }}%
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">Sin datos para este periodo</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
