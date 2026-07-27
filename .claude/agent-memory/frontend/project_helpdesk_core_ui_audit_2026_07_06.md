---
name: project-helpdesk-core-ui-audit-2026-07-06
description: Auditoría de solo-lectura de la capa de presentación de modules/Helpdesk (a11y, convenciones, performance, código muerto) realizada 2026-07-06
metadata:
  type: project
---

Auditoría de solo-lectura de `modules/Helpdesk/resources/views/` (238 blades), `resources/css/`, `public/` — foco en inbox, panel derecho, modales y settings — completada 2026-07-06.

**Why:** solicitada para priorizar deuda de accesibilidad/convenciones/performance antes de seguir añadiendo features al inbox.

**Hallazgos de mayor impacto (verificados con grep, no especulativos):**
- ~~El sistema `bv-modal` no usa `role="dialog"`/`aria-modal`/`aria-label` en cierre~~ — **CORREGIDO, este hallazgo era un falso positivo por metodología**: el grep solo miró los `.blade.php` estáticos, pero `public/vendor/helpdesk/conversations.js` ya inyectaba estos atributos en runtime desde el 2026-06-30 (commit `b2b7b2ec`, función `applyModalA11y()`, ~línea 222) — ANTES de esta auditoría (2026-07-06). Ver [[reference_helpdesk_dead_react_islands]] para el mismo tipo de gotcha (grep de blade no ve lo que inyecta JS). El 2026-07-06 se centralizó también el `aria-label="Cerrar"` fallback (antes solo 2/72 partials lo tenían hardcodeado) y se aplicó `applyModalA11y()` a los 3 modales ad-hoc que no pasan por `openModal()` (`openMessageForwardModal`, `openMessageInfoModal`, `openForwardModal` de adjuntos). Focus-trap, restauración de foco y cierre con Escape también ya existían desde el mismo commit. Ver [[reference_helpdesk_bv_modal_a11y_centralized]].
- 0 de 330 `<th>` en todo el módulo usan `scope`. (pendiente, sin tocar en esta sesión)
- `resources/js/app.tsx` ("React islands") + componentes en `components/ai-agent/*`, `components/campaigns/CampaignEditor.tsx`, `components/conversations/*` están registrados en `vite.config.js` pero NUNCA se cargan (`@vite`) ni se monta el mecanismo `[data-react-component]` en ninguna vista de Helpdesk (ese atributo solo existe en `modules/Campaign/views/helpers/react-mount.blade.php`, otro módulo). Ver [[reference_helpdesk_dead_react_islands]].
- ~~`thread.blade.php` no tiene `aria-live`/`role="log"`~~ — **CORREGIDO 2026-07-06**: `.bv-th-inner` (nodo real donde JS hace `.append()`/`.replaceWith()` de burbujas, confirmado en `conversations.js`) ahora tiene `role="log" aria-live="polite" aria-relevant="additions"`.
- Títulos de sección en Title Case (violan regla "solo primera palabra"): `helpdesk/customers/show.blade.php:153,162,178`, `customers/edit.blade.php:112,252,313`, `customers/create.blade.php:167`, `settings/attributes/edit.blade.php:14,24`. (pendiente)
- Directorios completamente vacíos (0 archivos) bajo `resources/views/managers/*` (inbox, conversations, customers/partials, helpcenter/*, agents, leaderboard, dashboard, reports, helpdesk) — resto muerto de una reorganización anterior. (pendiente)

**Verificado como YA CORRECTO (evitar falsos positivos en próximas auditorías):**
- 0 iconos Tabler (`ti ti-*`), 0 `select2` con `theme: 'bootstrap-5'`.
- Los 73 usos de `modal-dialog` (Bootstrap real) siempre incluyen `modal-dialog-centered`.
- Tablas de `business-hours`, `whatsapp-templates`, `email` en settings no necesitan patrón dropdown — son tablas de configuración sin acciones por fila, no listados CRUD.
- A11y de `bv-modal` (role/aria-modal/aria-labelledby/aria-label/focus-trap/Escape) — ver corrección arriba, no volver a auditar como pendiente.

**How to apply:** antes de dar por "faltante" algo de accesibilidad/JS en el inbox de Helpdesk, grep también `public/vendor/helpdesk/conversations.js` (servido directo, no se genera por build) — no solo los `.blade.php`. Quedan reales por atacar: `scope` en `<th>`, Title Case en customers/settings, React islands muertos, directorios vacíos en `managers/*`.
