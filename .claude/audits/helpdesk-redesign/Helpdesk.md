# Prompt de Rediseño — Módulo Helpdesk (Core)

> **IMPORTANTE**: Leer `SHARED-DESIGN-SYSTEM.md` antes de procesar este prompt.  
> Stack: Bootstrap 5.3 + jQuery + Font Awesome 6 + color primario `#90bb13`. SIN Livewire, SIN Alpine.

---

## Contexto del Módulo

El módulo **Helpdesk** es el núcleo de la suite: gestiona conversaciones omnicanal (email, chat, API) organizadas en inboxes. Tiene 182 vistas Blade actualmente. Los agentes trabajan desde una interfaz tipo "bandeja de entrada" muy similar a Gmail/Intercom, con herramientas de asignación, etiquetado, snooze, macros y SLA.

**Rutas principales**: `panel/helpdesk/*`  
**Aliases de permiso**: `helpdesk.*`, `helpdesk.conversations.*`

---

## Áreas a Rediseñar

### 1. Inbox Principal (`helpdesk/inbox/index.blade.php`)

**Estado actual**: Layout de tres columnas funcional pero visualmente denso. Barra de filtros tipo dropdown. Lista de conversaciones sin indicadores de SLA ni sentimiento. Thread central sin distinción visual entre canales. Composer con tabs básicos.

**Mejoras deseadas**:

#### 1.1 Layout Three-Panel Refinado
Implementar el layout estándar definido en `SHARED-DESIGN-SYSTEM.md §4.1`. Especificaciones adicionales:
- Panel izquierdo: 280px fixed, fondo `#f8f9fa`, border-right `#e9ecef`
- Panel central: fluid, fondo blanco
- Panel derecho: 300px fixed, fondo `#f8f9fa`, border-left `#e9ecef`
- Drag-handle entre paneles (cursor `col-resize`, rango 220–400px)
- Persiste ancho en `localStorage` por usuario

#### 1.2 Barra de Filtros como Chips (reemplaza dropdowns actuales)
```
[ Todos (142) ] [ Abiertos ] [ Sin asignar ] [ SLA en riesgo ] [ ... Más filtros ]
```
- Scroll horizontal sin scrollbar visible en mobile
- "Más filtros" abre un panel lateral (`offcanvas`) con: inbox selector, agente asignado, etiquetas, canal (email/chat/API), rango de fechas, prioridad, sentimiento
- Contador actualizado en tiempo real vía WebSocket (Reverb ya instalado)
- Chip activo: `background: var(--hd-primary); color: white;`

#### 1.3 Ítem de Conversación Mejorado
Cada fila de la lista debe mostrar:
- **Avatar** del contacto (40px, iniciales si no hay foto, colored by name hash)
- **Nombre** (fw-semibold, truncated) + **canal icon** pequeño (`fas fa-envelope`, `fas fa-comments`)
- **Preview** del último mensaje (14px, `text-muted`, 2 líneas max)
- **Timestamp** relativo (`2m`, `1h`, `ayer`) — top right
- **Badges** en fila inferior: inbox, SLA indicator, sentimiento dot
- **Border-left** 3px coloreado por prioridad: rojo=urgent, naranja=high, gris=normal
- **Dot azul** (8px) para no leídos, desaparece al abrir
- **Hover state**: fondo `#f0f4ff` suave, cursor pointer
- **Checkbox** aparece en hover (left side) para selección múltiple

```
SLA indicator:
  - Badge verde "SLA OK" cuando > 30% tiempo restante
  - Badge amarillo "SLA 2h" cuando 15-30% restante
  - Badge rojo pulsante "SLA VENCIDO" cuando expirado
```

#### 1.4 Thread de Conversación

- **Cabecera de conversación**: nombre contacto + canal icon + asunto + botones de acción rápida (`fas fa-user-check` asignar, `fas fa-check-circle` cerrar, `fas fa-clock` snooze, dropdown `fas fa-ellipsis-vertical` para más)
- **Burbujas de mensaje**: diferenciadas por remitente
  - Mensajes entrantes: alineados izquierda, fondo `#f0f2f5`, borde-radius `4px 16px 16px 16px`
  - Mensajes salientes (agente): alineados derecha, fondo `var(--hd-primary-light)`, border-radius `16px 4px 16px 16px`
  - Cada burbuja muestra: nombre, timestamp, estado de entrega (para canales que lo soportan), botón hover `fas fa-ellipsis-vertical` con opciones: Responder cita, Reenviar, Ver historial
