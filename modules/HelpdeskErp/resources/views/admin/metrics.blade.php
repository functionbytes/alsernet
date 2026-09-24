@extends('layouts.theme')

@php
    use Modules\HelpdeskErp\Services\ErpAdmin\ErpAdminMetricsService as M;

    $agentName = $agentId ? ($agents[$agentId] ?? 'Agente #'.$agentId) : null;
    $exportParams = array_filter(['days' => $days, 'agent' => $agentId]);
    $num = fn ($n) => number_format((int) $n, 0, ',', '.');
    $maxDaily = max(1, ...array_map(fn ($d) => $d['total'], $daily ?: [['total' => 0]]));
    $maxSection = max(1, ...array_map(fn ($s) => $s['total'], $sections ?: [['total' => 0]]));
    $maxAgent = max(1, ...array_map(fn ($a) => $a['overview'] + $a['orders'] + $a['sections'], $agentRows ?: [['overview' => 0, 'orders' => 0, 'sections' => 0]]));
@endphp

@section('title', 'Métricas de Gestión')

@section('page_header')
    @include('core::components.card', ['title' => 'Métricas de Gestión', 'subtitle' => 'Uso del panel de Gestión (ERP) en el chat y tiempos de respuesta del manager'])
@endsection

@include('helpdeskerp::admin._assets')

