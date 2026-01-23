# Patrón de Dropdown Menu para Acciones en Tablas

## Overview

El patrón de dropdown menu con Bootstrap 5.3 proporciona una interfaz limpia y moderna para acciones en tablas, reemplazando múltiples botones individuales con un menú compacto.

## Cuándo usar este patrón

✅ **Usar dropdown cuando:**
- Tienes 3 o más acciones por fila
- El espacio horizontal es limitado
- Quieres una interfaz más limpia y profesional
- Las acciones no son todas igualmente importantes

❌ **Usar botones individuales cuando:**
- Solo hay 1-2 acciones críticas
- Las acciones necesitan ser inmediatamente visibles
- El usuario necesita ejecutar acciones frecuentemente sin clicks extra

## Implementación básica

### HTML estático (Blade)

```blade
<div class="dropdown dropstart">
    <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="fa fa-ellipsis"></i>
    </a>
    <ul class="dropdown-menu">
        <li>
            <a href="{{ route('resource.edit', $item->id) }}" class="dropdown-item">
                <i class="fa fa-pen-to-square me-2"></i> Editar
            </a>
        </li>
        <li>
            <a href="{{ route('resource.show', $item->id) }}" class="dropdown-item">
                <i class="fa fa-eye me-2"></i> Ver detalles
            </a>
        </li>
        <li><hr class="dropdown-divider"></li>
        <li>
            <a href="javascript:void(0)" class="dropdown-item text-danger"
               onclick="confirmDelete({{ $item->id }})">
                <i class="fa fa-trash me-2"></i> Eliminar
            </a>
        </li>
    </ul>
</div>
```

### JavaScript dinámico (jQuery)

```javascript
function loadItems() {
    $.ajax({
        url: '/api/items',
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            var tbody = $('#itemsTable');
            var html = '';

            $.each(data.items, function(index, item) {
                html += '<tr>';
                html += '<td>' + item.name + '</td>';
                html += '<td>' + item.status + '</td>';
                html += '<td>';
                html += '    <div class="dropdown dropstart">';
                html += '        <a href="#" class="text-muted" data-bs-toggle="dropdown">';
                html += '            <i class="fa fa-ellipsis"></i>';
                html += '        </a>';
                html += '        <ul class="dropdown-menu">';
                html += '            <li>';
                html += '                <a href="javascript:void(0)" class="dropdown-item"';
                html += '                   onclick="editItem(' + item.id + ')">';
                html += '                    <i class="fa fa-pen-to-square me-2"></i> Editar';
                html += '                </a>';
                html += '            </li>';
                html += '            <li>';
                html += '                <a href="/items/' + item.id + '/download"';
                html += '                   class="dropdown-item">';
                html += '                    <i class="fa fa-download me-2"></i> Descargar';
                html += '                </a>';
                html += '            </li>';
                html += '            <li><hr class="dropdown-divider"></li>';
                html += '            <li>';
                html += '                <a href="javascript:void(0)"';
                html += '                   class="dropdown-item text-danger"';
                html += '                   onclick="deleteItem(' + item.id + ')">';
                html += '                    <i class="fa fa-trash me-2"></i> Eliminar';
                html += '                </a>';
                html += '            </li>';
                html += '        </ul>';
                html += '    </div>';
                html += '</td>';
                html += '</tr>';
            });

            tbody.html(html);
        }
    });
}
```

## Variantes del dropdown

### 1. Dropstart (menú a la izquierda)
```html
<div class="dropdown dropstart">
    <!-- Ideal para última columna de tablas -->
</div>
```

### 2. Dropend (menú a la derecha)
```html
<div class="dropdown dropend">
    <!-- Ideal para primera columna de tablas -->
</div>
```

### 3. Dropdown normal (hacia abajo)
```html
<div class="dropdown">
    <!-- Default, menú hacia abajo -->
</div>
```

### 4. Dropup (hacia arriba)
```html
<div class="dropdown dropup">
    <!-- Ideal para últimas filas de tablas largas -->
</div>
```

## Iconos para acciones comunes

| Acción | Icono Font Awesome | Clase |
|--------|-------------------|-------|
| Editar | `<i class="fa fa-pen-to-square">` | - |
| Ver/Detalles | `<i class="fa fa-eye">` | - |
| Restaurar | `<i class="fa fa-arrows-rotate">` | - |
| Descargar | `<i class="fa fa-download">` | - |
| Duplicar | `<i class="fa fa-copy">` | - |
| Archivar | `<i class="fa fa-box-archive">` | - |
| Compartir | `<i class="fa fa-share-nodes">` | - |
| Eliminar | `<i class="fa fa-trash">` | `text-danger` |
| Bloquear | `<i class="fa fa-lock">` | `text-warning` |

