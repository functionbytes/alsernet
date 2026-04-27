@extends('layouts.theme')

@section('title', 'Detalle de pago #' . $payment->id)

@section('content')
    @include('core::components.card', ['title' => 'Ecommerce - Detalle de pago'])

    @include('core::components.alerts')

    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Pago #{{ $payment->id }}</h5>
            <a href="{{ route('ecommerce-payment.payments.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Volver
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">{{ __('Informacion del pago') }}</h6>
                </div>
                <div class="card-body">
                    <table class="table table-borderless mb-0">
                        <tr>
                            <td class="text-muted">{{ __('Referencia') }}</td>
                            <td class="text-end"><code>{{ $payment->charge_id }}</code></td>
                        </tr>
                        <tr>
                            <td class="text-muted">{{ __('Monto') }}</td>
                            <td class="text-end">
                                <strong>{{ number_format($payment->amount, 2) }} {{ $payment->currency }}</strong>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">{{ __('Fee') }}</td>
                            <td class="text-end">{{ $payment->payment_fee ? number_format($payment->payment_fee, 2) : '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">{{ __('Reembolsado') }}</td>
                            <td class="text-end">{{ $payment->refunded_amount ? number_format($payment->refunded_amount, 2) : '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">{{ __('Canal') }}</td>
                            <td class="text-end"><span class="badge bg-info">{{ $payment->payment_channel }}</span></td>
                        </tr>
                        <tr>
                            <td class="text-muted">{{ __('Estado') }}</td>
                            <td class="text-end"><span class="{{ $payment->status->badgeClass() }}">{{ $payment->status->label() }}</span></td>
                        </tr>
                        <tr>
                            <td class="text-muted">{{ __('Tipo') }}</td>
                            <td class="text-end">{{ $payment->payment_type ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">{{ __('Fecha') }}</td>
                            <td class="text-end">{{ $payment->created_at->format('d/m/Y H:i:s') }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">{{ __('Orden asociada') }}</h6>
                </div>
                <div class="card-body">
                    @if($payment->order)
                        <table class="table table-borderless mb-0">
                            <tr>
                                <td class="text-muted">{{ __('Codigo') }}</td>
                                <td class="text-end">
                                    <a href="{{ route('ecommerce.orders.show', $payment->order) }}">
                                        {{ $payment->order->code }}
                                    </a>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">{{ __('Total') }}</td>
                                <td class="text-end">${{ number_format($payment->order->total, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">{{ __('Estado') }}</td>
                                <td class="text-end">{{ $payment->order->status->label() ?? $payment->order->status }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">{{ __('Cliente') }}</td>
                                <td class="text-end">{{ $payment->order->customer->name ?? '-' }}</td>
                            </tr>
                        </table>
                    @else
                        <p class="text-muted mb-0">{{ __('No hay orden asociada.') }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if($payment->metadata)
    <div class="card mb-4">
        <div class="card-header">
            <h6 class="mb-0">{{ __('Metadata') }}</h6>
        </div>
        <div class="card-body">
            <pre class="mb-0"><code>{{ json_encode($payment->metadata, JSON_PRETTY_PRINT) }}</code></pre>
        </div>
    </div>
    @endif

    @if($payment->status->value === 'pending' && in_array($payment->payment_channel, ['cod', 'bank_transfer']))
    <div class="card border-success mt-4">
        <div class="card-header bg-success-subtle py-3">
            <h6 class="mb-0 fw-bold text-success">
                <i class="fas fa-check-circle me-2"></i> Confirmar pago recibido
            </h6>
        </div>
        <div class="card-body p-4">
            <p class="text-muted small mb-3">
                Confirma que recibiste el pago de este pedido para actualizar el estado de la orden.
            </p>
            <form action="{{ route('ecommerce-payment.payments.confirm', $payment) }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-success w-100"
                        onclick="return confirm('¿Confirmar que recibiste el pago de ${{ number_format($payment->amount, 2) }}?')">
                    <i class="fas fa-check me-2"></i> Marcar como pagado
                </button>
            </form>
        </div>
    </div>
    @endif

    @if($payment->status->value === 'completed')
    <div class="card mb-4">
        <div class="card-header">
            <h6 class="mb-0">{{ __('Reembolso') }}</h6>
        </div>
        <div class="card-body">
            <form action="{{ route('ecommerce-payment.payments.refund', $payment) }}" method="POST">
                @csrf
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">{{ __('Monto') }}</label>
                        <input type="number" step="0.01" name="amount" class="form-control" placeholder="Máx: {{ number_format($payment->amount - ($payment->refunded_amount ?? 0), 2) }}">
                        <small class="text-muted">{{ __('Dejar vacio para reembolsar el total.') }}</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('Motivo') }}</label>
                        <input type="text" name="reason" class="form-control" placeholder="Motivo del reembolso...">
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-warning w-100" onclick="return confirm('¿Estas seguro de procesar el reembolso?')">
                            <i class="fas fa-undo me-2"></i>{{ __('Reembolsar') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    @endif

    @if($payment->logs->isNotEmpty())
    <div class="card">
        <div class="card-header">
            <h6 class="mb-0">{{ __('Logs') }}</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>{{ __('Metodo') }}</th>
                            <th>{{ __('IP') }}</th>
                            <th>{{ __('Request') }}</th>
                            <th>{{ __('Response') }}</th>
                            <th>{{ __('Fecha') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($payment->logs as $log)
                            <tr>
                                <td>{{ $log->payment_method }}</td>
                                <td><code>{{ $log->ip_address }}</code></td>
                                <td><pre class="mb-0"><code>{{ json_encode($log->request, JSON_PRETTY_PRINT) }}</code></pre></td>
                                <td><pre class="mb-0"><code>{{ json_encode($log->response, JSON_PRETTY_PRINT) }}</code></pre></td>
                                <td>{{ $log->created_at->format('d/m/Y H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
