#!/bin/bash
# Notification Models Consolidation Script
# This script executes the consolidation plan from the analysis
# Usage: bash NOTIFICATION_CONSOLIDATION_COMMANDS.sh

set -e  # Exit on first error

echo "=========================================="
echo "Notification Models Consolidation"
echo "=========================================="
echo ""

# Color codes
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to print sections
print_section() {
    echo ""
    echo -e "${YELLOW}>>> $1${NC}"
    echo ""
}

# Function to check file exists
check_file() {
    if [ -f "$1" ]; then
        echo -e "${GREEN}✓${NC} Found: $1"
        return 0
    else
        echo -e "${RED}✗${NC} Missing: $1"
        return 1
    fi
}

# ============================================
# PHASE 1: VERIFY CURRENT STATE
# ============================================

print_section "PHASE 1: Verifying Current State"

echo "Checking for models to delete..."
check_file "Modules/Notification/app/Models/PushNotificationToken.php"
check_file "Modules/Notification/app/Models/NotificationSetting.php"

echo ""
echo "Checking for migrations to delete..."
check_file "database/migrations/2025_12_29_054242_create_notification_settings_table.php"

echo ""
echo "Checking for models to keep..."
check_file "Modules/Notification/app/Models/NotificationPushToken.php"
check_file "Modules/Notification/app/Models/NotificationPreference.php"

echo ""
echo "Checking for migrations to keep..."
check_file "database/migrations/2025_12_29_054249_create_push_notification_tokens_table.php"

# ============================================
# PHASE 2: DELETE UNUSED MODELS
# ============================================

print_section "PHASE 2: Deleting Unused Models"

echo "Removing PushNotificationToken.php..."
if [ -f "Modules/Notification/app/Models/PushNotificationToken.php" ]; then
    rm modules/Notification/app/Models/PushNotificationToken.php
    echo -e "${GREEN}✓${NC} Deleted: Modules/Notification/app/Models/PushNotificationToken.php"
else
    echo -e "${RED}✗${NC} File not found: Modules/Notification/app/Models/PushNotificationToken.php"
fi

echo ""
echo "Removing NotificationSetting.php..."
if [ -f "Modules/Notification/app/Models/NotificationSetting.php" ]; then
    rm modules/Notification/app/Models/NotificationSetting.php
    echo -e "${GREEN}✓${NC} Deleted: Modules/Notification/app/Models/NotificationSetting.php"
else
    echo -e "${RED}✗${NC} File not found: Modules/Notification/app/Models/NotificationSetting.php"
fi

# ============================================
# PHASE 3: DELETE INCORRECT MIGRATION
# ============================================

print_section "PHASE 3: Removing Incorrect Migration"

echo "Removing create_notification_settings_table migration..."
if [ -f "database/migrations/2025_12_29_054242_create_notification_settings_table.php" ]; then
    rm database/migrations/2025_12_29_054242_create_notification_settings_table.php
    echo -e "${GREEN}✓${NC} Deleted: database/migrations/2025_12_29_054242_create_notification_settings_table.php"
else
    echo -e "${RED}✗${NC} File not found: database/migrations/2025_12_29_054242_create_notification_settings_table.php"
fi

# ============================================
# PHASE 4: CREATE NEW MIGRATIONS
# ============================================

print_section "PHASE 4: Creating Required Migrations"

# Determine migration timestamp
TIMESTAMP=$(date +%Y_%m_%d_%H%M%S)
PUSH_TOKEN_MIGRATION="database/migrations/${TIMESTAMP}_add_push_token_columns.php"
PREFERENCE_MIGRATION="database/migrations/${TIMESTAMP}_create_notification_preferences_table.php"

echo "Migration timestamp: $TIMESTAMP"
echo ""

