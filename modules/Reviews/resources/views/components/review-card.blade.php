@props(['review'])

<div {{ $attributes->merge(['class' => 'card mb-3 review-card']) }}>
    <div class="card-body">
        <div class="d-flex align-items-start gap-3">
            <div class="flex-shrink-0">
                <img src="{{ $review->reviewer_photo_url ?? asset('images/default-avatar.png') }}"
                     class="rounded-circle"
                     width="50"
                     height="50"
                     alt="{{ $review->reviewer_name }}">
            </div>
            <div class="flex-grow-1">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <h6 class="mb-1">{{ $review->reviewer_name }}</h6>
                        <div class="mb-1">
                            <x-reviews::rating-stars :rating="$review->star_rating" />
                        </div>
                        <small class="text-muted">
                            <i class="far fa-clock"></i> {{ $review->review_time->format('d/m/Y H:i') }}
                        </small>
                    </div>
                    <div class="text-end">
                        @if($review->moderation?->is_featured)
                            <span class="badge bg-warning-subtle text-warning mb-1">
                                <i class="fas fa-star"></i> Destacada
                            </span>
                        @endif
                        @if(!$review->moderation?->is_visible)
                            <span class="badge bg-secondary-subtle text-secondary mb-1">
                                <i class="fas fa-eye-slash"></i> Oculta
                            </span>
                        @endif
                    </div>
                </div>

                @if($review->comment)
                    <p class="mb-2">{{ Str::limit($review->comment, 200) }}</p>
                @else
                    <p class="mb-2 text-muted fst-italic">Sin comentario escrito</p>
                @endif

                <div class="d-flex justify-content-between align-items-center mt-3">
                    <small class="text-muted">
                        <i class="fas fa-map-marker-alt"></i> {{ $review->location->name }}
                    </small>
                    <a href="{{ route('reviews.show', $review->id) }}" class="btn btn-sm btn-primary">
                        Ver detalles
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .review-card {
        transition: box-shadow 0.3s;
    }

    .review-card:hover {
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    }
</style>
@endpush
