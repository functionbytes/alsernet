{{-- Modal: Detalle de ticket (#22 ve-ticket-detail) --}}
<div class="bv-modal" data-bv-modal-name="ticket">
    <div class="bv-modal-dialog lg">
        <div class="bv-modal-head">
            <div class="bv-modal-title">
                <i class="fas fa-ticket bv-modal-title-icon"></i>
                <div class="bv-tk-head-main">
                    <span class="bv-tk-head-label" id="bv-ticket-modal-num">T-—</span>
                    <span class="bv-tk-head-title" id="bv-ticket-modal-title">—</span>
                </div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">
            <div class="mv4-ticket-meta" id="bv-ticket-modal-pills">
                {{-- Pills (prioridad, estado, categoría) inyectadas vía JS --}}
            </div>

            <div class="bv-tk-section">
                <div class="mv4-sec-title">Descripción</div>
                <div class="bv-tk-text" id="bv-ticket-modal-desc">—</div>
            </div>

            <div class="bv-tk-section">
                <div class="mv4-sec-title">Conversaciones vinculadas</div>
                <div class="bv-tk-link-row">
                    <span class="bv-tk-link-ico">
                        <i class="fas fa-comment-dots"></i>
                    </span>
                    <div class="bv-tk-link-body">
                        <b>Widget · <span id="bv-ticket-modal-cust-name">—</span></b>
                        <span id="bv-ticket-modal-convo-meta">—</span>
                    </div>
                    <button class="bv-btn bv-btn-ghost bv-btn-sm" type="button"><i class="fas fa-arrow-right"></i></button>
                </div>
            </div>

            <div class="bv-tk-section">
                <div class="mv4-sec-title">Cliente</div>
                <div class="bv-tk-cust">
                    <div class="bv-tk-cust-av" id="bv-ticket-modal-avatar">??</div>
                    <div class="bv-tk-cust-body">
                        <b id="bv-ticket-modal-side-name">—</b>
                        <span id="bv-ticket-modal-side-meta">—</span>
                    </div>
                </div>
            </div>

            <div class="bv-tk-section">
                <div class="mv4-sec-title">Detalles</div>
                <div class="info-table">
                    <div class="lbl">Pedido</div>
                    <div class="val" id="bv-ticket-modal-related-order">—</div>
                    <div class="lbl">Asignado</div>
                    <div class="val" id="bv-ticket-modal-assignee">Sin asignar</div>
                    <div class="lbl">Equipo</div>
                    <div class="val" id="bv-ticket-modal-group">—</div>
                    <div class="lbl">Creado</div>
                    <div class="val" id="bv-ticket-modal-created">—</div>
                    <div class="lbl">Última act.</div>
                    <div class="val" id="bv-ticket-modal-updated">—</div>
                    <div class="lbl">Origen</div>
                    <div class="val" id="bv-ticket-modal-origin">—</div>
                    <div class="lbl">Prioridad</div>
                    <div class="val" id="bv-ticket-modal-priority">—</div>
                </div>
            </div>

            <div class="bv-tk-section">
                <div class="mv4-sec-title">Actividad</div>
                <div class="mv4-tl" id="bv-ticket-modal-activity">
                    {{-- Timeline inyectado vía JS (mv4-tl-item) --}}
                </div>
            </div>
        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="bv-ticket-modal-resolve"><i class="fas fa-check"></i> Resolver ticket</button>
            <button class="btn-secondary" data-bv-close>Cerrar</button>
        </div>
    </div>
</div>
