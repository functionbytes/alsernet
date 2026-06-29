# Paquetes para Claude Design

> Cada archivo `.md` en este directorio es un **paquete completo** para pasar a Claude Design.
> Claude Design debe devolver **solo CSS + HTML estático** — sin React, sin Livewire, sin Alpine.

## Instrucción base para Claude Design

Pegar al inicio de cada paquete:

> Eres Claude Design, especialista en UI/UX. Recibirás un CSS esqueleto y una especificación de diseño.
> Tu tarea: devolver HTML estático + CSS refinado, sin React ni ningún framework JS.
> Stack permitido: HTML5 + CSS3 + Bootstrap 5.3 (CDN). El CSS debe seguir la convención de prefijos BEM
> establecida en el esqueleto. El color primario es `#90bb13`. Usa Font Awesome 6 Free exclusivamente.

## Módulos y sus paquetes

| Módulo | Paquete | CSS esqueleto | Screenshot |
|--------|---------|---------------|------------|
| Helpdesk (core) | `pkg-helpdesk.md` | `conversations.css` + `conversations-identity.css` | `helpdesk-conversations.png` ✅ |
| HelpdeskTickets | `pkg-tickets.md` | `tickets.css` (existente) | `helpdesk-tickets.png` ✅ |
| HelpdeskAgents | `pkg-agents.md` | `agents.css` (nuevo) | `helpdesk-agents.png` ✅ |
| HelpdeskCampaigns | `pkg-campaigns.md` | `campaigns.css` (nuevo) | `helpdesk-campaigns.png` ✅ |
| HelpdeskHelpcenter | `pkg-helpcenter.md` | `helpcenter.css` (nuevo) | `helpdesk-helpcenter.png` ✅ |
| HelpdeskLivechat | `pkg-livechat.md` | Ver instrucción especial en el paquete | `helpdesk-livechat.png` ✅ |
| HelpdeskSocial | `pkg-social.md` | `social.css` (nuevo) | ⚠️ Módulo deshabilitado en este entorno — sin screenshot |
| HelpdeskTranslate | `pkg-translate.md` | `translate-panel.css` (existente) | `helpdesk-translate.png` ✅ |

## Capturas disponibles

Todas en: `.claude/audits/helpdesk-redesign/screenshots/`

- `helpdesk-conversations.png` — Inbox principal three-panel (conversaciones activas, thread, contacto)
- `helpdesk-tickets.png` — Lista de tickets con tabla y columnas SLA/prioridad/asignado
- `helpdesk-agents.png` — Tabla de agentes con disponibilidad y estadísticas
- `helpdesk-campaigns.png` — Dashboard campañas con KPIs y tabla de listado
- `helpdesk-helpcenter.png` — Gestión de KB (categorías, secciones, artículos jerárquicos)
- `helpdesk-livechat.png` — Settings de LiveChat con preview del widget en tiempo real
- `helpdesk-translate.png` — Settings de traducción (proveedor LibreTranslate + opciones auto-translate)
