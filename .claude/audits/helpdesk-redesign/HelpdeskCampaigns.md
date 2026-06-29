# Prompt de Rediseño — Módulo HelpdeskCampaigns

> **IMPORTANTE**: Leer `SHARED-DESIGN-SYSTEM.md` antes de procesar este prompt.  
> Stack: Bootstrap 5.3 + jQuery + Font Awesome 6. El widget embebible ya existe en `/public/js/widget.js`.

---

## Contexto del Módulo

**HelpdeskCampaigns** gestiona campañas de engagement proactivo: popups, banners, slides y pantallas completas que se muestran en el sitio del cliente para captar conversaciones. Tiene 11 vistas Blade. Incluye: editor de campañas con tabs (diseño, targeting, comportamiento), variantes A/B, flujo de aprobación y analytics de conversión.

**Rutas principales**: `panel/helpdesk-campaigns/*`  
**Tipos de campaña**: popup, banner, slide-in, full-screen  
**Widget embebible**: `/public/js/widget.js` (ya en producción)  
**Aliases de permiso**: `helpdesk.campaigns.*`

---

## Áreas a Rediseñar

### 1. Lista de Campañas (`campaigns/index.blade.php`)

**Estado actual**: Tabla o listado básico.

**Mejoras deseadas**:

#### 1.1 Vista Card Grid con Preview
Cada campaña se muestra como card con:
```
┌─────────────────────────────────────┐
│  [Preview thumbnail 16:9]           │  ← Miniatura del diseño de la campaña
│  ─────────────────────────────────  │
│  Bienvenida a nuevos visitantes     │  ← Nombre
│  Popup • 3 variantes                │  ← Tipo badge + # variantes
│  ─────────────────────────────────  │
│  👁️ 1,240 imp.  💬 87 conv.  7.1%  │  ← Impresiones, conversiones, tasa
│  ─────────────────────────────────  │
│  [● Activa]          [···]          │  ← Estado toggle + dropdown acciones
└─────────────────────────────────────┘
```
- Thumbnail: renderizado CSS de la campaña (posición, colores) o placeholder por tipo
- Tipo con icono: `fas fa-window-restore` Popup, `fas fa-bars` Banner, `fas fa-arrow-right-to-bracket` Slide-in, `fas fa-expand` Full-screen
- Toggle activo/pausado con confirmación inline si hay variantes en testing
- Hover: botón "Editar" overlay sobre el thumbnail

#### 1.2 Filtros y Ordenación
Chips: Todas | Activas | Pausadas | En revisión | Borradores | Archivadas  
Selector: Ordenar por (Conversión, Impresiones, Fecha creación, Nombre)

#### 1.3 Botón "Nueva campaña" con Selector de Tipo
Click abre modal de selección:
```
┌─────────────────────────────────────────────────────┐
│  Elige el tipo de campaña                           │
│  ─────────────────────────────────────────────────  │
│  [📌 Popup]     [📢 Banner]                         │
│  Ventana modal  Franja superior/inferior            │
│  [➡️ Slide-in]  [⬛ Pantalla completa]              │
│  Deslizable     Overlay completo                    │
└─────────────────────────────────────────────────────┘
```
Cada tipo como card seleccionable con icono grande, nombre y descripción breve.

---

### 2. Editor de Campaña (`campaigns/create.blade.php`, `campaigns/edit.blade.php`)

**Estado actual**: Editor con tabs (diseño, targeting, comportamiento). Funcional pero sin preview en tiempo real.

**Mejoras deseadas**:

#### 2.1 Layout del Editor
Split horizontal: Editor izquierda (60%) | Preview derecha (40%)

```
┌──────────────────────────┬─────────────────────────┐
│ [ Diseño | Targeting |   │  PREVIEW                │
│   Comportamiento | A/B ] │                         │
│                          │  ┌─────────────────┐   │
│  (Tabs de configuración) │  │  [Vista previa  │   │
│                          │  │  en tiempo real │   │
│                          │  │  de la campaña] │   │
│                          │  └─────────────────┘   │
│                          │                         │
│                          │  Dispositivo: [💻][📱]  │
└──────────────────────────┴─────────────────────────┘
```

Preview actualizable en tiempo real con debounce 500ms al cambiar cualquier campo.

#### 2.2 Tab Diseño

**Sección: Contenido**
- Titular: input text con contador caracteres
- Subtítulo: input text
- Mensaje: textarea
- CTA Button: texto + URL + selector de color (color picker simple, 8 presets + custom hex)
- Imagen/Logo: upload con preview inline

**Sección: Apariencia**
- Posición: grid de posiciones 3×3 (para popup/slide-in) o selector arriba/abajo (para banner)
  ```
  [↖] [↑] [↗]
  [←] [●] [→]
  [↙] [↓] [↘]
  ```
  Botones cuadrados 40px, activo con borde primario
- Dimensiones: slider de ancho (para popup, rango 300–800px)
- Colores: fondo, texto, botón — color pickers
- Animación de entrada: selector (fade, slide-up, bounce, zoom) con mini preview
- Delay de entrada: slider en segundos

#### 2.3 Tab Targeting

**Audiencia**:
- URL del sitio: input con operadores (contiene, es exactamente, empieza por) — tag input
- Dispositivo: checkboxes Desktop / Tablet / Móvil con iconos
- País: select2 multi-select con banderas
- Visitante nuevo vs retornante: radio buttons
- Fuente de tráfico: multi-select (Google, Facebook, Direct, Email, etc.)

