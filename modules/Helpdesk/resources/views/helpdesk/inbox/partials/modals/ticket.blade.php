{{-- Modal: Detalle de ticket (#22 ve-ticket-detail)

     Rediseño de sep-2026 sobre el diseño "A · Una sola columna": una sola
     columna que se lee en el orden en que se decide —estado, SLA, cliente, qué
     pide, qué pasó, datos— con la anatomía de los modales de la pantalla de
     tickets (cabecera con kicker + número en chip, secciones sin marcos
     innecesarios, botonera apilada abajo).

     Antes de este rediseño el modal salía ENTERO en blanco: el JS que lo
     rellena escuchaba clics en ".rp3-ticket", una clase que dejó de existir
     cuando el panel derecho pasó a ".tk-card", así que la petición no llegaba
     a salir. Ver el handler en public/vendor/helpdesk/conversations-extras.js.

     Todo lo que se pinta aquí viene de HelpdeskTicketBridgeService::
     getTicketDetail(); los bloques con id bv-tkm-* los rellena el JS y se
     ocultan solos cuando el dato no existe (sin dejar guiones sueltos). --}}
<div class="bv-modal" data-bv-modal-name="ticket">
    <div class="bv-modal-dialog">
        <div class="bv-modal-head bv-tkm-head">
            <div class="bv-tkm-head-icon">
                <i class="fa-solid fa-ticket" aria-hidden="true"></i>
            </div>
            <div class="bv-tkm-head-main">
                <span class="bv-tkm-kicker">{{ __('helpdesk::helpdesk.inbox.modals.ticket_kicker') }}</span>
                <div class="bv-tkm-head-line">
                    <span class="bv-tkm-title" id="bv-ticket-modal-title">—</span>
                    <span class="bv-tkm-num" id="bv-ticket-modal-num">—</span>
                </div>
            </div>
            <button class="bv-modal-close" data-bv-close aria-label="{{ __('helpdesk::helpdesk.inbox.modals.ticket_close') }}"><i class="fas fa-xmark"></i></button>
        </div>

        <div class="bv-modal-body bv-tkm-body">

            {{-- 1 · Estado, prioridad y categoría --}}
            <div class="bv-tkm-pills" id="bv-ticket-modal-pills"></div>

            {{-- 2 · SLA: lo primero que decide si el ticket se abre ahora --}}
            <div class="bv-tkm-sla" id="bv-ticket-modal-sla" hidden>
                <span class="ico"><i class="fa-regular fa-clock" aria-hidden="true"></i></span>
                <div class="body">
                    <span class="lbl" id="bv-ticket-modal-sla-label"></span>
                    <span class="sub" id="bv-ticket-modal-sla-sub"></span>
                </div>
            </div>

            {{-- 3 · Cliente --}}
            <a class="bv-tkm-customer" id="bv-ticket-modal-customer" href="#">
                <span class="av" id="bv-ticket-modal-avatar">—</span>
                <span class="body">
                    <span class="name" id="bv-ticket-modal-side-name">—</span>
                    <span class="mail" id="bv-ticket-modal-side-meta"></span>
                </span>
                <span class="erp" id="bv-ticket-modal-erp" hidden></span>
            </a>

            {{-- 4 · Descripción, recortada con opción de desplegar --}}
            <div class="bv-tkm-section">
                <span class="bv-tkm-sec-title">{{ __('helpdesk::helpdesk.inbox.modals.ticket_description') }}</span>
                <div class="bv-tkm-desc" id="bv-ticket-modal-desc">—</div>
                <button type="button" class="bv-tkm-more" id="bv-ticket-modal-desc-more" hidden>{{ __('helpdesk::helpdesk.inbox.modals.ticket_desc_more') }}</button>
            </div>

            {{-- 5 · Último movimiento del hilo --}}
            <div class="bv-tkm-section" id="bv-ticket-modal-lastmove-wrap" hidden>
                <div class="bv-tkm-sec-head">
                    <span class="bv-tkm-sec-title">{{ __('helpdesk::helpdesk.inbox.modals.ticket_last_move') }}</span>
                    <span class="bv-tkm-count" id="bv-ticket-modal-msgcount"></span>
                </div>
                <div class="bv-tkm-lastmove">
                    <span class="av" id="bv-ticket-modal-lastmove-av">—</span>
                    <div class="body">
                        <span class="text" id="bv-ticket-modal-lastmove-text"></span>
                        <span class="meta" id="bv-ticket-modal-lastmove-meta"></span>
                    </div>
                </div>
            </div>

            {{-- 6 · Detalles en rejilla: la mitad de alto que las siete filas
                 rayadas de antes, y sin las que no tienen valor --}}
            <div class="bv-tkm-section">
                <span class="bv-tkm-sec-title">{{ __('helpdesk::helpdesk.inbox.modals.ticket_details') }}</span>
                <div class="bv-tkm-grid" id="bv-ticket-modal-grid"></div>
            </div>

            {{-- 7 · Etiquetas --}}
            <div class="bv-tkm-tags" id="bv-ticket-modal-tags" hidden></div>

            {{-- 8 · Conversación de origen --}}
            <div class="bv-tkm-convo" id="bv-ticket-modal-convo" hidden>
                <span class="ico"><i class="fa-regular fa-comment-dots" aria-hidden="true"></i></span>
                <div class="body">
                    <span class="name">{{ __('helpdesk::helpdesk.inbox.modals.ticket_source_conversation') }}</span>
                    <span class="meta" id="bv-ticket-modal-convo-meta"></span>
                </div>
                <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
            </div>

            {{-- 9 · Actividad real del ticket --}}
            <div class="bv-tkm-section" id="bv-ticket-modal-activity-wrap" hidden>
                <span class="bv-tkm-sec-title">{{ __('helpdesk::helpdesk.inbox.modals.ticket_activity') }}</span>
                <div class="bv-tkm-timeline" id="bv-ticket-modal-activity"></div>
            </div>

        </div>

        <div class="bv-modal-foot bv-tkm-foot">
            <button class="bv-tkm-btn primary" id="bv-ticket-modal-resolve">{{ __('helpdesk::helpdesk.inbox.modals.ticket_resolve') }}</button>
            <div class="bv-tkm-foot-row">
                <button class="bv-tkm-btn" id="bv-ticket-modal-assign">{{ __('helpdesk::helpdesk.inbox.modals.ticket_assign_me') }}</button>
                <a class="bv-tkm-btn" id="bv-ticket-modal-open" href="#" target="_blank" rel="noopener">{{ __('helpdesk::helpdesk.inbox.modals.ticket_open_full') }}</a>
            </div>
        </div>
    </div>
</div>
