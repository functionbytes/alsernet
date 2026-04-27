@extends('layouts.theme')

@section('title', 'Detalle gift card')

@section('content')
    @include('core::components.card', ['title' => 'Ecommerce - Gift card'])

    @include('core::components.alerts')

    <div class="row g-4">
        <div class="col-12 col-lg-8">
            <div class="card">
                <div class="card-header border-bottom p-3 d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0 fw-bold font-monospace">{{ $giftCard->code }}</h5>
                        <small class="text-muted">Creada {{ $giftCard->created_at->format('d/m/Y H:i') }}</small>
                    </div>
                    @php
                        $statusMap = [
                            'active' => ['bg-success', 'Activa'],
                            'used' => ['bg-secondary', 'Usada'],
                            'expired' => ['bg-warning', 'Expirada'],
                            'cancelled' => ['bg-danger', 'Cancelada'],
                        ];
                        [$bg, $label] = $statusMap[$giftCard->status] ?? ['bg-secondary', $giftCard->status];
                    @endphp
                    <span class="badge {{ $bg }} fs-6">{{ $label }}</span>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded">
                                <div class="text-muted small mb-1">Monto original</div>
                                <div class="fs-4 fw-bold">${{ number_format($giftCard->amount, 2) }}</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded">
                                <div class="text-muted small mb-1">Saldo disponible</div>
                                <div class="fs-4 fw-bold {{ (float) $giftCard->balance > 0 ? 'text-success' : 'text-muted' }}">
                                    ${{ number_format($giftCard->balance, 2) }}
                                </div>
                            </div>
                        </div>
                        @if($giftCard->expires_at)
                            <div class="col-12">
                                <div class="small text-muted">
                                    <i class="fas fa-calendar-alt me-1"></i>
                                    Vencimiento: <strong class="{{ $giftCard->expires_at->isPast() ? 'text-danger' : '' }}">
                                        {{ $giftCard->expires_at->format('d/m/Y') }}
                                    </strong>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            @if($giftCard->recipient_email || $giftCard->recipient_name || $giftCard->message)
                <div class="card mt-4">
                    <div class="card-header border-bottom p-3">
                        <h6 class="mb-0 fw-bold">Destinatario</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-2">
                            @if($giftCard->recipient_name)
                                <div class="col-md-6">
                                    <div class="text-muted small">Nombre</div>
                                    <div>{{ $giftCard->recipient_name }}</div>
                                </div>
                            @endif
                            @if($giftCard->recipient_email)
                                <div class="col-md-6">
                                    <div class="text-muted small">Email</div>
                                    <div>{{ $giftCard->recipient_email }}</div>
                                </div>
                            @endif
                            @if($giftCard->message)
                                <div class="col-12">
                                    <div class="text-muted small">Mensaje</div>
                                    <div class="p-3 bg-light rounded mt-1">{{ $giftCard->message }}</div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            @if($giftCard->buyer)
                <div class="card mt-4">
                    <div class="card-header border-bottom p-3">
                        <h6 class="mb-0 fw-bold">Comprador</h6>
                    </div>
                    <div class="card-body">
                        <div>{{ $giftCard->buyer->name }}</div>
                        <small class="text-muted">{{ $giftCard->buyer->email }}</small>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <a href="{{ route('ecommerce.gift-cards.index') }}" class="btn btn-light w-100 mb-2">
                        Volver al listado
                    </a>
                    <form action="{{ route('ecommerce.gift-cards.destroy', $giftCard) }}" method="POST">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger w-100"
                            onclick="return confirm('¿Eliminar esta gift card permanentemente?')">
                            Eliminar
                        </button>
                    </form>
                </div>
            </div>

            @if($giftCard->order)
                <div class="card mt-3">
                    <div class="card-header border-bottom p-3">
                        <h6 class="mb-0 fw-bold">Orden asociada</h6>
                    </div>
                    <div class="card-body">
                        <a href="{{ route('ecommerce.orders.show', $giftCard->order) }}" class="text-decoration-none">
                            #{{ $giftCard->order->code ?? $giftCard->order->id }}
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
