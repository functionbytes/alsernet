# Prompt de Rediseño — Módulo HelpdeskSocial

> **IMPORTANTE**: Leer `SHARED-DESIGN-SYSTEM.md` antes de procesar este prompt.  
> Stack: Bootstrap 5.3 + jQuery + Font Awesome 6. Para gráficos: Chart.js. Para drag-and-drop: Sortable.js.

---

## Contexto del Módulo

**HelpdeskSocial** gestiona la presencia en redes sociales (Meta/WhatsApp únicamente: Facebook, Instagram, WhatsApp Business). Tiene 25 vistas Blade y 19 modelos. Incluye: inbox social unificado, gestión de comentarios, análisis de sentimiento e intención con IA, flujo de aprobación de contenido, monitoreo de competidores y 10 comandos de consola para sincronización.

**Rutas principales**: `panel/helpdesk-social/*`  
**Redes soportadas**: Facebook Pages, Instagram Business, WhatsApp Business API  
**Aliases de permiso**: `helpdesk.social.*`

---

## Áreas a Rediseñar

### 1. Inbox Social (`social/inbox/index.blade.php`)

El inbox social es la vista más usada. Implementar el layout three-panel del `SHARED-DESIGN-SYSTEM.md §4.1` con adaptaciones específicas para social.

#### 1.1 Panel Izquierdo: Lista de Mensajes Sociales

**Filtros como chips**:
```
[Todos (47)] [Sin leer (12)] [Comentarios] [DMs] [WhatsApp] [···]
```

**Chips de red social** (segunda fila opcional):
```
[fab fa-facebook FB] [fab fa-instagram IG] [fab fa-whatsapp WA]
```

**Cada item de la lista**:
```
┌────────────────────────────────────┐
│● [Ava]  Juan López        2m       │  ← Dot no leído, avatar, nombre, tiempo
│ [fab fa-facebook] Comentario post  │  ← Icono red + tipo mensaje
│ "Excelente servicio, los..."        │  ← Preview truncado 2 líneas
│ [● Negativo] [Queja] [P1]          │  ← Sentimiento + intención + prioridad
└────────────────────────────────────┘
```

- **Borde izquierdo** 3px por sentimiento: verde=positivo, gris=neutral, rojo=negativo
- **Badge de red social**: `fab fa-facebook` azul, `fab fa-instagram` gradient, `fab fa-whatsapp` verde
- **Badge de intención**: chips de colores (ver tabla en §1.3)
- Checkbox en hover para selección múltiple y bulk actions

#### 1.2 Panel Central: Thread de Mensajes

**Header del thread**:
- Avatar + nombre + handle (@usuario) + icono de red
- Botones de acción: `fas fa-user-check` Asignar, `fas fa-check-circle` Resolver, `fas fa-flag` Escalar, `fas fa-ellipsis-vertical` Más
- Badge de sentimiento del contacto (global, no solo el mensaje actual)
- Timer WhatsApp (si es canal WA): ver §1.4

**Mensajes en el thread**:
- Mensajes entrantes (izquierda): burbuja gris, icono de red en la esquina inferior
- Mensajes salientes (derecha): burbuja `var(--hd-primary-light)` con texto oscuro
- Comentarios de post: renderizado especial — mostrar thumbnail del post + el comentario debajo
- Notas internas: burbuja amarilla `fas fa-lock`
- Eventos de sistema: línea centrada italic

**Composer**:
- Textarea expandible con límites de caracteres por red (FB: 8000, IG: 2200, WA: 4096)
- Contador de caracteres restantes en esquina inferior derecha, rojo si excede
- Botones toolbar: `fas fa-paperclip` adjunto, `fas fa-face-smile` emoji, `fas fa-language` traducir
- WhatsApp: cuando la sesión está cerrada, el textarea está deshabilitado + banner "Sesión expirada" (ver §1.4)

#### 1.3 Sentimiento e Intención AI

