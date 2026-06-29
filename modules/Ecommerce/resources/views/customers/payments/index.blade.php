@extends('layouts.theme')

@section('title', 'Pagos de ' . $customer->name)

@section('page_header')
    @include('core::components.card', ['title' => 'Ecommerce - Pagos del cliente'])
@endsection

@section('content')
    <div class="widget-content searchable-container list">
        @include('core::components.alerts')

        <div class="card">
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Pagos de {{ $customer->name }}</h5>
                        <p class="small mb-0 text-muted">Historial de pagos asociados a este cliente</p>
                    </div>
                    @if(\Route::has('ecommerce.customers.show'))
                        <a href="{{ route('ecommerce.customers.show', $customer) }}" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Volver al cliente
                        </a>
                    @else
                        <a href="{{ route('ecommerce.customers.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Volver a clientes
                        </a>
                    @endif
                </div>
            </div>

            <div class="card-body">
                @if($payments->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Referencia</th>
                                    <th>Orden</th>
                                    <th>Monto</th>
                                    <th>Estado</th>
                                    <th>Fecha</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($payments as $payment)
                                    <tr>
                                        <td>
                                            <span class="fw-semibold">{{ $payment->reference ?? $payment->code ?? ('PAG-' . str_pad($payment->id, 6, '0', STR_PAD_LEFT)) }}</span>
                                        </td>
                                        <td>
                                            @if($payment->order ?? false)
                                                @if(\Route::has('ecommerce.orders.show'))
                                                    <a href="{{ route('ecommerce.orders.show', $payment->order) }}" class="text-decoration-none">
                                                        {{ $payment->order->code }}
                                                    </a>
                                                @else
                                                    <small class="text-muted">{{ $payment->order->code }}</small>
                                                @endif
                                            @else
                                                <small class="text-muted">—</small>
                                            @endif
                                        </td>
                                        <td class="fw-semibold">${{ number_format($payment->amount, 2) }}</td>
                                        <td>
                                            @php
                                                $statusValue = is_object($payment->status) ? $payment->status->value : ($payment->status ?? 'pending');
                                                $statusColor = match($statusValue) {
                                                    'paid', 'completed', 'approved' => 'success',
                                                    'pending'                        => 'warning',
                                                    'failed', 'rejected'             => 'danger',
                                                    default                          => 'secondary',
                                                };
                                            @endphp
                                            <span class="badge bg-{{ $statusColor }}">{{ $statusValue }}</span>
                                        </td>
                                        <td>
                                            <small class="text-muted">{{ $payment->created_at?->format('d/m/Y H:i') ?? '—' }}</small>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-credit-card fa-4x text-muted opacity-50 mb-4"></i>
                        <h5 class="text-muted mb-2">Sin pagos registrados</h5>
                        <p class="text-muted mb-0 small">Este cliente no tiene pagos registrados todavia.</p>
                    </div>
                @endif
            </div>

            @if($payments->hasPages())
                <div class="card-footer">{{ $payments->withQueryString()->links() }}</div>
            @endif
        </div>
    </div>
@endsection
