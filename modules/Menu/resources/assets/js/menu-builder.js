/**
 * Menu Builder with Alpine.js and SortableJS
 *
 * This file contains the Alpine.js components for the menu builder interface.
 * It handles drag & drop functionality, menu item management, and AJAX operations.
 */

// Menu Item Form Component
window.menuItemForm = function() {
    return {
        type: 'custom',
        title: '',
        url: '',
        icon: '',
        target: '_self',
        cssClass: '',
        referenceId: '',
        referenceType: '',

        init() {
            // Watch for type changes to auto-fill title from reference
            this.$watch('referenceId', (value) => {
                if (value && this.type !== 'custom') {
                    const select = this.$el.querySelector(`select[x-model="referenceId"]`);
                    if (select) {
                        const option = select.querySelector(`option[value="${value}"]`);
                        if (option) {
                            this.title = option.dataset.title || option.textContent;
                        }
                    }
                }
            });
        },

        async addItem() {
            if (!this.validate()) {
                return;
            }

            const data = {
                type: this.type,
                title: this.title,
                url: this.url,
                icon: this.icon,
                target: this.target,
                css_class: this.cssClass,
                reference_id: this.referenceId || null,
                reference_type: this.getReferenceType()
            };

            try {
                const response = await fetch(this.getAddItemUrl(), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.getCsrfToken()
                    },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.success) {
                    this.showNotification('Menu item added successfully!', 'success');
                    setTimeout(() => location.reload(), 500);
                } else {
                    this.showNotification('Failed to add menu item.', 'error');
                }
            } catch (error) {
                console.error('Error adding menu item:', error);
                this.showNotification('An error occurred.', 'error');
            }
        },

        validate() {
            if (!this.title && this.type === 'custom') {
                this.showNotification('Title is required.', 'error');
                return false;
            }

            if (!this.url && this.type === 'custom') {
                this.showNotification('URL is required.', 'error');
                return false;
            }

            if (!this.referenceId && this.type !== 'custom' && this.type !== 'route') {
                this.showNotification('Please select an item.', 'error');
                return false;
            }

            return true;
        },

        getReferenceType() {
            // This should match the reference types from your backend
            const types = {
                'page': window.menuReferences?.page_class || '',
                'post': window.menuReferences?.post_class || '',
                'category': window.menuReferences?.category_class || ''
            };
            return types[this.type] || null;
        },

        getAddItemUrl() {
            return window.menuBuilder?.addItemUrl || '';
        },

        getCsrfToken() {
            return document.querySelector('meta[name="csrf-token"]')?.content || '';
        },

        showNotification(message, type = 'info') {
            // Simple notification - can be replaced with your notification system
            alert(message);
        }
    }
};

