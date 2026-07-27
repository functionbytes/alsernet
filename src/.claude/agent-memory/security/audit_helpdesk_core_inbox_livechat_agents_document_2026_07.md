---
name: audit_helpdesk_core_inbox_livechat_agents_document_2026_07
description: Consolidated re-audit Jul 2026 of Helpdesk core/inbox, HelpdeskLivechat, HelpdeskAgents, HelpdeskDocument — confirms prior critical IDOR/upload fixes landed, HelpdeskAgents SSRF still open
metadata:
  type: project
---

Audit 2026-07-06, v2 branch, HEAD at commit `6d58d7ee` (today's "auditoría, fixes responsive y carga de CSS" commit). Scope: Helpdesk core inbox/conversations, HelpdeskLivechat, HelpdeskAgents, HelpdeskDocument.

## Confirmed FIXED since last audits (verified in current code, not just commit message)

- `WidgetConversationService::createConversation()` customer_id IDOR (was CRITICAL in [[audit_helpdesklivechat_2026]]) — fixed in commit `bcf7d89f`: identity now resolved server-side via `WidgetSession.customer_id` bound to `widget_session_token`; client-supplied `customer_id` fully ignored; guest-email promotion only applies to the session's own customer (`@anonymous.local` check). Regression test `WidgetConversationSecurityTest::test_store_ignores_client_supplied_customer_id` passes.
- `UploadDocumentFilesRequest` missing mime whitelist (was HIGH, RCE/stored-XSS via media-library public disk) — fixed in commit `c7a67c83`: `mimes:pdf,jpg,jpeg,png,doc,docx` added, unit test rejects `.php`.
- `UpdateConversationRequest::authorize()` fail-open `?? true` — fixed, now `?? false` (`modules/Helpdesk/app/Http/Requests/UpdateConversationRequest.php:11`).
- `ConversationAjaxActionRequest` missing `group_id` validation branch — fixed, now validates `exists:helpdesk.helpdesk_groups,id` (line 49-53).
- `ConversationPolicy::restore()` skipping `canAccessInbox()` — fixed, now calls it (line 45-49).
- `reactToMessage`/`forwardMessage`/`messageInfo` missing inbox-scope — confirmed still fixed via `assertItemAccess()` private helper in `ConversationsController.php`.
- `merge()` IDOR — confirmed fixed: authorizes both source and target conversation + rejects cross-customer merges.
- `BulkConversationsController` — good pattern confirmed: per-conversation `can('update', ...)` check before any bulk mutation, plus `bulkAssign()` verifies the new assignee has `AgentInboxCapacity` for the target inbox (or `helpdesk.manage`) before reassigning — prevents assigning conversations to agents outside their inbox.
- New "saved views" feature (`ConversationView`, `StoreInboxViewRequest`, `ConversationViewPolicy`) — real permission in `authorize()`, ownership check (`owns()`) gates `update`/`delete`, no fallback bypass.

## Still OPEN (re-verified, no fix found)

- **HIGH**: `modules/HelpdeskAgents/app/Services/LlmConnectionTesterService.php` `testLocal()` — unrestricted SSRF (no private-IP/scheme guard on admin-supplied `base_url`). See [[audit_helpdeskagents_ssrf_2026]] for full detail — unchanged.
- **HIGH**: `modules/HelpdeskAgents/app/Services/ToolExecutionService.php` `executeApiTool()` — SSRF-via-redirect (pre-request URL validated, Guzzle redirects not disabled/re-checked). Unchanged.
- **MEDIUM**: `modules/HelpdeskLivechat/app/Http/Controllers/Api/PreChatFormApiController.php` `ownsConversation()` (~line 97-116) — still authorizes via guessable `customer_id` (sequential int) or client-supplied unverified `customer_email` instead of the module's own `widget_pubsub_token`/session-token pattern used everywhere else. Route `api/v1/helpdesk-livechat/pre-chat-form` only has `['api','throttle:60,1']`. Not touched by this week's livechat fix commits — recommend applying the same session-token check used in `WidgetConversationService` fix.
- **MEDIUM (dead code, latent trap)**: `HelpdeskAgents\Policies\AgentShiftPolicy`/`OncallRotationPolicy` still gate on unseeded `helpdesk.shifts.*`/`helpdesk.oncall.*` permissions, never invoked by any controller (real gating is `helpdesk.schedule.*` in `ScheduleController`). Unchanged from prior audit.

## New observation this pass (not a vulnerability, noted for completeness)

- `ConversationsController::forwardMessage()` lets any agent with inbox access copy a message's body/attachments to a conversation with an arbitrary `customer_id` (`Customer::find($customerId)` — no relationship/ownership check between source and destination customer). This is the intended "forward to another customer" feature, gated by `helpdesk.conversations.update` + `assertItemAccess()` inbox-scope on the *source* item; there is no restriction on which customer can receive the forward. Low risk (requires an authenticated agent to deliberately misuse an intended feature), but flag if stricter data-boundary requirements are ever needed between customers.
- Livechat new features this week (queue-position polling `0f99de27`, agent-availability `is_open` `df28245d`, quick-reply chips `b113e232`) all reviewed — no new vulnerabilities: queue-position reuses `authorizeConversation()` token check and only returns a count; agent-availability only exposes a boolean; quick-reply chips render as plain React text (no `dangerouslySetInnerHTML`), admin-configured only.

**Why:** requested full re-audit of Helpdesk core inbox + HelpdeskLivechat + HelpdeskAgents + HelpdeskDocument; needed to distinguish which previously-reported findings the team actually shipped fixes for this week vs. which remain.
**How to apply:** HelpdeskAgents SSRF findings ([[audit_helpdeskagents_ssrf_2026]]) and the PreChatForm IDOR are the two concrete remaining action items for this scope; everything else in Helpdesk-core/inbox and HelpdeskLivechat's conversation-creation path can be treated as closed.
