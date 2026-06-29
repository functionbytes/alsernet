@php
$locale = app()->getLocale();

$i18n = [
    'es' => [
        'eyebrow'     => 'Opiniones de clientes',
        'title_pre'   => 'Lo que dicen sobre',
        'suffix'      => 'reseñas verificadas',
        'see_all'     => 'Ver todas las opiniones',
        'reviews_url' => '/opiniones',
    ],
    'pt' => [
        'eyebrow'     => 'Opiniões de clientes',
        'title_pre'   => 'O que dizem sobre',
        'suffix'      => 'opiniões verificadas',
        'see_all'     => 'Ver todas as opiniões',
        'reviews_url' => '/opinioes',
    ],
    'en' => [
        'eyebrow'     => 'Customer reviews',
        'title_pre'   => 'What customers say about',
        'suffix'      => 'verified reviews',
        'see_all'     => 'View all reviews',
        'reviews_url' => '/reviews',
    ],
    'fr' => [
        'eyebrow'     => 'Avis des clients',
        'title_pre'   => 'Ce que disent les clients sur',
        'suffix'      => 'avis vérifiés',
        'see_all'     => 'Voir tous les avis',
        'reviews_url' => '/avis',
    ],
][$locale] ?? [
    'eyebrow'     => 'Opiniones de clientes',
    'title_pre'   => 'Lo que dicen sobre',
    'suffix'      => 'reseñas verificadas',
    'see_all'     => 'Ver todas las opiniones',
    'reviews_url' => '/opiniones',
];

// Localized tag labels (keys match the `tag` attribute in the shortcode)
$tagLabelMap = [
    'ventanas-pvc'       => ['es' => 'nuestras ventanas',     'pt' => 'as nossas janelas',     'en' => 'our windows',       'fr' => 'nos fenêtres'],
    'estores-y-persianas'=> ['es' => 'nuestros estores',      'pt' => 'os nossos estores',     'en' => 'our blinds',        'fr' => 'nos stores'],
    'mosquiteras'        => ['es' => 'nuestras mosquiteras',  'pt' => 'os nossos mosquiteiros', 'en' => 'our mosquito nets', 'fr' => 'nos moustiquaires'],
    'barandillas-de-vidrio' => ['es' => 'nuestras barandillas','pt' => 'os nossos guarda-corpos','en' => 'our railings',     'fr' => 'nos garde-corps'],
];
$genericLabel = ['es' => 'nuestros servicios', 'pt' => 'os nossos serviços', 'en' => 'our services', 'fr' => 'nos services'][$locale] ?? 'nuestros servicios';
$resolvedLabel = $tagLabelMap[$tag ?? ''][$locale]
    ?? $tagLabelMap[$tag ?? '']['es']
    ?? $genericLabel;

if (!isset($avgRating) || !isset($totalCount)) {
    $visible = fn ($q) => $q
        ->whereHas('moderation', fn ($m) => $m->where('is_visible', true))
        ->orWhereDoesntHave('moderation');
    $statsQuery = \Modules\Reviews\Models\Review::query()->where($visible);
    if (!empty($tag)) {
        $statsQuery->whereHas('moderation', fn ($m) => $m->whereJsonContains('tags', $tag));
    }
    $statsRow = $statsQuery->selectRaw("COUNT(*) as total, AVG(CASE star_rating
        WHEN 'ONE' THEN 1 WHEN 'TWO' THEN 2 WHEN 'THREE' THEN 3
        WHEN 'FOUR' THEN 4 WHEN 'FIVE' THEN 5 ELSE NULL END) as avg_rating")->first();
    $avgRating  = (float) ($statsRow->avg_rating ?? 0);
    $totalCount = (int) ($statsRow->total ?? 0);
}
@endphp

<div class="service-reviews-section sp1">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 m-auto text-center mb-5">
                <div class="testimonial3-header heading6">
                    <h5>{{ $i18n['eyebrow'] }}</h5>
                    <h2 class="tg-element-title">{{ $i18n['title_pre'] }} {{ $resolvedLabel }}</h2>
                    <div class="sr-rating-bar">
                        @for($i = 1; $i <= 5; $i++)
                            <i class="fa-solid fa-star{{ $i <= round($avgRating) ? '' : ' opacity-25' }}"></i>
                        @endfor
                        <span class="ms-2"><strong>{{ number_format($avgRating, 1) }}</strong> · <span class="sr-count-num">{{ $totalCount }}</span> {{ $i18n['suffix'] }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4"
             data-reviews-endpoint="/reviews/cards"
             data-limit="{{ $limit ?? 6 }}"
             data-min-rating="{{ $minRating ?? 4 }}"
             data-scope="service"
             @if(!empty($tag)) data-tag="{{ $tag }}" @endif>
        </div>

        <div class="row mt-5">
            <div class="col-12 text-center">
                <a href="{{ $i18n['reviews_url'] }}" class="header-btn3 btn2">
                    {{ $i18n['see_all'] }} <i class="fa-solid fa-arrow-right ms-1"></i>
                </a>
            </div>
        </div>
    </div>
</div>
