@extends('layouts.theme')
@section('title', 'Campañas de cumpleaños')
@section('page_header')
    @include('core::components.card', ['title' => 'Campañas de cumpleaños'])
@endsection

@push('css')
    <link rel="stylesheet" href="{{ asset('modules/helpdeskbirthday/css/birthday.css') }}?v={{ @filemtime(public_path('modules/helpdeskbirthday/css/birthday.css')) }}">
@endpush

@section('content')

<div class="d-flex align-items-center gap-3 mb-4 flex-wrap">
    <h1 class="h4 mb-0 fw-bold">
        <i class="fas fa-gift text-primary me-2"></i>Campañas de cumpleaños
    </h1>
    <p class="text-muted small mb-0 w-100 order-3 mt-1">
        Cada día se crea una campaña con los clientes que cumplen años, gestión emite el bono de
        cada uno y los correos se reparten dentro de la ventana horaria configurada.
    </p>
    <div class="ms-auto order-2">
        <form method="POST" action="{{ route('helpdeskbirthday.campaigns.prepare') }}" class="d-flex gap-2">
            @csrf
            <input type="date" name="date" class="form-control form-control-sm" value="{{ now()->toDateString() }}" aria-label="Día a preparar">
            <button type="submit" class="btn btn-primary btn-sm text-nowrap">Preparar campaña</button>
        </form>
    </div>
</div>

@include('core::components.alerts')

@php
    $delivery = $overview['delivery'];
    $totals = $overview['campaigns'];
    $today = $overview['today'];
    $redemption = $overview['redemption'];
@endphp

{{-- El envío está atascado: los correos se encolan y no sale ninguno. --}}
{{-- Que hoy no exista campaña es el fallo más silencioso de todos: no hay nada
     atascado porque no hay nada, y el día pasa sin felicitar a nadie. --}}
@if($overview['health']['missing_today'])
    <div class="alert alert-warning">
        <strong>Hoy no hay campaña.</strong>
        La preparación de las {{ config('helpdeskbirthday.prepare_at') }} no llegó a crearla,
        o falló. Se reintenta cada hora hasta el final de la ventana de envío; si no
        aparece, revisa el registro y prepárala a mano.
    </div>
@endif

@if(! $overview['health']['healthy'] && ($overview['health']['stuck_sending'] > 0 || $overview['health']['overdue_pending'] > 0))
    <div class="alert alert-warning">
        <strong>El envío parece atascado.</strong>
        @if($overview['health']['stuck_sending'] > 0)
            Hay {{ $overview['health']['stuck_sending'] }} correos reservados sin procesar
        @endif
        @if($overview['health']['overdue_pending'] > 0)
            {{ $overview['health']['stuck_sending'] > 0 ? 'y' : 'Hay' }}
            {{ $overview['health']['overdue_pending'] }} pendientes que ya deberían haber salido
        @endif
        desde hace más de 15 minutos. Comprueba que el worker de la cola
        <code>{{ $overview['health']['queue'] }}</code> está corriendo.
    </div>
@endif

{{-- Campaña de hoy: lo primero que se quiere saber al abrir el panel. --}}
@if($today)
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body d-flex align-items-center gap-4 flex-wrap">
            <div>
                <div class="text-muted small">Campaña de hoy</div>
                <div class="fw-bold">
                    {{ __('helpdeskbirthday::messages.status.'.$today['status']) }}
                    · {{ $today['sent'] }}/{{ $today['total'] }} enviados
                </div>
            </div>

            <div class="flex-grow-1 bd-today-progress">
                <div class="progress" role="progressbar" aria-label="Progreso de la campaña de hoy"
                     aria-valuenow="{{ $today['progress'] }}" aria-valuemin="0" aria-valuemax="100">
                    <div class="progress-bar bd-progress-{{ (int) (round($today['progress'] / 10) * 10) }}"></div>
                </div>
            </div>

            <div class="text-end">
                <div class="text-muted small">
                    @if($today['pending'] > 0)
                        {{ $today['pending'] }} pendientes
                        @if($today['next_at'])
                            · siguiente a las {{ \Illuminate\Support\Carbon::parse($today['next_at'])->timezone(config('helpdeskbirthday.timezone'))->format('H:i') }}
                        @endif
                    @else
                        Sin envíos pendientes
                    @endif
                </div>
                <a href="{{ route('helpdeskbirthday.campaigns.show', $today['id']) }}" class="small">Ver la campaña</a>
            </div>
        </div>
    </div>
