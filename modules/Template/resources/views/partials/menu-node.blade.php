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
    ]);
@endphp

<li class="dd-item dd3-item" data-id="{{ $item->id }}" data-menu-item="{{ $nodeData }}">
    <div class="dd-handle dd3-handle"></div>

    <div class="dd3-content d-flex align-items-center gap-2 pe-3">
        @if($item->icon)
            <i class="{{ $item->icon }} text-muted"></i>
        @endif
        <span data-update="title" class="fw-semibold">{{ $item->title }}</span>
        @php
            $typeBadge = match($item->type ?? 'custom') {
                'page'     => 'bg-primary-subtle text-primary',
                'post'     => 'bg-success-subtle text-success',
                'category' => 'bg-purple-subtle text-purple',
                'route'    => 'bg-warning-subtle text-warning',
                default    => 'bg-secondary-subtle text-secondary',
            };
        @endphp
        <span class="badge {{ $typeBadge }} ms-1" style="font-size:.7rem">
            {{ $item->type ?? 'custom' }}
        </span>
        <a href="#" class="show-item-details ms-auto text-muted">
            <i class="fas fa-chevron-down"></i>
        </a>
    </div>

    <div class="item-details d-none px-3 pb-3 border-top mt-0">
        <div class="row g-2 mt-1">
            <div class="col-12">
                <label class="form-label small mb-1">Titulo</label>
                <input type="text" name="title" class="form-control form-control-sm"
                       value="{{ $item->title }}" data-old="{{ $item->title }}">
            </div>

            @if(($item->type ?? 'custom') === 'custom')
                <div class="col-12">
                    <label class="form-label small mb-1">URL</label>
                    <input type="text" name="url" class="form-control form-control-sm"
                           value="{{ $item->url }}" data-old="{{ $item->url }}">
                </div>
            @endif

            <div class="col-6">
                <label class="form-label small mb-1">Target</label>
                <select name="target" class="form-select form-select-sm" data-old="{{ $item->target ?? '_self' }}">
                    <option value="_self" {{ ($item->target ?? '_self') === '_self' ? 'selected' : '' }}>Misma ventana</option>
                    <option value="_blank" {{ ($item->target ?? '') === '_blank' ? 'selected' : '' }}>Nueva ventana</option>
                </select>
            </div>

            <div class="col-6">
                <label class="form-label small mb-1">Icono</label>
                <input type="text" name="icon" class="form-control form-control-sm"
                       value="{{ $item->icon }}" data-old="{{ $item->icon }}" placeholder="fas fa-home">
            </div>

            <div class="col-12">
                <label class="form-label small mb-1">Clase CSS</label>
                <input type="text" name="css_class" class="form-control form-control-sm"
                       value="{{ $item->css_class }}" data-old="{{ $item->css_class }}">
            </div>

            <div class="col-12 d-flex gap-2 justify-content-end mt-1">
                <button type="button" class="btn btn-sm btn-outline-secondary btn-cancel">Cancelar</button>
                <button type="button" class="btn btn-sm btn-outline-danger btn-delete-item">
                    <i class="fas fa-trash-alt me-1"></i> Eliminar
                </button>
            </div>
        </div>
    </div>

    @if($item->has_child && $item->children->isNotEmpty())
        @include('template::partials.menu-tree', ['menu_nodes' => $item->children, 'menu' => $menu])
    @endif
</li>
