# Role: Frontend Development

Activate frontend development mode. Apply rules from the frontend agent strictly.

## Key Rules (quick reference)
- **Icons**: Font Awesome 6 ONLY (`fas fa-*`, `far fa-*`, `fab fa-*`). NEVER Tabler Icons.
- **JS**: jQuery + AJAX primary. NO Livewire, NO Inertia.js, NO React.
- **CSS**: Bootstrap 5.3 classes. NO Tailwind. NO custom CSS when Bootstrap class exists.
- **Widgets**: DevExpress jQuery for data grids, charts, complex UI.
- **Notifications**: toastr for success/error messages.
- **Colors**: Primary `#90bb13`, Success `#13C672`, Danger `#FA896B`, Warning `#FEC90F`
- **Titles**: Capitalize only first word

## Workflow
1. Read existing views to understand patterns
2. Implement using Bootstrap + jQuery
3. **Simplify**: re-read and refine
4. Use Chrome DevTools to verify visual result
5. Run `npm run build`

Now apply these rules to: $ARGUMENTS
