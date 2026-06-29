# Prompt de Rediseño — Módulo HelpdeskHelpcenter

> **IMPORTANTE**: Leer `SHARED-DESIGN-SYSTEM.md` antes de procesar este prompt.  
> Stack: Bootstrap 5.3 + jQuery + Font Awesome 6. El portal público es un frontend separado con su propio layout.

---

## Contexto del Módulo

**HelpdeskHelpcenter** es el sistema de base de conocimiento (KB). Tiene 17 vistas Blade. Jerarquía: Categorías → Secciones → Artículos. Soporta 7 idiomas con gestión de traducciones por artículo, embeddings para búsqueda semántica, votación de utilidad, generación de sitemap XML y un portal público de acceso libre.

**Rutas principales**: `panel/helpdesk-helpcenter/*` (admin) y rutas públicas del portal  
**Aliases de permiso**: `helpdesk.helpcenter.*`

---

## Áreas a Rediseñar

### 1. Portal Público (Vistas públicas del Helpcenter)

El portal público es la cara visible al cliente final. Es la prioridad más alta de este módulo.

#### 1.1 Homepage del Portal (`portal/index.blade.php`)

**Estructura**:
```
┌─────────────────────────────────────────────────────────────┐
│  [Logo marca]                              [Idioma ▼] [Login]│
│═════════════════════════════════════════════════════════════│
│                                                             │
│         ¿En qué podemos ayudarte?                           │
│  ┌─────────────────────────────────────────────────────┐   │
│  │ fas fa-magnifying-glass  Busca artículos...         │   │
│  └─────────────────────────────────────────────────────┘   │
│                                                             │
│═════════════════════════════════════════════════════════════│
│                                                             │
│  CATEGORÍAS PRINCIPALES                                     │
│  ┌──────────────┐ ┌──────────────┐ ┌──────────────┐       │
│  │ [Icono]      │ │ [Icono]      │ │ [Icono]      │       │
│  │ Primeros     │ │ Facturación  │ │ Integraciones│       │
│  │ pasos        │ │              │ │              │       │
│  │ 12 artículos │ │ 8 artículos  │ │ 15 artículos │       │
│  └──────────────┘ └──────────────┘ └──────────────┘       │
│                                                             │
│  ARTÍCULOS POPULARES                                        │
│  • ¿Cómo configurar mi cuenta?                    →        │
│  • Métodos de pago aceptados                      →        │
│  • Cómo exportar mis datos                        →        │
│                                                             │
│═════════════════════════════════════════════════════════════│
│  ¿No encontraste lo que buscabas?  [Contactar soporte]     │
└─────────────────────────────────────────────────────────────┘
```

**Detalles de la barra de búsqueda**:
- Full-width, altura 52px, border-radius 8px, shadow suave
- Al escribir (debounce 300ms): dropdown con resultados instantáneos
  - Primeras 3-5 coincidencias con: título artículo + breadcrumb de categoría/sección
  - Pie del dropdown: "Ver todos los resultados para '{{query}}'" → link a página de resultados
  - Estado sin resultados: "Sin resultados para '{{query}}'. ¿Deseas contactar a soporte?"
- Soporte de búsqueda semántica (embeddings ya implementados)

**Categoria card**:
```html
<div class="hd-hc-category-card">
  <div class="hd-hc-category-card__icon">
    <!-- Icono FA6 48px, color personalizable por categoría -->
    <i class="fas fa-rocket" style="color: var(--hd-primary);"></i>
  </div>
  <h3 class="hd-hc-category-card__title">Primeros pasos</h3>
  <p class="hd-hc-category-card__count text-muted">12 artículos</p>
  <p class="hd-hc-category-card__desc text-muted small">Aprende a configurar tu cuenta en minutos.</p>
</div>
```
- Grid 3 columnas desktop, 2 tablet, 1 mobile
- Hover: shadow + translateY(-2px) suave
- Click navega a la página de categoría

**Language Switcher**:
- Botón `fas fa-globe` + código idioma actual (ES, EN, FR...)
- Dropdown con lista de idiomas disponibles en script nativo: Español, English, Français
- Detecta idioma del navegador en primera visita, guarda preferencia en cookie
- Filtra la URL base con prefijo de idioma: `/helpcenter/en/`, `/helpcenter/es/`

#### 1.2 Página de Categoría (`portal/category.blade.php`)

```
Breadcrumb: Inicio > Facturación

[Icono grande] Facturación
Gestión de facturas, pagos y suscripciones.

Secciones:
├── Pagos (4 artículos)
│   • Métodos de pago aceptados
│   • ¿Cómo solicitar una factura?
│   • Cambiar plan de suscripción
│   • Reembolsos y devoluciones
└── Configuración de facturación (3 artículos)
    • Actualizar datos fiscales
    • ...
```

- Lista de secciones con acordeón expandido por defecto
- Conteo de artículos por sección como badge
- Link a cada artículo con fecha última actualización en texto muted

#### 1.3 Página de Artículo (`portal/article.blade.php`)

