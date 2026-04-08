<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: sans-serif; margin: 0; padding: 10px; background: transparent; }
        .review-card { border: 1px solid #e0e0e0; border-radius: 8px; padding: 15px; margin-bottom: 10px; }
        .stars { color: #FEC90F; font-size: 1.1em; }
        .author { font-weight: bold; margin-bottom: 4px; }
        .date { color: #888; font-size: 0.85em; }
        .text { margin-top: 8px; line-height: 1.5; }
        .no-reviews { text-align: center; color: #888; padding: 20px 0; }
    </style>
</head>
<body>

@forelse($reviews as $review)
    <div class="review-card">
        <div class="stars">
            @for($i = 1; $i <= 5; $i++)
                {{ $i <= $review->star_rating->value() ? '★' : '☆' }}
            @endfor
        </div>
        <div class="author">{{ $review->reviewer_name }}</div>
        <div class="date">{{ $review->review_time?->format('d/m/Y') }}</div>
        @if($review->comment)
            <div class="text">{{ Str::limit($review->comment, 200) }}</div>
        @endif
    </div>
@empty
    <p class="no-reviews">No hay reseñas disponibles.</p>
@endforelse

</body>
</html>
