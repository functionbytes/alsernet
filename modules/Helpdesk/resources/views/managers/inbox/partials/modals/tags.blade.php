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
                            <span class="dot bv-tag-dot" style="background:{{ $tag->color ?? '#6c757d' }}"></span>
                            <div class="body">
                                <div class="name">{{ $tag->name }}</div>
                                <div class="sub">{{ $tag->conversations_count ?? 0 }} conversaciones</div>
                            </div>
                            <i class="fas fa-check check"></i>
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
            <button class="btn-primary" id="bv-tags-apply"><i class="fas fa-check"></i> Guardar etiquetas</button>
            <button class="btn-secondary" data-bv-close>Cancelar</button>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
$(document).on('click', '[data-bv-modal-name="tags"] .bv-opt', function () {
    $(this).toggleClass('on');
    var tagId = $(this).data('tag-id');
    var tagName = $(this).find('.name').text();
    var applied = $('#tags-applied');

    if ($(this).hasClass('on')) {
        if (!applied.find('[data-tag-id="' + tagId + '"]').length) {
            applied.append(
                '<span class="bv-chpill on" data-tag-id="' + tagId + '">' +
                tagName +
                '<button type="button" class="tags-remove-chip bv-chip-remove"><i class="fas fa-xmark bv-icon-xs"></i></button>' +
                '</span>'
            );
        }
    } else {
        applied.find('[data-tag-id="' + tagId + '"]').remove();
    }
});

$(document).on('click', '.tags-remove-chip', function () {
    var chip = $(this).closest('.bv-chpill');
    var tagId = chip.data('tag-id');
    chip.remove();
    $('[data-bv-modal-name="tags"] .bv-opt[data-tag-id="' + tagId + '"]').removeClass('on');
});

$(document).on('input', '#tags-search', function () {
    var q = $(this).val().toLowerCase().trim();
    var hasExact = false;

    $('[data-bv-modal-name="tags"] .bv-opt').each(function () {
        var name = $(this).find('.name').text().toLowerCase();
        var visible = !q || name.includes(q);
        $(this).toggle(visible);
        if (name === q) { hasExact = true; }
    });

    if (q && !hasExact) {
        $('#tags-create-text').text($(this).val());
        $('#tags-create-hint').show();
    } else {
        $('#tags-create-hint').hide();
    }
});

$(document).on('click', '#tags-create-hint', function () {
    var newTag = $('#tags-search').val().trim();
    if (!newTag) { return; }
    $('#tags-search').val('').trigger('input');
    $('#tags-list').append(
        '<div class="bv-opt on" data-tag-id="new-' + Date.now() + '">' +
        '<span class="dot bv-tag-dot bv-tag-dot--purple"></span>' +
        '<div class="body"><div class="name">' + $('<span>').text(newTag).html() + '</div><div class="sub">Nueva etiqueta</div></div>' +
        '<i class="fas fa-check check"></i></div>'
    );
});
</script>
@endpush
@endonce
