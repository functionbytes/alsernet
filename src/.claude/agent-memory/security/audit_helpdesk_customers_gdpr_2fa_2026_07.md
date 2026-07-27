---
name: audit_helpdesk_customers_gdpr_2fa_2026_07
description: Security audit jul-2026 — Helpdesk core CustomersController, GDPR (GdprController/Services), 2FA (TwoFactorController/Service), merge, exports. IDOR/PII/mass-assignment/2FA focus.
metadata:
  type: project
---

Audit performed 2026-07-07, read-only, scope: `modules/Helpdesk/app/Http/Controllers/Managers/CustomersController.php`, `CustomerInsightsController.php`, `CustomerEcommerceController.php`, `CustomerProfileController.php`, `AtRiskCustomersReportController.php`, `Compliance/GdprController.php`, `Compliance/TwoFactorController.php`, related Form Requests/Actions/Services, `ExportController::customers`.

## Findings (ranked)

1. **MEDIA (código) / mitigado hoy por rol** — `CustomersExporter::rows()` (`modules/Helpdesk/app/Services/Exports/CustomersExporter.php`) does NOT apply `Customer::forAgent($user)` inbox isolation, unlike `CustomersController::index/search`. Exports name/email/phone/country/created_at for ALL customers system-wide to any user holding `helpdesk.exports.create`. DB-verified today: only `helpdesk-admin`/`super-admin`/`super-settings` hold that permission (which also hold `.manage`, so no real elevation currently) — but it's a single plain permission (not `.manage`), so granting it to a regular agent role would leak the full customer PII database bypassing the module's own documented inbox-isolation model. Fix: pass authenticated user into `CustomersExporter` and apply `->forAgent($user)` unless `$user->can('helpdesk.customers.manage')`.

2. **MEDIA-BAJA, same pattern** — `AtRiskCustomersReportController::rankedAtRiskCustomers()` lists up to 50 customers' name+email system-wide with no `sharesInboxWith` filter, gated only by `helpdesk.reports.view` (today admin-only in DB). Fix: filter by agent's assigned inboxes unless `helpdesk.customers.manage`.

3. **BAJA, PLAUSIBLE (requires local host access)** — `GdprExportService::exportToZip()` (`modules/Helpdesk/app/Services/Compliance/GdprExportService.php:94,125`) uses predictable temp paths in shared `sys_get_temp_dir()` (`/tmp/gdpr_customer_{id}.zip`, `/tmp/gdpr_export_{id}_{time}/`) with default `mkdir(...,0755)` — world-readable on a shared host/container between creation and `deleteFileAfterSend`. Fix: `tempnam()`/random suffix + `chmod 0600`, or write under `storage/app/private`.

4. **BAJA, hardening only, not exploitable today** — `Customer::$fillable` (`modules/Helpdesk/app/Models/Customer.php:27-52`) includes sensitive columns (`portal_token`, `portal_password`, `banned_at`, `ban_reason`, `email_verified_at`). Not currently exploitable: `StoreCustomerRequest`/`UpdateCustomerRequest` pass `$request->validated()` (strict whitelist: name/email/phone/country/language/timezone/internal_notes only), never `$request->all()`. Latent risk only if a future endpoint bypasses the Form Request.

5. **BAJA/diseño, PLAUSIBLE if permissions reassigned** — `CustomerMergeAction`/`MergeCustomerRequest` gated only by flat `helpdesk.customers.merge` permission (route `can:` middleware), with NO `CustomerPolicy::sharesInboxWith` check on `base`/`mergee` inside `CustomersController::merge()`, unlike every other customer endpoint in the controller. Today only `helpdesk-admin` (already bypasses isolation via `.manage`) holds `.merge`, so no real elevation currently. Fix: add `$this->authorize('view', $base)` + `$this->authorize('view', $mergee)` for consistency.

## Confirmed GOOD (do not re-flag)

- `CustomerPolicy::sharesInboxWith()` (`modules/Helpdesk/app/Policies/CustomerPolicy.php`) — solid, well-documented inbox isolation, explicitly reused by satellite modules (HelpdeskErp/HelpdeskPrestashop) per its own docblock.
- `CustomersController`: `show/edit/update/destroy/restore/ban/unban/media/conversations/emails/emailsData` all call `$this->authorize()` against the policy correctly — no missing gates.
- `CustomerInsightsController`, `CustomerEcommerceController`, `CustomerProfileController` — all call `$this->authorize('view', $customer)`; `CustomerInsightsController` additionally has constructor-level `can:helpdesk.customers.view` (defense in depth).
- `GdprController` — `panel()/export()/delete()` all gated by `$this->authorize('manage', Customer::class)` → `helpdesk.customers.manage`, DB-confirmed restricted to helpdesk-admin/super-admin/super-settings only. `delete()` additionally requires literal `'confirmation' => 'in:CONFIRMAR'` — good anti-misclick design.
- `GdprDeletionService` — soft mode anonymizes all PII fields + redacts message bodies + deletes physical attachments; hard mode cascades fully (ratings, drip executions, conversations, customer). Both modes call `AuditLogService::record()` and dispatch `CustomerGdprDeleted` for satellite-module cascade. Well built.
- `TwoFactorService` — secret + recovery codes encrypted at rest (`Crypt::encryptString`), never logged in plaintext; QR/secret/recovery codes only returned once from `enable()`, always scoped to `$request->user()` (self) — **no admin route exists to reset/disable another user's 2FA** (checked routes + controller, confirmed self-only). `disable()` requires `current_password` rule, not just session auth.
- `2fa.confirm/verify/disable` routes carry `throttle:6,1` against TOTP/recovery-code brute force.
- `Require2FA` middleware self-exempts its own routes cleanly (no bypass found via headers/route spoofing), gate is purely server-side session (`2fa_passed`).
- Entire `panel/helpdesk/*` surface wrapped in `['web','auth','can:helpdesk.view', Require2FA::class]` at `RouteServiceProvider::mapWebRoutes()` — no endpoint in this scope escapes authentication.
- `StoreCustomerRequest`/`UpdateCustomerRequest` — real Form Request whitelisting via `validated()`, Spanish messages/attributes, `Rule::unique(...)->ignore()` correctly used on update.

**Why:** documents a full re-check of the customer/GDPR/2FA/merge/export surface after prior sessions covered tickets/livechat/contacts merge superficially ([[audit_tickets_livechat_contacts_2026]] flagged merge as "untested", not as an authz gap — this audit is the first to find the missing per-customer `sharesInboxWith` check on merge/export/at-risk).
**How to apply:** before re-auditing this module, check DB role-permission assignments (not just seeder existence) — several findings here are code-level gaps currently neutralized by the fact that only admin-tier roles hold the relevant permission in the live DB; if that role config changes, severity escalates. See [[audit_prestashop_erp_analytics_contacts_batch_b_2026]] for the sibling finding that HelpdeskContacts' own merge route already got an `audit.access` gate — this Helpdesk-core merge (different module, different action) still lacks the inbox check.
