@php
$locale = app()->getLocale();
$i18n = [
    'es' => [
        'eyebrow'     => 'Opiniones de clientes',
        'title'       => 'Lo que dicen nuestros clientes',
        'subtitle'    => 'Más de 1.000 instalaciones en Alcobaça, Leiria y Marinha Grande. Valoración media de 4.9/5 en Google Maps.',
        'count'       => 'reseñas de Google',
        'maps_cta'    => 'Ver en Google Maps',
        'see_all'     => 'Ver todas las opiniones',
        'reviews_url' => '/opiniones',
    ],
    'pt' => [
        'eyebrow'     => 'Opiniões de clientes',
        'title'       => 'O que dizem os nossos clientes',
        'subtitle'    => 'Mais de 1.000 instalações em Alcobaça, Leiria e Marinha Grande. Avaliação média de 4,9/5 no Google Maps.',
        'count'       => 'opiniões do Google',
        'maps_cta'    => 'Ver no Google Maps',
        'see_all'     => 'Ver todas as opiniões',
        'reviews_url' => '/opinioes',
    ],
    'en' => [
        'eyebrow'     => 'Customer reviews',
        'title'       => 'What our customers say',
        'subtitle'    => 'Over 1,000 installations in Alcobaça, Leiria and Marinha Grande. Average rating 4.9/5 on Google Maps.',
        'count'       => 'Google reviews',
        'maps_cta'    => 'View on Google Maps',
        'see_all'     => 'View all reviews',
        'reviews_url' => '/reviews',
    ],
    'fr' => [
        'eyebrow'     => 'Avis des clients',
        'title'       => 'Ce que disent nos clients',
        'subtitle'    => 'Plus de 1 000 installations à Alcobaça, Leiria et Marinha Grande. Note moyenne de 4,9/5 sur Google Maps.',
        'count'       => 'avis Google',
        'maps_cta'    => 'Voir sur Google Maps',
        'see_all'     => 'Voir tous les avis',
        'reviews_url' => '/avis',
    ],
][$locale] ?? [
    'eyebrow'     => 'Opiniones de clientes',
    'title'       => 'Lo que dicen nuestros clientes',
    'subtitle'    => 'Más de 1.000 instalaciones en Alcobaça, Leiria y Marinha Grande. Valoración media de 4.9/5 en Google Maps.',
    'count'       => 'reseñas de Google',
    'maps_cta'    => 'Ver en Google Maps',
    'see_all'     => 'Ver todas las opiniones',
    'reviews_url' => '/opiniones',
];

$mapsUrl = theme_option('google_maps_url') ?: 'https://www.google.com/maps/place/Caixilharia+PVC+Blanco/@39.5201241,-9.0202247,17z';

if (!isset($avgRating) || !isset($totalCount)) {
    [$totalCount, $avgRating] = app(\Modules\Reviews\Http\Controllers\PublicReviewController::class)->getAggregateStats();
}
@endphp

<div class="testimonial3-section-area sp1" data-reviews-section>
    <div class="container">
        <div class="row">
            <div class="col-lg-8 m-auto">
                <div class="testimonial3-header heading6">
                    <h5>{{ $i18n['eyebrow'] }}</h5>
                    <h2 class="tg-element-title">{{ $i18n['title'] }}</h2>
                    <p>{{ $i18n['subtitle'] }}</p>
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
                        <span class="ht-count"><span class="ht-count-num">{{ $totalCount }}</span> {{ $i18n['count'] }}</span>
                    </div>
                    <a href="{{ $mapsUrl }}" target="_blank" rel="noopener" class="ht-google-btn">
                        <span class="ht-g-icon">G</span>
                        {{ $i18n['maps_cta'] }}
                    </a>
                </div>
            </div>
        </div>

        <div class="row g-4"
             data-reviews-endpoint="/reviews/cards"
             data-limit="6"
             data-min-rating="5"
             data-scope="home">
        </div>

        <div class="row mt-5">
            <div class="col-12 text-center">
                <a href="{{ $i18n['reviews_url'] }}" class="header-btn3 btn2">
                    {{ $i18n['see_all'] }} <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>
</div>
