{{-- Modal: Atajos de teclado --}}
<div class="bv-modal" data-bv-modal-name="shortcuts">
    <div class="bv-modal-dialog">
        <div class="bv-modal-head">
            <div class="bv-modal-title">Atajos de teclado</div>
            <button class="bv-modal-close" data-bv-close>
                <i class="fas fa-xmark"></i>
            </button>
        </div>
        <div class="bv-modal-body">
            <div style="display:flex;flex-direction:column;gap:16px">
                <div>
                    <div class="bv-right-section-title" style="margin-bottom:8px">Navegación</div>
                    <div style="display:flex;flex-direction:column;gap:6px">
                        @foreach([['J / ↓','Siguiente conversación'],['K / ↑','Conversación anterior'],['⌘K','Buscar'],['?','Mostrar atajos']] as $sc)
                            <div style="display:flex;align-items:center;justify-content:space-between;padding:6px 10px;background:var(--bv-bg-subtle);border-radius:7px;font-size:12px">
                                <span>{{ $sc[1] }}</span>
                                <kbd style="background:var(--bv-bg-panel);border:1px solid var(--bv-border);border-radius:5px;padding:2px 8px;font-family:'JetBrains Mono',monospace;font-size:11px">{{ $sc[0] }}</kbd>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div>
                    <div class="bv-right-section-title" style="margin-bottom:8px">Acciones</div>
                    <div style="display:flex;flex-direction:column;gap:6px">
                        @foreach([['R','Responder'],['N','Nota interna'],['A','Asignar'],['T','Etiquetar'],['#','Cerrar conversación'],['S','Cambiar estado'],['P','Cambiar prioridad'],['F','Filtrar']] as $sc)
                            <div style="display:flex;align-items:center;justify-content:space-between;padding:6px 10px;background:var(--bv-bg-subtle);border-radius:7px;font-size:12px">
                                <span>{{ $sc[1] }}</span>
                                <kbd style="background:var(--bv-bg-panel);border:1px solid var(--bv-border);border-radius:5px;padding:2px 8px;font-family:'JetBrains Mono',monospace;font-size:11px">{{ $sc[0] }}</kbd>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
