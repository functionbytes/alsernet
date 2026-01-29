# Collections Quick Reference - Mailing Module

Fast reference guide for using custom collections in the Mailing module.

---

## Setup Checklist

- [ ] Rename example collection file (remove `.example.php` extension)
- [ ] Update model's `newCollection()` method
- [ ] Rename test file (remove `.example.php` extension)
- [ ] Run tests to verify functionality
- [ ] Update documentation if adding custom methods

---

## Quick Setup

### 1. Activate a Collection (3 steps)

```bash
# Step 1: Rename example file
mv modules/Mailing/app/Collections/CampaignCollection.example.php \
   modules/Mailing/app/Collections/CampaignCollection.php

# Step 2: Rename test file
mv modules/Mailing/Tests/Unit/Collections/CampaignCollectionTest.example.php \
   modules/Mailing/Tests/Unit/Collections/CampaignCollectionTest.php

# Step 3: Run tests
php artisan test --filter=CampaignCollectionTest
```

### 2. Update Model

```php
<?php

namespace Modules\Mailing\Models;

use Modules\Mailing\Collections\CampaignCollection;

class Campaign extends Model
{
    public function newCollection(array $models = []): CampaignCollection
    {
        return new CampaignCollection($models);
    }
}
```

---

## Available Collections & Methods

### CampaignCollection

**Filters:**
```php
$campaigns->active()           // Status = active
$campaigns->paused()           // Status = paused
$campaigns->completed()        // Status = completed
$campaigns->draft()            // Status = draft
$campaigns->byStatus('active') // Custom status
$campaigns->scheduled()        // Future scheduled
$campaigns->betweenDates($start, $end)
```

**Metrics:**
```php
$campaigns->totalSent()         // Sum emails sent
$campaigns->totalClicks()       // Sum total clicks
$campaigns->totalOpens()        // Sum total opens
$campaigns->averageOpenRate()   // Avg open rate %
$campaigns->averageClickRate()  // Avg click rate %
```

**Analysis:**
```php
$campaigns->highEngagement()    // >50% open rate
$campaigns->lowEngagement()     // <10% open rate
$campaigns->byPerformance()     // Sort by open rate desc
$campaigns->groupedByStatus()   // Group by status
```

---

### SubscriberCollection

**Filters:**
```php
$subscribers->subscribed()         // Status = subscribed
$subscribers->unsubscribed()       // Status = unsubscribed
$subscribers->bounced()            // Status = bounced
$subscribers->spamReported()       // Status = spam_reported
$subscribers->byStatus('subscribed')
$subscribers->bySegment($id)       // Filter by segment
$subscribers->byMailList($id)      // Filter by list
$subscribers->byDomain('gmail.com')
$subscribers->withTag('vip')
```

**Engagement:**
```php
$subscribers->engaged(30)          // Engaged in last 30 days
$subscribers->inactive(90)         // Inactive for 90 days
$subscribers->vip()                // High engagement
$subscribers->recentlyJoined(7)    // Joined in last 7 days
$subscribers->engagementRate()     // Calculate engagement %
```

**Verification:**
```php
$subscribers->verified()           // Email verified
$subscribers->unverified()         // Email not verified
```

**Grouping:**
```php
$subscribers->uniqueEmails()       // Unique email list
$subscribers->groupedByDomain()    // Group by domain
```

---

### MailListCollection

**Filters:**
```php
$lists->active()                   // Active lists
$lists->inactive()                 // Inactive lists
$lists->large()                    // >10k subscribers
$lists->small()                    // <1k subscribers
$lists->withMinimumSubscribers(500)
$lists->byCategory('newsletter')
$lists->byOwner($userId)
$lists->recentlyCreated(30)
$lists->createdBetween($start, $end)
```

**Metrics:**
```php
$lists->totalSubscribers()         // Sum all subscribers
$lists->totalSubscribed()          // Sum active subscribers
$lists->totalUnsubscribed()        // Sum unsubscribed
$lists->averageEngagement()        // Avg engagement %
$lists->growthRate()               // Growth rate %
```

**Analysis:**
```php
$lists->highChurn()                // >20% unsubscribe rate
$lists->sortedBySize('desc')       // Sort by subscriber count
$lists->sortedByEngagement('desc') // Sort by engagement
$lists->groupedByCategory()        // Group by category
```

---

## Common Usage Patterns

### Dashboard Stats

```php
$campaigns = Campaign::all();

return [
    'total' => $campaigns->count(),
    'active' => $campaigns->active()->count(),
    'total_sent' => $campaigns->totalSent(),
    'avg_open_rate' => $campaigns->averageOpenRate(),
];
```

### Segmentation

