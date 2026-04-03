---
name: eighth_pass_findings
description: Round 8: middleware dead code (exit after return), reset password logic inversion, plaintext reset tokens, exception message leaks, unreachable code (2026-03-28)
type: project
---

# Round 8 Findings

## Critical
1. **ResetPasswordController broken logic** - `modules/Auth/app/Http/Controllers/ResetPasswordController.php:37` - Hash::check is called with two already-hashed values (bcrypt of bcrypt) which is always false. The condition body (the password-saving branch) is on the `== false` branch, meaning password is saved when check *fails*. Also `$user->password = $request->password` stores the raw unhashed password.

2. **Plaintext password reset token** - `modules/Auth/app/Http/Controllers/ForgotPasswordController.php:65` - `password_reset_token` stored raw in DB. If the users table is breached, all active reset tokens are exposed and can be used directly. Should be hashed before storage (compare with Hash::check on verify).

## Warnings
3. **Unreachable exit() after return** - `modules/Core/app/Http/Middleware/HttpsProtocolMiddleware.php:32` and `modules/System/app/Http/Middleware/LanguageMiddleware.php:26` - Both have `return $next($request); exit('Could not connect...')` in a catch block. The exit is unreachable dead code and should be removed.

4. **Three middleware hit DB on every request** - HttpsProtocolMiddleware, LanguageMiddleware, and MaintenanceMiddleware all call `DB::connection()->getPdo()` and `DB::getSchemaBuilder()->hasTable('backups')` on every request to detect the install state. This is expensive and should be replaced with a cached flag (e.g., `Cache::remember('app_installed', 3600, fn() => Schema::hasTable('backups'))`).

5. **BlockMiddleware references undefined classes** - `modules/Role/app/Http/Middleware/BlockMiddleware.php` uses bare `GeoIP::` and `IPLIST::` with no `use` imports. No facade alias for either was found in config. This middleware is never registered in bootstrap/app.php either, so it is dead code but would throw a fatal if ever activated.

6. **Custom RoleMiddleware is duplicate/unused** - `modules/Role/app/Http/Middleware/RoleMiddleware.php` duplicates Spatie's middleware. bootstrap/app.php aliases `'role'` to `\Spatie\Permission\Middleware\RoleMiddleware::class` directly. The custom one is dead code.

7. **Exception getMessage() leaked in admin responses** - Multiple controllers return `$e->getMessage()` in JSON/back() responses (PageTranslationController:81, BlogPostTranslationController:81, CacheSettingsController:63/81, SystemSettingsController:298/318, TemplateController:140/142). These are admin-only routes so not a public exposure, but stack traces or internal paths can appear in the browser. Should be logged and a generic message returned.

8. **LanguageMiddleware raw DB query** - `modules/System/app/Http/Middleware/LanguageMiddleware.php:20` reads from `DB::table('backups')` using `@` error suppression to swallow null property access. This queries the wrong table name conceptually (it's a settings table, not backups) and the `@` suppression hides real errors.

## Already Clean / Positive
- PageCacheMiddleware correctly skips authenticated users and admin routes
- RedirectMiddleware (Seo) correctly uses Cache::remember and async hit counting
- CheckAttentionPermission is well-structured and handles edge cases
- bootstrap/app.php 500 exception handler correctly returns generic messages
- BlockMiddleware, though unused, does not expose any currently-running risk
