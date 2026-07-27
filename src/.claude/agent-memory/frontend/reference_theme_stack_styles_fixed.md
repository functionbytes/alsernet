---
name: reference-theme-stack-styles-fixed
description: "@stack('styles') SI existe en el layout admin desde el commit b6718e21 -- la vieja advertencia de stack fantasma ya no aplica"
metadata:
  type: reference
---

`modules/Theme/resources/views/layouts/theme.blade.php` tiene `@stack('styles')` (línea ~82, junto a `@stack('css')`) desde el commit `b6718e21 fix(theme): añade @stack('styles') al head del layout admin`. Verificado con `git log -p` sobre ese archivo (2026-07-06).

Por tanto `@push('styles')` funciona correctamente en cualquier vista que extienda `layouts.theme` (resuelto vía `View::addLocation` a `modules/Theme/resources/views`), incluidas vistas incluidas indirectamente con `@include` dentro de una cadena `@extends` (Blade evalúa el hijo completo antes de renderizar el layout, así que el orden de aparición en el archivo no importa).

**Superseded:** la nota anterior "CSS de partials/modals/*.blade.php va en conversations.css (no @push styles, que es fantasma)" quedó desactualizada por ese fix. Sigue habiendo un comentario obsoleto al respecto en `modules/Helpdesk/resources/views/helpdesk/inbox/partials/modals/attach-file.blade.php:51-52` que ya no es cierto (usa `<style>` inline en vez de `@push('styles')` por una razón que ya no existe).

**How to apply:** si se audita o refactoriza CSS de modales del inbox, ya no hace falta evitar `@push('styles')`; se puede centralizar ahí en vez de `<style>` inline por archivo. Verificar igual con `git log` si el layout cambia de nuevo.