@section('content')
    <div class="era-page">
        @include('helpdeskerp::admin._flash')

        @unless ($ready)
            <div class="era-note era-note--warn" role="alert">
                Falta crear la tabla de métricas: hay una migración pendiente de ejecutar. Hasta entonces no se registra el uso.
            </div>
        @endunless
        @if ($ready && ! $enabled)
            <div class="era-note" role="status">
                El registro de uso está desactivado en «Ajustes de Gestión»: se muestra lo guardado hasta entonces.
            </div>
        @endif

        <div class="card era-card mb-3">
            <div class="card-body">
                <form method="GET" action="{{ route('manager.helpdesk.erp.admin.metrics.index') }}" class="era-filters" id="eraMetricsFilters">
                    <div class="era-chips" role="group" aria-label="Periodo">
                        @foreach ($periods as $period)
                            <a href="{{ route('manager.helpdesk.erp.admin.metrics.index', array_filter(['days' => $period, 'agent' => $agentId])) }}"
                               class="era-chip {{ $days === $period ? 'is-on' : '' }}"
                               @if ($days === $period) aria-current="true" @endif>{{ $period }} días</a>
                        @endforeach
                    </div>
                    <input type="hidden" name="days" value="{{ $days }}">
                    <select name="agent" class="form-select era-select" aria-label="Agente" data-era-autosubmit>
                        <option value="">Todos los agentes</option>
                        @foreach ($agents as $id => $name)
                            <option value="{{ $id }}" @selected($agentId === (int) $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                    <noscript><button type="submit" class="era-btn era-btn--ghost">Aplicar</button></noscript>
                    <span class="era-caption">Desde el {{ $since->format('d/m/Y') }}{{ $agentName ? ' · '.$agentName : '' }} · se guardan {{ $retention }} días</span>
                    @if ($ready)
                        <a class="era-btn era-btn--ghost era-filters-export" href="{{ route('manager.helpdesk.erp.admin.metrics.export', $exportParams) }}">Exportar CSV</a>
                    @endif
                </form>
            </div>
        </div>

        @if ($kpis)
            <div class="era-kpis mb-3">
                <div class="era-kpi">
                    <span class="era-kpi-l">Resúmenes abiertos</span>
                    <span class="era-kpi-n">{{ $num($kpis['overview']) }}</span>
                    <span class="era-kpi-d">{{ $num($kpis['customers']) }} clientes · {{ $num($kpis['agents']) }} agentes</span>
                </div>
                <div class="era-kpi">
                    <span class="era-kpi-l">Pedidos abiertos</span>
                    <span class="era-kpi-n">{{ $num($kpis['orders']) }}</span>
                    <span class="era-kpi-d">{{ $num($kpis['documents']) }} albaranes y facturas</span>
                </div>
                <div class="era-kpi">
                    <span class="era-kpi-l">Secciones consultadas</span>
                    <span class="era-kpi-n">{{ $num($kpis['sections']) }}</span>
                    <span class="era-kpi-d">Pestañas del panel</span>
                </div>
                <div class="era-kpi era-kpi--amber">
                    <span class="era-kpi-l">Bloqueados vistos</span>
                    <span class="era-kpi-n">{{ $num($kpis['blocked']) }}</span>
                    <span class="era-kpi-d">Secciones pendientes de permiso en Oracle</span>
                </div>
                <div class="era-kpi {{ $kpis['down'] > 0 ? 'era-kpi--amber' : '' }}">
                    <span class="era-kpi-l">Sin conexión</span>
                    <span class="era-kpi-n">{{ $num($kpis['down']) }}</span>
                    <span class="era-kpi-d">{{ $num($kpis['panel_down']) }} respuestas · {{ $num($kpis['manager_down']) }} llamadas fallidas</span>
                </div>
                <div class="era-kpi">
                    <span class="era-kpi-l">Clientes sin vínculo</span>
                    <span class="era-kpi-n">{{ $num($kpis['unlinked_customers']) }}</span>
                    <span class="era-kpi-d">{{ $num($kpis['unlinked_opens']) }} aperturas sin vínculo con Gestión</span>
                </div>
                <div class="era-kpi">
                    <span class="era-kpi-l">Manager p50 / p95</span>
                    <span class="era-kpi-n era-kpi-n--sm">{{ M::ms($kpis['manager_p50']) }} / {{ M::ms($kpis['manager_p95']) }}</span>
                    <span class="era-kpi-d">{{ $num($kpis['manager_calls']) }} llamadas · {{ $num($kpis['manager_errors']) }} con error 5xx</span>
                </div>
                <div class="era-kpi">
                    <span class="era-kpi-l">Panel p50 / p95</span>
                    <span class="era-kpi-n era-kpi-n--sm">{{ M::ms($kpis['panel_p50']) }} / {{ M::ms($kpis['panel_p95']) }}</span>
                    <span class="era-kpi-d">Respuesta al agente, con caché</span>
                </div>
            </div>

            {{-- Actividad diaria --}}
            <div class="card era-card mb-3">
                <div class="card-header border-bottom p-3">
                    <h5 class="mb-0 fw-bold">Actividad diaria</h5>
                    <small class="text-muted">Resúmenes, pedidos y secciones abiertos cada día.</small>
                </div>
                <div class="card-body">
                    <div class="era-legend">
                        <span class="era-legend-i"><span class="era-dot era-dot--a"></span>Resúmenes</span>
                        <span class="era-legend-i"><span class="era-dot era-dot--b"></span>Pedidos</span>
                        <span class="era-legend-i"><span class="era-dot era-dot--c"></span>Secciones</span>
                    </div>
                    <div class="era-days" role="list">
                        @foreach ($daily as $d)
                            <div class="era-day" role="listitem" title="{{ $d['label'] }}: {{ $d['overview'] }} resúmenes, {{ $d['orders'] }} pedidos, {{ $d['sections'] }} secciones{{ $d['down'] ? ', '.$d['down'].' sin conexión' : '' }}">
                                <div class="era-day-bar {{ M::heightClass($d['total'], $maxDaily) }}">
                                    @if ($d['total'] > 0)
                                        <span class="era-seg era-seg--c {{ M::heightClass($d['sections'], $d['total']) }}"></span>
                                        <span class="era-seg era-seg--b {{ M::heightClass($d['orders'], $d['total']) }}"></span>
                                        <span class="era-seg era-seg--a {{ M::heightClass($d['overview'], $d['total']) }}"></span>
                                    @endif
                                </div>
                                <span class="era-day-l">{{ $d['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-3">
                {{-- Secciones --}}
                <div class="col-12 col-xl-6">
                    <div class="card era-card h-100">
                        <div class="card-header border-bottom p-3">
                            <h5 class="mb-0 fw-bold">Secciones consultadas</h5>
                            <small class="text-muted">Qué pestañas del panel se abren y cómo responden.</small>
                        </div>
                        <div class="card-body">
                            @forelse ($sections as $s)
                                <div class="era-hbar">
                                    <div class="era-hbar-top">
                                        <span class="era-hbar-l">{{ $s['label'] }}</span>
                                        <span class="era-hbar-v">{{ $num($s['total']) }}</span>
                                    </div>
                                    <div class="era-track"><span class="era-fill {{ M::widthClass($s['total'], $maxSection) }}"></span></div>
                                    <div class="era-hbar-sub">
                                        {{ $num($s['ok']) }} con datos
                                        @if ($s['blocked']) · <span class="era-amber">{{ $num($s['blocked']) }} pendientes de permiso</span>@endif
                                        @if ($s['down']) · <span class="era-amber">{{ $num($s['down']) }} sin conexión</span>@endif
                                        @if ($s['other']) · {{ $num($s['other']) }} otros @endif
                                        · media {{ M::ms($s['avg_ms']) }}
                                    </div>
                                </div>
                            @empty
                                <div class="era-empty">Sin consultas de secciones en el periodo.</div>
                            @endforelse
                        </div>
                    </div>
                </div>

                {{-- Manager --}}
                <div class="col-12 col-xl-6">
                    <div class="card era-card h-100">
                        <div class="card-header border-bottom p-3">
                            <h5 class="mb-0 fw-bold">Tiempos del manager</h5>
                            <small class="text-muted">Llamadas a la API del manager hechas desde el panel (las respuestas en caché no llaman).</small>
                        </div>
                        <div class="card-body era-table-wrap">
                            @if ($resources === [])
                                <div class="era-empty">Sin llamadas al manager en el periodo.</div>
                            @else
                                <table class="table table-sm era-table mb-0">
                                    <thead>
                                        <tr><th>Recurso</th><th class="text-end">Llamadas</th><th class="text-end">p50</th><th class="text-end">p95</th><th class="text-end">Fallos</th></tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($resources as $r)
                                            <tr>
                                                <td>{{ $r['label'] }}</td>
                                                <td class="text-end">{{ $num($r['calls']) }}</td>
                                                <td class="text-end">{{ M::ms($r['p50']) }}</td>
                                                <td class="text-end">{{ M::ms($r['p95']) }}</td>
                                                <td class="text-end {{ $r['down'] + $r['errors'] > 0 ? 'era-amber' : '' }}">{{ $num($r['down'] + $r['errors']) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Agentes --}}
            <div class="card era-card mb-3">
                <div class="card-header border-bottom p-3">
                    <h5 class="mb-0 fw-bold">Uso por agente</h5>
                    <small class="text-muted">Aperturas del panel de Gestión por agente en el periodo.</small>
                </div>
                <div class="card-body era-table-wrap">
                    @if ($agentRows === [])
                        <div class="era-empty">Ningún agente ha abierto el panel de Gestión en el periodo.</div>
                    @else
                        <table class="table table-sm era-table mb-0">
                            <thead>
                                <tr>
                                    <th>Agente</th>
                                    <th class="era-col-bar">Uso</th>
                                    <th class="text-end">Resúmenes</th>
                                    <th class="text-end">Pedidos</th>
                                    <th class="text-end">Secciones</th>
                                    <th class="text-end">Bloqueados</th>
                                    <th class="text-end">Sin conexión</th>
                                    <th class="text-end">Último uso</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($agentRows as $a)
                                    <tr>
                                        <td>
                                            <a href="{{ route('manager.helpdesk.erp.admin.metrics.index', ['days' => $days, 'agent' => $a['user_id']]) }}">{{ $a['name'] }}</a>
                                        </td>
                                        <td class="era-col-bar"><div class="era-track"><span class="era-fill {{ M::widthClass($a['overview'] + $a['orders'] + $a['sections'], $maxAgent) }}"></span></div></td>
                                        <td class="text-end">{{ $num($a['overview']) }}</td>
                                        <td class="text-end">{{ $num($a['orders']) }}</td>
                                        <td class="text-end">{{ $num($a['sections']) }}</td>
                                        <td class="text-end">{{ $num($a['blocked']) }}</td>
                                        <td class="text-end {{ $a['down'] > 0 ? 'era-amber' : '' }}">{{ $num($a['down']) }}</td>
                                        <td class="text-end era-nowrap">{{ $a['last_at'] ? \Illuminate\Support\Carbon::parse($a['last_at'])->format('d/m H:i') : '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        @endif

        @if ($snapshot)
            @php $snapTotal = max(1, array_sum($snapshot)); @endphp
            <div class="card era-card">
                <div class="card-header border-bottom p-3">
                    <h5 class="mb-0 fw-bold">Vinculación de contactos hoy</h5>
                    <small class="text-muted">Contactos del helpdesk según el resultado de su búsqueda en Gestión (foto actual, no del periodo).</small>
                </div>
                <div class="card-body">
                    @foreach ([
                        'linked' => 'Vinculados',
                        'not_found' => 'No encontrados en Gestión',
                        'error' => 'Última búsqueda fallida',
                        'pending' => 'Sin buscar todavía',
                    ] as $key => $label)
                        <div class="era-hbar">
                            <div class="era-hbar-top">
                                <span class="era-hbar-l">{{ $label }}</span>
                                <span class="era-hbar-v">{{ $num($snapshot[$key]) }}</span>
                            </div>
                            <div class="era-track"><span class="era-fill {{ $key === 'error' ? 'era-fill--amber' : ($key === 'linked' ? '' : 'era-fill--grey') }} {{ M::widthClass($snapshot[$key], $snapTotal) }}"></span></div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
@endsection
