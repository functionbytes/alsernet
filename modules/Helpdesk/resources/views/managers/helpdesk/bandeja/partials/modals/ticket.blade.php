{{-- Modal: Detalle de ticket --}}
<div class="bv-modal" data-bv-modal-name="ticket">
    <div class="bv-modal-dialog lg">
        <div class="bv-modal-head">
            <div class="bv-modal-title">
                <i class="fas fa-ticket" style="font-size:14px;margin-right:6px"></i>
                Ticket #T-2026-001
                <span style="font-size:11px;font-weight:600;padding:2px 10px;border-radius:10px;background:#fef2f2;color:#b91c1c;margin-left:6px">Urgente</span>
                <span style="font-size:11px;font-weight:600;padding:2px 10px;border-radius:10px;background:#eff6ff;color:#1d4ed8;margin-left:4px">Abierto</span>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            <div style="display:grid;grid-template-columns:1fr 200px;gap:18px">

                {{-- Left (70%) --}}
                <div>
                    {{-- Title --}}
                    <div style="font-size:16px;font-weight:700;color:var(--bv-text);margin-bottom:6px">
                        Pedido extraviado — solicitud de reenvío urgente
                    </div>

                    {{-- Meta pills --}}
                    <div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:14px">
                        <span class="bv-th-pill"><span style="width:8px;height:8px;border-radius:50%;background:#ef4444;display:inline-block;margin-right:4px"></span>Urgente</span>
                        <span class="bv-th-pill"><span style="width:8px;height:8px;border-radius:50%;background:#3b82f6;display:inline-block;margin-right:4px"></span>Abierto</span>
                        <span class="bv-th-pill">Soporte N1</span>
                        <span class="bv-th-pill" style="font-family:monospace">SLA: 1h 24m</span>
                    </div>

                    {{-- Description --}}
                    <div class="bv-right-section-title" style="margin-bottom:6px">Descripción</div>
                    <div style="font-size:12.5px;color:var(--bv-text);line-height:1.65;background:var(--bv-bg-subtle);border-radius:10px;padding:12px;margin-bottom:16px">
                        Cliente reporta que su pedido #1234 con tracking ES1234-NC fue marcado como entregado pero no lo recibió.
                        El conserje del edificio confirma que no llegó ningún paquete. Solicita reenvío urgente o reembolso antes del viernes.
                    </div>

                    {{-- Linked conversations --}}
                    <div class="bv-right-section-title" style="margin-bottom:8px">Conversaciones vinculadas</div>
                    <div style="display:flex;flex-direction:column;gap:6px;margin-bottom:16px">
                        <div style="display:flex;align-items:center;gap:10px;padding:10px 12px;background:var(--bv-bg-subtle);border-radius:10px">
                            <i class="fab fa-whatsapp" style="color:#25d366;font-size:16px;flex-shrink:0"></i>
                            <div style="flex:1">
                                <div style="font-size:12px;font-weight:600">WhatsApp · Carmen Pérez</div>
                                <div style="font-size:11px;color:var(--bv-text-muted)">Hoy 14:23 · 8 mensajes</div>
                            </div>
                            <button type="button" style="background:none;border:none;padding:4px 6px;cursor:pointer;color:var(--bv-text-muted);border-radius:6px">
                                <i class="fas fa-arrow-right" style="font-size:11px"></i>
                            </button>
                        </div>
                        <div style="display:flex;align-items:center;gap:10px;padding:10px 12px;background:var(--bv-bg-subtle);border-radius:10px">
                            <i class="far fa-envelope" style="color:#6366f1;font-size:14px;flex-shrink:0"></i>
                            <div style="flex:1">
                                <div style="font-size:12px;font-weight:600">Email · carmen@email.com</div>
                                <div style="font-size:11px;color:var(--bv-text-muted)">Ayer 18:42 · 2 mensajes</div>
                            </div>
                            <button type="button" style="background:none;border:none;padding:4px 6px;cursor:pointer;color:var(--bv-text-muted);border-radius:6px">
                                <i class="fas fa-arrow-right" style="font-size:11px"></i>
                            </button>
                        </div>
                    </div>

                    {{-- Timeline --}}
                    <div class="bv-right-section-title" style="margin-bottom:8px">Actividad</div>
                    <div style="display:flex;flex-direction:column;gap:0;margin-bottom:16px">
                        @foreach([
                            ['success', 'María López', 'abrió el ticket', 'hoy 14:23'],
                            ['info', 'Sistema', 'contactó a Nacex via API', 'hoy 14:25'],
                            ['warning', 'Nacex', 'respondió: paquete extraviado', 'hoy 15:10'],
                            ['', 'María López', 'cambió prioridad a <strong>Urgente</strong>', 'hoy 15:12'],
                            ['info', 'Juan Ruiz', 'fue asignado al ticket', 'hoy 15:30'],
                        ] as $ev)
                        <div style="display:flex;align-items:flex-start;gap:10px;padding:8px 0;border-bottom:1px solid var(--bv-border)">
                            <div style="width:8px;height:8px;border-radius:50%;margin-top:5px;flex-shrink:0;background:{{ $ev[0] === 'success' ? '#10b981' : ($ev[0] === 'info' ? '#3b82f6' : ($ev[0] === 'warning' ? '#f59e0b' : 'var(--bv-border)')) }}"></div>
                            <div style="font-size:12px;color:var(--bv-text);flex:1">
                                <strong>{{ $ev[1] }}</strong> {!! $ev[2] !!}
                                <span style="color:var(--bv-text-muted);font-size:11px"> · {{ $ev[3] }}</span>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    {{-- Comments --}}
                    <div class="bv-right-section-title" style="margin-bottom:8px">Comentarios del equipo</div>
                    <div style="display:flex;flex-direction:column;gap:8px;margin-bottom:12px">
                        <div style="display:flex;gap:10px">
                            <div class="bv-av c1" style="flex-shrink:0">ML</div>
                            <div style="flex:1;background:var(--bv-bg-subtle);border-radius:10px;padding:10px 12px">
                                <div style="font-size:11px;font-weight:600;margin-bottom:4px">María López <span style="font-weight:400;color:var(--bv-text-muted)">· hace 2h</span></div>
                                <div style="font-size:12.5px">He contactado con Nacex. Confirman que el paquete se extravió en el hub de Madrid. Están iniciando proceso de búsqueda.</div>
                            </div>
                        </div>
                        <div style="display:flex;gap:10px">
                            <div class="bv-av c2" style="flex-shrink:0">JR</div>
                            <div style="flex:1;background:var(--bv-bg-subtle);border-radius:10px;padding:10px 12px">
                                <div style="font-size:11px;font-weight:600;margin-bottom:4px">Juan Ruiz <span style="font-weight:400;color:var(--bv-text-muted)">· hace 45m</span></div>
                                <div style="font-size:12.5px">He hablado con logística. Podemos hacer reenvío express mañana sin coste. Esperando confirmación del cliente.</div>
                            </div>
                        </div>
                    </div>

                    {{-- Add comment --}}
                    <div style="display:flex;gap:8px;align-items:flex-start">
                        <div class="bv-av c1" style="flex-shrink:0;margin-top:2px">ML</div>
                        <textarea placeholder="Añadir comentario interno… usa @ para mencionar"
                                  style="flex:1;padding:9px 12px;border:1px solid var(--bv-border);border-radius:10px;font-size:12.5px;font-family:inherit;resize:vertical;min-height:60px"
                                  rows="2"></textarea>
                    </div>

                </div>

                {{-- Right sidebar (30%) --}}
                <aside style="display:flex;flex-direction:column;gap:14px">

                    <div>
                        <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--bv-text-muted);margin-bottom:5px">Estado</div>
                        <span style="display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:20px;background:#eff6ff;color:#1d4ed8;font-size:12px;font-weight:600;cursor:pointer">
                            <span style="width:7px;height:7px;border-radius:50%;background:#3b82f6"></span> Abierto
                        </span>
                    </div>

                    <div>
                        <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--bv-text-muted);margin-bottom:5px">Prioridad</div>
                        <span style="display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:20px;background:#fef2f2;color:#b91c1c;font-size:12px;font-weight:600;cursor:pointer">
                            <i class="fas fa-flag" style="font-size:10px"></i> Urgente
                        </span>
                    </div>

                    <div>
                        <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--bv-text-muted);margin-bottom:5px">Asignado a</div>
                        <div style="display:flex;align-items:center;gap:7px">
                            <div class="bv-av c2" style="width:24px;height:24px;font-size:10px">JR</div>
                            <span style="font-size:12.5px;font-weight:600">Juan Ruiz</span>
                        </div>
                    </div>

                    <div>
                        <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--bv-text-muted);margin-bottom:5px">Equipo</div>
                        <div style="font-size:12.5px;font-weight:600">Soporte N1</div>
                    </div>

                    <div>
                        <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--bv-text-muted);margin-bottom:5px">Cliente</div>
                        <div style="display:flex;align-items:center;gap:7px">
                            <div class="bv-av c1" style="width:24px;height:24px;font-size:10px">CP</div>
                            <div>
                                <div style="font-size:12px;font-weight:600">Carmen Pérez</div>
                                <div style="font-size:10.5px;color:var(--bv-text-muted)">VIP · 14 pedidos</div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--bv-text-muted);margin-bottom:5px">Fecha creación</div>
                        <div style="font-size:12px">27 abr 2026 · 14:23</div>
                    </div>

                    <div>
                        <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--bv-text-muted);margin-bottom:5px">Límite SLA</div>
                        <div class="bv-sla warn" style="font-size:12px;font-weight:600">
                            <i class="fas fa-clock" style="font-size:10px;margin-right:3px"></i> 1h 24m restantes
                        </div>
                    </div>

                    <div>
                        <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--bv-text-muted);margin-bottom:6px">Etiquetas</div>
                        <div style="display:flex;flex-wrap:wrap;gap:5px">
                            <span class="bv-tag urgent">Urgente</span>
                            <span class="bv-tag">Pedido</span>
                            <span class="bv-tag">Reenvío</span>
                        </div>
                    </div>

                </aside>

            </div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary"><i class="fas fa-check"></i> Resolver ticket</button>
            <button class="btn-secondary"><i class="fas fa-user-check"></i> Asignar otro agente</button>
        </div>
    </div>
</div>
