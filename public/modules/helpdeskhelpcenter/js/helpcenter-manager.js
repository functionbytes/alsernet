/**
 * Gestor de categorias/secciones/articulos del Centro de Ayuda
 * (helpcenter/index.blade.php). Extraido del <script> inline de esa vista.
 *
 * Depende de window.HelpcenterApi (sembrado por un bootstrap inline minimo
 * en la propia vista con las URLs de route() — lo unico que este fichero no
 * puede resolver por su cuenta) y de SortableJS (cargado antes vía CDN).
 */
(function () {
    'use strict';

    $(document).ready(function () {
        var api = window.HelpcenterApi || {};

        var HC_API = {
            categories: api.categories,
            categoriesCreate: api.categoriesCreate,
            categoriesReorder: api.categoriesReorder,
            categoryDelete: function (id) { return (api.categoryDeleteTpl || '').replace('__ID__', id); },
            sections: function (catId) { return (api.sectionsTpl || '').replace('__ID__', catId); },
            articles: function (secId) { return (api.articlesTpl || '').replace('__ID__', secId); },
            articlesCreate: api.articlesCreate,
            articlesReorder: function (secId) { return (api.articlesReorderTpl || '').replace('__ID__', secId); },
            articleDelete: function (id) { return (api.articleDeleteTpl || '').replace('__ID__', id); },
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
})();
