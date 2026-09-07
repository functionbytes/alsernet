@extends('layouts.theme')
@section('title', 'Campañas de cumpleaños')
@section('page_header')
    @include('core::components.card', ['title' => 'Campañas de cumpleaños'])
@endsection

@push('css')
    <link rel="stylesheet" href="{{ asset('modules/helpdeskbirthday/css/birthday.css') }}?v={{ @filemtime(public_path('modules/helpdeskbirthday/css/birthday.css')) }}">
@endpush

@section('content')

{{-- .bd-panel lleva la paleta del módulo: el tema resuelve --bs-primary a negro,
     así que el verde de marca no puede heredarse de Bootstrap. --}}
<div class="bd-panel">

{{-- El título ya lo pinta la cabecera de página con su breadcrumb; aquí solo
     va la explicación y la acción, sin repetirlo. --}}
<div class="d-flex align-items-start justify-content-between gap-3 mb-4 flex-wrap">
    <p class="text-muted small mb-0 bd-intro">
        Cada día se crea una campaña con los clientes que cumplen años, gestión emite el bono de
        cada uno y los correos se reparten dentro de la ventana horaria configurada.
    </p>

    <form method="POST" action="{{ route('helpdeskbirthday.campaigns.prepare') }}" class="d-flex align-items-end gap-2">
        @csrf
        <div>
            <label for="bd-prepare-date" class="form-label text-muted small mb-1">Día a preparar</label>
            <input type="date" id="bd-prepare-date" name="date" class="form-control form-control-sm" value="{{ now()->toDateString() }}">
        </div>
        <button type="submit" class="btn btn-primary btn-sm text-nowrap">Preparar campaña</button>
    </form>
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
                    · {{ $today['sent'] }}/{{ $today['target'] }} enviados
                </div>
            </div>

            {{-- Enviados y fallidos apilados sobre el mismo objetivo. Sumarlos en
                 una sola barra hacía que 139 de 577 enviados se dibujaran como un
                 60% de avance, con los 209 fallos escondidos dentro. --}}
            <div class="flex-grow-1 bd-today-progress">
                <div class="progress">
                    <div class="progress-bar bd-w-{{ $today['sent_percent'] }}" role="progressbar"
                         aria-label="Correos enviados" aria-valuenow="{{ $today['sent_percent'] }}"
                         aria-valuemin="0" aria-valuemax="100"></div>
                    <div class="progress-bar progress-bar-striped bd-bar--failed bd-w-{{ $today['failed_percent'] }}" role="progressbar"
                         aria-label="Envíos fallidos" aria-valuenow="{{ $today['failed_percent'] }}"
                         aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <div class="text-muted small mt-1">
                    {{ $today['sent'] }} enviados
                    @if($today['failed'] > 0)
                        · <span class="fw-semibold">{{ $today['failed'] }} fallidos</span>
                    @endif
                    · {{ $today['pending'] }} pendientes
                    @if($today['skipped'] > 0)
                        · {{ $today['skipped'] }} omitidos
                    @endif
                </div>
            </div>

            <div class="text-end">
                <div class="text-muted small">
                    @if($today['pending'] > 0)
                        @if($today['next_at'])
                            Siguiente a las {{ \Illuminate\Support\Carbon::parse($today['next_at'])->timezone(config('helpdeskbirthday.timezone'))->format('H:i') }}
                        @else
                            Sin hora asignada
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
    <span class="text-muted small" id="bd-range-label">Estadísticas de los últimos</span>
    <div class="btn-group btn-group-sm" role="group" aria-labelledby="bd-range-label">
        @foreach([7, 30, 90] as $option)
            <a href="{{ route('helpdeskbirthday.campaigns.index', ['days' => $option]) }}"
               class="btn {{ $days === $option ? 'btn-primary' : 'btn-outline-secondary' }}"
               @if($days === $option) aria-current="true" @endif>{{ $option }} días</a>
        @endforeach
    </div>
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
                    <div class="text-muted text-uppercase bd-kpi-label">{{ $kpi['label'] }}</div>
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
            @php
                // Un paso con gente nunca se dibuja vacío: 2 aperturas de 1153 es
                // 0,17% y redondeando a entero desaparecía, así que "casi nadie"
                // y "nadie" se veían igual.
                $width = $step['value'] > 0 ? max(1, (int) round($step['percent'])) : 0;
            @endphp
            <div class="bd-funnel__row mb-2">
                <div class="bd-funnel__label text-muted small">{{ $step['label'] }}</div>
                <div class="bd-funnel__bar">
                    <div class="progress" role="progressbar" aria-label="{{ $step['label'] }}"
                         aria-valuenow="{{ $step['percent'] }}" aria-valuemin="0" aria-valuemax="100">
                        <div class="progress-bar bd-w-{{ $width }}"></div>
                    </div>
                </div>
                <div class="bd-funnel__value fw-semibold">{{ $step['value'] }}</div>
                <div class="bd-funnel__drop text-muted small">
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
                <p class="text-muted small mb-3">
                    Cumpleañeros de cada campaña (la pista) y cuántos correos salieron (el relleno).
                </p>

                @php
                    $peak = max(array_column($overview['trend'], 'total')) ?: 1;
                    // Una etiqueta cada N columnas: a 90 días no caben 90 fechas.
                    // Se cuentan desde el final para que el último día, que es el
                    // que se mira, nunca se quede sin fecha.
                    $trendCount = count($overview['trend']);
                    $tickEvery = max(1, (int) ceil($trendCount / 8));
                @endphp

                @if($totals['count'] === 0)
                    <p class="text-muted small mb-0">Todavía no hay campañas en este periodo.</p>
                @else
                    <div class="bd-trend" role="img"
                         aria-label="Cumpleañeros y correos enviados por día durante los últimos {{ $days }} días">
                        @foreach($overview['trend'] as $point)
                            <div class="bd-trend__col {{ $point['has_campaign'] ? '' : 'bd-trend__col--empty' }}"
                                 title="{{ $point['label'] }}: {{ $point['has_campaign'] ? $point['sent'].'/'.$point['total'] : 'sin campaña' }}">
                                <div class="bd-trend__track bd-h-{{ $point['total'] > 0 ? max(1, (int) round(($point['total'] / $peak) * 100)) : 0 }}">
                                    <div class="bd-trend__fill bd-h-{{ $point['total'] > 0 ? (int) round(($point['sent'] / $point['total']) * 100) : 0 }}"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="bd-trend__axis mt-1">
                        @foreach($overview['trend'] as $point)
                            <div class="bd-trend__tick text-muted">
                                {{ ($trendCount - 1 - $loop->index) % $tickEvery === 0 ? $point['label'] : '' }}
                            </div>
                        @endforeach
                    </div>

                    <div class="d-flex gap-3 mt-3 small text-muted">
                        <span><span class="bd-trend__legend-swatch bd-trend__legend-swatch--sent me-1"></span>Enviados</span>
                        <span><span class="bd-trend__legend-swatch bd-trend__legend-swatch--total me-1"></span>Cumpleañeros</span>
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
        <h6 class="fw-bold mb-1">Historial de campañas</h6>
        <p class="text-muted small mb-3">Una fila por día, de la más reciente a la más antigua.</p>
        <p class="text-muted small bd-table-hint">Desliza la tabla para ver el resto de columnas.</p>

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
                        @php
                            $target = max(0, $campaign->recipients_total - $campaign->skipped_count);
                            $sentPercent = $target > 0 ? (int) round(($campaign->sent_count / $target) * 100) : 0;
                            $failedPercent = $target > 0 ? (int) round(($campaign->failed_count / $target) * 100) : 0;
                        @endphp
                        <tr>
                            <td class="fw-semibold">{{ $campaign->campaign_date->format('d/m/Y') }}</td>
                            <td>
                                {{-- Colores propios: bg-*-subtle + text-* del tema dejaban
                                     "Enviando" en verde sobre verde, a 1.98:1. --}}
                                <span class="badge bd-badge {{ $campaign->status === 'completed' ? 'bd-badge--done' : 'bd-badge--live' }}">
                                    {{ __('helpdeskbirthday::messages.status.'.$campaign->status) }}
                                </span>
                            </td>
                            {{-- Bonos emitidos, no un código: cada cliente
                                 recibe el suyo de gestión. --}}
                            <td class="text-end">{{ $campaign->coupons_count }}</td>
                            <td class="text-end">{{ $campaign->recipients_total }}</td>
                            <td class="text-end">{{ $campaign->sent_count }}</td>
                            <td class="text-end {{ $campaign->failed_count > 0 ? 'fw-semibold' : '' }}">{{ $campaign->failed_count }}</td>
                            <td class="text-end">{{ $campaign->skipped_count }}</td>
                            <td class="w-25">
                                @if($target === 0)
                                    <small class="text-muted">Sin envíos: se omitió a todos</small>
                                @else
                                {{-- Ancho por clase y no por style="": el repo no admite estilos inline.
                                     En pasos de 1, no de 10: redondear a la decena convertía un 96%
                                     en una barra llena. --}}
                                <div class="progress">
                                    <div class="progress-bar bd-w-{{ $sentPercent }}" role="progressbar"
                                         aria-label="Correos enviados" aria-valuenow="{{ $sentPercent }}"
                                         aria-valuemin="0" aria-valuemax="100"></div>
                                    <div class="progress-bar progress-bar-striped bd-bar--failed bd-w-{{ $failedPercent }}" role="progressbar"
                                         aria-label="Envíos fallidos" aria-valuenow="{{ $failedPercent }}"
                                         aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                <small class="text-muted">
                                    {{ $sentPercent }}% enviado
                                    @if($campaign->failed_count > 0)
                                        · {{ $failedPercent }}% fallido
                                    @endif
                                </small>
                                @endif
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

</div>

@endsection
