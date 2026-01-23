# Estándares de iconos para AlserNet

## Regla general

**TODOS los iconos en el proyecto deben usar Font Awesome.**

## Por qué Font Awesome

1. **Consistencia visual**: Un único sistema de iconos en toda la aplicación
2. **Amplia biblioteca**: Más de 2,000 iconos gratuitos disponibles
3. **Compatibilidad**: Funciona perfectamente con Bootstrap 5.3
4. **Documentación**: Excelente documentación en https://fontawesome.com/icons

## Uso correcto

### ✅ Correcto - Font Awesome
```html
<!-- Iconos sólidos (más comunes) -->
<i class="fa fa-check"></i>
<i class="fa-solid fa-circle-info"></i>

<!-- Iconos regulares -->
<i class="fa-regular fa-heart"></i>

<!-- Iconos de marca -->
<i class="fa-brands fa-github"></i>
```

### ❌ Incorrecto - Tabler Icons
```html
<!-- NO USAR -->
<i class="ti ti-check"></i>
<i class="ti ti-info-circle"></i>
```

## Equivalencias de iconos migrados

Cuando migres código con Tabler Icons, usa estas equivalencias:

| Tabler Icons | Font Awesome |
|--------------|--------------|
| `ti ti-info-circle` | `fa fa-circle-info` |
| `ti ti-alert-triangle` | `fa fa-triangle-exclamation` |
| `ti ti-check` | `fa fa-check` |
| `ti ti-x` | `fa fa-xmark` |
| `ti ti-loader` | `fa fa-spinner` |
| `ti ti-settings` | `fa fa-gear` |
| `ti ti-list-check` | `fa fa-list-check` |
| `ti ti-wifi` | `fa fa-wifi` |

## Buscar iconos

1. Visita https://fontawesome.com/icons
2. Busca por nombre o categoría
3. Copia la clase CSS del icono
4. Usa el formato: `<i class="fa fa-{nombre}"></i>`

## Iconos con animación

Font Awesome incluye clases de animación:

```html
<!-- Spinner giratorio -->
<i class="fa fa-spinner fa-spin"></i>

<!-- Pulso -->
<i class="fa fa-heart fa-beat"></i>

<!-- Rebote -->
<i class="fa fa-exclamation fa-bounce"></i>
```

## Tamaños de iconos

```html
<!-- Tamaños relativos -->
<i class="fa fa-home fa-xs"></i>
<i class="fa fa-home fa-sm"></i>
<i class="fa fa-home fa-lg"></i>
<i class="fa fa-home fa-2x"></i>
<i class="fa fa-home fa-3x"></i>

<!-- Tamaños con clases de Bootstrap -->
<i class="fa fa-home fs-1"></i>  <!-- Muy grande -->
<i class="fa fa-home fs-5"></i>  <!-- Mediano -->
<i class="fa fa-home fs-6"></i>  <!-- Pequeño -->
```

## Colores

Usa las clases de utilidad de Bootstrap:

```html
<!-- Colores de texto -->
<i class="fa fa-check text-success"></i>
<i class="fa fa-times text-danger"></i>
<i class="fa fa-info text-primary"></i>
<i class="fa fa-exclamation text-warning"></i>

<!-- Color personalizado -->
<i class="fa fa-heart text-dark"></i>
```

## Ejemplos de uso en el proyecto

### En alertas
```html
<div class="alert alert-info">
    <i class="fa fa-circle-info me-2"></i>
    Este es un mensaje informativo
</div>
```

### En botones
```html
<button class="btn btn-primary">
    <i class="fa fa-save me-2"></i> Guardar
</button>

<button class="btn btn-light btn-sm">
    <i class="fa-solid fa-play"></i>
</button>
```

### En navegación
```html
<a href="#" class="nav-link">
    <i class="fa fa-home me-2"></i>
    <span>Dashboard</span>
</a>
```

### En loading/spinner
```html
<button disabled>
    <i class="fa fa-spinner fa-spin me-2"></i> Cargando...
</button>
```

## Verificación

Antes de hacer commit, verifica que:

1. ✅ No haya iconos de Tabler (`ti ti-*`)
2. ✅ Todos los iconos usen Font Awesome (`fa fa-*`)
3. ✅ Los iconos tengan el tamaño apropiado
4. ✅ Los colores sean consistentes con el diseño

## Archivos actualizados

