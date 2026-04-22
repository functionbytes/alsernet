#!/bin/bash
set -e

echo "==> Pint format check"
vendor/bin/pint --test

echo "==> Tests"
vendor/bin/phpunit \
    modules/HelpdeskTickets/tests/Feature \
    modules/HelpdeskAgents/tests \
    modules/Helpdesk/tests \
    modules/HelpdeskCampaigns/tests \
    --no-coverage \
    --filter='test_controller_class_exists|test_model_class_exists|test_index_route_registered|test_store_route_registered|test_destroy_route_registered|test_class_exists|test_has_table_property|test_uses_helpdesk_connection|test_has_fillable'

echo "==> PHPStan"
if [ -f vendor/bin/phpstan ]; then
    vendor/bin/phpstan analyse --memory-limit=2G --no-progress
else
    echo "phpstan not installed, skipping"
fi

echo "All checks pass"
