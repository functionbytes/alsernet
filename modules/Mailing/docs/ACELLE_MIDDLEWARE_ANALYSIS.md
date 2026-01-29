# Acelle Middleware Analysis Report

**Generated:** 2026-01-29
**Source:** `/Users/functionbytes/Function/Coding/acelle/app/Http/Middleware/`
**Purpose:** Complete analysis of all Acelle middleware for migration assessment

---

## Table of Contents

1. [Executive Summary](#executive-summary)
2. [Middleware Inventory](#middleware-inventory)
3. [Critical Middleware (Must Migrate)](#critical-middleware-must-migrate)
4. [Standard Laravel Middleware (Optional)](#standard-laravel-middleware-optional)
5. [Migration Priority Matrix](#migration-priority-matrix)
6. [Detailed Analysis](#detailed-analysis)
7. [Migration Recommendations](#migration-recommendations)

---

## Executive Summary

**Total Middleware Count:** 14 files

**Categories:**
- **Authentication & Authorization:** 5 middleware (Backend, Frontend, Authenticate, NotLoggedIn, Subscription)
- **Installation Guards:** 2 middleware (Installed, NotInstalled)
- **Standard Laravel:** 7 middleware (EncryptCookies, VerifyCsrfToken, TrimStrings, TrustProxies, TrustHosts, PreventRequestsDuringMaintenance, RedirectIfAuthenticated)

**Critical Finding:**
The `Backend` and `Frontend` middleware contain essential business logic for:
- Permission checking (`admin_access`, `customer_access`)
- User status validation (active/disabled)
- Locale/language configuration
- Site offline mode
- Multi-tenant WordPress database configuration (commented out but present)

---

## Middleware Inventory

| # | Middleware | Type | Critical | Lines | Status |
|---|------------|------|----------|-------|--------|
| 1 | `Backend.php` | Custom Auth | ✅ YES | 42 | **MUST MIGRATE** |
| 2 | `Frontend.php` | Custom Auth | ✅ YES | 90 | **MUST MIGRATE** |
| 3 | `Authenticate.php` | Laravel Override | ⚠️ PARTIAL | 22 | Adapt to Laravel 12 |
| 4 | `NotLoggedIn.php` | Custom Locale | ✅ YES | 37 | **MUST MIGRATE** |
| 5 | `Subscription.php` | Custom Feature | ⚠️ PARTIAL | 28 | Evaluate SaaS need |
| 6 | `Installed.php` | Installation Guard | ❌ NO | 26 | Skip (one-time use) |
| 7 | `NotInstalled.php` | Installation Guard | ❌ NO | 26 | Skip (disabled) |
| 8 | `RedirectIfAuthenticated.php` | Laravel Standard | ❌ NO | 33 | Use Laravel 12 default |
| 9 | `VerifyCsrfToken.php` | Laravel Standard | ⚠️ PARTIAL | 25 | Adapt CSRF exceptions |
| 10 | `EncryptCookies.php` | Laravel Standard | ❌ NO | 18 | Use Laravel 12 default |
| 11 | `TrimStrings.php` | Laravel Standard | ❌ NO | 20 | Use Laravel 12 default |
| 12 | `TrustProxies.php` | Laravel Standard | ❌ NO | 24 | Use Laravel 12 default |
| 13 | `TrustHosts.php` | Laravel Standard | ❌ NO | 21 | Use Laravel 12 default |
| 14 | `PreventRequestsDuringMaintenance.php` | Laravel Standard | ❌ NO | 18 | Use Laravel 12 default |

---

## Critical Middleware (Must Migrate)

### 1. Backend Middleware ⭐⭐⭐⭐⭐
**File:** `Backend.php` (42 lines)
**Namespace:** `Acelle\Http\Middleware`

**Functionality:**
```php
public function handle($request, Closure $next)
{
    $user = $request->user();

    // 1. Permission Check: admin_access
    if (isset($user) && !$user->can("admin_access", $user)) {
        return redirect()->action('Controller@notAuthorized');
    }

    // 2. User Active Status Check
    if ((isset($user) && $user->admin && !$user->admin->isActive())) {
        return redirect()->action('Controller@userDisabled');
    }

    // 3. Language/Locale Configuration
    if ($user->admin->language) {
        \App::setLocale($user->admin->language->code);
        \Carbon\Carbon::setLocale($user->admin->language->code);
    }

    return $next($request);
}
```

**Business Logic:**
- ✅ Enforces `admin_access` permission gate
- ✅ Validates admin user is active (not disabled)
- ✅ Sets application locale based on admin's language preference
- ✅ Sets Carbon locale for date/time formatting

**Dependencies:**
- User model with `admin` relationship
- Permission system (`can("admin_access")`)
- Admin model with `isActive()` method
- Admin model with `language` relationship
- Language model with `code` attribute

**Migration Path:**
1. Create `app/Http/Middleware/Mailing/BackendAccess.php` in main system
2. Update permission check to use Spatie Laravel Permission
3. Adapt user/admin relationships to system's User model
4. Register in `bootstrap/app.php` for Mailing module routes

---

### 2. Frontend Middleware ⭐⭐⭐⭐⭐
**File:** `Frontend.php` (90 lines)
**Namespace:** `Acelle\Http\Middleware`

**Functionality:**
```php
public function handle($request, Closure $next, $guard = null)
{
    $user = $request->user();

    // 1. Redirect backend-only users
    if (isset($user) && !$user->can("customer_access", User::class)
        && $user->can("admin_access", User::class)) {
        return redirect()->action('Admin\HomeController@index');
    }

    // 2. Permission Check: customer_access
    if (!$user->can("customer_access", User::class)) {
        return redirect()->action('Controller@notAuthorized');
    }

    // 3. Site Online Check
    if (\Acelle\Model\Setting::get('site_online') == 'false'
        && (isset($user) && get_tmp_quota($user->customer, 'access_when_offline') != 'yes')) {
        return redirect()->action('Controller@offline');
    }

    // 4. User Active Status Check
    if ((isset($user) && $user->customer && !$user->customer->isActive() && is_null($user->admin))) {
        return redirect()->action('Controller@userDisabled');
    }

    // 5. Language/Locale Configuration
    if ($user->customer->language) {
        \App::setLocale($user->customer->language->code);
        \Carbon\Carbon::setLocale($user->customer->language->code);
    }

    // 6. WordPress Multi-tenant Database Config (COMMENTED OUT)
    // Lines 52-84: Dynamic WordPress DB configuration per customer

    return $next($request);
}
```

**Business Logic:**
- ✅ Enforces `customer_access` permission gate
- ✅ Redirects backend users trying to access frontend
- ✅ Validates customer is active (not disabled)
- ✅ Checks site online/offline mode with quota override
- ✅ Sets application locale based on customer's language preference
- ⚠️ Contains commented WordPress multi-tenant database configuration

**Dependencies:**
- User model with `customer` and `admin` relationships
- Permission system (`can("customer_access")` and `can("admin_access")`)
- Customer model with `isActive()` method
- Customer model with `language` relationship
- Setting model with `get('site_online')` method
- Helper function `get_tmp_quota()`
- Language model with `code` attribute

**Migration Path:**
1. Create `app/Http/Middleware/Mailing/CustomerAccess.php` in main system
2. Update permission checks to use Spatie Laravel Permission
3. Adapt user/customer relationships to system's User model
4. Integrate with system's settings infrastructure (MailingSetting)
5. Decide if WordPress multi-tenant config is needed (likely NOT)
6. Register in `bootstrap/app.php` for customer-facing Mailing routes

---

### 3. NotLoggedIn Middleware ⭐⭐⭐⭐
**File:** `NotLoggedIn.php` (37 lines)
**Namespace:** `Acelle\Http\Middleware`

**Functionality:**
```php
public function handle($request, Closure $next)
{
    // 1. Get default language from settings
    $default_language = \Acelle\Model\Language::find(
        \Acelle\Model\Setting::get('default_language')
    );

    // 2. Check cookie for last used language
    if (isset($_COOKIE['last_language_code'])) {
        $language_code = $_COOKIE['last_language_code'];
    } elseif ($default_language) {
        $language_code = $default_language->code;
    } else {
        $language_code = 'en';
    }

    // 3. Set locale for unauthenticated users
    if ($language_code) {
        \App::setLocale($language_code);
        \Carbon\Carbon::setLocale($language_code);
    }

    return $next($request);
}
```

**Business Logic:**
- ✅ Sets locale for unauthenticated (guest) users
- ✅ Uses cookie-based language preference
- ✅ Falls back to system default language
- ✅ Final fallback to English ('en')

**Dependencies:**
- Language model with `find()` and `code` attribute
- Setting model with `get('default_language')` method
- Cookie named `last_language_code`

**Migration Path:**
1. Create `app/Http/Middleware/Mailing/GuestLocale.php` in main system
2. Integrate with system's Language model or MailingLanguage
3. Use system's settings infrastructure (MailingSetting)
4. Consider using Laravel's localization features
5. Register in `bootstrap/app.php` for guest routes

---

### 4. Subscription Middleware ⭐⭐
**File:** `Subscription.php` (28 lines)
**Namespace:** `Acelle\Http\Middleware`

**Functionality:**
```php
public function handle($request, Closure $next)
{
    // Only active if SaaS mode is enabled
    if (!config('app.saas')) {
        return $next($request);
    }

    // Empty implementation - placeholder for future subscription logic

    return $next($request);
}
```

**Business Logic:**
- ⚠️ Currently EMPTY - no active logic
- ⚠️ Checks `config('app.saas')` flag
- ⚠️ Placeholder for subscription/plan enforcement

**Dependencies:**
- Configuration key `app.saas`

**Migration Decision:**
- **Option A:** Skip migration if not using SaaS model
- **Option B:** Implement subscription logic if multi-tenant billing is needed
- **Option C:** Create placeholder for future enhancement

**Recommendation:** Skip unless SaaS features are required

---

### 5. Authenticate Middleware ⭐⭐⭐
**File:** `Authenticate.php` (22 lines)
**Namespace:** `Acelle\Http\Middleware`

**Functionality:**
```php
class Authenticate extends Middleware
{
    protected function redirectTo($request)
    {
        if (!$request->expectsJson()) {
            return route('login');
        }
    }
}
```

**Business Logic:**
- ✅ Extends Laravel's base `Authenticate` middleware
- ✅ Redirects unauthenticated users to `route('login')`
- ✅ Respects JSON requests (returns 401 instead of redirect)

**Migration Path:**
- **Laravel 12:** Use native `Authenticate` middleware from `bootstrap/app.php`
- Configure redirect route in `bootstrap/app.php`:
  ```php
  ->withMiddleware(function (Middleware $middleware) {
      $middleware->redirectGuestsTo(fn() => route('mailing.login'));
  })
  ```

---

## Standard Laravel Middleware (Optional)

### 6. VerifyCsrfToken ⚠️
**File:** `VerifyCsrfToken.php` (25 lines)

**Custom Configuration:**
```php
protected $except = [
    'webhooks/*',
    'plugins/webhooks/*',
    'delivery/*',
    'api/*',
    'manager/*',
    '*/embedded-form-*',
    'payments/stripe/credit-card*',
    'frontend/*',
];
```

**Migration Path:**
- Register CSRF exceptions in `bootstrap/app.php`:
  ```php
  ->withMiddleware(function (Middleware $middleware) {
      $middleware->validateCsrfTokens(except: [
          'mailing/webhooks/*',
          'mailing/api/*',
          'mailing/delivery/*',
      ]);
  })
  ```

---

### 7-14. Other Standard Middleware

These extend Laravel's base middleware without customization:

| Middleware | Extends | Customization | Migration |
|------------|---------|---------------|-----------|
| `EncryptCookies` | `Illuminate\Cookie\Middleware\EncryptCookies` | None | Use Laravel 12 default |
| `TrimStrings` | `Illuminate\Foundation\Http\Middleware\TrimStrings` | Password fields excluded | Use Laravel 12 default |
| `TrustProxies` | `Fideloper\Proxy\TrustProxies` | All proxy headers | Use Laravel 12 default |
| `TrustHosts` | `Illuminate\Http\Middleware\TrustHosts` | All subdomains | Use Laravel 12 default |
| `PreventRequestsDuringMaintenance` | `Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance` | None | Use Laravel 12 default |
| `RedirectIfAuthenticated` | None | Uses `RouteServiceProvider::HOME` | Use Laravel 12 default |
| `Installed` | None | Installation guard | Skip (one-time use) |
| `NotInstalled` | None | Installation guard (disabled) | Skip (commented out) |

---

## Migration Priority Matrix

### Priority 1: Critical (Must Migrate)
1. **Backend.php** - Core admin authorization logic
2. **Frontend.php** - Core customer authorization logic
3. **NotLoggedIn.php** - Guest locale management

### Priority 2: High (Should Migrate)
4. **Authenticate.php** - Adapt for Laravel 12 pattern
5. **VerifyCsrfToken.php** - Copy CSRF exceptions to config

### Priority 3: Low (Evaluate)
6. **Subscription.php** - Only if SaaS features are needed

### Priority 4: Skip
7-14. All standard Laravel middleware - use Laravel 12 defaults

---

## Detailed Analysis

### Authentication Flow Analysis

**Acelle's Multi-Guard Approach:**
```
┌─────────────────────────────────────────────────────────────┐
│ Request                                                      │
└─────────────────────────────────────────────────────────────┘
                          ↓
        ┌─────────────────┴─────────────────┐
        │                                    │
   [Backend Route]                    [Frontend Route]
        │                                    │
        ↓                                    ↓
   Backend Middleware              Frontend Middleware
        │                                    │
   Check: admin_access             Check: customer_access
   Status: isActive()              Status: isActive()
   Locale: admin.language          Locale: customer.language
        │                                    │
        └─────────────────┬─────────────────┘
                          ↓
                   [Controller]
```

**Permission Gates Used:**
1. `admin_access` - Admin panel access (Backend middleware)
2. `customer_access` - Customer portal access (Frontend middleware)

**User Relationships Required:**
```php
User Model:
  - hasOne: admin (Admin model)
  - hasOne: customer (Customer model)

Admin Model:
  - belongsTo: user
  - belongsTo: language
  - method: isActive()

Customer Model:
  - belongsTo: user
  - belongsTo: language
  - method: isActive()
```

---

### Locale/Language System

**Language Detection Priority:**

**For Authenticated Users:**
1. User's language preference (`$user->admin->language` or `$user->customer->language`)
2. System default language

**For Guest Users (NotLoggedIn middleware):**
1. Cookie: `last_language_code`
2. System default: `Setting::get('default_language')`
3. Hardcoded fallback: `'en'`

**Implementation:**
```php
// Both App locale and Carbon locale are set
\App::setLocale($language_code);
\Carbon\Carbon::setLocale($language_code);
```

---

### Site Online/Offline Mode

**Frontend Middleware Logic:**
```php
if (\Acelle\Model\Setting::get('site_online') == 'false'
    && (isset($user) && get_tmp_quota($user->customer, 'access_when_offline') != 'yes')) {
    return redirect()->action('Controller@offline');
}
```

**Features:**
- Global site offline switch via settings
- VIP override via quota system (`access_when_offline`)
- Only affects customer-facing frontend
- Backend (admin) always accessible

---

### WordPress Multi-Tenant Configuration (Commented Out)

**Found in Frontend.php (lines 52-84):**
```php
// Wordpress db by user
/*
if (isset($user) && isset($user->customer)) {
    config([
        'database.connections.wordpress.database' => config('wordpress.'.$user->customer->id.'.db_name'),
        'database.connections.wordpress.prefix' => config('wordpress.'.$user->customer->id.'.db_prefix'),
        'wordpress.url' => config('wordpress.'.$user->customer->id.'.url'),
    ]);

    // Dynamic host, port, user, password configuration
}
*/
```

**Analysis:**
- Per-customer WordPress database configuration
- Dynamic connection credentials at runtime
- Currently DISABLED (commented out)
- Indicates Acelle may have had WordPress integration feature

**Migration Decision:**
- ❌ Skip - This feature is commented out and likely deprecated
- ❌ Not relevant for email marketing system migration
- ✅ If WordPress integration is needed, implement as separate plugin

---

### Installation Guards

**Installed.php:**
```php
public function handle($request, Closure $next)
{
    if (isInitiated()) {
        return redirect()->action('HomeController@index');
    }
    return $next($request);
}
```
- Redirects to home if app is already installed
- Used during installation wizard
- **Migration:** Not needed (one-time installation process)

**NotInstalled.php:**
```php
public function handle($request, Closure $next)
{
    // COMMENTED OUT
    //if (!isInitiated()) {
    //    return redirect()->action('InstallController@starting');
    //}
    return $next($request);
}
```
- Currently DISABLED (commented out logic)
- Would redirect to installation wizard if app not installed
- **Migration:** Not needed (disabled and one-time use)

---

## Migration Recommendations

### Step 1: Create Mailing Module Middleware Directory

```bash
mkdir -p app/Http/Middleware/Mailing
```

### Step 2: Migrate Critical Middleware

**Create these files:**

1. **app/Http/Middleware/Mailing/BackendAccess.php**
   ```php
   <?php

   namespace App\Http\Middleware\Mailing;

   use Closure;
   use Illuminate\Http\Request;

   class BackendAccess
   {
       public function handle(Request $request, Closure $next)
       {
           $user = $request->user();

           // Check admin access permission
           if (!$user || !$user->can('mailing.admin.access')) {
               return redirect()->route('mailing.unauthorized');
           }

           // Check if user is active
           if ($user->status !== 'active') {
               return redirect()->route('mailing.user.disabled');
           }

           // Set locale if user has language preference
           if ($user->language) {
               app()->setLocale($user->language);
               \Carbon\Carbon::setLocale($user->language);
           }

           return $next($request);
       }
   }
   ```

2. **app/Http/Middleware/Mailing/CustomerAccess.php**
   ```php
   <?php

   namespace App\Http\Middleware\Mailing;

   use Closure;
   use Illuminate\Http\Request;
   use App\Models\MailingSetting;

   class CustomerAccess
   {
       public function handle(Request $request, Closure $next)
       {
           $user = $request->user();

           // Redirect backend users to admin panel
           if ($user && !$user->can('mailing.customer.access')
               && $user->can('mailing.admin.access')) {
               return redirect()->route('mailing.admin.dashboard');
           }

           // Check customer access permission
           if (!$user || !$user->can('mailing.customer.access')) {
               return redirect()->route('mailing.unauthorized');
           }

           // Check site online mode
           $siteOnline = MailingSetting::get('site_online', true);
           $hasOfflineAccess = $user->can('mailing.access_when_offline');

           if (!$siteOnline && !$hasOfflineAccess) {
               return redirect()->route('mailing.offline');
           }

           // Check if user is active
           if ($user->status !== 'active') {
               return redirect()->route('mailing.user.disabled');
           }

           // Set locale
           if ($user->language) {
               app()->setLocale($user->language);
               \Carbon\Carbon::setLocale($user->language);
           }

           return $next($request);
       }
   }
   ```

3. **app/Http/Middleware/Mailing/GuestLocale.php**
   ```php
   <?php

   namespace App\Http\Middleware\Mailing;

   use Closure;
   use Illuminate\Http\Request;
   use App\Models\MailingSetting;
   use App\Models\MailingLanguage;

   class GuestLocale
   {
       public function handle(Request $request, Closure $next)
       {
           // Get language preference
           $languageCode = $request->cookie('mailing_language')
               ?? MailingSetting::get('default_language', 'en');

           // Verify language exists
           if (MailingLanguage::where('code', $languageCode)->exists()) {
               app()->setLocale($languageCode);
               \Carbon\Carbon::setLocale($languageCode);
           } else {
               app()->setLocale('en');
               \Carbon\Carbon::setLocale('en');
           }

           return $next($request);
       }
   }
   ```

### Step 3: Register Middleware in bootstrap/app.php

```php
// bootstrap/app.php

->withMiddleware(function (Middleware $middleware) {
    // Mailing Module Middleware
    $middleware->alias([
        'mailing.backend' => \App\Http\Middleware\Mailing\BackendAccess::class,
        'mailing.customer' => \App\Http\Middleware\Mailing\CustomerAccess::class,
        'mailing.guest.locale' => \App\Http\Middleware\Mailing\GuestLocale::class,
    ]);

    // CSRF Exceptions for Mailing
    $middleware->validateCsrfTokens(except: [
        'mailing/webhooks/*',
        'mailing/api/*',
        'mailing/delivery/*',
        'mailing/embedded-form-*',
    ]);

    // Authentication redirect
    $middleware->redirectGuestsTo(fn() => route('mailing.login'));
})
```

### Step 4: Define Permissions Using Spatie Laravel Permission

```php
// database/seeders/MailingPermissionSeeder.php

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class MailingPermissionSeeder extends Seeder
{
    public function run()
    {
        // Create permissions
        Permission::create(['name' => 'mailing.admin.access']);
        Permission::create(['name' => 'mailing.customer.access']);
        Permission::create(['name' => 'mailing.access_when_offline']);

        // Create roles
        $adminRole = Role::create(['name' => 'mailing_admin']);
        $customerRole = Role::create(['name' => 'mailing_customer']);
        $vipRole = Role::create(['name' => 'mailing_vip']);

        // Assign permissions
        $adminRole->givePermissionTo([
            'mailing.admin.access',
            'mailing.access_when_offline',
        ]);

        $customerRole->givePermissionTo('mailing.customer.access');

        $vipRole->givePermissionTo([
            'mailing.customer.access',
            'mailing.access_when_offline',
        ]);
    }
}
```

### Step 5: Apply Middleware to Routes

```php
// modules/Mailing/routes/web.php

use Illuminate\Support\Facades\Route;

// Guest routes (with locale)
Route::middleware(['mailing.guest.locale'])->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('mailing.login');
    Route::post('/login', [LoginController::class, 'login']);
    Route::get('/register', [RegisterController::class, 'showRegistrationForm']);
});

// Customer routes
Route::middleware(['auth', 'mailing.customer'])->prefix('customer')->name('mailing.customer.')->group(function () {
    Route::get('/dashboard', [CustomerDashboardController::class, 'index'])->name('dashboard');
    Route::resource('campaigns', CampaignController::class);
    Route::resource('subscribers', SubscriberController::class);
});

// Admin routes
Route::middleware(['auth', 'mailing.backend'])->prefix('admin')->name('mailing.admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::resource('users', UserController::class);
    Route::resource('settings', SettingController::class);
});
```

---

## Implementation Checklist

### Pre-Migration Tasks
- [ ] Review User model structure in main system
- [ ] Verify Spatie Laravel Permission is installed
- [ ] Check existing permission naming conventions
- [ ] Review Language/Locale system architecture
- [ ] Audit MailingSetting model implementation

### Middleware Migration
- [ ] Create `app/Http/Middleware/Mailing/` directory
- [ ] Migrate `BackendAccess.php`
- [ ] Migrate `CustomerAccess.php`
- [ ] Migrate `GuestLocale.php`
- [ ] Update all permission checks to Spatie format
- [ ] Update all user relationship references

### Configuration
- [ ] Register middleware aliases in `bootstrap/app.php`
- [ ] Configure CSRF exceptions
- [ ] Configure authentication redirect routes
- [ ] Define Mailing permissions in seeder
- [ ] Create Mailing roles (admin, customer, vip)

### Integration
- [ ] Apply middleware to Mailing module routes
- [ ] Test admin access with `mailing.admin.access` permission
- [ ] Test customer access with `mailing.customer.access` permission
- [ ] Test guest locale selection
- [ ] Test site offline mode
- [ ] Test user disabled status handling
- [ ] Test language switching

### Testing
- [ ] Unit test: BackendAccess middleware
- [ ] Unit test: CustomerAccess middleware
- [ ] Unit test: GuestLocale middleware
- [ ] Feature test: Admin route protection
- [ ] Feature test: Customer route protection
- [ ] Feature test: Permission enforcement
- [ ] Feature test: Locale persistence
- [ ] Feature test: Site offline mode

---

## Key Dependencies to Verify

### User Model Requirements
```php
// app/Models/User.php

use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasRoles;

    // Required attributes
    protected $fillable = [
        'name',
        'email',
        'password',
        'status', // 'active', 'disabled', 'pending'
        'language', // Language code: 'en', 'es', 'fr', etc.
    ];

    // Required methods
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    // Check if user has admin role
    public function isAdmin(): bool
    {
        return $this->hasPermissionTo('mailing.admin.access');
    }

    // Check if user has customer role
    public function isCustomer(): bool
    {
        return $this->hasPermissionTo('mailing.customer.access');
    }
}
```

### MailingSetting Model Requirements
```php
// app/Models/MailingSetting.php

class MailingSetting extends Model
{
    // Required method
    public static function get(string $key, $default = null)
    {
        $setting = static::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    // Required settings
    // - site_online: boolean (true/false)
    // - default_language: string (language code)
}
```

### MailingLanguage Model Requirements
```php
// app/Models/MailingLanguage.php

class MailingLanguage extends Model
{
    protected $fillable = ['code', 'name', 'is_active'];

    // Required methods
    public static function getDefault(): ?self
    {
        $code = MailingSetting::get('default_language', 'en');
        return static::where('code', $code)->first();
    }
}
```

---

## Potential Issues and Solutions

### Issue 1: Permission System Mismatch
**Problem:** Acelle uses custom permission gates (`admin_access`, `customer_access`)
**Solution:** Map to Spatie permissions with `mailing.` prefix

**Migration:**
```php
// Old Acelle way
$user->can("admin_access", $user)

// New system way
$user->can('mailing.admin.access')
```

### Issue 2: User Relationship Structure
**Problem:** Acelle uses separate Admin and Customer models related to User
**Solution:** Unify into single User model with roles and permissions

**Migration:**
```php
// Old Acelle structure
User -> hasOne(Admin) -> hasMany(...)
User -> hasOne(Customer) -> hasMany(...)

// New unified structure
User -> hasRole('mailing_admin')
User -> hasRole('mailing_customer')
User -> hasPermissionTo('mailing.admin.access')
```

### Issue 3: Language Model Integration
**Problem:** Acelle uses `admin->language` and `customer->language` relationships
**Solution:** Store language code directly on User model or use system's Language model

**Migration:**
```php
// Old Acelle way
$user->admin->language->code

// New system way (Option A: Direct attribute)
$user->language // 'en', 'es', 'fr'

// New system way (Option B: Relationship)
$user->language()->code // If Language model exists
```

### Issue 4: Setting Model Differences
**Problem:** Acelle uses `Setting::get('key')` static method
**Solution:** Use system's MailingSetting model with same pattern

**Migration:**
```php
// Old Acelle way
\Acelle\Model\Setting::get('site_online')

// New system way
\App\Models\MailingSetting::get('site_online')
```

### Issue 5: Helper Functions
**Problem:** Acelle uses `get_tmp_quota()` helper function
**Solution:** Implement as permission or setting check

**Migration:**
```php
// Old Acelle way
get_tmp_quota($user->customer, 'access_when_offline') != 'yes'

// New system way (Option A: Permission)
$user->can('mailing.access_when_offline')

// New system way (Option B: User attribute)
$user->hasOfflineAccess()
```

### Issue 6: Redirect Action References
**Problem:** Acelle uses `redirect()->action('Controller@method')`
**Solution:** Use named routes with `route()` helper

**Migration:**
```php
// Old Acelle way
redirect()->action('Controller@notAuthorized')
redirect()->action('Admin\HomeController@index')

// New system way
redirect()->route('mailing.unauthorized')
redirect()->route('mailing.admin.dashboard')
```

---

## Testing Strategy

### Unit Tests

**BackendAccess Middleware Test:**
```php
// tests/Unit/Middleware/Mailing/BackendAccessTest.php

public function test_allows_active_admin_users()
{
    $user = User::factory()->create(['status' => 'active']);
    $user->givePermissionTo('mailing.admin.access');

    $request = Request::create('/mailing/admin/dashboard');
    $request->setUserResolver(fn() => $user);

    $middleware = new BackendAccess();
    $response = $middleware->handle($request, fn($req) => new Response());

    $this->assertInstanceOf(Response::class, $response);
}

public function test_redirects_users_without_admin_permission()
{
    $user = User::factory()->create();

    $request = Request::create('/mailing/admin/dashboard');
    $request->setUserResolver(fn() => $user);

    $middleware = new BackendAccess();
    $response = $middleware->handle($request, fn($req) => new Response());

    $this->assertInstanceOf(RedirectResponse::class, $response);
    $this->assertEquals(route('mailing.unauthorized'), $response->getTargetUrl());
}

public function test_redirects_disabled_users()
{
    $user = User::factory()->create(['status' => 'disabled']);
    $user->givePermissionTo('mailing.admin.access');

    $request = Request::create('/mailing/admin/dashboard');
    $request->setUserResolver(fn() => $user);

    $middleware = new BackendAccess();
    $response = $middleware->handle($request, fn($req) => new Response());

    $this->assertInstanceOf(RedirectResponse::class, $response);
    $this->assertEquals(route('mailing.user.disabled'), $response->getTargetUrl());
}

public function test_sets_locale_from_user_language()
{
    $user = User::factory()->create(['language' => 'es']);
    $user->givePermissionTo('mailing.admin.access');

    $request = Request::create('/mailing/admin/dashboard');
    $request->setUserResolver(fn() => $user);

    $middleware = new BackendAccess();
    $middleware->handle($request, fn($req) => new Response());

    $this->assertEquals('es', app()->getLocale());
}
```

### Feature Tests

**Admin Access Feature Test:**
```php
// tests/Feature/Mailing/AdminAccessTest.php

public function test_admin_can_access_dashboard()
{
    $admin = User::factory()->create(['status' => 'active']);
    $admin->givePermissionTo('mailing.admin.access');

    $response = $this->actingAs($admin)
        ->get(route('mailing.admin.dashboard'));

    $response->assertOk();
}

public function test_customer_cannot_access_admin_dashboard()
{
    $customer = User::factory()->create();
    $customer->givePermissionTo('mailing.customer.access');

    $response = $this->actingAs($customer)
        ->get(route('mailing.admin.dashboard'));

    $response->assertRedirect(route('mailing.unauthorized'));
}

public function test_site_offline_prevents_customer_access()
{
    MailingSetting::set('site_online', false);

    $customer = User::factory()->create();
    $customer->givePermissionTo('mailing.customer.access');

    $response = $this->actingAs($customer)
        ->get(route('mailing.customer.dashboard'));

    $response->assertRedirect(route('mailing.offline'));
}

public function test_vip_customer_can_access_when_offline()
{
    MailingSetting::set('site_online', false);

    $vip = User::factory()->create();
    $vip->givePermissionTo(['mailing.customer.access', 'mailing.access_when_offline']);

    $response = $this->actingAs($vip)
        ->get(route('mailing.customer.dashboard'));

    $response->assertOk();
}
```

---

## Summary and Next Steps

### What to Migrate
1. ✅ **Backend.php** → `BackendAccess.php` (admin authorization)
2. ✅ **Frontend.php** → `CustomerAccess.php` (customer authorization)
3. ✅ **NotLoggedIn.php** → `GuestLocale.php` (guest locale)
4. ⚠️ **VerifyCsrfToken.php** → Configure CSRF exceptions in `bootstrap/app.php`
5. ⚠️ **Authenticate.php** → Use Laravel 12 native redirect configuration

### What to Skip
6. ❌ **Subscription.php** - Empty placeholder (skip unless SaaS needed)
7. ❌ **Installed.php** - Installation guard (one-time use)
8. ❌ **NotInstalled.php** - Disabled installation guard
9. ❌ **All standard Laravel middleware** - Use Laravel 12 defaults

### Critical Dependencies
- Spatie Laravel Permission package
- User model with `status` and `language` attributes
- MailingSetting model with `get()` method
- MailingLanguage model
- Permission gates: `mailing.admin.access`, `mailing.customer.access`
- Named routes for all redirects

### Recommended Approach
1. **Phase 1:** Set up permissions and roles using Spatie
2. **Phase 2:** Migrate three critical middleware files
3. **Phase 3:** Configure middleware in `bootstrap/app.php`
4. **Phase 4:** Update routes to use middleware
5. **Phase 5:** Write comprehensive tests
6. **Phase 6:** Test all authentication flows

---

## Appendix: Middleware Registration Example

### Complete bootstrap/app.php Configuration

```php
<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Global middleware aliases
        $middleware->alias([
            // Mailing module middleware
            'mailing.backend' => \App\Http\Middleware\Mailing\BackendAccess::class,
            'mailing.customer' => \App\Http\Middleware\Mailing\CustomerAccess::class,
            'mailing.guest.locale' => \App\Http\Middleware\Mailing\GuestLocale::class,
        ]);

        // CSRF exceptions
        $middleware->validateCsrfTokens(except: [
            'mailing/webhooks/*',
            'mailing/api/*',
            'mailing/delivery/*',
            'mailing/embedded-form-*',
        ]);

        // Authentication redirects
        $middleware->redirectGuestsTo(fn() => route('login'));

        // Trim strings exceptions
        $middleware->trimStrings(except: [
            'current_password',
            'password',
            'password_confirmation',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
```

---

**End of Report**

Generated for Alsernet Mailing Module Migration
Source: Acelle Email Marketing System
Target: Laravel 12 with Spatie Permissions
Date: 2026-01-29
