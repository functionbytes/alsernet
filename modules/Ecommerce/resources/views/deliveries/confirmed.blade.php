@extends('layouts.theme')

@section('title', 'Entrega confirmada')

@section('content')
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-check-circle fa-4x text-success mb-4"></i>
                        <h4 class="fw-bold mb-2">Entrega confirmada</h4>
                        <p class="text-muted mb-1">Orden: <strong>{{ $shipment->order?->code ?? '—' }}</strong></p>
                        <p class="text-muted mb-4">Gracias por confirmar la recepcion de tu pedido.</p>
                        <a href="{{ route('shop.index') }}" class="btn btn-primary w-100">Volver a la tienda</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
