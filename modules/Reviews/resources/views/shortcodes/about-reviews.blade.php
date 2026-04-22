@php
$locale = app()->getLocale();
$i18n = [
    'es' => [
        'eyebrow'    => 'Testimonios',
        'title'      => 'Opiniones reales de nuestros clientes',
        'subtitle'   => 'Desde viviendas particulares hasta proyectos comerciales, nuestros clientes valoran la calidad, puntualidad y profesionalismo.',
        'see_all'    => 'Ver todas las opiniones',
        'reviews_url'=> '/opiniones',
    ],
    'pt' => [
        'eyebrow'    => 'Testemunhos',
        'title'      => 'Opiniões reais dos nossos clientes',
        'subtitle'   => 'De habitações particulares a projetos comerciais, os nossos clientes valorizam a qualidade, pontualidade e profissionalismo.',
        'see_all'    => 'Ver todas as opiniões',
        'reviews_url'=> '/opinioes',
    ],
    'en' => [
        'eyebrow'    => 'Testimonials',
        'title'      => 'Real reviews from our customers',
        'subtitle'   => 'From private homes to commercial projects, our customers value quality, punctuality and professionalism.',
        'see_all'    => 'View all reviews',
        'reviews_url'=> '/reviews',
    ],
    'fr' => [
        'eyebrow'    => 'Témoignages',
        'title'      => 'Avis réels de nos clients',
        'subtitle'   => 'Des habitations particulières aux projets commerciaux, nos clients apprécient la qualité, la ponctualité et le professionnalisme.',
        'see_all'    => 'Voir tous les avis',
        'reviews_url'=> '/avis',
    ],
][$locale] ?? [
    'eyebrow'    => 'Testimonios',
    'title'      => 'Opiniones reales de nuestros clientes',
    'subtitle'   => 'Desde viviendas particulares hasta proyectos comerciales, nuestros clientes valoran la calidad, puntualidad y profesionalismo.',
    'see_all'    => 'Ver todas las opiniones',
    'reviews_url'=> '/opiniones',
];
@endphp

<div class="testimonial2-section">
    <div class="container">
        <div class="row">
            <div class="col-lg-4">
                <div class="testimonial-header wow fadeInUp">
                    <h5>{{ $i18n['eyebrow'] }}</h5>
                    <h2>{{ $i18n['title'] }}</h2>
                    <p>{{ $i18n['subtitle'] }}</p>
                    <a href="{{ $i18n['reviews_url'] }}" class="btn-default">
                        {{ $i18n['see_all'] }} <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>
            </div>
            <div class="col-lg-8 wow fadeInUp" data-wow-delay="0.2s">
                <div class="about-testimonial-area">
                    <div class="swiper testimonialSwiper">
                        <div class="swiper-wrapper"
                             data-reviews-endpoint="/reviews/cards"
                             data-limit="8"
                             data-min-rating="4"
                             data-scope="about">
                        </div>
                    </div>
                    <div class="testimonial-arrows">
                        <button class="testimonial-prev"><i class="fa-solid fa-arrow-left"></i></button>
                        <button class="testimonial-next"><i class="fa-solid fa-arrow-right"></i></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
