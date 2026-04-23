# Rule: javascript

> **Applies to:** "**/*.js,modules/*/resources/**/*.js"


# JavaScript Rules

- Use jQuery + AJAX for ALL dynamic interactions. NEVER use Livewire, Inertia, React, or Alpine.js
- CSRF token header on every AJAX request: `'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')`
- Use event delegation for dynamic content: `$(document).on('click', '.selector', handler)`
- Use DevExpress jQuery widgets for data grids, charts, and complex UI
- Handle 422 validation errors: parse `xhr.responseJSON.errors`
- Use `toastr` for notifications (success, error, warning, info)
- Use `$.ajax()` for complex requests, `$.get()`/`$.post()` for simple ones
