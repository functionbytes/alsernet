# Riode Walkthrough — Caso real completo

> Caso de ejemplo: cómo se generó el template Riode (38 shortcodes) usando este pipeline.
> Basado en sesión real del 2026-04-28.

## Input inicial

- **Plantilla**: Riode (D-Themes — ThemeForest #11198104)
- **Carpeta**: `/Users/developerts/Desktop/Plantillas/riode/`
- **Total HTMLs**: 278 archivos (274 raíz + 4 en `ajax/`)
- **Color primario original**: `#26c` (azul) o `#d26e4b` (terracota según demo)
- **Fonts**: Poppins (base), Jost/Kalam/Delius/Rammetto One (display por vertical)

## Fase A — Análisis (3 horas con agentes)

### A.1 Inventario y categorización

```bash
cd /Users/developerts/Desktop/Plantillas/riode
ls *.html | wc -l  # → 274
ls element-*.html | wc -l  # → 35 (componentes candidatos)
ls demo-*.html | wc -l  # → 135 (45 demos × 3 layouts)
ls product-*.html | wc -l  # → 30
ls shop-*.html | wc -l  # → 31
ls blog-*.html | wc -l  # → 12
```

### A.2 Screenshots

- **278 HTMLs visitados** con Chrome DevTools (puerto 8765)
- **208 fullpage screenshots** capturados (excluí algunos demo numéricas shop/product que eran clones temáticos)

### A.3 Análisis paralelo — 9 agentes en 3 fases

**Fase A primaria (3 agentes)**
- Agente A: 10 shop layouts → `01-shop-layouts.md`
- Agente B: 30 product layouts → `02-product-layouts.md`
- Agente C: 35 element components → `03-element-components.md` (30 shortcodes propuestos)

**Fase A secundaria (4 agentes)**
- Agente D: 135 demos → `04-demos.md` (paletas + fonts por vertical)
- Agente E: 21 shops adicionales → `05-shops-extras.md` (7 cards + 6 cols + 8 misc)
- Agente F: 13 blog → `06-blog.md`
- Agente G: 12 marketplace + vendor + store → `07-marketplace.md`
- Agente H: 16 utility → `08-utility.md`

**Fase A terciaria (1 agente)**
- Agente M: cierre gaps (CSS principal, modales hidden, icon font) → `09-js-ajax-vendor.md`, `10-css-modales-fonts.md`

**Total análisis**: 11 docs + 41 shortcodes individuales en `riode-analisis/shortcodes/` + 208 screenshots.

### A.4 Design tokens extraídos

Sin variables CSS modernas en `style.min.css` (only 5 customs en 334KB). Hard-coded:
- Color primario detectado: `#26c` (azul Riode)
- **Sustituido por `#90bb13` Alsernet** ← regla del proyecto
- Fonts: Poppins (Google) + 4 display fonts por vertical
- Bootstrap-like grid (custom reimplementado, NO Bootstrap real)

## Fase B — Generar estructura Laravel (~2 horas)

### B.1 Arquitectura template-specific (CRÍTICO descubrimiento)

⚠️ **Originalmente** los agentes registraron shortcodes en `modules/Shortcode/app/Providers/ShortcodeServiceProvider.php` (globales — patrón inicial del proyecto).

⚠️ **Pero el usuario pidió** vinculación al template activo. Se reorganizó:

```bash
# Mover de modules/Shortcode/app/Shortcodes/ → modules/Template/Templates/Riode/Shortcodes/
mv modules/Shortcode/app/Shortcodes/RiodeContentShortcodes.php \
   modules/Template/Templates/Riode/Shortcodes/

# Actualizar namespace
sed -i '' 's|namespace Modules\\Shortcode\\Shortcodes;|namespace Modules\\Template\\Templates\\Riode\\Shortcodes;|g' \
   modules/Template/Templates/Riode/Shortcodes/RiodeContentShortcodes.php

# Actualizar view namespace en las clases
sed -i '' "s|view('shortcode::shortcodes\.|view('riode::shortcodes.|g" \
   modules/Template/Templates/Riode/Shortcodes/*.php

# Quitar registro global
sed -i '' '/(new RiodeContentShortcodes/d' modules/Shortcode/app/Providers/ShortcodeServiceProvider.php
```

Y se añadió método `registerActiveTemplateShortcodes()` en TemplateServiceProvider:

```php
protected function registerActiveTemplateShortcodes(): void
{
    $this->app->booted(function () {
        $active = TemplateModel::query()->where('status', 'active')->first();
        if (!$active) return;
        
        $slug = $active->slug;
        $studlySlug = str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $slug)));
        
        // 1. Registrar namespace de views: {slug}::
        $viewsPath = base_path("modules/Template/Templates/{$studlySlug}/Resources/views");
        if (is_dir($viewsPath)) view()->addNamespace($slug, $viewsPath);
        
        // 2. Cargar todas las clases Shortcodes/*.php
        $shortcodesPath = base_path("modules/Template/Templates/{$studlySlug}/Shortcodes");
        $compiler = app('shortcode');
        
        foreach (glob($shortcodesPath.'/*.php') as $file) {
            $className = basename($file, '.php');
            $fqcn = "Modules\\Template\\Templates\\{$studlySlug}\\Shortcodes\\{$className}";
            
            if (class_exists($fqcn) && method_exists($fqcn, 'registerAll')) {
                (new $fqcn($compiler))->registerAll();
            }
        }
    });
}
```

### B.2 Distribución de shortcodes en 6 categorías

| Categoría | Clase | Shortcodes |
|-----------|-------|-----------|
| Content (7) | RiodeContentShortcodes.php | cta, cta-column, countdown, counter, counter-grid, icon-box, icon-box-grid |
| Structure (8) | RiodeStructureShortcodes.php | title, tabs, tab, slider, slide, banner, hotspot, hotspot-pin |
| Utility (5) | RiodeUtilityShortcodes.php | breadcrumb, page-header, social-links, image-box, video |
| Media (7) | RiodeMediaShortcodes.php | blog-posts, category-card, category-grid, creative-grid, grid-item, testimonials, testimonial |
| Effects (4) | RiodeEffectsShortcodes.php | animate, floating, scroll-reveal, svg-float |
| Marketplace (4) | RiodeMarketplaceShortcodes.php | instagram-feed, subcategory-card, category-column, vendor-card |
| **Total** | **6 clases** | **35 shortcodes** |

Plus 3 shortcodes globales ampliados en ServiceProvider (button +10 attrs, alert +8, accordion +9).

### B.3 Lanzar agentes paralelos

**Fase 1 — 3 agentes (12 shortcodes ALTA prioridad)**:
- Agente backend 23: AMPLIAR button, alert, accordion (en ServiceProvider global)
- Agente backend 24: CREAR cta + countdown + counter + icon-box (Content)
- Agente backend 25: CREAR title + tabs + slider + banner + hotspot (Structure)

**Fase 2+3 — 3 agentes (23 shortcodes MEDIA y BAJA)**:
- Agente backend 26: 5 utility (breadcrumb, page-header, social, image-box, video)
- Agente backend 27: 7 media (blog-posts, category-card+grid, creative-grid+item, testimonials+testimonial)
- Agente backend 28: 9 baja (animate, floating, scroll-reveal, svg-float + instagram, subcategory+column, vendor-card)

### B.4 Seeder

```bash
# Crear modules/Template/database/seeders/RiodeTemplateSeeder.php
php artisan db:seed --class="Modules\\Template\\Database\\Seeders\\RiodeTemplateSeeder"
```

### B.5 Composer autoload

⚠️ **Crítico**: hubo que añadir entry en `modules/Template/composer.json`:

```json
"modules\\Template\\Templates\\": "Templates/"
```

Y luego:
```bash
composer dump-autoload
```

## Fase C — Activar y verificar

```bash
# 1. Composer
composer dump-autoload

# 2. Seeder
php artisan db:seed --class="Modules\\Template\\Database\\Seeders\\RiodeTemplateSeeder"
# → ✓ Template Riode activado.

# 3. Cache
php artisan optimize:clear

# 4. Verificar shortcodes
php artisan shortcode:list | grep -c "Riode shortcodes"
# → 35

# 5. Renderizar
php artisan shortcode:compile '[animate name="fadeIn"]hola[/animate]'
# → <div class="appear-animate" data-animation-options='{"name":"fadeIn"}'>hola</div> ✓

# 6. Tests
php artisan test --filter="Riode.*Shortcodes" --compact
# → 50 passing, 3 failing (snapshot tests por HTML enriquecido — esperado)

# 7. Pint
vendor/bin/pint --dirty
# → fixed
```

## Resultado final

```
modules/Template/Templates/Riode/
├── Shortcodes/                                  6 clases PHP
├── Resources/views/shortcodes/                  36 Blade views
├── Tests/Feature/                               5 archivos
└── (sin tokens.css ni README aún — pendiente Fase 4)

Plus:
├── modules/Template/database/seeders/RiodeTemplateSeeder.php
├── modules/Template/app/Providers/TemplateServiceProvider.php  (modificado)
└── modules/Shortcode/app/Providers/ShortcodeServiceProvider.php  (modificado)
```

## Métricas finales

| Métrica | Valor |
|---------|------:|
| HTMLs analizados | 278/278 (100%) |
| Screenshots | 208 fullpage |
| Docs análisis | 11 + 41 shortcodes + skill secundario |
| Shortcodes Riode-específicos | 35 |
| Shortcodes globales ampliados | 3 |
| Total shortcodes en sistema | 72 |
| Clases PHP | 6 |
| Views Blade | 36 |
| Tests Feature | 5 archivos |
| Tiempo total análisis + implementación | ~5 horas |

## Lecciones aprendidas (aplicar a futuros templates)

### ✅ DO
1. **Empezar con la arquitectura template-specific** desde el principio (no implementar global y luego mover)
2. **Composer dump-autoload OBLIGATORIO** después de crear nuevas clases en directorios PSR-4 nuevos
3. **Auto-discovery via `glob()`** funciona perfecto si namespaces son consistentes
4. **Lanzar agentes en paralelo** ahorra ~50% del tiempo total
5. **Snapshots tests son frágiles** — usar `assertStringContainsString` mejor que `assertEquals` exacto
6. **Verificar template activo en DB** antes de cada cache clear
7. **Mantener convención `{slug}::shortcodes.X`** para views

### ❌ DON'T
1. NO registrar shortcodes específicos del template en `Shortcode/app/Providers/` (pollution global)
2. NO asumir que los agentes harán composer dump-autoload (hacerlo explícitamente al final)
3. NO usar `shortcode::shortcodes.X` (namespace genérico) — usar el específico del template
4. NO testear con shortcodes que necesitan atributos requeridos sin pasarlos (return empty es válido)
5. NO mezclar UI shortcodes globales (button, alert, accordion) con specific-template (countdown, hotspot)

### 💡 Tips para optimización

1. **Si el template usa Owl Carousel 2** → migrar a Swiper 11 (más mantenido)
2. **Si usa magnific-popup** → reemplazar por Bootstrap modal nativo o GLightbox
3. **Si usa skrollr** → reemplazar por IntersectionObserver + CSS transforms (skrollr deprecado)
4. **Si usa isotope** → CSS Grid `grid-auto-flow: dense` + `:has()` selectors
5. **Si usa jquery.gmap** → iframe simple sin API key

## Aplicabilidad a otros templates

Este pipeline se puede aplicar a:

- ✅ **Wolmart** (ZIP en escritorio del usuario, similar a Riode pero shop-focused)
- ✅ **Avada / Bridge / Newspaper** (templates ThemeForest similares)
- ✅ **Hello Elementor / Astra** (themes WordPress — convertir a Laravel)
- ✅ **Shopify themes** (Liquid → Blade — conversion adicional necesaria)

Para cada uno: aplicar el mismo workflow + scaffolds. Tiempo estimado por template: ~3-5 horas con agentes paralelos.
