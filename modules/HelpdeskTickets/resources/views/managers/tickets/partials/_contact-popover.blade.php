{{-- Popover lateral del contacto/cliente. Se abre desde el detalle de un ticket. --}}
<aside class="htk-contact-pop" id="htk-contact-pop" role="dialog" aria-label="Detalle del contacto">

    {{-- Hero --}}
    <div class="cnt-hero">
        <button type="button" class="close" id="htk-cnt-close" aria-label="Cerrar">
            <i class="fas fa-xmark"></i>
        </button>
        <div id="htk-cnt-av" class="htk-av c1">—</div>
        <div id="htk-cnt-name" class="nm">—</div>
        <div id="htk-cnt-email" class="em">—</div>
        <div class="badges">
            <span class="htk-badge resolved">Cliente activo</span>
        </div>
    </div>

    {{-- Score --}}
    <div class="cnt-score">
        <div class="blk">
            <span class="lbl">LTV</span>
            <span class="v">—</span>
        </div>
        <div class="sep"></div>
        <div class="blk">
            <span class="lbl">Tickets</span>
            <span class="v" id="htk-cnt-tickets-count">0</span>
        </div>
        <div class="sep"></div>
        <div class="blk">
            <span class="lbl">CSAT</span>
            <span class="v good">—</span>
        </div>
        <div class="sep"></div>
        <div class="blk">
            <span class="lbl">Riesgo</span>
            <span class="v warn">—</span>
        </div>
    </div>

    {{-- Quick actions --}}
    <div class="cnt-quick">
        <button type="button" class="b"><i class="fas fa-message"></i>Mensaje</button>
        <button type="button" class="b"><i class="fas fa-phone"></i>Llamar</button>
        <button type="button" class="b"><i class="fas fa-envelope"></i>Email</button>
        <button type="button" class="b"><i class="fas fa-ticket"></i>Ticket</button>
    </div>

    {{-- Tabs --}}
    <div class="cnt-tabs">
        <button type="button" class="cnt-tab on" data-cnt-tab="resumen">Resumen</button>
        <button type="button" class="cnt-tab" data-cnt-tab="tickets">Tickets</button>
        <button type="button" class="cnt-tab" data-cnt-tab="notas">Notas</button>
        <button type="button" class="cnt-tab" data-cnt-tab="adjuntos">Adjuntos</button>
        <button type="button" class="cnt-tab" data-cnt-tab="pedidos">Pedidos</button>
        <button type="button" class="cnt-tab" data-cnt-tab="audit">Auditoría</button>
    </div>

    {{-- Body --}}
    <div class="cnt-body">
        {{-- Resumen --}}
        <div class="cnt-pane" data-cnt-pane="resumen">
            <div class="cnt-block">
                <div class="h">Datos de contacto</div>
                <div class="cnt-row"><span class="lbl">Email</span><span class="v mono" id="htk-cnt-r-email">—</span></div>
                <div class="cnt-row"><span class="lbl">Teléfono</span><span class="v mono">—</span></div>
                <div class="cnt-row"><span class="lbl">Empresa</span><span class="v">—</span></div>
                <div class="cnt-row"><span class="lbl">Cliente desde</span><span class="v">—</span></div>
            </div>

            <div class="cnt-block">
                <div class="h">Tickets recientes</div>
                <div id="htk-cnt-tickets-list">
                    <span style="font-size:11px;color:#a1a1aa">Cargando…</span>
                </div>
            </div>
        </div>

        {{-- Tickets pane --}}
        <div class="cnt-pane" data-cnt-pane="tickets" style="display:none">
            <div class="cnt-block">
                <div class="h">Histórico</div>
                <div id="htk-cnt-tickets-full">
                    <span style="font-size:11px;color:#a1a1aa">Cargando…</span>
                </div>
            </div>
        </div>

        {{-- Otros panes (placeholders) --}}
        <div class="cnt-pane" data-cnt-pane="notas" style="display:none">
            <div class="htk-empty-state">
                <div class="ic"><i class="far fa-note-sticky"></i></div>
                <div class="t">Notas internas</div>
                <div class="s">Las notas son visibles solo para el equipo.</div>
            </div>
        </div>
        <div class="cnt-pane" data-cnt-pane="adjuntos" style="display:none">
            <div class="htk-empty-state">
                <div class="ic"><i class="fas fa-paperclip"></i></div>
                <div class="t">Archivos adjuntos</div>
                <div class="s">Documentos compartidos por el cliente.</div>
            </div>
        </div>
        <div class="cnt-pane" data-cnt-pane="pedidos" style="display:none">
            <div class="htk-empty-state">
                <div class="ic"><i class="fas fa-box"></i></div>
                <div class="t">Pedidos del cliente</div>
                <div class="s">Integración con e-commerce próximamente.</div>
            </div>
        </div>
        <div class="cnt-pane" data-cnt-pane="audit" style="display:none">
            <div class="htk-empty-state">
                <div class="ic"><i class="fas fa-shield"></i></div>
                <div class="t">Registro de auditoría</div>
                <div class="s">Cambios y accesos al perfil del cliente.</div>
            </div>
        </div>
    </div>
</aside>
