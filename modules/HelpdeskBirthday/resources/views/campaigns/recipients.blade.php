@extends('layouts.theme')
@section('title', 'Destinatarios · Campaña del '.$campaign->campaign_date->format('d/m/Y'))
@section('page_header')
    @include('core::components.card', ['title' => 'Campaña del '.$campaign->campaign_date->format('d/m/Y')])
@endsection

@push('css')
    <link rel="stylesheet" href="{{ asset('modules/helpdeskbirthday/css/birthday.css') }}?v={{ @filemtime(public_path('modules/helpdeskbirthday/css/birthday.css')) }}">
@endpush

@section('content')

<div class="bd-panel">

@include('helpdeskbirthday::campaigns._header')

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="d-flex align-items-start gap-3 mb-3 flex-wrap">
            <div>
                <h6 class="fw-bold mb-1">Destinatarios</h6>
                <p class="text-muted small mb-0">
                    Cada cumpleañero con su hora de envío, su bono y qué hizo con el correo.
                </p>
            </div>

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

        {{-- Con un filtro puesto, la tabla enseña 1 fila de 577 sin decir por
             qué. El aviso da el número real y la salida en un clic: vaciar el
             buscador a mano era la única forma de volver. --}}
        @if(request()->filled('search') || request()->filled('status'))
            <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
                <span class="text-muted small">
                    {{ $recipients->total() }}
                    {{ $recipients->total() === 1 ? 'destinatario' : 'destinatarios' }}
                    @if(request()->filled('search'))
                        con «{{ request('search') }}»
                    @endif
                    @if(request()->filled('status'))
                        en estado «{{ __('helpdeskbirthday::messages.recipient_status.'.request('status')) }}»
                    @endif
                    de {{ $campaign->recipients_total }} en total
                </span>
                <a href="{{ route('helpdeskbirthday.campaigns.show', $campaign) }}" class="btn btn-outline-secondary btn-sm">Quitar el filtro</a>
            </div>
        @endif

        <p class="text-muted small bd-table-hint">Desliza la tabla para ver el resto de columnas.</p>

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
                        @if($redeemers->isNotEmpty())
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
                                <span class="badge {{ $recipient->status === 'sent' ? 'bd-badge bd-badge--live' : 'bd-badge bd-badge--done' }}">
                                    {{ __('helpdeskbirthday::messages.recipient_status.'.$recipient->status) }}
                                </span>
                                @if($recipient->skip_reason)
                                    <small class="text-muted d-block">{{ __('helpdeskbirthday::messages.skip_reason.'.$recipient->skip_reason) }}</small>
                                @endif

                                {{-- Por qué falló, igual que la columna del bono
                                     enseña por qué no se emitió: sin esto, 209
                                     fallos eran 209 filas mudas. --}}
                                @if($recipient->error_message)
                                    <small class="text-muted d-block" title="{{ $recipient->error_message }}">
                                        {{ \Illuminate\Support\Str::limit($recipient->error_message, 40) }}
                                    </small>
                                @endif
                            </td>
                            <td>{{ $recipient->sent_at?->timezone(config('helpdeskbirthday.timezone'))->format('H:i:s') ?: '—' }}</td>

                            {{-- Estado real del correo, consultado al log vía
                                 EmailDeliveryLookupService (no lo sabe la campaña). --}}
                            @php $d = $delivery[$recipient->email] ?? null; @endphp

                            <td>
                                @if($d && $d['delivered'] > 0)
                                    <span class="badge bd-badge bd-badge--live">Sí</span>
                                @elseif($d && $d['bounced'] > 0)
                                    <span class="badge bd-badge bd-badge--done">Rebotado</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($d && $d['was_opened'])
                                    <span class="badge bd-badge bd-badge--live">Sí</span>
                                @elseif($d && $d['was_sent'])
                                    <span class="text-muted">No</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($d && $d['was_clicked'])
                                    <span class="badge bd-badge bd-badge--live">Sí</span>
                                @elseif($d && $d['was_sent'])
                                    <span class="text-muted">No</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>

                            @if($redeemers->isNotEmpty())
                                @php $redeemed = $redeemers->get(mb_strtolower($recipient->email)); @endphp
                                <td>
                                    @if($redeemed)
                                        <span class="badge bd-badge bd-badge--live"
                                              title="Pedido {{ $redeemed->ps_order_reference ?: '#'.$redeemed->ps_order_id }} · {{ number_format((float) $redeemed->order_total, 2, ',', '.') }} €">Sí</span>
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
                            <td colspan="{{ $redeemers->isNotEmpty() ? 12 : 11 }}" class="text-center text-muted py-4">
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

@include('helpdeskbirthday::campaigns._cancel-modal')

</div>

@endsection
