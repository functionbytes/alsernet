{{-- Modal: Gestionar etiquetas --}}
<div class="bv-modal" data-bv-modal-name="tags">
    <div class="bv-modal-dialog">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box"><i class="fas fa-tags"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">{{ __('helpdesk::helpdesk.inbox.modals.tags_eyebrow') }}</span>
                <div class="bv-modal-title">
                    {{ __('helpdesk::helpdesk.inbox.modals.tags_title') }}
                    @if(!empty($selectedConversation))<span class="bv-chip-id">#{{ $selectedConversation->id }}</span>@endif
                </div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            {{-- Search / create --}}
            <div class="bv-modal-search bv-mb-10">
                <i class="fas fa-magnifying-glass"></i>
                <input id="tags-search" type="text" placeholder="{{ __('helpdesk::helpdesk.inbox.modals.tags_search_placeholder') }}" autocomplete="off">
            </div>

            {{-- Applied section --}}
            <div class="bv-tags-section">
                <div class="bv-tags-section-label">{{ __('helpdesk::helpdesk.inbox.modals.tags_applied_label') }}</div>
                <div id="tags-applied" class="bv-tags-chip-wrap">
                    @forelse($selectedConversation?->conversationTags ?? [] as $tag)
                        <span class="bv-rtag bv-rtag--on" data-tag-id="{{ $tag->id }}">
                            {{ $tag->name }}<i class="fas fa-xmark bv-rtag-x tags-remove-chip"></i>
                        </span>
                    @empty
                        <em class="bv-tags-empty" id="tags-applied-empty">{{ __('helpdesk::helpdesk.inbox.modals.tags_none_applied') }}</em>
                    @endforelse
                </div>
            </div>

            {{-- Available tags --}}
            <div class="bv-tags-section">
                <div class="bv-tags-section-label">{{ __('helpdesk::helpdesk.inbox.modals.tags_available_label') }}</div>
                <div class="bv-tags-chip-wrap" id="tags-list">
                    @if(!empty($inboxTags) && $inboxTags->isNotEmpty())
                        @foreach($inboxTags as $tag)
                            <span class="bv-rtag {{ $selectedConversation && $selectedConversation->conversationTags->contains('id', $tag->id) ? 'bv-rtag--on' : '' }}"
                                  data-tag-id="{{ $tag->id }}">{{ $tag->name }}</span>
                        @endforeach
                    @else
                        <em class="bv-tags-empty">{{ __('helpdesk::helpdesk.inbox.modals.tags_none_available') }}</em>
                    @endif
                    <span class="bv-rtag bv-rtag--add" id="tags-create-chip">
                        <i class="fas fa-plus bv-x42"></i>
                        <span id="tags-create-text">{{ __('helpdesk::helpdesk.inbox.modals.tags_create_new') }}</span>
                    </span>
                </div>
            </div>

        </div>
        <div class="bv-modal-foot">
            <div class="bv-tags-foot-hint">
                <span class="bv-kbd">⏎</span> {{ __('helpdesk::helpdesk.inbox.modals.tags_create_new') }} · <span class="bv-kbd">⌫</span> {{ __('helpdesk::helpdesk.inbox.modals.tags_remove_last') }}
            </div>
            <button class="btn-primary" id="bv-tags-apply">{{ __('helpdesk::helpdesk.inbox.modals.tags_save') }}</button>
            <button class="btn-secondary" data-bv-close>{{ __('helpdesk::helpdesk.inbox.modals.cancel') }}</button>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
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
                url: '{{ route("settings.helpdesk.tags.store") }}',
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

    /* ── Actualiza chips del panel derecho ───────────────────── */
    function updateRightPanelTags() {
        var chips = [];
        applied().find('.bv-rtag').each(function () {
            var $chip = $(this);
            var id    = $chip.data('tag-id');
            var name  = $chip.clone().children().remove().end().text().trim();
            var color = '#6c757d';
            chips.push({ name: name, color: color });
        });

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
            if ($wrap.length) { $wrap.html(html); } else { $empty.before('<div class="bv-tags-wrap">' + html + '</div>'); }
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
