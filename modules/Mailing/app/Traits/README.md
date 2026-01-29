# Scope Traits - Mailing Module

This directory contains **Scope Traits** that provide reusable local scopes for models.

---

## What are Local Scopes?

Local scopes are methods you call explicitly on queries. They're defined as methods prefixed with `scope`.

**Example**:
```php
// Trait defines:
public function scopeActive($query) {
    return $query->where('status', 'active');
}

// Use in queries:
Model::active()->get();
```

---

## Available Scope Traits

### 1. HasCommonScopes (17 scopes)
**Generic scopes for most models**

```php
use Modules\Mailing\Traits\HasCommonScopes;

class MyModel extends Model {
    use HasCommonScopes;
}

// Available scopes:
->byStatus('active')
->active() / ->inactive()
->recent(30)
->ownedBy($userId)
->search('term')
->verified() / ->unverified()
// ... and more
```

---

### 2. HasCampaignScopes (19 scopes)
**Campaign lifecycle and filtering**

```php
use Modules\Mailing\Traits\HasCampaignScopes;

class Campaign extends Model {
    use HasCampaignScopes;
}

// Available scopes:
->draft() / ->scheduled() / ->sent()
->readyToSend()
->highPerforming(30.0)
->forList($listId)
// ... and more
```

---

### 3. HasSubscriberScopes (20 scopes)
**Subscriber management**

```php
use Modules\Mailing\Traits\HasSubscriberScopes;

class Subscriber extends Model {
    use HasSubscriberScopes;
}

// Available scopes:
->subscribed() / ->unsubscribed()
->sendable()
->inGroup($groupId)
->needsSyncing()
// ... and more
```

---

### 4. HasMailingServerScopes (14 scopes)
**Sending server health and quotas**

```php
use Modules\Mailing\Traits\HasMailingServerScopes;

class SendingServer extends Model {
    use HasMailingServerScopes;
}

// Available scopes:
->availableToSend()
->quotaExceeded()
->highBounceRate(5.0)
->needsConnectionCheck(24)
// ... and more
```

---

### 5. HasTemplateScopes (11 scopes)
**Template and layout management**

```php
use Modules\Mailing\Traits\HasTemplateScopes;

class Template extends Model {
    use HasTemplateScopes;
}

// Available scopes:
->active()
->system() / ->userCreated()
->byCategory('newsletter')
->featured()
// ... and more
```

---

### 6. HasLogScopes (18 scopes)
**Log filtering and analytics**

```php
use Modules\Mailing\Traits\HasLogScopes;

class BounceLog extends Model {
    use HasLogScopes;
}

// Available scopes:
->today() / ->thisWeek() / ->thisMonth()
->hardBounces() / ->softBounces()
->processed() / ->unprocessed()
->latest() / ->oldest()
// ... and more
```

---

## How to Use Scope Traits

### Step 1: Add Trait to Model
```php
<?php

namespace Modules\Mailing\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Mailing\Traits\HasCommonScopes;
use Modules\Mailing\Traits\HasCampaignScopes;

class Campaign extends Model
{
    use HasCommonScopes, HasCampaignScopes;
}
```

### Step 2: Use Scopes in Queries
```php
// Single scope
$drafts = Campaign::draft()->get();

// Chain multiple scopes
$recent = Campaign::draft()
    ->recent(30)
    ->ownedBy(auth()->id())
    ->get();

// Mix with Eloquent methods
$paginated = Campaign::sent()
    ->withAnalytics()
    ->with('mailingList')
    ->orderBy('sent_at', 'desc')
    ->paginate(20);
```

---

## Combining Multiple Traits

You can use multiple traits on one model:

```php
class Campaign extends Model
{
    use HasCommonScopes;    // Generic: search, recent, active, etc.
    use HasCampaignScopes;  // Specific: draft, scheduled, sent, etc.
}

// Now you have access to all scopes:
Campaign::draft()           // From HasCampaignScopes
    ->recent(7)             // From HasCommonScopes
    ->search('Newsletter')  // From HasCommonScopes
    ->get();
```

---

## Creating Custom Scope Traits

1. Create new trait file:
```php
<?php

namespace Modules\Mailing\Traits;

use Illuminate\Database\Eloquent\Builder;

trait HasMyCustomScopes
{
    /**
     * Scope description
     */
    public function scopeMyScope(Builder $query, $param): Builder
    {
        return $query->where('column', $param);
    }

    /**
     * Another scope
     */
    public function scopeAnotherScope(Builder $query): Builder
    {
        return $query->where('active', true);
    }
}
```

2. Add to model:
```php
use Modules\Mailing\Traits\HasMyCustomScopes;

class MyModel extends Model
{
    use HasMyCustomScopes;
}
```

3. Use in queries:
```php
MyModel::myScope('value')->anotherScope()->get();
```

---

## Scope Naming Conventions

✅ **Good Names**:
- `scopeActive()` - Clear, concise
- `scopeByStatus($status)` - Descriptive with parameter
- `scopeNeedsSyncing()` - Action-oriented
- `scopeHighPerforming($threshold)` - Meaningful

❌ **Bad Names**:
- `scopeData()` - Too vague
- `scopeGet()` - Conflicts with Eloquent
- `scopeX()` - Not descriptive
- `scopeFetchActiveRecords()` - Too long

---

## Performance Tips

```php
// ✅ Chain scopes efficiently
Model::scope1()->scope2()->scope3()->get();
// Generates single optimized query

// ✅ Use with eager loading
Model::active()->with('relation')->get();

// ✅ Add database indexes for scoped columns
// In migration:
$table->index('status');
$table->index('created_at');

// ❌ Avoid loading all then filtering in PHP
Model::all()->filter(fn($m) => $m->status === 'active');
// This loads ALL records into memory first!
```

---

## Testing Scope Traits

```php
use Tests\TestCase;
use Modules\Mailing\Models\Campaign;

class CampaignScopesTest extends TestCase
{
    public function test_draft_scope_filters_draft_campaigns()
    {
        Campaign::factory()->create(['status' => CampaignStatus::DRAFT]);
        Campaign::factory()->create(['status' => CampaignStatus::SENT]);

        $drafts = Campaign::draft()->get();

        $this->assertCount(1, $drafts);
        $this->assertTrue($drafts->first()->isDraft());
    }

    public function test_scopes_can_be_chained()
    {
        $campaign = Campaign::factory()->create([
            'status' => CampaignStatus::DRAFT,
            'created_at' => now()->subDays(5),
        ]);

        Campaign::factory()->create([
            'status' => CampaignStatus::SENT,
            'created_at' => now()->subDays(5),
        ]);

        $results = Campaign::draft()->recent(7)->get();

        $this->assertCount(1, $results);
        $this->assertEquals($campaign->id, $results->first()->id);
    }
}
```

---

## Debugging Scopes

```php
// See generated SQL
DB::enableQueryLog();
Model::myScope()->anotherScope()->get();
dd(DB::getQueryLog());

// Output SQL without executing
$sql = Model::myScope()->toSql();
$bindings = Model::myScope()->getBindings();

// Check which scopes are applied
$query = Model::draft()->recent(7);
// Use debugbar or dd($query) to inspect
```

---

**See Also**:
- `app/Models/Scopes/` - Global scopes
- `docs/SCOPES_MIGRATION_REPORT.md` - Complete documentation
- `docs/SCOPES_QUICK_REFERENCE.md` - Quick lookup guide

---

**Total Scopes Available**: 99+ local scopes across 6 traits
