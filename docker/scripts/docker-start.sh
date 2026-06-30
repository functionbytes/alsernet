#!/bin/bash

# Script para iniciar Docker del proyecto manager

set -e

echo "🐳 Iniciando Docker - Manager ERP"
echo "=================================="
echo ""

# Colores
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Obtener directorio del script
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PROJECT_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
DOCKER_COMPOSE_FILE="$SCRIPT_DIR/docker-compose.yml"

# Cambiar al directorio del proyecto
cd "$PROJECT_DIR"

# Verificar si Docker está instalado
if ! command -v docker &> /dev/null; then
    echo -e "${RED}❌ Docker no está instalado${NC}"
    exit 1
fi

# Verificar si docker-compose está instalado
if ! command -v docker-compose &> /dev/null; then
    echo -e "${RED}❌ Docker Compose no está instalado${NC}"
    exit 1
fi

# Verificar que estamos en el directorio correcto
if [ ! -f "$DOCKER_COMPOSE_FILE" ]; then
    echo -e "${RED}❌ docker-compose.yml no encontrado en $DOCKER_COMPOSE_FILE${NC}"
    exit 1
fi

# Crear directorio de Oracle si no existe
if [ ! -d "docker/oracle" ]; then
    echo -e "${YELLOW}⚠️  Directorio docker/oracle no existe${NC}"
    echo "   Si necesitas usar Oracle, copia los archivos:"
    echo "   - instantclient-basic-linux.x64-11.2.0.4.0.zip"
    echo "   - instantclient-sdk-linux.x64-11.2.0.4.0.zip"
    echo "   A: docker/oracle/"
    echo ""
fi

# Iniciar servicios
echo -e "${GREEN}📦 Levantando servicios...${NC}"
docker-compose -f "$DOCKER_COMPOSE_FILE" up -d

echo ""
echo -e "${GREEN}✅ Servicios iniciados${NC}"
echo ""

# Esperar a que MySQL esté listo
echo -e "${YELLOW}⏳ Esperando a que MySQL esté listo...${NC}"
sleep 5

# Ejecutar migraciones (opcional)
# echo "🔄 Ejecutando migraciones..."
# docker-compose -f "$DOCKER_COMPOSE_FILE" exec -T app php artisan migrate

echo ""
echo "✨ Manager ERP está listo!"
echo ""
echo -e "${GREEN}URLs de acceso:${NC}"
echo "  - 🌐 Web:     http://localhost:8080"
echo "  - 📧 Mailhog: http://localhost:8026"
echo "  - 💾 MySQL:   localhost:3307 (usuario: webadmin)"
echo "  - 🔴 Redis:   localhost:6380"
echo ""
echo -e "${GREEN}Comandos útiles:${NC}"
echo "  - Ver logs:     docker-compose -f "$DOCKER_COMPOSE_FILE" logs -f app"
echo "  - Bash:         docker-compose -f "$DOCKER_COMPOSE_FILE" exec app bash"
echo "  - Artisan:      docker-compose -f "$DOCKER_COMPOSE_FILE" exec app php artisan"
echo "  - Tinker:       docker-compose -f "$DOCKER_COMPOSE_FILE" exec app php artisan tinker"
echo "  - Parar:        docker-compose -f "$DOCKER_COMPOSE_FILE" down"
echo ""

# Conectar con Oracle si está disponible
echo -e "${YELLOW}🔗 Intentando conectar a la red de Oracle (integracion)...${NC}"
if docker network ls | grep -q laravel-oracle; then
    docker network connect laravel-oracle manager-app 2>/dev/null || true
    echo -e "${GREEN}✅ Conectado a Oracle${NC}"
    echo "   ORACLE_HOST dentro del contenedor: laravel-oracle9i-db"
else
    echo -e "${YELLOW}⚠️  Red de Oracle no encontrada (integracion no está levantado)${NC}"
    echo "   Para usar Oracle:"
    echo "   1. Levanta integracion primero: cd ../integracion && docker-compose up -d"
    echo "   2. Luego ejecuta: docker network connect laravel-oracle manager-app"
fi

echo ""
