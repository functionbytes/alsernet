# Prompt de Rediseño — Módulo HelpdeskLivechat

> **IMPORTANTE**: Leer `SHARED-DESIGN-SYSTEM.md` antes de procesar este prompt.  
> **DOS UIs diferenciadas**: (A) Admin Blade (Bootstrap 5.3 + jQuery) y (B) Widget React (React 19 + Zustand + Vite).  
> Tratar cada UI por separado. El widget React es la excepción al stack jQuery del proyecto.

---

## Contexto del Módulo

**HelpdeskLivechat** es el sistema de chat en vivo. Tiene 8 vistas Blade para el admin + 35 archivos React/TypeScript para el widget embebible. El widget incluye: WebRTC (audio/video), RRWeb session replay, 8 pantallas de conversación, embed loader. No hay tests ni Storybook actualmente.

**Rutas admin**: `panel/helpdesk-livechat/*`  
**Widget**: embebible en sitios clientes via snippet  
**Aliases de permiso**: `helpdesk.livechat.*`

---

## Parte A: Admin Panel (Blade + Bootstrap 5.3)

### A.1 Dashboard de Livechat (`admin/dashboard.blade.php`)

**KPI Cards en tiempo real** (actualizadas cada 30s vía polling AJAX):
- Conversaciones activas ahora
- Agentes online
- Tiempo medio de espera
- CSAT del día

**Sección "En vivo ahora"**: tabla de conversaciones activas con:
- Visitante (nombre o "Visitante anónimo" + país flag)
- Agente asignado (o "Sin asignar" en rojo)
- Tiempo en cola (si sin asignar) / Duración (si activa)
- Página actual del visitante
- Acciones: "Tomar conversación" / "Monitorear"

**Monitor de equipo**: grid de cards de agentes (estado: disponible/ocupado/ausente)

#### A.1.1 Session Replay Preview
- Tabla "Sesiones grabadas recientes" con: visitante, duración, páginas visitadas, fecha
- Click en fila: modal con reproductor de sesión (RRWeb) integrado
- Controles básicos: play/pause, barra de progreso, velocidad (1x, 2x)

---

### A.2 Configuración del Widget (`admin/settings.blade.php`)

Layout settings estándar (`SHARED-DESIGN-SYSTEM.md §4.2`).

**Sección Apariencia**:
- Color del widget: color picker con preset de 8 colores + custom hex
- Posición: selector Bottom-Right / Bottom-Left con preview visual del widget
- Forma del launcher: círculo / cuadrado / personalizado
- Texto del launcher: input (ej: "¿Necesitas ayuda?")
- Avatar del agente visible: toggle
- **Preview en tiempo real**: iframe o div que simula el widget con los cambios aplicados

**Sección Comportamiento**:
- Saludo automático: toggle + textarea para el mensaje
- Delay de saludo: slider en segundos
- Horario de disponibilidad: selector de días + rangos horarios
- Respuesta fuera de horario: textarea

**Sección Canales Adicionales**:
- Links a WhatsApp, Email, Teléfono — campos con toggle activo/inactivo

**Código de instalación**:
```html
<div class="hd-snippet-block">
  <div class="d-flex justify-content-between align-items-center mb-2">
    <span class="fw-semibold small">Código de instalación</span>
    <button class="btn btn-sm btn-outline-secondary" id="copySnippet">
      <i class="fas fa-copy me-1"></i>Copiar
    </button>
  </div>
  <pre class="hd-code-block"><code><!-- snippet --></code></pre>
</div>
```

---

### A.3 Historial de Conversaciones Livechat (`admin/conversations/index.blade.php`)

- dxDataGrid con: visitante, agente, duración, mensajes count, CSAT, fecha
- Filtros: agente, rango de fechas, calificación CSAT, con/sin replay
- Click en fila: panel lateral (offcanvas) con el transcript completo de la conversación + botón ver replay si existe

---

### A.4 Analytics de Livechat (`admin/analytics/index.blade.php`)

**KPI período seleccionado**:
- Conversaciones totales | Resueltas | Abandonadas | CSAT promedio | Tiempo medio primera respuesta

**Gráficos**:
- Conversaciones por hora del día (bar chart — para identificar picos)
- Tiempo medio de espera por día (line chart)
- CSAT tendencia (line chart)
- Top 5 páginas donde más se inician chats (tabla)

---

## Parte B: Widget React (React 19 + Zustand + Vite)

### B.1 Principios Generales del Widget

