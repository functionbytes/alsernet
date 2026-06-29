# Verification — Checklist final

> Lista de verificación para confirmar que el template generado funciona correctamente.

## Checklist completo (12 puntos)

### 1. Estructura de directorios

```bash
TEMPLATE_NAME="Wolmart"
TEMPLATE_DIR="/Users/developerts/Herd/system/modules/Template/Templates/$TEMPLATE_NAME"

# Mínimo requerido
test -d "$TEMPLATE_DIR/Shortcodes" && echo "✓ Shortcodes/ existe"
test -d "$TEMPLATE_DIR/Resources/views/shortcodes" && echo "✓ views/shortcodes/ existe"
test -d "$TEMPLATE_DIR/Tests/Feature" && echo "✓ Tests/Feature/ existe"
test -f "$TEMPLATE_DIR/README.md" && echo "✓ README.md existe"
test -f "$TEMPLATE_DIR/tokens.css" && echo "✓ tokens.css existe"
test -f "$TEMPLATE_DIR/metadata.json" && echo "✓ metadata.json existe"
```

### 2. Conteo de archivos

```bash
echo "Clases PHP: $(ls $TEMPLATE_DIR/Shortcodes/*.php 2>/dev/null | wc -l)"
echo "Views Blade: $(ls $TEMPLATE_DIR/Resources/views/shortcodes/*.blade.php 2>/dev/null | wc -l)"
echo "Tests: $(ls $TEMPLATE_DIR/Tests/Feature/*.php 2>/dev/null | wc -l)"
```

Esperado (Fase 1+2+3 full):
- Clases PHP: 4-6
- Views Blade: 30-40
- Tests: 4-6

### 3. Sintaxis PHP

```bash
for f in $TEMPLATE_DIR/Shortcodes/*.php; do
    out=$(php -l "$f" 2>&1)
    if echo "$out" | grep -q "No syntax errors"; then
        echo "✓ $(basename $f)"
    else
        echo "✗ $(basename $f) — error: $out"
    fi
done
```

### 4. Namespaces correctos

```bash
for f in $TEMPLATE_DIR/Shortcodes/*.php; do
    ns=$(grep "^namespace" "$f" | head -1)
    expected="namespace Modules\\Template\\Templates\\$TEMPLATE_NAME\\Shortcodes;"
    if [ "$ns" = "$expected" ]; then
        echo "✓ $(basename $f)"
    else
        echo "✗ $(basename $f) — namespace incorrecto: $ns"
    fi
done
```

### 5. Composer autoload

```bash
cd /Users/developerts/Herd/system

# Verificar entry en composer.json
grep "Templates" modules/Template/composer.json

# Si no está, añadir:
# "modules\\Template\\Templates\\": "Templates/"

# Recargar autoload
composer dump-autoload

# Verificar clases loadable
php -r '
require "/Users/developerts/Herd/system/vendor/autoload.php";
$classes = ["{Name}ContentShortcodes", "{Name}StructureShortcodes"];
foreach ($classes as $name) {
    $fqcn = "Modules\\Template\\Templates\\{Name}\\Shortcodes\\$name";
    echo "$name: " . (class_exists($fqcn) ? "✓" : "✗") . "\n";
}
'
```

### 6. Template activo en DB

```bash
php artisan tinker --execute='
$active = \Modules\Template\Models\Template::where("status","active")->first();
if ($active) {
    echo "✓ Active: " . $active->slug . " (" . $active->name . ")\n";
} else {
    echo "✗ Ningún template activo\n";
}
'
```

### 7. Auto-discovery funcionó

```bash
php artisan optimize:clear
php artisan shortcode:list | grep -E "(shortcode-1|shortcode-2)" 
```

### 8. Renderizado básico

```bash
# Test happy path
php artisan shortcode:compile '[shortcode-1 title="Test" style="primary"][/shortcode-1]'
# Esperado: HTML válido no vacío

# Test edge case (atributo faltante)
php artisan shortcode:compile '[shortcode-1][/shortcode-1]'
# Esperado: vacío o placeholder
```

### 9. Tests Feature

```bash
php artisan test --filter="$TEMPLATE_NAME.*Shortcodes" --compact
```

Esperado: 0 fails, todos passing (o documentar las excepciones esperadas).

### 10. Pint formato

```bash
vendor/bin/pint --dirty
```

Esperado: `{"result":"pass"}` o files fixed (sin errors).

### 11. Activación/desactivación

```bash
# Test desactivar
php artisan tinker --execute='
\Modules\Template\Models\Template::where("slug","{slug}")->update(["status" => "inactive"]);
'
php artisan optimize:clear
count_inactive=$(php artisan shortcode:list 2>&1 | grep -c "shortcode-1-name")
echo "Shortcodes con template INACTIVE: $count_inactive (esperado 0)"

# Reactivar
php artisan tinker --execute='
\Modules\Template\Models\Template::where("slug","{slug}")->update(["status" => "active"]);
'
php artisan optimize:clear
count_active=$(php artisan shortcode:list 2>&1 | grep -c "shortcode-1-name")
echo "Shortcodes con template ACTIVE: $count_active (esperado ≥1)"
```

### 12. Documentación

