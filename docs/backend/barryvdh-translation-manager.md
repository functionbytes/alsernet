# Laravel Translation Manager (barryvdh/laravel-translation-manager)

## Overview

The `barryvdh/laravel-translation-manager` package is a database-backed translation management system for Laravel. It **does not replace** Laravel's built-in translation system, but rather provides a workflow to:

1. Import translation files into a database
2. Edit translations through a web interface
3. Export translations back to language files

This approach enables non-technical users to manage translations without directly editing PHP/JSON files.

---

## Installation & Setup

### 1. Install via Composer

```bash
composer require barryvdh/laravel-translation-manager
```

### 2. Publish and Run Migrations

```bash
php artisan vendor:publish --provider="Barryvdh\TranslationManager\ManagerServiceProvider" --tag=migrations
php artisan migrate
```

This creates the `ltm_langs` table in your database.

### 3. Publish Configuration

```bash
php artisan vendor:publish --provider="Barryvdh\TranslationManager\ManagerServiceProvider" --tag=config
```

This creates `config/translation-manager.php` for customization.

### 4. Publish Views (Optional)

```bash
php artisan vendor:publish --provider="Barryvdh\TranslationManager\ManagerServiceProvider" --tag=views
```

Publishes Blade templates for customizing the web interface.

### 5. Configure Middleware

**Important for Laravel 5.2+:** Update `config/translation-manager.php` to include both `web` and `auth` middleware:

```php
'route' => [
    'prefix' => 'translations',
    'middleware' => ['web', 'auth'],
],
```

---

## Database Structure

### `ltm_langs` Table Schema

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigIncrements | Primary key |
| `status` | integer (default: 0) | Translation status (0 = saved, 1 = changed) |
| `locale` | string | Language code (e.g., 'en', 'es', 'de') |
| `group` | string | Translation group/file name |
| `key` | text | Translation key identifier |
| `value` | text (nullable) | Translated text content |
| `created_at` | timestamp | Record creation time |
| `updated_at` | timestamp | Last modification time |

**Collation:** `utf8mb4_bin` for case-sensitive matching and proper Unicode handling.

### Translation Model

**Class:** `Barryvdh\TranslationManager\Models\Translation`

**Status Constants:**
- `STATUS_SAVED = 0` - Translation has been saved to file
- `STATUS_CHANGED = 1` - Translation modified in database but not yet exported

**Available Scopes:**
- `ofTranslatedGroup($group)` - Filter by group, exclude null values
- `orderByGroupKeys($ordered)` - Order by group then key (conditional)
- `selectDistinctGroup()` - Get unique group names

---

## Artisan Commands

### Import Translations from Files to Database

```bash
php artisan translations:import
```

**Options:**
- `--replace` - Overwrite existing database entries with file values

**What it does:**
- Scans `resources/lang/` directory recursively
- Imports all translation keys from PHP and JSON files
- Preserves existing translations unless `--replace` is used

### Find Translation Keys in Source Code

```bash
php artisan translations:find
```

**What it does:**
- Scans PHP, Twig, and Vue files for translation function calls
- Extracts keys from: `__()`, `trans()`, `@lang()`, `trans_choice()`, etc.
- Creates database entries for discovered keys (status: 0)

### Export Translations from Database to Files

```bash
php artisan translations:export <group>
```

**Parameters:**
- `<group>` - Specific translation group to export (optional)

**Options:**
- `--json` - Export as JSON files instead of PHP arrays

**Examples:**
```bash
# Export all groups
php artisan translations:export

# Export specific group
php artisan translations:export messages

# Export to JSON format
php artisan translations:export _json --json
```

### Clean Database Entries

```bash
php artisan translations:clean
```

Removes all translations with `NULL` values to clean up the database and web interface.

### Reset Database

```bash
php artisan translations:reset
```

**Warning:** Deletes ALL translations from the database. Use with caution.

---

## Typical Workflow

### Standard Development Workflow

1. **Import existing translations:**
   ```bash
   php artisan translations:import
   ```

2. **Find missing translations in code:**
   ```bash
   php artisan translations:find
   ```

