@extends('layouts.theme')

@section('title', 'Actividad de correo — Reputación')

{{-- Sin page_header y con content_full_width, igual que el listado: el
     título de la franja del tema repetía el que ya lleva el breadcrumb de
     .evx-toolbar de dentro, y dejaba estas pantallas con un ancho distinto
     al del listado del que cuelgan. --}}
@section('content_full_width', true)

@include('helpdeskemailactivity::settings.partials.css')

@php
    // Paleta sin rojos (ver .evx-badge.crit / .evx-stat.accent-danger en
    // emaillog.css): bien=verde, aviso=ámbar, crítico=oliva oscuro (nunca
    // rojo de alarma).
    $authBadge = fn (string $status) => match ($status) {
        'pass' => 'ok',
        'warning' => 'warn',
        'missing' => 'crit',
        default => 'neutral',
    };
    $statAccent = fn (float $rate, float $warning, float $critical) => match (true) {
        $rate >= $critical => 'accent-danger',
        $rate >= $warning => 'accent-warning',
        default => 'accent-success',
    };
    $rateClass = fn (float $rate, float $warning, float $critical) => match (true) {
        $rate >= $critical => 'is-crit',
        $rate >= $warning => 'is-warn',
        default => 'is-good',
    };

    // Los cientos de dominios detectados sin monitorizar sepultaban el resto
    // de la página — solo se destacan los 20 más frecuentes (ya vienen
    // ordenados por volumen desde el controlador), con el resto disponible
    // en un <details> nativo (sin JS) y filtrable por texto.
    $topSuggestions = array_slice($suggestions, 0, 20, true);
    $restSuggestions = array_slice($suggestions, 20, null, true);
@endphp

