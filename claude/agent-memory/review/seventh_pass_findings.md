---
name: seventh_pass_findings
description: Round 7 audit — testing gaps, job retry/backoff, blade XSS, schedule monitoring, duplicate schedules, PageVersionController auth comments (2026-03-28)
type: project
---

## Round 7 Key Findings (2026-03-28)

### Critical
- PageVersionController: constructor has ALL middleware commented out (lines 22-24). Routes ARE behind `auth` group but NO permission checks enforced. Any authenticated user can restore/delete any page version.
- `activity:prune` scheduled TWICE: weekly in bootstrap/app.php AND monthly in ActivityServiceProvider. Runs at conflicting frequencies.
- `notifications:clean` (CleanOldNotifications) AND `notifications:cleanup` (CleanupNotificationsCommand) both scheduled daily — two separate commands doing the same thing.

### Job Retry/Backoff Gaps
- `SendEndpointEmailJob`: no `$tries`, `$backoff`, `$timeout`. Calls `$this->fail($e)` immediately inside catch — no retry for transient SMTP failures. Should have `$tries=3`, `$backoff=[60,120,300]`.
- `CreateBackupJob`: has `$maxExceptions=2` and `ShouldBeUnique` but NO `$tries`, `$backoff`, `$timeout`, NO `failed()` method. Silent failures on backup — critical.
- `WarmPageCacheJob`: no `$tries`, `$backoff`, `$timeout`, no `failed()`. Low severity but inconsistent.

### Blade XSS
- `Attention/emails.blade.php:101` — `{!! $mail->body !!}` renders raw email HTML from external senders. Admin-only but still risky. Should use `clean_html()`.
- `Page/versions/show.blade.php:56` — `{!! $version->content !!}`. Content IS sanitized on save via `strip_tags()` in PageService, but PageVersion model does NOT sanitize on save — versions created outside PageService bypass sanitization.
- `Theme/layouts/theme.blade.php:81,82,309,310` — `{!! Setting::get('theme.custom_header_html|js') !!}` renders raw admin-configurable HTML/JS into every public page. Admin-only write access — intentional but worth noting.
- `Template/partials/breadcrumb.blade.php:8` — `{!! $crumb['label'] !!}` but Breadcrumb::add() calls `strip_tags()` on labels before storing — safe.
- `Template/partials/menu-node.blade.php:18` — `{!! $nodeData !!}` but uses `JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS` flags — safe.

### Scheduled Task Monitoring Gaps
- `tickets:check-sla-breaches` (everyFiveMinutes) — no `withoutOverlapping()`, no failure handler
- `tickets:sla-warnings` (everyFifteenMinutes) — no `withoutOverlapping()`, no failure handler
- `alerts:check` (everyFiveMinutes) — no `withoutOverlapping()`, no failure handler
- `notifications:clean` (daily), `notifications:cleanup` (daily), `forms:cleanup-*` (weekly/daily/monthly) — no `withoutOverlapping()`
- Only `translations:missing` has `emailOutputOnFailure` among bootstrap schedules

### Testing Gaps
Modules with ZERO tests: Activity, Auth, Backup, Cache, Captcha, Core, Health, MailsSettings, Media, Modules, Optimize, Pulse, Queue, Storage, System, Widget, Theme (0 files), User (2 files but minimal)

High-priority test targets:
- Auth: login throttling, 2FA flow, password reset
- Backup: CreateBackupJob, schedule management, download auth
- Media: ConvertToWebpJob, file upload security
- Activity: export throttle, audit data endpoint

### Indexes — All good
Attentions, blog_posts, reviews, form_submissions all have comprehensive indexes. No missing indexes found.

### i18n Gaps
~20+ hardcoded Spanish strings in Attention, Analytics, Backup controllers (flash messages). No lang files for Attention, Auth, Backup, Blog, Core, Forms, User modules.

### Config Gaps
User module has 0 config files. No hardcoded problematic values found.
