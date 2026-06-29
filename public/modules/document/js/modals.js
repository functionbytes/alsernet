/**
 * Document Module – Modales reutilizables
 *
 * Patron jQuery + delegacion de eventos. Cada modal renderizado tiene un
 * id unico que se pasa como argumento a window.DocsModal.open(id) o se
 * abre via [data-docs-modal-trigger="<id>"].
 *
 * Eventos:
 *   - click .docs-close, .docs-backdrop, [data-docs-modal-close] → cierra
 *   - tecla Escape mientras hay un modal abierto → cierra el ultimo
 */
(function ($) {
    'use strict';

    if (!window.jQuery) {
        return;
    }

    window.DocsModal = window.DocsModal || {
        open: function (id) {
            var $modal = $('#' + id);
            var $backdrop = $('[data-docs-modal-backdrop-for="' + id + '"]');
            $backdrop.addClass('open');
            $modal.addClass('open');
            document.body.classList.add('docs-modal-open');
        },
        close: function (id) {
            var $modal = $('#' + id);
            var $backdrop = $('[data-docs-modal-backdrop-for="' + id + '"]');
            $modal.removeClass('open');
            $backdrop.removeClass('open');
            if ($('.docs-modal.open').length === 0) {
                document.body.classList.remove('docs-modal-open');
            }
        },
        closeAll: function () {
            $('.docs-modal.open').each(function () {
                window.DocsModal.close(this.id);
            });
        }
    };

    $(document).on('click', '[data-docs-modal-trigger]', function (e) {
        e.preventDefault();
        var id = $(this).data('docs-modal-trigger');
        window.DocsModal.open(id);
    });

    $(document).on('click', '.docs-close, [data-docs-modal-close]', function (e) {
        e.preventDefault();
        var $modal = $(this).closest('.docs-modal');
        if ($modal.length) {
            window.DocsModal.close($modal.attr('id'));
        }
    });

    $(document).on('click', '.docs-backdrop.open', function (e) {
        if (e.target !== this) return;
        var forId = $(this).data('docs-modal-backdrop-for');
        if (!forId) return;
        var modal = document.getElementById(forId);
        if (modal && modal.hasAttribute('data-docs-persistent')) return;
        window.DocsModal.close(forId);
    });

    $(document).on('keydown', function (e) {
        if (e.key !== 'Escape') return;
        var $open = $('.docs-modal.open').last();
        if (!$open.length) return;
        if ($open[0].hasAttribute('data-docs-persistent')) return;
        window.DocsModal.close($open.attr('id'));
    });

    // ── Filter pills (Visualizar documentacion) ─────────────
    $(document).on('click', '.docs-pill', function (e) {
        e.preventDefault();
        var $pill = $(this);
        var filter = $pill.data('filter');
        var $root = $pill.closest('.docs-modal');

        $pill.siblings('.docs-pill').removeClass('on');
        $pill.addClass('on');

        $root.find('.docs-card').each(function () {
            var status = $(this).data('status');
            if (!filter || filter === 'all' || status === filter) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });

    // ── Chat gallery picker (toggle + counter) ──────────────
    $(document).on('click', '.docs-cg-pick', function (e) {
        e.preventDefault();
        var $pick = $(this);
        $pick.toggleClass('on');

        var $modal = $pick.closest('.docs-modal');
        var count = $modal.find('.docs-cg-pick.on').length;
        var $btn = $modal.find('[data-docs-cg-import-btn]');
        if ($btn.length) {
            if (count === 0) {
                $btn.prop('disabled', true).text($btn.data('empty-label') || 'Selecciona archivos');
            } else {
                var template = $btn.data('label-template') || 'Importar {n} archivos';
                $btn.prop('disabled', false).text(template.replace('{n}', count));
            }
        }
    });

}(window.jQuery));
