{{--
    Cabecera compartida por las cuatro pantallas de una campaña.

    El día y el breadcrumb ya los pinta la cabecera de página, así que aquí van
    el estado, la explicación y las acciones — una sola destacada, la que toca
    según el estado, y el resto en el menú: siete botones en fila no dejaban ver
    cuál era la importante.
--}}
<div class="d-flex align-items-start justify-content-between gap-3 mb-3 flex-wrap">
    <div>
        {{-- Colores propios: bg-*-subtle + text-* del tema dejaban "Enviando"
             en verde sobre verde, a 1.98:1. --}}
        <span class="badge bd-badge {{ $campaign->isActive() || $campaign->isPaused() ? 'bd-badge--live' : 'bd-badge--done' }} mb-2">
            {{ __('helpdeskbirthday::messages.status.'.$campaign->status) }}
        </span>
        <p class="text-muted small mb-0 bd-intro">
            Los {{ $campaign->recipients_total }} clientes que cumplían años el
            {{ $campaign->campaign_date->format('d/m/Y') }}, con el bono que gestión emitió para cada uno
            y qué pasó con su correo.
        </p>
    </div>

    <div class="d-flex align-items-center gap-2">
        @can('helpdeskbirthday.manage')
            @if($campaign->canBeResumed())
                <form method="POST" action="{{ route('helpdeskbirthday.campaigns.resume', $campaign) }}">
                    @csrf
                    <button type="submit" class="btn btn-primary btn-sm text-nowrap">Reanudar</button>
                </form>
            @elseif(($withoutCoupon ?? 0) > 0)
                {{-- Sin bono no se envía nada, así que cuando faltan es LA
                     acción que desatasca la campaña. --}}
                <form method="POST" action="{{ route('helpdeskbirthday.campaigns.retry-bonos', $campaign) }}">
                    @csrf
                    <button type="submit" class="btn btn-primary btn-sm text-nowrap">
                        Generar los {{ $withoutCoupon }} bonos que faltan
                    </button>
                </form>
            @elseif($campaign->failed_count > 0)
                <form method="POST" action="{{ route('helpdeskbirthday.campaigns.retry-failed', $campaign) }}">
                    @csrf
                    <button type="submit" class="btn btn-primary btn-sm text-nowrap">
                        Reintentar los {{ $campaign->failed_count }} fallidos
                    </button>
                </form>
            @endif
        @endcan

        <div class="dropdown">
            <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Acciones de la campaña">
                <i class="fas fa-ellipsis-vertical"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li>
                    <a class="dropdown-item" href="{{ route('helpdeskbirthday.campaigns.preview', $campaign) }}" target="_blank" rel="noopener">Previsualizar el correo</a>
                </li>

                @can('helpdeskbirthday.manage')
                    <li>
                        <form method="POST" action="{{ route('helpdeskbirthday.campaigns.sync-redemptions', $campaign) }}">
                            @csrf
                            <button type="submit" class="dropdown-item">Actualizar los canjes desde la tienda</button>
                        </form>
                    </li>

                    @if($campaign->canBePaused() || $campaign->failed_count > 0 || ($withoutCoupon ?? 0) > 0 || $campaign->canBeCancelled())
                        <li><hr class="dropdown-divider"></li>
                    @endif

                    @if($campaign->canBePaused())
                        <li>
                            <form method="POST" action="{{ route('helpdeskbirthday.campaigns.pause', $campaign) }}">
                                @csrf
                                <button type="submit" class="dropdown-item">Pausar el envío</button>
                            </form>
                        </li>
                    @endif

                    {{-- Las dos acciones de recuperación siguen aquí salvo
                         cuando ya son el botón principal, para no duplicarlas. --}}
                    @if(($withoutCoupon ?? 0) > 0 && $campaign->canBeResumed())
                        <li>
                            <form method="POST" action="{{ route('helpdeskbirthday.campaigns.retry-bonos', $campaign) }}">
                                @csrf
                                <button type="submit" class="dropdown-item">Generar los {{ $withoutCoupon }} bonos que faltan</button>
                            </form>
                        </li>
                    @endif

                    @if($campaign->failed_count > 0 && ($campaign->canBeResumed() || ($withoutCoupon ?? 0) > 0))
                        <li>
                            <form method="POST" action="{{ route('helpdeskbirthday.campaigns.retry-failed', $campaign) }}">
                                @csrf
                                <button type="submit" class="dropdown-item">Reintentar los {{ $campaign->failed_count }} fallidos</button>
                            </form>
                        </li>
                    @endif

                    @if($campaign->canBeCancelled())
                        <li>
                            <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#bd-cancel-modal">Cancelar la campaña</button>
                        </li>
                    @endif
                @endcan
            </ul>
        </div>
    </div>
</div>

{{-- Pestañas secundarias con el estilo del tema (nav-pills.user-profile-tab),
     que es el que usan las fichas de detalle del resto del panel. --}}
<ul class="nav nav-pills user-profile-tab mb-3" role="tablist">
    @php
        $tabs = [
            'summary' => ['label' => 'Resumen', 'route' => route('helpdeskbirthday.campaigns.show', $campaign)],
            'recipients' => ['label' => 'Destinatarios', 'route' => route('helpdeskbirthday.campaigns.recipients', $campaign)],
            'redemptions' => ['label' => 'Canjes', 'route' => route('helpdeskbirthday.campaigns.redemptions', $campaign)],
            'reconciliation' => ['label' => 'Descuadre', 'route' => route('helpdeskbirthday.campaigns.reconciliation', $campaign)],
        ];
    @endphp

    @foreach($tabs as $key => $item)
        <li class="nav-item" role="presentation">
            <a class="nav-link {{ ($tab ?? 'summary') === $key ? 'active' : '' }}"
               href="{{ $item['route'] }}"
               @if(($tab ?? 'summary') === $key) aria-current="page" @endif>
                {{ $item['label'] }}
            </a>
        </li>
    @endforeach
</ul>

@include('core::components.alerts')

@if($campaign->error_message)
    <div class="alert alert-warning">{{ $campaign->error_message }}</div>
@endif

@php
    // Los fallos solo se veían como una cifra dentro de una tarjeta. Cuando son
    // la mayoría de los intentos, el problema no es la campaña sino el envío, y
    // eso tiene que leerse antes que ninguna métrica: una tasa de apertura
    // calculada sobre 141 correos de 577 no dice nada.
    $intentos = $campaign->sent_count + $campaign->failed_count;
    $fallosGraves = $intentos > 0 && ($campaign->failed_count / $intentos) >= 0.2;
@endphp

@if($fallosGraves)
    <div class="alert alert-warning">
        <strong>Están fallando la mayoría de los envíos.</strong>
        {{ $campaign->failed_count }} de los {{ $intentos }} intentos no salieron
        ({{ (int) round(($campaign->failed_count / $intentos) * 100) }}%).
        Revisa el motivo en la columna «Estado» de un destinatario fallido antes de
        dar por buena ninguna cifra de apertura o de canje: se calculan sobre los
        correos que sí salieron.
    </div>
@endif
