{{-- Quota Progress Component
    Parameters:
    - current: integer (current usage)
    - quota: integer (total quota limit)
    - label: string (display label)
    - unit: string (default: 'correos')
    - resetDate: string (next reset date, optional)
    - showPercentage: boolean (default: true)
    - showStats: boolean (default: true)
--}}

@php
    $current = $current ?? 0;
    $quota = $quota ?? 0;
    $label = $label ?? 'Cuota de envío';
    $unit = $unit ?? 'correos';
    $resetDate = $resetDate ?? null;
    $showPercentage = $showPercentage ?? true;
    $showStats = $showStats ?? true;

    // Calculate percentage
    $percentage = $quota > 0 ? min(($current / $quota) * 100, 100) : 0;
    $remaining = max($quota - $current, 0);

    // Determine color based on percentage
    if ($percentage < 50) {
        $colorClass = 'success';
        $bgClass = 'bg-success';
        $textColorClass = 'text-success';
        $icon = 'fa-smile';
    } elseif ($percentage < 80) {
        $colorClass = 'warning';
        $bgClass = 'bg-warning';
        $textColorClass = 'text-warning';
        $icon = 'fa-exclamation-triangle';
    } elseif ($percentage < 95) {
        $colorClass = 'orange';
        $bgClass = 'bg-orange';
        $textColorClass = 'text-orange';
        $icon = 'fa-exclamation-circle';
    } else {
        $colorClass = 'danger';
        $bgClass = 'bg-danger';
        $textColorClass = 'text-danger';
        $icon = 'fa-circle-exclamation';
    }
@endphp

<div class="quota-progress-card">
    <div class="card border-0 shadow-sm h-100">
        <div class="card-body p-4">
            {{-- Header with Label and Icon --}}
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h6 class="mb-0 fw-bold">{{ $label }}</h6>
                <span class="badge bg-{{ $colorClass }}-subtle text-{{ $colorClass }}">
                    <i class="fas {{ $icon }} me-1"></i>
                    {{ round($percentage) }}%
                </span>
            </div>

            {{-- Progress Bar --}}
            <div class="progress mb-3" style="height: 8px;">
                <div class="progress-bar {{ $bgClass }}"
                     role="progressbar"
                     style="width: {{ $percentage }}%"
                     aria-valuenow="{{ $current }}"
                     aria-valuemin="0"
                     aria-valuemax="{{ $quota }}">
                </div>
            </div>

            {{-- Statistics Section --}}
            @if($showStats)
                <div class="row g-2 mb-3">
                    {{-- Current Usage --}}
                    <div class="col-6">
                        <div class="p-2 rounded bg-light">
                            <small class="text-muted d-block fw-semibold">Utilizado</small>
                            <strong class="d-block fs-6">
                                <span class="{{ $textColorClass }}">{{ number_format($current) }}</span>
                                <span class="text-muted" style="font-size: 0.85em;">{{ $unit }}</span>
                            </strong>
                        </div>
                    </div>

                    {{-- Remaining --}}
                    <div class="col-6">
                        <div class="p-2 rounded bg-light">
                            <small class="text-muted d-block fw-semibold">Disponible</small>
                            <strong class="d-block fs-6">
                                <span class="text-success">{{ number_format($remaining) }}</span>
                                <span class="text-muted" style="font-size: 0.85em;">{{ $unit }}</span>
                            </strong>
                        </div>
                    </div>
                </div>

                {{-- Total Quota --}}
                <div class="p-2 rounded bg-light mb-3">
                    <small class="text-muted d-block fw-semibold">Cuota total</small>
                    <strong class="d-block fs-6">
                        {{ number_format($quota) }}
                        <span class="text-muted" style="font-size: 0.85em;">{{ $unit }}</span>
                    </strong>
                </div>

                {{-- Status Message --}}
                <div class="alert alert-sm mb-0 p-2 border-0"
                     style="background-color: rgba(var(--bs-{{ $colorClass }}-rgb), 0.1);
                             border-left: 3px solid var(--bs-{{ $colorClass }});">
                    <small class="text-{{ $colorClass }} fw-semibold">
                        <i class="fas {{ $icon }} me-1"></i>
                        @if($percentage < 50)
                            Cuota disponible - Sin preocupaciones
                        @elseif($percentage < 80)
                            Cuota moderada - Monitorea tu uso
                        @elseif($percentage < 95)
                            Cuota casi llena - Considera aumentar el límite
                        @else
                            Cuota casi agotada - Aumenta el límite pronto
                        @endif
                    </small>
                </div>
            @endif

            {{-- Reset Information --}}
            @if($resetDate)
                <div class="border-top mt-3 pt-3">
                    <small class="text-muted d-block mb-2">
                        <i class="fas fa-redo me-1"></i>Siguiente reinicio
                    </small>
                    <strong class="d-block">
                        {{ \Carbon\Carbon::parse($resetDate)->format('d/m/Y H:i') }}
                    </strong>
                    <small class="text-muted d-block">
                        {{ \Carbon\Carbon::parse($resetDate)->diffForHumans() }}
                    </small>
                </div>
            @endif

            {{-- Action Link (Optional slot) --}}
            @if(isset($actionLink) && isset($actionLabel))
                <div class="border-top mt-3 pt-3">
                    <a href="{{ $actionLink }}" class="btn btn-sm btn-primary w-100">
                        <i class="fas fa-arrow-up me-1"></i>{{ $actionLabel }}
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>

@push('styles')
<style>
    .quota-progress-card .card {
        transition: box-shadow 0.3s ease;
    }

    .quota-progress-card .card:hover {
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1) !important;
    }

    .quota-progress-card .progress {
        background-color: rgba(0, 0, 0, 0.05);
        border-radius: 4px;
        overflow: hidden;
    }

    .quota-progress-card .progress-bar {
        transition: width 0.3s ease;
    }

    /* Dark mode support */
    @media (prefers-color-scheme: dark) {
        .quota-progress-card .progress {
            background-color: rgba(255, 255, 255, 0.1);
        }
    }

    /* Responsive adjustments */
    @media (max-width: 576px) {
        .quota-progress-card .card {
            box-shadow: none;
            border: 1px solid rgba(0, 0, 0, 0.1);
        }

        .quota-progress-card .card-body {
            padding: 1rem !important;
        }

        .quota-progress-card .row.g-2 {
            gap: 0.5rem !important;
        }
    }
</style>
@endpush
