{{-- Modal: Detalle de ticket --}}
<div class="bv-modal" data-bv-modal-name="ticket">
    <div class="bv-modal-dialog lg">
        <div class="bv-modal-head">
            <div class="bv-modal-title">
                <i class="fas fa-ticket bv-modal-title-icon"></i>
                Ticket #T-2026-001
                <span class="bv-modal-title-badge bv-modal-title-badge-danger">Urgente</span>
                <span class="bv-modal-title-badge bv-modal-title-badge-info bv-ml-4">Abierto</span>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            <div class="bv-ticket-grid">

                {{-- Left (70%) --}}
                <div>
                    {{-- Title --}}
                    <div class="bv-ticket-detail-title">
                        Pedido extraviado — solicitud de reenvío urgente
                    </div>

                    {{-- Meta pills --}}
                    <div class="bv-ticket-pills">
                        <span class="bv-th-pill"><span class="bv-th-pill-dot bv-activity-dot-danger"></span>Urgente</span>
                        <span class="bv-th-pill"><span class="bv-th-pill-dot bv-activity-dot-info"></span>Abierto</span>
                        <span class="bv-th-pill">Soporte N1</span>
                        <span class="bv-th-pill bv-th-pill-mono">SLA: 1h 24m</span>
                    </div>

                    {{-- Description --}}
                    <div class="bv-right-section-title bv-mb-6">Descripción</div>
                    <div class="bv-ticket-desc">
                        Cliente reporta que su pedido #1234 con tracking ES1234-NC fue marcado como entregado pero no lo recibió.
                        El conserje del edificio confirma que no llegó ningún paquete. Solicita reenvío urgente o reembolso antes del viernes.
                    </div>

                    {{-- Linked conversations --}}
                    <div class="bv-right-section-title bv-mb-8">Conversaciones vinculadas</div>
                    <div class="bv-linked-convs">
                        <div class="bv-linked-conv-item">
                            <i class="fab fa-whatsapp bv-icon-channel-wa"></i>
                            <div class="bv-spacer">
                                <div class="bv-linked-conv-name">WhatsApp · Carmen Pérez</div>
                                <div class="bv-linked-conv-meta">Hoy 14:23 · 8 mensajes</div>
                            </div>
                            <button type="button" class="bv-btn-ghost">
                                <i class="fas fa-arrow-right bv-icon-sm"></i>
                            </button>
                        </div>
                        <div class="bv-linked-conv-item">
                            <i class="far fa-envelope bv-icon-channel-email"></i>
                            <div class="bv-spacer">
                                <div class="bv-linked-conv-name">Email · carmen@email.com</div>
                                <div class="bv-linked-conv-meta">Ayer 18:42 · 2 mensajes</div>
                            </div>
                            <button type="button" class="bv-btn-ghost">
                                <i class="fas fa-arrow-right bv-icon-sm"></i>
                            </button>
                        </div>
                    </div>

                    {{-- Timeline --}}
                    <div class="bv-right-section-title bv-mb-8">Actividad</div>
                    <div class="bv-activity-list">
                        @foreach([
                            ['success', 'María López', 'abrió el ticket', 'hoy 14:23'],
                            ['info', 'Sistema', 'contactó a Nacex via API', 'hoy 14:25'],
                            ['warning', 'Nacex', 'respondió: paquete extraviado', 'hoy 15:10'],
                            ['', 'María López', 'cambió prioridad a <strong>Urgente</strong>', 'hoy 15:12'],
                            ['info', 'Juan Ruiz', 'fue asignado al ticket', 'hoy 15:30'],
                        ] as $ev)
                        <div class="bv-activity-item">
                            <div class="bv-activity-dot {{ $ev[0] === 'success' ? 'bv-activity-dot-success' : ($ev[0] === 'info' ? 'bv-activity-dot-info' : ($ev[0] === 'warning' ? 'bv-activity-dot-warning' : 'bv-activity-dot-default')) }}"></div>
                            <div class="bv-activity-text">
                                <strong>{{ $ev[1] }}</strong> {!! $ev[2] !!}
                                <span class="bv-activity-time"> · {{ $ev[3] }}</span>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    {{-- Comments --}}
                    <div class="bv-right-section-title bv-mb-8">Comentarios del equipo</div>
                    <div class="bv-comments-list">
                        <div class="bv-comment-item">
                            <div class="bv-av c1 flex-shrink-0">ML</div>
                            <div class="bv-comment-bubble">
                                <div class="bv-comment-head">María López <span class="bv-comment-author-time">· hace 2h</span></div>
                                <div class="bv-comment-body">He contactado con Nacex. Confirman que el paquete se extravió en el hub de Madrid. Están iniciando proceso de búsqueda.</div>
                            </div>
                        </div>
                        <div class="bv-comment-item">
                            <div class="bv-av c2 flex-shrink-0">JR</div>
                            <div class="bv-comment-bubble">
                                <div class="bv-comment-head">Juan Ruiz <span class="bv-comment-author-time">· hace 45m</span></div>
                                <div class="bv-comment-body">He hablado con logística. Podemos hacer reenvío express mañana sin coste. Esperando confirmación del cliente.</div>
                            </div>
                        </div>
                    </div>

                    {{-- Add comment --}}
                    <div class="bv-comment-compose">
                        <div class="bv-av c1 flex-shrink-0 bv-mt-2">ML</div>
                        <textarea placeholder="Añadir comentario interno… usa @ para mencionar"
                                  class="bv-comment-ta"
                                  rows="2"></textarea>
                    </div>

                </div>

                {{-- Right sidebar (30%) --}}
                <aside class="bv-ticket-aside">

                    <div class="bv-aside-section">
                        <div class="bv-field-label">Estado</div>
                        <span class="bv-status-badge bv-status-badge-info">
                            <span class="bv-badge-dot bv-badge-dot-info"></span> Abierto
                        </span>
                    </div>

                    <div class="bv-aside-section">
                        <div class="bv-field-label">Prioridad</div>
                        <span class="bv-status-badge bv-status-badge-danger">
                            <i class="fas fa-flag bv-icon-flag-sm"></i> Urgente
                        </span>
                    </div>

                    <div class="bv-aside-section">
                        <div class="bv-field-label">Asignado a</div>
                        <div class="bv-flex-row">
                            <div class="bv-av c2 bv-av-sm">JR</div>
                            <span class="bv-aside-name">Juan Ruiz</span>
                        </div>
                    </div>

                    <div class="bv-aside-section">
                        <div class="bv-field-label">Equipo</div>
                        <div class="bv-aside-text">Soporte N1</div>
                    </div>

                    <div class="bv-aside-section">
                        <div class="bv-field-label">Cliente</div>
                        <div class="bv-flex-row">
                            <div class="bv-av c1 bv-av-sm">CP</div>
                            <div>
                                <div class="bv-aside-name">Carmen Pérez</div>
                                <div class="bv-aside-sub">VIP · 14 pedidos</div>
                            </div>
                        </div>
                    </div>

                    <div class="bv-aside-section">
                        <div class="bv-field-label">Fecha creación</div>
                        <div class="bv-aside-date">27 abr 2026 · 14:23</div>
                    </div>

                    <div class="bv-aside-section">
                        <div class="bv-field-label">Límite SLA</div>
                        <div class="bv-sla warn bv-aside-date bv-aside-date--bold">
                            <i class="fas fa-clock bv-sla-clock-icon"></i> 1h 24m restantes
                        </div>
                    </div>

                    <div class="bv-aside-section">
                        <div class="bv-field-label-mb6">Etiquetas</div>
                        <div class="bv-aside-tags">
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
