# Supervisor Configuration Setup

## Overview
This directory contains the Supervisor configuration files needed to run Laravel queue workers and scheduler.

## Files

- **laravel-queue-worker.conf** - Queue workers for default, emails, and notifications
- **laravel-queue-erp.conf** - Queue worker for ERP sync operations
- **laravel-scheduler.conf** - Laravel scheduler runner
- **laravel-health-worker.conf** - Health check queue worker (NEW)
- **laravel-reverb.conf** - Laravel Reverb WebSocket server

## Installation Steps

### 1. Copy configuration files to Supervisor
```bash
sudo cp laravel-queue-worker.conf /etc/supervisor/conf.d/
sudo cp laravel-queue-erp.conf /etc/supervisor/conf.d/
sudo cp laravel-scheduler.conf /etc/supervisor/conf.d/
sudo cp laravel-health-worker.conf /etc/supervisor/conf.d/
sudo cp laravel-reverb.conf /etc/supervisor/conf.d/
```

### 2. Reload Supervisor
```bash
sudo supervisorctl reread
sudo supervisorctl update
```

### 3. Start all processes
```bash
sudo supervisorctl start all
```

### 4. Verify processes are running
```bash
sudo supervisorctl status
```

## Process Details

### laravel-queue-worker
- **Purpose**: Process general queue jobs (default, emails, notifications)
- **Command**: `php artisan queue:work --queue=default,emails,notifications`
- **Workers**: 4 processes (configurable with `numprocs`)

### laravel-queue-erp
- **Purpose**: Process ERP synchronization jobs
- **Command**: `php artisan queue:work --queue=erp`
- **Workers**: 1 process

### laravel-scheduler
- **Purpose**: Run the Laravel scheduler every minute
- **Script**: `/home/webadmin/web/deployment/scripts/scheduler.sh`
- **Workers**: 1 process

### laravel-health-worker (NEW)
- **Purpose**: Process health check monitoring jobs
- **Command**: `php artisan queue:work --queue=health`
- **Workers**: 1 process
- **Details**: Runs health checks and heartbeat monitoring for system status

### laravel-reverb
- **Purpose**: Handle WebSocket connections
- **Command**: `php artisan reverb:start`
- **Workers**: 1 process

## Common Commands

```bash
# View status of all processes
sudo supervisorctl status

# Start specific process
sudo supervisorctl start laravel-queue-worker:*

# Stop specific process
sudo supervisorctl stop laravel-queue-worker:*

# Restart specific process
sudo supervisorctl restart laravel-queue-worker:*

# View logs
sudo tail -f /var/log/supervisor/laravel-queue-worker.log
sudo tail -f /var/log/supervisor/laravel-scheduler.log
sudo tail -f /var/log/supervisor/laravel-health-worker.log

# Reread all configurations
sudo supervisorctl reread

# Update and apply changes
sudo supervisorctl update
```

## Troubleshooting

### Processes keep restarting
1. Check logs: `sudo tail -f /var/log/supervisor/laravel-*.log`
2. Run queue:work manually: `php artisan queue:work database`
3. Check for errors in application logs: `/home/webadmin/web/storage/logs/`

### Database connection issues
- Ensure database credentials in `.env` are correct
- Verify database is running and accessible

### Permission issues
- Ensure processes run as correct user (www-data)
- Check storage permissions: `chmod -R 775 storage`

## Notes

- All paths should point to `/home/webadmin/web` (production path)
- Queue connection is set to `database` (uses jobs table)
- Health worker processes jobs from the `health` queue for monitoring
- Logs are stored in `/var/log/supervisor/`
