# Mejoras de velocidad y UX/UI — Inbox Helpdesk (2026-06-30)

## Intro

Este roadmap consolida el análisis por 4 ejes (velocidad backend, velocidad frontend, experiencia de usuario y UI/accesibilidad/responsive) del inbox de 3 columnas del módulo Helpdesk (`index.blade.php` 791L, `right-panel.blade.php` 2618L, `thread.blade.php` 947L, `conversations.css` ~14.080L, `conversations.js` ~6.662L).

El hallazgo dominante es transversal a tres ejes: **abrir o cambiar de conversación provoca una recarga COMPLETA de página** (`conversations.js:709` `window.location.href` + `ConversationsController@show` que devuelve un 302 al index). Cada clic re-ejecuta el `index()` entero (subquery de agentes, 13 contadores, lista de 50 con `toInboxArray`), re-renderiza el right-panel pesado (incluido inline y con queries + APIs externas síncronas), carga TODOS los mensajes del hilo sin límite y vuelca ~84 modales, además de re-descargar 399KB de CSS y 328KB de JS sin minificar. El resultado: la UI se siente congelada 300-1500ms en cada triaje, sin spinner intermedio.

La estrategia recomendada: **primero quick wins de alto ROI** (limitar el hilo, sacar el sync del render, minificar/combinar assets, feedback de carga inmediato, scroll inteligente, y los 4 fixes de CSS/a11y) que reducen el coste por carga; **después los cambios grandes** (apertura AJAX parcial tipo SPA y lazy-load del right-panel/modales) que eliminan la recarga de raíz.

---

## Top 10 por ROI

| Mejora | Eje | Impacto | Esfuerzo | ROI | Archivo |
|---|---|---|---|---|---|
| perf-04 — Limitar items del hilo a ~50 (paginar el resto) | Backend | high | s | high | `app/Http/Controllers/Managers/ConversationsController.php:332` |
| perf-03 — Sacar `CustomerCommerceSyncService->sync()` del render (job/TTL) | Backend | high | m | high | `resources/views/helpdesk/inbox/partials/right-panel.blade.php:57-64` |
| FE-02 — Minificar y combinar CSS (399KB) + JS (328KB) | Frontend | high | s | high | `public/vendor/helpdesk/conversations.css` / `conversations.js` |
| FE-01 — Apertura de conversación por AJAX parcial (elimina 302 + index completo) | Frontend | high | m | high | `public/vendor/helpdesk/conversations.js:709` · `ConversationsController.php:849` |
| UX-01 — Feedback de carga inmediato al abrir/cambiar de conversación | UX | high | s | high | `public/vendor/helpdesk/conversations.js:708-710` |
| UX-03 — Scroll inteligente: no arrancar la vista al entrar mensaje por WS | UX | high | s | high | `public/vendor/helpdesk/conversations.js:1317` |
| UX-02 — Envío de mensaje optimista (burbuja `pending` + textarea activo) | UX | high | m | high | `public/vendor/helpdesk/conversations.js:1000-1037` |
| UI-01 — Definir token `--bv-primary` una sola vez (unificar 3 "primarios") | UI/a11y | high | s | high | `modules/Helpdesk/resources/css/conversations.css:108/1548/11135` |
| UI-02 — Corregir contraste WCAG del verde `#90bb13` + texto blanco | UI/a11y | high | s | high | `modules/Helpdesk/resources/css/conversations.css:1547-1551` |
| UI-03 — Foco de teclado visible (`:focus-visible` global en `.conversations`) | UI/a11y | high | s | high | `modules/Helpdesk/resources/css/conversations.css` (35× `outline:none`) |

> Hay >10 ítems con impacto/ROI alto; los excluidos del Top 10 (FE-04, FE-05, FE-06, UI-04, UI-05, perf-05) están en sus secciones y deberían entrar en la 2ª tanda.

---

## Quick wins (esfuerzo s/m, alto ROI)

