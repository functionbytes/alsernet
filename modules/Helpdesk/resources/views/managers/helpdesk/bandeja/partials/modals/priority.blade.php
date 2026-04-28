{{-- Modal: Cambiar prioridad (paleta grises + rojos, sin amarillo/azul) --}}
<div class="bv-modal" data-bv-modal-name="priority">
    <div class="bv-modal-dialog">
        <div class="bv-modal-head">
            <div class="bv-modal-title">Cambiar prioridad</div>
            <button class="bv-modal-close" data-bv-close>
                <i class="fas fa-xmark"></i>
            </button>
        </div>
        <div class="bv-modal-body">
            <div class="bv-opt-list">
                <button class="bv-opt">
                    <span class="dot" style="background:var(--bv-priority-low)"></span>
                    <div class="body">
                        <div class="name">Baja</div>
                        <div class="sub">Sin urgencia · Tiempo de respuesta amplio</div>
                    </div>
                    <i class="fas fa-check check"></i>
                </button>
                <button class="bv-opt">
                    <span class="dot" style="background:var(--bv-priority-normal)"></span>
                    <div class="body">
                        <div class="name">Normal</div>
                        <div class="sub">Tiempo de respuesta estándar</div>
                    </div>
                    <i class="fas fa-check check"></i>
                </button>
                <button class="bv-opt on">
                    <span class="dot" style="background:var(--bv-priority-high)"></span>
                    <div class="body">
                        <div class="name">Alta</div>
                        <div class="sub">Requiere atención prioritaria</div>
                    </div>
                    <i class="fas fa-check check"></i>
                </button>
                <button class="bv-opt" style="background:var(--bv-priority-urgent-bg)">
                    <span class="dot" style="background:var(--bv-priority-urgent);box-shadow:0 0 0 3px var(--bv-priority-urgent-glow)"></span>
                    <div class="body">
                        <div class="name" style="color:var(--bv-priority-urgent)">Urgente</div>
                        <div class="sub">Atención inmediata · Escalable a supervisor</div>
                    </div>
                    <i class="fas fa-check check"></i>
                </button>
            </div>
        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary">Guardar cambios</button>
            <button class="btn-secondary" data-bv-close>Cancelar</button>
        </div>
    </div>
</div>
