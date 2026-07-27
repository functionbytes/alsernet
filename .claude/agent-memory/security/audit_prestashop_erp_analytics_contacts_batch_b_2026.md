---
name: audit_prestashop_erp_analytics_contacts_batch_b_2026
description: Security audit jul-2026 batch B — HelpdeskPrestashop/HelpdeskErp/HelpdeskAnalytics/HelpdeskContacts roadmap items all resolved or false-positive
metadata:
  type: project
---

Audit performed 2026-07-06, read-only verification of 6 specific roadmap line items (derived, not exact line numbers) across HelpdeskPrestashop, HelpdeskErp, HelpdeskAnalytics, HelpdeskContacts. Result: 5/6 already fixed in code, 1/6 false positive.

## Findings

- **FALSO-POSITIVO**: `ProductSearchController::categories()` (`modules/HelpdeskPrestashop/app/Http/Controllers/Managers/ProductSearchController.php:190`) calls `$this->authorize('helpdeskprestashop.view')` — a plain permission string with no dedicated Policy method. This is NOT a bug: Spatie Permission's `PermissionRegistrar::registerPermissions()` (`vendor/spatie/laravel-permission/src/PermissionRegistrar.php:125`) registers a global `Gate::before()` hook that intercepts every ability check and resolves it via `$user->checkPermissionTo($ability)`, short-circuiting normal Gate/Policy resolution. `$this->authorize('perm.name')` and `$user->can('perm.name')` are equivalent and both work without a Policy class — this is the standard idiom already used elsewhere in the codebase (Form Request `authorize()` methods). Only real risk: permission must actually be seeded + assigned to the agent role (seeder exists at `HelpdeskPrestashopPermissionsSeeder.php:20`, but `permissions` table was empty in the audited test DB — an environment/seeding gap, not a code defect).

- **YA-RESUELTO**: `AssistedCartService::generateOrder()` (`modules/HelpdeskPrestashop/app/Services/AssistedCartService.php:176-200`) already has explicit idempotency: checks `$cart->ecommerce_order_id` before creating an order, wraps creation in `Cache::lock("assisted-cart-order:{$cart->id}", 15)->block(5, ...)` with a double-check inside the lock, and throws on `LockTimeoutException`. Code comment explicitly references the "un carrito genera como mucho UN pedido" concern.

- **YA-RESUELTO**: fill-in password for the auto-created `EcommerceCustomer` in `AssistedCartService::resolveEcommerceCustomer()` (line ~363) uses `bcrypt(Str::random(40))`, with a comment explicitly rejecting `uniqid()` as non-cryptographic. No `uniqid()`-as-password pattern remains in this module.

- **YA-RESUELTO**: `modules/HelpdeskErp/routes/api.php` — both `/health` (line 37-39) and `/cache/warm` (line 42-44) already carry `audit.access:erp,health_check` / `audit.access:erp,cache_warm`, identical pattern to sibling routes (`customers.search`, `context`, `timeline`, etc).

- **YA-RESUELTO**: `AnalyticsRangeRequest::withValidator()` (`modules/HelpdeskAnalytics/app/Http/Requests/Managers/AnalyticsRangeRequest.php:29-43`) already caps the effective from/to range at 366 days, with a comment explaining the DoS risk (trends()/heatmap()/channelDistribution() unbounded aggregation) that motivated the fix.

- **YA-RESUELTO**: `modules/HelpdeskContacts/routes/web.php:51-63` — the merge group's `POST merge.execute` route already carries `audit.access:contacts,merge`, with an inline comment tying it to the same audit pattern used by HelpdeskErp.

**Why:** roadmap line numbers had drifted (code moved since the original scan); re-verifying against current file contents showed nearly everything in this batch had already been remediated in a prior pass, plus one item that was never actually a bug (Spatie's Gate::before makes bare permission-string `authorize()` calls valid without a Policy).
**How to apply:** before flagging `$this->authorize('some.permission')` (no matching Policy method) as broken, confirm whether Spatie Permission's `Gate::before` hook is registered (`config('permission.register_permission_check_method')`, default true in this project) — if so, it is a valid, working pattern, not a 403 trap. Also see [[audit_helpdeskcampaigns_2026]] and [[audit_helpdeskagents_ssrf_2026]] for the "verify current code, not just the roadmap line" discipline on this project.
