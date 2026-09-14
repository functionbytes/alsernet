#!/bin/bash

################################################################################
# Laravel Route Watcher - Supervisor Setup Script
#
# This script automates the setup of supervisord for the route watcher daemon
# Works in both development and production environments
#
# Usage:
#   ./scripts/setup-supervisor.sh dev    # Setup for development
#   ./scripts/setup-supervisor.sh prod   # Setup for production
#   ./scripts/setup-supervisor.sh both   # Setup for both environments
################################################################################

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Get environment
ENVIRONMENT="${1:-dev}"
LARAVEL_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
SUPERVISOR_CONF_DIR="/etc/supervisor/conf.d"
SUPERVISOR_CONFIG_DIR="$(dirname "${BASH_SOURCE[0]}")/../config/supervisor"

# Check if running as root (required for supervisor setup)
if [[ $EUID -ne 0 ]]; then
   echo -e "${RED}❌ This script must be run as root (use: sudo ./scripts/setup-supervisor.sh)${NC}"
   exit 1
fi

# Check if supervisord is installed
if ! command -v supervisord &> /dev/null; then
    echo -e "${RED}❌ Supervisor is not installed${NC}"
    echo -e "${YELLOW}Install it with:${NC}"
    echo -e "  Ubuntu/Debian: sudo apt-get install supervisor"
    echo -e "  macOS: brew install supervisor"
    echo -e "  CentOS/RHEL: sudo yum install supervisor"
    exit 1
fi

# Function to setup development environment
setup_dev() {
    echo -e "${BLUE}═══════════════════════════════════════════════════════${NC}"
    echo -e "${BLUE}  🔧 Setting up Route Watcher for DEVELOPMENT${NC}"
    echo -e "${BLUE}═══════════════════════════════════════════════════════${NC}\n"

    # Copy configuration
    echo -e "${YELLOW}📋 Copying supervisor configuration...${NC}"
    sudo cp "${SUPERVISOR_CONFIG_DIR}/laravel-route-watcher-dev.conf" \
            "${SUPERVISOR_CONF_DIR}/laravel-route-watcher-dev.conf"

    # Set environment variables in config
    sudo sed -i "s|%(ENV_LARAVEL_ROOT)s|${LARAVEL_ROOT}|g" \
             "${SUPERVISOR_CONF_DIR}/laravel-route-watcher-dev.conf"
    sudo sed -i "s|%(ENV_USER)s|$(whoami)|g" \
             "${SUPERVISOR_CONF_DIR}/laravel-route-watcher-dev.conf"

    echo -e "${GREEN}✅ Configuration copied${NC}\n"

    # Create log directory
    echo -e "${YELLOW}📁 Creating log directories...${NC}"
    mkdir -p "${LARAVEL_ROOT}/storage/logs/supervisor"
    chmod 755 "${LARAVEL_ROOT}/storage/logs/supervisor"
    echo -e "${GREEN}✅ Log directories created${NC}\n"

    # Reload supervisor
    echo -e "${YELLOW}🔄 Reloading supervisor...${NC}"
    sudo supervisorctl reread
    sudo supervisorctl update
    echo -e "${GREEN}✅ Supervisor reloaded${NC}\n"

    # Start the service
    echo -e "${YELLOW}🚀 Starting route watcher daemon...${NC}"
    sudo supervisorctl start laravel-route-watcher-dev
    echo -e "${GREEN}✅ Route watcher started${NC}\n"

    # Show status
    echo -e "${BLUE}📊 Service Status:${NC}"
    sudo supervisorctl status laravel-route-watcher-dev
    echo ""
}

