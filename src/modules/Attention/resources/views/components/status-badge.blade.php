@props(['status'])

@php
    $statusClass = match($status->value) {
        'received' => 'bg-info',
        'in_process' => 'bg-warning',
        'resolved' => 'bg-success',
        'closed' => 'bg-secondary',
        default => 'bg-secondary'
    };
@endphp

<span {{ $attributes->merge(['class' => "badge {$statusClass}"]) }}>
    {{ $status->label() }}
</span>
