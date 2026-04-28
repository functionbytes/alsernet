{{-- Panel derecho con tabs (Refined v4 / right-panel-v3) --}}
<aside class="bv-right">
    <div class="bv-right-hero">
        <div class="bv-right-cover"></div>
        <div class="bv-right-avatar">CP</div>
        <div class="bv-right-name">Carmen Pérez</div>
        <div class="bv-right-sub">VIP · Cliente desde 2022</div>
        <div class="bv-right-actions">
            <button class="bv-right-action" data-bv-modal="email">
                <i class="far fa-envelope"></i>Email
            </button>
            <button class="bv-right-action" data-bv-modal="schedule">
                <i class="far fa-calendar-plus"></i>Agendar
            </button>
            <button class="bv-right-action" data-bv-modal="note">
                <i class="far fa-pen-to-square"></i>Nota
            </button>
            <button class="bv-right-action">
                <i class="fas fa-ellipsis"></i>Más
            </button>
        </div>
    </div>

    <div class="bv-right-stats">
        <div class="bv-right-stat">
            <div class="val">€2,450</div>
            <div class="lbl">LTV</div>
        </div>
        <div class="bv-right-stat">
            <div class="val">14</div>
            <div class="lbl">Pedidos</div>
        </div>
        <div class="bv-right-stat">
            <div class="val">4.8</div>
            <div class="lbl">CSAT</div>
        </div>
    </div>

    <div class="bv-right-tabs">
        <button class="bv-right-tab on" data-bv-tab="general">General</button>
        <button class="bv-right-tab" data-bv-tab="orders">Pedidos</button>
        <button class="bv-right-tab" data-bv-tab="tickets">Tickets</button>
        <button class="bv-right-tab" data-bv-tab="activity">Actividad</button>
    </div>

    <div class="bv-right-body">
        {{-- Tab: General --}}
        <div class="bv-right-tab-content" data-bv-tab-content="general">
            <div class="bv-right-section">
                <div class="bv-right-section-head">
                    <span class="bv-right-section-title">Información de contacto</span>
                    <button class="bv-right-section-edit" data-bv-modal="edit-contact" title="Editar">
                        <i class="fas fa-pen"></i>
                    </button>
                </div>
                <div class="bv-right-row">
                    <span class="lbl">Email</span>
                    <span class="val">carmen.perez@email.com</span>
                </div>
                <div class="bv-right-row">
                    <span class="lbl">Teléfono</span>
                    <span class="val">+34 612 345 678</span>
                </div>
                <div class="bv-right-row">
                    <span class="lbl">Empresa</span>
                    <span class="val">Boutique Rosa</span>
                </div>
                <div class="bv-right-row">
                    <span class="lbl">Idioma</span>
                    <span class="val">Español</span>
                </div>
                <div class="bv-right-row">
                    <span class="lbl">Zona horaria</span>
                    <span class="val">Europe/Madrid</span>
                </div>
            </div>

            <div class="bv-right-section">
                <div class="bv-right-section-head">
                    <span class="bv-right-section-title">Estado de la conversación</span>
                </div>
                <div class="bv-right-row">
                    <span class="lbl">Estado</span>
                    <button class="bv-th-pill" data-bv-modal="status">
                        <span class="dot" style="background:#059669"></span>Abierta
                        <i class="fas fa-chevron-down" style="font-size:9px"></i>
                    </button>
                </div>
                <div class="bv-right-row">
                    <span class="lbl">Prioridad</span>
                    <button class="bv-th-pill" data-bv-modal="priority">
                        <span class="dot" style="background:#d97706"></span>Alta
                        <i class="fas fa-chevron-down" style="font-size:9px"></i>
                    </button>
                </div>
                <div class="bv-right-row">
                    <span class="lbl">Agente</span>
                    <button class="bv-th-pill" data-bv-modal="assign">
                        <span class="dot" style="background:linear-gradient(135deg,#f97316,#db2777)"></span>María L.
                        <i class="fas fa-chevron-down" style="font-size:9px"></i>
                    </button>
                </div>
                <div class="bv-right-row">
                    <span class="lbl">Equipo</span>
                    <button class="bv-th-pill">
                        <i class="far fa-shop"></i>Ventas
                        <i class="fas fa-chevron-down" style="font-size:9px"></i>
                    </button>
                </div>
            </div>

            <div class="bv-right-section">
                <div class="bv-right-section-head">
                    <span class="bv-right-section-title">Etiquetas</span>
                    <button class="bv-right-section-edit" data-bv-modal="tags">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
                <div style="display:flex;gap:6px;flex-wrap:wrap">
                    <span class="bv-tag" style="background:#fef2f2;color:#dc2626">VIP</span>
                    <span class="bv-tag" style="background:#fffbeb;color:#d97706">Recurrente</span>
                    <span class="bv-tag" style="background:#eff6ff;color:#1d4ed8">B2C</span>
                </div>
            </div>

            <div class="bv-right-section">
                <div class="bv-right-section-head">
                    <span class="bv-right-section-title">Integraciones</span>
                </div>
                <div style="display:flex;flex-direction:column;gap:6px">
                    <div style="display:flex;align-items:center;gap:8px;padding:8px 10px;border:1px solid var(--bv-border);border-radius:8px;font-size:12px">
                        <span style="width:24px;height:24px;border-radius:6px;background:#95bf47;display:grid;place-items:center;color:#fff;font-size:10px;font-weight:700">S</span>
                        <span style="flex:1">Shopify</span>
                        <span style="color:var(--bv-success);font-size:10px">● Conectado</span>
                    </div>
                    <div style="display:flex;align-items:center;gap:8px;padding:8px 10px;border:1px solid var(--bv-border);border-radius:8px;font-size:12px">
                        <span style="width:24px;height:24px;border-radius:6px;background:#ff7a59;display:grid;place-items:center;color:#fff;font-size:10px;font-weight:700">H</span>
                        <span style="flex:1">HubSpot</span>
                        <span style="color:var(--bv-success);font-size:10px">● Conectado</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tab: Pedidos --}}
        <div class="bv-right-tab-content" data-bv-tab-content="orders" style="display:none">
            <div class="bv-right-section">
                <div class="bv-right-section-head">
                    <span class="bv-right-section-title">Últimos pedidos</span>
                </div>
                <div style="display:flex;flex-direction:column;gap:8px">
                    <div style="padding:10px 12px;border:1px solid var(--bv-border);border-radius:10px;cursor:pointer" data-bv-modal="order">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px">
                            <span style="font-weight:600">#1234</span>
                            <span style="font-size:11px;color:var(--bv-success)">● Entregado</span>
                        </div>
                        <div style="font-size:11px;color:var(--bv-text-muted)">€89.00 · 15 abr 2026</div>
                    </div>
                    <div style="padding:10px 12px;border:1px solid var(--bv-border);border-radius:10px;cursor:pointer" data-bv-modal="order">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px">
                            <span style="font-weight:600">#1198</span>
                            <span style="font-size:11px;color:var(--bv-warning)">● En proceso</span>
                        </div>
                        <div style="font-size:11px;color:var(--bv-text-muted)">€124.50 · 12 abr 2026</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tab: Tickets --}}
        <div class="bv-right-tab-content" data-bv-tab-content="tickets" style="display:none">
            <div class="bv-right-section">
                <div class="bv-right-section-head">
                    <span class="bv-right-section-title">Tickets relacionados</span>
                    <button class="bv-right-section-edit"><i class="fas fa-plus"></i></button>
                </div>
                <div style="font-size:12px;color:var(--bv-text-muted);text-align:center;padding:24px">
                    Sin tickets relacionados
                </div>
            </div>
        </div>

        {{-- Tab: Actividad --}}
        <div class="bv-right-tab-content" data-bv-tab-content="activity" style="display:none">
            <div class="bv-right-section">
                <div class="bv-right-section-head">
                    <span class="bv-right-section-title">Timeline</span>
                </div>
                <div style="display:flex;flex-direction:column;gap:10px;font-size:12px">
                    <div style="display:flex;gap:10px;padding-left:16px;border-left:2px solid var(--bv-border);position:relative">
                        <span style="position:absolute;left:-5px;top:5px;width:8px;height:8px;border-radius:50%;background:var(--bv-success)"></span>
                        <div>
                            <div style="font-weight:500">Pedido entregado</div>
                            <div style="color:var(--bv-text-muted);font-size:11px;margin-top:2px">Hace 2 días · #1234</div>
                        </div>
                    </div>
                    <div style="display:flex;gap:10px;padding-left:16px;border-left:2px solid var(--bv-border);position:relative">
                        <span style="position:absolute;left:-5px;top:5px;width:8px;height:8px;border-radius:50%;background:var(--bv-info)"></span>
                        <div>
                            <div style="font-weight:500">Nueva conversación iniciada</div>
                            <div style="color:var(--bv-text-muted);font-size:11px;margin-top:2px">Hace 5 días · WhatsApp</div>
                        </div>
                    </div>
                    <div style="display:flex;gap:10px;padding-left:16px;border-left:2px solid var(--bv-border);position:relative">
                        <span style="position:absolute;left:-5px;top:5px;width:8px;height:8px;border-radius:50%;background:var(--bv-warning)"></span>
                        <div>
                            <div style="font-weight:500">Carrito abandonado recuperado</div>
                            <div style="color:var(--bv-text-muted);font-size:11px;margin-top:2px">Hace 1 semana · €124.50</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</aside>
