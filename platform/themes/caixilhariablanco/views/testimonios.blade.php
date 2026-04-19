@extends('template::layouts.default')

@php
    $pageTitle = 'Testimonios y reseñas';
    $pageDesc  = 'Lo que nuestros clientes dicen sobre Caixilharia Blanco. Reseñas verificadas de Google.';
@endphp

@section('title', $pageTitle)
@section('description', $pageDesc)

@section('seo_head')
    <link rel="canonical" href="{{ url('/testimonios') }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $pageDesc }}">
    <meta property="og:url" content="{{ url('/testimonios') }}">
    <meta property="og:site_name" content="{{ theme_option('site_title', config('app.name')) }}">

    @if($totalCount > 0)
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "LocalBusiness",
        "name": "{{ addslashes(theme_option('site_title', config('app.name'))) }}",
        "url": "{{ url('/') }}",
        @if(theme_option('address'))
        "address": {
            "@type": "PostalAddress",
            "streetAddress": "{{ addslashes(theme_option('address')) }}"
        },
        @endif
        "aggregateRating": {
            "@type": "AggregateRating",
            "ratingValue": "{{ number_format($avgRating, 1) }}",
            "reviewCount": "{{ $totalCount }}",
            "bestRating": "5",
            "worstRating": "1"
        }
    }
    </script>
    @endif
@endsection

@section('content')

{{-- Breadcrumb --}}
<div class="breadcrumb-area py-3 bg-light border-bottom">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item">
                    <a href="{{ url('/') }}" class="text-decoration-none">
                        <i class="fas fa-home me-1"></i>Inicio
                    </a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">Testimonios</li>
            </ol>
        </nav>
    </div>
</div>

<section class="section-padding">
    <div class="container">

        {{-- Encabezado con rating promedio --}}
        <div class="text-center mb-5">
            <h1 class="mb-2">Lo que dicen nuestros clientes</h1>
            @if($totalCount > 0)
            <div class="d-flex justify-content-center align-items-center gap-2 mb-3">
                @for($i = 1; $i <= 5; $i++)
                    <i class="fas fa-star {{ $i <= round($avgRating) ? 'text-warning' : 'text-muted' }}" style="font-size:1.6rem;"></i>
                @endfor
                <span class="fw-bold fs-3 ms-2">{{ number_format($avgRating, 1) }}</span>
            </div>
            <p class="text-muted">Basado en {{ number_format($totalCount) }} reseñas verificadas de Google</p>
            @endif
        </div>

        {{-- Filtros por estrella --}}
        <div class="d-flex flex-wrap gap-2 justify-content-center mb-4">
            <a href="{{ route('reviews.public.index') }}"
               class="btn btn-sm {{ $rating === 0 ? 'btn-primary' : 'btn-outline-secondary' }}">
                Todas
            </a>
            @foreach([5, 4, 3, 2, 1] as $star)
            <a href="{{ route('reviews.public.index', ['rating' => $star]) }}"
               class="btn btn-sm {{ $rating === $star ? 'btn-warning' : 'btn-outline-secondary' }}">
                {{ $star }} <i class="fas fa-star text-warning ms-1" style="font-size:.75rem;"></i>
            </a>
            @endforeach
        </div>

        {{-- Grid de reseñas --}}
        @if($reviews->isEmpty())
            <div class="text-center py-5 text-muted">
                <i class="fas fa-star fa-3x mb-3 d-block"></i>
                No hay reseñas disponibles.
            </div>
        @else
        <div class="row g-4">
            @foreach($reviews as $review)
            @php
                $stars   = $review->star_rating->value();
                $comment = $review->comment ?? '';
            @endphp
            <div class="col-lg-4 col-md-6">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body p-4">

                        {{-- Reviewer header --}}
                        <div class="d-flex align-items-center mb-3">
                            @if($review->reviewer_photo_url)
                                <img alt="" src="{{ $review->reviewer_photo_url }}"
                                     alt="{{ $review->reviewer_name }}"
                                     class="rounded-circle me-3"
                                     width="48" height="48"
                                     onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                                <div class="rounded-circle me-3 d-none align-items-center justify-content-center text-white fw-bold flex-shrink-0"
                                     style="width:48px;height:48px;background:#90bb13;font-size:1.1rem;">
                                    {{ strtoupper(substr($review->reviewer_name ?? 'A', 0, 1)) }}
                                </div>
                            @else
                                <div class="rounded-circle me-3 d-flex align-items-center justify-content-center text-white fw-bold flex-shrink-0"
                                     style="width:48px;height:48px;background:#90bb13;font-size:1.1rem;">
                                    {{ strtoupper(substr($review->reviewer_name ?? 'A', 0, 1)) }}
                                </div>
                            @endif

                            <div class="flex-grow-1 min-w-0">
                                <div class="fw-semibold text-truncate">{{ $review->reviewer_name ?? 'Usuario' }}</div>
                                <div class="text-muted small">{{ $review->review_time?->diffForHumans() }}</div>
                            </div>

                            {{-- Google logo --}}
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" viewBox="0 0 488 512" class="ms-2 flex-shrink-0" aria-hidden="true">
                                <path fill="#4285F4" d="M488 261.8C488 403.3 391.1 504 248 504 110.8 504 0 393.2 0 256S110.8 8 248 8c66.8 0 123 24.5 166.3 64.9l-67.5 64.9C258.5 52.6 94.3 116.6 94.3 256c0 86.5 69.1 156.6 153.7 156.6 98.2 0 135-70.4 140.8-106.9H248v-85.3h236.1c2.3 12.7 3.9 24.9 3.9 41.4z"/>
                            </svg>
                        </div>

                        {{-- Stars --}}
                        <div class="mb-2">
                            @for($i = 1; $i <= 5; $i++)
                                <i class="fas fa-star {{ $i <= $stars ? 'text-warning' : 'text-muted' }}" style="font-size:.85rem;"></i>
                            @endfor
                        </div>

                        {{-- Comment --}}
                        @if($comment)
                        <p class="text-muted small mb-0"
                           style="line-height:1.6;overflow:hidden;display:-webkit-box;-webkit-line-clamp:5;-webkit-box-orient:vertical;">
                            {{ $comment }}
                        </p>
                        @endif

                    </div>
                </div>
            </div>
            @endforeach
        </div>

        {{-- Paginación --}}
        @if($reviews->hasPages())
        <div class="d-flex justify-content-center mt-5">
            {{ $reviews->links() }}
        </div>
        @endif
        @endif

    </div>
</section>

@endsection
