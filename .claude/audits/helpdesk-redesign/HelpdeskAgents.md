# Prompt de Rediseño — Módulo HelpdeskAgents

> **IMPORTANTE**: Leer `SHARED-DESIGN-SYSTEM.md` antes de procesar este prompt.  
> Stack: Bootstrap 5.3 + jQuery + Font Awesome 6. SIN Livewire, SIN Alpine.  
> **Crítico**: El constructor de flujos IA usa arrays PHP + Sortable.js (NO React Flow canvas). Mantener esta arquitectura.

---

## Contexto del Módulo

**HelpdeskAgents** gestiona el equipo humano y los agentes de IA del helpdesk. Tiene 12 vistas Blade. Incluye: gestión de agentes (CRUD, roles, disponibilidad), constructor de flujos de IA (secuencia de nodos: trigger → condición → acción → respuesta → handoff), turnos/shifts, vacaciones y un calendario de guardia (on-call).

**Rutas principales**: `panel/helpdesk-agents/*`  
**Aliases de permiso**: `helpdesk.agents.*`

---

## Áreas a Rediseñar

### 1. Lista de Agentes (`agents/index.blade.php`)

**Estado actual**: Tabla básica con avatar, nombre, rol, estado y acciones.

**Mejoras deseadas**:

#### 1.1 Vista Card Grid (reemplaza tabla para gestores)
Mantener toggle tabla/cards. La vista card muestra por agente:

```
┌──────────────────────────────┐
│  ●  [Avatar 64px]            │  ← Dot indicador: verde=online, gris=offline, amarillo=ocupado
│     Laura García             │
│     Agente Senior            │
│  ─────────────────────────   │
│  🗣️ 8 conv.  ⏱️ 1h 20m avg  │  ← Conversaciones activas + tiempo medio respuesta hoy
│  📊 92% CSAT  ✅ 14 res.    │  ← CSAT score + tickets resueltos esta semana
│  ─────────────────────────   │
│  [Inboxes: Principal, Sales] │  ← Badges de inboxes asignados
│  [···]                       │  ← Dropdown de acciones
└──────────────────────────────┘
```

- Dot de disponibilidad con tooltip: "Disponible desde hace 2h" / "Ocupado" / "Fuera de turno"
- Hover: card se eleva con shadow `0 4px 16px rgba(0,0,0,0.12)`
- Click en card: abre panel lateral (offcanvas right) con perfil completo del agente

#### 1.2 Panel de Perfil de Agente (Offcanvas)
- Avatar grande + nombre + rol + estado
- Métricas del período: CSAT, Tiempo medio respuesta, Tickets resueltos, Conversaciones activas
- Mini gráfico de actividad últimos 7 días (line chart pequeño)
- Lista de inboxes asignados con toggle de activación por inbox
- Historial reciente de conversaciones asignadas (últimas 5)
- Botones: "Editar perfil" / "Ver conversaciones" / "Asignar conversación"

#### 1.3 Filtros de Lista
- Chips: Todos | Online | Offline | Con turno activo | Sin turnos asignados
- Selector de inbox (para filtrar agentes por inbox)
- Búsqueda por nombre

---

### 2. Constructor de Flujos de IA (`ai-flows/` o `agents/flows/`)

**Estado actual**: Constructor funcional basado en arrays PHP para nodos/aristas + Sortable.js para reordenar. Interfaz funcional pero visualmente poco clara sobre el flujo lógico.

**Restricción crítica**: NO usar React Flow ni ningún canvas drag-and-drop. Continuar con la arquitectura array + Sortable.js. El objetivo es hacer el mismo sistema más visual y comprensible sin cambiar el paradigma técnico.

#### 2.1 Lista de Flujos IA
Card grid de flujos:
- Nombre + descripción
- Badge de tipo: `fas fa-robot` Chatbot / `fas fa-handshake` Handoff / `fas fa-bolt` Automatización
- Trigger principal resumido: "Cuando: Cliente escribe en chat"
- Estadísticas: N conversaciones atendidas esta semana, % handoff
- Estado toggle activo/pausado
- Acciones: Editar / Duplicar / Ver estadísticas / Eliminar

#### 2.2 Editor de Flujo — Rediseño Visual (Sin Canvas)

El editor mantiene la estructura lineal pero se presenta como un **diagrama vertical accordion**:

```
┌─────────────────────────────────────────┐
│  TRIGGER                         [+ Añadir trigger] │
│  ┌──────────────────────────────────┐   │
│  │ fas fa-bolt  Cliente inicia chat  │   │
│  │ Canal: Chat web                   │   │
│  └──────────────────────────────────┘   │
│                   │ (línea vertical)     │
│  CONDICIÓN                              │
│  ┌──────────────────────────────────┐   │
│  │ fas fa-code-branch  SI           │   │
│  │ Idioma detectado ES español       │   │
│  │  ├── SÍ → [siguiente nodo]       │   │
│  │  └── NO → [rama alternativa]     │   │
│  └──────────────────────────────────┘   │
│                   │                     │
│  ACCIÓN                                 │
│  ┌──────────────────────────────────┐   │
│  │ fas fa-comment  Enviar mensaje    │   │
│  │ "¡Hola! ¿En qué puedo ayudarte?" │   │
│  └──────────────────────────────────┘   │
│                   │                     │
│  [+ Añadir siguiente paso]              │
└─────────────────────────────────────────┘
```

