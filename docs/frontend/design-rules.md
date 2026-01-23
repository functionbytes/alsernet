# Frontend Design Rules - Alsernet

Estas son las reglas estándar que el agente frontend debe seguir al crear o modificar componentes y vistas en Alsernet.

## 1. Select Elements - Select2 Integration

### Regla Principal
**Todo elemento `<select>` DEBE tener la clase `select2`**

Esto asegura que:
- Los selects tengan una interfaz visual consistente
- La búsqueda funcione correctamente en listas largas
- La validación con jQuery Validate funcione sin problemas
- Los estilos de Bootstrap se apliquen correctamente

### Implementación

#### HTML - Estructura correcta:
```blade
<select class="form-select select2 @error('campo') is-invalid @enderror"
        id="idDelSelect"
        name="campo"
        data-placeholder="Seleccionar opción..."
        required>
    <option value=""></option>
    <option value="valor1">Opción 1</option>
    <option value="valor2">Opción 2</option>
</select>
```

#### Clases OBLIGATORIAS:
- `form-select` - Clase Bootstrap para selects
- `select2` - Inicializador de Select2
- `@error()` - Validación de Laravel Blade

#### Atributos RECOMENDADOS:
- `data-placeholder` - Texto cuando no hay selección
- `required` - Validación HTML5
- `id` - Para JavaScript y testing

### JavaScript - Inicialización

Todo select debe inicializarse en el documento ready:

```javascript
$(document).ready(function() {
    // Inicializar todos los Select2
    $('.select2').select2({
        allowClear: false,
        language: {
            noResults: function() {
                return 'Sin resultados';
            },
            searching: function() {
                return 'Buscando...';
            }
        }
    });
});
```

### Validación con jQuery Validate

Si el formulario usa jQuery Validate, agregar en las reglas:

```javascript
$('#formName').validate({
    rules: {
        campo: {
            required: true
        }
    },
    highlight: function(element) {
        $(element).addClass('is-invalid');

        // Para Select2
        if ($(element).hasClass('select2')) {
            $(element).next('.select2-container')
                .find('.select2-selection')
                .addClass('is-invalid');
        }
    },
    unhighlight: function(element) {
        $(element).removeClass('is-invalid');

        // Para Select2
        if ($(element).hasClass('select2')) {
            $(element).next('.select2-container')
                .find('.select2-selection')
                .removeClass('is-invalid');
        }
    },
    errorPlacement: function(error, element) {
        error.addClass('field-validation-error');

        // Colocar error después del contenedor Select2
        if ($(element).hasClass('select2')) {
            error.insertAfter(element.next('.select2-container'));
        } else {
            error.insertAfter(element);
        }
    }
});

// Validar Select2 al cambiar
$('.select2').on('change', function() {
    $(this).valid();
});
```

## 2. Responsive Design - Mobile First

### Regla Obligatoria: Responsive Grid en Formularios

**SIEMPRE que uses `row` en un formulario, DEBES hacer responsive**

#### Para dos columnas (col-6):
```blade
<!-- ❌ INCORRECTO - No responsive en mobile -->
<div class="row">
    <div class="col-6">
        <!-- campo -->
    </div>
    <div class="col-6">
        <!-- campo -->
    </div>
</div>

<!-- ✅ CORRECTO - Responsive -->
<div class="row">
    <div class="col-12 col-md-6">
        <!-- campo -->
    </div>
    <div class="col-12 col-md-6">
        <!-- campo -->
    </div>
</div>
```

**Estructura:** `col-{mobile} col-md-{tablet} col-lg-{desktop}`

#### Para tres columnas (col-4):
```blade
<!-- ❌ INCORRECTO -->
<div class="col-4">

<!-- ✅ CORRECTO -->
<div class="col-12 col-md-6 col-lg-4">
```

#### Para cuatro columnas (col-3):
```blade
<!-- ✅ CORRECTO -->
<div class="col-12 col-md-6 col-lg-3">
```

### Breakpoints Bootstrap Estándar

| Clase | Ancho Pantalla | Uso |
|-------|---|---|
| `col-12` | < 576px | Mobile (default) |
| `col-sm-*` | ≥ 576px | Small devices |
| `col-md-*` | ≥ 768px | Tablets |
| `col-lg-*` | ≥ 992px | Desktop |
| `col-xl-*` | ≥ 1200px | Large desktop |

### Ejemplo Completo de Formulario Responsive

