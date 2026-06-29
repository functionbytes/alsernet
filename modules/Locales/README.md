# Locales

> Locale and translation management

## Proposito

Gestiona los idiomas disponibles en la aplicacion y permite editar las traducciones de la interfaz directamente desde el panel de administracion. Soporta activar/desactivar locales, establecer el idioma por defecto, e importar/exportar archivos de traduccion por grupo.

## Componentes principales

- **Modelos**: `Locale`
- **Controladores**: `LocaleController`, `ThemeTranslationController`
- **Rutas**: `panel/settings/locales` (prefijo `locales.`)
  - CRUD de locales (`index`, `create`, `store`, `edit`, `update`, `destroy`)
  - `POST /{locale}/default` — establecer idioma por defecto
  - `POST /{locale}/toggle` — activar/desactivar
  - `GET /translations` — listar grupos de traducciones
  - `GET /translations/{locale}/{group}` — editar traducciones de un grupo
  - `POST /translations/{locale}/{group}/bulk` — acciones masivas
  - `GET/POST /translations/{locale}/{group}/export|import` — exportar/importar JSON
- **API**: `routes/api.php` (endpoints para locales via Sanctum)

## Permisos

Convencion: `locales.view`, `locales.create`, `locales.update`, `locales.delete`, `locales.manage`

(Completar segun seeder del modulo — no se encontro seeder dedicado en el repositorio actual.)

## Configuracion

- Archivo: `config/config.php`
- Variables env relevantes: Ninguna especifica; usa la configuracion de locale de Laravel (`APP_LOCALE`, `APP_FALLBACK_LOCALE`)

## Dependencias

- **Core**: Si
- Otros: Ninguno
