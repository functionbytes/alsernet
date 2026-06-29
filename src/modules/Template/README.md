# Template

> Sistema modular de gestion de plantillas web (templates/layouts)

## Proposito

Permite crear y gestionar plantillas de pagina, menus de navegacion y shortcodes reutilizables. Las plantillas tienen versionado integrado. Los menus son estructurados (items anidados con orden). Los shortcodes permiten insertar bloques de contenido dinamico en plantillas y paginas del sitio.

## Componentes principales

- **Modelos**: `Template`, `TemplateVersion`, `Menu`, `MenuItem`, `Shortcode`, `ShortcodeCategory`
- **Controladores**: Gestionados via rutas web y API
- **Rutas**:
  - `routes/web.php` — panel de gestion de templates, menus y shortcodes
  - `routes/api.php` — API REST para consumo de templates y shortcodes

## Permisos

| Entidad | Permisos |
|---------|---------|
| Templates | `template.view`, `template.create`, `template.update`, `template.delete`, `template.manage` |
| Menus | `menu.view`, `menu.create`, `menu.update`, `menu.delete`, `menu.manage` |
| Shortcodes | `shortcode.view`, `shortcode.create`, `shortcode.update`, `shortcode.delete`, `shortcode.manage` |

## Configuracion

- Archivo: `config/config.php`
- Variables env relevantes: Ninguna especifica

## Dependencias

- **Core**: Si
- Otros: Ninguno
