---
name: audit_helpdeskagents_ssrf_2026
description: Security audit jul-2026 pass 5 — HelpdeskAgents SSRF gaps in LLM connection tester and tool executor (redirect bypass); orphaned shifts/oncall policies
metadata:
  type: project
---

Audit performed 2026-07-06, targeted re-audit of `modules/HelpdeskAgents` (agent presence/schedule + AI agent config), superseding the "confirmed hardened" summary in [[audit_document_webhooks_helpdeskagents_2026]] which only checked the AiAgentFlowEngine prompt-injection/rate-limit surface, not the HTTP-calling services below.

## New confirmed findings

- **HIGH**: `modules/HelpdeskAgents/app/Services/LlmConnectionTesterService.php:72-91` `testLocal()` — the `base_url` used to test an Ollama/local LLM connection is fully user-controlled (validated only by `TestAiAgentConnectionRequest:20` with Laravel's `url` rule — no scheme/host/private-IP restriction) and is fetched directly via `Http::timeout(10)->get($baseUrl.'/api/tags')`. This is an unrestricted SSRF: any user with `helpdesk.aiagents.manage` can point it at internal services/metadata endpoints and read back status/body via the thrown error message. Contrast with the sibling `ToolExecutionService::validateApiUrl()` in the same module which DOES enforce HTTPS + private-IP blocking + optional host allowlist. Fix: apply the same SSRF guard before calling `Http::get()`.

- **HIGH**: `modules/HelpdeskAgents/app/Services/ToolExecutionService.php:82-84` `executeApiTool()` — `validateApiUrl()` (lines 101-125) checks the *initial* URL only (HTTPS-only + `gethostbyname` private-IP block + optional allowed_hosts). The actual `Http::get($url)`/`->post($url, ...)` call keeps Guzzle's default `allow_redirects => true`, so a malicious/compromised external HTTPS host can 3xx-redirect the request to an internal/private address and bypass the private-IP check entirely (classic SSRF-via-redirect). Fix: disable redirects or re-validate every redirect hop.

- **MEDIUM**: `modules/HelpdeskAgents/app/Policies/AgentShiftPolicy.php` and `OncallRotationPolicy.php` gate on `helpdesk.shifts.*`/`helpdesk.oncall.*` permissions that are never seeded anywhere (only `helpdesk.schedule.view`/`.update` exist and are what `ScheduleController` route middleware + the Store*Request `authorize()` methods actually check). Policies registered via `Gate::policy()` in `HelpdeskAgentsServiceProvider.php:79-81` but never invoked by any controller today — dead code, not currently exploitable, but a latent trap if someone later authorizes through them expecting the `helpdesk.schedule.*` semantics.

## Confirmed NOT vulnerable (good patterns)

- `ToolExecutionService::executeDatabaseTool` — fail-closed: SELECT-only, blocks `;`/`--`/`/*`, enforces explicit table allowlist even when DB tools are config-enabled.
- `ToolExecutionService::executeFunctionTool` — explicit allowed-functions config allowlist, no arbitrary `call_user_func` from request input.
- All 17 Form Requests in this module have real `authorize()` checks (no `return true`); all controllers (`AiAgentFlowsController`, `AiToolsController`, `AiKnowledgeController`, `AiTagsController`, `AgentSettingsController`, `ScheduleController`) call `$this->authorize()`/`can:` consistently with real Spatie permissions.
- `AiToolExecution::$guarded = []` model exists but is dead code for mass-assignment purposes — actual execution-log writes go through `DB::table(...)->insert()` in `ToolExecutionService::logExecution()`, not `AiToolExecution::create()`.
- No `{!! !!}` XSS in this module's Blade views.

**Why:** requested audit specifically of HelpdeskAgents "gestión de agentes, presencia, macros, modo ausente, reasignación" — module actually has no macros/reassignment concept (it's AI-chatbot config + human agent shift/vacation/oncall scheduling); the real attack surface turned out to be the two outbound-HTTP services (LLM connection tester + AI tool executor), not the schedule/permissions layer which is solid.
**How to apply:** when auditing any Laravel HTTP client call built from admin-configurable or request-supplied URLs in this codebase, check both (a) whether private-IP/scheme validation exists at all, and (b) whether redirects are disabled/re-validated — validating the pre-request URL is not sufficient on its own if Guzzle's default redirect-following is left enabled.