- **perf-04 (s)**: limitar `selectedConversation.items` a los últimos ~50 (subconsulta `latest()->limit(50)`, reinvertir en la vista) y paginar "cargar anteriores" por AJAX.
- **perf-05 (s)**: reutilizar/cachear los counts de `listJson` (`helpdesk:inbox:counters:{userId}`, 30-60s) en vez de recalcular ~5 COUNT en cada poll.
- **perf-03 (m)**: sacar `CustomerCommerceSyncService->sync()` del Blade → job en cola al abrir/recibir mensaje, o `Cache::add` con TTL 1h por `customer_id`.
- **FE-02 (s)**: generar `*.min.css`/`*.min.js` (cssnano/esbuild/terser) y combinar las 4 CSS (identity+conversations+commerce+dark) en 1 request render-blocking; verificar gzip/brotli.
- **FE-06 (s)**: usar el polling de notificaciones de 15s solo como fallback cuando Echo no esté conectado (o subir a 60s) para no duplicar el realtime.
- **FE-01 (m)**: convertir el clic en `.bv-conv` en `$.get` del par hilo+right-panel y `replaceWith` de columnas con `history.pushState`, evitando re-descargar todo.
- **FE-04 (m)**: no volcar los ~84 modales al index; cargarlos bajo demanda (`/modal/{name}`) y eliminar sus `<script>` inline.
- **FE-05 (m)**: limitar el thread a ~30 mensajes y cargar antiguos por scroll infinito hacia arriba.
- **UX-01 (s)**: pintar skeleton/overlay (o barra tipo NProgress) en `.bv-thread`/`.bv-right` ANTES de navegar; aplica también a `navigateConv` (j/k).
- **UX-03 (s)**: antes del auto-scroll, comprobar si el agente está cerca del fondo (<120px); si no, mostrar píldora "Nuevo mensaje ↓". Auto-scroll solo para mensajes propios.
- **UX-02 (m)**: UI optimista en el envío: burbuja `bv-bubble--pending` al instante, textarea vacío pero habilitado/enfocado, reconciliar en `.done`, "Reintentar" en `.fail`.
- **UI-01 (s)**: definir `--bv-primary: #90bb13;` en `:root` de `conversations-identity.css` y reemplazar hardcodeos `#90bb13`/fallbacks rojos `#b91c1c` por `var(--bv-primary)`.
- **UI-02 (s)**: usar verde oscuro accesible (`--bv-primary-strong`, p.ej. `#5e7a0d`) para botones con texto blanco; reservar `#90bb13` para acentos/fondos.
- **UI-03 (s)**: añadir `.conversations :focus-visible{ outline:2px solid var(--bv-primary); outline-offset:2px }` (un solo bloque) para restaurar el foco de teclado.
- **UI-04 (m)**: añadir `role="dialog"`/`aria-modal`/`aria-labelledby` al partial base de modal y focus-trap + restauración de foco en `openModal()`/`closeModal()`.
- **UI-05 (s)**: marcar el indicador "escribiendo…" con `aria-live="polite" role="status"` y añadir live-region oculto para anunciar mensajes entrantes.

---

## Cambios grandes (esfuerzo l)

- **perf-01 / FE-01 / UX-08 — Apertura AJAX parcial (tipo SPA)** *(valor: máximo en velocidad percibida)*: nuevo endpoint `conversations/{id}/pane` que devuelva solo el HTML de `thread.blade` + `right-panel`; inyectar en `.bv-center`/`.bv-right` con `history.pushState(?selected=)` y re-bind de listeners Echo por conversación. Convierte j/k y cada apertura en sub-100ms y evita recomputar lista/sidebar/agents. **Alcance**: backend (endpoint) + frontend (handler `.bv-conv`, re-bind). Hacerlo después de los quick wins UX-01/04/perf-04 que abaratan cada carga.
- **perf-02 / FE-03 — Lazy-load del right-panel y sus pestañas** *(valor: elimina del request principal ~8 queries + 2 APIs externas de PrestaShop)*: renderizar solo el esqueleto + pestaña "general"; cargar cada pestaña (pedidos, ficheros, anteriores, tecnología, documentos, customer-360, ERP/PS) vía AJAX al abrirla. **Alcance**: partir `right-panel.blade.php` en fragmentos + endpoints por pestaña; mover ahí las queries de perf-09 (historial "Anteriores" y galería "Ficheros").
- **UI-10 — Unificar los dos sistemas de modal** *(valor: foco/ARIA/ESC centralizados y un único estilo de botón primario)*: migrar el modal "Respuesta rápida" (`hd-overlay`/`hd-modal`) al sistema estándar `bv-modal`, eliminando CSS duplicado y la lógica inline `openCannedModal`. ROI menor; abordar tras UI-04.

---