@endif

<div class="d-flex align-items-center gap-2 mb-3">
    <span class="text-muted small">Estadísticas de los últimos</span>
    @foreach([7, 30, 90] as $option)
        <a href="{{ route('helpdeskbirthday.campaigns.index', ['days' => $option]) }}"
           class="btn btn-sm {{ $days === $option ? 'btn-primary' : 'btn-outline-secondary' }}">{{ $option }} días</a>
    @endforeach
</div>

<div class="row g-3 mb-3">
    @php
        $kpis = [
            ['label' => 'Campañas', 'value' => $totals['count'], 'hint' => $totals['failed_campaigns'] > 0 ? $totals['failed_campaigns'].' abortadas' : $totals['completed'].' completadas'],
            ['label' => 'Cumpleañeros', 'value' => $totals['recipients'], 'hint' => $totals['skipped'].' omitidos'],
            ['label' => 'Correos enviados', 'value' => $delivery['sent'], 'hint' => $totals['failed'] > 0 ? $totals['failed'].' fallidos' : 'sin fallos'],
            ['label' => 'Tasa de apertura', 'value' => $delivery['open_rate'].'%', 'hint' => $delivery['opened'].' abiertos'],
            ['label' => 'Tasa de clic', 'value' => $delivery['click_rate'].'%', 'hint' => $delivery['clicked'].' con clic'],
            ['label' => 'Rebotes', 'value' => $delivery['bounced'], 'hint' => $delivery['bounce_rate'].'% del total'],
        ];

        // El canje solo se puede leer si la BD de PrestaShop está configurada;
        // sin ella se oculta la tarjeta en vez de enseñar un 0 que parecería
        // un mal resultado cuando en realidad es "no lo sabemos".
        if ($redemption['available']) {
            $kpis[] = [
                'label' => 'Bonos usados',
                'value' => $redemption['attributed'],
                'hint' => $redemption['rate'].'% de los enviados',
            ];
            $kpis[] = [
                'label' => 'Facturado',
                'value' => number_format($redemption['revenue'], 0, ',', '.').' €',
                'hint' => $redemption['redemptions'].' pedidos con bono',
            ];
        }
    @endphp

    @foreach($kpis as $kpi)
        <div class="col-6 col-lg-{{ count($kpis) > 6 ? 3 : 2 }}">
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

