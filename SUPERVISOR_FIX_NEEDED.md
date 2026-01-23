# Supervisor Configuration Fixes Needed

## ⚠️ Issues Found

### 1. laravel-health-worker - FATAL (Exited too quickly)

**File**: `/etc/supervisor/conf.d/laravel-health-worker.conf`

**Problem**: Command has malformed arguments

**Current (WRONG)**:
```ini
command=/usr/bin/php /home/webadmin/web/artisan queue:work database --queue=health numprocs=1
```

**Should be (CORRECT)**:
```ini
command=/usr/bin/php /home/webadmin/web/artisan queue:work database --queue=health --sleep=3 --tries=3 --max-time=300 --timeout=300
numprocs=1
```

**Complete file** `/etc/supervisor/conf.d/laravel-health-worker.conf`:
```ini
[program:laravel-health-worker]
process_name=%(program_name)s_%(process_num)02d
command=/usr/bin/php /home/webadmin/web/artisan queue:work database --queue=health --sleep=3 --tries=3 --max-time=300 --timeout=300
autostart=true
autorestart=true
numprocs=1
redirect_stderr=true
stdout_logfile=/var/log/supervisor/laravel-health-worker.log
stdout_logfile_maxbytes=10MB
stdout_logfile_backups=10
environment=LARAVEL_ENV=production
user=www-data
startsecs=10
stopwaitsecs=10
priority=999
```

### 2. laravel-scheduler - FATAL (Exited too quickly)

**File**: `/etc/supervisor/conf.d/laravel-scheduler.conf`

**Problem**: Path points to `/home2/webadmin/web` which may not exist or have wrong permissions

**Current (POSSIBLY WRONG)**:
```ini
command=/bin/bash /home2/webadmin/web/deployment/scripts/scheduler.sh
```

**Should be (CHECK PATH)**:
```ini
command=/bin/bash /home/webadmin/web/deployment/scripts/scheduler.sh
```

Or verify `/home2/webadmin/web` exists and has correct permissions.

## Fix Steps

Run as root/sudo:

```bash
# 1. Edit laravel-health-worker.conf
sudo nano /etc/supervisor/conf.d/laravel-health-worker.conf
# Replace the command line with the corrected version above

# 2. Edit laravel-scheduler.conf (if needed)
sudo nano /etc/supervisor/conf.d/laravel-scheduler.conf
# Verify the path is correct

# 3. Reload and restart
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl restart laravel-health-worker:* laravel-scheduler

# 4. Verify status
sudo supervisorctl status
```

## Current Status

✅ **RUNNING**:
- laravel-queue-default:laravel-queue-default_00
- laravel-queue-erp:laravel-queue-erp_00
- laravel-reverb

❌ **FATAL**:
- laravel-health-worker:* (4 processes)
- laravel-scheduler

## Notes

- Do NOT modify `/etc/supervisor/conf.d/` files from within this application
- Use root/sudo access to edit supervisor configurations
- Always run `sudo supervisorctl reread && sudo supervisorctl update` after changes
- Check logs: `sudo tail -f /var/log/supervisor/laravel-*.log`
