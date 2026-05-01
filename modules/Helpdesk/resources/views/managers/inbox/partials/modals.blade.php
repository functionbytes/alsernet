{{-- ═══════════════════════════════════════════════════════════════════════
   Inbox v4 — Modales
   ─────────────────────────────────────────────────────────────────────────
   Cada modal vive en su propio archivo en partials/modals/{name}.blade.php
   Trigger: click en cualquier elemento con data-bv-modal="{name}"
   Cerrar: data-bv-close, click en backdrop, o tecla ESC
   ═══════════════════════════════════════════════════════════════════════ --}}

{{-- Modales clave (acciones frecuentes) --}}
@include('helpdesk::managers.inbox.partials.modals.status')
@include('helpdesk::managers.inbox.partials.modals.priority')
@include('helpdesk::managers.inbox.partials.modals.filter')
@include('helpdesk::managers.inbox.partials.modals.edit-contact')
@include('helpdesk::managers.inbox.partials.modals.shortcuts')

{{-- Modales del flujo principal --}}
@include('helpdesk::managers.inbox.partials.modals.newconv')
@include('helpdesk::managers.inbox.partials.modals.assign')
@include('helpdesk::managers.inbox.partials.modals.move-to-team')
@include('helpdesk::managers.inbox.partials.modals.tags')
@include('helpdesk::managers.inbox.partials.modals.close-conv')
@include('helpdesk::managers.inbox.partials.modals.merge')
@include('helpdesk::managers.inbox.partials.modals.hsm')

{{-- Modales de detalle --}}
@include('helpdesk::managers.inbox.partials.modals.order')
@include('helpdesk::managers.inbox.partials.modals.ticket')
@include('helpdesk::managers.inbox.partials.modals.preview-conv')

{{-- Modales de búsqueda y composer --}}
@include('helpdesk::managers.inbox.partials.modals.customer-search')
@include('helpdesk::managers.inbox.partials.modals.email')
@include('helpdesk::managers.inbox.partials.modals.schedule')
@include('helpdesk::managers.inbox.partials.modals.snooze')
@include('helpdesk::managers.inbox.partials.modals.note')
@include('helpdesk::managers.inbox.partials.modals.mention')
@include('helpdesk::managers.inbox.partials.modals.mention-help')

{{-- Modal: Lightbox de archivos --}}
@include('helpdesk::managers.inbox.partials.modals.file-preview')

{{-- Modal: Compartir tienda de la empresa --}}
@include('helpdesk::managers.inbox.partials.modals.store-picker')

{{-- Modales: Adjuntar (contacto, ubicación) --}}
@include('helpdesk::managers.inbox.partials.modals.attach-contact')
@include('helpdesk::managers.inbox.partials.modals.attach-location')