{{-- Embudo: dónde se pierde la gente entre el cumpleaños y la compra. --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <h6 class="fw-bold mb-1">Del cumpleaños a la compra</h6>
        <p class="text-muted small mb-3">Cuánta gente queda en cada paso y cuánta se pierde por el camino.</p>

        @foreach($funnel as $step)
            <div class="d-flex align-items-center gap-3 mb-2">
                <div class="bd-funnel__label text-muted small">{{ $step['label'] }}</div>
                <div class="flex-grow-1">
                    <div class="progress" role="progressbar" aria-label="{{ $step['label'] }}"
                         aria-valuenow="{{ $step['percent'] }}" aria-valuemin="0" aria-valuemax="100">
                        <div class="progress-bar bd-progress-{{ (int) (round($step['percent'] / 10) * 10) }}"></div>
                    </div>
                </div>
                <div class="bd-funnel__value fw-semibold text-end">{{ $step['value'] }}</div>
                <div class="bd-funnel__drop text-muted small text-end">
                    @if($step['drop'] !== null)
                        −{{ $step['drop'] }}%
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="fw-bold mb-1">Envíos por día</h6>
                <p class="text-muted small mb-3">Cumpleañeros de cada campaña y cuántos correos salieron.</p>

                @if($overview['trend'] === [])
                    <p class="text-muted small mb-0">Todavía no hay campañas en este periodo.</p>
                @else
                    @php $peak = max(array_column($overview['trend'], 'total')) ?: 1; @endphp
                    <div class="bd-trend">
                        @foreach($overview['trend'] as $point)
                            <div class="bd-trend__col" title="{{ $point['label'] }}: {{ $point['sent'] }}/{{ $point['total'] }}">
                                <div class="bd-trend__bar bd-progress-{{ (int) (round(($point['total'] / $peak) * 100 / 10) * 10) }}"></div>
                                <small class="text-muted">{{ $point['label'] }}</small>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="fw-bold mb-1">Por qué se omitió gente</h6>
                <p class="text-muted small mb-3">Explica la diferencia entre cumpleañeros y correos enviados.</p>

                @forelse($skipReasons as $reason => $count)
                    <div class="d-flex justify-content-between small mb-1">
                        <span class="text-muted">{{ __('helpdeskbirthday::messages.skip_reason.'.$reason) }}</span>
                        <span class="fw-semibold">{{ $count }}</span>
                    </div>
                @empty
                    <p class="text-muted small mb-0">No se omitió a nadie.</p>
                @endforelse

                @if($upcoming !== [])
                    <hr>
                    <h6 class="fw-bold mb-2 mt-3">Próximos días</h6>
                    @foreach($upcoming as $next)
                        <div class="d-flex justify-content-between small mb-1">
                            <span class="text-muted">{{ \Illuminate\Support\Carbon::parse($next['date'])->format('d/m') }}</span>
                            <span class="fw-semibold">{{ $next['recipients'] }}</span>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Día</th>
                        <th>Estado</th>
                        <th class="text-end">Bonos</th>
                        <th class="text-end">Destinatarios</th>
                        <th class="text-end">Enviados</th>
                        <th class="text-end">Fallidos</th>
                        <th class="text-end">Omitidos</th>
                        <th>Progreso</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($campaigns as $campaign)
                        <tr>
                            <td class="fw-semibold">{{ $campaign->campaign_date->format('d/m/Y') }}</td>
                            <td>
                                <span class="badge {{ $campaign->status === 'completed' ? 'bg-primary-subtle text-primary' : 'bg-secondary-subtle text-secondary' }}">
                                    {{ __('helpdeskbirthday::messages.status.'.$campaign->status) }}
                                </span>
                            </td>
                            {{-- Bonos emitidos, no un código: cada cliente
                                 recibe el suyo de gestión. --}}
                            <td class="text-end">{{ $campaign->coupons_count }}</td>
                            <td class="text-end">{{ $campaign->recipients_total }}</td>
                            <td class="text-end">{{ $campaign->sent_count }}</td>
                            <td class="text-end">{{ $campaign->failed_count }}</td>
                            <td class="text-end">{{ $campaign->skipped_count }}</td>
                            <td class="w-25">
                                <div class="progress" role="progressbar" aria-label="Progreso de la campaña"
                                     aria-valuenow="{{ $campaign->progressPercent() }}" aria-valuemin="0" aria-valuemax="100">
                                    {{-- Ancho por clase y no por style="": el repo no admite estilos inline, así que se redondea a la decena. --}}
                                    <div class="progress-bar bd-progress-{{ (int) (round($campaign->progressPercent() / 10) * 10) }}"></div>
                                </div>
                                <small class="text-muted">{{ $campaign->progressPercent() }}%</small>
                            </td>
                            <td class="text-end">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-link text-body" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Acciones">
                                        <i class="fas fa-ellipsis-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li>
                                            <a class="dropdown-item" href="{{ route('helpdeskbirthday.campaigns.show', $campaign) }}">Ver detalle</a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('helpdeskbirthday.campaigns.preview', $campaign) }}" target="_blank" rel="noopener">Previsualizar correo</a>
                                        </li>
                                        @if($redemption['available'] && $campaign->coupons_count > 0)
                                            <li>
                                                <a class="dropdown-item" href="{{ route('helpdeskbirthday.campaigns.redemptions', $campaign) }}">Ver los canjes</a>
                                            </li>
                                        @endif
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">
                                Todavía no hay ninguna campaña. Se crean solas cada día, o puedes preparar la de hoy con el botón de arriba.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $campaigns->links() }}
        </div>
    </div>
</div>

@endsection
