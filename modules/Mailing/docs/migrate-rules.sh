#!/bin/bash

################################################################################
# Custom Validation Rules Migration Script
#
# Purpose: Automated migration of Acelle validation rules to Mailing module
# Usage: ./migrate-rules.sh
################################################################################

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Directories
ACELLE_RULES_DIR="/Users/functionbytes/Function/Coding/acelle/app/Rules"
MAILING_RULES_DIR="/Users/functionbytes/Function/Coding/system/modules/Mailing/app/Rules"
MAILING_TESTS_DIR="/Users/functionbytes/Function/Coding/system/modules/Mailing/tests/Feature/Rules"
REPORT_FILE="/Users/functionbytes/Function/Coding/system/modules/Mailing/docs/RULES_MIGRATION_REPORT.md"

echo -e "${BLUE}================================${NC}"
echo -e "${BLUE}Custom Validation Rules Migration${NC}"
echo -e "${BLUE}================================${NC}\n"

# Check if source directory exists
if [ ! -d "$ACELLE_RULES_DIR" ]; then
    echo -e "${RED}Error: Source directory not found: $ACELLE_RULES_DIR${NC}"
    echo -e "${YELLOW}Please verify the Acelle installation path.${NC}"
    exit 1
fi

# Create destination directories
echo -e "${BLUE}Creating destination directories...${NC}"
mkdir -p "$MAILING_RULES_DIR"
mkdir -p "$MAILING_TESTS_DIR"

# Count total rules
TOTAL_RULES=$(find "$ACELLE_RULES_DIR" -name "*.php" -type f | wc -l | tr -d ' ')
echo -e "${GREEN}Found $TOTAL_RULES rule(s) to migrate${NC}\n"

if [ "$TOTAL_RULES" -eq 0 ]; then
    echo -e "${YELLOW}No rules found in source directory.${NC}"
    exit 0
fi

# List all rules
echo -e "${BLUE}Rules to migrate:${NC}"
find "$ACELLE_RULES_DIR" -name "*.php" -type f -exec basename {} \;
echo ""

# Counter for migrated rules
MIGRATED=0
FAILED=0

# Migrate each rule
find "$ACELLE_RULES_DIR" -name "*.php" -type f | while read -r SOURCE_FILE; do
    FILENAME=$(basename "$SOURCE_FILE")
    RULENAME="${FILENAME%.php}"
    DEST_FILE="$MAILING_RULES_DIR/$FILENAME"

    echo -e "${YELLOW}Processing: $RULENAME${NC}"

    # Copy file
    cp "$SOURCE_FILE" "$DEST_FILE"

    # Update namespace
    sed -i '' 's/namespace App\\Rules;/namespace Modules\\Mailing\\Rules;/g' "$DEST_FILE"

    # Update Acelle model references
    sed -i '' 's/\\Acelle\\Model\\/\\Modules\\Mailing\\Models\\/g' "$DEST_FILE"
    sed -i '' 's/use Acelle\\Model\\/use Modules\\Mailing\\Models\\/g' "$DEST_FILE"

    # Add ValidationRule import if not present
    if ! grep -q "use Illuminate\\Contracts\\Validation\\ValidationRule;" "$DEST_FILE"; then
        sed -i '' '/^namespace /a\
\
use Closure;\
use Illuminate\\Contracts\\Validation\\ValidationRule;
' "$DEST_FILE"
    fi

    echo -e "${GREEN}  ✓ Migrated: $FILENAME${NC}"

    # Create test file
    TEST_FILE="$MAILING_TESTS_DIR/${RULENAME}Test.php"

    cat > "$TEST_FILE" << EOF
<?php

namespace Tests\Feature\Mailing\Rules;

use Modules\Mailing\Rules\\${RULENAME};
use Tests\TestCase;
use Illuminate\Support\Facades\Validator;

class ${RULENAME}Test extends TestCase
{
    public function testValidData(): void
    {
        \$validator = Validator::make(
            ['field' => 'valid-value'],
            ['field' => [new ${RULENAME}()]]
        );

        \$this->assertFalse(\$validator->fails());
    }

    public function testInvalidData(): void
    {
        \$validator = Validator::make(
            ['field' => 'invalid-value'],
            ['field' => [new ${RULENAME}()]]
        );

        \$this->assertTrue(\$validator->fails());
    }
}
EOF

    echo -e "${GREEN}  ✓ Created test: ${RULENAME}Test.php${NC}"
    echo ""

    ((MIGRATED++))
done

echo -e "\n${BLUE}================================${NC}"
echo -e "${GREEN}Migration Summary${NC}"
echo -e "${BLUE}================================${NC}"
echo -e "Total rules found:    ${TOTAL_RULES}"
echo -e "Successfully migrated: ${GREEN}${MIGRATED}${NC}"
echo -e "Failed:               ${RED}${FAILED}${NC}"
echo ""

echo -e "${YELLOW}Next Steps:${NC}"
echo "1. Review migrated rules in: $MAILING_RULES_DIR"
echo "2. Update rule implementations to Laravel 12 syntax"
echo "3. Update test files with proper test cases"
echo "4. Run: php artisan test --filter=Rules"
echo "5. Run: vendor/bin/pint to format code"
echo "6. Update Form Requests to use new namespaces"
echo ""

echo -e "${BLUE}Updating migration report...${NC}"
# Note: Report update would be done manually or with a more complex script

echo -e "${GREEN}Migration script completed!${NC}"
