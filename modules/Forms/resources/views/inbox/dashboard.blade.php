@extends('layouts.theme')

@section('title', 'Dashboard formularios')

@section('content')

    @include('core::components.card', ['title' => 'Dashboard formularios'])

    {{-- Filters bar --}}
    <div class="card card-body mb-4 border-0 shadow-sm">
        <div class="row align-items-center g-3">

            {{-- Period nav-pills --}}
            <div class="col-md">
                <ul class="nav nav-pills gap-1 flex-nowrap overflow-auto">
                    @foreach ([
                        'today'        => 'Hoy',
                        'last_7_days'  => '7 días',
                        'last_30_days' => '30 días',
                        'this_month'   => 'Este mes',
                        'last_month'   => 'Mes anterior',
                        'this_year'    => 'Este año',
                    ] as $key => $label)
                        <li class="nav-item flex-shrink-0">
                            <a class="nav-link py-1 px-3 small fw-semibold {{ $range === $key ? 'active' : 'text-muted' }}"
                               href="{{ route('forms.inbox.dashboard', ['range' => $key]) }}">
                                {{ $label }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- Actions --}}
            <div class="col-md-auto d-flex align-items-center gap-2">
                <div class="dropdown">
                    <a href="javascript:void(0)"
                       class="d-flex align-items-center justify-content-center rounded-circle text-muted btn-icon-sm"
                       data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-ellipsis-vertical"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item" href="{{ route('forms.inbox.index') }}">
                                Ir al inbox
                            </a>
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

    {{-- Stats cards --}}
    <div class="row g-3 mb-4">

        {{-- Total submissions --}}
        <div class="col-md-6">
            <div class="card w-100">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-8">
                            <h5 class="card-title fw-semibold mb-3">Total submissions</h5>
                            <h4 class="fw-semibold mb-2">{{ number_format($totalAll) }}</h4>
                            @php
                                $pct30 = $prev30Days > 0 ? round(($curr30Days - $prev30Days) / $prev30Days * 100, 1) : null;
                            @endphp
                            @if ($pct30 !== null)
                                <div class="d-flex align-items-center">
                                    <p class="text-muted me-1 fs-3 mb-0">{{ $pct30 >= 0 ? '+' : '' }}{{ $pct30 }}%</p>
                                    <p class="fs-3 mb-0 text-muted">vs anterior</p>
                                </div>
                            @else
                                <p class="fs-3 mb-0 text-muted">Todas las épocas</p>
                            @endif
                        </div>
                        <div class="col-4">
                            <div class="d-flex justify-content-center">
                                <div id="spark-total"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Submissions hoy --}}
        <div class="col-md-6">
            <div class="card w-100">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-8">
                            <h5 class="card-title fw-semibold mb-3">Submissions hoy</h5>
                            <h4 class="fw-semibold mb-2">{{ number_format($totalToday) }}</h4>
                            @php
                                $pctToday = $prevToday > 0 ? round(($totalToday - $prevToday) / $prevToday * 100, 1) : null;
                            @endphp
                            @if ($pctToday !== null)
                                <div class="d-flex align-items-center">
                                    <p class="text-muted me-1 fs-3 mb-0">{{ $pctToday >= 0 ? '+' : '' }}{{ $pctToday }}%</p>
                                    <p class="fs-3 mb-0 text-muted">vs ayer</p>
                                </div>
                            @else
                                <p class="fs-3 mb-0 text-muted">Recibidas hoy</p>
                            @endif
                        </div>
                        <div class="col-4">
                            <div class="d-flex justify-content-center">
                                <div id="spark-today"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Este mes --}}
        <div class="col-md-6">
            <div class="card w-100">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-8">
                            <h5 class="card-title fw-semibold mb-3">Este mes</h5>
                            <h4 class="fw-semibold mb-2">{{ number_format($totalMonth) }}</h4>
                            @php
                                $pctMonth = $prevMonth > 0 ? round(($totalMonth - $prevMonth) / $prevMonth * 100, 1) : null;
                            @endphp
                            @if ($pctMonth !== null)
                                <div class="d-flex align-items-center">
                                    <p class="text-muted me-1 fs-3 mb-0">{{ $pctMonth >= 0 ? '+' : '' }}{{ $pctMonth }}%</p>
                                    <p class="fs-3 mb-0 text-muted">vs mes anterior</p>
                                </div>
                            @else
                                <p class="fs-3 mb-0 text-muted">Recibidas este mes</p>
                            @endif
                        </div>
                        <div class="col-4">
                            <div class="d-flex justify-content-center">
                                <div id="spark-month"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Sin leer --}}
        <div class="col-md-6">
            <div class="card w-100">
                <div class="card-body">
                    <h5 class="card-title fw-semibold mb-3">Sin leer</h5>
                    <h4 class="fw-semibold mb-2">{{ number_format($totalUnread) }}</h4>
                    <p class="fs-3 mb-0 text-muted">Pendientes de revisión</p>
                </div>
            </div>
        </div>

    </div>

    <div class="row g-3">

        {{-- Formularios más activos --}}
        <div class="col-lg-8">
            <div class="card w-100 h-100">
                <div class="card-header">
                    <h4 class="card-title fw-semibold mb-0">Formularios más activos</h4>
                    <p class="card-subtitle mt-1">Análisis de submissions por formulario</p>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0 text-nowrap">
                            <thead>
                                <tr class="text-muted fw-semibold">
                                    <th scope="col" class="ps-0">Formulario</th>
                                    <th scope="col" class="text-end">Total</th>
                                    <th scope="col" class="text-end">Sin leer</th>
                                    <th scope="col" class="text-end">Nuevas</th>
                                    <th scope="col" class="text-end pe-0"></th>
                                </tr>
                            </thead>
                            <tbody class="border-top">
                                @forelse ($topForms as $i => $item)
                                    <tr>
                                        <td class="ps-0">
                                            <div>
                                                <a href="{{ route('forms.inbox.index', ['form_id' => $item->id]) }}"
                                                   class="fw-semibold text-decoration-none text-dark text-truncate d-block form-name-link">
                                                    {{ $item->name }}
                                                </a>
                                                <span class="fs-2 text-muted">#{{ $item->id }}</span>
                                            </div>
                                        </td>
                                        <td class="text-end">
                                            <span class="badge rounded-pill" style="background:#e8e8e8;color:#333333;">
                                                {{ number_format($item->total ?? 0) }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            @if (($item->unread ?? 0) > 0)
                                                <span class="badge rounded-pill" style="background:#e8e8e8;color:#333333;">
                                                    {{ number_format($item->unread) }}
                                                </span>
                                            @else
                                                <span class="text-muted">0</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            @if (($item->count_new ?? 0) > 0)
                                                <span class="badge rounded-pill" style="background:#e8e8e8;color:#333333;">
                                                    {{ number_format($item->count_new) }}
                                                </span>
                                            @else
                                                <span class="text-muted">0</span>
                                            @endif
                                        </td>
                                        <td class="text-end pe-0">
                                            <span class="text-muted" data-bs-toggle="tooltip" data-bs-placement="left"
                                                  title="En revisión: {{ number_format($item->in_review ?? 0) }} · Resueltas: {{ number_format($item->resolved ?? 0) }} · Rechazadas: {{ number_format($item->rejected ?? 0) }}">
                                                <i class="fas fa-circle-info"></i>
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4 ps-0">
                                            <i class="fas fa-inbox fa-2x mb-2 d-block opacity-25"></i>
                                            <small>Sin datos disponibles</small>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Submissions por estado --}}
        <div class="col-lg-4">
            @php
                $statusItems = [
                    ['key' => 'new',       'label' => 'Nuevas',      'sub' => 'Sin revisar',       'icon' => 'fas fa-inbox',        'bg' => '#e0e0e0', 'txt' => '#333333', 'color' => '#333333'],
                    ['key' => 'in_review', 'label' => 'En revisión', 'sub' => 'Bajo seguimiento',  'icon' => 'fas fa-eye',          'bg' => '#e8e8e8', 'txt' => '#555555', 'color' => '#555555'],
                    ['key' => 'resolved',  'label' => 'Resueltas',   'sub' => 'Completadas',       'icon' => 'fas fa-check-circle', 'bg' => '#efefef', 'txt' => '#777777', 'color' => '#777777'],
                    ['key' => 'rejected',  'label' => 'Rechazadas',  'sub' => 'Descartadas',       'icon' => 'fas fa-times-circle', 'bg' => '#f5f5f5', 'txt' => '#999999', 'color' => '#999999'],
                ];
                $statusTotal = array_sum(array_values($byStatus));
            @endphp
            <div class="card w-100 h-100">
                <div class="card-header">
                    <h4 class="card-title fw-semibold mb-0">Submissions por estado</h4>
                    <p class="card-subtitle mt-1">Por estado</p>
                </div>
                <div class="card-body">
                    <div id="status-chart" class="mb-4 chart-200"></div>
                    <hr>
                    @foreach ($statusItems as $i => $item)
                        @php
                            $count = $byStatus[$item['key']] ?? 0;
                            $pct   = $statusTotal > 0 ? round($count / $statusTotal * 100, 1) : 0;
                            $isLast = $i === count($statusItems) - 1;
                        @endphp
                        <div class="d-flex align-items-center justify-content-between {{ $isLast ? '' : 'mb-4' }}">
                            <div class="d-flex align-items-center">
                                <div class="p-2 rounded-2 d-flex align-items-center justify-content-center me-3"
                                     style="width:36px;height:36px;background:{{ $item['bg'] }};">
                                    <i class="{{ $item['icon'] }}" style="color:{{ $item['txt'] }};"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0 fw-semibold">{{ $item['label'] }}</h6>
                                    <p class="fs-3 mb-0 text-muted">{{ $item['sub'] }}</p>
                                </div>
                            </div>
                            <h6 class="mb-0 fw-semibold">
                                {{ number_format($count) }}
                                <small class="text-muted fw-normal">{{ $pct }}%</small>
                            </h6>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

    </div>

