# Paquete Claude Design — HelpdeskLivechat

## IMPORTANTE: Este módulo tiene DOS UIs separadas

Tratar como dos proyectos independientes con entregas separadas.

---

## Parte A — Admin (Blade)

### Archivos a incluir

```
modules/Helpdesk/resources/css/conversations.css            ← referencia de estilos inbox
modules/Helpdesk/resources/css/conversations-identity.css   ← tokens CSS
.claude/audits/helpdesk-redesign/HelpdeskLivechat.md        ← especificación (sección "Part A")
.claude/audits/helpdesk-redesign/SHARED-DESIGN-SYSTEM.md
```

### Qué debe entregar Claude Design (Parte A)

1. **`livechat-admin.html`** — panel admin del livechat
   - `.bv-*` prefijos (mismo que inbox principal) — el livechat admin sigue el mismo sistema visual
   - Vistas: chats en tiempo real / configuración del widget / analytics de sesiones
   - Panel de chat activo: lista de visitantes online + thread en tiempo real

2. **`livechat-settings.html`** — configuración del widget
   - Personalización: color, posición, mensaje bienvenida, horarios
   - Preview live del widget en la misma página (iframe simulado CSS)
   - Toggle business hours con horarios por día

---

## Parte B — Widget React (archivo separado)

### Archivos a incluir

```
modules/Chat/resources/assets/js/widget/    ← código fuente React existente (referencia)
.claude/audits/helpdesk-redesign/HelpdeskLivechat.md  ← sección "Part B: Widget React"
```

### Qué debe entregar Claude Design (Parte B)

> ⚠️ Esta es la ÚNICA excepción React en el proyecto. El widget existe en React 19 + Zustand.
> Claude Design debe entregar maquetas HTML estáticas de cada pantalla del widget.
> Los desarrolladores adaptarán las maquetas al componente React existente.

1. **`widget-home.html`** — pantalla home del widget
   - Header con logo/nombre empresa + botón cerrar
   - Greeting message
   - Cards de acciones: Chat / Buscar en KB / Dejar mensaje
   - Artículos destacados de KB

2. **`widget-chat.html`** — pantalla de chat activo
   - Header: agente asignado (avatar + nombre + estado online)
   - Mensajes: burbujas in/out, timestamps, typing indicator
   - Composer: textarea + emoji + adjuntos
   - Banner de CSAT al cierre de sesión

3. **`widget-prechat.html`** — formulario pre-chat
   - Campos: nombre, email, tema/departamento
   - Mensaje de espera estimada

4. **`widget-csat.html`** — pantalla CSAT post-chat
   - Rating estrellas 1-5
   - Comentario opcional
   - Botón "Enviar valoración"

5. **`widget-kb.html`** — búsqueda KB dentro del widget
   - Buscador mini + resultados
   - Artículo expandido inline

6. **`widget.css`** — CSS completo del widget
   - Scope: `.hcl-widget` (prefijo `--hcl-*` / `.hcl-*`)
   - Debe funcionar aislado del resto de la app (no depende de Bootstrap)
   - El widget se inyecta en cualquier página web externa

## Restricciones

- Parte A: NO React, usar convención `.bv-*` del inbox
- Parte B: HTML estático para maquetas — los devs adaptan a React
- El widget CSS debe ser completamente autónomo (no importar Bootstrap externo)
- Dimensiones del widget: `min(380px, 100vw)` × `min(640px, 100vh)` cuando expandido
- Posición: `position: fixed; bottom: 20px; right: 20px`
- El widget debe tener estado colapsado (FAB 56px) y expandido (modal-like)

## Componentes críticos del widget

```html
<!-- Widget FAB (colapsado) -->
<button class="hcl-fab">
  <i class="fas fa-comment-dots hcl-fab__icon hcl-fab__icon--chat"></i>
  <i class="fas fa-xmark hcl-fab__icon hcl-fab__icon--close"></i>
  <span class="hcl-fab__badge">2</span>
</button>

<!-- Widget expandido - Home -->
<div class="hcl-widget hcl-widget--open">
  <div class="hcl-header">
    <div class="hcl-header__brand">
      <img src="logo.png" class="hcl-header__logo" alt="">
      <div>
        <div class="hcl-header__name">Soporte Alsernet</div>
        <div class="hcl-header__status"><span class="hcl-dot hcl-dot--online"></span>En línea</div>
      </div>
    </div>
    <button class="hcl-header__close"><i class="fas fa-xmark"></i></button>
  </div>
  <div class="hcl-body">
    <div class="hcl-greeting">
      <h2 class="hcl-greeting__title">¡Hola! 👋</h2>
      <p class="hcl-greeting__sub">¿En qué podemos ayudarte hoy?</p>
    </div>
    <div class="hcl-actions">
      <button class="hcl-action-card">
        <i class="fas fa-comment-lines"></i>
        <span>Chatear con soporte</span>
        <i class="fas fa-chevron-right ms-auto"></i>
      </button>
      <button class="hcl-action-card">
        <i class="fas fa-magnifying-glass"></i>
        <span>Buscar en ayuda</span>
        <i class="fas fa-chevron-right ms-auto"></i>
      </button>
    </div>
  </div>
</div>
```
