---
globs: "modules/*/resources/views/**/*.blade.php"
---

# Blade View Rules

- Icons: Font Awesome 6 ONLY (`fas fa-*`, `far fa-*`, `fab fa-*`). NEVER use Tabler Icons (`ti ti-*`)
- JavaScript: jQuery + AJAX. NEVER use Livewire or Inertia.js
- Section titles: capitalize only first word (`Informacion basica`, NOT `Informacion Basica`)
- Use Bootstrap 5.3 classes over custom CSS. NEVER use `style=""` inline styles
- CSRF token in AJAX: `headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }`
- Handle 422 errors: parse `xhr.responseJSON.errors` for per-field messages
- Use `toastr` for success/error notifications
- Table actions: ALWAYS dropdown with `fa-ellipsis-vertical`, no icons in items, no `text-danger` on delete
- Modals: ALWAYS `modal-dialog-centered` with footer buttons w-100 stacked (primary mb-2 top, secondary bottom)
- select2: NEVER use `theme: 'bootstrap-5'` (CSS not loaded)
- Primary color: `#90bb13`
