---
name: audit_document_webhooks_helpdeskagents_2026
description: Security audit jul-2026 pass 4 — Document module PrestaShop webhook unauthenticated, ERP webhook fail-open; HelpdeskAgents/HelpdeskIntegration confirmed hardened
metadata:
  type: project
---

Audit performed 2026-07-05, follow-up pass focused on modules NOT covered by [[audit_chatflow_inbox_2026]], [[audit_tickets_livechat_contacts_2026]], [[audit_analytics_helpcenter_campaigns_2026]]: HelpdeskAgents, HelpdeskIntegration, HelpdeskErp, HelpdeskPrestashop, HelpdeskSocial, Document (base module webhooks).

## New confirmed findings

- **CRITICAL**: `modules/Document/app/Http/Controllers/Api/DocumentsController.php:1627` `prestashopOrderPaid()`, routed at `modules/Document/routes/api.php:20` inside the public `['api','throttle:60,1']` group with ZERO authentication/signature check. Accepts `order_id`/`id_order`, looks up `PrestashopOrder::find()`, and creates a `Document` record for ANY existing order. Returns 404 for non-existent orders vs success for real ones → unauthenticated order-ID enumeration oracle, plus spam-creation of Document rows for arbitrary real orders. Contrast with the properly-secured sibling webhooks: `HelpdeskErp\Http\Controllers\Api\WebhookController::ordersReady` (HMAC-SHA256 + timestamp window, fail-closed 503 if secret unset) and `HelpdeskPrestashop`'s `PsEventReceiverController` (protected by `VerifyAlsernetHmac` middleware, fail-closed). Fix: add the same HMAC+timestamp middleware pattern to this route, or fail-closed 503 if no secret configured.

- **HIGH**: `modules/Document/app/Http/Controllers/Api/DocumentsController.php:1679-1688` `erpOrderStatus()` — auth is fail-OPEN: `if ($secret) { check token }` — when `config('services.erp.webhook_secret')` is empty/unset, the entire token check is skipped and the endpoint accepts unauthenticated requests that create/update Document status (including customer PII: name/email/phone/dni). Also compares token with `!==` instead of `hash_equals()` (minor timing-attack surface). Fix: fail-closed (503) when secret unset, like `HelpdeskErp\WebhookController`; use `hash_equals()`.

- **LOW/INFO**: `modules/Document/routes/web.php:31-33` — `panel/documents/modals-preview` route sits outside the `role:super-admin|supervisor` group (only `web,auth`), despite an inline comment "solo dev — quitar en produccion". Any authenticated user (not just document validators) can load this internal preview view. Dead code / minor info disclosure, not data-sensitive by itself but should be removed per its own comment.

## Confirmed NOT vulnerable (good patterns, rule out in future passes)

- `HelpdeskErp\Http\Controllers\Api\WebhookController::ordersReady` — HMAC-SHA256(timestamp:body) + hash_equals + ±300s timestamp window + fail-closed 503 if secret unset. Reference pattern.
- `HelpdeskPrestashop\PsEventReceiverController` — signature verified by `VerifyAlsernetHmac` middleware (`modules/HelpdeskPrestashop/app/Http/Middleware/VerifyAlsernetHmac.php`), fail-closed, plus two-phase idempotency cache lock. Good pattern.
- `HelpdeskSocial\MetaWebhookController` — fail-closed 401 if `app_secret` unset, signature verified via `WebhookVerifierInterface`.
- `HelpdeskAgents\AiAgentFlowEngine` — already hardened: `PromptSanitizer` (truncation, control-char stripping, injection-pattern filtering + security-channel logging) applied to all user input before LLM calls; per-user/per-session/per-day `RateLimiter` gates before every provider call; `CircuitBreaker` per provider; ReDoS-safe regex evaluation in condition nodes (backtrack limit + validated pattern). No prompt-injection or SSRF/DoS gap found here.
- `HelpdeskIntegration\CustomerIntegrationsController` + its Form Requests (`Link/Unlink/SearchCustomerIntegrationRequest`) — every mutating/search action requires BOTH `$user->can('view'|'update', $customer)` AND `CustomerIdentityVerificationService::isVerified($customer)` inside `authorize()`. Well-built, consistent identity gate.
- `HelpdeskSocial` — module confirmed still `false` (disabled) in `modules_statuses.json` (both `HelpdeskSocial` and legacy `Social`). Permission names now consistent lowercase `helpdesksocial.*` (accounts.manage, analytics.view, rules.manage, templates.manage, view) — the previously-reported permission-naming crash (roadmap "2 críticos", Social score 56) appears fixed. No live attack surface while disabled.

## Not fully covered this pass (follow-up candidates)
HelpdeskCompliance, HelpdeskSla, HelpdeskEmailLog, HelpdeskTranslate, HelpdeskSocial API controllers (SocialTags/SocialRules/SocialAssignmentRules/etc. — only the Meta webhook + permission names were checked, not each controller's authorize() calls), HelpdeskErp/HelpdeskPrestashop Managers controllers (only the webhook receivers were checked).

**Why:** requested follow-up security pass explicitly excluding previously-fixed findings (XSS, SSRF, IDOR already covered in prior sessions — see [[project_helpdesk_modules_roadmap]]).
**How to apply:** when auditing any new webhook endpoint in this codebase, check for the HMAC+timestamp+fail-closed pattern from HelpdeskErp/HelpdeskPrestashop before assuming it's covered — Document module's own duplicate webhook routes were missed by earlier passes that focused on the Helpdesk* satellite modules' dedicated webhook controllers.
