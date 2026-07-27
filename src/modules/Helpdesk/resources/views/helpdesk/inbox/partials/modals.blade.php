{{-- ═══════════════════════════════════════════════════════════════════════
   Inbox v4 — Modales
   ─────────────────────────────────────────────────────────────────────────
   Cada modal vive en su propio archivo en partials/modals/{name}.blade.php
   Trigger: click en cualquier elemento con data-bv-modal="{name}"
   Cerrar: data-bv-close, click en backdrop, o tecla ESC
   ═══════════════════════════════════════════════════════════════════════ --}}

{{-- Modales clave (acciones frecuentes) --}}
@include('helpdesk::helpdesk.inbox.partials.modals.status')
@include('helpdesk::helpdesk.inbox.partials.modals.priority')
@include('helpdesk::helpdesk.inbox.partials.modals.filter')
@include('helpdesk::helpdesk.inbox.partials.modals.edit-contact')
@include('helpdesk::helpdesk.inbox.partials.modals.shortcuts')

{{-- Modales del flujo principal --}}
@include('helpdesk::helpdesk.inbox.partials.modals.newconv')
@include('helpdesk::helpdesk.inbox.partials.modals.assign')
@include('helpdesk::helpdesk.inbox.partials.modals.transfer')
@include('helpdesk::helpdesk.inbox.partials.modals.move-to-team')
@include('helpdesk::helpdesk.inbox.partials.modals.tags')
@include('helpdesk::helpdesk.inbox.partials.modals.tag-create')
@include('helpdesk::helpdesk.inbox.partials.modals.close-conv')
@include('helpdesk::helpdesk.inbox.partials.modals.archive-conv')
@include('helpdesk::helpdesk.inbox.partials.modals.block-contact')
@include('helpdesk::helpdesk.inbox.partials.modals.mark-spam')
@include('helpdesk::helpdesk.inbox.partials.modals.delete-conv')
@include('helpdesk::helpdesk.inbox.partials.modals.merge')

{{-- Modales de detalle --}}
@include('helpdesk::helpdesk.inbox.partials.modals.order')
@include('helpdesk::helpdesk.inbox.partials.modals.ticket')
@include('helpdesk::helpdesk.inbox.partials.modals.preview-conv')

{{-- Helper JS commerce (compartido) + modales de carrito (módulo HelpdeskPrestashop) --}}
@include('helpdesk::helpdesk.inbox.partials.modals._commerce-js')
@if(helpdesk_prestashop_enabled())
    @include('helpdeskprestashop::modals.cart-build')
    @include('helpdeskprestashop::modals.carts-list')
    @include('helpdeskprestashop::modals.cart-detail')
    @include('helpdeskprestashop::modals.order-workspace')
    @include('helpdeskprestashop::modals.product-recommend')
@endif

@if(helpdesk_erp_enabled() && view()->exists('helpdeskerp::modals.order-workspace'))
    @include('helpdeskerp::modals.order-workspace')
@endif

{{-- orders-list / order-view eliminados: consolidados en el tab "Tienda" (PrestaShop)
     y el modal 'order' existente. Ver right-panel.blade.php tab ps-orders. --}}

{{-- Modales de cliente, agente, tickets, ayuda, integraciones y documentos --}}
@include('helpdesk::helpdesk.inbox.partials.modals.profile-customer')
@include('helpdesk::helpdesk.inbox.partials.modals.agent-profile')
@if(helpdesk_helpcenter_enabled())
    @include('helpdesk::helpdesk.inbox.partials.modals.help-center')
@endif
@if(helpdesk_integration_enabled() && view()->exists('helpdeskintegration::modals.customer-integrations'))
    @include('helpdeskintegration::modals.customer-integrations')
@endif
@if(view()->exists('helpdeskintegration::modals.verify-customer-identity'))
    @include('helpdeskintegration::modals.verify-customer-identity')
@endif

{{-- Modales de búsqueda y composer --}}
@include('helpdesk::helpdesk.inbox.partials.modals.customer-search')
@include('helpdesk::helpdesk.inbox.partials.modals.quick-reply')
@include('helpdesk::helpdesk.inbox.partials.modals.translate')
@include('helpdesk::helpdesk.inbox.partials.modals.attach-file')
@include('helpdesk::helpdesk.inbox.partials.modals.assign-tickets')
@include('helpdesk::helpdesk.inbox.partials.modals.reminder')
@include('helpdesk::helpdesk.inbox.partials.modals.email')
@include('helpdesk::helpdesk.inbox.partials.modals.email-viewer')
@include('helpdesk::helpdesk.inbox.partials.modals.create-ticket')
@include('helpdesk::helpdesk.inbox.partials.modals.schedule')
@include('helpdesk::helpdesk.inbox.partials.modals.schedule-msg')
@include('helpdesk::helpdesk.inbox.partials.modals.snooze')
@include('helpdesk::helpdesk.inbox.partials.modals.note')
@include('helpdesk::helpdesk.inbox.partials.modals.mention')
@include('helpdesk::helpdesk.inbox.partials.modals.mention-help')