# Create push token columns migration
echo "Creating migration: $PUSH_TOKEN_MIGRATION"
cat > "$PUSH_TOKEN_MIGRATION" << 'EOF'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('push_notification_tokens', function (Blueprint $table) {
            // Add columns that NotificationPushToken model expects
            if (!Schema::hasColumn('push_notification_tokens', 'browser')) {
                $table->string('browser')->nullable()->after('device_type');
            }
            if (!Schema::hasColumn('push_notification_tokens', 'platform')) {
                $table->string('platform')->nullable()->after('browser');
            }
            if (!Schema::hasColumn('push_notification_tokens', 'ip_address')) {
                $table->string('ip_address')->nullable()->after('platform');
            }
        });
    }

    public function down(): void
    {
        Schema::table('push_notification_tokens', function (Blueprint $table) {
            $table->dropColumn(['browser', 'platform', 'ip_address']);
        });
    }
};
EOF

if [ -f "$PUSH_TOKEN_MIGRATION" ]; then
    echo -e "${GREEN}✓${NC} Created: $PUSH_TOKEN_MIGRATION"
else
    echo -e "${RED}✗${NC} Failed to create migration"
    exit 1
fi

echo ""

# Create notification preferences table migration
echo "Creating migration: $PREFERENCE_MIGRATION"
cat > "$PREFERENCE_MIGRATION" << 'EOF'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('channel');              // email, push, in_app, sms
            $table->string('notification_type');   // order.created, order.shipped, etc.
            $table->boolean('is_enabled')->default(true);
            $table->json('settings')->nullable();  // For extensibility

            $table->timestamps();

            // Ensure one preference per user+channel+type combination
            $table->unique(['user_id', 'channel', 'notification_type']);

            // Index for common queries
            $table->index(['user_id', 'channel']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
    }
};
EOF

if [ -f "$PREFERENCE_MIGRATION" ]; then
    echo -e "${GREEN}✓${NC} Created: $PREFERENCE_MIGRATION"
else
    echo -e "${RED}✗${NC} Failed to create migration"
    exit 1
fi

# ============================================
# PHASE 5: RUN MIGRATIONS
# ============================================

print_section "PHASE 5: Running Database Migrations"

echo "Executing: php artisan migrate"
echo ""

if command -v php &> /dev/null; then
    php artisan migrate
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓${NC} Migrations executed successfully"
    else
        echo -e "${RED}✗${NC} Migration failed"
        exit 1
    fi
else
    echo -e "${YELLOW}!${NC} PHP not found. Skipping migration execution."
    echo "Run manually: php artisan migrate"
fi

# ============================================
# PHASE 6: SUMMARY
# ============================================

print_section "PHASE 6: Consolidation Summary"

echo -e "${GREEN}Files Deleted:${NC}"
echo "  ✓ Modules/Notification/app/Models/PushNotificationToken.php"
echo "  ✓ Modules/Notification/app/Models/NotificationSetting.php"
echo "  ✓ database/migrations/2025_12_29_054242_create_notification_settings_table.php"

echo ""
echo -e "${GREEN}Files Created:${NC}"
echo "  ✓ $PUSH_TOKEN_MIGRATION"
echo "  ✓ $PREFERENCE_MIGRATION"

echo ""
echo -e "${GREEN}Models Remaining:${NC}"
echo "  ✓ Modules/Notification/app/Models/NotificationPushToken.php"
echo "  ✓ Modules/Notification/app/Models/NotificationPreference.php"

echo ""
echo -e "${GREEN}Database Tables:${NC}"
echo "  ✓ push_notification_tokens (schema updated)"
echo "  ✓ notification_preferences (newly created)"

echo ""
echo "=========================================="
echo -e "${GREEN}Consolidation Complete!${NC}"
echo "=========================================="

print_section "NEXT STEPS"

echo "1. Run tests to verify everything works:"
echo "   php artisan test"
echo ""
echo "2. Test the API endpoints:"
echo "   POST /api/notifications/push-token"
echo "   POST /api/notifications/preferences"
echo ""
echo "3. Verify relationships:"
echo "   php artisan tinker"
echo "   >>> User::find(1)->pushTokens()->count()"
echo "   >>> User::find(1)->notificationPreferences()->count()"
echo ""
echo "4. Optional: Create a factory for NotificationPushToken"
echo "   php artisan make:factory NotificationPushTokenFactory"
echo ""

exit 0
