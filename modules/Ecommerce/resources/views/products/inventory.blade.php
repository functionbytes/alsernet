@extends('layouts.theme')

@section('title', 'Inventario de productos')

@section('page_header')
    @include('core::components.card', ['title' => 'Ecommerce - Inventario'])
@endsection

@section('content')
    <div class="widget-content searchable-container list">
        @include('core::components.alerts')

        <div class="card">
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Gestion de inventario</h5>
                        <p class="small mb-0 text-muted">Actualiza el stock de productos de forma individual o masiva</p>
                    </div>
                    <div class="d-flex gap-2 align-items-center">
                        <button type="button" class="btn btn-primary" id="saveBulkBtn" hidden>
                            <i class="fas fa-save me-1"></i> Guardar cambios
                        </button>
                        <a href="{{ route('ecommerce.inventory.export') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-file-export me-1"></i> Exportar
                        </a>
                        <a href="{{ route('ecommerce.inventory.import') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-file-import me-1"></i> Importar
                        </a>
                    </div>
                </div>
            </div>

            <div class="card-body border-bottom">
                <form method="GET">
                    <div class="row g-2">
                        <div class="col-md-4">
                            <input type="text" name="search" class="form-control" placeholder="Buscar por nombre o SKU..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-3">
                            <select name="stock" class="form-select">
                                <option value="">Todos los productos</option>
                                <option value="out" {{ request('stock') === 'out' ? 'selected' : '' }}>Sin stock</option>
                                <option value="low" {{ request('stock') === 'low' ? 'selected' : '' }}>Stock bajo (1-5)</option>
                                <option value="ok" {{ request('stock') === 'ok' ? 'selected' : '' }}>Stock normal (&gt;5)</option>
                            </select>
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-primary">Filtrar</button>
                            <a href="{{ route('ecommerce.inventory.index') }}" class="btn btn-outline-secondary">Limpiar</a>
                        </div>
                    </div>
                </form>
            </div>

            <div class="card-body p-0">
                @if($products->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Producto</th>
                                    <th>SKU</th>
                                    <th>Estado</th>
                                    <th class="text-center" width="160">Stock actual</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($products as $product)
                                    <tr>
                                        <td>
                                            <a href="{{ route('ecommerce.products.edit', $product) }}" class="text-decoration-none fw-semibold">{{ $product->name }}</a>
                                        </td>
                                        <td><small class="text-muted">{{ $product->sku ?? '—' }}</small></td>
                                        <td>
                                            @if(!$product->with_storehouse_management)
                                                <span class="badge bg-secondary">Sin control</span>
                                            @elseif($product->quantity <= 0)
                                                <span class="badge bg-danger">Sin stock</span>
                                            @elseif($product->quantity <= 5)
                                                <span class="badge bg-warning">Stock bajo</span>
                                            @else
                                                <span class="badge bg-success">En stock</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex align-items-center justify-content-center gap-2">
                                                <input type="number"
                                                    class="form-control form-control-sm text-center inventory-input"
                                                    value="{{ $product->quantity ?? 0 }}"
                                                    min="0"
                                                    data-original="{{ $product->quantity ?? 0 }}"
                                                    data-product-id="{{ $product->id }}"
                                                    style="width:80px">
                                                <button type="button"
                                                    class="btn btn-sm btn-outline-primary btn-save-single d-none"
                                                    data-product-id="{{ $product->id }}">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-boxes fa-4x text-muted opacity-50 mb-4"></i>
                        <h5 class="text-muted mb-2">No se encontraron productos</h5>
                    </div>
                @endif
            </div>

            @if($products->hasPages())
                <div class="card-footer">{{ $products->withQueryString()->links() }}</div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
<script>
$(function () {
    var pendingChanges = {};
    var csrfToken = $('meta[name="csrf-token"]').attr('content');

    $(document).on('input', '.inventory-input', function () {
        var $input = $(this);
        var productId = $input.data('product-id');
        var newVal = parseInt($input.val()) || 0;
        var original = parseInt($input.data('original')) || 0;

        if (newVal !== original) {
            pendingChanges[productId] = newVal;
            $input.closest('tr').find('.btn-save-single').removeClass('d-none');
            $('#saveBulkBtn').removeAttr('hidden');
        } else {
            delete pendingChanges[productId];
            $input.closest('tr').find('.btn-save-single').addClass('d-none');
            if (Object.keys(pendingChanges).length === 0) {
                $('#saveBulkBtn').attr('hidden', true);
            }
        }
    });

    $(document).on('click', '.btn-save-single', function () {
        var $btn = $(this);
        var productId = $btn.data('product-id');
        var $input = $('[data-product-id="' + productId + '"].inventory-input');
        var quantity = parseInt($input.val()) || 0;

        $.ajax({
            url: '{{ url('panel/ecommerce/inventory') }}/' + productId,
            method: 'PATCH',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            data: { quantity: quantity },
            success: function () {
                $input.data('original', quantity);
                $btn.addClass('d-none');
                delete pendingChanges[productId];
                if (Object.keys(pendingChanges).length === 0) {
                    $('#saveBulkBtn').attr('hidden', true);
                }
                toastr.success('Stock actualizado.');
            },
            error: function () {
                toastr.error('Error al actualizar stock.');
            }
        });
    });

    $('#saveBulkBtn').on('click', function () {
        if (Object.keys(pendingChanges).length === 0) {
            return;
        }

        var items = Object.entries(pendingChanges).map(function (entry) {
            return { id: parseInt(entry[0]), quantity: entry[1] };
        });

        $.ajax({
            url: '{{ route('ecommerce.inventory.bulk') }}',
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            contentType: 'application/json',
            data: JSON.stringify({ items: items }),
            success: function (data) {
                items.forEach(function (item) {
                    var $input = $('[data-product-id="' + item.id + '"].inventory-input');
                    $input.data('original', item.quantity);
                    $input.closest('tr').find('.btn-save-single').addClass('d-none');
                });
                pendingChanges = {};
                $('#saveBulkBtn').attr('hidden', true);
                toastr.success(data.updated + ' productos actualizados.');
            },
            error: function () {
                toastr.error('Error al guardar cambios.');
            }
        });
    });
});
</script>
@endpush
