@extends('layouts.theme')
@section('title', 'Canjes · Campaña del '.$campaign->campaign_date->format('d/m/Y'))
@section('page_header')
    @include('core::components.card', ['title' => 'Canjes del cupón'])
@endsection

@push('css')
    <link rel="stylesheet" href="{{ asset('modules/helpdeskbirthday/css/birthday.css') }}?v={{ @filemtime(public_path('modules/helpdeskbirthday/css/birthday.css')) }}">
@endpush

@section('content')

<div class="d-flex align-items-center gap-3 mb-4 flex-wrap">
    <h1 class="h4 mb-0 fw-bold">
        <i class="fas fa-receipt text-primary me-2"></i>Canjes del cupón
        <span class="bd-coupon-code ms-2">{{ $campaign->coupon_code ?: '—' }}</span>
    </h1>
    <p class="text-muted small mb-0 w-100 order-3 mt-1">
        Pedidos de PrestaShop que usaron este cupón y qué contestó gestión al marcar el bono.
        Campaña del {{ $campaign->campaign_date->format('d/m/Y') }}.
    </p>
    <div class="ms-auto order-2">
        <a href="{{ route('helpdeskbirthday.campaigns.show', $campaign) }}" class="btn btn-outline-secondary btn-sm">Volver a la campaña</a>
    </div>
</div>

@include('core::components.alerts')

@if(! $available)
    <div class="alert alert-warning">
        No hay base de datos de PrestaShop configurada (<code>HELPDESK_PS_DB</code>),
        así que no se puede saber quién usó el cupón.
    </div>
@else

    <div class="row g-3 mb-3">
        @php
            $conGestion = collect($rows)->where('erp_ok', true)->count();
            $sinGestion = collect($rows)->filter(fn ($r) => $r['erp_response'] === null)->count();
            $kpis = [
                ['label' => 'Pedidos con el cupón', 'value' => count($rows), 'hint' => $summary['attributed'].' de destinatarios'],
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
                Al ser el cupón el mismo para todos, un pedido sin atribuir puede ser de alguien
                que recibió el código reenviado.
            </p>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Cliente</th>
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
                                <td>
                                    <code>{{ $row['order_reference'] ?: '#'.$row['order_id'] }}</code>
                                    <small class="text-muted d-block">PS #{{ $row['order_id'] }}</small>
                                </td>
                                <td>
                                    <span class="badge {{ $row['order_valid'] ? 'bg-primary-subtle text-primary' : 'bg-secondary-subtle text-secondary' }}">
                                        {{ $row['order_state'] ?: '—' }}
                                    </span>
                                </td>
                                <td class="text-end text-nowrap">{{ number_format($row['order_total'], 2, ',', '.') }} €</td>
                                <td class="text-end text-nowrap">{{ number_format($row['discount'], 2, ',', '.') }} €</td>
                                <td>
                                    @if($row['attributed'])
                                        <span class="badge bg-primary-subtle text-primary">Sí</span>
                                    @else
                                        <span class="text-muted">No</span>
                                    @endif
                                </td>
                                <td>
                                    @if($row['erp_ok'])
                                        <span class="badge bg-primary-subtle text-primary">Marcado</span>
                                        @if($row['erp_marked_at'])
                                            <small class="text-muted d-block">{{ \Illuminate\Support\Carbon::parse($row['erp_marked_at'])->format('d/m H:i') }}</small>
                                        @endif
                                    @elseif($row['erp_response'] !== null)
                                        {{-- Se intentó marcar y gestión contestó otra cosa: es lo que hay
                                             que revisar a mano, un cupón consumido sin descontar en el ERP. --}}
                                        <span class="badge bg-secondary-subtle text-secondary">Revisar</span>
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
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">
                                    Todavía nadie ha usado este cupón.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endif

@endsection
