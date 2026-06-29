---
name: template-builder
description: >
  Convierte una plantilla HTML estática (Riode, Wolmart, ThemeForest, etc.) en un template Laravel
  funcional con shortcodes específicos del template. Auto-genera la estructura completa
  `modules/Template/Templates/{Name}/`, clases PHP de shortcodes, views Blade, seeder DB y tests.
  Usa esta skill cuando el usuario pida: "genera template desde HTML", "convierte plantilla X a
  Laravel", "analiza esta plantilla y crea sus shortcodes", "necesito portar el theme {name}",
  "crea template a partir de demo", "transforma HTML en módulo Template", "instala plantilla
  HTMLtoLaravel". También cuando proporcione una URL/carpeta de plantilla HTML/ThemeForest
  y diga "crea su template" o "extrae sus shortcodes".
compatibility:
  framework: Laravel 12 + nwidart/laravel-modules
  frontend: Bootstrap 5.3 + jQuery + Font Awesome 6
  module: Template (con sistema de shortcodes template-specific ya implementado)
  base_skill: riode-frontend (análisis de Riode como referencia)
---

# Template Builder Skill

> Convierte cualquier plantilla HTML estática en un template Laravel funcional con shortcodes
> auto-registrados al activar el template en DB.

## 🎯 Qué hace

Dado un input (URL, carpeta HTML, o ZIP de plantilla), genera:

1. **Estructura completa** `modules/Template/Templates/{Name}/`:
   - `Shortcodes/` — clases PHP con `registerAll()`
   - `Resources/views/shortcodes/` — Blade views por shortcode
   - `Tests/Feature/` — tests Feature
2. **Seeder** que activa el template en DB (`status='active'`)
3. **Auto-registro** vía `TemplateServiceProvider::registerActiveTemplateShortcodes()` (ya existe)
4. **Composer autoload** entry actualizado
5. **Documentación** del template generado (paleta, fonts, shortcodes, uso)

## 🚀 Cuándo usar esta skill

ACTIVAR cuando el usuario diga:
- "genera template desde esta plantilla HTML"
- "convierte {nombre} a Laravel" (Wolmart, Riode, Avada, Bridge, etc.)
- "porta este theme HTML a mi sistema"
- "analiza esta carpeta y crea sus shortcodes"
- "crea el template a partir de demo X"
- "transforma plantilla en módulo Template"
- Provee URL de demo de ThemeForest + dice "crear template"

NO usar para:
- Modificar shortcodes ya existentes (usa skill `riode-frontend`)
- Crear shortcodes globales no atados a template
- Análisis de Riode específicamente (ya hecho — usa `riode-frontend`)

## 📋 Inputs requeridos

Pedir al usuario:

1. **Carpeta o URL** de la plantilla HTML
2. **Nombre del template** (slug — ej: "wolmart", "avada", "bridge")
3. **Confirmación** de paleta primaria (sustituir color del template por brand del proyecto)
4. **Scope inicial**: ¿Fase 1 (shortcodes ALTA prioridad ~12) o full (todos)?

## 🔄 Workflow del análisis

Sigue este pipeline (detalle en `references/workflow.md`):

### Fase A — Análisis del HTML (paralelo)

1. Inventariar archivos `.html`
2. Categorizar por tipo (homepage, shop, product, blog, element, utility)
3. Capturar screenshots con Chrome DevTools (puerto 8765)
4. Identificar shortcodes candidatos en archivos `element-*.html` o equivalentes
5. Extraer design tokens (colores, fonts, spacing)
6. Documentar componentes deep-dive (header, modales, forms)

### Fase B — Generar estructura Laravel (paralelo)

1. Crear `modules/Template/Templates/{Name}/` directory tree
2. Para cada categoría de shortcodes (Content, Structure, Utility, Media, Effects, Marketplace):
   - Crear clase `{Name}{Category}Shortcodes.php` con `registerAll()`
   - Crear views Blade en `Resources/views/shortcodes/`
   - Crear tests Feature en `Tests/Feature/`
