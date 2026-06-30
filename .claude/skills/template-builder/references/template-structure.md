# Template Structure — Output esperado

> Estructura final del directorio del template generado.

## Layout completo

```
modules/Template/Templates/{Name}/
│
├── Shortcodes/                                          # Clases PHP con registerAll()
│   ├── {Name}ContentShortcodes.php                      # cta, countdown, counter, icon-box, title
│   ├── {Name}StructureShortcodes.php                    # tabs, slider, banner, hotspot
│   ├── {Name}UtilityShortcodes.php                      # breadcrumb, page-header, social-links, video
│   ├── {Name}MediaShortcodes.php                        # blog-posts, category-card, testimonials
│   ├── {Name}EffectsShortcodes.php                      # animate, floating, scroll-reveal
│   └── {Name}MarketplaceShortcodes.php                  # instagram-feed, vendor-card (opcional)
│
├── Resources/
│   ├── views/
│   │   ├── shortcodes/                                  # Blade views (~30-40)
│   │   │   ├── cta.blade.php
│   │   │   ├── cta-column.blade.php                     # children indicators con sufijo
│   │   │   ├── countdown.blade.php
│   │   │   ├── ...
│   │   │   └── vendor-card.blade.php
│   │   │
│   │   └── partials/                                    # Partials reutilizables (opcional)
│   │       ├── header.blade.php
│   │       ├── footer.blade.php
│   │       └── ...
│   │
│   └── lang/                                            # Traducciones específicas (opcional)
│       ├── es/
│       │   └── shortcodes.php
│       └── en/
│           └── shortcodes.php
│
├── Tests/
│   └── Feature/                                         # Tests Feature
│       ├── {Name}ContentShortcodesTest.php
│       ├── {Name}StructureShortcodesTest.php
│       ├── {Name}UtilityShortcodesTest.php
│       ├── {Name}MediaShortcodesTest.php
│       ├── {Name}EffectsShortcodesTest.php
│       └── {Name}MarketplaceShortcodesTest.php
│
├── public/                                              # Assets específicos (opcional)
│   ├── css/
│   │   └── {slug}-theme.css                             # CSS específico del template
│   ├── js/
│   │   └── {slug}-init.js                               # JS init específico
│   └── images/
│       └── ...
│
├── tokens.css                                           # Variables CSS del template
├── README.md                                            # Documentación del template
└── metadata.json                                        # Metadata del template
```

## metadata.json

```json
{
  "name": "{Name}",
  "slug": "{slug}",
  "version": "1.0.0",
  "author": "{author original} (adaptado para Alsernet)",
  "description": "{descripción}",
  "origin": "{ThemeForest URL u origen}",
  "license": "GPL-3.0|MIT|Comercial",
  "shortcodes_count": 35,
  "created_at": "2026-04-28",
  "categories": [
    "Content", "Structure", "Utility", "Media", "Effects", "Marketplace"
  ],
  "dependencies": {
    "fontawesome": "6.x",
    "bootstrap": "5.3",
    "jquery": "3.x"
  },
  "plugins_required": [
    "swiper@11"
  ],
  "plugins_optional": [
    "jquery.countdown@2.2"
  ]
}
```

## tokens.css

```css
/* Variables CSS del template {Name} adaptadas al brand Alsernet */
:root {
    /* Brand */
    --color-primary: #90bb13;          /* Alsernet — sustituye color original del template */
    --color-primary-hover: #7da010;
    --color-primary-light: #d3e8a8;
    
    /* Status */
    --color-success: #13C672;
    --color-danger: #FA896B;
    --color-warning: #FEC90F;
    --color-info: #4990D9;
    
    /* Texto */
    --color-text: {extraído del CSS original};
    --color-text-muted: {extraído};
    
    /* Fondos */
    --color-bg: #FFFFFF;
    --color-bg-alt: {extraído};
    
    /* Tipografía */
    --font-base: '{Font primaria del template}', system-ui, sans-serif;
    --font-display: '{Font display}', sans-serif;
    
    /* ... más tokens según template ... */
}
```

## README.md (auto-generado)

