@extends('layouts.theme')

@section('title', 'Log de emails — Analítica')

@section('page_header')
    @include('core::components.card', ['title' => 'Log de emails — Analítica'])
@endsection

@include('helpdeskemaillog::settings.partials.css')

@php
    use Modules\HelpdeskEmailLog\Services\EmailLogAnalyticsService;

    // Misma paleta sin rojos que emails/reputation.blade.php (ver
    // .evx-rate.is-* en emaillog.css): bien=verde, aviso=ámbar,
    // crítico=oliva oscuro, nunca rojo de alarma.
    $rateClass = fn (?float $rate, float $warning, float $critical) => match (true) {
        $rate === null => 'neutral',
        $rate >= $critical => 'is-crit',
        $rate >= $warning => 'is-warn',
        default => 'is-good',
    };

    // Mismo formato "Xh Ym"/"Xm Ys"/"Xs" que
    // EmailLogController::formatDuration() (privado, no reutilizable desde
    // aquí) — reimplementado en miniatura porque es puro formato de
    // presentación, sin ninguna regla de negocio propia.
    $formatSeconds = function (?float $seconds) {
        if ($seconds === null) {
            return '—';
        }

        $seconds = (int) round($seconds);

        if ($seconds < 60) {
            return "{$seconds}s";
        }

        $minutes = intdiv($seconds, 60);

        if ($minutes < 60) {
            $remainingSeconds = $seconds % 60;

            return $remainingSeconds > 0 ? "{$minutes}m {$remainingSeconds}s" : "{$minutes}m";
        }

        $hours = intdiv($minutes, 60);
        $remainingMinutes = $minutes % 60;

        return $remainingMinutes > 0 ? "{$hours}h {$remainingMinutes}m" : "{$hours}h";
    };

    $totalSends = $mailables->sum('sends');
    $hasDateFilter = request()->hasAny(['date_from', 'date_to']);
@endphp

