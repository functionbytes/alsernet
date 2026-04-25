@extends('layouts.theme')

@section('title', 'Factura')

@section('content')
    @include('core::components.card', ['title' => 'Ecommerce - Factura'])

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Factura {{ $invoice->code ?? $invoice->id }}</h5>
            <span class="badge bg-{{ $invoice->status === 'paid' ? 'success' : ($invoice->status === 'pending' ? 'warning' : 'secondary') }}">{{ $invoice->status }}</span>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6>Informacion del cliente</h6>
                    <p class="mb-1"><strong>Nombre:</strong> {{ $invoice->customer_name ?? '—' }}</p>
                    <p class="mb-1"><strong>Email:</strong> {{ $invoice->customer_email ?? '—' }}</p>
                    <p class="mb-1"><strong>Telefono:</strong> {{ $invoice->customer_phone ?? '—' }}</p>
                    <p class="mb-1"><strong>Direccion:</strong> {{ $invoice->customer_address ?? '—' }}</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <h6>Informacion de factura</h6>
                    <p class="mb-1"><strong>Fecha:</strong> {{ $invoice->created_at?->format('d/m/Y H:i') ?? '—' }}</p>
                    <p class="mb-1"><strong>Pagado:</strong> {{ $invoice->paid_at?->format('d/m/Y H:i') ?? 'No' }}</p>
                </div>
            </div>

            <hr>

            <table class="table table-bordered">
                <thead class="table-light">
                    <tr><th>Producto</th><th class="text-center">Cantidad</th><th class="text-end">Subtotal</th></tr>
                </thead>
                <tbody>
                    @foreach($invoice->items as $item)
                        <tr>
                            <td>{{ $item->name ?? '—' }}</td>
                            <td class="text-center">{{ $item->qty }}</td>
                            <td class="text-end">{{ number_format($item->sub_total ?? 0, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr><td colspan="2" class="text-end"><strong>Subtotal</strong></td><td class="text-end">{{ number_format($invoice->sub_total ?? 0, 2) }}</td></tr>
                    <tr><td colspan="2" class="text-end"><strong>Impuesto</strong></td><td class="text-end">{{ number_format($invoice->tax_amount ?? 0, 2) }}</td></tr>
                    <tr><td colspan="2" class="text-end"><strong>Envio</strong></td><td class="text-end">{{ number_format($invoice->shipping_amount ?? 0, 2) }}</td></tr>
                    <tr><td colspan="2" class="text-end"><strong>Descuento</strong></td><td class="text-end">{{ number_format($invoice->discount_amount ?? 0, 2) }}</td></tr>
                    <tr><td colspan="2" class="text-end fw-bold">Total</td><td class="text-end fw-bold">{{ number_format($invoice->amount ?? 0, 2) }}</td></tr>
                </tfoot>
            </table>
        </div>
    </div>
@endsection
