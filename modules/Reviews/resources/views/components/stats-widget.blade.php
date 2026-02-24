@props([
    'title',
    'value',
    'icon',
    'subtitle' => '',
    'id' => '',
    'color' => 'primary',
    'trend' => null,
    'trendValue' => null
])

@php
    $colorMap = [
        'primary' => ['bg' => 'rgba(144, 187, 19, 0.1)', 'text' => '#90bb13'],
        'success' => ['bg' => 'rgba(19, 198, 114, 0.1)', 'text' => '#13C672'],
        'warning' => ['bg' => 'rgba(254, 201, 15, 0.1)', 'text' => '#FEC90F'],
        'danger' => ['bg' => 'rgba(250, 137, 107, 0.1)', 'text' => '#FA896B'],
        'info' => ['bg' => 'rgba(99, 102, 241, 0.1)', 'text' => '#6366f1'],
    ];
    $colors = $colorMap[$color] ?? $colorMap['primary'];
@endphp

<div class="card h-100 stats-widget" {{ $id ? "id=$id" : '' }}>
    <div class="card-body">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div class="icon-wrapper d-flex align-items-center justify-content-center rounded-3"
                 style="width: 48px; height: 48px; background: {{ $colors['bg'] }};">
                <i class="{{ $icon }} fs-4" style="color: {{ $colors['text'] }};"></i>
            </div>
            @if($trend && $trendValue)
                <span class="badge bg-light-{{ $trend === 'up' ? 'success' : 'danger' }} text-{{ $trend === 'up' ? 'success' : 'danger' }} rounded-pill px-2 py-1">
                    <i class="fas fa-arrow-{{ $trend }}"></i> {{ $trendValue }}
                </span>
            @endif
        </div>
        <div>
            <h3 class="mb-1 fw-bold">{{ $value }}</h3>
            <p class="text-muted mb-0 fs-3">{{ $title }}</p>
            @if($subtitle)
                <small class="text-muted">{{ $subtitle }}</small>
            @endif
        </div>
    </div>
</div>
