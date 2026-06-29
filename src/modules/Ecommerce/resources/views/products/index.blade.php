@extends('layouts.theme')

@section('title', 'Productos')

@push('css')
<style>
.bulk-actions-bar {
    position: fixed;
    bottom: 20px;
    left: 50%;
    transform: translateX(-50%);
    background: #1a2030;
    color: #fff;
    padding: 12px 20px;
    border-radius: 50px;
    box-shadow: 0 8px 24px rgba(0,0,0,.2);
    display: flex;
    align-items: center;
    gap: 12px;
    z-index: 1040;
    white-space: nowrap;
}

.js-inline-edit {
    cursor: pointer;
    padding: 2px 6px;
    border-radius: 3px;
    display: inline-block;
}

.js-inline-edit:hover {
    background: #fffacd;
    outline: 1px dashed #999;
}

.inline-edit-input {
    width: 100px;
    padding: 2px 6px;
    border: 2px solid #90bb13;
    border-radius: 3px;
    font-size: inherit;
}
</style>
@endpush

@section('page_header')
    @include('core::components.card', ['title' => 'Ecommerce - Productos'])
@endsection

@section('content')
    <div class="widget-content searchable-container list">
        @include('core::components.alerts')

        <div class="card">
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Productos</h5>
                        <p class="small mb-0 text-muted">Administra los productos de la tienda</p>
                    </div>
                    <div class="d-flex gap-2 align-items-center">
                        <a href="{{ route('ecommerce.products.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> Nuevo producto
                        </a>
                        <div class="btn-group">
                            <a href="{{ route('ecommerce.products.export') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-file-export me-1"></i> Exportar
                            </a>
                            <a href="{{ route('ecommerce.products.import') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-file-import me-1"></i> Importar
                            </a>
                        </div>
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
                            <select name="status" class="form-select">
                                <option value="">Todos los estados</option>
                                <option value="published" {{ request('status') === 'published' ? 'selected' : '' }}>Publicado</option>
                                <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Borrador</option>
                                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pendiente</option>
                            </select>
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-primary">Filtrar</button>
                            <a href="{{ route('ecommerce.products.index') }}" class="btn btn-outline-secondary">Limpiar</a>
                        </div>
                    </div>
                </form>
            </div>

            <div class="card-body">
                @if($products->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:40px">
                                        <input type="checkbox" id="select-all-products" class="form-check-input" title="Seleccionar todos">
                                    </th>
                                    <th>Producto</th>
                                    <th>SKU</th>
                                    <th>Precio</th>
                                    <th>Stock</th>
                                    <th>Estado</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($products as $product)
                                    <tr>
                                        <td>
                                            <input type="checkbox" class="form-check-input product-checkbox" value="{{ $product->id }}">
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                @if($product->featured_image)
                                                    <img src="{{ $product->featured_image }}" alt="" class="rounded object-fit-cover flex-shrink-0" width="40" height="40">
                                                @else
                                                    <div class="bg-light rounded d-flex align-items-center justify-content-center flex-shrink-0" style="width:40px;height:40px">
                                                        <i class="fas fa-image text-muted"></i>
                                                    </div>
                                                @endif
                                                <div>
                                                    <a href="{{ route('ecommerce.products.edit', $product) }}" class="text-decoration-none fw-semibold">{{ $product->name }}</a>
                                                    <div><small class="text-muted">{{ $product->brand->name ?? 'Sin marca' }}</small></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><small class="text-muted">{{ $product->sku ?? '—' }}</small></td>
                                        <td>
                                            @if($product->is_on_sale)
                                                <span class="text-decoration-line-through text-muted small">${{ number_format($product->price, 2) }}</span>
                                                <span class="fw-bold text-danger">${{ number_format($product->final_price, 2) }}</span>
                                            @else
                                                <span class="js-inline-edit fw-semibold"
                                                      data-field="price"
                                                      data-id="{{ $product->id }}"
                                                      data-value="{{ $product->price }}"
                                                      title="Clic para editar">${{ number_format($product->price, 2) }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($product->with_storehouse_management)
                                                <span class="js-inline-edit badge bg-{{ $product->quantity > 5 ? 'success' : ($product->quantity > 0 ? 'warning' : 'danger') }}"
                                                      data-field="quantity"
                                                      data-id="{{ $product->id }}"
                                                      data-value="{{ $product->quantity }}"
                                                      title="Clic para editar">{{ $product->quantity }}</span>
                                            @else
                                                <span class="badge bg-secondary">N/A</span>
                                            @endif
                                        </td>
                                        <td>
                                            @php
                                                $statusMap = [
                                                    'published' => ['bg' => 'success', 'label' => 'Publicado'],
                                                    'draft'     => ['bg' => 'secondary', 'label' => 'Borrador'],
                                                    'pending'   => ['bg' => 'warning', 'label' => 'Pendiente'],
                                                ];
                                                $statusVal  = $product->status->value;
                                                $statusInfo = $statusMap[$statusVal] ?? ['bg' => 'secondary', 'label' => $statusVal];
                                            @endphp
                                            <span class="badge bg-{{ $statusInfo['bg'] }}">{{ $statusInfo['label'] }}</span>
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <button type="button" class="btn btn-sm btn-light" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li><a class="dropdown-item" href="{{ route('ecommerce.products.edit', $product) }}">Editar</a></li>
                                                    <li>
                                                        <form action="{{ route('ecommerce.products.duplicate', $product) }}" method="POST">
                                                            @csrf
                                                            <button type="submit" class="dropdown-item">Duplicar</button>
                                                        </form>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <form action="{{ route('ecommerce.products.destroy', $product) }}" method="POST">
                                                            @csrf @method('DELETE')
                                                            <button type="submit" class="dropdown-item" onclick="return confirm('¿Eliminar este producto?')">Eliminar</button>
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
                        <i class="fas fa-box fa-4x text-muted opacity-50 mb-4"></i>
                        <h5 class="text-muted mb-2">No hay productos</h5>
                        <a href="{{ route('ecommerce.products.create') }}" class="btn btn-primary">Crear primer producto</a>
                    </div>
                @endif
            </div>

            @if($products->hasPages())
                <div class="card-footer">{{ $products->withQueryString()->links() }}</div>
            @endif
        </div>
    </div>

    {{-- Barra flotante de acciones masivas --}}
    <div id="bulk-actions-bar" class="bulk-actions-bar" style="display:none">
        <span><strong id="bulk-count">0</strong> seleccionados</span>
        <button class="btn btn-sm btn-outline-light js-bulk-action" data-action="publish">Publicar</button>
        <button class="btn btn-sm btn-outline-light js-bulk-action" data-action="unpublish">Despublicar</button>
        <button class="btn btn-sm btn-outline-warning js-bulk-action" data-action="delete">Eliminar</button>
        <button class="btn btn-sm btn-outline-secondary" id="clear-selection">Cancelar</button>
    </div>

    @include('ecommerce::partials._admin-quick-search')
    @include('ecommerce::partials._admin-realtime-notifications')
@endsection

@push('scripts')
<script>
$(function () {
    var bulkUrl = '{{ route('ecommerce.products.bulk-action') }}';
    var inlineUrl = '{{ route('ecommerce.products.inline-update') }}';
    var csrfToken = $('meta[name="csrf-token"]').attr('content');

    // ---- Bulk selection ----
    function getSelectedIds() {
        return $('.product-checkbox:checked').map(function () { return this.value; }).get();
    }

    function toggleBulkBar() {
        var ids = getSelectedIds();
        $('#bulk-count').text(ids.length);
        $('#bulk-actions-bar').toggle(ids.length > 0);
    }

    $('#select-all-products').on('change', function () {
        $('.product-checkbox').prop('checked', this.checked);
        toggleBulkBar();
    });

    $(document).on('change', '.product-checkbox', function () {
        var total = $('.product-checkbox').length;
        var checked = $('.product-checkbox:checked').length;
        $('#select-all-products').prop('indeterminate', checked > 0 && checked < total);
        $('#select-all-products').prop('checked', checked === total);
        toggleBulkBar();
    });

    $('#clear-selection').on('click', function () {
        $('.product-checkbox, #select-all-products').prop('checked', false).prop('indeterminate', false);
        toggleBulkBar();
    });

    $('.js-bulk-action').on('click', function () {
        var action = $(this).data('action');
        var ids = getSelectedIds();

        if (!ids.length) { return; }
        if (action === 'delete' && !confirm('¿Eliminar ' + ids.length + ' productos? Esta accion no se puede deshacer.')) { return; }

        $.ajax({
            url: bulkUrl,
            method: 'POST',
            data: { action: action, ids: ids },
            headers: { 'X-CSRF-TOKEN': csrfToken },
            success: function (r) {
                toastr.success(r.message || 'Accion aplicada');
                setTimeout(function () { location.reload(); }, 800);
            },
            error: function () {
                toastr.error('Error al ejecutar la accion masiva');
            },
        });
    });

    // ---- Inline edit (price / quantity) ----
    $(document).on('click', '.js-inline-edit', function () {
        var $span = $(this);
        var originalValue = $span.data('value');
        var $input = $('<input type="text" class="inline-edit-input">').val(originalValue);

        $span.replaceWith($input);
        $input.focus().select();

        function restore() {
            $input.replaceWith($span);
        }

        $input.on('blur keydown', function (e) {
            if (e.type === 'keydown') {
                if (e.key === 'Escape') { restore(); return; }
                if (e.key !== 'Enter') { return; }
            }

            var newValue = $input.val();

            if (newValue == originalValue) { restore(); return; }

            $.ajax({
                url: inlineUrl,
                method: 'POST',
                data: { id: $span.data('id'), field: $span.data('field'), value: newValue },
                headers: { 'X-CSRF-TOKEN': csrfToken },
                success: function () {
                    var display = $span.data('field') === 'price'
                        ? '$' + parseFloat(newValue).toFixed(2)
                        : newValue;
                    $span.data('value', newValue).text(display);
                    $input.replaceWith($span);
                    toastr.success('Actualizado');
                },
                error: function () {
                    toastr.error('Error al actualizar');
                    restore();
                },
            });
        });
    });
});
</script>
@endpush