**Badges de sentimiento** (usando CSS del sistema compartido):
```html
<!-- Borde izquierdo en lista + badge en thread -->
<span class="badge hd-badge-sentiment-pos"><i class="fas fa-face-smile me-1"></i>Positivo</span>
<span class="badge hd-badge-sentiment-neu"><i class="fas fa-minus me-1"></i>Neutral</span>
<span class="badge hd-badge-sentiment-neg"><i class="fas fa-face-frown me-1"></i>Negativo</span>
```

**Badges de intención** (chips en la fila de metadata del item):
```html
<span class="badge hd-intent-love"><i class="fas fa-heart me-1"></i>Elogio</span>
<span class="badge hd-intent-support"><i class="fas fa-circle-question me-1"></i>Consulta</span>
<span class="badge hd-intent-risk"><i class="fas fa-triangle-exclamation me-1"></i>Queja</span>
<span class="badge hd-intent-info"><i class="fas fa-info-circle me-1"></i>Información</span>
```

| Intención | Color | Icono |
|-----------|-------|-------|
| Elogio / Amor a la marca | `#16a34a` verde | `fas fa-heart` |
| Consulta / Soporte | `#2563eb` azul | `fas fa-circle-question` |
| Queja / Alto riesgo | `#dc2626` rojo | `fas fa-triangle-exclamation` |
| Información general | `#6b7280` gris | `fas fa-info-circle` |

**Override manual de sentimiento**: en el panel de contexto (right sidebar), campo "Sentimiento detectado" con dropdown editable para que el agente lo corrija.

#### 1.4 WhatsApp — Ventana de 24 Horas

El mayor diferenciador de UX de WhatsApp Business:

**Timer de sesión en el header**:
```html
<!-- > 2 horas restantes -->
<div class="hd-wa-timer hd-wa-timer--ok">
  <i class="fas fa-clock me-1"></i>
  <span class="hd-wa-timer__countdown">18h 42m restantes</span>
</div>

<!-- < 2 horas restantes — rojo + pulso -->
<div class="hd-wa-timer hd-wa-timer--urgent hd-pulse">
  <i class="fas fa-triangle-exclamation me-1"></i>
  <span class="hd-wa-timer__countdown">1h 24m restantes</span>
</div>

<!-- Sesión expirada -->
<div class="hd-wa-timer hd-wa-timer--expired">
  <i class="fas fa-ban me-1"></i>
  <span>Sesión expirada</span>
</div>
```

**Estado "Sesión expirada"**:
- Textarea del compositor reemplazado por:
```html
<div class="alert alert-warning d-flex align-items-center gap-3">
  <i class="fas fa-exclamation-circle"></i>
  <div>
    <strong>Sesión de WhatsApp expirada</strong>
    <p class="mb-0 small">La ventana de 24h ha cerrado. Envía un mensaje plantilla para reanudar.</p>
  </div>
  <button class="btn btn-warning ms-auto" id="openTemplatePicker">
    <i class="fas fa-bookmark me-1"></i>Elegir plantilla
  </button>
</div>
```

**Modal selector de plantillas WA** (`#modalWaTemplate`):
```
┌─────────────────────────────────────────────────────┐
│  Seleccionar plantilla de WhatsApp              [×]  │
│  ─────────────────────────────────────────────────── │
│  [🔍 Buscar plantilla...]                           │
│  Categoría: [Todas ▼]  Estado: [Aprobadas ▼]        │
│  ─────────────────────────────────────────────────── │
│  ┌────────────────────────────────────────────┐      │
│  │ UTILITY   Recordatorio de cita             │ ✓ AP │
│  │ "Hola {{1}}, tu cita es el {{2}} a las    │      │
│  │  {{3}}. ¿Confirmas?"                       │      │
│  │ [ES] [EN]  Variables: 3                    │      │
│  └────────────────────────────────────────────┘      │
│  ┌────────────────────────────────────────────┐      │
│  │ MARKETING  Oferta especial                 │ ✓ AP │
│  │ "¡Hola {{1}}! Tenemos una oferta..."       │      │
│  └────────────────────────────────────────────┘      │
│  ─────────────────────────────────────────────────── │
│  [Cancelar]                        [Usar plantilla]  │
└─────────────────────────────────────────────────────┘
```

