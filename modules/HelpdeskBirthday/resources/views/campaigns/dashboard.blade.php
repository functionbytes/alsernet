@extends('layouts.theme')
@section('title', 'Campaña de cumpleaños · '.$campaign->campaign_date->format('d/m/Y'))
@section('page_header')
    @include('core::components.card', ['title' => 'Campaña del '.$campaign->campaign_date->format('d/m/Y')])
@endsection

@push('css')
    <link rel="stylesheet" href="{{ asset('modules/helpdeskbirthday/css/birthday.css') }}?v={{ @filemtime(public_path('modules/helpdeskbirthday/css/birthday.css')) }}">
@endpush

@section('content')

<div class="bd-panel">

@include('helpdeskbirthday::campaigns._header')

@php
    $bonos = $stats['bonos'];
    $money = $stats['money'];
    $desc = $stats['reconciliation'];
    $target = max(0, $campaign->recipients_total - $campaign->skipped_count);
    $sentPercent = $target > 0 ? (int) round(($campaign->sent_count / $target) * 100) : 0;
    $failedPercent = $target > 0 ? (int) round(($campaign->failed_count / $target) * 100) : 0;
@endphp

{{-- Las cifras que contestan «¿esto ha funcionado?». Los bonos emitidos y el
     dinero juntos, porque uno sin el otro no dice nada: 574 bonos son buenos o
     malos según cuántos se gasten. --}}
<div class="row g-3 mb-3">
    @php
        $kpis = [
            ['label' => 'Bonos emitidos', 'value' => $bonos['issued'], 'hint' => $bonos['missing'] > 0 ? $bonos['missing'].' sin emitir' : 'todos emitidos'],
            ['label' => 'Correos enviados', 'value' => $campaign->sent_count, 'hint' => $campaign->failed_count > 0 ? $campaign->failed_count.' fallidos' : 'sin fallos'],
            ['label' => 'Bonos usados', 'value' => $bonos['used'], 'hint' => $bonos['use_rate'].'% de los emitidos'],
            ['label' => 'Facturado', 'value' => number_format($money['revenue'], 0, ',', '.').' €', 'hint' => $money['orders'].' pedidos con bono'],
            ['label' => 'Descontado', 'value' => number_format($money['discount'], 0, ',', '.').' €', 'hint' => 'lo que costó la campaña'],
            ['label' => 'Pedido medio', 'value' => number_format($money['avg_order'], 0, ',', '.').' €', 'hint' => 'de quien usó su bono'],
        ];
    @endphp

    @foreach($kpis as $kpi)
        <div class="col-6 col-lg-2">
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

{{-- El descuadre con gestión, arriba y no escondido: mientras un bono no se
     marque sigue vivo en el ERP y se puede volver a gastar. --}}
@if($desc['pending'] > 0)
    <div class="alert alert-warning d-flex align-items-center gap-3 flex-wrap">
        <div class="flex-grow-1">
            <strong>{{ $desc['pending'] }} bonos gastados sin registrar en gestión.</strong>
            La tienda descontó {{ number_format($desc['amount'], 2, ',', '.') }} € que el ERP nunca llegó a
            restar del bono: hasta que se marquen, esos bonos se pueden volver a usar.
        </div>
        <a href="{{ route('helpdeskbirthday.campaigns.reconciliation', $campaign) }}" class="btn btn-outline-secondary btn-sm text-nowrap">Ver el descuadre</a>
    </div>
@endif

