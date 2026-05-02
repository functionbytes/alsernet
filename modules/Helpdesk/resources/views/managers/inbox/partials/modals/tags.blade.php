{{-- Modal: Gestionar etiquetas --}}
<div class="bv-modal" data-bv-modal-name="tags">
    <div class="bv-modal-dialog">
        <div class="bv-modal-head">
            <div>
                <span class="bv-modal-label">Chat · Bandeja</span>
                <div class="bv-modal-title"><i class="fas fa-tags bv-modal-title-icon"></i> Etiquetas</div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            @include('helpdesk::managers.inbox.partials.modals._context-card')

            {{-- Search / create --}}
            <div class="bv-modal-search">
                <i class="fas fa-magnifying-glass"></i>
                <input id="tags-search" type="text" placeholder="Buscar o crear etiqueta…" autocomplete="off">
            </div>

            {{-- Create suggestion (hidden by default) --}}
            <div id="tags-create-hint" class="bv-tags-create-hint d-none">
                <i class="fas fa-plus bv-tags-hint-icon"></i>
                Crear etiqueta "<strong id="tags-create-text"></strong>"
            </div>

            {{-- Tag list --}}
            <div class="bv-right-section-title bv-mb-8">Etiquetas disponibles</div>
            <div class="bv-opt-list" id="tags-list">
                @if($inboxTags->isEmpty())
                    <em class="text-muted small">Sin etiquetas disponibles</em>
                @else
                    @foreach($inboxTags as $tag)
                        <div class="bv-opt {{ $selectedConversation && $selectedConversation->conversationTags->contains('id', $tag->id) ? 'on' : '' }}"
                             data-tag-id="{{ $tag->id }}">
                            <div class="body">
                                <div class="name">{{ $tag->name }}</div>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>

            {{-- Applied chips --}}
            <div class="bv-right-section-title bv-mt-14 bv-mb-8">Aplicadas a esta conversación</div>
            <div id="tags-applied" class="bv-tags-applied">
                @forelse($selectedConversation?->conversationTags ?? [] as $tag)
                    <span class="bv-chpill on" data-tag-id="{{ $tag->id }}">
                        {{ $tag->name }}
                        <button type="button" class="tags-remove-chip bv-chip-remove"><i class="fas fa-xmark bv-icon-xs"></i></button>
                    </span>
                @empty
                    <em class="text-muted small">Ninguna aplicada</em>
                @endforelse
            </div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="bv-tags-apply">Guardar etiquetas</button>
            <button class="btn-secondary" data-bv-close>Cancelar</button>
        </div>
    </div>
</div>

@once
<style>
/* Lista de opts como chips inline en vez de filas full-width */
[data-bv-modal-name="tags"] .bv-opt-list {
    display: flex !important;
    flex-direction: row !important;
    flex-wrap: wrap !important;
    gap: 6px;
}
[data-bv-modal-name="tags"] .bv-opt {
    width: auto !important;
    flex: 0 0 auto !important;
    display: inline-flex !important;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    border-radius: 20px;
    cursor: pointer;
}
[data-bv-modal-name="tags"] .bv-opt .body { flex: none; }

/* Etiqueta ya aplicada al abrir el modal */
[data-bv-modal-name="tags"] .bv-opt.on {
    background: #e8e8e8;
    color: #555;
    border-color: #ccc;
}
/* Etiqueta recién seleccionada en esta sesión */
[data-bv-modal-name="tags"] .bv-opt.on--new {
    background: rgba(177,1,0,.08);
    color: #b10100;
    border-color: #b10100;
    font-weight: 600;
}
</style>
@endonce

