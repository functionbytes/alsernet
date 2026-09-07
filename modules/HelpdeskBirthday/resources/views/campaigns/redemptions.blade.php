@extends('layouts.theme')
@section('title', 'Canjes · Campaña del '.$campaign->campaign_date->format('d/m/Y'))
@section('page_header')
    @include('core::components.card', ['title' => 'Canjes de los bonos'])
@endsection

@push('css')
    <link rel="stylesheet" href="{{ asset('modules/helpdeskbirthday/css/birthday.css') }}?v={{ @filemtime(public_path('modules/helpdeskbirthday/css/birthday.css')) }}">
@endpush

@section('content')

{{-- .bd-panel lleva la paleta y los estados del módulo. Sin él, .bd-badge--live
     y .bd-coupon-code no aplican: están definidos bajo .bd-panel. --}}
<div class="bd-panel">

@include('helpdeskbirthday::campaigns._header')

@if(! $available)
    <div class="alert alert-warning">
        No hay base de datos de PrestaShop configurada (<code>HELPDESK_PS_DB</code>),
        así que no se puede saber quién usó su bono.
    </div>
@else

    <div class="row g-3 mb-3">
        @php
            $conGestion = collect($rows)->where('erp_ok', true)->count();
            $sinGestion = collect($rows)->filter(fn ($r) => $r['erp_response'] === null)->count();
            $kpis = [
                ['label' => 'Pedidos con bono', 'value' => count($rows), 'hint' => $summary['attributed'].' de destinatarios'],
                ['label' => 'Facturado', 'value' => number_format($summary['revenue'], 2, ',', '.').' €', 'hint' => number_format($summary['discount'], 2, ',', '.').' € descontados'],
                ['label' => 'Marcados en gestión', 'value' => $conGestion, 'hint' => $sinGestion > 0 ? $sinGestion.' sin registro' : 'todos registrados'],
                ['label' => 'Conversión', 'value' => $summary['rate'].'%', 'hint' => 'sobre '.$campaign->sent_count.' correos enviados'],
            ];
        @endphp

        @foreach($kpis as $kpi)
            <div class="col-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small text-uppercase bd-kpi-label">{{ $kpi['label'] }}</div>
                        <div class="fw-bold bd-kpi-value">{{ $kpi['value'] }}</div>
                        <div class="text-muted small">{{ $kpi['hint'] }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h6 class="fw-bold mb-1">Pedidos</h6>
            <p class="text-muted small mb-3">
                «Atribuido» significa que el pedido es de alguien a quien le mandamos el correo.
                Cada cliente recibe su propio bono, así que un pedido sin atribuir suele ser de
                alguien que compró con el código que le reenviaron.
            </p>

            <p class="text-muted small bd-table-hint">Desliza la tabla para ver el resto de columnas.</p>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Cliente</th>
                            <th>Bono</th>
                            <th>Pedido</th>
                            <th>Estado</th>
                            <th class="text-end">Total</th>
                            <th class="text-end">Descuento</th>
                            <th>Atribuido</th>
                            <th>Gestión</th>
                            <th class="text-end">Ver</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            <tr>
                                <td class="bd-timeline-slot">
                                    {{ $row['ordered_at'] ? \Illuminate\Support\Carbon::parse($row['ordered_at'])->format('d/m/Y H:i') : '—' }}
                                </td>
                                <td>
                                    {{ $row['email'] }}
                                    @if($row['name'])
                                        <small class="text-muted d-block">{{ $row['name'] }}</small>
                                    @endif
                                </td>
                                <td><code class="small">{{ $row['coupon_code'] ?: '—' }}</code></td>
                                <td>
                                    <code>{{ $row['order_reference'] ?: '#'.$row['order_id'] }}</code>
                                    <small class="text-muted d-block">PS #{{ $row['order_id'] }}</small>
                                </td>
                                <td>
                                    <span class="badge {{ $row['order_valid'] ? 'bd-badge bd-badge--live' : 'bd-badge bd-badge--done' }}">
                                        {{ $row['order_state'] ?: '—' }}
                                    </span>
                                </td>
                                <td class="text-end text-nowrap">{{ number_format($row['order_total'], 2, ',', '.') }} €</td>
                                <td class="text-end text-nowrap">{{ number_format($row['discount'], 2, ',', '.') }} €</td>
                                <td>
                                    @if($row['attributed'])
                                        <span class="badge bd-badge bd-badge--live">Sí</span>
                                    @else
                                        <span class="text-muted">No</span>
                                    @endif
                                </td>
                                <td>
                                    @if($row['erp_ok'])
                                        <span class="badge bd-badge bd-badge--live">Marcado</span>
                                        @if($row['erp_marked_at'])
                                            <small class="text-muted d-block">{{ \Illuminate\Support\Carbon::parse($row['erp_marked_at'])->format('d/m H:i') }}</small>
                                        @endif
                                    @elseif($row['erp_response'] !== null)
                                        {{-- Se intentó marcar y gestión contestó otra cosa: es lo que hay
                                             que revisar a mano, un cupón consumido sin descontar en el ERP. --}}
                                        <span class="badge bd-badge bd-badge--done">Revisar</span>
                                        <small class="text-muted d-block">{{ \Illuminate\Support\Str::limit($row['erp_response'], 40) }}</small>
                                    @else
                                        <span class="text-muted">Sin registro</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-link text-body" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Acciones del pedido">
                                            <i class="fas fa-ellipsis-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            @if($psAdminUrl = config('helpdeskbirthday.ps_admin_url'))
                                                <li>
                                                    <a class="dropdown-item" target="_blank" rel="noopener"
                                                       href="{{ rtrim($psAdminUrl, '/') }}/index.php?controller=AdminOrders&id_order={{ $row['order_id'] }}&vieworder">
                                                        Ver el pedido en PrestaShop
                                                    </a>
                                                </li>
                                            @endif

                                            @if($row['erp_customer_id'])
                                                <li>
                                                    <a class="dropdown-item" target="_blank" rel="noopener"
                                                       href="{{ url('/panel/helpdesk/contacts?search='.urlencode($row['email'])) }}">
                                                        Ficha del cliente (ERP {{ $row['erp_customer_id'] }})
                                                    </a>
                                                </li>
                                            @endif

                                            @if(Route::has('helpdeskemailactivity.index'))
                                                <li>
                                                    <a class="dropdown-item" target="_blank" rel="noopener"
                                                       href="{{ route('helpdeskemailactivity.index', ['search' => $row['email'], 'module' => 'HelpdeskBirthday']) }}">
                                                        Correo que se le envió
                                                    </a>
                                                </li>
                                            @endif

                                            {{-- El caso caro: el cliente se llevó el descuento en la
                                                 tienda pero el bono no se descontó en gestión, así que
                                                 sigue vivo y se puede volver a gastar. ESTO ESCRIBE EN
                                                 EL ERP, por eso solo aparece cuando hace falta. --}}
                                            @if(! $row['erp_ok'] && $row['coupon_code'] && $canManage)
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <form method="POST" action="{{ route('helpdeskbirthday.campaigns.mark-coupon-used', $campaign) }}">
                                                        @csrf
                                                        <input type="hidden" name="coupon_code" value="{{ $row['coupon_code'] }}">
                                                        <input type="hidden" name="sale_amount" value="{{ $row['order_total'] }}">
                                                        <button type="submit" class="dropdown-item">
                                                            Marcar el bono en gestión
                                                        </button>
                                                    </form>
                                                </li>
                                            @endif
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted py-4">
                                    Todavía nadie ha usado su bono.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endif

@include('helpdeskbirthday::campaigns._cancel-modal')

</div>

@endsection
