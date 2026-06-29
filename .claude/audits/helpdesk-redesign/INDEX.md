# Helpdesk Suite — Guía de Rediseño UI

> Índice principal de todos los prompts de redesign generados para Claude Design.  
> Fecha de generación: 2026-05-07  
> Stack base: Bootstrap 5.3 + jQuery + Font Awesome 6 + color `#90bb13`

---

## Cómo usar estos archivos

1. **SIEMPRE** leer `SHARED-DESIGN-SYSTEM.md` primero — contiene tokens de diseño, componentes y convenciones compartidas
2. Luego abrir el archivo del módulo específico que se va a rediseñar
3. El archivo del módulo indica los archivos Blade exactos a modificar y el CSS a crear

---

## Archivos en este directorio

| Archivo | Descripción | Prioridad |
|---------|-------------|-----------|
| `SHARED-DESIGN-SYSTEM.md` | Tokens de diseño, componentes y convenciones globales | **Leer primero siempre** |
| `Helpdesk.md` | Core: inbox principal, 32 modales, settings, dashboard | Alta |
| `HelpdeskTickets.md` | Kanban de tickets, detalle modal, recurrentes, plantillas | Alta |
| `HelpdeskAgents.md` | Equipo, flujos IA (array+Sortable), turnos, vacaciones | Media |
| `HelpdeskCampaigns.md` | Campañas popup/banner, editor A/B, aprobación, analytics | Media |
| `HelpdeskHelpcenter.md` | Portal público KB, editor artículos, multilingüe, analytics | Alta |
| `HelpdeskLivechat.md` | Admin Blade + Widget React (dos UIs separadas) | Alta |
| `HelpdeskSocial.md` | Inbox social, WA 24h timer, sentimiento, competidores | Media |
| `HelpdeskTranslate.md` | Settings proveedor, panel composer, tab historial | Baja |

---

## Resumen por Módulo

### Helpdesk (Core)
**182 vistas** · Inbox omnicanal con three-panel layout, 32 modales de acciones, settings extensos y dashboard.
- Prioridad: rediseñar el inbox principal y sus componentes (list item, thread, composer)
- Los 32 modales deben seguir el patrón estándar: `modal-dialog-centered` + footer stacked
- Settings: 25+ subcategorías, renovar workflows con stepper visual

### HelpdeskTickets
**71 vistas** · Sistema de tickets con kanban, SLA, tickets recurrentes, plantillas.
- Prioridad: kanban con cards SLA visuale y modal de detalle (evitar salir del listado)
- `partials-v2/` está vacío — aquí van los nuevos componentes
- Tabla principal → dxDataGrid con bulk actions

### HelpdeskAgents
**12 vistas** · Agentes humanos + flujos IA (array+Sortable) + shifts + vacaciones.
- Flujos IA: el constructor usa arrays PHP — NO React Flow. Hacerlo visual con diagrama vertical accordion
- Shifts → grid semanal tipo calendario con heatmap de cobertura

### HelpdeskCampaigns
**11 vistas** · Popup/banner/slide-in/full-screen, A/B testing, aprobación, widget embebible.
- Editor con split preview en tiempo real (60%/40%)
- Aprobación con stepper y cola de revisión
- Modal selector de tipo al crear nueva campaña

### HelpdeskHelpcenter
**17 vistas** · Knowledge base con portal público, jerarquía Cat→Sec→Art, 7 idiomas, embeddings.
- Portal público: hero search + card grid categorías + TOC sticky en artículo
- Widget feedback: binary thumbs + follow-up panel condicional
- Admin: tree view drag-and-drop de categorías, editor split con panel SEO

### HelpdeskLivechat
**8 vistas admin + 35 archivos React** · Chat en vivo con WebRTC, session replay, widget embebible.
- **Dos UIs diferenciadas**: admin Blade y widget React (tratarlos por separado)
- Widget: 6 pantallas (home, chat, pre-chat, CSAT, KB, videollamada)
- Zustand store como única fuente de verdad del estado del widget

