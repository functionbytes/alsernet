---
name: reference-helpdesk-dead-react-islands
description: modules/Helpdesk/resources/js/app.tsx (React islands) está registrado en Vite pero nunca se carga ni se monta en ninguna vista
metadata:
  type: reference
---

`modules/Helpdesk/resources/js/app.tsx` monta componentes React buscando elementos `[data-react-component]` en el DOM (ver comentario del propio archivo). Está declarado como entry point en `vite.config.js` ("React islands entry point") junto con `components/ai-agent/*.tsx`, `components/campaigns/CampaignEditor.tsx`, `components/conversations/{ConversationDetail,MessageComposer,MessageList}.tsx` y sus hooks.

Verificado (2026-07-06): ninguna vista de `modules/Helpdesk/resources/views/**/*.blade.php` incluye `@vite` para ese entry ni usa el atributo `data-react-component`. Ese atributo solo aparece en `modules/Campaign/views/helpers/react-mount.blade.php`, un módulo distinto.

**Why importa:** el proyecto prohíbe React salvo la excepción documentada de HelpdeskLivechat (widget cliente). Este bundle no es esa excepción — es código muerto/abandonado que además contradice esa regla si algún día se activa sin revisión.

**How to apply:** antes de recomendar borrar el bundle, re-verificar con `grep -rn "app.tsx\|data-react-component" modules/Helpdesk` por si se añadió un mount point desde la última revisión. Si sigue sin uso, es candidato a eliminación o hay que preguntar al usuario si hay un plan de reactivarlo.
