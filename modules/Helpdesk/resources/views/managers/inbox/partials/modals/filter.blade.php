{{-- Modal: Filtrar conversaciones (mejorado) --}}
<div class="bv-modal" data-bv-modal-name="filter">
    <div class="bv-modal-dialog lg">
        <div class="bv-modal-head">
            <div class="bv-modal-title"><i class="fas fa-filter bv-modal-title-icon"></i> Filtrar conversaciones</div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            {{-- Vistas guardadas --}}
            <div class="fl-section">
                <div class="fl-section-title">Vistas guardadas</div>
                <div class="fl-saved" data-preset="urgent">
                    <div class="ico bv-fl-ico-danger"><i class="fas fa-fire"></i></div>
                    <div class="body">
                        <div class="t">Urgentes sin asignar</div>
                        <div class="s">Prioridad alta/urgente · sin agente</div>
                    </div>
                    <span class="c">5</span>
                </div>
                <div class="fl-saved" data-preset="sla">
                    <div class="ico bv-fl-ico-warning"><i class="fas fa-clock"></i></div>
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
                    <button class="fl-pill on" data-key="channel" data-val="whatsapp">
                        <span class="d bv-fl-dot-wa"></span>WhatsApp<span class="c">124</span>
                    </button>
                    <button class="fl-pill" data-key="channel" data-val="facebook">
                        <span class="d bv-fl-dot-fb"></span>Messenger<span class="c">38</span>
                    </button>
                    <button class="fl-pill" data-key="channel" data-val="instagram">
                        <span class="d bv-fl-dot-ig"></span>Instagram<span class="c">22</span>
                    </button>
                    <button class="fl-pill" data-key="channel" data-val="widget">
                        <span class="d bv-fl-dot-widget"></span>Chat web<span class="c">44</span>
                    </button>
                    <button class="fl-pill" data-key="channel" data-val="email">
                        <span class="d bv-fl-dot-email"></span>Email<span class="c">20</span>
                    </button>
                </div>
            </div>

            <div class="bv-filter-grid">
                {{-- Estado --}}
                <div class="fl-section">
                    <div class="fl-section-title">Estado</div>
                    <div class="fl-pills">
                        <button class="fl-pill on" data-key="status" data-val="open">Abiertas<span class="c">248</span></button>
                        <button class="fl-pill" data-key="status" data-val="pending">En espera<span class="c">12</span></button>
                        <button class="fl-pill" data-key="status" data-val="closed">Cerradas<span class="c">1402</span></button>
                    </div>
                </div>

                {{-- Prioridad --}}
                <div class="fl-section">
                    <div class="fl-section-title">Prioridad</div>
                    <div class="fl-pills">
                        <button class="fl-pill" data-key="priority" data-val="urgent">
                            <span class="d bv-fl-dot-danger"></span>Urgente<span class="c">5</span>
                        </button>
                        <button class="fl-pill" data-key="priority" data-val="high">
                            <span class="d bv-fl-dot-warning"></span>Alta<span class="c">14</span>
                        </button>
                        <button class="fl-pill" data-key="priority" data-val="normal">
                            <span class="d bv-fl-dot-info"></span>Normal<span class="c">180</span>
                        </button>
                        <button class="fl-pill" data-key="priority" data-val="low">
                            <span class="d bv-fl-dot-success"></span>Baja<span class="c">49</span>
                        </button>
                    </div>
                </div>

                {{-- Asignación --}}
                <div class="fl-section">
                    <div class="fl-section-title">Asignación</div>
                    <div class="fl-pills">
                        <button class="fl-pill on" data-key="assignee" data-val="me">Mías<span class="c">12</span></button>
                        <button class="fl-pill" data-key="assignee" data-val="team">Mi equipo<span class="c">48</span></button>
                        <button class="fl-pill" data-key="assignee" data-val="unassigned">Sin asignar<span class="c">7</span></button>
                        <button class="fl-pill" data-key="assignee" data-val="mentions">Con mención<span class="c">3</span></button>
                    </div>
                </div>

                {{-- Etiquetas --}}
                <div class="fl-section">
                    <div class="fl-section-title">Etiquetas</div>
                    <div class="fl-pills">
                        <button class="fl-pill on" data-key="tag" data-val="urgent">
                            <span class="d bv-fl-dot-red"></span>Urgente
                        </button>
                        <button class="fl-pill" data-key="tag" data-val="vip">
                            <span class="d bv-fl-dot-amber"></span>VIP
                        </button>
                        <button class="fl-pill" data-key="tag" data-val="shipping">
                            <span class="d bv-fl-dot-blue"></span>Envío
                        </button>
                        <button class="fl-pill" data-key="tag" data-val="refund">
                            <span class="d bv-fl-dot-purple"></span>Reembolso
                        </button>
                        <button class="fl-pill" data-key="tag" data-val="b2b">
                            <span class="d bv-fl-dot-teal"></span>B2B
                        </button>
                    </div>
                </div>
            </div>

            {{-- Rango de fechas --}}
            <div class="fl-section bv-filter-section-last">
                <div class="fl-section-title">Fecha</div>
                <div class="fl-pills bv-fl-date-pills">
                    <button class="fl-pill on" data-key="date" data-val="today">Hoy</button>
                    <button class="fl-pill" data-key="date" data-val="yesterday">Ayer</button>
                    <button class="fl-pill" data-key="date" data-val="7d">Últimos 7 días</button>
                    <button class="fl-pill" data-key="date" data-val="30d">Últimos 30 días</button>
                    <button class="fl-pill" data-key="date" data-val="custom">Personalizado</button>
                </div>
                <div class="fl-range">
                    <input type="date" id="flDateFrom" value="{{ date('Y-m-01') }}">
                    <span class="bv-date-arrow">→</span>
                    <input type="date" id="flDateTo" value="{{ date('Y-m-d') }}">
                </div>
            </div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-secondary" id="flBtnClear">
                <i class="fas fa-rotate-left"></i> Limpiar
            </button>
            <span id="flActiveCount" class="bv-filter-count">3 filtros activos</span>
            <div class="bv-filter-foot-actions">
                <button class="btn-secondary">
                    <i class="far fa-bookmark"></i> Guardar vista
                </button>
                <button class="btn-primary" id="flBtnApply"><i class="fas fa-check"></i> Aplicar filtros</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function() {
    function countActive() {
        return $('[data-bv-modal-name="filter"] .fl-pill.on').length;
    }

    function updateCount() {
        var n = countActive();
        $('#flActiveCount').text(n + ' filtro' + (n !== 1 ? 's' : '') + ' activo' + (n !== 1 ? 's' : ''));
    }

    $(document).on('click', '[data-bv-modal-name="filter"] .fl-pill', function() {
        $(this).toggleClass('on');
        updateCount();
    });

    $(document).on('click', '#flBtnClear', function() {
        $('[data-bv-modal-name="filter"] .fl-pill').removeClass('on');
        updateCount();
    });

    $(document).on('click', '.fl-saved', function() {
        var preset = $(this).data('preset');
        $('[data-bv-modal-name="filter"] .fl-pill').removeClass('on');
        if (preset === 'urgent') {
            $('[data-bv-modal-name="filter"] [data-val="urgent"],[data-bv-modal-name="filter"] [data-val="unassigned"]').addClass('on');
        } else if (preset === 'sla') {
            $('[data-bv-modal-name="filter"] [data-val="open"]').addClass('on');
        }
        updateCount();
    });

    $(document).on('click', '#flBtnApply', function() {
        var params = {};

        $('[data-bv-modal-name="filter"] .fl-pill.on').each(function() {
            var key = $(this).data('key');
            var val = $(this).data('val');
            if (!key || !val) return;
            if (params[key]) {
                params[key] += ',' + val;
            } else {
                params[key] = val;
            }
        });

        // Close modal
        $('[data-bv-modal-name="filter"]').removeClass('on');
        if ($('.bv-modal.on').length === 0) {
            $('body').css('overflow', '');
        }

        if (typeof applyInboxFilters === 'function') {
            applyInboxFilters(params);
        } else {
            $(document).trigger('bv:filter:apply', [params]);
        }
    });
}());
</script>
@endpush
