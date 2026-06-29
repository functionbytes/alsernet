# Preview System - Quick Start

## Guía Rápida de Uso

### Desde el Panel de Administración

1. **Editar una página**: Ve a `/admin/pages/{id}/edit`
2. **Encontrar la sección "Vista Previa"**: Tarjeta azul con ícono de ojo
3. **Generar Preview**: Clic en "Generar Preview"
4. **Copiar enlace**: El sistema genera un enlace seguro que puedes compartir
5. **Compartir**: Envía el enlace a quien necesite revisar la página

### Desde el Código

```php
use Modules\Page\Models\Page;

$page = Page::find(1);

// Generar token de preview (válido 24 horas)
$token = $page->generatePreviewToken();

// Obtener URL para compartir
$url = $token->getPreviewUrl();

// Enviar por email, Slack, etc.
```

## Características Principales

- Tokens seguros de 64 caracteres
- Expiración automática (24h por defecto)
- Seguimiento de vistas
- Banner visual distintivo
- Limpieza automática de tokens expirados

## Comandos Útiles

```bash
# Ver comandos disponibles
php artisan list | grep page:

# Limpiar tokens expirados
php artisan page:cleanup-preview-tokens --force

# Ver estadísticas de tokens
php artisan page:cleanup-preview-tokens -v
```

## Rutas

| Ruta | Método | Descripción |
|------|--------|-------------|
| `/preview/{slug}/{token}` | GET | Ver preview público |
| `/admin/pages/{page}/preview/generate` | POST | Generar token |
| `/admin/pages/{page}/preview` | GET | Listar tokens |
| `/admin/pages/{page}/preview/revoke` | POST | Revocar tokens |

## Seguridad

- Los enlaces expiran automáticamente
- Tokens únicos e impredecibles
- Revocación manual disponible
- Formularios deshabilitados en preview

## Más Información

Ver documentación completa en `PREVIEW_SYSTEM.md`
