#!/bin/bash
##############################################################################
# Script de Verificación de Instalación
# Verifica que todos los servicios están funcionando correctamente
#
# Uso: bash /home2/webadminpruebas/web/deployment/VERIFY_INSTALLATION.sh
##############################################################################

set -e

PROJECT_PATH="/home2/webadminpruebas/web"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

PASS=0
FAIL=0
WARN=0

# Functions
print_header() {
    echo -e "${BLUE}========================================${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}========================================${NC}"
}

pass() {
    echo -e "${GREEN}✓ $1${NC}"
    ((PASS++))
}

fail() {
    echo -e "${RED}✗ $1${NC}"
    ((FAIL++))
}

warn() {
    echo -e "${YELLOW}⚠ $1${NC}"
    ((WARN++))
}

info() {
    echo -e "${BLUE}ℹ $1${NC}"
}

print_header "Verificación de Instalación - Laravel Reverdezcámonos"

echo ""
print_header "1. Verificar Web Server (Apache + PHP-FPM)"

# Check Apache
if systemctl is-active --quiet apache2; then
    pass "Apache2 está corriendo"
else
    fail "Apache2 NO está corriendo"
fi

# Check PHP-FPM
if systemctl is-active --quiet php8.4-fpm 2>/dev/null || pgrep -q php-fpm; then
    pass "PHP-FPM está corriendo"
else
    fail "PHP-FPM NO está corriendo"
fi

# Check ports 80 and 443
if netstat -tlnp 2>/dev/null | grep -q ":80 " || ss -tlnp 2>/dev/null | grep -q ":80 "; then
    pass "Puerto 80 (HTTP) está escuchando"
else
    warn "Puerto 80 (HTTP) NO está escuchando"
fi

if netstat -tlnp 2>/dev/null | grep -q ":443 " || ss -tlnp 2>/dev/null | grep -q ":443 "; then
    pass "Puerto 443 (HTTPS) está escuchando"
else
    warn "Puerto 443 (HTTPS) NO está escuchando"
fi

echo ""
print_header "2. Verificar Servicios de Aplicación"

# Check systemd services or supervisor
USE_SYSTEMD=false
USE_SUPERVISOR=false

# Check systemd
if systemctl list-unit-files | grep -q "reverb.service"; then
    USE_SYSTEMD=true
fi

# Check Supervisor
if command -v supervisorctl &> /dev/null; then
    if supervisorctl status laravel-reverb 2>/dev/null | grep -q "laravel-reverb"; then
        USE_SUPERVISOR=true
    fi
fi

if [ "$USE_SYSTEMD" = true ]; then
    echo "Modo: systemd"
    echo ""

    # Check Reverb
    if systemctl is-active --quiet reverb.service; then
        pass "Reverb WebSocket Server está corriendo"
    else
        fail "Reverb WebSocket Server NO está corriendo"
    fi

    # Check Queue Worker
    if systemctl is-active --quiet queue-worker.service; then
        pass "Queue Worker está corriendo"
    else
        fail "Queue Worker NO está corriendo"
    fi

    # Check Scheduler
    if systemctl is-active --quiet scheduler.service; then
        pass "Scheduler está corriendo"
    else
        fail "Scheduler NO está corriendo"
    fi

    # Check port 8080 (Reverb)
    if netstat -tlnp 2>/dev/null | grep -q ":8080 " || ss -tlnp 2>/dev/null | grep -q ":8080 "; then
        pass "Puerto 8080 (Reverb) está escuchando"
    else
        fail "Puerto 8080 (Reverb) NO está escuchando"
    fi

elif [ "$USE_SUPERVISOR" = true ]; then
    echo "Modo: Supervisor"
    echo ""

    # Check Supervisor
    if systemctl is-active --quiet supervisor; then
        pass "Supervisor está corriendo"
    else
        fail "Supervisor NO está corriendo"
    fi

    # Check processes
    if supervisorctl status laravel-reverb 2>/dev/null | grep -q "RUNNING"; then
        pass "Reverb WebSocket Server está corriendo"
    else
        fail "Reverb WebSocket Server NO está corriendo"
    fi

    if supervisorctl status laravel-queue-worker 2>/dev/null | grep -q "RUNNING"; then
        pass "Queue Worker está corriendo"
    else
        fail "Queue Worker NO está corriendo"
    fi

    if supervisorctl status laravel-scheduler 2>/dev/null | grep -q "RUNNING"; then
        pass "Scheduler está corriendo"
    else
        fail "Scheduler NO está corriendo"
    fi

    # Check port 8080
    if netstat -tlnp 2>/dev/null | grep -q ":8080 " || ss -tlnp 2>/dev/null | grep -q ":8080 "; then
        pass "Puerto 8080 (Reverb) está escuchando"
    else
        fail "Puerto 8080 (Reverb) NO está escuchando"
    fi

else
    warn "Servicios no están configurados con systemd o Supervisor"
fi

echo ""
print_header "3. Verificar Configuración"

# Check .env file
if [ -f "$PROJECT_PATH/.env" ]; then
    pass "Archivo .env existe"
else
    fail "Archivo .env NO existe"
fi

# Check BROADCAST_CONNECTION
if grep -q "BROADCAST_CONNECTION=reverb" "$PROJECT_PATH/.env"; then
    pass "BROADCAST_CONNECTION está configurado a 'reverb'"
