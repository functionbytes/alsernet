@php
if (!function_exists('rcInitials')) {
    function rcInitials(string $name): string {
        $words = array_filter(explode(' ', trim($name)));
        $parts = array_slice(array_values($words), 0, 2);
        return strtoupper(implode('', array_map(fn($w) => $w[0] ?? '', $parts)));
    }
}

$locale = app()->getLocale();
$i18n = [
    'es' => ['fallback_name' => 'Cliente', 'verified' => 'Cliente verificado'],
    'pt' => ['fallback_name' => 'Cliente', 'verified' => 'Cliente verificado'],
    'en' => ['fallback_name' => 'Customer', 'verified' => 'Verified customer'],
    'fr' => ['fallback_name' => 'Client', 'verified' => 'Client vérifié'],
][$locale] ?? ['fallback_name' => 'Cliente', 'verified' => 'Cliente verificado'];
@endphp

@foreach ($reviews as $r)
    @php
        $text = $r->translations->firstWhere('locale_code', strtoupper($locale))?->translated_text
            ?? $r->comment
            ?? '';
        $name = $r->reviewer_name ?? $i18n['fallback_name'];
        $initials = rcInitials($name);
        $rating = $r->star_rating->value();
    @endphp
    <div class="col-lg-4 col-md-6">
        <div class="home-testimonial-card">
            <div class="ht-avatar">{{ $initials }}</div>
            <ul class="ht-stars">
                @for ($i = 1; $i <= $rating; $i++)
                    <li><i class="fa-solid fa-star"></i></li>
                @endfor
            </ul>
            <p class="ht-text">&ldquo;{{ $text }}&rdquo;</p>
            <span class="ht-name">{{ $name }}</span>
            <span class="ht-meta">
                {{ $r->review_time?->diffForHumans() ?? $i18n['verified'] }}
                <i class="fa-brands fa-google ms-1"></i>
            </span>
        </div>
    </div>
@endforeach
