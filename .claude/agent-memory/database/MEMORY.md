# Reviews Module Database Layer

## Status: COMPLETE

Completed full database layer implementation for the Reviews module including migrations, models, factories, and seeders.

## Database Schema

### 8 Tables Created
1. **review_google_connections** - OAuth connections to Google Business Profiles
2. **review_google_locations** - Business locations synced from Google
3. **reviews** - Individual reviews from Google
4. **review_moderations** - Moderation settings for reviews (1:1 with reviews)
5. **review_replies** - Staff replies to reviews
6. **review_reply_templates** - Template library for reply responses
7. **review_saved_filters** - User-saved filter configurations for review searches
8. **review_auto_suggestions** - AI-powered template suggestions based on keywords and ratings

### Key Design Decisions
- **Enums**: `ONE`, `TWO`, `THREE`, `FOUR`, `FIVE` for star ratings (not `ONE_STAR`, etc.)
- **Encryption**: access_token & refresh_token encrypted via Laravel's `encrypted` cast
- **Soft Deletes**: Used on connections, locations, replies, templates for audit trail
- **Activity Logging**: All models use `LogsActivity` trait (requires activity_log table)
- **Foreign Keys**: Cascade delete for hierarchical relationships

### Index Strategy
- Composite indexes on frequently filtered combinations: `(location_id, star_rating)`, `(location_id, review_time)`
- Single indexes on enum/status fields for WHERE queries
- Unique constraints on Google IDs to prevent duplicates

## Models

### ReviewGoogleConnection
- Manages OAuth tokens (encrypted, with refresh logic)
- Status: pending|active|expired|revoked|error
- Methods: `isActive()`, `isExpired()`, `markAsActive()`, `markAsRevoked()`

### ReviewGoogleLocation
- Synced location stats (average_rating, total_reviews)
- Scope: `needingSync()` checks sync_interval_minutes config
- Method: `updateStats()` for bulk stat updates

### Review
- Star ratings via ReviewRating enum with helper methods: `stars()`, `fromInt()`
- Scopes for filtering: `recent()`, `visible()`, `featured()`, `withComment()`, `withGoogleReply()`
- Moderation via hasOne relationship (auto-created if needed)

### ReviewModeration
- 1:1 relationship with Review
- Methods: `toggleVisibility()`, `toggleFeatured()`, `addTag()`, `removeTag()`
- Tags stored as JSON array

### ReviewReply
- Workflow: draft → approved → published (or failed)
- Methods: `markAsApproved()`, `markAsPublished()`, `markAsFailed()`
- Tracks error_count for retry logic

### ReviewReplyTemplate
- 4 categories: positive, negative, neutral, general
- Scopes: `active()`, `byCategory()`, `mostUsed()`
- Method: `render()` for variable substitution with {placeholder} syntax

### ReviewSavedFilter
- Per-user saved filter configurations with JSON storage
- Only one default filter per user (enforced by `setAsDefault()` transaction)
- Methods: `hasFilter()`, `getFilter()`, `applyToQuery()`, `setAsDefault()`
- Scope: `forUser($userId)`, `defaults()`
- Supports complex filtering: star_rating, comments, replies, visibility, date ranges, sorting

### ReviewAutoSuggestion
- AI-powered template suggestions based on trigger keywords and rating ranges
- JSON array of keywords matched case-insensitively against review comments
- Rating ranges: single value ("3") or range ("1-2", "4-5")
- Priority system for determining best match (higher = shown first)
- Methods: `matchesReview()`, `matchesRating()`, `matchesKeywords()`, `getRelevanceScore()`
- Scope: `active()`, `byPriority()`, `forRating()`
- Static: `findBestMatch()` returns highest priority matching suggestion

## Factories

All 8 factories have full state methods:
- **ReviewGoogleConnectionFactory**: pending(), expired(), revoked(), error()
- **ReviewGoogleLocationFactory**: unverified(), inactive(), lowRated(), highRated()
- **ReviewFactory**: oneStar()...fiveStars(), withGoogleReply(), noComment(), positive(), negative()
- **ReviewModerationFactory**: hidden(), featured(), unmoderated(), withTags()
- **ReviewReplyFactory**: draft(), approved(), published(), failed(), positive(), negative()
- **ReviewReplyTemplateFactory**: positive(), negative(), neutral(), inactive(), popular()
- **ReviewSavedFilterFactory**: default(), highRated(), lowRated(), needsReply(), featured(), recentWithComment()
- **ReviewAutoSuggestionFactory**: positive(), negative(), neutral(), highPriority(), lowPriority(), inactive(), forRating(), withKeywords(), withTemplate()

