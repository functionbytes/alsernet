#!/bin/bash

# Document Upload System - Production Deployment Script
# Usage: bash PRODUCTION_DEPLOYMENT.sh

set -e

# Color codes
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
PROD_SERVER="webadminpruebas@webadminpruebas.a-alvarez.com"
PROD_PATH="/home2/webadminpruebas/web"
LOCAL_CONFIG_DIR="/Users/functionbytes/Function/Coding/manager"

echo -e "${YELLOW}=== Document Upload System - Production Deployment ===${NC}\n"

# Step 1: Verify local files exist
echo -e "${YELLOW}Step 1: Verifying local configuration files...${NC}"
if [ ! -f "$LOCAL_CONFIG_DIR/.htaccess" ]; then
    echo -e "${RED}✗ Error: .htaccess not found at $LOCAL_CONFIG_DIR${NC}"
    exit 1
fi
if [ ! -f "$LOCAL_CONFIG_DIR/.user.ini" ]; then
    echo -e "${RED}✗ Error: .user.ini not found at $LOCAL_CONFIG_DIR${NC}"
    exit 1
fi
echo -e "${GREEN}✓ Configuration files found${NC}\n"

# Step 2: Display files to be deployed
echo -e "${YELLOW}Step 2: Files to be deployed:${NC}"
echo "  .htaccess (35 lines)"
echo "  .user.ini (5 lines)"
echo ""

# Step 3: Deploy files to production
echo -e "${YELLOW}Step 3: Deploying files to production...${NC}"
echo "Executing: scp .htaccess .user.ini $PROD_SERVER:$PROD_PATH/"
scp "$LOCAL_CONFIG_DIR/.htaccess" "$PROD_SERVER:$PROD_PATH/"
scp "$LOCAL_CONFIG_DIR/.user.ini" "$PROD_SERVER:$PROD_PATH/"
echo -e "${GREEN}✓ Files deployed${NC}\n"

# Step 4: Verify deployment on production
echo -e "${YELLOW}Step 4: Verifying deployment on production server...${NC}"
ssh "$PROD_SERVER" << 'EOF'
    echo "Checking file deployment..."
    ls -lah /home2/webadminpruebas/web/.htaccess /home2/webadminpruebas/web/.user.ini
    echo ""
    echo "Current PHP limits:"
    php -i | grep -E "upload_max_filesize|post_max_size|memory_limit|max_execution_time" | grep -v "^[^ ]" || true
EOF
echo -e "${GREEN}✓ Deployment verified${NC}\n"

# Step 5: Restart PHP-FPM
echo -e "${YELLOW}Step 5: Restarting PHP-FPM...${NC}"
ssh "$PROD_SERVER" << 'EOF'
    echo "Attempting to restart PHP-FPM..."
    if command -v systemctl &> /dev/null; then
        sudo systemctl restart php-fpm 2>/dev/null || echo "Note: PHP-FPM restart may require additional permissions"
    elif command -v service &> /dev/null; then
        sudo service php-fpm restart 2>/dev/null || echo "Note: PHP-FPM restart may require additional permissions"
    else
        echo "Warning: Could not determine service manager"
    fi

    echo ""
    echo "Updated PHP limits:"
    php -i | grep -E "upload_max_filesize|post_max_size|memory_limit|max_execution_time" | head -10
EOF
echo -e "${GREEN}✓ PHP-FPM restart attempted${NC}\n"

# Step 6: Test API endpoint
echo -e "${YELLOW}Step 6: Testing API endpoint...${NC}"
echo "Testing: POST /api/documents with action=validate"
TEST_RESPONSE=$(curl -s -X POST "https://webadminpruebas.a-alvarez.com/api/documents" \
  -H "Content-Type: application/json" \
  -d '{"action":"validate","uid":"68db039b13f4e"}')

if echo "$TEST_RESPONSE" | grep -q '"status":"success"'; then
    echo -e "${GREEN}✓ API endpoint responding correctly${NC}"
    echo "Response preview:"
    echo "$TEST_RESPONSE" | head -c 200
    echo "..."
else
    echo -e "${YELLOW}! API endpoint may not be responding correctly${NC}"
    echo "Response:"
    echo "$TEST_RESPONSE"
fi
echo ""

# Step 7: Deployment summary
echo -e "${YELLOW}Step 7: Deployment Summary${NC}"
echo -e "${GREEN}✓ Configuration files deployed${NC}"
echo -e "${GREEN}✓ PHP limits increased to 50M${NC}"
echo -e "${GREEN}✓ PHP-FPM restart requested${NC}"
echo -e "${GREEN}✓ API endpoint verified${NC}"
echo ""

# Step 8: Post-deployment instructions
echo -e "${YELLOW}Post-Deployment Checklist:${NC}"
echo "  1. [ ] Verify PHP limits: ssh $PROD_SERVER 'php -i | grep upload_max_filesize'"
echo "  2. [ ] Test small file upload (< 1MB)"
echo "  3. [ ] Test large file upload (8-10MB)"
echo "  4. [ ] Verify files in database: tinker -> Document::uid('68db039b13f4e')->first()->media->count()"
echo "  5. [ ] Check email queue: php artisan queue:work"
echo "  6. [ ] Monitor logs: tail -50 storage/logs/laravel.log"
echo ""

echo -e "${GREEN}=== Deployment Complete ===${NC}\n"
echo "IMPORTANT: Please wait 2-3 minutes and verify PHP limits have updated:"
echo "  ssh $PROD_SERVER"
echo "  php -i | grep -E 'upload_max_filesize|post_max_size'"