- Badge de categoría: UTILITY / MARKETING / AUTHENTICATION
- Badge de estado: `✓ AP` = Aprobada (verde), `⏳ PE` = Pendiente (amarillo), `✗ RE` = Rechazada (rojo)
- Al seleccionar, el modal muestra los campos de variables `{{1}}`, `{{2}}` para rellenar antes de enviar

#### 1.5 Panel Derecho: Contexto del Contacto

- Avatar + nombre + handle + red social
- Datos sociales: followers, seguimiento mutuo, fecha primer contacto
- **Historial de sentimiento**: mini bar horizontal "Últimas 20 interacciones" con segmentos verde/gris/rojo proporcionales
- **Timeline de interacciones**: lista compacta de últimas 5 interacciones (fecha + tipo)
- Etiquetas del contacto con `+` para agregar
- **Score de riesgo**: badge numérico 0-100 basado en sentimiento histórico ("Riesgo de churn: 72/100")

#### 1.6 Bulk Actions para Mensajes Sociales

Toolbar flotante al seleccionar mensajes:
```html
<div class="hd-bulk-bar">
  <span><strong>8</strong> mensajes seleccionados</span>
  <button class="btn btn-sm btn-outline-secondary">Asignar</button>
  <button class="btn btn-sm btn-outline-secondary">Marcar revisado</button>
  <button class="btn btn-sm btn-outline-secondary">Etiquetar</button>
  <button class="btn btn-sm btn-outline-secondary">Ocultar comentarios</button>
  <button class="btn btn-sm btn-outline-danger">Eliminar comentarios</button>
</div>
```

---

### 2. Gestión de Comentarios (`social/comments/`)

Vista especializada para moderación de comentarios de posts (diferente al inbox de DMs):

**Selector de post** en la cabecera: dropdown "Ver comentarios de: [Post] ▼"
- Lista de posts recientes con thumbnail y fecha

**Layout**:
- Vista por post: accordion donde cada post es colapsable
- Al expandir: lista de comentarios del post con acciones inline
- Comentario card: avatar + texto + fecha + botones (Responder / Ocultar / Eliminar / Like)
- "Responder" expande textarea inline debajo del comentario (no abre modal)

**Collision detection**: badge de avatar de compañero si otro agente está viendo el mismo comentario

---

### 3. Aprobación de Contenido (`social/approvals/`)

Para contenido programado que requiere aprobación antes de publicar:

**Stepper** (usando patrón del sistema compartido):
```
[Borrador] → [En revisión] → [Aprobado] → [Publicado]
```

**Cola de revisión**: cards de contenido pendiente con:
- Preview del post (texto + imagen si hay)
- Red social destino con icono
- Fecha/hora programada
- Autor y fecha de envío a revisión
- Botones: "Aprobar" (verde) / "Solicitar cambios" (naranja)
- "Solicitar cambios" expande textarea de nota de revisión inline, sin modal

**Historial de aprobaciones**: tabla con: post, estado, aprobado por, fecha

---

### 4. Monitoreo de Competidores (`social/competitors/`)

Dashboard de inteligencia competitiva:

#### 4.1 Cabecera
- Selector "Tu marca + competidores": dropdown multi-select de cuentas configuradas
- Rango de fechas: flatpickr range
- Filtro de red: chips Facebook / Instagram / WhatsApp

#### 4.2 Share of Voice
```html
<div class="hd-sov-chart">
  <!-- Donut chart Chart.js: mi marca + competidores, cada uno con color -->
  <!-- Centro: total de menciones del período -->
</div>
```
- Leyenda con nombre + porcentaje + tendencia (flecha)

#### 4.3 Tabla Comparativa de Métricas