## Eje 1 — Velocidad backend (queries, caché, payload)

La lista del inbox está bien optimizada (eager-loading, `withCount`, índices compuestos de `2026_06_24`). El cuello de botella real es el **full-reload + right-panel pesado** que ejecuta queries y APIs externas síncronas en cada clic.

- **perf-01 (high/l)** — Clic de conversación recarga toda la página. → Endpoint `conversations/{id}/pane` con thread+right-panel, inyección AJAX y `pushState`; evita recomputar agents/contadores/lista en cada apertura.
- **perf-02 (high/l)** — Right-panel ejecuta trabajo síncrono pesado (incl. APIs externas) en cada render (`sync`, `getCustomerTickets`, eventos, `WidgetSession`, `pageViews(50)`, `Orders(20)`, items de 60 conversaciones, `PrestashopContextService->getCustomerContext`, anteriores(20)). → Renderizar solo esqueleto + pestaña activa; resto on-demand por AJAX.
- **perf-03 (high/m)** — `CustomerCommerceSyncService->sync()` corre dentro del Blade (`right-panel.blade.php:57-64`). → Job en cola (`SyncCustomerCommerceJob::dispatch`) o guard `Cache::add` TTL 1h por `customer_id`.
- **perf-04 (high/s)** — `selectedConversation` carga TODOS los items del hilo (`ConversationsController.php:332`). → `latest()->limit(50)` reordenado + "cargar anteriores" por AJAX.
- **perf-05 (medium/s)** — `listJson` recalcula ~5 COUNT sin caché en cada poll (`:510-526`). → Reutilizar bloque cacheado `helpdesk:inbox:counters:{userId}` (30-60s).
- **perf-06 (medium/s)** — Subquery correlacionada `open_count` por agente sin caché (`:361-369`). → `cache()->remember` 30-60s, o un único `GROUP BY assignee_id` mapeado.
- **perf-07 (medium/m)** — `withoutActiveBot` usa `whereJsonDoesntContain` no indexable (`Conversation.php:316`, usado en lista + 13 contadores). → Columna booleana indexada `handled_by_bot` (o generada STORED) con backfill y cambiar los scopes a `where(...)`.
- **perf-08 (medium/s)** — Sidebar: 13 COUNT con caché bloqueante sin SWR (`:265-299`). → `Cache::flexible()` (Laravel 12) para evitar stampede + colapsar los 5 counts por canal en `selectRaw('channel, COUNT(*)')->groupBy('channel')`.
- **perf-09 (medium/m)** — "Anteriores" y galería "Ficheros" (cruza TODAS las conversaciones del cliente) se cargan aunque la pestaña esté oculta (`:1133`, `:202-209`). → Mover a los endpoints por pestaña de perf-02.
- **perf-10 (low/s)** — `customer.externalIds` se carga lazy y duplicada (`:14-15` y `:60`). → Eager-load `customer.externalIds` en `selectedConversation` (`:331-335`).

---

## Eje 2 — Velocidad frontend (CSS/JS/bundle/render)

Monolito server-rendered de altísimo peso que se re-renderiza ENTERO en cada acción clave, sin Vite ni pipeline de assets.

- **FE-01 (high/m)** — Abrir conversación = recarga entera (302 + index completo). → AJAX parcial de thread+right-panel con `pushState`.
- **FE-02 (high/s)** — CSS (399KB) y JS (328KB) sin minificar ni bundling (`index.blade.php:30,340` vía `asset()`+filemtime). → Minificar y combinar las 4 CSS en 1; verificar gzip/brotli (~40-60% menos bytes).
- **FE-03 (high/l)** — Right-panel renderiza TODAS las pestañas server-side y las oculta por CSS (`bv-tab-hidden`). → Lazy-load por pestaña (endpoint por pestaña), solo "general" upfront.
- **FE-04 (high/m)** — ~84 modales (~12.765 líneas) volcados al DOM en cada carga (`modals.blade.php`). → Cargar bajo demanda (`/modal/{name}`), eliminar `<script>` inline.
- **FE-05 (high/m)** — Thread carga todos los mensajes sin paginar (`ConversationsController.php:~336`, `thread.blade.php:180`). → `latest()->limit(30)` + scroll infinito hacia arriba.
- **FE-06 (medium/s)** — Polling de notificaciones cada 15s redundante con Reverb/Echo (`conversations.js:6658`). → Solo fallback si Echo desconectado, o intervalo 60s.
- **FE-07 (medium/m)** — 86 bloques `<script>` inline dispersos. → Consolidar en `conversations.js` con event delegation; permite cachear el JS.
- **FE-08 (medium/s)** — Imágenes de adjuntos/link-preview sin alto explícito → CLS (`thread.blade.php:343,284`). → `height`/`aspect-ratio` en `.bv-lp-img` y adjuntos.
- **FE-09 (low/s)** — Refresh de pestaña "Tecnología" descarga el HTML completo del inbox (`right-panel.blade.php:~1739`). → Endpoint parcial que devuelva solo ese fragmento (alineado con FE-03).
- **FE-10 (medium/s)** — 4 hojas CSS render-blocking + Google Fonts externo (`index.blade.php:26,28,30,32,34`). → Combinar CSS (FE-02) y auto-hospedar/diferir fuentes (`media=print`+`onload`).
- **FE-11 (low/s)** — `conversations-identity.css` sin cache-busting (`index.blade.php:28`). → Añadir `?v={{ @filemtime(...) }}` (o resolver al combinar en FE-02).

