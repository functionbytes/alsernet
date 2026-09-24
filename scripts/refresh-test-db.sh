#!/usr/bin/env bash
# Crea (o rehace) la base de datos de test aislada `webadmin_test` como copia
# de `webadmin`: esquema completo + datos de referencia, sin las tablas de
# logs/telemetría (Telescope, Pulse, colas, notificaciones, activity_log…).
#
# Por qué (24-sep-2026): los tests corren contra la base real con
# transacciones, y varios caminos se escapan de ellas (conexiones que no
# comparten PDO, FULLTEXT, caché en Redis, suites que vacían tablas). Con una
# copia, lo peor que puede pasar es tener que volver a lanzar este script.
#
# Uso:
#   scripts/refresh-test-db.sh                       # desde el host (Herd)
#   DB_DATABASE=webadmin_test php artisan test …     # tests contra la copia
#   docker exec -e DB_DATABASE=webadmin_test webadmin-app php artisan test …
#
# Las tres conexiones (mysql, mariadb, helpdesk) leen DB_DATABASE, así que
# basta con esa variable. En Docker, `-e` gana a phpunit.xml igual que el
# resto del entorno del contenedor (ver phpunit.xml).
set -euo pipefail

cd "$(dirname "$0")/.."

SOURCE_DB="${SOURCE_DB:-webadmin}"
TARGET_DB="${TARGET_DB:-webadmin_test}"
HOST="${TEST_DB_HOST:-127.0.0.1}"
PORT="$(grep -E '^DB_PORT=' .env | cut -d= -f2- || true)"
PORT="${PORT:-3306}"
USER_NAME="$(grep -E '^DB_USERNAME=' .env | cut -d= -f2-)"
export MYSQL_PWD="$(grep -E '^DB_PASSWORD=' .env | cut -d= -f2- | sed -e 's/^"//' -e 's/"$//')"

if [[ "$TARGET_DB" == "$SOURCE_DB" ]]; then
    echo "TARGET_DB no puede ser la base real ($SOURCE_DB)." >&2
    exit 1
fi

# Tablas cuyo contenido no hace falta para ningún test: solo se copia su
# estructura. Son las que hacen que la base real pese 1,7 GB.
NO_DATA_TABLES=(
    telescope_entries telescope_entries_tags telescope_monitoring
    pulse_entries pulse_aggregates pulse_values
    notifications activity_log email_logs
    jobs failed_jobs job_batches sessions cache cache_locks
)

CONN=(-h "$HOST" -P "$PORT" -u "$USER_NAME")

existing_no_data=()
for t in "${NO_DATA_TABLES[@]}"; do
    if [[ -n "$(mysql "${CONN[@]}" -N -e "SELECT 1 FROM information_schema.tables WHERE table_schema='$SOURCE_DB' AND table_name='$t'")" ]]; then
        existing_no_data+=("$t")
    fi
done

ignore_args=()
for t in "${existing_no_data[@]}"; do
    ignore_args+=("--ignore-table=$SOURCE_DB.$t")
done

echo "→ Recreando $TARGET_DB en $HOST:$PORT"
mysql "${CONN[@]}" -e "DROP DATABASE IF EXISTS \`$TARGET_DB\`; CREATE DATABASE \`$TARGET_DB\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

echo "→ Esquema + datos (sin ${#existing_no_data[@]} tablas de logs)"
mysqldump "${CONN[@]}" --single-transaction --quick --routines --triggers --no-tablespaces \
    "${ignore_args[@]}" "$SOURCE_DB" 2>/dev/null \
    | mysql "${CONN[@]}" "$TARGET_DB"

if (( ${#existing_no_data[@]} )); then
    echo "→ Solo estructura: ${existing_no_data[*]}"
    mysqldump "${CONN[@]}" --no-data --no-tablespaces \
        "$SOURCE_DB" "${existing_no_data[@]}" 2>/dev/null \
        | mysql "${CONN[@]}" "$TARGET_DB"
fi

# Salidas neutralizadas en la copia: el mailer sale de la fila
# settings.mail_mailer (no del .env), así que sin esto un test sin Mail::fake()
# mandaría correo de verdad por el SMTP configurado.
mysql "${CONN[@]}" "$TARGET_DB" -e "UPDATE settings SET value='array' WHERE \`key\`='mail_mailer';" 2>/dev/null || true

tables="$(mysql "${CONN[@]}" -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$TARGET_DB'")"
echo "✓ $TARGET_DB lista ($tables tablas)."
