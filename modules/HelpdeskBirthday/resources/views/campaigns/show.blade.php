@extends('layouts.theme')
@section('title', 'Campaña de cumpleaños · '.$campaign->campaign_date->format('d/m/Y'))
@section('page_header')
    @include('core::components.card', ['title' => 'Campaña del '.$campaign->campaign_date->format('d/m/Y')])
@endsection

@push('css')
    <link rel="stylesheet" href="{{ asset('modules/helpdeskbirthday/css/birthday.css') }}?v={{ @filemtime(public_path('modules/helpdeskbirthday/css/birthday.css')) }}">
@endpush

@section('content')

<div class="d-flex align-items-center gap-3 mb-4 flex-wrap">
    <h1 class="h4 mb-0 fw-bold">
        <i class="fas fa-gift text-primary me-2"></i>Campaña del {{ $campaign->campaign_date->format('d/m/Y') }}
    </h1>
    <span class="badge {{ $campaign->status === 'completed' ? 'bg-primary-subtle text-primary' : 'bg-secondary-subtle text-secondary' }}">
        {{ __('helpdeskbirthday::messages.status.'.$campaign->status) }}
    </span>

    <div class="ms-auto d-flex gap-2">
        <a href="{{ route('helpdeskbirthday.campaigns.preview', $campaign) }}" class="btn btn-outline-secondary btn-sm" target="_blank" rel="noopener">Previsualizar correo</a>

        @if($redemption['available'])
            <a href="{{ route('helpdeskbirthday.campaigns.redemptions', $campaign) }}" class="btn btn-outline-secondary btn-sm">
                Canjes ({{ $redemption['redemptions'] }})
            </a>
        @endif

        @can('helpdeskbirthday.manage')
            @if($campaign->canBePaused())
                <form method="POST" action="{{ route('helpdeskbirthday.campaigns.pause', $campaign) }}">
                    @csrf
                    <button type="submit" class="btn btn-outline-secondary btn-sm">Pausar</button>
                </form>
            @endif

            @if($campaign->canBeResumed())
                <form method="POST" action="{{ route('helpdeskbirthday.campaigns.resume', $campaign) }}">
                    @csrf
                    <button type="submit" class="btn btn-primary btn-sm">Reanudar</button>
                </form>
            @endif

            @if($campaign->failed_count > 0)
                <form method="POST" action="{{ route('helpdeskbirthday.campaigns.retry-failed', $campaign) }}">
                    @csrf
                    <button type="submit" class="btn btn-outline-secondary btn-sm">
                        Reintentar los {{ $campaign->failed_count }} fallidos
                    </button>
                </form>
            @endif

            {{-- Sin bono no se envía nada, así que esta es la acción que
                 desatasca la campaña cuando Gestión falló al emitirlos. --}}
            @if($withoutCoupon > 0)
                <form method="POST" action="{{ route('helpdeskbirthday.campaigns.retry-bonos', $campaign) }}">
                    @csrf
                    <button type="submit" class="btn btn-outline-secondary btn-sm">
                        Generar los {{ $withoutCoupon }} bonos que faltan
                    </button>
                </form>
            @endif

            @if($campaign->canBeCancelled())
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#bd-cancel-modal">Cancelar</button>
            @endif
        @endcan
    </div>
</div>

@include('core::components.alerts')

@if($campaign->error_message)
    <div class="alert alert-warning">{{ $campaign->error_message }}</div>
@endif

