{{--
  Slot del módulo HelpdeskTickets. La vista real vive en HelpdeskTickets para
  que Helpdesk no contenga datos ni clases del módulo de tickets. Solo se
  renderiza cuando la integración está habilitada (helpdesk_tickets_enabled()).
--}}
@if(helpdesk_tickets_enabled())
    @include('helpdesktickets::inbox-slots.create-ticket-modal', [
        'convo' => $selectedConversation ?? null,
    ])
@endif