{{-- Modal: Lightbox de archivos --}}
@include('helpdesk::helpdesk.inbox.partials.modals.file-preview')

{{-- Modal: Compartir tienda de la empresa --}}
@include('helpdesk::helpdesk.inbox.partials.modals.store-picker')

{{-- Modales: Adjuntar (contacto, ubicación) --}}
@include('helpdesk::helpdesk.inbox.partials.modals.attach-contact')
@include('helpdesk::helpdesk.inbox.partials.modals.attach-location')

{{-- Modal: Vincular conversación a otro cliente (identidad/ficha incorrecta) --}}
@include('helpdesk::helpdesk.inbox.partials.modals.link-customer')

{{-- Modal: Plantillas WhatsApp HSM --}}
@include('helpdesk::helpdesk.inbox.partials.modals.hsm')

{{-- Modal: Guardar vista (inline, fuera del sistema bv-modal) --}}
@include('helpdesk::helpdesk.inbox.partials.modals.save-view')

{{-- Modales: Historial y visor de conversaciones anteriores --}}
@include('helpdesk::helpdesk.inbox.partials.modals.history')
@include('helpdesk::helpdesk.inbox.partials.modals.conversation-viewer')

{{-- Modal: Audit log de la conversación --}}
@include('helpdesk::helpdesk.inbox.partials.modals.audit-log')

{{-- Modales de agente y sistema --}}
@include('helpdesk::helpdesk.inbox.partials.modals.away-mode')
@include('helpdesk::helpdesk.inbox.partials.modals.notifications')

{{-- Modales de acciones avanzadas --}}
@include('helpdesk::helpdesk.inbox.partials.modals.macro')
@include('helpdesk::helpdesk.inbox.partials.modals.bulk-actions')
@include('helpdesk::helpdesk.inbox.partials.modals.export-conv')

{{-- Command palette (Ctrl+K / Cmd+K — activa independiente del sistema bv-modal) --}}
@include('helpdesk::helpdesk.inbox.partials.modals.command-palette')

{{-- Modales de resolución y encuesta --}}
@include('helpdesk::helpdesk.inbox.partials.modals.resolve')

{{-- Modales de traducción automática --}}
@include('helpdesk::helpdesk.inbox.partials.modals.detect-lang')

{{-- Modales de silenciar / gestión de canal --}}
@include('helpdesk::helpdesk.inbox.partials.modals.mute-chat')

{{-- Modales de permisos de micrófono --}}
@include('helpdesk::helpdesk.inbox.partials.modals.mic-permission')

{{-- Modales de notas internas avanzadas --}}
@include('helpdesk::helpdesk.inbox.partials.modals.internal-note')

{{-- Modales IA --}}
@include('helpdesk::helpdesk.inbox.partials.modals.ai-summary')
@include('helpdesk::helpdesk.inbox.partials.modals.ai-suggest')
@include('helpdesk::helpdesk.inbox.partials.modals.sentiment')

{{-- Modales de incidencias y supervisión --}}
@include('helpdesk::helpdesk.inbox.partials.modals.report-incident')
@include('helpdesk::helpdesk.inbox.partials.modals.supervisor-review')

{{-- Modales de administración (roles, SLA, horarios, auto-asignación) --}}
@include('helpdesk::helpdesk.inbox.partials.modals.role-perms')
@include('helpdesk::helpdesk.inbox.partials.modals.sla-config')
@include('helpdesk::helpdesk.inbox.partials.modals.business-hours')
@include('helpdesk::helpdesk.inbox.partials.modals.auto-assign')

{{-- Modales de feedback y novedades --}}
@include('helpdesk::helpdesk.inbox.partials.modals.feedback')
@include('helpdesk::helpdesk.inbox.partials.modals.changelog')

{{-- Modales de importación / exportación / reportes --}}
@include('helpdesk::helpdesk.inbox.partials.modals.import-conv')
@include('helpdesk::helpdesk.inbox.partials.modals.schedule-report')
@include('helpdesk::helpdesk.inbox.partials.modals.export-contacts')

{{-- Modales de nueva conversación (wizard 2 pasos) --}}
@include('helpdesk::helpdesk.inbox.partials.modals.new-conv-wizard')

{{-- Modales de pedidos y paneles laterales --}}
@include('helpdesk::helpdesk.inbox.partials.modals.orders-list')
@include('helpdesk::helpdesk.inbox.partials.modals.media-panel')
@include('helpdesk::helpdesk.inbox.partials.modals.tickets-panel')

