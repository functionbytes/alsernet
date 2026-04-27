@extends('layouts.theme')

@section('title', 'Categorias')

@section('content')
    @include('core::components.card', ['title' => 'Ecommerce - Categorias'])
    @include('core::components.alerts')

    <div class="row g-4 align-items-start">

        {{-- Lista de categorias --}}
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header p-3 border-bottom d-flex justify-content-between align-items-center">
                    <small class="text-muted fw-semibold">{{ $categories->total() }} {{ Str::plural('categoria', $categories->total()) }}</small>
                    <button type="button" id="btn-create-mode" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus me-1"></i> Crear
                    </button>
                </div>

                <div class="card-body p-0">
                    @if($categories->count() > 0)
                        <ul class="list-group list-group-flush" id="categories-sortable">
                            @foreach($categories as $category)
                                <li class="list-group-item category-row d-flex align-items-center gap-3 py-2 px-3"
                                    style="cursor:pointer"
                                    data-id="{{ $category->id }}"
                                    data-name="{{ $category->name }}"
                                    data-slug="{{ $category->slug }}"
                                    data-parent="{{ $category->parent_id ?? '' }}"
                                    data-description="{{ $category->description }}"
                                    data-status="{{ $category->status }}"
                                    data-order="{{ $category->order }}"
                                    data-featured="{{ $category->is_featured ? '1' : '0' }}"
                                    data-update-url="{{ route('ecommerce.categories.update', $category) }}"
                                    data-products="{{ $category->products_count }}">
                                    <i class="fas fa-grip-vertical text-muted drag-handle" style="cursor:grab"></i>
                                    <i class="fas fa-folder text-muted small"></i>
                                    <span class="flex-grow-1 small fw-semibold">
                                        {{ $category->name }}
                                        <span class="text-muted fw-normal">({{ $category->products_count }})</span>
                                    </span>
                                    <div class="dropdown" onclick="event.stopPropagation()">
                                        <button type="button" class="btn btn-sm btn-light" data-bs-toggle="dropdown">
                                            <i class="fas fa-ellipsis-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <a class="dropdown-item" href="{{ route('ecommerce.categories.edit', $category) }}">Editar (página completa)</a>
                                            </li>
                                            <li>
                                                <form action="{{ route('ecommerce.categories.destroy', $category) }}" method="POST">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="dropdown-item"
                                                        onclick="return confirm('¿Eliminar esta categoria?')">Eliminar</button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-tags fa-3x text-muted opacity-50 mb-3 d-block"></i>
                            <p class="text-muted small mb-0">Sin categorias. Crea la primera.</p>
                        </div>
                    @endif
                </div>

                @if($categories->hasPages())
                    <div class="card-footer bg-white border-top">
                        {{ $categories->withQueryString()->links() }}
                    </div>
                @endif
            </div>
        </div>

        {{-- Panel formulario --}}
        <div class="col-lg-7">
            <div class="sticky-top" style="top:80px">
                <div class="card">
                    <div class="card-header p-3 border-bottom d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold" id="form-panel-title">Crear categoria</h6>
                        <button type="button" id="btn-reset-form" class="btn btn-sm btn-outline-secondary" style="display:none">
                            Cancelar edición
                        </button>
                    </div>
                    <div class="card-body">
                        <form id="category-form" action="{{ route('ecommerce.categories.store') }}" method="POST">
                            @csrf
                            <input type="hidden" name="_method" id="form-method" value="POST">

                            <div class="mb-3">
                                <label class="form-label">Nombre <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="cat-name" class="form-control" required
                                    placeholder="Nombre de la categoria">
                            </div>

                            {{-- Enlace permanente --}}
                            <div class="mb-3">
                                <label class="form-label">Enlace permanente <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text text-muted small pe-1">{{ rtrim(url('/'), '/') }}/categorias/</span>
                                    <input type="text" name="slug" id="cat-slug" class="form-control" required
                                        placeholder="url-amigable">
                                </div>
                                <div id="permalink-preview" class="mt-1 small text-muted" style="display:none">
                                    Vista: <a id="permalink-link" href="#" target="_blank" class="text-decoration-none"></a>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Padre</label>
                                <select name="parent_id" id="cat-parent" class="form-select">
                                    <option value="">Nada</option>
                                    @foreach($parents as $id => $name)
                                        <option value="{{ $id }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Descripción</label>
                                <textarea name="description" id="cat-description" class="form-control" rows="4"
                                    placeholder="Descripción de la categoria..."></textarea>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <label class="form-label">Estado</label>
                                    <select name="status" id="cat-status" class="form-select">
                                        <option value="published">Publicado</option>
                                        <option value="draft">Borrador</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Orden</label>
                                    <input type="number" name="order" id="cat-order" class="form-control" value="0" min="0">
                                </div>
                                <div class="col-md-4 d-flex align-items-end pb-1">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="is_featured" value="1" id="cat-featured">
                                        <label class="form-check-label" for="cat-featured">Destacado</label>
                                    </div>
                                </div>
                            </div>

                            <div class="d-grid">
                                <button type="submit" id="form-submit-btn" class="btn btn-primary">
                                    Crear categoria
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection

