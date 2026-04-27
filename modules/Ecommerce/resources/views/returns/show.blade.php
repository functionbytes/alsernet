@extends('layouts.theme')

@section('title', 'Devolucion #' . $return->id)

@section('content')
    @include('core::components.card', ['title' => 'Ecommerce - Devolucion #' . $return->id])

    @include('core::components.alerts')

    @php
        $badgeClass = match($return->return_status) {
            'pending'   => 'bg-warning text-dark',
            'approved'  => 'bg-success',
            'rejected'  => 'bg-danger',
            'completed' => 'bg-primary',
            default     => 'bg-secondary',
        };

        $reasonLabels = [
            'defective'         => 'Defectuoso',
            'wrong_item'        => 'Artículo incorrecto',
            'not_as_described'  => 'No coincide descripción',
            'other'             => 'Otro',
        ];
    @endphp

    <div class="row g-3">

        {{-- COLUMNA PRINCIPAL --}}
        <div class="col-md-8">

            {{-- Informacion del pedido --}}
            <div class="card mb-3">
                <div class="card-header p-3 border-bottom d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0 fw-bold">Informacion del pedido</h5>
                        <small class="text-muted">Orden original asociada a esta devolucion</small>
                    </div>
                    <a href="{{ route('ecommerce.orders.show', $return->order) }}" class="btn btn-sm btn-outline-secondary">
                        {{ $return->order?->code ?? '—' }}
                    </a>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Producto</th>
                                <th class="text-center">Cantidad</th>
                                <th class="text-end">Precio unit.</th>
                                <th class="text-end pe-3">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($return->order->items as $item)
                                <tr>
                                    <td class="ps-3">
                                        <div class="d-flex align-items-center gap-2">
                                            @if($item->product_image)
                                                <img src="{{ $item->product_image }}" class="rounded object-fit-cover flex-shrink-0" width="44" height="44" alt="{{ $item->product_name }}">
                                            @else
                                                <div class="rounded bg-light d-flex align-items-center justify-content-center flex-shrink-0" style="width:44px;height:44px">
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
                                <td colspan="3" class="text-end">Subtotal</td>
                                <td class="text-end pe-3 fw-semibold">${{ number_format($return->order->sub_total, 2) }}</td>
                            </tr>
                            @if(($return->order->discount_amount ?? 0) > 0)
                                <tr>
                                    <td colspan="3" class="text-end">Descuento</td>
                                    <td class="text-end pe-3 text-danger">-${{ number_format($return->order->discount_amount, 2) }}</td>
                                </tr>
                            @endif
                            @if(($return->order->shipping_amount ?? 0) > 0)
                                <tr>
                                    <td colspan="3" class="text-end">Envio</td>
                                    <td class="text-end pe-3">${{ number_format($return->order->shipping_amount, 2) }}</td>
                                </tr>
                            @endif
                            <tr>
                                <td colspan="3" class="text-end fw-bold">Total</td>
                                <td class="text-end pe-3 fw-bold text-primary">${{ number_format($return->order->total, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            {{-- Productos a devolver --}}
            <div class="card mb-3">
                <div class="card-header p-3 border-bottom">
                    <h5 class="mb-0 fw-bold">Productos a devolver</h5>
                    <small class="text-muted">Items incluidos en esta solicitud de devolucion</small>
                </div>
                <div class="card-body p-0">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Producto</th>
                                <th class="text-center">Cantidad</th>
                                <th class="pe-3">Motivo</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($return->items as $item)
                                <tr>
                                    <td class="ps-3 fw-semibold">{{ $item->product_name }}</td>
                                    <td class="text-center">{{ $item->qty }}</td>
                                    <td class="pe-3">
                                        <small class="text-muted">{{ $reasonLabels[$item->reason] ?? ucfirst($item->reason ?? '—') }}</small>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-3">Sin productos registrados.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Historial de devolucion --}}
            <div class="card">
                <div class="card-header p-3 border-bottom">
                    <h5 class="mb-0 fw-bold">Historial de devolucion</h5>
                    <small class="text-muted">Registro cronologico de cambios</small>
                </div>
                <div class="card-body">
                    @if($return->histories->count() > 0)
                        <ul class="list-unstyled mb-0">
                            @foreach($return->histories as $history)
                                <li class="d-flex gap-3 pb-3 {{ !$loop->last ? 'border-bottom mb-3' : '' }}">
                                    <div class="flex-shrink-0 mt-1">
                                        <span class="badge bg-light text-dark rounded-circle p-2">
                                            <i class="fas fa-circle-dot"></i>
                                        </span>
                                    </div>
                                    <div>
                                        <div class="fw-semibold">{{ $history->action }}</div>
                                        @if($history->description)
                                            <p class="mb-1 text-muted small">{{ $history->description }}</p>
                                        @endif
                                        <small class="text-muted">{{ $history->created_at->format('d/m/Y H:i') }}</small>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-muted mb-0">Sin historial registrado.</p>
                    @endif
                </div>
            </div>

        </div>

        {{-- COLUMNA LATERAL --}}
        <div class="col-md-4">

            {{-- Cliente --}}
            <div class="card mb-3">
                <div class="card-header p-3 border-bottom">
                    <h6 class="mb-0 fw-bold">Cliente</h6>
                </div>
                <div class="card-body">
                    @if($return->customer)
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold flex-shrink-0" style="width:42px;height:42px;font-size:1.1rem">
                                {{ strtoupper(substr($return->customer->name, 0, 1)) }}
                            </div>
                            <div>
                                <div class="fw-semibold">{{ $return->customer->name }}</div>
                                <small class="text-muted">{{ $return->customer->email }}</small>
                            </div>
                        </div>
                        @if($return->customer->phone)
                            <div class="d-flex align-items-center gap-2 text-muted small">
                                <i class="fas fa-phone"></i>
                                <span>{{ $return->customer->phone }}</span>
                            </div>
                        @endif
                    @else
                        <p class="text-muted mb-0">Invitado</p>
                    @endif
                </div>
            </div>

            {{-- Estado de devolucion --}}
            <div class="card mb-3">
                <div class="card-header p-3 border-bottom">
                    <h6 class="mb-0 fw-bold">Estado de devolucion</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('ecommerce.returns.status', $return) }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="return_status" class="form-label">Estado actual</label>
                            <select name="return_status" id="return_status" class="form-select">
                                <option value="pending"   {{ $return->return_status === 'pending'   ? 'selected' : '' }}>Pendiente</option>
                                <option value="approved"  {{ $return->return_status === 'approved'  ? 'selected' : '' }}>Aprobada</option>
                                <option value="rejected"  {{ $return->return_status === 'rejected'  ? 'selected' : '' }}>Rechazada</option>
                                <option value="completed" {{ $return->return_status === 'completed' ? 'selected' : '' }}>Completada</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Actualizar estado</button>
                    </form>
                </div>
            </div>

            {{-- Detalles --}}
            <div class="card">
                <div class="card-header p-3 border-bottom">
                    <h6 class="mb-0 fw-bold">Detalles</h6>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <tbody>
                            <tr>
                                <td class="ps-3 text-muted">ID</td>
                                <td class="pe-3 fw-semibold">#{{ $return->id }}</td>
                            </tr>
                            <tr>
                                <td class="ps-3 text-muted">Orden</td>
                                <td class="pe-3">
                                    <a href="{{ route('ecommerce.orders.show', $return->order) }}" class="text-decoration-none fw-semibold">
                                        {{ $return->order?->code ?? '—' }}
                                    </a>
                                </td>
                            </tr>
                            <tr>
                                <td class="ps-3 text-muted">Estado orden</td>
                                <td class="pe-3">{{ $return->order_status ?? '—' }}</td>
                            </tr>
                            <tr>
                                <td class="ps-3 text-muted">Fecha solicitud</td>
                                <td class="pe-3">
                                    <small>{{ $return->created_at->format('d/m/Y H:i') }}</small>
                                </td>
                            </tr>
                            <tr>
                                <td class="ps-3 text-muted">Motivo</td>
                                <td class="pe-3">
                                    <small>{{ $return->reason ?? '—' }}</small>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    {{-- Footer acciones --}}
    <div class="d-flex gap-2 mt-3">
        <a href="{{ route('ecommerce.returns.index') }}" class="btn btn-light">
            Volver a devoluciones
        </a>
        <a href="{{ route('ecommerce.returns.edit', $return) }}" class="btn btn-outline-secondary">
            Editar
        </a>
    </div>

@endsection
