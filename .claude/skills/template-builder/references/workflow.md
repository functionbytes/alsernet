# Workflow detallado — Pipeline de 3 fases

> Pipeline completo para convertir HTML estático en template Laravel funcional.
> Cada fase tiene sub-tareas paralelas con agentes.

---

## FASE A — Análisis del HTML (3 agentes en paralelo)

### A.1 — Inventario y categorización

```bash
TEMPLATE_PATH="/Users/.../template-name"
TEMPLATE_NAME="WolmartTemplate"  # PascalCase
TEMPLATE_SLUG="wolmart"          # kebab-case

# Inventario
cd $TEMPLATE_PATH
ls *.html | wc -l
find . -name "*.html" -type f | sort

# Categorizar (regex similar a Riode)
ls element*.html  # → shortcodes candidates
ls demo*.html     # → demos / homepages
ls shop*.html     # → listing layouts
ls product*.html  # → detail layouts
ls blog*.html     # → blog layouts
ls *.html | grep -vE "^(demo|shop|product|element|blog|post-|market|vendor|store)"  # → utility
```

Generar `00-inventory.md` con tabla por categoría.

### A.2 — Captura de screenshots con Chrome DevTools

```bash
# 1. Servidor http
nohup python3 -m http.server 8765 --directory $TEMPLATE_PATH > /tmp/server.log 2>&1 &

# 2. Por cada categoría, abrir Chrome y capturar fullpage
mkdir -p analisis/screenshots/{shop,product,element,blog,utility,demo-vertical,demo-numeric,marketplace}

# Usar mcp__chrome-devtools__navigate_page + take_screenshot fullPage:true
# para CADA HTML del scope
```

**Convención numbering**: `NN-name.jpeg` con `NN` por orden alfabético dentro de su categoría.

### A.3 — Análisis paralelo (3 agentes)

#### Agente 1: Element pages (shortcodes candidates)

Lee cada `element-*.html`, extrae:
- Componente identificable (banner, slider, hotspot, countdown, etc.)
- Variantes detectadas (cuántos estilos por archivo)
- HTML mínimo de cada variante
- Clases CSS clave
- JS dependencias
- Atributos para shortcode propuesto

Output: `analisis/03-element-components.md` con propuestas de 30-40 shortcodes.

#### Agente 2: Layout pages (templates de página)

Lee cada `shop-*.html`, `product-*.html`, `blog-*.html`, identifica:
- Tipos de layout primarios (4-6)
- Props ortogonales (cols, sidebar, pagination, card_style)
- Diferenciadores entre variantes

Output: `analisis/01-shop-layouts.md`, `02-product-layouts.md`, `06-blog.md`.

#### Agente 3: Demos + utility + design tokens

Lee `demo-*.html`, `index.html`, `cart.html`, `checkout.html`, etc.:
- Paletas por demo vertical
- Fonts (Google Fonts URLs)
- Page templates típicos (homepage, services, product-landing)
- Componentes utility (header, footer, cart drawer, login modal)

Output: `analisis/04-demos.md`, `08-utility.md`, `tokens.css`.

### A.4 — Extracción de design tokens

(Ver `references/design-tokens-extraction.md`)

```bash
CSS_FILE=$TEMPLATE_PATH/css/style.min.css

# 1. Buscar CSS variables (si las usa)
grep -oE '\-\-[a-z-]+:\s*[^;}]+' $CSS_FILE | sort -u

# 2. Si NO usa variables, extraer hex repetidos
grep -oE '#[0-9a-fA-F]{6}' $CSS_FILE | sort | uniq -c | sort -rn | head -10

# 3. Fonts
grep -oE 'font-family:[^;]+' $CSS_FILE | sort | uniq -c | sort -rn | head

# 4. Google Fonts en HTML
grep -h "fonts.googleapis.com" *.html | sort -u
```

Generar `analisis/tokens.css` con variables CSS adaptadas al brand del proyecto.

### A.5 — Componentes deep-dive

Para cada componente CRÍTICO (header, footer, cart drawer, modales), generar `.md` con:
- HTML real (snippet limpio)
- Trigger / activación
- Variantes
- Equivalente Laravel (`@include 'partial'` o BS5 nativo)
- JS necesario

Output: `analisis/components/{header,footer,modals,...}.md`

---

## FASE B — Generar estructura Laravel (paralelo)

### B.1 — Crear directorio base

```bash
TEMPLATE_DIR="/Users/developerts/Herd/system/modules/Template/Templates/$TEMPLATE_NAME"

mkdir -p $TEMPLATE_DIR/Shortcodes
mkdir -p $TEMPLATE_DIR/Resources/views/shortcodes
mkdir -p $TEMPLATE_DIR/Tests/Feature
```

### B.2 — Distribuir shortcodes en categorías (decisión)

Agrupa los 30-40 shortcodes propuestos en 4-6 clases lógicas:

| Categoría | Cantidad típica | Shortcodes |
|-----------|----------------:|------------|
| **Content** | 6-8 | cta, countdown, counter, icon-box, title, etc. |
| **Structure** | 6-10 | tabs, slider, banner, accordion-extras, hotspot, etc. |
| **Utility** | 4-6 | breadcrumb, page-header, social-links, image-box, video |
| **Media** | 5-8 | blog-posts, category-card, creative-grid, testimonials |
| **Effects** | 3-5 | animate, floating, scroll-reveal, svg-float |
| **Marketplace** | 3-5 | instagram, vendor-card, etc. (opcional) |

Cada categoría → 1 clase PHP con `registerAll()`.

### B.3 — Lanzar agentes en paralelo (1 por categoría)

Cada agente recibe:
- Path al template HTML
- Path a docs `analisis/03-element-components.md`
- Lista de shortcodes que debe implementar
- Convenciones (NO inline styles, FA6, etc.)
- Path a scaffolds (`scaffolds/shortcode-class.php.tpl`)

Cada agente produce:
- `{Name}{Category}Shortcodes.php`
- N views Blade
- 1 archivo Test Feature

(Ver `references/shortcode-patterns.md` para el patrón exacto)

### B.4 — Crear seeder

```php
// modules/Template/database/seeders/{Name}TemplateSeeder.php
namespace Modules\Template\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Template\Models\Template;

class {Name}TemplateSeeder extends Seeder
{
    public function run(): void
    {
        Template::query()->where('status', 'active')->update(['status' => 'inactive']);

        Template::updateOrCreate(
            ['slug' => '{slug}'],
            [
                'name' => '{Name}',
                'description' => 'Template {Name} (origen: {origin}) — {N} shortcodes específicos',
                'template_path' => 'modules/Template/Templates/{Name}',
                'status' => 'active',
                'author' => '{author} (adaptado para Alsernet)',
                'version' => '1.0.0',
            ]
        );
    }
}
```

(Ver `scaffolds/seeder.php.tpl`)

### B.5 — Actualizar composer autoload

`modules/Template/composer.json` ya debería incluir:
```json
"modules\\Template\\Templates\\": "Templates/"
```

Si NO, añadir.

---

## FASE C — Activar y verificar

### C.1 — Composer dump

```bash
cd /Users/developerts/Herd/system
composer dump-autoload
```

### C.2 — Run seeder

```bash
php artisan db:seed --class="Modules\\Template\\Database\\Seeders\\{Name}TemplateSeeder"
```

### C.3 — Cache clear

```bash
php artisan optimize:clear
```

### C.4 — Verificación funcional

```bash
# 1. Verificar template activo
php artisan tinker --execute='echo \Modules\Template\Models\Template::where("status","active")->value("slug");'
# Esperado: {slug}

# 2. Verificar shortcodes registrados
php artisan shortcode:list | grep -E "(shortcode1|shortcode2|shortcode3)"
# Esperado: cada uno aparece

# 3. Renderizar test rápido
php artisan shortcode:compile '[shortcode1 attr="test"]content[/shortcode1]'
# Esperado: HTML válido, no vacío
```

### C.5 — Run tests

```bash
php artisan test --filter="{Name}.*Shortcodes" --compact
```

### C.6 — Pint formato

```bash
vendor/bin/pint --dirty
```

### C.7 — Reportar al usuario

```markdown
## ✅ Template {Name} generado

### Estructura creada
- {N} clases PHP en `modules/Template/Templates/{Name}/Shortcodes/`
- {M} views Blade en `Resources/views/shortcodes/`
- {K} tests Feature en `Tests/Feature/`
- 1 seeder en `modules/Template/database/seeders/`

### Shortcodes activos ({N})
[lista]

### Cómo cambiar de template
```sql
UPDATE templates SET status='inactive' WHERE slug='{slug}';
UPDATE templates SET status='active' WHERE slug='other-template';
```
```

---

## ⚠️ Errores comunes durante el pipeline

| Error | Causa | Fix |
|-------|-------|-----|
| `class does not exist` | Composer no recargó | `composer dump-autoload` |
| Shortcode no aparece en list | Template inactivo o cache | `optimize:clear` + verificar `templates.status` |
| View not found `{slug}::shortcodes.X` | Namespace no registrado | Verificar `view()->addNamespace()` en `registerActiveTemplateShortcodes()` |
| Test failed assertion | View genera HTML distinto al esperado | Ajustar assertions del test |
| Pint fails | Formato no estándar | Run `vendor/bin/pint --dirty` |
| Migration "templates table doesn't exist" | Migration no ejecutada | `php artisan migrate` |

---

## 🎬 Tiempo estimado por fase (para 30-40 shortcodes)

| Fase | Tiempo (paralelo con agentes) |
|------|------------------------------:|
| A.1-A.5 (análisis) | 30-45 min |
| B.1-B.5 (generar) | 60-90 min |
| C.1-C.7 (activar) | 10-15 min |
| **Total** | **~2 horas** |

(Comparable: Riode tomó ~3 horas en total porque incluyó arquitectura template-specific desde cero)
