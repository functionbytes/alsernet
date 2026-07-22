# Frontera entre los dos motores SLA del Helpdesk

Hay dos motores SLA deliberadamente separados. **No se unifican**: cada uno es
dueño de su entidad, de su tabla de breaches y de su ciclo de vida. Este doc
fija la frontera para que ningún cambio futuro cree una tercera fuente de
verdad ni cruce escrituras entre tablas.

## Conversaciones → módulo `HelpdeskSla`

- Servicio: `Modules\HelpdeskSla\Services\ConversationSlaService`.
- Entidad: `helpdesk_conversations` (flags `sla_*` en la propia fila).
- Auditoría: `helpdesk_conversation_sla_breaches`
  (`Modules\HelpdeskSla\Models\ConversationSlaBreach`).
- Comportamiento: inicializa/recalcula vencimientos, **registra breaches**,
  emite `SlaBreached` / `SlaWarningThreshold` y pausa/reanuda el reloj.
- **No escala prioridades.**
- Programación: `helpdesksla:check-breaches`, `helpdesksla:send-warnings`,
  `helpdesksla:prune-breaches` (en `HelpdeskSlaServiceProvider`, con
  `withoutOverlapping()` + `onOneServer()` y gate `helpdesk_sla_enabled()`).

## Tickets → módulo `HelpdeskTickets`

- Servicios: `Modules\HelpdeskTickets\Services\SlaService` (cálculo de
  vencimientos, marca `sla_resolution_breached`, avisos) y
  `Modules\HelpdeskTickets\Services\EscalationService` (escalado de prioridad
  por edad o por SLA).
- Entidad: `helpdesk_tickets`.
- Auditoría: `helpdesk_ticket_sla_breaches`
  (`Modules\HelpdeskTickets\Models\TicketSlaBreach`; esquema alineado por la
  migración `2026_07_22_000000_align_helpdesk_ticket_sla_breaches_with_model`).
- Comportamiento: **escala prioridades** y, cuando la escalada es por un SLA
  ya vencido, `EscalationService::recordSlaBreachAudit()` registra el
  incumplimiento en `helpdesk_ticket_sla_breaches` (idempotente: un solo
  breach de resolución sin resolver por ticket).

## Reglas

1. `HelpdeskSla` nunca escribe en tablas de tickets; `HelpdeskTickets` nunca
   escribe en `helpdesk_conversation_sla_breaches`.
2. Cada dashboard/consulta de breaches lee SOLO la tabla de su entidad.
3. Si un requisito parece necesitar cruzar la frontera, se resuelve con un
   evento del módulo dueño, no con una escritura directa.
