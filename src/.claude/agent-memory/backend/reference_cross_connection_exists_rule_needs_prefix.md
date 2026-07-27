---
name: cross-connection-exists-rule-needs-prefix
description: exists:table,id validation rules must prefix the connection name when the referenced table lives on a non-default DB connection (e.g. helpdesk)
metadata:
  type: reference
---

Several Helpdesk-family models (e.g. `Modules\HelpdeskTickets\Models\Ticket`) declare
`protected $connection = 'helpdesk';`, so their tables do not live on the app's default
connection. Laravel's `exists:table,column` validation rule queries the default connection
unless the connection is prefixed: `exists:helpdesk.table_name,column`.

`StoreTicketRequest.php` and `UpdateTicketRequest.php` already use the correct
`exists:helpdesk.helpdesk_*,id` form. `StoreTicketNoteRequest.php` did not (`exists:helpdesk_tickets,id`)
and silently rejected every valid `ticket_id` with "El ticket especificado no existe" — fixed
2026-07-06. `StoreTicketCommentRequest.php` has the identical unprefixed pattern
(`exists:helpdesk_tickets,id`) and is likely affected too, but was out of scope for that fix.

**How to apply:** When adding/reviewing a Form Request in a Helpdesk-family module that
validates a foreign key into a `helpdesk`-connection table, always use
`exists:helpdesk.{table},id`, not `exists:{table},id`. If a 422 "no existe" error appears for
a value you can see in the DB via the `helpdesk` connection, check this first.
