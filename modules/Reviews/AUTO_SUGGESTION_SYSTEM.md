# Review Auto-Suggestion System

## Overview

The Review Auto-Suggestion System automatically suggests appropriate reply templates based on review content and rating. It uses simple keyword matching and rating-based rules to provide relevant template suggestions.

## Database Schema

### Table: `review_auto_suggestions`

| Column                    | Type              | Description                                           |
|---------------------------|-------------------|-------------------------------------------------------|
| id                        | bigint unsigned   | Primary key                                           |
| trigger_keywords          | JSON              | Array of keywords that trigger this suggestion        |
| rating_range              | string(10)        | Rating range (e.g., "1-2", "3", "4-5")               |
| suggested_template_id     | bigint unsigned   | Foreign key to `review_reply_templates`               |
| priority                  | integer unsigned  | Priority for ordering (higher = shown first)          |
| is_active                 | boolean           | Whether this suggestion is active                     |
| created_at                | timestamp         |                                                       |
| updated_at                | timestamp         |                                                       |

### Indexes
- `rating_range`
- `is_active`
- `is_active, priority` (composite)

## Components

### 1. Model: `ReviewAutoSuggestion`

**Location:** `modules/Reviews/app/Models/ReviewAutoSuggestion.php`

**Key Methods:**
- `matchesReview(Review $review): bool` - Check if suggestion matches a review
- `matchesRating(int $rating): bool` - Check if rating is in range
- `matchesKeywords(?string $content): bool` - Check if keywords are in content
- `getRelevanceScore(Review $review): int` - Calculate relevance score

**Scopes:**
- `active()` - Only active suggestions
- `byRatingRange(string $ratingRange)` - Filter by rating range
- `orderedByPriority()` - Order by priority descending

**Relationships:**
- `suggestedTemplate()` - BelongsTo ReviewReplyTemplate

### 2. Service: `ReviewAutoSuggestionService`

**Location:** `modules/Reviews/app/Services/ReviewAutoSuggestionService.php`

**Key Method:**
```php
public function suggestTemplates(Review $review): Collection
```

**Suggestion Algorithm:**

1. **Load Active Suggestions:** Get all active `ReviewAutoSuggestion` records
2. **Filter by Review Match:** Check each suggestion with `matchesReview()`
   - Rating must be in range
   - If keywords defined, at least one must match (case-insensitive)
3. **Calculate Relevance Score:**
   - Base score = priority
   - If keywords match: +100
   - Additional +10 per matched keyword
4. **Sort by Relevance:** Order by relevance_score descending
5. **Remove Duplicates:** Keep unique templates only
6. **Fallback:** If no matches, return category-based templates

**Fallback Logic:**
- Rating 4-5: "positive" or "general" templates
- Rating 1-2: "negative" or "general" templates
- Rating 3: "neutral" or "general" templates
- Ordered by usage_count descending
- Max 5 templates

### 3. API Endpoint

**Route:** `GET /api/reviews/{review}/suggestions`
**Name:** `api.reviews.suggestions`
**Controller:** `ReviewController@suggestions`
**Middleware:** `api`, `auth:sanctum`, `throttle:60,1`

**Authorization:** Requires `reviews.reviews.view` permission

**Response Format:**
```json
{
  "review_id": 123,
  "star_rating": 5,
  "has_comment": true,
  "suggestions": [
    {
      "template_id": 1,
      "template_name": "Thank You Template",
      "template_body": "Thank you {reviewer_name}!",
      "category": "positive",
      "relevance_score": 210,
      "matched_keywords": ["excellent", "great"],
      "usage_count": 42
    }
  ]
}
```

### 4. Policy: `ReviewAutoSuggestionPolicy`

**Location:** `modules/Reviews/app/Policies/ReviewAutoSuggestionPolicy.php`

**Permissions:**
- `viewAny()` - `reviews.templates.view`
- `view()` - `reviews.templates.view`
- `create()` - `reviews.templates.create`
- `update()` - `reviews.templates.update`
- `delete()` - `reviews.templates.delete`

## Usage Examples

### 1. Create Auto-Suggestion via Service

```php
use Modules\Reviews\Services\ReviewAutoSuggestionService;

$service = app(ReviewAutoSuggestionService::class);

$suggestion = $service->createSuggestion([
    'trigger_keywords' => ['excellent', 'great', 'amazing'],
    'rating_range' => '4-5',
    'suggested_template_id' => 1,
    'priority' => 100,
    'is_active' => true,
]);
```

### 2. Get Suggestions for a Review

```php
use Modules\Reviews\Models\Review;
use Modules\Reviews\Services\ReviewAutoSuggestionService;

$review = Review::find(1);
$service = app(ReviewAutoSuggestionService::class);

$suggestions = $service->suggestTemplates($review);

foreach ($suggestions as $suggestion) {
    echo $suggestion['template_name'];
    echo " (Score: {$suggestion['relevance_score']})";
}
```

