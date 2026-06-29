@extends('layouts.theme')

@section('title', 'Detalle de descuento')

@section('page_header')
    @include('core::components.card', ['title' => 'Ecommerce - Detalle de descuento'])
@endsection

@section('content')
    <div class="widget-content searchable-container list">
        @include('core::components.alerts')

        <div class="row g-3">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header p-3 border-bottom">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-1 fw-bold">{{ $discount->title }}</h5>
                                @if($discount->code)
                                    <code>{{ $discount->code }}</code>
                                @endif
                            </div>
                            <span class="badge bg-{{ $discount->is_active && !$discount->isExpired() ? 'success' : 'secondary' }}">
                                {{ $discount->is_active && !$discount->isExpired() ? 'Activo' : 'Inactivo' }}
                            </span>
                        </div>
                    </div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-4 text-muted fw-normal">Tipo</dt>
                            <dd class="col-sm-8">
                                @php
                                    $typeLabel = match($discount->type->value) {
                                        'fixed' => 'Monto fijo',
                                        'percentage' => 'Porcentaje',
                                        'free_shipping' => 'Envio gratis',
                                        default => $discount->type->value,
                                    };
                                    $typeColor = match($discount->type->value) {
                                        'fixed' => 'primary',
                                        'percentage' => 'info',
                                        'free_shipping' => 'success',
                                        default => 'secondary',
                                    };
                                @endphp
                                <span class="badge bg-{{ $typeColor }}">{{ $typeLabel }}</span>
                            </dd>

                            <dt class="col-sm-4 text-muted fw-normal">Valor</dt>
                            <dd class="col-sm-8 fw-semibold">
                                @if($discount->type->value === 'percentage')
                                    {{ $discount->value }}%
                                @elseif($discount->type->value === 'free_shipping')
                                    Envio gratis
                                @else
                                    ${{ number_format($discount->value, 2) }}
                                @endif
                            </dd>

                            <dt class="col-sm-4 text-muted fw-normal">Precio minimo de orden</dt>
                            <dd class="col-sm-8">{{ $discount->min_order_price ? '$' . number_format($discount->min_order_price, 2) : '—' }}</dd>

                            @if($discount->type->value === 'free_shipping')
                            <dt class="col-sm-4 text-muted fw-normal">Envio gratis</dt>
                            <dd class="col-sm-8"><span class="badge bg-success">Si</span></dd>
                            @endif

                            <dt class="col-sm-4 text-muted fw-normal">Fecha inicio</dt>
                            <dd class="col-sm-8">{{ $discount->start_date?->format('d/m/Y H:i') ?? '—' }}</dd>

                            <dt class="col-sm-4 text-muted fw-normal">Fecha fin</dt>
                            <dd class="col-sm-8">{{ $discount->end_date?->format('d/m/Y H:i') ?? '—' }}</dd>
                        </dl>
                    </div>
                </div>

                @if(isset($discount->uses) && $discount->uses->count() > 0)
                    <div class="card mt-3">
                        <div class="card-header p-3 border-bottom">
                            <h6 class="mb-0 fw-bold">Ultimos usos</h6>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Cliente</th>
                                            <th>Orden</th>
                                            <th>Fecha</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($discount->uses as $use)
                                            <tr>
                                                <td class="small">{{ $use->customer->name ?? $use->customer_name ?? '—' }}</td>
                                                <td>
                                                    @if(isset($use->order))
                                                        <a href="{{ route('ecommerce.orders.show', $use->order) }}" class="text-decoration-none small">
                                                            {{ $use->order->code }}
                                                        </a>
                                                    @else
                                                        <span class="text-muted small">—</span>
                                                    @endif
                                                </td>
                                                <td><small class="text-muted">{{ $use->created_at->format('d/m/Y') }}</small></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <div class="col-lg-4">
                {{-- Usos con barra de progreso --}}
                <div class="card mb-3">
                    <div class="card-header border-bottom p-3">
                        <h6 class="mb-0 fw-bold">Uso del cupon</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-baseline mb-2">
                            <span class="text-muted small">Usos realizados</span>
                            <span class="fw-bold">
                                {{ $discount->total_used }}
                                @if($discount->quantity)
                                    / {{ $discount->quantity }}
                                @endif
                            </span>
                        </div>

                        @if($discount->quantity && $discount->quantity > 0)
                            @php $pct = min(100, round(($discount->total_used / $discount->quantity) * 100)); @endphp
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-primary" role="progressbar"
                                    style="width: {{ $pct }}%"
                                    aria-valuenow="{{ $pct }}"
                                    aria-valuemin="0"
                                    aria-valuemax="100">
                                </div>
                            </div>
                            <div class="text-end mt-1">
                                <small class="text-muted">{{ $pct }}% utilizado</small>
                            </div>
                        @else
                            <div class="text-muted small">Sin limite de usos</div>
                        @endif
                    </div>
                </div>

                {{-- Acciones --}}
                <div class="card">
                    <div class="card-body">
                        <a href="{{ route('ecommerce.discounts.edit', $discount) }}" class="btn btn-primary w-100 mb-2">
                            Editar descuento
                        </a>
                        <a href="{{ route('ecommerce.discounts.index') }}" class="btn btn-light w-100">
                            Volver al listado
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
