@extends('layouts.theme')

@section('title', 'Tu opinion - Ticket '.$ticket->ticket_number)

@section('content')
@php($csatReasons = (array) config('helpdesktickets.csat_reasons', []))
@php($csatThreshold = (int) config('helpdesktickets.csat_reason_threshold', 3))
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
                    <form method="POST" action="{{ $submitUrl }}" data-reason-threshold="{{ $csatThreshold }}">
                        @csrf
                        <div class="mb-4">
                            @for($i = 1; $i <= 5; $i++)
                                <input type="radio" name="rating" value="{{ $i }}" id="r{{ $i }}" class="d-none" required>
                                <label for="r{{ $i }}" class="rating-star mx-1" data-rating="{{ $i }}">
                                    <i class="far fa-star"></i>
                                </label>
                            @endfor
                        </div>
                        @if($csatReasons)
                            <div class="mb-4 d-none text-start" id="reason-block">
                                <label class="form-label small text-muted">¿Qué podríamos mejorar?</label>
                                <select name="reason" class="form-select">
                                    <option value="">Selecciona un motivo...</option>
                                    @foreach($csatReasons as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
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
    const form = document.querySelector('form[data-reason-threshold]');
    const threshold = form ? parseInt(form.dataset.reasonThreshold, 10) : 3;
    const reasonBlock = document.getElementById('reason-block');
    stars.forEach((star, idx) => {
        star.addEventListener('click', () => {
            const rating = idx + 1;
            const radio = document.getElementById('r' + rating);
            radio.checked = true;
            stars.forEach((s, i) => {
                s.classList.toggle('active', i <= idx);
                s.querySelector('i').className = i <= idx ? 'fas fa-star' : 'far fa-star';
            });
            // Solo pedimos el motivo cuando la nota es baja (<= umbral).
            if (reasonBlock) {
                reasonBlock.classList.toggle('d-none', rating > threshold);
            }
        });
    });
})();
</script>
@endpush
@endsection