---

## Eje 3 — Experiencia de usuario (interacción, feedback, latencia percibida)

Base sólida de feedback (toastr, validación 422, skeletons en commerce/ERP, autosave de borrador, atajos j/k, typing indicator, realtime). La latencia percibida la domina el full-reload sin spinner intermedio.

- **UX-01 (high/s)** — Sin feedback de carga al abrir/cambiar conversación; UI congelada (`conversations.js:708-710`). → Skeleton/overlay/NProgress + `.on` inmediato en el item; aplica a j/k.
- **UX-02 (high/m)** — Envío no optimista: textarea bloqueado y burbuja solo tras round-trip (`:1000-1037`). → Burbuja `pending` instantánea, textarea activo/enfocado, reconciliar/`Reintentar`; mínimo, spinner en `.btn-send`.
- **UX-03 (high/s)** — Mensaje entrante por WS fuerza scroll al fondo aunque el agente lea historial (`:1317`). → Comprobar cercanía al fondo; píldora "Nuevo mensaje ↓"; auto-scroll solo en mensajes propios.
- **UX-04 (medium/m)** — Hilo carga todos los mensajes sin límite (render/scroll lentos). → Últimos N (~50) desc renderizados asc + "Cargar anteriores" lazy.
- **UX-05 (medium/m)** — Búsqueda de la lista solo filtra client-side las 50 ya cargadas (`:727-738`, `paginate(50)`). → Búsqueda server-side debounced (`q` a `manager.helpdesk.conversations.list`) + filtrado local como respuesta instantánea.
- **UX-06 (low/s)** — El hint `<kbd>F</kbd>` junto al buscador no enfoca el buscador (la `f` abre filtros) (`list.blade.php:7` vs `conversations.js:899`). → Que `f` (fuera de input) haga `focus()` del buscador y mover filtros a otra tecla, o quitar el `<kbd>F</kbd>`.
- **UX-07 (low/s)** — Acciones destructivas (archivar/cerrar) sin deshacer (`:1405-1407`, `:1532`). → Toastr con acción "Deshacer" (~6s) que reinserte el item y llame al endpoint inverso.
- **UX-08 (high/l)** — Convertir la apertura en carga AJAX (eliminar la recarga completa). → Cambio grande de máximo impacto; reutiliza `listJson`/`appendBubbleToThread`/Echo parametrizado por convId. Hacerlo tras los quick wins UX-01/04.

---

## Eje 4 — UI, consistencia, accesibilidad y responsive

Bueno: Font Awesome 6 (cero Tabler), tokens en `conversations-identity.css`, dark mode sin FOUC, tabs responsive. Mayor ROI: a11y y consistencia de color.

