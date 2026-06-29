@extends('layouts.theme')

@section('title', 'Catálogo de productos')

@section('page_header')
    @include('core::components.card', ['title' => 'Catálogo de productos'])
@endsection

@section('content')

    @include('core::components.alerts')

    <div class="widget-content searchable-container list">

        <div class="card">

            <div class="card-header p-4 border-bottom">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Catálogo de productos</h5>
                        <p class="small mb-0 text-muted">Productos sincronizados desde tus tiendas conectadas</p>
                    </div>
                </div>
            </div>

            <div class="card-body border-bottom py-3">
                <form method="GET" action="{{ route('remarketing.products.index') }}">
                    <div class="d-flex gap-2">
                        <select name="store_id" class="form-select w-auto">
                            <option value="">Todas las tiendas</option>
                            @foreach($stores ?? [] as $store)
                                <option value="{{ $store->id }}" {{ request('store_id') == $store->id ? 'selected' : '' }}>
                                    {{ $store->name }}
                                </option>
                            @endforeach
                        </select>
                        <select name="status" class="form-select w-auto">
                            <option value="">Todos los estados</option>
                            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Activo</option>
                            <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Borrador</option>
                            <option value="archived" {{ request('status') === 'archived' ? 'selected' : '' }}>Archivado</option>
                        </select>
                        <input type="text" name="search" class="form-control" placeholder="Buscar por título o SKU..."
                               value="{{ request('search') }}">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i>
                        </button>
                        @if(request()->hasAny(['search', 'store_id', 'status']))
                            <a href="{{ route('remarketing.products.index') }}" class="btn btn-light">
                                <i class="fas fa-times"></i>
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:60px">Imagen</th>
                                <th>Título</th>
                                <th>SKU</th>
                                <th>Precio</th>
                                <th>Stock</th>
                                <th>Estado</th>
                                <th>Tienda</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($products as $product)
                                <tr>
                                    <td>
                                        @if($product->image_url)
                                            <img src="{{ $product->image_url }}" alt="{{ $product->title }}"
                                                 width="48" height="48" class="rounded" loading="lazy"
                                                 style="object-fit:cover">
                                        @else
                                            <div class="rounded bg-light d-flex align-items-center justify-content-center" style="width:48px;height:48px">
                                                <i class="fas fa-image text-muted"></i>
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="fw-semibold small">{{ Str::limit($product->title, 60) }}</div>
                                        @if($product->vendor)
                                            <small class="text-muted">{{ $product->vendor }}</small>
                                        @endif
                                    </td>
                                    <td class="small text-muted">{{ $product->sku ?: '—' }}</td>
                                    <td class="small fw-semibold">{{ number_format($product->price, 2) }} {{ $product->currency }}</td>
                                    <td class="small">
                                        @if($product->inventory <= 0)
                                            <span class="text-danger">Sin stock</span>
                                        @elseif($product->inventory < 5)
                                            <span class="text-warning">{{ $product->inventory }}</span>
                                        @else
                                            {{ $product->inventory }}
                                        @endif
                                    </td>
                                    <td>
                                        @if($product->status === 'active')
                                            <span class="badge bg-success-subtle text-success">Activo</span>
                                        @elseif($product->status === 'draft')
                                            <span class="badge bg-secondary-subtle text-secondary">Borrador</span>
                                        @else
                                            <span class="badge bg-light text-muted">{{ $product->status }}</span>
                                        @endif
                                    </td>
                                    <td class="small text-muted">{{ $product->store?->name ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">No se encontraron productos</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if($products->hasPages())
                <div class="card-footer bg-white border-top">
                    {{ $products->appends(request()->input())->links() }}
                </div>
            @endif

        </div>

    </div>

@endsection
