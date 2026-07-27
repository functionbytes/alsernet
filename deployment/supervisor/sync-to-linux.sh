#!/bin/bash
# Sincroniza los archivos prueba-*.conf de Supervisor hacia el servidor Linux
# de destino, y opcionalmente los instala en /etc/supervisor/conf.d/.
#
# Uso:
#   ./sync-to-linux.sh                 # solo sincroniza a REMOTE_STAGING_DIR
#   ./sync-to-linux.sh --install       # sincroniza + instala + supervisorctl reread/update

set -euo pipefail

# ── Configura esto con los datos de tu servidor ──────────────────────────
REMOTE_USER="tu_usuario"
REMOTE_HOST="tu_servidor.com"
REMOTE_STAGING_DIR="/home/${REMOTE_USER}/supervisor-staging"
SUPERVISOR_CONF_DIR="/etc/supervisor/conf.d"
# ──────────────────────────────────────────────────────────────────────

LOCAL_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

echo "==> Sincronizando prueba-*.conf hacia ${REMOTE_USER}@${REMOTE_HOST}:${REMOTE_STAGING_DIR}"
ssh "${REMOTE_USER}@${REMOTE_HOST}" "mkdir -p '${REMOTE_STAGING_DIR}'"

rsync -avz --progress \
    "${LOCAL_DIR}"/prueba-*.conf \
    "${REMOTE_USER}@${REMOTE_HOST}:${REMOTE_STAGING_DIR}/"

# El scheduler depende de este script — sincronizarlo tambien
rsync -avz --progress \
    "$(cd "${LOCAL_DIR}/../scripts" && pwd)/scheduler.sh" \
    "${REMOTE_USER}@${REMOTE_HOST}:${REMOTE_STAGING_DIR}/../scripts/" 2>/dev/null || \
    echo "   (aviso: no se pudo sincronizar scheduler.sh automaticamente, copialo a mano a /var/www/deployment/scripts/)"

echo "==> Archivos copiados a ${REMOTE_STAGING_DIR} en el servidor."

if [[ "${1:-}" == "--install" ]]; then
    echo "==> Instalando en ${SUPERVISOR_CONF_DIR} (quitando el prefijo 'prueba-')..."
    ssh "${REMOTE_USER}@${REMOTE_HOST}" bash -s <<EOF
set -e
for f in ${REMOTE_STAGING_DIR}/prueba-*.conf; do
    name=\$(basename "\$f" | sed 's/^prueba-//')
    sudo cp "\$f" "${SUPERVISOR_CONF_DIR}/\$name"
    echo "   instalado: \$name"
done
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl status
EOF
    echo "==> Listo. Revisa el estado arriba (todos deberian estar RUNNING)."
else
    echo "==> No se instalo nada todavia (falta --install). Revisa los archivos en el servidor antes de aplicarlos:"
    echo "    ssh ${REMOTE_USER}@${REMOTE_HOST} 'ls -la ${REMOTE_STAGING_DIR}'"
fi
