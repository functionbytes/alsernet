{{-- Modal: Modo ausente / disponibilidad (#60 ve-away-mode) --}}
<div class="bv-modal" data-bv-modal-name="away-mode">
    <div class="bv-modal-dialog md">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box"><i class="far fa-moon"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">{{ __('helpdesk::helpdesk.inbox.modals.away_mode_label') }}</span>
                <div class="bv-modal-title">{{ __('helpdesk::helpdesk.inbox.modals.away_mode_title') }}</div>
            </div>
            <button class="bv-modal-close" data-bv-close aria-label="{{ __('helpdesk::helpdesk.inbox.modals.away_mode_close_aria') }}"><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            <div class="field">
                <label class="flabel">{{ __('helpdesk::helpdesk.inbox.modals.away_mode_field_current_status') }}</label>
                <div class="reason-list" id="awayModeList">
                    <button type="button" class="reason" data-bv-value="available">
                        <div class="ic"><i class="fas fa-circle bv-st-available"></i></div>
                        <div class="body">
                            <span class="t">{{ __('helpdesk::helpdesk.inbox.modals.away_mode_status_available_title') }}</span>
                            <span class="s">{{ __('helpdesk::helpdesk.inbox.modals.away_mode_status_available_desc') }}</span>
                        </div>
                        <div class="radio"></div>
                    </button>
                    <button type="button" class="reason" data-bv-value="away">
                        <div class="ic"><i class="far fa-moon"></i></div>
                        <div class="body">
                            <span class="t">{{ __('helpdesk::helpdesk.inbox.modals.away_mode_status_away_title') }}</span>
                            <span class="s">{{ __('helpdesk::helpdesk.inbox.modals.away_mode_status_away_desc') }}</span>
                        </div>
                        <div class="radio"></div>
                    </button>
                    <button type="button" class="reason" data-bv-value="busy">
                        <div class="ic"><i class="fas fa-mug-hot"></i></div>
                        <div class="body">
                            <span class="t">{{ __('helpdesk::helpdesk.inbox.modals.away_mode_status_busy_title') }}</span>
                            <span class="s">{{ __('helpdesk::helpdesk.inbox.modals.away_mode_status_busy_desc') }}</span>
                        </div>
                        <div class="radio"></div>
                    </button>
                    <button type="button" class="reason" data-bv-value="offline">
                        <div class="ic"><i class="fas fa-circle-stop bv-st-dnd"></i></div>
                        <div class="body">
                            <span class="t">{{ __('helpdesk::helpdesk.inbox.modals.away_mode_status_offline_title') }}</span>
                            <span class="s">{{ __('helpdesk::helpdesk.inbox.modals.away_mode_status_offline_desc') }}</span>
                        </div>
                        <div class="radio"></div>
                    </button>
                </div>
            </div>

            <div class="field">
                <label class="flabel">{{ __('helpdesk::helpdesk.inbox.modals.away_mode_field_auto_message') }}</label>
                <textarea class="finput" id="awayMessage" rows="3"
                          placeholder="{{ __('helpdesk::helpdesk.inbox.modals.away_mode_auto_message_placeholder') }}"></textarea>
            </div>

            <div class="frow">
                <div class="field">
                    <label class="flabel">{{ __('helpdesk::helpdesk.inbox.modals.away_mode_field_auto_return') }}</label>
                    <select class="fselect" id="awayAutoReturn">
                        <option value="manual">{{ __('helpdesk::helpdesk.inbox.modals.away_mode_auto_return_manual') }}</option>
                        <option value="1h">{{ __('helpdesk::helpdesk.inbox.modals.away_mode_auto_return_1h') }}</option>
                        <option value="4h">{{ __('helpdesk::helpdesk.inbox.modals.away_mode_auto_return_4h') }}</option>
                        <option value="tomorrow">{{ __('helpdesk::helpdesk.inbox.modals.away_mode_auto_return_tomorrow') }}</option>
                    </select>
                </div>
                <div class="field">
                    <label class="flabel">{{ __('helpdesk::helpdesk.inbox.modals.away_mode_field_reassign') }}</label>
                    <select class="fselect" id="awayReassign">
                        <option value="keep">{{ __('helpdesk::helpdesk.inbox.modals.away_mode_reassign_keep') }}</option>
                        <option value="team">{{ __('helpdesk::helpdesk.inbox.modals.away_mode_reassign_team') }}</option>
                        <optgroup label="{{ __('helpdesk::helpdesk.inbox.modals.away_mode_reassign_agent_group') }}" id="awayReassignAgentsGroup"></optgroup>
                    </select>
                </div>
            </div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="bv-away-mode-confirm">{{ __('helpdesk::helpdesk.inbox.modals.away_mode_save') }}</button>
            <button class="btn-secondary" data-bv-close>{{ __('helpdesk::helpdesk.inbox.modals.cancel') }}</button>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
window.HD_CURRENT_USER_ID = @json(auth()->id());
(function ($) {
    'use strict';

    var _loaded = null;
    var _agentsList = null;
    var LABELS = { available: 'Disponible', away: 'Ausente', busy: 'En descanso', offline: 'No molestar' };
    var BTN    = { available: 'Guardar estado', away: 'Activar modo ausente', busy: 'Activar descanso', offline: 'Activar no molestar' };

    function csrf() { return $('meta[name="csrf-token"]').attr('content'); }

    function initSelect2() {
        if (!$.fn.select2) { return; }
        $('#awayAutoReturn').select2({ width: '100%', minimumResultsForSearch: Infinity, dropdownAutoWidth: false });
        $('#awayReassign').select2({ width: '100%', dropdownAutoWidth: false });
    }

    // Puebla el optgroup "A un agente específico" con los agentes reales
    // (excluyendo al propio usuario) y re-sincroniza el widget select2.
    function populateReassignAgents(agents) {
        var $group = $('#awayReassignAgentsGroup');
        if (!$group.length) { return; }
        var current = $('#awayReassign').val();
        $group.empty();
        (agents || []).forEach(function (a) {
            if (String(a.user_id) === String(window.HD_CURRENT_USER_ID)) { return; }
            $group.append($('<option>').val('agent:' + a.user_id).text(a.name));
        });
        $('#awayReassign').val(current).trigger('change');
    }

    function loadReassignAgents() {
        $.ajax({
            url: '/panel/helpdesk/presence/agents', method: 'GET',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf() }
        }).done(function (resp) {
            _agentsList = resp.agents || [];
            populateReassignAgents(_agentsList);
            // Si la presencia ya se había cargado (posible carrera con loadPresence),
            // reaplica la selección ahora que las opciones de agente ya existen.
            if (_loaded) { fillForm(_loaded); }
        });
    }

    function paintCard(state) {
        var $st = $('.bv-nav-user-status');
        if (!$st.length) { return; }
        var raw = $st.text();
        var tail = raw.indexOf('·') !== -1 ? raw.slice(raw.indexOf('·')) : '';
        $st.html('<span class="bv-nav-user-dot is-' + state + '"></span>' + (LABELS[state] || '') + ' ' + tail);
    }

    function updateBtn() {
        var st = $('#awayModeList .reason.on').data('bv-value') || 'available';
        $('#bv-away-mode-confirm').text(BTN[st] || 'Guardar estado');
    }

    function fillForm(data) {
        var st = (data && data.raw_state) || 'available';
        $('#awayModeList .reason').removeClass('on');
        $('#awayModeList .reason[data-bv-value="' + st + '"]').addClass('on');
        $('#awayMessage').val((data && data.away_message) || '');
        $('#awayAutoReturn').val((data && data.auto_return) || 'manual').trigger('change');
        var reassign = (data && data.reassign) || 'keep';
        var reassignVal = (reassign === 'agent' && data && data.reassign_agent_id)
            ? ('agent:' + data.reassign_agent_id) : reassign;
        $('#awayReassign').val(reassignVal).trigger('change');
        updateBtn();
    }

    function loadPresence() {
        $.ajax({
            url: '/panel/helpdesk/presence/me', method: 'GET',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf() }
        }).done(function (resp) {
            _loaded = resp;
            paintCard(resp.raw_state || 'available');
        });
    }

    function heartbeat() {
        // Con la pestaña en segundo plano no latimos si el agente está
        // "disponible": así deja de figurar disponible mientras no está mirando.
        // En estados manuales (ausente/ocupado/no molestar) sí mantenemos el
        // latido para no perder el estado elegido.
        var state = (_loaded && _loaded.raw_state) || 'available';
        if (document.hidden && state === 'available') { return; }
        $.ajax({
            url: '/panel/helpdesk/presence/heartbeat', method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' }
        });
    }

    // Mantiene la presencia viva (auto-marca "disponible" si estaba offline) y
    // refleja el estado inicial en la tarjeta de usuario del nav.
    initSelect2();
    heartbeat();
    setInterval(heartbeat, 60000);
    setTimeout(loadPresence, 500);
    setTimeout(loadReassignAgents, 500);
    // Latir de inmediato al volver a la pestaña.
    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) { heartbeat(); }
    });

    $(document).on('bv:modal:open', function (e, name) {
        if (name !== 'away-mode') { return; }
        if (_loaded) { fillForm(_loaded); }
        else {
            loadPresence();
            setTimeout(function () { fillForm(_loaded); }, 350);
        }
    });

    $(document).on('click', '#awayModeList .reason', function () {
        $('#awayModeList .reason').removeClass('on');
        $(this).addClass('on');
        updateBtn();
    });

    $(document).on('click', '#bv-away-mode-confirm', function () {
        var state = $('#awayModeList .reason.on').data('bv-value') || 'available';
        var reassignRaw = $('#awayReassign').val() || 'keep';
        var reassignMode = reassignRaw;
        var reassignAgentId = null;
        if (reassignRaw.indexOf('agent:') === 0) {
            reassignMode = 'agent';
            reassignAgentId = reassignRaw.slice('agent:'.length);
        }
        var payload = {
            state: state,
            away_message: $('#awayMessage').val(),
            auto_return: $('#awayAutoReturn').val(),
            reassign: reassignMode,
            reassign_agent_id: reassignAgentId,
            timezone: (window.Intl && Intl.DateTimeFormat().resolvedOptions().timeZone) || '',
        };
        var $btn = $(this).prop('disabled', true);
        $.ajax({
            url: '/panel/helpdesk/presence/state', method: 'POST', data: payload,
            headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' }
        }).done(function (resp) {
            _loaded = {
                raw_state: state, away_message: payload.away_message, auto_return: payload.auto_return,
                reassign: reassignMode, reassign_agent_id: reassignAgentId,
            };
            paintCard(state);
            $('[data-bv-modal-name="away-mode"]').removeClass('on');
            if ($('.bv-modal.on').length === 0) { $('body').css('overflow', ''); }
            if (window.toastr) {
                var msg = 'Estado actualizado: ' + (LABELS[state] || state);
                var n = resp && resp.reassigned;
                if (n > 0) { msg += ' · ' + n + (n === 1 ? ' conversación devuelta a la cola' : ' conversaciones devueltas a la cola'); }
                toastr.success(msg);
            }
        }).fail(function (xhr) {
            var msg = (xhr && xhr.responseJSON && xhr.responseJSON.message) || 'Error al actualizar estado';
            if (window.toastr) { toastr.error(msg); }
        }).always(function () { $btn.prop('disabled', false); });
    });

    // ─── Presencia de agentes en vivo ─────────────────────────────────────────
    // Mantiene los dots de estado de CADA agente ([data-agent-id] en assign /
    // transfer / bulk / panel derecho) sincronizados. Tiempo real vía Echo cuando
    // Reverb está activo; polling como fallback (dev sin Reverb).
    var PRESENCE_DOT = { available: 'online', busy: 'busy', away: 'away', offline: 'offline' };
    var _presencePoll = null;

    function updateAgentDot(userId, state) {
        if (!userId) { return; }
        $('[data-agent-id="' + userId + '"] .bv-av-dot')
            .attr('class', 'bv-av-dot ' + (PRESENCE_DOT[state] || 'offline'));
    }

    function refreshAllAgentDots() {
        $.ajax({
            url: '/panel/helpdesk/presence/agents', method: 'GET',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf() }
        }).done(function (resp) {
            (resp.agents || []).forEach(function (a) { updateAgentDot(a.user_id, a.presence_state); });
        }).fail(function (xhr) {
            // Sin permiso para ver la presencia de agentes: dejar de pollear.
            if (xhr && xhr.status === 403 && _presencePoll) { clearInterval(_presencePoll); }
        });
    }

    if (window.Echo) {
        try {
            window.Echo.channel('helpdesk.presence.global')
                .listen('.presence.changed', function (e) { updateAgentDot(e.user_id, e.new_state); });
        } catch (err) { /* Echo no disponible */ }
    }
    refreshAllAgentDots();
    _presencePoll = setInterval(refreshAllAgentDots, 90000);

    // Reflejar el propio cambio de estado en los dots al instante.
    $(document).on('click', '#bv-away-mode-confirm', function () {
        var state = $('#awayModeList .reason.on').data('bv-value') || 'available';
        updateAgentDot(window.HD_CURRENT_USER_ID, state);
    });

}(window.jQuery));
</script>
@endpush
@endonce