@push('scripts')
@include('ecommerce::partials._sortable-script')
<script>
(function () {
    var storeUrl = '{{ route('ecommerce.categories.store') }}';
    var baseUrl = '{{ rtrim(url('/'), '/') }}/categorias/';
    var $form = $('#category-form');
    var $title = $('#form-panel-title');
    var $method = $('#form-method');
    var $submitBtn = $('#form-submit-btn');
    var $resetBtn = $('#btn-reset-form');
    var $preview = $('#permalink-preview');
    var $previewLink = $('#permalink-link');
    var currentEditId = null;

    function updatePermalink() {
        var slug = $('#cat-slug').val().trim();
        if (slug) {
            var url = baseUrl + slug;
            $previewLink.text(url).attr('href', url);
            $preview.show();
        } else {
            $preview.hide();
        }
    }

    // Auto-slug from name (create mode only)
    $('#cat-name').on('input', function () {
        if (!currentEditId) {
            var slug = $(this).val().toLowerCase()
                .normalize('NFD').replace(/[̀-ͯ]/g, '')
                .replace(/[^a-z0-9\s-]/g, '')
                .trim().replace(/\s+/g, '-');
            $('#cat-slug').val(slug);
            updatePermalink();
        }
    });

    $('#cat-slug').on('input', updatePermalink);

    // Click category row → load into form
    $(document).on('click', '.category-row', function () {
        var $row = $(this);
        var id = $row.data('id');
        var updateUrl = $row.data('update-url');

        currentEditId = id;

        $title.text('Editar: ' + $row.data('name'));
        $form.attr('action', updateUrl);
        $method.val('PUT');
        $submitBtn.text('Actualizar categoria');
        $resetBtn.show();

        $('#cat-name').val($row.data('name'));
        $('#cat-slug').val($row.data('slug'));
        $('#cat-parent').val($row.data('parent') || '');
        $('#cat-description').val($row.data('description') || '');
        $('#cat-status').val($row.data('status'));
        $('#cat-order').val($row.data('order'));
        $('#cat-featured').prop('checked', $row.data('featured') == '1');

        updatePermalink();

        $('.category-row').removeClass('table-active');
        $row.addClass('table-active');

        if (window.innerWidth < 992) {
            document.querySelector('.col-lg-7').scrollIntoView({ behavior: 'smooth' });
        }
    });

    // Crear nueva (reset form)
    $('#btn-create-mode, #btn-reset-form').on('click', function () {
        currentEditId = null;
        $title.text('Crear categoria');
        $form.attr('action', storeUrl);
        $method.val('POST');
        $submitBtn.text('Crear categoria');
        $resetBtn.hide();
        $preview.hide();

        $form[0].reset();
        $('#cat-order').val('0');
        $('.category-row').removeClass('table-active');
    });

    // Drag-and-drop reorder
    var sortableList = document.getElementById('categories-sortable');
    if (sortableList && typeof Sortable !== 'undefined') {
        Sortable.create(sortableList, {
            handle: '.drag-handle',
            animation: 150,
            onEnd: function () {
                var ids = Array.from(sortableList.querySelectorAll('li[data-id]')).map(function (li) {
                    return li.dataset.id;
                });
                fetch('{{ route('ecommerce.categories.reorder') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ ids: ids }),
                }).then(function (r) { return r.json(); }).then(function () {
                    if (typeof toastr !== 'undefined') { toastr.success('Orden actualizado'); }
                }).catch(function () {
                    if (typeof toastr !== 'undefined') { toastr.error('Error al actualizar'); }
                });
            },
        });
    }
}());
</script>
@endpush
