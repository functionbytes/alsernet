#!/usr/bin/env bash
set -e

# Build script — generates {BRAND_SLUG}.zip ready to upload to PrestaShop.
# Reads brand from .env (BRAND_NAME, BRAND_SLUG, BRAND_URL) and templates files.

cd "$(dirname "$0")"

# Read brand from .env (project root)
ROOT_DIR="$(cd ../../../.. && pwd)"
ENV_FILE="$ROOT_DIR/.env"
if [ -f "$ENV_FILE" ]; then
    BRAND_NAME=$(grep -E '^BRAND_NAME=' "$ENV_FILE" | cut -d'=' -f2- | tr -d '"' | tr -d "'")
    BRAND_SLUG=$(grep -E '^BRAND_SLUG=' "$ENV_FILE" | cut -d'=' -f2- | tr -d '"' | tr -d "'")
    BRAND_URL=$(grep -E '^BRAND_URL=' "$ENV_FILE" | cut -d'=' -f2- | tr -d '"' | tr -d "'")
fi

BRAND_NAME="${BRAND_NAME:-Alsernet}"
BRAND_SLUG="${BRAND_SLUG:-alsernet_chat}"
BRAND_URL="${BRAND_URL:-https://example.com}"

echo "Brand:     $BRAND_NAME"
echo "Slug:      $BRAND_SLUG"
echo "URL:       $BRAND_URL"

VERSION=$(grep -m1 '<version>' alsernet_chat/config.xml | sed 's/.*<!\[CDATA\[\(.*\)\]\]>.*/\1/')
ZIP_NAME="${BRAND_SLUG}-${VERSION}.zip"
WORK="/tmp/${BRAND_SLUG}-build-$$"

# Clean
rm -f "$ZIP_NAME" "${BRAND_SLUG}.zip"
rm -rf "$WORK"

# Stage files renaming alsernet_chat → BRAND_SLUG
mkdir -p "$WORK/$BRAND_SLUG"
cp -R alsernet_chat/. "$WORK/$BRAND_SLUG/"

# Substitute brand strings in PHP/markdown/translation files
find "$WORK/$BRAND_SLUG" -type f \( -name '*.php' -o -name '*.xml' -o -name '*.md' \) -exec sed -i '' \
    -e "s|Alsernet Live Chat|$BRAND_NAME Live Chat|g" \
    -e "s|alsernet_chat|$BRAND_SLUG|g" \
    -e "s|Alsernet Chat|$BRAND_NAME Chat|g" \
    -e "s|alsernet\\.com|$(echo "$BRAND_URL" | sed 's|https\?://||;s|/$||')|g" \
    -e "s|>Alsernet<|>$BRAND_NAME<|g" \
    -e "s|'Alsernet'|'$BRAND_NAME'|g" \
    {} \;

# Rename main file to match slug
if [ -f "$WORK/$BRAND_SLUG/alsernet_chat.php" ]; then
    mv "$WORK/$BRAND_SLUG/alsernet_chat.php" "$WORK/$BRAND_SLUG/$BRAND_SLUG.php"
    # Class name in CamelCase from slug (alsernet_chat → AlsernetChat)
    CLASS_NAME=$(echo "$BRAND_SLUG" | awk -F_ '{for(i=1;i<=NF;i++) printf toupper(substr($i,1,1))substr($i,2); print ""}')
    # PrestaShop convention: keep first letter uppercase only (Alsernet_chat) — for compat with slug-based discovery
    PS_CLASS_NAME=$(echo "$BRAND_SLUG" | awk '{print toupper(substr($0,1,1))substr($0,2)}')
    sed -i '' "s|class Alsernet_chat|class $PS_CLASS_NAME|g" "$WORK/$BRAND_SLUG/$BRAND_SLUG.php"
    sed -i '' "s|new Alsernet_chat|new $PS_CLASS_NAME|g" "$WORK/$BRAND_SLUG/cron.php"
fi

# Create zip
(cd "$WORK" && zip -rq "$OLDPWD/$ZIP_NAME" "$BRAND_SLUG" -x "*/.DS_Store")

ln -sf "$ZIP_NAME" "${BRAND_SLUG}.zip"
rm -rf "$WORK"

echo ""
echo "✓ Empaquetado: $ZIP_NAME ($(du -h "$ZIP_NAME" | awk '{print $1}'))"
echo "  Symlink:    ${BRAND_SLUG}.zip"
echo ""
echo "Para instalar:"
echo "  1. Subir al back office: Módulos → Catálogo → Subir un módulo"
echo "  2. Configurar URL de la API + Website token + Integration ID + Webhook secret"
