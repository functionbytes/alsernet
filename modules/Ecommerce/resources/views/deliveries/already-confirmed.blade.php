@extends('layouts.theme')

@section('title', 'Ya confirmado')

@section('content')
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-info-circle fa-4x text-info mb-4"></i>
                        <h4 class="fw-bold mb-2">Entrega ya confirmada</h4>
                        <p class="text-muted mb-1">Orden: <strong>{{ $shipment->order?->code ?? '—' }}</strong></p>
                        <p class="text-muted mb-4">
                            Esta entrega fue confirmada el
                            {{ $shipment->delivered_at ? \Carbon\Carbon::parse($shipment->delivered_at)->format('d/m/Y H:i') : '—' }}.
                        </p>
                        <a href="{{ route('shop.index') }}" class="btn btn-primary w-100">Volver a la tienda</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
