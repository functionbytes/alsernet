# 📐 Análisis de Estructura de Plantillas - inoqualab

## Objetivo

Estandarizar todas las plantillas (default, full-width, landing, wowy) bajo una estructura consistente y modular inspirada en Wowy.

---

## 🏗️ Estructura Estándar de Plantilla

```
platform/themes/{template-name}/
│
├── 📋 METADATA & CONFIG
│   ├── template.json          ← Metadata inoqualab
│   ├── theme.json             ← Metadata Botble (opcional)
│   ├── config.php             ← Configuración
│   └── webpack.mix.js         ← Build configuration
│
├── 📚 LAYOUTS (13 mínimo recomendado)
│   ├── default.blade.php
│   ├── homepage.blade.php
│   ├── full-width.blade.php
│   ├── blog-left-sidebar.blade.php
│   ├── blog-right-sidebar.blade.php
│   ├── blog-full-width.blade.php
│   ├── product-left-sidebar.blade.php
│   ├── product-right-sidebar.blade.php
│   ├── product-full-width.blade.php
│   └── ... (más layouts específicos)
│
├── 🧩 PARTIALS (20+ componentes reutilizables)
│   ├── header.blade.php
│   ├── footer.blade.php
│   ├── sidebar.blade.php
│   ├── breadcrumb.blade.php
│   ├── pagination.blade.php
│   ├── main-menu.blade.php
│   ├── mobile-menu.blade.php
│   ├── cart-panel.blade.php
│   ├── language-switcher.blade.php
│   ├── quick-view.blade.php
│   └── shortcodes/
│       └── ... (shortcodes específicos)
│
├── 👁️ VIEWS (Páginas especializadas)
│   ├── templates/
│   │   ├── page.blade.php
│   │   ├── post.blade.php
│   │   └── product.blade.php
│   └── ecommerce/
│       ├── brand.blade.php
│       ├── customers/
│       ├── product/
│       └── ...
│
├── 🔧 FUNCTIONS (Funciones PHP personalizadas)
│   ├── functions.php           ← Funciones generales
│   ├── helpers.php             ← Helpers
│   ├── hooks.php               ← Hooks/events
│   └── ... (más funciones)
│
├── 🎨 ASSETS (Fuentes/estilos originales)
│   ├── js/
│   │   └── ... (archivos SCSS originales)
│   └── sass/
│       └── ... (archivos SCSS originales)
│
├── 📦 PUBLIC (Assets compilados - distribución)
│   ├── css/
│   │   ├── vendors/
│   │   ├── plugins/
│   │   └── style.css
│   ├── js/
│   │   ├── vendor/
│   │   ├── plugins/
│   │   └── main.js
│   ├── fonts/
│   │   └── ... (fuentes TTF, WOFF, etc.)
│   ├── images/
│   │   └── ... (imágenes de la plantilla)
│   └── plugins/
│       └── ... (librerías JS/CSS de terceros)
│
├── 🛠️ WIDGETS (Componentes reutilizables avanzados)
│   ├── featured-products/
│   ├── product-categories/
│   ├── recent-posts/
│   ├── newsletter/
│   ├── custom-menu/
│   └── ... (más widgets)
│
├── 🌐 LANG (Traducciones)
│   ├── en.json                ← English
│   └── es.json                ← Español
│
├── 🛣️ ROUTES (Rutas específicas de la plantilla)
│   └── web.php
│
├── 📂 SRC (Código fuente PHP avanzado)
│   ├── Http/
│   │   ├── Controllers/
│   │   └── Middleware/
│   ├── Models/
│   └── Services/
│
├── 🖼️ screenshot.png           ← Preview imagen (400x300 PNG)
└── README.md                  ← Documentación
```

---

## 📊 Comparativa de Estructuras

### Plantillas Actuales (default, full-width, landing)

**Estado Actual**: ⚠️ BÁSICO
```
├── assets/            (vacío o mínimo)
├── layouts/           (1 archivo .blade.php)
├── partials/          (2-3 archivos .blade.php)
├── config.php
├── template.json
└── screenshot.png
```

**Problemas**:
- ❌ Solo 1 layout por plantilla (no flexible)
- ❌ Partials mínimos (header, footer)
- ❌ Sin functions, helpers, hooks
- ❌ Sin widgets
- ❌ Sin traducciones
- ❌ Sin vistas especializadas
- ❌ Sin rutas personalizadas

### Plantilla Wowy