@once
@push('scripts')
<script>
(function () {
    /* ── helpers ─────────────────────────────────────────── */
    function tagsModal() { return $('[data-bv-modal-name="tags"]'); }

    var preAppliedIds = new Set();

    function addChip(tagId, tagName) {
        var applied = $('#tags-applied');
        applied.find('em').remove();
        if (!applied.find('[data-tag-id="' + tagId + '"]').length) {
            applied.append(
                '<span class="bv-chpill on" data-tag-id="' + tagId + '">' +
                $('<span>').text(tagName).html() +
                '<button type="button" class="tags-remove-chip bv-chip-remove">' +
                '<i class="fas fa-xmark bv-icon-xs"></i></button></span>'
            );
        }
    }

    function removeChip(tagId) {
        $('#tags-applied [data-tag-id="' + tagId + '"]').remove();
        if (!$('#tags-applied .bv-chpill').length) {
            $('#tags-applied').html('<em class="text-muted small">Ninguna aplicada</em>');
        }
    }

    /* ── sincroniza opts al abrir el modal ──────────────── */
    (new MutationObserver(function (mutations) {
        mutations.forEach(function (m) {
            if (m.attributeName !== 'class') { return; }
            var $modal = $(m.target);
            if (!$modal.hasClass('on')) { return; }
            preAppliedIds = new Set();
            $modal.find('.bv-opt').removeClass('on on--new');
            $('#tags-applied .bv-chpill').each(function () {
                var id = String($(this).data('tag-id'));
                preAppliedIds.add(id);
                $modal.find('.bv-opt[data-tag-id="' + id + '"]').addClass('on');
            });
            $('#tags-search').val('').trigger('input');
        });
    })).observe(document.querySelector('[data-bv-modal-name="tags"]'), { attributes: true });

    /* ── toggle selección (en el modal, no en document, para disparar antes que bandeja-v4) */
    $(document.querySelector('[data-bv-modal-name="tags"]')).on('click', '.bv-opt', function (e) {
        e.stopPropagation();                                   // evita que bandeja-v4 resetee los opts
        var $opt    = $(this);
        var tagId   = String($opt.data('tag-id'));
        var tagName = $opt.find('.name').text().trim();
        var wasOn   = $opt.hasClass('on');

        if (wasOn) {
            $opt.removeClass('on on--new');
            removeChip(tagId);
        } else {
            $opt.addClass('on');
            if (!preAppliedIds.has(tagId)) {
                $opt.addClass('on--new');
            }
            addChip(tagId, tagName);
        }
    });

    /* ── quitar chip ─────────────────────────────────────── */
    $(document).on('click', '.tags-remove-chip', function () {
        var chip  = $(this).closest('.bv-chpill');
        var tagId = String(chip.data('tag-id'));
        chip.remove();
        if (!$('#tags-applied .bv-chpill').length) {
            $('#tags-applied').html('<em class="text-muted small">Ninguna aplicada</em>');
        }
        tagsModal().find('.bv-opt[data-tag-id="' + tagId + '"]').removeClass('on on--new');
    });

    /* ── búsqueda ────────────────────────────────────────── */
    $(document).on('input', '#tags-search', function () {
        var q = $(this).val().toLowerCase().trim();
        var hasExact = false;
        tagsModal().find('.bv-opt').each(function () {
            var name = $(this).find('.name').text().toLowerCase();
            var show = !q || name.includes(q);
            $(this).toggle(show);
            if (name === q) { hasExact = true; }
        });
        if (q && !hasExact) {
            $('#tags-create-text').text($(this).val());
            $('#tags-create-hint').removeClass('d-none').show();
        } else {
            $('#tags-create-hint').addClass('d-none').hide();
        }
    });

    /* ── crear etiqueta nueva ────────────────────────────── */
    $(document).on('click', '#tags-create-hint', function () {
        var newName = $('#tags-search').val().trim();
        if (!newName) { return; }
        var tempId = 'new-' + Date.now();
        // Optimistic: add to list with temp ID
        $('#tags-list').append(
            '<div class="bv-opt on on--new" data-tag-id="' + tempId + '">' +
            '<div class="body"><div class="name">' + $('<span>').text(newName).html() + '</div></div>' +
            '</div>'
        );
        addChip(tempId, newName);
        $('#tags-search').val('').trigger('input');
    });

    /* ── guardar etiquetas ───────────────────────────────── */
    $(document).on('click', '#bv-tags-apply', function () {
        var $btn       = $(this).prop('disabled', true).text('Guardando…');
        var updateUrl  = $('.bv-composer').data('bv-update-url');
        var csrf       = $('meta[name="csrf-token"]').attr('content');
        var existingIds = [];
        var newOpts     = [];

        tagsModal().find('.bv-opt.on').each(function () {
            var id = String($(this).data('tag-id'));
            if (id.startsWith('new-')) {
                newOpts.push({ el: $(this), name: $(this).find('.name').text().trim() });
            } else {
                existingIds.push(parseInt(id));
            }
        });

        // Crea primero las etiquetas nuevas, luego sincroniza
        var createPromises = newOpts.map(function (opt) {
            return $.ajax({
                url: '{{ route("settings.helpdesk.tags.store") }}',
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                data: { name: opt.name, is_active: 1 },
            }).then(function (resp) {
                if (resp && resp.tag && resp.tag.id) {
                    // Reemplaza el temp-id en la opt y en el chip
                    opt.el.attr('data-tag-id', resp.tag.id);
                    $('#tags-applied [data-tag-id="' + opt.el.data('tag-id') + '"]')
                        .attr('data-tag-id', resp.tag.id);
                    existingIds.push(resp.tag.id);
                }
            }).fail(function () {
                // Si falla la creación, ignora esa etiqueta
            });
        });

        $.when.apply($, createPromises).always(function () {
            if (!updateUrl) {
                toastr.error('No se pudo determinar la conversación activa.');
                $btn.prop('disabled', false).text('Guardar etiquetas');
                return;
            }

            // Envía como JSON para que el array vacío llegue correctamente al backend
            $.ajax({
                url: updateUrl,
                method: 'PUT',
                contentType: 'application/json',
                headers: { 'X-CSRF-TOKEN': csrf },
                data: JSON.stringify({ tag_ids: existingIds }),
            }).done(function (resp) {
                if (resp && resp.success) {
                    // Actualiza contadores del nav lateral
                    var finalIds = new Set(existingIds.map(String));
                    preAppliedIds.forEach(function (id) {
                        if (!finalIds.has(String(id))) {
                            // Tag eliminado: decrementa contador
                            var $c = $('.bv-nav-item--tag[data-bv-tag-id="' + id + '"] .c');
                            var n = parseInt($c.text()) || 0;
                            if (n > 1) { $c.text(n - 1); } else { $c.text('0'); }
                        }
                    });
                    finalIds.forEach(function (id) {
                        if (!preAppliedIds.has(id)) {
                            // Tag añadido: incrementa contador
                            var $c = $('.bv-nav-item--tag[data-bv-tag-id="' + id + '"] .c');
                            var n = parseInt($c.text()) || 0;
                            $c.text(n + 1);
                        }
                    });
                    // Cierra el modal
                    $('[data-bv-modal-name="tags"]').removeClass('on');
                    if (!$('.bv-modal.on').length) { $('body').css('overflow', ''); }
                    // Actualiza chips del panel derecho
                    updateRightPanelTags();
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

    /* ── actualiza chips del panel derecho ───────────────── */
    function updateRightPanelTags() {
        var chips = [];

        $('#tags-applied .bv-chpill').each(function () {
            var id    = $(this).data('tag-id');
            var name  = $(this).clone().children().remove().end().text().trim();
            var $dot  = tagsModal().find('.bv-opt[data-tag-id="' + id + '"] .bv-tag-dot');
            var color = $dot.length ? $dot.css('background-color') : '#6c757d';
            chips.push({ name: name, color: color });
        });

        // El panel derecho tiene la sección de etiquetas con .bv-right-section
        // Buscamos la sección que contiene .bv-tags-wrap o .bv-tab-empty-inline con "Sin etiquetas"
        var $section = $('.bv-right-section').filter(function () {
            return $(this).find('.bv-tags-wrap, .bv-tab-empty-inline').length > 0;
        }).first();

        if (!$section.length) { return; }

        var $wrap  = $section.find('.bv-tags-wrap');
        var $empty = $section.find('.bv-tab-empty-inline');

        if (chips.length) {
            var html = chips.map(function (t) {
                return '<span class="bv-tag-pill bv-tag-pill--dynamic" style="--bv-tag-color:' +
                    $('<span>').text(t.color).html() + '">' +
                    $('<span>').text(t.name).html() + '</span>';
            }).join('');
            if ($wrap.length) {
                $wrap.html(html);
            } else {
                $empty.before('<div class="bv-tags-wrap">' + html + '</div>');
            }
            $empty.hide();
        } else {
            $wrap.remove();
            $empty.show();
        }
    }
})();
</script>
@endpush
@endonce
