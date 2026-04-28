{{-- Modal: Detalle de pedido --}}
<div class="bv-modal" data-bv-modal-name="order">
    <div class="bv-modal-dialog lg">
        <div class="bv-modal-head">
            <div class="bv-modal-title">
                <i class="fas fa-box" style="font-size:14px;margin-right:6px"></i>
                Pedido #1234
                <span style="font-size:11px;font-weight:600;padding:2px 10px;border-radius:10px;background:#d1fae5;color:#065f46;margin-left:8px">Entregado</span>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            {{-- Timeline horizontal --}}
            <div style="margin-bottom:20px">
                <div style="display:flex;align-items:flex-start;justify-content:space-between;position:relative;padding:0 10px">
                    {{-- Progress line --}}
                    <div style="position:absolute;top:12px;left:20px;right:20px;height:2px;background:var(--bv-border);z-index:0">
                        <div style="width:100%;height:100%;background:#10b981"></div>
                    </div>

                    @foreach([
                        ['Creado', '15 abr 10:23'],
                        ['Pagado', '15 abr 10:25'],
                        ['Preparando', '15 abr 14:12'],
                        ['Enviado', '16 abr 09:00'],
                        ['Entregado', '17 abr 11:45'],
                    ] as $step)
                    <div style="display:flex;flex-direction:column;align-items:center;gap:6px;z-index:1;flex:1">
                        <div style="width:24px;height:24px;border-radius:50%;background:#10b981;border:2px solid #fff;box-shadow:0 0 0 2px #10b981;display:flex;align-items:center;justify-content:center">
                            <i class="fas fa-check" style="color:#fff;font-size:9px"></i>
                        </div>
                        <div style="text-align:center">
                            <div style="font-size:11px;font-weight:600;color:var(--bv-text)">{{ $step[0] }}</div>
                            <div style="font-size:10px;color:var(--bv-text-muted)">{{ $step[1] }}</div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Grid: products + side --}}
            <div style="display:grid;grid-template-columns:1fr 220px;gap:16px">

                {{-- Left --}}
                <div>
                    {{-- Products --}}
                    <div class="bv-right-section-title" style="margin-bottom:8px">Productos</div>
                    <div style="display:flex;flex-direction:column;gap:8px;margin-bottom:16px">
                        <div style="display:flex;align-items:center;gap:12px;padding:10px;background:var(--bv-bg-subtle);border-radius:10px">
                            <div style="width:44px;height:44px;border-radius:8px;background:var(--bv-bg-panel);border:1px solid var(--bv-border);display:flex;align-items:center;justify-content:center;flex-shrink:0">
                                <i class="fas fa-shoe-prints" style="color:var(--bv-text-muted);font-size:16px"></i>
                            </div>
                            <div style="flex:1">
                                <div style="font-size:12.5px;font-weight:600;color:var(--bv-text)">Zapatillas Nike Air Max 270</div>
                                <div style="font-size:11px;color:var(--bv-text-muted)">SKU: NK-AM270-42 · Talla 42 · Negro</div>
                            </div>
                            <div style="font-size:12px;color:var(--bv-text-muted)">×1</div>
                            <div style="font-size:13px;font-weight:600;color:var(--bv-text)">€89.00</div>
                        </div>
                        <div style="display:flex;align-items:center;gap:12px;padding:10px;background:var(--bv-bg-subtle);border-radius:10px">
                            <div style="width:44px;height:44px;border-radius:8px;background:var(--bv-bg-panel);border:1px solid var(--bv-border);display:flex;align-items:center;justify-content:center;flex-shrink:0">
                                <i class="fas fa-shirt" style="color:var(--bv-text-muted);font-size:16px"></i>
                            </div>
                            <div style="flex:1">
                                <div style="font-size:12.5px;font-weight:600;color:var(--bv-text)">Calcetines deportivos (pack 3)</div>
                                <div style="font-size:11px;color:var(--bv-text-muted)">SKU: CALC-DEP-3 · Talla M</div>
                            </div>
                            <div style="font-size:12px;color:var(--bv-text-muted)">×1</div>
                            <div style="font-size:13px;font-weight:600;color:var(--bv-text)">€12.90</div>
                        </div>
                    </div>

                    {{-- Totals --}}
                    <div style="border-top:1px solid var(--bv-border);padding-top:12px;display:flex;flex-direction:column;gap:6px">
                        <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--bv-text-muted)">
                            <span>Subtotal</span><span>€101.90</span>
                        </div>
                        <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--bv-text-muted)">
                            <span>IVA (21%)</span><span>€21.40</span>
                        </div>
                        <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--bv-text-muted)">
                            <span>Envío</span><span style="color:#10b981;font-weight:600">Gratis</span>
                        </div>
                        <div style="display:flex;justify-content:space-between;font-size:14px;font-weight:700;color:var(--bv-text);padding-top:6px;border-top:1px solid var(--bv-border)">
                            <span>Total</span><span>€123.30</span>
                        </div>
                    </div>

                    {{-- Tracking --}}
                    <div class="bv-right-section-title" style="margin-top:16px;margin-bottom:8px">Número de seguimiento</div>
                    <div style="display:flex;align-items:center;gap:8px;padding:10px 12px;background:var(--bv-bg-subtle);border-radius:10px">
                        <i class="fas fa-truck" style="color:var(--bv-text-muted)"></i>
                        <span style="font-size:12.5px">Nacex · <strong>ES1234-NC</strong></span>
                        <button type="button" style="margin-left:auto;background:none;border:none;padding:4px 8px;cursor:pointer;border-radius:6px;font-size:11px;color:var(--bv-text-muted)">
                            <i class="far fa-copy"></i> Copiar
                        </button>
                        <a href="#" style="font-size:11px;font-weight:600;color:var(--bv-accent);text-decoration:none">
                            <i class="fas fa-arrow-up-right-from-square" style="font-size:9px"></i> Rastrear
                        </a>
                    </div>
                </div>

                {{-- Right sidebar --}}
                <aside>
                    <div style="display:flex;flex-direction:column;gap:14px">

                        {{-- Customer --}}
                        <div>
                            <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--bv-text-muted);margin-bottom:6px">Cliente</div>
                            <div style="display:flex;align-items:center;gap:8px">
                                <div class="bv-av c1">CP</div>
                                <div>
                                    <div style="font-size:12.5px;font-weight:600">Carmen Pérez</div>
                                    <div style="font-size:11px;color:var(--bv-text-muted)">carmen@email.com</div>
                                </div>
                            </div>
                        </div>

                        {{-- Payment --}}
                        <div>
                            <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--bv-text-muted);margin-bottom:6px">Pago</div>
                            <div style="display:flex;align-items:center;gap:8px">
                                <i class="fab fa-cc-visa" style="font-size:20px;color:#1a1f71"></i>
                                <div>
                                    <div style="font-size:12px;font-weight:600">Visa ••4242</div>
                                    <div style="font-size:11px;color:var(--bv-text-muted)">Pagado · 15 abr</div>
                                </div>
                            </div>
                        </div>

                        {{-- Shipping address --}}
                        <div>
                            <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--bv-text-muted);margin-bottom:6px">Dirección de envío</div>
                            <div style="font-size:12px;color:var(--bv-text);line-height:1.6">
                                Calle Mayor 42, 3ºB<br>
                                28013 Madrid · España
                            </div>
                        </div>

                        {{-- Quick actions --}}
                        <div>
                            <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--bv-text-muted);margin-bottom:6px">Acciones</div>
                            <div style="display:flex;flex-direction:column;gap:5px">
                                <button type="button" style="display:flex;align-items:center;gap:7px;padding:7px 10px;background:var(--bv-bg-subtle);border:1px solid var(--bv-border);border-radius:8px;font-size:11.5px;cursor:pointer;color:var(--bv-text);width:100%">
                                    <i class="far fa-envelope" style="width:14px"></i> Reenviar confirmación
                                </button>
                                <button type="button" style="display:flex;align-items:center;gap:7px;padding:7px 10px;background:var(--bv-bg-subtle);border:1px solid var(--bv-border);border-radius:8px;font-size:11.5px;cursor:pointer;color:var(--bv-text);width:100%">
                                    <i class="fas fa-receipt" style="width:14px"></i> Descargar factura
                                </button>
                                <button type="button" style="display:flex;align-items:center;gap:7px;padding:7px 10px;background:var(--bv-bg-subtle);border:1px solid var(--bv-border);border-radius:8px;font-size:11.5px;cursor:pointer;color:#ef4444;width:100%">
                                    <i class="fas fa-rotate-left" style="width:14px"></i> Solicitar reembolso
                                </button>
                            </div>
                        </div>

                    </div>
                </aside>

            </div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary"><i class="fas fa-arrow-up-right-from-square"></i> Seguir envío</button>
            <button class="btn-secondary"><i class="fas fa-rotate-left"></i> Solicitar devolución</button>
            <button class="btn-secondary"><i class="fas fa-file-invoice"></i> Descargar factura</button>
        </div>
    </div>
</div>
