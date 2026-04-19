---
name: Table row actions dropdown (ti-dots)
description: Actions inside table rows use the ti-dots dropdown — same universal pattern as card headers
type: feedback
---

For action columns inside `<table>` rows, use the `ti ti-dots` dropdown trigger — same pattern used everywhere else in the UI.

**Why:** Universal pattern across card headers, toolbars, and table rows. Keeps the UI consistent.

**How to apply:**

```html
<div class="dropdown">
    <a href="javascript:void(0)" class="link" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="ti ti-dots fs-6 text-dark"></i>
    </a>
    <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
        <li><a class="dropdown-item" href="...">Editar</a></li>
        <li><a class="dropdown-item" href="...">Ver detalle</a></li>
        <li><a class="dropdown-item text-danger" href="...">Eliminar</a></li>
    </ul>
</div>
```

Note: `ti ti-dots` is the ONLY allowed use of Tabler icons in this project. All other icons must use Font Awesome 6.
