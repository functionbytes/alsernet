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
                            <h6 class="mb-0 fw-bold">Configuración del menú</h6>
                            <small class="text-muted">Nombre, slug y ubicación</small>
                        </div>
                        <div class="card-body p-4">
                            @include('core::components.alerts')

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Nombre <span class="text-danger">*</span></label>
                                <input type="text" name="name" value="{{ old('name', $menu->name) }}"
                                       required class="form-control @error('name') is-invalid @enderror">
                                <small class="form-text text-muted">El slug se actualiza automáticamente desde el nombre</small>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Slug</label>
                                <input type="text" name="slug" value="{{ old('slug', $menu->slug) }}"
                                       class="form-control @error('slug') is-invalid @enderror">
                                <small class="form-text text-muted">Identificador único del menú en URL</small>
                                @error('slug')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Ubicación</label>
                                <select name="location" class="select2 form-select">
                                    <option value="">Sin ubicación</option>
                                    @foreach($locations as $key => $label)
                                        <option value="{{ $key }}"
                                            {{ old('location', $menu->location) == $key ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                <small class="form-text text-muted">Zona del tema donde se mostrará el menú</small>
                            </div>

                            <div class="mb-0">
                                <label class="form-label fw-semibold">Estado</label>
                                <select name="status" class="select2 form-select">
                                    <option value="1" {{ old('status', $menu->status ? '1' : '0') == '1' ? 'selected' : '' }}>Activo</option>
                                    <option value="0" {{ old('status', $menu->status ? '1' : '0') == '0' ? 'selected' : '' }}>Inactivo</option>
                                </select>
                                <small class="form-text text-muted">Los menús inactivos no se muestran en el sitio</small>
                            </div>
                        </div>
                        <div class="card-footer p-4">
                            <button type="submit" form="form-menu" class="btn btn-primary w-100 mb-2">
                               Guardar menú
                            </button>
                            <a href="{{ route('settings.menus.index') }}" class="btn btn-secondary w-100">
                                Volver al listado
                            </a>
                        </div>
                    </div>

                    {{-- Keyboard Shortcuts --}}
                    <div class="card">
                        <div class="card-header border-bottom p-3">
                            <h6 class="mb-0 fw-bold">Atajos de teclado</h6>
                            <small class="text-muted">Acelera tu trabajo con estos atajos</small>
                        </div>
                        <div class="card-body p-0">
                            <div class="list-group list-group-flush">
                                <div class="list-group-item px-3 py-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-muted">Guardar menú</span>
                                        <kbd class="bg-black text-white px-2 py-1 rounded">Ctrl+S</kbd>
                                    </div>
                                </div>
                                <div class="list-group-item px-3 py-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-muted">Reordenar items</span>
                                        <kbd class="bg-black text-white px-2 py-1 rounded">Arrastrar</kbd>
                                    </div>
                                </div>
                                <div class="list-group-item px-3 py-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-muted">Crear subitem</span>
                                        <kbd class="bg-black text-white px-2 py-1 rounded">→ al arrastrar</kbd>
                                    </div>
                                </div>
                                <div class="list-group-item px-3 py-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-muted">Ver/editar detalles</span>
                                        <kbd class="bg-black text-white px-2 py-1 rounded">›</kbd>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                {{-- Right column: menu tree --}}
                <div class="col-lg-8">
                    <div class="card">
                        {{-- Header: título + estado --}}
                        <div class="card-header p-3 border-bottom">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0 fw-bold">Estructura del menú</h6>
                                    <small class="text-muted">Arrastra los items para reordenar</small>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="text-info small d-none d-md-inline">
                                        <i class="fas fa-keyboard me-1"></i>Ctrl+S para guardar
                                    </span>
                                    <span class="badge bg-black text-white" id="menuStatus">Listo</span>
                                </div>
                            </div>
                        </div>
                        {{-- Toolbar: agregar items al menú --}}
                        <div class="card-body border-bottom py-2 px-3 bg-light">
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <small class="text-muted fw-semibold me-1">Agregar al menú:</small>
                                <button type="button" class="btn  btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalCustomLink">
                                    Enlace personalizado
                                </button>
                                @php $hasRefs = collect($references ?? [])->filter(fn($r) => $r->isNotEmpty())->isNotEmpty(); @endphp
                                @if($hasRefs)
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalContent">
                                        Páginas y contenido
                                    </button>
                                @endif
                            </div>
                        </div>
                        <div class="card-body p-4">
                            <div class="dd myadmin-dd-empty nestable-menu" id="nestable" data-depth="3">
                                @include('template::partials.menu-tree', ['menu_nodes' => $menu->items, 'menu' => $menu])
                            </div>

                            @if($menu->items->isEmpty())
                                <div id="menu-empty" class="text-center py-5 text-muted">
                                    <i class="fas fa-list fa-3x mb-3 d-block opacity-50"></i>
                                    <p class="mb-1 fw-semibold">No hay items en este menú</p>
                                    <small>Usa los botones "Enlace" o "Contenido" para agregar items</small>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

            </div>
        </form>
    </div>

    {{-- Modal: Agregar enlace personalizado --}}
    <div class="modal fade" id="modalCustomLink" tabindex="-1" aria-labelledby="modalCustomLinkLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="modalCustomLinkLabel">
                        <i class="fas fa-link me-2 text-primary"></i>Agregar enlace personalizado
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Título <span class="text-danger">*</span></label>
                        <input type="text" id="item-title" class="form-control" placeholder="Ej: Inicio">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">URL</label>
                        <input type="text" id="item-url" class="form-control" placeholder="https://ejemplo.com">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Icono</label>
                            <input type="text" id="item-icon" class="form-control" placeholder="fas fa-home">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Clase CSS</label>
                            <input type="text" id="item-css-class" class="form-control">
                        </div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-semibold">Abrir en</label>
                        <select id="item-target" class="form-select">
                            <option value="_self">Misma ventana</option>
                            <option value="_blank">Nueva pestaña</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer flex-column">
                    <button type="button" class="btn btn-primary w-100 mb-2 btn-add-custom">
                        Agregar al menú
                    </button>
                    <button type="button" class="btn btn-black w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal: Agregar contenido (páginas, artículos, categorías) --}}
    @php $hasRefs = collect($references ?? [])->filter(fn($r) => $r->isNotEmpty())->isNotEmpty(); @endphp
    @if($hasRefs)
    <div class="modal fade" id="modalContent" tabindex="-1" aria-labelledby="modalContentLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="modalContentLabel">
                        Agregar contenido al menú
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body p-0">
                    {{-- Search bar --}}
                    <div class="p-3 border-bottom">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" id="ref-search" class="form-control" placeholder="Buscar páginas, artículos, categorías...">
                            <span class="input-group-text d-none" id="ref-search-spinner">
                                <span class="spinner-border spinner-border-sm" role="status"></span>
                            </span>
                        </div>
                    </div>

                    {{-- Tabs --}}
                    <ul class="nav nav-tabs nav-fill border-bottom px-3 pt-3" id="contentTabs" role="tablist">
                        @if(isset($references['pages']) && $references['pages']->isNotEmpty())
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-pages" type="button">
                                    <i class="fas fa-file-alt me-1"></i> Páginas
                                    <span class="badge bg-secondary ms-1 ref-count" id="count-pages">{{ $references['pages']->count() }}</span>
                                </button>
                            </li>
                        @endif
                        @if(isset($references['posts']) && $references['posts']->isNotEmpty())
                            <li class="nav-item" role="presentation">
                                <button class="nav-link {{ !isset($references['pages']) || $references['pages']->isEmpty() ? 'active' : '' }}"
                                        data-bs-toggle="tab" data-bs-target="#tab-posts" type="button">
                                    <i class="fas fa-newspaper me-1"></i> Artículos
                                    <span class="badge bg-secondary ms-1 ref-count" id="count-posts">{{ $references['posts']->count() }}</span>
                                </button>
                            </li>
                        @endif
                        @if(isset($references['categories']) && $references['categories']->isNotEmpty())
                            <li class="nav-item" role="presentation">
                                <button class="nav-link {{ !isset($references['pages']) || $references['pages']->isEmpty() ? (isset($references['posts']) && $references['posts']->isNotEmpty() ? '' : 'active') : '' }}"
                                        data-bs-toggle="tab" data-bs-target="#tab-categories" type="button">
                                    <i class="fas fa-tags me-1"></i> Categorías
                                    <span class="badge bg-secondary ms-1 ref-count" id="count-categories">{{ $references['categories']->count() }}</span>
                                </button>
                            </li>
                        @endif
                    </ul>

                    <div class="tab-content p-3" id="ref-tab-content" style="max-height: 380px; overflow-y: auto;">
                        @if(isset($references['pages']) && $references['pages']->isNotEmpty())
                            <div class="tab-pane fade show active" id="tab-pages" role="tabpanel">
                                <ul class="list-unstyled mb-0 ref-list" data-type="pages">
                                    @foreach($references['pages'] as $page)
                                        <li class="py-1 border-bottom">
                                            <div class="form-check">
                                                <input class="form-check-input ref-checkbox" type="checkbox"
                                                       id="modal-page-{{ $page['id'] }}"
                                                       data-reference-type="{{ $page['reference_type'] }}"
                                                       data-reference-id="{{ $page['id'] }}"
                                                       data-title="{{ $page['title'] }}"
                                                       data-type="page">
                                                <label class="form-check-label" for="modal-page-{{ $page['id'] }}">
                                                    {{ $page['title'] }}
                                                </label>
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        @if(isset($references['posts']) && $references['posts']->isNotEmpty())
                            <div class="tab-pane fade {{ !isset($references['pages']) || $references['pages']->isEmpty() ? 'show active' : '' }}"
                                 id="tab-posts" role="tabpanel">
                                <ul class="list-unstyled mb-0 ref-list" data-type="posts">
                                    @foreach($references['posts'] as $post)
                                        <li class="py-1 border-bottom">
                                            <div class="form-check">
                                                <input class="form-check-input ref-checkbox" type="checkbox"
                                                       id="modal-post-{{ $post['id'] }}"
                                                       data-reference-type="{{ $post['reference_type'] }}"
                                                       data-reference-id="{{ $post['id'] }}"
                                                       data-title="{{ $post['title'] }}"
                                                       data-type="post">
                                                <label class="form-check-label" for="modal-post-{{ $post['id'] }}">
                                                    {{ $post['title'] }}
                                                </label>
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        @if(isset($references['categories']) && $references['categories']->isNotEmpty())
                            <div class="tab-pane fade" id="tab-categories" role="tabpanel">
                                <ul class="list-unstyled mb-0 ref-list" data-type="categories">
                                    @foreach($references['categories'] as $category)
                                        <li class="py-1 border-bottom">
                                            <div class="form-check">
                                                <input class="form-check-input ref-checkbox" type="checkbox"
                                                       id="modal-category-{{ $category['id'] }}"
                                                       data-reference-type="{{ $category['reference_type'] }}"
                                                       data-reference-id="{{ $category['id'] }}"
                                                       data-title="{{ $category['title'] }}"
                                                       data-type="category">
                                                <label class="form-check-label" for="modal-category-{{ $category['id'] }}">
                                                    {{ $category['title'] }}
                                                </label>
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                </div>
                <div class="modal-footer flex-column">
                    <button type="button" class="btn btn-primary w-100 mb-2 btn-add-ref">
                       Agregar seleccionados al menú
                    </button>
                    <button type="button" class="btn btn-black w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>
    @endif

@endsection

@push('css')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/nestable2/1.6.0/jquery.nestable.min.css">
<style>
/* Fix nestable2 .collapse conflict with Bootstrap .collapse */
#form-menu .collapse { visibility: visible !important; }
#form-menu .collapse:not(.show) { display: none !important; }
/* Flex content row */
.myadmin-dd-empty .dd-list .dd3-content {
    height: auto;
    display: flex;
    align-items: center;
    min-height: 36px;
}
/* Grip icon: same positioning as theme Tabler icon, but using FA Pro */
.myadmin-dd-empty .dd-item .dd3-handle:before {
    content: "\f58e" !important;
    font-family: "Font Awesome 6 Pro" !important;
    font-weight: 900 !important;
    display: block !important;
    position: absolute !important;
    left: 0 !important;
    width: 100% !important;
    text-align: center !important;
    text-indent: 0 !important;
    font-size: 14px !important;
    color: #adb5bd !important;
    top: 7px !important;
}
/* Item details panel */
.item-details {
    border: var(--bb-border-width) var(--bb-border-style) var(--bb-border-color);
    border-top: 0;
    display: none;
    margin-bottom: 5px;
    max-width: 363px;
    padding: 10px 15px;
    background: var(--bb-bg-surface);
    display: block;
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
        this.hasChanges = false;
    }

    init() {
        if (!this.$nestable.length) { return; }

        const depth = parseInt(this.$nestable.data('depth')) || 3;
        this.$nestable.nestable({
            group: 1,
            maxDepth: depth,
            expandBtnHTML: '',
            collapseBtnHTML: '',
        });

        this.attachEventHandlers();
    }

    initSelect2($container) {
        $container.find('select.select2').each(function () {
            if (!$(this).hasClass('select2-hidden-accessible')) {
                $(this).select2({ width: '100%' });
            }
        });
    }

    updateStatus(status, type = 'black') {
        const $badge = $('#menuStatus');
        $badge.attr('class', 'badge bg-' + type + ' text-white').text(status);
    }

    markChanged() {
        this.hasChanges = true;
        this.updateStatus('Modificado', 'warning');
    }

    markSaved() {
        this.hasChanges = false;
        this.updateStatus('Listo', 'black');
    }

    attachEventHandlers() {
        // Ctrl+S to save
        $(document).on('keydown', (e) => {
            if (e.ctrlKey && e.key === 's') {
                e.preventDefault();
                $('#form-menu').trigger('submit');
            }
        });

        // Track changes on nestable reorder
        this.$nestable.on('change', () => this.markChanged());

        // Track changes on form inputs
        $('#form-menu').on('input change', 'input, select, textarea', () => this.markChanged());

        // Toggle node details panel
        $(document).on('click', '.show-item-details', (e) => {
            e.preventDefault();
            const $li  = $(e.currentTarget).closest('.dd-item');
            const $det = $li.find('> .item-details');
            const isHidden = $det.hasClass('d-none');
            $det.toggleClass('d-none');
            $(e.currentTarget).find('i').toggleClass('fa-chevron-down fa-chevron-up');
            if (isHidden) { this.initSelect2($det); }
        });

        // Sync input changes → data-menu-item + visible title
        $(document).on('input change blur', '.item-details input, .item-details select', (e) => {
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

            const modal = bootstrap.Modal.getInstance(document.getElementById('modalCustomLink'));
            if (modal) { modal.hide(); }
        });

        // Add reference items (pages, posts, categories)
        $(document).on('click', '.btn-add-ref', (e) => {
            e.preventDefault();
            const $container = $(e.currentTarget).closest('.modal, .card');
            const $checked = $container.find('.ref-checkbox:checked');

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

            const $modal = $(e.currentTarget).closest('.modal');
            if ($modal.length) {
                const modal = bootstrap.Modal.getInstance($modal[0]);
                if (modal) { modal.hide(); }
            }
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
            this.markChanged();
            this.updateEmptyState();
        });

        // Cancel edits
        $(document).on('click', '.btn-cancel', (e) => {
            e.preventDefault();
            const $li = $(e.currentTarget).closest('.dd-item');
            $li.find('.item-details input, .item-details select').each((i, el) => {
                $(el).val($(el).data('old')).trigger('change');
            });
            $li.find('> .item-details').addClass('d-none');
            $li.find('.show-item-details i').removeClass('fa-chevron-up').addClass('fa-chevron-down');
        });

        // Intercept form submit to serialize tree
        $('#form-menu').on('submit', () => {
            const nodes = this.serializeTree(this.$nestable.find('> ol.dd-list > .dd-item'));
            $('#nestable-output').val(JSON.stringify(nodes));
            this.markSaved();
        });

        // Warn on unsaved changes when navigating away
        window.addEventListener('beforeunload', (e) => {
            if (this.hasChanges) {
                e.preventDefault();
                return '';
            }
        });
    }

    createNode(data) {
        $.ajax({
            url:      this.nodeUrl,
            method:   'POST',
            dataType: 'json',
            data:     { data, _token: this.csrfToken },
            success: (response) => {
                let $list = this.$nestable.find('> ol.dd-list');
                if (!$list.length) {
                    $list = $('<ol class="dd-list">').appendTo(this.$nestable);
                    $('#menu-empty').addClass('d-none');
                }
                $list.append($(response.html));
                this.$nestable.nestable('reset');
                this.markChanged();
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
    const menu = new MenuNestable();
    menu.init();
    // Select2 for static selects
    $('.select2').select2({ width: '100%' });
    $('#item-target').select2({ width: '100%', dropdownParent: $('#modalCustomLink') });

    // Reference search with debounce
    let searchTimer = null;

    $('#ref-search').on('input', function () {
        clearTimeout(searchTimer);
        const query = $(this).val().trim();

        searchTimer = setTimeout(function () {
            if (query.length > 0 && query.length < 2) { return; }
            searchReferences(query);
        }, 350);
    });

    // Clear search when modal closes
    $('#modalContent').on('hidden.bs.modal', function () {
        $('#ref-search').val('');
    });

    function searchReferences(query) {
        $('#ref-search-spinner').removeClass('d-none');

        $.ajax({
            url: '{{ route("settings.menus.references") }}',
            method: 'GET',
            data: { search: query },
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (data) {
                renderReferenceList('pages', data.pages ?? []);
                renderReferenceList('posts', data.posts ?? []);
                renderReferenceList('categories', data.categories ?? []);
            },
            error: function () {
                toastr.error('Error al buscar referencias.');
            },
            complete: function () {
                $('#ref-search-spinner').addClass('d-none');
            },
        });
    }

    function renderReferenceList(type, items) {
        const $list = $('.ref-list[data-type="' + type + '"]');
        if (!$list.length) { return; }

        const typeMap = { pages: 'page', posts: 'post', categories: 'category' };
        const itemType = typeMap[type];

        if (!items.length) {
            $list.html('<li class="py-2 text-muted text-center small">Sin resultados</li>');
            $('#count-' + type).text(0);
            return;
        }

        const html = items.map(function (item) {
            const id = 'modal-' + itemType + '-' + item.id;
            return `<li class="py-1 border-bottom">
                <div class="form-check">
                    <input class="form-check-input ref-checkbox" type="checkbox"
                           id="${id}"
                           data-reference-type="${item.reference_type}"
                           data-reference-id="${item.id}"
                           data-title="${item.title}"
                           data-type="${itemType}">
                    <label class="form-check-label" for="${id}">${item.title}</label>
                </div>
            </li>`;
        }).join('');

        $list.html(html);
        $('#count-' + type).text(items.length);
    }
});
</script>
@endpush
