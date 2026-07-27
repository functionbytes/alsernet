---
name: audit_helpdesklivechat_2026
description: HelpdeskLivechat widget backend audit Jul 2026 — critical customer_id IDOR in conversation creation bypasses the module's own token protection
metadata:
  type: project
---

Audit of `modules/HelpdeskLivechat` (public widget backend PHP, ~5k lines) on 2026-07-06.

**Critical: `WidgetConversationService::createConversation()` trusts client-supplied `customer_id`.**
File: `modules/HelpdeskLivechat/app/Services/Widget/WidgetConversationService.php:66-89`.
`POST /hd/api/conversation` (`StoreWidgetConversationRequest`) accepts an optional `customer_id` integer
with no ownership proof. If it resolves to a real `Customer`, the service (a) overwrites that customer's
`email`/`name`/`custom_attributes` with attacker-supplied values, and (b) if that customer has an open
conversation in the inbox, returns its `widget_pubsub_token` — the same secret [[VerifiesConversationToken]]
relies on everywhere else in the module to gate read/write access to a conversation. Since `Customer` ids
are sequential, this is a full IDOR/account-hijack path that bypasses the token model used consistently by
the rest of the controller (`WidgetConversationController::authorizeConversation`, `LivestreamController`,
`WebRtcSignalingController` all correctly use `hash_equals` against `widget_pubsub_token`). This is the
single entry point that doesn't, because it runs *before* any token exists.

**Warning: `PreChatFormApiController::ownsConversation()` uses guessable IDs instead of the token.**
File: `modules/HelpdeskLivechat/app/Http/Controllers/Api/PreChatFormApiController.php:97-116`.
Route mounted at `api/v1/helpdesk-livechat/pre-chat-form` with only `['api','throttle:60,1']` — not behind
`ValidateTrustedOrigin`/`VerifyWidgetHmac`/conversation token like the `hd/api` widget routes. Authorization
is just "does the submitted `customer_id` (sequential) or `customer_email` (client-supplied, unverified)
match the conversation's customer" — should instead require `X-Conversation-Token` like everything else.

**Pattern confirmed good** (for future reference/reuse): [[VerifiesConversationToken]] trait
(`modules/HelpdeskLivechat/app/Concerns/VerifiesConversationToken.php`) — per-conversation
`widget_pubsub_token` (32-byte random, stored in `conversation.metadata`, compared with `hash_equals`)
handed to the visitor only at creation. This is the correct model; any new widget endpoint touching an
existing conversation should use it, and the `customer_id` trust bug above is exactly what happens when an
endpoint doesn't.

**Also confirmed clean**: no raw SQL, no `$guarded = []`, no SSRF surface (no outbound HTTP calls in this
module), visitor message bodies sanitized with `clean()` (HTMLPurifier) before persisting, transcript email
Blade view uses `e()` before `nl2br`, `WidgetAssetController::chunk` filename regex blocks path traversal,
admin Settings controllers gate with real Spatie `can:` middleware.
