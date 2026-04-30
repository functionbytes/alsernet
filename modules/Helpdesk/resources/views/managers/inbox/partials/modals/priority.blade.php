{{-- Modal: Cambiar prioridad (paleta grises + rojos, sin amarillo/azul) --}}
<div class="bv-modal" data-bv-modal-name="priority">
    <div class="bv-modal-dialog">
        <div class="bv-modal-head">
            <div class="bv-modal-title"><i class="fas fa-flag bv-modal-title-icon"></i> Cambiar prioridad</div>
            <button class="bv-modal-close" data-bv-close>
                <i class="fas fa-xmark"></i>
            </button>
        </div>
        <div class="bv-modal-body">
            <div class="bv-opt-list" data-bv-opt-group="priority">
                <button class="bv-opt" data-bv-value="low" data-bv-label="Baja" data-bv-color="muted">
                    <span class="dot" style="background:var(--bv-priority-low)"></span>
                    <div class="body">
                        <div class="name">Baja</div>
                        <div class="sub">Sin urgencia · Tiempo de respuesta amplio</div>
                    </div>
                    <i class="fas fa-check check"></i>
                </button>
                <button class="bv-opt" data-bv-value="normal" data-bv-label="Normal" data-bv-color="info">
                    <span class="dot" style="background:var(--bv-priority-normal)"></span>
                    <div class="body">
                        <div class="name">Normal</div>
                        <div class="sub">Tiempo de respuesta estándar</div>
                    </div>
                    <i class="fas fa-check check"></i>
                </button>
                <button class="bv-opt" data-bv-value="high" data-bv-label="Alta" data-bv-color="warning">
                    <span class="dot" style="background:var(--bv-priority-high)"></span>
                    <div class="body">
                        <div class="name">Alta</div>
                        <div class="sub">Requiere atención prioritaria</div>
                    </div>
                    <i class="fas fa-check check"></i>
                </button>
                <button class="bv-opt bv-priority-urgent-opt" data-bv-value="urgent" data-bv-label="Urgente" data-bv-color="danger">
                    <span class="dot bv-priority-dot-urgent"></span>
                    <div class="body">
                        <div class="name bv-priority-urgent-name">Urgente</div>
                        <div class="sub">Atención inmediata · Escalable a supervisor</div>
                    </div>
                    <i class="fas fa-check check"></i>
                </button>
            </div>
        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" data-bv-apply="priority"><i class="fas fa-check"></i> Guardar cambios</button>
            <button class="btn-secondary" data-bv-close>Cancelar</button>
        </div>
    </div>
</div>
