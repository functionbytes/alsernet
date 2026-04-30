@extends('layouts.theme')

@section('title', 'Descuentos')

@section('page_header')
    @include('core::components.card', ['title' => 'Ecommerce - Descuentos'])
@endsection

@section('content')
    <div class="widget-content searchable-container list">
        @include('core::components.alerts')

        <div class="card">
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Descuentos</h5>
                        <p class="small mb-0 text-muted">Administra cupones y promociones</p>
                    </div>
                    <a href="{{ route('ecommerce.discounts.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> Nuevo descuento
                    </a>
                </div>
            </div>

            <div class="card-body">
                @if($discounts->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Titulo</th>
                                    <th>Codigo</th>
                                    <th>Tipo</th>
                                    <th>Valor</th>
                                    <th>Usos</th>
                                    <th>Estado</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($discounts as $discount)
                                    <tr>
                                        <td><a href="{{ route('ecommerce.discounts.edit', $discount) }}" class="fw-semibold text-decoration-none">{{ $discount->title }}</a></td>
                                        <td><code>{{ $discount->code ?? '—' }}</code></td>
                                        <td><span class="badge bg-info">{{ $discount->type->value }}</span></td>
                                        <td>{{ $discount->type->value === 'percentage' ? $discount->value . '%' : '$' . number_format($discount->value, 2) }}</td>
                                        <td><small class="text-muted">{{ $discount->total_used }}{{ $discount->quantity ? '/' . $discount->quantity : '' }}</small></td>
                                        <td><span class="badge bg-{{ $discount->is_active && ! $discount->isExpired() ? 'success' : 'secondary' }}">{{ $discount->is_active && ! $discount->isExpired() ? 'Activo' : 'Inactivo' }}</span></td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <button type="button" class="btn btn-sm btn-light" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li><a class="dropdown-item" href="{{ route('ecommerce.discounts.edit', $discount) }}">Editar</a></li>
                                                    <li>
                                                        <form action="{{ route('ecommerce.discounts.destroy', $discount) }}" method="POST">
                                                            @csrf @method('DELETE')
                                                            <button type="submit" class="dropdown-item" onclick="return confirm('¿Eliminar este descuento?')">Eliminar</button>
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
                        <i class="fas fa-tags fa-4x text-muted opacity-50 mb-4"></i>
                        <h5 class="text-muted mb-2">No hay descuentos</h5>
                        <a href="{{ route('ecommerce.discounts.create') }}" class="btn btn-primary">Crear primer descuento</a>
                    </div>
                @endif
            </div>

            @if($discounts->hasPages())
                <div class="card-footer">{{ $discounts->withQueryString()->links() }}</div>
            @endif
        </div>
    </div>
@endsection
