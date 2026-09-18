{{-- Página pública sin login: layouts.theme asume Auth::user() (rompía con
     un 500 "Attempt to read property firstname on null" para cualquier
     cliente real, confirmado 30-ago-2026). layouts.auth no depende de sesión
     y de paso arregla el @push('styles') de abajo, que con layouts.theme no
     llegaba de forma fiable a <head> (ver reference_inbox_modal_push_styles_gotcha). --}}
@extends('layouts.auth')

@section('title', 'Ticket '.$ticket->ticket_number)

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-7">
            <div class="card shadow-sm">
                <div class="card-body p-4 p-md-5">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="badge bg-secondary">Ticket #{{ $ticket->ticket_number }}</span>
                        <span class="badge {{ $ticket->closed_at ? 'bg-dark' : 'bg-success' }}">{{ $ticket->status?->name ?? '—' }}</span>
                    </div>
                    <h4 class="fw-bold mt-2 mb-1">{{ $ticket->subject }}</h4>
                    <p class="text-muted small mb-4">Abierto el {{ $ticket->created_at?->format('d/m/Y H:i') }}</p>

                    {{-- Solo mensajes visibles para el cliente (sin notas
                         internas ni eventos internos) — mismo criterio que
                         el filtro "Solo cliente" del panel de agente. --}}
                    @if($thread->isEmpty())
                        <p class="text-muted text-center py-4">Todavía no hay mensajes en este ticket.</p>
                    @else
                        <div class="st-thread">
                            @foreach($thread as $item)
                                <div class="st-row {{ $item['from_agent'] ? 'st-row-agent' : 'st-row-customer' }}">
                                    <div class="st-bubble">
                                        <div class="st-bubble-meta">{{ $item['sender_name'] }} · {{ $item['created_at_human'] }}</div>
                                        <div class="st-bubble-body">{{ $item['body'] }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <p class="text-muted small text-center mt-5 mb-0">
                        Enlace de solo lectura. Para responder, hazlo directamente por email.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@push('css')
<link rel="stylesheet" href="{{ asset('modules/helpdesktickets/css/shared-ticket.css') }}">
@endpush
@endsection
