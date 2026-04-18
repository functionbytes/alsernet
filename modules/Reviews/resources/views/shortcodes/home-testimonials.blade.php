@php
if (!function_exists('htInitials')) {
    function htInitials(string $name): string {
        $words = array_filter(explode(' ', trim($name)));
        $parts = array_slice(array_values($words), 0, 2);
        return strtoupper(implode('', array_map(fn($w) => $w[0] ?? '', $parts)));
    }
}
@endphp

<div class="testimonial3-section-area sp1">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 m-auto">
                <div class="testimonial3-header heading6">
                    <h5>Opiniones de clientes</h5>
                    <h2 class="tg-element-title">Lo que dicen nuestros clientes</h2>
                    <p>Más de 1.000 instalaciones en Alcobaça, Leiria y Marinha Grande. Valoración media de 4.9/5 en Google Maps.</p>
                </div>
            </div>
        </div>

        <div class="row justify-content-center mb-5">
            <div class="col-12 col-md-11 col-lg-10">
                <div class="ht-stats-bar">
                    <div class="ht-stats-left">
                        <span class="ht-score">{{ number_format($avgRating, 1) }}</span>
                        <div class="ht-stars-row">
                            @for($i = 1; $i <= 5; $i++)
                                <i class="fa-solid fa-star{{ $i <= round($avgRating) ? '' : ' opacity-25' }}"></i>
                            @endfor
                        </div>
                        <span class="ht-count">{{ $totalCount }} reseñas de Google</span>
                    </div>
                    @php $mapsUrl = theme_option('google_maps_url') ?: 'https://www.google.com/maps/place/Caixilharia+PVC+Blanco/@39.5201241,-9.0202247,17z'; @endphp
                    <a href="{{ $mapsUrl }}" target="_blank" rel="noopener" class="ht-google-btn">
                        <span class="ht-g-icon">G</span>
                        Ver en Google Maps
                    </a>
                </div>
            </div>
        </div>

        <div class="row g-4">
            @foreach($reviews as $r)
                @php
                    $text = $r->translations->firstWhere('locale_code', strtoupper(app()->getLocale()))?->translated_text
                        ?? $r->comment
                        ?? '';
                    $name = $r->reviewer_name ?? 'Cliente';
                    $initials = htInitials($name);
                    $rating = $r->star_rating->value();
                @endphp
                <div class="col-lg-4 col-md-6">
                    <div class="home-testimonial-card">
                        <div class="ht-avatar">{{ $initials }}</div>
                        <ul class="ht-stars">
                            @for($i = 1; $i <= $rating; $i++)
                                <li><i class="fa-solid fa-star"></i></li>
                            @endfor
                        </ul>
                        <p class="ht-text">&ldquo;{{ $text }}&rdquo;</p>
                        <span class="ht-name">{{ $name }}</span>
                        <span class="ht-meta">
                            {{ $r->review_time?->diffForHumans() ?? 'Cliente verificado' }}
                            <i class="fa-brands fa-google ms-1"></i>
                        </span>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="row mt-5">
            <div class="col-12 text-center">
                <a href="/testimonios" class="header-btn3 btn2">
                    Ver todas las opiniones <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>
</div>
