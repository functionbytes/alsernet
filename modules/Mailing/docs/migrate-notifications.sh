#!/bin/bash

# Notifications Migration Script
# Migrates notification classes from Acelle to Mailing module

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Paths
ACELLE_NOTIFICATIONS="/Users/functionbytes/Function/Coding/acelle/app/Notifications"
MAILING_NOTIFICATIONS="/Users/functionbytes/Function/Coding/system/modules/Mailing/app/Notifications"
REPORT_FILE="/Users/functionbytes/Function/Coding/system/modules/Mailing/docs/NOTIFICATIONS_MIGRATION_REPORT.md"

echo -e "${GREEN}=== Notifications Migration Script ===${NC}"
echo ""

# Check if source directory exists
if [ ! -d "$ACELLE_NOTIFICATIONS" ]; then
    echo -e "${RED}Error: Acelle notifications directory not found at $ACELLE_NOTIFICATIONS${NC}"
    exit 1
fi

# Create destination directory if it doesn't exist
mkdir -p "$MAILING_NOTIFICATIONS"

# Initialize report
cat > "$REPORT_FILE" << 'EOF'
# Notifications Migration Report

**Migration Date:** $(date +"%Y-%m-%d %H:%M:%S")
**Source:** `/Users/functionbytes/Function/Coding/acelle/app/Notifications/`
**Destination:** `modules/Mailing/app/Notifications/`

## Summary

EOF

# Count files
TOTAL_FILES=$(find "$ACELLE_NOTIFICATIONS" -type f -name "*.php" | wc -l | tr -d ' ')
echo -e "${YELLOW}Found $TOTAL_FILES notification files to migrate${NC}"
echo ""

# Array to store notification names
declare -a NOTIFICATIONS=()

# Process each notification file
find "$ACELLE_NOTIFICATIONS" -type f -name "*.php" | while read -r file; do
    filename=$(basename "$file")
    notification_name="${filename%.php}"

    echo -e "${YELLOW}Processing: $notification_name${NC}"

    # Read the file content
    content=$(cat "$file")

    # Update namespace
    content=$(echo "$content" | sed 's/namespace App\\Notifications;/namespace Modules\\Mailing\\Notifications;/')

    # Update model imports
    content=$(echo "$content" | sed 's/use App\\Models\\/use Modules\\Mailing\\Models\\/')

    # Update view paths
    content=$(echo "$content" | sed "s/->view('emails\\./->view('mailing::emails./g")
    content=$(echo "$content" | sed 's/->view("emails\./->view("mailing::emails./g')

    # Update route helpers (basic patterns)
    content=$(echo "$content" | sed "s/url('\\//route('mailing./g")

    # Write to destination
    echo "$content" > "$MAILING_NOTIFICATIONS/$filename"

    # Store notification name
    NOTIFICATIONS+=("$notification_name")

    echo -e "${GREEN}✓ Migrated: $filename${NC}"
done

# Generate report details
echo "" >> "$REPORT_FILE"
echo "**Total Notifications Migrated:** $TOTAL_FILES" >> "$REPORT_FILE"
echo "" >> "$REPORT_FILE"
echo "## Migrated Notifications" >> "$REPORT_FILE"
echo "" >> "$REPORT_FILE"

# List all notifications
for notification in "${NOTIFICATIONS[@]}"; do
    echo "- \`$notification\`" >> "$REPORT_FILE"
done

# Add migration notes
cat >> "$REPORT_FILE" << 'EOF'

## Migration Changes Applied

### 1. Namespace Updates
- Changed from `namespace App\Notifications;`
- To `namespace Modules\Mailing\Notifications;`

### 2. Model Imports
- Updated all `use App\Models\*` references
- To `use Modules\Mailing\Models\*`

### 3. View Paths
- Updated all `->view('emails.*')` references
- To `->view('mailing::emails.*')`

### 4. Route References
- Updated basic `url()` patterns to `route()` helpers
- May require manual verification for complex routes

## Post-Migration Tasks

### Required Actions

- [ ] **Verify all model imports** - Ensure all referenced models exist in Mailing module
- [ ] **Check view files** - Verify all email view files exist at correct paths
- [ ] **Test notification delivery** - Send test notifications for each type
- [ ] **Update route references** - Manually verify complex route patterns
- [ ] **Review notification channels** - Verify mail, database, slack configurations
- [ ] **Update service providers** - Register notifications if needed
- [ ] **Create email views** - Migrate corresponding email templates
- [ ] **Update queue configuration** - Configure queued notifications
- [ ] **Run tests** - Create and run notification tests

### Files Requiring Manual Review

Check these patterns in migrated files:

1. **Custom Route Helpers**
   - Search for `url()` usage that may need named routes
   - Verify any `route()` calls use correct route names

2. **External Dependencies**
   - Check for any third-party notification channels
   - Verify Slack webhook configurations
   - Review any custom notification channels

3. **Database Columns**
   - Review `toArray()` methods for database notifications
   - Ensure notification table schema supports all data

4. **Queued Notifications**
   - Verify `ShouldQueue` interface usage
   - Check queue connection configurations
   - Review retry and timeout settings

## Testing Strategy

### 1. Unit Tests
Create unit tests for each notification:

```php
namespace Modules\Mailing\Tests\Unit\Notifications;

use Tests\TestCase;
use Illuminate\Support\Facades\Notification;

class NotificationNameTest extends TestCase
{
    public function test_notification_can_be_sent()
    {
        Notification::fake();

        // Test notification sending
    }
}
```

### 2. Integration Tests
Test notification delivery in real scenarios:
- Campaign completion triggers notification
- Subscriber actions trigger notifications
- System events trigger admin notifications

### 3. Manual Testing
1. Send test notifications via Tinker
2. Verify email delivery
3. Check database notification records
4. Review notification formatting

## Known Issues

Document any issues discovered during migration:

- [ ] Issue 1: Description
- [ ] Issue 2: Description

## Migration Completion Checklist

- [ ] All notification files migrated
- [ ] Namespaces updated
- [ ] Model imports corrected
- [ ] View paths updated
- [ ] Routes verified
- [ ] Tests created
- [ ] Email views migrated
- [ ] Documentation updated
- [ ] Code review completed
- [ ] Deployed to staging
- [ ] Production deployment approved

## Notes

Add any additional notes, gotchas, or important observations here.

---

**Migration completed by:** Claude Code Agent
**Review required:** Yes
**Approved by:** _Pending_
**Date:** $(date +"%Y-%m-%d")
EOF

echo ""
echo -e "${GREEN}=== Migration Complete ===${NC}"
echo -e "${GREEN}Report generated at: $REPORT_FILE${NC}"
echo ""
echo -e "${YELLOW}Next steps:${NC}"
echo "1. Review migrated files in: $MAILING_NOTIFICATIONS"
echo "2. Check migration report: $REPORT_FILE"
echo "3. Verify model imports and view paths"
echo "4. Create tests for notifications"
echo "5. Migrate corresponding email views"
echo ""
