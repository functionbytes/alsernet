# Console Commands Location Reference

Quick lookup guide for finding Artisan console commands by module.

## Command Signatures to File Path Mapping

### Campaign Commands
| Signature | File Path | Module |
|-----------|-----------|--------|
| `campaign:test` | `Modules/Campaign/app/Console/Commands/TestCampaign.php` | Campaign |
| `campaign:send-notification` | `Modules/Campaign/app/Console/Commands/SendGroupNotification.php` | Campaign |
| `campaign:send-test-notifications` | `Modules/Campaign/app/Console/Commands/SendTestNotifications.php` | Campaign |
| `campaign:verify-sender` | `Modules/Campaign/app/Console/Commands/VerifySender.php` | Campaign |

### Documents/Tickets Commands
| Signature | File Path | Module |
|-----------|-----------|--------|
| `tickets:migrate-categories` | `Modules/Documents/app/Console/Commands/MigrateTicketCategoriesToHelpdesk.php` | Documents |
| `tickets:migrate-status` | `Modules/Documents/app/Console/Commands/MigrateTicketStatusToHelpdesk.php` | Documents |
| `tickets:check-sla` | `Modules/Documents/app/Console/Commands/CheckSlaBreaches.php` | Documents |

### Helpdesk Commands
| Signature | File Path | Module |
|-----------|-----------|--------|
| `helpdesk:send-sla-warnings` | `Modules/Helpdesk/app/Console/Commands/SendSlaWarnings.php` | Helpdesk |
| `helpdesk:cleanup-old-communications` | `Modules/Helpdesk/app/Console/Commands/CleanupOldCommunications.php` | Helpdesk |

### Returns Commands
| Signature | File Path | Module |
|-----------|-----------|--------|
| `returns:process-components` | `Modules/Returns/app/Console/Commands/ProcessComponents.php` | Returns |
| `returns:process-warranties` | `Modules/Returns/app/Console/Commands/ProcessWarranties.php` | Returns |
| `returns:send-reminders` | `Modules/Returns/app/Console/Commands/SendReturnReminders.php` | Returns |
| `returns:audit-rules` | `Modules/Returns/app/Console/Commands/AuditReturnRules.php` | Returns |

### Warehouse Commands
| Signature | File Path | Module |
|-----------|-----------|--------|
| `returns:update-tracking` | `Modules/Warehouse/app/Console/Commands/UpdateTrackingStatuses.php` | Warehouse |

## Directory Structure

```
Modules/
├── Campaign/
│   └── app/Console/Commands/
│       ├── TestCampaign.php
│       ├── SendGroupNotification.php
│       ├── SendTestNotifications.php
│       └── VerifySender.php
├── Documents/
│   └── app/Console/Commands/
│       ├── MigrateTicketCategoriesToHelpdesk.php
│       ├── MigrateTicketStatusToHelpdesk.php
│       └── CheckSlaBreaches.php
├── Helpdesk/
│   └── app/Console/Commands/
│       ├── SendSlaWarnings.php
│       └── CleanupOldCommunications.php
├── Returns/
│   └── app/Console/Commands/
│       ├── ProcessComponents.php
│       ├── ProcessWarranties.php
│       ├── SendReturnReminders.php
│       └── AuditReturnRules.php
└── Warehouse/
    └── app/Console/Commands/
        └── UpdateTrackingStatuses.php
```

## How to Find a Command

### Method 1: By Signature Name
```bash
# Find command file by its signature
php artisan list

# Example: Looking for "campaign:test"
# File: modules/Campaign/app/Console/Commands/TestCampaign.php
```

### Method 2: By Module
Navigate to `Modules/{ModuleName}/app/Console/Commands/` and browse the files.

### Method 3: By Filename
All command class names match their file names:
- File: `TestCampaign.php` → Class: `TestCampaign`
- File: `SendReturnReminders.php` → Class: `SendReturnReminders`

## Namespace Pattern

All commands follow this namespace pattern:

```php
namespace Modules\{ModuleName}\Console\Commands;

class {CommandClassName} extends Command
{
    protected $signature = '{signature}';
    // ...
}
```

## Adding New Commands to a Module

To create a new console command in a module:

```bash
# Create command file
php artisan make:command {CommandName} --no-interaction

# Move the generated file from app/Console/Commands/ to:
# modules/{ModuleName}/app/Console/Commands/

# Update namespace from:
# namespace App\Console\Commands;

# To:
# namespace modules\{ModuleName}\Console\Commands;
```

Laravel 12 will automatically discover it in the module structure.

## Testing Commands

```bash
# List all available commands
php artisan list

# Get help for a specific command
php artisan {signature} --help

# Run a command
php artisan {signature} {arguments}
```

## Scheduling Commands

Commands are scheduled in `routes/console.php`:

```php
// Command signature remains the same regardless of file location
Schedule::command('campaign:test')->hourly();
Schedule::command('returns:send-reminders')->daily();
Schedule::command('helpdesk:send-sla-warnings')->everyFiveMinutes();
```

## Related Documentation

- Full technical report: [console-commands-migration-report.md](./console-commands-migration-report.md)
- Laravel 12 commands: [Laravel Docs](https://laravel.com/docs/12/artisan)
- Module structure: See each module's `app/Console/Commands/` directory