<div class="row g-3 mb-3">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                {{-- No hay cupón del día: Gestión emite un bono para cada
                     cliente, así que lo que se enseña es cuántos hay, cuántos
                     faltan y con qué plantilla sale el correo. --}}
                <h6 class="fw-bold mb-1">Bonos emitidos</h6>
                <p class="text-muted small mb-3">
                    Gestión emite un bono por cliente, con su propio código.
                </p>

                <p class="bd-kpi-value fw-bold mb-2">{{ $coupons['issued'] }}</p>

                <dl class="row small mb-0">
                    <dt class="col-7 fw-normal text-muted">Sin bono</dt>
                    <dd class="col-5 text-end">{{ $withoutCoupon }}</dd>

                    <dt class="col-7 fw-normal text-muted">Importe</dt>
                    <dd class="col-5 text-end">{{ $coupons['amount'] !== null ? number_format((float) $coupons['amount'], 2, ',', '.').' €' : '—' }}</dd>

                    <dt class="col-7 fw-normal text-muted">Compra mínima</dt>
                    <dd class="col-5 text-end">{{ $coupons['min_purchase'] !== null ? number_format((float) $coupons['min_purchase'], 2, ',', '.').' €' : '—' }}</dd>

                    <dt class="col-7 fw-normal text-muted">Válidos hasta</dt>
                    <dd class="col-5 text-end">{{ $coupons['valid_to'] ? \Illuminate\Support\Carbon::parse($coupons['valid_to'])->format('d/m/Y') : '—' }}</dd>

                    <dt class="col-7 fw-normal text-muted">Plantilla del correo</dt>
                    <dd class="col-5 text-end"><code class="small">{{ $campaign->template_key ?: '—' }}</code></dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="fw-bold mb-1">Ritmo de envío</h6>
                <p class="text-muted small mb-3">
                    Cómo se reparten los correos a lo largo del día.
                </p>

                <dl class="row small mb-0">
                    <dt class="col-7 fw-normal text-muted">Ventana</dt>
                    <dd class="col-5 text-end">{{ substr((string) $campaign->window_start, 0, 5) }}–{{ substr((string) $campaign->window_end, 0, 5) }}</dd>

                    <dt class="col-7 fw-normal text-muted">Un correo cada</dt>
                    <dd class="col-5 text-end">{{ $campaign->interval_seconds }} s</dd>

                    <dt class="col-7 fw-normal text-muted">Tope configurado</dt>
                    <dd class="col-5 text-end">{{ $campaign->throttle_per_hour }}/h</dd>

                    <dt class="col-7 fw-normal text-muted">Arrancó</dt>
                    <dd class="col-5 text-end">{{ $campaign->started_at?->timezone(config('helpdeskbirthday.timezone'))->format('H:i') ?: '—' }}</dd>

                    <dt class="col-7 fw-normal text-muted">Terminó</dt>
                    <dd class="col-5 text-end">{{ $campaign->finished_at?->timezone(config('helpdeskbirthday.timezone'))->format('H:i') ?: '—' }}</dd>
                </dl>
            </div>
        </div>
    </div>

    @php
        // Desglose de la audiencia del día, tal como lo devolvió Gestión al
        // preparar la campaña (ver BirthdayAudienceStatsService). Los motivos
        // NO son excluyentes: alguien puede estar de baja y además no tener
        // correo, así que no suman el total — cada uno cuenta su condición.
        $audiencia = $campaign->audience_stats ?? [];
        $descartes = [
            'Dados de baja' => $audiencia['unsubscribed'] ?? null,
            'Sin correo válido' => $audiencia['no_email'] ?? null,
            'Sin LOPD aceptada' => $audiencia['no_lopd'] ?? null,
            'No quieren publicidad' => $audiencia['no_commercial_optin'] ?? null,
        ];
        $descartes = array_filter($descartes, static fn ($v) => $v !== null);
    @endphp

    @if($descartes !== [])
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h6 class="fw-bold mb-1">Audiencia del día</h6>
                    <p class="text-muted small mb-3">
                        Quién cumplía años y por qué motivo quedó fuera cada uno.
                    </p>

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

                    <p class="text-muted small mb-0 mt-3">
                        Los motivos no suman el total: una misma persona puede estar en varios.
                    </p>
                </div>
            </div>
        </div>
    @endif

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="fw-bold mb-1">Avance</h6>
                <p class="text-muted small mb-3">
                    Los omitidos no cuentan: nunca entraron en la cola de envío.
                </p>

                <div class="progress mb-3" role="progressbar" aria-label="Progreso de la campaña"
                     aria-valuenow="{{ $campaign->progressPercent() }}" aria-valuemin="0" aria-valuemax="100">
                    <div class="progress-bar bd-progress-{{ (int) (round($campaign->progressPercent() / 10) * 10) }}"></div>
                </div>

                <dl class="row small mb-0">
                    <dt class="col-7 fw-normal text-muted">Destinatarios</dt>
                    <dd class="col-5 text-end">{{ $campaign->recipients_total }}</dd>

                    <dt class="col-7 fw-normal text-muted">Enviados</dt>
                    <dd class="col-5 text-end">{{ $campaign->sent_count }}</dd>

                    <dt class="col-7 fw-normal text-muted">Fallidos</dt>
                    <dd class="col-5 text-end">{{ $campaign->failed_count }}</dd>

                    <dt class="col-7 fw-normal text-muted">Omitidos</dt>
                    <dd class="col-5 text-end">{{ $campaign->skipped_count }}</dd>

                    @if($redemption['available'])
                        <dt class="col-7 fw-normal text-muted">Cupones usados</dt>
                        <dd class="col-5 text-end">{{ $redemption['attributed'] }} ({{ $redemption['rate'] }}%)</dd>

                        <dt class="col-7 fw-normal text-muted">Facturado</dt>
                        <dd class="col-5 text-end">{{ number_format($redemption['revenue'], 2, ',', '.') }} €</dd>
                    @endif
                </dl>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="d-flex align-items-center gap-3 mb-3 flex-wrap">
            <h6 class="fw-bold mb-0">Destinatarios</h6>

            <form method="GET" class="ms-auto d-flex gap-2">
                <input type="search" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Buscar por email" aria-label="Buscar por email">
                <select name="status" class="form-select form-select-sm" aria-label="Filtrar por estado">
                    <option value="">Todos los estados</option>
                    @foreach($statuses as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>
                            {{ __('helpdeskbirthday::messages.recipient_status.'.$status) }}
                        </option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-outline-secondary btn-sm text-nowrap">Filtrar</button>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Hora prevista</th>
                        <th>Email</th>
                        <th>Nombre</th>
                        <th>Nacimiento</th>
                        <th>Bono</th>
                        <th>Estado</th>
                        <th>Enviado</th>
                        <th>Entregado</th>
                        <th>Abierto</th>
                        <th>Clic</th>
                        @if($redeemers !== [])
                            <th>Canjeado</th>
                        @endif
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recipients as $recipient)
                        <tr>
                            <td class="bd-timeline-slot">{{ $recipient->scheduled_at?->timezone(config('helpdeskbirthday.timezone'))->format('H:i:s') ?: '—' }}</td>
                            <td>{{ $recipient->email }}</td>
                            <td>{{ $recipient->name ?: '—' }}</td>
                            <td>{{ $recipient->birth_date?->format('d/m/Y') ?: '—' }}</td>

                            {{-- El bono de ESTA persona: Gestión emite uno por
                                 cliente, así que aquí está lo que de verdad
                                 recibió, y el motivo si se quedó sin él. --}}
                            <td>
                                @if($code = $recipient->publicCode())
                                    <code class="small">{{ $code }}</code>
                                    @if($recipient->coupon_amount)
                                        <small class="text-muted d-block">
                                            {{ number_format((float) $recipient->coupon_amount, 2, ',', '.') }} €
                                            @if($recipient->coupon_valid_to)
                                                · hasta {{ $recipient->coupon_valid_to->format('d/m/Y') }}
                                            @endif
                                        </small>
                                    @endif
                                @elseif($recipient->coupon_error)
                                    <span class="text-muted">Sin bono</span>
                                    <small class="text-muted d-block" title="{{ $recipient->coupon_error }}">
                                        {{ \Illuminate\Support\Str::limit($recipient->coupon_error, 40) }}
                                    </small>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>

                            <td>
                                <span class="badge {{ $recipient->status === 'sent' ? 'bg-primary-subtle text-primary' : 'bg-secondary-subtle text-secondary' }}">
                                    {{ __('helpdeskbirthday::messages.recipient_status.'.$recipient->status) }}
                                </span>
                                @if($recipient->skip_reason)
                                    <small class="text-muted d-block">{{ __('helpdeskbirthday::messages.skip_reason.'.$recipient->skip_reason) }}</small>
                                @endif
                            </td>
                            <td>{{ $recipient->sent_at?->timezone(config('helpdeskbirthday.timezone'))->format('H:i:s') ?: '—' }}</td>

                            {{-- Estado real del correo, consultado al log vía
                                 EmailDeliveryLookupService (no lo sabe la campaña). --}}
                            @php $d = $delivery[$recipient->email] ?? null; @endphp

                            <td>
                                @if($d && $d['delivered'] > 0)
                                    <span class="badge bg-primary-subtle text-primary">Sí</span>
                                @elseif($d && $d['bounced'] > 0)
                                    <span class="badge bg-secondary-subtle text-secondary">Rebotado</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($d && $d['was_opened'])
                                    <span class="badge bg-primary-subtle text-primary">Sí</span>
                                @elseif($d && $d['was_sent'])
                                    <span class="text-muted">No</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($d && $d['was_clicked'])
                                    <span class="badge bg-primary-subtle text-primary">Sí</span>
                                @elseif($d && $d['was_sent'])
                                    <span class="text-muted">No</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>

                            @if($redeemers !== [])
                                @php $redeemed = $redeemers[$recipient->email] ?? null; @endphp
                                <td>
                                    @if($redeemed)
                                        <span class="badge bg-primary-subtle text-primary"
                                              title="Pedido #{{ $redeemed['order_id'] }}">Sí</span>
                                    @elseif($d && $d['was_sent'])
                                        <span class="text-muted">No</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            @endif

                            <td class="text-end">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-link text-body" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Acciones del destinatario">
                                        <i class="fas fa-ellipsis-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li>
                                            <button type="button" class="dropdown-item bd-view-email"
                                                    data-email-url="{{ route('helpdeskbirthday.campaigns.recipient-email', [$campaign, $recipient]) }}"
                                                    data-email-to="{{ $recipient->email }}">
                                                Ver el correo
                                            </button>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" target="_blank" rel="noopener"
                                               href="{{ route('helpdeskbirthday.campaigns.recipient-email', [$campaign, $recipient]) }}">
                                                Abrirlo en otra pestaña
                                            </a>
                                        </li>

                                        @if($recipient->email_log_id && Route::has('helpdeskemailactivity.index'))
                                            <li>
                                                <a class="dropdown-item" target="_blank" rel="noopener"
                                                   href="{{ route('helpdeskemailactivity.index', ['search' => $recipient->email, 'module' => 'HelpdeskBirthday']) }}">
                                                    Ver la trazabilidad en el log
                                                </a>
                                            </li>
                                        @endif

                                        @can('helpdeskbirthday.manage')
                                            <li><hr class="dropdown-divider"></li>

                                            @if($recipient->status === 'failed')
                                                <li>
                                                    <form method="POST" action="{{ route('helpdeskbirthday.campaigns.recipient-retry', [$campaign, $recipient]) }}">
                                                        @csrf
                                                        <button type="submit" class="dropdown-item">Reintentar el envío</button>
                                                    </form>
                                                </li>
                                            @endif

                                            <li>
                                                <form method="POST" action="{{ route('helpdeskbirthday.campaigns.recipient-unsubscribe', [$campaign, $recipient]) }}">
                                                    @csrf
                                                    <button type="submit" class="dropdown-item">Dar de baja de cumpleaños</button>
                                                </form>
                                            </li>
                                        @endcan
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $redeemers !== [] ? 12 : 11 }}" class="text-center text-muted py-4">
                                @if($campaign->recipients_total === 0 && $campaign->status === \Modules\HelpdeskBirthday\Models\BirthdayCampaign::STATUS_FAILED)
                                    {{-- Distinguir "el filtro no encuentra nada" de "esta campaña nunca
                                         llegó a tener destinatarios": la preparación aborta antes de
                                         consultar la audiencia, así que la tabla está vacía por el fallo,
                                         no por lo que se haya escrito en el buscador. --}}
                                    Esta campaña se detuvo antes de reunir a nadie, así que no hay destinatarios que mostrar.
                                    @if($campaign->error_message)
                                        <span class="d-block mt-1">Motivo: {{ $campaign->error_message }}</span>
                                    @endif
                                @elseif($campaign->recipients_total === 0)
                                    Todavía no hay destinatarios en esta campaña.
                                @else
                                    No hay destinatarios con ese filtro.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $recipients->links() }}
        </div>
    </div>
