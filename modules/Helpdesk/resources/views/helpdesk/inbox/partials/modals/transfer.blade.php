{{-- Modal: Transferir conversación (#02 ve-transfer) --}}
<div class="bv-modal" data-bv-modal-name="transfer">
    <div class="bv-modal-dialog md">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box"><i class="fas fa-right-left"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">BANDEJA · CONVERSACIÓN</span>
                <div class="bv-modal-title">Transferir conversación</div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            {{-- Agente actual --}}
            <div class="bv-tf-current" id="tfCurrentAgentBanner" style="display:none">
                <div class="bv-tf-current__inner">
                    <span class="bv-tf-current__label">Actualmente asignado a</span>
                    <span class="bv-tf-current__name" id="tfCurrentAgentName"></span>
                    <i class="fas fa-arrow-right bv-tf-current__arrow"></i>
                </div>
            </div>

            {{-- Búsqueda --}}
            <div class="bv-modal-search">
                <i class="fas fa-magnifying-glass"></i>
                <input id="transferSearch" type="text" placeholder="Buscar agente…" autocomplete="off">
            </div>

            {{-- Lista de agentes --}}
            <div id="transferList" class="asgn-list">
                <div class="bv-cv-loading-msg"><i class="fas fa-spinner fa-spin"></i></div>
            </div>
        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="bv-transfer-confirm" disabled>Transferir</button>
            <button class="btn-secondary" data-bv-close>Cancelar</button>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
(function ($) {
    'use strict';

    var _tfAgentId = null;

    function getConvId() {
        return $('.bv-composer').data('bv-conversation-id') || null;
    }

    function closeBvModal(name) {
        $('[data-bv-modal-name="' + name + '"]').removeClass('on');
        if ($('.bv-modal.on').length === 0) { $('body').css('overflow', ''); }
    }

    function loadTransferAgents() {
        var $list = $('#transferList');
        $list.html('<div class="bv-cv-loading-msg"><i class="fas fa-spinner fa-spin"></i></div>');
        _tfAgentId = null;
        $('#bv-transfer-confirm').prop('disabled', true);

        $.ajax({
            url: '/panel/helpdesk/presence/agents',
            method: 'GET',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
        }).done(function (resp) {
            var agents = (resp.agents || []).filter(function (a) { return a.status !== 'offline'; });
            if (!agents.length) {
                $list.html('<div class="bv-cv-loading-msg"><i class="fas fa-users-slash"></i> Sin agentes disponibles</div>');
                return;
            }
            var html = agents.map(function (a, i) {
                var initials = ((a.firstname || a.name || '?')[0] + ((a.lastname || '')[0] || '')).toUpperCase();
                var col = (i % 7) + 1;
                var statusClass = a.status || 'offline';
                return '<button class="asgn-item" data-agent-id="' + a.id + '">' +
                    '<div class="bv-av c' + col + ' asgn-av">' + initials + '<span class="bv-av-dot ' + statusClass + '"></span></div>' +
                    '<div class="asgn-body"><span class="asgn-t">' + (a.name || (a.firstname + ' ' + a.lastname)) + '</span><span class="asgn-s">' + (a.role || a.email || '') + '</span></div>' +
                    '<div class="asgn-check"><i class="fas fa-check"></i></div>' +
                    '</button>';
            }).join('');
            $list.html(html);
        }).fail(function () {
            $list.html('<div class="bv-cv-loading-msg"><i class="fas fa-triangle-exclamation"></i> Error al cargar</div>');
        });
    }

    $(document).on('bv:modal:open', function (e, name) {
        if (name !== 'transfer') { return; }
        $('#transferSearch').val('');
        loadTransferAgents();

        var current = $('.bv-composer [data-assignee-name]').data('assignee-name');
        if (current) {
            $('#tfCurrentAgentName').text(current);
            $('#tfCurrentAgentBanner').show();
        } else {
            $('#tfCurrentAgentBanner').hide();
        }
    });

    $(document).on('click', '#transferList .asgn-item', function () {
        $('#transferList .asgn-item').removeClass('on');
        $(this).addClass('on');
        _tfAgentId = $(this).data('agent-id');
        $('#bv-transfer-confirm').prop('disabled', false);
    });

    $(document).on('input', '#transferSearch', function () {
        var q = $(this).val().toLowerCase();
        $('#transferList .asgn-item').each(function () {
            $(this).toggle(!q || $(this).find('.asgn-t').text().toLowerCase().indexOf(q) !== -1);
        });
    });

    $(document).on('click', '#bv-transfer-confirm', function () {
        if (_tfAgentId == null) { return; }
        var convId = getConvId();
        if (!convId) {
            if (window.toastr) { toastr.warning('Sin conversación activa'); }
            return;
        }
        var $btn = $(this).prop('disabled', true);
        $.ajax({
            url: '/panel/helpdesk/conversations/' + convId,
            method: 'PUT',
            data: { assignee_id: _tfAgentId },
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), 'Accept': 'application/json' }
        }).done(function () {
            closeBvModal('transfer');
            if (window.toastr) { toastr.success('Conversación transferida'); }
        }).fail(function (xhr) {
            var msg = xhr?.responseJSON?.message || 'Error al transferir';
            if (window.toastr) { toastr.error(msg); }
        }).always(function () {
            $btn.prop('disabled', false);
        });
    });

}(window.jQuery));
</script>
@endpush
@endonce