@push('scripts')
<script>
(function ($) {
    'use strict';
    if (!window.jQuery) { return; }

    var _histData   = [];
    var _histFilter = 'all';
    var _histSearch = '';

    function getCustomerId() {
        var fromAttr = $('[data-customer-id]').first().attr('data-customer-id');
        if (fromAttr) { return fromAttr; }
        if (window.HDCommerce && typeof window.HDCommerce.customerId === 'function') {
            return window.HDCommerce.customerId();
        }
        return null;
    }

    function escHtml(s) {
        if (s == null) { return ''; }
        return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    function escAttr(s) {
        if (s == null) { return ''; }
        return String(s).replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    function channelIcon(ch) {
        var map = {
            whatsapp: 'fa-brands fa-whatsapp', facebook: 'fa-brands fa-facebook-messenger',
            instagram: 'fa-brands fa-instagram', email: 'fa-regular fa-envelope',
            widget: 'fa-regular fa-comment', webchat: 'fa-regular fa-comment',
        };
        return map[ch] || 'fa-regular fa-comment-dots';
    }

    function renderList(data) {
        var filtered = data.filter(function (c) {
            var okFilter = _histFilter === 'all' ||
                           (_histFilter === 'open' && c.status_open) ||
                           (_histFilter === 'closed' && !c.status_open);
            var okSearch = !_histSearch ||
                           (c.subject || '').toLowerCase().indexOf(_histSearch) !== -1 ||
                           (c.preview || '').toLowerCase().indexOf(_histSearch) !== -1;
            return okFilter && okSearch;
        });

        if (!filtered.length) {
            $('#histList').html('<div class="bv-cv-loading-msg"><i class="fas fa-inbox"></i></div>');
            return;
        }

        var html = filtered.map(function (c) {
            var previewUrl = '/panel/helpdesk/conversations/' + c.id + '/preview';
            var openUrl    = '/panel/helpdesk/conversations?selected=' + c.id;
            var stClass    = c.status_open ? 'open' : '';
            return '<div class="bv-hist-item rp3-prev"' +
                   ' data-preview-url="' + escAttr(previewUrl) + '"' +
                   ' data-open-url="' + escAttr(openUrl) + '"' +
                   ' data-subject="' + escAttr(c.subject) + '">' +
                       '<div class="bv-hist-ch"><i class="' + channelIcon(c.channel) + '"></i></div>' +
                       '<div class="bv-hist-info">' +
                           '<div class="bv-hist-subj">' + escHtml(c.subject || ('#' + c.id)) + '</div>' +
                           '<div class="bv-hist-prev">' + escHtml(c.preview || '') + '</div>' +
                       '</div>' +
                       '<div class="bv-hist-meta">' +
                           '<span class="bv-hist-status ' + stClass + '">' + escHtml(c.status || (c.status_open ? 'Abierta' : 'Cerrada')) + '</span>' +
                           '<span class="bv-hist-time">' + escHtml(c.time) + '</span>' +
                       '</div>' +
                   '</div>';
        }).join('');

        $('#histList').html(html);
    }

    function loadHistory(customerId) {
        _histData   = [];
        _histFilter = 'all';
        _histSearch = '';
        $('#histSearchInput').val('');
        $('.bv-hist-pill').removeClass('on');
        $('.bv-hist-pill[data-bv-hist-filter="all"]').addClass('on');
        $('#histList').html('<div class="bv-cv-loading-msg"><i class="fas fa-spinner fa-spin"></i></div>');

        $.ajax({
            url: '/panel/helpdesk/customers/' + customerId + '/conversations',
            method: 'GET',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
        }).done(function (resp) {
            _histData = resp.data || [];
            var open   = _histData.filter(function (c) { return c.status_open; }).length;
            var closed = _histData.length - open;

            $('#histModalCount').text(_histData.length);
            $('#histCountAll').text(_histData.length);
            $('#histCountOpen').text(open);
            $('#histCountClosed').text(closed);

            renderList(_histData);
        }).fail(function () {
            $('#histList').html('<div class="bv-cv-loading-msg"><i class="fas fa-triangle-exclamation"></i></div>');
        });
    }

    // Cargar cuando se abre el modal history
    $(document).on('bv:modal:open', function (e, name) {
        if (name !== 'history') { return; }
        var customerId = getCustomerId();
        if (!customerId) {
            $('#histList').html('<div class="bv-cv-loading-msg"><i class="fas fa-user-slash"></i></div>');
            return;
        }
        loadHistory(customerId);
    });

    // Filter pills
    $(document).on('click', '.bv-hist-pill', function () {
        $('.bv-hist-pill').removeClass('on');
        $(this).addClass('on');
        _histFilter = $(this).data('bv-hist-filter') || 'all';
        renderList(_histData);
    });

    // Search
    $(document).on('input', '#histSearchInput', function () {
        _histSearch = $(this).val().toLowerCase().trim();
        renderList(_histData);
    });

}(window.jQuery));
</script>
@endpush