El widget se inyecta en sitios de terceros. Debe:
- Funcionar en un shadow DOM o iframe aislado (sin conflictos CSS con el sitio host)
- Tener un diseño limpio, moderno y profesional
- Ser completamente responsive (mobile-first)
- Animaciones suaves (no jarring)
- Accesible (ARIA, keyboard navigation, focus management)

**Colores del widget**: configurables desde el admin (primario viene del setting). Default: `#90bb13`. Adaptar automáticamente contraste del texto sobre el color de fondo (wcag aa).

### B.2 Launcher (Estado cerrado)

El launcher es el único elemento visible cuando el chat está cerrado:

```
Variante 1 (solo ícono):
  [💬 circular button, 56px, shadow, animación pop-in]

Variante 2 (con texto):
  [💬 ¿Necesitas ayuda? ×]  ← pill con text y dismiss
```

- Animación de entrada: `scale(0) → scale(1)` con cubic-bezier rebote, 300ms
- Notificación de mensaje no leído: badge rojo con número sobre el launcher
- "Pulso" animado cuando hay un agente disponible que inició un saludo proactivo

### B.3 Pantalla 1: Inicio del Widget (Expanded, sin conversación)

```
┌─────────────────────────────┐
│ [Header: Logo + "Hola 👋"]  │
│ [Texto: "¿En qué podemos   │
│  ayudarte?"]                │
│ ─────────────────────────── │
│ 💬 Iniciar conversación     │
│ 📚 Ver base de conocimiento │
│ 📧 Enviar email             │
│ ─────────────────────────── │
│ Conversaciones anteriores   │
│ • Conv. hace 3 días >       │
└─────────────────────────────┘
```

- Opciones como lista de items con icono + texto + flecha
- Conversaciones anteriores con preview del último mensaje

### B.4 Pantalla 2: Chat Activo

```
┌─────────────────────────────┐
│ [← Back]  Laura García  [×] │
│ [Status: Escribiendo...]     │
│ ─────────────────────────── │
│                             │
│  [Burbuja agente 1]         │
│         [Burbuja usuario]   │
│  [Burbuja agente 2]         │
│                             │
│ ─────────────────────────── │
│ [📎][😊] [Input...]  [Send] │
└─────────────────────────────┘
```

**Burbujas de chat**:
- Agente: izquierda, fondo gris claro, border-radius `4px 16px 16px 16px`
- Usuario: derecha, fondo color primario, texto blanco, border-radius `16px 4px 16px 16px`
- Timestamp al hover (no persistente para no saturar)
- Indicador de typing: tres puntos animados (...)

**Toolbar del composer**:
- `fas fa-paperclip` adjuntos
- `fas fa-face-smile` selector emoji
- Input expandible (max 4 líneas)
- Botón send: `fas fa-paper-plane` o ícono flecha, deshabilitado si input vacío

**Header del chat**:
- Avatar del agente 32px + nombre + estado (disponible/ocupado)
- Botón de opciones `fas fa-ellipsis-vertical`: Ver historial, Solicitar email transcript, Minimizar

### B.5 Pantalla 3: Rating Post-Chat (CSAT)

```
┌─────────────────────────────┐
│  ¿Cómo fue tu experiencia?  │
│                             │
│  [ 😞 ]  [ 😐 ]  [ 😊 ]   │
│   Mala   Regular   Buena   │
│                             │
│  [Comentario opcional...]   │
│  [Enviar]                   │
└─────────────────────────────┘
```

- Emojis grandes (40px) como botones, el seleccionado se anima con scale(1.3)
- Comentario opcional, solo aparece tras seleccionar emoji
- Transición suave a pantalla de agradecimiento tras enviar

### B.6 Pantalla 4: Formulario Pre-Chat (Fuera de horario o con formulario obligatorio)

```
┌─────────────────────────────┐
│  Cuéntanos cómo ayudarte    │
│  ─────────────────────────  │
│  Nombre *                   │
│  [__________________]       │
│  Email *                    │
│  [__________________]       │
│  Mensaje *                  │
│  [__________________]       │
│  [                    ]     │
│  [Enviar mensaje]           │
└─────────────────────────────┘
```

- Validación inline (sin submit hasta que todos los campos requeridos estén OK)
- Animación de shake en campo inválido al intentar enviar
- Mensaje de éxito animado: checkmark + "Mensaje enviado. Te responderemos pronto."

### B.7 Pantalla 5: Video/Audio Call (WebRTC)

```
┌─────────────────────────────┐
│  Video call con Laura       │
│ ┌───────────────────────┐   │
│ │  [Video del agente]   │   │
│ │                       │   │
│ │  [Video propio, PiP]  │   │
│ └───────────────────────┘   │
│                             │
│ [🎤 Mute][📹 Cam][📞 End]  │
└─────────────────────────────┘
```