3. Crear seeder `{Name}TemplateSeeder.php`
4. Actualizar `modules/Template/composer.json` autoload

### Fase C — Activar y verificar

1. `composer dump-autoload`
2. `php artisan db:seed --class={Name}TemplateSeeder`
3. `php artisan optimize:clear`
4. `php artisan shortcode:list` → verificar shortcodes nuevos cargados
5. Renderizar 3-5 shortcodes de prueba
6. Run tests

## 📁 Estructura del Skill

```
.claude/skills/template-builder/
├── SKILL.md                              # Este archivo
├── references/
│   ├── workflow.md                       # Pipeline detallado por fase
│   ├── template-structure.md             # Estructura de output esperada
│   ├── shortcode-patterns.md             # Patrones de cierre PHP por categoría
│   ├── conventions.md                    # Reglas obligatorias del proyecto
│   ├── design-tokens-extraction.md       # Cómo extraer colores/fonts del CSS
│   └── verification.md                   # Checklist de verificación final
├── scaffolds/
│   ├── shortcode-class.php.tpl           # Plantilla clase PHP con registerAll()
│   ├── blade-view.blade.tpl              # Plantilla Blade view
│   ├── feature-test.php.tpl              # Plantilla test Feature
│   ├── seeder.php.tpl                    # Plantilla seeder
│   └── readme-template.md.tpl            # Plantilla README del template
└── examples/
    └── riode-walkthrough.md              # Cómo se hizo Riode paso a paso
```

## 🔑 Convenciones obligatorias

(Detalle en `references/conventions.md`)

- ❌ NO `style=""` inline (excepción: bg-image dinámica via `data-bg-image`)
- ✅ Font Awesome 6 ONLY (`fas fa-*`, `far fa-*`, `fab fa-*`) — NUNCA `d-icon-*` ni Tabler
- ✅ jQuery + Bootstrap 5.3 nativo (NO Livewire/React/Inertia/Alpine)
- ✅ Color primario `#90bb13` (Alsernet) — sustituir el del template original
- ✅ Vanilla JS preferido sobre plugins jQuery pesados
- ✅ `prefers-reduced-motion` en animaciones
- ✅ `loading="lazy"` y dimensiones explícitas en imágenes
- ✅ Multi-idioma con `__('shortcode::messages.X')`
- ✅ `htmlspecialchars()` para XSS en atributos
- ✅ Validar enums con fallback a default
- ✅ Tests Feature con happy path + edge cases

## 📊 Patrón de los shortcodes generados

(Detalle en `references/shortcode-patterns.md`)

Cada clase sigue este patrón EXACTO:

```php
<?php
namespace Modules\Template\Templates\{Name}\Shortcodes;

use Modules\Shortcode\Compiler\ShortcodeCompiler;

class {Name}{Category}Shortcodes
{
    public function __construct(private readonly ShortcodeCompiler $compiler) {}

    public function registerAll(): void
    {
        $this->register{Shortcode1}();
        $this->register{Shortcode2}();
        // ...
    }

    protected function register{Shortcode}(): void
    {
        $this->compiler->register('{shortcode-name}', function (array $attrs, string $content): string {
            return view('{slug}::shortcodes.{shortcode-name}', compact('attrs', 'content'))->render();
        }, [
            'description' => '...',
            'example'     => '[{shortcode-name} attr="value"][/{shortcode-name}]',
            'attributes'  => ['attr' => 'descripción'],
        ]);
    }
}
```

## 🎬 Ejemplo de uso (cómo activar la skill)

### Caso 1: Plantilla en carpeta local

Usuario dice:
> "Tengo la plantilla Wolmart en `/Users/me/Desktop/Plantillas/wolmart/`. Genera el template Laravel con sus shortcodes."

Tu acción:
1. Lee `references/workflow.md`
2. Ejecuta Fase A (análisis) en paralelo con agentes
3. Ejecuta Fase B (generar) usando `scaffolds/`
4. Ejecuta Fase C (activar) con composer + seeder + tests
5. Reporta: shortcodes registrados, estructura creada, tests passing

