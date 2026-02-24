@extends('layouts.theme')

@section('page_title', 'Editar menú')

@section('content')

    @include('core::components.card', ['title' => 'Editar menú: ' . $menu->name])

    <div class="widget-content">
        <form id="form-menu" action="{{ route('settings.menus.update', $menu) }}" method="POST">
            @csrf
            @method('PUT')

            <input type="hidden" name="deleted_nodes" id="deleted-nodes" value="">
            <textarea name="menu_nodes" id="nestable-output" class="d-none"></textarea>

            <div class="row g-3">

                {{-- Left column --}}
                <div class="col-lg-4">

                    {{-- Menu settings --}}
                    <div class="card mb-3">
                        <div class="card-header p-4 border-bottom">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <h6 class="mb-0 fw-bold">Configuracion del menú</h6>
                                    <small class="text-muted">Nombre, slug y ubicacion</small>
                                </div>
                                @if($menu->status)
                                    <span class="badge bg-success-subtle text-success">Activo</span>
                                @else
                                    <span class="badge bg-danger-subtle text-danger">Inactivo</span>
                                @endif
                            </div>
                        </div>
                        <div class="card-body p-4">
                            @include('core::components.alerts')

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Nombre <span class="text-danger">*</span></label>
                                <input type="text" name="name" value="{{ old('name', $menu->name) }}"
                                       required class="form-control @error('name') is-invalid @enderror">
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Slug</label>
                                <input type="text" name="slug" value="{{ old('slug', $menu->slug) }}"
                                       class="form-control @error('slug') is-invalid @enderror">
                                @error('slug')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Ubicacion</label>
                                <select name="location" class="form-select">
                                    <option value="">Seleccionar ubicacion</option>
                                    @foreach($locations as $key => $label)
                                        <option value="{{ $key }}"
                                            {{ old('location', $menu->location) == $key ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-0">
                                <div class="form-check form-switch">
                                    <input type="checkbox" name="status" value="1" id="menu-status"
                                           class="form-check-input"
                                           {{ old('status', $menu->status) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="menu-status">Activo</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Custom link accordion --}}
                    <div class="card mb-3">
                        <div class="card-header p-4 border-bottom">
                            <a class="fw-semibold text-dark text-decoration-none d-flex align-items-center justify-content-between"
                               data-bs-toggle="collapse" href="#collapseCustomLink" role="button" aria-expanded="false">
                                <span><i class="fas fa-link me-2 text-primary"></i>Agregar enlace personalizado</span>
                                <i class="fas fa-chevron-down  text-muted"></i>
                            </a>
                        </div>
                        <div id="collapseCustomLink" class="collapse">
                            <div class="card-body p-4">
                                <div class="mb-3">
                                    <label class="form-label small fw-semibold">Titulo <span class="text-danger">*</span></label>
                                    <input type="text" id="item-title" class="form-control"
                                           placeholder="Ej: Inicio">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small fw-semibold">URL</label>
                                    <input type="text" id="item-url" class="form-control"
                                           placeholder="https://ejemplo.com">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small fw-semibold">Target</label>
                                    <select id="item-target" class="form-select">
                                        <option value="_self">Misma ventana</option>
                                        <option value="_blank">Nueva ventana</option>
                                    </select>
                                </div>
                                <div class="row g-2 mb-3">
                                    <div class="col-6">
                                        <label class="form-label small fw-semibold">Icono</label>
                                        <input type="text" id="item-icon" class="form-control"
                                               placeholder="fas fa-home">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small fw-semibold">Clase CSS</label>
                                        <input type="text" id="item-css-class" class="form-control">
                                    </div>
                                </div>
                                <button type="button" class="btn btn-primary w-100 btn-add-custom">
                                    <i class="fas fa-plus me-1"></i> Agregar al menú
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Reference panels --}}
                    @if(isset($references['pages']) && $references['pages']->isNotEmpty())
                        <div class="card mb-3">
                            <div class="card-header p-4 border-bottom">
                                <a class="fw-semibold text-dark text-decoration-none d-flex align-items-center justify-content-between"
                                   data-bs-toggle="collapse" href="#collapsePages" role="button" aria-expanded="false">
                                    <span><i class="fas fa-file-alt me-2 text-primary"></i>Páginas</span>
                                    <i class="fas fa-chevron-down  text-muted"></i>
                                </a>
                            </div>
                            <div id="collapsePages" class="collapse">
                                <div class="card-body p-3 list-items" data-ref-type="page">
                                    <ul class="list-unstyled mb-0">
                                        @foreach($references['pages'] as $page)
                                            <li class="py-1">
                                                <div class="form-check">
                                                    <input class="form-check-input ref-checkbox" type="checkbox"
                                                           id="page-{{ $page['id'] }}"
                                                           data-reference-type="{{ $page['reference_type'] }}"
                                                           data-reference-id="{{ $page['id'] }}"
                                                           data-title="{{ $page['title'] }}"
                                                           data-type="page">
                                                    <label class="form-check-label small" for="page-{{ $page['id'] }}">
                                                        {{ $page['title'] }}
                                                    </label>
                                                </div>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                                <div class="card-footer p-3 text-end">
                                    <button type="button" class="btn btn-sm btn-primary btn-add-ref">
                                        <i class="fas fa-plus me-1"></i> Agregar al menú
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if(isset($references['posts']) && $references['posts']->isNotEmpty())
                        <div class="card mb-3">
                            <div class="card-header p-4 border-bottom">
                                <a class="fw-semibold text-dark text-decoration-none d-flex align-items-center justify-content-between"
                                   data-bs-toggle="collapse" href="#collapsePosts" role="button" aria-expanded="false">
                                    <span><i class="fas fa-newspaper me-2 text-primary"></i>Artículos</span>
                                    <i class="fas fa-chevron-down  text-muted"></i>
                                </a>
                            </div>
                            <div id="collapsePosts" class="collapse">
                                <div class="card-body p-3 list-items" data-ref-type="post">
                                    <ul class="list-unstyled mb-0">
                                        @foreach($references['posts'] as $post)
                                            <li class="py-1">
                                                <div class="form-check">
                                                    <input class="form-check-input ref-checkbox" type="checkbox"
                                                           id="post-{{ $post['id'] }}"
                                                           data-reference-type="{{ $post['reference_type'] }}"
                                                           data-reference-id="{{ $post['id'] }}"
                                                           data-title="{{ $post['title'] }}"
                                                           data-type="post">
                                                    <label class="form-check-label small" for="post-{{ $post['id'] }}">
                                                        {{ $post['title'] }}
                                                    </label>
                                                </div>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                                <div class="card-footer p-3 text-end">
                                    <button type="button" class="btn btn-sm btn-primary btn-add-ref">
                                        <i class="fas fa-plus me-1"></i> Agregar al menú
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if(isset($references['categories']) && $references['categories']->isNotEmpty())
                        <div class="card mb-3">
                            <div class="card-header p-4 border-bottom">
                                <a class="fw-semibold text-dark text-decoration-none d-flex align-items-center justify-content-between"
                                   data-bs-toggle="collapse" href="#collapseCategories" role="button" aria-expanded="false">
                                    <span><i class="fas fa-tags me-2 text-primary"></i>Categorías</span>
                                    <i class="fas fa-chevron-down  text-muted"></i>
                                </a>
                            </div>
                            <div id="collapseCategories" class="collapse">
                                <div class="card-body p-3 list-items" data-ref-type="category">
                                    <ul class="list-unstyled mb-0">
                                        @foreach($references['categories'] as $category)
                                            <li class="py-1">
                                                <div class="form-check">
                                                    <input class="form-check-input ref-checkbox" type="checkbox"
                                                           id="category-{{ $category['id'] }}"
                                                           data-reference-type="{{ $category['reference_type'] }}"
                                                           data-reference-id="{{ $category['id'] }}"
                                                           data-title="{{ $category['title'] }}"
                                                           data-type="category">
                                                    <label class="form-check-label small" for="category-{{ $category['id'] }}">
                                                        {{ $category['title'] }}
                                                    </label>
                                                </div>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                                <div class="card-footer p-3 text-end">
                                    <button type="button" class="btn btn-sm btn-primary btn-add-ref">
                                        <i class="fas fa-plus me-1"></i> Agregar al menú
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endif

                </div>

                {{-- Right column: menu tree --}}
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header p-4 border-bottom">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <h6 class="mb-1 fw-bold">Estructura del menú</h6>
                                    <small class="text-muted">Arrastra los items para reordenar</small>
                                </div>
                                <button type="submit" form="form-menu" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i> Guardar menú
                                </button>
                            </div>
                        </div>
                        <div class="card-body p-4">
                            <div class="dd nestable-menu" id="nestable" data-depth="3">
                                @include('template::partials.menu-tree', ['menu_nodes' => $menu->items, 'menu' => $menu])
                            </div>

                            @if($menu->items->isEmpty())
                                <div id="menu-empty" class="text-center py-5 text-muted">
                                    <i class="fas fa-list fa-3x mb-3 d-block opacity-50"></i>
                                    <p class="mb-1 fw-semibold">No hay items en este menú</p>
                                    <small>Usa los paneles de la izquierda para agregar items</small>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

            </div>
        </form>
    </div>

