@extends('layouts.theme')

@section('title', 'Nuevo producto')

@section('page_header')
    @include('core::components.card', ['title' => 'Nuevo producto'])
@endsection

@section('content')
    <div class="widget-content searchable-container list">
        @include('core::components.alerts')

        <form action="{{ route('ecommerce.products.store') }}" method="POST">
            @csrf
            <div class="card">
                <div class="card-body">
                    <ul class="nav nav-tabs mb-4" role="tablist">
                        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-basic" type="button">Basico</button></li>
                        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-pricing" type="button">Precios</button></li>
                        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-inventory" type="button">Inventario</button></li>
                        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-shipping" type="button">Envio</button></li>
                        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-images" type="button">Imagenes</button></li>
                        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-labels" type="button">Etiqueta</button></li>
                        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-attributes" type="button">Atributos</button></li>
                        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-specs" type="button">Especificaciones</button></li>
                        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-options" type="button">Opciones</button></li>
                    </ul>

                    <div class="tab-content">
                        {{-- Tab Basico --}}
                        <div class="tab-pane fade show active" id="tab-basic">
                            <div class="row g-3">
                                <div class="col-md-8">
                                    <div class="mb-3">
                                        <label class="form-label">Nombre <span class="text-danger">*</span></label>
                                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Slug <span class="text-danger">*</span></label>
                                        <input type="text" name="slug" class="form-control @error('slug') is-invalid @enderror" value="{{ old('slug') }}" required>
                                        @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Descripcion corta</label>
                                        <textarea name="description" class="form-control" rows="3">{{ old('description') }}</textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Contenido</label>
                                        <textarea name="content" class="form-control wysiwyg-editor" rows="5">{{ old('content') }}</textarea>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Estado</label>
                                        <select name="status" class="form-select">
                                            <option value="published" {{ old('status') === 'published' ? 'selected' : '' }}>Publicado</option>
                                            <option value="draft" {{ old('status') === 'draft' ? 'selected' : '' }}>Borrador</option>
                                            <option value="pending" {{ old('status') === 'pending' ? 'selected' : '' }}>Pendiente</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Marca</label>
                                        <select name="brand_id" class="form-select">
                                            <option value="">Sin marca</option>
                                            @foreach($brands as $id => $name)
                                                <option value="{{ $id }}" {{ old('brand_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Categorias</label>
                                        <select name="categories[]" class="form-select" multiple size="5">
                                            @foreach($categories as $category)
                                                <option value="{{ $category->id }}" {{ in_array($category->id, old('categories', [])) ? 'selected' : '' }}>{{ $category->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Etiquetas</label>
                                        <select name="tags[]" class="form-select" multiple size="5">
                                            @foreach($tags as $id => $name)
                                                <option value="{{ $id }}" {{ in_array($id, old('tags', [])) ? 'selected' : '' }}>{{ $name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Colecciones</label>
                                        <select name="collections[]" class="form-select" multiple size="5">
                                            @foreach($collections as $id => $name)
                                                <option value="{{ $id }}" {{ in_array($id, old('collections', [])) ? 'selected' : '' }}>{{ $name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Orden</label>
                                        <input type="number" name="order" class="form-control" value="{{ old('order', 0) }}">
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="is_featured" value="1" id="is_featured" {{ old('is_featured') ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_featured">Producto destacado</label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="is_variation" value="1" id="is_variation" {{ old('is_variation') ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_variation">Es variacion</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Tab Precios --}}
                        <div class="tab-pane fade" id="tab-pricing">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Precio <span class="text-danger">*</span></label>
                                        <input type="number" step="0.01" name="price" class="form-control @error('price') is-invalid @enderror" value="{{ old('price') }}" required>
                                        @error('price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Precio de oferta</label>
                                        <input type="number" step="0.01" name="sale_price" class="form-control" value="{{ old('sale_price') }}">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Tipo de oferta</label>
                                        <select name="sale_type" class="form-select">
                                            <option value="default" {{ old('sale_type') === 'default' ? 'selected' : '' }}>Por defecto</option>
                                            <option value="percentage" {{ old('sale_type') === 'percentage' ? 'selected' : '' }}>Porcentaje</option>
                                            <option value="fixed" {{ old('sale_type') === 'fixed' ? 'selected' : '' }}>Fijo</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Fecha inicio oferta</label>
                                        <input type="datetime-local" name="start_date" class="form-control" value="{{ old('start_date') }}">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Fecha fin oferta</label>
                                        <input type="datetime-local" name="end_date" class="form-control" value="{{ old('end_date') }}">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Costo por item</label>
                                        <input type="number" step="0.01" name="cost_per_item" class="form-control" value="{{ old('cost_per_item') }}">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Codigo de barras</label>
                                        <input type="text" name="barcode" class="form-control" value="{{ old('barcode') }}">
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Tab Inventario --}}
                        <div class="tab-pane fade" id="tab-inventory">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">SKU</label>
                                        <input type="text" name="sku" class="form-control @error('sku') is-invalid @enderror" value="{{ old('sku') }}">
                                        @error('sku')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Cantidad</label>
                                        <input type="number" name="quantity" class="form-control" value="{{ old('quantity') }}">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Estado de stock</label>
                                        <select name="stock_status" class="form-select">
                                            <option value="in_stock" {{ old('stock_status') === 'in_stock' ? 'selected' : '' }}>En stock</option>
                                            <option value="out_of_stock" {{ old('stock_status') === 'out_of_stock' ? 'selected' : '' }}>Sin stock</option>
                                            <option value="backorder" {{ old('stock_status') === 'backorder' ? 'selected' : '' }}>Pedido pendiente</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Cantidad minima por orden</label>
                                        <input type="number" name="minimum_order_quantity" class="form-control" value="{{ old('minimum_order_quantity', 1) }}">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Cantidad maxima por orden</label>
                                        <input type="number" name="maximum_order_quantity" class="form-control" value="{{ old('maximum_order_quantity') }}">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="with_storehouse_management" value="1" id="with_storehouse_management" {{ old('with_storehouse_management') ? 'checked' : '' }}>
                                        <label class="form-check-label" for="with_storehouse_management">Gestionar almacen</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="allow_checkout_when_out_of_stock" value="1" id="allow_checkout_when_out_of_stock" {{ old('allow_checkout_when_out_of_stock') ? 'checked' : '' }}>
                                        <label class="form-check-label" for="allow_checkout_when_out_of_stock">Permitir checkout sin stock</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Tab Envio --}}
                        <div class="tab-pane fade" id="tab-shipping">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label">Peso (kg)</label>
                                        <input type="number" step="0.01" name="weight" class="form-control" value="{{ old('weight') }}">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label">Largo (cm)</label>
                                        <input type="number" step="0.01" name="length" class="form-control" value="{{ old('length') }}">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label">Ancho (cm)</label>
                                        <input type="number" step="0.01" name="wide" class="form-control" value="{{ old('wide') }}">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label">Alto (cm)</label>
                                        <input type="number" step="0.01" name="height" class="form-control" value="{{ old('height') }}">
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Tab Imagenes --}}
                        <div class="tab-pane fade" id="tab-images">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Imagen destacada (URL)</label>
                                        <input type="text" name="featured_image" class="form-control" value="{{ old('featured_image') }}">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Galeria de imagenes (URLs separadas por coma)</label>
                                        <input type="text" name="images" class="form-control" value="{{ old('images') }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                        {{-- Tab Etiqueta --}}
                        <div class="tab-pane fade" id="tab-labels">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Etiqueta de producto</label>
                                    <select name="label_id" class="form-select">
                                        <option value="">Sin etiqueta</option>
                                        @foreach($labels as $label)
                                            <option value="{{ $label->id }}"
                                                {{ old('label_id') == $label->id ? 'selected' : '' }}
                                                style="background-color: {{ $label->color }}; color: {{ $label->text_color }}">
                                                {{ $label->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="mt-2" id="label-preview"></div>
                                </div>
                            </div>
                        </div>

                        {{-- Tab Atributos --}}
                        <div class="tab-pane fade" id="tab-attributes">
                            <p class="text-muted small mb-3">Define los conjuntos de atributos para las variantes de este producto (talla, color, material, etc.)</p>
                            @if($attributeSets->count() > 0)
                                <div class="row g-2">
                                    @foreach($attributeSets as $set)
                                        <div class="col-md-4">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox"
                                                    name="attribute_sets[]" value="{{ $set->id }}"
                                                    id="attr_set_{{ $set->id }}"
                                                    {{ in_array($set->id, old('attribute_sets', [])) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="attr_set_{{ $set->id }}">{{ $set->title }}</label>
                                                <small class="d-block text-muted">{{ $set->slug }}</small>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-muted">No hay conjuntos de atributos. <a href="{{ route('ecommerce.product-attribute-sets.create') }}">Crear uno</a></p>
                            @endif
                        </div>

                        {{-- Tab Especificaciones --}}
                        <div class="tab-pane fade" id="tab-specs">
                            <p class="text-muted small mb-3">Selecciona las tablas de especificaciones que aplican a este producto.</p>
                            @if($specificationTables->count() > 0)
                                <div class="row g-2">
                                    @foreach($specificationTables as $table)
                                        <div class="col-md-4">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox"
                                                    name="specification_tables[]" value="{{ $table->id }}"
                                                    id="spec_table_{{ $table->id }}"
                                                    {{ in_array($table->id, old('specification_tables', [])) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="spec_table_{{ $table->id }}">{{ $table->name }}</label>
                                                @if($table->description)
                                                    <small class="d-block text-muted">{{ $table->description }}</small>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-muted">No hay tablas de especificaciones. <a href="{{ route('ecommerce.specification-tables.create') }}">Crear una</a></p>
                            @endif
                        </div>

                        {{-- Tab Opciones --}}
                        <div class="tab-pane fade" id="tab-options">
                            <p class="text-muted small mb-3">Define opciones personalizables para este producto (texto personalizado, grabado, color especial, etc.)</p>
                            <div id="options-container"></div>
                            <button type="button" class="btn btn-outline-primary btn-sm" id="btn-add-option">
                                <i class="fas fa-plus me-1"></i> Agregar opcion
                            </button>

                            <template id="option-template">
                                <div class="card mb-3 option-block" data-index="__IDX__">
                                    <div class="card-header d-flex justify-content-between align-items-center py-2">
                                        <span class="fw-semibold small">Opcion #<span class="option-num">1</span></span>
                                        <button type="button" class="btn btn-sm btn-outline-danger btn-remove-option">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                    <div class="card-body">
                                        <div class="row g-3">
                                            <div class="col-md-5">
                                                <label class="form-label">Nombre <span class="text-danger">*</span></label>
                                                <input type="text" name="options[__IDX__][name]" class="form-control" placeholder="Ej: ¿Agregar grabado?">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Tipo</label>
                                                <select name="options[__IDX__][option_type]" class="form-select option-type-select">
                                                    <option value="text">Texto</option>
                                                    <option value="textarea">Texto largo</option>
                                                    <option value="select">Desplegable</option>
                                                    <option value="checkbox">Casillas</option>
                                                    <option value="radio">Radio</option>
                                                    <option value="file">Archivo</option>
                                                </select>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label">Orden</label>
                                                <input type="number" name="options[__IDX__][order]" class="form-control" value="0" min="0">
                                            </div>
                                            <div class="col-md-2 d-flex align-items-end pb-1">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="options[__IDX__][required]" value="1" id="req___IDX__">
                                                    <label class="form-check-label" for="req___IDX__">Requerido</label>
                                                </div>
                                            </div>
                                            <div class="col-12 option-values-section" style="display:none;">
                                                <label class="form-label">Valores de la opcion</label>
                                                <div class="option-values-list"></div>
                                                <button type="button" class="btn btn-outline-secondary btn-sm btn-add-value mt-1">
                                                    <i class="fas fa-plus me-1"></i> Valor
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <template id="option-value-template">
                                <div class="input-group mb-2 option-value-row">
                                    <input type="text" name="options[__IDX__][values][__VIDX__][option_value]" class="form-control" placeholder="Valor">
                                    <input type="number" step="0.01" name="options[__IDX__][values][__VIDX__][affect_price]" class="form-control" placeholder="Precio" style="max-width:100px">
                                    <select name="options[__IDX__][values][__VIDX__][affect_type]" class="form-select" style="max-width:110px">
                                        <option value="plus">+ Suma</option>
                                        <option value="minus">- Resta</option>
                                        <option value="percent">% Porcent</option>
                                    </select>
                                    <button type="button" class="btn btn-outline-secondary btn-remove-value"><i class="fas fa-times"></i></button>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
                <div class="card-footer text-end">
                    <a href="{{ route('ecommerce.products.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Guardar producto</button>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
@include('ecommerce::partials._wysiwyg-script')
<script>
(function () {
    var optIdx = 0;

    document.getElementById('btn-add-option').addEventListener('click', function () {
        addOption();
    });

    function addOption() {
        var tmpl = document.getElementById('option-template').innerHTML.replace(/__IDX__/g, optIdx);
        var div = document.createElement('div');
        div.innerHTML = tmpl;
        document.getElementById('options-container').appendChild(div.firstElementChild);
        updateOptionNumbers();
        optIdx++;
    }

    document.getElementById('options-container').addEventListener('click', function (e) {
        if (e.target.closest('.btn-remove-option')) {
            e.target.closest('.option-block').remove();
            updateOptionNumbers();
        }
        if (e.target.closest('.btn-add-value')) {
            var block = e.target.closest('.option-block');
            addValue(block, block.dataset.index);
        }
        if (e.target.closest('.btn-remove-value')) {
            e.target.closest('.option-value-row').remove();
        }
    });

    document.getElementById('options-container').addEventListener('change', function (e) {
        if (e.target.classList.contains('option-type-select')) {
            var section = e.target.closest('.option-block').querySelector('.option-values-section');
            section.style.display = ['select', 'checkbox', 'radio'].includes(e.target.value) ? '' : 'none';
        }
    });

    function addValue(block, idx) {
        var list = block.querySelector('.option-values-list');
        var vIdx = list.children.length;
        var tmpl = document.getElementById('option-value-template').innerHTML
            .replace(/__IDX__/g, idx).replace(/__VIDX__/g, vIdx);
        var div = document.createElement('div');
        div.innerHTML = tmpl;
        list.appendChild(div.firstElementChild);
    }

    function updateOptionNumbers() {
        document.querySelectorAll('#options-container .option-block').forEach(function (block, i) {
            block.querySelector('.option-num').textContent = i + 1;
        });
    }
}());
</script>
@endpush
