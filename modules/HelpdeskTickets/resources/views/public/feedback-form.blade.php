@extends('layouts.theme')

@section('title', 'Tu opinion - Ticket '.$ticket->ticket_number)

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-6">
            <div class="card shadow-sm">
                <div class="card-body p-5 text-center">
                    <i class="fas fa-comment-dots text-primary mb-3" style="font-size: 3rem"></i>
                    <h4 class="fw-bold mb-1">¿Cómo calificarias nuestra atencion?</h4>
                    <p class="text-muted small mb-4">Ticket #{{ $ticket->ticket_number }}</p>

                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    {{-- $submitUrl es una URL firmada (la ruta submit exige middleware signed) --}}
                    <form method="POST" action="{{ $submitUrl }}">
                        @csrf
                        <div class="mb-4">
                            @for($i = 1; $i <= 5; $i++)
                                <input type="radio" name="rating" value="{{ $i }}" id="r{{ $i }}" class="d-none" required>
                                <label for="r{{ $i }}" class="rating-star mx-1" data-rating="{{ $i }}">
                                    <i class="far fa-star"></i>
                                </label>
                            @endfor
                        </div>
                        <div class="mb-4">
                            <textarea name="comment" class="form-control" rows="4"
                                placeholder="Cuentanos mas (opcional)..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Enviar feedback</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.rating-star { font-size: 2.5rem; cursor: pointer; color: #e4e5e9; transition: color 0.2s; }
.rating-star.active, .rating-star:hover { color: #ffc107; }
.rating-star.active i, .rating-star:hover i { font-weight: 900; }
</style>
@endpush

@push('scripts')
<script>
(function() {
    const stars = document.querySelectorAll('.rating-star');
    stars.forEach((star, idx) => {
        star.addEventListener('click', () => {
            const radio = document.getElementById('r' + (idx + 1));
            radio.checked = true;
            stars.forEach((s, i) => {
                s.classList.toggle('active', i <= idx);
                s.querySelector('i').className = i <= idx ? 'fas fa-star' : 'far fa-star';
            });
        });
    });
})();
</script>
@endpush
@endsection
