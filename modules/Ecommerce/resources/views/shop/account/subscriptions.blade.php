@extends('ecommerce::layouts.shop-wowy')

@section('title', 'Mis suscripciones')

@section('content')
<section class="mt-60 mb-60">
    <div class="container">
        <h2 class="mb-4">Mis suscripciones</h2>

        @if($subscriptions->count() > 0)
            <div class="row">
                @foreach($subscriptions as $sub)
                <div class="col-md-6 mb-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="mb-0">{{ $sub->product?->name ?? 'Producto eliminado' }}</h5>
                                @switch($sub->status)
                                    @case('active') <span class="badge bg-success">Activa</span> @break
                                    @case('paused') <span class="badge bg-warning text-dark">Pausada</span> @break
                                    @case('cancelled') <span class="badge bg-danger">Cancelada</span> @break
                                    @default <span class="badge bg-secondary">{{ $sub->status }}</span>
                                @endswitch
                            </div>
                            <p class="mb-1"><strong>${{ number_format($sub->price, 2) }}</strong> cada {{ $sub->interval }}</p>
                            <p class="small text-muted mb-3">Cantidad: {{ $sub->qty }}</p>
                            @if($sub->next_billing_at && $sub->status === 'active')
                                <p class="small">Próximo cobro: <strong>{{ $sub->next_billing_at->format('d/m/Y') }}</strong></p>
                            @endif
                            <div class="d-flex gap-2 flex-wrap">
                                @if($sub->status === 'active')
                                    <form method="POST" action="{{ route('account.subscriptions.pause', $sub) }}">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-warning">Pausar</button>
                                    </form>
                                @elseif($sub->status === 'paused')
                                    <form method="POST" action="{{ route('account.subscriptions.resume', $sub) }}">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-success">Reactivar</button>
                                    </form>
                                @endif
                                @if($sub->status !== 'cancelled')
                                    <form method="POST" action="{{ route('account.subscriptions.cancel', $sub) }}" onsubmit="return confirm('¿Cancelar esta suscripción?')">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-danger">Cancelar</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-5">
                <i class="fas fa-sync-alt fa-3x text-muted opacity-25 mb-3"></i>
                <p class="text-muted mb-0">No tienes suscripciones activas.</p>
            </div>
        @endif
    </div>
</section>
@endsection
