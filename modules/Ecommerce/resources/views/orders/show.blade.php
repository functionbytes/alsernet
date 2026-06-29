@extends('layouts.theme')

@section('title', 'Orden ' . $order->code)

@section('page_header')
    @include('core::components.card', ['title' => 'Ecommerce - Orden ' . $order->code])
@endsection

@section('content')
    <div class="widget-content searchable-container list">
        @include('core::components.alerts')

        <div class="row g-3">

            {{-- COLUMNA PRINCIPAL --}}
            <div class="col-md-8">

                {{-- Productos --}}
                <div class="card mb-3">
                    <div class="card-header p-3 border-bottom border-light d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0 fw-bold">Informacion del pedido {{ $order->code }}</h5>
                        </div>
                        <span class="{{ $order->status->badgeClass() }} fs-6">{{ $order->status->label() }}</span>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Producto</th>
                                    <th class="text-center">Cantidad</th>
                                    <th class="text-end">Precio</th>
                                    <th class="text-end pe-3">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($order->items as $item)
                                    <tr>
                                        <td class="ps-3">
                                            <div class="d-flex align-items-center gap-2">
                                                @if($item->product_image)
                                                    <img src="{{ $item->product_image }}" class="rounded object-fit-cover flex-shrink-0" width="48" height="48">
                                                @else
                                                    <div class="rounded bg-light d-flex align-items-center justify-content-center flex-shrink-0" style="width:48px;height:48px">
                                                        <i class="fas fa-box text-muted"></i>
                                                    </div>
                                                @endif
                                                <div>
                                                    <div class="fw-semibold">{{ $item->product_name }}</div>
                                                    @if($item->product?->sku)
                                                        <small class="text-muted">SKU: {{ $item->product->sku }}</small>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">{{ $item->qty }}</td>
                                        <td class="text-end">${{ number_format($item->price, 2) }}</td>
                                        <td class="text-end pe-3 fw-semibold">${{ number_format($item->total, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="3" class="text-end ps-3">Subtotal</td>
                                    <td class="text-end pe-3 fw-semibold">${{ number_format($order->sub_total, 2) }}</td>
                                </tr>
                                @if($order->shipping_amount > 0)
                                    <tr>
                                        <td colspan="3" class="text-end">Envio</td>
                                        <td class="text-end pe-3">${{ number_format($order->shipping_amount, 2) }}</td>
                                    </tr>
                                @endif
                                @if($order->tax_amount > 0)
                                    <tr>
                                        <td colspan="3" class="text-end">Impuestos</td>
                                        <td class="text-end pe-3">${{ number_format($order->tax_amount, 2) }}</td>
                                    </tr>
                                @endif
                                @if($order->discount_amount > 0)
                                    <tr>
                                        <td colspan="3" class="text-end">Descuento
                                            @if($order->coupon_code)
                                                <small class="text-muted">({{ $order->coupon_code }})</small>
                                            @endif
                                        </td>
                                        <td class="text-end pe-3 text-danger">-${{ number_format($order->discount_amount, 2) }}</td>
                                    </tr>
                                @endif
                                <tr>
                                    <td colspan="3" class="text-end fw-bold">Total</td>
                                    <td class="text-end pe-3 fw-bold fs-5 text-primary">${{ number_format($order->total, 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <div class="card-footer d-flex gap-2">
                        <a href="{{ route('ecommerce.orders.pdf', $order) }}" target="_blank" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-print me-1"></i> Imprimir factura
                        </a>
                        <a href="{{ route('ecommerce.orders.pdf', $order) }}" target="_blank" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-download me-1"></i> Descargar factura
                        </a>
                    </div>
                </div>

                {{-- Notas --}}
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nota del cliente</label>
                            <form action="{{ route('ecommerce.orders.customer-note', $order) }}" method="POST">
                                @csrf
                                <textarea name="customer_note" class="form-control mb-2" rows="2" placeholder="Nota del pedido...">{{ $order->customer_note }}</textarea>
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">Nota añadida por el cliente en la pagina de pago.</small>
                                    <button type="submit" class="btn btn-sm btn-outline-secondary">Guardar</button>
                                </div>
                            </form>
                        </div>
                        <div>
                            <label class="form-label fw-semibold">Nota privada (admin)</label>
                            <form action="{{ route('ecommerce.orders.note', $order) }}" method="POST">
                                @csrf
                                <textarea name="admin_note" class="form-control mb-2" rows="3" placeholder="Nota interna...">{{ $order->admin_note }}</textarea>
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">El cliente no puede verla.</small>
                                    <button type="submit" class="btn btn-sm btn-primary">Guardar nota</button>
                                </div>
                            </form>
                        </div>

                        <hr class="my-3">

                        <div>
                            <label class="form-label fw-semibold">Fecha y hora de entrega programada</label>
                            <form action="{{ route('ecommerce.orders.delivery', $order) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <div class="row g-2 mb-2">
                                    <div class="col-sm-6">
                                        <label class="form-label small text-muted">Fecha</label>
                                        <input type="date" name="delivery_date" class="form-control form-control-sm"
                                            value="{{ $order->delivery_date?->format('Y-m-d') }}">
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label small text-muted">Hora</label>
                                        <input type="time" name="delivery_time" class="form-control form-control-sm"
                                            value="{{ $order->delivery_time }}">
                                    </div>
                                </div>
                                <small class="text-muted d-block mb-2">Fecha y hora estimada de entrega al cliente.</small>
                                <button type="submit" class="btn btn-sm btn-primary">Guardar</button>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- Metodo de pago y envio --}}
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <p class="mb-1 small fw-semibold text-muted text-uppercase">Metodo de pago</p>
                                <p class="mb-0">{{ $order->payment_method ?: '—' }}</p>
                            </div>
                            <div class="col-sm-6">
                                <p class="mb-1 small fw-semibold text-muted text-uppercase">Estado de pago</p>
                                @php
                                    $payLabels = ['paid' => 'Pagado', 'pending' => 'Pendiente', 'failed' => 'Fallido', 'refunded' => 'Reembolsado'];
                                    $payColors = ['paid' => 'success', 'pending' => 'warning', 'failed' => 'danger', 'refunded' => 'secondary'];
                                @endphp
                                <span class="badge bg-{{ $payColors[$order->payment_status] ?? 'secondary' }}">
                                    {{ $payLabels[$order->payment_status] ?? $order->payment_status }}
                                </span>
                            </div>
                            <div class="col-sm-6">
                                <p class="mb-1 small fw-semibold text-muted text-uppercase">Metodo de envio</p>
                                <p class="mb-0">{{ $order->shipping_method ?: '—' }}</p>
                            </div>
                            <div class="col-sm-6">
                                <p class="mb-1 small fw-semibold text-muted text-uppercase">Fecha del pedido</p>
                                <p class="mb-0">{{ $order->created_at->format('d/m/Y H:i') }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Estado y acciones --}}
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <i class="fas fa-check-circle text-success"></i>
                            <span class="fw-semibold">ORDEN RECIBIDA</span>
                        </div>

                        @if($order->payment_status !== 'paid')
                            <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fas fa-clock text-warning"></i>
                                    <span class="fw-semibold">PAGO PENDIENTE</span>
                                </div>
                                <form action="{{ route('ecommerce.orders.confirm-payment', $order) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('¿Confirmar el pago?')">
                                        Confirmar pago
                                    </button>
                                </form>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Historial --}}
                <div class="card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold">Historial</h6>
                        <form action="{{ route('ecommerce.orders.resend-confirmation', $order) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-paper-plane me-1"></i> Reenviar confirmacion
                            </button>
                        </form>
                    </div>
                    <div class="card-body p-0">
                        @if($order->histories->count() > 0)
                            <ul class="list-unstyled mb-0">
                                @foreach($order->histories->sortByDesc('created_at') as $history)
                                    <li class="d-flex align-items-start gap-3 p-3 border-bottom">
                                        <div class="rounded-circle bg-primary flex-shrink-0" style="width:8px;height:8px;margin-top:6px"></div>
                                        <div class="flex-grow-1">
                                            <p class="mb-0 small">{{ $history->description ?: $history->action }}</p>
                                            <small class="text-muted">{{ $history->created_at->format('d/m/Y H:i') }}
                                                @if($history->user->name ?? null)
                                                    · {{ $history->user->name }}
                                                @endif
                                            </small>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-muted small p-3 mb-0">Sin historial registrado.</p>
                        @endif
                    </div>
                </div>

                {{-- Historial de cambios (Spatie Activity Log) --}}
                @if(class_exists(\Spatie\Activitylog\Models\Activity::class))
                    @php
                        $activities = \Spatie\Activitylog\Models\Activity::query()
                            ->where('subject_type', \Modules\Ecommerce\Models\Order::class)
                            ->where('subject_id', $order->id)
                            ->latest()
                            ->limit(20)
                            ->get();
                    @endphp
                    <div class="card mb-3">
                        <div class="card-header">
                            <h6 class="mb-0 fw-bold">Historial de cambios</h6>
                        </div>
                        <div class="card-body p-0">
                            @forelse($activities as $activity)
                                <div class="d-flex border-bottom py-2 px-3">
                                    <div class="me-3 text-muted small flex-shrink-0" style="width:140px">
                                        {{ $activity->created_at->format('d/m/Y H:i') }}
                                    </div>
                                    <div class="flex-grow-1">
                                        <strong>{{ ucfirst($activity->description) }}</strong>
                                        @if($activity->causer)
                                            <span class="text-muted small">— {{ $activity->causer->name ?? 'Sistema' }}</span>
                                        @endif
                                        @if($activity->properties->has('attributes') || $activity->properties->has('old'))
                                            <ul class="small text-muted mb-0 mt-1">
                                                @foreach($activity->properties['attributes'] ?? [] as $key => $value)
                                                    <li>{{ $key }}:
                                                        <s>{{ $activity->properties['old'][$key] ?? '—' }}</s>
                                                        → <strong>{{ is_scalar($value) ? $value : json_encode($value) }}</strong>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <p class="text-muted small p-3 mb-0">Sin cambios registrados.</p>
                            @endforelse
                        </div>
                    </div>
                @endif

                {{-- Descargas digitales --}}
                @php $digitalItems = $order->items->filter(fn($i) => $i->product_type === 'digital'); @endphp
                @if($digitalItems->count() > 0)
                    <div class="card mb-3">
                        <div class="card-header"><h6 class="mb-0 fw-bold">Descargas de productos digitales</h6></div>
                        <div class="card-body p-0">
                            <ul class="list-unstyled mb-0">
                                @foreach($digitalItems as $item)
                                    <li class="d-flex align-items-center justify-content-between p-3 border-bottom">
                                        <div>
                                            <p class="mb-0 fw-semibold">{{ $item->product_name }}</p>
                                            <small class="text-muted">
                                                <i class="fas fa-download me-1"></i>
                                                {{ $item->times_downloaded }} descarga(s)
                                            </small>
                                        </div>
                                        @if($item->times_downloaded === 0)
                                            <span class="badge bg-warning text-dark">Aun no descargado</span>
                                        @else
                                            <span class="badge bg-success">Descargado</span>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

            </div>

            {{-- SIDEBAR --}}
            <div class="col-md-4">

                {{-- Cliente --}}
                <div class="card mb-3">
                    <div class="card-header"><h6 class="mb-0 fw-bold">Cliente</h6></div>
                    <div class="card-body">
                        @if($order->customer->id ?? null)
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center text-white fw-bold flex-shrink-0" style="width:42px;height:42px">
                                    {{ strtoupper(substr($order->customer->name ?? '?', 0, 1)) }}
                                </div>
                                <div>
                                    <p class="mb-0 fw-semibold">{{ $order->customer->name }}</p>
                                    <small class="text-muted">{{ $order->customer->orders()->count() }} pedidos</small>
                                </div>
                            </div>
                            <p class="mb-1 small"><i class="fas fa-envelope me-2 text-muted"></i>{{ $order->customer->email ?? '—' }}</p>
                            <p class="mb-1 small"><i class="fas fa-phone me-2 text-muted"></i>{{ $order->customer->phone ?? '—' }}</p>
                        @else
                            <p class="text-muted mb-0">Invitado</p>
                        @endif
                    </div>
                </div>

                {{-- Informacion de envio --}}
                <div class="card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold">Informacion de envio</h6>
                        <button type="button" class="btn btn-sm btn-link p-0 text-muted" data-bs-toggle="modal" data-bs-target="#editShippingModal">
                            <i class="fas fa-pen"></i>
                        </button>
                    </div>
                    <div class="card-body">
                        @if($order->shippingAddress)
                            <p class="mb-1 small fw-semibold">{{ $order->shippingAddress->name }}</p>
                            @if($order->shippingAddress->phone)
                                <p class="mb-1 small"><i class="fas fa-phone me-2 text-muted"></i>{{ $order->shippingAddress->phone }}</p>
                            @endif
                            @if($order->shippingAddress->email)
                                <p class="mb-1 small"><i class="fas fa-envelope me-2 text-muted"></i>{{ $order->shippingAddress->email }}</p>
                            @endif
                            <p class="mb-0 small">
                                <i class="fas fa-map-marker-alt me-2 text-muted"></i>
                                {{ $order->shippingAddress->address }}, {{ $order->shippingAddress->city }}, {{ $order->shippingAddress->country }}
                            </p>
                        @else
                            <p class="text-muted small mb-0">Sin direccion de envio registrada.</p>
                        @endif
                    </div>
                </div>

                {{-- Remision --}}
                <div class="card mb-3">
                    <div class="card-header"><h6 class="mb-0 fw-bold">Remision</h6></div>
                    <div class="card-body">
                        @if($order->shipments->count() > 0)
                            @foreach($order->shipments as $shipment)
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div>
                                        <p class="mb-0 fw-semibold small">Envio #{{ $shipment->id }}</p>
                                        @if($shipment->tracking_number)
                                            <small class="text-muted">Tracking: {{ $shipment->tracking_number }}</small>
                                        @endif
                                        <small class="text-muted d-block">{{ $shipment->created_at->format('d/m/Y H:i') }}</small>
                                    </div>
                                    <a href="{{ route('ecommerce.shipments.show', $shipment) }}" class="btn btn-sm btn-outline-secondary">Ver</a>
                                </div>
                            @endforeach
                        @else
                            <p class="text-muted small mb-0">Sin envios registrados.</p>
                        @endif
                    </div>
                </div>

                {{-- Actualizar estado --}}
                <div class="card mb-3">
                    <div class="card-header"><h6 class="mb-0 fw-bold">Actualizar estado</h6></div>
                    <div class="card-body">
                        <form action="{{ route('ecommerce.orders.status', $order) }}" method="POST">
                            @csrf
                            <select name="status" class="form-select mb-2">
                                @foreach(\Modules\Ecommerce\Enums\OrderStatus::cases() as $statusOption)
                                    <option value="{{ $statusOption->value }}" {{ $order->status === $statusOption ? 'selected' : '' }}>
                                        {{ $statusOption->label() }}
                                    </option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn btn-primary w-100">Actualizar</button>
                        </form>
                    </div>
                </div>

                {{-- Acciones --}}
                <div class="card mb-3">
                    <div class="card-header"><h6 class="mb-0 fw-bold">Acciones</h6></div>
                    <div class="card-body d-flex flex-column gap-2">

                        <a href="{{ route('ecommerce.orders.reorder', $order) }}" class="btn btn-outline-secondary w-100">Reordenar</a>

                        <a href="{{ route('ecommerce.orders.manage', $order) }}" class="btn btn-outline-secondary w-100">
                            Editar productos
                        </a>

                        @if($order->status->value !== 'cancelled')
                            <button type="button" class="btn btn-outline-danger w-100" data-bs-toggle="modal" data-bs-target="#cancelOrderModal">
                                Cancelar
                            </button>
                        @endif

                        @if($order->shipments->count() === 0)
                            <button type="button" class="btn btn-outline-primary w-100" data-bs-toggle="modal" data-bs-target="#createShipmentModal">
                                Crear envio
                            </button>
                        @endif

                        <a href="{{ route('ecommerce.orders.pdf', $order) }}" target="_blank" class="btn btn-outline-secondary w-100">
                            <i class="fas fa-file-pdf me-1"></i> Descargar PDF
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- Modal: Cancelar orden --}}
    <div class="modal fade" id="cancelOrderModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="{{ route('ecommerce.orders.cancel', $order) }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Cancelar orden</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Motivo</label>
                            <textarea name="cancel_reason" class="form-control" rows="3" placeholder="Motivo de cancelacion (opcional)"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer flex-column">
                        <button type="submit" class="btn btn-danger w-100 mb-2">Cancelar orden</button>
                        <button type="button" class="btn btn-outline-secondary w-100" data-bs-dismiss="modal">Cerrar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal: Crear envio --}}
    <div class="modal fade" id="createShipmentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="{{ route('ecommerce.orders.shipment', $order) }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Crear envio</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Peso (kg)</label>
                            <input type="number" name="weight" class="form-control" step="0.01" min="0">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nota (opcional)</label>
                            <textarea name="note" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer flex-column">
                        <button type="submit" class="btn btn-primary w-100 mb-2">Crear envio</button>
                        <button type="button" class="btn btn-outline-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal: Editar direccion de envio --}}
    <div class="modal fade" id="editShippingModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Informacion de envio</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('ecommerce.orders.shipping-address', $order) }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nombre</label>
                                <input type="text" name="name" class="form-control" value="{{ $order->shippingAddress->name ?? '' }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Telefono</label>
                                <input type="text" name="phone" class="form-control" value="{{ $order->shippingAddress->phone ?? '' }}">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" value="{{ $order->shippingAddress->email ?? '' }}">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Direccion</label>
                                <input type="text" name="address" class="form-control" value="{{ $order->shippingAddress->address ?? '' }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Ciudad</label>
                                <input type="text" name="city" class="form-control" value="{{ $order->shippingAddress->city ?? '' }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Estado/Dpto</label>
                                <input type="text" name="state" class="form-control" value="{{ $order->shippingAddress->state ?? '' }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Pais</label>
                                <input type="text" name="country" class="form-control" value="{{ $order->shippingAddress->country ?? '' }}">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer flex-column">
                        <button type="submit" class="btn btn-primary w-100 mb-2">Guardar</button>
                        <button type="button" class="btn btn-outline-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
