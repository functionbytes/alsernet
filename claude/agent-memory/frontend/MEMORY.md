# Frontend Agent Memory

## Layout & Stack directives
- Layout: `@stack('css')` for stylesheets, `@stack('scripts')` for JS (NOT `@stack('styles')`)
- Main layout: `modules/Theme/resources/views/layouts/theme.blade.php`

## Nestable2 + Bootstrap Collapse CSS conflict
- nestable2 CSS sets `visibility: collapse` on `.collapse` elements, breaking Bootstrap accordion
- Fix: add this CSS override when using nestable2 alongside Bootstrap collapse:
```css
.collapse { visibility: visible !important; }
.collapse:not(.show) { display: none !important; }
```
- CDN: `https://cdnjs.cloudflare.com/ajax/libs/nestable2/1.6.0/jquery.nestable.min.css`
- CDN: `https://cdnjs.cloudflare.com/ajax/libs/nestable2/1.6.0/jquery.nestable.min.js`

## Nestable2 dd3-content padding
- Remove `px-*` (Bootstrap padding) from `.dd3-content` elements
- Nestable2 sets `padding-left: 44px` on `.dd3-content` to clear the 32px handle
- Use `pe-3` only for right padding, never `px-3` on `dd3-content`

## Menu Module Routes
- `menu.get-node` expects `data` as array query params: `?data[type]=custom&data[title]=...`
- `menu.update` handles both config fields AND tree in ONE PUT form
- `menu.structure.update` is for drag-only (no field edits)

## Key File Paths
- Menu views: `modules/Menu/resources/views/`
- Menu partials: `modules/Menu/resources/views/partials/`
- Menu controller: `modules/Menu/app/Http/Controllers/MenuController.php`
- Menu service: `modules/Menu/app/Services/MenuService.php`
