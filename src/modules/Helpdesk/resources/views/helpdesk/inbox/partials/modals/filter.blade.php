{{-- Modal: Filtrar conversaciones (mejorado) --}}
<div class="bv-modal" data-bv-modal-name="filter">
    <div class="bv-modal-dialog lg">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box primary"><i class="fas fa-filter"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">BANDEJA · VISTA</span>
                <div class="bv-modal-title">Filtrar conversaciones</div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            {{-- Accesos rápidos --}}
            <div class="fl-section">
                <div class="fl-section-title">Accesos rápidos</div>
                <div class="fl-saved" data-preset="urgent">
                    <div class="ico"><i class="fas fa-fire"></i></div>
                    <div class="body">
                        <div class="t">Urgentes sin asignar</div>
                        <div class="s">Prioridad urgente · sin agente</div>
                    </div>
                    <span class="c">{{ $sidebarCounters['urgent'] ?? 0 }}</span>
                </div>
                <div class="fl-saved" data-preset="unread">
                    <div class="ico"><i class="fas fa-envelope-open"></i></div>
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
                        <button class="fl-pill" data-key="assignee" data-val="unassigned">Sin asignar<span class="c">{{ $sidebarCounters['unassigned'] ?? 0 }}</span></button>
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
            <span id="flActiveCount" class="bv-filter-count">Sin filtros activos</span>
            <button class="btn-primary" id="flBtnApply">Aplicar filtros</button>
            <button class="btn-secondary" id="flBtnSaveView">Guardar vista</button>
            <button class="btn-secondary" id="flBtnClear">Limpiar</button>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
(function() {
    var SEL = '[data-bv-modal-name="filter"]';

    // El rango de fecha no cuenta como "filtro activo" del usuario
    function countActive() {
        return $(SEL + ' .fl-pill.on:not([data-key="date"])').length;
    }

    function updateCount() {
        var n = countActive();
        var txt = n === 0
            ? 'Sin filtros activos'
            : n + ' filtro' + (n !== 1 ? 's' : '') + ' activo' + (n !== 1 ? 's' : '');
        $('#flActiveCount').text(txt);
    }

    // El rango Desde/Hasta solo se muestra cuando la fecha es "Personalizado"
    function syncDateRange() {
        var isCustom = $(SEL + ' .fl-pill[data-key="date"].on').data('val') === 'custom';
        $(SEL + ' .fl-range').toggleClass('on', !!isCustom);
    }

    // Chips normales: selección múltiple
    $(document).on('click', SEL + ' .fl-pill:not([data-key="date"])', function() {
        $(this).toggleClass('on');
        updateCount();
    });

    // Chips de fecha: selección única + rango solo en "Personalizado"
    $(document).on('click', SEL + ' .fl-pill[data-key="date"]', function() {
        $(SEL + ' .fl-pill[data-key="date"]').removeClass('on');
        $(this).addClass('on');
        syncDateRange();
        if ($(this).data('val') === 'custom') {
            var range = $(SEL + ' .fl-range')[0];
            if (range) {
                range.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
            }
        }
    });

    $(document).on('click', '#flBtnClear', function() {
        $(SEL + ' .fl-pill').removeClass('on');
        $(SEL + ' .fl-range').removeClass('on');
        updateCount();
    });

    $(document).on('click', '.fl-saved', function() {
        var preset = $(this).data('preset');
        $(SEL + ' .fl-pill:not([data-key="date"])').removeClass('on');
        if (preset === 'urgent') {
            $(SEL + ' [data-val="urgent"][data-key="priority"],' + SEL + ' [data-val="unassigned"]').addClass('on');
        } else if (preset === 'unread') {
            $(SEL + ' [data-val="unassigned"]').addClass('on');
        }
        updateCount();
    });

    $(document).on('click', '#flBtnApply', function() {
        var params = {};

        $(SEL + ' .fl-pill.on').each(function() {
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
        $(SEL).removeClass('on');
        if ($('.bv-modal.on').length === 0) {
            $('body').css('overflow', '');
        }

        if (typeof applyInboxFilters === 'function') {
            applyInboxFilters(params);
        } else {
            $(document).trigger('bv:filter:apply', [params]);
        }
    });

    // Refleja los filtros de la URL en los chips al abrir el modal
    function syncFromUrl() {
        var u = new URL(window.location.href);
        $(SEL + ' .fl-pill').removeClass('on');
        ['channel', 'status', 'priority', 'tag', 'mine', 'unread', 'urgent', 'vip', 'assignee', 'date'].forEach(function(param) {
            var raw = u.searchParams.get(param);
            if (!raw) { return; }
            raw.split(',').forEach(function(val) {
                $(SEL + ' .fl-pill[data-key="' + param + '"][data-val="' + val + '"]').addClass('on');
            });
        });
        // Fecha por defecto (Hoy) si la URL no trae ninguna
        if (!$(SEL + ' .fl-pill[data-key="date"].on').length) {
            $(SEL + ' .fl-pill[data-key="date"][data-val="today"]').addClass('on');
        }
        syncDateRange();
        updateCount();
    }

    $(document).on('click', '[data-bv-modal="filter"]', function() {
        setTimeout(syncFromUrl, 0);
    });

    // Guardar vista: aplica la selección (actualiza la URL) y abre el modal de guardar vista
    $(document).on('click', '#flBtnSaveView', function(e) {
        e.preventDefault();
        $('#flBtnApply').trigger('click');
        $('#bv-save-view-btn').trigger('click');
    });

    // Estado inicial
    syncDateRange();
    updateCount();
}());
</script>
@endpush
@endonce
