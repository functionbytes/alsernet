# Template Riode

> **Origen**: [Riode HTML — D-Themes](https://themeforest.net/item/riode-multipurpose-html-template/27867306)
> **Versión**: 1.0.0
> **Adaptado para**: Alsernet/Caixilharia/Inoqualab
> **Generado**: 2026-04-28

---

## Resumen

Template basado en la plantilla Riode (D-Themes) de ThemeForest. Provee 35 shortcodes específicos cubriendo todas las categorías: contenido, estructura, utility, media, efectos y marketplace.

Color primario `#26c` o `#d26e4b` original sustituido por `#90bb13` (verde Alsernet).

## Cómo activar

### Opción A — Vía seeder

```bash
cd /Users/developerts/Herd/system
php artisan db:seed --class="Modules\\Template\\Database\\Seeders\\RiodeTemplateSeeder"
php artisan optimize:clear
```

### Opción B — Vía SQL

```sql
UPDATE templates SET status='inactive' WHERE slug != 'riode';
UPDATE templates SET status='active' WHERE slug='riode';
```

```bash
php artisan optimize:clear
```

### Verificar activación

```bash
php artisan shortcode:list | grep -E "(cta|countdown|hotspot|banner)"
# Deben aparecer 35 shortcodes
```

---

## Shortcodes específicos (35)

### Content (7)

| Shortcode | Descripción | Ejemplo |
|-----------|-------------|---------|
| `[cta]` | Call-to-action con 6 layouts | `[cta layout="2-cols"]...[/cta]` |
| `[cta-column]` | Child de cta para layout 3-cols | `[cta-column]...[/cta-column]` |
| `[countdown]` | Cuenta regresiva 5 estilos | `[countdown until="2026-12-31T23:59:59" style="default"]` |
| `[counter]` | Contador animado IntersectionObserver | `[counter number="1500" suffix="+" label="Clientes"]` |
| `[counter-grid]` | Grid wrapper para counters | `[counter-grid cols="4"]...[/counter-grid]` |
| `[icon-box]` | Caja con icono FA6 + título + descripción | `[icon-box icon="fa-truck" title="Envío gratis"]` |
| `[icon-box-grid]` | Grid wrapper para icon-boxes | `[icon-box-grid cols="3"]...[/icon-box-grid]` |

### Structure (8)

| Shortcode | Descripción | Ejemplo |
|-----------|-------------|---------|
| `[title]` | Títulos estilizados 9 variantes | `[title text="Servicios" style="centered"]` |
| `[tabs]` | Tabs Bootstrap 5 nativo (9 estilos) | `[tabs style="underline"]...[/tabs]` |
| `[tab]` | Child de tabs | `[tab title="Detalles" active="true"]...[/tab]` |
| `[slider]` | Slider/carousel (Owl o Swiper) | `[slider autoplay="true" effect="fade"]...[/slider]` |
| `[slide]` | Child de slider | `[slide image="..."]...[/slide]` |
| `[banner]` | Banner promocional 5 estilos + 18 effects | `[banner style="big" image="..." title="..."]` |
| `[hotspot]` | Imagen con puntos interactivos | `[hotspot image="room.jpg"]...[/hotspot]` |
| `[hotspot-pin]` | Pin individual con tooltip | `[hotspot-pin top="35%" left="40%"]...[/hotspot-pin]` |

### Utility (5)

| Shortcode | Descripción | Ejemplo |
|-----------|-------------|---------|
| `[breadcrumb]` | Migas de pan 4 estilos | `[breadcrumb separator="chevron"]` |
| `[page-header]` | Hero header con bg-image + overlay | `[page-header title="Sobre" overlay="dark"]` |
| `[social-links]` | 13 redes mapeadas a FA6 | `[social-links networks='[{"type":"facebook"}]']` |
| `[image-box]` | Perfiles de equipo (foto + cargo + redes) | `[image-box image="..." name="..." job="..."]` |
| `[video]` | Video 3 modos (popup/inline/background) | `[video mode="popup" url="..."]` |

### Media (7)

| Shortcode | Descripción | Ejemplo |
|-----------|-------------|---------|
| `[blog-posts]` | Listado dinámico de posts (6 layouts) | `[blog-posts layout="grid" cols="3" limit="6"]` |
| `[category-card]` | Card de categoría 11 estilos | `[category-card image="..." name="..." count="245"]` |
| `[category-grid]` | Grid wrapper de categorías | `[category-grid cols="4"]...[/category-grid]` |
| `[creative-grid]` | Grid mosaico CSS Grid | `[creative-grid gutter="3"]...[/creative-grid]` |
| `[grid-item]` | Item del creative-grid | `[grid-item cols-xl="6" height="x2" image="..."]` |
| `[testimonials]` | Testimonios 5 layouts | `[testimonials layout="centered-border" cols="3"]...[/testimonials]` |
| `[testimonial]` | Child de testimonials | `[testimonial author="..." rating="5"]...[/testimonial]` |

### Effects (4)

| Shortcode | Descripción | Ejemplo |
|-----------|-------------|---------|
| `[animate]` | Wrapper animate.css 46+ nombres | `[animate name="fadeInUp"]...[/animate]` |
| `[floating]` | Mouse parallax decorativo | `[floating depth="0.3"]<img...>[/floating]` |
| `[scroll-reveal]` | Parallax al scroll IntersectionObserver | `[scroll-reveal]...[/scroll-reveal]` |
| `[svg-float]` | SVG decorativo flotante CSS animation | `[svg-float src="..." position="bottom-left"]` |

### Marketplace (4 — opcional para multi-vendor)

| Shortcode | Descripción | Ejemplo |
|-----------|-------------|---------|
| `[instagram-feed]` | Feed Instagram (placeholder si sin API) | `[instagram-feed username="alsernet"]` |
| `[subcategory-card]` | Card de subcategoría con icono + lista | `[subcategory-card icon="..." title="..."]` |
| `[category-column]` | Columna de subcategorías | `[category-column cols="3"]...[/category-column]` |
| `[vendor-card]` | Card de vendor (multi-vendor opcional) | `[vendor-card name="..." logo="..."]` |

---

## Globales ampliados (3)

Estos shortcodes son **globales** (siempre activos), pero recibieron variantes nuevas inspiradas en Riode:

### `[button]` (+10 atributos nuevos)

```
[button style="primary" color="default" shape="rounded" size="lg" 
        shadow="sm" icon="fa-arrow-right" icon-position="right"
        animation="slide-left" infinite="true" disabled="false"
        underline="false" url="/comprar"]Comprar ahora[/button]
```

### `[alert]` (+8 atributos nuevos)

```
[alert type="success" style="filled" layout="default" 
       title="¡Listo!" icon="auto" round="true"
       button-text="Continuar" button-href="/next"
       link-href="..." dismissible="true"]
Operación exitosa
[/alert]
```

### `[accordion]` + `[accordion-item]` (+9 atributos nuevos)

```
[accordion style="boxed" color="primary" icon-style="chevron" 
           gutter="3" card-border="false" border="true"
           multi-open="false"]
  [accordion-item title="Pregunta 1" icon="fa-question" open="true"]
    Respuesta...
  [/accordion-item]
[/accordion]
```

---

## Design Tokens

Ver `tokens.css` para variables CSS completas.

### Color primario sustituido

| Original Riode | Alsernet (sustituye) | Variable CSS |
|----------------|----------------------|--------------|
| `#26c` (azul) o `#d26e4b` (terracota) | **`#90bb13`** (verde) | `--color-primary` |

### Fonts

- **Base**: Poppins (Google Fonts)
- **Display por vertical**: Jost (beauty/diamart/yoga), Kalam (food2), Delius (tea), Rammetto One (food3)
- Override automático con `[data-demo="X"]` en el `<html>`

---

## Estructura del template

```
modules/Template/Templates/Riode/
├── Shortcodes/                                    6 clases PHP
│   ├── RiodeContentShortcodes.php
│   ├── RiodeStructureShortcodes.php
│   ├── RiodeUtilityShortcodes.php
│   ├── RiodeMediaShortcodes.php
│   ├── RiodeEffectsShortcodes.php
│   └── RiodeMarketplaceShortcodes.php
├── Resources/
│   └── views/
│       └── shortcodes/                            36 Blade views
├── Tests/
│   └── Feature/                                   5 archivos test
├── tokens.css                                     Variables CSS
├── metadata.json                                  Info del template
└── README.md                                      Este archivo
```

---

## Plugins JS

### Eliminados / reemplazados

| Plugin original Riode | Reemplazo |
|------------------------|-----------|
| owl-carousel 2 | **Swiper 11** (cuando aplica) |
| magnific-popup | **Bootstrap 5 modal nativo** |
| sticky.min.js | **CSS `position: sticky`** |
| skrollr (deprecado) | **IntersectionObserver + CSS transforms** |
| isotope | **CSS Grid `grid-auto-flow: dense`** |
| jquery.gmap (con API key) | **iframe simple sin API key** |
| d-icon-* font | **Font Awesome 6** |

### Cargados bajo demanda (`@once + @push`)

- jQuery (ya en el proyecto)
- Bootstrap 5.3 nativo (ya cargado)
- Font Awesome 6 (ya cargado)

---

## Tests

```bash
# Todos los tests del template
php artisan test --filter="Riode.*Shortcodes" --compact

# Solo una clase
php artisan test modules/Template/Templates/Riode/Tests/Feature/RiodeContentShortcodesTest.php
```

**Estado actual**: tests creados pero algunos requieren ajustar assertions para coincidir con HTML real generado por las views.

---

## Convenciones aplicadas

- ✅ Font Awesome 6 ONLY (mapping desde `d-icon-*` originales)
- ✅ Bootstrap 5.3 nativo
- ✅ jQuery + AJAX (NO Livewire/React/Inertia/Alpine)
- ✅ NO inline styles (excepto excepciones documentadas)
- ✅ Color primario `#90bb13` (Alsernet)
- ✅ Multi-idioma con `__()`
- ✅ Tests Feature happy path + edge cases
- ✅ `loading="lazy"` y dimensiones explícitas en imágenes
- ✅ `@once + @push` para JS de plugins (carga UNA vez)
- ✅ XSS protection con `htmlspecialchars`

---

## Cambiar a otro template

```sql
-- Activar Wolmart
UPDATE templates SET status='inactive' WHERE slug='riode';
UPDATE templates SET status='active' WHERE slug='wolmart';
```

```bash
php artisan optimize:clear
```

→ Los 35 shortcodes Riode se desregistran automáticamente.
→ Se cargan los shortcodes de `modules/Template/Templates/Wolmart/Shortcodes/`.
→ Los 37 globales (button, alert, accordion, contact-form, etc.) se mantienen.

---

## Documentación adicional

- **Análisis exhaustivo**: `/Users/developerts/Desktop/Plantillas/riode-analisis/` (11 docs + 41 shortcodes individuales + 208 screenshots)
- **Skill secundario**: `/Users/developerts/Desktop/Plantillas/riode-frontend-skill/` (componentes deep-dive + design tokens visuales)
- **Skill template-builder**: `/Users/developerts/Herd/system/.claude/skills/template-builder/` (para crear futuros templates desde HTML)

---

## Marcadores especiales

Algunos shortcodes contienen marcadores que requieren validación visual o ajuste:

- `[INFERIDO]` — 6 valores estimados visualmente
- `[VERIFICAR]` — 2 valores poco confiables
- `[INCONSISTENCIA]` — 1 caso (`product-thumbnail-label` rompe regla NO inline styles)
- `[JS-DEPENDIENTE]` — 8 efectos requieren JavaScript
- `[TODO]` — algunos shortcodes (`blog-posts`, `instagram-feed`, `vendor-card`) requieren conexión con módulos externos

---

## Versionado

- **v1.0** (2026-04-28) — Versión inicial con 35 shortcodes Riode + 3 globales ampliados

## Próximos pasos sugeridos

1. **Refinar tests** — ajustar assertions para coincidir con HTML real generado
2. **Conectar `blog-posts`** con módulo Blog del proyecto
3. **Conectar `instagram-feed`** con API IG si se quiere usar
4. **Crear seeders adicionales** con contenido de demos verticales (homepage-services para Caixilharia)
5. **Generar otro template** (Wolmart, Avada, etc.) usando la skill `template-builder`
