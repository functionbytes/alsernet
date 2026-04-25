@extends('layouts.theme')

@section('title', 'Ventas flash')

@section('content')
    @include('core::components.card', ['title' => 'Ecommerce - Ventas flash'])

    <div class="widget-content searchable-container list">
        @include('core::components.alerts')

        <div class="card">
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Ventas flash</h5>
                        <p class="small mb-0 text-muted">Administra las ventas flash</p>
                    </div>
                    <a href="{{ route('ecommerce.flash-sales.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> Nueva venta flash
                    </a>
                </div>
            </div>

            <div class="card-body">
                @if($flashSales->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Nombre</th>
                                    <th>Inicio</th>
                                    <th>Fin</th>
                                    <th>Estado</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($flashSales as $flashSale)
                                    <tr>
                                        <td>
                                            <a href="{{ route('ecommerce.flash-sales.edit', $flashSale) }}" class="text-decoration-none fw-semibold">{{ $flashSale->name }}</a>
                                        </td>
                                        <td><small class="text-muted">{{ $flashSale->start_date?->format('d/m/Y H:i') ?? '—' }}</small></td>
                                        <td><small class="text-muted">{{ $flashSale->end_date?->format('d/m/Y H:i') ?? '—' }}</small></td>
                                        <td><span class="badge bg-{{ $flashSale->status === 'published' ? 'success' : 'secondary' }}">{{ $flashSale->status }}</span></td>
                                        <td class="text-center">
                                            <a href="{{ route('ecommerce.flash-sales.edit', $flashSale) }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
                                            <form action="{{ route('ecommerce.flash-sales.destroy', $flashSale) }}" method="POST" class="d-inline" onsubmit="return confirm('Eliminar esta venta flash?')">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-bolt fa-4x text-muted opacity-50 mb-4"></i>
                        <h5 class="text-muted mb-2">No hay ventas flash</h5>
                        <a href="{{ route('ecommerce.flash-sales.create') }}" class="btn btn-primary">Crear primera venta flash</a>
                    </div>
                @endif
            </div>

            @if($flashSales->hasPages())
                <div class="card-footer">{{ $flashSales->withQueryString()->links() }}</div>
            @endif
        </div>
    </div>
@endsection