- **UI-01 (high/s)** — `--bv-primary` nunca definido → 3 "primarios" (rojo `#b91c1c`, verde `#90bb13`, gris `#18181b`). → Definir `--bv-primary:#90bb13` en `:root` y reemplazar hardcodeos/fallbacks.
- **UI-02 (high/s)** — Verde `#90bb13` + texto blanco incumple WCAG AA (~2.3:1) (`:1547-1551`). → `--bv-primary-strong` (`#5e7a0d`) para botones con texto; `#90bb13` solo acentos/large-text.
- **UI-03 (high/s)** — Sin foco de teclado visible: 35× `outline:none`, cero `:focus-visible` en controles. → Regla global `:focus-visible` en `.conversations`.
- **UI-04 (high/m)** — 82/83 modales sin `role=dialog`/`aria-modal`; `openModal()` no gestiona foco (`conversations.js:215-237`). → ARIA en partial base + focus-trap/restauración centralizados.
- **UI-05 (medium/s)** — "Escribiendo…" y cambios dinámicos sin `aria-live` (`index.blade.php:715`). → `aria-live="polite" role="status"` + live-region oculto para entrantes.
- **UI-06 (medium/m)** — Items de conversación son `<div>` no enfocables (`conv-item.blade.php:10`). → `role="button" tabindex="0"` + `aria-label` + Enter/Space; idealmente envolver en `<a href>`.
- **UI-07 (medium/m)** — Hasta 17 tabs icon-only del panel derecho sin `aria-label`/semántica tablist (`right-panel.blade.php:343-416`). → `aria-label` por botón + `role=tablist/tab/tabpanel` con `aria-selected`; agrupar PS/ERP en overflow.
- **UI-08 (medium/m)** — Hueco responsive 1024-1280px: el panel derecho desaparece sin forma de recuperarlo (`conversations.css:2086-2087`). → Mostrar mobile-tabs/botón "Detalle" en ese rango, u off-canvas deslizable.
- **UI-09 (low/s)** — Media queries con `grid-template-areas` inválidas (topbar/rail inexistentes) (`:2077-2095`). → Eliminar las 2 media queries muertas (~20 líneas).
- **UI-10 (medium/l)** — Dos sistemas de modal paralelos (`bv-modal` vs `hd-overlay/hd-modal`) (`thread.blade.php:682`). → Migrar "Respuesta rápida" a `bv-modal`; eliminar CSS/lógica duplicada.
- **UI-11 (low/s)** — Hover de botón primario verde que vira a rojo oscuro (`:4451-4454`, `:9879`, `:11139`). → Unificar a oscurecido del propio `--bv-primary` (`color-mix`).
- **UI-12 (low/m)** — 195 estilos inline `style=""` en las vistas (incumple `blade-views.md`) (`index.blade.php:99-107,262-270`). → Extraer patrones repetidos a clases utilitarias.
- **UI-13 (low/s)** — Waveform de audio con `role=slider` sin valores ARIA ni teclado (`thread.blade.php:368`). → Implementar patrón slider completo o quitar `role=slider`/`tabindex`.

---

## Orden recomendado (por impacto en velocidad percibida)

**Tanda 1 — Quick wins que abaratan cada carga (s/m, sin reescribir el flujo):**
1. perf-03 — sacar `sync()` del render (job/TTL) → quita una API externa del primer paint.
2. perf-04 / FE-05 / UX-04 — limitar items del hilo (~30-50) + paginar anteriores.
3. UX-01 — feedback de carga inmediato (skeleton/NProgress) en cada clic y en j/k.
4. UX-03 — scroll inteligente (no arrancar la vista al entrar mensaje por WS).
5. FE-02 / FE-10 — minificar y combinar CSS+JS, diferir fuentes.
6. FE-06 — polling de notificaciones solo como fallback de Echo.
7. UI-01, UI-02, UI-03 — token `--bv-primary`, contraste accesible, `:focus-visible`.

**Tanda 2 — Optimista + a11y de fondo + caché:**
8. UX-02 — envío optimista de mensaje.
9. perf-05, perf-06, perf-08 — cachear contadores (incl. `Cache::flexible`) y `open_count`.
10. UI-04, UI-05 — ARIA/focus-trap en modales + `aria-live`.
11. UX-05 — búsqueda server-side debounced.

**Tanda 3 — Cambios grandes (eliminan la recarga de raíz):**
12. perf-01 / FE-01 / UX-08 — apertura de conversación por AJAX parcial (SPA) con `pushState`.
13. perf-02 / FE-03 / perf-09 / FE-09 — lazy-load del right-panel y sus pestañas.
14. FE-04 / FE-07 — modales bajo demanda + consolidar `<script>` inline.

**Tanda 4 — Pulido:** perf-07 (columna `handled_by_bot` indexada), UI-06/07/08 (a11y items/tabs/responsive), UI-09/11/12/13 y UI-10 (unificar modales).
