{{-- Modal: Crear ticket desde conversación
     Propietario: HelpdeskTickets · Incluido por Helpdesk en el inbox vía slot.
     Solo se renderiza si el integration helper helpdesk_tickets_enabled() es true.
--}}
@php
    $convo = $convo ?? ($selectedConversation ?? null);
    $escTickets = app(\Modules\Helpdesk\Contracts\TicketServiceContract::class);

    // items ya viene cargado por ConversationsController (limit 50), así que
    // contar aquí no añade query. 'messages_count' no existe en el modelo: la
    // plantilla pintaba siempre 0 hasta que el JS refrescaba el texto al abrir.
    $escMessages = $convo?->relationLoaded('items') ? $convo->items->count() : 0;

    // Las dos formas del plural viajan al JS por data-attributes: al abrir el
    // modal recalcula el contexto con los mensajes realmente cargados en el
    // hilo, y sin esto acababa escribiendo "1 mensajes".
    // Tickets que YA nacieron de esta conversación: nada impedía escalar la
    // misma conversación cinco veces seguidas y salían cinco tickets.
    $escExisting = $convo ? $escTickets->getConversationTickets($convo) : collect();

    $escCtx = array_pad(
        explode('|', __('helpdesktickets::helpdesktickets.inbox_escalate.context_value'), 2),
        2,
        ''
    );
