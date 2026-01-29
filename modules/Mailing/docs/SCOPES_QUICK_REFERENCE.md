# Query Scopes Quick Reference - Mailing Module

**Quick lookup guide for all available scopes**

---

## Global Scopes

Apply automatically to all queries. Remove with `withoutGlobalScope()`.

```php
// ActiveScope - only active records
Model::withoutGlobalScope(ActiveScope::class)->get();

// CustomerScope - only user's records
Model::withAllCustomers()->get();

// DateFilterScope - only recent records
Model::withAllDates()->get();
Model::lastDays(7)->get();

// MailListScope - only specific list
Model::withAllLists()->get();
Model::forList($listId)->get();

// StatusScope - exclude statuses
Model::withAllStatuses()->get();
Model::onlyStatus('draft')->get();
```

---

## Common Scopes (HasCommonScopes)

Available on all models with the trait.

```php
// Status
->byStatus('active')
->exceptStatus(['deleted', 'archived'])
->active()
->inactive()

// Dates
->createdBetween('2026-01-01', '2026-01-31')
->createdAfter('2026-01-01')
->recent(30) // last 30 days

// Ownership
->ownedBy($userId)

// Search
->search('term') // searches name, email, description, subject

// Ordering
->ordered('created_at', 'desc')

// Other
->byUid('abc123')
->published()
->verified()
->unverified()
```

---

## Campaign Scopes (HasCampaignScopes)

```php
// By Status
->draft()
->scheduled()
->sending()
->sent()
->paused()
->failed()
->queued()

// Ready to Send
->readyToSend() // scheduled in past
->futureScheduled() // scheduled in future

// Groups
->notDraft()
->activeCampaigns() // scheduled/sending/queued
->completed() // sent or failed

// Dates
->sentBetween('2026-01-01', '2026-01-31')
->sentToday()

// Filters
->forList($listId)
->bySender($senderId)
->withAnalytics()
->highPerforming(25.0) // min open rate %

// Search
->searchCampaigns('Newsletter')
```

---

## Subscriber Scopes (HasSubscriberScopes)

```php
// By Status
->active()
->subscribed()
->unsubscribed()
->bounced()
->spamReported()
->blacklisted()
->pending()

// Sync Status
->needsSyncing()
->synced()

// Validation
->validated()
->unvalidated()

// Groups
->inGroup($groupId)
->notInGroup($groupId)

// Dates
->subscribedBetween('2026-01-01', '2026-01-31')
->subscribedToday()
->recentlyActive(30)

// Filters
->byEmail('user@example.com')
->sendable() // active + validated
->withCustomField('key', 'value')

// Search
->searchSubscribers('gmail')
```

---

## Sending Server Scopes (HasMailingServerScopes)

```php
// Status
->active()
->inactive()
->withErrors()

// Availability
->availableToSend() // active + quota available
->quotaExceeded()

// Types
->byType('smtp')
->smtp()
->apiServers() // SendGrid, Mailgun, etc.

// Health
->needsConnectionCheck(24) // hours
->highBounceRate(5.0) // percentage
->highComplaintRate(0.1) // percentage
->recentlySent(24) // hours

// Ordering
->orderByCapacity('desc') // by quota remaining

// Filters
->ownedBy($userId)

// Search
->search('server name')
```

---

## Template Scopes (HasTemplateScopes)

```php
// Status
->active()
->inactive()

// Ownership
->ownedBy($userId)
->system() // no user
->userCreated() // has user

// Categories
->byCategory('newsletter')

// Filters
->featured()
->recent(30)
->withSettings('key', 'value')

// Ordering
->orderByName('asc')

// Search
->search('welcome email')
```

---

## Log Scopes (HasLogScopes)

```php
// Time Ranges
->today()
->yesterday()
->thisWeek()
->thisMonth()
->lastDays(7)
->betweenDates('2026-01-01', '2026-01-31')

// Processing
->processed()
->unprocessed()

// Bounce Types
->byBounceType('hard')
->hardBounces()
->softBounces()
->complaints()

// Ordering
->latest() // most recent first
->oldest() // oldest first

// Filters
->byEmail('user@example.com')
->byHandler($handlerId)
->withErrors()

// Search
->search('mailbox full')
```

---

## Chaining Examples

```php
// Find sendable subscribers in a group, subscribed this month
Subscriber::sendable()
    ->inGroup(5)
    ->subscribedBetween(now()->startOfMonth(), now())
    ->searchSubscribers('gmail')
    ->get();

// Get high performing campaigns from last quarter
Campaign::highPerforming(30)
    ->sentBetween(now()->subMonths(3), now())
    ->forList($listId)
    ->withAnalytics()
    ->ordered('sent_at', 'desc')
    ->get();

// Find best available sending server
SendingServer::availableToSend()
    ->apiServers()
    ->orderByCapacity('desc')
    ->first();

// Today's unprocessed hard bounces
BounceLog::today()
    ->hardBounces()
    ->unprocessed()
    ->latest()
    ->get();

// Active user templates from this month
Template::active()
    ->userCreated()
    ->ownedBy(auth()->id())
    ->recent(30)
    ->orderByName()
    ->get();
```

---

## Performance Tips

```php
// ✅ Use eager loading with scopes
Campaign::sent()
    ->with('analytics', 'mailingList')
    ->get();

// ✅ Use select to limit columns
Subscriber::sendable()
    ->select('id', 'email', 'name')
    ->get();

// ✅ Paginate large result sets
Campaign::recent(30)
    ->paginate(50);

// ❌ Avoid N+1 queries
foreach (Campaign::sent()->get() as $campaign) {
    $campaign->analytics; // N+1 problem
}
```

---

## Debugging Scopes

```php
// See generated SQL
DB::enableQueryLog();
Campaign::draft()->recent(7)->get();
dd(DB::getQueryLog());

// Explain query performance
DB::select('EXPLAIN ANALYZE ' .
    Campaign::draft()->recent(7)->toSql()
);

// Bypass global scopes
Model::withoutGlobalScopes()->get();
Model::withoutGlobalScope(ActiveScope::class)->get();
```

---

**For detailed documentation, see**: `SCOPES_MIGRATION_REPORT.md`
