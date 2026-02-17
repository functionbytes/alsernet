#!/bin/bash
# Script para iniciar Chrome con Remote Debugging habilitado
# Necesario para usar el MCP de Chrome DevTools

# Cerrar Chrome si está corriendo
killall "Google Chrome" 2>/dev/null

# Esperar un momento
sleep 1

# Iniciar Chrome con remote debugging en el puerto 9222
echo "Iniciando Chrome con Remote Debugging en el puerto 9222..."
/Applications/Google\ Chrome.app/Contents/MacOS/Google\ Chrome \
  --remote-debugging-port=9222 \
  --user-data-dir=/tmp/chrome-debug-profile \
  --no-first-run \
  --no-default-browser-check \
  > /dev/null 2>&1 &

echo "Chrome iniciado con Remote Debugging"
echo "Ahora puedes navegar a https://inoqualab.test y usar el MCP de Chrome DevTools"
echo ""
echo "Para verificar que funciona, ejecuta:"
echo "curl http://127.0.0.1:9222/json/version"
