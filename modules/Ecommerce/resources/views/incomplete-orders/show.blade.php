@extends('layouts.theme')

@section('title', 'Orden incompleta - ' . $order->code)

@section('page_header')
    @include('core::components.card', ['title' => 'Ecommerce - Ordenes incompletas'])
@endsection

@section('content')
    <div class="widget-content searchable-container list">
        @include('core::components.alerts')

        {{-- Banner informativo con enlace de recupero --}}
        <div class="alert alert-warning border-warning mb-4" role="alert">
            <p class="fw-semibold mb-1">
                Un pedido incompleto es cuando un cliente potencial coloca artículos en su carrito de compras y va hasta la página de pago, pero luego no completa la transacción.
            </p>
            <p class="mb-2">Si se ha puesto en contacto con clientes y quieren seguir comprando, puede ayudarles a completar su pedido siguiendo el enlace:</p>
            <div class="d-flex gap-2 align-items-center flex-wrap">
                @if($order->token)
                    <input type="text" class="form-control form-control-sm" style="max-width:480px"
                        value="{{ url('/orden/' . $order->token . '/recover') }}" readonly onclick="this.select()">
                    @php
                        $recoveryEmail = $order->customer?->email ?? $order->shippingAddress?->email;
                    @endphp
                    @if($recoveryEmail)
                        <form action="{{ route('ecommerce.incomplete-orders.send-recovery', $order) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-secondary"
                                onclick="return confirm('¿Enviar correo de recupero a {{ $recoveryEmail }}?')">
                                Enviar correo de recupero al cliente
                            </button>
                        </form>
                    @else
                        <span class="text-danger small">No se encontró correo electrónico, no es posible enviar el correo de recupero.</span>
                    @endif
                @else
                    <span class="text-muted small">Esta orden no tiene un token de recupero.</span>
                @endif
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-8">

                {{-- Información del pedido --}}
                <div class="card mb-3">
                    <div class="card-header p-3 border-bottom border-light">
                        <h5 class="mb-0 fw-bold">Información del pedido</h5>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-hover align-middle mb-0">
                            <tbody>
                                @foreach($order->items as $item)
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
                                        <td class="text-end">${{ number_format($item->price, 2) }}</td>
                                        <td class="text-center text-muted">x {{ $item->qty }}</td>
                                        <td class="text-end fw-semibold">${{ number_format($item->total, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer">
                        <div class="row justify-content-end">
                            <div class="col-md-5">
                                <table class="table table-sm table-borderless mb-0">
                                    <tr>
                                        <td class="text-muted">Cantidad</td>
                                        <td class="text-end fw-semibold">{{ $order->items->sum('qty') }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Importe secundario</td>
                                        <td class="text-end fw-semibold">${{ number_format($order->sub_total, 2) }}</td>
                                    </tr>
                                    @if($order->discount_amount > 0)
                                        <tr>
                                            <td class="text-muted">Descuento</td>
                                            <td class="text-end fw-semibold text-danger">-${{ number_format($order->discount_amount, 2) }}</td>
                                        </tr>
                                    @else
                                        <tr>
                                            <td class="text-muted">Descuento</td>
                                            <td class="text-end">$0</td>
                                        </tr>
                                    @endif
                                    <tr>
                                        <td class="text-muted">
                                            Gastos de envío
                                            <div><small class="text-muted">{{ $order->shipping_method ?: 'Por defecto' }}</small></div>
                                        </td>
                                        <td class="text-end fw-semibold">${{ number_format($order->shipping_amount, 2) }}</td>
                                    </tr>
                                    <tr class="border-top">
                                        <td class="fw-bold">Cantidad total</td>
                                        <td class="text-end fw-bold fs-5">${{ number_format($order->total, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Monto de pago</td>
                                        <td class="text-end">$0</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Nota de pedido --}}
                <div class="card mb-3">
                    <div class="card-body">
                        <form action="{{ route('ecommerce.incomplete-orders.note', $order) }}" method="POST">
                            @csrf
                            <label class="form-label fw-semibold">Nota de pedido</label>
                            <textarea name="admin_note" class="form-control mb-3" rows="3"
                                placeholder="Nota sobre el pedido, por ejemplo: tiempo o instrucciones de envío.">{{ $order->admin_note }}</textarea>
                            <div class="text-end">
                                <button type="submit" class="btn btn-outline-secondary btn-sm">Guardar nota</button>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- Marcar como completado --}}
                <form action="{{ route('ecommerce.incomplete-orders.complete', $order) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-primary w-100" onclick="return confirm('¿Marcar esta orden como completada?')">
                        <i class="fas fa-check me-2"></i> Marcar como completado
                    </button>
                </form>

            </div>

            {{-- Sidebar --}}
            <div class="col-md-4">

                {{-- Cliente --}}
                <div class="card mb-3">
                    <div class="card-header"><h6 class="mb-0 fw-bold">Cliente</h6></div>
                    <div class="card-body">
                        @if($order->customer)
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center text-white fw-bold flex-shrink-0"
                                    style="width:42px;height:42px">
                                    {{ strtoupper(substr($order->customer->name, 0, 1)) }}
                                </div>
                                <div>
                                    <p class="mb-0 fw-semibold">{{ $order->customer->name }}</p>
                                    <small class="text-muted">{{ $order->customer->orders()->count() }} pedidos</small>
                                </div>
                            </div>
                            <p class="mb-1 small"><i class="fas fa-envelope me-2 text-muted"></i>{{ $order->customer->email ?? '—' }}</p>
                            @if($order->customer->phone)
                                <p class="mb-0 small"><i class="fas fa-phone me-2 text-muted"></i>{{ $order->customer->phone }}</p>
                            @endif
                        @else
                            <p class="text-muted mb-1">Todavía no tengo una cuenta</p>
                        @endif
                    </div>
                </div>

                {{-- Dirección --}}
                @if($order->shippingAddress)
                    <div class="card mb-3">
                        <div class="card-header"><h6 class="mb-0 fw-bold">Dirección</h6></div>
                        <div class="card-body">
                            <p class="mb-1 small fw-semibold">{{ $order->shippingAddress->name }}</p>
                            @if($order->shippingAddress->phone)
                                <p class="mb-1 small"><i class="fas fa-phone me-2 text-muted"></i>{{ $order->shippingAddress->phone }}</p>
                            @endif
                            @if($order->shippingAddress->email)
                                <p class="mb-1 small"><i class="fas fa-envelope me-2 text-muted"></i>{{ $order->shippingAddress->email }}</p>
                            @endif
                            <p class="mb-0 small">
                                <i class="fas fa-map-marker-alt me-2 text-muted"></i>
                                {{ $order->shippingAddress->address }},
                                {{ $order->shippingAddress->city }},
                                {{ $order->shippingAddress->country }}
                            </p>
                        </div>
                    </div>
                @endif

                {{-- Remisión --}}
                @if($order->referral)
                    <div class="card mb-3">
                        <div class="card-header"><h6 class="mb-0 fw-bold">Remisión</h6></div>
                        <div class="card-body">
                            <dl class="row mb-0 small">
                                @if($order->referral->ip)
                                    <dt class="col-5 text-muted fw-normal">IP</dt>
                                    <dd class="col-7">{{ $order->referral->ip }}</dd>
                                @endif
                                @if($order->referral->landing_domain)
                                    <dt class="col-5 text-muted fw-normal">Dominio de aterrizaje</dt>
                                    <dd class="col-7">{{ $order->referral->landing_domain }}</dd>
                                @endif
                                @if($order->referral->landing_page)
                                    <dt class="col-5 text-muted fw-normal">Página de destino</dt>
                                    <dd class="col-7">{{ $order->referral->landing_page }}</dd>
                                @endif
                                @if($order->referral->referral_url)
                                    <dt class="col-5 text-muted fw-normal">URL de referencia</dt>
                                    <dd class="col-7 text-break">{{ $order->referral->referral_url }}</dd>
                                @endif
                                @if($order->referral->referral_domain)
                                    <dt class="col-5 text-muted fw-normal">Dominio de referencia</dt>
                                    <dd class="col-7">{{ $order->referral->referral_domain }}</dd>
                                @endif
                            </dl>
                        </div>
                    </div>
                @endif

            </div>
        </div>

        <div class="mt-3">
            <a href="{{ route('ecommerce.incomplete-orders.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i> Volver a ordenes incompletas
            </a>
        </div>
    </div>
@endsection
