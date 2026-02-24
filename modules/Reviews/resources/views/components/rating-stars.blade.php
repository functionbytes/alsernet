@props(['rating'])

@php
    $stars = match($rating) {
        'ONE_STAR' => 1,
        'TWO_STAR' => 2,
        'THREE_STAR' => 3,
        'FOUR_STAR' => 4,
        'FIVE_STAR' => 5,
        default => 0,
    };
@endphp

@for($i = 1; $i <= 5; $i++)
    @if($i <= $stars)
        <i class="fas fa-star text-warning"></i>
    @else
        <i class="far fa-star text-muted"></i>
    @endif
@endfor
