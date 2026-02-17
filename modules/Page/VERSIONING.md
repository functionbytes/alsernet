# Sistema de Versionado de Contenido - Módulo Page

## Descripción General

El sistema de versionado de contenido permite rastrear y gestionar todas las modificaciones realizadas en las páginas del CMS. Cada vez que se actualiza una página, se crea automáticamente una nueva versión que captura el estado completo de la página en ese momento.

## Características Principales

- **Versionado Automático**: Cada actualización de página crea automáticamente una nueva versión
- **Historial Completo**: Visualización de todas las versiones de una página con información detallada
- **Restauración**: Capacidad de restaurar cualquier versión anterior
- **Comparación Visual**: Comparar dos versiones para ver los cambios exactos realizados
- **Snapshots Manuales**: Crear versiones manualmente cuando sea necesario
- **Metadatos Completos**: Cada versión guarda todos los campos de la página (contenido, SEO, template, etc.)

## Estructura de Archivos

```
modules/Page/
├── app/
│   ├── Models/
│   │   ├── Page.php                    # Modelo principal con trait Versionable
│   │   └── PageVersion.php             # Modelo de versiones
│   ├── Traits/
│   │   └── Versionable.php             # Trait con funcionalidad de versionado
│   ├── Observers/
│   │   └── PageObserver.php            # Observer para auto-crear versiones
│   └── Http/
│       └── Controllers/
│           └── PageVersionController.php # Controlador de versiones
├── database/
│   └── migrations/
│       └── 2026_02_08_000002_create_page_versions_table.php
├── resources/
│   └── views/
│       └── admin/
│           └── versions/
│               ├── index.blade.php     # Lista de versiones
│               ├── show.blade.php      # Detalle de una versión
│               └── compare.blade.php   # Comparación de versiones
└── tests/
    └── VersioningTest.php              # Tests unitarios
```

## Uso del Sistema

### 1. Versionado Automático

El versionado ocurre automáticamente gracias al `PageObserver`. Cada vez que se actualiza una página:

```php
$page = Page::find(1);
$page->update([
    'title' => 'Nuevo Título',
    'content' => 'Nuevo contenido'
]);
// Se crea automáticamente una nueva versión
```

### 2. Crear Versión Manual (Snapshot)

```php
// Crear un snapshot del estado actual
$version = $page->createVersion();

// Con usuario específico
$version = $page->createVersion($userId);
```

### 3. Obtener Historial de Versiones

```php
// Obtener últimas 50 versiones (por defecto)
$history = $page->getVersionHistory();

// Limitar cantidad
$history = $page->getVersionHistory(10);

// Con relaciones
$history = $page->versions()->with('user')->latest()->get();
```

### 4. Restaurar Versión Anterior

```php
// Restaurar por ID de versión
$page->restoreVersion($versionId);

// Restaurar versión específica por número
$version = $page->getVersionByNumber(5);
$page->restoreVersion($version->id);
```

### 5. Comparar Versiones

```php
// Comparar dos versiones
$comparison = $page->compareVersions($versionId1, $versionId2);

// Resultado incluye:
// - version1: objeto de versión 1
// - version2: objeto de versión 2
// - differences: array de diferencias por campo
// - has_changes: boolean indicando si hay cambios
```

### 6. Información de Versiones

```php
// Verificar si tiene versiones
if ($page->hasVersions()) {
    echo "Tiene versiones";
}

// Total de versiones
$total = $page->getTotalVersions();

// Número de versión actual
$current = $page->getCurrentVersionNumber();

// Obtener versión por número
$version = $page->getVersionByNumber(3);
```

### 7. Limpieza de Versiones Antiguas

```php
// Mantener solo las últimas 10 versiones
$deleted = $page->pruneVersions(10);
```

## Campos Versionados

Cada versión almacena los siguientes campos de la página:

- `title` - Título de la página
- `content` - Contenido HTML
- `description` - Descripción
- `template` - Template utilizado
- `status` - Estado (draft, published, pending)
- `slug` - URL slug
- `seo_title` - Título SEO
- `seo_description` - Descripción SEO
- `seo_keywords` - Palabras clave SEO
- `user_id` - Usuario que creó la versión
- `version_number` - Número secuencial de versión
- `created_at` - Fecha de creación

## Interfaz de Usuario

### Historial de Versiones
- URL: `/admin/pages/{page}/versions`
- Muestra lista completa de versiones con:
  - Número de versión
  - Título
  - Estado
  - Autor
  - Fecha
  - Tamaño
  - Acciones (ver, restaurar, eliminar)

