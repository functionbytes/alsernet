@extends('helpdesk::public.csat.layout')

@section('title', 'Gracias por tu valoración - ' . config('app.name'))

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-sm-10 col-md-8 col-lg-6 csat-wrapper">

            <div class="text-center mb-4">
                <span class="fw-bold fs-5">{{ config('app.name') }}</span>
            </div>

            <div class="card shadow-sm text-center">
                <div class="card-body p-4 p-md-5">

                    @php
                        $ratingValue = $rating->rating ?? 0;
                        $phrase = match(true) {
                            $ratingValue <= 2 => 'Lamentamos tu experiencia. Trabajaremos para mejorar.',
                            $ratingValue === 3 => 'Gracias por tu valoración. Seguiremos mejorando.',
                            default           => '¡Nos alegra que hayas tenido una buena experiencia!',
                        };
                        $iconClass = match(true) {
                            $ratingValue <= 2 => 'fas fa-face-sad-tear csat-icon-sad',
                            $ratingValue === 3 => 'fas fa-face-meh csat-icon-neutral',
                            default           => 'fas fa-face-smile csat-icon-happy',
                        };
                    @endphp

                    <i class="{{ $iconClass }} mb-3 csat-icon-lg"></i>
                    <h5 class="fw-bold mb-2">¡Gracias por tu valoración!</h5>
                    <p class="text-muted small mb-4">{{ $phrase }}</p>

                    <div class="csat-stars-readonly mb-3" aria-label="Tu calificación: {{ $ratingValue }} de 5">
                        @for($i = 1; $i <= 5; $i++)
                            <i class="{{ $i <= $ratingValue ? 'fas text-warning' : 'far text-muted' }} fa-star csat-star-lg"></i>
                        @endfor
                    </div>
                    <small class="text-muted">Tu valoración: {{ $ratingValue }} de 5</small>

                    @if($rating->comment)
                        <blockquote class="csat-comment mt-4 text-start">
                            "{{ $rating->comment }}"
                        </blockquote>
                    @endif

                </div>
            </div>

        </div>
    </div>
</div>
@endsection

@push('css')
<style>
body { background-color: #f6f7f9; }

.csat-wrapper { max-width: 480px; }

.csat-icon-lg { font-size: 3rem; display: block; }
.csat-icon-happy  { color: #13C672; }
.csat-icon-neutral { color: #FEC90F; }
.csat-icon-sad    { color: #FA896B; }

.csat-star-lg { font-size: 2rem; }

.csat-comment {
    background-color: #f8f9fa;
    border-left: 3px solid #90bb13;
    border-radius: 0.25rem;
    padding: 0.75rem 1rem;
    font-style: italic;
    font-size: 0.9rem;
    color: #6c757d;
}
</style>
@endpush