```blade
<form method="POST" action="">
    <div class="row">
        <!-- Dos campos lado a lado en desktop, uno debajo del otro en mobile -->
        <div class="col-12 col-md-6">
            <div class="mb-3">
                <label class="form-label">Campo 1</label>
                <input type="text" class="form-control" name="campo1">
            </div>
        </div>
        <div class="col-12 col-md-6">
            <div class="mb-3">
                <label class="form-label">Campo 2</label>
                <input type="text" class="form-control" name="campo2">
            </div>
        </div>

        <!-- Tres campos: 1 en mobile, 2 en tablet, 3 en desktop -->
        <div class="col-12 col-md-6 col-lg-4">
            <div class="mb-3">
                <label class="form-label">Campo 3</label>
                <input type="text" class="form-control" name="campo3">
            </div>
        </div>
        <div class="col-12 col-md-6 col-lg-4">
            <div class="mb-3">
                <label class="form-label">Campo 4</label>
                <input type="text" class="form-control" name="campo4">
            </div>
        </div>
        <div class="col-12 col-md-6 col-lg-4">
            <div class="mb-3">
                <label class="form-label">Campo 5</label>
                <input type="text" class="form-control" name="campo5">
            </div>
        </div>

        <!-- Campo full-width -->
        <div class="col-12">
            <div class="mb-3">
                <label class="form-label">Descripción (Full Width)</label>
                <textarea class="form-control" name="descripcion"></textarea>
            </div>
        </div>

        <!-- Botones -->
        <div class="col-12">
            <button type="submit" class="btn btn-primary w-100">Guardar</button>
        </div>
    </div>
</form>
```

### Atajo de Referencia Rápida

```
col-6  → col-12 col-md-6
col-4  → col-12 col-md-6 col-lg-4
col-3  → col-12 col-md-6 col-lg-3
col-8  → col-12 col-md-8
col-9  → col-12 col-md-9
col-12 → col-12 (ya full-width)
```

### Testing de Responsive

Siempre probar:
1. **Mobile** (< 576px) - Un campo por línea
2. **Tablet** (768px - 991px) - Dos campos por línea
3. **Desktop** (≥ 992px) - Según diseño

---

## 2. Bootstrap Classes - Standard Usage

### Grid System
- Use `row` y `col-*` para layouts
- SIEMPRE hacer responsive con breakpoints
- Mobile first: empezar con `col-12`, luego agregar `col-md-*`
- Use `col-12` para ancho completo

### Spacing
- `mb-3` para margins inferiores entre secciones
- `mt-2` para espacios pequeños
- `p-3` para padding general

### Cards
- Use `card` para contenedores
- `card-header` para títulos
- `card-body` para contenido

### Colores
- `btn-primary` para acciones principales
- `btn-secondary` para acciones secundarias
- `btn-danger` para acciones destructivas
- `text-muted` para texto secundario

## 3. Form Controls

### Text Inputs
```blade
<input type="text"
       class="form-control @error('campo') is-invalid @enderror"
       name="campo"
       value="{{ old('campo', $value) }}"
       required>
@error('campo')
    <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
@enderror
```

### Required Fields
- Agregar `<span class="text-danger">*</span>` en el label
- Agregar atributo `required` en el input

### Error Messages
- Usar `@error()` de Blade
- Clase `field-validation-error` para estilos
- Icono `fa fa-circle-exclamation` para consistencia

## 4. Validation Rules

### Para formularios con jQuery Validate:
1. Validar en el lado del cliente (UX rápida)
2. Validar en el servidor (seguridad)
3. Mostrar errores de forma clara y específica

### Mensajes de error personalizados:
```javascript
messages: {
    campo: {
        required: 'Este campo es obligatorio',
        minlength: 'Mínimo {0} caracteres',
        maxlength: 'Máximo {0} caracteres'
    }
}
```

## 5. Icons - Tabler Icons

Usar Tabler Icons para consistencia:
- `fa fa-circle-check` para confirmación
- `fa fa-circle-exclamation` para errores
- `fa fa-circle-info` para información
- `fa fa-spinner` para carga (agregar `animate-spin`)

## Ejemplos en el Codebase

### ✅ CORRECTO - Database Settings Form
`resources/views/managers/views/settings/database/edit.blade.php`

Elementos select con clase `select2`:
```blade
<select class="form-select select2 @error('db_connection') is-invalid @enderror"
        id="dbConnection"
        name="db_connection"
        required>
```

Validación en JavaScript:
```javascript
$('#dbConnection, #dbCharset, #dbCollation').select2({...});
$('#formDatabase').validate({...});
```

## Regla de Oro

> **Si creas o modificas un `<select>`, SIEMPRE agrega la clase `select2`**

Esto es no negociable y asegura:
- Experiencia de usuario consistente
- Búsqueda en selects funcional
- Validación confiable
- Mantenimiento más fácil