else
    warn "BROADCAST_CONNECTION no está configurado a 'reverb'"
fi

# Check QUEUE_CONNECTION
if grep -q "QUEUE_CONNECTION=database" "$PROJECT_PATH/.env"; then
    pass "QUEUE_CONNECTION está configurado a 'database'"
else
    warn "QUEUE_CONNECTION no está configurado a 'database'"
fi

# Check REVERB variables
if grep -q "REVERB_APP_KEY" "$PROJECT_PATH/.env" && \
   grep -q "REVERB_APP_SECRET" "$PROJECT_PATH/.env" && \
   grep -q "REVERB_HOST" "$PROJECT_PATH/.env"; then
    pass "Variables de Reverb están configuradas"
else
    fail "Variables de Reverb NO están completamente configuradas"
fi

echo ""
print_header "4. Verificar Base de Datos"

# Check database connection
cd "$PROJECT_PATH"
php artisan tinker <<'EOF' > /tmp/db_check.log 2>&1
try {
    DB::connection()->getPdo();
    echo "OK";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
exit;
EOF

if grep -q "^OK$" /tmp/db_check.log; then
    pass "Conexión a base de datos OK"
else
    fail "Error de conexión a base de datos"
    cat /tmp/db_check.log
fi
rm -f /tmp/db_check.log

# Check if migration table exists
php artisan tinker <<'EOF' > /tmp/migrations_check.log 2>&1
try {
    $count = DB::table('migrations')->count();
    echo "OK";
} catch (Exception $e) {
    echo "ERROR";
}
exit;
EOF

if grep -q "^OK$" /tmp/migrations_check.log; then
    pass "Tabla de migraciones existe"
else
    warn "Tabla de migraciones podría no existir"
fi
rm -f /tmp/migrations_check.log

echo ""
print_header "5. Verificar Sistema de Archivos"

# Check storage directory
if [ -d "$PROJECT_PATH/storage" ]; then
    pass "Directorio storage existe"
else
    fail "Directorio storage NO existe"
fi

# Check logs directory permissions
if [ -d "$PROJECT_PATH/storage/logs" ] && [ -w "$PROJECT_PATH/storage/logs" ]; then
    pass "Directorio storage/logs es escribible"
else
    warn "Directorio storage/logs podría no ser escribible"
fi

# Check /var/log/laravel
if [ -d "/var/log/laravel" ]; then
    pass "Directorio /var/log/laravel existe"
else
    warn "Directorio /var/log/laravel NO existe"
fi

# Check permissions
if [ -d "/var/log/laravel" ]; then
    if [ -w "/var/log/laravel" ]; then
        pass "Directorio /var/log/laravel es escribible"
    else
        fail "Directorio /var/log/laravel NO es escribible"
    fi
fi

echo ""
print_header "6. Verificar Scripts y Configuraciones"

# Check scheduler script
if [ -f "$PROJECT_PATH/deployment/scripts/scheduler.sh" ]; then
    pass "Script de scheduler existe"
    if [ -x "$PROJECT_PATH/deployment/scripts/scheduler.sh" ]; then
        pass "Script de scheduler es ejecutable"
    else
        fail "Script de scheduler NO es ejecutable"
    fi
else
    fail "Script de scheduler NO existe"
fi

# Check systemd services
if [ -d "/etc/systemd/system" ]; then
    if [ -f "/etc/systemd/system/reverb.service" ]; then
        pass "Archivo reverb.service está en systemd"
    else
        info "Archivo reverb.service no está en systemd (podría usar Supervisor)"
    fi
fi

echo ""
print_header "7. Verificar Procesos Ejecutándose"

# Check for running PHP processes
if pgrep -f "artisan reverb" > /dev/null; then
    pass "Proceso de Reverb está ejecutándose"
else
    warn "Proceso de Reverb NO detectado (podría estar en error)"
fi

if pgrep -f "queue:work" > /dev/null; then
    pass "Proceso de Queue está ejecutándose"
else
    warn "Proceso de Queue NO detectado (podría estar en error)"
fi

# Check for scheduler process (menos confiable)
if [ "$USE_SYSTEMD" = true ] && systemctl is-active --quiet scheduler.service; then
    pass "Servicio de Scheduler está activo"
elif [ "$USE_SUPERVISOR" = true ] && supervisorctl status laravel-scheduler 2>/dev/null | grep -q "RUNNING"; then
    pass "Proceso de Scheduler está ejecutándose en Supervisor"
else
    info "No se pudo verificar el scheduler (ejecución cada minuto)"
fi

echo ""
print_header "Resumen de Verificación"

TOTAL=$((PASS + FAIL + WARN))
echo "Total de verificaciones: $TOTAL"
echo -e "  ${GREEN}Exitosas: $PASS${NC}"
echo -e "  ${RED}Fallos: $FAIL${NC}"
echo -e "  ${YELLOW}Advertencias: $WARN${NC}"

if [ $FAIL -eq 0 ]; then
    echo ""
    echo -e "${GREEN}✓ Todas las verificaciones críticas pasaron${NC}"
    exit 0
else
    echo ""
    echo -e "${RED}✗ Hay fallos que deben solucionarse${NC}"
    exit 1
fi
