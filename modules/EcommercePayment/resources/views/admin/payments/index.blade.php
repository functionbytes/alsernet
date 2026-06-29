@extends('layouts.theme')

@section('title', 'Pagos')

@section('page_header')
    @include('core::components.card', ['title' => 'Ecommerce - Pagos'])
@endsection

@section('content')
    @include('core::components.alerts')

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Pagos</h5>
            <a href="{{ route('ecommerce-payment.payments.export', request()->only(['status', 'search', 'date_from', 'date_to', 'payment_channel'])) }}" class="btn btn-sm btn-outline-success">
                <i class="fas fa-file-excel me-1"></i> Exportar
            </a>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3 mb-4">
                <div class="col-md-3">
                    <input type="text" name="search" class="form-control" placeholder="Buscar por referencia o orden..." value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">Todos los estados</option>
                        @foreach($statuses as $status)
                            <option value="{{ $status->value }}" {{ request('status') == $status->value ? 'selected' : '' }}>
                                {{ $status->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="payment_channel" class="form-select">
                        <option value="">Todos los métodos</option>
                        @foreach($channels as $channel)
                            <option value="{{ $channel }}" {{ request('payment_channel') === $channel ? 'selected' : '' }}>
                                {{ match($channel) {
                                    'cod' => 'Contra entrega',
                                    'bank_transfer' => 'Transferencia',
                                    'wompi' => 'Wompi',
                                    default => ucfirst(str_replace('_', ' ', $channel))
                                } }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_from" class="form-control" placeholder="Desde" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_to" class="form-control" placeholder="Hasta" value="{{ request('date_to') }}">
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
                @if(request()->hasAny(['search', 'status', 'date_from', 'date_to', 'payment_channel']))
                <div class="col-md-2">
                    <a href="{{ route('ecommerce-payment.payments.index') }}" class="btn btn-outline-secondary w-100">
                        <i class="fas fa-times me-2"></i>{{ __('Limpiar') }}
                    </a>
                </div>
                @endif
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('ID') }}</th>
                            <th>{{ __('Referencia') }}</th>
                            <th>{{ __('Orden') }}</th>
                            <th>{{ __('Monto') }}</th>
                            <th>{{ __('Canal') }}</th>
                            <th>{{ __('Estado') }}</th>
                            <th>{{ __('Fecha') }}</th>
                            <th class="text-center">{{ __('Acciones') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payments as $payment)
                            <tr>
                                <td>#{{ $payment->id }}</td>
                                <td><code>{{ $payment->charge_id }}</code></td>
                                <td>
                                    @if($payment->order)
                                        <a href="{{ route('ecommerce.orders.show', $payment->order) }}">
                                            {{ $payment->order->code }}
                                        </a>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    <strong>{{ number_format($payment->amount, 2) }}</strong>
                                    <small class="text-muted">{{ $payment->currency }}</small>
                                </td>
                                <td><span class="badge bg-info">{{ $payment->payment_channel }}</span></td>
                                <td>
                                    <span class="{{ $payment->status->badgeClass() }}">
                                        {{ $payment->status->label() }}
                                    </span>
                                </td>
                                <td>{{ $payment->created_at->format('d/m/Y H:i') }}</td>
                                <td class="text-center">
                                    <div class="dropdown">
                                        <button type="button" class="btn btn-sm btn-light" data-bs-toggle="dropdown">
                                            <i class="fas fa-ellipsis-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li><a class="dropdown-item" href="{{ route('ecommerce-payment.payments.show', $payment) }}">Ver detalle</a></li>
                                            @if($payment->order)
                                                <li><a class="dropdown-item" href="{{ route('ecommerce.orders.show', $payment->order) }}">Ver orden</a></li>
                                            @endif
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-2x mb-2"></i>
                                    <p class="mb-0">{{ __('No hay pagos registrados.') }}</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-end">
                {{ $payments->withQueryString()->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