@endsection

@push('css')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/nestable2/1.6.0/jquery.nestable.min.css">
<style>
/* Fix nestable2 .collapse conflict with Bootstrap .collapse */
.collapse { visibility: visible !important; }
.collapse:not(.show) { display: none !important; }
.item-details {
    background: #f8f9fa;
    border-top: 1px solid #dee2e6;
    border-radius: 0 0 4px 4px;
}
.dd3-content {
    border-radius: 4px;
    transition: background .15s;
}
.dd3-content:hover {
    background: #f8f9fa;
}
</style>
@endpush

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/nestable2/1.6.0/jquery.nestable.min.js"
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>

<script>
class MenuNestable {
    constructor() {
        this.$nestable  = $('#nestable');
        this.csrfToken  = $('meta[name="csrf-token"]').attr('content');
        this.nodeUrl    = '{{ route("settings.menus.get-node", $menu) }}';
    }

    init() {
        if (!this.$nestable.length) { return; }

        const depth = parseInt(this.$nestable.data('depth')) || 3;
        this.$nestable.nestable({ group: 1, maxDepth: depth });

        this.attachEventHandlers();
    }

    attachEventHandlers() {
        // Toggle node details panel
        $(document).on('click', '.show-item-details', (e) => {
            e.preventDefault();
            const $li  = $(e.currentTarget).closest('.dd-item');
            const $det = $li.find('> .item-details');
            $det.toggleClass('d-none');
            $(e.currentTarget).find('i').toggleClass('fa-chevron-down fa-chevron-up');
        });

        // Sync input changes → data-menu-item + visible title
        $(document).on('change blur', '.item-details input, .item-details select', (e) => {
            const $input = $(e.currentTarget);
            const $li    = $input.closest('.dd-item');
            const field  = $input.attr('name');
            const value  = $input.val();

            const menuItem     = JSON.parse($li.attr('data-menu-item'));
            menuItem[field]    = value;
            $li.attr('data-menu-item', JSON.stringify(menuItem));

            if (field === 'title') {
                $li.find('> .dd3-content [data-update="title"]').text(value);
            }
        });

        // Add custom link
        $(document).on('click', '.btn-add-custom', (e) => {
            e.preventDefault();
            const title = $('#item-title').val().trim();
            if (!title) { toastr.warning('El titulo es obligatorio.'); return; }

            this.createNode({
                type:      'custom',
                title,
                url:       $('#item-url').val(),
                target:    $('#item-target').val(),
                icon:      $('#item-icon').val(),
                css_class: $('#item-css-class').val(),
            });

            $('#item-title, #item-url, #item-icon, #item-css-class').val('');
            $('#item-target').val('_self');
        });

        // Add reference items (pages, posts, categories)
        $(document).on('click', '.btn-add-ref', (e) => {
            e.preventDefault();
            const $card = $(e.currentTarget).closest('.card');
            const $checked = $card.find('.ref-checkbox:checked');

            if (!$checked.length) { toastr.warning('Selecciona al menos un item.'); return; }

            $checked.each((i, el) => {
                const $cb = $(el);
                this.createNode({
                    type:           $cb.data('type'),
                    reference_type: $cb.data('reference-type'),
                    reference_id:   $cb.data('reference-id'),
                    title:          $cb.data('title'),
                });
                $cb.prop('checked', false);
            });
        });

        // Delete node
        $(document).on('click', '.btn-delete-item', (e) => {
            e.preventDefault();
            const $li   = $(e.currentTarget).closest('.dd-item');
            const itemId = $li.data('id');

            if (itemId && String(itemId).match(/^\d+$/)) {
                const $del     = $('#deleted-nodes');
                const existing = $del.val().trim().split(/\s+/).filter(Boolean);
                existing.push(itemId);
                $del.val(existing.join(' '));
            }

            const $children = $li.find('> ol.dd-list > .dd-item');
            if ($children.length) {
                $li.before($children);
            }

            $li.remove();
            this.updateEmptyState();
        });

        // Cancel edits
        $(document).on('click', '.btn-cancel', (e) => {
            e.preventDefault();
            const $li = $(e.currentTarget).closest('.dd-item');
            $li.find('.item-details input, .item-details select').each((i, el) => {
                $(el).val($(el).data('old'));
            });
            $li.find('.item-details input, .item-details select').trigger('change');
            $li.find('> .item-details').addClass('d-none');
            $li.find('.show-item-details i').removeClass('fa-chevron-up').addClass('fa-chevron-down');
        });

        // Intercept form submit to serialize tree
        $('#form-menu').on('submit', () => {
            const nodes = this.serializeTree(this.$nestable.find('> ol.dd-list > .dd-item'));
            $('#nestable-output').val(JSON.stringify(nodes));
        });
    }

    createNode(data) {
        const params = $.param({ data });

        $.ajax({
            url:      this.nodeUrl + '?' + params,
            method:   'GET',
            dataType: 'json',
            success: (response) => {
                let $list = this.$nestable.find('> ol.dd-list');
                if (!$list.length) {
                    $list = $('<ol class="dd-list">').appendTo(this.$nestable);
                    $('#menu-empty').addClass('d-none');
                }
                $list.append($(response.html));
                this.$nestable.nestable('reset');
                toastr.success('Item agregado correctamente.');
            },
            error: () => {
                toastr.error('Error al agregar el item.');
            },
        });
    }

    serializeTree($items) {
        const nodes = [];

        $items.each((i, el) => {
            const $li      = $(el);
            const menuItem = JSON.parse($li.attr('data-menu-item') || '{}');
            const $children = $li.find('> ol.dd-list > .dd-item');

            nodes.push({
                ...menuItem,
                children: this.serializeTree($children),
            });
        });

        return nodes;
    }

    updateEmptyState() {
        const hasItems = this.$nestable.find('.dd-item').length > 0;
        $('#menu-empty').toggleClass('d-none', hasItems);
    }
}

$(function () {
    new MenuNestable().init();
});
</script>
@endpush
