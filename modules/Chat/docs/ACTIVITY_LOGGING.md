# Activity Logging - Chat Module

## Overview

The Chat module now includes comprehensive activity logging using Spatie Activity Log to track all critical actions for compliance, debugging, and auditing purposes.

## Events Being Logged

### 1. Conversation Status Changes
**Event Name:** `conversation_status_changed`

Tracks when a conversation's status is changed (e.g., open → resolved).

**Properties Logged:**
- `old_status_id`: Previous status ID
- `new_status_id`: New status ID
- `old_status_name`: Previous status name
- `new_status_name`: New status name

**Triggered By:** ConversationObserver

**Example:**
```php
activity()
    ->performedOn($conversation)
    ->causedBy(auth()->user())
    ->withProperties([
        'old_status_id' => 1,
        'new_status_id' => 2,
        'old_status_name' => 'Open',
        'new_status_name' => 'Resolved'
    ])
    ->log('conversation_status_changed');
```

### 2. Agent Reassignments
**Event Name:** `conversation_agent_reassigned`

Tracks when a conversation is reassigned from one agent to another.

**Properties Logged:**
- `old_assignee_id`: Previous agent ID
- `new_assignee_id`: New agent ID
- `old_assignee_name`: Previous agent name
- `new_assignee_name`: New agent name

**Triggered By:** ConversationObserver

### 3. Priority Changes
**Event Name:** `conversation_priority_changed`

Tracks when a conversation's priority is modified.

**Properties Logged:**
- `old_priority_id`: Previous priority ID
- `new_priority_id`: New priority ID
- `old_priority_name`: Previous priority name
- `new_priority_name`: New priority name

**Triggered By:** ConversationObserver

### 4. Label Changes
**Event Name:** `conversation_labels_changed`

Tracks when labels are added to or removed from a conversation.

**Properties Logged:**
- `added_labels`: Array of labels added
- `removed_labels`: Array of labels removed
- `old_labels`: Complete list before change
- `new_labels`: Complete list after change

**Triggered By:** ConversationObserver

### 5. Macro Executions
**Event Name:** `macro_executed`

Tracks when a macro is executed on a conversation.

**Properties Logged:**
- `macro_id`: ID of the macro
- `macro_name`: Name of the macro
- `actions_count`: Number of actions in the macro
- `executed_at`: Timestamp of execution
- `results`: Array of action results

**Triggered By:** MacroExecutor

### 6. Automation Rule Triggers
**Event Name:** `automation_rule_triggered`

Tracks when an automation rule is triggered and its conditions match.

**Properties Logged:**
- `automation_id`: ID of the automation rule
- `automation_name`: Name of the automation rule
- `event_name`: Event that triggered the rule
- `conditions_matched`: Array of conditions that matched
- `actions_executed`: Array of action types executed

**Triggered By:** AutomationRuleExecutor

### 7. SLA Breaches
**Event Name:** `sla_breach_detected`

Tracks when an SLA policy is breached (first response or resolution).

**Properties Logged:**
- `sla_policy_id`: ID of the SLA policy
- `sla_policy_name`: Name of the SLA policy
- `breach_type`: Type of breach (first_response or resolution)
- `due_at`: When the SLA was due
- `breached_at`: When the breach was detected
- `conversation_id`: ID of the conversation

**Triggered By:** SlaTracker

## Using Activity Logs

### Retrieving Logs Programmatically

Use the `ActivityLogService` to query logs:

```php
use Modules\Chat\Services\ActivityLogService;

$service = app(ActivityLogService::class);

// Get all logs for a conversation
$logs = $service->getConversationLogs($conversation, perPage: 20);

// Get logs by event type
$statusChanges = $service->getStatusChangeLogs();
$slaBreaches = $service->getSlaBreachLogs();
$macroExecutions = $service->getMacroExecutionLogs();

// Get logs by user
$userLogs = $service->getLogsByUser($userId);

// Get activity statistics
$stats = $service->getActivityStatistics(
    from: '2026-01-01',
    to: '2026-02-11',
    accountId: 1
);

// Format log entry for display
$formatted = $service->formatLogEntry($log);
```

### Command Line Interface

View activity logs using the provided command:

