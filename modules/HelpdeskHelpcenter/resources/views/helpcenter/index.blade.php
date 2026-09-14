@extends('layouts.theme')

@section('title', 'Centro de ayuda — Organizar')

@section('page_header')
    @include('core::components.card', ['title' => 'Centro de ayuda — Organización'])
@endsection

@section('content')
    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        {{-- Breadcrumb Navigation --}}
        <div class="card mb-3">
            <div class="card-body p-3">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0" id="breadcrumb">
                        <li class="breadcrumb-item">
                            <a href="#" class="text-decoration-none" data-level="categories">
                                <i class="far fa-home me-1"></i> Categorías
                            </a>
                        </li>
                    </ol>
                </nav>
            </div>
        </div>

        {{-- Main Content Card --}}
        <div class="card">
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold" id="content-title">
                            <i class="far fa-folder text-muted me-2"></i>
                            <span id="title-text">Categorías</span>
                            <span id="title-count" class="badge bg-primary-subtle text-primary ms-2">0</span>
                        </h5>
                        <p class="small mb-0 text-muted">Arrastra los elementos para reordenarlos</p>
                    </div>
                    <button type="button" class="btn btn-primary btn-sm" id="addItemBtn">
                        <i class="fas fa-plus me-1"></i>
                        <span id="addBtnText">Nueva categoría</span>
                    </button>
                </div>
            </div>

            <div class="card-body p-4">
                <!-- Loading State -->
                <div id="loading" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p class="text-muted mt-2">Cargando contenido...</p>
                </div>

                {{-- Empty State --}}
                <div id="empty-state" class="text-center py-5 d-none">
                    <div class="d-flex flex-column align-items-center">
                        <div class="round-48 rounded-circle bg-light-subtle text-muted mb-3 d-flex align-items-center justify-content-center">
                            <i class="far fa-folder-open fs-7"></i>
                        </div>
                        <h6 class="mb-1" id="empty-title">No hay categorías</h6>
                        <p class="text-muted mb-3" id="empty-description">
                            Crea tu primera categoría para comenzar
                        </p>
                        <button type="button" class="btn btn-sm btn-primary" id="addItemBtnEmpty">
                            <i class="fas fa-plus me-1"></i>
                            <span id="addBtnEmptyText">Nueva categoría</span>
                        </button>
                    </div>
                </div>

                <!-- Items List -->
                <div id="items-container" class="d-none">
                    <div id="sortable-list" class="list-group"></div>
                </div>
            </div>
        </div>
    </div>

<!-- Create/Edit Modal -->
<div class="modal fade" id="itemModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Nueva Categoría</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="itemForm">
                <div class="modal-body">
                    <input type="hidden" id="itemId">
                    <input type="hidden" id="parentId">
                    <input type="hidden" id="isSection">

                    <div class="mb-3">
                        <label for="itemName" class="form-label fw-semibold">Nombre *</label>
                        <input type="text" class="form-control" id="itemName" required>
                    </div>

                    <div class="mb-3">
                        <label for="itemDescription" class="form-label fw-semibold">Descripción</label>
                        <textarea class="form-control" id="itemDescription" rows="3"></textarea>
                    </div>

                    <div class="mb-3" id="imageField">
                        <label for="itemImage" class="form-label fw-semibold">URL de Imagen</label>
                        <input type="text" class="form-control" id="itemImage" placeholder="https://...">
                        <small class="text-muted">URL de la imagen para la categoría</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-check me-1"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
.hc-item {
    cursor: pointer;
    transition: all 0.2s ease;
    border-left: 3px solid transparent !important;
}

.hc-item:hover {
    border-left-color: var(--bs-primary) !important;
    background-color: var(--bs-primary-bg-subtle);
    transform: translateX(2px);
}

.hc-item.sortable-ghost {
    opacity: 0.4;
    background-color: var(--bs-light);
}

