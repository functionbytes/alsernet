{{-- Modal: Gestionar etiquetas --}}
<div class="bv-modal" data-bv-modal-name="tags">
    <div class="bv-modal-dialog">
        <div class="bv-modal-head">
            <div class="bv-modal-title">Etiquetas</div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            {{-- Search / create --}}
            <div class="bv-modal-search">
                <i class="fas fa-magnifying-glass"></i>
                <input id="tags-search" type="text" placeholder="Buscar o crear etiqueta…" autocomplete="off">
            </div>

            {{-- Create suggestion (hidden by default) --}}
            <div id="tags-create-hint" style="display:none;padding:8px 12px;margin-bottom:8px;border-radius:8px;background:var(--bv-bg-subtle);font-size:12px;cursor:pointer">
                <i class="fas fa-plus" style="margin-right:6px;color:var(--bv-accent)"></i>
                Crear etiqueta "<strong id="tags-create-text"></strong>"
            </div>

            {{-- Tag list --}}
            <div class="bv-right-section-title" style="margin-bottom:8px">Etiquetas disponibles</div>
            <div class="bv-opt-list" id="tags-list">
                <div class="bv-opt on" data-tag-id="urgente">
                    <span class="dot" style="background:#ef4444;width:10px;height:10px;border-radius:50%;flex-shrink:0"></span>
                    <div class="body">
                        <div class="name">Urgente</div>
                        <div class="sub">34 conversaciones</div>
                    </div>
                    <i class="fas fa-check check"></i>
                </div>
                <div class="bv-opt" data-tag-id="envio">
                    <span class="dot" style="background:#f59e0b;width:10px;height:10px;border-radius:50%;flex-shrink:0"></span>
                    <div class="body">
                        <div class="name">Problema de envío</div>
                        <div class="sub">21 conversaciones</div>
                    </div>
                    <i class="fas fa-check check"></i>
                </div>
                <div class="bv-opt on" data-tag-id="reembolso">
                    <span class="dot" style="background:#10b981;width:10px;height:10px;border-radius:50%;flex-shrink:0"></span>
                    <div class="body">
                        <div class="name">Reembolso</div>
                        <div class="sub">15 conversaciones</div>
                    </div>
                    <i class="fas fa-check check"></i>
                </div>
                <div class="bv-opt" data-tag-id="vip">
                    <span class="dot" style="background:#3b82f6;width:10px;height:10px;border-radius:50%;flex-shrink:0"></span>
                    <div class="body">
                        <div class="name">Cliente VIP</div>
                        <div class="sub">12 conversaciones</div>
                    </div>
                    <i class="fas fa-check check"></i>
                </div>
                <div class="bv-opt" data-tag-id="tecnico">
                    <span class="dot" style="background:#6366f1;width:10px;height:10px;border-radius:50%;flex-shrink:0"></span>
                    <div class="body">
                        <div class="name">Soporte técnico</div>
                        <div class="sub">9 conversaciones</div>
                    </div>
                    <i class="fas fa-check check"></i>
                </div>
                <div class="bv-opt" data-tag-id="postventa">
                    <span class="dot" style="background:#ec4899;width:10px;height:10px;border-radius:50%;flex-shrink:0"></span>
                    <div class="body">
                        <div class="name">Postventa</div>
                        <div class="sub">7 conversaciones</div>
                    </div>
                    <i class="fas fa-check check"></i>
                </div>
            </div>

            {{-- Applied chips --}}
            <div class="bv-right-section-title" style="margin-top:14px;margin-bottom:8px">Aplicadas a esta conversación</div>
            <div id="tags-applied" style="display:flex;flex-wrap:wrap;gap:6px">
                <span class="bv-chpill on" data-tag-id="urgente">
                    Urgente
                    <button type="button" class="tags-remove-chip" style="background:none;border:none;padding:0;margin-left:4px;cursor:pointer;line-height:1"><i class="fas fa-xmark" style="font-size:9px"></i></button>
                </span>
                <span class="bv-chpill on" data-tag-id="reembolso">
                    Reembolso
                    <button type="button" class="tags-remove-chip" style="background:none;border:none;padding:0;margin-left:4px;cursor:pointer;line-height:1"><i class="fas fa-xmark" style="font-size:9px"></i></button>
                </span>
            </div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary">Guardar etiquetas</button>
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
                '<button type="button" class="tags-remove-chip" style="background:none;border:none;padding:0;margin-left:4px;cursor:pointer;line-height:1"><i class="fas fa-xmark" style="font-size:9px"></i></button>' +
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
        '<span class="dot" style="background:#6366f1;width:10px;height:10px;border-radius:50%;flex-shrink:0"></span>' +
        '<div class="body"><div class="name">' + $('<span>').text(newTag).html() + '</div><div class="sub">Nueva etiqueta</div></div>' +
        '<i class="fas fa-check check"></i></div>'
    );
});
</script>
@endpush
@endonce