**Estado**: ✅ COMPLETO
```
├── assets/            (js, sass compilables)
├── public/            (css, js, fonts, images compilados)
├── layouts/           (13 archivos .blade.php)
├── partials/          (19+ componentes .blade.php)
├── views/             (vistas especializadas ecommerce)
├── functions/         (7 archivos PHP)
├── widgets/           (11 widgets avanzados)
├── lang/              (en.json, es.json)
├── routes/            (web.php)
├── src/               (Controllers, Services)
├── config.php
├── template.json
├── theme.json
├── webpack.mix.js
└── screenshot.png
```

**Ventajas**:
- ✅ 13 layouts flexibles
- ✅ 20+ partials modulares
- ✅ Vistas especializadas
- ✅ Funciones personalizadas
- ✅ Widgets avanzados
- ✅ Sistema de traducciones
- ✅ Build automation (webpack)
- ✅ Código fuente modular

---

## 🎯 Plan de Estandarización

### Fase 1: Expandir Plantillas Básicas

Para **default**, **full-width**, y **landing**:

1. **Crear Layouts Adicionales** (mínimo 5 por plantilla)
   - Mantener layout actual como `default.blade.php`
   - Agregar: homepage, blog-sidebar, product, full-width variants

2. **Expandir Partials** (mínimo 15 por plantilla)
   - header, footer, sidebar, breadcrumb
   - menu, mobile-menu, cart-panel, pagination
   - language-switcher, quick-view, etc.

3. **Agregar Carpeta Functions**
   - functions.php - funciones generales
   - helpers.php - helper functions
   - hooks.php - hooks/events

4. **Agregar Carpeta Lang**
   - en.json, es.json con strings de UI

5. **Agregar Carpeta Views**
   - templates/ - páginas especializadas
   - ecommerce/ - vistas ecommerce opcionales

6. **Agregar Carpeta Widgets** (opcional)
   - featured-products, product-categories, newsletter, etc.

7. **Mejorar Public Assets**
   - Compilar/mejorar CSS
   - Agregar fonts, icons
   - Organizar plugins

8. **Agregar webpack.mix.js**
   - Para compilación de assets

9. **Crear README.md**
   - Documentación de cada plantilla

### Fase 2: Verificar Consistencia

- ✅ Todas las plantillas siguen estructura estándar
- ✅ Todos los layouts son renderizables
- ✅ Todos los partials se incluyen correctamente
- ✅ Las traducciones funcionan
- ✅ Los widgets se cargan

### Fase 3: Documentación

- ✅ Documentar estructura de cada plantilla
- ✅ Guías de personalización
- ✅ Ejemplos de uso

---

## 📁 Estructura Específica para Default

```
platform/themes/default/
│
├── template.json                  ← Metadata
├── config.php                     ← Configuración
├── webpack.mix.js                 ← Build config
│
├── layouts/                       ← 9 layouts
│   ├── default.blade.php         ← Principal
│   ├── homepage.blade.php        ← Homepage
│   ├── full-width.blade.php      ← Sin sidebars
│   ├── blog-left.blade.php       ← Blog sidebar izq
│   ├── blog-right.blade.php      ← Blog sidebar der
│   ├── blog-full.blade.php       ← Blog ancho completo
│   ├── product-left.blade.php    ← Producto sidebar izq
│   ├── product-right.blade.php   ← Producto sidebar der
│   └── product-full.blade.php    ← Producto ancho completo
│
├── partials/                      ← 18 partials
│   ├── header.blade.php
│   ├── footer.blade.php
│   ├── sidebar.blade.php
│   ├── breadcrumb.blade.php
│   ├── pagination.blade.php
│   ├── main-menu.blade.php
│   ├── mobile-menu.blade.php
│   ├── cart-panel.blade.php
│   ├── language-switcher.blade.php
│   ├── quick-view.blade.php
│   ├── product-card.blade.php
│   ├── post-card.blade.php
│   ├── category-list.blade.php
│   ├── preloader.blade.php
│   └── shortcodes/
│       ├── product-grid.blade.php
│       ├── product-slider.blade.php
│       └── testimonials.blade.php
│
├── views/                         ← Vistas especializadas
│   ├── templates/
│   │   ├── page.blade.php
│   │   ├── post.blade.php
│   │   └── product.blade.php
│   └── ecommerce/ (opcional)
│       ├── brand.blade.php
│       ├── product/index.blade.php
│       └── ...
│
├── functions/                     ← Funciones PHP
│   ├── functions.php             ← Funciones generales
│   ├── helpers.php               ← Helpers
│   └── hooks.php                 ← Hooks/Events
│
├── lang/                          ← Traducciones
│   ├── en.json
│   └── es.json
│
├── assets/                        ← Fuentes originales
│   ├── js/
│   └── sass/
│
├── public/                        ← Assets compilados
│   ├── css/
│   │   ├── vendors/
│   │   ├── plugins/
│   │   └── style.css
│   ├── js/
│   │   ├── vendor/
│   │   └── main.js
│   ├── fonts/
│   └── images/
│
├── widgets/                       ← Componentes avanzados (opcional)
│   ├── featured-products/
│   ├── newsletter/
│   └── ...
│
├── routes/                        ← Rutas personalizadas (opcional)
│   └── web.php
│
├── screenshot.png                 ← Preview
└── README.md                      ← Documentación
```

