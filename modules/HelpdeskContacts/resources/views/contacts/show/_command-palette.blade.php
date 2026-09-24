{{-- Buscador de acciones (tecla «.») de la ficha: lista las acciones de la
     cabecera y del menú "···" que el agente puede usar, y las ejecuta
     pulsando el botón original (mismos permisos, mismo comportamiento). --}}
<div class="modal fade ct-mdl ct-mdl-md" id="c3l-palette" tabindex="-1" aria-labelledby="c3l-palette-label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <div class="flex-grow-1">
                    <div class="ct-modal-eyebrow">{{ $customer->name ?: 'Contacto' }}</div>
                    <h5 class="modal-title" id="c3l-palette-label">Acciones</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <label class="visually-hidden" for="c3l-palette-input">Buscar una acción</label>
                <input type="text" id="c3l-palette-input" class="ct-finput" placeholder="Escribe una acción…" autocomplete="off">
                <div class="c3l-palette-list" id="c3l-palette-list" role="listbox" aria-label="Acciones"></div>
            </div>
            <div class="c3l-palette-foot">
                <span><kbd>↑</kbd> <kbd>↓</kbd> elegir</span>
                <span><kbd>↵</kbd> ejecutar</span>
                <span><kbd>esc</kbd> cerrar</span>
            </div>
        </div>
    </div>
</div>