@endphp
<div class="bv-modal" data-bv-modal-name="create-ticket">
    <div class="modal w-md">
        <div class="modal-head">
            <div class="modal-icon"><i class="fa-solid fa-ticket"></i></div>
            <div class="modal-title-wrap">
                <div class="modal-label">{{ __('helpdesktickets::helpdesktickets.inbox_escalate.label') }}</div>
                <div class="modal-title">{{ __('helpdesktickets::helpdesktickets.inbox_escalate.title') }}</div>
            </div>
            <button class="modal-close" data-bv-close aria-label="{{ __('helpdesktickets::helpdesktickets.inbox_escalate.close') }}">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="modal-body">
            <div class="esc-context">
                <span class="lbl">{{ __('helpdesktickets::helpdesktickets.inbox_escalate.context') }}</span>
                {{-- El '#' lo pone la traducción, no la plantilla: cuando además
                     lo escribía el marcado, el JS que refresca el contexto al
                     abrir el modal lo duplicaba y salía "Conversación ##2226". --}}
                <span class="val"
                      id="bv-ticket-context"
                      data-tpl-one="{{ $escCtx[0] }}"
                      data-tpl-many="{{ $escCtx[1] }}">
                    {{ trans_choice('helpdesktickets::helpdesktickets.inbox_escalate.context_value', $escMessages, ['id' => $convo?->id ?? '—', 'count' => $escMessages]) }}
                </span>
            </div>

            {{-- Aviso de escalado repetido. Se rellena también desde el JS tras
                 crear un ticket, porque el modal es un único nodo por página y
                 puede reabrirse sin recargar la bandeja. --}}
            <div class="esc-dup {{ $escExisting->isEmpty() ? 'bv-hidden' : '' }}" id="bv-ticket-duplicates">
                <i class="fa-solid fa-circle-info"></i>
                <div>
                    <span class="lbl" id="bv-ticket-dup-label">
                        {{ $escExisting->count() === 1
                            ? __('helpdesktickets::helpdesktickets.inbox_escalate.duplicate_one')
                            : __('helpdesktickets::helpdesktickets.inbox_escalate.duplicate_many', ['count' => $escExisting->count()]) }}
                    </span>
                    <span class="vals" id="bv-ticket-dup-list">
                        @foreach($escExisting as $escTicket)
                            <a href="{{ route('manager.helpdesk.tickets.show', $escTicket) }}" target="_blank" rel="noopener">
                                {{ $escTicket->ticket_number }}</a>{{ !$loop->last ? ',' : '' }}
                        @endforeach
                    </span>
                </div>
            </div>

            <div class="field">
                <div class="flabel">
                    {{ __('helpdesktickets::helpdesktickets.inbox_escalate.subject') }} <span class="req">*</span>
                </div>
                <input type="text" class="finput" id="bv-ticket-subject"
                    maxlength="191"
                    value="{{ $convo?->subject ?? '' }}"
                    placeholder="{{ __('helpdesktickets::helpdesktickets.inbox_escalate.subject_placeholder') }}">
            </div>

            <div class="field">
                <div class="flabel">{{ __('helpdesktickets::helpdesktickets.inbox_escalate.priority') }}</div>
                {{-- Los value SON el vocabulario del módulo: low|normal|high|urgent.
                     "Normal" mandaba data-priority="medium", que no existe en
                     ninguna parte de HelpdeskTickets — se guardaba tal cual y
                     dejaba el ticket fuera del orden por prioridad, sin color en
                     el panel y sin match de SLA. Mismo fallo que ya se corrigió
                     en los formularios create/edit del panel. --}}
                <div class="prio-bar" id="bv-ticket-priority">
                    <button type="button" class="prio-card" data-priority="low">
                        <div class="ic"><i class="fa-solid fa-chevron-down"></i></div>
                        <span class="lbl">{{ __('helpdesktickets::helpdesktickets.priority.low') }}</span>
                    </button>
                    <button type="button" class="prio-card on" data-priority="normal">
                        <div class="ic"><i class="fa-solid fa-minus"></i></div>
                        <span class="lbl">{{ __('helpdesktickets::helpdesktickets.priority.normal') }}</span>
                    </button>
                    <button type="button" class="prio-card" data-priority="high">
                        <div class="ic"><i class="fa-solid fa-chevron-up"></i></div>
                        <span class="lbl">{{ __('helpdesktickets::helpdesktickets.priority.high') }}</span>
                    </button>
                    <button type="button" class="prio-card" data-priority="urgent">
                        <div class="ic"><i class="fa-solid fa-angles-up"></i></div>
                        <span class="lbl">{{ __('helpdesktickets::helpdesktickets.priority.urgent') }}</span>
                    </button>
                </div>
            </div>

            <div class="frow">
                <div class="field">
                    <div class="flabel">{{ __('helpdesktickets::helpdesktickets.inbox_escalate.category') }}</div>
                    {{-- data-sla: el ticket hereda la política de la categoría, así
                         que el agente ve qué compromiso está fijando. Sin categoría
                         el ticket nace sin SLA y eso también se dice. --}}
                    <select class="fselect select2" id="bv-ticket-category"
                            data-sla-none="{{ __('helpdesktickets::helpdesktickets.inbox_escalate.sla_none') }}"
                            data-sla-prefix="{{ __('helpdesktickets::helpdesktickets.inbox_escalate.sla_prefix') }}">
                        <option value="">{{ __('helpdesktickets::helpdesktickets.inbox_escalate.category_none') }}</option>
                        @foreach($escTickets->getCategories() as $cat)
                            <option value="{{ $cat['id'] }}" data-sla="{{ $cat['sla'] ?? '' }}">{{ $cat['name'] }}</option>
                        @endforeach
                    </select>
                    <div class="fhint" id="bv-ticket-sla-hint">
                        {{ __('helpdesktickets::helpdesktickets.inbox_escalate.sla_none') }}
                    </div>
                </div>
                <div class="field">
                    <div class="flabel">{{ __('helpdesktickets::helpdesktickets.inbox_escalate.group') }}</div>
                    <select class="fselect select2" id="bv-ticket-group">
                        <option value="">{{ __('helpdesktickets::helpdesktickets.inbox_escalate.group_none') }}</option>
                        @foreach($escTickets->getTicketGroups() as $group)
                            <option value="{{ $group['id'] }}">{{ $group['name'] }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="frow">
                <div class="field">
                    <div class="flabel">{{ __('helpdesktickets::helpdesktickets.inbox_escalate.agent') }}</div>
                    {{-- Agrupados por disponibilidad real: la lista tenía doce
                         agentes de los que once no han entrado nunca al panel, y
                         nada en pantalla lo decía. El estado va en la propia
                         opción para que se vea también con el select cerrado. --}}
                    @php $escAgents = $escTickets->getAssignableAgents()->groupBy(fn ($a) => $a['available'] ? 'ok' : 'ko'); @endphp
                    <select class="fselect select2" id="bv-ticket-assignee">
                        <option value="">{{ __('helpdesktickets::helpdesktickets.inbox_escalate.agent_none') }}</option>
                        @foreach(['ok' => __('helpdesktickets::helpdesktickets.inbox_escalate.agents_available'),
                                  'ko' => __('helpdesktickets::helpdesktickets.inbox_escalate.agents_unavailable')] as $escKey => $escGroupLabel)
                            @if(($escAgents[$escKey] ?? collect())->isNotEmpty())
                                <optgroup label="{{ $escGroupLabel }}">
                                    @foreach($escAgents[$escKey] as $agent)
                                        <option value="{{ $agent['id'] }}"
                                            data-status="{{ $agent['status'] }}"
                                            {{ $convo?->assignee_id == $agent['id'] ? 'selected' : '' }}>
                                            {{ $agent['name'] }} · {{ $agent['status_label'] }}
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endif
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="field">
                <div class="flabel">
                    {{ __('helpdesktickets::helpdesktickets.inbox_escalate.description') }}
                    <span class="hint">{{ __('helpdesktickets::helpdesktickets.inbox_escalate.description_hint') }}</span>
                </div>
                <textarea class="finput" id="bv-ticket-description" rows="4"
                    placeholder="{{ __('helpdesktickets::helpdesktickets.inbox_escalate.description_placeholder') }}"></textarea>
            </div>

            <div class="field">
                <label class="check">
                    <input type="checkbox" id="bv-ticket-attach-chat" checked>
                    {{ __('helpdesktickets::helpdesktickets.inbox_escalate.attach_transcript') }}
                </label>
                <label class="check">
                    <input type="checkbox" id="bv-ticket-notify" checked>
                    {{ __('helpdesktickets::helpdesktickets.inbox_escalate.notify_customer') }}
                </label>
            </div>
        </div>

        <div class="modal-foot">
            {{-- data-label-another: si la conversación ya tiene tickets, el botón
                 lo dice en su propia etiqueta en vez de abrir un confirm() --}}
            <button type="button" class="btn btn-primary" id="bv-btn-create-ticket"
                data-label="{{ __('helpdesktickets::helpdesktickets.inbox_escalate.submit') }}"
                data-label-another="{{ __('helpdesktickets::helpdesktickets.inbox_escalate.submit_another') }}"
                data-label-busy="{{ __('helpdesktickets::helpdesktickets.inbox_escalate.submitting') }}">
                {{ $escExisting->isEmpty()
                    ? __('helpdesktickets::helpdesktickets.inbox_escalate.submit')
                    : __('helpdesktickets::helpdesktickets.inbox_escalate.submit_another') }}
            </button>
            <button class="btn btn-outline" data-bv-close>
                {{ __('helpdesktickets::helpdesktickets.inbox_escalate.cancel') }}
            </button>
        </div>
    </div>
</div>

{{-- Los assets propios del modal se cargan aquí, no en conversations.css/js:
     son de HelpdeskTickets y con la integración apagada no deben servirse.
     El <link> va en el cuerpo a propósito — un @push('styles') desde un partial
     del body ya no llega al <head>, que se emitió antes (mismo motivo por el
     que el resto de modales del inbox tienen su CSS en conversations.css). --}}
<link rel="stylesheet" href="{{ asset('modules/helpdesktickets/css/inbox-create-ticket.css') }}">

@push('scripts')
<script src="{{ asset('modules/helpdesktickets/js/inbox-create-ticket.js') }}"></script>
<script>
window.bvTicketI18n = {
    subjectRequired: @json(__('helpdesktickets::helpdesktickets.inbox_escalate.subject_required')),
    noConversation: @json(__('helpdesktickets::helpdesktickets.inbox_escalate.no_conversation')),
    error: @json(__('helpdesktickets::helpdesktickets.inbox_escalate.error')),
    viewTicket: @json(__('helpdesktickets::helpdesktickets.inbox_escalate.view_ticket')),
};
$(function () {
    // dropdownParent: igual que el resto de selects dentro de un .bv-modal
    // (z-index:1080) — sin esto, el desplegable de select2 (z-index:1051 por
    // defecto) se renderiza detrás del propio modal.
    $('#bv-ticket-category, #bv-ticket-assignee, #bv-ticket-group').select2({
        width: '100%',
        dropdownParent: $('[data-bv-modal-name="create-ticket"]'),
    });
});
</script>
@endpush
