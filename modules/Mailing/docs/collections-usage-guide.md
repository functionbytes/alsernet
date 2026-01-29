# Collections Usage Guide - Mailing Module

This guide explains how to use custom Eloquent Collections in the Mailing module for cleaner, more maintainable code.

---

## Table of Contents

1. [What Are Custom Collections?](#what-are-custom-collections)
2. [Why Use Custom Collections?](#why-use-custom-collections)
3. [Setting Up a Custom Collection](#setting-up-a-custom-collection)
4. [Available Example Collections](#available-example-collections)
5. [Usage Examples](#usage-examples)
6. [Best Practices](#best-practices)
7. [Testing Collections](#testing-collections)
8. [Performance Considerations](#performance-considerations)

---

## What Are Custom Collections?

Custom Collections extend Laravel's `Illuminate\Database\Eloquent\Collection` class to provide domain-specific methods for working with groups of models.

Instead of writing repetitive filtering/aggregation logic everywhere, you encapsulate it once in the collection class.

### Standard Approach (Repetitive)

```php
// Controller A
$activeCampaigns = $campaigns->filter(fn($c) => $c->status === 'active');
$totalSent = $activeCampaigns->sum('emails_sent_count');

// Controller B
$activeCampaigns = $campaigns->filter(fn($c) => $c->status === 'active');
$totalSent = $activeCampaigns->sum('emails_sent_count');

// Repeated in multiple places...
```

### Custom Collection Approach (Reusable)

```php
// Anywhere in your code
$activeCampaigns = $campaigns->active();
$totalSent = $activeCampaigns->totalSent();
```

---

## Why Use Custom Collections?

### Benefits

1. **Code Reusability** - Write once, use everywhere
2. **Readability** - `$campaigns->active()` vs `$campaigns->filter(fn($c) => $c->status === 'active')`
3. **Maintainability** - Change logic in one place
4. **Type Safety** - IDE autocomplete and type hints
5. **Testing** - Easy to unit test collection methods
6. **Business Logic Encapsulation** - Domain logic stays with domain objects

### Example: Before and After

**Before (Scattered Logic):**
```php
// In CampaignController
$stats = [
    'total_sent' => $campaigns->sum('emails_sent_count'),
    'avg_open_rate' => ($campaigns->sum('total_opens') / $campaigns->sum('emails_sent_count')) * 100,
];

// In DashboardController
$totalSent = $campaigns->sum('emails_sent_count');
$totalOpens = $campaigns->sum('total_opens');
$avgOpenRate = ($totalOpens / $totalSent) * 100;

// In ReportController
$avgRate = ($campaigns->sum('total_opens') / $campaigns->sum('emails_sent_count')) * 100;
```

**After (Centralized Logic):**
```php
// Everywhere
$stats = [
    'total_sent' => $campaigns->totalSent(),
    'avg_open_rate' => $campaigns->averageOpenRate(),
];
```

---

## Setting Up a Custom Collection

### Step 1: Create the Collection Class

Choose one of the example templates in `modules/Mailing/app/Collections/` and rename it:

```bash
# Rename example to actual collection
mv modules/Mailing/app/Collections/CampaignCollection.example.php \
   modules/Mailing/app/Collections/CampaignCollection.php
```

Or create from scratch:

```php
<?php

namespace Modules\Mailing\Collections;

use Illuminate\Database\Eloquent\Collection;

class CampaignCollection extends Collection
{
    public function active(): static
    {
        return $this->filter(fn($campaign) => $campaign->status === 'active');
    }

    public function totalSent(): int
    {
        return $this->sum('emails_sent_count') ?? 0;
    }
}
```

### Step 2: Update the Model

Tell your model to use the custom collection:

```php
<?php

namespace Modules\Mailing\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Mailing\Collections\CampaignCollection;

class Campaign extends Model
{
    /**
     * Create a new Eloquent Collection instance.
     *
     * @param  array  $models
     * @return \Modules\Mailing\Collections\CampaignCollection
     */
    public function newCollection(array $models = []): CampaignCollection
    {
        return new CampaignCollection($models);
    }
}
```

### Step 3: Use the Collection

Now all queries return your custom collection:

```php
$campaigns = Campaign::all(); // Returns CampaignCollection
$active = $campaigns->active(); // Use custom method
$total = $active->totalSent(); // Chain methods
```

---

## Available Example Collections

The following example collections are available in `modules/Mailing/app/Collections/`:

### 1. CampaignCollection.example.php

**Methods:**
- `byStatus(string $status)` - Filter by status
- `active()` - Get active campaigns
- `paused()` - Get paused campaigns
- `completed()` - Get completed campaigns
- `draft()` - Get draft campaigns
- `totalSent()` - Total emails sent
- `totalClicks()` - Total clicks
- `totalOpens()` - Total opens
- `averageOpenRate()` - Calculate avg open rate
- `averageClickRate()` - Calculate avg click rate
- `scheduled()` - Future scheduled campaigns
- `betweenDates($start, $end)` - Date range filter
- `highEngagement()` - >50% open rate
- `lowEngagement()` - <10% open rate
- `byPerformance()` - Sort by open rate

### 2. SubscriberCollection.example.php

**Methods:**
- `byStatus(string $status)` - Filter by status
- `subscribed()` - Get subscribed only
- `unsubscribed()` - Get unsubscribed
- `bounced()` - Get bounced contacts
- `spamReported()` - Get spam reports
- `bySegment($segmentId)` - Filter by segment
- `byMailList($listId)` - Filter by list
- `engaged(int $days)` - Recently engaged
- `inactive(int $days)` - Inactive subscribers
- `verified()` - Email verified
- `unverified()` - Email not verified
- `withTag(string $tag)` - Filter by tag
- `uniqueEmails()` - Get unique emails
- `byDomain(string $domain)` - Filter by email domain
- `groupedByDomain()` - Group by domain
- `engagementRate()` - Calculate engagement %
- `recentlyJoined(int $days)` - New subscribers
- `vip()` - High engagement subscribers

### 3. MailListCollection.example.php

**Methods:**
- `active()` - Active lists only
- `inactive()` - Inactive lists
- `totalSubscribers()` - Sum all subscribers
- `totalSubscribed()` - Sum active subscribers
- `totalUnsubscribed()` - Sum unsubscribed
- `averageEngagement()` - Avg engagement rate
- `byCategory(string $category)` - Filter by category
- `groupedByCategory()` - Group by category
- `withMinimumSubscribers(int $threshold)` - Min size
- `large()` - Lists >10k subscribers
- `small()` - Lists <1k subscribers
- `sortedBySize(string $direction)` - Sort by size
- `sortedByEngagement(string $direction)` - Sort by engagement
- `highChurn()` - >20% unsubscribe rate
- `createdBetween($start, $end)` - Date range
- `recentlyCreated(int $days)` - Recent lists
- `byOwner($userId)` - Filter by owner
- `growthRate()` - Calculate growth %

---

## Usage Examples

### Campaign Analytics Dashboard

```php
<?php

namespace Modules\Mailing\Http\Controllers;

use Modules\Mailing\Models\Campaign;

class DashboardController extends Controller
{
    public function index()
    {
        $campaigns = Campaign::all();

        return view('mailing::dashboard', [
            'total_campaigns' => $campaigns->count(),
            'active_campaigns' => $campaigns->active()->count(),
            'completed_campaigns' => $campaigns->completed()->count(),
            'total_sent' => $campaigns->totalSent(),
            'total_opens' => $campaigns->totalOpens(),
            'total_clicks' => $campaigns->totalClicks(),
            'avg_open_rate' => $campaigns->averageOpenRate(),
            'avg_click_rate' => $campaigns->averageClickRate(),
            'high_performers' => $campaigns->highEngagement()->count(),
            'low_performers' => $campaigns->lowEngagement()->count(),
            'scheduled' => $campaigns->scheduled()->count(),
        ]);
    }
}
```

### Subscriber Segmentation

```php
<?php

namespace Modules\Mailing\Services;

use Modules\Mailing\Models\Subscriber;

class SegmentationService
{
    public function getSegmentStats(int $listId)
    {
        $subscribers = Subscriber::where('mail_list_id', $listId)->get();

        return [
            'total' => $subscribers->count(),
            'subscribed' => $subscribers->subscribed()->count(),
            'unsubscribed' => $subscribers->unsubscribed()->count(),
            'bounced' => $subscribers->bounced()->count(),
            'verified' => $subscribers->verified()->count(),
            'engaged_30d' => $subscribers->engaged(30)->count(),
            'inactive_90d' => $subscribers->inactive(90)->count(),
            'engagement_rate' => $subscribers->engagementRate(),
            'vip_count' => $subscribers->vip()->count(),
            'recent_joins' => $subscribers->recentlyJoined(7)->count(),
            'domains' => $subscribers->groupedByDomain()->map->count(),
        ];
    }

    public function getInactiveSubscribers(int $days = 90)
    {
        return Subscriber::all()
            ->subscribed()
            ->inactive($days)
            ->sortBy('last_open_at');
    }
}
```

### Mail List Performance Report

```php
<?php

namespace Modules\Mailing\Http\Controllers;

use Modules\Mailing\Models\MailList;

class ReportController extends Controller
{
    public function listPerformance()
    {
        $lists = MailList::all();

        return view('mailing::reports.lists', [
            'total_subscribers' => $lists->totalSubscribers(),
            'total_subscribed' => $lists->totalSubscribed(),
            'total_unsubscribed' => $lists->totalUnsubscribed(),
            'avg_engagement' => $lists->averageEngagement(),
            'growth_rate' => $lists->growthRate(),
            'large_lists' => $lists->large()->count(),
            'small_lists' => $lists->small()->count(),
            'high_churn_lists' => $lists->highChurn(),
            'top_lists' => $lists->sortedBySize('desc')->take(10),
            'best_engagement' => $lists->sortedByEngagement('desc')->take(10),
            'recent_lists' => $lists->recentlyCreated(30),
        ]);
    }
}
```

### Chaining Methods

```php
// Get total sent for active campaigns this month
$totalSent = Campaign::all()
    ->active()
    ->betweenDates(now()->startOfMonth(), now())
    ->totalSent();

// Get VIP subscribers who opened emails in the last 7 days
$vipEngaged = Subscriber::all()
    ->subscribed()
    ->vip()
    ->engaged(7);

// Get large active lists sorted by engagement
$topLists = MailList::all()
    ->active()
    ->large()
    ->sortedByEngagement('desc')
    ->take(5);
```

---

## Best Practices

### 1. Return Types Matter

Always return `static` for chainable methods:

```php
// ✅ Good - Chainable
public function active(): static
{
    return $this->filter(fn($c) => $c->status === 'active');
}

// ❌ Bad - Not chainable
public function active(): void
{
    $this->filter(fn($c) => $c->status === 'active');
}
```

### 2. Use Type Hints

```php
// ✅ Good
public function totalSent(): int
{
    return $this->sum('emails_sent_count') ?? 0;
}

// ❌ Bad
public function totalSent()
{
    return $this->sum('emails_sent_count');
}
```

### 3. Handle Edge Cases

```php
// ✅ Good - Handles zero division
public function averageOpenRate(): float
{
    $totalSent = $this->totalSent();

    if ($totalSent === 0) {
        return 0.0;
    }

    return round(($this->totalOpens() / $totalSent) * 100, 2);
}

// ❌ Bad - Can cause division by zero
public function averageOpenRate(): float
{
    return ($this->totalOpens() / $this->totalSent()) * 100;
}
```

### 4. Keep Methods Focused

```php
// ✅ Good - Single responsibility
public function active(): static
{
    return $this->byStatus('active');
}

public function totalSent(): int
{
    return $this->sum('emails_sent_count') ?? 0;
}

// ❌ Bad - Too much responsibility
public function getActiveStats(): array
{
    $active = $this->filter(fn($c) => $c->status === 'active');
    return [
        'count' => $active->count(),
        'total_sent' => $active->sum('emails_sent_count'),
        'avg_open_rate' => /* complex calculation */
    ];
}
```

### 5. Avoid Side Effects

```php
// ✅ Good - No side effects
public function active(): static
{
    return $this->filter(fn($c) => $c->status === 'active');
}

// ❌ Bad - Modifies database
public function activateAll(): void
{
    $this->each(function ($campaign) {
        $campaign->update(['status' => 'active']);
    });
}
```

---

## Testing Collections

Create unit tests in `modules/Mailing/Tests/Unit/Collections/`:

```php
<?php

namespace Modules\Mailing\Tests\Unit\Collections;

use Tests\TestCase;
use Modules\Mailing\Models\Campaign;
use Modules\Mailing\Collections\CampaignCollection;

class CampaignCollectionTest extends TestCase
{
    public function test_active_filters_active_campaigns(): void
    {
        $campaigns = new CampaignCollection([
            Campaign::factory()->make(['status' => 'active']),
            Campaign::factory()->make(['status' => 'paused']),
            Campaign::factory()->make(['status' => 'active']),
        ]);

        $active = $campaigns->active();

        $this->assertCount(2, $active);
        $this->assertTrue($active->every(fn($c) => $c->status === 'active'));
    }

    public function test_total_sent_calculates_sum(): void
    {
        $campaigns = new CampaignCollection([
            Campaign::factory()->make(['emails_sent_count' => 100]),
            Campaign::factory()->make(['emails_sent_count' => 250]),
            Campaign::factory()->make(['emails_sent_count' => 150]),
        ]);

        $this->assertEquals(500, $campaigns->totalSent());
    }

    public function test_average_open_rate_handles_zero_sent(): void
    {
        $campaigns = new CampaignCollection([
            Campaign::factory()->make([
                'emails_sent_count' => 0,
                'total_opens' => 0,
            ]),
        ]);

        $this->assertEquals(0.0, $campaigns->averageOpenRate());
    }

    public function test_average_open_rate_calculates_correctly(): void
    {
        $campaigns = new CampaignCollection([
            Campaign::factory()->make([
                'emails_sent_count' => 100,
                'total_opens' => 50,
            ]),
            Campaign::factory()->make([
                'emails_sent_count' => 200,
                'total_opens' => 100,
            ]),
        ]);

        // Total: 150 opens / 300 sent = 50%
        $this->assertEquals(50.0, $campaigns->averageOpenRate());
    }
}
```

Run tests:

```bash
php artisan test --filter=CampaignCollectionTest
```

---

## Performance Considerations

### 1. Use Eager Loading

```php
// ✅ Good - Eager load relationships
$campaigns = Campaign::with('mailList', 'user')->get();
$active = $campaigns->active();

// ❌ Bad - N+1 queries when accessing relationships
$campaigns = Campaign::all();
$active = $campaigns->active();
// Later: $campaign->mailList (triggers query for each)
```

### 2. Filter at Database Level When Possible

```php
// ✅ Good - Filter in database
$active = Campaign::where('status', 'active')->get();

// ❌ Bad - Load all, then filter
$active = Campaign::all()->active();
```

**When to use collections:**
- When you already have a collection loaded
- When combining multiple filters
- When the filter logic is complex

**When to use query builder:**
- When filtering large datasets
- When pagination is needed
- When the filter is simple and database-indexable

### 3. Be Mindful of Large Collections

```php
// ✅ Good - Chunk large datasets
Campaign::chunk(1000, function ($campaigns) {
    $totalSent = $campaigns->totalSent();
    // Process chunk
});

// ❌ Bad - Load everything into memory
$campaigns = Campaign::all(); // Could be millions of records
$totalSent = $campaigns->totalSent();
```

---

## Summary

Custom Collections are powerful tools for:
- Encapsulating business logic
- Improving code readability
- Reducing duplication
- Making code more testable

Use the example templates in `modules/Mailing/app/Collections/` as starting points, and customize them based on your specific needs after migrating from Acelle.

For migration instructions, see `COLLECTIONS_MIGRATION_REPORT.md`.
