#!/usr/bin/env bash

# ===========================================================================
# run-contract-e2e.sh — contrato E2E tienda (bridge stub) <-> panel helpdesk
#
# Fases:
#   1. Levanta el compose (db + bridge + panel + panel-nginx) y espera health.
#   2. Migra la BD del panel (php artisan migrate --force).
#   3. Ejecuta la suite del módulo: modules/alsernetbridge/tests/run-e2e.sh
#      contra el bridge stub (HMAC, cache, idempotencia, health, metrics).
#   4. Webhook bridge -> panel con el código real del módulo
#      (AlsernetWebhookSender) + verificación de firma y anti-replay del
#      middleware VerifyAlsernetHmac (2ª entrega idéntica => 401).
#   5. Pull panel -> bridge: php artisan helpdeskprestashop:test-connection.
#
# Uso:
#   ./run-contract-e2e.sh            # levanta, prueba y deja el entorno arriba
#   ./run-contract-e2e.sh --down     # además hace docker compose down -v al final
#
# Variables (defaults = fixtures del compose):
#   ALSERNETBRIDGE_WEBHOOK_SECRET, ALSERNETBRIDGE_OPS_SECRET,
#   BRIDGE_URL, PANEL_WEBHOOK_URL, ALVAREZ_SRC
#
# Exit code: 0 si todas las fases pasan; 1 si alguna falla; 2 error de entorno.
# ===========================================================================

set -u
cd "$(dirname "$0")"

COMPOSE="docker compose"
TEARDOWN=0
[ "${1:-}" = "--down" ] && TEARDOWN=1

# Secrets — deben coincidir con fixtures/20-bridge-data.sql y panel.env
SECRET="${ALSERNETBRIDGE_WEBHOOK_SECRET:-e2e5ecb2f4b4d0f9a3c1d2e3f405162738495a6b7c8d9e0f1a2b3c4d5e6f7081}"
OPS_SECRET="${ALSERNETBRIDGE_OPS_SECRET:-op5e2e1b2c3d4e5f60718293a4b5c6d7e8f900fedcba98765432112345678900}"

BRIDGE_URL="${BRIDGE_URL:-http://localhost:8093/modules/alsernetbridge/api.php}"
PANEL_WEBHOOK_URL="${PANEL_WEBHOOK_URL:-http://localhost:8094/api/helpdeskprestashop/webhooks/event}"
ALVAREZ_SRC="${ALVAREZ_SRC:-$(cd ../../../alvarez/src 2>/dev/null && pwd || true)}"

FAILED=0
declare -a SUMMARY=()

step() { printf '\n\033[1m== %s ==\033[0m\n' "$1"; }
ok()   { echo "  [OK]   $1"; SUMMARY+=("OK   $1"); }
ko()   { echo "  [FAIL] $1"; SUMMARY+=("FAIL $1"); FAILED=1; }

http_code() { curl -s -o "${2:-/dev/null}" -w '%{http_code}' "${@:3}" "$1"; }

wait_for() { # wait_for <descripcion> <intentos> <cmd...>
    local desc="$1" tries="$2"; shift 2
    for _ in $(seq 1 "$tries"); do
        if "$@" >/dev/null 2>&1; then return 0; fi
        sleep 2
    done
    echo "  timeout esperando: $desc"
    return 1
}

# ---------------------------------------------------------------------------
# Fase 0 — prerequisitos
# ---------------------------------------------------------------------------
step "Prerequisitos"

if ! docker info >/dev/null 2>&1; then
    echo "Docker no está disponible en esta máquina. Nada que ejecutar."
    exit 2
fi

if [ -z "$ALVAREZ_SRC" ] || [ ! -f "$ALVAREZ_SRC/modules/alsernetbridge/tests/run-e2e.sh" ]; then
    echo "No se encuentra el repo de la tienda (esperado en ../../../alvarez/src)."
    echo "Define ALVAREZ_SRC=/ruta/a/alvarez/src y reintenta."
    exit 2
fi
ok "docker disponible y módulo localizado en $ALVAREZ_SRC"

# ---------------------------------------------------------------------------
# Fase 1 — levantar entorno
# ---------------------------------------------------------------------------
step "Levantando entorno (db + bridge + panel)"

if ! $COMPOSE up -d --build db bridge; then
    echo "docker compose up (db/bridge) falló"; exit 2
fi
if ! $COMPOSE up -d panel panel-nginx; then
    echo "docker compose up (panel) falló"; exit 2
fi

