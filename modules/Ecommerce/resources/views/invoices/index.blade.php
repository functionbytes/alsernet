@extends('layouts.theme')

@section('title', 'Facturas')

@section('page_header')
    @include('core::components.card', ['title' => 'Ecommerce - Facturas'])
@endsection

@section('content')
    <div class="widget-content searchable-container list">
        @include('core::components.alerts')

        {{-- Tarjetas de resumen --}}
        <div class="row g-3 mb-3">
            <div class="col-6 col-md-3">
                <div class="card text-center h-100">
                    <div class="card-body py-3">
                        <div class="fs-4 fw-bold">{{ $counts->sum() }}</div>
                        <div class="small text-muted">Total</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card text-center h-100">
                    <div class="card-body py-3">
                        <div class="fs-4 fw-bold text-warning">{{ $counts['pending'] ?? 0 }}</div>
                        <div class="small text-muted">Pendientes</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card text-center h-100">
                    <div class="card-body py-3">
                        <div class="fs-4 fw-bold text-success">{{ $counts['paid'] ?? 0 }}</div>
                        <div class="small text-muted">Pagadas</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card text-center h-100">
                    <div class="card-body py-3">
                        <div class="fs-4 fw-bold text-danger">{{ $counts['cancelled'] ?? 0 }}</div>
                        <div class="small text-muted">Canceladas</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                    <div>
                        <h5 class="mb-1 fw-bold">Facturas</h5>
                        <p class="small mb-0 text-muted">Administra las facturas de órdenes</p>
                    </div>
                    <form method="GET" action="{{ route('ecommerce.invoices.index') }}" class="d-flex gap-2 flex-wrap align-items-center">
                        <input type="text" name="search" class="form-control form-control-sm"
                            placeholder="Buscar por cliente o código..."
                            value="{{ request('search') }}" style="min-width:200px">
                        <select name="status" class="form-select form-select-sm" style="min-width:140px">
                            <option value="">Todos los estados</option>
                            <option value="pending"   {{ request('status') === 'pending'   ? 'selected' : '' }}>Pendiente</option>
                            <option value="paid"      {{ request('status') === 'paid'      ? 'selected' : '' }}>Pagada</option>
                            <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelada</option>
                        </select>
                        <button type="submit" class="btn btn-sm btn-primary">Buscar</button>
                        @if(request('search') || request('status'))
                            <a href="{{ route('ecommerce.invoices.index') }}" class="btn btn-sm btn-outline-secondary">Limpiar</a>
                        @endif
                    </form>
                </div>
            </div>

            <div class="card-body p-0">
                @if($invoices->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">ID</th>
                                    <th>Nombre</th>
                                    <th>Orden</th>
                                    <th>Código</th>
                                    <th>Monto</th>
                                    <th>Fecha</th>
                                    <th>Estado</th>
                                    <th class="text-center pe-3" style="width:80px">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($invoices as $invoice)
                                    @php
                                        $badgeClass = match($invoice->status) {
                                            'paid'      => 'bg-success',
                                            'cancelled' => 'bg-danger',
                                            default     => 'bg-warning text-dark',
                                        };
                                        $statusLabel = match($invoice->status) {
                                            'paid'      => 'Pagada',
                                            'cancelled' => 'Cancelada',
                                            default     => 'Pendiente',
                                        };
                                    @endphp
                                    <tr>
                                        <td class="ps-3">
                                            <small class="text-muted">#{{ $invoice->id }}</small>
                                        </td>
                                        <td>
                                            <div class="fw-semibold small">{{ $invoice->customer_name ?? '—' }}</div>
                                            @if($invoice->customer_email)
                                                <small class="text-muted">{{ $invoice->customer_email }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            @if($invoice->reference)
                                                <a href="{{ route('ecommerce.orders.show', $invoice->reference) }}" class="text-decoration-none fw-semibold small">
                                                    {{ $invoice->reference->code ?? '#' . $invoice->reference_id }}
                                                </a>
                                            @else
                                                <small class="text-muted">—</small>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('ecommerce.invoices.show', $invoice) }}" class="text-decoration-none fw-semibold">
                                                {{ $invoice->code ?? '—' }}
                                            </a>
                                        </td>
                                        <td class="fw-semibold">${{ number_format($invoice->amount, 2) }}</td>
                                        <td>
                                            <small class="text-muted">{{ $invoice->created_at->format('d/m/Y') }}</small>
                                        </td>
                                        <td>
                                            <span class="badge {{ $badgeClass }}">{{ $statusLabel }}</span>
                                        </td>
                                        <td class="text-center pe-3">
                                            <div class="dropdown">
                                                <button type="button" class="btn btn-sm btn-light" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('ecommerce.invoices.show', $invoice) }}">Ver</a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('ecommerce.invoices.download', $invoice) }}" target="_blank">Descargar PDF</a>
                                                    </li>
                                                    <li>
                                                        <form method="POST" action="{{ route('ecommerce.invoices.destroy', $invoice) }}">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="dropdown-item"
                                                                onclick="return confirm('¿Seguro que deseas eliminar esta factura?')">
                                                                Eliminar
                                                            </button>
                                                        </form>
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
                        <i class="fas fa-file-invoice fa-4x text-muted opacity-50 mb-4 d-block"></i>
                        <h5 class="text-muted mb-1">No hay facturas</h5>
                        <p class="text-muted small mb-0">No se encontraron facturas con los filtros aplicados.</p>
                    </div>
                @endif
            </div>

            @if($invoices->hasPages())
                <div class="card-footer bg-white border-top">
                    {{ $invoices->appends(request()->input())->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
