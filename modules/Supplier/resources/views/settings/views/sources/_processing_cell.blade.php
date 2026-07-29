@php
    $batchStatus = $source->last_batch_status;
    $statusMap = [
        'pending'    => ['badge' => 'warning',   'icon' => 'fa-hourglass-half',             'label' => 'Pendiente'],
        'processing' => ['badge' => 'primary',   'icon' => 'fa-circle-notch fa-spin',       'label' => 'Procesando'],
        'completed'  => ['badge' => 'success',   'icon' => 'fa-check',                      'label' => 'Completado'],
        'failed'     => ['badge' => 'danger',    'icon' => 'fa-xmark',                      'label' => 'Fallido'],
    ];
    $info = $statusMap[$batchStatus] ?? null;
@endphp

@if($info)
    <span class="badge bg-{{ $info['badge'] }}-subtle text-{{ $info['badge'] }}">
        <i class="fas {{ $info['icon'] }} me-1"></i>{{ $info['label'] }}
    </span>
@else
    <span class="badge bg-info-subtle text-info">Nunca procesado</span>
@endif

@if($source->last_processed_at)
    <small class="text-muted d-block mt-1">{{ $source->last_processed_at->diffForHumans() }}</small>
@endif