- ✅ `resources/views/managers/views/settings/system/index.blade.php`
- ✅ `resources/views/managers/views/settings/maintenance/index.blade.php`
- ✅ `resources/views/managers/views/settings/backups/create.blade.php`
- ✅ `resources/views/managers/views/settings/backups/schedules/index.blade.php`
- ✅ `resources/views/managers/views/settings/backups/schedules/create.blade.php`
- ✅ `resources/views/managers/views/settings/backups/schedules/edit.blade.php`
- ✅ `resources/views/managers/views/settings/supervisor/index.blade.php`

### Problemas corregidos en schedules

1. **Iconos Tabler** → Font Awesome
   - `ti-code` → `fa-code`
   - `ti-settings` → `fa-gear`
   - `ti-map` → `fa-map`
   - `ti-palette` → `fa-palette`
   - `ti-database` → `fa-database`
   - `ti-folder` → `fa-folder`
   - `ti-file` → `fa-file`

2. **Iconos Font Awesome Pro (Duotone)** → Estándar
   - `fa-duotone fa-magnifying-glass` → `fa fa-magnifying-glass`
   - `fa-duotone fa-plus` → `fa fa-plus`
   - `fa-duotone fa-solid fa-ellipsis` → `fa fa-ellipsis`

3. **Iconos Feather** → Font Awesome
   - `data-feather="search"` → `fa fa-magnifying-glass`

4. **Variantes de Font Awesome** → Estándar
   - `fas fa-check-circle` → `fa fa-circle-check`
   - `fas fa-times-circle` → `fa fa-circle-xmark`
   - `fas fa-calendar-times` → `fa fa-calendar-xmark`

5. **Error de tipeo corregido**
   - `fa-checkme-1` → `fa-check me-1` (icono + clase de margen)

### Problemas corregidos en supervisor

1. **Layout modernizado al estilo system/index.blade.php**
   - Agregado `managers.includes.card` para header consistente
   - Convertido nav-tabs a nav-pills user-profile-tab
   - Aplicado estructura moderna de card con border-bottom
   - Mejorado spacing y alineación de status cards con g-3
   - Cada tab tiene título + descripción + layout de dos columnas

2. **Componentes de tabs mejorados**
   - **Procesos**: Título "Gestión de procesos" + descripción + tablas organizadas
   - **Backups**: Layout de 2 columnas con cards, formulario mejorado, alert informativo
   - **Configuración**: Sidebar de archivos + editor mejorado con alert de ayuda
   - **Logs**: Selector de proceso en card + visor de logs con header oscuro

3. **Iconos Tabler** → Font Awesome
   - `ti ti-settings` → `fa fa-gear`
   - `ti ti-list` → `fa fa-list`
   - `ti ti-loader` → `fa fa-spinner fa-spin`
   - `ti ti-info-circle` → `fa fa-circle-info`

4. **Errores HTML corregidos**
   - Typos: `lass=` → `class=` (2 instancias)
   - Tags sin cerrar: `<i class="fa fa-eye>` → `<i class="fa fa-eye">` (2 instancias)
   - Error de Blade: Eliminado `@endsection` duplicado

5. **JavaScript convertido a jQuery/AJAX**
   - ✅ `fetch()` → `$.ajax()` (12 funciones convertidas)
   - ✅ `document.getElementById()` → `$('#element')`
   - ✅ `element.textContent` → `$('#element').text()`
   - ✅ `element.innerHTML` → `$('#element').html()`
   - ✅ `new FormData()` → `$('#form').serialize()`
   - ✅ `addEventListener()` → `$('#element').on()`
   - ✅ Arrow functions → Function expressions

6. **Sistema de notificaciones modernizado**
   - Función `showNotification()` personalizada → `toastr`
   - Configuración de toastr al inicio: `toastr.options = {...}`
   - Todas las notificaciones usan: `toastr.success()` / `toastr.error()`
   - Iconos Tabler en notificaciones → Font Awesome

7. **Mejoras de UX**
   - Labels con fw-semibold para mejor jerarquía visual
   - Small text para ayuda contextual en cada campo
   - Alerts con iconos y formato moderno (bg-info-subtle, etc.)
   - Botones con ancho completo (w-100) para mejor usabilidad
   - Headers de cards con bg-light y border-secondary

## Comando de búsqueda

Para encontrar iconos de Tabler en el código:

```bash
# Buscar todos los iconos de Tabler
grep -r "ti ti-" resources/views/

# Buscar en un archivo específico
grep "ti ti-" resources/views/theme/views/backups/system/v5.blade.php
```

## Última actualización

Fecha: 2025-12-09
Responsable: Claude Code
Cambios:
- Modernización completa de supervisor/index.blade.php
- Implementación de dropdown menu para acciones de backup
- Corrección de errores de sudo en SupervisorService.php
