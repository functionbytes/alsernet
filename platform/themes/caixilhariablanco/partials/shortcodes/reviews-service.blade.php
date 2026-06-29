@php
    if (! class_exists('Modules\Reviews\Models\Review') || ! app('modules')->isEnabled('Reviews')) { return; }

    $limit    = (int) ($limit ?? 6);
    $tag      = $tag ?? null;
    $title    = $title ?? 'Opiniones de nuestros clientes';
    $subtitle = $subtitle ?? '1.000+ clientes satisfechos · Valoración 4.9/5 en Google';

    $baseQuery = fn() => \Modules\Reviews\Models\Review::query()
        ->whereIn('star_rating', ['FOUR', 'FIVE'])
        ->where(function ($q) {
            $q->where('comment', '!=', '')->whereNotNull('comment');
        })
        ->where(function ($q) {
            $q->whereHas('moderation', fn ($m) => $m->where('is_visible', true))
              ->orWhereDoesntHave('moderation');
        })
        ->whereRaw('CHAR_LENGTH(COALESCE(comment,"")) > 60')
        ->with('moderation');

    // Filter by tag if provided
    if ($tag) {
        $reviews = $baseQuery()
            ->whereHas('moderation', fn ($m) => $m->whereJsonContains('tags', $tag))
            ->inRandomOrder()
            ->limit($limit)
            ->get();

        // Fallback: not enough tagged reviews → use all
        if ($reviews->count() < 3) {
            $reviews = $baseQuery()->inRandomOrder()->limit($limit)->get();
        }
    } else {
        $reviews = $baseQuery()->inRandomOrder()->limit($limit)->get();
    }

    if ($reviews->isEmpty()) { return; }

    $avatarColor = '#90bb13';
@endphp

<div class="testimonial3-section-area sp1">
    <div class="container">
        <div class="row mb-4">
            <div class="col-lg-8 m-auto text-center wow fadeInUp">
                <div class="testimonial3-header heading6">
                    <h5>Testimonios reales</h5>
                    <h2>{{ $title }}</h2>
                    <p>{{ $subtitle }}</p>
                </div>
            </div>
        </div>
        <div class="row g-4">
            @foreach($reviews as $i => $review)
            @php
                $name     = $review->reviewer_name ?? 'Cliente';
                $words    = explode(' ', trim($name));
                $initials = strtoupper(substr($words[0], 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ''));
                $text     = $review->translated_comment ?: $review->comment;
                $rating   = $review->star_rating instanceof \Modules\Reviews\Enums\ReviewRating
                    ? $review->star_rating->toNumeric()
                    : (int) $review->star_rating;
                $ctId     = 'srv-ct-' . $i . '-' . Str::random(8);
                $dateStr  = $review->review_time
                    ? \Carbon\Carbon::parse($review->review_time)->diffForHumans()
                    : '';
            @endphp
            <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="{{ $i * 0.1 }}s">
                <div class="testimonial-inner-boxarea">
                    <div class="img1">
                        <div class="avatar-circle" style="background:{{ $avatarColor }};">{{ $initials }}</div>
                    </div>
                    <div class="content-area">
                        <ul>
                            @for($s = 1; $s <= 5; $s++)
                                <li><i class="fa-solid fa-star{{ $s <= $rating ? '' : '-half-stroke' }}"></i></li>
                            @endfor
                        </ul>
                        <p id="{{ $ctId }}">"{{ $text }}"</p>
                        @if(strlen($text) > 200)
                        <button class="expand-btn" onclick="toggleReviewText(this,'{{ $ctId }}')">Leer más</button>
                        @endif
                        <div class="text">
                            <a href="#">{{ $name }}</a>
                            <p>{{ $dateStr }}<span class="card-google-icon"><i class="fa-brands fa-google"></i></span></p>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>

<script>
function toggleReviewText(btn, id) {
    var el = document.getElementById(id);
    if (!el) return;
    var full = el.getAttribute('data-full') || el.textContent;
    var short = el.getAttribute('data-short');
    if (!el.getAttribute('data-full')) {
        el.setAttribute('data-full', full);
        el.setAttribute('data-short', full.substring(0, 200) + '…');
        el.textContent = el.getAttribute('data-short');
    }
    if (btn.textContent === 'Leer más') {
        el.textContent = full;
        btn.textContent = 'Leer menos';
    } else {
        el.textContent = el.getAttribute('data-short');
        btn.textContent = 'Leer más';
    }
}
</script>
