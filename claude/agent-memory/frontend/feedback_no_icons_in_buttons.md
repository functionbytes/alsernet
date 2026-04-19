---
name: No icons in buttons or links
description: Never use <i> icon tags inside buttons or anchor links — text only
type: feedback
---

Never add `<i class="fas ...">` or any icon tag inside `<button>` or `<a>` elements.

**Why:** Project UI convention — buttons and links must be text-only. Icons in buttons add visual noise and the user explicitly prohibited this.

**How to apply:** Remove all `<i>` tags from inside `<button>`, `<a>`, and `dropdown-item` elements. Use plain text labels only.

❌ Wrong:
```html
<button class="btn btn-primary"><i class="fas fa-save me-1"></i>Guardar</button>
<a href="..."><i class="fas fa-download me-2"></i>Exportar CSV</a>
```

✅ Correct:
```html
<button class="btn btn-primary">Guardar</button>
<a href="...">Exportar CSV</a>
```