3. **Edit translations via web interface:**
   - Navigate to `http://yourdomain.com/translations`
   - Click on translation values to edit inline
   - New locales are auto-created when editing

4. **Export back to files:**
   ```bash
   php artisan translations:export
   ```

5. **Commit translation files to version control**

### Best Practice: Development Only

The package documentation recommends:
> "You shouldn't use this in production, just in development to translate your views, then just switch back."

**Reason:** The web interface and dynamic translation detection add overhead suitable for development but not production environments.

---

## Programmatic API Usage

### Accessing the Manager Class

The `Manager` class handles all translation operations. Access it via dependency injection or the service container:

```php
use Barryvdh\TranslationManager\Manager;

// Via dependency injection
class TranslationService
{
    public function __construct(protected Manager $manager)
    {
    }
}

// Via service container
$manager = app(Manager::class);
```

### Import Operations

#### Import All Translations

```php
$manager = app(Manager::class);

// Import without replacing existing
$count = $manager->importTranslations();

// Import and replace existing values
$count = $manager->importTranslations($replace = true);

// Import specific base path
$count = $manager->importTranslations(false, base_path('custom/lang'));

// Import specific group only
$count = $manager->importTranslations(false, null, 'messages');
```

**Returns:** Integer count of imported translations

#### Import Single Translation

```php
$success = $manager->importTranslation(
    $key = 'welcome_message',
    $value = 'Welcome to our site!',
    $locale = 'en',
    $group = 'messages',
    $replace = false
);
```

**Returns:** Boolean success indicator

### Export Operations

#### Export All Groups

```php
$manager->exportAllTranslations();
```

#### Export Specific Group

```php
// Export to PHP array files
$manager->exportTranslations($group = 'messages');

// Export to JSON format
$manager->exportTranslations($group = '_json', $json = true);
```

### Translation Discovery

#### Find Translations in Source Code

```php
// Search default paths (app/, resources/views/)
$count = $manager->findTranslations();

// Search custom path
$count = $manager->findTranslations($path = base_path('modules'));
```

**Returns:** Integer count of found translation keys

**Supported File Types:** PHP, Twig, Vue

**Detected Patterns:**
- `__('key')`
- `trans('key')`
- `@lang('key')`
- `trans_choice('key', $count)`
- `Lang::get('key')`

#### Register Missing Translation Key

```php
$manager->missingKey(
    $namespace = '*',
    $group = 'messages',
    $key = 'new.translation.key'
);
```

Creates database entry for undefined translation key.

### Locale Management

#### Get All Locales

```php
$locales = $manager->getLocales();
// Return: ['en', 'es', 'de', 'fr']
```

**Sources:** Configuration, database, and filesystem locales combined.

#### Add New Locale

```php
$result = $manager->addLocale('fr');
```

**What it does:**
- Creates `resources/lang/fr/` directory
- Removes locale from ignore list (if present)
- Returns directory creation result

#### Remove Locale

```php
$success = $manager->removeLocale('fr');
```

**What it does:**
- Adds locale to ignore list
- Deletes all translations for that locale from database
- Does NOT delete language files (manual cleanup required)

### Maintenance Operations

#### Clean Null Translations

```php
$manager->cleanTranslations();
```

Removes all database entries with `NULL` values.

#### Clear All Translations

```php
$manager->truncateTranslations();
```

**Warning:** Deletes entire translation table. Use with extreme caution.

### Configuration Access

```php
// Get all config
$config = $manager->getConfig();

// Get specific key
$deleteEnabled = $manager->getConfig('delete_enabled');
```

---

## Working with the Translation Model

### Direct Database Queries

```php
use Barryvdh\TranslationManager\Models\Translation;

// Find all translations for a group
$translations = Translation::where('group', 'messages')
    ->where('locale', 'en')
    ->get();

// Get distinct groups
$groups = Translation::selectDistinctGroup()->pluck('group');

// Find changed translations
$changed = Translation::where('status', Translation::STATUS_CHANGED)
    ->get();

// Get translations for specific key across all locales
$allLocales = Translation::where('group', 'messages')
    ->where('key', 'welcome')
    ->get()
    ->pluck('value', 'locale');
```

