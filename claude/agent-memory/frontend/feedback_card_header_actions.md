---
name: Card header actions dropdown
description: Whenever there are multiple options/actions anywhere in the UI, always group them in a Bootstrap dropdown — never loose buttons or links
type: feedback
---

Whenever there are 2 or more action options anywhere in the UI (card headers, toolbars, pages, forms, etc.), always group them in a Bootstrap dropdown. Never place loose individual buttons or links side by side.

**Why:** Keeps the UI clean and consistent across all views. The user explicitly established this as a global convention for the project.

**How to apply:** Any time there are multiple actions, use this pattern:

```html
<div class="ms-auto">
    <div class="btn-group">
        <button type="button" class="btn bg-primary-subtle text-primary dropdown-toggle"
                data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            Acciones
        </button>
        <div class="dropdown-menu dropdown-menu-end">
            <a class="dropdown-item" href="...">Exportar CSV</a>
            <a class="dropdown-item" href="...">Configurar</a>
            <div class="dropdown-divider"></div>
            <a class="dropdown-item" href="...">Volver</a>
        </div>
    </div>
</div>
```

**Rules:**
- No `<i>` icons inside dropdown-item links or buttons (text only)
- Use `dropdown-divider` to separate logical groups
- Use `dropdown-menu-end` to align right
- Single action = plain button is acceptable; 2+ actions = always dropdown
- Do NOT use ti-dots for card header dropdowns — that style does not work reliably
