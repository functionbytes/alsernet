# Optimize

> HTML optimization: minify, collapse whitespace, defer JS, remove comments

## Proposito

Middleware de optimizacion que procesa el HTML de respuesta antes de enviarlo al navegador. Minifica el marcado eliminando espacios en blanco innecesarios y comentarios HTML, lo que reduce el tamano de transferencia sin modificar el comportamiento de la pagina. Se puede configurar para omitir rutas especificas (assets, archivos de descarga, rutas de settings).

## Componentes principales

- **Modelos**: Ninguno
- **Middleware**: Aplicado automaticamente a respuestas HTML via ServiceProvider
- **Rutas**: `routes/web.php` (rutas internas del modulo si aplica)
- **Config**: `config/general.php` — lista de rutas a omitir del proceso de optimizacion

## Configuracion

- Archivo: `config/general.php`
- La clave `skip` contiene patrones de rutas y extensiones de archivo excluidas del minificado:
  - Rutas de settings: `setting/*`
  - Archivos estaticos: `*.css`, `*.js`, `*.jpg`, `*.png`, `*.pdf`, `*.mp4`, etc.

```php
// Ejemplo: agregar exclusion
'skip' => [
    'mi-ruta-especial/*',
    '*.xml',
],
```

- Variables env relevantes: Ninguna

## Permisos

No requiere permisos — es un modulo de infraestructura sin interfaz de usuario.

## Dependencias

- **Core**: No
- Otros: Ninguno (pure PHP string processing)