```
┌──────────────────────────────────────────────┬──────────────┐
│ Breadcrumb: Inicio > Facturación > Pagos     │ EN ESTA      │
│                                              │ PÁGINA       │
│ Cómo solicitar una factura                   │ ────────     │
│                                              │ • Paso 1     │
│ Actualizado: 3 May 2026 ·  ⏱️ 3 min lectura │ • Paso 2     │
│ ──────────────────────────────────────────── │ • Resumen    │
│                                              │              │
│ [Contenido del artículo con rich HTML]       │ (sticky TOC) │
│                                              │              │
│ ──────────────────────────────────────────── │              │
│ ¿Te fue útil este artículo?                  │              │
│ [👍 Sí, gracias]  [👎 No del todo]          │              │
│                                              │              │
│ ── También puede interesarte ────────────── │              │
│ • Artículo relacionado 1                    │              │
│ • Artículo relacionado 2                    │              │
│                                             │              │
│ ── ¿Necesitas más ayuda? ─────────────────  │              │
│ [Contactar soporte]                         │              │
└─────────────────────────────────────────────┴──────────────┘
```

**TOC sticky (Table of Contents)**:
- Generado automáticamente desde H2/H3 del artículo
- Solo visible si el artículo tiene ≥2 headings
- Ancho 200px, sticky top-80px
- Highlight del heading activo con borde izquierdo `var(--hd-primary)` y scroll tracking (IntersectionObserver)
- En mobile/tablet: colapsado en botón "Índice del artículo" que abre un dropdown

**Widget de feedback**:
```html
<div class="hd-article-feedback">
  <p class="hd-article-feedback__question">¿Te fue útil este artículo?</p>
  <div class="hd-article-feedback__actions">
    <button class="btn btn-outline-success hd-feedback-btn" data-value="1">
      <i class="fas fa-thumbs-up me-1"></i>Sí, gracias
    </button>
    <button class="btn btn-outline-secondary hd-feedback-btn" data-value="0">
      <i class="fas fa-thumbs-down me-1"></i>No del todo
    </button>
  </div>
  <!-- Panel de feedback negativo (hidden, se muestra al votar negativo) -->
  <div class="hd-feedback-negative d-none mt-3">
    <p class="small text-muted">¿Qué podemos mejorar?</p>
    <div class="d-flex flex-column gap-2">
      <div class="form-check"><input type="checkbox" class="form-check-input"> <label class="form-check-label small">Confuso o difícil de entender</label></div>
      <div class="form-check"><input type="checkbox" class="form-check-input"> <label class="form-check-label small">Información incompleta</label></div>
      <div class="form-check"><input type="checkbox" class="form-check-input"> <label class="form-check-label small">No resolvió mi problema</label></div>
    </div>
    <textarea class="form-control form-control-sm mt-2" placeholder="Comentario adicional (opcional)" rows="2"></textarea>
    <button class="btn btn-sm btn-primary mt-2">Enviar feedback</button>
  </div>
</div>
```

---

### 2. Panel de Administración del Helpcenter

#### 2.1 Dashboard Admin (`admin/dashboard.blade.php`)

**KPI Cards**:
- Total artículos | Artículos sin traducción | Búsquedas sin resultado esta semana | Artículos con CSAT < 50%

**Secciones**:
- Top 10 artículos más vistos (tabla)
- Top búsquedas sin resultado (tabla — "knowledge gap") con CTA "Crear artículo"
- Artículos con baja calificación (< 60% útil) marcados para revisión

#### 2.2 Lista de Categorías (`admin/categories/index.blade.php`)

- **Tree view** con drag-and-drop (Sortable.js) para reordenar categorías
- Cada categoría en el árbol muestra: icono + nombre + secciones count + artículos count + estado badge
- Expansión inline para ver secciones hijo
- Botón inline "+" para añadir sección dentro de la categoría
- Acciones: editar, reordenar, activar/desactivar, eliminar

#### 2.3 Lista de Artículos (`admin/articles/index.blade.php`)

- **dxDataGrid** con filtros por: categoría, sección, idioma, estado (publicado/borrador), período
- Columnas: Título, Categoría/Sección, Idioma (chips de idiomas disponibles), Estado, Visitas, CSAT %, Actualizado
- Chip de idioma: cada idioma del artículo como badge pequeño — gris si no tiene traducción en ese idioma, verde si sí
- Fila con CSAT < 60%: fondo levemente rosado para indicar que necesita revisión
- Click en fila: abre editor de artículo

#### 2.4 Editor de Artículo (`admin/articles/create.blade.php`, `admin/articles/edit.blade.php`)

