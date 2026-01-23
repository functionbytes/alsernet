# Module Provider Architecture Patterns

## Overview

This document outlines the two different approaches used for organizing module providers in this Laravel system, comparing the Document module (proven pattern) with the Mailrelay module (newer pattern).

---

## Pattern 1: Direct Route Loading (Document Module) ✅ PROVEN

### Structure
```
Document Module
├── app/Providers/
│   ├── DocumentsServiceProvider.php  (single provider)
│   └── EventServiceProvider.php
└── routes/
    ├── web.php   (applies own middleware)
    └── api.php
```

### Implementation

**DocumentsServiceProvider.php (lines 140-153):**
```php
protected function registerRoutes(): void
{
    // Routes loaded directly with require
    require module_path($this->name, 'routes/web.php');

    // API routes with explicit prefix and name
    Route::prefix('api/documents')
        ->name('api.documents.')
        ->group(function () {
            require module_path($this->name, 'routes/api.php');
        });
}
```

**routes/web.php (line 28):**
```php
// Routes apply middleware directly
Route::middleware(['web', 'auth'])->group(function () {
    Route::prefix('documents')->name('documents.')->group(function () {
        // All operational and settings routes...
    });
});
```

### Key Characteristics

✅ **Advantages:**
- Single point of route registration
- Middleware applied at the source (routes file)
- No RouteServiceProvider complexity
- Clear separation: provider handles registration, routes file handles structure
- Easier to debug middleware stack

❌ **Limitations:**
- Less modular for large modules
- Namespace handling done inline

---

## Pattern 2: RouteServiceProvider Pattern (Mailrelay Module) 🔄 CURRENT

### Structure
```
Mailrelay Module
├── app/Providers/
│   ├── MailrelayServiceProvider.php    (delegates to RouteServiceProvider)
│   ├── RouteServiceProvider.php        (handles route registration)
│   └── EventServiceProvider.php
└── routes/
    ├── web.php    (applies middleware)
    └── api.php
```

### Implementation

**MailrelayServiceProvider.php (boot method):**
```php
public function boot(): void
{
    // Delegates route registration
    $this->registerRoutes();  // NOT DEFINED - relies on boot() parent call

    // Other registrations...
    $this->registerGates();
    $this->registerViewComposers();
    // ...
}
```

**RouteServiceProvider.php:**
```php
public function map(): void
{
    $this->mapApiRoutes();
    $this->mapWebRoutes();
}

protected function mapWebRoutes(): void
{
    Route::namespace($this->moduleNamespace)
        ->group(module_path('Mailrelay', '/routes/web.php'));
}
```

**routes/web.php (line 33):**
```php
// Middleware applied here (again)
Route::middleware(['web', 'auth'])->group(function () {
    Route::prefix('mailrelay')->name('mailrelay.')->group(function () {
        // Routes...
    });
});
```

### Key Characteristics

✅ **Advantages:**
- More modular and extensible
- Separates route registration logic
- Follows Laravel package conventions
- Better for complex multi-route scenarios

⚠️ **Issues Found:**
- **Middleware Duplication**: Routes file applies `['web', 'auth']` AND RouteServiceProvider may apply additional middleware
- **Missing Boot Signature**: MailrelayServiceProvider doesn't define `registerRoutes()`, relying on parent behavior
- **Namespace Handling**: RouteServiceProvider sets namespace, but this may conflict with routes file structure
- **Gate Definition Pattern**: Uses `Gate::define()` with closures that serialize in cache (caused corruption)

---

## Critical Differences

| Aspect | Document (Direct) | Mailrelay (RouteServiceProvider) |
|--------|-------------------|----------------------------------|
| **Route Loading** | `require` in provider | RouteServiceProvider wrapper |
| **Middleware Location** | routes/web.php | Both provider AND routes/web.php |
| **Namespace Application** | Inline in routes | RouteServiceProvider |
| **Config Loading** | Recursive directory iteration | Direct mergeConfigFrom |
| **Gate Pattern** | Gate::before() with dynamic checking | Gate::define() with static closures |
| **Event Handling** | Separate EventServiceProvider | Minimal/stub implementation |

---

## Identified Issues in Mailrelay Module

### 1. Middleware Duplication
**Problem**: Middleware appears twice in the request stack
```
['web', 'web', 'auth', 'role:super-admin']
```

**Cause**:
- routes/web.php line 33: `Route::middleware(['web', 'auth'])`
- RouteServiceProvider may apply additional middleware

**Solution**: Verify RouteServiceProvider is NOT adding middleware, only namespace

### 2. Route Cache Serialization Error
**Error**: `Call to undefined method Closure::__set_state()`

**Cause**: Gate::define() uses Closures that cannot be serialized when route cache is enabled

**Solution**: Migrate to Gate::before() pattern like Document module (lines 106-134)

### 3. Missing $errors Variable in Views
**Problem**: Blade components use `@error` without variable being passed

**Cause**: Controller not passing MessageBag to view

**Solution**: Update controller to: `->withErrors(session()->get('errors', new MessageBag()))`

---

## Recommended Refactoring

To align Mailrelay with proven Document module patterns:

### Step 1: Simplify Route Registration
Remove RouteServiceProvider, register routes directly in MailrelayServiceProvider:

```php
// In MailrelayServiceProvider.boot()
protected function registerRoutes(): void
{
    require module_path('Mailrelay', 'routes/web.php');

    Route::prefix('api')
        ->name('api.')
        ->group(function () {
            require module_path('Mailrelay', 'routes/api.php');
        });
}
```

### Step 2: Migrate Gate Pattern
Replace static Gate::define() with dynamic Gate::before():

```php
protected function registerGates(): void
{
    Gate::before(function ($user, $ability) {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        // Check permission table for dynamic permissions
        try {
            return $user->hasPermissionTo($ability);
        } catch (\Exception $e) {
            return false;
        }
    });

    // Keep specific gates for framework-level authorization
    Gate::define('mailrelay.access', fn ($user) => true);
}
```

### Step 3: Config Loading Pattern
Adopt Document module's recursive config loading:

```php
protected function registerConfig(): void
{
    $configPath = module_path('Mailrelay', 'config');
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($configPath)
    );

    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $key = str_replace([DIRECTORY_SEPARATOR, '.php'], ['.', ''],
                str_replace($configPath.DIRECTORY_SEPARATOR, '', $file->getPathname())
            );
            $this->mergeConfigFrom($file->getPathname(), 'mailrelay.'.$key);
        }
    }
}
```

---

## Testing Provider Changes

After refactoring, verify:

1. **Routes load correctly**:
   ```bash
   php artisan route:list | grep mailrelay
   ```

2. **Middleware stack is correct**:
   ```bash
   # Check for duplicates
   php artisan route:list -v | grep web.*web
   ```

3. **Permissions work**:
   ```bash
   php artisan tinker
   >>> Gate::allows('mailrelay.access', auth()->user())
   ```

4. **View rendering**:
   ```
   Navigate to https://system.test/settings/mailrelay/general
   Should render without "undefined variable $errors"
   ```

---

## Summary

| Provider Pattern | Best For | Current Use |
|------------------|----------|------------|
| **Direct (Document)** | Smaller modules, simpler structure | ✅ Documents, Theme |
| **RouteServiceProvider (Mailrelay)** | Large complex modules, high extensibility | 🔄 Mailrelay (needs refinement) |

The Mailrelay module has the right architecture for a complex module but needs refinements to avoid:
- Middleware duplication
- Closure serialization in route cache
- Proper variable passing in views

Document module provides a proven, simpler approach that works reliably but may need additional abstraction for very large modules.
