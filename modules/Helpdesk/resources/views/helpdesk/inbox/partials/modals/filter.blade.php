{{-- Modal: Filtrar conversaciones (mejorado) --}}
<div class="bv-modal" data-bv-modal-name="filter">
    <div class="bv-modal-dialog lg">
        <div class="bv-modal-head">
            <div class="bv-modal-title"><i class="fad fa-filter bv-modal-title-icon"></i> Filtrar conversaciones</div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            {{-- Accesos rápidos --}}
            <div class="fl-section">
                <div class="fl-section-title">Accesos rápidos</div>
                <div class="fl-saved" data-preset="urgent">
                    <div class="ico bv-fl-ico-danger"><i class="fas fa-fire"></i></div>
                    <div class="body">
                        <div class="t">Urgentes sin asignar</div>
                        <div class="s">Prioridad urgente · sin agente</div>
                    </div>
                    <span class="c">{{ $sidebarCounters['urgent'] ?? 0 }}</span>
                </div>
                <div class="fl-saved" data-preset="unread">
                    <div class="ico bv-fl-ico-info"><i class="fas fa-envelope-open"></i></div>
                    <div class="body">
                        <div class="t">Sin leer</div>
                        <div class="s">Abiertas sin asignar</div>
                    </div>
                    <span class="c">{{ $sidebarCounters['unread'] ?? 0 }}</span>
                </div>
            </div>

            {{-- Canales --}}
            <div class="fl-section">
                <div class="fl-section-title">Canales</div>
                <div class="fl-pills">
                    <button class="fl-pill" data-key="channel" data-val="whatsapp">
                        <span class="d bv-fl-dot-wa"></span>WhatsApp<span class="c">{{ $sidebarCounters['whatsapp'] ?? 0 }}</span>
                    </button>
                    <button class="fl-pill" data-key="channel" data-val="facebook">
                        <span class="d bv-fl-dot-fb"></span>Messenger<span class="c">{{ $sidebarCounters['facebook'] ?? 0 }}</span>
                    </button>
                    <button class="fl-pill" data-key="channel" data-val="instagram">
                        <span class="d bv-fl-dot-ig"></span>Instagram<span class="c">{{ $sidebarCounters['instagram'] ?? 0 }}</span>
                    </button>
                    <button class="fl-pill" data-key="channel" data-val="web">
                        <span class="d bv-fl-dot-widget"></span>Chat web<span class="c">{{ $sidebarCounters['web'] ?? 0 }}</span>
                    </button>
                    <button class="fl-pill" data-key="channel" data-val="email">
                        <span class="d bv-fl-dot-email"></span>Email<span class="c">{{ $sidebarCounters['email'] ?? 0 }}</span>
                    </button>
                </div>
            </div>

            <div class="bv-filter-grid">
                {{-- Estado --}}
                <div class="fl-section">
                    <div class="fl-section-title">Estado</div>
                    <div class="fl-pills">
                        <button class="fl-pill" data-key="status" data-val="open">Abiertas</button>
                        <button class="fl-pill" data-key="status" data-val="pending">En espera<span class="c">{{ $sidebarCounters['pending'] ?? 0 }}</span></button>
                        <button class="fl-pill" data-key="status" data-val="closed">Cerradas<span class="c">{{ $sidebarCounters['archived'] ?? 0 }}</span></button>
                    </div>
                </div>

                {{-- Prioridad --}}
                <div class="fl-section">
                    <div class="fl-section-title">Prioridad</div>
                    <div class="fl-pills">
                        <button class="fl-pill" data-key="priority" data-val="urgent">
                            <span class="d bv-fl-dot-danger"></span>Urgente<span class="c">{{ $sidebarCounters['urgent'] ?? 0 }}</span>
                        </button>
                        <button class="fl-pill" data-key="priority" data-val="high">
                            <span class="d bv-fl-dot-warning"></span>Alta
                        </button>
                        <button class="fl-pill" data-key="priority" data-val="normal">
                            <span class="d bv-fl-dot-info"></span>Normal
                        </button>
                        <button class="fl-pill" data-key="priority" data-val="low">
                            <span class="d bv-fl-dot-success"></span>Baja
                        </button>
                    </div>
                </div>

                {{-- Asignación --}}
                <div class="fl-section">
                    <div class="fl-section-title">Asignación</div>
                    <div class="fl-pills">
                        <button class="fl-pill" data-key="mine" data-val="1">Mías<span class="c">{{ $sidebarCounters['mine'] ?? 0 }}</span></button>
                        <button class="fl-pill" data-key="assignee" data-val="unassigned">Sin asignar<span class="c">{{ $sidebarCounters['unread'] ?? 0 }}</span></button>
                        <button class="fl-pill" data-key="urgent" data-val="1">Urgentes<span class="c">{{ $sidebarCounters['urgent'] ?? 0 }}</span></button>
                    </div>
                </div>

                {{-- Etiquetas --}}
                <div class="fl-section">
                    <div class="fl-section-title">Etiquetas</div>
                    <div class="fl-pills">
                        @forelse($inboxTags ?? [] as $tag)
                            <button class="fl-pill" data-key="tag" data-val="{{ $tag->id }}"
                                    style="--bv-tag-color: {{ $tag->color ?? '#6c757d' }}">
                                <span class="d" style="background:{{ $tag->color ?? '#6c757d' }}"></span>
                                {{ $tag->name }}
                                @if($tag->conversations_count)<span class="c">{{ $tag->conversations_count }}</span>@endif
                            </button>
                        @empty
                            <span class="bv-text-muted-12">Sin etiquetas configuradas</span>
                        @endforelse
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
            <button class="btn-primary" id="flBtnApply">Aplicar filtros</button>
            <button class="btn-secondary">Guardar vista</button>
            <span id="flActiveCount" class="bv-filter-count">3 filtros activos</span>
            <button class="btn-secondary" id="flBtnClear">Limpiar</button>
        </div>
    </div>
</div>

@once
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
            $('[data-bv-modal-name="filter"] [data-val="urgent"][data-key="priority"],[data-bv-modal-name="filter"] [data-val="unassigned"]').addClass('on');
        } else if (preset === 'unread') {
            $('[data-bv-modal-name="filter"] [data-val="unassigned"]').addClass('on');
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
@endonce
