@extends('layouts.theme')

@section('title', 'Detalle de envio')

@section('page_header')
    @include('core::components.card', ['title' => 'Ecommerce - Detalle de envio'])
@endsection

@section('content')
    @include('core::components.alerts')

    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Envio #{{ $shipment->shipment_id ?? $shipment->id }}</h5>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-{{ $shipment->status === 'delivered' ? 'success' : ($shipment->status === 'pending' ? 'warning' : 'info') }}">{{ $shipment->status }}</span>
                    <a href="{{ route('ecommerce.shipments.label', $shipment) }}" target="_blank" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-print me-1"></i> Imprimir etiqueta
                    </a>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="generateTokenBtn">
                        <i class="fas fa-link me-1"></i> Generar enlace entrega
                    </button>
                </div>
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
                    <p class="mb-1"><strong>Empresa de envio:</strong> {{ $shipment->shipping_company_name ?? '—' }}</p>
                    <p class="mb-1"><strong>Tracking ID:</strong>
                        @if($shipment->tracking_link)
                            <a href="{{ $shipment->tracking_link }}" target="_blank">{{ $shipment->tracking_id ?? 'Ver seguimiento' }}</a>
                        @else
                            {{ $shipment->tracking_id ?? '—' }}
                        @endif
                    </p>
                    <p class="mb-1"><strong>Fecha estimada despacho:</strong> {{ $shipment->estimate_date_shipped?->format('d/m/Y') ?? '—' }}</p>
                    <p class="mb-1"><strong>Fecha despachado:</strong> {{ $shipment->date_shipped?->format('d/m/Y') ?? '—' }}</p>
                    @if($shipment->delivered_at)
                        <p class="mb-1"><strong>Entregado el:</strong> {{ $shipment->delivered_at->format('d/m/Y H:i') }}</p>
                    @endif
                    @if($shipment->delivery_token)
                        <p class="mb-0"><strong>Enlace de confirmacion:</strong>
                            <a href="{{ route('ecommerce.delivery.confirm', $shipment->delivery_token) }}" target="_blank" class="text-break small">
                                {{ route('ecommerce.delivery.confirm', $shipment->delivery_token) }}
                            </a>
                        </p>
                    @endif
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

@push('scripts')
<script>
$(function () {
    $('#generateTokenBtn').on('click', function () {
        var $btn = $(this);
        $btn.prop('disabled', true);
        $.ajax({
            url: '{{ route('ecommerce.shipments.token', $shipment) }}',
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function () {
                toastr.success('Enlace generado. Recarga la pagina para verlo.');
                setTimeout(function () { location.reload(); }, 1500);
            },
            error: function () {
                toastr.error('Error al generar enlace.');
                $btn.prop('disabled', false);
            }
        });
    });
});
</script>
@endpush
