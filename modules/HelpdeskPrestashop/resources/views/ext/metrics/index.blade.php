@extends('layouts.theme')

@php
    use Modules\HelpdeskPrestashop\Services\Ext\MetricsChatService;
    $money = fn ($v) => MetricsChatService::money($v);
    $agentName = $agentId ? ($agents[$agentId] ?? 'Agente #'.$agentId) : null;
    $exportParams = array_filter(['days' => $days, 'agent' => $agentId]);
@endphp

@section('title', 'Métricas del chat · PrestaShop')

@section('page_header')
    @include('core::components.card', ['title' => 'Métricas del chat · PrestaShop', 'subtitle' => 'Lo que los agentes han hecho contra la tienda desde el chat, según la auditoría de acciones'])
@endsection

@include('helpdeskprestashop::ext.metrics._assets')

@section('content')
    @include('helpdeskprestashop::ext.opsmap._flash')

    <div class="card mb-3">
        <div class="card-body psc-metrics-body">
            <form method="GET" action="{{ route('manager.helpdesk.ps.ext.metrics.index') }}" class="psc-metrics-filters" id="psMetricsFilters">
                <div class="psc-chips" role="group" aria-label="Periodo">
                    @foreach ($periods as $period)
                        <a href="{{ route('manager.helpdesk.ps.ext.metrics.index', array_filter(['days' => $period, 'agent' => $agentId])) }}"
                           class="psc-chip {{ $days === $period ? 'is-on' : '' }}"
                           @if ($days === $period) aria-current="true" @endif>{{ $period }} días</a>
                    @endforeach
                </div>
                <input type="hidden" name="days" value="{{ $days }}">
                <select name="agent" class="form-select psc-metrics-select" aria-label="Agente" data-metrics-autosubmit>
                    <option value="">Todos los agentes</option>
                    @foreach ($agents as $id => $name)
                        <option value="{{ $id }}" @selected($agentId === (int) $id)>{{ $name }}</option>
                    @endforeach
                </select>
                <noscript><button type="submit" class="psc-btn psc-btn--outline psc-metrics-inline-btn">Aplicar</button></noscript>
                <span class="psc-metrics-caption">
                    Desde el {{ $since->format('d/m/Y') }} · {{ $total }} {{ $total === 1 ? 'acción' : 'acciones' }} contra la tienda{{ $agentName ? ' de '.$agentName : '' }}
                </span>
            </form>
        </div>
    </div>

    {{-- KPIs --}}
    <div class="card mb-3">
        <div class="card-header border-bottom p-3">
            <h5 class="mb-0 fw-bold">Resumen del periodo</h5>
            <small class="text-muted">Cada número es una acción aceptada por la tienda y registrada en la auditoría.</small>
        </div>
        <div class="card-body">
            <div class="psc-kpis psc-metrics-kpis">
                @php $k = $kpis['vouchers']; @endphp
                <div class="psc-kpi">
                    <span class="l">{{ $k['label'] }}</span>
                    <span class="n">{{ $k['count'] }}</span>
                    <span class="d {{ $k['amount'] > 0 ? 'is-good' : '' }}">{{ $money($k['amount']) }}</span>
                    @if ($details['vouchers_percent'] > 0)
                        <span class="psc-metrics-kpi-note">{{ $details['vouchers_percent'] }} de porcentaje, sin importe</span>
                    @endif
                </div>

                @php $k = $kpis['refunds']; @endphp
                <div class="psc-kpi">
                    <span class="l">{{ $k['label'] }}</span>
                    <span class="n">{{ $k['count'] }}</span>
                    <span class="d {{ $k['amount'] > 0 ? 'is-good' : '' }}">{{ $money($k['amount']) }}</span>
                </div>

                @php $k = $kpis['cancelled']; @endphp
                <div class="psc-kpi">
                    <span class="l">{{ $k['label'] }}</span>
                    <span class="n">{{ $k['count'] }}</span>
                    <span class="d">Sin importe registrado</span>
                </div>

                @php $k = $kpis['returns']; @endphp
                <div class="psc-kpi">
                    <span class="l">{{ $k['label'] }}</span>
                    <span class="n">{{ $k['count'] }}</span>
                    <span class="d">{{ $details['returns_completed'] }} completadas · {{ $details['returns_denied'] }} denegadas</span>
                </div>

                @php $k = $kpis['carts_converted']; @endphp
                <div class="psc-kpi">
                    <span class="l">{{ $k['label'] }}</span>
                    <span class="n">{{ $k['count'] }}</span>
                    <span class="d {{ $k['amount'] > 0 ? 'is-good' : '' }}">{{ $money($k['amount']) }} en pedidos</span>
                </div>

                @php $k = $kpis['carts_emptied']; @endphp
                <div class="psc-kpi">
                    <span class="l">{{ $k['label'] }}</span>
                    <span class="n">{{ $k['count'] }}</span>
                    <span class="d">Sin importe registrado</span>
                </div>

                @php $k = $kpis['addresses']; @endphp
                <div class="psc-kpi">
                    <span class="l">{{ $k['label'] }}</span>
                    <span class="n">{{ $k['count'] }}</span>
                    <span class="d">{{ $details['addresses_order'] }} pedido · {{ $details['addresses_cart'] }} carrito · {{ $details['addresses_book'] }} ficha</span>
                </div>

                @php $k = $kpis['stock_alerts']; @endphp
                <div class="psc-kpi">
                    <span class="l">{{ $k['label'] }}</span>
                    <span class="n">{{ $k['count'] }}</span>
                    <span class="d">Sin importe registrado</span>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        {{-- Evolución semanal --}}
        <div class="col-12 col-xl-5">
            <div class="card h-100">
                <div class="card-header border-bottom p-3">
                    <h5 class="mb-0 fw-bold">Evolución semanal</h5>
                    <small class="text-muted">Acciones por semana (de lunes a domingo).</small>
                </div>
                <div class="card-body psc-metrics-body">
                    <div class="psc-chips" role="tablist" aria-label="Serie">
                        @foreach ($series as $key => $serie)
                            <button type="button" class="psc-chip {{ $loop->first ? 'is-on' : '' }}" role="tab"
                                    aria-selected="{{ $loop->first ? 'true' : 'false' }}"
                                    data-metrics-series="{{ $key }}">{{ $key === 'all' ? 'Todas' : $metricDefs[$key]['short'] }}</button>
                        @endforeach
                    </div>

                    @foreach ($series as $key => $serie)
                        <div class="psc-metrics-serie {{ $loop->first ? '' : 'psc-metrics-hidden' }}" data-metrics-panel="{{ $key }}" role="tabpanel">
                            <div class="psc-metrics-serie-hd">
                                <span>{{ $serie['label'] }}</span>
                                <span class="psc-metrics-mono">{{ $serie['total'] }} en total</span>
                            </div>
                            @if ($serie['total'] === 0)
                                <div class="psc-state psc-state--compact">
                                    <i class="fas fa-chart-column" aria-hidden="true"></i>
                                    <div class="s">Ninguna en este periodo.</div>
                                </div>
                            @else
                                <div class="psc-bars psc-metrics-bars">
                                    @foreach ($serie['bars'] as $bar)
                                        <div class="col {{ $bar['tone'] === 'is-top' ? 'is-top' : '' }}" title="{{ $bar['range'] }}: {{ $bar['n'] }}">
                                            <span class="psc-metrics-val">{{ $bar['n'] ?: '' }}</span>
                                            <span class="psc-metrics-track"><span class="bar {{ $bar['height'] }} {{ $bar['tone'] }}"></span></span>
                                            <span class="lbl">{{ $bar['label'] }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Ranking por agente --}}
        <div class="col-12 col-xl-7">
            <div class="card h-100">
                <div class="card-header border-bottom p-3">
                    <h5 class="mb-0 fw-bold">Ranking por agente</h5>
                    <small class="text-muted">Ordenado por número total de acciones contra la tienda.</small>
                </div>
                <div class="card-body psc-metrics-body">
                    @if (count($ranking) === 0)
                        <div class="psc-state">
                            <i class="fas fa-clipboard-check" aria-hidden="true"></i>
                            <div class="t">Sin acciones en este periodo</div>
                            <div class="s">Cuando un agente emita un vale, un reembolso o cambie un pedido desde el chat, aparecerá aquí.</div>
                        </div>
                    @else
                        <div class="psc-metrics-scroll">
                            <div class="psc-logtable psc-metrics-rank">
                                <div class="psc-logtable-hd">
                                    <span class="psc-metrics-c-pos">#</span>
                                    <span class="psc-metrics-c-name">Agente</span>
                                    @foreach ($metricDefs as $key => $meta)
                                        <span class="psc-metrics-c-num" title="{{ $meta['label'] }}">{{ $meta['short'] }}</span>
                                    @endforeach
                                    <span class="psc-metrics-c-num" title="Notas, seguimiento, ficha del cliente, cupones de carrito…">Otras</span>
                                    <span class="psc-metrics-c-num">Total</span>
                                </div>
                                @foreach ($ranking as $i => $agent)
                                    <div class="psc-logrow {{ $agent['id'] === null ? 'psc-metrics-system' : '' }}">
                                        <span class="psc-metrics-c-pos">{{ $i + 1 }}</span>
                                        <span class="psc-metrics-c-name">
                                            @if ($agent['id'])
                                                <a href="{{ route('manager.helpdesk.ps.ext.metrics.index', ['days' => $days, 'agent' => $agent['id']]) }}">{{ $agent['name'] }}</a>
                                            @else
                                                {{ $agent['name'] }}
                                            @endif
                                        </span>
                                        @foreach ($metricDefs as $key => $meta)
                                            @php $m = $agent['metrics'][$key]; @endphp
                                            <span class="psc-metrics-c-num {{ $m['count'] === 0 ? 'is-zero' : '' }}">
                                                {{ $m['count'] }}
                                                @if ($meta['money'] && $m['count'] > 0)
                                                    <small>{{ $money($m['amount']) }}</small>
                                                @endif
                                            </span>
                                        @endforeach
                                        <span class="psc-metrics-c-num {{ $agent['other'] === 0 ? 'is-zero' : '' }}">{{ $agent['other'] }}</span>
                                        <span class="psc-metrics-c-num psc-metrics-total">{{ $agent['total'] }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
                <div class="card-footer psc-metrics-foot">
                    <a href="{{ route('manager.helpdesk.ps.ext.metrics.export', $exportParams + ['kind' => 'agents']) }}" class="psc-btn psc-btn--outline">Exportar resumen por agente (CSV)</a>
                    <a href="{{ route('manager.helpdesk.ps.ext.metrics.export', $exportParams + ['kind' => 'detail']) }}" class="psc-btn psc-btn--outline">Exportar detalle de acciones (CSV)</a>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header border-bottom">
            <h6 class="mb-0 fw-bold">De dónde sale cada número</h6>
        </div>
        <div class="card-body psc-metrics-help">
            <p>Todo se lee de la auditoría de acciones (log de actividad): solo cuenta lo que la tienda aceptó. No se consulta PrestaShop ni se estima nada.</p>
            <ul>
                <li><b>Vales:</b> vales de compensación creados y vales duplicados; el importe es el que se guardó al crearlos. Un vale de porcentaje se cuenta sin importe.</li>
                <li><b>Reembolsos:</b> reembolsos parciales emitidos; importe con IVA devuelto por la tienda.</li>
                <li><b>Pedidos anulados:</b> cambios de estado del pedido a un estado de cancelación. No guardan importe.</li>
                <li><b>Devoluciones resueltas:</b> devoluciones pasadas a «completada» o «denegada». No guardan importe.</li>
                <li><b>Carritos convertidos:</b> el importe es el total del pedido creado. Los carritos vaciados no guardan importe.</li>
                <li><b>Cambios de dirección:</b> del pedido, del carrito o de una dirección de la ficha. No guardan importe.</li>
                <li class="mb-0"><b>Avisos de reposición:</b> clientes apuntados al aviso de stock de un producto. No guardan importe.</li>
            </ul>
            <p class="mb-0">«Otras» reúne el resto de acciones contra la tienda (notas, seguimiento, correos, productos y cupones del carrito, ficha del cliente…). Las semanas y fechas van en hora UTC.</p>
        </div>
    </div>
@endsection
