#!/bin/bash

# 🐳 Herd + Docker Bridge Script
# Inicia Docker e integra automáticamente con Herd

set -e

DOCKER_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PROJECT_DIR="$(dirname "$DOCKER_DIR")"
PROJECT_NAME="manager"
DOMAIN="managers.test"
DOCKER_PORT=80

echo "🌉 Integrando Docker con Herd..."
echo "=================================="

# 1. Iniciar Docker
echo ""
echo "📦 Iniciando servicios Docker..."
cd "$DOCKER_DIR"
docker-compose up -d

# 2. Esperar a que Docker esté listo
echo ""
echo "⏳ Esperando a que Docker esté listo..."
sleep 5

# 3. Obtener IP de Docker (IP del contenedor nginx)
DOCKER_IP=$(docker inspect -f '{{range.NetworkSettings.Networks}}{{.IPAddress}}{{end}}' "${PROJECT_NAME}-nginx")

# 4. Información para el usuario
echo ""
echo "✨ ¡Docker está integrado con Herd!"
echo "===================================="
echo ""
echo "🌐 Acceso a la aplicación:"
echo "  Local Docker:  http://localhost"
echo "  Via Herd:      http://${DOMAIN}"
echo ""
echo "📝 Para acceder via Herd (${DOMAIN}):"
echo "  Opción 1 - Proxy Local (RECOMENDADO):"
echo "    Agrega en tu hosts (/etc/hosts o en Herd):"
echo "    127.0.0.1  ${DOMAIN}"
echo ""
echo "    Luego accede a: http://${DOMAIN} (puerto 80 de tu máquina)"
echo ""
echo "  Opción 2 - IP Directa:"
echo "    http://${DOCKER_IP} (dentro de la red Docker)"
echo ""
echo "🔧 Comandos útiles:"
echo "  Ver logs:      docker-compose -f ${DOCKER_DIR}/docker-compose.yml logs -f app"
echo "  Bash:          docker-compose -f ${DOCKER_DIR}/docker-compose.yml exec app bash"
echo "  Artisan:       docker-compose -f ${DOCKER_DIR}/docker-compose.yml exec app php artisan"
echo "  Parar:         docker-compose -f ${DOCKER_DIR}/docker-compose.yml down"
echo ""
echo "💡 NOTA: Docker está expuesto en puerto 80 (localhost)"
echo "    Si quieres usar otro puerto, edita docker-compose.yml"
echo ""
