@extends('layouts.theme')

@section('title', 'Editar envío #' . ($shipment->shipment_id ?? $shipment->id))

@section('page_header')
    @include('core::components.card', ['title' => 'Ecommerce - Editar envío #' . ($shipment->shipment_id ?? $shipment->id)])
@endsection

@section('content')
    @include('core::components.alerts')

    @php
        $statusLabel = match($shipment->status) {
            'delivered'  => 'Entregado',
            'delivering' => 'En camino',
            'processing' => 'Procesando',
            'cancelled'  => 'Cancelado',
            default      => 'Pendiente',
        };
        $statusBadge = match($shipment->status) {
            'delivered'  => 'bg-success',
            'delivering' => 'bg-info',
            'processing' => 'bg-primary',
            'cancelled'  => 'bg-danger',
            default      => 'bg-warning text-dark',
        };
        $customer = $shipment->order?->customer;
        $address  = $shipment->order?->shippingAddress;
    @endphp

    <div class="row g-3">

        {{-- Columna principal --}}
        <div class="col-lg-8">

            {{-- Productos de la orden --}}
            @if($shipment->order?->items->count())
                <div class="card mb-3">
                    <div class="card-body p-0">
                        <table class="table table-hover align-middle mb-0">
                            <tbody>
                                @foreach($shipment->order->items as $item)
                                    <tr>
                                        <td class="ps-3" style="width:60px">
                                            @if($item->product_image)
                                                <img src="{{ $item->product_image }}" class="rounded" width="50" height="50" style="object-fit:cover">
                                            @else
                                                <div class="rounded bg-light d-flex align-items-center justify-content-center" style="width:50px;height:50px">
                                                    <i class="fas fa-box text-muted"></i>
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="fw-semibold small">{{ $item->product_name }}</div>
                                            @if($item->product?->sku)
                                                <small class="text-muted">SKU: {{ $item->product->sku }}</small>
                                            @endif
                                        </td>
                                        <td class="text-muted small text-nowrap">
                                            {{ $item->qty }} × ${{ number_format($item->price, 2) }}
                                        </td>
                                        <td class="text-end fw-semibold text-nowrap">
                                            ${{ number_format($item->total, 2) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="border-top">
                                    <td colspan="3" class="text-end text-muted small ps-3 pb-3">Total</td>
                                    <td class="text-end fw-bold pb-3">${{ number_format($shipment->order->total, 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    @if($shipment->order)
                        <div class="card-footer border-top">
                            <a href="{{ route('ecommerce.orders.show', $shipment->order) }}" class="small text-decoration-none">
                                <i class="fas fa-external-link-alt me-1"></i>
                                Ver orden {{ $shipment->order->code }}
                            </a>
                        </div>
                    @endif
                </div>
            @endif

            {{-- Formulario --}}
            <div class="card mb-3">
                <div class="card-header p-3 border-bottom">
                    <h6 class="mb-0 fw-bold">Información adicional de envío</h6>
                </div>
                <form action="{{ route('ecommerce.shipments.update', $shipment) }}" method="POST">
                    @csrf @method('PUT')
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nombre de la empresa de envío</label>
                                <input type="text" name="shipping_company_name"
                                    class="form-control @error('shipping_company_name') is-invalid @enderror"
                                    value="{{ old('shipping_company_name', $shipment->shipping_company_name) }}"
                                    placeholder="Ej: DHL, AliExpress...">
                                @error('shipping_company_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">ID de rastreo</label>
                                <input type="text" name="tracking_id"
                                    class="form-control @error('tracking_id') is-invalid @enderror"
                                    value="{{ old('tracking_id', $shipment->tracking_id) }}"
                                    placeholder="Ej: JJD0099999999">
                                @error('tracking_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label">Enlace de seguimiento</label>
                                <input type="text" name="tracking_link"
                                    class="form-control @error('tracking_link') is-invalid @enderror"
                                    value="{{ old('tracking_link', $shipment->tracking_link) }}"
                                    placeholder="Ej: https://mydhl.express.dhl/...">
                                @error('tracking_link')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Fecha estimada de envío</label>
                                <input type="date" name="estimate_date_shipped"
                                    class="form-control @error('estimate_date_shipped') is-invalid @enderror"
                                    value="{{ old('estimate_date_shipped', $shipment->estimate_date_shipped?->format('Y-m-d')) }}">
                                @error('estimate_date_shipped')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Fecha de despacho</label>
                                <input type="date" name="date_shipped"
                                    class="form-control @error('date_shipped') is-invalid @enderror"
                                    value="{{ old('date_shipped', $shipment->date_shipped?->format('Y-m-d')) }}">
                                @error('date_shipped')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label">Nota</label>
                                <textarea name="note" rows="3"
                                    class="form-control @error('note') is-invalid @enderror"
                                    placeholder="Añadir la nota...">{{ old('note', $shipment->note) }}</textarea>
                                @error('note')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-2">
                            <i class="fas fa-save me-1"></i> Guardar
                        </button>
                        <a href="{{ route('ecommerce.shipments.index') }}" class="btn btn-light w-100">Cancelar</a>
                    </div>
                </form>
            </div>

            {{-- Historial --}}
            <div class="card">
                <div class="card-header p-3 border-bottom">
                    <h6 class="mb-0 fw-bold">Historial del envío</h6>
                </div>

                @if($shipment->histories->count() > 0)
                    <ul class="list-group list-group-flush">
                        @foreach($shipment->histories as $history)
                            <li class="list-group-item px-4 py-3">
                                <div class="d-flex justify-content-between align-items-start">
                                    <p class="mb-0 small">{{ $history->description }}</p>
                                    <small class="text-muted text-nowrap ms-3">{{ $history->created_at->format('d/m/Y H:i') }}</small>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <div class="card-body text-center py-4">
                        <i class="fas fa-history fa-2x text-muted opacity-50 mb-2"></i>
                        <p class="text-muted mb-0 small">Sin entradas en el historial</p>
                    </div>
                @endif

                <div class="card-footer border-top">
                    <form action="{{ route('ecommerce.shipments.history.store', $shipment) }}" method="POST">
                        @csrf
                        <label class="form-label small">Agregar entrada al historial</label>
                        <textarea name="description" rows="2"
                            class="form-control form-control-sm @error('description') is-invalid @enderror mb-2"
                            placeholder="Descripción del evento..." required>{{ old('description') }}</textarea>
                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <button type="submit" class="btn btn-outline-secondary btn-sm">Agregar</button>
                    </form>
                </div>
            </div>

        </div>

        {{-- Sidebar --}}
        <div class="col-lg-4">

            {{-- Información de envío --}}
            <div class="card mb-3">
                <div class="card-header p-3 border-bottom">
                    <h6 class="mb-0 fw-bold">Información de envío</h6>
                </div>
                <div class="card-body">
                    <dl class="row mb-0 small">
                        <dt class="col-5 text-muted fw-normal">Número de orden</dt>
                        <dd class="col-7">
                            @if($shipment->order)
                                <a href="{{ route('ecommerce.orders.show', $shipment->order) }}" class="text-decoration-none fw-semibold">
                                    {{ $shipment->order->code }}
                                </a>
                            @else
                                —
                            @endif
                        </dd>

                        <dt class="col-5 text-muted fw-normal">Método de envío</dt>
                        <dd class="col-7">{{ $shipment->order?->shipping_method ?: 'Por defecto' }}</dd>

                        <dt class="col-5 text-muted fw-normal">Gastos de envío</dt>
                        <dd class="col-7 fw-semibold">${{ number_format($shipment->order?->shipping_amount ?? 0, 2) }}</dd>

                        <dt class="col-5 text-muted fw-normal">Estado del envío</dt>
                        <dd class="col-7">
                            <span class="badge {{ $statusBadge }}">{{ $statusLabel }}</span>
                        </dd>
                    </dl>
                </div>
                <div class="card-footer border-top">
                    <form action="{{ route('ecommerce.shipments.status', $shipment) }}" method="POST">
                        @csrf
                        <div class="d-flex gap-2 align-items-center">
                            <select name="status" class="form-select form-select-sm flex-grow-1">
                                <option value="pending"    {{ $shipment->status === 'pending'    ? 'selected' : '' }}>Pendiente</option>
                                <option value="processing" {{ $shipment->status === 'processing' ? 'selected' : '' }}>Procesando</option>
                                <option value="delivering" {{ $shipment->status === 'delivering' ? 'selected' : '' }}>En camino</option>
                                <option value="delivered"  {{ $shipment->status === 'delivered'  ? 'selected' : '' }}>Entregado</option>
                                <option value="cancelled"  {{ $shipment->status === 'cancelled'  ? 'selected' : '' }}>Cancelado</option>
                            </select>
                            <button type="submit" class="btn btn-sm btn-outline-secondary text-nowrap">Actualizar</button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Información del cliente --}}
            <div class="card mb-3">
                <div class="card-header p-3 border-bottom">
                    <h6 class="mb-0 fw-bold">Información del cliente</h6>
                </div>
                <div class="card-body">
                    @if($customer)
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center text-white fw-bold flex-shrink-0"
                                style="width:42px;height:42px;font-size:1.1rem">
                                {{ strtoupper(substr($customer->name, 0, 1)) }}
                            </div>
                            <div>
                                <p class="mb-0 fw-semibold small">{{ $customer->name }}</p>
                            </div>
                        </div>
                        @if($customer->phone)
                            <p class="mb-2 small">
                                <i class="fas fa-phone me-2 text-muted"></i>
                                <a href="tel:{{ $customer->phone }}" class="text-decoration-none">{{ $customer->phone }}</a>
                            </p>
                        @endif
                        @if($customer->email)
                            <p class="mb-2 small">
                                <i class="fas fa-envelope me-2 text-muted"></i>
                                <a href="mailto:{{ $customer->email }}" class="text-decoration-none">{{ $customer->email }}</a>
                            </p>
                        @endif
                        @if($address)
                            <p class="mb-1 small"><i class="fas fa-map-marker-alt me-2 text-muted"></i>{{ $address->address }}</p>
                            <p class="mb-0 small text-muted ms-4">{{ $address->city }}{{ $address->country ? ', ' . $address->country : '' }}</p>
                        @endif
                    @else
                        <p class="text-muted small mb-0">Sin información de cliente</p>
                    @endif
                </div>
            </div>

            {{-- Acciones --}}
            <div class="card">
                <div class="card-body">
                    <a href="{{ route('ecommerce.shipments.label', $shipment) }}" target="_blank"
                        class="btn btn-outline-secondary w-100 mb-2 btn-sm">
                        <i class="fas fa-print me-1"></i> Imprimir etiqueta
                    </a>
                    <button type="button" class="btn btn-outline-primary w-100 btn-sm" id="generateTokenBtn">
                        <i class="fas fa-link me-1"></i> Generar enlace de entrega
                    </button>
                    @if($shipment->delivery_token)
                        <div class="mt-2">
                            <small class="text-muted d-block mb-1">Enlace de confirmación:</small>
                            <input type="text" class="form-control form-control-sm"
                                value="{{ route('ecommerce.delivery.confirm', $shipment->delivery_token) }}"
                                readonly onclick="this.select()">
                        </div>
                    @endif
                </div>
            </div>

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
                toastr.success('Enlace generado. Recargando...');
                setTimeout(function () { location.reload(); }, 1200);
            },
            error: function () {
                toastr.error('Error al generar el enlace.');
                $btn.prop('disabled', false);
            }
        });
    });
});
</script>
@endpush
