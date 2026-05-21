@extends('layouts.theme')

@section('title', 'Remarketing — Dashboard')

@section('content')

    {{-- Filters bar --}}
    <div class="card card-body mb-4 border-0 shadow-sm">
        <div class="row align-items-center g-3">
            <div class="col-md">
                <p class="mb-0 fw-semibold text-muted small">Datos de remarketing y carros abandonados</p>
            </div>
            <div class="col-md-auto d-flex align-items-center gap-2">
                <div class="dropdown">
                    <a href="javascript:void(0)"
                       class="d-flex align-items-center justify-content-center rounded-circle text-muted btn-icon-sm"
                       data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-ellipsis-vertical"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item" href="{{ route('remarketing.campaigns.index') }}">Ver campañas</a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="{{ route('remarketing.carts.index') }}">Ver carritos abandonados</a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="javascript:void(0)" onclick="window.print()">Imprimir</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    @include('core::components.alerts')

    {{-- KPI cards (con comparación vs período anterior) --}}
    @php
        $current = $stats['current'] ?? [];
        $delta = $stats['delta'] ?? [];

        $renderDelta = function (?array $d) {
            if (! $d || abs($d['percent']) < 0.05) {
                return '<span class="badge bg-secondary-subtle text-secondary delta-badge">—</span>';
            }
            $color = $d['is_positive'] ? 'success' : 'danger';
            $arrow = $d['percent'] >= 0 ? 'arrow-up' : 'arrow-down';
            $sign = $d['percent'] >= 0 ? '+' : '';
            return '<span class="badge bg-'.$color.'-subtle text-'.$color.' delta-badge"><i class="fas fa-'.$arrow.' me-1"></i>'.$sign.number_format($d['percent'], 1).'%</span>';
        };

        $kpis = [
            ['key' => 'stores_count',  'title' => 'Tiendas conectadas', 'value' => number_format($current['stores_count'] ?? 0),     'subtitle' => 'Integraciones activas',  'icon' => 'fa-store',              'tone' => 'primary'],
            ['key' => 'customers_count','title' => 'Clientes totales',  'value' => number_format($current['customers_count'] ?? 0),   'subtitle' => 'En todas las tiendas',   'icon' => 'fa-users',              'tone' => 'info'],
            ['key' => 'subscribed_count','title'=> 'Suscritos activos', 'value' => number_format($current['subscribed_count'] ?? 0),  'subtitle' => 'Clientes suscritos',      'icon' => 'fa-check-circle',        'tone' => 'success'],
            ['key' => 'campaigns_sent','title' => 'Enviados (30d)',     'value' => number_format($current['campaigns_sent'] ?? 0),    'subtitle' => 'Campañas enviadas',       'icon' => 'fa-paper-plane',         'tone' => 'warning'],
            ['key' => 'revenue',        'title' => 'Revenue (30d)',     'value' => number_format($current['revenue'] ?? 0, 2).' €',   'subtitle' => 'Ingresos generados',     'icon' => 'fa-euro-sign',           'tone' => 'success'],
            ['key' => 'bounce_rate',    'title' => 'Bounce rate',       'value' => number_format($current['bounce_rate'] ?? 0, 1).'%','subtitle' => 'Últimos 30 días',         'icon' => 'fa-ban',                 'tone' => 'danger'],
            ['key' => 'open_rate',      'title' => 'Open rate',         'value' => number_format($current['open_rate'] ?? 0, 1).'%', 'subtitle' => 'Apertura de emails',     'icon' => 'fa-envelope-open-text',  'tone' => 'primary'],
        ];
    @endphp

    <div class="row g-3 mb-4">
        @foreach($kpis as $kpi)
            <div class="col-md-6 col-xl-3">
                <div class="card w-100">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-8">
                                <h5 class="card-title fw-semibold mb-3">{{ $kpi['title'] }}</h5>
                                <h4 class="fw-semibold mb-2">{{ $kpi['value'] }}</h4>
                                <p class="fs-3 mb-0 text-muted d-flex align-items-center gap-2">
                                    {!! $renderDelta($delta[$kpi['key']] ?? null) !!}
                                    <span>vs 30d anteriores</span>
                                </p>
                            </div>
                            <div class="col-4 d-flex justify-content-end">
                                <span class="rounded-circle bg-{{ $kpi['tone'] }}-subtle d-flex align-items-center justify-content-center stat-icon">
                                    <i class="fas {{ $kpi['icon'] }} text-{{ $kpi['tone'] }}"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Revenue chart (col-8) + Recent messages (col-4) --}}
    <div class="row g-3 mb-4">

        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="card-title fw-semibold mb-0">Revenue mensual</h4>
                        <p class="card-subtitle mt-1">Ingresos por mes</p>
                    </div>
                    <select id="chart-range" class="form-select form-select-sm w-auto">
                        <option value="6">Últimos 6 meses</option>
                        <option value="12" selected>Últimos 12 meses</option>
                    </select>
                </div>
                <div class="card-body">
                    <div id="revenue-chart" style="height:300px"></div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header">
                    <h4 class="card-title fw-semibold mb-0">Mensajes recientes</h4>
                    <p class="card-subtitle mt-1">Últimos enviados</p>
                </div>
                <div class="card-body p-0">
                    @if($recentMessages->isEmpty())
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-inbox fa-2x mb-2 d-block opacity-25"></i>
                            <small>Sin mensajes enviados recientemente</small>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Email</th>
                                        <th>Estado</th>
                                        <th>Enviado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentMessages as $msg)
                                        <tr>
                                            <td class="small text-truncate" style="max-width:130px;">{{ $msg->email }}</td>
                                            <td>
                                                @php
                                                    $statusColors = ['sent'=>'success','delivered'=>'primary','opened'=>'info','clicked'=>'warning','bounced'=>'danger','failed'=>'danger'];
                                                    $color = $statusColors[$msg->status] ?? 'secondary';
                                                @endphp
                                                <span class="badge bg-{{ $color }}-subtle text-{{ $color }}">{{ $msg->status }}</span>
                                            </td>
                                            <td class="small text-muted">{{ $msg->sent_at?->diffForHumans() ?? '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

    </div>

    {{-- Abandoned carts --}}
    <div class="row g-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="card-title fw-semibold mb-0">Carritos abandonados recientes</h4>
                        <p class="card-subtitle mt-1">Últimas recuperaciones</p>
                    </div>
                    <a href="{{ route('remarketing.carts.index') }}" class="btn btn-sm btn-outline-secondary">Ver todos</a>
                </div>
                <div class="card-body p-0">
                    @if($recentCarts->isEmpty())
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-shopping-cart fa-2x mb-2 d-block opacity-25"></i>
                            <small>Sin carritos abandonados recientes</small>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Email</th>
                                        <th>Total</th>
                                        <th>Estado</th>
                                        <th>Abandonado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentCarts as $cart)
                                        <tr>
                                            <td class="small">{{ $cart->email ?? '(anónimo)' }}</td>
                                            <td class="small fw-semibold">{{ number_format($cart->total, 2) }} {{ $cart->currency }}</td>
                                            <td>
                                                @php
                                                    $cartColors = ['active'=>'warning','abandoned'=>'danger','recovered'=>'success','converted'=>'primary'];
                                                    $cColor = $cartColors[$cart->status] ?? 'secondary';
                                                @endphp
                                                <span class="badge bg-{{ $cColor }}-subtle text-{{ $cColor }}">{{ $cart->status }}</span>
                                            </td>
                                            <td class="small text-muted">{{ $cart->abandoned_at?->diffForHumans() ?? '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

@endsection

@push('css')
<style>
    .btn-icon-sm { width: 30px; height: 30px; background: #f5f6f8; }
    .stat-icon   { width: 44px; height: 44px; }
    .delta-badge { font-size: 0.7rem; padding: 0.25rem 0.5rem; }
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function () {
    let revenueChart = null;

    function loadRevenueChart(months) {
        $.getJSON('{{ route('remarketing.dashboard.chart-data') }}', { months: months }, function (res) {
            if (revenueChart) revenueChart.dispose();
            revenueChart = $('#revenue-chart').dxChart({
                dataSource: (res.labels || []).map(function (label, i) {
                    return { month: label, revenue: (res.datasets && res.datasets[0]) ? res.datasets[0].data[i] : 0 };
                }),
                series: [{
                    valueField: 'revenue',
                    argumentField: 'month',
                    name: 'Revenue',
                    type: 'bar',
                    color: '#b10100'
                }],
                argumentAxis: { argumentType: 'string' },
                valueAxis: { label: { format: { type: 'fixedPoint', precision: 0 } } },
                tooltip: { enabled: true, format: { type: 'fixedPoint', precision: 2 } },
                legend: { visible: false }
            }).dxChart('instance');
        }).fail(function () {
            $('#revenue-chart').dxChart({
                dataSource: [
                    { month: 'Ene', revenue: 0 }, { month: 'Feb', revenue: 0 },
                    { month: 'Mar', revenue: 0 }, { month: 'Abr', revenue: 0 }
                ],
                series: [{ valueField: 'revenue', argumentField: 'month', type: 'bar', color: '#b10100' }],
                legend: { visible: false }
            });
        });
    }

    loadRevenueChart(12);

    $('#chart-range').on('change', function () {
        loadRevenueChart($(this).val());
    });
});
</script>
@endpush