// Menu Builder Component
window.menuBuilder = function(initialItems) {
    return {
        items: initialItems || [],
        sortableInstances: [],

        init() {
            this.$nextTick(() => {
                this.initSortable();
            });
        },

        initSortable() {
            const containers = document.querySelectorAll('.menu-items-container');

            containers.forEach(container => {
                if (!container.classList.contains('sortable-initialized')) {
                    const sortable = Sortable.create(container, {
                        animation: 150,
                        handle: '.drag-handle',
                        group: 'nested',
                        fallbackOnBody: true,
                        swapThreshold: 0.65,
                        ghostClass: 'sortable-ghost',
                        dragClass: 'sortable-drag',
                        chosenClass: 'sortable-chosen',

                        onEnd: (evt) => {
                            this.updateOrder();
                        }
                    });

                    this.sortableInstances.push(sortable);
                    container.classList.add('sortable-initialized');
                }
            });
        },

        renderItem(item, level = 0) {
            const indent = level * 20;
            const hasChildren = item.children && item.children.length > 0;

            let html = `
                <div class="menu-item border rounded p-3 bg-gray-50 mb-2" style="margin-left: ${indent}px" data-id="${item.id}">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3 flex-1">
                            <span class="drag-handle cursor-move text-gray-400 hover:text-gray-600">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"></path>
                                </svg>
                            </span>
                            <div class="flex-1">
                                <div class="font-medium text-gray-900">${this.escapeHtml(item.title)}</div>
                                <div class="text-sm text-gray-500">${this.escapeHtml(item.url || item.full_url || '')}</div>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2">
                            ${item.icon ? `<i class="${this.escapeHtml(item.icon)} text-gray-400"></i>` : ''}
                            <button
                                @click="editItem(${item.id})"
                                class="text-blue-500 hover:text-blue-700 p-1"
                                title="Edit"
                            >
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"></path>
                                </svg>
                            </button>
                            <button
                                @click="deleteItem(${item.id})"
                                class="text-red-500 hover:text-red-700 p-1"
                                title="Delete"
                            >
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            `;

            if (hasChildren) {
                html += '<div class="menu-items-container pl-4">';
                item.children.forEach(child => {
                    html += this.renderItem(child, level + 1);
                });
                html += '</div>';
            }

            return html;
        },

        escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text ? text.replace(/[&<>"']/g, m => map[m]) : '';
        },

        updateOrder() {
            // Order is handled when saving structure
            console.log('Order updated');
        },

        async saveStructure() {
            const structure = this.getStructure();

            try {
                const response = await fetch(window.menuBuilder?.saveStructureUrl || '', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.getCsrfToken()
                    },
                    body: JSON.stringify({ items: structure })
                });

                const result = await response.json();

                if (result.success) {
                    this.showNotification('Menu structure saved successfully!', 'success');
                } else {
                    this.showNotification('Failed to save menu structure.', 'error');
                }
            } catch (error) {
                console.error('Error saving structure:', error);
                this.showNotification('An error occurred while saving.', 'error');
            }
        },

        getStructure() {
            const items = [];
            const containers = document.querySelectorAll('.menu-items-container');

            containers.forEach(container => {
                const menuItems = container.querySelectorAll(':scope > .menu-item');

                menuItems.forEach((element, index) => {
                    const id = element.dataset.id;
                    const children = this.getChildStructure(element);

                    items.push({
                        id: parseInt(id),
                        order: index,
                        children: children
                    });
                });
            });

            return items;
        },

        getChildStructure(parentElement) {
            const children = [];
            const childContainer = parentElement.querySelector(':scope > .menu-items-container');

            if (childContainer) {
                const childItems = childContainer.querySelectorAll(':scope > .menu-item');

                childItems.forEach((element, index) => {
                    const id = element.dataset.id;
                    const grandchildren = this.getChildStructure(element);

                    children.push({
                        id: parseInt(id),
                        order: index,
                        children: grandchildren
                    });
                });
            }

            return children;
        },

        async deleteItem(itemId) {
            if (!confirm('Are you sure you want to delete this item? This will also delete all child items.')) {
                return;
            }

            try {
                const url = (window.menuBuilder?.deleteItemUrl || '').replace('__ITEM__', itemId);

                const response = await fetch(url, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': this.getCsrfToken()
                    }
                });

                const result = await response.json();

                if (result.success) {
                    this.showNotification('Menu item deleted successfully!', 'success');
                    setTimeout(() => location.reload(), 500);
                } else {
                    this.showNotification('Failed to delete menu item.', 'error');
                }
            } catch (error) {
                console.error('Error deleting item:', error);
                this.showNotification('An error occurred.', 'error');
            }
        },

        editItem(itemId) {
            // Implement edit functionality
            console.log('Edit item:', itemId);
            // You can show a modal or redirect to edit page
        },

        getCsrfToken() {
            return document.querySelector('meta[name="csrf-token"]')?.content || '';
        },

        showNotification(message, type = 'info') {
            // Simple notification - can be replaced with your notification system
            alert(message);
        }
    }
};
