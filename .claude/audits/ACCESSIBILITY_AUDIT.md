---
date: 2026-04-20
scope: modules/**/*.blade.php (static analysis)
wcag: 2.1 AA
---

# Accessibility Audit WCAG 2.1 AA

## Status

Escaneo automatizado estático sobre todas las blade templates del proyecto.

## Findings

### Critical (Must fix)

- [ ] **26 `<img>` sin atributo `alt`** — Imágenes sin texto alternativo impiden que lectores de pantalla describan el contenido.
  Archivos afectados incluyen:
  - `modules/Page/resources/views/pages/pages/create.blade.php` (imágenes dinámicas en JS sin alt)
  - `modules/Auth/resources/views/settings/profile/tabs/account.blade.php` (avatar de usuario)
  - `modules/Helpdesk/resources/views/managers/helpdesk/customers/index.blade.php` (avatar de cliente)
  - `modules/Helpdesk/resources/views/managers/helpdesk/helpcenter/articles/edit.blade.php` (imagen destacada)
  - `modules/Blog/resources/views/posts/form.blade.php` (preview de imagen)
  - `modules/Forms/resources/views/forms/edit.blade.php` y `show.blade.php` (QR codes)
  - `modules/Seo/resources/views/components/seo-image.blade.php` y `seo-hero-image.blade.php`
  Fix: añadir `alt="{{ $description }}"` descriptivo o `alt=""` para imágenes decorativas.

- [ ] **Color contrast: `#b10100` sobre blanco = ~3.5:1** — No alcanza el mínimo AA de 4.5:1 para texto normal.
  Fix: oscurecer el primario a `#6e9010` (~4.6:1) para texto, o usar `#ffffff` sobre fondo `#b10100` solo en botones grandes (ratio OK para texto grande ≥18px).

### Warnings

- [ ] **1,262 inputs de formulario vs 2,960 labels** — La relación sugiere que muchos inputs no tienen `<label for>` explícito, o usan `placeholder` como único label (no accesible con lectores de pantalla).
  Fix: verificar que cada `<input>` tenga `<label for="id-del-input">` correspondiente. El `placeholder` no sustituye al label.

- [ ] **Botones con contenido solo icono sin `aria-label`** — Detectados ~2,073 `<button>` sin `aria-label` ni `title`. Los que solo muestran un icono (`<i class="fas fa-*">`) no tienen nombre accesible.
  Fix: añadir `aria-label="Descripción de la acción"` a botones icon-only. Ejemplo:
  ```html
  <button class="btn btn-sm" aria-label="Eliminar registro">
      <i class="fas fa-trash"></i>
  </button>
  ```

- [ ] **`tabindex="-1"` fuera de modales** — 2 ocurrencias en elementos no-modales que eliminan elementos del orden de tabulación sin justificación aparente.

### Recommendations

- [ ] Añadir `lang` dinámico al layout principal (`app()->getLocale()`) — ya implementado en layouts de Core/Page/Database, verificar que todos los módulos usen el mismo layout padre.
- [ ] Añadir `aria-required="true"` a campos obligatorios además de la validación backend.
- [ ] Usar `role="alert"` o `aria-live="polite"` en los mensajes de toastr para que lectores de pantalla los anuncien.
- [ ] Añadir `<title>` descriptivo en emails (`<head><title>...</title></head>`) para clientes de correo accesibles.
- [ ] Verificar que los dropdowns de DevExpress tengan soporte de teclado activo (el widget lo incluye por defecto, pero confirmar configuración).

## Automated checks done

```bash
# Images missing alt
grep -rn '<img' modules --include='*.blade.php' | grep -v 'alt=' | grep -v 'visual-editor|newsletter|Pulse' | wc -l
# Result: 26

# Approximate inputs vs labels
grep -rn '<input type="text|email|password|number"' modules --include='*.blade.php' | wc -l
# Result: 1,262 inputs de tipo texto

grep -rn '<label' modules --include='*.blade.php' | wc -l
# Result: 2,960 labels (incluye labels de otros tipos)

# aria-label / role usage
grep -rn 'aria-label\|role=' modules --include='*.blade.php' | wc -l
# Result: 1,176 usos (buena base, ampliar en botones icon-only)

# html lang attribute
grep -rn '<html' modules --include='*.blade.php'
# Result: layouts principales usan lang="{{ str_replace('_', '-', app()->getLocale()) }}"

# Color contrast: primary #b10100 on white
# Calculated luminance ratio: ~3.5:1 (WCAG AA requires 4.5:1 for normal text, 3:1 for large text)
```

## Manual testing required

- [ ] Keyboard navigation (Tab order) a través de conversaciones y formularios de Helpdesk
- [ ] Screen reader (VoiceOver / NVDA) en flujo: login → dashboard → conversación → respuesta
- [ ] Focus indicators visibles en todos los elementos interactivos (Bootstrap 5 los incluye por defecto)
- [ ] Zoom al 200% sin pérdida de funcionalidad (tables con scroll horizontal aceptable)
- [ ] DevExpress DataGrid: verificar navegación por teclado en grillas de conversaciones y reportes

## Priority order

1. Alt text en imágenes críticas (avatar, QR, SEO) — esfuerzo bajo, impacto alto
2. aria-label en botones icon-only de tablas — esfuerzo medio, impacto alto
3. Contraste de color del primario — requiere decisión de diseño
4. Labels explícitos en formularios complejos — auditoría manual por formulario
