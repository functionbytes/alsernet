@extends('layouts.theme')

@section('title', 'Actividad de correo — Eventos de webhook')

{{-- Sin page_header y con content_full_width, igual que el listado: el
     título de la franja del tema repetía el que ya lleva el breadcrumb de
     .evx-toolbar de dentro, y dejaba estas pantallas con un ancho distinto
     al del listado del que cuelgan. --}}
@section('content_full_width', true)

@include('helpdeskemailactivity::settings.partials.css')

@php
    // Mismo criterio sin rojos que reputation.blade.php: ok=verde,
    // warn=ámbar, neutral=gris, crit/error=oliva oscuro.
    $typeLabels = [
        'bounce' => 'Rebote',
        'complaint' => 'Queja de spam',
        'delivered' => 'Entregado',
        'open' => 'Apertura',
    ];

    $typeBadge = fn (?string $type) => match ($type) {
        'bounce' => 'warn',
        'complaint' => 'error',
        'delivered' => 'ok',
        'open' => 'neutral',
        default => 'neutral',
    };
@endphp

@section('content')
    @include('core::components.alerts')

    <div class="emaillog-settings">
        <div class="evx-shell">
            @include('helpdeskemailactivity::settings.partials.subnav', ['current' => 'webhook-events'])

            {{-- Panel de salud por proveedor --}}
            <div class="evx-section-block">
                <h2 class="evx-section-title">{{ __('helpdeskemailactivity::emaillog.webhook_events.health') }}</h2>
                <p class="evx-section-desc">
                    Solo el proveedor activo procesa webhooks de verdad (ver
                    <a href="{{ route('settings.helpdeskemailactivity.index') }}">configuración</a>) — el resto se muestra
                    por si queda histórico de un cambio de proveedor anterior.
                </p>

                <div class="evx-stats">
                    @foreach($health as $row)
                        <div class="evx-stat is-static {{ $row['is_active'] ? 'accent-success' : '' }}">
                            <span class="evx-stat-label">
                                {{ $row['label'] }}
                                @if($row['is_active'])
                                    <span class="evx-badge ok">{{ __('helpdeskemailactivity::emaillog.webhook_events.active') }}</span>
                                @else
                                    <span class="evx-badge neutral">{{ __('helpdeskemailactivity::emaillog.webhook_events.inactive') }}</span>
                                @endif
                            </span>

                            @if($row['is_active'])
                                <span class="evx-badge {{ $row['has_secret'] ? 'ok' : 'warn' }}">
                                    {{ $row['has_secret'] ? 'Secreto configurado' : 'Sin secreto configurado' }}
                                </span>
                            @endif

                            <span class="evx-stat-hint">
                                Último evento:
                                {{ $row['last_event_at'] ? $row['last_event_at']->diffForHumans() : 'nunca' }}
                            </span>
                            <span class="evx-stat-hint">Hoy: {{ number_format($row['today_count']) }} evento(s)</span>
                            <span class="evx-stat-hint">Duplicados descartados hoy: {{ number_format($row['duplicates_today']) }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Filtro por proveedor --}}
            <div class="evx-section-block">
                <form method="GET" action="{{ route('settings.helpdeskemailactivity.webhook-events.index') }}" class="d-flex align-items-center gap-2">
                    <span class="evx-select-icon">
                        <i class="fas fa-filter" aria-hidden="true"></i>
                        <select name="provider" class="evx-select" onchange="this.form.submit()">
                            <option value="">{{ __('helpdeskemailactivity::emaillog.webhook_events.all_providers') }}</option>
                            @foreach($providerLabels as $key => $label)
                                <option value="{{ $key }}" @selected($filterProvider === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </span>
                    @if($filterProvider)
                        <a href="{{ route('settings.helpdeskemailactivity.webhook-events.index') }}" class="evx-muted small">{{ __('helpdeskemailactivity::emaillog.webhook_events.clear_filter') }}</a>
                    @endif
                </form>
            </div>

            {{-- Listado de eventos --}}
            <div class="evx-section-block">
                <h2 class="evx-section-title">{{ __('helpdeskemailactivity::emaillog.webhook_events.recent') }}</h2>
                <p class="evx-section-desc mb-0">
                    Cada fila es un evento de webhook ya verificado. "Ver payload" muestra exactamente lo que mandó el
                    proveedor (redactado/acotado); "Reprocesar" solo aparece en los que no llegaron a correlacionar con
                    ningún email.
                </p>

                @if($events->isEmpty())
                    <div class="evx-empty-row">
                        <i class="fas fa-plug-circle-bolt" aria-hidden="true"></i>
                        <p>{{ __('helpdeskemailactivity::emaillog.webhook_events.empty') }}</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table evx-table mb-0">
                            <thead>
                                <tr>
                                    <th>{{ __('helpdeskemailactivity::emaillog.webhook_events.provider') }}</th>
                                    <th>{{ __('helpdeskemailactivity::emaillog.webhook_events.type') }}</th>
                                    <th>{{ __('helpdeskemailactivity::emaillog.webhook_events.date') }}</th>
                                    <th>{{ __('helpdeskemailactivity::emaillog.webhook_events.correlation') }}</th>
                                    <th class="text-center">{{ __('helpdeskemailactivity::emaillog.webhook_events.duplicates') }}</th>
                                    <th class="text-end">{{ __('helpdeskemailactivity::emaillog.webhook_events.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($events as $event)
                                    <tr>
                                        <td class="fw-semibold">{{ $providerLabels[$event->provider] ?? $event->provider }}</td>
                                        <td><span class="evx-badge {{ $typeBadge($event->event_type) }}">{{ $typeLabels[$event->event_type] ?? ($event->event_type ?? '—') }}</span></td>
                                        <td class="evx-list-date">{{ $event->created_at?->format('d/m/Y H:i') }}</td>
                                        <td>
                                            @if($event->emailLog)
                                                <a href="{{ route('helpdeskemailactivity.show', $event->emailLog) }}">
                                                    {{ Str::limit($event->emailLog->subject, 40) }}
                                                </a>
                                            @elseif($event->processed_at)
                                                <span class="evx-badge neutral">{{ __('helpdeskemailactivity::emaillog.webhook_events.uncorrelated') }}</span>
                                            @else
                                                <span class="evx-muted small">{{ __('helpdeskemailactivity::emaillog.webhook_events.not_processed') }}</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($event->duplicate_count > 0)
                                                <span class="evx-badge warn">{{ $event->duplicate_count }}</span>
                                            @else
                                                <span class="evx-muted">0</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <div class="d-flex justify-content-end align-items-center gap-2">
                                                @if($event->email_log_id === null)
                                                    <form method="POST" action="{{ route('settings.helpdeskemailactivity.webhook-events.reprocess', $event) }}"
                                                          onsubmit="return confirm('¿Reprocesar este evento? Si ahora correlaciona, actualizará el email correspondiente.');">
                                                        @csrf
                                                        <button type="submit" class="evx-icon-btn" title="Reprocesar">
                                                            <i class="fas fa-rotate" aria-hidden="true"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="6" class="pt-0">
                                            <details class="evx-payload-toggle">
                                                <summary>{{ __('helpdeskemailactivity::emaillog.webhook_events.view_payload') }}</summary>
                                                <pre class="evx-payload mono">{{ json_encode($event->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: 'Sin payload guardado (evento anterior a esta auditoría).' }}</pre>
                                            </details>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if($events->hasPages())
                        <div class="evx-pagination">
                            {{ $events->links() }}
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
@endsection