## Seeders

### ReviewsPermissionsSeeder
Creates 11 Spatie permissions organized by feature:
- connections.view|create|delete
- reviews.view|export
- moderate + moderate.featured
- replies.create|approve|publish|delete
- templates.view|create|update|delete
- settings.manage

Assigns to roles: super-settings (all), settings (except settings.manage), manager (limited)

### ReviewsDatabaseSeeder
Main entry point calling ReviewsPermissionsSeeder + seeding 4 default templates:
- "Agradecimiento - Reseña positiva" (category: positive)
- "Disculpa - Reseña negativa" (category: negative)
- "Respuesta neutral" (category: neutral)
- "Respuesta general" (category: general)

Then calls ReviewAutoSuggestionsSeeder which creates 10 auto-suggestion rules.

Uses `updateOrInsert()` with raw SQL to avoid activity_log dependency issues during seeding.

### ReviewAutoSuggestionsSeeder
Seeds 10 template auto-suggestion rules:
- 3 positive (4-5 stars): excelente/increíble, gracias/encantado, recomiendo/profesional
- 4 negative (1-2 stars): malo/terrible, decepcionado/frustrado, lento/demora, rudo/grosero
- 2 neutral (3 stars): normal/regular, bien/correcto
- 1 fallback (4-5 stars, no keywords): catches positive reviews without specific keywords

## Recent Fixes & Updates

1. **ReviewReply Model**: Updated to use `error_message` + `error_count` (not `published_by`)
2. **ReviewReplyTemplate Model**: Changed from `content`/`rating_filter`/`is_default` to `body`/`category`/`is_active`/`usage_count`
3. **All Factories**: Normalized fake data generation - removed `rand()` in favor of `fake()->numberBetween()`
4. **Google IDs**: Standardized format (accounts/*, locations/*, etc.)
5. **Seeder**: Uses DB::table() instead of Model::create() to bypass activity logging during seed

## Testing Notes

- All migrations verified: `php artisan migrate:status` shows all 6 running
- Seeder tested: `php artisan db:seed --class="Modules\\Reviews\\Database\\Seeders\\ReviewsDatabaseSeeder"`
- Models can be instantiated and saved (verified via raw SQL inserts)
- Factories auto-discovery issue: Use explicit namespace when calling in tests

## Common Patterns for Future Use

### Review Filtering
```php
Review::where('location_id', $id)
    ->rating('FOUR') // or FIVE, TWO, etc.
    ->recent(days: 30)
    ->visible()
    ->get()
```

### Reply Management
```php
$reply->markAsApproved($user);  // draft → approved
$reply->markAsPublished();      // approved → published
$reply->markAsFailed("API error"); // any → failed (increments error_count)
```

### Template Rendering
```php
$template = ReviewReplyTemplate::where('category', 'positive')->first();
$rendered = $template->render([
    'reviewer_name' => 'John',
    'location_name' => 'Main Store'
]);
```

### Saved Filters
```php
// Apply saved filter to query
$filter = ReviewSavedFilter::forUser($userId)->where('is_default', true)->first();
$reviews = $filter->applyToQuery(Review::query())->get();

// Set as default (unmarks others for same user)
$filter->setAsDefault();

// Check/get specific filters
if ($filter->hasFilter('star_rating')) {
    $ratings = $filter->getFilter('star_rating');
}
```

### Auto-Suggestion Matching
```php
// Find best matching template for a review
use Modules\Reviews\Models\ReviewAutoSuggestion;

$review = Review::find($id);
$suggestion = ReviewAutoSuggestion::active()
    ->get()
    ->first(fn($s) => $s->matchesReview($review));

if ($suggestion) {
    $template = $suggestion->suggestedTemplate;
    $rendered = $template->render([
        'reviewer_name' => $review->reviewer_name,
        'location_name' => $review->location->name,
    ]);
}

// Test matching logic
$suggestion->matchesRating(5);  // true if rating_range is "5" or "4-5"
$suggestion->matchesKeywords('El servicio fue excelente');  // true if any keyword matches
```
