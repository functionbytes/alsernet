# Activity Logging - Quick Start Guide

## Setup (One-Time)

### 1. Run Migrations
```bash
php artisan migrate --force
```

This creates the `activity_log` table.

## Viewing Activity Logs

### CLI Commands

```bash
# Show usage and available commands
php artisan chat:activity-logs

# Show logs for conversation #123
php artisan chat:activity-logs 123

# Show all SLA breaches
php artisan chat:activity-logs --event=sla_breach_detected

# Show all status changes
php artisan chat:activity-logs --event=conversation_status_changed

# Show activity by user #5
php artisan chat:activity-logs --user=5

# Show statistics (last 30 days)
php artisan chat:activity-logs --stats
```

### In Code

```php
use Modules\Chat\Services\ActivityLogService;
use Modules\Chat\Models\Conversations\Conversation;

$service = app(ActivityLogService::class);

// Get logs for a conversation
$conversation = Conversation::find(123);
$logs = $service->getConversationLogs($conversation);

foreach ($logs as $log) {
    $formatted = $service->formatLogEntry($log);
    echo "{$formatted['created_at']}: {$formatted['human_description']}\n";
}

// Get all SLA breaches
$breaches = $service->getSlaBreachLogs();

// Get statistics
$stats = $service->getActivityStatistics();
print_r($stats);
```

## Events Available

| Event Name | Description |
|---|---|
| `conversation_status_changed` | Status changed (open → resolved, etc.) |
| `conversation_agent_reassigned` | Conversation reassigned to different agent |
| `conversation_priority_changed` | Priority modified (low → high, etc.) |
| `conversation_labels_changed` | Labels added or removed |
| `macro_executed` | Macro executed on conversation |
| `automation_rule_triggered` | Automation rule triggered |
| `sla_breach_detected` | SLA policy breached |

## Common Use Cases

### 1. Debug: What changed on this conversation?
```bash
php artisan chat:activity-logs 123
```

### 2. Compliance: Who changed the status?
```php
$logs = $service->getStatusChangeLogs();
foreach ($logs as $log) {
    echo "User {$log->causer->name} changed status at {$log->created_at}\n";
}
```

### 3. Reporting: SLA breach count this month
```php
$stats = $service->getActivityStatistics(
    from: now()->startOfMonth()->format('Y-m-d'),
    to: now()->format('Y-m-d')
);
echo "SLA Breaches: {$stats['sla_breaches']}\n";
```

### 4. Audit: All actions by a specific user
```bash
php artisan chat:activity-logs --user=5
```

### 5. Monitoring: Recent automation triggers
```bash
php artisan chat:activity-logs --event=automation_rule_triggered
```

## Maintenance

### Clean Up Old Logs

```php
use Modules\Chat\Services\ActivityLogService;

$service = app(ActivityLogService::class);

// Delete logs older than 90 days
$deleted = $service->cleanupOldLogs(90);
echo "Deleted {$deleted} old log entries\n";
```

### Schedule Regular Cleanup

Add to `routes/console.php`:

```php
use Modules\Chat\Services\ActivityLogService;

Schedule::call(function () {
    app(ActivityLogService::class)->cleanupOldLogs(90);
})->monthly();
```

## Testing

Run the test suite:

```bash
php artisan test --filter=ActivityLoggingTest
```

## Troubleshooting

### No logs appearing?
1. Check migrations are run: `php artisan migrate:status`
2. Verify you're authenticated when making changes
3. Check application logs for errors

### Too many logs?
1. Implement log cleanup schedule
2. Adjust retention period (default 90 days)
3. Add indexes if queries are slow

### Need more detail?
See full documentation: `/modules/Chat/docs/ACTIVITY_LOGGING.md`
