#!/usr/bin/env bash
# Lanza `php artisan test` contra la copia aislada `webadmin_test` (ver
# scripts/refresh-test-db.sh) y con Redis en otras bases (13 y 14), para que
# ni los datos ni la caché de la aplicación real se toquen.
#
# Desde el host (PHP de Herd), con el mismo entorno que el contenedor:
#   scripts/test-isolated.sh modules/HelpdeskTickets/tests
#   scripts/test-isolated.sh --filter=Escalation modules/HelpdeskTickets/tests
#
# Dentro de Docker:
#   docker exec -e DB_DATABASE=webadmin_test -e REDIS_DB=13 -e REDIS_CACHE_DB=14 \
#       webadmin-app php artisan test …
set -euo pipefail

cd "$(dirname "$0")/.."

export DB_DATABASE="${TEST_DB_DATABASE:-webadmin_test}"
export REDIS_DB="${TEST_REDIS_DB:-13}"
export REDIS_CACHE_DB="${TEST_REDIS_CACHE_DB:-14}"

# Fuera de Docker los nombres de servicio no resuelven: se usan los puertos
# publicados en el host.
if ! getent hosts host.docker.internal >/dev/null 2>&1 && [[ ! -f /.dockerenv ]]; then
    export DB_HOST="${TEST_DB_HOST:-127.0.0.1}"
    export REDIS_HOST="${TEST_REDIS_HOST:-127.0.0.1}"
    export REDIS_PORT="${TEST_REDIS_PORT:-6381}"
    # Mismo entorno que el contenedor: phpunit.xml no gana a getenv() para
    # estas variables (ver la nota en phpunit.xml), así que se replican.
    export DB_CONNECTION=mysql MAIL_MAILER=array QUEUE_CONNECTION=sync CACHE_STORE=redis SESSION_DRIVER=array
fi

exists="$(MYSQL_PWD="$(grep -E '^DB_PASSWORD=' .env | cut -d= -f2-)" \
    mysql -h "${DB_HOST:-127.0.0.1}" -u "$(grep -E '^DB_USERNAME=' .env | cut -d= -f2-)" -N \
    -e "SELECT 1 FROM information_schema.schemata WHERE schema_name='$DB_DATABASE'" 2>/dev/null || true)"
if [[ "$exists" != "1" ]]; then
    echo "No existe la base $DB_DATABASE: lanza antes scripts/refresh-test-db.sh" >&2
    exit 1
fi

# Caché de test limpia en cada pasada: Setting::get() cachea valores (el
# mailer, entre otros) y una pasada anterior contra otra base los dejaría
# puestos. Con REDIS_CACHE_DB apuntando a la base de test, esto no toca la
# caché de la aplicación.
php artisan cache:clear >/dev/null

exec php artisan test "$@"
