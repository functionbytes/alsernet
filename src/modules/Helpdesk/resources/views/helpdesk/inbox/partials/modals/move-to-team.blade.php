{{-- Modal: Mover conversación a equipo --}}
<div class="bv-modal" data-bv-modal-name="move-to-team">
    <div class="bv-modal-dialog">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box"><i class="fas fa-right-left"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">BANDEJA · CONVERSACIÓN</span>
                <div class="bv-modal-title">Mover a equipo</div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            @include('helpdesk::helpdesk.inbox.partials.modals._context-card')

            {{-- Search --}}
            <div class="bv-modal-search bv-mb-12">
                <i class="fas fa-magnifying-glass"></i>
                <input id="move-team-search" type="text" placeholder="Buscar equipo…" autocomplete="off">
            </div>

            {{-- Group list --}}
            <div class="bv-opt-list" id="move-team-list">
                @forelse($groups ?? [] as $group)
                    <button class="bv-opt" data-group-id="{{ $group->id }}">
                        <div class="bv-av c{{ ($loop->index % 8) + 1 }}">
                            <i class="fas fa-users bv-icon-sm"></i>
                        </div>
                        <div class="body">
                            <div class="name">{{ $group->name }}</div>
                        </div>
                        <i class="fas fa-check check"></i>
                    </button>
                @empty
                    <div class="bv-empty-msg">No hay equipos disponibles.</div>
                @endforelse
            </div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="move-team-btn">Mover conversación</button>
            <button class="btn-secondary" data-bv-close>Cancelar</button>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
$(document).on('click', '[data-bv-modal-name="move-to-team"] .bv-opt', function () {
    $('[data-bv-modal-name="move-to-team"] .bv-opt').removeClass('on');
    $(this).addClass('on');
});

$(document).on('input', '#move-team-search', function () {
    var q = $(this).val().toLowerCase();
    $('[data-bv-modal-name="move-to-team"] .bv-opt').each(function () {
        var name = $(this).find('.name').text().toLowerCase();
        $(this).toggle(!q || name.includes(q));
    });
});

$(document).on('click', '#move-team-btn', function () {
    var $selected = $('[data-bv-modal-name="move-to-team"] .bv-opt.on');
    var groupId = $selected.data('group-id');
    var convId = $('.bv-composer').data('bv-conversation-id');

    if (!groupId) {
        if (window.toastr) toastr.warning('Selecciona un equipo destino');
        return;
    }

    if (!convId) {
        if (window.toastr) toastr.warning('No hay conversación activa');
        return;
    }

    $.ajax({
        url: '/panel/helpdesk/conversations/' + convId,
        method: 'PUT',
        dataType: 'json',
        data: { group_id: groupId },
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            'Accept': 'application/json',
        },
    })
    .done(function (resp) {
        $('[data-bv-modal-name="move-to-team"]').removeClass('on');
        if ($('body').css('overflow') !== '') $('body').css('overflow', '');

        var groupName = $selected.find('.name').text();
        $('.bv-conv.on').fadeOut(300, function () { $(this).remove(); });
    })
    .fail(function (xhr) {
        var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'No se pudo mover la conversación';
        if (window.toastr) toastr.error(msg);
    });
});
</script>
@endpush
@endonce
