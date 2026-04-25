@extends('layouts.theme')

@section('title', 'Marcas')

@section('content')
    @include('core::components.card', ['title' => 'Ecommerce - Marcas'])

    <div class="widget-content searchable-container list">
        @include('core::components.alerts')

        <div class="card">
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Marcas</h5>
                        <p class="small mb-0 text-muted">Administra las marcas de productos</p>
                    </div>
                    <a href="{{ route('ecommerce.brands.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> Nueva marca
                    </a>
                </div>
            </div>

            <div class="card-body">
                @if($brands->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Marca</th>
                                    <th>Sitio web</th>
                                    <th>Estado</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($brands as $brand)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                @if($brand->logo)
                                                    <img src="{{ $brand->logo }}" alt="" class="rounded" width="40" height="40" style="object-fit:cover;">
                                                @else
                                                    <div class="bg-light rounded d-flex align-items-center justify-content-center" style="width:40px;height:40px;"><i class="fas fa-copyright text-muted"></i></div>
                                                @endif
                                                <a href="{{ route('ecommerce.brands.edit', $brand) }}" class="text-decoration-none fw-semibold">{{ $brand->name }}</a>
                                            </div>
                                        </td>
                                        <td><small class="text-muted">{{ $brand->website ?? '—' }}</small></td>
                                        <td><span class="badge bg-{{ $brand->status === 'published' ? 'success' : 'secondary' }}">{{ $brand->status }}</span></td>
                                        <td class="text-center">
                                            <a href="{{ route('ecommerce.brands.edit', $brand) }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
                                            <form action="{{ route('ecommerce.brands.destroy', $brand) }}" method="POST" class="d-inline" onsubmit="return confirm('Eliminar esta marca?')">
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
                        <i class="fas fa-copyright fa-4x text-muted opacity-50 mb-4"></i>
                        <h5 class="text-muted mb-2">No hay marcas</h5>
                        <a href="{{ route('ecommerce.brands.create') }}" class="btn btn-primary">Crear primera marca</a>
                    </div>
                @endif
            </div>

            @if($brands->hasPages())
                <div class="card-footer">{{ $brands->withQueryString()->links() }}</div>
            @endif
        </div>
    </div>
@endsection
