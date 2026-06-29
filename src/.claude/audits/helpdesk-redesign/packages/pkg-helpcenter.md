# Paquete Claude Design — HelpdeskHelpcenter

## Archivos a incluir

```
modules/HelpdeskHelpcenter/public/css/helpcenter.css        ← CSS esqueleto nuevo
.claude/audits/helpdesk-redesign/HelpdeskHelpcenter.md      ← especificación completa
.claude/audits/helpdesk-redesign/SHARED-DESIGN-SYSTEM.md    ← sistema compartido
```

## Qué debe entregar Claude Design

### Parte A — Admin (Blade)

1. **`admin-index.html`** — gestión de KB
   - `.hhc-page-bar` + `.hhc-admin-layout` (sidebar tree 280px + content)
   - `.hhc-tree` con tree-nodes de categoría → sección → artículo (Sortable.js drag-and-drop)
   - Panel principal: lista de artículos `.hhc-article-row` con badges de idioma y estado
   - Acciones: Nuevo artículo / Nueva categoría / Nueva sección

2. **`article-editor.html`** — editor de artículos
   - `.hhc-editor-layout` grid 1fr/300px
   - Panel izquierdo: `.hhc-lang-tabs` (ES/EN/PT/etc) + `.hhc-editor-toolbar` + `.hhc-editor-canvas`
   - Título editable `contenteditable` + body wysiwyg (placeholder TipTap/ProseMirror)
   - Panel derecho `.hhc-seo-panel`: meta title, meta desc, URL slug, `.hhc-seo-score`, tags
   - `.hhc-status-*` badges + botones: Guardar borrador / Enviar a revisión / Publicar

3. **`analytics-admin.html`** — dashboard analytics KB
   - `.hhc-kpi-grid` (4 KPIs: vistas / búsquedas / ratio resolución / CSAT KB)
   - Charts: artículos más vistos (tabla), búsquedas sin resultado (lista), satisfaction trend (line)

### Parte B — Portal Público

4. **`portal-home.html`** — página principal del portal
   - `.helpdesk-helpcenter-portal`
   - `.hhc-portal-hero` con gradiente rojo, título, buscador con resultados live
   - `.hhc-portal-cats` grid de `.hhc-cat-card` (negativamente marginadas para overlap del hero)

5. **`portal-category.html`** — página de categoría
   - `.hhc-breadcrumb`
   - Header de categoría (icono + nombre + descripción)
   - Secciones con artículos listados debajo

6. **`portal-article.html`** — artículo individual
   - `.hhc-breadcrumb`
   - `.hhc-article-layout` grid 1fr/240px
   - `.hhc-article` (título + meta + body formateado) + `.hhc-feedback` (thumbs up/down + followup panel)
   - `.hhc-toc` sticky + `.hhc-related` artículos relacionados

7. **`helpcenter.css`** — CSS refinado (prefijo `--hhc-*` / `.hhc-*`)

## Restricciones

- El portal público es HTML/CSS puro — sin autenticación, completamente estático
- El buscador usa AJAX jQuery (resultado live en dropdown)
- El tree de categorías usa Sortable.js para reordenar — NO drag canvas
- El feedback (thumbs) expande textarea inline sin modal
- NO inline styles

## Componentes críticos

```html
<!-- Portal hero -->
<section class="hhc-portal-hero">
  <h1 class="hhc-portal-hero__title">¿En qué podemos ayudarte?</h1>
  <p class="hhc-portal-hero__sub">Explora artículos o busca tu respuesta</p>
  <div class="hhc-portal-search">
    <input type="text" class="hhc-portal-search__input" placeholder="Buscar artículos...">
    <i class="fas fa-magnifying-glass hhc-portal-search__icon"></i>
    <div class="hhc-portal-search__results">
      <div class="hhc-search-result-item">
        <i class="fas fa-file-lines"></i>
        <span>Cómo cambiar mi contraseña</span>
      </div>
    </div>
  </div>
</section>

<!-- Feedback widget -->
<div class="hhc-feedback">
  <p class="hhc-feedback__question">¿Este artículo fue útil?</p>
  <div class="hhc-feedback__buttons">
    <button class="hhc-feedback-btn yes">
      <i class="fas fa-thumbs-up"></i>Sí, fue útil
    </button>
    <button class="hhc-feedback-btn no">
      <i class="fas fa-thumbs-down"></i>No me ayudó
    </button>
  </div>
  <div class="hhc-feedback__followup">
    <label class="small fw-600 mb-1">¿Qué podemos mejorar?</label>
    <textarea class="form-control form-control-sm" rows="3" placeholder="Tu comentario..."></textarea>
    <button class="btn btn-sm btn-primary mt-2">Enviar comentario</button>
  </div>
  <div class="hhc-feedback__thanks">
    <i class="fas fa-circle-check text-success"></i>
    ¡Gracias por tu opinión!
  </div>
</div>

<!-- TOC sidebar -->
<nav class="hhc-toc">
  <p class="hhc-toc__title">En este artículo</p>
  <ul class="hhc-toc-list">
    <li class="hhc-toc-item"><a href="#s1" class="hhc-toc-link active">Introducción</a></li>
    <li class="hhc-toc-item hhc-toc-item--h3"><a href="#s1-1" class="hhc-toc-link">Prerrequisitos</a></li>
    <li class="hhc-toc-item"><a href="#s2" class="hhc-toc-link">Pasos</a></li>
  </ul>
</nav>
```
