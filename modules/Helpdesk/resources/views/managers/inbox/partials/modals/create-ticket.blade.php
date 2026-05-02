{{-- Modal: Crear ticket desde conversación --}}
<div class="bv-modal" data-bv-modal-name="create-ticket">
    <div class="bv-modal-dialog w-md">
        <div class="modal-head">
            <div class="modal-icon"><i class="fa-solid fa-ticket"></i></div>
            <div class="modal-title-wrap">
                <span class="modal-label">CHAT → TICKETS</span>
                <span class="modal-title">Escalar a ticket</span>
            </div>
            <button class="x" data-bv-close><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <div class="field-group">
                <div class="fg-row">
                    <span class="lbl">Contexto</span>
                    <span class="val">
                        Conversación <span class="mono" style="color:var(--bv-text-muted, #8c929d)">#<span id="bv-ticket-conv-id">{{ $convo?->id ?? '—' }}</span></span>
                        · <span id="bv-ticket-message-count">{{ $convo?->messages_count ?? 0 }}</span> mensajes
                    </span>
                </div>
            </div>

            <div class="field">
                <label class="flabel">Asunto <span class="req">*</span></label>
                <input type="text" class="finput" id="bv-ticket-subject" value="{{ $convo?->subject ?? '' }}" placeholder="Ej: Error al iniciar sesión">
            </div>

            <div class="frow-3">
                <div class="field">
                    <label class="flabel">Prioridad</label>
                    <div class="r-prio-group" id="bv-ticket-priority" style="width:100%">
                        <button type="button" class="r-prio-btn low" data-priority="low"><span class="d"></span>Baja</button>
                        <button type="button" class="r-prio-btn normal on" data-priority="medium"><span class="d"></span>Normal</button>
                        <button type="button" class="r-prio-btn high" data-priority="high"><span class="d"></span>Alta</button>
                        <button type="button" class="r-prio-btn urgent" data-priority="urgent"><span class="d"></span>Urgente</button>
                    </div>
                </div>
            </div>

            <div class="frow">
                <div class="field">
                    <label class="flabel">Categoría</label>
                    <select class="fselect" id="bv-ticket-category">
                        <option value="">Sin categoría</option>
                        @php
                            $categories = [];
                            if (class_exists(\Modules\HelpdeskTickets\Models\TicketCategory::class)) {
                                try {
                                    $categories = \Modules\HelpdeskTickets\Models\TicketCategory::on('helpdesk')->get();
                                } catch (\Throwable $e) {}
                            }
                        @endphp
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label class="flabel">Agente</label>
                    <select class="fselect" id="bv-ticket-assignee">
                        <option value="">Sin asignar</option>
                        @php
                            $agents = [];
                            if (class_exists(\App\Models\User::class)) {
                                try {
                                    $agents = \App\Models\User::query()
                                        ->whereHas('roles', fn($q) => $q->whereIn('name', ['agent', 'admin', 'manager']))
                                        ->orWhereHas('permissions', fn($q) => $q->where('name', 'like', '%ticket%'))
                                        ->limit(50)
                                        ->get();
                                } catch (\Throwable $e) {}
                            }
                        @endphp
                        @foreach($agents as $agent)
                            <option value="{{ $agent->id }}" {{ $convo?->assignee_id == $agent->id ? 'selected' : '' }}>{{ $agent->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="field">
                <label class="flabel">Descripción <span class="hint">Pre-llenada con el chat</span></label>
                <textarea class="finput" id="bv-ticket-description" rows="3" placeholder="Describe el problema..."></textarea>
            </div>

            <label class="check">
                <input type="checkbox" id="bv-ticket-attach-chat" checked>
                Adjuntar transcripción del chat al ticket
            </label>
            <label class="check">
                <input type="checkbox" id="bv-ticket-notify" checked>
                Notificar al cliente con el ID del ticket
            </label>
        </div>
        <div class="modal-foot">
            <button class="btn btn-ghost btn-sm" data-bv-close>Cancelar</button>
            <div class="ml"></div>
            <button type="button" class="btn btn-primary btn-sm" id="bv-btn-create-ticket">
                <i class="fa-solid fa-ticket"></i> Crear ticket
            </button>
        </div>
    </div>
</div>
