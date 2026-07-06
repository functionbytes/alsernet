{{-- Modal: Perfil de agente (#10 ve-profile-agent) --}}
<div class="bv-modal" data-bv-modal-name="agent-profile">
    <div class="bv-modal-dialog md">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box primary"><i class="fas fa-headset"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">{{ __('helpdesk::helpdesk.inbox.modals.agent_profile_label') }}</span>
                <div class="bv-modal-title"><span id="apTitle">{{ __('helpdesk::helpdesk.inbox.modals.agent_profile_title') }}</span></div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>

        <div class="bv-modal-body" id="apBody">
            <div class="bv-oc-loading"><i class="fas fa-spinner fa-spin"></i> {{ __('helpdesk::helpdesk.inbox.modals.agent_profile_loading') }}</div>
        </div>

        <div class="bv-modal-foot">
            <button class="btn-primary" id="apTransfer" type="button">{{ __('helpdesk::helpdesk.inbox.modals.agent_profile_transfer') }}</button>
            <button class="btn-secondary" id="apMessage" type="button">{{ __('helpdesk::helpdesk.inbox.modals.agent_profile_send_internal_message') }}</button>
            <button class="btn-secondary" data-bv-close>{{ __('helpdesk::helpdesk.inbox.modals.agent_profile_close') }}</button>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
(function () {
    var _agentId = null;

    function body() { return $('#apBody'); }

    function esc(s) { return HDCommerce.esc(s == null ? '' : String(s)); }

    function statusClass(status) {
        if (status === 'online') { return 'online'; }
        if (status === 'away') { return 'away'; }
        if (status === 'busy') { return 'busy'; }
        return 'offline';
    }

    function fmtResponse(seconds) {
        if (seconds == null) { return '—'; }
        if (seconds < 60) { return seconds + 's'; }
        var mins = Math.round(seconds / 60);
        if (mins < 60) { return mins + 'm'; }
        var hours = Math.floor(mins / 60);
        var rem = mins % 60;
        return rem > 0 ? (hours + 'h ' + rem + 'm') : (hours + 'h');
    }

    function fmtMemberSince(iso) {
        if (!iso) { return '—'; }
        var d = new Date(iso);
        if (isNaN(d.getTime())) { return '—'; }
        var months = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
            'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
        return d.getDate() + ' ' + months[d.getMonth()] + ' ' + d.getFullYear();
    }

    function toMinutes(hhmm) {
        var parts = String(hhmm || '').split(':');
        if (parts.length < 2) { return null; }
        var h = parseInt(parts[0], 10);
        var m = parseInt(parts[1], 10);
        if (isNaN(h) || isNaN(m)) { return null; }
        return (h * 60) + m;
    }

    function fmtRemaining(minutes) {
        if (minutes <= 0) { return null; }
        var h = Math.floor(minutes / 60);
        var m = minutes % 60;
        if (h > 0 && m > 0) { return h + 'h ' + m + 'm'; }
        if (h > 0) { return h + 'h'; }
        return m + 'm';
    }

    function renderSchedule(schedule) {
        var today = schedule && schedule.today;
        if (!today || !today.enabled) {
            return '<div class="bv-ap-section">' +
                '<div class="bv-oc-section-title"><i class="fas fa-calendar-day"></i> Horario · hoy</div>' +
                '<div class="bv-ap-muted">Sin turno programado para hoy</div>' +
                '</div>';
        }

        var start = toMinutes(today.start);
        var end = toMinutes(today.end);
        var now = parseInt(schedule.nowMinutes, 10);
        var bar = '';
        var note = '';

        if (start != null && end != null && end > start) {
            var span = end - start;
            var leftPct = Math.max(0, Math.min(100, ((start) / (24 * 60)) * 100));
            var widthPct = Math.max(0, Math.min(100 - leftPct, (span / (24 * 60)) * 100));
            var nowPct = Math.max(0, Math.min(100, (now / (24 * 60)) * 100));
            bar = '<div class="bv-ap-timeline">' +
                '<div class="seg" data-left="' + leftPct.toFixed(2) + '" data-width="' + widthPct.toFixed(2) + '"></div>' +
                '<div class="cur" data-left="' + nowPct.toFixed(2) + '"></div>' +
                '</div>';

            if (now >= start && now < end) {
                var rem = fmtRemaining(end - now);
                if (rem) { note = 'Faltan <b>' + rem + '</b> para fin de turno'; }
            } else if (now < start) {
                note = 'El turno comienza a las <b>' + esc(today.start) + '</b>';
            } else {
                note = 'Turno finalizado a las <b>' + esc(today.end) + '</b>';
            }
        }

        return '<div class="bv-ap-section">' +
            '<div class="bv-oc-section-title"><i class="fas fa-calendar-day"></i> Horario · hoy</div>' +
            '<div class="bv-ap-schedule-row">' +
                bar +
                '<span class="bv-ap-schedule-time">' + esc(today.start) + ' → ' + esc(today.end) + '</span>' +
            '</div>' +
            (note ? '<div class="bv-ap-muted bv-ap-schedule-note">' + note + '</div>' : '') +
            '</div>';
    }

    function render(d) {
        var ag = d.agent || {};
        var wl = d.workload || {};
        var pf = d.performance || {};
        var info = d.info || {};
        var skills = d.skills || [];

        $('#apTitle').text(ag.name || 'Perfil del agente');

        // Header
        var head = '<div class="bv-ap-head">' +
            '<div class="bv-ap-av c1">' + esc(ag.initials || '?') +
                '<span class="av-dot ' + statusClass(ag.status) + '"></span>' +
            '</div>' +
            '<div class="bv-ap-head-body">' +
                '<div class="bv-ap-name">' + esc(ag.name) + '</div>' +
                '<div class="bv-ap-sub">' + esc(ag.email) + (ag.role ? ' · ' + esc(ag.role) : '') + '</div>' +
            '</div>' +
            '<span class="r-tag bv-ap-status-tag ' + statusClass(ag.status) + '">' +
                '<i class="fas fa-circle"></i> ' + esc(ag.statusLabel) +
            '</span>' +
        '</div>';

        // Workload
        var open = parseInt(wl.open, 10) || 0;
        var limit = parseInt(wl.limit, 10) || 0;
        var percent = parseInt(wl.percent, 10) || 0;
        var available = parseInt(wl.available, 10) || 0;
        var workload = '<div class="bv-ap-section">' +
            '<div class="bv-oc-section-title"><i class="fas fa-gauge-high"></i> Carga actual</div>' +
            '<div class="bv-ap-load-row"><span>Tickets activos</span><span class="bv-ap-load-val">' + open + ' / ' + limit + '</span></div>' +
            '<div class="bv-ap-bar"><div class="fill" data-pct="' + percent + '"></div></div>' +
            '<div class="bv-ap-load-foot"><span>Capacidad al ' + percent + '%</span><span>' + available + ' disponibles</span></div>' +
        '</div>';

        // Performance
        var perf = '<div class="bv-ap-section">' +
            '<div class="bv-oc-section-title"><i class="fas fa-chart-bar"></i> Desempeño · últimos 7 días</div>' +
            '<div class="bv-ap-stat-grid">' +
                '<div class="bv-ap-stat"><div class="sv">' + (parseInt(pf.resolved7d, 10) || 0) + '</div><div class="sl">Resueltos</div></div>' +
                '<div class="bv-ap-stat"><div class="sv">' + (pf.csat != null ? pf.csat : '—') + '</div><div class="sl">CSAT promedio</div></div>' +
                '<div class="bv-ap-stat"><div class="sv">' + fmtResponse(pf.avgFirstResponse) + '</div><div class="sl">1ª respuesta</div></div>' +
            '</div>' +
        '</div>';

        // Skills
        var skillsHtml = '';
        if (skills.length) {
            skillsHtml = '<div class="bv-ap-section">' +
                '<div class="bv-oc-section-title"><i class="fas fa-bolt"></i> Especialidades</div>' +
                '<div class="bv-ap-tags">' +
                    skills.map(function (s) { return '<span class="r-tag">' + esc(s.name) + '</span>'; }).join('') +
                '</div>' +
            '</div>';
        }

        // Info
        var rows = '';
        if (info.languages) { rows += '<div class="lbl">Idiomas</div><div class="val">' + esc(info.languages) + '</div>'; }
        if (info.department) { rows += '<div class="lbl">Departamento</div><div class="val">' + esc(info.department) + '</div>'; }
        if (info.phone) { rows += '<div class="lbl">Teléfono</div><div class="val">' + esc(info.phone) + '</div>'; }
        if (info.memberSince) {
            var since = fmtMemberSince(info.memberSince) + (info.tenure ? ' · ' + esc(info.tenure) : '');
            rows += '<div class="lbl">Activo desde</div><div class="val">' + since + '</div>';
        }
        var infoHtml = rows
            ? '<div class="bv-ap-section">' +
                '<div class="bv-oc-section-title"><i class="fas fa-globe"></i> Información</div>' +
                '<div class="info-table">' + rows + '</div>' +
              '</div>'
            : '';

        body().html(head + workload + perf + skillsHtml + infoHtml + renderSchedule(d.schedule));

        // Apply dynamic widths/positions without inline style attributes in markup.
        body().find('.bv-ap-bar .fill').each(function () {
            this.style.width = ($(this).data('pct') || 0) + '%';
        });
        body().find('.bv-ap-timeline .seg').each(function () {
            this.style.left = ($(this).data('left') || 0) + '%';
            this.style.width = ($(this).data('width') || 0) + '%';
        });
        body().find('.bv-ap-timeline .cur').each(function () {
            this.style.left = ($(this).data('left') || 0) + '%';
        });
    }

    function load(agentId) {
        body().html('<div class="bv-oc-loading"><i class="fas fa-spinner fa-spin"></i> Cargando…</div>');
        HDCommerce.ajax({
            url: '/panel/helpdesk/agents/' + agentId + '/profile-data',
            method: 'GET',
        }).done(function (resp) {
            if (!resp || !resp.success) {
                body().html('<div class="bv-oc-empty"><i class="fas fa-triangle-exclamation"></i><div class="title">No se pudo cargar el perfil</div></div>');
                return;
            }
            render(resp);
        }).fail(function (xhr) {
            body().html('<div class="bv-oc-empty"><i class="fas fa-triangle-exclamation"></i><div class="title">' +
                esc(HDCommerce.errorMessage(xhr, 'No se pudo cargar el perfil del agente.')) + '</div></div>');
        });
    }

    // ── Footer actions ──
    $(document).on('click', '#apTransfer', function () {
        HDCommerce.close('agent-profile');
        if (typeof window.openAssign === 'function') {
            window.openAssign(_agentId);
            return;
        }
        var $trigger = $('[data-bv-modal="assign"]').first();
        if ($trigger.length) {
            setTimeout(function () { $trigger.trigger('click'); }, 80);
            return;
        }
        if (window.toastr) { toastr.info('Abre el panel de asignación para transferir la conversación.'); }
    });

    $(document).on('click', '#apMessage', function () {
        HDCommerce.close('agent-profile');
        if (typeof window.openMention === 'function') {
            window.openMention(_agentId);
            return;
        }
        var $trigger = $('[data-bv-modal="mention"]').first();
        if ($trigger.length) {
            setTimeout(function () { $trigger.trigger('click'); }, 80);
            return;
        }
        if (window.toastr) { toastr.info('Usa la mención (@) en el editor para enviar un mensaje interno.'); }
    });

    // ── Trigger delegation ──
    $(document).on('click', '[data-bv-modal="agent-profile"]', function () {
        var agentId = $(this).data('agent-id');
        if (!agentId) {
            if (window.toastr) { toastr.warning('Esta conversación no tiene agente asignado.'); }
            return;
        }
        window.openAgentProfile(agentId);
    });

    // ── Public API ──
    window.openAgentProfile = function (agentId) {
        if (!agentId) {
            if (window.toastr) { toastr.warning('Agente no disponible.'); }
            return;
        }
        _agentId = agentId;
        HDCommerce.open('agent-profile');
        load(agentId);
    };
})();
</script>
@endpush
@endonce