<div class="row g-3 mb-3">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="fw-bold mb-1">Los bonos</h6>
                <p class="text-muted small mb-3">Gestión emite uno por cliente, con su propio código.</p>

                @if($bonos['issued'] > 0)
                    @php $usedPercent = (int) round(($bonos['used'] / $bonos['issued']) * 100); @endphp
                    <div class="progress mb-3">
                        <div class="progress-bar bd-w-{{ max($bonos['used'] > 0 ? 1 : 0, $usedPercent) }}" role="progressbar"
                             aria-label="Bonos usados" aria-valuenow="{{ $usedPercent }}"
                             aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                @endif

                <dl class="row small mb-0">
                    <dt class="col-7 fw-normal text-muted">Emitidos</dt>
                    <dd class="col-5 text-end">{{ $bonos['issued'] }}</dd>

                    <dt class="col-7 fw-normal text-muted">Usados</dt>
                    <dd class="col-5 text-end">{{ $bonos['used'] }}</dd>

                    <dt class="col-7 fw-normal text-muted">Sin usar todavía</dt>
                    <dd class="col-5 text-end">{{ $bonos['unused'] }}</dd>

                    @if($bonos['missing'] > 0)
                        <dt class="col-7 fw-normal text-muted">Sin emitir</dt>
                        <dd class="col-5 text-end fw-semibold">{{ $bonos['missing'] }}</dd>
                    @endif

                    <dt class="col-7 fw-normal text-muted">Importe</dt>
                    <dd class="col-5 text-end">{{ $bonos['amount'] !== null ? number_format((float) $bonos['amount'], 2, ',', '.').' €' : '—' }}</dd>

                    <dt class="col-7 fw-normal text-muted">Válidos hasta</dt>
                    <dd class="col-5 text-end">{{ $bonos['valid_to'] ? \Illuminate\Support\Carbon::parse($bonos['valid_to'])->format('d/m/Y') : '—' }}</dd>
                </dl>

                <p class="text-muted small mb-0 mt-3">
                    Un bono sin usar no está perdido: vale semanas, así que el día de la campaña
                    casi todos lo están.
                </p>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="fw-bold mb-1">El envío</h6>
                <p class="text-muted small mb-3">Los omitidos no cuentan: nunca entraron en la cola.</p>

                @if($target === 0)
                    <p class="text-muted small">Sin envíos: se omitió a todos los destinatarios.</p>
                @else
                    {{-- Enviados y fallidos apilados sobre el mismo objetivo:
                         sumarlos en una sola barra escondía los fallos dentro
                         de lo que parecía avance. --}}
                    <div class="progress mb-3">
                        <div class="progress-bar bd-w-{{ $sentPercent }}" role="progressbar"
                             aria-label="Correos enviados" aria-valuenow="{{ $sentPercent }}"
                             aria-valuemin="0" aria-valuemax="100"></div>
                        <div class="progress-bar progress-bar-striped bd-bar--failed bd-w-{{ $failedPercent }}" role="progressbar"
                             aria-label="Envíos fallidos" aria-valuenow="{{ $failedPercent }}"
                             aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                @endif

                <dl class="row small mb-0">
                    <dt class="col-7 fw-normal text-muted">Destinatarios</dt>
                    <dd class="col-5 text-end">{{ $campaign->recipients_total }}</dd>

                    <dt class="col-7 fw-normal text-muted">Enviados</dt>
                    <dd class="col-5 text-end">{{ $campaign->sent_count }}</dd>

                    <dt class="col-7 fw-normal text-muted">Fallidos</dt>
                    <dd class="col-5 text-end {{ $campaign->failed_count > 0 ? 'fw-semibold' : '' }}">{{ $campaign->failed_count }}</dd>

                    <dt class="col-7 fw-normal text-muted">Omitidos</dt>
                    <dd class="col-5 text-end">{{ $campaign->skipped_count }}</dd>

                    <dt class="col-7 fw-normal text-muted">Ventana</dt>
                    <dd class="col-5 text-end">{{ substr((string) $campaign->window_start, 0, 5) }}–{{ substr((string) $campaign->window_end, 0, 5) }}</dd>

                    <dt class="col-7 fw-normal text-muted">Un correo cada</dt>
                    <dd class="col-5 text-end">{{ $campaign->interval_seconds }} s</dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="fw-bold mb-1">Por qué quedó gente fuera</h6>
                <p class="text-muted small mb-3">La diferencia entre cumpleañeros y correos enviados.</p>

                @php
                    // Desglose tal como lo devolvió Gestión al preparar la campaña.
                    // Los motivos NO son excluyentes: alguien puede estar de baja
                    // y además no tener correo, así que no suman el total.
                    $audiencia = $campaign->audience_stats ?? [];
                    $descartes = array_filter([
                        'Dados de baja' => $audiencia['unsubscribed'] ?? null,
                        'Sin correo válido' => $audiencia['no_email'] ?? null,
                        'Sin LOPD aceptada' => $audiencia['no_lopd'] ?? null,
                        'No quieren publicidad' => $audiencia['no_commercial_optin'] ?? null,
                    ], static fn ($v) => $v !== null);
                @endphp

                @if($descartes !== [])
                    <dl class="row small mb-0">
                        <dt class="col-7 fw-normal text-muted">Cumplen años</dt>
                        <dd class="col-5 text-end fw-semibold">{{ number_format($audiencia['total'] ?? 0) }}</dd>

                        @foreach($descartes as $etiqueta => $cuantos)
                            <dt class="col-7 fw-normal text-muted ps-3">· {{ $etiqueta }}</dt>
                            <dd class="col-5 text-end">{{ number_format($cuantos) }}</dd>
                        @endforeach

                        <dt class="col-7 fw-normal border-top pt-2">Se les puede escribir</dt>
                        <dd class="col-5 text-end fw-semibold border-top pt-2">{{ number_format($audiencia['writable'] ?? 0) }}</dd>
                    </dl>
                @endif

                @if($skipReasons !== [])
                    <h6 class="fw-bold mb-2 {{ $descartes !== [] ? 'mt-3' : '' }}">Omitidos al enviar</h6>
                    @foreach($skipReasons as $reason => $count)
                        <div class="d-flex justify-content-between small mb-1">
                            <span class="text-muted">{{ __('helpdeskbirthday::messages.skip_reason.'.$reason) }}</span>
                            <span class="fw-semibold">{{ $count }}</span>
                        </div>
                    @endforeach
                @endif

                @if($descartes === [] && $skipReasons === [])
                    <p class="text-muted small mb-0">No quedó nadie fuera.</p>
                @endif

                @if($descartes !== [])
                    <p class="text-muted small mb-0 mt-3">
                        Los motivos no suman el total: una misma persona puede estar en varios.
                    </p>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="fw-bold mb-1">Cuándo se gastó el bono</h6>
                <p class="text-muted small mb-3">
                    Pedidos por día desde la campaña. Dice si la validez configurada tiene sentido.
                </p>

                @if($stats['timeline'] === [])
                    <p class="text-muted small mb-0">
                        Todavía no consta ningún canje de esta campaña.
                        @if($bonos['issued'] > 0)
                            Es lo normal el mismo día: el bono vale hasta
                            {{ $bonos['valid_to'] ? \Illuminate\Support\Carbon::parse($bonos['valid_to'])->format('d/m/Y') : 'semanas después' }}.
                        @endif
                    </p>
                @else
                    @php $peak = max(array_column($stats['timeline'], 'orders')) ?: 1; @endphp
                    <div class="bd-trend" role="img" aria-label="Pedidos con bono por día">
                        @foreach($stats['timeline'] as $point)
                            <div class="bd-trend__col" title="{{ $point['label'] }}: {{ $point['orders'] }} pedidos · {{ number_format($point['revenue'], 2, ',', '.') }} €">
                                <div class="bd-trend__track bd-h-100">
                                    <div class="bd-trend__fill bd-h-{{ max(1, (int) round(($point['orders'] / $peak) * 100)) }}"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="bd-trend__axis mt-1">
                        @foreach($stats['timeline'] as $point)
                            <div class="bd-trend__tick text-muted">{{ $point['label'] }}</div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="fw-bold mb-1">Comparado con el histórico</h6>
                <p class="text-muted small mb-3">
                    El cheque de cumpleaños que la tienda lleva repartiendo desde 2023.
                </p>

                @if($baseline['available'] ?? false)
                    <dl class="row small mb-0">
                        <dt class="col-7 fw-normal text-muted">Canjes registrados</dt>
                        <dd class="col-5 text-end">{{ number_format($baseline['orders']) }}</dd>

                        <dt class="col-7 fw-normal text-muted">Al mes, de media</dt>
                        <dd class="col-5 text-end">{{ number_format($baseline['per_month'], 1, ',', '.') }}</dd>

                        <dt class="col-7 fw-normal text-muted">Pedido medio</dt>
                        <dd class="col-5 text-end">{{ number_format($baseline['avg_order'], 2, ',', '.') }} €</dd>

                        <dt class="col-7 fw-normal text-muted">Facturado</dt>
                        <dd class="col-5 text-end">{{ number_format($baseline['revenue'], 0, ',', '.') }} €</dd>

                        <dt class="col-7 fw-normal text-muted">Descontado</dt>
                        <dd class="col-5 text-end">{{ number_format($baseline['discount'], 0, ',', '.') }} €</dd>
                    </dl>

                    <p class="text-muted small mb-0 mt-3">
                        Cada 1 € de bono trajo
                        {{ $baseline['discount'] > 0 ? number_format($baseline['revenue'] / $baseline['discount'], 1, ',', '.') : '—' }} €
                        de pedido.
                    </p>
                @else
                    <p class="text-muted small mb-0">
                        Todavía no hay histórico cargado. Se trae con
                        <code>helpdeskbirthday:sync-redemptions --all</code>.
                    </p>
                @endif

                @if($stats['last_sync'])
                    <p class="text-muted small mb-0 mt-3">
                        Canjes actualizados el
                        {{ \Illuminate\Support\Carbon::parse($stats['last_sync'])->timezone(config('helpdeskbirthday.timezone'))->format('d/m/Y H:i') }}.
                    </p>
                @endif
            </div>
        </div>
    </div>
</div>

@include('helpdeskbirthday::campaigns._cancel-modal')

</div>

@endsection
