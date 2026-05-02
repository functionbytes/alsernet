@extends('layouts.theme')

@section('title', 'Valora tu atención - ' . config('app.name'))

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-sm-10 col-md-8 col-lg-6 csat-wrapper">

            <div class="text-center mb-4">
                <span class="fw-bold fs-5">{{ config('app.name') }}</span>
            </div>

            <div class="card shadow-sm">
                <div class="card-body p-4 p-md-5 text-center">
                    <i class="fas fa-star-half-stroke mb-3 csat-icon-header"></i>
                    <h5 class="fw-bold mb-2">¿Cómo calificarías nuestra atención?</h5>

                    @if($rating->conversation)
                        <p class="text-muted small mb-4">
                            {{ $rating->conversation->subject ?? 'Conversación #' . $rating->conversation_id }}
                        </p>
                    @else
                        <p class="text-muted small mb-4">Conversación #{{ $rating->conversation_id }}</p>
                    @endif

                    <form method="POST" action="{{ route('helpdesk.csat.submit', $rating->survey_token) }}">
                        @csrf

                        <div class="mb-4" role="group" aria-label="Calificación de estrellas">
                            @for($i = 1; $i <= 5; $i++)
                                <input type="radio" name="rating" value="{{ $i }}"
                                    id="star{{ $i }}" class="d-none" required>
                                <label for="star{{ $i }}" class="csat-star mx-1"
                                    data-value="{{ $i }}" aria-label="{{ $i }} estrella{{ $i > 1 ? 's' : '' }}">
                                    <i class="far fa-star"></i>
                                </label>
                            @endfor
                        </div>

                        <div class="mb-4 text-start">
                            <label for="comment" class="form-label small text-muted">Comentario (opcional)</label>
                            <textarea name="comment" id="comment" class="form-control"
                                rows="3" maxlength="500"
                                placeholder="Cuéntanos más sobre tu experiencia..."></textarea>
                            <div class="d-flex justify-content-end mt-1">
                                <small class="text-muted"><span id="char-count">0</span>/500</small>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-csat-primary w-100">
                            Enviar valoración
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection

@push('css')
<style>
body { background-color: #f6f7f9; }

.csat-wrapper { max-width: 480px; }

.csat-icon-header {
    font-size: 2.5rem;
    color: #b10100;
}

.csat-star {
    font-size: 2.5rem;
    cursor: pointer;
    color: #d1d5db;
    transition: color 0.15s ease;
    display: inline-block;
    line-height: 1;
}

.csat-star.active,
.csat-star:hover {
    color: #f59e0b;
}

.csat-star.active i,
.csat-star:hover i {
    font-weight: 900;
}

.btn-csat-primary {
    background-color: #b10100;
    border-color: #b10100;
    color: #fff;
}

.btn-csat-primary:hover,
.btn-csat-primary:focus {
    background-color: #900000;
    border-color: #900000;
    color: #fff;
}
</style>
@endpush

@push('scripts')
<script>
$(function () {
    var $stars = $('.csat-star');
    var selected = 0;

    function paintStars(upTo) {
        $stars.each(function (i) {
            var active = i < upTo;
            $(this).toggleClass('active', active);
            $(this).find('i').attr('class', active ? 'fas fa-star' : 'far fa-star');
        });
    }

    $stars.on('click', function () {
        selected = parseInt($(this).data('value'));
        $('#star' + selected).prop('checked', true);
        paintStars(selected);
    });

    $stars.on('mouseenter', function () {
        paintStars(parseInt($(this).data('value')));
    });

    $stars.parent().on('mouseleave', function () {
        paintStars(selected);
    });

    $('#comment').on('input', function () {
        $('#char-count').text($(this).val().length);
    });
});
</script>
@endpush
