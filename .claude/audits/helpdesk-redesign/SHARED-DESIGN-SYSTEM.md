# Sistema de Diseño Compartido — Helpdesk Suite

> Este documento define los tokens de diseño, componentes y convenciones que aplican a TODOS los módulos de la suite Helpdesk. Claude Design debe leer este archivo antes de procesar cualquier prompt de módulo individual para garantizar coherencia visual y de interacción.

---

## 1. Stack Técnico (NO cambiar)

| Capa | Tecnología | Notas |
|------|-----------|-------|
| CSS framework | Bootstrap 5.3 | Utility classes + componentes nativos |
| JS principal | jQuery + AJAX | Sin Livewire, sin Alpine, sin React (excepto widget Livechat) |
| Iconos | Font Awesome 6 | `fas fa-*`, `far fa-*`, `fab fa-*` ÚNICAMENTE — NUNCA Tabler |
| Notificaciones | toastr.js | `toastr.success()`, `toastr.error()` |
| Tablas complejas | DevExpress jQuery | dxDataGrid, dxScheduler |
| Selectores | select2 | NUNCA `theme: 'bootstrap-5'` |
| Color primario | `#90bb13` | Rojo oscuro, todos los módulos |
| CSS inline | PROHIBIDO | Siempre clases CSS |

---

## 2. Paleta de Color Global

```css
/* === Colores base === */
--hd-primary:        #90bb13;   /* Rojo principal — acciones primarias, highlights */
--hd-primary-dark:   #7b0000;   /* Hover/active del primario */
--hd-primary-light:  #fdf0f0;   /* Fondos sutiles de primario */

/* === Semánticos === */
--hd-success:        #13C672;
--hd-danger:         #FA896B;
--hd-warning:        #FEC90F;
--hd-info:           #539bff;
--hd-muted:          #6c757d;

/* === Sentimiento (HelpdeskSocial + conversaciones) === */
--hd-sentiment-pos:  #22c55e;   /* Verde — positivo */
--hd-sentiment-neu:  #9ca3af;   /* Gris — neutral */
--hd-sentiment-neg:  #ef4444;   /* Rojo — negativo */

/* === Estados de aprobación (HelpdeskCampaigns + Social) === */
--hd-state-draft:    #9ca3af;
--hd-state-review:   #f59e0b;
--hd-state-approved: #10b981;
--hd-state-rejected: #f97316;
--hd-state-active:   #13C672;
--hd-state-paused:   #FEC90F;

/* === Plataformas sociales === */
--hd-facebook:       #1877f2;
--hd-instagram:      linear-gradient(45deg, #f09433, #e6683c, #dc2743, #cc2366, #bc1888);
--hd-whatsapp:       #25d366;

/* === Layout === */
--hd-sidebar-width:    260px;
--hd-context-width:    300px;
--hd-topbar-height:    60px;
--hd-border-color:     #e9ecef;
--hd-border-radius:    0.375rem;   /* bs5 default */
--hd-border-radius-lg: 0.5rem;
```

---

## 3. Tipografía

- **Jerarquía en secciones**: capitalizar solo primera palabra (`Información básica`, NOT `Información Básica`)
- **Excepciones**: nombres propios, siglas, marcas
- **Tamaño base**: 14px para contenido de interfaz de gestión
- **Pesos**: 400 cuerpo, 600 labels, 700 títulos de sección

---

## 4. Layout Patterns

### 4.1 Three-Panel Layout (Inbox / Conversaciones)

```html
<div class="hd-inbox d-flex h-100">
  <!-- Panel izquierdo: lista de items -->
  <div class="hd-inbox__list" style="width: var(--hd-sidebar-width); min-width: 220px; max-width: 360px;">
    <!-- Filtros como chips + lista scroll -->
  </div>

  <!-- Panel central: detalle / thread -->
  <div class="hd-inbox__thread flex-grow-1 d-flex flex-column">
    <!-- Header conversación + mensajes + compositor -->
  </div>

  <!-- Panel derecho: contexto / info -->
  <div class="hd-inbox__context" style="width: var(--hd-context-width); min-width: 260px;">
    <!-- Datos del contacto, historial, metadata -->
  </div>
</div>
```

- Resizable con drag handle entre paneles (solo desktop)
- En tablet (`< lg`): contexto oculto, toggle con botón info
- En mobile (`< md`): solo 1 panel activo a la vez, navegación por tab bar