- **Separadores de fecha**: línea horizontal con fecha centrada (`─── Hoy ───`)
- **Notas internas**: burbujas fondo amarillo pálido `#fffde7`, prefijo `fas fa-lock` + "Nota interna"
- **Eventos de sistema**: línea centrada italic muted: `"Asignado a Laura García por Sistema • hace 10 min"`
- **Scroll to bottom button**: FAB redondo que aparece cuando no se está al final

#### 1.5 Composer Multi-Tab Mejorado

```
[ Responder ] [ Nota interna ] [ Email ] [ WhatsApp ]
```

- Tab activo usa `border-bottom: 2px solid var(--hd-primary)`
- **Toolbar del composer**: negrita, cursiva, lista, enlace, imagen, `fas fa-at` mención agente, `fas fa-robot` respuesta IA sugerida, `fas fa-bookmark` respuestas guardadas, `fas fa-paperclip` adjunto
- **Respuestas guardadas**: dropdown buscable con trigger `/` en el textarea
- **IA suggest**: botón que llama al endpoint y muestra `<div class="card border-primary mt-2">` con la sugerencia + botones "Usar" / "Descartar"
- **Preview de adjuntos**: chips removibles por debajo del textarea antes de enviar
- **Módulo Translate integrado**: switch `form-check form-switch` en la toolbar del composer — ver `HelpdeskTranslate.md` para detalles

---

### 2. Modales de Inbox (`helpdesk/inbox/partials/modals/`)

Son 32 modales. Todos deben seguir el patrón estándar de `SHARED-DESIGN-SYSTEM.md §5.3`.

#### 2.1 Modal Asignar (`assign.blade.php`)
- **Búsqueda de agente**: input con `fas fa-magnifying-glass`, filtra en tiempo real vía AJAX
- **Lista de agentes**: avatar 32px + nombre + badge de carga actual (`"8 conv."` en text-muted small)
- **Agente actual** resaltado con `fas fa-check` y color primario
- **Equipo / grupo**: tab secundaria "Asignar a equipo" con lista de grupos disponibles
- Botón primario: "Asignar"

#### 2.2 Modal Snooze (`snooze.blade.php`)
- **Opciones rápidas en grid 2×3**: Mañana 9am, En 2 horas, Esta tarde, El lunes, En 3 días, Fecha personalizada
- Cada opción como `<button class="btn btn-outline-secondary w-100 mb-2">` con icono `fas fa-clock` y texto descriptivo
- "Fecha personalizada" abre flatpickr datetime picker inline debajo del grid
- Sin inputs adicionales — simplicity first

#### 2.3 Modal Tags (`tags.blade.php`)
- Input con autocompletado (select2 tags mode) para buscar/crear etiquetas
- Chips de etiquetas existentes en la conversación con `×` para remover
- Color picker en la creación de nueva etiqueta (6 colores preset)

#### 2.4 Modal Crear Ticket (`create-ticket.blade.php`)
- Pre-rellena: asunto, contacto, descripción desde la conversación
- Selector de tipo (bug, solicitud, consulta) como radio-cards horizontales
- Prioridad como radio-buttons con colores
- Selector de agente asignado
- Link "Ver ticket creado" en el toast de éxito

#### 2.5 Modal Merge (`merge.blade.php`)
- Dos columnas: conversación actual (fija) + buscador de conversación destino
- Preview de ambas conversaciones con últimos 2 mensajes
- Warning box: "Los mensajes de ambas conversaciones se combinarán. Esta acción no se puede deshacer."

#### 2.6 Modal Filtros Avanzados (`filter-advanced.blade.php`)
- Reemplazar por `offcanvas` lateral (derecha), no modal centrado — más espacio para filtros complejos
- Secciones colapsables: Canal, Asignación, Etiquetas, Prioridad, SLA, Fecha, Sentimiento
- Contador de filtros activos en el botón trigger: `fas fa-filter` + badge

---

### 3. Settings de Helpdesk (`settings/`)

25+ subcategorías. Todas en layout `SHARED-DESIGN-SYSTEM.md §4.2`.

#### 3.1 Settings Overview (`settings/index.blade.php`)
Añadir un "dashboard de configuración" con cards de resumen:
- Inboxes configurados (N activos)
- Flujos de trabajo activos
- Políticas SLA
- Macros disponibles
- Respuestas guardadas

#### 3.2 Brands (`settings/brands/`)
- Card grid de marcas con logo 48px, nombre, inbox count, estado badge
- Inline edit del nombre al hacer doble click
- Color picker para color de marca