</div>

{{-- Visor del correo enviado. Un único modal reutilizado por todas las filas:
     uno por destinatario serían 50 iframes en el DOM. --}}
<div class="modal fade" id="bd-email-modal" tabindex="-1" aria-labelledby="bd-email-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bd-email-title">Correo enviado</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-2" id="bd-email-to"></p>
                {{-- iframe y no innerHTML: el CSS del correo no debe filtrarse al panel. --}}
                <iframe class="bd-email-frame" id="bd-email-frame" title="Contenido del correo" src="about:blank"></iframe>
            </div>
            <div class="modal-footer">
                <a href="#" class="btn btn-primary w-100 mb-2" id="bd-email-open" target="_blank" rel="noopener">Abrirlo en otra pestaña</a>
                <button type="button" class="btn btn-outline-secondary w-100" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('click', function (event) {
        const trigger = event.target.closest('.bd-view-email');

        if (!trigger) {
            return;
        }

        const url = trigger.dataset.emailUrl;

        document.getElementById('bd-email-frame').src = url;
        document.getElementById('bd-email-open').href = url;
        document.getElementById('bd-email-to').textContent = 'Para: ' + trigger.dataset.emailTo;

        bootstrap.Modal.getOrCreateInstance(document.getElementById('bd-email-modal')).show();
    });

    // Soltar el iframe al cerrar: si no, el correo sigue cargado de fondo.
    document.getElementById('bd-email-modal').addEventListener('hidden.bs.modal', function () {
        document.getElementById('bd-email-frame').src = 'about:blank';
    });
</script>
@endpush

@can('helpdeskbirthday.manage')
    @if($campaign->canBeCancelled())
        <div class="modal fade" id="bd-cancel-modal" tabindex="-1" aria-labelledby="bd-cancel-title" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="bd-cancel-title">Cancelar la campaña</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-0">
                            Se descartarán los {{ $campaign->pendingCount() }} envíos que quedan pendientes.
                            Los correos ya enviados no se pueden retirar.
                        </p>
                    </div>
                    <div class="modal-footer">
                        <form method="POST" action="{{ route('helpdeskbirthday.campaigns.cancel', $campaign) }}" class="w-100">
                            @csrf
                            <button type="submit" class="btn btn-primary w-100 mb-2">Cancelar la campaña</button>
                            <button type="button" class="btn btn-outline-secondary w-100" data-bs-dismiss="modal">Volver</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endcan

@endsection
