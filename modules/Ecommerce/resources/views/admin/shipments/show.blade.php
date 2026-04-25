@extends('layouts.theme')

@section('title', 'Detalle de envio')

@section('content')
    @include('core::components.card', ['title' => 'Ecommerce - Detalle de envio'])

    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Envio #{{ $shipment->shipment_id ?? $shipment->id }}</h5>
                <span class="badge bg-{{ $shipment->status === 'delivered' ? 'success' : ($shipment->status === 'pending' ? 'warning' : 'info') }}">{{ $shipment->status }}</span>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6>Informacion de orden</h6>
                    <p class="mb-1"><strong>Orden:</strong> {{ $shipment->order?->code ?? '—' }}</p>
                    <p class="mb-1"><strong>Cliente:</strong> {{ $shipment->order?->customer?->name ?? '—' }}</p>
                </div>
                <div class="col-md-6">
                    <h6>Informacion de envio</h6>
                    <p class="mb-1"><strong>Peso:</strong> {{ $shipment->weight }} kg</p>
                    <p class="mb-1"><strong>Precio:</strong> {{ number_format($shipment->price, 2) }}</p>
                    <p class="mb-1"><strong>Nota:</strong> {{ $shipment->note ?? '—' }}</p>
                </div>
            </div>

            <hr>

            <form action="{{ route('ecommerce.shipments.status', $shipment) }}" method="POST" class="mt-3">
                @csrf
                <div class="row align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Actualizar estado</label>
                        <select name="status" class="form-select">
                            <option value="pending" {{ $shipment->status === 'pending' ? 'selected' : '' }}>Pendiente</option>
                            <option value="processing" {{ $shipment->status === 'processing' ? 'selected' : '' }}>Procesando</option>
                            <option value="delivering" {{ $shipment->status === 'delivering' ? 'selected' : '' }}>En camino</option>
                            <option value="delivered" {{ $shipment->status === 'delivered' ? 'selected' : '' }}>Entregado</option>
                            <option value="cancelled" {{ $shipment->status === 'cancelled' ? 'selected' : '' }}>Cancelado</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary">Actualizar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
