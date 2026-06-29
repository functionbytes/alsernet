@php
    if (! class_exists('Modules\Reviews\Models\Review') || ! app('modules')->isEnabled('Reviews')) {
        return;
    }

    $limit = (int) ($limit ?? 6);
    $showRating = filter_var($show_rating ?? true, FILTER_VALIDATE_BOOLEAN);
    $reviews_section_title = $reviews_section_title ?? null;

    $reviews = $reviews_override ?? \Modules\Reviews\Models\Review::query()
        ->where(function ($q) {
            $q->whereHas('moderation', fn ($m) => $m->where('is_visible', true))
              ->orWhereDoesntHave('moderation');
        })
        ->with('moderation')
        ->orderByDesc('star_rating')
        ->orderByDesc('review_time')
        ->limit($limit)
        ->get();

    $avgRating = $reviews->avg(fn ($r) => $r->star_rating instanceof \Modules\Reviews\Enums\ReviewRating
        ? $r->star_rating->value()
        : (int) $r->star_rating
    ) ?? 0;
@endphp

@if($reviews->isNotEmpty())
<section class="reviews-widget py-5">
    <div class="container">

        @if($reviews_section_title)
        <div class="row justify-content-center mb-4">
            <div class="col-lg-9 text-center heading6">
                <h2 class="tg-element-title wow fadeInUp">{{ $reviews_section_title }}</h2>
            </div>
        </div>
        @endif

        @if($showRating)
        <div class="text-center mb-4">
            <div class="d-flex justify-content-center align-items-center gap-2">
                @for($i = 1; $i <= 5; $i++)
                    <i class="fas fa-star {{ $i <= round($avgRating) ? 'text-warning' : 'text-muted' }}"
                      ></i>
                @endfor
                <span class="fw-bold fs-5 ms-2">{{ number_format($avgRating, 1) }}</span>
                <span class="text-muted">/ 5 ({{ $reviews->count() }} reseñas)</span>
            </div>
        </div>
        @endif

        <div class="row g-4">
            @foreach($reviews as $review)
            @php
                $stars = $review->star_rating instanceof \Modules\Reviews\Enums\ReviewRating
                    ? $review->star_rating->value()
                    : (int) $review->star_rating;
                $comment = $review->comment ?? '';
                $initial = strtoupper(substr($review->reviewer_name ?? 'A', 0, 1));
            @endphp
            <div class="col-lg-4 col-md-6">
                <div class="h-100 p-4 bg-white rounded-3 shadow-sm border border-light">

                    {{-- Header: avatar + nombre + fecha --}}
                    <div class="d-flex align-items-center gap-3 mb-3">
                        @if($review->reviewer_photo_url)
                            <img alt="" src="{{ $review->reviewer_photo_url }}"
                                 alt="{{ $review->reviewer_name }}"
                                 class="rounded-circle flex-shrink-0"
                                 width="48" height="48"
                                 loading="lazy"
                                 onerror="this.replaceWith(this.nextElementSibling)">
                            <div class="rounded-circle flex-shrink-0 d-none d-flex align-items-center justify-content-center fw-bold review-avatar">
                                {{ $initial }}
                            </div>
                        @else
                            <div class="rounded-circle flex-shrink-0 d-flex align-items-center justify-content-center fw-bold review-avatar">
                                {{ $initial }}
                            </div>
                        @endif

                        <div class="flex-grow-1 min-w-0">
                            <div class="fw-semibold text-truncate">{{ $review->reviewer_name ?? 'Usuario' }}</div>
                            @if($review->review_time)
                                <div class="text-muted small">{{ $review->review_time->diffForHumans() }}</div>
                            @endif
                        </div>

                        <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 48 48'%3E%3Cpath fill='%234285F4' d='M45.12 24.5c0-1.56-.14-3.06-.4-4.5H24v8.51h11.84c-.51 2.75-2.06 5.08-4.39 6.64v5.52h7.11c4.16-3.83 6.56-9.47 6.56-16.17z'/%3E%3Cpath fill='%2334A853' d='M24 46c5.94 0 10.92-1.97 14.56-5.33l-7.11-5.52c-1.97 1.32-4.49 2.1-7.45 2.1-5.73 0-10.58-3.87-12.31-9.07H4.34v5.7C7.96 41.07 15.4 46 24 46z'/%3E%3Cpath fill='%23FBBC05' d='M11.69 28.18C11.25 26.86 11 25.45 11 24s.25-2.86.69-4.18v-5.7H4.34C2.85 17.09 2 20.45 2 24c0 3.55.85 6.91 2.34 9.88l7.35-5.7z'/%3E%3Cpath fill='%23EA4335' d='M24 10.75c3.23 0 6.13 1.11 8.41 3.29l6.31-6.31C34.91 4.18 29.93 2 24 2 15.4 2 7.96 6.93 4.34 14.12l7.35 5.7c1.73-5.2 6.58-9.07 12.31-9.07z'/%3E%3C/svg%3E"
                             alt="Google" width="20" height="20" class="flex-shrink-0">
                    </div>

                    {{-- Estrellas --}}
                    <div class="mb-2">
                        @for($i = 1; $i <= 5; $i++)
                            <i class="fas fa-star {{ $i <= $stars ? 'text-warning' : 'text-muted' }} fs-8"></i>
                        @endfor
                    </div>

                    {{-- Comentario --}}
                    @if($comment)
                        <p class="text-muted small mb-0 line-clamp-4">
                            {{ $comment }}
                        </p>
                    @endif

                </div>
            </div>
            @endforeach
        </div>

    </div>
</section>

{{-- Schema.org AggregateRating --}}
@if($avgRating > 0)
@php
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'LocalBusiness',
        'name' => theme_option('site_title', config('app.name')),
        'url' => url('/'),
    ];

    if (theme_option('phone')) {
        $schema['telephone'] = theme_option('phone');
    }
    if (theme_option('address')) {
        $schema['address'] = [
            '@type' => 'PostalAddress',
            'streetAddress' => theme_option('address'),
        ];
    }
    if (theme_option('opening_hours')) {
        $schema['openingHours'] = theme_option('opening_hours');
    }

    $schema['aggregateRating'] = [
        '@type' => 'AggregateRating',
        'ratingValue' => number_format($avgRating, 1),
        'reviewCount' => $reviews->count(),
        'bestRating' => '5',
        'worstRating' => '1',
    ];
@endphp
<script type="application/ld+json">
{!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>
@endif

@endif
