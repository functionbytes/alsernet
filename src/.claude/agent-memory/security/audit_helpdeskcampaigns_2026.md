---
name: audit-helpdeskcampaigns-2026
description: HelpdeskCampaigns security audit (Jul 2026) — approval-bypass on publish, CSV formula injection on export, confirms frequency-cap client-session bypass already fixed
metadata:
  type: project
---

Audit performed 2026-07-06 on `modules/HelpdeskCampaigns`. `modules/Campaign` is a separate, independent module — grepped and confirmed HelpdeskCampaigns has zero references to `Modules\Campaign\*`, so it was not deep-audited (only spot-checked the one file flagged as modified in git status).

## Findings

- **Critical: approval-workflow bypass.** `Campaign::publish()` (`app/Models/Campaign.php:241`), `CampaignsController@publish` (`app/Http/Controllers/Managers/CampaignsController.php:163`), and `CampaignsApiController@publish` (`app/Http/Controllers/Api/CampaignsApiController.php:95`) all activate a campaign unconditionally regardless of `approval_required`/`approved_at`, and are gated only by the `update` permission (same level required for `submitForApproval`). Only `PublishScheduledCampaignsJob` (`app/Jobs/PublishScheduledCampaignsJob.php:34-38`) actually checks the approval flag. Net effect: a user with `helpdesk.campaigns.update` (not `manage`) can directly fire `POST .../publish` and skip the `manage`-gated `approve` step entirely, sending campaigns that were supposed to require sign-off.
  - Fix: add the same `approval_required && !approved_at` guard to `Campaign::publish()` (or require `manage` permission on the publish routes/actions).

- **Medium: CSV formula/injection risk on export.** `CampaignsController::exportStatistics()` (`app/Http/Controllers/Managers/CampaignsController.php:289-322`) streams `page_url`, `browser`, `country`, `device_type` straight into `fputcsv()` with no leading-character sanitization. `page_url` (up to 2000 chars) and `browser` (up to 100 chars) are populated from the **unauthenticated public** tracking endpoint (`RecordImpressionRequest`), so an attacker can plant a formula payload (e.g. `=HYPERLINK(...)`) that later detonates when an admin opens the exported CSV in Excel.
  - Fix: prefix cell values starting with `=`, `+`, `-`, `@` with a `'` (or single-quote escape) before `fputcsv()`.

- **Fixed (verified, no longer exploitable):** the frequency-cap bypass via client-supplied `customer_session_id`, previously flagged in `audit_analytics_helpcenter_campaigns_2026`, is resolved — `FrequencyCapService::visitorKey()` (`app/Services/FrequencyCapService.php:97-108`) now explicitly ignores `customer_session_id` and keys anonymous visitors by IP only, with an inline comment documenting why.

- **Low/informational, verified not currently exploitable:**
  - `DispatchCampaignWebhooks::isSafeUrl()` (`app/Listeners/DispatchCampaignWebhooks.php:91-108`) is a hostname blocklist, not a DNS-resolved check — vulnerable to DNS-rebinding in theory — but webhook entries only come from `config('helpdeskcampaigns.webhooks')` (`config/config.php:17`), which has no admin UI writing to it (grepped, confirmed). Not attacker-reachable today.
  - `Campaign`/`CampaignImpression`/`CampaignTemplate`/`CampaignVariant` all use explicit `$fillable` (no `$guarded = []`), and no controller does mass-assignment via `$request->all()` — confirmed clean. `Campaign::$fillable` does include `status`/`approved_at`/`approved_by_user_id` alongside content fields, which is a soft modeling smell (any future `$request->all()` misuse would immediately forge approval state) but not currently reachable via any Form Request's `rules()`.
  - No ownership/tenant scoping in `CampaignPolicy` — any user with the blanket permission can touch any campaign — consistent with this being a single-tenant admin panel (no `owner_id`/`tenant_id` column exists), not flagged as IDOR.
  - Manager routes gated by hardcoded `role:super-admin|super-settings` in `HelpdeskCampaignsServiceProvider.php:162` in addition to per-action Spatie policy checks — this is an AND (stricter), not a bypass, so not a vulnerability, just an inconsistency vs. the documented `{alias}.action` routing convention.
  - Unauthenticated public tracking endpoint accepts an arbitrary client-supplied `customer_id` integer (`RecordImpressionRequest.php:25`) with no ownership check — could pollute another customer's impression/frequency-cap analytics data, but exposes no sensitive data and is throttled (`throttle:120,1`).

- **Not verified:** the actual customer-facing widget JS that renders `campaign.content`/`appearance` for XSS risk — no JS widget file exists under `modules/HelpdeskCampaigns/resources/`, so the rendering code lives elsewhere (theme or separate widget bundle) and was out of scope for this pass.

See also [[audit_analytics_helpcenter_campaigns_2026]] for the original frequency-cap finding this audit confirms as fixed.