```php
$subscribers = Subscriber::where('mail_list_id', $listId)->get();

return [
    'subscribed' => $subscribers->subscribed()->count(),
    'engaged' => $subscribers->engaged(30)->count(),
    'inactive' => $subscribers->inactive(90)->count(),
    'vip' => $subscribers->vip()->count(),
];
```

### Performance Report

```php
$lists = MailList::all();

return [
    'total_subscribers' => $lists->totalSubscribers(),
    'avg_engagement' => $lists->averageEngagement(),
    'top_lists' => $lists->sortedBySize('desc')->take(10),
    'high_churn' => $lists->highChurn(),
];
```

### Method Chaining

```php
// Active campaigns with high engagement this month
$result = Campaign::all()
    ->active()
    ->betweenDates(now()->startOfMonth())
    ->highEngagement()
    ->totalSent();

// VIP subscribers from specific list
$vips = Subscriber::all()
    ->byMailList($listId)
    ->subscribed()
    ->vip();

// Top performing active lists
$topLists = MailList::all()
    ->active()
    ->large()
    ->sortedByEngagement('desc')
    ->take(5);
```

---

## Adding Custom Methods

### Create New Method

```php
/**
 * Get campaigns with custom filter
 *
 * @return static
 */
public function myCustomFilter(): static
{
    return $this->filter(function ($campaign) {
        // Your custom logic
        return $campaign->custom_field === 'value';
    });
}
```

### Add Corresponding Test

```php
public function test_my_custom_filter(): void
{
    $campaigns = new CampaignCollection([
        Campaign::factory()->make(['custom_field' => 'value']),
        Campaign::factory()->make(['custom_field' => 'other']),
    ]);

    $filtered = $campaigns->myCustomFilter();

    $this->assertCount(1, $filtered);
}
```

---

## Performance Tips

### ✅ Good Practices

```php
// Use database filtering for large datasets
$active = Campaign::where('status', 'active')->get();

// Use collection methods for already-loaded data
$stats = $campaigns->active()->totalSent();

// Chain efficiently
$result = $campaigns->active()->highEngagement()->count();
```

### ❌ Avoid

```php
// Don't load all records then filter
$active = Campaign::all()->active(); // Loads everything!

// Don't repeat database queries
foreach ($campaigns as $campaign) {
    $campaign->mailList; // N+1 problem
}
```

### Eager Loading

```php
// Always eager load relationships
$campaigns = Campaign::with('mailList', 'user')->get();
$active = $campaigns->active();
```

---

## Testing

### Run All Collection Tests

```bash
php artisan test modules/Mailing/Tests/Unit/Collections
```

### Run Specific Collection Test

```bash
php artisan test --filter=CampaignCollectionTest
```

### Run Single Test Method

```bash
php artisan test --filter=test_active_filters_active_campaigns
```

---

## Troubleshooting

### Collection Not Returning Custom Instance

**Problem:** `Campaign::all()` returns `Collection` instead of `CampaignCollection`

**Solution:** Ensure model has `newCollection()` method:

```php
public function newCollection(array $models = []): CampaignCollection
{
    return new CampaignCollection($models);
}
```

### Method Not Found

**Problem:** `Call to undefined method active()`

**Solution:**
1. Check collection class exists and is properly named
2. Verify namespace is correct: `Modules\Mailing\Collections`
3. Ensure model's `newCollection()` returns correct type

### Type Hint Errors

**Problem:** IDE doesn't recognize custom methods

**Solution:** Add PHPDoc to model:

```php
/**
 * @method static CampaignCollection all()
 * @method static CampaignCollection get()
 */
class Campaign extends Model
```

---

## File Locations

```
modules/Mailing/
├── app/Collections/
│   ├── README.md
│   ├── CampaignCollection.example.php
│   ├── SubscriberCollection.example.php
│   └── MailListCollection.example.php
├── Tests/Unit/Collections/
│   └── CampaignCollectionTest.example.php
└── docs/
    ├── COLLECTIONS_MIGRATION_REPORT.md
    ├── COLLECTIONS_QUICK_REFERENCE.md (this file)
    └── collections-usage-guide.md
```

---

## Next Steps

1. **Verify Acelle has custom collections** (see COLLECTIONS_MIGRATION_REPORT.md)
2. **Activate example collections** if no Acelle collections exist
3. **Migrate Acelle collections** if they exist
4. **Run tests** to ensure everything works
5. **Update models** to use collections
6. **Refactor controllers/services** to use collection methods

---

## Resources

- Full guide: `docs/collections-usage-guide.md`
- Migration report: `docs/COLLECTIONS_MIGRATION_REPORT.md`
- Laravel docs: https://laravel.com/docs/12.x/eloquent-collections#custom-collections
- Example tests: `Tests/Unit/Collections/CampaignCollectionTest.example.php`
