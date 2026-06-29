{{-- Popover lateral del contacto/cliente. Se abre desde el detalle de un ticket. --}}
<style>
/* [1] Loading skeletons */
.htk-skeleton {
    background: linear-gradient(90deg, #e2e8f0 25%, #f1f5f9 50%, #e2e8f0 75%);
    background-size: 200% 100%;
    animation: htkShimmer 1.4s ease infinite;
    border-radius: 4px;
    height: 12px;
    margin: 6px 0;
}
.htk-skeleton.lg { height: 16px; }
.htk-skeleton.sm { height: 8px; }
.htk-skeleton.w-50 { width: 50%; }
.htk-skeleton.w-75 { width: 75%; }
.htk-skeleton.w-100 { width: 100%; }
@keyframes htkShimmer {
    0%   { background-position: -200% 0; }
    100% { background-position:  200% 0; }
}

/* [6] Customer timeline */
.htk-timeline { position: relative; padding-left: 30px; }
.htk-timeline::before {
    content: '';
    position: absolute;
    left: 12px; top: 0; bottom: 0;
    width: 2px;
    background: #e2e8f0;
}
.htk-timeline-item { position: relative; margin-bottom: 16px; display: flex; gap: 10px; }
.htk-timeline-icon {
    position: absolute;
    left: -24px;
    width: 24px; height: 24px;
    border-radius: 50%;
    color: white;
    display: flex; align-items: center; justify-content: center;
    font-size: 11px;
    flex-shrink: 0;
}
.htk-timeline-title { font-weight: 500; font-size: 12px; }
.htk-timeline-date { color: #71717a; font-size: 11px; margin-top: 2px; }

/* [7] Search */
.htk-search-wrap { padding: 8px 12px 0; position: relative; }
.htk-search-row { display: flex; gap: 6px; }
.htk-search-wrap select { font-size: 11px; padding: 3px 6px; }
.htk-search-wrap input { font-size: 12px; flex: 1; }
#htk-search-results {
    position: absolute;
    left: 12px; right: 12px;
    background: white;
    border: 1px solid #e4e4e7;
    border-radius: 6px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    z-index: 10;
    max-height: 180px;
    overflow-y: auto;
}
.htk-search-item {
    display: block;
    padding: 7px 10px;
    font-size: 12px;
    text-decoration: none;
    color: #18181b;
    border-bottom: 1px solid #f4f4f5;
}
.htk-search-item:hover { background: #f8fafc; }
.htk-search-no-results { padding: 8px 10px; font-size: 12px; color: #a1a1aa; }

/* [12] Debug panel */
#htk-debug-panel {
    position: fixed;
    bottom: 0; right: 0;
    background: #1e293b;
    color: #f1f5f9;
    padding: 8px 10px;
    font-size: 11px;
    font-family: monospace;
    z-index: 9999;
    max-width: 200px;
    border-top-left-radius: 6px;
    line-height: 1.6;
}
</style>

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

    {{-- [7] Search por email/teléfono/NIF --}}
    <div class="htk-search-wrap mb-2">
        <div class="htk-search-row">
            <select id="htk-search-type" class="form-select form-select-sm w-auto flex-shrink-0">
                <option value="email">Email</option>
                <option value="phone">Teléfono</option>
                <option value="nif">NIF</option>
            </select>
            <input id="htk-search-input"
                   type="text"
                   class="form-control form-control-sm"
                   placeholder="Buscar cliente..."
                   autocomplete="off">
        </div>
        <div id="htk-search-results"></div>
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
        <button type="button" class="cnt-tab" data-cnt-tab="erp">ERP</button>
        <button type="button" class="cnt-tab" data-cnt-tab="timeline">Timeline</button>
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
                    <span class="small text-secondary">Cargando…</span>
                </div>
            </div>
        </div>

        {{-- Tickets pane --}}
        <div class="cnt-pane d-none" data-cnt-pane="tickets">
            <div class="cnt-block">
                <div class="h">Histórico</div>
                <div id="htk-cnt-tickets-full">
                    <span class="small text-secondary">Cargando…</span>
                </div>
            </div>
        </div>

        {{-- Notas --}}
        <div class="cnt-pane d-none" data-cnt-pane="notas">
            <div class="htk-empty-state">
                <div class="ic"><i class="far fa-note-sticky"></i></div>
                <div class="t">Notas internas</div>
                <div class="s">Las notas son visibles solo para el equipo.</div>
            </div>
            <div id="htk-notes-list"></div>
        </div>

        {{-- Adjuntos --}}
        <div class="cnt-pane d-none" data-cnt-pane="adjuntos">
            <div class="htk-empty-state">
                <div class="ic"><i class="fas fa-paperclip"></i></div>
                <div class="t">Archivos adjuntos</div>
                <div class="s">Documentos compartidos por el cliente.</div>
            </div>
        </div>

        {{-- Pedidos (Prestashop) --}}
        <div class="cnt-pane d-none" data-cnt-pane="pedidos">
            <div id="htk-cnt-ps-loading" class="htk-empty-state d-none">
                <div class="ic"><i class="fas fa-spinner fa-spin"></i></div>
                <div class="t">Cargando pedidos…</div>
            </div>
            <div id="htk-cnt-ps-empty" class="htk-empty-state d-none">
                <div class="ic"><i class="fas fa-box-open"></i></div>
                <div class="t">Sin pedidos</div>
                <div class="s">Este cliente no tiene pedidos en Prestashop.</div>
            </div>
            <div id="htk-cnt-ps-data" class="d-none">
                <div class="cnt-score mb-2 d-none" id="htk-cnt-ps-score">
                    <div class="blk"><span class="lbl">LTV</span><span class="v" id="htk-cnt-ps-ltv">—</span></div>
                    <div class="sep"></div>
                    <div class="blk"><span class="lbl">Pedidos</span><span class="v" id="htk-cnt-ps-orders-count">—</span></div>
                    <div class="sep"></div>
                    <div class="blk"><span class="lbl">Último</span><span class="v" id="htk-cnt-ps-last-order">—</span></div>
                </div>
                <div class="cnt-block">
                    <div class="h">Pedidos recientes</div>
                    <div id="htk-cnt-ps-orders-list"></div>
                </div>
                <div class="cnt-block d-none" id="htk-cnt-ps-carts-block">
                    <div class="h">Carritos abandonados</div>
                    <div id="htk-cnt-ps-carts-list"></div>
                </div>
            </div>
        </div>

        {{-- ERP --}}
        <div class="cnt-pane d-none" data-cnt-pane="erp">
            <div id="htk-cnt-erp-loading" class="htk-empty-state d-none">
                <div class="ic"><i class="fas fa-spinner fa-spin"></i></div>
                <div class="t">Consultando ERP…</div>
            </div>
            <div id="htk-cnt-erp-empty" class="htk-empty-state d-none">
                <div class="ic"><i class="fas fa-building"></i></div>
                <div class="t">Sin datos en ERP</div>
                <div class="s">Este cliente no está registrado en el ERP.</div>
            </div>
            <div id="htk-cnt-erp-data" class="d-none">
                <div class="cnt-block">
                    <div class="h">Datos comerciales</div>
                    <div class="cnt-row"><span class="lbl">Nombre</span><span class="v" id="htk-erp-name">—</span></div>
                    <div class="cnt-row"><span class="lbl">NIF</span><span class="v mono" id="htk-erp-nif">—</span></div>
                    <div class="cnt-row"><span class="lbl">Límite crédito</span><span class="v" id="htk-erp-credit">—</span></div>
                    <div class="cnt-row"><span class="lbl">Saldo</span><span class="v" id="htk-erp-balance">—</span></div>
                    <div class="cnt-row"><span class="lbl">Forma de pago</span><span class="v" id="htk-erp-terms">—</span></div>
                    <div class="cnt-row"><span class="lbl">Localidad</span><span class="v" id="htk-erp-city">—</span></div>
                </div>
                <div class="cnt-block d-none" id="htk-erp-orders-block">
                    <div class="h">Últimos pedidos ERP</div>
                    <div id="htk-erp-orders-list"></div>
                </div>
            </div>
        </div>

        {{-- [6] Timeline --}}
        <div class="cnt-pane d-none" data-cnt-pane="timeline">
            <div id="htk-cnt-timeline-data" class="cnt-block">
                <div class="htk-empty-state">
                    <div class="ic"><i class="fas fa-clock-rotate-left"></i></div>
                    <div class="t">Historial del cliente</div>
                    <div class="s">Haz clic en la pestaña para cargar el historial.</div>
                </div>
            </div>
        </div>

        {{-- Auditoría --}}
        <div class="cnt-pane d-none" data-cnt-pane="audit">
            <div class="htk-empty-state">
                <div class="ic"><i class="fas fa-shield"></i></div>
                <div class="t">Registro de auditoría</div>
                <div class="s">Cambios y accesos al perfil del cliente.</div>
            </div>
        </div>
    </div>
</aside>
