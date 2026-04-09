# Visual Editor — Roadmap de funcionalidades

Comparativa con Elementor y plan de implementación para el editor visual de Alsernet.

## Estado actual

### Funcionalidades ya implementadas ✅

| # | Feature | Descripción |
|---|---|---|
| 1 | Drag & drop bloques | Arrastrar shortcodes desde sidebar al preview iframe |
| 2 | Inline editing | Edición de texto directamente en el preview via postMessage |
| 3 | Inspector de estilos | Panel con tipografía, espaciado, fondo, borde, sombra, posición, display, transform |
| 4 | Layout panel | Presets de grid Bootstrap + árbol de containers/rows/cols colapsable |
| 5 | Secciones panel | Lista de secciones con drag-to-reorder (jQuery UI sortable) |
| 6 | Historial/Undo | Stack de hasta 50 estados con undo/redo + snapshots manuales |
| 7 | Editor HTML completo | CodeMirror con syntax highlighting, fold, search |
| 8 | Editor HTML modal | Para editar HTML de un elemento específico |
| 9 | Responsive breakpoints | Desktop, laptop, tablet, móvil con resize del iframe |
| 10 | Context menu | Click derecho con copiar, pegar, mover, duplicar, eliminar, inspeccionar |
| 11 | Link editor | Modal completo para enlaces internos, externos, anclas, email, descargas |
| 12 | Icon selector | Grid de 200+ iconos Font Awesome para insertar |
| 13 | Shortcode builder | Modal dinámico que construye shortcodes con sus atributos |
| 14 | Guardar como bloque | Guardar elemento seleccionado como bloque personalizado reutilizable |
| 15 | Auto-save | Guardado automático periódico con detección de cambios |
| 16 | Locale switcher | Cambiar entre idiomas y editar contenido por locale |
| 17 | Canvas zoom | 50%-150% con controles visuales |
| 18 | Export HTML | Descargar el HTML como archivo .html |
| 19 | Rotate device | Rotar orientación del preview responsive |
| 20 | Grid overlay | Toggle de visualización de grid Bootstrap |
| 21 | Sidebar resize | Arrastrar para redimensionar el sidebar |
| 22 | Sidebar collapse | Colapsar a solo iconos / expandir |
| 23 | MediaPicker integrado | Para seleccionar imagen de fondo desde el gestor de medios |
| 24 | Select2 | Dropdowns mejorados en el inspector |
| 25 | Word count | Contador de palabras en la barra inferior |
| 26 | Keyboard shortcuts | Modal con lista de atajos (tecla ?) |
| 27 | Copy/paste feedback | Toast de confirmación al copiar/pegar elementos |
| 28 | Breakpoint dimensions | Muestra dimensiones al cambiar breakpoint |
| 29 | Unsaved changes dot | Punto rojo en "Guardar" cuando hay cambios pendientes |
| 30 | Autosave timestamp | "Guardado hace X min" relativo |
| 31 | ScrollIntoView | Al hacer click en breadcrumb/layout/sections, el preview scrollea al elemento |
| 32 | Search shortcodes | Búsqueda en tiempo real de bloques desde el topbar |
| 33 | Auto-refresh panels | Layout y Secciones se refrescan automáticamente al cambiar de panel |
| 34 | SEO analysis | Análisis de meta title, description, keywords con contadores |

---

## Funcionalidades de Elementor que podemos implementar

### Prioridad 1 — Alto impacto, complejidad media

