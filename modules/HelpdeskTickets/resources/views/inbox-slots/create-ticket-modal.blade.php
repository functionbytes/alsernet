{{-- Modal: Crear ticket desde conversación
     Propietario: HelpdeskTickets · Incluido por Helpdesk en el inbox vía slot.
     Solo se renderiza si el integration helper helpdesk_tickets_enabled() es true.
--}}
@php
    $convo = $convo ?? ($selectedConversation ?? null);
@endphp
<div class="bv-modal" data-bv-modal-name="create-ticket">
    <div class="modal w-md">
        <div class="modal-head">
            <div class="modal-icon"><i class="fa-solid fa-ticket"></i></div>
            <div class="modal-title-wrap">
                <div class="modal-label">CHAT → TICKETS</div>
                <div class="modal-title">Escalar a ticket</div>
            </div>
            <button class="modal-close" data-bv-close><i class="fa-solid fa-xmark"></i></button>
        </div>

        <div class="modal-body">
            <div class="esc-context">
                <span class="lbl">Contexto</span>
                <span class="val">
                    Conversación #<span id="bv-ticket-conv-id">{{ $convo?->id ?? '—' }}</span>
                    · <span id="bv-ticket-message-count">{{ $convo?->messages_count ?? 0 }}</span> mensajes
                </span>
            </div>

            <div class="field">
                <div class="flabel">Asunto <span class="req">*</span></div>
                <input type="text" class="finput" id="bv-ticket-subject"
                    value="{{ $convo?->subject ?? '' }}"
                    placeholder="Ej: Error al iniciar sesión">
            </div>

            <div class="field">
                <div class="flabel">Prioridad</div>
                <div class="prio-bar" id="bv-ticket-priority">
                    <button type="button" class="prio-card" data-priority="low">
                        <div class="ic"><i class="fa-solid fa-chevron-down"></i></div>
                        <span class="lbl">Baja</span>
                    </button>
                    <button type="button" class="prio-card on" data-priority="medium">
                        <div class="ic"><i class="fa-solid fa-minus"></i></div>
                        <span class="lbl">Normal</span>
                    </button>
                    <button type="button" class="prio-card" data-priority="high">
                        <div class="ic"><i class="fa-solid fa-chevron-up"></i></div>
                        <span class="lbl">Alta</span>
                    </button>
                    <button type="button" class="prio-card" data-priority="urgent">
                        <div class="ic"><i class="fa-solid fa-angles-up"></i></div>
                        <span class="lbl">Urgente</span>
                    </button>
                </div>
            </div>

            <div class="frow">
                <div class="field">
                    <div class="flabel">Categoría</div>
                    <select class="fselect" id="bv-ticket-category">
                        <option value="">Sin categoría</option>
                        @foreach(app(\Modules\Helpdesk\Contracts\TicketServiceContract::class)->getCategories() as $cat)
                            <option value="{{ $cat['id'] }}">{{ $cat['name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <div class="flabel">Agente</div>
                    <select class="fselect" id="bv-ticket-assignee">
                        <option value="">Sin asignar</option>
                        @php
                            $ticketAgents = [];
                            try {
                                $ticketAgents = \App\Models\User::query()
                                    ->whereHas('roles', fn($q) => $q->whereIn('name', ['agent', 'admin', 'manager']))
                                    ->orWhereHas('permissions', fn($q) => $q->where('name', 'like', '%ticket%'))
                                    ->limit(50)
                                    ->get();
                            } catch (\Throwable $e) {}
                        @endphp
                        @foreach($ticketAgents as $agent)
                            <option value="{{ $agent->id }}" {{ $convo?->assignee_id == $agent->id ? 'selected' : '' }}>
                                {{ $agent->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="field">
                <div class="flabel">
                    Descripción
                    <span class="hint">Pre-llenada con el chat</span>
                </div>
                <textarea class="finput" id="bv-ticket-description" rows="4"
                    placeholder="Describe el problema..."></textarea>
            </div>

            <div class="field">
                <label class="check">
                    <input type="checkbox" id="bv-ticket-attach-chat" checked>
                    Adjuntar transcripción del chat al ticket
                </label>
                <label class="check">
                    <input type="checkbox" id="bv-ticket-notify" checked>
                    Notificar al cliente con el ID del ticket
                </label>
            </div>
        </div>

        <div class="modal-foot">
            <button type="button" class="btn btn-primary" id="bv-btn-create-ticket">
                <i class="fa-solid fa-ticket"></i> Crear ticket
            </button>
            <button class="btn btn-outline" data-bv-close>Cancelar</button>
        </div>
    </div>
</div>