## Separadores y agrupación

Usa `<hr class="dropdown-divider">` para agrupar acciones lógicamente:

```html
<ul class="dropdown-menu">
    <!-- Acciones principales -->
    <li><a class="dropdown-item">Editar</a></li>
    <li><a class="dropdown-item">Ver</a></li>

    <li><hr class="dropdown-divider"></li>

    <!-- Acciones secundarias -->
    <li><a class="dropdown-item">Descargar</a></li>
    <li><a class="dropdown-item">Duplicar</a></li>

    <li><hr class="dropdown-divider"></li>

    <!-- Acciones destructivas -->
    <li><a class="dropdown-item text-danger">Eliminar</a></li>
</ul>
```

## Estilos de acciones

### Acción normal
```html
<a class="dropdown-item">
    <i class="fa fa-eye me-2"></i> Ver
</a>
```

### Acción de advertencia
```html
<a class="dropdown-item text-warning">
    <i class="fa fa-exclamation-triangle me-2"></i> Advertencia
</a>
```

### Acción peligrosa
```html
<a class="dropdown-item text-danger">
    <i class="fa fa-trash me-2"></i> Eliminar
</a>
```

### Acción deshabilitada
```html
<a class="dropdown-item disabled" aria-disabled="true">
    <i class="fa fa-lock me-2"></i> No disponible
</a>
```

## Mejores prácticas

1. **Siempre usa iconos** - Mejoran el reconocimiento visual
2. **Espacio con `me-2`** - Separa el icono del texto consistentemente
3. **Acciones destructivas al final** - Después de un separador
4. **Máximo 6-8 acciones** - Más allá de esto, considera sub-menús
5. **Texto descriptivo** - Usa verbos claros: "Editar", "Eliminar", no "Opciones"
6. **Color para destructivas** - `text-danger` para acciones irreversibles
7. **Confirmación para destructivas** - Siempre usa `confirm()` o modal

## Archivos de ejemplo en el proyecto

### Implementado en:
- `resources/views/managers/views/settings/supervisor/index.blade.php` (línea 729-750)
  - Dropdown para backups con Restaurar, Descargar, Eliminar

### Pendiente de implementar:
- Tablas de roles (`resources/views/managers/views/settings/roles/`)
- Tablas de usuarios (`resources/views/managers/views/users/`)
- Tablas de documentos (`resources/views/managers/views/documents/`)

## Conversión de botones a dropdown

### Antes (múltiples botones):
```html
<td>
    <button class="btn btn-sm btn-primary" onclick="edit()">
        <i class="fa fa-pen"></i>
    </button>
    <button class="btn btn-sm btn-info" onclick="view()">
        <i class="fa fa-eye"></i>
    </button>
    <button class="btn btn-sm btn-danger" onclick="delete()">
        <i class="fa fa-trash"></i>
    </button>
</td>
```

### Después (dropdown):
```html
<td>
    <div class="dropdown dropstart">
        <a href="#" class="text-muted" data-bs-toggle="dropdown">
            <i class="fa fa-ellipsis"></i>
        </a>
        <ul class="dropdown-menu">
            <li><a class="dropdown-item" onclick="edit()">
                <i class="fa fa-pen-to-square me-2"></i> Editar
            </a></li>
            <li><a class="dropdown-item" onclick="view()">
                <i class="fa fa-eye me-2"></i> Ver
            </a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item text-danger" onclick="delete()">
                <i class="fa fa-trash me-2"></i> Eliminar
            </a></li>
        </ul>
    </div>
</td>
```

**Ventajas:**
- Reduce ancho de columna de ~150px a ~30px
- Interfaz más limpia y profesional
- Escalable - fácil agregar más acciones
- Consistente con diseño moderno

## Debugging

### El dropdown no se abre
- Verificar que Bootstrap JS está cargado
- Verificar `data-bs-toggle="dropdown"` está presente
- Verificar que no hay errores JavaScript en consola

### El menú se corta por el borde de la tabla
- Usar `dropstart` en lugar de `dropdown`
- Agregar `overflow-x: visible` al contenedor de la tabla
- Usar `data-bs-boundary="viewport"`

### Los clicks no funcionan en contenido dinámico
- Asegurar que los dropdowns se inicializan después de cargar el HTML
- Alternativamente, usar delegación de eventos con jQuery

## Última actualización

Fecha: 2025-12-09
Responsable: Claude Code
