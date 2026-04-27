@extends('layouts.theme')

@section('title', 'Gestion de precios')

@section('content')
    @include('core::components.card', ['title' => 'Ecommerce - Precios'])

    <div class="widget-content searchable-container list">
        @include('core::components.alerts')

        <div class="card">
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Gestion de precios</h5>
                        <p class="small mb-0 text-muted">Actualiza precios normales y de oferta de forma masiva</p>
                    </div>
                    <div class="d-flex gap-2 align-items-center">
                        <button type="button" class="btn btn-primary" id="saveBulkBtn" hidden>
                            <i class="fas fa-save me-1"></i> Guardar cambios
                        </button>
                        <a href="{{ route('ecommerce.product-prices.export') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-file-export me-1"></i> Exportar
                        </a>
                        <a href="{{ route('ecommerce.product-prices.import') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-file-import me-1"></i> Importar
                        </a>
                    </div>
                </div>
            </div>

            <div class="card-body border-bottom">
                <form method="GET">
                    <div class="row g-2">
                        <div class="col-md-5">
                            <input type="text" name="search" class="form-control" placeholder="Buscar por nombre o SKU..." value="{{ request('search') }}">
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-primary">Filtrar</button>
                            <a href="{{ route('ecommerce.product-prices.index') }}" class="btn btn-outline-secondary">Limpiar</a>
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
                                    <th width="150">Precio normal</th>
                                    <th width="150">Precio oferta</th>
                                    <th class="text-center" width="60">Accion</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($products as $product)
                                    <tr data-product-id="{{ $product->id }}">
                                        <td>
                                            <a href="{{ route('ecommerce.products.edit', $product) }}" class="text-decoration-none fw-semibold">{{ $product->name }}</a>
                                        </td>
                                        <td><small class="text-muted">{{ $product->sku ?? '—' }}</small></td>
                                        <td>
                                            <input type="number" step="0.01" min="0"
                                                class="form-control form-control-sm price-input"
                                                name="price"
                                                value="{{ $product->price }}"
                                                data-original="{{ $product->price }}"
                                                data-product-id="{{ $product->id }}">
                                        </td>
                                        <td>
                                            <input type="number" step="0.01" min="0"
                                                class="form-control form-control-sm price-input"
                                                name="sale_price"
                                                value="{{ $product->sale_price ?? '' }}"
                                                data-original="{{ $product->sale_price ?? '' }}"
                                                data-product-id="{{ $product->id }}"
                                                placeholder="Sin oferta">
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-outline-primary btn-save-price d-none" data-product-id="{{ $product->id }}">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-tag fa-4x text-muted opacity-50 mb-4"></i>
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

    $(document).on('input', '.price-input', function () {
        var $row = $(this).closest('tr');
        var productId = $row.data('product-id');

        var priceChanged = $row.find('[name="price"]').val() !== String($row.find('[name="price"]').data('original'));
        var saleChanged = $row.find('[name="sale_price"]').val() !== String($row.find('[name="sale_price"]').data('original'));

        if (priceChanged || saleChanged) {
            pendingChanges[productId] = {
                id: parseInt(productId),
                price: parseFloat($row.find('[name="price"]').val()) || 0,
                sale_price: $row.find('[name="sale_price"]').val() || null
            };
            $row.find('.btn-save-price').removeClass('d-none');
            $('#saveBulkBtn').removeAttr('hidden');
        } else {
            delete pendingChanges[productId];
            $row.find('.btn-save-price').addClass('d-none');
            if (Object.keys(pendingChanges).length === 0) {
                $('#saveBulkBtn').attr('hidden', true);
            }
        }
    });

    $(document).on('click', '.btn-save-price', function () {
        var $btn = $(this);
        var productId = $btn.data('product-id');
        var $row = $btn.closest('tr');

        $.ajax({
            url: '{{ url('panel/ecommerce/product-prices') }}/' + productId,
            method: 'PATCH',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            data: {
                price: $row.find('[name="price"]').val(),
                sale_price: $row.find('[name="sale_price"]').val() || null
            },
            success: function () {
                $row.find('.price-input').each(function () {
                    $(this).data('original', $(this).val());
                });
                $btn.addClass('d-none');
                delete pendingChanges[productId];
                if (Object.keys(pendingChanges).length === 0) {
                    $('#saveBulkBtn').attr('hidden', true);
                }
                toastr.success('Precio actualizado.');
            },
            error: function () {
                toastr.error('Error al actualizar precio.');
            }
        });
    });

    $('#saveBulkBtn').on('click', function () {
        var items = Object.values(pendingChanges);
        if (!items.length) {
            return;
        }

        $.ajax({
            url: '{{ route('ecommerce.product-prices.bulk') }}',
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            contentType: 'application/json',
            data: JSON.stringify({ items: items }),
            success: function (data) {
                $('.price-input').each(function () {
                    $(this).data('original', $(this).val());
                });
                $('.btn-save-price').addClass('d-none');
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
