# Mailing Module Observers

## Quick Reference

This directory contains documentation for all Eloquent Observers in the Mailing module. Observers provide automatic tracking, caching, and lifecycle management for models.

## Available Observers

### Critical Observers (Must Have)

1. **CampaignObserver** - Campaign lifecycle and analytics tracking
2. **MailListObserver** - Mail list management and subscriber counts
3. **SubscriberObserver** - Subscriber lifecycle and engagement tracking
4. **TrackingLogObserver** - Email tracking and analytics processing

### High Priority Observers

5. **SendingServerObserver** - Sending server status and quota management
6. **TemplateObserver** - Template caching and thumbnail generation
7. **AutomationObserver** - Automation workflow lifecycle management

### Medium Priority Observers

8. **SegmentObserver** - Segment subscriber count calculation
9. **SendingDomainObserver** - Domain verification management

## Observer Locations

All observers are located at:
```
modules/Mailing/app/Observers/
├── CampaignObserver.php
├── MailListObserver.php
├── SubscriberObserver.php
├── TemplateObserver.php
├── SendingServerObserver.php
├── TrackingLogObserver.php
├── AutomationObserver.php
├── SegmentObserver.php
└── SendingDomainObserver.php
```

## Registration

All observers are registered in:
```
modules/Mailing/app/Providers/EventServiceProvider.php
```

Registration is conditional - observers only register if their corresponding models exist.

## Key Features Across All Observers

- ✅ Automatic UID generation
- ✅ Cache invalidation
- ✅ Activity logging (Spatie)
- ✅ Asynchronous processing
- ✅ Laravel 12 compatible

## Common Observer Patterns

### UID Generation

All models get a unique identifier on creation:
```php
if (empty($model->uid)) {
    $model->uid = $this->generateUid();
}
```

### Cache Clearing

Multi-level cache invalidation:
```php
cache()->forget("model.{$id}");
cache()->forget("model.uid.{$uid}");
cache()->forget('models.list');
```

### Activity Logging

All CRUD operations logged:
```php
activity()
    ->performedOn($model)
    ->withProperties(['field' => 'value'])
    ->log('Action performed');
```

### Async Processing

Heavy operations queued:
```php
dispatch(function () use ($model) {
    // Heavy processing
})->afterResponse();
```

## Documentation

For complete documentation, see:
- **[OBSERVERS_MIGRATION_REPORT.md](../OBSERVERS_MIGRATION_REPORT.md)** - Full migration report and technical details

## Testing

Each observer should have:
- Unit tests for each event method
- Integration tests with related models
- Performance benchmarks

## Performance Metrics

Expected overhead per observer:
- **Creation:** +30-50ms (async operations queued)
- **Update:** +20-40ms (cache invalidation)
- **Deletion:** +10-30ms (cleanup operations)

Most heavy operations are async, so actual response time impact is minimal.

## Troubleshooting

### Observer Not Firing

1. Check registration in EventServiceProvider
2. Verify model exists and uses HasFactory
3. Clear cache: `php artisan cache:clear`

### Performance Issues

1. Check queue workers running: `php artisan horizon:status`
2. Monitor Redis: `redis-cli info stats`
3. Review async operations in observer code

### Cache Issues

1. Verify Redis connection
2. Check cache driver in `.env`
3. Test cache tags support

## Related Documentation

- [Migration Plan](../MIGRATION_PLAN.md) - Overall Acelle to Mailing migration
- [Database Agents](../DATABASE_AGENTS.md) - Database CRUD agents
- [Models Documentation](../../app/Models/README.md) - Model reference

---

**Last Updated:** 2026-01-29
**Total Observers:** 9
**Migration Status:** ✅ Complete