```bash
# Show usage information
php artisan chat:activity-logs

# Show logs for a specific conversation
php artisan chat:activity-logs 123

# Show logs for a specific event type
php artisan chat:activity-logs --event=sla_breach_detected

# Show logs for a specific user
php artisan chat:activity-logs --user=1

# Show activity statistics
php artisan chat:activity-logs --stats
```

### Querying Directly

You can also query activity logs directly using Spatie's Activity model:

```php
use Spatie\Activitylog\Models\Activity;
use Modules\Chat\Models\Conversations\Conversation;

// Get all status changes
$statusChanges = Activity::query()
    ->where('description', 'conversation_status_changed')
    ->with('causer', 'subject')
    ->latest()
    ->get();

// Get logs for a specific conversation
$conversationLogs = Activity::query()
    ->where('subject_type', Conversation::class)
    ->where('subject_id', $conversationId)
    ->get();

// Get logs by a specific user
$userLogs = Activity::query()
    ->where('causer_id', $userId)
    ->where('causer_type', \App\Models\User::class)
    ->get();
```

## Data Retention

Activity logs can accumulate over time. Use the cleanup method to remove old logs:

```php
use Modules\Chat\Services\ActivityLogService;

$service = app(ActivityLogService::class);

// Delete logs older than 90 days (default)
$deletedCount = $service->cleanupOldLogs();

// Delete logs older than 30 days
$deletedCount = $service->cleanupOldLogs(daysToRetain: 30);
```

Consider scheduling this in `routes/console.php`:

```php
Schedule::call(function () {
    app(\Modules\Chat\Services\ActivityLogService::class)->cleanupOldLogs(90);
})->monthly();
```

## Compliance & Auditing

All activity logs include:

- **Who:** User who performed the action (`causer_id`, `causer_type`)
- **What:** Description of the action (`description`)
- **When:** Timestamp of the action (`created_at`)
- **Where:** The affected resource (`subject_id`, `subject_type`)
- **Details:** Additional context (`properties` JSON field)

This provides a complete audit trail for:
- Compliance requirements (GDPR, SOC 2, etc.)
- Debugging production issues
- Performance analysis
- Team activity tracking
- Customer support documentation

## Database Schema

Activity logs are stored in the `activity_log` table:

```
id: bigint (primary key)
log_name: string (default: 'default')
description: string (event name)
subject_type: string (model class)
subject_id: bigint (model ID)
causer_type: string (user class)
causer_id: bigint (user ID)
properties: json (additional data)
batch_uuid: uuid (for batch operations)
event: string (created/updated/deleted)
created_at: timestamp
updated_at: timestamp
```

## Performance Considerations

1. **Indexes:** The activity_log table has indexes on subject, causer, and created_at
2. **Eager Loading:** Always eager load relationships when querying logs
3. **Pagination:** Use pagination when displaying large result sets
4. **Cleanup:** Regularly clean up old logs to maintain performance

## Best Practices

1. **Always log the "who":** Use `causedBy(auth()->user())` to track who made changes
2. **Include context:** Add relevant properties for debugging and reporting
3. **Human-readable descriptions:** Use descriptive event names
4. **Avoid PII in logs:** Don't log sensitive customer data in properties
5. **Regular cleanup:** Schedule regular cleanup of old logs
6. **Monitor log growth:** Track log table size and query performance

## Troubleshooting

### Logs Not Being Created

1. Verify migrations are run: `php artisan migrate`
2. Check if Spatie Activity Log is installed: `composer show spatie/laravel-activitylog`
3. Verify observers are registered in ChatServiceProvider or EventServiceProvider
4. Check application logs for errors

### Performance Issues

1. Add indexes to activity_log table if needed
2. Implement log cleanup schedule
3. Use pagination when displaying logs
4. Consider archiving old logs to separate table

### Missing Causer Information

Some events (like scheduled SLA checks) run without an authenticated user. In these cases, `causer_id` will be null and the system is considered the actor.

## Future Enhancements

Potential improvements to consider:

1. **Real-time Log Streaming:** Use Laravel Reverb to stream logs to dashboard
2. **Advanced Filtering:** Add UI for filtering logs by date, user, event type
3. **Export Functionality:** Export logs to CSV/Excel for compliance reporting
4. **Alerting:** Send notifications on critical events (SLA breaches, etc.)
5. **Analytics Dashboard:** Visualize activity trends and patterns
6. **Log Archiving:** Move old logs to cold storage (S3, etc.)
