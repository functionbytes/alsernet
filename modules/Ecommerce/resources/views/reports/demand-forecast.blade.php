@extends('layouts.theme')

@section('title', 'Pronostico de demanda')

@section('content')
    @include('core::components.card', ['title' => 'Ecommerce - Reportes'])

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
                <li class="nav-item"><a class="nav-link" href="{{ route('ecommerce.reports.margin') }}">Margen</a></li>
                <li class="nav-item"><a class="nav-link active" href="{{ route('ecommerce.reports.forecast') }}">Pronostico</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('ecommerce.reports.journey') }}">Journey</a></li>
            </ul>
        </div>
    </div>

    {{-- Filter --}}
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="d-flex gap-2 align-items-end flex-wrap">
                <div>
                    <label class="form-label small mb-1">Pronostico para los proximos</label>
                    <select name="days_ahead" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="7"  @selected($daysAhead == 7)>7 dias</option>
                        <option value="15" @selected($daysAhead == 15)>15 dias</option>
                        <option value="30" @selected($daysAhead == 30)>30 dias</option>
                        <option value="60" @selected($daysAhead == 60)>60 dias</option>
                    </select>
                </div>
            </form>
        </div>
    </div>

    {{-- Info note --}}
    <div class="alert alert-info d-flex gap-2 align-items-center mb-4">
        <i class="fas fa-circle-info"></i>
        <span>El pronostico usa el promedio diario de los ultimos 60 dias ajustado por tendencia (comparacion primera vs segunda mitad del periodo).</span>
    </div>

    {{-- Forecast table --}}
    <div class="card">
        <div class="card-header p-4 border-bottom">
            <h6 class="fw-bold mb-0">Pronostico de demanda — proximos {{ $daysAhead }} dias</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Producto</th>
                            <th>SKU</th>
                            <th class="text-end">Ventas historicas</th>
                            <th class="text-end">Promedio diario</th>
                            <th class="text-end">Tendencia</th>
                            <th class="text-end">Pronostico ({{ $daysAhead }}d)</th>
                            <th class="text-end">Stock actual</th>
                            <th class="text-center">Reposicion</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            @php $product = $row['product']; @endphp
                            <tr>
                                <td>{{ $product->name }}</td>
                                <td><code class="small">{{ $product->sku ?? '—' }}</code></td>
                                <td class="text-end">{{ number_format($row['historical_units']) }}</td>
                                <td class="text-end">{{ $row['avg_daily'] }}</td>
                                <td class="text-end">
                                    @php $trend = $row['trend_factor']; @endphp
                                    <span class="badge {{ $trend > 1.05 ? 'bg-success' : ($trend < 0.95 ? 'bg-danger' : 'bg-secondary') }}">
                                        @if($trend > 1)
                                            <i class="fas fa-arrow-up"></i>
                                        @elseif($trend < 1)
                                            <i class="fas fa-arrow-down"></i>
                                        @else
                                            <i class="fas fa-minus"></i>
                                        @endif
                                        {{ $trend }}x
                                    </span>
                                </td>
                                <td class="text-end fw-semibold">{{ number_format($row['forecasted_units']) }}</td>
                                <td class="text-end">
                                    @if($product->with_storehouse_management)
                                        {{ number_format($row['current_stock']) }}
                                    @else
                                        <span class="text-muted small">Sin control</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($row['needs_restock'])
                                        <span class="badge bg-warning text-dark">
                                            <i class="fas fa-triangle-exclamation"></i>
                                            Reponer {{ number_format($row['restock_quantity']) }}
                                        </span>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">Sin datos de ventas en los ultimos 60 dias</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
