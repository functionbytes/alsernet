@extends('layouts.theme')

@section('title', 'Envios')

@section('content')
    @include('core::components.card', ['title' => 'Ecommerce - Envios'])

    <div class="widget-content searchable-container list">
        @include('core::components.alerts')

        <div class="card">
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Envios</h5>
                        <p class="small mb-0 text-muted">Administra los envios de ordenes</p>
                    </div>
                </div>
            </div>

            <div class="card-body">
                @if($shipments->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Orden</th>
                                    <th>ID envio</th>
                                    <th>Estado</th>
                                    <th>Precio</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($shipments as $shipment)
                                    <tr>
                                        <td>
                                            <a href="{{ route('ecommerce.orders.show', $shipment->order) }}" class="text-decoration-none fw-semibold">{{ $shipment->order?->code ?? '—' }}</a>
                                        </td>
                                        <td><small class="text-muted">{{ $shipment->shipment_id ?? '—' }}</small></td>
                                        <td><span class="badge bg-{{ $shipment->status === 'delivered' ? 'success' : ($shipment->status === 'pending' ? 'warning' : 'info') }}">{{ $shipment->status }}</span></td>
                                        <td>{{ number_format($shipment->price, 2) }}</td>
                                        <td class="text-center">
                                            <a href="{{ route('ecommerce.shipments.show', $shipment) }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i></a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-shipping-fast fa-4x text-muted opacity-50 mb-4"></i>
                        <h5 class="text-muted mb-2">No hay envios</h5>
                    </div>
                @endif
            </div>

            @if($shipments->hasPages())
                <div class="card-footer">{{ $shipments->withQueryString()->links() }}</div>
            @endif
        </div>
    </div>
@endsection
