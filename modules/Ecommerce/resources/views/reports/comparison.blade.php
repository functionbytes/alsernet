@extends('layouts.theme')

@section('title', 'Comparativa de período')

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
                <li class="nav-item"><a class="nav-link active" href="{{ route('ecommerce.reports.comparison') }}">Comparativa</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('ecommerce.reports.funnel') }}">Embudo</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('ecommerce.reports.customer-ltv') }}">LTV clientes</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('ecommerce.reports.abandoned-carts') }}">Carritos abandonados</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('ecommerce.reports.search') }}">Busquedas</a></li>
            </ul>
        </div>
    </div>

    {{-- Period selector --}}
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="d-flex gap-2 align-items-center flex-wrap">
                <select name="period" class="form-select form-select-sm" style="max-width:250px">
                    <option value="week" @selected($period === 'week')>Semana vs semana anterior</option>
                    <option value="month" @selected($period === 'month')>Mes vs mes anterior</option>
                    <option value="quarter" @selected($period === 'quarter')>Trimestre vs trimestre anterior</option>
                    <option value="year" @selected($period === 'year')>Año vs año anterior</option>
                </select>
                <button type="submit" class="btn btn-sm btn-primary">Actualizar</button>
            </form>
        </div>
    </div>

    {{-- KPI comparison cards --}}
    <div class="row g-3 mb-4">
        @php
            $kpis = [
                ['label' => 'Ingresos',        'current' => '$'.number_format($current['revenue'], 2),  'previous' => '$'.number_format($previous['revenue'], 2),  'delta' => $delta['revenue'],   'icon' => 'fa-dollar-sign'],
                ['label' => 'Ordenes',          'current' => $current['orders'],                         'previous' => $previous['orders'],                         'delta' => $delta['orders'],    'icon' => 'fa-shopping-bag'],
                ['label' => 'Ticket promedio',  'current' => '$'.number_format($current['aov'], 2),      'previous' => '$'.number_format($previous['aov'], 2),      'delta' => $delta['aov'],       'icon' => 'fa-receipt'],
                ['label' => 'Clientes unicos',  'current' => $current['customers'],                      'previous' => $previous['customers'],                      'delta' => $delta['customers'], 'icon' => 'fa-users'],
            ];
        @endphp
        @foreach ($kpis as $kpi)
            <div class="col-sm-6 col-xl-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="text-muted small mb-1">{{ $kpi['label'] }}</p>
                                <h4 class="mb-0 fw-bold">{{ $kpi['current'] }}</h4>
                                <small class="text-muted">Anterior: {{ $kpi['previous'] }}</small>
                            </div>
                            <i class="fas {{ $kpi['icon'] }} fa-2x text-muted opacity-25"></i>
                        </div>
                        <div class="mt-2">
                            <span class="badge {{ $kpi['delta'] >= 0 ? 'bg-success' : 'bg-danger' }}">
                                <i class="fas fa-arrow-{{ $kpi['delta'] >= 0 ? 'up' : 'down' }}"></i>
                                {{ abs($kpi['delta']) }}%
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Daily revenue chart --}}
    <div class="card">
        <div class="card-header p-4 border-bottom">
            <h6 class="fw-bold mb-0">Evolucion diaria de ingresos</h6>
        </div>
        <div class="card-body">
            <canvas id="comparisonChart" height="80"></canvas>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const ctx = document.getElementById('comparisonChart');
            if (!ctx) { return; }

            const current = @json($currentSeries);
            const previous = @json($previousSeries);

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: current.map(p => p.date),
                    datasets: [
                        {
                            label: 'Período actual',
                            data: current.map(p => p.revenue),
                            borderColor: '#90bb13',
                            backgroundColor: 'rgba(144,187,19,0.08)',
                            fill: true,
                            tension: 0.3,
                        },
                        {
                            label: 'Período anterior',
                            data: previous.map(p => p.revenue),
                            borderColor: '#999',
                            borderDash: [5, 5],
                            backgroundColor: 'rgba(153,153,153,0.05)',
                            fill: true,
                            tension: 0.3,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    plugins: { legend: { position: 'top' } },
                    scales: { y: { beginAtZero: true } },
                },
            });
        });
    </script>
@endpush