### HelpdeskSocial
**25 vistas** · Meta/WhatsApp inbox, sentimiento IA, aprobación, competidores.
- Three-panel inbox con borde izquierdo por sentimiento
- WhatsApp: timer de 24h con countdown y modal selector de plantillas
- Competidores: SOV donut + tabla comparativa de métricas

### HelpdeskTranslate
**3 vistas** · DeepL + LibreTranslate, caché BD, settings, partial en composer.
- Settings: radio cards de proveedor + health check + progress bar de cuota
- Partial del composer: toggle switch + preview de traducción + rating
- Tab del composer: historial de traducciones + "Traducir todo" banner

---

## Componentes Compartidos a Crear (CSS Global)

Crear `modules/Helpdesk/public/css/helpdesk-suite.css` con:
- Variables CSS `--hd-*` (colores, dimensiones, border-radius)
- `.hd-kpi-card` — KPI widget
- `.hd-conv-item` — item de lista de conversaciones
- `.hd-filter-chips` — barra de chips de filtro
- `.hd-bulk-bar` — barra de acciones masivas sticky
- `.hd-stepper` — stepper de aprobación horizontal
- `.hd-badge-sentiment-*` — badges de sentimiento
- `.hd-empty-state` — estado vacío estándar
- `.hd-bubble` — burbujas de mensaje (incoming/outgoing)
- `.hd-avatar` — avatar con iniciales y colores

Publicar a `public/modules/helpdesk/css/helpdesk-suite.css`.

---

## Principios de Diseño Aplicados

1. **Información densa pero legible**: los agentes procesan mucho volumen — la información crítica (SLA, sentimiento, sin asignar) debe ser visible sin hover
2. **Acciones de un click**: las acciones más frecuentes (asignar, cerrar, responder) accesibles directamente, sin submenús
3. **Feedback inmediato**: toasts, loaders en botones, actualizaciones en tiempo real via WebSocket
4. **Coherencia visual**: mismo patrón de dropdown acciones, mismo patrón de modal, mismos badges de estado en todos los módulos
5. **Progressive disclosure**: filtros avanzados ocultos por defecto, detalles completos en panels secundarios
6. **Mobile-friendly para contextos de emergencia**: aunque es una app de escritorio, los agentes en guardia pueden necesitar intervenir desde mobile

---

## Convenciones de Nomenclatura CSS

Todos los estilos nuevos usan el prefijo `.hd-` para evitar colisiones:
```
.hd-[componente]                  — componente raíz
.hd-[componente]__[elemento]      — elemento del componente (BEM)
.hd-[componente]--[modificador]   — variante del componente (BEM)
```

Ejemplos:
- `.hd-conv-item` `.hd-conv-item__avatar` `.hd-conv-item--unread`
- `.hd-bubble` `.hd-bubble__text` `.hd-bubble--outgoing`
- `.hd-badge-sentiment-pos` (utility, no BEM)

---

## Herramientas de Referencia Consultadas

Los prompts de este directorio están informados por investigación de UI de:
- **Inbox/Conversaciones**: Intercom, Zendesk, Front, HubSpot
- **Tickets**: Linear, Jira Service Management, Freshservice
- **Agentes IA**: Intercom Fin, Botpress, Voiceflow, Zendesk AI
- **Campañas proactivas**: Intercom Outbound, Customer.io, Wisepops, OptinMonster
- **Knowledge Base**: Zendesk Guide, Intercom Help Center, Document360, GitBook, HelpScout
- **Livechat widget**: Crisp, Tidio, Drift, Intercom Messenger, Tawk.to
- **Social media**: Sprout Social, Hootsuite, Agorapulse, Buffer, Brandwatch
- **Traducción**: Intercom AI Translate, Freshworks Freddy, Lokalise, DeepL API
