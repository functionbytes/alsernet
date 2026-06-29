@extends('layouts.theme')

@section('title', 'Cliente - ' . $customer->name)

@section('page_header')
    @include('core::components.card', ['title' => 'Ecommerce - Cliente'])
@endsection

@section('content')
    <div class="widget-content searchable-container list">
        @include('core::components.alerts')

        <div class="row g-3">
            {{-- Info del cliente --}}
            <div class="col-12 col-lg-4">
                <div class="card">
                    <div class="card-header border-bottom p-3">
                        <h6 class="mb-0 fw-bold">Informacion del cliente</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3 mb-4">
                            @if($customer->avatar)
                                <img src="{{ $customer->avatar }}" class="rounded-circle object-fit-cover flex-shrink-0" width="56" height="56">
                            @else
                                <div class="bg-light rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width:56px;height:56px">
                                    <i class="fas fa-user fa-lg text-muted"></i>
                                </div>
                            @endif
                            <div>
                                <div class="fw-bold">{{ $customer->name }}</div>
                                <span class="badge bg-{{ $customer->status->value === 'active' ? 'success' : 'secondary' }}">
                                    {{ $customer->status->value }}
                                </span>
                            </div>
                        </div>

                        <dl class="row g-2 mb-0">
                            <dt class="col-5 text-muted fw-normal small">Email</dt>
                            <dd class="col-7 mb-0 small">{{ $customer->email }}</dd>

                            <dt class="col-5 text-muted fw-normal small">Telefono</dt>
                            <dd class="col-7 mb-0 small">{{ $customer->phone ?? '—' }}</dd>

                            <dt class="col-5 text-muted fw-normal small">Registro</dt>
                            <dd class="col-7 mb-0 small">{{ $customer->created_at->format('d/m/Y') }}</dd>
                        </dl>
                    </div>
                    <div class="card-footer bg-white border-top">
                        <a href="{{ route('ecommerce.customers.edit', $customer) }}" class="btn btn-primary w-100 mb-2">
                            Editar cliente
                        </a>
                        <a href="{{ route('ecommerce.customers.index') }}" class="btn btn-light w-100">
                            Volver al listado
                        </a>
                    </div>
                </div>
            </div>

            {{-- Tabs --}}
            <div class="col-12 col-lg-8">
                <div class="card">
                    <div class="card-header border-bottom p-0">
                        <ul class="nav nav-tabs border-0 px-3" id="customerTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="tab-orders" data-bs-toggle="tab" data-bs-target="#pane-orders" type="button" role="tab">
                                    Ordenes
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a class="nav-link" href="{{ route('ecommerce.customers.addresses.index', $customer) }}">
                                    Direcciones
                                </a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a class="nav-link" href="{{ route('ecommerce.customers.payments.index', $customer) }}">
                                    Pagos
                                </a>
                            </li>
                        </ul>
                    </div>
                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="pane-orders" role="tabpanel">
                            @if(isset($orders) && $orders->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Codigo</th>
                                                <th>Total</th>
                                                <th>Estado</th>
                                                <th>Fecha</th>
                                                <th class="text-center">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($orders as $order)
                                                <tr>
                                                    <td>
                                                        <a href="{{ route('ecommerce.orders.show', $order) }}" class="fw-semibold text-decoration-none">
                                                            {{ $order->code }}
                                                        </a>
                                                    </td>
                                                    <td>${{ number_format($order->total, 2) }}</td>
                                                    <td>
                                                        <span class="badge bg-{{ match($order->status->value) {
                                                            'completed' => 'success',
                                                            'cancelled' => 'danger',
                                                            'processing' => 'primary',
                                                            'shipped' => 'info',
                                                            default => 'secondary'
                                                        } }}">
                                                            {{ $order->status->value }}
                                                        </span>
                                                    </td>
                                                    <td><small class="text-muted">{{ $order->created_at->format('d/m/Y') }}</small></td>
                                                    <td class="text-center">
                                                        <div class="dropdown">
                                                            <button type="button" class="btn btn-sm btn-light" data-bs-toggle="dropdown">
                                                                <i class="fas fa-ellipsis-vertical"></i>
                                                            </button>
                                                            <ul class="dropdown-menu dropdown-menu-end">
                                                                <li>
                                                                    <a class="dropdown-item" href="{{ route('ecommerce.orders.show', $order) }}">Ver detalle</a>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-center py-5">
                                    <i class="fas fa-shopping-bag fa-3x text-muted opacity-50 mb-3"></i>
                                    <p class="text-muted mb-0">Este cliente no tiene ordenes</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
