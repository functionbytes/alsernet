@php
$locale = app()->getLocale();
$fallback = ['es' => 'Cliente', 'pt' => 'Cliente', 'en' => 'Customer', 'fr' => 'Client'][$locale] ?? 'Cliente';
$verified = ['es' => 'Cliente verificado', 'pt' => 'Cliente verificado', 'en' => 'Verified customer', 'fr' => 'Client vérifié'][$locale] ?? 'Cliente verificado';
@endphp

@foreach ($reviews as $r)
    @php
        $text   = $r->translations->firstWhere('locale_code', strtoupper($locale))?->translated_text ?? $r->comment ?? '';
        $name   = $r->reviewer_name ?? $fallback;
        $rating = $r->star_rating->value();
        $time   = $r->review_time?->diffForHumans() ?? $verified;
    @endphp
    <div class="swiper-slide">
        <div class="testimonial-slide-inner">
            <div class="testimonial-slide-content">
                <div class="stars">
                    @for($i = 1; $i <= $rating; $i++)
                        <i class="fa-solid fa-star"></i>
                    @endfor
                </div>
                <p class="quote">&ldquo;{{ Str::limit($text, 220) }}&rdquo;</p>
                <div class="client-name">{{ $name }}</div>
                <div class="client-info">{{ $time }}</div>
            </div>
        </div>
    </div>
@endforeach
