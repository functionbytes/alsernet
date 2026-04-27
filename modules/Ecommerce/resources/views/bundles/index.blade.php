@extends('layouts.theme')

@section('title', 'Bundles de productos')

@section('content')
    @include('core::components.card', ['title' => 'Ecommerce - Bundles'])

    <div class="widget-content searchable-container list">
        @include('core::components.alerts')

        <div class="card">
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Bundles de productos</h5>
                        <p class="small mb-0 text-muted">Combina productos con descuento especial</p>
                    </div>
                    <a href="{{ route('ecommerce.bundles.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> Nuevo bundle
                    </a>
                </div>
            </div>

            <div class="card-body border-bottom py-3">
                <form method="GET" class="d-flex gap-2">
                    <input type="text" name="search" value="{{ request('search') }}"
                        class="form-control" placeholder="Buscar bundle..." style="max-width: 300px;">
                    <button type="submit" class="btn btn-outline-secondary">Buscar</button>
                    @if(request('search'))
                        <a href="{{ route('ecommerce.bundles.index') }}" class="btn btn-light">Limpiar</a>
                    @endif
                </form>
            </div>

            <div class="card-body p-0">
                @if($bundles->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Nombre</th>
                                    <th>Productos</th>
                                    <th>Descuento</th>
                                    <th>Estado</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($bundles as $bundle)
                                    <tr>
                                        <td class="ps-4">
                                            <a href="{{ route('ecommerce.bundles.edit', $bundle) }}"
                                                class="text-decoration-none fw-semibold">{{ $bundle->name }}</a>
                                            <div><small class="text-muted">{{ $bundle->slug }}</small></div>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border">{{ $bundle->products_count }} productos</span>
                                        </td>
                                        <td>
                                            @if($bundle->discount_type === 'percentage')
                                                <span class="text-success fw-semibold">{{ $bundle->discount_value }}%</span>
                                            @else
                                                <span class="text-success fw-semibold">${{ number_format($bundle->discount_value, 2) }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $bundle->status === 'published' ? 'success' : 'secondary' }}">
                                                {{ $bundle->status === 'published' ? 'Publicado' : 'Borrador' }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <button type="button" class="btn btn-sm btn-light"
                                                    data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item"
                                                            href="{{ route('ecommerce.bundles.edit', $bundle) }}">Editar</a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item"
                                                            href="{{ route('shop.bundles.show', $bundle->slug) }}"
                                                            target="_blank">Ver en tienda</a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <form action="{{ route('ecommerce.bundles.destroy', $bundle) }}"
                                                            method="POST">
                                                            @csrf @method('DELETE')
                                                            <button type="submit" class="dropdown-item"
                                                                onclick="return confirm('¿Eliminar este bundle?')">
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
                        <i class="fas fa-box-open fa-4x text-muted opacity-50 mb-4"></i>
                        <h5 class="text-muted mb-2">No hay bundles creados</h5>
                        <a href="{{ route('ecommerce.bundles.create') }}" class="btn btn-primary">
                            Crear primer bundle
                        </a>
                    </div>
                @endif
            </div>

            @if($bundles->hasPages())
                <div class="card-footer">{{ $bundles->withQueryString()->links() }}</div>
            @endif
        </div>
    </div>
@endsection