| # | Feature | Descripción | Complejidad | Archivos |
|---|---|---|---|---|
| P1.1 | **Drag reorder en preview** | Arrastrar secciones/elementos directamente en el iframe para reordenar (no solo desde el panel de secciones) | Alta | Controller bridge JS + visual-editor.blade.php |
| P1.2 | **Copy/Paste styles** | Click derecho → "Copiar estilo" en un elemento, luego "Pegar estilo" en otro (solo CSS, no contenido) | Media | Context menu + bridge JS |
| P1.3 | **Navigator panel mejorado** | Panel tipo Elementor Navigator: árbol expandible de TODA la estructura DOM (no solo containers), con drag reorder, rename, show/hide | Alta | Nuevo partial + bridge JS |
| P1.4 | **Global colors/fonts** | Definir colores y fuentes globales del sitio que se aplican con un click desde el inspector | Alta | Settings panel + nuevo partial |
| P1.5 | **Responsive per-device overrides** | En el inspector, poder definir estilos diferentes por breakpoint (ej: font-size 16px en desktop, 14px en mobile) | Alta | Inspector panel + bridge JS |

### Prioridad 2 — Mejoras de productividad

| # | Feature | Descripción | Complejidad | Archivos |
|---|---|---|---|---|
| P2.1 | **Quick actions floating bar** | Al seleccionar elemento en preview, mostrar mini-toolbar flotante sobre el elemento: ↑ ↓ copy delete edit | Alta | Bridge JS + CSS |
| P2.2 | **CSS class autocomplete** | Al escribir clases CSS en el inspector, autocompletar con clases Bootstrap 5.3 | Media | Inspector panel JS |
| P2.3 | **Template library** | Panel con plantillas prediseñadas de secciones completas (hero, features, pricing, testimonials, footer) que se insertan con un click | Media | Nuevo partial + seeder |
| P2.4 | **Revision history** | Guardar versiones nombradas con diff visual entre ellas, poder restaurar cualquier versión anterior | Alta | Controller + DB migration |
| P2.5 | **Multi-select elements** | Seleccionar múltiples elementos (Ctrl+click) y aplicar estilos a todos a la vez | Alta | Bridge JS + Inspector |

### Prioridad 3 — Funcionalidades avanzadas

| # | Feature | Descripción | Complejidad | Archivos |
|---|---|---|---|---|
| P3.1 | **Motion effects** | Animaciones de scroll (fade-in, slide-up, parallax) configurables desde el inspector sin código | Alta | Inspector panel + CSS animations |
| P3.2 | **Popup builder** | Crear popups/modales visualmente con triggers (click, scroll, timer, exit intent) | Muy alta | Nuevo módulo |
| P3.3 | **Form builder visual** | Arrastrar campos de formulario (input, textarea, select, checkbox) y configurar validación + envío | Muy alta | Nuevo partial + backend |
| P3.4 | **Dynamic content** | Tags dinámicos que se resuelven en runtime (título de página, fecha, nombre de usuario, custom fields) | Alta | Shortcode system + bridge |
| P3.5 | **Conditions/display rules** | Mostrar/ocultar secciones basado en: tipo de usuario, hora, URL, dispositivo, cookie | Alta | Middleware + bridge JS |
| P3.6 | **A/B testing** | Crear variantes de secciones y medir cuál convierte mejor | Muy alta | Nuevo módulo + analytics |
| P3.7 | **Custom CSS per element** | Ya existe parcialmente — mejorar con CodeMirror embebido en el inspector con autocomplete | Media | Inspector panel |
| P3.8 | **Responsive preview split** | Ver desktop + mobile side-by-side en el canvas | Media | Canvas layout CSS + JS |
| P3.9 | **AI content generation** | Botón "Generar con AI" para crear texto, títulos, descripciones basados en prompt | Alta | Backend API + JS |
| P3.10 | **Image optimization** | Al subir imagen, auto-convertir a WebP, redimensionar, y optimizar | Media | Ya parcial con ConvertToWebpJob |

### Prioridad 4 — Polish y UX

