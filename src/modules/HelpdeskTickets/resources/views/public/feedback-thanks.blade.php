@extends('layouts.theme')

@section('title', 'Gracias - Ticket '.$ticket->ticket_number)

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-6 text-center">
            <i class="fas fa-check-circle text-success" style="font-size: 5rem"></i>
            <h2 class="fw-bold mt-4">¡Gracias por tu feedback!</h2>
            <p class="text-muted">Tu opinion ayuda a mejorar nuestro servicio.</p>

            <div class="card bg-light-secondary mt-4">
                <div class="card-body">
                    <div class="mb-2">
                        @for($i = 1; $i <= 5; $i++)
                            <i class="{{ $i <= $ticket->rating ? 'fas text-warning' : 'far text-muted' }} fa-star fa-2x"></i>
                        @endfor
                    </div>
                    <small class="text-muted">Tu valoracion: {{ $ticket->rating }} de 5</small>
                    @if($ticket->rating_comment)
                        <p class="mt-3 text-start small fst-italic">"{{ $ticket->rating_comment }}"</p>
                    @endif
                </div>
            </div>

            <p class="text-muted small mt-4">Ticket #{{ $ticket->ticket_number }}</p>
        </div>
    </div>
</div>
@endsection
