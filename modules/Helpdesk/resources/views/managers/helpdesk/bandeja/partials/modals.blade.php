{{-- ═══════════════════════════════════════════════════════════════════════
   Bandeja v4 — Modales
   ─────────────────────────────────────────────────────────────────────────
   Cada modal vive en su propio archivo en partials/modals/{name}.blade.php
   Trigger: click en cualquier elemento con data-bv-modal="{name}"
   Cerrar: data-bv-close, click en backdrop, o tecla ESC
   ═══════════════════════════════════════════════════════════════════════ --}}

{{-- Modales clave (acciones frecuentes) --}}
@include('helpdesk::managers.helpdesk.bandeja.partials.modals.status')
@include('helpdesk::managers.helpdesk.bandeja.partials.modals.priority')
@include('helpdesk::managers.helpdesk.bandeja.partials.modals.filter')
@include('helpdesk::managers.helpdesk.bandeja.partials.modals.edit-contact')
@include('helpdesk::managers.helpdesk.bandeja.partials.modals.shortcuts')

{{-- Modales del flujo principal --}}
@include('helpdesk::managers.helpdesk.bandeja.partials.modals.newconv')
@include('helpdesk::managers.helpdesk.bandeja.partials.modals.assign')
@include('helpdesk::managers.helpdesk.bandeja.partials.modals.tags')
@include('helpdesk::managers.helpdesk.bandeja.partials.modals.close-conv')
@include('helpdesk::managers.helpdesk.bandeja.partials.modals.merge')
@include('helpdesk::managers.helpdesk.bandeja.partials.modals.hsm')

{{-- Modales de detalle --}}
@include('helpdesk::managers.helpdesk.bandeja.partials.modals.order')
@include('helpdesk::managers.helpdesk.bandeja.partials.modals.ticket')
@include('helpdesk::managers.helpdesk.bandeja.partials.modals.preview-conv')

{{-- Modales de búsqueda y composer --}}
@include('helpdesk::managers.helpdesk.bandeja.partials.modals.customer-search')
@include('helpdesk::managers.helpdesk.bandeja.partials.modals.email')
@include('helpdesk::managers.helpdesk.bandeja.partials.modals.schedule')
@include('helpdesk::managers.helpdesk.bandeja.partials.modals.snooze')
@include('helpdesk::managers.helpdesk.bandeja.partials.modals.note')
