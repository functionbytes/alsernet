{{-- Expects: $report (AiRegressionReport) --}}
@php
    $badge = match ($report->status) {
        'completed' => 'bg-success-subtle text-success',
        'failed' => 'bg-danger-subtle text-danger',
        'running' => 'bg-info-subtle text-info',
        default => 'bg-secondary-subtle text-secondary',
    };
@endphp
<span class="badge {{ $badge }}">{{ __('helpdeskaiprompts::quality.status_'.$report->status) }}</span>
