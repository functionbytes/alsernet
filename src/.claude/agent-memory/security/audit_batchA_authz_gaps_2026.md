---
name: audit_batchA_authz_gaps_2026
description: Read-only verification jul-2026 of 6 roadmap authz gaps (merge, tickets store, emaillog bulkDestroy, social assign/bulk, document routes) — all 6 confirmed already fixed in current code
metadata:
  type: project
---

Verified 2026-07-06 (read-only pass, no edits) against the 6 "Batch A" authorization gaps listed in the roadmap ([[project_helpdesk_modules_roadmap]]). All 6 are now YA-RESUELTO — no live vulnerability found in current `v2` branch code.

1. **Conversation merge() IDOR** — `modules/Helpdesk/app/Http/Controllers/Managers/ConversationsController.php` `merge()` (not `mergeCandidates()`). Authorizes BOTH source (`$this->authorize('update', $conversation)`) AND target (`$this->authorize('update', $target)` after `Conversation::findOrFail($targetId)`), plus rejects cross-customer merges (422 if `$target->customer_id !== $conversation->customer_id`). Inline comment explicitly documents why target is checked too.

2. **HelpdeskTickets store() missing authorize()** — `TicketsCrudController::store()` uses `StoreTicketRequest` whose `authorize()` returns `$this->user()?->can('helpdesk.tickets.create') ?? false`. Correctly gated.

3. **HelpdeskEmailLog bulkDestroy() missing authorize('deleteAny')** — `EmailLogController::bulkDestroy()` calls `$this->authorize('deleteAny', EmailLog::class)` before iterating deletes. Comment notes it was added for consistency with `destroy()`.

4/5. **HelpdeskSocial assign()/bulk() weak permission** — controller moved from `Managers/` to `Api/SocialInboxController.php` since the original roadmap entry was written. Both `assign()` and `bulk()` gate on `helpdesksocial.manage` (not the weaker `view`), with defense-in-depth: Form Request (`AssignSocialCommentRequest`/`BulkSocialCommentRequest`) `authorize()` AND an explicit `abort_if(! auth()->user()?->hasPermissionTo('helpdesksocial.manage'), 403)` inside the controller method. Module remains disabled (`"HelpdeskSocial": false` in `modules_statuses.json`), consistent with [[audit_document_webhooks_helpdeskagents_2026]].

6. **HelpdeskDocument single `view` permission gating destructive routes** — `modules/HelpdeskDocument/routes/managers.php` now explicitly splits read-only routes (panel, action-history, download-zip, list) on `can:helpdesk.conversations.view` from ALL mutating routes (file destroy, upload, assign, approve-stage, reject-stage, every send-*, notes add/delete, upload/delete-attachment, update) on `can:helpdesk.documents.manage`. File has an inline comment documenting the historical bug and its fix.

**Why:** roadmap line numbers had drifted from repeated refactors; user asked for a skeptical re-check by method name rather than trusting stale line numbers.
**How to apply:** when this roadmap file is referenced again, these 6 items can be marked closed/resolved rather than re-audited from scratch — only re-verify if a diff touches `ConversationsController::merge`, `StoreTicketRequest`, `EmailLogController::bulkDestroy`, `SocialInboxController` (now under `Api/`, not `Managers/`), or `HelpdeskDocument/routes/managers.php`.
