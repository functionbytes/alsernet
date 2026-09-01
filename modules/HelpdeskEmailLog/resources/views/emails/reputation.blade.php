@extends('layouts.theme')

@section('title', 'Log de emails — Reputación')

@section('page_header')
    @include('core::components.card', ['title' => 'Log de emails — Reputación'])
@endsection

@include('helpdeskemaillog::settings.partials.css')

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
            @include('helpdeskemaillog::settings.partials.subnav', ['current' => 'reputation'])

            {{-- Tasas globales --}}
            <div class="evx-section-block">
                <h2 class="evx-section-title">Reputación de envío</h2>
                <p class="evx-section-desc">
                    Últimos {{ $days }} días · {{ number_format($overall['attempted']) }} envíos con resultado terminal
                </p>

                <div class="evx-stats">
                    <div class="evx-stat is-static {{ $statAccent($overall['bounce_rate'], $thresholds['bounce_warning'], $thresholds['bounce_critical']) }}">
                        <span class="evx-stat-label">Tasa de rebote</span>
                        <span class="evx-stat-value">{{ $overall['bounce_rate'] }}%</span>
                        <span class="evx-stat-hint">Límite crítico {{ $thresholds['bounce_critical'] }}%</span>
                    </div>
                    <div class="evx-stat is-static {{ $statAccent($overall['complaint_rate'], $thresholds['complaint_warning'], $thresholds['complaint_critical']) }}">
                        <span class="evx-stat-label">Tasa de quejas de spam</span>
                        <span class="evx-stat-value">{{ $overall['complaint_rate'] }}%</span>
                        <span class="evx-stat-hint">Límite crítico {{ $thresholds['complaint_critical'] }}%</span>
                    </div>
                    <div class="evx-stat is-static">
                        <span class="evx-stat-label">Rebotes</span>
                        <span class="evx-stat-value">{{ number_format($overall['bounced']) }}</span>
                    </div>
                    <div class="evx-stat is-static">
                        <span class="evx-stat-label">Quejas</span>
                        <span class="evx-stat-value">{{ number_format($overall['complained']) }}</span>
                    </div>
                </div>
            </div>

            {{-- Dominios monitorizados --}}
            <div class="evx-section-block">
                <h2 class="evx-section-title">Autenticación por dominio</h2>
                <p class="evx-section-desc">SPF/DMARC vía DNS · DKIM solo si se conoce el selector</p>

                @if($rows->isEmpty())
                    <p class="evx-muted mb-0">
                        Sin dominios configurados.
                        <a href="{{ route('settings.helpdeskemaillog.index') }}">Añadir uno</a> en la configuración del módulo.
                    </p>
                @else
                    <div class="table-responsive">
                        <table class="table evx-table mb-0">
                            <thead>
                                <tr>
                                    <th>Dominio</th>
                                    <th>SPF</th>
                                    <th>DMARC</th>
                                    <th>DKIM</th>
                                    <th class="text-center">Tasa rebote</th>
                                    <th class="text-center">Tasa quejas</th>
                                    <th class="text-end">Acciones</th>
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
                                            <form method="POST" action="{{ route('helpdeskemaillog.reputation.refresh') }}">
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
                <h2 class="evx-section-title">Dominios vistos, aún sin monitorizar</h2>
                <p class="evx-section-desc">
                    Detectados en el remitente de envíos de los últimos 30 días, ordenados por volumen.
                    @if(count($suggestions) > 0)
                        {{ count($suggestions) }} en total.
                    @endif
                </p>

                @if(empty($suggestions))
                    <p class="evx-muted mb-0">No hay dominios nuevos por revisar.</p>
                @else
                    @if(count($suggestions) > 10)
                        <input type="text" id="evx-domain-filter" class="evx-input mb-2"
                               placeholder="Filtrar dominios…" aria-label="Filtrar dominios detectados">
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
<script>
$(function () {
    // Filtra los chips de dominio (visibles y los del <details> colapsado)
    // por subcadena — abre el desplegable automáticamente si hay coincidencias
    // dentro de él, para no esconder resultados de la búsqueda.
    const $filter = $('#evx-domain-filter');
    if (!$filter.length) return;

    const $chips = $('.evx-domain-chips .evx-tag');
    const $details = $('.evx-domain-more');

    $filter.on('input', function () {
        const query = this.value.trim().toLowerCase();
        $chips.each(function () {
            $(this).toggle(!query || $(this).data('domain').includes(query));
        });
        if (query) $details.prop('open', true);
    });
});
</script>
@endpush