### Create/Update Translation

```php
use Barryvdh\TranslationManager\Models\Translation;

// Create or update translation
$translation = Translation::firstOrNew([
    'locale' => 'en',
    'group' => 'messages',
    'key' => 'greeting',
]);

$translation->value = 'Hello World!';
$translation->status = Translation::STATUS_CHANGED;
$translation->save();
```

### Using Query Scopes

```php
// Get all translations for a group (excluding null values)
$translations = Translation::ofTranslatedGroup('messages')->get();

// Ordered by group and key
$ordered = Translation::orderByGroupKeys(true)->get();

// Get unique groups
$groups = Translation::selectDistinctGroup()->get();
```

---

## Web Interface Routes & Controllers

### Default Routes

With default configuration (`prefix` = 'translations'):

| Method | Route | Controller Method | Description |
|--------|-------|-------------------|-------------|
| GET | `/translations` | `getIndex()` | Main translation management interface |
| GET | `/translations/view/{group?}` | `getView()` | View specific group |
| POST | `/translations/add/{group}` | `postAdd()` | Add new translation keys |
| POST | `/translations/add-group` | `postAddGroup()` | Create new translation group |
| POST | `/translations/add-locale` | `postAddLocale()` | Add new locale |
| POST | `/translations/edit/{group}` | `postEdit()` | Update translation value |
| POST | `/translations/delete/{group}/{key}` | `postDelete()` | Delete translation |
| POST | `/translations/import` | `postImport()` | Import from files |
| POST | `/translations/find` | `postFind()` | Find in source code |
| POST | `/translations/publish/{group}` | `postPublish()` | Export to files |
| POST | `/translations/translate-missing` | `postTranslateMissing()` | Auto-translate via API |
| POST | `/translations/remove-locale` | `postRemoveLocale()` | Remove locale |

### Controller Integration Example

If you want to build a custom translation panel, you can reuse the controller methods:

```php
use Barryvdh\TranslationManager\Controller as TranslationController;

class CustomTranslationController extends Controller
{
    public function __construct(
        protected TranslationController $translationController
    ) {}

    public function customImport(Request $request)
    {
        // Call the original import method
        $result = $this->translationController->postImport($request);

        // Add custom logic
        Log::info("Imported {$result} translations");

        return response()->json(['count' => $result]);
    }
}
```

Or call the Manager directly:

```php
use Barryvdh\TranslationManager\Manager;

class CustomTranslationController extends Controller
{
    public function apiImport(Request $request, Manager $manager)
    {
        $replace = $request->boolean('replace', false);
        $count = $manager->importTranslations($replace);

        return response()->json([
            'success' => true,
            'imported' => $count,
            'timestamp' => now()
        ]);
    }

    public function apiExport(Request $request, Manager $manager)
    {
        $group = $request->input('group');
        $json = $request->boolean('json', false);

        $manager->exportTranslations($group, $json);

        return response()->json([
            'success' => true,
            'group' => $group,
            'format' => $json ? 'json' : 'php'
        ]);
    }

    public function apiGetMissingKeys(Manager $manager)
    {
        $missing = Translation::whereNull('value')
            ->orWhere('value', '')
            ->selectDistinctGroup()
            ->get()
            ->groupBy('group')
            ->map(function ($group) {
                return $group->count();
            });

        return response()->json([
            'missing_by_group' => $missing,
            'total_missing' => $missing->sum()
        ]);
    }
}
```

---

## Advanced Features

### Automatic Missing Translation Detection

**Setup:** Replace Laravel's default `TranslationServiceProvider` in `config/app.php`:

```php
// Comment out or remove:
// Illuminate\Translation\TranslationServiceProvider::class,

// Add:
Barryvdh\TranslationManager\TranslationServiceProvider::class,
```

**What it does:**
- Extends Laravel's Translator
- Automatically logs missing translation keys to database
- Creates entries when `__()` or `trans()` encounters undefined keys
- Useful during development to discover missing translations by browsing the site

**Important:** Only use during development, not production!

### Custom Service Provider

