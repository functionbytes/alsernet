@extends('layouts.theme')

@section('title', 'Factura ' . ($invoice->code ?? '#' . $invoice->id))

@section('page_header')
    @include('core::components.card', ['title' => 'Ecommerce - Comprobante'])
@endsection

@section('content')
    @include('core::components.alerts')

    @php
        $badgeClass = match($invoice->status) {
            'paid'      => 'bg-success',
            'cancelled' => 'bg-danger',
            default     => 'bg-warning text-dark',
        };
        $statusLabel = match($invoice->status) {
            'paid'      => 'Pagada',
            'cancelled' => 'Cancelada',
            default     => 'Pendiente',
        };
    @endphp

    {{-- Barra de acciones --}}
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <a href="{{ route('ecommerce.invoices.index') }}" class="btn btn-light btn-sm">
            <i class="fas fa-arrow-left me-1"></i> Volver a facturas
        </a>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                <i class="fas fa-print me-1"></i> Imprimir factura
            </button>
            <a href="{{ route('ecommerce.invoices.download', $invoice) }}" target="_blank" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-file-pdf me-1"></i> Descargar factura
            </a>
        </div>
    </div>

    <div id="invoice-printable" class="card">
        <div class="card-body p-4">

            {{-- Encabezado --}}
            <div class="row align-items-start mb-4">
                <div class="col-md-6">
                    <h4 class="fw-bold mb-1">{{ $invoice->customer_name ?? '—' }}</h4>
                    @if($invoice->customer_email)
                        <p class="mb-1 small text-muted">
                            <i class="fas fa-envelope me-1"></i>
                            <a href="mailto:{{ $invoice->customer_email }}" class="text-decoration-none text-muted">{{ $invoice->customer_email }}</a>
                        </p>
                    @endif
                    @if($invoice->customer_phone)
                        <p class="mb-1 small text-muted">
                            <i class="fas fa-phone me-1"></i>
                            <a href="tel:{{ $invoice->customer_phone }}" class="text-decoration-none text-muted">{{ $invoice->customer_phone }}</a>
                        </p>
                    @endif
                    @if($invoice->customer_address)
                        <p class="mb-0 small text-muted">
                            <i class="fas fa-map-marker-alt me-1"></i> {{ $invoice->customer_address }}
                        </p>
                    @endif
                </div>
                <div class="col-md-6 text-md-end mt-3 mt-md-0">
                    <h2 class="fw-bold text-uppercase letter-spacing-1 mb-1" style="letter-spacing:2px">Comprobante</h2>
                    <p class="text-muted mb-1 small">{{ $invoice->code ?? '#' . $invoice->id }}</p>
                    <span class="badge {{ $badgeClass }}">{{ $statusLabel }}</span>
                </div>
            </div>

            <hr class="border-2" style="border-color:#b10100!important">

            {{-- Datos de la factura --}}
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="bg-light rounded p-3 h-100">
                        <div class="text-muted small text-uppercase fw-semibold mb-1" style="letter-spacing:.5px">Código de factura</div>
                        <div class="fw-semibold">{{ $invoice->code ?? '—' }}</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="bg-light rounded p-3 h-100">
                        <div class="text-muted small text-uppercase fw-semibold mb-1" style="letter-spacing:.5px">Emitida el</div>
                        <div class="fw-semibold">{{ $invoice->created_at->translatedFormat('j F, Y') }}</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="bg-light rounded p-3 h-100">
                        <div class="text-muted small text-uppercase fw-semibold mb-1" style="letter-spacing:.5px">Método de pago</div>
                        <div class="fw-semibold">{{ $invoice->shipping_method ?: 'No especificado' }}</div>
                    </div>
                </div>
            </div>

            {{-- Tabla de productos --}}
            <div class="table-responsive mb-4">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3" style="width:40px">#</th>
                            <th style="width:56px">Imagen</th>
                            <th>Producto</th>
                            <th class="text-end">Precio unit.</th>
                            <th class="text-center" style="width:80px">Cantidad</th>
                            <th class="text-end pe-3">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($invoice->items as $index => $item)
                            @php
                                $unitPrice = $item->qty > 0 ? $item->sub_total / $item->qty : $item->sub_total;
                            @endphp
                            <tr>
                                <td class="ps-3 text-muted small">{{ $index + 1 }}</td>
                                <td>
                                    @if($item->image)
                                        <img src="{{ $item->image }}" class="rounded" width="44" height="44" style="object-fit:cover">
                                    @else
                                        <div class="rounded bg-light d-flex align-items-center justify-content-center" style="width:44px;height:44px">
                                            <i class="fas fa-box text-muted"></i>
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <div class="fw-semibold small">{{ $item->name ?? '—' }}</div>
                                    @if($item->description)
                                        <small class="text-muted">SKU: {{ $item->description }}</small>
                                    @endif
                                </td>
                                <td class="text-end small">${{ number_format($unitPrice, 2) }}</td>
                                <td class="text-center small">{{ $item->qty }}</td>
                                <td class="text-end fw-semibold pe-3">${{ number_format($item->sub_total, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">Sin items registrados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Totales --}}
            <div class="row justify-content-end">
                <div class="col-md-5 col-lg-4">
                    <table class="table table-sm mb-0">
                        <tbody>
                            <tr>
                                <td class="text-muted">Subtotal</td>
                                <td class="text-end fw-semibold">${{ number_format($invoice->sub_total ?? 0, 2) }}</td>
                            </tr>
                            @if(($invoice->shipping_amount ?? 0) > 0)
                                <tr>
                                    <td class="text-muted">Tarifa de envío</td>
                                    <td class="text-end">${{ number_format($invoice->shipping_amount, 2) }}</td>
                                </tr>
                            @endif
                            @if(($invoice->tax_amount ?? 0) > 0)
                                <tr>
                                    <td class="text-muted">Impuestos</td>
                                    <td class="text-end">${{ number_format($invoice->tax_amount, 2) }}</td>
                                </tr>
                            @endif
                            @if(($invoice->discount_amount ?? 0) > 0)
                                <tr>
                                    <td class="text-muted">Descuento</td>
                                    <td class="text-end text-danger">-${{ number_format($invoice->discount_amount, 2) }}</td>
                                </tr>
                            @endif
                            <tr class="border-top">
                                <td class="fw-bold">Gran total</td>
                                <td class="text-end fw-bold text-danger fs-5">${{ number_format($invoice->amount ?? 0, 2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        @if($invoice->reference)
            <div class="card-footer border-top">
                <small class="text-muted">Comprobante para:</small>
                <a href="{{ route('ecommerce.orders.show', $invoice->reference) }}" class="text-decoration-none fw-semibold ms-1">
                    {{ $invoice->reference->code ?? '#' . $invoice->reference_id }}
                </a>
            </div>
        @endif
    </div>
@endsection
