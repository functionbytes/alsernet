# Agent Memory - Inoqualab (Kimi CLI)

## Known Issues & Workarounds

### PSR-4 Filename Mismatches
PSR-4 requires filename must match class name exactly. Several module files were renamed without updating class names, causing fatal errors.

**Known fixed mismatches:**
| Module | Old filename | Class name | Fix |
|--------|-------------|------------|-----|
| Seo | `SeoHelperServiceProvider.php` | `SeoServiceProvider` | rename file |
| Cookie | `CookieConsentServiceProvider.php` | `CookieServiceProvider` | rename file |

**Diagnostic pattern:** `include(...SeoServiceProvider.php): Failed to open stream`
**Fix:** `mv OldName.php ClassName.php` then `composer dump-autoload`

### Bootstrap Cache Stale Entries
`bootstrap/cache/services.php` can hold provider class names that no longer exist.
When artisan fails entirely, delete:
- `bootstrap/cache/services.php`
- `bootstrap/cache/packages.php`
Then run `composer dump-autoload`

### Module Providers Path
Module providers live at `modules/ModuleName/app/Providers/` (with `app/` subdirectory).
NOT at `modules/ModuleName/Providers/`.

### Known PSR-4 Warnings (non-blocking)
`composer dump-autoload` reports ambiguous class resolution for `Spatie\MediaLibrary\*`
because `modules/Media/vendor/` and root `vendor/` both contain the library.
This is expected/harmless.

## Policy Pattern in Modules
Policies live at `modules/ModuleName/app/Policies/NamePolicy.php`.
Registration: add `registerPolicies()` protected method in module's ServiceProvider
and call it from `boot()`. Use `Gate::policy(Model::class, Policy::class)`.

## Seeder Pattern for Permissions
```php
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

// Reset cached roles and permissions
app()[PermissionRegistrar::class]->forgetCachedPermissions();

// Create permissions
Permission::firstOrCreate(['name' => 'attention.view', 'guard_name' => 'web']);

// Create role and assign permissions
$role = Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);
$role->givePermissionTo(['attention.view', 'attention.create']);
```

## Test User Credentials
Default admin: `admin@caixilhariablanco.pt` / password set via PHP script
Role seeders create users like `{role}@alsernet.test` / `secret`

## Dashboard Controller Endpoints
All under `/panel/dashboard/`:
- `GET /` → index
- `GET /kpis` → kpis
- `GET /activity` → recentActivity
- `GET /queue-stats` → queueStats
- `GET /trends?days=N` → trends
- `GET /health` → health
- `GET /alerts` → alerts
- `GET /distribution` → distribution
- `GET /latest-reviews` → latestReviews
- `GET /security-metrics` → securityMetrics
- `GET /login-timeline` → loginAttemptsTimeline
- `GET /top-failed-ips` → topFailedIps
