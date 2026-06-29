# Prompt de Rediseño — Módulo HelpdeskTickets

> **IMPORTANTE**: Leer `SHARED-DESIGN-SYSTEM.md` antes de procesar este prompt.  
> Stack: Bootstrap 5.3 + jQuery + Font Awesome 6. SIN Livewire, SIN Alpine.

---

## Contexto del Módulo

**HelpdeskTickets** es el sistema de gestión de tickets IT/soporte del helpdesk. Tiene 71 vistas Blade. A diferencia del inbox (conversaciones en tiempo real), los tickets siguen un flujo estructurado: creación → asignación → progreso → resolución, con SLA estricto, tickets recurrentes, historial completo y métricas de calidad.

**Rutas principales**: `panel/helpdesk-tickets/*`  
**Vistas kanban + tabla** disponibles (toggle existente)  
**Directorio `partials-v2/` VACÍO** — refactorización de vistas pendiente de iniciar  
**Modelos clave**: Ticket, TicketComment, TicketType, TicketPriority, TicketStatus, TicketSLA, RecurringTicket, TicketTemplate

---

## Áreas a Rediseñar

### 1. Vista Kanban (`tickets/kanban.blade.php`)

**Estado actual**: Kanban funcional con Sortable.js pero visualmente básico. Cards con información mínima.

**Mejoras deseadas**:

#### 1.1 Cabecera del Kanban
- Barra horizontal con: título "Tickets" + contador total, toggle vista (kanban/tabla con `fas fa-columns` y `fas fa-table-list`), botones "Nuevo ticket" (primario) y filtros
- Selector de agrupación: por estado (default) / por prioridad / por agente / por tipo

#### 1.2 Columnas de Estado
Cada columna kanban:
```html
<div class="hd-kanban-col">
  <div class="hd-kanban-col__header">
    <span class="hd-kanban-col__title">En progreso</span>
    <span class="badge bg-secondary ms-2">14</span>
    <button class="btn btn-sm btn-link ms-auto"><i class="fas fa-plus"></i></button>
  </div>
  <div class="hd-kanban-col__body">
    <!-- Ticket cards, sortable -->
  </div>
</div>
```
- Ancho fijo 280px por columna, scroll horizontal del contenedor
- Header sticky dentro del scroll
- Límite WIP opcional: número rojo si se supera el límite configurado en la columna

#### 1.3 Ticket Card en Kanban
```
┌─────────────────────────────────┐
│ [URGENTE] Bug crítico en login   │  ← Badge prioridad + título
│ #TK-1842 · Tipo: Bug            │  ← ID + tipo chip
│ Laura García                     │  ← Avatar 24px + nombre asignado
│ SLA: ██████░░░░ 2h restantes    │  ← Progress bar SLA
│ 🏷️ backend  🔴 P1  💬 3         │  ← Tags + prioridad + comentarios
└─────────────────────────────────┘
```
- Drag handle en el borde izquierdo (visible en hover)
- Click abre modal de detalle (no navega a nueva página)
- Hover: shadow suave + border primario
- Card color-coded por prioridad en borde izquierdo: urgente=rojo, alta=naranja, normal=azul, baja=gris

#### 1.4 SLA Progress Bar en Card
```html
<div class="hd-sla-bar">
  <div class="hd-sla-bar__fill" style="width: 65%"></div>
  <!-- > 50% restante: verde, 25-50%: amarillo, < 25%: rojo, vencido: rojo pulsante -->
</div>
<small class="text-muted">2h 14m restantes</small>
```

---

### 2. Vista Tabla (`tickets/index.blade.php`)

**Estado actual**: Tabla HTML con paginación básica.

**Mejoras deseadas**:

#### 2.1 Cabecera con Estadísticas Rápidas
Row de 4 mini-KPI cards antes de la tabla:
- Total abiertos | Vencidos SLA | Sin asignar | Cerrados hoy

#### 2.2 Tabla con DevExpress dxDataGrid
Reemplazar tabla HTML por `dxDataGrid` con:
- Columnas: #ID, Asunto (link), Tipo (badge), Prioridad (badge colored), Asignado (avatar+nombre), Estado (badge), SLA (progress bar mini), Creado, Acciones (dropdown)
- Filtros inline por columna
- Ordenamiento por cualquier columna
- Paginación servidor-side
- Selección múltiple con checkbox + bulk actions bar que aparece al seleccionar

#### 2.3 Bulk Actions Bar
```html
<!-- Aparece al seleccionar ≥1 ticket, sticky arriba de la tabla -->
<div class="hd-bulk-bar">
  <span class="me-3"><strong>5</strong> tickets seleccionados</span>
  <button class="btn btn-sm btn-outline-secondary">Asignar</button>
  <button class="btn btn-sm btn-outline-secondary">Cambiar estado</button>
  <button class="btn btn-sm btn-outline-secondary">Añadir etiqueta</button>
  <button class="btn btn-sm btn-outline-danger">Eliminar</button>
  <button class="btn btn-sm btn-link ms-auto">Limpiar selección</button>
</div>
```

---

### 3. Modal / Vista Detalle de Ticket

**PROPUESTA**: Migrar la página de detalle a un modal grande (modal-xl o offcanvas lateral) para no salir del listado.

