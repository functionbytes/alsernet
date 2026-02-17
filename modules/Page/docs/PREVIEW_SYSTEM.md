# Sistema de Preview de Páginas

El sistema de preview permite compartir enlaces seguros de páginas antes de publicarlas, facilitando la revisión y aprobación del contenido.

## Características

- **Tokens seguros**: Cada enlace de preview usa un token único de 64 caracteres
- **Expiración automática**: Los tokens expiran después de un tiempo configurable (por defecto 24 horas)
- **Seguimiento de vistas**: Registra cuántas veces se ha visto el preview y cuándo fue la última vez
- **Limpieza automática**: Los tokens expirados se limpian automáticamente mediante tareas programadas
- **Banner visual**: Muestra claramente que es una vista previa con información del token

## Uso Básico

### Generar un Preview Token desde el Admin

1. Edita una página en el panel de administración
2. En la sección "Vista Previa", haz clic en "Generar Preview"
3. Copia el enlace generado y compártelo
4. El enlace será válido por 24 horas

### Programáticamente

```php
use Modules\Page\Models\Page;

$page = Page::find(1);

// Generar token con expiración de 24 horas (por defecto)
$token = $page->generatePreviewToken();

// Generar token con expiración personalizada (48 horas)
$token = $page->generatePreviewToken(48);

// Obtener URL de preview
$url = $token->getPreviewUrl();

// O directamente desde la página
$url = $page->getPreviewUrl();

// Verificar si tiene token activo
if ($page->hasActivePreviewToken()) {
    echo "La página tiene un preview activo";
}

// Obtener o crear token (reutiliza si existe uno activo)
$token = $page->getOrCreatePreviewToken();

// Revocar todos los tokens activos
$page->revokeAllPreviewTokens();
```

## API Endpoints

### Generar Token (Admin)

```http
POST /admin/pages/{page}/preview/generate
Content-Type: application/json

{
  "expires_in_hours": 48  // Opcional, por defecto 24
}
```

**Respuesta:**
```json
{
  "success": true,
  "message": "Token de preview generado exitosamente.",
  "data": {
    "token": "cTKhWkCFWpwL9bByaHJx...",
    "url": "https://domain.com/preview/page-slug/cTKhWkCFWpwL9bByaHJx...",
    "expires_at": "2026-02-09T23:09:44.000000Z",
    "expires_in_human": "en 23 horas"
  }
}
```

### Listar Tokens (Admin)

```http
GET /admin/pages/{page}/preview
```

**Respuesta:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "token": "cTKhWkCFWpwL9bByaHJx...",
      "url": "https://domain.com/preview/page-slug/cTKhWkCFWpwL9bByaHJx...",
      "expires_at": "2026-02-09T23:09:44.000000Z",
      "expires_in_human": "en 23 horas",
      "is_active": true,
      "viewed_count": 5,
      "last_viewed_at": "2026-02-08T22:30:00.000000Z",
      "created_by": "John Doe",
      "created_at": "2026-02-08T23:09:44.000000Z"
    }
  ]
}
```

### Revocar Tokens (Admin)

```http
POST /admin/pages/{page}/preview/revoke
```

### Ver Preview (Público)

```http
GET /preview/{slug}/{token}
```

Esta ruta muestra la página con un banner distintivo que indica que es una vista previa.

## Modelo PagePreviewToken

### Relaciones

- `page()` - Obtiene la página asociada
- `creator()` - Obtiene el usuario que creó el token

### Scopes

- `active()` - Filtra tokens no expirados
- `expired()` - Filtra tokens expirados
- `recent()` - Ordena por más recientes

### Métodos

- `isExpired()` - Verifica si el token ha expirado
- `isActive()` - Verifica si el token está activo
- `recordView()` - Incrementa contador de vistas y actualiza timestamp
- `getPreviewUrl()` - Obtiene la URL completa del preview
- `getExpiresInHuman()` - Obtiene tiempo de expiración en formato legible

## Modelo Page - Métodos de Preview

### `generatePreviewToken($expiresInHours = 24, $userId = null)`

Genera un nuevo token de preview para la página.

**Parámetros:**
- `$expiresInHours` (int): Horas hasta que expire el token (por defecto: 24)
- `$userId` (int|null): ID del usuario que crea el token (por defecto: usuario autenticado)

**Retorna:** `PagePreviewToken`

### `getPreviewUrl()`

Obtiene la URL del último token activo de preview.

**Retorna:** `string|null`

### `hasActivePreviewToken()`

Verifica si la página tiene algún token de preview activo.

**Retorna:** `bool`

### `getOrCreatePreviewToken($expiresInHours = 24, $userId = null)`

Obtiene el token activo más reciente, o crea uno nuevo si no existe.

**Retorna:** `PagePreviewToken`

### `revokeAllPreviewTokens()`

Revoca (expira inmediatamente) todos los tokens activos de la página.

**Retorna:** `int` (número de tokens revocados)

## Comando Artisan

### Limpiar Tokens Expirados

```bash
# Limpiar tokens expirados hace más de 7 días (por defecto)
php artisan page:cleanup-preview-tokens

