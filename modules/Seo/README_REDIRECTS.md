# SEO Redirects System

Complete implementation of 301/302 redirects for the Seo module.

## Features

- Full CRUD management for redirects
- Support for 301 (permanent) and 302 (temporary) redirects
- Case-insensitive path matching
- Automatic hits tracking
- Caching for better performance
- Bulk operations (delete, toggle active status)
- Search and filtering capabilities
- Active/Inactive status management

## Database Structure

Table: `seo_redirects`

| Column       | Type           | Description                           |
|--------------|----------------|---------------------------------------|
| id           | bigint         | Primary key                           |
| source_path  | varchar(255)   | The path to redirect from             |
| target_path  | varchar(255)   | The destination path or URL           |
| status_code  | integer        | 301 (permanent) or 302 (temporary)    |
| hits_count   | bigint         | Number of times redirect was used     |
| is_active    | boolean        | Whether redirect is active            |
| created_at   | timestamp      | Creation timestamp                    |
| updated_at   | timestamp      | Last update timestamp                 |

## Usage

### Admin Interface

Access the redirects management interface at:
```
/admin/seo/redirects
```

### Creating Redirects

#### Via Admin Panel
1. Navigate to `/admin/seo/redirects`
2. Click "Add Redirect"
3. Fill in the form:
   - **Source Path**: The old URL path (e.g., `/old-page`)
   - **Target Path**: The new URL path or full URL (e.g., `/new-page` or `https://example.com`)
   - **Status Code**: 301 for permanent, 302 for temporary
   - **Active**: Check to enable the redirect
4. Click "Create Redirect"

#### Via Code
```php
use Modules\Seo\Models\SeoRedirect;

SeoRedirect::create([
    'source_path' => '/old-blog-post',
    'target_path' => '/blog/new-post',
    'status_code' => 301,
    'is_active' => true,
]);
```

### Finding Redirects

```php
// Find by source path (case-insensitive)
$redirect = SeoRedirect::findBySourcePath('/old-page');

// Get all active redirects
$activeRedirects = SeoRedirect::active()->get();

// Get redirects ordered by hits
$popularRedirects = SeoRedirect::byHits()->take(10)->get();

// Search redirects
$results = SeoRedirect::search('blog')->get();

// Filter by status code
$permanent = SeoRedirect::withStatusCode(301)->get();
```

### Middleware

The `RedirectMiddleware` is automatically registered and applied to the `web` middleware group. It:

1. Checks if the current path matches any active redirect
2. Increments the hits counter asynchronously
3. Performs the redirect with the appropriate status code
4. Uses caching for better performance

The middleware automatically skips:
- Admin routes (`/admin/*`)
- API routes (`/api/*`)

### Model Scopes

```php
// Active redirects only
SeoRedirect::active()->get();

// Order by hits (desc by default)
SeoRedirect::byHits()->get();
SeoRedirect::byHits('asc')->get();

// Search in source and target paths
SeoRedirect::search('blog')->get();

// Filter by status code
SeoRedirect::withStatusCode(301)->get();
```

### Helper Methods

```php
$redirect = SeoRedirect::find(1);

// Increment hits
$redirect->incrementHits();

// Check redirect type
$redirect->isPermanent(); // true if 301
$redirect->isTemporary();  // true if 302

// Get status code label
$redirect->status_code_label; // "301 - Permanent"
```

## Best Practices

### 301 vs 302 Redirects

**Use 301 (Permanent) when:**
- A page has been permanently moved or renamed
- You want search engines to transfer ranking signals to the new URL
- The old URL will never be used again

**Use 302 (Temporary) when:**
- A page is temporarily moved
- You're running a temporary campaign or promotion
- You don't want search engines to transfer ranking signals
- The old URL might be used again in the future

### Path Conventions

- Always start paths with `/`
- Paths are automatically normalized to lowercase for case-insensitive matching
- The system automatically adds leading slashes if missing
- External URLs should include the full protocol (e.g., `https://example.com`)

### Performance

- Redirects are cached for 1 hour by default
- Hits are incremented asynchronously to avoid slowing down redirects
- Use the "Clear Cache" button after bulk updates

### Avoiding Redirect Chains

Don't create chains like: A → B → C

Instead, create direct redirects:
- A → C
- B → C

Redirect chains can slow down page loads and hurt SEO.

## Admin Routes

| Route                                    | Method | Description                  |
|------------------------------------------|--------|------------------------------|
| /admin/seo/redirects                     | GET    | List all redirects           |
| /admin/seo/redirects/create              | GET    | Show create form             |
| /admin/seo/redirects                     | POST   | Store new redirect           |
| /admin/seo/redirects/{id}                | GET    | Show redirect details        |
| /admin/seo/redirects/{id}/edit           | GET    | Show edit form               |
| /admin/seo/redirects/{id}                | PUT    | Update redirect              |
| /admin/seo/redirects/{id}                | DELETE | Delete redirect              |
| /admin/seo/redirects/{id}/toggle-active  | PATCH  | Toggle active status         |
| /admin/seo/redirects-bulk-delete         | DELETE | Delete multiple redirects    |
| /admin/seo/redirects-clear-cache         | GET    | Clear all redirect caches    |

## Seeder

The system includes a seeder with 15 example redirects. To run it:

```bash
php artisan db:seed --class=Modules\\Seo\\Database\\Seeders\\SeoRedirectSeeder
```

## Cache Management

Redirects are cached for better performance. The cache is automatically cleared when:
- A redirect is created
- A redirect is updated
- A redirect is deleted
- Active status is toggled

To manually clear all redirect caches:
1. Go to `/admin/seo/redirects`
2. Click "Clear Cache" button

Or via code:
```php
use Illuminate\Support\Facades\Cache;

$redirect = SeoRedirect::find(1);
Cache::forget('seo_redirect_' . md5(strtolower($redirect->source_path)));
```

## Testing

To test if a redirect is working:

1. Create a redirect in the admin panel
2. Visit the source path in your browser
3. You should be redirected to the target path
4. Check the redirect in the admin panel - the hits count should increase

## Troubleshooting

### Redirects not working

1. Check that the redirect is active
2. Clear the redirect cache
3. Make sure the source path matches exactly (remember it's case-insensitive)
4. Check that you're not on an admin or API route
5. Verify the middleware is registered in `SeoServiceProvider`

### Performance issues

1. Make sure caching is enabled
2. Check your queue configuration (hits are incremented asynchronously if queues are configured)
3. Consider adding more indexes if you have thousands of redirects

## Files Created

- **Migration**: `modules/Seo/database/migrations/2026_02_08_000002_create_seo_redirects_table.php`
- **Model**: `modules/Seo/app/Models/SeoRedirect.php`
- **Middleware**: `modules/Seo/app/Http/Middleware/RedirectMiddleware.php`
- **Controller**: `modules/Seo/app/Http/Controllers/SeoRedirectController.php`
- **Requests**:
  - `modules/Seo/app/Http/Requests/StoreSeoRedirectRequest.php`
  - `modules/Seo/app/Http/Requests/UpdateSeoRedirectRequest.php`
- **Views**:
  - `modules/Seo/resources/views/admin/redirects/index.blade.php`
  - `modules/Seo/resources/views/admin/redirects/create.blade.php`
  - `modules/Seo/resources/views/admin/redirects/edit.blade.php`
- **Seeder**: `modules/Seo/database/seeders/SeoRedirectSeeder.php`

## Requirements

- Laravel 10+
- PHP 8.1+
- Database (MySQL/PostgreSQL/SQLite)

## License

This module is part of the Seo package.