For full customization, extend the `ManagerServiceProvider`:

```php
namespace App\Providers;

use Barryvdh\TranslationManager\ManagerServiceProvider as BaseProvider;

class CustomTranslationProvider extends BaseProvider
{
    protected function registerManager()
    {
        // Custom manager registration
        parent::registerManager();

        // Add custom bindings
        $this->app->singleton('custom.translator', function ($app) {
            return new CustomTranslationManager($app);
        });
    }

    public function map()
    {
        // Override route mapping
        $config = $this->app['config']->get('translation-manager.route');

        // Add custom routes
        Route::middleware($config['middleware'])
            ->prefix($config['prefix'])
            ->group(function () {
                // Custom translation routes
                Route::get('/custom-view', [CustomController::class, 'index']);
            });

        // Call parent map for default routes
        parent::map();
    }
}
```

Register in `config/app.php`:

```php
'providers' => [
    // ...
    App\Providers\CustomTranslationProvider::class,
],
```

### Google Translate Integration

The package supports auto-translation via Google Translate API:

1. **Install Google Translate package:**
   ```bash
   composer require tanmuhittin/laravel-google-translate
   ```

2. **Publish configuration:**
   ```bash
   php artisan vendor:publish --provider=Tanmuhittin\LaravelGoogleTranslate\LaravelGoogleTranslateServiceProvider
   ```

3. **Configure API key** in published config file

4. **Use via web interface:**
   - Select base locale (source)
   - Select target locale (destination)
   - Click "Translate Missing" button

The controller method `postTranslateMissing()` handles the translation logic.

---

## Configuration Options

### `config/translation-manager.php`

```php
return [
    /*
    |--------------------------------------------------------------------------
    | Routes group config
    |--------------------------------------------------------------------------
    */
    'route' => [
        'prefix' => 'translations',
        'middleware' => ['web', 'auth'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Enable deletion of translations
    |--------------------------------------------------------------------------
    */
    'delete_enabled' => true,

    /*
    |--------------------------------------------------------------------------
    | Exclude specific groups from Laravel Translation Manager
    |--------------------------------------------------------------------------
    */
    'exclude_groups' => [],

    /*
    |--------------------------------------------------------------------------
    | Exclude specific languages from Laravel Translation Manager
    |--------------------------------------------------------------------------
    */
    'exclude_langs' => [],

    /*
    |--------------------------------------------------------------------------
    | Database connection to use
    |--------------------------------------------------------------------------
    */
    'db_connection' => null, // null = default connection

    /*
    |--------------------------------------------------------------------------
    | Sort keys alphabetically
    |--------------------------------------------------------------------------
    */
    'sort_keys' => false,

    /*
    |--------------------------------------------------------------------------
    | Supported locales
    |--------------------------------------------------------------------------
    */
    'locales' => ['en'],
];
```

### Common Configuration Scenarios

**Restrict Access to Specific Role:**

```php
'route' => [
    'middleware' => ['web', 'auth', 'role:translator'],
],
```

**Exclude Laravel's Validation Translations:**

```php
'exclude_groups' => ['validation', 'passwords'],
```

**Use Separate Database Connection:**

```php
'db_connection' => 'mysql_langs',
```

**Enable Alphabetical Sorting:**

```php
'sort_keys' => true,
```

---

## Integration Examples

### Custom API Endpoint for Translation Management

