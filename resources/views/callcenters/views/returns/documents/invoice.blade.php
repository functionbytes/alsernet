@extends('callcenter.viewsreutnrs.docuemnts.la')

@section('header')
    <h1>FACTURA</h1>
    <p>{{ $company_name ?? 'A-alvarez' }}</p>
@endsection

@section('content')
    <div class="mb-3">
        <strong>Factura #:</strong> {{ $invoice_number ?? 'N/A' }}<br>
        <strong>Fecha:</strong> {{ $date ?? now()->format('d/m/Y') }}<br>
        <strong>Cliente:</strong> {{ $client_name ?? 'N/A' }}
    </div>

    @if(isset($items) && is_array($items))
    <table>
        <thead>
            <tr>
                <th>Descripción</th>
                <th class="text-center">Cantidad</th>
                <th class="text-right">Precio Unitario</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @php $total = 0; @endphp
            @foreach($items as $item)
            @php
                $itemTotal = ($item['quantity'] ?? 1) * ($item['price'] ?? 0);
                $total += $itemTotal;
            @endphp
            <tr>
                <td>{{ $item['description'] ?? 'N/A' }}</td>
                <td class="text-center">{{ $item['quantity'] ?? 1 }}</td>
                <td class="text-right">${{ number_format($item['price'] ?? 0, 2) }}</td>
                <td class="text-right">${{ number_format($itemTotal, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th colspan="3" class="text-right">TOTAL:</th>
                <th class="text-right">${{ number_format($total, 2) }}</th>
            </tr>
        </tfoot>
    </table>
    @endif

    @if(isset($notes))
    <div class="mt-3">
        <strong>Notas:</strong><br>
        {{ $notes }}
    </div>
    @endif
@endsection
