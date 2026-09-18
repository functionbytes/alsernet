{{-- ═══════════════════════════════════════════════════════════════════════
   Inbox v4 — Modales
   ─────────────────────────────────────────────────────────────────────────
   Cada modal vive en su propio archivo en partials/modals/{name}.blade.php
   Trigger: click en cualquier elemento con data-bv-modal="{name}"
   Cerrar: data-bv-close, click en backdrop, o tecla ESC
   ═══════════════════════════════════════════════════════════════════════ --}}

{{-- Carga diferida de scripts de modal (auditoría 17-sep-2026): los ~40 modales
     que no son de uso inmediato (todo salvo status/priority/filter/edit-contact/
     shortcuts, que son las acciones más frecuentes) ya NO traen su propio
     <script src> — su JS se descarga la primera vez que openModal() los abre.
     Ver window.BvLazyModalScripts / window.BvLoadedModalScripts en
     conversations-core.js. El mapa se resuelve aquí en PHP (mismo filemtime de
     cache-busting que usaba cada partial) para no recalcularlo en JS. --}}
@php
    $bvLazyModalNames = [
        'agent-profile', 'ai-suggest', 'ai-summary', 'attach-contact', 'attach-file',
        'attach-location', 'audit-log', 'away-mode', 'bulk-actions', 'business-hours',
        'email', 'export-contacts', 'export-conv', 'feedback', 'help-center',
        'import-conv', 'internal-note', 'link-customer', 'macro', 'media-panel',
        'merge', 'newconv', 'note', 'notifications', 'orders-list', 'profile-customer',
        'reminder', 'report-incident', 'resolve', 'role-perms', 'schedule',
        'schedule-msg', 'schedule-report', 'sentiment', 'sla-config', 'snooze',
        'supervisor-review', 'tags', 'tickets-panel', 'translate',
    ];
    $bvLazyModalScripts = collect($bvLazyModalNames)->mapWithKeys(function (string $name) {
        $path = "vendor/helpdesk/modals/{$name}.js";
        return [$name => asset($path) . '?v=' . @filemtime(public_path($path))];
    });
@endphp
@push('scripts')
<script>window.BvLazyModalScripts = @json($bvLazyModalScripts);</script>
@endpush

{{-- Modales clave (acciones frecuentes) --}}
@include('helpdesk::helpdesk.inbox.partials.modals.status')
@include('helpdesk::helpdesk.inbox.partials.modals.priority')
@include('helpdesk::helpdesk.inbox.partials.modals.filter')
@include('helpdesk::helpdesk.inbox.partials.modals.edit-contact')
@include('helpdesk::helpdesk.inbox.partials.modals.shortcuts')

{{-- Modales del flujo principal --}}
@include('helpdesk::helpdesk.inbox.partials.modals.newconv')
@include('helpdesk::helpdesk.inbox.partials.modals.assign')
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
@if(helpdesk_integration_enabled() && view()->exists('helpdeskintegration::modals.verify-customer-identity'))
    @include('helpdeskintegration::modals.verify-customer-identity')
@endif

{{-- Modales de búsqueda y composer --}}
@include('helpdesk::helpdesk.inbox.partials.modals.translate')
@include('helpdesk::helpdesk.inbox.partials.modals.attach-file')
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

{{-- Modales de notas internas avanzadas --}}
@include('helpdesk::helpdesk.inbox.partials.modals.internal-note')

{{-- Modales IA --}}
@include('helpdesk::helpdesk.inbox.partials.modals.ai-summary')
@include('helpdesk::helpdesk.inbox.partials.modals.ai-suggest')
@include('helpdesk::helpdesk.inbox.partials.modals.sentiment')

{{-- Modales de incidencias y supervisión.
     supervisor-review queda sin @can: es un agente pidiendo que un
     supervisor revise su conversación, no una acción administrativa — la
     misma helpdesk.conversations.update que ya cubre close/snooze/etc.
     protege el endpoint en el backend (SupervisorReviewController). --}}
@include('helpdesk::helpdesk.inbox.partials.modals.report-incident')
@include('helpdesk::helpdesk.inbox.partials.modals.supervisor-review')

{{-- Modales de administración (roles, SLA, horarios, auto-asignación). --}}
@can("roles.permissions.view")
    @include('helpdesk::helpdesk.inbox.partials.modals.role-perms')
@endcan
@can('helpdesk.sla-policies.view')
    @include('helpdesk::helpdesk.inbox.partials.modals.sla-config')
@endcan
@can('helpdesk.settings.view')
    @include('helpdesk::helpdesk.inbox.partials.modals.business-hours')
@endcan
@can('helpdesk.manage')
    @include('helpdesk::helpdesk.inbox.partials.modals.auto-assign')
@endcan

{{-- Modales de feedback y novedades --}}
@include('helpdesk::helpdesk.inbox.partials.modals.feedback')

{{-- Modales de importación / exportación / reportes --}}
@include('helpdesk::helpdesk.inbox.partials.modals.import-conv')
@include('helpdesk::helpdesk.inbox.partials.modals.schedule-report')
@include('helpdesk::helpdesk.inbox.partials.modals.export-contacts')

{{-- Modales de pedidos y paneles laterales --}}
@include('helpdesk::helpdesk.inbox.partials.modals.orders-list')
@include('helpdesk::helpdesk.inbox.partials.modals.media-panel')
@include('helpdesk::helpdesk.inbox.partials.modals.tickets-panel')

@push('scripts')
<script src="{{ asset('vendor/helpdesk/modals/history.js') }}?v={{ @filemtime(public_path('vendor/helpdesk/modals/history.js')) }}" defer></script>
@endpush