### 4.2 Settings Layout (Settings de módulo)

```html
<div class="row">
  <div class="col-md-3">
    <!-- Nav vertical de secciones de settings -->
    <div class="list-group list-group-flush hd-settings-nav">
      <a href="#" class="list-group-item list-group-item-action active">General</a>
      <a href="#" class="list-group-item list-group-item-action">Notificaciones</a>
    </div>
  </div>
  <div class="col-md-9">
    <!-- Contenido de la sección activa -->
    <div class="hd-settings-section">
      <h5 class="hd-settings-section__title">General</h5>
      <p class="text-muted small hd-settings-section__description">Descripción breve de la sección.</p>
      <hr>
      <!-- Campos del formulario -->
    </div>
  </div>
</div>
```

### 4.3 Dashboard Layout

```html
<div class="row g-4">
  <!-- KPI Cards row -->
  <div class="col-12">
    <div class="row g-3">
      <div class="col-sm-6 col-xl-3"><!-- KPI Card --></div>
    </div>
  </div>
  <!-- Gráficos -->
  <div class="col-lg-8"><!-- Gráfico principal --></div>
  <div class="col-lg-4"><!-- Gráfico secundario / top list --></div>
</div>
```

---

## 5. Componentes UI Estándar

### 5.1 KPI Card

```html
<div class="card hd-kpi-card">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-start">
      <div>
        <p class="text-muted small mb-1">Conversaciones abiertas</p>
        <h3 class="mb-0 fw-bold">142</h3>
        <small class="text-success"><i class="fas fa-arrow-up me-1"></i>12% vs ayer</small>
      </div>
      <div class="hd-kpi-icon bg-primary-light rounded p-2">
        <i class="fas fa-comments text-primary" style="color: var(--hd-primary)!important;"></i>
      </div>
    </div>
  </div>
</div>
```

### 5.2 Tabla Acciones — Dropdown Obligatorio

```html
<!-- SIEMPRE dropdown con fa-ellipsis-vertical. NUNCA iconos en items. NUNCA text-danger en Eliminar -->
<div class="dropdown">
  <button class="btn btn-sm btn-light" data-bs-toggle="dropdown">
    <i class="fas fa-ellipsis-vertical"></i>
  </button>
  <ul class="dropdown-menu dropdown-menu-end">
    <li><a class="dropdown-item" href="#">Ver detalle</a></li>
    <li><a class="dropdown-item" href="#">Editar</a></li>
    <li><hr class="dropdown-divider"></li>
    <li><a class="dropdown-item" href="#">Eliminar</a></li>
  </ul>
</div>
```

### 5.3 Modal Estándar

```html
<!-- SIEMPRE modal-dialog-centered. Footer con botones w-100 apilados -->
<div class="modal fade" id="modalId" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Título del modal</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <!-- Contenido -->
      </div>
      <div class="modal-footer flex-column">
        <button type="button" class="btn btn-primary w-100 mb-2">Acción principal</button>
        <button type="button" class="btn btn-outline-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
      </div>
    </div>
  </div>
</div>
```

### 5.4 Status Badge / Pill

```html
<!-- Estado activo -->
<span class="badge rounded-pill" style="background-color: var(--hd-success);">Activo</span>

<!-- Estado pendiente -->
<span class="badge rounded-pill" style="background-color: var(--hd-warning); color: #000;">Pendiente</span>

<!-- Estado inactivo -->
<span class="badge rounded-pill bg-secondary">Inactivo</span>

<!-- Estado cerrado -->
<span class="badge rounded-pill bg-dark">Cerrado</span>
```

### 5.5 Conversation Item (lista de conversaciones)

```html
<div class="hd-conv-item d-flex align-items-start gap-3 p-3 border-bottom" 
     style="border-left: 3px solid var(--hd-primary);">
  <!-- Avatar -->
  <div class="flex-shrink-0">
    <div class="hd-avatar hd-avatar--md rounded-circle">
      <img src="..." class="rounded-circle" width="36" height="36" alt="">
    </div>
  </div>
  <!-- Info -->
  <div class="flex-grow-1 min-w-0">
    <div class="d-flex justify-content-between align-items-baseline">
      <span class="fw-semibold text-truncate">Nombre del contacto</span>
      <small class="text-muted flex-shrink-0 ms-2">2m</small>
    </div>
    <p class="text-muted small mb-1 text-truncate">Último mensaje truncado aquí...</p>
    <div class="d-flex gap-2 align-items-center">
      <span class="badge bg-primary-subtle text-primary small">Inbox principal</span>
      <span class="hd-sentiment-dot hd-sentiment-dot--pos" title="Sentimiento positivo"></span>
    </div>
  </div>
</div>
```

