---
name: Security & quality audit completed
description: Multi-session exhaustive security, quality and correctness pass across all 35 modules — what was done and what patterns to maintain
type: project
---

Completed a full multi-session audit across all modules. Everything is now clean.

**Why:** User requested iterative "fix everything" passes across the entire modular codebase.

**What was done:**
- `$e->getMessage()` removed from all user-facing HTTP responses (replaced with generic messages + Log::error). Exceptions: intentional 422 domain validation messages (ReviewController, TemplateController, PageWebhookController, AnalyticsSettingController) are left as-is.
- `Log::error()` added to all catch blocks that return error responses without logging
- Return type declarations added to all public controller methods (MailsSettings, Activity, Shortcode, Attention, Page modules)
- XSS: `{!! !!}` Blade renders of user content now use `clean_html()` (Forms field.blade.php)
- Bulk actions: per-row `Gate::allows()` authorization added (User, Page modules)
- Auth stubs replaced with `abort(501)` + `never` return type (Auth module)
- `withCount('replies')` instead of `with('replies')` in Reviews API index
- `ReviewResource` uses `$this->whenCounted('replies')`
- Duplicate `clean_html()` helper removed from Blog module (canonical is app/Helpers/Helper.php)
- `AttentionController::downloadExport` return type added: `JsonResponse|StreamedResponse`

**How to apply:** When adding new controllers/catch blocks, always include `Log::error()` before error responses. Never expose `$e->getMessage()` directly to users unless it's a controlled domain validation exception thrown with `new \Exception('user-facing message')` and caught with a 422 response.
