# ADR 0002: Bridge contract entre Helpdesk y HelpdeskTickets

- **Estado**: Aceptado
- **Fecha**: 2026-05-05
- **Decisor**: arquitectura

---

## Contexto

`HelpdeskTickets` es un módulo opcional que extiende `Helpdesk` con tickets,
SLAs, automatizaciones, macros, plantillas, recurrentes y portal de cliente.
Antes de este ADR, `Helpdesk` importaba directamente clases de `HelpdeskTickets`
en 11 archivos (controllers, listeners, services, views), lo que rompía el
principio de que un módulo opcional debe ser desinstalable sin romper el módulo
base.

Síntomas concretos del acoplamiento:

- `ConversationsController::createTicket()` y `ticketDetail()` instanciaban
  `Modules\HelpdeskTickets\Models\Ticket` directamente.
- `right-panel.blade.php` y `create-ticket.blade.php` consultaban
  `\Modules\HelpdeskTickets\Models\Ticket` y `TicketCategory` con `class_exists()`
  como única defensa.
- `NotificationService` (en `Helpdesk`) era llamado por
  `HelpdeskTickets\SlaService` y `AssignmentService` formando una dependencia
  circular conceptual.
- `NotifyAgentOfAssignment` listener vivía en `Helpdesk` pero escuchaba un
  evento `Modules\HelpdeskTickets\Events\TicketAssigned`.
- Las migraciones de tablas `helpdesk_tickets*` se crean desde `Helpdesk`
  (no se cambia para preservar instalaciones existentes — ver "Pendientes").

## Decisión

Introducir un **bridge contract** (NullObject pattern) entre los dos módulos:

```
Helpdesk
 ├─ Contracts\TicketServiceContract (interfaz)
 └─ Services\NullTicketService      (no-op fallback)

HelpdeskTickets
 └─ Services\HelpdeskTicketBridgeService (implementación real)
```

`HelpdeskServiceProvider` resuelve el contrato condicionalmente:

```php
$this->app->singleton(TicketServiceContract::class, function () {
    if (helpdesk_tickets_enabled()) {
        return $this->app->make(HelpdeskTicketBridgeService::class);
    }
    return new NullTicketService;
});
```

El helper `helpdesk_tickets_enabled()` combina tres señales:

1. `config('helpdesk.tickets.enabled')` (`HELPDESK_TICKETS_ENABLED` env var).
2. `class_exists(\Modules\HelpdeskTickets\Models\Ticket::class)` (autoload).
3. `Module::find('HelpdeskTickets')->isEnabled()` (nwidart/laravel-modules).

## Patrón de uso

**En Helpdesk** (cualquier punto que necesita datos/acciones de tickets):

```php
$tickets = app(\Modules\Helpdesk\Contracts\TicketServiceContract::class);

if ($tickets->isAvailable()) {
    $list = $tickets->getCustomerTickets($customer, 5);
    $created = $tickets->createFromConversation($conversation, $payload);
}
```

**En views**:

```blade
@if(helpdesk_tickets_enabled())
    @include('helpdesktickets::inbox-slots.create-ticket-modal')
@endif
```

## Consecuencias

### Positivas

- `Helpdesk` puede arrancar sin `HelpdeskTickets`. El feature gate `false`
  oculta toda la UI de tickets, los endpoints `/conversations/{c}/ticket*`
  devuelven 404/422 con mensaje claro, y el dashboard muestra stats vacías.
- `Helpdesk\app\Services\` y `Helpdesk\app\Listeners\` quedan **0 imports**
  de `Modules\HelpdeskTickets\`.
- `Helpdesk\resources\views\` quedan **0 imports** de `Modules\HelpdeskTickets\`
  ejecutables (sólo comentarios y `@include('helpdesktickets::...')`).
- Las URLs y route names (`manager.helpdesk.conversations.ticket`,
  `helpdesk.feedback.show`) se mantienen idénticos — el frontend JS no
  necesita cambios.
- Tests unitarios cubren los tres caminos del helper, NullService y bridge.

### Negativas / pendientes

- `DashboardController`, `AgentsController`, `ReportsController` en `Helpdesk`
  todavía importan `Modules\HelpdeskTickets\Models\Ticket`. Están protegidos
  con `helpdesk_tickets_enabled()` runtime — son safe pero acoplados a nivel
  de import. Mover requeriría mover también las views y rutas, refactor mayor.
- Las migraciones de tablas `helpdesk_tickets*` siguen en
  `modules/Helpdesk/database/migrations/`. Mover destruiría timestamps en
  instalaciones existentes; aplazado.

## Archivos relevantes

- `app/helpers.php` — `helpdesk_tickets_enabled()`
- `modules/Helpdesk/config/helpdesk.php` — flag `tickets.enabled`
- `modules/Helpdesk/app/Contracts/TicketServiceContract.php`
- `modules/Helpdesk/app/Services/NullTicketService.php`
- `modules/Helpdesk/app/Providers/HelpdeskServiceProvider.php` — binding
- `modules/HelpdeskTickets/app/Services/HelpdeskTicketBridgeService.php`
- `modules/HelpdeskTickets/app/Http/Controllers/Managers/ConversationTicketBridgeController.php`
- `modules/HelpdeskTickets/app/Http/Controllers/FeedbackController.php`
- `modules/HelpdeskTickets/app/Services/TicketNotificationService.php`
- `modules/HelpdeskTickets/resources/views/inbox-slots/create-ticket-modal.blade.php`
- `modules/HelpdeskTickets/resources/views/inbox-slots/right-panel-tickets-tab.blade.php`
- `modules/HelpdeskTickets/routes/public.php`
- `modules/Helpdesk/tests/Unit/TicketsFeatureGateTest.php`

## Cómo desactivar la integración

1. `HELPDESK_TICKETS_ENABLED=false` en `.env`, o
2. `php artisan module:disable HelpdeskTickets`, o
3. `composer remove modules/helpdesktickets` (uninstall).

Cualquiera de los tres ramales hace que `helpdesk_tickets_enabled()` devuelva
`false` y todo el código de Helpdesk siga funcionando sin tickets.
