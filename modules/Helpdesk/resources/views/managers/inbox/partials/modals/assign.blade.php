{{-- Modal: Asignar conversación --}}
<div class="bv-modal" data-bv-modal-name="assign">
    <div class="bv-modal-dialog">
        <div class="bv-modal-head">
            <div>
                <span class="bv-modal-label">Chat · Bandeja</span>
                <div class="bv-modal-title">
                    <i class="fas fa-user-plus bv-modal-title-icon"></i>
                    Asignar conversación
                    @if(!empty($selectedConversation))<span class="bv-chip-id">#{{ $selectedConversation->id }}</span>@endif
                </div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            @include('helpdesk::managers.inbox.partials.modals._context-card')

            {{-- Search --}}
            <div class="bv-modal-search">
                <i class="fas fa-magnifying-glass"></i>
                <input id="assign-search" type="text" placeholder="Buscar agente o equipo…" autocomplete="off">
            </div>

            {{-- Tabs --}}
            <div class="bv-modal-tabs">
                <button class="bv-modal-tab on" data-tab="agentes">Agentes</button>
                <button class="bv-modal-tab" data-tab="equipos">Equipos</button>
            </div>

            {{-- Tab: Agentes --}}
            <div class="bv-assign-tab" data-panel="agentes">
                <div class="bv-opt-list" id="assign-agents-list">
                    {{-- Unassign option --}}
                    <button class="bv-opt {{ !$selectedConversation?->assignee_id ? 'on' : '' }}" data-agent-id="">
                        <div class="bv-av bv-av--none"><i class="fas fa-user-slash bv-icon-sm"></i></div>
                        <div class="body">
                            <div class="name">Sin asignar</div>
                            <div class="sub">Quitar la asignación actual</div>
                        </div>
                        <i class="fas fa-check check"></i>
                    </button>

                    @if(empty($agents) || (is_countable($agents) && count($agents) === 0))
                        <em class="text-muted small">No hay agentes disponibles</em>
                    @else
                        @foreach($agents as $agent)
                            <button class="bv-opt {{ $selectedConversation?->assignee_id === $agent->id ? 'on' : '' }}"
                                    data-agent-id="{{ $agent->id }}">
                                <div class="bv-av c{{ ($loop->index % 7) + 1 }}">{{ strtoupper(substr($agent->firstname, 0, 1) . substr($agent->lastname, 0, 1)) }}</div>
                                <div class="body">
                                    <div class="name">{{ $agent->firstname }} {{ $agent->lastname }}</div>
                                    <div class="sub">{{ $agent->email }}</div>
                                </div>
                                <i class="fas fa-check check"></i>
                            </button>
                        @endforeach
                    @endif
                </div>
            </div>

            {{-- Tab: Equipos --}}
            <div class="bv-assign-tab d-none" data-panel="equipos">
                <div class="bv-opt-list">
                    <button class="bv-opt" data-team-id="1">
                        <div class="bv-av c1"><i class="fas fa-users bv-icon-sm"></i></div>
                        <div class="body">
                            <div class="name">Ventas</div>
                            <div class="sub">6 miembros · 18 conversaciones activas</div>
                        </div>
                        <i class="fas fa-check check"></i>
                    </button>
                    <button class="bv-opt" data-team-id="2">
                        <div class="bv-av c3"><i class="fas fa-headset bv-icon-sm"></i></div>
                        <div class="body">
                            <div class="name">Soporte</div>
                            <div class="sub">5 miembros · 12 conversaciones activas</div>
                        </div>
                        <i class="fas fa-check check"></i>
                    </button>
                    <button class="bv-opt" data-team-id="3">
                        <div class="bv-av c5"><i class="fas fa-truck bv-icon-sm"></i></div>
                        <div class="body">
                            <div class="name">Logística</div>
                            <div class="sub">3 miembros · 7 conversaciones activas</div>
                        </div>
                        <i class="fas fa-check check"></i>
                    </button>
                    <button class="bv-opt" data-team-id="4">
                        <div class="bv-av c7"><i class="fas fa-robot bv-icon-sm"></i></div>
                        <div class="body">
                            <div class="name">IA y Automatización</div>
                            <div class="sub">2 miembros · 4 conversaciones activas</div>
                        </div>
                        <i class="fas fa-check check"></i>
                    </button>
                </div>
            </div>

            {{-- Options --}}
            <div class="bv-modal-divider"></div>
            <label class="bv-modal-check">
                <input type="checkbox" checked>
                <span>Notificar al agente por email</span>
            </label>
            <label class="bv-modal-check">
                <input type="checkbox">
                <span>Transferir también conversaciones anteriores</span>
            </label>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="assign-btn-notify">Asignar y notificar</button>
            <button class="btn-secondary" id="assign-btn-silent">Asignar</button>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
$(document).on('click', '[data-bv-modal-name="assign"] .bv-modal-tab', function () {
    var tab = $(this).data('tab');
    $(this).closest('.bv-modal-dialog').find('.bv-modal-tab').removeClass('on');
    $(this).addClass('on');
    $(this).closest('.bv-modal-dialog').find('.bv-assign-tab').hide();
    $(this).closest('.bv-modal-dialog').find('[data-panel="' + tab + '"]').show();
});

$(document).on('click', '[data-bv-modal-name="assign"] .bv-opt', function () {
    $(this).closest('.bv-opt-list').find('.bv-opt').removeClass('on');
    $(this).addClass('on');
});

$(document).on('input', '#assign-search', function () {
    var q = $(this).val().toLowerCase();
    $('[data-bv-modal-name="assign"] .bv-opt').each(function () {
        var name = $(this).find('.name').text().toLowerCase();
        $(this).toggle(!q || name.includes(q));
    });
});
</script>
@endpush
@endonce
