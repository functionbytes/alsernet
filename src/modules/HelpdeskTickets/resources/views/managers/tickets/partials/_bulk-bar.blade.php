{{-- Bulk action bar (oculto por defecto, se muestra al seleccionar tickets) --}}
<div id="htk-bulk" class="htk-bulk">
    <span class="count"><span id="htk-bulk-count">0</span> seleccionados</span>
    <div class="acts">
        @can('helpdesk.tickets.update')
            <button type="button" class="b htk-bulk-action" data-action="assign">
                <i class="fas fa-user-plus"></i> Asignar
            </button>
            <button type="button" class="b htk-bulk-action" data-action="tag">
                <i class="fas fa-tag"></i> Etiquetar
            </button>
        @endcan
        @can('helpdesk.tickets.resolve')
            <button type="button" class="b htk-bulk-action" data-action="resolve">
                <i class="fas fa-check"></i> Resolver
            </button>
        @endcan
        @can('helpdesk.tickets.close')
            <button type="button" class="b htk-bulk-action" data-action="close">
                <i class="fas fa-circle-xmark"></i> Cerrar
            </button>
        @endcan
        @can('helpdesk.tickets.delete')
            <button type="button" class="b htk-bulk-action" data-action="delete">
                <i class="fas fa-trash"></i> Eliminar
            </button>
        @endcan
    </div>
</div>
