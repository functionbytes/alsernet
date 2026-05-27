{{-- Modal: Detalle de ticket --}}
<div class="bv-modal" data-bv-modal-name="ticket">
    <div class="bv-modal-dialog xl">
        <div class="bv-modal-head">
            <div class="bv-modal-title">
                <i class="fad fa-ticket bv-modal-title-icon"></i>
                <span id="bv-ticket-modal-num">T-—</span>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">
            <div class="mv4-ticket-grid">
                <div class="mv4-ticket-main">
                    <div class="mv4-ticket-head">
                        <h3 id="bv-ticket-modal-title">—</h3>
                        <div class="mv4-ticket-meta" id="bv-ticket-modal-pills">
                            {{-- Se rellena vía JS --}}
                        </div>
                    </div>

                    <div class="mv4-section">
                        <div class="mv4-sec-title">Descripción</div>
                        <div class="mv4-text" id="bv-ticket-modal-desc">—</div>
                    </div>

                    <div class="mv4-section">
                        <div class="mv4-sec-title">Conversaciones vinculadas</div>
                        <div class="mv4-link-row">
                            <span class="ico bv-text-primary">
                                <i class="fas fa-comment-dots"></i>
                            </span>
                            <div class="body">
                                <b>Widget · <span id="bv-ticket-modal-cust-name">—</span></b>
                                <span id="bv-ticket-modal-convo-meta">—</span>
                            </div>
                            <button class="bv-btn bv-btn-ghost bv-btn-sm"><i class="fas fa-arrow-right"></i></button>
                        </div>
                    </div>

                    <div class="mv4-section">
                        <div class="mv4-sec-title">Actividad</div>
                        <div class="mv4-tl" id="bv-ticket-modal-activity">
                            {{-- Se rellena vía JS --}}
                        </div>
                    </div>
                </div>

                <aside class="mv4-ticket-side">
                    <div class="mv4-side-block">
                        <div class="lbl">Cliente</div>
                        <div class="mv4-cust">
                            <div class="av c1" id="bv-ticket-modal-avatar">??</div>
                            <div>
                                <b id="bv-ticket-modal-side-name">—</b>
                                <span id="bv-ticket-modal-side-meta">—</span>
                            </div>
                        </div>
                    </div>
                    <div class="mv4-side-block">
                        <div class="lbl">Pedido relacionado</div>
                        <div class="mv4-rel">
                            <i class="fas fa-box"></i>
                            <div>
                                <b id="bv-ticket-modal-related-order">—</b>
                                <span>—</span>
                            </div>
                        </div>
                    </div>
                    <div class="mv4-side-row">
                        <span>Asignado</span>
                        <b id="bv-ticket-modal-assignee">Sin asignar</b>
                    </div>
                    <div class="mv4-side-row">
                        <span>Equipo</span>
                        <b id="bv-ticket-modal-group">—</b>
                    </div>
                    <div class="mv4-side-row">
                        <span>Creado</span>
                        <b id="bv-ticket-modal-created">—</b>
                    </div>
                    <div class="mv4-side-row">
                        <span>Última act.</span>
                        <b id="bv-ticket-modal-updated">—</b>
                    </div>
                    <div class="mv4-side-row">
                        <span>Origen</span>
                        <b id="bv-ticket-modal-origin">—</b>
                    </div>
                    <div class="mv4-side-row">
                        <span>Prioridad</span>
                        <b id="bv-ticket-modal-priority">—</b>
                    </div>
                </aside>
            </div>
        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="bv-ticket-modal-resolve"><i class="fas fa-check"></i> Resolver ticket</button>
            <button class="btn-secondary" data-bv-close>Cerrar</button>
        </div>
    </div>
</div>