@endsection

@push('css')
<style>
    .btn-icon-sm { width: 30px; height: 30px; background: #f5f6f8; }
    .status-icon { width: 36px; height: 36px; }
    .chart-200 { height: 200px; }
    .form-name-link { max-width: 260px; }
</style>
@endpush

@push('scripts')
<script data-pagespeed-no-defer src="{{ themeAsset('libs/apexcharts/dist/apexcharts.min.js') }}"></script>
<script>
(function () {
    'use strict';

    const sparkDays = @json($sparkDays);

    const sparkCfg = (data, color, type) => ({
        series: [{ data }],
        chart: {
            type,
            height: 70,
            width: 70,
            sparkline: { enabled: true },
            animations: { enabled: false },
            fontFamily: 'inherit',
        },
        colors: [color],
        stroke: { curve: 'smooth', width: 2 },
        fill: type === 'area'
            ? { type: 'gradient', gradient: { opacityFrom: 0.35, opacityTo: 0.02 } }
            : { type: 'solid' },
        tooltip: {
            fixed: { enabled: false },
            x: { show: false },
            y: { title: { formatter: () => '' } },
        },
        plotOptions: { bar: { borderRadius: 2, columnWidth: '60%' } },
    });

    const sparks = [
        { id: 'spark-total', color: '#F5B754', type: 'area' },
        { id: 'spark-today', color: '#333333', type: 'bar'  },
        { id: 'spark-month', color: '#008bcd', type: 'bar'  },
    ];

    sparks.forEach(function (s) {
        const el = document.getElementById(s.id);
        if (!el) return;
        new ApexCharts(el, sparkCfg(sparkDays, s.color, s.type)).render();
    });

    // ─── Status donut ─────────────────────────────────────────────────────
    const statusData   = @json(array_values($byStatus));
    const statusLabels = @json(array_column($statusItems, 'label'));
    const statusColors = @json(array_column($statusItems, 'color'));

    new ApexCharts(document.querySelector('#status-chart'), {
        series: statusData,
        labels: statusLabels,
        chart: { type: 'donut', height: 200, fontFamily: 'inherit' },
        colors: statusColors,
        legend: { show: false },
        dataLabels: { enabled: false },
        tooltip: { y: { formatter: v => new Intl.NumberFormat('es-ES').format(v) } },
        plotOptions: { pie: { donut: { size: '75%' } } },
    }).render();
}());
</script>
@endpush