#### 3.1 Layout del Modal de Detalle
```
┌──────────────────────────────────────────────────────────────┐
│ [#TK-1842] Bug crítico en login              [Cerrar ×]      │
├─────────────────────────────────┬────────────────────────────┤
│  Thread de comentarios          │  Panel de propiedades      │
│  (scroll independiente)         │  ─────────────────────     │
│                                 │  Estado: [En progreso ▼]   │
│  [Comentario público]           │  Prioridad: [Alta ▼]       │
│  [Nota interna]                 │  Asignado: [Laura ▼]       │
│                                 │  Tipo: [Bug ▼]             │
│  Compositor tabbed              │  SLA: ██████░ 2h rest.     │
│  [Guardar]                      │  Etiquetas: [backend] [+]  │
│                                 │  ─────────────────────     │
│                                 │  Historial de cambios      │
│                                 │  (scroll compacto)         │
└─────────────────────────────────┴────────────────────────────┘
```

#### 3.2 Thread de Comentarios
- Burbujas diferenciadas: comentarios públicos (blanco) vs notas internas (amarillo pálido con `fas fa-lock`)
- Avatar 36px + nombre + timestamp + badge "Agente" / "Cliente"
- Cada comentario: hover muestra opciones `fas fa-reply` citar, `fas fa-pencil` editar (si es propio), `fas fa-ellipsis-vertical` más
- Eventos de sistema entre comentarios: línea centrada italic muted

#### 3.3 Panel de Propiedades (Derecho)
- Cada campo como selector inline: click activa dropdown/input, ESC cancela, Enter guarda
- Sin botón "Guardar" para cada campo — auto-save con debounce AJAX
- Toast de éxito/error al guardar

---

### 4. Formulario Crear/Editar Ticket (`tickets/create.blade.php`, `tickets/edit.blade.php`)

**Rediseño en 3 secciones con sticky sidebar**:

```
Sección izquierda (col-8):          Sección derecha sticky (col-4):
─────────────────────               ───────────────────────────────
Información básica                  Asignación
  - Asunto *                          - Agente
  - Descripción (rich editor)         - Equipo
  - Adjuntos                        
                                    Clasificación
                                      - Tipo
                                      - Prioridad
                                      - Etiquetas

                                    SLA
                                      - Política SLA
                                      - Fecha límite (auto/manual)

                                    [Crear ticket] (btn primario w-100)
                                    [Cancelar] (btn outline w-100)
```

---

### 5. Tickets Recurrentes (`recurring-tickets/`)

**Estado actual**: CRUD básico.

**Mejoras deseadas**:

#### 5.1 Lista de Recurrentes
- Card view (no tabla): cada card muestra el patrón de recurrencia como chip visual
- Badge "Próxima ejecución: en 3 días" con countdown
- Toggle activo/pausado con `form-check form-switch`
- Última ejecución con link al ticket generado

#### 5.2 Editor de Recurrencia
- **Frecuencia visual**: selector tipo calendario simplificado
  - Diario: slider de días
  - Semanal: botones de días de la semana (L M X J V S D) como checkbox-buttons
  - Mensual: selector de día del mes (1-28) o "último día del mes"
  - Personalizado: input cron con helper de lectura humana debajo ("Cada lunes y miércoles a las 9:00")
- Preview: "Próximas ejecuciones: 12 May, 19 May, 26 May..."

---

### 6. Plantillas de Ticket (`ticket-templates/`)

**Mejoras deseadas**:
- Card grid de plantillas con: nombre, descripción breve, tipo badge, uso count ("Usado 23 veces")
- Preview en hover (popover con los campos pre-rellenados)
- Botón "Crear ticket desde esta plantilla" en la card
- Editor: misma estructura que crear ticket pero con marcadores de posición `{{nombre_cliente}}`

---

### 7. Dashboard de Tickets (`tickets/dashboard.blade.php`)

**KPI Cards**:
- Tickets abiertos | Resueltos hoy | Tiempo medio resolución | SLA cumplido %

**Gráficos**:
- Volumen semanal por tipo (bar chart)
- Distribución por estado (donut)
- Top 5 agentes por tickets resueltos (bar horizontal)
- SLA breach por prioridad (heat map o tabla)

**Tabla de alertas**:
- Tickets con SLA vencido (máximo urgencia visual — fondo rojo pálido en filas)
- Tickets sin actividad > N días

---

### 8. Funcionalidades Futuras a Diseñar (Espacio Reservado)

1. **AI Categorización automática**: al crear, sugerir tipo y prioridad — badge "Sugerido por IA" editable
2. **Tiempo de resolución predicho**: badge en el detalle "IA estima: ~4h"
3. **Sugerencias de respuesta IA**: botón en el compositor del comentario
4. **Relacionar tickets**: panel en sidebar "Tickets relacionados" con búsqueda
5. **Customer portal view**: vista pública para que el cliente vea el estado de sus tickets (URL token-based)
6. **Time tracking**: log de tiempo por ticket, total en panel de propiedades

---

## Archivos Clave

```
modules/HelpdeskTickets/resources/views/
├── tickets/
│   ├── index.blade.php          ← Lista/tabla con dxDataGrid
│   ├── kanban.blade.php         ← Vista kanban mejorada
│   ├── create.blade.php         ← Form crear (2 columnas)
│   ├── edit.blade.php           ← Form editar
│   ├── show.blade.php           ← Migrar a modal desde lista
│   ├── partials/                ← Existentes
│   └── partials-v2/             ← VACÍO — aquí van los nuevos partials
├── recurring-tickets/
│   ├── index.blade.php
│   ├── create.blade.php
│   └── edit.blade.php
├── ticket-templates/
└── dashboard/
    └── index.blade.php
```

**NOTA**: Priorizar crear archivos en `partials-v2/` para los nuevos componentes. El directorio existe pero está vacío — la migración es incremental.

---

## CSS Específico

Crear `modules/HelpdeskTickets/public/css/tickets.css`:
- `.hd-kanban-col` y layout horizontal scroll
- `.hd-ticket-card` con estados
- `.hd-sla-bar` con colores dinámicos
- `.hd-bulk-bar` sticky