```php
// routes/api.php
Route::middleware(['auth:sanctum'])->group(function () {
    Route::prefix('translations')->group(function () {
        Route::get('/', [ApiTranslationController::class, 'index']);
        Route::post('/import', [ApiTranslationController::class, 'import']);
        Route::post('/export', [ApiTranslationController::class, 'export']);
        Route::post('/sync', [ApiTranslationController::class, 'sync']);
        Route::get('/missing', [ApiTranslationController::class, 'missing']);
    });
});

// app/Http/Controllers/Api/ApiTranslationController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Barryvdh\TranslationManager\Manager;
use Barryvdh\TranslationManager\Models\Translation;
use Illuminate\Http\Request;

class ApiTranslationController extends Controller
{
    public function __construct(protected Manager $manager)
    {
    }

    public function index(Request $request)
    {
        $group = $request->input('group');
        $locale = $request->input('locale', app()->getLocale());

        $query = Translation::where('locale', $locale);

        if ($group) {
            $query->where('group', $group);
        }

        return response()->json([
            'translations' => $query->get(),
            'groups' => Translation::selectDistinctGroup()->pluck('group'),
            'locales' => $this->manager->getLocales(),
        ]);
    }

    public function import(Request $request)
    {
        $replace = $request->boolean('replace', false);
        $count = $this->manager->importTranslations($replace);

        return response()->json([
            'success' => true,
            'imported' => $count,
            'message' => "Imported {$count} translations"
        ]);
    }

    public function export(Request $request)
    {
        $group = $request->input('group');
        $json = $request->boolean('json', false);

        $this->manager->exportTranslations($group, $json);

        return response()->json([
            'success' => true,
            'message' => "Exported translations for group: {$group}"
        ]);
    }

    public function sync(Request $request)
    {
        // Import, find, then export - full synchronization
        $imported = $this->manager->importTranslations();
        $found = $this->manager->findTranslations();
        $this->manager->exportAllTranslations();

        return response()->json([
            'success' => true,
            'imported' => $imported,
            'found' => $found,
            'message' => 'Full synchronization completed'
        ]);
    }

    public function missing(Request $request)
    {
        $locale = $request->input('locale', app()->getLocale());

        $missing = Translation::where('locale', $locale)
            ->where(function ($query) {
                $query->whereNull('value')
                    ->orWhere('value', '');
            })
            ->get()
            ->groupBy('group');

        return response()->json([
            'locale' => $locale,
            'missing_count' => $missing->sum(fn($g) => $g->count()),
            'by_group' => $missing->map(fn($g) => $g->count()),
        ]);
    }
}
```

### Scheduled Translation Sync Command

```php
// app/Console/Commands/SyncTranslations.php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Barryvdh\TranslationManager\Manager;

class SyncTranslations extends Command
{
    protected $signature = 'translations:sync
                            {--import : Import from files}
                            {--find : Find in source}
                            {--export : Export to files}';

    protected $description = 'Synchronize translations between files and database';

    public function handle(Manager $manager): int
    {
        if ($this->option('import') || !$this->hasAnyOption()) {
            $this->info('Importing translations from files...');
            $count = $manager->importTranslations();
            $this->info("Imported {$count} translations");
        }

        if ($this->option('find') || !$this->hasAnyOption()) {
            $this->info('Finding translations in source code...');
            $count = $manager->findTranslations();
            $this->info("Found {$count} translation keys");
        }

        if ($this->option('export') || !$this->hasAnyOption()) {
            $this->info('Exporting translations to files...');
            $manager->exportAllTranslations();
            $this->info('Export completed');
        }

        return Command::SUCCESS;
    }

    private function hasAnyOption(): bool
    {
        return !$this->option('import')
            && !$this->option('find')
            && !$this->option('export');
    }
}
```

Schedule it in `routes/console.php`:

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('translations:sync --import --export')
    ->daily()
    ->at('03:00');
```

### Event Listener for Translation Changes

```php
// app/Listeners/TranslationExportedListener.php
namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;

class TranslationExportedListener implements ShouldQueue
{
    public function handle(object $event): void
    {
        // Dispatch event to clear translation cache
        cache()->tags(['translations'])->flush();

        // Log export activity
        activity()
            ->withProperties(['timestamp' => now()])
            ->log('Translations exported to files');

        // Notify team via Slack/Discord
        // Notification::route('slack', config('services.slack.webhook'))
        //     ->notify(new TranslationsUpdated());
    }
}
```

Register in `app/Providers/EventServiceProvider.php`:

```php
protected $listen = [
    'Barryvdh\TranslationManager\Events\TranslationsExportedEvent' => [
        TranslationExportedListener::class,
    ],
];
```

---

## Testing

### Example Test Cases

```php
use Barryvdh\TranslationManager\Manager;
use Barryvdh\TranslationManager\Models\Translation;

