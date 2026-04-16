---
name: module-entity
description: "Add features to an existing module. Supports: entity (CRUD completo), api (REST layer), settings (config page), events (event system), dashboard (stats+charts). Combine multiple in one call. Use when adding any functionality to a module."
disable-model-invocation: false
argument-hint: "[ModuleName] [features: entity,api,settings,events,dashboard] [EntityName] [description]"
---

# Module Entity Builder: $ARGUMENTS

Adds features to an existing module. Parse arguments to determine:
- **ModuleName**: PascalCase (e.g., `Inventory`)
- **Features requested**: one or more from the list below
- **EntityName**: PascalCase singular if creating an entity (e.g., `Product`)
- **Description**: remaining text

## Feature Detection

Detect which features to generate from the arguments. Examples:
- `/module-entity Inventory Product Gestion de productos` → entity (default if EntityName given)
- `/module-entity Inventory api Product` → API layer for Product
- `/module-entity Inventory settings` → settings page
- `/module-entity Inventory events ProductCreated` → event system
- `/module-entity Inventory dashboard` → dashboard with stats
- `/module-entity Inventory Product entity api settings dashboard` → ALL features for Product

If no feature keyword detected but an EntityName is given, default to **entity** (full CRUD).

## MANDATORY: Read Existing Module First

1. Read `modules/{ModuleName}/module.json` to confirm it exists
2. Read existing models in `modules/{ModuleName}/app/Models/` for naming patterns
3. Read `modules/{ModuleName}/routes/web.php` for route group structure
4. Read ServiceProvider for NavService and route registration patterns
5. Use Boost `database-schema` to check existing tables

Also read the supporting files in this skill:
- [reference.md](reference.md) for nwidart commands and project patterns
- [existing-modules.md](existing-modules.md) for similar module examples
- [templates.md](templates.md) for exact code templates

**For all Blade views** (entity index, forms, settings, dashboard), ALWAYS consult the **ui-patterns** skill files:
- `ui-patterns/list-patterns.md` for index page structure
- `ui-patterns/form-patterns.md` for create/edit forms
- `ui-patterns/modal-patterns.md` for filter/delete/bulk modals
- `ui-patterns/dashboard-patterns.md` for stats + charts
- `ui-patterns/javascript-patterns.md` for BulkActions, select2, ajax, delete

---

## Feature: entity (full CRUD)

Generates: Model + Migration + Factory + Controller + Form Requests + Routes + Blade Views

### Migration
```bash
php artisan module:make-migration create_{table}_table {ModuleName}
```
- Include indexes on filtered/sorted columns
- Foreign keys with `->constrained()->cascadeOnDelete()`
- `timestamps()` + `softDeletes()`

### Model
File: `modules/{ModuleName}/app/Models/{EntityName}.php`
- `HasFactory`, `SoftDeletes` traits
- `$fillable` array matching migration
- `casts()` method for booleans, arrays, dates
- Relationships (belongsTo, hasMany) based on foreign keys

### Factory
File: `modules/{ModuleName}/database/factories/{EntityName}Factory.php`
- Realistic fake data with `$this->faker`
- State methods: `inactive()`, `completed()`, etc.

### Form Requests
Files: `Store{EntityName}Request.php` + `Update{EntityName}Request.php`
- `authorize()` checks permission: `{alias}.create` / `{alias}.update`
- `rules()` matches migration columns

### Controller
File: `modules/{ModuleName}/app/Http/Controllers/{EntityName}Controller.php`
- Methods: `index`, `create`, `store`, `edit`, `update`, `destroy`
- `$this->authorize()` on each method
- `{EntityName}::query()` with eager loading (never `DB::`)
- Form Requests for store/update
- Views: `{alias}::{entity-kebab}.index`, `{alias}::{entity-kebab}.form`

### Routes
Add to existing `routes/web.php` inside auth group:
```php
Route::prefix('{entity-kebab}')->name('{entity-kebab}.')->group(function () {
    Route::get('/', [{EntityName}Controller::class, 'index'])->name('index');
    Route::get('/create', [{EntityName}Controller::class, 'create'])->name('create');
    Route::post('/', [{EntityName}Controller::class, 'store'])->name('store');
    Route::get('/{id}/edit', [{EntityName}Controller::class, 'edit'])->name('edit');
    Route::put('/{id}', [{EntityName}Controller::class, 'update'])->name('update');
    Route::delete('/{id}', [{EntityName}Controller::class, 'destroy'])->name('destroy');
});
```

### Blade Views
Directory: `modules/{ModuleName}/resources/views/{entity-kebab}/`

**index.blade.php**: `@extends('layouts.theme')`, card + table, dropdown actions (`fa-ellipsis-vertical`), delete modal (`modal-dialog-centered`), jQuery AJAX for delete, toastr notifications, bulk actions toolbar

**form.blade.php** (shared create/edit): `@extends('layouts.theme')`, form with `@csrf` + `@method('PUT')` for edit, Bootstrap card with `row g-3`, per-field validation errors, footer with submit + cancel

---

## Feature: api

Generates: API Resource + API Controller + api.php routes

### API Resource
File: `modules/{ModuleName}/app/Http/Resources/{EntityName}Resource.php`
- `extends JsonResource`
- camelCase keys, ISO8601 dates
- `$this->whenLoaded()` for relationships
- `$this->whenCounted()` for counts

### API Controller
File: `modules/{ModuleName}/app/Http/Controllers/Api/{EntityName}ApiController.php`
- JSON responses: `{ success: bool, message: string, data: Resource }`
- Status codes: 200 GET/PUT, 201 POST, 204 DELETE, 422 validation, 404 not found
- DB::transaction for writes
- Pagination with `->paginate($request->input('per_page', 15))`

