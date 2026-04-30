{{-- Bulk action bar — shown when one or more conversations are selected --}}
<div class="bv-bulk-bar bv-hidden" id="bv-bulk-bar">
    <div class="bv-bulk-bar-info">
        <span id="bv-bulk-count">0</span> seleccionadas
        <button class="bv-bulk-deselect" id="bv-bulk-deselect" title="Cancelar selección">
            <i class="fas fa-xmark"></i>
        </button>
    </div>
    <div class="bv-bulk-bar-actions">
        <button class="bv-bulk-btn" data-bv-bulk-action="archive" title="Archivar seleccionadas">
            <i class="fas fa-box-archive"></i> Archivar
        </button>
        <button class="bv-bulk-btn" data-bv-bulk-action="close" title="Cerrar seleccionadas">
            <i class="fas fa-check"></i> Cerrar
        </button>
        <button class="bv-bulk-btn" data-bv-bulk-action="mark_read" title="Marcar como leído">
            <i class="fas fa-envelope-open"></i> Marcar leído
        </button>
        <button class="bv-bulk-btn" data-bv-bulk-action="assign" title="Asignar seleccionadas" data-bv-modal="assign">
            <i class="fas fa-user-plus"></i> Asignar
        </button>
    </div>
</div>