.hc-item.sortable-drag {
    opacity: 1;
    background-color: white;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.hc-item-icon {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: var(--bs-primary-bg-subtle);
    color: var(--bs-primary);
    border-radius: 8px;
    font-size: 1.2rem;
}

.hc-item-actions {
    opacity: 0;
    transition: opacity 0.2s ease;
}

.hc-item:hover .hc-item-actions {
    opacity: 1;
}

.drag-handle {
    cursor: grab;
    color: var(--bs-secondary);
}

.drag-handle:active {
    cursor: grabbing;
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
$(document).ready(function() {
    const HC_API = {
        categories:        '{{ route("manager.helpcenter.api.categories") }}',
        categoriesCreate:  '{{ route("manager.helpcenter.api.categories.create") }}',
        categoriesReorder: '{{ route("manager.helpcenter.api.categories.reorder") }}',
        categoryDelete:    function(id) { return '{{ route("manager.helpcenter.api.categories.delete", ["id" => "__ID__"]) }}'.replace('__ID__', id); },
        sections:          function(catId) { return '{{ route("manager.helpcenter.api.sections", ["id" => "__ID__"]) }}'.replace('__ID__', catId); },
        articles:          function(secId) { return '{{ route("manager.helpcenter.api.articles", ["id" => "__ID__"]) }}'.replace('__ID__', secId); },
        articlesCreate:    '{{ route("manager.helpcenter.api.articles.create") }}',
        articlesReorder:   function(secId) { return '{{ route("manager.helpcenter.api.articles.reorder", ["id" => "__ID__"]) }}'.replace('__ID__', secId); },
        articleDelete:     function(id) { return '{{ route("manager.helpcenter.api.articles.delete", ["id" => "__ID__"]) }}'.replace('__ID__', id); },
    };

    let currentLevel = 'categories'; // categories, sections, articles
    let currentParentId = null;
    let currentCategoryData = null;
    let sortableInstance = null;

    // Initialize
    loadContent();

    // Add Item Buttons
    $('#addItemBtn, #addItemBtnEmpty').on('click', function() {
        openCreateModal();
    });

    // Item Form Submit
    $('#itemForm').on('submit', function(e) {
        e.preventDefault();
        saveItem();
    });

    // Breadcrumb Navigation
    $(document).on('click', '.breadcrumb-item a', function(e) {
        e.preventDefault();
        const level = $(this).data('level');
        const parentId = $(this).data('parent-id');
        navigateToLevel(level, parentId);
    });

    // Load Content
    function loadContent() {
        $('#loading').show();
        $('#empty-state').hide();
        $('#items-container').hide();

        let url = HC_API.categories;

        if (currentLevel === 'sections' && currentParentId) {
            url = HC_API.sections(currentParentId);
        } else if (currentLevel === 'articles' && currentParentId) {
            url = HC_API.articles(currentParentId);
        }

        $.ajax({
            url: url,
            method: 'GET',
            success: function(data) {
                displayContent(data);
            },
            error: function(xhr) {
                toastr.error('Error al cargar el contenido');
                $('#loading').hide();
            }
        });
    }

    // Display Content
    function displayContent(data) {
        $('#loading').hide();

        if (currentLevel === 'articles') {
            displayArticles(data);
        } else {
            displayCategories(data);
        }
    }

    // Display Categories or Sections
    function displayCategories(data) {
        const items = data.categories || [];
        $('#title-count').text(items.length);

        if (items.length === 0) {
            $('#empty-state').show();
            return;
        }

        $('#items-container').show();
        const $list = $('#sortable-list').empty();

        items.forEach(item => {
            const $item = createCategoryItem(item);
            $list.append($item);
        });

        initializeSortable();
    }

    // Create Category/Section Item
    function createCategoryItem(item) {
        const icon = item.is_section ? 'fa-list' : 'fa-folder';
        const itemType = item.is_section ? 'section' : 'category';
        const countText = item.is_section
            ? `${item.articles_count} artículos`
            : `${item.sections_count} secciones, ${item.articles_count} artículos`;

        return $(`
            <div class="hc-item list-group-item border mb-2 rounded p-3" data-id="${item.id}">
                <div class="d-flex align-items-center gap-3">
                    <div class="drag-handle">
                        <i class="fa fa-grip-vertical"></i>
                    </div>
                    <div class="hc-item-icon">
                        <i class="fa ${icon}"></i>
                    </div>
                    <div class="flex-grow-1" data-action="navigate">
                        <h6 class="mb-0 fw-semibold">${escapeHtml(item.name)}</h6>
                        ${item.description ? `<p class="text-muted mb-0 mt-1">${escapeHtml(item.description)}</p>` : ''}
                        <small class="text-muted">${countText}</small>
                    </div>
                    <div class="hc-item-actions d-flex gap-2">
                        <button class="btn btn-sm btn-outline-primary" data-action="edit" title="Editar">
                            <i class="fa fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" data-action="delete" title="Eliminar">
                            <i class="fa fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        `);
    }

    // Display Articles
    function displayArticles(data) {
        const items = data.articles || [];
        $('#title-count').text(items.length);

        if (items.length === 0) {
            $('#empty-state').show();
            $('#empty-title').text('Esta sección está vacía');
            $('#empty-description').text('Las secciones vacías no son visibles en el Centro de Ayuda');
            return;
        }

        $('#items-container').show();
        const $list = $('#sortable-list').empty();

        items.forEach(item => {
            const $item = createArticleItem(item);
            $list.append($item);
        });

        initializeSortable();
    }

    // Create Article Item
    function createArticleItem(item) {
        const statusBadge = item.draft
            ? '<span class="badge bg-warning-subtle text-warning">Borrador</span>'
            : '<span class="badge bg-success-subtle text-success">Publicado</span>';

        return $(`
            <div class="hc-item list-group-item border mb-2 rounded p-3" data-id="${item.id}">
                <div class="d-flex align-items-center gap-3">
                    <div class="drag-handle">
                        <i class="fa fa-grip-vertical"></i>
                    </div>
                    <div class="hc-item-icon">
                        <i class="fa fa-file-alt"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="mb-0 fw-semibold">${escapeHtml(item.title)}</h6>
                        <div class="mt-1">
                            ${statusBadge}
                            <small class="text-muted ms-2">${item.views} vistas</small>
                        </div>
                    </div>
                    <div class="hc-item-actions d-flex gap-2">
                        <button class="btn btn-sm btn-outline-primary" data-action="edit" title="Editar">
                            <i class="fa fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" data-action="delete" title="Eliminar">
                            <i class="fa fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        `);
    }

    // Initialize Sortable
    function initializeSortable() {
        if (sortableInstance) {
            sortableInstance.destroy();
        }

        const el = document.getElementById('sortable-list');
        sortableInstance = Sortable.create(el, {
            animation: 150,
            handle: '.drag-handle',
            ghostClass: 'sortable-ghost',
            dragClass: 'sortable-drag',
            onEnd: function(evt) {
                handleReorder();
            }
        });
    }

    // Handle Item Click
    $(document).on('click', '.hc-item', function(e) {
        const action = $(e.target).closest('[data-action]').data('action');
        const itemId = $(this).data('id');

        if (action === 'navigate' || !action) {
            handleNavigate(itemId);
        } else if (action === 'edit') {
            e.stopPropagation();
            handleEdit(itemId);
        } else if (action === 'delete') {
            e.stopPropagation();
            handleDelete(itemId);
        }
    });

    // Navigate to next level
    function handleNavigate(itemId) {
        if (currentLevel === 'categories') {
            // Navigate to sections
            navigateToLevel('sections', itemId);
        } else if (currentLevel === 'sections') {
            // Navigate to articles
            navigateToLevel('articles', itemId);
        }
    }

    // Navigate to Level
    function navigateToLevel(level, parentId = null) {
        currentLevel = level;
        currentParentId = parentId;

        updateUI();
        loadContent();
    }

    // Update UI based on current level
    function updateUI() {
        if (currentLevel === 'categories') {
            $('#title-text').text('Categorías');
            $('#addBtnText, #addBtnEmptyText').text('Nueva Categoría');
            $('#breadcrumb').html(`
                <li class="breadcrumb-item active">
                    <i class="fa fa-home me-1"></i> Categorías
                </li>
            `);
        } else if (currentLevel === 'sections') {
            $('#title-text').text('Secciones');
            $('#addBtnText, #addBtnEmptyText').text('Nueva Sección');
            // Update breadcrumb with category name (would need to fetch category data)
        } else if (currentLevel === 'articles') {
            $('#title-text').text('Artículos');
            $('#addBtnText, #addBtnEmptyText').text('Nuevo Artículo');
        }
    }

    // Handle Reorder
    function handleReorder() {
        const ids = [];
        $('#sortable-list .hc-item').each(function() {
            ids.push($(this).data('id'));
        });

        let url, data;

        if (currentLevel === 'articles') {
            url = HC_API.articlesReorder(currentParentId);
            data = { ids: ids };
        } else {
            url = HC_API.categoriesReorder;
            data = { ids: ids, parentId: currentParentId };
        }

        $.ajax({
            url: url,
            method: 'POST',
            data: data,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function() {
            },
            error: function() {
                toastr.error('Error al actualizar el orden');
                loadContent();
            }
        });
    }

    // Open Create Modal
    function openCreateModal() {
        $('#itemId').val('');
        $('#itemName').val('');
        $('#itemDescription').val('');
        $('#itemImage').val('');
        $('#parentId').val(currentParentId || '');
        $('#isSection').val(currentLevel === 'sections' ? '1' : '0');

        if (currentLevel === 'categories') {
            $('#modalTitle').text('Nueva Categoría');
            $('#imageField').show();
        } else if (currentLevel === 'sections') {
            $('#modalTitle').text('Nueva Sección');
            $('#imageField').show();
        } else {
            $('#modalTitle').text('Nuevo Artículo');
            $('#imageField').hide();
        }

        $('#itemModal').modal('show');
    }

    // Handle Edit
    function handleEdit(itemId) {
        // For now, just open modal with edit mode
        // In a full implementation, you'd fetch the item data first
    }

    // Handle Delete
    function handleDelete(itemId) {
        window.__confirm('¿Estás seguro de que deseas eliminar este elemento?', function () {
            const url = currentLevel === 'articles'
                ? HC_API.articleDelete(itemId)
                : HC_API.categoryDelete(itemId);

            $.ajax({
                url: url,
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function() {
                    loadContent();
                },
                error: function(xhr) {
                    const message = xhr.responseJSON?.error || 'Error al eliminar el elemento';
                    toastr.error(message);
                }
            });
        });
    }

    // Save Item
    function saveItem() {
        const data = {
            name: $('#itemName').val(),
            description: $('#itemDescription').val(),
            image: $('#itemImage').val(),
            parent_id: $('#parentId').val() || null,
            is_section: $('#isSection').val() === '1'
        };

        const url = currentLevel === 'articles'
            ? HC_API.articlesCreate
            : HC_API.categoriesCreate;

        if (currentLevel === 'articles') {
            data.title = data.name;
            data.category_id = currentParentId;
            delete data.name;
            delete data.is_section;
        }

        $.ajax({
            url: url,
            method: 'POST',
            data: data,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function() {
                $('#itemModal').modal('hide');
                loadContent();
            },
            error: function(xhr) {
                const errors = xhr.responseJSON?.errors;
                if (errors) {
                    Object.values(errors).forEach(error => {
                        toastr.error(error[0]);
                    });
                } else {
                    toastr.error('Error al guardar el elemento');
                }
            }
        });
    }

    // Utility function to escape HTML
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});
</script>
@endpush
