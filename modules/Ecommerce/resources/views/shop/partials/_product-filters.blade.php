@php
    $brands = \Modules\Ecommerce\Supports\BrandHelper::getActiveBrands();
    $categories = \Modules\Ecommerce\Supports\ProductCategoryHelper::getNavigationCategories();
    $maxPrice = \Modules\Ecommerce\Models\Product::where('status', 'published')->max('price') ?? 1000;
    $selectedBrandIds = (array) request('brand_ids', []);
    $selectedCategoryIds = (array) request('category_ids', []);
@endphp

<aside class="product-filters" id="productFilters">
    <div class="d-flex justify-content-between align-items-center mb-3 d-lg-none">
        <h5 class="mb-0">Filtros</h5>
        <button type="button" class="btn-close" id="closeMobileFilters" aria-label="Cerrar filtros"></button>
    </div>

    <form id="filtersForm" data-action="{{ url()->current() }}">
        @if(! empty($currentCategorySlug ?? null))
            <input type="hidden" name="category" value="{{ $currentCategorySlug }}">
        @endif

        {{-- Categorias --}}
        @if($categories->count() > 0)
            <div class="filter-group mb-3">
                <h6 class="filter-title">Categorias</h6>
                <div class="filter-options">
                    @foreach($categories as $cat)
                        <div class="form-check">
                            <input class="form-check-input js-filter" type="checkbox" name="category_ids[]"
                                   value="{{ $cat->id }}" id="cat-{{ $cat->id }}"
                                   {{ in_array($cat->id, $selectedCategoryIds) ? 'checked' : '' }}>
                            <label class="form-check-label" for="cat-{{ $cat->id }}">{{ $cat->name }}</label>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Marcas --}}
        @if($brands->count() > 0)
            <div class="filter-group mb-3">
                <h6 class="filter-title">Marcas</h6>
                <div class="filter-options">
                    @foreach($brands as $brand)
                        <div class="form-check">
                            <input class="form-check-input js-filter" type="checkbox" name="brand_ids[]"
                                   value="{{ $brand->id }}" id="brand-{{ $brand->id }}"
                                   {{ in_array($brand->id, $selectedBrandIds) ? 'checked' : '' }}>
                            <label class="form-check-label" for="brand-{{ $brand->id }}">{{ $brand->name }}</label>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Rango de precio --}}
        <div class="filter-group mb-3">
            <h6 class="filter-title">Precio</h6>
            <div class="row g-2">
                <div class="col">
                    <input type="number" name="min_price" class="form-control form-control-sm js-filter"
                           placeholder="Min" min="0" value="{{ request('min_price') }}">
                </div>
                <div class="col">
                    <input type="number" name="max_price" id="maxPriceInput" class="form-control form-control-sm js-filter"
                           placeholder="Max" min="0" value="{{ request('max_price') }}">
                </div>
            </div>
            <input type="range" class="form-range mt-2" id="priceRangeSlider"
                   min="0" max="{{ $maxPrice }}" value="{{ request('max_price', $maxPrice) }}"
                   aria-label="Precio maximo">
        </div>

        {{-- Disponibilidad --}}
        <div class="filter-group mb-3">
            <h6 class="filter-title">Disponibilidad</h6>
            <div class="form-check">
                <input class="form-check-input js-filter" type="checkbox" name="in_stock" value="1"
                       id="filter-stock" {{ request('in_stock') ? 'checked' : '' }}>
                <label class="form-check-label" for="filter-stock">Solo en stock</label>
            </div>
            <div class="form-check">
                <input class="form-check-input js-filter" type="checkbox" name="on_sale" value="1"
                       id="filter-sale" {{ request('on_sale') ? 'checked' : '' }}>
                <label class="form-check-label" for="filter-sale">En oferta</label>
            </div>
        </div>

        {{-- Ordenamiento --}}
        <div class="filter-group mb-3">
            <h6 class="filter-title">Ordenar por</h6>
            <select name="sort" class="form-select form-select-sm js-filter" aria-label="Ordenar por">
                <option value="latest" {{ request('sort') === 'latest' ? 'selected' : '' }}>Mas recientes</option>
                <option value="price_asc" {{ request('sort') === 'price_asc' ? 'selected' : '' }}>Precio: menor a mayor</option>
                <option value="price_desc" {{ request('sort') === 'price_desc' ? 'selected' : '' }}>Precio: mayor a menor</option>
                <option value="name_asc" {{ request('sort') === 'name_asc' ? 'selected' : '' }}>Nombre: A-Z</option>
                <option value="best_selling" {{ request('sort') === 'best_selling' ? 'selected' : '' }}>Mas vendidos</option>
            </select>
        </div>

        <button type="button" class="btn btn-outline-secondary btn-sm w-100 js-clear-filters">
            Limpiar filtros
        </button>
    </form>

    @auth('ecommerce')
    <div class="filter-group mt-3">
        <button type="button" class="btn btn-outline-success btn-sm w-100" data-bs-toggle="modal" data-bs-target="#saveSearchModal">
            <i class="fas fa-bell me-1"></i>Guardar esta busqueda
        </button>
    </div>

    <div class="modal fade" id="saveSearchModal" tabindex="-1" aria-labelledby="saveSearchModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="{{ route('account.saved-searches.store') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="saveSearchModalLabel">Guardar busqueda</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label" for="saveSearchName">Nombre de esta busqueda</label>
                            <input type="text" id="saveSearchName" name="name" class="form-control"
                                   required maxlength="255" placeholder="Ej: Camisetas en oferta">
                        </div>
                        <input type="hidden" name="query" id="saveSearchQuery">
                        <input type="hidden" name="filters[min_price]" id="saveSearchMinPrice">
                        <input type="hidden" name="filters[max_price]" id="saveSearchMaxPrice">
                        <input type="hidden" name="filters[on_sale]" id="saveSearchOnSale">
                        <p class="small text-muted mb-0">Te enviaremos un correo cuando aparezcan nuevos productos.</p>
                    </div>
                    <div class="modal-footer d-block">
                        <button type="submit" class="btn btn-primary w-100 mb-2">Guardar</button>
                        <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    document.getElementById('saveSearchModal')?.addEventListener('show.bs.modal', function () {
        var params = new URLSearchParams(window.location.search);
        document.getElementById('saveSearchQuery').value = params.get('q') || '';
        document.getElementById('saveSearchMinPrice').value = params.get('min_price') || '';
        document.getElementById('saveSearchMaxPrice').value = params.get('max_price') || '';
        document.getElementById('saveSearchOnSale').value = params.get('on_sale') || '';
    });
    </script>
    @endauth
</aside>
