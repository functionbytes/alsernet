#!/bin/bash
# Laravel Scheduler — ejecuta artisan schedule:run cada 60 segundos

LARAVEL_ROOT="/var/www"

mkdir -p "$LARAVEL_ROOT/storage/logs/supervisor"

while true; do
    /usr/bin/php "$LARAVEL_ROOT/artisan" schedule:run >> "$LARAVEL_ROOT/storage/logs/supervisor/scheduler.log" 2>&1
    sleep 60
done
