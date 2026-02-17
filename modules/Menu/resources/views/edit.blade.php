@extends('layouts.theme')

@section('title', 'Editar menu: ' . $menu->name)

@section('content')

    @include('core::components.card', ['title' => 'Editar menu'])

    <div class="row">

        {{-- Left column: Settings + Add item --}}
        <div class="col-lg-4">

            {{-- Menu settings --}}
            <div class="card mb-3">
                <div class="card-header p-3 border-bottom">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="mb-0 fw-bold text-dark">Configuracion</h6>
                            <p class="mb-0 text-muted small">Configura los parámetros del menú</p>
                        </div>
                        @if($menu->status)
                            <span class="badge bg-success-subtle text-success">Activo</span>
                        @else
                            <span class="badge bg-danger-subtle text-danger">Inactivo</span>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    @include('core::components.alerts')
                    <form action="{{ route('menu.update', $menu) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label class="control-label col-form-label">Nombre <span class="text-danger">*</span></label>
                            <input type="text" name="name" value="{{ old('name', $menu->name) }}" required class="form-control">
                            @error('name')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="control-label col-form-label">Slug</label>
                            <input type="text" name="slug" value="{{ old('slug', $menu->slug) }}" class="form-control">
                            @error('slug')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="control-label col-form-label">Ubicacion</label>
                            <select name="location" class="form-select">
                                <option value="">Seleccionar ubicacion</option>
                                @foreach($locations as $key => $label)
                                    <option value="{{ $key }}" {{ old('location', $menu->location) == $key ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input type="checkbox" name="status" value="1" id="status"
                                       {{ old('status', $menu->status) ? 'checked' : '' }} class="form-check-input">
                                <label class="form-check-label" for="status">Activo</label>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-save me-1"></i> Actualizar menu
                        </button>
                    </form>
                </div>
            </div>

            {{-- Add menu item --}}
            <div class="card">
                <div class="card-header border-bottom py-3">
                    <h6 class="mb-0 fw-bold text-dark">
                        <i class="fas fa-plus-circle me-1"></i> Agregar item
                    </h6>
                </div>
                <div class="card-body">

                    <div class="mb-3">
                        <label class="control-label col-form-label">Tipo</label>
                        <select id="item-type" class="form-select">
                            <option value="custom">Enlace personalizado</option>
                            @if(isset($references['pages']) && $references['pages']->isNotEmpty())
                                <option value="page">Pagina</option>
                            @endif
                            @if(isset($references['posts']) && $references['posts']->isNotEmpty())
                                <option value="post">Articulo</option>
                            @endif
                            @if(isset($references['categories']) && $references['categories']->isNotEmpty())
                                <option value="category">Categoria</option>
                            @endif
                            <option value="route">Ruta</option>
                        </select>
                    </div>

                    {{-- Custom link fields --}}
                    <div id="fields-custom">
                        <div class="mb-3">
                            <label class="control-label col-form-label">Titulo <span class="text-danger">*</span></label>
                            <input type="text" id="item-title" class="form-control" placeholder="Titulo del enlace">
                        </div>
                        <div class="mb-3">
                            <label class="control-label col-form-label">URL <span class="text-danger">*</span></label>
                            <input type="text" id="item-url" class="form-control" placeholder="https://ejemplo.com">
                        </div>
                    </div>

                    {{-- Page reference --}}
                    @if(isset($references['pages']) && $references['pages']->isNotEmpty())
                        <div id="fields-page" class="d-none">
                            <div class="mb-3">
                                <label class="control-label col-form-label">Seleccionar pagina <span class="text-danger">*</span></label>
                                <select id="ref-page" class="form-select">
                                    <option value="">Seleccionar...</option>
                                    @foreach($references['pages'] as $page)
                                        <option value="{{ $page['id'] }}" data-title="{{ $page['title'] }}">{{ $page['title'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    @endif

                    {{-- Post reference --}}
                    @if(isset($references['posts']) && $references['posts']->isNotEmpty())
                        <div id="fields-post" class="d-none">
                            <div class="mb-3">
                                <label class="control-label col-form-label">Seleccionar articulo <span class="text-danger">*</span></label>
                                <select id="ref-post" class="form-select">
                                    <option value="">Seleccionar...</option>
                                    @foreach($references['posts'] as $post)
                                        <option value="{{ $post['id'] }}" data-title="{{ $post['title'] }}">{{ $post['title'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    @endif

                    {{-- Category reference --}}
                    @if(isset($references['categories']) && $references['categories']->isNotEmpty())
                        <div id="fields-category" class="d-none">
                            <div class="mb-3">
                                <label class="control-label col-form-label">Seleccionar categoria <span class="text-danger">*</span></label>
                                <select id="ref-category" class="form-select">
                                    <option value="">Seleccionar...</option>
                                    @foreach($references['categories'] as $category)
                                        <option value="{{ $category['id'] }}" data-title="{{ $category['title'] }}">{{ $category['title'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    @endif

                    {{-- Route fields --}}
                    <div id="fields-route" class="d-none">
                        <div class="mb-3">
                            <label class="control-label col-form-label">Titulo <span class="text-danger">*</span></label>
                            <input type="text" id="item-title-route" class="form-control" placeholder="Titulo del enlace">
                        </div>
                        <div class="mb-3">
                            <label class="control-label col-form-label">Nombre de ruta <span class="text-danger">*</span></label>
                            <input type="text" id="item-route-name" class="form-control" placeholder="home">
                        </div>
                    </div>

                    {{-- Advanced options (collapsible) --}}
                    <div class="mb-3">
                        <a class="text-muted small" data-bs-toggle="collapse" href="#advancedOptions" role="button">
                            <i class="fas fa-chevron-down me-1"></i> Opciones avanzadas
                        </a>
                    </div>

                    <div class="collapse" id="advancedOptions">
                        <div class="mb-3">
                            <label class="control-label col-form-label">Icono <small class="text-muted">(clase CSS)</small></label>
                            <div class="input-group">
                                <span class="input-group-text" id="icon-preview"><i class="fas fa-icons"></i></span>
                                <input type="text" id="item-icon" class="form-control" placeholder="fas fa-home">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="control-label col-form-label">Target</label>
                            <select id="item-target" class="form-select">
                                <option value="_self">Misma ventana</option>
                                <option value="_blank">Nueva ventana</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="control-label col-form-label">Clase CSS</label>
                            <input type="text" id="item-css-class" class="form-control" placeholder="mi-clase-css">
                        </div>
                    </div>

                    <button type="button" id="btn-add-item" class="btn btn-success w-100">
                        <i class="fas fa-plus me-1"></i> Agregar item
                    </button>
                </div>
            </div>

        </div>

        {{-- Right column: Menu structure --}}
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header border-bottom py-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="mb-0 fw-bold text-dark">
                                <i class="fas fa-sitemap me-1"></i> Estructura del menu
                            </h6>
                            <small class="text-muted">Arrastra los items para reordenar</small>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-primary-subtle text-primary" id="items-count">
                                {{ $menu->items->count() }} items
                            </span>
                            <button type="button" id="btn-save-structure" class="btn btn-sm btn-primary">
                                <i class="fas fa-save me-1"></i> Guardar orden
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">

                    <div id="menu-items">
                        {{-- Rendered by JS --}}
                    </div>

                    <div id="menu-empty" class="text-center py-5 {{ $menu->items->count() ? 'd-none' : '' }}">
                        <div class="text-muted">
                            <i class="fas fa-list fa-3x mb-3 d-block opacity-50"></i>
                            <p class="mb-1">No hay items en este menu</p>
                            <small>Usa el formulario de la izquierda para agregar items</small>
                        </div>
                    </div>

                </div>
            </div>
        </div>

    </div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
$(function () {
    var menuItems = @json($menu->items);
    var csrfToken = $('meta[name="csrf-token"]').attr('content');

    var referenceTypes = {
        page: '{{ isset($references["pages"]) && $references["pages"]->isNotEmpty() ? addslashes(get_class($references["pages"]->first())) : "" }}',
        post: '{{ isset($references["posts"]) && $references["posts"]->isNotEmpty() ? addslashes(get_class($references["posts"]->first())) : "" }}',
        category: '{{ isset($references["categories"]) && $references["categories"]->isNotEmpty() ? addslashes(get_class($references["categories"]->first())) : "" }}'
    };

    var typeLabels = { custom: 'Enlace', page: 'Pagina', post: 'Articulo', category: 'Categoria', route: 'Ruta' };
    var typeColors = { custom: 'primary', page: 'info', post: 'warning', category: 'success', route: 'secondary' };

    // Icon preview
    $('#item-icon').on('input', function () {
        var val = $(this).val().trim();
        $('#icon-preview').html(val ? '<i class="' + $('<span>').text(val).html() + '"></i>' : '<i class="fas fa-icons"></i>');
    });

    // Toggle type fields
    $('#item-type').on('change', function () {
        var type = $(this).val();
        $('#fields-custom, #fields-page, #fields-post, #fields-category, #fields-route').addClass('d-none');
        if (type === 'custom') {
            $('#fields-custom').removeClass('d-none');
        } else if (type === 'route') {
            $('#fields-route').removeClass('d-none');
        } else {
            $('#fields-' + type).removeClass('d-none');
        }
    });

    // Render items
    function renderItems() {
        var $container = $('#menu-items').empty();
        if (!menuItems.length) {
            $('#menu-empty').removeClass('d-none');
            return;
        }
        $('#menu-empty').addClass('d-none');
        $.each(menuItems, function (i, item) {
            $container.append(buildItemHtml(item, 0));
        });
        initSortable();
    }

    function buildItemHtml(item, level) {
        var indent = level * 28;
        var type = item.type || 'custom';
        var color = typeColors[type] || 'secondary';
        var label = typeLabels[type] || type;
        var iconHtml = item.icon ? '<i class="' + $('<span>').text(item.icon).html() + ' me-2 text-muted"></i>' : '';

        var html = '' +
            '<div class="menu-item border rounded p-3 mb-2 bg-white shadow-sm" style="margin-left:' + indent + 'px" data-id="' + item.id + '">' +
                '<div class="d-flex align-items-center justify-content-between">' +
                    '<div class="d-flex align-items-center gap-3">' +
                        '<span class="drag-handle text-muted" style="cursor:move" title="Arrastrar"><i class="fas fa-grip-vertical fa-lg"></i></span>' +
                        '<div>' +
                            '<div class="d-flex align-items-center gap-2">' +
                                iconHtml +
                                '<span class="fw-semibold">' + $('<span>').text(item.title).html() + '</span>' +
                                '<span class="badge bg-' + color + '-subtle text-' + color + ' ms-1" style="font-size:0.7rem">' + label + '</span>' +
                            '</div>' +
                            '<small class="text-muted">' + $('<span>').text(item.url || item.full_url || '').html() + '</small>' +
                        '</div>' +
                    '</div>' +
                    '<div class="d-flex gap-1">' +
                        '<button type="button" class="btn btn-sm btn-outline-danger btn-delete-item" data-id="' + item.id + '" title="Eliminar">' +
                            '<i class="fas fa-trash-alt"></i>' +
                        '</button>' +
                    '</div>' +
                '</div>' +
            '</div>';

        if (item.children && item.children.length) {
            $.each(item.children, function (j, child) {
                html += buildItemHtml(child, level + 1);
            });
        }
        return html;
    }

    function initSortable() {
        var el = document.getElementById('menu-items');
        if (el) {
            Sortable.create(el, { animation: 150, handle: '.drag-handle' });
        }
    }

    // Add item
    $('#btn-add-item').on('click', function () {
        var type = $('#item-type').val();
        var data = {
            type: type,
            title: '',
            url: '',
            icon: $('#item-icon').val(),
            target: $('#item-target').val(),
            css_class: $('#item-css-class').val(),
            reference_id: null,
            reference_type: referenceTypes[type] || null,
        };

        if (type === 'custom') {
            data.title = $('#item-title').val();
            data.url = $('#item-url').val();
        } else if (type === 'route') {
            data.title = $('#item-title-route').val();
            data.url = $('#item-route-name').val();
        } else {
            var $select = $('#ref-' + type);
            data.reference_id = $select.val();
            data.title = $select.find(':selected').data('title') || '';
        }

        if (!data.title) {
            toastr.error('El titulo es obligatorio.');
            return;
        }

        var $btn = $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Agregando...');

        $.ajax({
            url: '{{ route("menu.items.store", $menu) }}',
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            contentType: 'application/json',
            data: JSON.stringify(data),
            success: function (res) {
                if (res.success) {
                    toastr.success('Item agregado correctamente.');
                    location.reload();
                }
            },
            error: function (xhr) {
                $btn.prop('disabled', false).html('<i class="fas fa-plus me-1"></i> Agregar item');
                var errors = xhr.responseJSON && xhr.responseJSON.errors;
                if (errors) {
                    $.each(errors, function (field, msgs) { toastr.error(msgs[0]); });
                } else {
                    toastr.error('Error al agregar el item.');
                }
            }
        });
    });

    // Delete item
    $(document).on('click', '.btn-delete-item', function () {
        if (!confirm('¿Seguro que deseas eliminar este item?')) return;

        var $btn = $(this);
        var itemId = $btn.data('id');
        $btn.prop('disabled', true);

        $.ajax({
            url: '{{ route("menu.items.destroy", [$menu, "__ITEM__"]) }}'.replace('__ITEM__', itemId),
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            success: function (res) {
                if (res.success) {
                    toastr.success('Item eliminado.');
                    location.reload();
                }
            },
            error: function () {
                $btn.prop('disabled', false);
                toastr.error('Error al eliminar el item.');
            }
        });
    });

    // Save structure
    $('#btn-save-structure').on('click', function () {
        var structure = [];
        var $btn = $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Guardando...');

        $('#menu-items > .menu-item').each(function (i) {
            structure.push({ id: $(this).data('id'), order: i, children: [] });
        });

        $.ajax({
            url: '{{ route("menu.structure.update", $menu) }}',
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            contentType: 'application/json',
            data: JSON.stringify({ items: structure }),
            success: function (res) {
                $btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i> Guardar orden');
                if (res.success) {
                    toastr.success('Estructura guardada correctamente.');
                }
            },
            error: function () {
                $btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i> Guardar orden');
                toastr.error('Error al guardar la estructura.');
            }
        });
    });

    // Tooltips
    $('[title]').tooltip({ trigger: 'hover' });

    // Initial render
    renderItems();
});
</script>
@endpush
