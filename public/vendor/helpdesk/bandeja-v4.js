/**
 * Bandeja v4 - Helpdesk conversations interactions
 * jQuery + Bootstrap 5.3 (sin React/Babel)
 *
 * Funcionalidad:
 * - Modales (open/close por data-bv-modal)
 * - Atajos de teclado (J/K navegación, ?/A/T/S/P/F/R/N/#)
 * - Click en items de lista → activar conversación
 * - Tabs del panel derecho
 * - Tabs del composer
 * - Channel filter pills
 * - Filter chips
 * - Búsqueda en lista
 */

(function ($) {
    'use strict';

    $(function () {
        // ─── Modales ─────────────────────────────────────────────────
        function openModal(name) {
            const $modal = $(`[data-bv-modal-name="${name}"]`);
            if ($modal.length) {
                $modal.addClass('on');
                $('body').css('overflow', 'hidden');
            }
        }

        function closeModal($modal) {
            $modal.removeClass('on');
            if ($('.bv-modal.on').length === 0) {
                $('body').css('overflow', '');
            }
        }

        // Abrir modal por click en data-bv-modal
        $(document).on('click', '[data-bv-modal]', function (e) {
            const name = $(this).data('bv-modal');
            if (name) {
                e.preventDefault();
                openModal(name);
            }
        });

        // Cerrar modal por click en data-bv-close o backdrop
        $(document).on('click', '[data-bv-close]', function () {
            closeModal($(this).closest('.bv-modal'));
        });

        $(document).on('click', '.bv-modal', function (e) {
            if (e.target === this) {
                closeModal($(this));
            }
        });

        // ESC cierra el modal abierto
        $(document).on('keydown', function (e) {
            if (e.key === 'Escape') {
                const $open = $('.bv-modal.on').last();
                if ($open.length) {
                    closeModal($open);
                }
            }
        });

        // ─── Tabs panel derecho ──────────────────────────────────────
        $(document).on('click', '.bv-right-tab', function () {
            const $tab = $(this);
            const target = $tab.data('bv-tab');
            $tab.siblings().removeClass('on');
            $tab.addClass('on');
            $('.bv-right-tab-content').hide();
            $(`[data-bv-tab-content="${target}"]`).show();
        });

        // ─── Tabs composer ───────────────────────────────────────────
        $(document).on('click', '.bv-composer-tab', function () {
            const $tab = $(this);
            const target = $tab.data('bv-tab');
            $tab.siblings().removeClass('on');
            $tab.addClass('on');
            $('#bv-composer-box').toggleClass('note', target === 'note');
        });

        // ─── Channel filter pills ────────────────────────────────────
        $(document).on('click', '.bv-chpill', function () {
            const $pill = $(this);
            $pill.siblings().removeClass('on');
            $pill.addClass('on');
            // TODO: filtrar la lista por canal vía AJAX
            const channel = $pill.data('bv-channel');
            console.log('Filter by channel:', channel);
        });

        // ─── Filter chips ────────────────────────────────────────────
        $(document).on('click', '.bv-chip[data-bv-filter]', function () {
            $(this).toggleClass('on');
            const filter = $(this).data('bv-filter');
            console.log('Toggle filter:', filter);
        });

        // ─── Click en item de la lista ───────────────────────────────
        $(document).on('click', '.bv-conv', function (e) {
            // Ignorar si el click fue en checkbox
            if ($(e.target).is('input[type="checkbox"]')) return;

            const $conv = $(this);
            $conv.siblings('.bv-conv').removeClass('on');
            $conv.addClass('on').removeClass('unread');

            const convId = $conv.data('bv-conv-id');
            console.log('Open conversation:', convId);
            // TODO: cargar el thread vía AJAX
        });

        // ─── Rail (vistas de la izquierda) ───────────────────────────
        $(document).on('click', '.bv-rail-btn', function () {
            $(this).siblings().removeClass('on');
            $(this).addClass('on');
            const view = $(this).data('bv-view');
            console.log('Switch view:', view);
        });

        // ─── Nav (vistas guardadas) ──────────────────────────────────
        $(document).on('click', '.bv-nav-item', function (e) {
            e.preventDefault();
            $(this).closest('.bv-nav-section').find('.bv-nav-item').removeClass('on');
            $(this).addClass('on');
        });

        // ─── Búsqueda en lista (live) ────────────────────────────────
        let searchTimeout = null;
        $('#bv-search-input').on('input', function () {
            const term = $(this).val().toLowerCase().trim();
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function () {
                $('.bv-conv').each(function () {
                    const name = $(this).find('.name').text().toLowerCase();
                    const preview = $(this).find('.preview').text().toLowerCase();
                    const match = !term || name.includes(term) || preview.includes(term);
                    $(this).toggle(match);
                });
            }, 200);
        });

        // ─── Status / Priority / Filter option clicks ───────────────
        $(document).on('click', '.bv-opt', function () {
            $(this).siblings().removeClass('on');
            $(this).addClass('on');
        });

        // ─── Atajos de teclado ───────────────────────────────────────
        $(document).on('keydown', function (e) {
            // No interferir si el usuario está escribiendo
            if ($(e.target).is('input, textarea, [contenteditable]')) return;
            // Ignorar si hay modificadores (excepto ⌘K)
            if (e.metaKey || e.ctrlKey) {
                if (e.key.toLowerCase() === 'k') {
                    e.preventDefault();
                    openModal('search-customer');
                }
                return;
            }
            const key = e.key.toLowerCase();
            switch (e.key) {
                case '?':
                    e.preventDefault();
                    openModal('shortcuts');
                    break;
                case '#':
                    e.preventDefault();
                    openModal('close-conv');
                    break;
                case 'ArrowDown':
                case 'j':
                    e.preventDefault();
                    navigateConv(1);
                    break;
                case 'ArrowUp':
                case 'k':
                    e.preventDefault();
                    navigateConv(-1);
                    break;
                default:
                    if (key === 'a') openModal('assign');
                    else if (key === 't') openModal('tags');
                    else if (key === 's') openModal('status');
                    else if (key === 'p') openModal('priority');
                    else if (key === 'f') openModal('filter');
                    else if (key === 'n') $('.bv-composer-tab[data-bv-tab="note"]').click();
                    else if (key === 'r') $('.bv-composer-tab[data-bv-tab="reply"]').click();
            }
        });

        function navigateConv(direction) {
            const $items = $('.bv-conv:visible');
            if ($items.length === 0) return;
            const $current = $items.filter('.on');
            let nextIndex;
            if ($current.length === 0) {
                nextIndex = 0;
            } else {
                const currentIndex = $items.index($current);
                nextIndex = Math.max(0, Math.min($items.length - 1, currentIndex + direction));
            }
            $items.eq(nextIndex).click();
        }

        // ─── Composer: enviar con ⌘Enter ──────────────────────────────
        $(document).on('keydown', '.bv-composer-input', function (e) {
            if ((e.metaKey || e.ctrlKey) && e.key === 'Enter') {
                e.preventDefault();
                $(this).closest('.bv-composer').find('.btn-send').click();
            }
        });

        // ─── Btn enviar ──────────────────────────────────────────────
        $(document).on('click', '.btn-send', function () {
            const $textarea = $(this).closest('.bv-composer').find('.bv-composer-input');
            const text = $textarea.val().trim();
            if (!text) return;
            console.log('Send message:', text);
            // TODO: AJAX send
            $textarea.val('').focus();
        });

        // ─── Auto-resize textarea ────────────────────────────────────
        $(document).on('input', '.bv-composer-input', function () {
            this.style.height = 'auto';
            this.style.height = Math.min(160, this.scrollHeight) + 'px';
        });

        // ─── Bulk select (checkbox) ──────────────────────────────────
        $(document).on('change', '[data-bv-bulk-select]', function () {
            $(this).closest('.bv-conv').toggleClass('selected', this.checked);
        });

        console.log('Bandeja v4 initialized');
    });

})(jQuery);