### Ver Versión
- URL: `/admin/pages/{page}/versions/{version}`
- Muestra el contenido completo de una versión específica
- Incluye metadatos y campos SEO
- Opción para restaurar directamente

### Comparar Versiones
- URL: `/admin/pages/{page}/versions/compare?version1=X&version2=Y`
- Visualización lado a lado de dos versiones
- Resalta los cambios en cada campo
- Código de colores:
  - Rojo: contenido eliminado/antiguo
  - Verde: contenido nuevo/agregado
  - Amarillo: campo modificado

### Crear Snapshot Manual
- Botón en la página de historial
- Crea una versión del estado actual sin necesidad de guardar cambios

## Rutas

```php
// Ver historial
GET /admin/pages/{page}/versions

// Ver versión específica
GET /admin/pages/{page}/versions/{version}

// Comparar versiones
GET /admin/pages/{page}/versions/compare?version1=X&version2=Y

// Restaurar versión
POST /admin/pages/{page}/versions/{version}/restore

// Crear snapshot
POST /admin/pages/{page}/versions/create

// Eliminar versión
DELETE /admin/pages/{page}/versions/{version}
```

## Base de Datos

### Tabla: `page_versions`

```sql
CREATE TABLE `page_versions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `page_id` bigint unsigned NOT NULL,
  `version_number` int unsigned NOT NULL,
  `title` varchar(191) NOT NULL,
  `content` longtext,
  `description` text,
  `user_id` bigint unsigned,
  `template` varchar(60),
  `status` enum('draft','published','pending'),
  `slug` varchar(191),
  `seo_title` varchar(191),
  `seo_description` text,
  `seo_keywords` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `page_versions_page_id_version_number_unique` (`page_id`,`version_number`),
  KEY `page_versions_page_id_index` (`page_id`),
  KEY `page_versions_created_at_index` (`created_at`),
  CONSTRAINT `page_versions_page_id_foreign` FOREIGN KEY (`page_id`) REFERENCES `pages` (`id`) ON DELETE CASCADE,
  CONSTRAINT `page_versions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
);
```

### Índices y Restricciones

- `PRIMARY KEY`: id
- `UNIQUE`: (page_id, version_number)
- `INDEX`: page_id, created_at
- `FOREIGN KEY`: page_id → pages(id) CASCADE
- `FOREIGN KEY`: user_id → users(id) SET NULL

## Eventos y Observers

### PageObserver

El `PageObserver` está registrado en `EventServiceProvider` y escucha los siguientes eventos:

- **created**: Crea versión inicial (v1)
- **updating**: Crea versión antes de actualizar (solo si hay cambios)
- **restored**: Crea versión cuando se restaura desde soft delete

### Campos Monitoreados

El observer solo crea versiones si alguno de estos campos cambia:

```php
'title', 'content', 'description', 'template',
'status', 'slug', 'seo_title', 'seo_description', 'seo_keywords'
```

## Testing

Ejecutar tests del sistema de versionado:

```bash
php artisan test --filter=VersioningTest
```

Tests incluidos:
- Creación de versión inicial
- Creación de versión en actualización
- Restauración de versiones
- Comparación de versiones
- Historial de versiones
- Creación manual de versiones
- Tracking de metadatos

## Mejores Prácticas

1. **Limpieza Periódica**: Ejecutar `pruneVersions()` periódicamente para evitar acumulación excesiva
2. **Snapshots Importantes**: Crear snapshots manuales antes de cambios importantes
3. **Revisión de Cambios**: Siempre comparar versiones antes de restaurar
4. **Documentación**: Usar descripciones claras en los commits para identificar cambios

## Consideraciones de Rendimiento

- Las versiones se almacenan con índices optimizados
- La consulta de versiones usa eager loading para evitar N+1 queries
- El contenido HTML se almacena como `longtext` sin comprimir para comparaciones rápidas
- Se recomienda limitar el historial a 50-100 versiones por página

## Extensiones Futuras

Posibles mejoras al sistema:

1. **Etiquetas de Versión**: Permitir etiquetar versiones importantes
2. **Comentarios**: Agregar comentarios/notas a cada versión
3. **Diff Avanzado**: Implementar diff línea por línea para contenido HTML
4. **Compresión**: Comprimir contenido antiguo para ahorrar espacio
5. **Exportación**: Exportar versiones como JSON o backup
6. **Programación**: Programar restauración de versiones
7. **Aprobaciones**: Sistema de aprobación de versiones antes de publicar

## Soporte

Para problemas o preguntas sobre el sistema de versionado:
- Revisar logs en `storage/logs/laravel.log`
- Verificar permisos de base de datos
- Consultar tests para ejemplos de uso