# Limpiar tokens expirados hace más de 30 días
php artisan page:cleanup-preview-tokens --days=30

# Modo verbose para ver detalles
php artisan page:cleanup-preview-tokens -v

# Forzar limpieza sin confirmación
php artisan page:cleanup-preview-tokens --force
```

Este comando se ejecuta automáticamente todos los días a las 2:00 AM mediante el scheduler de Laravel.

## Seguridad

- Los tokens son únicos y aleatorios de 64 caracteres
- Los enlaces expiran automáticamente
- Los tokens pueden ser revocados en cualquier momento
- Los formularios en modo preview están deshabilitados
- El acceso requiere conocer tanto el slug como el token

## Vista de Preview

La vista de preview incluye:

1. **Banner Superior Sticky**: Muestra claramente que es una vista previa
2. **Información del Token**: Tiempo de expiración y número de vistas
3. **Badge de Estado**: Indica si la página no está publicada
4. **Marca de Agua**: Texto "PREVIEW" en la esquina inferior derecha
5. **Desactivación de Formularios**: Los formularios no pueden enviarse en modo preview

## Personalización

### Cambiar Tiempo de Expiración por Defecto

Modifica el parámetro `$expiresInHours` al generar el token:

```php
// 72 horas
$token = $page->generatePreviewToken(72);
```

### Personalizar Vista de Preview

Edita el archivo:
```
modules/Page/resources/views/public/preview.blade.php
```

### Personalizar Banner

Los estilos del banner están en la sección `@push('styles')` del archivo `preview.blade.php`.

## Tareas Programadas

El sistema incluye dos tareas programadas:

1. **Limpieza de Tokens**: Se ejecuta diariamente a las 2:00 AM
   - Elimina tokens expirados hace más de 7 días
   - Libera espacio en la base de datos

Para que funcionen, asegúrate de que el cron job de Laravel esté configurado:

```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

## Troubleshooting

### El enlace de preview no funciona

1. Verifica que el token no haya expirado
2. Comprueba que la ruta esté correctamente configurada
3. Verifica que el slug y el token sean correctos

### Los formularios se envían en preview

Verifica que el JavaScript esté cargándose correctamente en la vista preview.

### El banner no aparece

Asegúrate de que la vista `preview.blade.php` esté siendo utilizada y no las vistas de página normal.

## Ejemplos de Uso

### Workflow de Aprobación

```php
// 1. Crear borrador de página
$page = Page::create([
    'title' => 'Nueva Página',
    'slug' => 'nueva-pagina',
    'content' => $content,
    'status' => 'draft',
]);

// 2. Generar preview para revisión (válido por 48 horas)
$token = $page->generatePreviewToken(48);

// 3. Enviar enlace al revisor
Mail::to('revisor@empresa.com')->send(
    new PreviewNotification($page, $token->getPreviewUrl())
);

// 4. Cuando se apruebe, publicar
$page->publish();

// 5. Revocar tokens de preview
$page->revokeAllPreviewTokens();
```

### Múltiples Revisores

```php
$page = Page::find(1);

// Crear token para cada revisor con diferentes tiempos
$token1 = $page->generatePreviewToken(24, $reviewer1->id);
$token2 = $page->generatePreviewToken(48, $reviewer2->id);

// Enviar enlaces personalizados
Mail::to($reviewer1->email)->send(new PreviewEmail($token1->getPreviewUrl()));
Mail::to($reviewer2->email)->send(new PreviewEmail($token2->getPreviewUrl()));
```

## Base de Datos

### Tabla: page_preview_tokens

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint | ID primario |
| page_id | bigint | ID de la página |
| token | varchar(64) | Token único |
| expires_at | timestamp | Fecha de expiración |
| created_by | bigint | ID del usuario creador |
| viewed_count | int | Número de vistas |
| last_viewed_at | timestamp | Última vista |
| created_at | timestamp | Fecha de creación |
| updated_at | timestamp | Fecha de actualización |

### Índices

- `token` (unique)
- `page_id, token`
- `expires_at`
- `created_by`
