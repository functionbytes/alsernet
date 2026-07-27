---
name: reference-helpdesk-bv-modal-a11y-centralized
description: Punto central de accesibilidad del sistema bv-modal del inbox Helpdesk, en conversations.js — dónde tocar para afectar los 72+ modales sin editarlos uno a uno
metadata:
  type: reference
---

`public/vendor/helpdesk/conversations.js` (servido directo, sin build — ver `[[reference_conversations_css_publish]]`/gotcha del stack de estilos) tiene un único punto centralizado de a11y para el widget custom `.bv-modal` que cubre los ~72 partials de `modules/Helpdesk/resources/views/helpdesk/inbox/partials/modals/*.blade.php` sin tocarlos individualmente:

- `applyModalA11y($modal, name)` (~línea 222): pone `role="dialog"` + `aria-modal="true"` en `.bv-modal-dialog` (o en `.bv-modal` si no hay dialog interno), `role="presentation"` en el overlay exterior, genera `id="bv-modal-title-{name}"` en `.bv-modal-title` si no lo tiene y lo referencia con `aria-labelledby`. Desde 2026-07-06 también recorre `.bv-modal-close` y pone `aria-label="Cerrar"` en los que no lo traigan ya en su Blade (solo `auto-assign.blade.php` y `away-mode.blade.php` lo hardcodean con i18n; el resto lo hereda de aquí).
- `openModal(name)` (~línea 239) llama a `applyModalA11y`, guarda `lastFocusedBeforeModal = document.activeElement` y mueve el foco al diálogo (focus-trap con Tab ya implementado en el `keydown` de ~línea 274-292).
- `closeModal($modal)` (~línea 253) restaura el foco a `lastFocusedBeforeModal` cuando no queda ningún `.bv-modal.on`.
- Escape global (~línea 316-323): cierra el último `.bv-modal.on` vía `closeModal()`. Ya existía, no hace falta re-implementarlo.

**3 modales ad-hoc que NO pasan por `openModal()`** (construyen su HTML a mano con `.bv-modal on` ya puesto) — hay que llamarles `applyModalA11y()` explícitamente si se tocan de nuevo:
- `openMessageForwardModal()` (~línea 2836, id `bv-msg-forward-modal`)
- `openMessageInfoModal()` (~línea 2925, id `bv-msg-info-modal`)
- `openForwardModal()` de adjuntos (~línea 4510, id `bv-forward-modal`, único de los tres que sí usa `[data-bv-close]` y por tanto sí es cerrado por el handler centralizado — por eso también se le puso `lastFocusedBeforeModal = document.activeElement` antes de crearlo).

Los otros dos (`msg-forward`/`msg-info`) cierran con su propio `$modal.remove()` en vez de `closeModal()`, así que no se les tocó el foco-restore para no cambiar su comportamiento existente — deliberado, no un olvido.

**How to apply:** si se añade un modal `bv-modal` nuevo (partial o ad-hoc), o se toca alguno existente, verificar primero si pasa por `openModal()`/`data-bv-modal-name` (gratis, no hace falta nada) o si construye su propio HTML (hay que llamar `applyModalA11y($modal, 'nombre-unico')` a mano, como los 3 de arriba).