```bash
test -s "$TEMPLATE_DIR/README.md" && echo "✓ README.md tiene contenido"
test -s "$TEMPLATE_DIR/tokens.css" && echo "✓ tokens.css tiene contenido"
test -s "$TEMPLATE_DIR/metadata.json" && echo "✓ metadata.json tiene contenido"

# Verificar que README cubre lo mínimo
for section in "Cómo activar" "Shortcodes específicos" "Design Tokens" "Tests"; do
    if grep -q "$section" "$TEMPLATE_DIR/README.md"; then
        echo "  ✓ Sección: $section"
    else
        echo "  ✗ Falta sección: $section"
    fi
done
```

## Script all-in-one de verificación

```bash
#!/bin/bash
# verify-template.sh

TEMPLATE_NAME="$1"  # ej: Wolmart
SLUG="$2"            # ej: wolmart

if [ -z "$TEMPLATE_NAME" ] || [ -z "$SLUG" ]; then
    echo "Usage: $0 <TemplateName> <slug>"
    exit 1
fi

TEMPLATE_DIR="/Users/developerts/Herd/system/modules/Template/Templates/$TEMPLATE_NAME"
PASS=0
FAIL=0

check() {
    local label="$1"
    local cmd="$2"
    if eval "$cmd" > /dev/null 2>&1; then
        echo "  ✓ $label"
        PASS=$((PASS + 1))
    else
        echo "  ✗ $label"
        FAIL=$((FAIL + 1))
    fi
}

echo "═══════════════════════════════════════════════════════════"
echo "    Verificación template $TEMPLATE_NAME"
echo "═══════════════════════════════════════════════════════════"
echo ""

echo "🔍 Estructura"
check "Shortcodes/" "[ -d $TEMPLATE_DIR/Shortcodes ]"
check "views/shortcodes/" "[ -d $TEMPLATE_DIR/Resources/views/shortcodes ]"
check "Tests/Feature/" "[ -d $TEMPLATE_DIR/Tests/Feature ]"
check "README.md" "[ -f $TEMPLATE_DIR/README.md ]"
check "tokens.css" "[ -f $TEMPLATE_DIR/tokens.css ]"
check "metadata.json" "[ -f $TEMPLATE_DIR/metadata.json ]"
echo ""

echo "🔍 Sintaxis PHP"
for f in $TEMPLATE_DIR/Shortcodes/*.php; do
    [ -f "$f" ] || continue
    check "$(basename $f)" "php -l '$f' 2>&1 | grep -q 'No syntax errors'"
done
echo ""

echo "🔍 Composer autoload"
check "Templates/ en composer.json" "grep -q 'Templates' /Users/developerts/Herd/system/modules/Template/composer.json"
echo ""

echo "🔍 Template DB"
check "Template '$SLUG' existe" "php /Users/developerts/Herd/system/artisan tinker --execute='exit(\Modules\Template\Models\Template::where(\"slug\",\"$SLUG\")->exists() ? 0 : 1);' 2>/dev/null"
check "Template '$SLUG' activo" "php /Users/developerts/Herd/system/artisan tinker --execute='exit(\Modules\Template\Models\Template::where(\"slug\",\"$SLUG\")->where(\"status\",\"active\")->exists() ? 0 : 1);' 2>/dev/null"
echo ""

echo "🔍 Pint"
cd /Users/developerts/Herd/system
check "Pint pass" "vendor/bin/pint --test --dirty 2>&1 | grep -qv 'fail'"
echo ""

echo "═══════════════════════════════════════════════════════════"
echo "    Resultado: $PASS passed, $FAIL failed"
echo "═══════════════════════════════════════════════════════════"

if [ $FAIL -gt 0 ]; then
    exit 1
fi
```

## Reporte final al usuario

Después de la verificación, reportar al usuario en este formato:

```markdown
## ✅ Template {Name} generado exitosamente

### Estructura creada
- {N} clases PHP en `modules/Template/Templates/{Name}/Shortcodes/`
- {M} views Blade en `Resources/views/shortcodes/`
- {K} tests Feature en `Tests/Feature/`
- 1 seeder, 1 README, 1 tokens.css, 1 metadata.json

### Shortcodes activos ({N})

#### Content ({Nc})
[lista]

#### Structure ({Ns})
[lista]

[... otras categorías ...]

### Cómo cambiar de template

```sql
-- Activar otro template
UPDATE templates SET status='inactive' WHERE slug='{slug}';
UPDATE templates SET status='active' WHERE slug='other-template';
```

```bash
php artisan optimize:clear
```

### Tests passing

```
✓ {N} tests passing, 0 failed
```

### Próximos pasos sugeridos

1. Revisar `README.md` del template
2. Probar visualmente los shortcodes en el Visual Editor
3. Personalizar `tokens.css` si es necesario
4. Ejecutar `php artisan test --filter={Name}` para validación completa
5. Documentar shortcodes específicos del proyecto en el módulo Page

### Marcadores especiales detectados

- `[INFERIDO]`: {N} valores estimados visualmente — verificar
- `[VERIFICAR]`: {N} valores poco confiables — validar
- `[TODO]`: {N} features pendientes (e.g., conexión con módulo Blog)
```
