# Full-Text Search Feature

## Overview

The Page module includes a powerful full-text search feature that provides fast, case-insensitive search capabilities across page titles, content, and descriptions.

## Features

- **Full-Text Search**: Uses MySQL FULLTEXT indexes for fast searching
- **Fallback Support**: Automatically falls back to LIKE search for short queries or if FULLTEXT fails
- **Live Search**: Real-time autocomplete search box with debouncing
- **Highlighted Results**: Search terms are highlighted in results
- **API Endpoints**: RESTful API for search functionality
- **Pagination**: Paginated search results

## Database Setup

### Migration

The search feature requires a FULLTEXT index on the `pages` table. This is automatically created by the migration:

```bash
php artisan migrate
```

### Manual Reindexing

If you need to rebuild the FULLTEXT index:

```bash
# Basic reindex
php artisan page:reindex

# Drop existing index and recreate
php artisan page:reindex --drop

# Repair and optimize table
php artisan page:reindex --repair
```

## Usage

### Model Methods

#### scopeSearchFullText

Search using MySQL FULLTEXT indexes (requires minimum 3 characters):

```php
use Modules\Page\Models\Page;

// Search published pages
$results = Page::published()
    ->searchFullText('laravel framework')
    ->get();
```

#### scopeSearch

Fallback LIKE-based search (works with any length query):

```php
$results = Page::published()
    ->search('la')
    ->get();
```

#### searchPages (Static Method)

Combined search method that automatically chooses the best approach:

```php
// Automatically uses FULLTEXT for terms >= 3 chars
$results = Page::searchPages('laravel')->published()->get();

// Force LIKE search
$results = Page::searchPages('la', false)->published()->get();
```

### API Endpoints

#### Full Search

**GET** `/api/v1/pages/search?q={query}`

Returns paginated search results with excerpts and highlighting.

**Parameters:**
- `q` (required): Search query
- `per_page` (optional): Results per page (default: 10, max: 50)

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "Laravel Framework Guide",
      "slug": "laravel-framework-guide",
      "excerpt": "...comprehensive guide about <mark>Laravel</mark>...",
      "url": "http://example.com/laravel-framework-guide",
      "published_at": "2026-02-08 10:00:00",
      "highlighted_title": "<mark>Laravel</mark> Framework Guide"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 10,
    "total": 1,
    "from": 1,
    "to": 1
  },
  "query": "Laravel"
}
```

#### Quick Search (Autocomplete)

**GET** `/api/v1/pages/search/quick?q={query}`

Returns limited results for autocomplete functionality.

**Parameters:**
- `q` (required): Search query
- `limit` (optional): Max results (default: 5, max: 20)

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "Laravel Framework Guide",
      "slug": "laravel-framework-guide",
      "url": "http://example.com/laravel-framework-guide",
      "highlighted_title": "<mark>Laravel</mark> Framework Guide"
    }
  ],
  "query": "Laravel"
}
```

### Frontend Components

#### Search Box Component

Add the live search component to your views:

```blade
<x-page::search-box
    placeholder="Search pages..."
    :action="route('pages.index')"
    :value="$search ?? ''"
/>
```

**Props:**
- `placeholder`: Input placeholder text
- `action`: Form action URL
- `value`: Initial search value
- `apiEndpoint`: API endpoint for autocomplete (default: `/api/v1/pages/search/quick`)
- `minLength`: Minimum characters for search (default: 2)
- `debounceMs`: Debounce delay in milliseconds (default: 300)

#### Using in Public Index

The public pages index automatically includes the search box:

```blade
// In your view, search is already available
@extends('page::components.layouts.master')

@section('content')
    <!-- Search box is automatically included -->
@endsection
```

### Configuration

Configure search behavior in `config/page.php`:

```php
'search' => [
    'enabled' => true,
    'fulltext' => true, // Use FULLTEXT indexes
    'live_search' => true, // Enable live autocomplete
    'min_length' => 2,
    'debounce_ms' => 300,
    'results_per_page' => 10,
    'quick_search_limit' => 5,
],

'enable_live_search' => true, // Toggle between live search and basic form
```

Environment variables:

```env
PAGE_SEARCH_ENABLED=true
PAGE_SEARCH_FULLTEXT=true
PAGE_SEARCH_LIVE=true
PAGE_ENABLE_LIVE_SEARCH=true
```

## Search Features

### Case-Insensitive

All searches are case-insensitive by default.

```php
// These return the same results
Page::searchPages('Laravel')->get();
Page::searchPages('LARAVEL')->get();
Page::searchPages('laravel')->get();
```

### Partial Matching

FULLTEXT search supports partial word matching using the `*` wildcard (automatically added):

```php
// Finds: Laravel, LaravelPHP, Laravel-Framework, etc.
Page::searchPages('larav')->get();
```

### Boolean Mode

The search uses MySQL's BOOLEAN MODE, which supports:

- `+` - Must include word
- `-` - Must exclude word
- `"phrase"` - Exact phrase
- `*` - Wildcard (added automatically)

Example:
```php
// Find pages with "Laravel" but not "Vue"
Page::searchFullText('+Laravel -Vue')->get();
```

### Relevance Ranking

Results are ordered by relevance when using FULLTEXT search:

```php
$results = Page::published()
    ->searchFullText('Laravel framework')
    ->get();
// Pages with both terms ranked higher than pages with just one term
```

## Performance

### Index Information

Check FULLTEXT index status:

```bash
php artisan page:reindex
```

### Optimization Tips

1. **Use FULLTEXT for longer queries**: Queries with 3+ characters automatically use FULLTEXT
2. **Fallback for short queries**: Queries < 3 characters use LIKE search
3. **Debounce live search**: Default 300ms delay prevents excessive API calls
4. **Limit autocomplete results**: Default 5 results keeps responses fast
5. **Cache results**: Consider caching popular searches

### Query Examples

```php
// Good performance (FULLTEXT)
Page::searchPages('Laravel framework')->get();

// Still works but uses LIKE
Page::searchPages('La')->get();

// Best for autocomplete
Page::searchPages('Larav')
    ->select(['id', 'title', 'slug'])
    ->limit(5)
    ->get();
```

## Troubleshooting

### FULLTEXT Index Not Working

1. Check if index exists:
```bash
php artisan page:reindex
```

2. Verify MySQL version (FULLTEXT requires MySQL 5.6+)

3. Check table engine (must be InnoDB):
```sql
SHOW TABLE STATUS WHERE Name = 'pages';
```

### No Results for Short Queries

FULLTEXT search requires minimum 3 characters. Short queries automatically use LIKE search.

### Search Terms Not Highlighted

Make sure you're using the API endpoints which include the `highlighted_title` field.

## Testing

Run the search tests:

```bash
php artisan test --filter PageSearchTest
```

## Security

- All queries are sanitized and use parameter binding
- Search is limited to published pages only (in public endpoints)
- API endpoints include validation
- XSS protection for highlighted results

## Browser Support

The live search component requires:
- Alpine.js (included via CDN in component)
- Modern browser with JavaScript enabled
- Fetch API support

Falls back to basic form if JavaScript is disabled.
