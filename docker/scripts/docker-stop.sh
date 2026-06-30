#!/bin/bash

# Script para detener Docker del proyecto manager

echo "🛑 Deteniendo Docker - Manager ERP"
echo "==================================="
echo ""

DOCKER_COMPOSE_FILE="docker/docker-compose.yml"

# Verificar si docker-compose está instalado
if ! command -v docker-compose &> /dev/null; then
    echo "❌ Docker Compose no está instalado"
    exit 1
fi

# Detener servicios
echo "📦 Deteniendo servicios..."
docker-compose -f "$DOCKER_COMPOSE_FILE" down

echo ""
echo "✅ Servicios detenidos"
echo ""

# Mostrar opciones
echo "Opciones:"
echo "  - Para borrar volúmenes: docker-compose -f $DOCKER_COMPOSE_FILE down -v"
echo "  - Para limpiar todo:     docker-compose -f $DOCKER_COMPOSE_FILE down --remove-orphans"
echo ""
