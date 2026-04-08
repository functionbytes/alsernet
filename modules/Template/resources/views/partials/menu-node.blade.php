@php
    $nodeData = json_encode([
        'id'             => $item->id,
        'menu_id'        => $item->menu_id,
        'parent_id'      => $item->parent_id,
        'title'          => $item->title,
        'url'            => $item->url,
        'icon'           => $item->icon,
        'css_class'      => $item->css_class,
        'target'         => $item->target ?? '_self',
        'type'           => $item->type ?? 'custom',
        'reference_id'   => $item->reference_id,
        'reference_type' => $item->reference_type,
        'has_child'      => $item->has_child,
    ], JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS);

    $typeLabel = match($item->type ?? 'custom') {
        'page'     => 'Página',
        'post'     => 'Artículo',
        'category' => 'Categoría',
        'route'    => 'Ruta',
        default    => 'Enlace personalizado',
    };
@endphp

<li class="dd-item dd3-item" data-id="{{ $item->id }}" data-menu-item='{!! $nodeData !!}'>
    <div class="dd-handle dd3-handle"></div>

    <div class="dd3-content d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2">
            @if($item->icon)
                <i class="{{ $item->icon }} text-muted small"></i>
            @endif
            <span data-update="title" class="fw-medium">{{ $item->title }}</span>
        </div>
        <div class="d-flex align-items-center gap-3 me-1">
            <span class="text-muted small">{{ $typeLabel }}</span>
            <a href="#" class="show-item-details text-muted">
                <i class="fas fa-chevron-down"></i>
            </a>
        </div>
    </div>

    <div class="item-details d-none p-3 border-top">
        <div class="row g-3">
            <div class="col-12">
                <label class="form-label">Título</label>
                <input type="text" name="title" class="form-control"
                       value="{{ $item->title }}" data-old="{{ $item->title }}">
            </div>

            @if(($item->type ?? 'custom') === 'custom')
                <div class="col-12">
                    <label class="form-label">URL</label>
                    <input type="text" name="url" class="form-control"
                           value="{{ $item->url }}" data-old="{{ $item->url }}">
                </div>
            @endif

            <div class="col-12">
                <label class="form-label">Icono</label>
                <input type="text" name="icon" class="form-control"
                       value="{{ $item->icon }}" data-old="{{ $item->icon }}" placeholder="fas fa-home">
            </div>

            <div class="col-12">
                <label class="form-label">Clase CSS</label>
                <input type="text" name="css_class" class="form-control"
                       value="{{ $item->css_class }}" data-old="{{ $item->css_class }}">
            </div>

            <div class="col-12">
                <label class="form-label">Objetivo</label>
                <select name="target" class="form-select select2" data-old="{{ $item->target ?? '_self' }}">
                    <option value="_self" {{ ($item->target ?? '_self') === '_self' ? 'selected' : '' }}>Abrir enlace directamente</option>
                    <option value="_blank" {{ ($item->target ?? '') === '_blank' ? 'selected' : '' }}>Abrir en nueva pestaña</option>
                </select>
            </div>

            <div class="col-12 d-flex flex-column gap-2">
                <button type="button" class="btn btn-danger w-100 btn-delete-item">Eliminar</button>
                <button type="button" class="btn btn-outline-secondary w-100 btn-cancel">Cancelar</button>
            </div>
        </div>
    </div>

    @if($item->has_child && $item->children->isNotEmpty())
        @include('template::partials.menu-tree', ['menu_nodes' => $item->children, 'menu' => $menu])
    @endif
</li>