```markdown
# Template {Name}

> Origen: [{origen}]({URL})
> Versión: 1.0.0
> Adaptado de: {Author original}

## Resumen

{Descripción breve del template — qué tipo de sitios sirve, estilo, complejidad}

## Cómo activar

```sql
UPDATE templates SET status='inactive' WHERE slug != '{slug}';
UPDATE templates SET status='active' WHERE slug='{slug}';
```

O vía artisan:
```bash
php artisan db:seed --class="Modules\\Template\\Database\\Seeders\\{Name}TemplateSeeder"
php artisan optimize:clear
```

## Shortcodes específicos ({N})

### Content ({Nc})
| Shortcode | Descripción | Ejemplo |
|-----------|-------------|---------|
| ... |

### Structure ({Ns})
...

(repetir para cada categoría)

## Design Tokens

Ver `tokens.css`. Color primario `#90bb13` (Alsernet) reemplaza el original `{color}`.

## Plugins JS

- {Owl Carousel|Swiper}: requerido para sliders
- ...

## Tests

```bash
php artisan test --filter="{Name}.*Shortcodes" --compact
```

## Convenciones aplicadas

- ✅ Font Awesome 6 (mapping desde icons originales)
- ✅ Bootstrap 5.3 nativo
- ✅ jQuery + AJAX (NO Livewire/React)
- ✅ NO inline styles (excepto bg-image data attribute)
- ✅ Multi-idioma con __()
- ✅ Tests Feature con happy path + edge cases
```

## Verificación de la estructura

Comando para confirmar que todo está en su sitio:

```bash
TEMPLATE_DIR="/Users/developerts/Herd/system/modules/Template/Templates/{Name}"

# Estructura mínima esperada
test -d $TEMPLATE_DIR/Shortcodes && echo "✓ Shortcodes/ existe"
test -d $TEMPLATE_DIR/Resources/views/shortcodes && echo "✓ views/shortcodes/ existe"
test -d $TEMPLATE_DIR/Tests/Feature && echo "✓ Tests/Feature/ existe"
test -f $TEMPLATE_DIR/README.md && echo "✓ README.md existe"
test -f $TEMPLATE_DIR/tokens.css && echo "✓ tokens.css existe"
test -f $TEMPLATE_DIR/metadata.json && echo "✓ metadata.json existe"

# Conteo
echo "Clases PHP: $(ls $TEMPLATE_DIR/Shortcodes/*.php | wc -l)"
echo "Views Blade: $(ls $TEMPLATE_DIR/Resources/views/shortcodes/*.blade.php | wc -l)"
echo "Tests: $(ls $TEMPLATE_DIR/Tests/Feature/*.php | wc -l)"

# Sintaxis PHP
for f in $TEMPLATE_DIR/Shortcodes/*.php; do
    php -l "$f" 2>&1 | grep -v "^No syntax errors"
done

# Autoload
composer dump-autoload && echo "✓ Composer dump OK"

# Verificar registro
php artisan optimize:clear
ACTIVE=$(php artisan tinker --execute='echo \Modules\Template\Models\Template::where("status","active")->value("slug");')
echo "Active template: $ACTIVE"

if [ "$ACTIVE" = "{slug}" ]; then
    php artisan shortcode:list | grep -E "{slug}-related-shortcodes"
fi
```

## Convenciones de naming

### Carpetas
- `{Name}` en PascalCase (ej: `Wolmart`, `Avada`, `Bridge`, `Riode`)
- Slug equivalente en kebab-case (ej: `wolmart`, `avada`, `bridge`, `riode`)

### Clases PHP
- `{Name}{Category}Shortcodes` (ej: `WolmartContentShortcodes`)
- Una clase por categoría lógica (no más de 8-10 shortcodes por clase)

### Views Blade
- Nombre del shortcode en kebab-case (ej: `category-card.blade.php`)
- Children con prefijo del parent: `cta-column.blade.php` (child de cta)

### Tests
- `{Name}{Category}ShortcodesTest.php`
- Métodos: `test_{shortcode_name}_with_{scenario}`