**Segmentos avanzados**:
- Constructor de condiciones visual (mismo patrón que el constructor de workflows en HelpdeskAgents)
- N páginas visitadas, N sesiones anteriores, tiempo en el sitio

#### 2.4 Tab Comportamiento

- **Trigger**: radio cards
  - Tras N segundos en la página (slider)
  - Al scroll X% (slider)
  - Al intentar salir (exit intent)
  - Al hacer click en elemento (input CSS selector)
  - Inmediato
- **Frecuencia**: ¿Cuántas veces mostrar a cada visitante?
  - Siempre | Una vez por sesión | Una vez por N días (slider) | Solo una vez nunca más
- **Cerrar campaña**: ¿Cuándo cerrar automáticamente?
  - Al hacer click fuera | Nunca | Tras N segundos
- **Acciones post-conversión**: redirect URL | abrir chat | cerrar sin acción

#### 2.5 Tab A/B Testing

- Botón "+ Añadir variante" (máximo 4 variantes)
- Cada variante: nombre editable, porcentaje de tráfico (slider, suma debe ser 100%), badge "Control" en la original
- Indicador de distribución: barra dividida proporcional
- Variante activa para editar seleccionada visualmente con borde
- Preview A/B: toggle entre variantes en el preview lateral

---

### 3. Vista de Variantes (`campaigns/variants/`)

**Tabla de variantes con métricas A/B**:

```
Variante    | Impresiones | Conversiones | Tasa  | Ganadora
──────────────────────────────────────────────────────────
Control (A) |  620        | 44           | 7.1%  | —
Variante B  |  620        | 63           | 10.2% | ✓ (+43%)
```

- Badge "Estadísticamente significativo" cuando p-value < 0.05
- Botón "Aplicar variante ganadora" que hace ganador al control → confirma en modal
- Gráfico de conversión en el tiempo por variante (líneas)

---

### 4. Flujo de Aprobación (`campaigns/approvals/`)

**Rediseño con stepper visual**:

Usando el patrón `SHARED-DESIGN-SYSTEM.md §5.8`:
```
[Borrador] → [En revisión] → [Aprobada] → [Activa]
```

**Vista de cola de revisión** (para revisores):
- Cards de campañas pendientes con: thumbnail preview, nombre, solicitante, fecha envío
- Dos acciones primarias inline: "Aprobar" (verde) / "Solicitar cambios" (naranja)
- "Solicitar cambios" expande textarea de nota de revisión debajo de la card
- Badge contador en el nav: `Revisión (3)`

**Timeline de aprobación** en el detalle de campaña:
```
● Creada por Miguel — 3 May 2026 14:23
● Enviada a revisión — 3 May 2026 14:45
○ En espera de aprobación de Laura García
```

---

### 5. Analytics de Campaña (`campaigns/analytics/`)

**KPI Cards**:
- Impresiones totales | Conversiones totales | Tasa de conversión | Clics en CTA

**Gráficos**:
- Impresiones vs. Conversiones por día (líneas dobles)
- Distribución por dispositivo (donut: desktop/tablet/móvil)
- Mapa de calor de impresiones por hora/día (heatmap grid 7×24)
- Top páginas por tasa de conversión (tabla ordenada)

**Filtros**:
- Selector de campaña (multi-select o filtro por campaña individual)
- Rango de fechas (flatpickr range)
- Dispositivo

---

### 6. Gestión del Widget (`widget-settings/`)

Sección de configuración del código de instalación del widget:

- Snippet de código con botón "Copiar" (`fas fa-copy`)
- Instrucciones paso a paso (acordeón)
- Test de instalación: botón "Verificar instalación" que hace ping al dominio configurado
- Historial de versiones del widget

---

### 7. Funcionalidades Futuras (Espacio Reservado)

1. **Personalización dinámica**: variables en el mensaje `{{nombre_usuario}}`, `{{producto_visto}}`
2. **Integración con Helpdesk inbox**: las conversaciones iniciadas por campaña se enrutan a inboxes específicos
3. **Campaña de email proactivo**: envío de email a segmento cuando no hay sesión activa
4. **Preview en iframe real**: mostrar el preview en un iframe de la URL target del cliente
5. **Builder drag-and-drop** (futuro lejano): reemplazar formularios por constructor visual similar a Wisepops — solo si se migra a un framework de componentes
6. **Multi-step campaigns**: flujo de varios pasos (paso 1: email captura, paso 2: mensaje personalizado)

---

## Archivos Clave

```
modules/HelpdeskCampaigns/resources/views/
├── campaigns/
│   ├── index.blade.php          ← Card grid
│   ├── create.blade.php         ← Editor tabbed con split preview
│   ├── edit.blade.php
│   ├── show.blade.php           ← Detalle con analytics
│   ├── variants/
│   │   └── index.blade.php      ← Tabla A/B
│   └── approvals/
│       └── index.blade.php      ← Cola de revisión
├── analytics/
│   └── index.blade.php
└── widget-settings/
    └── index.blade.php
```

---

## CSS Específico

Crear `modules/HelpdeskCampaigns/public/css/campaigns.css`:
- `.hd-campaign-card` con thumbnail y overlay
- `.hd-position-grid` para el selector 3×3
- `.hd-ab-bar` distribución proporcional
- `.hd-preview-device` toggle desktop/mobile