### 5.6 Empty State

```html
<div class="hd-empty-state text-center py-5 px-4">
  <div class="hd-empty-state__icon mb-3">
    <i class="fas fa-inbox fa-3x text-muted opacity-50"></i>
  </div>
  <h5 class="mb-2">Sin conversaciones activas</h5>
  <p class="text-muted small mb-4">Cuando lleguen nuevas conversaciones aparecerán aquí.</p>
  <a href="#" class="btn btn-primary btn-sm">
    <i class="fas fa-plus me-2"></i>Nueva conversación
  </a>
</div>
```

### 5.7 Filter Chip Bar

```html
<!-- Chips horizontales de filtro rápido. Scroll horizontal en mobile -->
<div class="hd-filter-chips d-flex gap-2 py-2 overflow-x-auto flex-nowrap">
  <button class="btn btn-sm btn-primary hd-chip hd-chip--active">Todos <span class="badge bg-white text-dark ms-1">24</span></button>
  <button class="btn btn-sm btn-outline-secondary hd-chip">Abiertos</button>
  <button class="btn btn-sm btn-outline-secondary hd-chip">Sin asignar</button>
  <button class="btn btn-sm btn-outline-secondary hd-chip">Esperando</button>
  <button class="btn btn-sm btn-outline-secondary hd-chip"><i class="fas fa-filter me-1"></i>Más filtros</button>
</div>
```

### 5.8 Stepper de Aprobación

```html
<!-- Flujo de aprobación horizontal -->
<ol class="hd-stepper d-flex align-items-center gap-0 mb-0 list-unstyled">
  <li class="hd-stepper__step hd-stepper__step--done">
    <div class="hd-stepper__dot"><i class="fas fa-check"></i></div>
    <span class="hd-stepper__label">Borrador</span>
  </li>
  <div class="hd-stepper__line"></div>
  <li class="hd-stepper__step hd-stepper__step--active">
    <div class="hd-stepper__dot"><i class="fas fa-clock"></i></div>
    <span class="hd-stepper__label">En revisión</span>
  </li>
  <div class="hd-stepper__line"></div>
  <li class="hd-stepper__step">
    <div class="hd-stepper__dot"><span>3</span></div>
    <span class="hd-stepper__label">Aprobado</span>
  </li>
</ol>
```

### 5.9 Sentiment Badges

```html
<!-- Positivo -->
<span class="badge hd-badge-sentiment-pos">
  <i class="fas fa-face-smile me-1"></i>Positivo
</span>

<!-- Neutral -->
<span class="badge hd-badge-sentiment-neu">
  <i class="fas fa-minus me-1"></i>Neutral
</span>

<!-- Negativo -->
<span class="badge hd-badge-sentiment-neg">
  <i class="fas fa-face-frown me-1"></i>Negativo
</span>
```

---

## 6. AJAX / Interacción JS

```javascript
// Patrón base para toda llamada AJAX
$.ajax({
    url: route,
    method: 'POST',
    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
    data: { /* payload */ },
    beforeSend: function() { /* spinner en botón */ },
    success: function(res) {
        if (res.success) { toastr.success(res.message); }
    },
    error: function(xhr) {
        if (xhr.status === 422) {
            // Parsear errores de validación campo a campo
            $.each(xhr.responseJSON.errors, function(field, errors) {
                $('[name="' + field + '"]')
                    .addClass('is-invalid')
                    .next('.invalid-feedback').text(errors[0]);
            });
        } else {
            toastr.error('Error inesperado. Intente de nuevo.');
        }
    }
});

// Event delegation para contenido dinámico
$(document).on('click', '.hd-action-btn', function(e) {
    e.preventDefault();
    // handler
});
```

---

## 7. Reglas de Iconos

