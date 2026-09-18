{{--
    Badge de delta para una tarjeta KPI del listado (evx-stats).

    $delta viene de EmailLogController::computeStatsDelta() con la forma:
    ['current', 'previous', 'diff', 'diff_percent', 'direction' => up|down|flat, 'positive' => true|false|null]

    $isRate (opcional, default false): pásalo en true para las tarjetas cuyo
    valor YA es un porcentaje (tasa de entrega/apertura/clic) — en ese caso
    $diff son puntos porcentuales, no una cantidad bruta, así que se ignora
    diff_percent (relativo a un porcentaje ya es confuso) y se sufija "pts"
    en vez de "%" para no leerse como un conteo absoluto.

    Acceso siempre defensivo (?? null): esta vista se integró en paralelo con
    el backend que genera $statsDelta, así que si alguna clave concreta
    todavía no existe (o cambia de forma), el badge simplemente no se pinta
    en vez de romper la tarjeta.
--}}
@php
    $diff = $delta['diff'] ?? null;
    $isRate ??= false;
@endphp
@if($diff !== null)
    @php
        $diffPercent = $isRate ? null : ($delta['diff_percent'] ?? null);

        $polarityClass = match ($delta['positive'] ?? null) {
            true => 'is-good',
            false => 'is-bad',
            default => 'is-neutral',
        };

        $icon = match ($delta['direction'] ?? 'flat') {
            'up' => 'fa-arrow-up',
            'down' => 'fa-arrow-down',
            default => 'fa-minus',
        };

        $sign = match (true) {
            $diff > 0 => '+',
            $diff < 0 => '-',
            default => '',
        };

        $label = match (true) {
            $diffPercent !== null => $sign.number_format(abs($diffPercent), 1).'%',
            $isRate => $sign.number_format(abs($diff), 1).' '.__('helpdeskemailactivity::emaillog.stats.delta_points_suffix'),
            default => $sign.number_format(abs($diff)),
        };
    @endphp
    <span class="evx-stat-delta {{ $polarityClass }}" title="{{ __('helpdeskemailactivity::emaillog.stats.delta_vs_previous') }}">
        <i class="fas {{ $icon }}" aria-hidden="true"></i>{{ $label }}
    </span>
@endif