# Function to setup production environment
setup_prod() {
    echo -e "${BLUE}═══════════════════════════════════════════════════════${NC}"
    echo -e "${BLUE}  🔧 Setting up Route Watcher for PRODUCTION${NC}"
    echo -e "${BLUE}═══════════════════════════════════════════════════════${NC}\n"

    # Get web server user
    WEB_USER="www-data"
    if [[ "$OSTYPE" == "darwin"* ]]; then
        WEB_USER="_www"
    fi

    echo -e "${YELLOW}👤 Web server user: ${WEB_USER}${NC}\n"

    # Copy configuration
    echo -e "${YELLOW}📋 Copying supervisor configuration...${NC}"
    sudo cp "${SUPERVISOR_CONFIG_DIR}/laravel-route-watcher-prod.conf" \
            "${SUPERVISOR_CONF_DIR}/laravel-route-watcher-prod.conf"

    # Set environment variables in config
    sudo sed -i "s|%(ENV_LARAVEL_ROOT)s|${LARAVEL_ROOT}|g" \
             "${SUPERVISOR_CONF_DIR}/laravel-route-watcher-prod.conf"
    sudo sed -i "s|www-data|${WEB_USER}|g" \
             "${SUPERVISOR_CONF_DIR}/laravel-route-watcher-prod.conf"

    echo -e "${GREEN}✅ Configuration copied${NC}\n"

    # Create log directory with proper permissions
    echo -e "${YELLOW}📁 Creating log directories...${NC}"
    mkdir -p "${LARAVEL_ROOT}/storage/logs/supervisor"
    chown -R "${WEB_USER}:${WEB_USER}" "${LARAVEL_ROOT}/storage"
    chmod 755 "${LARAVEL_ROOT}/storage/logs/supervisor"
    echo -e "${GREEN}✅ Log directories created with proper permissions${NC}\n"

    # Reload supervisor
    echo -e "${YELLOW}🔄 Reloading supervisor...${NC}"
    sudo supervisorctl reread
    sudo supervisorctl update
    echo -e "${GREEN}✅ Supervisor reloaded${NC}\n"

    # Start the service
    echo -e "${YELLOW}🚀 Starting route watcher daemon...${NC}"
    sudo supervisorctl start laravel-route-watcher-prod
    echo -e "${GREEN}✅ Route watcher started${NC}\n"

    # Show status
    echo -e "${BLUE}📊 Service Status:${NC}"
    sudo supervisorctl status laravel-route-watcher-prod
    echo ""
}

# Setup both environments
setup_both() {
    setup_dev
    setup_prod
}

# Main execution
case "$ENVIRONMENT" in
    dev|development)
        setup_dev
        ;;
    prod|production)
        setup_prod
        ;;
    both|all)
        setup_both
        ;;
    *)
        echo -e "${RED}❌ Invalid environment: $ENVIRONMENT${NC}"
        echo -e "${YELLOW}Usage: sudo ./scripts/setup-supervisor.sh [dev|prod|both]${NC}"
        exit 1
        ;;
esac

# Print final instructions
echo -e "${BLUE}═══════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}✅ Setup Complete!${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════════${NC}\n"

echo -e "${YELLOW}📌 Useful Commands:${NC}\n"
echo -e "  # View all supervisor programs"
echo -e "    sudo supervisorctl status\n"
echo -e "  # View route watcher status"
echo -e "    sudo supervisorctl status laravel-route-watcher-${ENVIRONMENT}\n"
echo -e "  # Start daemon"
echo -e "    sudo supervisorctl start laravel-route-watcher-${ENVIRONMENT}\n"
echo -e "  # Stop daemon"
echo -e "    sudo supervisorctl stop laravel-route-watcher-${ENVIRONMENT}\n"
echo -e "  # Restart daemon"
echo -e "    sudo supervisorctl restart laravel-route-watcher-${ENVIRONMENT}\n"
echo -e "  # View logs"
echo -e "    tail -f storage/logs/supervisor/route-watcher-${ENVIRONMENT}.log\n"
echo -e "  # View errors"
echo -e "    tail -f storage/logs/supervisor/route-watcher-${ENVIRONMENT}-error.log\n"

echo -e "${BLUE}═══════════════════════════════════════════════════════${NC}"
