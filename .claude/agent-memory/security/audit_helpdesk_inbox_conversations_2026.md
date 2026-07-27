---
name: audit-helpdesk-inbox-conversations-2026
description: Security audit of Helpdesk inbox conversation actions (close/reopen/assign/snooze/bulk/views) — Jul 2026
metadata:
  type: project
---

Audit scope: `modules/Helpdesk/app/Http/Controllers/Managers/ConversationsController.php`,
`BulkConversationsController.php`, `ConversationViewsController.php`, `ConversationPolicy`,
`ConversationViewPolicy`, related Form Requests, `routes/managers.php`. Date: 2026-07-06.

**Fail-open authorize() bug** — `UpdateConversationRequest::authorize()` (line 9-14) does
`$this->user()?->can(...) ?? $this->user()?->can(...) ?? true`. Since `?->can()` returns a bool
(never null) when authenticated, this only fails open if `$this->user()` is null — currently
masked because the route group requires `auth` middleware. Still a landmine if this Form Request
is ever reused elsewhere. Fix: drop the `?? true` fallback.

**group_id validation gap in AJAX path** — `ConversationAjaxActionRequest::rules()` has no branch
for `group_id` (falls through to `return []`), so `ConversationsController::handleAjaxUpdate()`
reads `$request->input('group_id')` raw/unvalidated when moving a conversation between teams. The
classic form path (`UpdateConversationRequest.php:25`) DOES validate
`exists:helpdesk.helpdesk_groups,id` — inconsistent between the two code paths that do the same
thing. Fix: add the missing rule branch and use `$request->validated()`.

**ConversationPolicy::restore() skips canAccessInbox()** — unlike view/update/delete, `restore()`
only checks `helpdesk.conversations.manage`, not inbox ownership. Currently not exploitable because
every role holding `helpdesk.conversations.manage` in the DB also holds the broader `helpdesk.manage`
(verified via query), but it's a distinct permission and a future narrower-scoped role would bypass
inbox isolation for restoring soft-deleted conversations. See [[audit_chatflow_inbox_2026]] for the
earlier assertItemAccess fix this builds on.

**Confirmed FIXED since last audit**: `reactToMessage`/`forwardMessage`/`messageInfo` now all call
`assertItemAccess($item)` (private helper checking the item's conversation inbox against
`AgentInboxCapacity`) — the cross-inbox IDOR via message id noted in
[[audit_chatflow_inbox_2026]] is resolved.

**Good patterns confirmed**: `BulkConversationsController::handle()` re-validates policy `update`
per-conversation-id before any bulk mutation (protects against arbitrary IDs in bulk payload);
`downloadAttachment()` resolves owning conversation + policy `view` before streaming a file (path
regex restricted to `/storage/helpdesk/...`); `forceDelete` policy requires `super-admin` role
(stronger than a Spatie permission) — good pattern for GDPR-style irreversible deletes.