| Métrica | Mi marca | Competidor A | Competidor B |
|---------|----------|-------------|-------------|
| Seguidores | 12,450 `↑3%` | 45,200 `↑1%` | 8,900 `↓2%` |
| Engagement rate | 4.2% `↑` | 2.1% `→` | 5.8% `↑` |
| Posts/semana | 7 | 12 | 3 |
| Respuesta promedio | 2h | 6h | 1h |
| Sentimiento positivo | 72% | 65% | 81% |

- Celdas con flechas de tendencia coloreadas
- Mi marca siempre en la primera columna con fondo levemente tintado
- Ordenable por métrica

#### 4.4 Top Contenido Competidores

Grid horizontal scrollable de posts con mejor performance de la competencia:
- Thumbnail del post (o placeholder si no disponible)
- Engagement count (likes + comments + shares)
- Tipo de contenido badge (imagen, video, carrusel)
- Fecha

#### 4.5 Alertas de Competidores

Card de alertas configuradas:
- "Competidor A publicó más de 3 posts hoy" — ícono de alerta
- "Tu share of voice bajó 5 puntos esta semana"
- Botón "Configurar alertas" → modal con condiciones configurables

---

### 5. Calendario de Contenido (`social/calendar/`)

Vista de planificación de publicaciones programadas:

**Toggle de vista**: `fas fa-calendar-days` Mensual / `fas fa-calendar-week` Semanal / `fas fa-list` Lista

**Vista mensual**: chips de posts en las celdas del día, con icono de red y estado de aprobación

**Vista lista**: tabla cronológica con: thumbnail mini, caption preview, red (icono), fecha/hora, estado (badge), acciones

**Composer de post** (botón "+ Nuevo post"):
- Modal con: selector de red(es), editor de texto, upload de imagen/video, programador de fecha/hora, botón "Publicar ahora" vs "Programar" vs "Enviar a revisión"
- Preview del post según la red seleccionada (simulación de cómo se verá)

---

### 6. Analytics de Social (`social/analytics/`)

**KPI Cards**: Alcance total | Engagement total | Sentiment score | Tiempo medio respuesta

**Gráficos**:
- Engagement por red social y período (line chart multi-series)
- Distribución de sentimiento (donut: positivo/neutral/negativo)
- Heatmap de actividad (hora × día)
- Intenciones detectadas (horizontal bar chart)

**Tabla de top posts**: ordenable por engagement, alcance, comentarios

---

### 7. Funcionalidades Futuras (Espacio Reservado)

1. **Social listening extendido**: monitorear menciones más allá de propias cuentas (hashtags, keywords)
2. **TikTok integration**: tercera red social (las vistas deben anticipar N redes, no solo 2)
3. **Generación de respuestas con IA**: botón "Sugerir respuesta" en el composer basado en el mensaje y el tono de la marca
4. **Detección de crisis**: alerta automática cuando el volumen de mensajes negativos supera umbral
5. **Integración con Helpdesk inbox**: mensajes de redes que requieren seguimiento crean conversación en el inbox principal

---

## Archivos Clave

```
modules/HelpdeskSocial/resources/views/
├── social/
│   ├── inbox/
│   │   └── index.blade.php        ← Three-panel layout
│   ├── comments/
│   │   └── index.blade.php        ← Vista por post + comentarios inline
│   ├── approvals/
│   │   └── index.blade.php        ← Cola de revisión con stepper
│   ├── competitors/
│   │   └── index.blade.php        ← Dashboard competidores
│   ├── calendar/
│   │   └── index.blade.php        ← Calendario de contenido
│   └── analytics/
│       └── index.blade.php        ← Analytics generales
```

---

## CSS Específico

Crear `modules/HelpdeskSocial/public/css/social.css`:
- `.hd-wa-timer` con variantes `--ok`, `--urgent`, `--expired`
- `.hd-intent-love`, `.hd-intent-support`, `.hd-intent-risk`, `.hd-intent-info`
- `.hd-badge-sentiment-pos/neu/neg` (pueden reutilizarse del sistema compartido)
- `.hd-social-icon--fb`, `.hd-social-icon--ig`, `.hd-social-icon--wa`
- `.hd-sov-chart` container
- `.hd-template-card` para el picker de plantillas WA
