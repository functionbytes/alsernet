/*!
 * Helpdesk · modal "tags" del inbox.
 *
 * Extraido de resources/views/helpdesk/inbox/partials/modals/tags.blade.php.
 * La config que antes se interpolaba con Blade (rutas, flags, datos de sesion,
 * textos traducidos) viaja ahora por atributos data-* del markup, que es lo que
 * permite servir este JS como fichero estatico cacheable.
 */
(function () {
    var preAppliedIds = new Set();

    function modal()    { return $('[data-bv-modal-name="tags"]'); }
    function applied()  { return $('#tags-applied'); }
    function tagsList() { return $('#tags-list'); }

    /* ── Sincroniza estado al abrir ──────────────────────────── */
    (new MutationObserver(function (mutations) {
        mutations.forEach(function (m) {
            if (m.attributeName !== 'class') { return; }
            var $modal = $(m.target);
            if (!$modal.hasClass('on')) { return; }

            preAppliedIds = new Set();
            tagsList().find('.bv-rtag[data-tag-id]').removeClass('bv-rtag--on');

            applied().find('.bv-rtag').each(function () {
                var id = String($(this).data('tag-id'));
                preAppliedIds.add(id);
                tagsList().find('.bv-rtag[data-tag-id="' + id + '"]').addClass('bv-rtag--on');
            });

            $('#tags-search').val('').trigger('input');
        });
    })).observe(document.querySelector('[data-bv-modal-name="tags"]'), { attributes: true });

    /* ── Añadir chip a "Aplicadas" ───────────────────────────── */
    function addChip(tagId, tagName) {
        if (applied().find('.bv-rtag[data-tag-id="' + tagId + '"]').length) { return; }
        applied().find('#tags-applied-empty, em').remove();
        applied().append(
            '<span class="bv-rtag bv-rtag--on" data-tag-id="' + tagId + '">' +
            $('<span>').text(tagName).html() +
            '<i class="fas fa-xmark bv-rtag-x tags-remove-chip"></i></span>'
        );
    }

    /* ── Quitar chip de "Aplicadas" ──────────────────────────── */
    function removeChip(tagId) {
        applied().find('.bv-rtag[data-tag-id="' + tagId + '"]').remove();
        if (!applied().find('.bv-rtag').length) {
            applied().append('<em class="bv-tags-empty" id="tags-applied-empty">Ninguna aplicada</em>');
        }
    }

    /* ── Click en chip de "Disponibles" ──────────────────────── */
    modal().on('click', '#tags-list .bv-rtag[data-tag-id]', function () {
        var $chip   = $(this);
        var tagId   = String($chip.data('tag-id'));
        var tagName = $chip.text().trim();

        if ($chip.hasClass('bv-rtag--on')) {
            $chip.removeClass('bv-rtag--on');
            removeChip(tagId);
        } else {
            $chip.addClass('bv-rtag--on');
            addChip(tagId, tagName);
        }
    });

    /* ── Click en X de chip "Aplicadas" ─────────────────────── */
    $(document).on('click', '.tags-remove-chip', function (e) {
        e.stopPropagation();
        var $chip = $(this).closest('.bv-rtag');
        var tagId = String($chip.data('tag-id'));
        $chip.remove();
        if (!applied().find('.bv-rtag').length) {
            applied().append('<em class="bv-tags-empty" id="tags-applied-empty">Ninguna aplicada</em>');
        }
        tagsList().find('.bv-rtag[data-tag-id="' + tagId + '"]').removeClass('bv-rtag--on');
    });

    /* ── Búsqueda / filtrado ─────────────────────────────────── */
    $(document).on('input', '#tags-search', function () {
        var q    = $(this).val().trim();
        var qLow = q.toLowerCase();
        var hasExact = false;

        tagsList().find('.bv-rtag[data-tag-id]').each(function () {
            var name = $(this).text().toLowerCase().trim();
            var show = !q || name.includes(qLow);
            $(this).toggleClass('bv-hidden', !show);
            if (name === qLow) { hasExact = true; }
        });

        /* Update "Crear nueva" chip label */
        if (q && !hasExact) {
            $('#tags-create-text').html('Crear "<strong>' + $('<span>').text(q).html() + '</strong>"');
        } else {
            $('#tags-create-text').text('Crear nueva');
        }
    });

    /* ── Crear etiqueta nueva ────────────────────────────────── */
    $(document).on('click', '#tags-create-chip', function () {
        var newName = $('#tags-search').val().trim();
        if (!newName) {
            $('#tags-search').focus();
            return;
        }
        var tempId   = 'new-' + Date.now();
        var $newChip = $('<span class="bv-rtag bv-rtag--on" data-tag-id="' + tempId + '">' +
            $('<span>').text(newName).html() + '</span>');
        $(this).before($newChip);
        addChip(tempId, newName);
        $('#tags-search').val('').trigger('input');
    });

    /* ── Guardar etiquetas ───────────────────────────────────── */
    $(document).on('click', '#bv-tags-apply', function () {
        var $btn      = $(this).prop('disabled', true).text('Guardando…');
        var updateUrl = $('.bv-composer').data('bv-update-url');
        var csrf      = $('meta[name="csrf-token"]').attr('content');
        var existingIds = [];
        var newOpts     = [];

        tagsList().find('.bv-rtag.bv-rtag--on[data-tag-id]').each(function () {
            var id = String($(this).data('tag-id'));
            if (id.startsWith('new-')) {
                newOpts.push({ el: $(this), name: $(this).text().trim() });
            } else {
                existingIds.push(parseInt(id));
            }
        });

        var createPromises = newOpts.map(function (opt) {
            return $.ajax({
                url: modal().data('url-store'),
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                data: { name: opt.name, is_active: 1 },
            }).then(function (resp) {
                if (resp && resp.tag && resp.tag.id) {
                    var newId = resp.tag.id;
                    opt.el.attr('data-tag-id', newId);
                    applied().find('[data-tag-id="' + opt.el.data('tag-id') + '"]').attr('data-tag-id', newId);
                    existingIds.push(newId);
                }
            });
        });

        $.when.apply($, createPromises).always(function () {
            if (!updateUrl) {
                toastr.error('No se pudo determinar la conversación activa.');
                $btn.prop('disabled', false).text('Guardar etiquetas');
                return;
            }

            $.ajax({
                url: updateUrl,
                method: 'PUT',
                contentType: 'application/json',
                headers: { 'X-CSRF-TOKEN': csrf },
                data: JSON.stringify({ tag_ids: existingIds }),
            }).done(function (resp) {
                if (resp && resp.success) {
                    var finalIds = new Set(existingIds.map(String));
                    preAppliedIds.forEach(function (id) {
                        if (!finalIds.has(String(id))) {
                            var $c = $('.bv-nav-item--tag[data-bv-tag-id="' + id + '"] .c');
                            var n = parseInt($c.text()) || 0;
                            $c.text(n > 1 ? n - 1 : '0');
                        }
                    });
                    finalIds.forEach(function (id) {
                        if (!preAppliedIds.has(id)) {
                            var $c = $('.bv-nav-item--tag[data-bv-tag-id="' + id + '"] .c');
                            $c.text((parseInt($c.text()) || 0) + 1);
                        }
                    });
                    $('[data-bv-modal-name="tags"]').removeClass('on');
                    if (!$('.bv-modal.on').length) { $('body').css('overflow', ''); }

                    // Recarga el pane para que la sección "Etiquetas" del panel
                    // derecho refleje de inmediato lo que se acaba de guardar.
                    var convId = $('.bv-composer').data('bv-conversation-id');
                    if (convId && typeof window.bvLoadConversationPane === 'function') {
                        window.bvLoadConversationPane(convId, null, { push: false });
                    }
                } else {
                    toastr.error(resp.message || 'Error al guardar etiquetas');
                }
            }).fail(function () {
                toastr.error('Error al guardar las etiquetas. Intenta de nuevo.');
            }).always(function () {
                $btn.prop('disabled', false).text('Guardar etiquetas');
            });
        });
    });

})();
