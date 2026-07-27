---
name: audit_analytics_helpcenter_campaigns_2026
description: Security+quality audit of HelpdeskAnalytics, HelpdeskHelpcenter, HelpdeskCampaigns (jul-2026)
metadata:
  type: project
---

Audit performed 2026-07-05 (see [[project_helpdesk_modules_roadmap]] for the earlier high-level pass).

Findings:
- HelpdeskAnalytics confirmed genuinely read-only: only `helpdeskanalytics.` routes are `index`/`data` (GET only), gated by `can:helpdeskanalytics.view` at both route and FormRequest level. AnalyticsAggregatorService uses grouped SQL only (no N+1), cached 300s per key. No write endpoints exist.
- `modules/HelpdeskLivechat/routes/widget.php:29-32` — the 4 `HelpcenterWidgetController` routes (apiWidget, apiSearch, apiArticle, apiArticleFeedback) have NO per-route `throttle` middleware, unlike every other route in that file (which all have explicit throttle:X,1 comments/rules). Mitigated somewhat by the group-level `ValidateTrustedOrigin` + `VerifyWidgetHmac` middleware, but still an inconsistency — `apiArticleFeedback` (POST, increments helpful/unhelpful counters) can be flooded by anyone with a valid widget HMAC key to skew article helpfulness metrics.
- No test file anywhere covers `HelpcenterWidgetController` or `HelpcenterWidgetService` (apiWidget/apiSearch/apiArticle/apiArticleFeedback) — confirmed via grep, zero references in tests/.
- `EmbeddingsService::search()` (actual cosine-similarity ranking logic) is untested; only `EmbeddingsServiceIntegrationToggleTest` exists, which just checks the enabled/disabled toggle short-circuit.
- HelpdeskHelpcenter semantic search (`EmbeddingsService::search`, `HelpcenterWidgetService::searchArticles`) uses parameterized `whereRaw('MATCH(...) AGAINST (?...)', [$query])` — NOT vulnerable to SQL injection (safe use of bindings, false-positive to rule out in future audits).
- HelpdeskCampaigns models (Campaign, CampaignImpression, CampaignVariant, CampaignTemplate) all use explicit `$fillable` — no repeat of the June-2026 fillable bug found elsewhere in this module.
- `FrequencyCapService`/dedup in `ImpressionTrackingController` key visitor identity by client-supplied `customer_session_id` (not tied to any real session/cookie) with IP as last-resort fallback only. An attacker can trivially bypass both the 30s dedup cache and the frequency cap by sending a new random `customer_session_id` per request, inflating campaign impression/CTR metrics. The route-level `throttle:120,1` (per IP) is the only real ceiling; distributing across IPs defeats it entirely. This is a metrics-integrity issue, not data exposure.
- HelpdeskCampaigns manager (`CampaignsController`) and API (`CampaignsApiController`) controllers both correctly call `$this->authorize()` on every action in addition to route-level `role:super-admin|super-settings` / Sanctum — no IDOR found.

How to apply: when auditing widget/public routes in HelpdeskLivechat's widget.php, check each route individually for throttle — the file mixes throttled and unthrottled endpoints and the "removed global throttle" comment can mislead you into assuming all routes are covered per-endpoint.