- Picture-in-Picture del video propio (draggable dentro del contenedor)
- Controles flotantes centrados en la parte inferior
- Indicador de calidad de conexión (dots de señal)

### B.8 Pantalla 6: KB Search (integrada con HelpdeskHelpcenter)

```
┌─────────────────────────────┐
│ [← Back]  Buscar ayuda  [×] │
│ ─────────────────────────── │
│ [🔍 Busca artículos...    ] │
│                             │
│ ARTÍCULOS SUGERIDOS         │
│ • ¿Cómo configurar X?     > │
│ • Primeros pasos          > │
│ • Facturación             > │
│                             │
│ [¿No encuentras respuesta?] │
│ [Hablar con un agente]      │
└─────────────────────────────┘
```

---

### B.9 Estado de Conexión y Fallbacks

**Indicadores de estado**:
- Conectado: punto verde en header
- Reconectando: "Reconectando..." con spinner
- Sin conexión: banner naranja "Sin conexión — intentando reconectar" + deshabilitar input

**Sin agentes disponibles**:
- Mensaje automático: "Nuestro equipo no está disponible ahora. Deja tu mensaje y te responderemos."
- Formulario de contacto fallback

---

### B.10 Zustand Store — Estructura de Estado

La UI debe reflejar estos estados del store:
```typescript
interface ChatStore {
  isOpen: boolean;
  screen: 'home' | 'chat' | 'prechat' | 'csat' | 'kb' | 'call';
  conversation: Conversation | null;
  messages: Message[];
  isTyping: boolean;
  connectionStatus: 'connected' | 'connecting' | 'disconnected';
  unreadCount: number;
  identity: Identity | null; // persistida en localStorage
}
```

Los componentes deben derivar su estado de aquí. Evitar estado local innecesario.

---

### B.11 Animaciones del Widget (React)

```typescript
// Patrón: usar CSS transitions + className toggling, NO Framer Motion (sin dependencias extra)
// El widget ya tiene CSS puro para animaciones — mantener ese patrón
const ANIMATION_DURATION = 300; // ms

// Apertura del widget
'.hd-widget-panel' {
  transform: scale(0.95) translateY(8px);
  opacity: 0;
  transition: transform 0.3s ease, opacity 0.3s ease;
}
'.hd-widget-panel.is-open' {
  transform: none;
  opacity: 1;
}
```

---

### B.12 Funcionalidades Futuras del Widget (Espacio Reservado)

1. **Co-browsing**: el agente puede ver la pantalla del visitante en tiempo real
2. **Screen annotation**: el agente dibuja sobre la pantalla del visitante
3. **File transfer mejorado**: preview de imágenes, progreso de upload, límite de tamaño visible
4. **Reactions a mensajes**: 👍 ❤️ 😄 (hover sobre burbuja)
5. **AI pre-chat intent detection**: antes de conectar con agente, el bot sugiere 3 artículos de KB relevantes
6. **Custom branding completo**: fonts, logos, formas personalizables desde el admin

---

## Archivos Clave

### Admin (Blade)
```
modules/HelpdeskLivechat/resources/views/admin/
├── dashboard.blade.php
├── settings.blade.php
├── conversations/
│   └── index.blade.php
└── analytics/
    └── index.blade.php
```

### Widget (React/TS)
```
modules/HelpdeskLivechat/resources/assets/js/
├── widget/
│   ├── components/
│   │   ├── Launcher.tsx
│   │   ├── ChatPanel.tsx
│   │   ├── screens/
│   │   │   ├── HomeScreen.tsx
│   │   │   ├── ChatScreen.tsx
│   │   │   ├── PreChatScreen.tsx
│   │   │   ├── CSATScreen.tsx
│   │   │   ├── KBSearchScreen.tsx
│   │   │   └── VideoCallScreen.tsx
│   │   └── ui/
│   │       ├── MessageBubble.tsx
│   │       ├── TypingIndicator.tsx
│   │       └── ConnectionStatus.tsx
│   ├── store/
│   │   └── chatStore.ts    ← Zustand store
│   └── index.tsx           ← Entry point + shadow DOM mount
```

---

## CSS del Widget

El widget usa CSS-in-JS o CSS Modules (verificar el setup actual de Vite). Si usa CSS modules:
- `widget.module.css` — estilos base
- Variables CSS encapsuladas en `:host` (shadow DOM) o `.hd-widget` namespace
- Mobile-first: el widget en mobile ocupa pantalla completa (`100vw × 100vh`)