### Routes
File: `modules/{ModuleName}/routes/api.php` (create if doesn't exist)
```php
Route::middleware(['api', 'auth:sanctum'])
    ->prefix('{alias}')
    ->name('api.{alias}.')
    ->group(function () {
        Route::apiResource('{entity-kebab}', {EntityName}ApiController::class);
    });
```

### Register in ServiceProvider
Add to `registerRoutes()`:
```php
Route::middleware('api')->prefix('api')
    ->group(module_path($this->moduleName, 'routes/api.php'));
```

---

## Feature: settings

Generates: SettingsController + Form Request + routes + Blade view + NavService update

### Controller
File: `modules/{ModuleName}/app/Http/Controllers/{ModuleName}SettingsController.php`
- Uses `Modules\Core\Models\Setting` for DB-backed config
- `private const PREFIX = '{alias}.';`
- `index()`: passes `$get` closure to view for reading settings
- `update()`: validates, sets each setting, clears prefix cache
- Middleware: `can:{ModuleName}.settings.index` / `can:{ModuleName}.settings.update`

### Routes
Add to `routes/web.php`:
```php
Route::prefix('panel/settings/{alias}')->name('settings.{alias}.')->group(function () {
    Route::get('/', [{ModuleName}SettingsController::class, 'index'])->name('index');
    Route::patch('/', [{ModuleName}SettingsController::class, 'update'])->name('update');
});
```

### View
File: `modules/{ModuleName}/resources/views/settings/index.blade.php`
- Form with `@csrf @method('PATCH')`
- Bootstrap card, `row g-3`, `form-label` + `form-control`
- Footer with save button (`fas fa-save`)

### NavService
Add to `registerMenus()`:
```php
NavService::registerSidebar('settings', [
    'title' => '{ModuleName}',
    'items' => [
        ['label' => 'Configuracion general', 'route' => 'settings.{alias}.index'],
    ],
]);
```

### Permissions
Add to seeder: `{alias}.settings.view`, `{alias}.settings.update`

---

## Feature: events

Generates: EventServiceProvider + Event class + Listener class

### Event
File: `modules/{ModuleName}/app/Events/{EventName}.php`
- Constructor with model property promotion: `public {EntityName} ${entityVar}`
- `Dispatchable`, `InteractsWithSockets`, `SerializesModels`
- Add `implements ShouldBroadcast` + `broadcastOn()` if real-time needed

### Listener
File: `modules/{ModuleName}/app/Listeners/{ListenerName}.php`
- `handle({EventName} $event)` method
- Add `implements ShouldQueue` + `use Queueable` for heavy operations

### EventServiceProvider
File: `modules/{ModuleName}/app/Providers/EventServiceProvider.php`
- `$listen` array mapping events to listeners
- `Module::find()->isDisabled()` check in `boot()`
- `shouldDiscoverEvents()` returns `false`

### Register
Add to main ServiceProvider `register()`:
```php
$this->app->register(EventServiceProvider::class);
```

### Dispatch
```php
event(new {EventName}($model));
```

---

## Feature: dashboard

Generates: DashboardController + routes + Blade view with KPIs + chart

### Controller
File: `modules/{ModuleName}/app/Http/Controllers/{ModuleName}DashboardController.php`
- `index()`: returns view with `$stats` array
- `chartData()`: returns JSON with `labels` + `datasets` for AJAX chart
- `getStats()`: counts (total, this_month, active, etc.)

### Routes
Add to `routes/web.php`:
```php
Route::get('panel/{alias}/dashboard', [{ModuleName}DashboardController::class, 'index'])->name('{alias}.dashboard');
Route::get('panel/{alias}/dashboard/chart-data', [{ModuleName}DashboardController::class, 'chartData'])->name('{alias}.dashboard.chart-data');
```

### View
File: `modules/{ModuleName}/resources/views/dashboard/index.blade.php`
- Row of KPI cards (Bootstrap + rounded-circle icons)
- Chart card with DevExpress `dxChart` (bar type, color `#90bb13`)
- Range selector (7/30/90 days) with jQuery `$.getJSON`
- `number_format()` for stats display

### NavService
Add "Dashboard" as first sidebar item:
```php
['label' => 'Dashboard', 'route' => '{alias}.dashboard'],
```

---

## Post-Generation Checklist

1. `php artisan module:migrate {ModuleName}` (if new migration)
2. `php artisan route:clear` (if new routes added)
3. `php artisan config:clear` (if config changed)
4. `php artisan route:list --name={alias}` (verify routes)
5. `vendor/bin/pint --dirty` (format all new PHP files)
6. Check NavService items appear in UI (refresh page, NavService is registered at boot)

### Cache commands by what changed

| Feature added | Commands to run |
|---------------|----------------|
| `entity` (new Model class) | `composer dump-autoload` (only if autoload-dev tests needed) |
| `entity` (new routes) | `php artisan route:clear` |
| `api` (new api.php) | `php artisan route:clear` |
| `settings` (new Setting model usage) | `php artisan cache:clear` (Setting model may cache) |
| `events` (new EventServiceProvider) | `php artisan config:clear` + `php artisan event:clear` |
| `dashboard` (new routes) | `php artisan route:clear` |
| **Anything and unsure** | `php artisan optimize:clear` |

## Rules (ALL features)
- Font Awesome 6 ONLY (not Tabler)
- jQuery + AJAX (not Livewire/Inertia)
- `Model::query()` over `DB::`
- Form Requests for ALL validation
- Section titles: capitalize first word only
- Table actions: dropdown with `fa-ellipsis-vertical`
- Modals: `modal-dialog-centered`, footer buttons `w-100` stacked
- No inline styles
- No `style=""` - create CSS classes
- select2: NEVER `theme: 'bootstrap-5'`
- Primary color: `#90bb13`
