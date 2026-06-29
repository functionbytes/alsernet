@extends('layouts.theme')

@section('title', 'Devoluciones')

@section('page_header')
    @include('core::components.card', ['title' => 'Ecommerce - Devoluciones'])
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
                        <div class="fs-4 fw-bold text-success">{{ $counts['approved'] ?? 0 }}</div>
                        <div class="small text-muted">Aprobadas</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card text-center h-100">
                    <div class="card-body py-3">
                        <div class="fs-4 fw-bold text-primary">{{ $counts['completed'] ?? 0 }}</div>
                        <div class="small text-muted">Completadas</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                    <div>
                        <h5 class="mb-1 fw-bold">Devoluciones</h5>
                        <p class="small mb-0 text-muted">Administra las devoluciones de ordenes</p>
                    </div>
                    <form method="GET" action="{{ route('ecommerce.returns.index') }}" class="d-flex gap-2 flex-wrap align-items-center">
                        <input type="text" name="search" class="form-control form-control-sm"
                            placeholder="Buscar por orden o motivo..."
                            value="{{ request('search') }}" style="min-width:200px">
                        <select name="status" class="form-select form-select-sm" style="min-width:140px">
                            <option value="">Todos</option>
                            <option value="pending"   {{ request('status') === 'pending'   ? 'selected' : '' }}>Pendiente</option>
                            <option value="approved"  {{ request('status') === 'approved'  ? 'selected' : '' }}>Aprobada</option>
                            <option value="rejected"  {{ request('status') === 'rejected'  ? 'selected' : '' }}>Rechazada</option>
                            <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completada</option>
                        </select>
                        <button type="submit" class="btn btn-sm btn-primary">Buscar</button>
                        @if(request('search') || request('status'))
                            <a href="{{ route('ecommerce.returns.index') }}" class="btn btn-sm btn-light">Limpiar</a>
                        @endif
                    </form>
                </div>
            </div>

            <div class="card-body p-0">
                @if($returns->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">ID devolucion</th>
                                    <th>Orden</th>
                                    <th>Cliente</th>
                                    <th>Motivo</th>
                                    <th>Estado</th>
                                    <th>Fecha</th>
                                    <th class="text-center pe-3">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($returns as $return)
                                    @php
                                        $badgeClass = match($return->return_status) {
                                            'pending'   => 'bg-warning text-dark',
                                            'approved'  => 'bg-success',
                                            'rejected'  => 'bg-danger',
                                            'completed' => 'bg-primary',
                                            default     => 'bg-secondary',
                                        };
                                        $statusLabel = match($return->return_status) {
                                            'pending'   => 'Pendiente',
                                            'approved'  => 'Aprobada',
                                            'rejected'  => 'Rechazada',
                                            'completed' => 'Completada',
                                            default     => ucfirst($return->return_status),
                                        };
                                    @endphp
                                    <tr>
                                        <td class="ps-3">
                                            <small class="text-muted">{{ $return->return_code ?? '#' . $return->id }}</small>
                                        </td>
                                        <td>
                                            <a href="{{ route('ecommerce.orders.show', $return->order) }}" class="text-decoration-none fw-semibold">
                                                {{ $return->order?->code ?? '—' }}
                                            </a>
                                        </td>
                                        <td>{{ $return->customer?->name ?? '—' }}</td>
                                        <td>
                                            <small class="text-muted">{{ Str::limit($return->reason ?? '—', 50) }}</small>
                                        </td>
                                        <td>
                                            <span class="badge {{ $badgeClass }}">{{ $statusLabel }}</span>
                                        </td>
                                        <td>
                                            <small class="text-muted">{{ $return->created_at->format('d/m/Y H:i') }}</small>
                                        </td>
                                        <td class="text-center pe-3">
                                            <div class="dropdown">
                                                <button type="button" class="btn btn-sm btn-light" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('ecommerce.returns.show', $return) }}">Ver</a>
                                                    </li>
                                                    <li>
                                                        <form method="POST" action="{{ route('ecommerce.returns.destroy', $return) }}">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="dropdown-item"
                                                                onclick="return confirm('¿Seguro que deseas eliminar esta devolucion?')">
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
                        <i class="fas fa-rotate-left fa-4x text-muted opacity-50 mb-4 d-block"></i>
                        <h5 class="text-muted mb-1">No hay devoluciones</h5>
                        <p class="text-muted small mb-0">No se encontraron devoluciones con los filtros aplicados.</p>
                    </div>
                @endif
            </div>

            @if($returns->hasPages())
                <div class="card-footer bg-white border-top">
                    {{ $returns->appends(request()->input())->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
