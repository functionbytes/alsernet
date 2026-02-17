# Agent Memory - Inoqualab Backend

## Module File Naming Conventions

PSR-4 requires the **filename must match the class name exactly**. In this project several
module files were renamed (adding "Consent", "Helper", etc. as prefix/suffix) without
renaming the class inside. This causes `ClassLoader` fatal errors on boot.

**Known past mismatches (fixed 2026-02-17):**

| Module | Old filename | Class name | Fixed by |
|--------|-------------|------------|----------|
| Seo | `SeoHelperServiceProvider.php` | `SeoServiceProvider` | rename file |
| Cookie | `CookieConsentServiceProvider.php` | `CookieServiceProvider` | rename file |
| vendor/spatie/laravel-cookie-consent | `CookieConsentServiceProvider.php` | `CookieServiceProvider` | rename file |

**Diagnostic pattern:** `include(...SeoServiceProvider.php): Failed to open stream`
**Fix command:** `mv OldName.php ClassName.php` then `composer dump-autoload`

## Config File Names in Seo Module

The Seo module config file is `seohelper.php` (NOT `Seo.php`).
`SeoServiceProvider` must reference `config/seohelper.php`.

## Bootstrap Cache Stale Entries

`bootstrap/cache/services.php` can hold provider class names that no longer exist.
When artisan fails entirely (not just a single route), delete these files first:
- `bootstrap/cache/services.php`
- `bootstrap/cache/packages.php`

Then run `composer dump-autoload` to regenerate from scratch.

## Providers Path in Modules

Module providers live at `modules/ModuleName/app/Providers/` (with the `app/` subdirectory).
NOT at `modules/ModuleName/Providers/`.

## Known PSR-4 Warnings (non-blocking)

`composer dump-autoload` reports ambiguous class resolution for `Spatie\MediaLibrary\*`
because `modules/Media/vendor/` and root `vendor/` both contain the library.
This is expected/harmless — the module vendor copy takes precedence.

## Unrelated Syntax Error

`modules/Mailrelay/database/migrations/2026_01_25_140000_create_mailrelay_variables_table.php`
has a syntax error on line 37. Does not affect app boot but breaks `pint --dirty`.
