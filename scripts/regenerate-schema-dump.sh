#!/usr/bin/env bash
# Regenerate database/schema/mariadb-schema.sql from a fresh migration state.
# Usage: bash scripts/regenerate-schema-dump.sh
set -e

cd "$(dirname "$0")/.."

DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3306}"
DB_USER="${DB_USERNAME:-root}"
DB_PASS="${DB_PASSWORD:-}"
TMP_DB="${SCHEMA_DUMP_DB:-system_schema_dump_tmp}"

echo "==> Recreating temp DB: $TMP_DB"
mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASS" -e "DROP DATABASE IF EXISTS \`$TMP_DB\`; CREATE DATABASE \`$TMP_DB\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

echo "==> Running migrate:fresh against temp DB"
DB_DATABASE="$TMP_DB" APP_ENV=local php artisan migrate:fresh --force --no-interaction

echo "==> Dumping schema (no data)"
mkdir -p database/schema
mysqldump -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASS" \
  --no-data --routines=false --triggers=false --add-drop-table --skip-comments "$TMP_DB" > /tmp/schema-only.sql

echo "==> Dumping migrations data"
mysqldump -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASS" \
  --no-create-info --skip-comments --insert-ignore "$TMP_DB" migrations > /tmp/migrations-data.sql

cat /tmp/schema-only.sql /tmp/migrations-data.sql > database/schema/mariadb-schema.sql
echo "==> Schema dump regenerated: database/schema/mariadb-schema.sql ($(wc -l < database/schema/mariadb-schema.sql) lines)"

echo "==> Cleaning up temp DB"
mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASS" -e "DROP DATABASE \`$TMP_DB\`;"

echo "Done."