@section('content')
    @include('core::components.alerts')

    <div class="emaillog-settings">
        <div class="evx-shell">
            @include('helpdeskemailactivity::settings.partials.subnav', ['current' => 'reputation'])

            {{-- Tasas globales --}}
            <div class="evx-section-block">
                <h2 class="evx-section-title">{{ __('helpdeskemailactivity::emaillog.reputation.heading') }}</h2>
                <p class="evx-section-desc">
                    Últimos {{ $days }} días · {{ number_format($overall['attempted']) }} envíos con resultado terminal
                </p>

                <div class="evx-stats">
                    <div class="evx-stat is-static {{ $statAccent($overall['bounce_rate'], $thresholds['bounce_warning'], $thresholds['bounce_critical']) }}">
                        <span class="evx-stat-label">{{ __('helpdeskemailactivity::emaillog.reputation.bounce_rate') }}</span>
                        <span class="evx-stat-value">{{ $overall['bounce_rate'] }}%</span>
                        <span class="evx-stat-hint">Límite crítico {{ $thresholds['bounce_critical'] }}%</span>
                    </div>
                    <div class="evx-stat is-static {{ $statAccent($overall['complaint_rate'], $thresholds['complaint_warning'], $thresholds['complaint_critical']) }}">
                        <span class="evx-stat-label">{{ __('helpdeskemailactivity::emaillog.reputation.complaint_rate') }}</span>
                        <span class="evx-stat-value">{{ $overall['complaint_rate'] }}%</span>
                        <span class="evx-stat-hint">Límite crítico {{ $thresholds['complaint_critical'] }}%</span>
                    </div>
                    <div class="evx-stat is-static">
                        <span class="evx-stat-label">{{ __('helpdeskemailactivity::emaillog.reputation.bounces') }}</span>
                        <span class="evx-stat-value">{{ number_format($overall['bounced']) }}</span>
                    </div>
                    <div class="evx-stat is-static">
                        <span class="evx-stat-label">{{ __('helpdeskemailactivity::emaillog.reputation.complaints') }}</span>
                        <span class="evx-stat-value">{{ number_format($overall['complained']) }}</span>
                    </div>
                </div>
            </div>

            {{-- Dominios monitorizados --}}
            <div class="evx-section-block">
                <h2 class="evx-section-title">{{ __('helpdeskemailactivity::emaillog.reputation.domain_auth') }}</h2>
                <p class="evx-section-desc">{{ __('helpdeskemailactivity::emaillog.reputation.domain_auth_hint') }}</p>

                @if($rows->isEmpty())
                    <p class="evx-muted mb-0">
                        {{ __('helpdeskemailactivity::emaillog.reputation.no_domains') }}
                        <a href="{{ route('settings.helpdeskemailactivity.index') }}">{{ __('helpdeskemailactivity::emaillog.reputation.add_domain') }}</a> en la configuración del módulo.
                    </p>
                @else
                    <div class="table-responsive">
                        <table class="table evx-table mb-0">
                            <thead>
                                <tr>
                                    <th>{{ __('helpdeskemailactivity::emaillog.reputation.domain') }}</th>
                                    <th>SPF</th>
                                    <th>{{ __('helpdeskemailactivity::emaillog.reputation.dmarc') }}</th>
                                    <th>{{ __('helpdeskemailactivity::emaillog.reputation.dkim') }}</th>
                                    <th class="text-center">{{ __('helpdeskemailactivity::emaillog.reputation.col_bounce_rate') }}</th>
                                    <th class="text-center">{{ __('helpdeskemailactivity::emaillog.reputation.col_complaint_rate') }}</th>
                                    <th class="text-end">{{ __('helpdeskemailactivity::emaillog.reputation.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($rows as $row)
                                    <tr>
                                        <td class="fw-semibold">{{ $row['domain'] }}</td>
                                        <td><span class="evx-badge {{ $authBadge($row['auth']['spf']['status']) }}">{{ $row['auth']['spf']['status'] }}</span></td>
                                        <td>
                                            <span class="evx-badge {{ $authBadge($row['auth']['dmarc']['status']) }}">{{ $row['auth']['dmarc']['status'] }}</span>
                                            @if($row['auth']['dmarc']['policy'])
                                                <span class="evx-muted small">p={{ $row['auth']['dmarc']['policy'] }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="evx-badge {{ $authBadge($row['auth']['dkim']['status']) }}">{{ $row['auth']['dkim']['status'] }}</span>
                                            @if($row['auth']['dkim']['selector'])
                                                <span class="evx-muted small">({{ $row['auth']['dkim']['selector'] }})</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span class="evx-rate {{ $rateClass($row['stats']['bounce_rate'], $thresholds['bounce_warning'], $thresholds['bounce_critical']) }}">
                                                {{ $row['stats']['bounce_rate'] }}%
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="evx-rate {{ $rateClass($row['stats']['complaint_rate'], $thresholds['complaint_warning'], $thresholds['complaint_critical']) }}">
                                                {{ $row['stats']['complaint_rate'] }}%
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <form method="POST" action="{{ route('helpdeskemailactivity.reputation.refresh') }}">
                                                @csrf
                                                <input type="hidden" name="domain" value="{{ $row['domain'] }}">
                                                <button type="submit" class="evx-icon-btn" title="Repetir la comprobación DNS">
                                                    <i class="fas fa-rotate" aria-hidden="true"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            {{-- Dominios vistos, aún sin monitorizar --}}
            <div class="evx-section-block">
                <h2 class="evx-section-title">{{ __('helpdeskemailactivity::emaillog.reputation.unmonitored') }}</h2>
                <p class="evx-section-desc">
                    Detectados en el remitente de envíos de los últimos 30 días, ordenados por volumen.
                    @if(count($suggestions) > 0)
                        {{ count($suggestions) }} en total.
                    @endif
                </p>

                @if(empty($suggestions))
                    <p class="evx-muted mb-0">{{ __('helpdeskemailactivity::emaillog.reputation.no_new_domains') }}</p>
                @else
                    @if(count($suggestions) > 10)
                        <input type="text" id="evx-domain-filter" class="evx-input mb-2"
                               placeholder="{{ __('helpdeskemailactivity::emaillog.reputation.filter_placeholder') }}" aria-label="Filtrar dominios detectados">
                    @endif

                    <div class="evx-domain-chips">
                        @foreach($topSuggestions as $domain => $sends)
                            <span class="evx-tag mono" data-domain="{{ Str::lower($domain) }}" title="{{ number_format($sends) }} envíos en 30 días">
                                {{ $domain }} <span class="evx-muted">· {{ number_format($sends) }}</span>
                            </span>
                        @endforeach
                    </div>

                    @if(count($restSuggestions) > 0)
                        <details class="evx-domain-more">
                            <summary>+{{ count($restSuggestions) }} más</summary>
                            <div class="evx-domain-chips">
                                @foreach($restSuggestions as $domain => $sends)
                                    <span class="evx-tag mono" data-domain="{{ Str::lower($domain) }}" title="{{ number_format($sends) }} envíos en 30 días">
                                        {{ $domain }} <span class="evx-muted">· {{ number_format($sends) }}</span>
                                    </span>
                                @endforeach
                            </div>
                        </details>
                    @endif
                @endif
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script src="{{ asset('modules/helpdeskemailactivity/js/reputation.js') }}?v={{ filemtime(public_path('modules/helpdeskemailactivity/js/reputation.js')) }}"></script>
@endpush
