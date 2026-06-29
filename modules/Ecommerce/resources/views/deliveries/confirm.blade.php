@extends('layouts.theme')

@section('title', 'Confirmar entrega')

@section('content')
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-box-open fa-4x text-primary mb-4"></i>
                        <h4 class="fw-bold mb-2">Confirmar recepcion</h4>
                        <p class="text-muted mb-1">Orden: <strong>{{ $shipment->order?->code ?? '—' }}</strong></p>
                        <p class="text-muted mb-4">Al confirmar indicas que recibiste tu pedido correctamente.</p>
                        <form action="{{ route('ecommerce.delivery.confirm.post', $shipment->delivery_token) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-primary btn-lg w-100 mb-2">
                                <i class="fas fa-check me-2"></i> Si, recibi mi pedido
                            </button>
                            <a href="{{ route('shop.index') }}" class="btn btn-outline-secondary w-100">Cancelar</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
