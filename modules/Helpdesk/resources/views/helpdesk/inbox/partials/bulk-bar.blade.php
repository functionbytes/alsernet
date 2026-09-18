{{-- Bulk action bar — shown when one or more conversations are selected --}}
<div class="bv-bulk-bar bv-hidden" id="bv-bulk-bar">
    <div class="bv-bulk-bar-info">
        <span id="bv-bulk-count">0</span> seleccionadas
        <button class="bv-bulk-deselect" id="bv-bulk-deselect" title="Cancelar selección" aria-label="Cancelar selección">
            <i class="fas fa-xmark" aria-hidden="true"></i>
        </button>
    </div>
    <div class="bv-bulk-bar-actions">
        <button class="bv-bulk-btn" data-bv-bulk-action="archive" title="Archivar seleccionadas">
            Archivar
        </button>
        <button class="bv-bulk-btn" data-bv-bulk-action="close" title="Cerrar seleccionadas">
            Cerrar
        </button>
        <button class="bv-bulk-btn" data-bv-bulk-action="mark_read" title="Marcar como leído">
            Marcar leído
        </button>
        <div class="dropdown d-inline-block" id="bv-bulk-assign-wrap">
            <button class="bv-bulk-btn dropdown-toggle" type="button" id="bv-bulk-assign-toggle"
                    data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false"
                    title="Asignar seleccionadas">
                Asignar
            </button>
            <div class="dropdown-menu dropdown-menu-end p-0" id="bv-bulk-assign-menu" aria-labelledby="bv-bulk-assign-toggle">
                <div class="dropdown-item-text text-muted small px-3 py-2" id="bv-bulk-assign-loading">
                    Cargando agentes…
                </div>
            </div>
        </div>
        @can('helpdesk.macros.use')
        <div class="dropdown d-inline-block" id="bv-bulk-macro-wrap">
            <button class="bv-bulk-btn dropdown-toggle" type="button" id="bv-bulk-macro-toggle"
                    data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false"
                    title="Aplicar macro a las seleccionadas">
                Aplicar macro
            </button>
            <div class="dropdown-menu dropdown-menu-end p-0" id="bv-bulk-macro-menu" aria-labelledby="bv-bulk-macro-toggle">
                <div class="dropdown-item-text text-muted small px-3 py-2" id="bv-bulk-macro-loading">
                    Cargando macros…
                </div>
            </div>
        </div>
        @endcan
        <button class="bv-bulk-btn" data-bv-modal="bulk-actions" title="Más acciones masivas">
            Más acciones
        </button>
    </div>
</div>

{{-- JS: ver public/vendor/helpdesk/conversations-list.js
     (sección "Bulk bar: contador de seleccionadas, acciones en bloque y macros"),
     ya cargado globalmente por index.blade.php — no requiere <script> aquí. --}}