### Caso 2: URL de demo ThemeForest

Usuario:
> "Crea el template Avada a partir de https://avada.theme-fusion.com/classic/"

Tu acción:
1. WebFetch URL inicial + páginas clave (homepage, shop, contact)
2. Pedir al usuario el ZIP completo si solo hay URL (assets necesarios)
3. Aplicar mismo workflow

### Caso 3: Solo design tokens (sin shortcodes)

Usuario:
> "Solo quiero extraer la paleta y fonts de esta plantilla, no los shortcodes"

Tu acción:
1. Saltar Fase B/C
2. Solo Fase A.5 (Extract design tokens)
3. Generar `Templates/{Name}/tokens.css` con variables CSS
4. NO crear clases ni seeder

## 📚 Documentos de referencia

Lee estos en orden cuando se active la skill:

1. **`references/workflow.md`** — Pipeline completo por fase (CRÍTICO leer primero)
2. **`references/template-structure.md`** — Estructura final esperada
3. **`references/shortcode-patterns.md`** — Cómo escribir cada tipo de shortcode
4. **`references/conventions.md`** — Reglas del proyecto Alsernet
5. **`references/design-tokens-extraction.md`** — Extraer paleta/fonts del CSS
6. **`references/verification.md`** — Checklist final

Para casos específicos:
- **`examples/riode-walkthrough.md`** — Caso real completo (Riode template)

## 🔗 Skills relacionadas

- **`riode-frontend`** (en `riode-frontend-skill/`) — Análisis específico de Riode (ya hecho)
- Esta skill (`template-builder`) — Skill GENÉRICA para CUALQUIER plantilla

## ⚙️ Sistema actual del proyecto

Antes de empezar, verifica que el sistema tiene:

```bash
# 1. Módulo Template con sistema template-specific
ls /Users/developerts/Herd/system/modules/Template/Templates/

# 2. TemplateServiceProvider con registerActiveTemplateShortcodes()
grep "registerActiveTemplateShortcodes" /Users/developerts/Herd/system/modules/Template/app/Providers/TemplateServiceProvider.php

# 3. Composer autoload PSR-4 incluye Templates/
grep "Templates" /Users/developerts/Herd/system/modules/Template/composer.json

# 4. Módulo Shortcode con ShortcodeCompiler
ls /Users/developerts/Herd/system/modules/Shortcode/app/Compiler/
```

Si alguno NO existe, primero implementar la arquitectura template-specific (referirse a `examples/riode-walkthrough.md`).

## 🧠 Decisiones automáticas que la skill toma

Cuando se invoca, la skill decide:

| Pregunta | Decisión por defecto |
|----------|---------------------|
| ¿Cuántos agentes en paralelo? | 3 si Fase 1, 5 si full |
| ¿Capturar screenshots? | Sí (puerto 8765) |
| ¿Usar Owl Carousel o Swiper? | Swiper (más mantenido) |
| ¿Magnific Popup o GLightbox? | Bootstrap 5 modal nativo > GLightbox |
| ¿Iconos d-icon-*? | Mapear a FA6 obligatorio |
| ¿Color primario template? | Sustituir por `#90bb13` Alsernet |
| ¿Activar template inmediatamente? | Sí, vía seeder con `status='active'` |
| ¿Crear tests? | Sí, ≥1 happy path + 1 edge case por shortcode |

Si el usuario quiere overridear, pregunta antes de empezar.

## 🛡️ Marcadores especiales en output

Usar al documentar:
- `[INFERIDO]` — valor estimado visualmente, no extraído del CSS
- `[VERIFICAR]` — valor encontrado pero poco confiable
- `[INCONSISTENCIA]` — mismo componente con valores distintos en distintas páginas
- `[JS-DEPENDIENTE]` — comportamiento requiere JavaScript
- `[TODO]` — feature pendiente (e.g., "TODO: connect to Blog module")

## 📌 Versionado

- **v1.0** (2026-04-28) — Skill inicial basada en experiencia de Riode template
- Próxima versión: añadir soporte para themes WordPress/Shopify (no solo HTML estático)