**Cada nodo** es un `<div class="hd-flow-node">` con:
- Borde izquierdo colored por tipo: trigger=morado, condición=azul, acción=verde, handoff=naranja
- Icono del tipo de nodo
- Resumen del contenido del nodo (editable al hacer click)
- Botones de nodo (top-right): `fas fa-grip-vertical` (drag Sortable), `fas fa-pen-to-square` editar, `fas fa-trash` eliminar
- Flecha/línea vertical conectora entre nodos (CSS `::after` border-left)
- Botón `[+ Añadir paso]` entre cada par de nodos

**Conector de rama**: cuando hay condición SÍ/NO, se bifurca en dos columnas con `display: grid; grid-template-columns: 1fr 1fr;`

#### 2.3 Editor de Nodo (Modal)
Al hacer click en editar nodo, un modal lateral (`modal-xl` o offcanvas):
- Tipo de nodo: selector de tipo con iconos (radio cards)
- Configuración específica por tipo:
  - **Trigger**: canal, condición de disparo, keywords
  - **Condición**: campo, operador, valor — constructor visual de condición
  - **Respuesta**: textarea con variables `{{nombre}}`, selector de tono, preview
  - **Handoff**: selector de agente/equipo, mensaje de traspaso
  - **Esperar**: duración, acción si no hay respuesta
  - **Llamar webhook**: URL, método, headers, body template

#### 2.4 Panel de Estadísticas del Flujo
Tab secundaria "Estadísticas" en el editor:
- Total conversaciones atendidas por el flujo
- % que completan el flujo vs. % que hacen handoff
- % que abandonan (sin respuesta tras el mensaje del bot)
- Nodo con mayor drop-off (destacado en rojo en el diagrama)
- Distribución de respuestas por nodo de tipo "Respuesta"

---

### 3. Turnos / Shifts (`shifts/`)

**Estado actual**: CRUD funcional con horarios.

**Mejoras deseadas**:

#### 3.1 Vista Semanal de Turnos
Reemplazar tabla por vista **grid semana** tipo calendario:
- Eje X: días de la semana (L-D)
- Eje Y: horas (0-23, solo mostrar rango laboral)
- Bloques de turno: rectángulos coloreados por agente o por equipo
- Hover en bloque: tooltip con nombre agente, horario exacto, inbox asignado
- Click en celda vacía: abrir modal "Crear turno" con fecha/hora pre-rellenada

#### 3.2 Vista de Cobertura
Toggle "Ver cobertura": transforma la vista en un heatmap del nivel de cobertura por hora/día
- Verde oscuro = ≥3 agentes, verde claro = 2 agentes, amarillo = 1 agente, rojo = sin cobertura
- Útil para identificar huecos de cobertura visualmente

---

### 4. Vacaciones / Time-Off (`vacations/`)

**Estado actual**: Lista básica de solicitudes.

**Mejoras deseadas**:

#### 4.1 Calendar View de Ausencias
- Vista mensual de ausencias de todo el equipo
- Cada agente tiene un color asignado (auto-generado)
- Barras horizontales en el calendario representan períodos de ausencia
- Selector de mes/año en el header
- Leyenda de agentes

#### 4.2 Panel de Solicitudes Pendientes
- Cards de solicitudes con: avatar agente, fechas, días contados, tipo (vacaciones/enfermedad/personal), nota
- Botones de aprobación/rechazo inline en la card
- Rechazo requiere campo de motivo (textarea en el mismo card, no modal)
- Indicador de cobertura: "⚠️ 2 agentes ausentes ese día" si hay solapamiento

---

### 5. On-Call / Guardia (`oncall/`)

**Estado actual**: Calendario de guardia existente.

**Mejoras deseadas**:
- Rota visual circular o tabla semanal con períodos de guardia
- Badge especial para el agente de guardia actual: `fas fa-shield` + "De guardia ahora"
- Notificación automática al agente cuando empieza su turno de guardia
- Historial de guardias realizadas por agente

---

### 6. Funcionalidades Futuras (Espacio Reservado)

1. **Leaderboard de agentes**: tabla de rankings por métrica (CSAT, resolución, velocidad) — gamificación
2. **AI Performance Coach**: sugerencias automáticas por agente ("Tus tiempos de respuesta mejoraron, pero tu CSAT bajó — revisar tono")
3. **Skill-based routing**: tags de habilidades por agente + asignación automática según skill match
4. **Disponibilidad en tiempo real**: slider manual en el header del agente para cambiar estado (Disponible/Ocupado/Ausente)
5. **Video call integrado**: botón en conversación para iniciar videollamada (WebRTC, ya parcialmente disponible en HelpdeskLivechat)

---

## Archivos Clave

```
modules/HelpdeskAgents/resources/views/
├── agents/
│   ├── index.blade.php          ← Card grid + toggle tabla
│   ├── show.blade.php           ← Migrar a offcanvas
│   ├── create.blade.php
│   ├── edit.blade.php
│   └── flows/
│       ├── index.blade.php      ← Lista de flujos IA
│       ├── create.blade.php     ← Editor de flujo (diagrama vertical)
│       └── edit.blade.php
├── shifts/
│   ├── index.blade.php          ← Grid semanal
│   └── create.blade.php
├── vacations/
│   ├── index.blade.php          ← Calendar view
│   └── requests.blade.php       ← Panel de solicitudes
└── oncall/
    └── index.blade.php          ← Rota de guardia
```

---

## CSS Específico

Crear `modules/HelpdeskAgents/public/css/agents.css`:
- `.hd-agent-card` con disponibilidad dot
- `.hd-flow-node` con border-left por tipo
- `.hd-flow-connector` (línea vertical CSS)
- `.hd-shift-grid` layout semana
- `.hd-coverage-heatmap` colores de cobertura
