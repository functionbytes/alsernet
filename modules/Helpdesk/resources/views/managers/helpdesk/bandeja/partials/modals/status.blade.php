{{-- Modal: Cambiar estado de la conversación --}}
<div class="bv-modal" data-bv-modal-name="status">
    <div class="bv-modal-dialog">
        <div class="bv-modal-head">
            <div class="bv-modal-title">Cambiar estado</div>
            <button class="bv-modal-close" data-bv-close>
                <i class="fas fa-xmark"></i>
            </button>
        </div>
        <div class="bv-modal-body">
            <div class="bv-opt-list">
                <button class="bv-opt on">
                    <span class="dot" style="background:var(--bv-success)"></span>
                    <div class="body">
                        <div class="name">Abierta</div>
                        <div class="sub">Conversación activa, esperando respuesta</div>
                    </div>
                    <i class="fas fa-check check"></i>
                </button>
                <button class="bv-opt">
                    <span class="dot" style="background:var(--bv-warning)"></span>
                    <div class="body">
                        <div class="name">En espera</div>
                        <div class="sub">Esperando información del cliente o terceros</div>
                    </div>
                    <i class="fas fa-check check"></i>
                </button>
                <button class="bv-opt">
                    <span class="dot" style="background:var(--bv-text-muted)"></span>
                    <div class="body">
                        <div class="name">Cerrada</div>
                        <div class="sub">Resuelta o sin respuesta del cliente</div>
                    </div>
                    <i class="fas fa-check check"></i>
                </button>
                <button class="bv-opt">
                    <span class="dot" style="background:var(--bv-danger)"></span>
                    <div class="body">
                        <div class="name">Spam</div>
                        <div class="sub">Marcar como spam y archivar</div>
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
