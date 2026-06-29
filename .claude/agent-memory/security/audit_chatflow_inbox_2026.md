---
name: audit-chatflow-inbox-2026
description: Security audit of HelpdeskChatFlow takeOver endpoint and Helpdesk inbox new surfaces (Jun 2026)
metadata:
  type: project
---

## Findings — Jun 2026 audit

### IDOR in takeOver (HIGH — mitigated by role gate to medium-effective risk)
- File: `modules/HelpdeskChatFlow/app/Http/Controllers/ChatFlowsController.php:144`
- Route: `POST panel/helpdesk/chatflows/takeover/{conversationId}` (managers.php:45)
- Route group has `role:super-admin|super-settings` middleware. This is stronger than the bare `chatflow.update` permission check inside the method. Users who reach this endpoint are already super-admin/super-settings and by convention have `helpdesk.manage`, meaning they legitimately see all conversations. Therefore IDOR risk is effectively zero for the current user population but **structurally unsound** — the controller does not scope the conversation lookup by any inbox or tenant filter.
- Fix: add `abort_unless($conversation->inbox_id in $user->inboxIds || $user->can('helpdesk.manage'), 403)` inside takeOver, or use ConversationPolicy::update.

### Missing authorization on message-level endpoints (MEDIUM)
- `reactToMessage`, `forwardMessage`, `messageInfo` — routes at managers.php:212-219 — have only throttle middleware, no permission or conversation-scope check.
- `reactToMessage` (line 1294): any authenticated manager can react to any `ConversationItem` by numeric ID, regardless of which inbox/conversation it belongs to.
- `messageInfo` (line 1399): exposes delivery metadata (delivered_at, read_at, external_id) for any item — no ownership check.
- `forwardMessage` (line 1341): reads body/html_body/attachment_urls of any item and copies it to an arbitrary customer — no check that the authenticated user can see the source item's conversation.
- Fix: add `$this->authorize('update', $item->conversation)` at the top of each method, after eager-loading conversation.

### import endpoint — no file-type validation (LOW)
- `ChatFlowsController::import` (line 224): accepts arbitrary file uploads, calls `file_get_contents($request->file('file')->getRealPath())` with no mime/extension/size restrictions.
- Risk limited because `json_decode` will reject non-JSON payloads and the route requires `super-admin|super-settings`, but a crafted 100 MB file would waste memory.
- Fix: add `'file' => ['file', 'max:2048', 'mimes:json,txt']` validation before processing.

### Bot-view (?bot=1) respects inbox filter — NO issue
- `applyBotVisibility` in ConversationsController:372 applies AFTER `getUserInboxIds()` already constrains the query. The bot=1 filter only replaces the bot-exclusion scope, not the inbox filter. Confirmed safe.

### send_file node — no SSRF (LOW/INFO)
- `ChatFlowNodeExecutor::executeSendFile` (line 578): writes `file_url` as `attachment_urls` to a ConversationItem — does NOT fetch the URL server-side. The URL is passed to the client/WhatsApp channel for retrieval. No server-side HTTP request is made. Only admins with `chatflow.update` can set `file_url` in the flow editor.
- Risk: if `file_url` could contain a context variable interpolated from customer input, an attacker could inject a URL. Verify `interpolateContext` does not allow customer-controlled substitution into `file_url`. If it does, that is a stored XSS / content-injection risk, not SSRF.

### Business event launcher — no email format validation (LOW)
- `ChatFlowBusinessEventLauncher::resolveCustomer` (line 68): uses raw `$email` from event payload directly in `Customer::on('helpdesk')->where('email', $email)` and then in `Customer::on('helpdesk')->create(['email' => $email, ...])`.
- Events come from PS/ERP webhooks (HMAC-validated upstream), so the trust boundary is the webhook signature, not this layer. Risk is low but a malformed email (e.g. 512 chars) would be stored without validation.
- Fix: add `filter_var($email, FILTER_VALIDATE_EMAIL)` guard before create.

**Why:** These patterns were found by manual code review and structured audit requested by user.
**How to apply:** When reviewing new helpdesk endpoints check (1) inbox-scope on conversation lookups, (2) item-level ownership before write/read, (3) file upload type/size limits.
