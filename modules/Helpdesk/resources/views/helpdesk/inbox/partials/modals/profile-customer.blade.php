{{-- Modal: Perfil del cliente (#09 ve-profile-customer) --}}
<div class="bv-modal" data-bv-modal-name="profile-customer">
    <div class="bv-modal-dialog lg">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box primary"><i class="fas fa-user"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">CHAT · CLIENTE</span>
                <div class="bv-modal-title"><span>Perfil del cliente</span></div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>

        <div class="bv-modal-body" id="cpBody">
            <div class="bv-oc-loading"><i class="fas fa-spinner fa-spin"></i> Cargando perfil…</div>
        </div>

        <div class="bv-modal-foot">
            <button class="btn-primary" id="cpStartConversation" type="button">Iniciar conversación</button>
            <button class="btn-secondary" id="cpFullHistory" type="button">Ver historial completo</button>
            <button class="btn-secondary" data-bv-close>Cerrar</button>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
(function () {
    var _loaded = false;

    function esc(s) { return window.HDCommerce.esc(s); }

    function loadingHtml() {
        return '<div class="bv-oc-loading"><i class="fas fa-spinner fa-spin"></i> Cargando perfil…</div>';
    }

    function errorHtml() {
        return '<div class="bv-oc-empty"><i class="fas fa-triangle-exclamation"></i>' +
            '<div class="title">No se pudo cargar el perfil</div></div>';
    }

    function initials(name) {
        var parts = (name || '').trim().split(/\s+/).slice(0, 2);
        var ini = parts.map(function (w) { return w.charAt(0); }).join('');
        return ini ? ini.toUpperCase() : '?';
    }

    function contactRow(label, value, mono) {
        if (!value) { return ''; }
        return '<div class="lbl">' + esc(label) + '</div>' +
            '<div class="val' + (mono ? ' mono' : '') + '">' + esc(value) + '</div>';
    }

    function ticketRow(t) {
        var color = t.statusColor || '#71717a';
        var stateClass = t.isOpen ? 'open' : 'closed';
        return '<div class="bv-cp-tkt">' +
            '<span class="bv-cp-tkt-id">' + esc(t.number || ('#' + t.id)) + '</span>' +
            '<div class="bv-cp-tkt-main">' +
                '<div class="bv-cp-tkt-subj">' + esc(t.subject || '—') + '</div>' +
                '<div class="bv-cp-tkt-meta">' + esc(t.createdAtHuman || '') + '</div>' +
            '</div>' +
            '<span class="bv-cp-tkt-state ' + stateClass + '" data-color="' + esc(color) + '">' +
                esc(t.statusLabel || (t.isOpen ? 'Abierto' : 'Cerrado')) +
            '</span>' +
        '</div>';
    }

    function render(data) {
        var c = data.customer || {};
        var stats = data.stats || {};
        var tags = data.tags || [];
        var tickets = data.recentTickets || [];

        var html = '';

        // ── Cabecera: avatar + nombre + badges ──
        var badges = '';
        if (c.isVip) {
            badges += '<span class="bv-cp-badge vip"><i class="fas fa-star"></i> VIP</span>';
        }
        badges += '<span class="bv-cp-badge">' + (stats.conversations || 0) + ' conversaciones</span>';
        badges += '<span class="bv-cp-badge">' + (stats.tickets || 0) + ' tickets</span>';

        var role = [c.company].filter(Boolean).join(' · ');

        html += '<div class="bv-cp-head">' +
            '<div class="bv-cp-avatar">' + esc(initials(c.name)) + '</div>' +
            '<div class="bv-cp-head-main">' +
                '<div class="bv-cp-name">' + esc(c.name || 'Sin nombre') + '</div>' +
                (role ? '<div class="bv-cp-role">' + esc(role) + '</div>' : '') +
                '<div class="bv-cp-badges">' + badges + '</div>' +
            '</div>' +
        '</div>';

        // ── Información de contacto ──
        var rows = '';
        rows += contactRow('Email', c.email);
        rows += contactRow('Teléfono', c.phone, true);
        rows += contactRow('Empresa', c.company);
        rows += contactRow('Idioma', c.language);
        rows += contactRow('Zona horaria', c.timezone);
        rows += contactRow('Cliente desde', c.createdAtHuman);

        if (rows) {
            html += '<div class="bv-cp-section">' +
                '<div class="bv-oc-section-title"><i class="fas fa-id-card"></i> Información de contacto</div>' +
                '<div class="info-table">' + rows + '</div>' +
            '</div>';
        }

        // ── Actividad ──
        var csat = (stats.csat === null || stats.csat === undefined) ? '—' : String(stats.csat);
        html += '<div class="bv-cp-section">' +
            '<div class="bv-oc-section-title"><i class="fas fa-chart-line"></i> Actividad</div>' +
            '<div class="bv-cp-stats">' +
                '<div class="bv-cp-stat"><div class="sv">' + (stats.conversations || 0) + '</div><div class="sl">Conversaciones</div></div>' +
                '<div class="bv-cp-stat"><div class="sv">' + (stats.tickets || 0) + '</div><div class="sl">Tickets</div></div>' +
                '<div class="bv-cp-stat"><div class="sv">' + esc(csat) + '</div><div class="sl">CSAT promedio</div></div>' +
            '</div>' +
        '</div>';

        // ── Etiquetas ──
        if (tags.length) {
            var tagHtml = tags.map(function (t) {
                return '<span class="bv-cp-tag">' + esc(t) + '</span>';
            }).join('');
            html += '<div class="bv-cp-section">' +
                '<div class="bv-oc-section-title"><i class="fas fa-tag"></i> Etiquetas</div>' +
                '<div class="bv-cp-tags">' + tagHtml + '</div>' +
            '</div>';
        }

        // ── Tickets recientes ──
        html += '<div class="bv-cp-section">' +
            '<div class="bv-oc-section-title"><i class="far fa-clock"></i> Tickets recientes</div>';
        if (tickets.length) {
            html += '<div class="bv-cp-tkt-list">' + tickets.map(ticketRow).join('') + '</div>';
        } else {
            html += '<div class="bv-oc-empty"><i class="far fa-clock"></i><div class="title">Sin tickets</div></div>';
        }
        html += '</div>';

        $('#cpBody').html(html);

        // Pintar color del estado del ticket sin estilos inline en el markup base.
        $('#cpBody .bv-cp-tkt-state').each(function () {
            var col = $(this).data('color');
            if (col) { this.style.setProperty('--tkt-color', col); }
        });
    }

    function load() {
        var base = window.HDCommerce.base();
        if (!base) { return; }
        $('#cpBody').html(loadingHtml());
        window.HDCommerce.ajax({ url: base + '/profile-data', method: 'GET' })
            .done(function (resp) {
                _loaded = true;
                render(resp);
            })
            .fail(function () {
                $('#cpBody').html(errorHtml());
            });
    }

    // Abrir vía evento bv:modal:open (lo dispara HDCommerce.open)
    $(document).on('bv:modal:open', function (e, name) {
        if (name === 'profile-customer') { load(); }
    });

    // Trigger declarativo data-bv-modal="profile-customer" no pasa por HDCommerce.open,
    // así que observamos la apertura de la clase .on para cargar los datos.
    var node = document.querySelector('[data-bv-modal-name="profile-customer"]');
    if (node) {
        (new MutationObserver(function (mutations) {
            mutations.forEach(function (m) {
                if (m.attributeName !== 'class') { return; }
                if ($(m.target).hasClass('on')) { load(); }
            });
        })).observe(node, { attributes: true });
    }

    // Footer: iniciar conversación (reusa el modal newconv existente)
    $(document).on('click', '#cpStartConversation', function () {
        window.HDCommerce.close('profile-customer');
        $('[data-bv-modal-name="newconv"]').addClass('on');
        $('body').css('overflow', 'hidden');
    });

    // Footer: ver historial completo del cliente
    $(document).on('click', '#cpFullHistory', function () {
        var id = window.HDCommerce.customerId();
        if (id) { window.open('/panel/helpdesk/customers/' + id, '_blank', 'noopener'); }
    });

    // API pública
    window.openCustomerProfile = function () {
        if (!window.HDCommerce.customerId()) {
            if (window.toastr) { toastr.warning('Selecciona una conversación con cliente.'); }
            return;
        }
        window.HDCommerce.open('profile-customer');
    };
})();
</script>
@endpush
@endonce
