@extends('layouts.theme')

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
@push('styles')
<style>
/* Clases propias en vez de utilidades Bootstrap (.bg-primary/.text-white):
   dentro de layouts.theme no se resuelven de forma fiable — el bug real
   encontrado al probar esta página era texto blanco sobre fondo
   transparente, invisible. */
.st-thread { display: flex; flex-direction: column; gap: 12px; }
.st-row { display: flex; }
.st-row-agent { justify-content: flex-start; }
.st-row-customer { justify-content: flex-end; }
.st-bubble { max-width: 80%; padding: 12px 14px; border-radius: 12px; }
.st-row-agent .st-bubble { background: #f1f2f5; color: #18181b; }
.st-row-customer .st-bubble { background: #18181b; color: #fff; }
.st-bubble-meta { font-size: 11px; opacity: .65; margin-bottom: 4px; }
.st-bubble-body { white-space: pre-wrap; font-size: 14px; line-height: 1.5; }
</style>
@endpush
@endsection
