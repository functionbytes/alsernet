<script>
(function ($) {
    'use strict';

    const CATEGORY_LABELS = {
        sections: 'Secciones',
        cards:    'Cards',
        content:  'Contenido',
        forms:    'Formularios',
        custom:   'Mis bloques',
    };

    /* ── Custom blocks (localStorage) ───────────────────────────────── */
    function getCustomBlocks() {
        try { return JSON.parse(localStorage.getItem('ve-custom-blocks') || '[]'); }
        catch (e) { return []; }
    }

    function deleteCustomBlock(id) {
        const blocks = getCustomBlocks().filter(function (b) { return b.id !== id; });
        localStorage.setItem('ve-custom-blocks', JSON.stringify(blocks));
        renderBlocks($('#ve-block-search').val());
    }

    /* ── Build a single block card element ───────────────────────────── */
    function buildBlockItem(block) {
        const iconClass = block.isCustom ? 've-block-icon ve-block-icon-custom' : 've-block-icon';
        const $item = $('<div>')
            .addClass('ve-block-item' + (block.isCustom ? ' ve-block-item-custom' : ''))
            .attr({ draggable: 'true', title: block.name });

        $item.html(
            '<div class="' + iconClass + '">' + block.icon + '</div>' +
            '<div class="ve-block-name">' + escapeHtml(block.name) + '</div>' +
            (block.isCustom
                ? '<button class="ve-delete-custom-block btn btn-xs btn-outline-danger" data-id="' + escapeHtml(block.id) + '" title="Eliminar"><i class="fa-duotone fa-solid fa-times"></i></button>'
                : ''
            )
        );

        $item[0].addEventListener('dragstart', function (e) {
            $item.addClass('dragging');
            e.dataTransfer.effectAllowed = 'copy';
            e.dataTransfer.setData('text/plain', block.name);
            if (window.veStartBlockDrag) window.veStartBlockDrag(block.html);
        });
        $item[0].addEventListener('dragend', function () { $item.removeClass('dragging'); });

        $item.on('click', function (e) {
            if ($(e.target).closest('.ve-delete-custom-block').length) return;
            insertBlock(block.html, block.name);
        });

        return $item;
    }

    /* ── Render blocks grouped by category ───────────────────────────── */
    function renderBlocks(filter) {
        const $accordion = $('#ve-blocks-accordion');
        $accordion.empty();

        const query  = (filter || '').toLowerCase();
        const custom = getCustomBlocks();
        const allBlocks = (window.veBlocks || []).concat(
            custom.map(function (b) {
                return { id: b.id, name: b.label, category: b.category || 'custom', html: b.html, icon: '<i class="fa-duotone fa-solid fa-bookmark"></i>', isCustom: true };
            })
        );

        const filtered = query
            ? allBlocks.filter(function (b) { return b.name.toLowerCase().includes(query); })
            : allBlocks;

        if (filtered.length === 0) {
            $accordion.html('<p class="text-muted text-center py-4" style="font-size:12px;">No se encontraron bloques</p>');
            return;
        }

        // Group blocks by category (preserving natural order)
        const groups = {};
        const groupOrder = [];
        filtered.forEach(function (block) {
            const cat = block.category || 'content';
            if (!groups[cat]) { groups[cat] = []; groupOrder.push(cat); }
            groups[cat].push(block);
        });

        groupOrder.forEach(function (cat, idx) {
            const label  = CATEGORY_LABELS[cat] || cat;
            const catId  = 've-cat-' + cat;
            const isFirst = idx === 0;
            const $group = $('<div class="ve-blocks-category">');

            const $header = $('<button type="button">')
                .addClass('ve-category-header' + (isFirst ? '' : ' collapsed'))
                .attr({
                    'data-bs-toggle':  'collapse',
                    'data-bs-target':  '#' + catId,
                    'data-bs-parent':  '#ve-blocks-accordion',
                    'aria-expanded':   isFirst ? 'true' : 'false',
                    'aria-controls':   catId,
                })
                .html(escapeHtml(label) + '<i class="fa-duotone fa-solid fa-chevron-down ve-cat-chevron"></i>');

            const $collapseDiv = $('<div>').addClass('collapse' + (isFirst ? ' show' : '')).attr({ 'id': catId, 'data-bs-parent': '#ve-blocks-accordion' });
            const $grid = $('<div class="ve-blocks-grid">');

            groups[cat].forEach(function (block) {
                $grid.append(buildBlockItem(block));
            });

            $collapseDiv.append($grid);
            $group.append($header, $collapseDiv);
            $accordion.append($group);
        });
    }

    // Delete custom block handler
    $(document).on('click', '.ve-delete-custom-block', function (e) {
        e.stopPropagation();
        const id = $(this).data('id');
        window.veConfirm('Se eliminará el bloque personalizado de tu biblioteca.', function () {
            deleteCustomBlock(id);
        });
    });

    /* ── Insert block into CKEditor ──────────────────────────────────── */
    function insertBlock(html, label) {
        if (!window.veEditor) {
            setTimeout(function () { insertBlock(html, label); }, 400);
            return;
        }

        window.veEditor.model.change(function () {
            const viewFragment  = window.veEditor.data.processor.toView(html);
            const modelFragment = window.veEditor.data.toModel(viewFragment);
            window.veEditor.model.insertContent(modelFragment);
        });

        if (window.vePushHistory) {
            window.vePushHistory('Insertar: ' + (label || 'bloque'), window.veEditor.getData());
        }

        showFeedback('Bloque insertado');
    }

    function showFeedback(msg) {
        const $f = $('<div>')
            .addClass('alert alert-success py-1 px-2 mx-2 mb-2')
            .css('font-size', '11px')
            .html('<i class="fa-duotone fa-solid fa-check me-1"></i>' + escapeHtml(msg));
        $('#ve-blocks-accordion').before($f);
        setTimeout(function () { $f.fadeOut(300, function () { $f.remove(); }); }, 1800);
    }

    function escapeHtml(str) {
        return $('<span>').text(str).html();
    }

    /* ── Search with debounce ─────────────────────────────────────────── */
    let searchTimer;
    $('#ve-block-search').on('input', function () {
        clearTimeout(searchTimer);
        const q = $(this).val();
        searchTimer = setTimeout(function () { renderBlocks(q); }, 200);
    });

    /* ── Expose render globally (used by save-as-block) ──────────────── */
    window.veRenderBlocks = function () { renderBlocks($('#ve-block-search').val()); };

    /* ── Initial render ───────────────────────────────────────────────── */
    renderBlocks('');

})(jQuery);
</script>