### 3. API Request Example

```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
     -H "Accept: application/json" \
     https://your-domain.com/api/reviews/123/suggestions
```

## Rating Range Format

The `rating_range` field accepts the following formats:

- **Single rating:** `"1"`, `"2"`, `"3"`, `"4"`, `"5"`
- **Range:** `"1-2"` (negative), `"4-5"` (positive)
- **Common patterns:**
  - Negative: `"1-2"`
  - Neutral: `"3"`
  - Positive: `"4-5"`

## Keyword Matching

- **Case-insensitive:** Keywords are matched without case sensitivity
- **Partial matching:** Uses `str_contains()`, so "excelente" matches "muy excelente servicio"
- **OR logic:** If ANY keyword matches, the suggestion qualifies
- **Empty keywords:** If `trigger_keywords` is empty, suggestion matches all reviews in the rating range

## Seeder Example

A seeder is provided at `modules/Reviews/database/seeders/ReviewAutoSuggestionSeeder.php` with example data:

```bash
php artisan db:seed --class="Modules\Reviews\Database\Seeders\ReviewAutoSuggestionSeeder"
```

This creates:
- 5 reply templates (positive_service, positive_quality, negative_service, negative_quality, neutral)
- 8 auto-suggestions covering different scenarios

## Factory

The `ReviewAutoSuggestionFactory` provides convenient states:

```php
use Modules\Reviews\Models\ReviewAutoSuggestion;

// Create positive suggestion
ReviewAutoSuggestion::factory()->positive()->create();

// Create negative suggestion with high priority
ReviewAutoSuggestion::factory()->negative()->highPriority()->create();

// Create inactive neutral suggestion
ReviewAutoSuggestion::factory()->neutral()->inactive()->create();

// Create with custom keywords
ReviewAutoSuggestion::factory()
    ->withKeywords(['custom', 'keywords'])
    ->forRating('4-5')
    ->create();
```

## Best Practices

1. **Priority Management:**
   - Use high priority (80-100) for very specific keyword matches
   - Use medium priority (40-80) for general category matches
   - Use low priority (0-40) for fallback suggestions

2. **Keyword Strategy:**
   - Start with 3-5 most common keywords per suggestion
   - Use language-specific variations (e.g., "excelente", "excellent")
   - Avoid very common words that appear in many reviews

3. **Template Association:**
   - Link multiple suggestions to the same template if they target the same scenario
   - Keep template categories aligned with rating ranges (positive templates for 4-5 ratings)

4. **Fallback Coverage:**
   - Always have at least one suggestion with empty keywords for each rating range
   - Set these fallback suggestions to low priority

5. **Testing Relevance:**
   - Review the `relevance_score` to ensure correct ordering
   - Adjust priorities if wrong templates appear first

## Extending the System

### Add New Sentiment Analysis

To add more sophisticated sentiment analysis:

1. Update `ReviewAutoSuggestion::matchesKeywords()` to call a sentiment service
2. Modify `ReviewAutoSuggestionService::suggestTemplates()` to use sentiment scores
3. Add sentiment score to relevance calculation

### Add Machine Learning

To integrate ML-based suggestions:

1. Create a new service `ReviewMLSuggestionService`
2. Train model on historical review-reply pairs
3. Modify `suggestTemplates()` to combine rule-based + ML suggestions
4. Blend scores: `relevance_score = (rule_score * 0.6) + (ml_score * 0.4)`

## Troubleshooting

### No Suggestions Returned

1. Check that suggestions exist: `ReviewAutoSuggestion::active()->count()`
2. Verify rating range format is correct
3. Check if review has a comment (required for keyword matching)
4. Verify suggested template is active

### Wrong Templates Suggested

1. Review priority values - higher priority suggestions appear first
2. Check keyword matches with `matchesKeywords()` method
3. Verify rating_range matches review star_rating
4. Inspect relevance_score calculation

### Performance Issues

1. Ensure indexes exist on `is_active` and `priority` columns
2. Consider caching suggestions for frequently accessed reviews
3. Limit number of active suggestions (recommended: max 50)

## Related Files

- Model: `modules/Reviews/app/Models/ReviewAutoSuggestion.php`
- Service: `modules/Reviews/app/Services/ReviewAutoSuggestionService.php`
- Controller: `modules/Reviews/app/Http/Controllers/Api/ReviewController.php`
- Migration: `modules/Reviews/database/migrations/2026_02_22_232444_create_review_auto_suggestions_table.php`
- Factory: `modules/Reviews/database/factories/ReviewAutoSuggestionFactory.php`
- Seeder: `modules/Reviews/database/seeders/ReviewAutoSuggestionSeeder.php`
- Policy: `modules/Reviews/app/Policies/ReviewAutoSuggestionPolicy.php`
- Tests: `modules/Reviews/tests/Feature/ReviewAutoSuggestionTest.php`
