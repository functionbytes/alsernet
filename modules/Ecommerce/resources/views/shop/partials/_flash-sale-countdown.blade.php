@props(['endDate'])

@php
    $endIso = $endDate instanceof \DateTimeInterface ? $endDate->toIso8601String() : (string) $endDate;
@endphp

<div class="flash-countdown" data-end="{{ $endIso }}">
    <div class="countdown-item">
        <span class="countdown-value js-cd-days">00</span>
        <span class="countdown-label">días</span>
    </div>
    <div class="countdown-sep">:</div>
    <div class="countdown-item">
        <span class="countdown-value js-cd-hours">00</span>
        <span class="countdown-label">hrs</span>
    </div>
    <div class="countdown-sep">:</div>
    <div class="countdown-item">
        <span class="countdown-value js-cd-mins">00</span>
        <span class="countdown-label">min</span>
    </div>
    <div class="countdown-sep">:</div>
    <div class="countdown-item">
        <span class="countdown-value js-cd-secs">00</span>
        <span class="countdown-label">seg</span>
    </div>
</div>