**Layout**:
```
┌──────────────────────────────────────────┬───────────────────┐
│ [ Volver a artículos ]                   │ [Borrador] [Pub.] │
│                                          │                   │
│ Título del artículo                      │ CONFIGURACIÓN     │
│ ┌────────────────────────────────────┐   │ ─────────────────  │
│ │ (input título, grande, sin borde)  │   │ Categoría         │
│ └────────────────────────────────────┘   │ [select▼]         │
│                                          │ Sección           │
│ [🌐 Idioma activo: Español ▼]           │ [select▼]         │
│                                          │ Etiquetas         │
│ ┌────────────────────────────────────┐   │ [select2 tags]    │
│ │                                    │   │                   │
│ │  EDITOR RICH TEXT (TipTap/CKE)    │   │ SEO               │
│ │                                    │   │ [fas fa-chevron-▼]│
│ │  Toolbar: B I U | Lista | Img...   │   │ Meta title        │
│ │                                    │   │ Meta desc.        │
│ └────────────────────────────────────┘   │ Slug              │
│                                          │                   │
│ [Adjuntos y archivos relacionados]       │ IDIOMAS           │
│                                          │ ES ✓ EN ✓ FR ○    │
└──────────────────────────────────────────┴───────────────────┘
```

**Gestión multilingüe en el editor**:
- Dropdown "Idioma activo" arriba del editor: cambia el contenido mostrado al idioma seleccionado
- Indicador de estado de traducción: punto verde (traducido) / punto gris (sin traducción) por idioma
- "Ver artículo original" sidebar muestra el master language en panel de referencia

**Panel SEO** (colapsable):
- Meta Title con contador de caracteres: `[45/60]` — verde si 40-60, amarillo si 60-70, rojo si >70
- Meta Description con contador: `[120/160]`
- Slug editable con auto-generación desde título
- Preview SERP: renderizado de cómo se verá en Google (título azul, URL verde, descripción)

**Panel de Idiomas**:
- Lista de idiomas configurados con badge de estado por cada uno
- Clic en idioma → cambia el editor al contenido de ese idioma
- Botón "Traducir automáticamente desde [master]" → llama a HelpdeskTranslate API y rellena el contenido

---

### 3. Gestión Multilingüe

#### 3.1 Vista de Traducciones (`admin/translations/index.blade.php`)

**Tabla comparativa de estado de traducción**:

```
Artículo               | ES | EN | FR | DE | Acciones
────────────────────────────────────────────────────────
Primeros pasos         | ✓  | ✓  | ✗  | ✗  | Traducir pendientes
¿Cómo facturar?        | ✓  | ✓  | ✓  | ✓  | —
Integraciones API      | ✓  | ✗  | ✗  | ✗  | Traducir pendientes
```

- Celdas verdes (✓) / rojas (✗) claramente diferenciadas
- Botón "Traducir pendientes" por fila → modal de selección de idiomas destino
- Botón "Traducir todo" global con progreso en barra

---

### 4. Analytics de Helpcenter (`admin/analytics/index.blade.php`)

**Tabs**:

**Tab 1: Artículos**
- Tabla de artículos ordenable por: vistas, % útil, % no útil, fecha última vista
- Artículos con alerta: < 60% useful o > 20% no-useful rate marcados con `fas fa-triangle-exclamation` en naranja

**Tab 2: Búsquedas**
- Tabla: Término buscado | Resultados devueltos | Clicks | CTR
- Sección "Sin resultados": búsquedas que no encontraron artículos → knowledge gap
- Botón "Crear artículo sobre esto" en cada fila de knowledge gap

**Tab 3: Feedback**
- Distribución de votos útil/no útil por período
- Top comentarios de feedback negativo (wordcloud o lista)

---

### 5. Funcionalidades Futuras (Espacio Reservado)

1. **AI Search con respuesta directa**: la búsqueda responde con un párrafo generado por IA basado en los artículos, con fuentes citadas
2. **Sugerencia de artículos en el chat**: cuando un agente está en una conversación, sugerir artículos relevantes de la KB en el panel de contexto
3. **Versioning de artículos**: historial de cambios con diff visual, restauración a versión anterior
4. **Import desde Notion/Confluence**: wizard de importación con mapeo de estructura
5. **Embed widget**: código snippet para insertar el buscador de KB en cualquier sitio externo
6. **Analytics de engagement**: tiempo de lectura promedio por artículo, scroll depth

---

## Archivos Clave

```
modules/HelpdeskHelpcenter/resources/views/
├── portal/
│   ├── index.blade.php          ← Homepage pública
│   ├── category.blade.php       ← Página categoría
│   ├── article.blade.php        ← Página artículo con TOC + feedback
│   └── search.blade.php         ← Resultados de búsqueda
└── admin/
    ├── dashboard.blade.php      ← KPIs + knowledge gaps
    ├── categories/
    │   ├── index.blade.php      ← Tree view
    │   └── edit.blade.php
    ├── articles/
    │   ├── index.blade.php      ← dxDataGrid con estado idiomas
    │   ├── create.blade.php     ← Editor split + SEO panel
    │   └── edit.blade.php
    ├── translations/
    │   └── index.blade.php      ← Tabla comparativa multilingüe
    └── analytics/
        └── index.blade.php      ← Tabs: Artículos / Búsquedas / Feedback
```

---

## CSS Específico

Crear `modules/HelpdeskHelpcenter/public/css/helpcenter.css`:
- `.hd-hc-hero` (barra de búsqueda hero)
- `.hd-hc-category-card` con hover
- `.hd-hc-toc` sticky sidebar
- `.hd-article-feedback` widget
- `.hd-lang-badge` chips de idioma con estado
- `.hd-serp-preview` (preview Google en panel SEO)