@section('content')
    @include('core::components.alerts')

    <div class="emaillog-settings">
        <div class="evx-shell">
            <div class="evx-toolbar">
                <nav class="evx-crumbs" aria-label="breadcrumb">
                    <i class="fa-solid fa-headset" aria-hidden="true"></i>
                    <span>{{ __('helpdeskemaillog::emaillog.crumbs.panel') }}</span>
                    <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                    <span>{{ __('helpdeskemaillog::emaillog.crumbs.helpdesk') }}</span>
                    <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                    <span>{{ __('helpdeskemaillog::emaillog.title') }}</span>
                    <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                    <span class="is-current">Analítica</span>
                </nav>

                <div class="evx-toolbar-actions">
                    <a href="{{ route('helpdeskemaillog.index') }}" class="evx-header-btn">
                        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Volver al listado
                    </a>
                </div>
            </div>

            {{-- Rango de fechas — nunca se agrega sobre todo el histórico sin
                 decirlo: por defecto, últimos N días (ver
                 EmailLogAnalyticsService::DEFAULT_WINDOW_DAYS), siempre
                 visible arriba de cada bloque. --}}
            <div class="evx-section-block">
                <h2 class="evx-section-title">Analítica de envíos</h2>
                <p class="evx-section-desc">
                    Del {{ $from->format('d/m/Y') }} al {{ $to->format('d/m/Y') }}
                    @unless($hasDateFilter)
                        · últimos {{ EmailLogAnalyticsService::DEFAULT_WINDOW_DAYS }} días por defecto
                    @endunless
                    · {{ number_format($totalSends) }} envíos en el rango
                </p>

                <form method="GET" class="d-flex align-items-center flex-wrap gap-2">
                    <span class="evx-select-icon">
                        <i class="fas fa-calendar" aria-hidden="true"></i>
                        <input type="date" name="date_from" class="evx-input" value="{{ request('date_from', $from->toDateString()) }}">
                    </span>
                    <span class="evx-select-icon">
                        <i class="fas fa-calendar" aria-hidden="true"></i>
                        <input type="date" name="date_to" class="evx-input" value="{{ request('date_to', $to->toDateString()) }}">
                    </span>
                    <button type="submit" class="evx-btn evx-btn-primary evx-btn-inline">
                        <i class="fas fa-magnifying-glass" aria-hidden="true"></i> Filtrar
                    </button>
                    @if($hasDateFilter)
                        <a href="{{ route('helpdeskemaillog.analytics.index') }}" class="evx-filter-clear">Quitar filtro</a>
                    @endif
                </form>
            </div>

            {{-- 1. Rendimiento por mailable --}}
            <div class="evx-section-block">
                <h2 class="evx-section-title">Rendimiento por mailable</h2>
                <p class="evx-section-desc">Ordenado por volumen de envíos en el rango seleccionado.</p>

                @if($mailables->isEmpty())
                    <p class="evx-muted mb-0">No hay envíos en este rango.</p>
                @else
                    <div class="table-responsive">
                        <table class="table evx-table mb-0">
                            <thead>
                                <tr>
                                    <th>Mailable</th>
                                    <th class="text-end">Envíos</th>
                                    <th class="text-center">% Entrega</th>
                                    <th class="text-center">% Rebote</th>
                                    <th class="text-center">% Apertura</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($mailables as $row)
                                    <tr>
                                        <td>
                                            @if($row['mailable_class'])
                                                <div class="fw-semibold">{{ class_basename($row['mailable_class']) }}</div>
                                                <div class="evx-tag mono">{{ $row['mailable_class'] }}</div>
                                            @else
                                                <span class="evx-muted">Sin clase de mailable</span>
                                            @endif
                                        </td>
                                        <td class="text-end">{{ number_format($row['sends']) }}</td>
                                        <td class="text-center">
                                            {{ $row['delivery_rate'] !== null ? $row['delivery_rate'].'%' : '—' }}
                                        </td>
                                        <td class="text-center">
                                            <span class="evx-rate {{ $rateClass($row['bounce_rate'], $thresholds['bounce_warning'], $thresholds['bounce_critical']) }}">
                                                {{ $row['bounce_rate'] !== null ? $row['bounce_rate'].'%' : '—' }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            @if($row['open_tracked'] === 0)
                                                <span class="evx-muted small" title="Ningún envío de este mailable llevaba píxel de seguimiento">Sin seguimiento</span>
                                            @else
                                                {{ $row['open_rate'] }}%
                                                <span class="evx-muted small">(de {{ number_format($row['open_tracked']) }} con seguimiento)</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            {{-- 2. Latencia de entrega --}}
            <div class="evx-section-block">
                <h2 class="evx-section-title">Latencia de entrega</h2>

                @if($latency['sample_size'] === 0)
                    <p class="evx-muted mb-0">
                        Ningún envío de este rango tiene confirmación de entrega del proveedor (<code>delivered_at</code>) —
                        hoy solo llega vía webhook de Mailgun, Postmark o SES. No se puede calcular latencia todavía.
                    </p>
                @else
                    <p class="evx-section-desc">
                        Calculada sobre {{ number_format($latency['sample_size']) }} de {{ number_format($totalSends) }} envíos del rango
                        — el resto nunca recibió confirmación de entrega del proveedor, así que no cuenta aquí.
                    </p>

                    <div class="evx-stats">
                        <div class="evx-stat is-static">
                            <span class="evx-stat-label">Mediana (p50)</span>
                            <span class="evx-stat-value">{{ $formatSeconds($latency['p50_seconds']) }}</span>
                        </div>
                        <div class="evx-stat is-static">
                            <span class="evx-stat-label">p95</span>
                            <span class="evx-stat-value">{{ $formatSeconds($latency['p95_seconds']) }}</span>
                        </div>
                        <div class="evx-stat is-static">
                            <span class="evx-stat-label">Máximo</span>
                            <span class="evx-stat-value">{{ $formatSeconds($latency['max_seconds']) }}</span>
                        </div>
                        <div class="evx-stat is-static">
                            <span class="evx-stat-label">Muestra</span>
                            <span class="evx-stat-value">{{ number_format($latency['sample_size']) }}</span>
                        </div>
                    </div>
                @endif
            </div>

            {{-- 3. Entregabilidad por dominio destinatario --}}
            <div class="evx-section-block">
                <h2 class="evx-section-title">Entregabilidad por dominio destinatario</h2>
                <p class="evx-section-desc">
                    Dominio del destinatario principal de cada envío · top {{ EmailLogAnalyticsService::DOMAIN_LIMIT }} de {{ number_format($domains['total_domains']) }} dominios por volumen
                </p>

                @if($domains['domains']->isEmpty())
                    <p class="evx-muted mb-0">No hay envíos con destinatario reconocible en este rango.</p>
                @else
                    <div class="table-responsive">
                        <table class="table evx-table mb-0">
                            <thead>
                                <tr>
                                    <th>Dominio</th>
                                    <th class="text-end">Volumen</th>
                                    <th class="text-center">% Entrega</th>
                                    <th class="text-center">% Rebote</th>
                                    <th class="text-center">% Queja</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($domains['domains'] as $row)
                                    <tr>
                                        <td class="fw-semibold">{{ $row['domain'] }}</td>
                                        <td class="text-end">{{ number_format($row['volume']) }}</td>
                                        <td class="text-center">{{ $row['delivery_rate'] !== null ? $row['delivery_rate'].'%' : '—' }}</td>
                                        <td class="text-center">
                                            <span class="evx-rate {{ $rateClass($row['bounce_rate'], $thresholds['bounce_warning'], $thresholds['bounce_critical']) }}">
                                                {{ $row['bounce_rate'] !== null ? $row['bounce_rate'].'%' : '—' }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="evx-rate {{ $rateClass($row['complaint_rate'], $thresholds['complaint_warning'], $thresholds['complaint_critical']) }}">
                                                {{ $row['complaint_rate'] !== null ? $row['complaint_rate'].'%' : '—' }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach

                                @if($domains['others'])
                                    <tr class="evx-muted">
                                        <td class="fst-italic">Otros ({{ number_format($domains['total_domains'] - $domains['domains']->count()) }} dominios)</td>
                                        <td class="text-end">{{ number_format($domains['others']['volume']) }}</td>
                                        <td class="text-center">{{ $domains['others']['delivery_rate'] !== null ? $domains['others']['delivery_rate'].'%' : '—' }}</td>
                                        <td class="text-center">{{ $domains['others']['bounce_rate'] !== null ? $domains['others']['bounce_rate'].'%' : '—' }}</td>
                                        <td class="text-center">{{ $domains['others']['complaint_rate'] !== null ? $domains['others']['complaint_rate'].'%' : '—' }}</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