class TranslationManagerTest extends TestCase
{
    protected Manager $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = app(Manager::class);
    }

    public function test_can_import_langs()
    {
        $count = $this->manager->importTranslations();

        $this->assertGreaterThan(0, $count);
        $this->assertDatabaseHas('ltm_langs', [
            'locale' => 'en',
            'group' => 'messages',
        ]);
    }

    public function test_can_find_langs_in_source()
    {
        $count = $this->manager->findTranslations();

        $this->assertGreaterThan(0, $count);
    }

    public function test_can_add_locale()
    {
        $result = $this->manager->addLocale('fr');

        $this->assertTrue(in_array('fr', $this->manager->getLocales()));
        $this->assertDirectoryExists(resource_path('lang/fr'));
    }

    public function test_can_create_translation()
    {
        Translation::create([
            'locale' => 'en',
            'group' => 'test',
            'key' => 'greeting',
            'value' => 'Hello',
            'status' => Translation::STATUS_SAVED,
        ]);

        $this->assertDatabaseHas('ltm_langs', [
            'key' => 'greeting',
            'value' => 'Hello',
        ]);
    }

    public function test_can_export_langs()
    {
        Translation::create([
            'locale' => 'en',
            'group' => 'test',
            'key' => 'test_key',
            'value' => 'Test Value',
            'status' => Translation::STATUS_CHANGED,
        ]);

        $this->manager->exportTranslations('test');

        $this->assertFileExists(resource_path('lang/en/test.php'));

        $translations = include resource_path('lang/en/test.php');
        $this->assertEquals('Test Value', $translations['test_key']);
    }
}
```

---

## Troubleshooting

### Common Issues

**Issue:** Middleware error - "Session not available"
- **Solution:** Ensure `web` middleware is included in route configuration for Laravel 5.2+

**Issue:** Translations not appearing in web interface
- **Solution:** Run `php artisan translations:import` first to populate database

**Issue:** Export command doesn't update files
- **Solution:** Check file permissions on `resources/lang/` directory (must be writable)

**Issue:** Missing translations not auto-detected
- **Solution:** Verify you've replaced the TranslationServiceProvider in `config/app.php`

**Issue:** Custom connection not working
- **Solution:** Ensure `db_connection` in config matches a defined database connection

---

## Performance Considerations

### Production Recommendations

1. **DO NOT use in production environment** - Package is designed for development workflow
2. **Export before deploying** - Always export translations to files before production deployment
3. **Use file-based translations in production** - Laravel's default file-based system is faster
4. **Cache translations** - Use Laravel's translation caching: `php artisan cache:clear`

### Optimization Tips

1. **Limit excluded groups** - Only exclude what's necessary
2. **Clean regularly** - Run `translations:clean` to remove NULL entries
3. **Index database** - Add indexes on frequently queried columns (locale, group, key)
4. **Batch operations** - Use bulk imports/exports when possible

---

## Summary

The `barryvdh/laravel-translation-manager` package provides:

- Database-backed translation editing interface
- Import/export workflow between files and database
- Source code scanning for translation keys
- Web UI for non-technical translators
- Programmatic API via Manager class
- Artisan commands for automation
- Auto-detection of missing translations

**Best Use Case:** Development environment where translators need a UI to manage translations before exporting to version-controlled language files.

**Not Recommended For:** Production runtime translation management (use file-based or dedicated translation services like Spatie's laravel-translation-loader instead).

---

## References & Sources

- [GitHub Repository](https://github.com/barryvdh/laravel-translation-manager)
- [Packagist Package](https://packagist.org/packages/barryvdh/laravel-translation-manager)
- [Database Schema Migration](https://github.com/barryvdh/laravel-translation-manager/blob/master/database/migrations/2014_04_02_193005_create_langs_table.php)
- [Laravel Translation Documentation](https://laravel.com/docs/11.x/localization)
- [Translation Management Best Practices](https://blog.quickadminpanel.com/10-best-laravel-packages-for-multi-language-translations/)
- [Laravel Localization Guide](https://lokalise.com/blog/laravel-localization-step-by-step/)
