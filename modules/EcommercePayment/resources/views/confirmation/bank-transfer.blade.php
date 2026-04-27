@extends('ecommerce::layouts.shop')

@section('title', 'Instrucciones de pago')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-7">

            {{-- Encabezado --}}
            <div class="text-center mb-4">
                <div class="rounded-circle bg-success bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3"
                     style="width:72px;height:72px;">
                    <i class="fas fa-check-circle text-success fs-1"></i>
                </div>
                <h3 class="fw-bold mb-1">¡Orden confirmada!</h3>
                <p class="text-muted">Orden <strong>#{{ $order->code }}</strong> &mdash; Total:
                    <strong>${{ number_format($order->total, 2) }}</strong>
                </p>
            </div>

            {{-- Datos bancarios --}}
            <div class="card border mb-4">
                <div class="card-header bg-primary text-white py-3">
                    <h5 class="mb-0 fw-bold">
                        <i class="fas fa-university me-2"></i>Realiza tu transferencia
                    </h5>
                </div>
                <div class="card-body p-4">
                    @if($bankDetails['bank_name'] || $bankDetails['account_number'])
                        <dl class="row mb-0">
                            @if($bankDetails['bank_name'])
                                <dt class="col-5 text-muted fw-normal">Banco</dt>
                                <dd class="col-7 fw-semibold">{{ $bankDetails['bank_name'] }}</dd>
                            @endif
                            @if($bankDetails['account_type'])
                                <dt class="col-5 text-muted fw-normal">Tipo de cuenta</dt>
                                <dd class="col-7 fw-semibold">{{ $bankDetails['account_type'] }}</dd>
                            @endif
                            @if($bankDetails['account_number'])
                                <dt class="col-5 text-muted fw-normal">Número de cuenta</dt>
                                <dd class="col-7">
                                    <span class="fw-semibold font-monospace">{{ $bankDetails['account_number'] }}</span>
                                    <button type="button" class="btn btn-sm btn-outline-secondary ms-2 copy-btn"
                                            data-copy="{{ $bankDetails['account_number'] }}">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </dd>
                            @endif
                            @if($bankDetails['account_holder'])
                                <dt class="col-5 text-muted fw-normal">Titular</dt>
                                <dd class="col-7 fw-semibold">{{ $bankDetails['account_holder'] }}</dd>
                            @endif
                            @if($bankDetails['document_type'] && $bankDetails['document_number'])
                                <dt class="col-5 text-muted fw-normal">{{ $bankDetails['document_type'] }}</dt>
                                <dd class="col-7 fw-semibold">{{ $bankDetails['document_number'] }}</dd>
                            @endif
                            <dt class="col-5 text-muted fw-normal">Monto exacto</dt>
                            <dd class="col-7">
                                <span class="fw-bold text-primary fs-5">${{ number_format($order->total, 2) }}</span>
                            </dd>
                            <dt class="col-5 text-muted fw-normal">Referencia</dt>
                            <dd class="col-7">
                                <span class="fw-semibold font-monospace">{{ $order->code }}</span>
                                <button type="button" class="btn btn-sm btn-outline-secondary ms-2 copy-btn"
                                        data-copy="{{ $order->code }}">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </dd>
                        </dl>
                    @else
                        <p class="text-muted mb-0">Contacta a nuestro equipo para recibir los datos bancarios.</p>
                    @endif
                </div>
            </div>

            {{-- Instrucciones --}}
            @if($bankDetails['instructions'])
                <div class="alert alert-info border-0 mb-4">
                    <i class="fas fa-info-circle me-2"></i>
                    {{ $bankDetails['instructions'] }}
                </div>
            @endif

            {{-- Resumen de productos --}}
            <div class="card border mb-4">
                <div class="card-header py-3">
                    <h6 class="mb-0 fw-bold">Resumen de tu orden</h6>
                </div>
                <div class="card-body p-0">
                    <table class="table mb-0">
                        <tbody>
                            @foreach($order->items as $item)
                                <tr>
                                    <td>{{ $item->product_name }} <span class="text-muted">x{{ $item->qty }}</span></td>
                                    <td class="text-end fw-semibold">${{ number_format($item->total, 2) }}</td>
                                </tr>
                            @endforeach
                            @if($order->shipping_amount > 0)
                                <tr>
                                    <td class="text-muted">Envío</td>
                                    <td class="text-end">${{ number_format($order->shipping_amount, 2) }}</td>
                                </tr>
                            @endif
                            <tr class="table-light">
                                <td class="fw-bold">Total</td>
                                <td class="text-end fw-bold text-primary">${{ number_format($order->total, 2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="d-flex gap-2 justify-content-center">
                <a href="{{ route('shop.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-store me-1"></i> Seguir comprando
                </a>
            </div>

        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    $(document).on('click', '.copy-btn', function () {
        var text = $(this).data('copy');
        navigator.clipboard.writeText(text).then(function () {
            toastr.success('Copiado al portapapeles');
        });
    });
});
</script>
@endpush
