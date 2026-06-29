@php
    // This view is a child emitter: it outputs a self-contained HTML block
    // that the parent [testimonials] view collects and wraps.
    $author     = $attrs['author'] ?? '';
    $role       = $attrs['role'] ?? null;
    $avatar     = $attrs['avatar'] ?? null;
    $rating     = isset($attrs['rating']) ? min(5, max(0, (float) $attrs['rating'])) : null;
    $quote      = trim($content);
    $extraClass = $attrs['class'] ?? null;

    $fullStars  = $rating !== null ? (int) $rating : 0;
    $halfStar   = $rating !== null && ($rating - $fullStars) >= 0.5;
    $emptyStars = $rating !== null ? max(0, 5 - $fullStars - ($halfStar ? 1 : 0)) : 0;
@endphp

<div class="testimonial-child{{ $extraClass ? ' '.$extraClass : '' }}"
     data-testimonial-item="1">
    @if ($quote)
        <blockquote>{{ $quote }}</blockquote>
    @endif

    <div class="testimonial-info">
        @if ($avatar)
            <figure class="testimonial-author-thumbnail">
                <img src="{{ htmlspecialchars($avatar) }}"
                     alt="{{ e($author) }}"
                     width="50" height="50"
                     loading="lazy">
            </figure>
        @endif

        <cite>
            {{ $author }}
            @if ($role)
                <span>{{ $role }}</span>
            @endif
        </cite>

        @if ($rating !== null)
            <div class="testimonial-rating" aria-label="{{ __('shortcode::shortcode.testimonial.rating', ['n' => $rating]) }}">
                @for ($i = 0; $i < $fullStars; $i++)
                    <i class="fas fa-star" aria-hidden="true"></i>
                @endfor
                @if ($halfStar)
                    <i class="fas fa-star-half-alt" aria-hidden="true"></i>
                @endif
                @for ($i = 0; $i < $emptyStars; $i++)
                    <i class="far fa-star" aria-hidden="true"></i>
                @endfor
            </div>
        @endif
    </div>
</div>
