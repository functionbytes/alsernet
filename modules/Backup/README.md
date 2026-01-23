# Backup Module

Backup Module for Alsernet. Provides comprehensive backup management including automatic backup scheduling, backup creation, and backup restoration.

## Features

- **Backup Management** - Create, list, download, and delete backups
- **Backup Scheduling** - Schedule automatic backups at specified intervals
- **Supervisor Backups** - Integration with Supervisor for background backup tasks
- **Backup Notifications** - Get notified on backup success or failure
- **Backup Jobs** - Async job for creating backups
- **Backup Listeners** - Event listeners for backup-related events
- **Console Commands** - CLI tools for backup operations

## Routes

Manager Routes (`/manager/settings/backups/`):
- GET / - List all backups
- GET /create - Create backup form
- POST /store - Create new backup
- GET /{id}/download - Download backup
- DELETE /{id} - Delete backup

Manager Routes (`/manager/settings/backup-schedules/`):
- GET / - List all schedules
- GET /create - Create schedule form
- POST /store - Store new schedule
- GET /{id}/edit - Edit form
- POST /{id}/update - Update schedule
- DELETE /{id} - Delete schedule

## Models

- **BackupSchedule** - Schedule configuration for automatic backups
- **SupervisorBackup** - Supervisor integration for backup tasks

## Jobs

- **CreateBackupJob** - Async job for creating backups

## Listeners

- **BackupEventListener** - Listens to backup-related events

## Notifications

- **BackupNotification** - Base backup notification
- **BackupSuccessfulNotification** - Sent on successful backup
- **BackupFailedNotification** - Sent on failed backup

## Console Commands

- **supervisor-backup** - Manage Supervisor backup tasks
- **run-scheduled-backups** - Execute scheduled backups

## Architecture

```
Modules/Backup/
├── app/
│   ├── Http/
│   │   └── Controllers/Managers/Settings/
│   │       ├── BackupController.php
│   │       └── BackupScheduleController.php
│   ├── Models/Setting/Backup/
│   │   ├── BackupSchedule.php
│   │   └── SupervisorBackup.php
│   ├── Jobs/
│   │   └── CreateBackupJob.php
│   ├── Listeners/Systems/Backups/
│   │   └── BackupEventListener.php
│   ├── Notifications/
│   │   ├── BackupNotification.php
│   │   └── BackupNotifications/
│   │       ├── BackupSuccessfulNotification.php
│   │       └── BackupFailedNotification.php
│   ├── Console/Commands/
│   │   ├── SupervisorBackupCommand.php
│   │   └── RunScheduledBackups.php
│   └── Providers/
│       └── BackupServiceProvider.php
├── config/
│   └── backup.php
├── routes/
│   └── managers.php
├── resources/views/managers/settings/
└── README.md
```

## Integration Points

- **Laravel Backup (Spatie)** - Backup functionality
- **Laravel Jobs** - Async backup creation
- **Laravel Events & Listeners** - Backup event handling
- **Laravel Notifications** - Backup notifications
- **Supervisor** - Background task management

## License

Proprietary - Alsernet