| # | Feature | Descripción | Complejidad | Archivos |
|---|---|---|---|---|
| P4.1 | **Tooltips mejorados** | Tooltips ricos al hover sobre elementos del sidebar (con preview del bloque) | Baja | CSS + JS |
| P4.2 | **Finder/command palette** | Ctrl+K para abrir buscador global: buscar bloques, páginas, acciones, shortcuts | Media | JS modal |
| P4.3 | **Pin panels** | Poder tener 2 paneles visibles a la vez (ej: Inspector + Secciones en split) | Media | Sidebar layout |
| P4.4 | **Dark mode** | Toggle para tema oscuro en sidebar/topbar/bottombar (canvas siempre claro) | Media | CSS variables |
| P4.5 | **Accesibilidad audit** | Botón que analiza el contenido y reporta problemas de accesibilidad (alt faltante, contraste bajo, etc.) | Media | JS analysis |
| P4.6 | **Performance score** | Mostrar peso estimado de la página, número de imágenes, scripts, y sugerencias | Media | JS analysis |
| P4.7 | **Comments/annotations** | Agregar notas/comentarios en secciones específicas para colaboración en equipo | Alta | DB + JS |
| P4.8 | **Undo por sección** | En vez de undo global, poder hacer undo solo de los cambios en una sección específica | Alta | History system refactor |

---

## Estado actual — Implementado ✅

### Sesión completa (60+ features + 15 commits)
- Stitch designs: Media Picker, Shortcode Manager, Visual Editor restructure
- Dark mode, Command palette (Ctrl+K), Keyboard shortcuts modal
- Navigator DOM tree, Split responsive preview, 20+ device presets
- Quick actions floating bar, Enhanced inline toolbar (B/I/U/S/Tag/Size/Color)
- Copy/paste styles, Drag reorder between columns, Image resize handles
- CSS class autocomplete (Bootstrap 5.3), Gradient builder, Box shadow builder
- Motion effects (8 animations), Wireframe mode
- Popup builder, Form builder, AI content generation
- Accessibility audit, Performance score, SEO score card
- Element search (Ctrl+F), Responsive ruler, Page weight monitor
- Global design system, Dynamic content tags, Display conditions
- Paste smart (Word/Docs cleanup), Global blocks, Version compare
- Drag files from desktop, Live CSS injection, Animation replay
- Google SEO preview, Social share preview
- Auto-save progress bar, Unsaved changes dot, Autosave timestamp

### Pendiente para próxima sesión
- **Refactor inline styles**: Inspector panel (217), Settings (47), Visual editor (99)
- **Extraer CSS/JS**: Mover 1500+ líneas de inline CSS y 600+ líneas de sync JS a archivos externos
- **Tests E2E**: Playwright/Cypress para el visual editor
- **API endpoints**: `/api/v1/ai/generate-content`, `/api/v1/page-import`
- **Collaboration indicators**: WebSocket para multi-usuario

## Plan de implementación recomendado

### Fase 1 (próximas 2 semanas)
- P1.1 — Drag reorder en preview
- P1.2 — Copy/Paste styles
- P3.7 — Custom CSS per element mejorado
- P4.1 — Tooltips mejorados

### Fase 2 (mes siguiente)
- P1.3 — Navigator panel mejorado
- P2.1 — Quick actions floating bar
- P2.2 — CSS class autocomplete
- P2.3 — Template library

### Fase 3 (2-3 meses)
- P1.4 — Global colors/fonts
- P1.5 — Responsive per-device overrides
- P3.1 — Motion effects
- P3.8 — Responsive preview split

### Fase 4 (largo plazo)
- P2.4 — Revision history
- P3.9 — AI content generation
- P4.2 — Finder/command palette
- P4.4 — Dark mode

---

## Referencias

- [Elementor Features](https://elementor.com/features/)
- [Elementor Editor](https://elementor.com/features/editor/)
- [Elementor Navigator](https://elementor.com/help/navigator/)
- [Elementor Responsive Editing](https://elementor.com/help/mobile-editing/)
- [Elementor Copy Paste](https://elementor.com/blog/introducing-copy-paste/)
- [Elementor Global Colors & Design System](https://elementor.com/academy/global-colors-fonts-creating-a-design-system-with-elementor/)
- [Elementor 4.0 Atomic Editor](https://github.com/orgs/elementor/discussions/35165)
- [Elementor Review 2026](https://wpastra.com/review/elementor-review/)
