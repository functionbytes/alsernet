---
name: helpdesktickets-manager-routes-require-role
description: HelpdeskTickets manager.helpdesk.* routes are gated by role:super-admin|super-settings middleware, not just Spatie permissions — tests must assignRole too
metadata:
  type: feedback
---

All routes loaded from `modules/HelpdeskTickets/routes/managers.php` are wrapped in
`Route::middleware(['web', 'auth', 'role:super-admin|super-settings'])` inside
`HelpdeskTicketsServiceProvider::loadManagerRoutes()` (around line 283). This applies to
every `manager.helpdesk.tickets.*` route (tickets CRUD, notes, comments, time entries, etc.).

**Why:** Feature tests that only do `$user->givePermissionTo([...])` in `setUp()` without
also `$user->assignRole('super-settings')` (or `super-admin`) get a 403 from the route
middleware *before* the controller/FormRequest/Policy ever runs. This looks identical to a
policy-authorization failure but has nothing to do with the Policy or Form Request logic —
diffing against the actual policy code will be a red herring. Confirmed while fixing
`TicketNotesControllerTest` (2026-07-06): 7/9 tests were 403ing purely because the test's
`$this->agent` lacked the role, even though `TicketNotePolicy` ownership logic
(`note->user_id === user->id`) was correct.

**How to apply:** When a HelpdeskTickets manager-namespace feature test 403s unexpectedly,
check role middleware first (`grep -n "role:" modules/HelpdeskTickets/app/Providers/HelpdeskTicketsServiceProvider.php`)
before assuming the Policy/Form Request is wrong. Fix the test by adding
`$user->assignRole('super-settings')` in `setUp()` — this is the established pattern used by
sibling tests like `ManagersTicketsCrudTest`. Do not relax the policy or remove the route
middleware to make a test pass. See [[reference_helpdesk_sla_gotchas]] for a related
priority-slug gotcha in the same module family.
