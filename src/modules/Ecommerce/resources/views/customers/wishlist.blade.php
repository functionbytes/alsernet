@extends('layouts.theme')

@section('title', 'Lista de deseos - ' . $customer->name)

@section('page_header')
    @include('core::components.card', ['title' => 'Ecommerce - Lista de deseos'])
@endsection

@section('content')
    <div class="widget-content searchable-container list">
        @include('core::components.alerts')

        <div class="card">
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Lista de deseos de {{ $customer->name }}</h5>
                        <p class="small mb-0 text-muted">Productos guardados por el cliente</p>
                    </div>
                    @if(\Route::has('ecommerce.customers.show'))
                        <a href="{{ route('ecommerce.customers.show', $customer) }}" class="btn btn-outline-secondary">
                            Volver
                        </a>
                    @else
                        <a href="{{ route('ecommerce.customers.index') }}" class="btn btn-outline-secondary">
                            Volver
                        </a>
                    @endif
                </div>
            </div>

            <div class="card-body">
                @if($wishlistItems->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Producto</th>
                                    <th>Precio</th>
                                    <th>Fecha agregado</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($wishlistItems as $item)
                                    <tr>
                                        <td>
                                            @if($item->product)
                                                <a href="{{ route('ecommerce.products.edit', $item->product) }}" class="fw-semibold text-decoration-none">
                                                    {{ $item->product->name }}
                                                </a>
                                            @else
                                                <span class="text-muted">Producto eliminado</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($item->product)
                                                ${{ number_format($item->product->price, 2) }}
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td>
                                            <small class="text-muted">{{ $item->created_at->format('d/m/Y H:i') }}</small>
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <button type="button" class="btn btn-sm btn-light" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <form action="{{ route('api.ecommerce.wishlist.destroy', $item) }}" method="POST">
                                                            @csrf @method('DELETE')
                                                            <button type="submit" class="dropdown-item" onclick="return confirm('¿Eliminar este producto de la lista de deseos?')">
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
                        <i class="fas fa-heart fa-4x text-muted opacity-50 mb-4"></i>
                        <h5 class="text-muted mb-0">Este cliente no tiene productos en su lista de deseos</h5>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