bridge_healthy() {
    [ "$(http_code "http://localhost:8093/modules/alsernetbridge/health.php")" = "200" ]
}
if wait_for "bridge health.php == 200" 45 bridge_healthy; then
    ok "bridge stub sano (health.php -> 200)"
else
    ko "bridge no llegó a estado sano"; $COMPOSE logs --tail 30 bridge; fi

# ---------------------------------------------------------------------------
# Fase 2 — migraciones del panel
# ---------------------------------------------------------------------------
step "Migraciones del panel"

migrate() { $COMPOSE exec -T panel php artisan migrate --force --no-interaction; }
if wait_for "conexión BD del panel" 15 $COMPOSE exec -T panel php artisan db:show --json \
    && migrate >/tmp/e2e-migrate.log 2>&1; then
    ok "php artisan migrate --force"
else
    ko "migraciones del panel (ver /tmp/e2e-migrate.log)"
    tail -20 /tmp/e2e-migrate.log 2>/dev/null
fi

panel_route_live() {
    # Sin firma debe responder 401 (middleware activo) — no 404/502
    [ "$(http_code "$PANEL_WEBHOOK_URL" /dev/null -X POST -H 'Content-Type: application/json' -d '{}')" = "401" ]
}
if wait_for "ruta webhook del panel viva (401 sin firma)" 30 panel_route_live; then
    ok "POST sin firma -> 401 (VerifyAlsernetHmac activo)"
else
    code=$(http_code "$PANEL_WEBHOOK_URL" /dev/null -X POST -d '{}')
    ko "ruta webhook del panel no responde 401 (respondió $code)"
fi

# ---------------------------------------------------------------------------
# Fase 3 — suite E2E del bridge (contrato panel->tienda a nivel HTTP)
# ---------------------------------------------------------------------------
step "Suite E2E del módulo alsernetbridge"

if ALSERNETBRIDGE_API_URL="$BRIDGE_URL" \
   ALSERNETBRIDGE_WEBHOOK_SECRET="$SECRET" \
   ALSERNETBRIDGE_OPS_SECRET="$OPS_SECRET" \
   TEST_EMAIL="test@test.com" \
   bash "$ALVAREZ_SRC/modules/alsernetbridge/tests/run-e2e.sh"; then
    ok "tests/run-e2e.sh del bridge"
else
    ko "tests/run-e2e.sh del bridge"
fi

# ---------------------------------------------------------------------------
# Fase 4 — webhook bridge -> panel
# ---------------------------------------------------------------------------
step "Webhook bridge -> panel (código real del módulo)"

if $COMPOSE exec -T bridge php /e2e/send-webhook.php order.created; then
    ok "AlsernetWebhookSender::sendToRemarketing('order.created') -> 2xx en el panel"
else
    ko "envío de webhook con el código del módulo"
fi

step "Webhook firmado + anti-replay (contrato HMAC)"

TS=$(date +%s)
BODY='{"event":"order.status_changed","data":{"order_id":1001,"new_state":4,"reference":"E2E000001"},"shop_id":1,"timestamp":'"$TS"'}'
SIG=$(printf '%s:%s' "$TS" "$BODY" | openssl dgst -sha256 -hmac "$SECRET" | awk '{print $2}')

RESP1=$(mktemp)
CODE1=$(http_code "$PANEL_WEBHOOK_URL" "$RESP1" -X POST \
    -H 'Content-Type: application/json' \
    -H "X-Alsernet-Event: order.status_changed" \
    -H "X-Alsernet-Signature: $SIG" \
    -H "X-Alsernet-Timestamp: $TS" \
    -d "$BODY")
if [ "$CODE1" = "200" ] && grep -q '"ok":true' "$RESP1"; then
    ok "1ª entrega firmada -> 200 {\"ok\":true}"
else
    ko "1ª entrega firmada (HTTP $CODE1: $(head -c 120 "$RESP1"))"
fi

CODE2=$(http_code "$PANEL_WEBHOOK_URL" /dev/null -X POST \
    -H 'Content-Type: application/json' \
    -H "X-Alsernet-Event: order.status_changed" \
    -H "X-Alsernet-Signature: $SIG" \
    -H "X-Alsernet-Timestamp: $TS" \
    -d "$BODY")
if [ "$CODE2" = "401" ]; then
    ok "replay de la misma firma -> 401 (anti-replay del middleware, efecto en cache verificado)"
else
    ko "replay debería dar 401 y dio $CODE2"
fi
rm -f "$RESP1"

# ---------------------------------------------------------------------------
# Fase 5 — pull panel -> bridge
# ---------------------------------------------------------------------------
step "Pull panel -> bridge (helpdeskprestashop:test-connection)"

if $COMPOSE exec -T panel php artisan helpdeskprestashop:test-connection; then
    ok "panel firma y consulta api.php del bridge (PrestashopContextService)"
else
    ko "pull del panel al bridge"
fi

# ---------------------------------------------------------------------------
# Resumen
# ---------------------------------------------------------------------------
step "Resumen"
for line in "${SUMMARY[@]}"; do echo "  $line"; done

if [ "$TEARDOWN" = "1" ]; then
    echo
    $COMPOSE down -v
fi

if [ "$FAILED" -eq 0 ]; then
    printf '\n\033[32mCONTRATO E2E: OK\033[0m\n'
else
    printf '\n\033[31mCONTRATO E2E: FALLOS\033[0m\n'
fi
exit "$FAILED"