---

## 🔄 Flujo de Renderizado de Plantilla

```
1. Usuario accede a página
   ↓
2. Controller obtiene Template activo (default, wowy, etc)
   ↓
3. TemplateManager carga metadata de template.json
   ↓
4. Obtiene config.php (opciones, assets, widgets)
   ↓
5. Carga functions.php, helpers.php, hooks.php
   ↓
6. Carga lang/{locale}.json (traducciones)
   ↓
7. Selecciona layout apropiado:
   - Si es blog post → blog-left.blade.php
   - Si es producto → product-full.blade.php
   - Si es página → default.blade.php
   ↓
8. Layout carga partials:
   - @include('template::partials.header')
   - @include('template::partials.sidebar')
   - @yield('content') ← contenido principal
   - @include('template::partials.footer')
   ↓
9. Partials cargan sub-componentes:
   - header incluye menu, cart, language-switcher
   - sidebar incluye widgets, categorías
   - footer incluye newsletter, links, copyright
   ↓
10. JavaScript de public/js/ se carga
   ↓
11. CSS de public/css/ se aplica (con RTL si aplica)
   ↓
12. Página renderizada completamente
```

---

## 🎨 Sistema de Layout Jerárquico

### Niveles:

```
1. LAYOUT (default.blade.php)
   ├─ @include('header') ← partial
   │  ├─ @include('main-menu') ← sub-partial
   │  ├─ @include('cart-panel') ← sub-partial
   │  └─ @include('language-switcher') ← sub-partial
   │
   ├─ @yield('content') ← contenido de página
   │
   ├─ @include('sidebar') ← partial (if aplica)
   │  ├─ Widget: featured-products
   │  ├─ Widget: categories
   │  └─ Widget: newsletter
   │
   └─ @include('footer') ← partial
      ├─ @include('newsletter-form') ← sub-partial
      └─ @include('footer-links') ← sub-partial
```

---

## ✅ Checklist de Estandarización

Para cada plantilla (default, full-width, landing):

- [ ] 9+ layouts diferentes
- [ ] 15+ partials modulares
- [ ] functions.php con funciones generales
- [ ] helpers.php con funciones helper
- [ ] hooks.php con eventos/filters
- [ ] lang/en.json y lang/es.json
- [ ] views/templates/ con página generales
- [ ] public/css/ con estilos compilados
- [ ] public/js/ con scripts compilados
- [ ] public/fonts/ con tipografías
- [ ] public/images/ con imágenes
- [ ] webpack.mix.js para build
- [ ] README.md documentación
- [ ] screenshot.png (400x300)
- [ ] config.php actualizado
- [ ] template.json actualizado

---

## 🎯 Beneficios de Estandarización

1. **Consistencia** - Todas las plantillas siguen mismo patrón
2. **Flexibilidad** - Múltiples layouts para diferentes tipos de contenido
3. **Modularidad** - Partials reutilizables
4. **Escalabilidad** - Fácil agregar más plantillas
5. **Mantenibilidad** - Código organizado y documentado
6. **Performance** - Assets compilados y optimizados
7. **Traducciones** - Soporte multiidioma integrado
8. **Extensibilidad** - Función, widgets, rutas personalizadas

---

## 📋 Siguiente Paso

Implementar esta estructura estándar en:
1. ✅ **wowy** - Ya completo
2. ⏳ **default** - Expandir estructura
3. ⏳ **full-width** - Expandir estructura
4. ⏳ **landing** - Expandir estructura

Objetivo: Todas las plantillas funcionan bajo la misma arquitectura modular.