| Uso | Icono correcto |
|-----|---------------|
| Conversación / chat | `fas fa-comments` |
| Email / mensaje | `fas fa-envelope` |
| Ticket | `fas fa-ticket` |
| Asignar agente | `fas fa-user-check` |
| Cerrar / resolver | `fas fa-check-circle` |
| Archivar | `fas fa-box-archive` |
| Snooze / posponer | `fas fa-clock` |
| Etiqueta / tag | `fas fa-tag` |
| Búsqueda | `fas fa-magnifying-glass` |
| Filtrar | `fas fa-filter` |
| Acciones (table) | `fas fa-ellipsis-vertical` |
| Configuración | `fas fa-gear` |
| Añadir | `fas fa-plus` |
| Eliminar | `fas fa-trash` |
| Editar | `fas fa-pen-to-square` |
| Exportar | `fas fa-file-export` |
| Importar | `fas fa-file-import` |
| Dashboard | `fas fa-chart-line` |
| Agente/usuario | `fas fa-user-circle` |
| Equipo | `fas fa-users` |
| Campaña | `fas fa-bullhorn` |
| Base de conocimiento | `fas fa-book-open` |
| Idioma | `fas fa-globe` |
| Traducir | `fas fa-language` |
| Positivo | `fas fa-face-smile` |
| Negativo | `fas fa-face-frown` |
| Neutral | `fas fa-minus` |
| Alerta / riesgo | `fas fa-triangle-exclamation` |
| Aprobado | `fas fa-circle-check` |
| Rechazado | `fas fa-circle-xmark` |
| Facebook | `fab fa-facebook` |
| Instagram | `fab fa-instagram` |
| WhatsApp | `fab fa-whatsapp` |
| IA / Bot | `fas fa-robot` |
| Automación | `fas fa-bolt` |

---

## 8. Responsive Breakpoints

| Viewport | Comportamiento |
|----------|---------------|
| `>= xl` (1200px+) | Tres paneles visibles, sidebar navegación completa |
| `>= lg` (992–1199px) | Tres paneles, sidebar colapsada a iconos |
| `>= md` (768–991px) | Context panel oculto (toggle botón), lista + thread |
| `< md` (< 768px) | Un panel activo a la vez, bottom navigation tabs |

---

## 9. Animaciones y Transiciones

```css
/* Transición estándar para elementos UI */
.hd-transition { transition: all 0.2s ease-in-out; }

/* Fade para mensajes nuevos / actualizaciones en tiempo real */
.hd-fade-in { animation: hdFadeIn 0.3s ease-in-out; }
@keyframes hdFadeIn { from { opacity: 0; transform: translateY(4px); } to { opacity: 1; transform: none; } }

/* Pulse para alertas urgentes (ej: timer WhatsApp < 2h) */
.hd-pulse { animation: hdPulse 1.5s infinite; }
@keyframes hdPulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
```

---

## 10. Patrones de Texto

- Mensajes de éxito: `"[Entidad] [acción] correctamente."` → `"Conversación cerrada correctamente."`
- Mensajes de error: `"No se pudo [acción]. Intente de nuevo."` → `"No se pudo asignar. Intente de nuevo."`
- Confirmaciones destructivas: `"¿Eliminar [entidad]? Esta acción no se puede deshacer."` — SIEMPRE en modal, nunca `confirm()` nativo
- Estados vacíos: `"Sin [entidades] [condición]"` → `"Sin conversaciones abiertas"`
- Tooltips: `data-bs-toggle="tooltip" data-bs-title="..."` — Bootstrap nativo, sin librerías adicionales

---

## 11. Archivo CSS Compartido Recomendado

Crear `modules/Helpdesk/public/css/helpdesk-suite.css` con todas las variables, clases `.hd-*` y componentes compartidos. Publicar a `public/modules/helpdesk/css/helpdesk-suite.css` e incluir en el layout base de la suite.

---

## 12. Módulos en Scope

| Módulo | Archivo de Prompt |
|--------|------------------|
| Helpdesk (core) | `Helpdesk.md` |
| HelpdeskTickets | `HelpdeskTickets.md` |
| HelpdeskAgents | `HelpdeskAgents.md` |
| HelpdeskCampaigns | `HelpdeskCampaigns.md` |
| HelpdeskHelpcenter | `HelpdeskHelpcenter.md` |
| HelpdeskLivechat | `HelpdeskLivechat.md` |
| HelpdeskSocial | `HelpdeskSocial.md` |
| HelpdeskTranslate | `HelpdeskTranslate.md` |