#### 3.3 Workflows (`settings/workflows/`)
**Rediseñar completamente**: el constructor de flujos de trabajo actual es funcional pero poco visual.
- Lista de workflows: card con nombre, trigger badge, acción count, estado toggle
- Editor de workflow: **stepper horizontal** — Trigger → Condiciones → Acciones → Guardar
  - Trigger: selector categorizado (Conversación creada, Conversación asignada, Mensaje recibido, SLA incumplido...)
  - Condiciones: constructor "IF/AND" visual con chips de condición removibles
  - Acciones: lista ordenable de acciones con drag handle (`fas fa-grip-vertical`)

#### 3.4 SLA Policies (`settings/sla-policies/`)
- Tabla de políticas con: nombre, prioridades aplicadas, tiempo primera respuesta, tiempo resolución
- Editor modal con campos claros para cada prioridad (urgent/high/normal/low) y sus timers
- Horario de negocio: selector visual de días + rangos horarios

#### 3.5 Canned Replies / Macros
- **Canned Replies**: tabla con búsqueda, ordenable por uso (más utilizadas primero), preview en hover
- **Macros**: cada macro muestra las acciones que ejecuta como chips en la fila de tabla
- Ambas con trigger `/` en el composer (ya existe, mejorar el dropdown de resultados)

---

### 4. Dashboard Principal (`dashboard/index.blade.php`)

**KPI Cards (fila 1)**:
- Conversaciones hoy
- Sin asignar
- Tiempo medio primera respuesta
- CSAT promedio

**Gráficos (fila 2)**:
- Volumen de conversaciones por canal (últimos 7 días) — bar chart stacked
- Distribución por estado actual — donut chart

**Tablas (fila 3)**:
- Top 5 agentes por conversaciones resueltas
- Conversaciones más antiguas sin resolver (CTA "Ver todas")

---

### 5. Funcionalidades Futuras a Contemplar en el Diseño

Los contenedores y espacios deben anticipar estas features:

1. **Búsqueda global con IA**: barra de búsqueda en el topbar del inbox que combina full-text + semántica — dejar espacio en la cabecera
2. **Collision detection**: cuando dos agentes están viendo la misma conversación, mostrar avatar bubble del otro agente en el header de la conversación
3. **AI Reply Quality Score**: indicador numérico 0-100 en la burbuja de respuesta IA sugerida
4. **Traducción en tiempo real**: ya parcialmente integrado vía HelpdeskTranslate — el switch en el composer debe estar pre-diseñado
5. **Sentiment trending**: mini sparkline en el header de la conversación mostrando tendencia de sentimiento del contacto
6. **Resumen IA de conversación**: botón `fas fa-robot` en la cabecera que abre un popover con resumen de la conversación en bullets

---

## Archivos Blade Clave para Modificar

```
modules/Helpdesk/resources/views/
├── helpdesk/
│   ├── inbox/
│   │   ├── index.blade.php                        ← Layout principal three-panel
│   │   ├── partials/
│   │   │   ├── conversation-list.blade.php         ← Lista de items
│   │   │   ├── conversation-thread.blade.php       ← Thread central
│   │   │   ├── conversation-context.blade.php      ← Panel derecho
│   │   │   ├── composer.blade.php                  ← Compositor de respuesta
│   │   │   └── modals/
│   │   │       ├── assign.blade.php
│   │   │       ├── snooze.blade.php
│   │   │       ├── tags.blade.php
│   │   │       ├── create-ticket.blade.php
│   │   │       ├── merge.blade.php
│   │   │       └── filter-advanced.blade.php       ← Cambiar a offcanvas
│   └── dashboard/
│       └── index.blade.php
└── settings/
    ├── index.blade.php
    ├── brands/
    ├── workflows/
    ├── sla-policies/
    ├── canned-replies/
    └── macros/
```

---

## CSS a Crear/Modificar

Crear `modules/Helpdesk/public/css/helpdesk-inbox.css`:
- Variables específicas del inbox
- `.hd-conv-item` y estados
- `.hd-bubble` (incoming/outgoing)
- `.hd-composer` y tabs
- `.hd-filter-chips`
- `.hd-sla-badge` con animación pulse

---

## Consideraciones de Accesibilidad

- `aria-label` en todos los botones de icono sin texto
- `role="listbox"` en la lista de conversaciones con `aria-selected`
- Contrast ratio mínimo 4.5:1 para texto sobre fondos de badge
- Focus visible en todos los elementos interactivos
- Keyboard navigation: `↑↓` para navegar conversaciones, `Enter` para abrir, `Esc` para cerrar modales
