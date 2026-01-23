#!/bin/bash
##############################################################################
# Script de Configuración Rápida - Laravel Reverdezcámonos
# Este script configura automáticamente todos los servicios necesarios
#
# Uso: sudo bash /home2/webadminpruebas/web/deployment/QUICK_SETUP.sh
##############################################################################

set -e

PROJECT_PATH="/home2/webadminpruebas/web"
DEPLOYMENT_PATH="$PROJECT_PATH/deployment"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Functions
print_header() {
    echo -e "${BLUE}========================================${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}========================================${NC}"
}

print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

print_info() {
    echo -e "${YELLOW}ℹ $1${NC}"
}

# Check if running as root
if [[ $EUID -ne 0 ]]; then
    print_error "Este script debe ejecutarse con sudo"
    exit 1
fi

print_header "Configuración Automática - Laravel Reverdezcámonos"

# Step 1: Create directories
print_info "Creando directorios necesarios..."
mkdir -p /var/log/laravel
chown www-data:www-data /var/log/laravel
chmod 755 /var/log/laravel
print_success "Directorios creados"

# Step 2: Make scheduler script executable
print_info "Configurando permisos de scripts..."
chmod +x "$DEPLOYMENT_PATH/scripts/scheduler.sh"
print_success "Permisos de scripts configurados"

# Step 3: Ask which option to use
print_header "Seleccionar método de configuración"
echo "1) systemd (RECOMENDADO para producción)"
echo "2) Supervisor (Alternativa)"
read -p "Selecciona opción (1 o 2): " METHOD

case $METHOD in
    1)
        print_header "Instalación con systemd"

        # Copy systemd files
        print_info "Copiando archivos de servicio systemd..."
        cp "$DEPLOYMENT_PATH/systemd"/*.service /etc/systemd/system/
        print_success "Archivos systemd copiados"

        # Reload systemd
        print_info "Recargando configuración de systemd..."
        systemctl daemon-reload
        print_success "systemd recargado"

        # Enable services
        print_info "Habilitando servicios..."
        systemctl enable reverb.service
        systemctl enable queue-worker.service
        systemctl enable scheduler.service
        print_success "Servicios habilitados"

        # Start services
        print_info "Iniciando servicios..."
        systemctl start reverb.service
        sleep 2
        systemctl start queue-worker.service
        sleep 2
        systemctl start scheduler.service
        sleep 2
        print_success "Servicios iniciados"

        # Verify status
        print_header "Estado de los servicios"
        systemctl status reverb.service || true
        echo ""
        systemctl status queue-worker.service || true
        echo ""
        systemctl status scheduler.service || true

        print_success "Instalación con systemd completada"
        print_info "Comandos útiles:"
        echo "  Ver logs en tiempo real: sudo journalctl -u reverb.service -f"
        echo "  Estado de servicios: sudo systemctl status reverb.service"
        echo "  Reiniciar servicio: sudo systemctl restart queue-worker.service"
        ;;

    2)
        print_header "Instalación con Supervisor"

        # Check if supervisor is installed
        if ! command -v supervisorctl &> /dev/null; then
            print_info "Supervisor no está instalado. Instalando..."
            apt-get update
            apt-get install -y supervisor
            print_success "Supervisor instalado"
        fi

        # Copy supervisor config files
        print_info "Copiando archivos de configuración Supervisor..."
        cp "$DEPLOYMENT_PATH/supervisor"/*.conf /etc/supervisor/conf.d/
        print_success "Archivos Supervisor copiados"

        # Reload supervisor
        print_info "Actualizando configuración de Supervisor..."
        supervisorctl reread
        supervisorctl update
        print_success "Supervisor actualizado"

        # Start processes
        print_info "Iniciando procesos..."
        supervisorctl start laravel-reverb
        supervisorctl start laravel-queue-worker:*
        supervisorctl start laravel-scheduler
        sleep 2
        print_success "Procesos iniciados"

        # Verify status
        print_header "Estado de los procesos"
        supervisorctl status

        print_success "Instalación con Supervisor completada"
        print_info "Comandos útiles:"
        echo "  Estado de procesos: sudo supervisorctl status"
        echo "  Ver logs: sudo tail -f /var/log/supervisor/laravel-reverb.log"
        echo "  Reiniciar proceso: sudo supervisorctl restart laravel-queue-worker:00"
        ;;

    *)
        print_error "Opción inválida"
        exit 1
        ;;
esac

print_header "Verificación Final"

# Check if web server is running
if systemctl is-active --quiet apache2; then
    print_success "Apache2 está corriendo"
else
    print_error "Apache2 no está corriendo"
fi

# Check if PHP-FPM is running
if systemctl is-active --quiet php8.4-fpm 2>/dev/null || pgrep -x "php-fpm" > /dev/null; then
    print_success "PHP-FPM está corriendo"
else
    print_error "PHP-FPM no está corriendo"
fi

# Check if database is reachable
print_info "Verificando conexión a base de datos..."
cd "$PROJECT_PATH"
php artisan tinker <<EOF
try {
    \DB::connection()->getPdo();
    echo "✓ Base de datos conectada\n";
} catch (Exception \$e) {
    echo "✗ Error de conexión: " . \$e->getMessage() . "\n";
}
exit;
EOF

print_header "Configuración completada"
print_success "Todos los servicios están configurados"
print_info "Consulta el archivo deployment/INSTALLATION_GUIDE.md para más información"
print_info "Y deployment/QUICK_REFERENCE.md para comandos rápidos"
