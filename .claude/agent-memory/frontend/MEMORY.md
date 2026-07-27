# Memory Index

## Project
- [Auditoría UI core Helpdesk (6-jul-2026)](project_helpdesk_core_ui_audit_2026_07_06.md) — bv-modal ARIA era falso positivo (ya en JS desde 30-jun), 0/330 th sin scope, React islands muertos, managers/* vacíos pendientes

## Reference
- [React islands de Helpdesk sin usar](reference_helpdesk_dead_react_islands.md) — app.tsx registrado en Vite pero nunca @vite ni data-react-component en ninguna vista Helpdesk
- [@stack('styles') ya existe en el layout](reference_theme_stack_styles_fixed.md) — fix b6718e21; la vieja advertencia de "stack fantasma" en comentarios de attach-file.blade.php está desactualizada
- [A11y centralizada de bv-modal en conversations.js](reference_helpdesk_bv_modal_a11y_centralized.md) — applyModalA11y() cubre los 72 partials; 3 modales ad-hoc necesitan llamarla a mano
