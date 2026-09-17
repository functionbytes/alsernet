/**
 * Bandeja v4 - Helpdesk conversations interactions (vista Kanban)
 * jQuery + SortableJS
 *
 * Extraido de resources/views/helpdesk/inbox/kanban.blade.php, donde vivia
 * inline. Pagina standalone (no forma parte del SPA principal del inbox:
 * sin pane AJAX, sin conversations-core/list/thread/panel/extras.js), asi
 * que este archivo no comparte estado con ellos.
 *
 * Convencion del modulo core: se sirve desde public/vendor/helpdesk/ y no
 * tiene copia fuente en resources/js/ (igual que conversations-core.js).
 */
(function () {
    'use strict';

    const csrfToken = $('meta[name="csrf-token"]').attr('content');

    // Init SortableJS on each column's card list
    document.querySelectorAll('.hd-kanban-cards').forEach(function (el) {
        Sortable.create(el, {
            group: 'kanban',
            animation: 150,
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            draggable: '.hd-kanban-card',
            onEnd: function (evt) {
                const card = evt.item;
                const toColumn = evt.to;
                const newStatusId = toColumn.dataset.statusId;
                const convId = card.dataset.convId;
                const updateUrl = card.dataset.updateUrl;

                if (!newStatusId || !convId || !updateUrl) return;

                // Optimistic: card already moved by SortableJS
                // Update column counts
                updateColumnCount(evt.from);
                updateColumnCount(evt.to);

                $.ajax({
                    url: updateUrl,
                    method: 'PUT',
                    contentType: 'application/json',
                    data: JSON.stringify({ status_id: parseInt(newStatusId) }),
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    success: function (resp) {
                    },
                    error: function (xhr) {
                        // Revert: move card back to original column
                        const fromColumn = evt.from;
                        const originalNext = evt.oldIndex < fromColumn.children.length
                            ? fromColumn.children[evt.oldIndex]
                            : null;

                        if (originalNext) {
                            fromColumn.insertBefore(card, originalNext);
                        } else {
                            fromColumn.appendChild(card);
                        }

                        updateColumnCount(evt.from);
                        updateColumnCount(evt.to);

                        const msg = xhr?.responseJSON?.message || 'No se pudo actualizar el estado';
                        if (window.toastr) {
                            toastr.error(msg);
                        }
                    },
                });
            },
        });
    });

    // Click on card → open conversation
    $(document).on('click', '.hd-kanban-card', function (e) {
        if ($(e.target).closest('.hd-kanban-card').length) {
            const url = $(this).data('show-url');
            if (url) window.location.href = url;
        }
    });

    function updateColumnCount(colEl) {
        const count = colEl.querySelectorAll('.hd-kanban-card').length;
        const col = colEl.closest('.hd-kanban-col');
        if (col) {
            const countEl = col.querySelector('.hd-kanban-col-count');
            if (countEl) countEl.textContent = count;
        }
    }
})();
