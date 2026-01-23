#!/bin/bash
# Laravel Scheduler Runner Script
# This script runs the Laravel scheduler every minute

PROJECT_PATH="/home2/webadminpruebas/web"
LARAVEL_ARTISAN="$PROJECT_PATH/artisan"
LOG_FILE="/var/log/laravel/scheduler.log"

# Create log directory if it doesn't exist
mkdir -p /var/log/laravel

while true; do
    # Run the scheduler
    /usr/bin/php "$LARAVEL_ARTISAN" schedule:run >> "$LOG_FILE" 2>&1

    # Sleep for 60 seconds minus the execution time
    sleep 60
done
