{{-- Modal: Filtrar conversaciones (mejorado) --}}
<div class="bv-modal" data-bv-modal-name="filter">
    <div class="bv-modal-dialog lg">
        <div class="bv-modal-head">
            <div class="bv-modal-title">Filtrar conversaciones</div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            {{-- Vistas guardadas --}}
            <div class="fl-section">
                <div class="fl-section-title">Vistas guardadas</div>
                <div class="fl-saved" data-preset="urgent">
                    <div class="ico" style="color:var(--bv-danger)"><i class="fas fa-fire"></i></div>
                    <div class="body">
                        <div class="t">Urgentes sin asignar</div>
                        <div class="s">Prioridad alta/urgente · sin agente</div>
                    </div>
                    <span class="c">5</span>
                </div>
                <div class="fl-saved" data-preset="sla">
                    <div class="ico" style="color:var(--bv-warning)"><i class="fas fa-clock"></i></div>
                    <div class="body">
                        <div class="t">SLA cerca de vencer</div>
                        <div class="s">&lt; 10 min restantes · abiertas</div>
                    </div>
                    <span class="c">3</span>
                </div>
            </div>

            {{-- Canales --}}
            <div class="fl-section">
                <div class="fl-section-title">Canales</div>
                <div class="fl-pills">
                    <button class="fl-pill on" data-group="channel" data-val="whatsapp">
                        <span class="d" style="background:var(--bv-wa)"></span>WhatsApp<span class="c">124</span>
                    </button>
                    <button class="fl-pill" data-group="channel" data-val="facebook">
                        <span class="d" style="background:var(--bv-fb)"></span>Messenger<span class="c">38</span>
                    </button>
                    <button class="fl-pill" data-group="channel" data-val="instagram">
                        <span class="d" style="background:var(--bv-ig)"></span>Instagram<span class="c">22</span>
                    </button>
                    <button class="fl-pill" data-group="channel" data-val="widget">
                        <span class="d" style="background:var(--bv-widget)"></span>Chat web<span class="c">44</span>
                    </button>
                    <button class="fl-pill" data-group="channel" data-val="email">
                        <span class="d" style="background:var(--bv-email)"></span>Email<span class="c">20</span>
                    </button>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                {{-- Estado --}}
                <div class="fl-section">
                    <div class="fl-section-title">Estado</div>
                    <div class="fl-pills">
                        <button class="fl-pill on" data-group="status" data-val="open">Abiertas<span class="c">248</span></button>
                        <button class="fl-pill" data-group="status" data-val="pending">En espera<span class="c">12</span></button>
                        <button class="fl-pill" data-group="status" data-val="closed">Cerradas<span class="c">1402</span></button>
                    </div>
                </div>

                {{-- Prioridad --}}
                <div class="fl-section">
                    <div class="fl-section-title">Prioridad</div>
                    <div class="fl-pills">
                        <button class="fl-pill" data-group="priority" data-val="urgent">
                            <span class="d" style="background:var(--bv-danger)"></span>Urgente<span class="c">5</span>
                        </button>
                        <button class="fl-pill" data-group="priority" data-val="high">
                            <span class="d" style="background:var(--bv-warning)"></span>Alta<span class="c">14</span>
                        </button>
                        <button class="fl-pill" data-group="priority" data-val="normal">
                            <span class="d" style="background:var(--bv-info)"></span>Normal<span class="c">180</span>
                        </button>
                        <button class="fl-pill" data-group="priority" data-val="low">
                            <span class="d" style="background:var(--bv-success)"></span>Baja<span class="c">49</span>
                        </button>
                    </div>
                </div>

                {{-- Asignación --}}
                <div class="fl-section">
                    <div class="fl-section-title">Asignación</div>
                    <div class="fl-pills">
                        <button class="fl-pill on" data-group="assign" data-val="me">Mías<span class="c">12</span></button>
                        <button class="fl-pill" data-group="assign" data-val="team">Mi equipo<span class="c">48</span></button>
                        <button class="fl-pill" data-group="assign" data-val="unassigned">Sin asignar<span class="c">7</span></button>
                        <button class="fl-pill" data-group="assign" data-val="mentions">Con mención<span class="c">3</span></button>
                    </div>
                </div>

                {{-- Etiquetas --}}
                <div class="fl-section">
                    <div class="fl-section-title">Etiquetas</div>
                    <div class="fl-pills">
                        <button class="fl-pill on" data-group="tag" data-val="urgent">
                            <span class="d" style="background:#ef4444"></span>Urgente
                        </button>
                        <button class="fl-pill" data-group="tag" data-val="vip">
                            <span class="d" style="background:#f59e0b"></span>VIP
                        </button>
                        <button class="fl-pill" data-group="tag" data-val="shipping">
                            <span class="d" style="background:#3b82f6"></span>Envío
                        </button>
                        <button class="fl-pill" data-group="tag" data-val="refund">
                            <span class="d" style="background:#8b5cf6"></span>Reembolso
                        </button>
                        <button class="fl-pill" data-group="tag" data-val="b2b">
                            <span class="d" style="background:#14b8a6"></span>B2B
                        </button>
                    </div>
                </div>
            </div>

            {{-- Rango de fechas --}}
            <div class="fl-section" style="margin-bottom:0">
                <div class="fl-section-title">Fecha</div>
                <div class="fl-pills" style="margin-bottom:10px">
                    <button class="fl-pill on" data-group="date" data-val="today">Hoy</button>
                    <button class="fl-pill" data-group="date" data-val="yesterday">Ayer</button>
                    <button class="fl-pill" data-group="date" data-val="7d">Últimos 7 días</button>
                    <button class="fl-pill" data-group="date" data-val="30d">Últimos 30 días</button>
                    <button class="fl-pill" data-group="date" data-val="custom">Personalizado</button>
                </div>
                <div class="fl-range">
                    <input type="date" id="flDateFrom" value="{{ date('Y-m-01') }}">
                    <span style="color:var(--bv-text-muted);font-size:11px">→</span>
                    <input type="date" id="flDateTo" value="{{ date('Y-m-d') }}">
                </div>
            </div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-secondary" id="flBtnClear">
                <i class="fas fa-rotate-left"></i> Limpiar
            </button>
            <span id="flActiveCount" style="margin-left:10px;font-size:11px;color:var(--bv-text-muted)">3 filtros activos</span>
            <div style="margin-left:auto;display:flex;gap:8px">
                <button class="btn-secondary">
                    <i class="far fa-bookmark"></i> Guardar vista
                </button>
                <button class="btn-primary" data-bv-close>Aplicar filtros</button>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    function countActive() {
        return $('.fl-pill.on').length;
    }

    function updateCount() {
        var n = countActive();
        $('#flActiveCount').text(n + ' filtro' + (n !== 1 ? 's' : '') + ' activo' + (n !== 1 ? 's' : ''));
    }

    $(document).on('click', '.fl-pill', function() {
        $(this).toggleClass('on');
        updateCount();
    });

    $(document).on('click', '#flBtnClear', function() {
        $('.fl-pill').removeClass('on');
        updateCount();
    });

    $(document).on('click', '.fl-saved', function() {
        var preset = $(this).data('preset');
        $('.fl-pill').removeClass('on');
        if (preset === 'urgent') {
            $('[data-val="urgent"],[data-val="unassigned"]').addClass('on');
        } else if (preset === 'sla') {
            $('[data-val="open"]').addClass('on');
        }
        updateCount();
    });
}());
</script>
