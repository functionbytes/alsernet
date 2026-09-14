{{--
  Slot del módulo HelpdeskTickets. La vista real vive en HelpdeskTickets para
  que Helpdesk no contenga datos ni clases del módulo de tickets. Solo se
  renderiza cuando la integración está habilitada (helpdesk_tickets_enabled()).
--}}
{{-- Mismas tres condiciones que el botón del hilo (thread.blade.php): antes el
     modal se renderizaba con solo helpdesk_tickets_enabled(), así que en una
     bandeja con la feature apagada —o para quien no puede crear tickets— el
     marcado y sus dos queries seguían saliendo en cada carga sin que hubiera
     forma de abrirlo. --}}
@if(helpdesk_tickets_enabled()
    && helpdesk_feature_enabled('tickets')
    && app(\Modules\Helpdesk\Contracts\TicketServiceContract::class)->canCreateTickets())
    @include('helpdesktickets::inbox-slots.create-ticket-modal', [
        'convo' => $selectedConversation ?? null,
    ])
@endif
