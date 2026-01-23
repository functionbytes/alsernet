# Estándares de notificaciones con Toastr

## Regla general

**TODAS las notificaciones/alertas dinámicas deben usar Toastr.**

## Configuración obligatoria

Siempre configurar toastr al inicio del archivo JavaScript con:

```javascript
toastr.options = {
    closeButton: true,
    progressBar: true,
    positionClass: "toast-bottom-right"
};
```

## Por qué Toastr

1. **Consistencia**: Todas las notificaciones aparecen en la misma posición
2. **No invasivo**: Las notificaciones no bloquean el contenido de la página
3. **Experiencia de usuario**: Mejor UX con animaciones suaves y cierre automático
4. **Simplicidad**: Código más limpio y mantenible
5. **Personalizable**: Control sobre posición, duración, y comportamiento

## Tipos de notificaciones

### ✅ Success (Éxito)
```javascript
toastr.success('Operación completada correctamente', 'Éxito');
```

**Cuándo usar:**
- Operación completada exitosamente
- Guardado de datos confirmado
- Eliminación exitosa
- Actualización correcta

### ❌ Error
```javascript
toastr.error('No se pudo completar la operación', 'Error');
```

**Cuándo usar:**
- Operación fallida
- Error de validación
- Error del servidor
- Conexión perdida

### ⚠️ Warning (Advertencia)
```javascript
toastr.warning('Algunos comandos presentaron errores', 'Advertencia');
```

**Cuándo usar:**
- Operación completada con advertencias
- Datos incompletos pero aceptables
- Acciones que requieren atención

### ℹ️ Info (Información)
```javascript
toastr.info('El proceso puede tardar unos minutos', 'Información');
```

**Cuándo usar:**
- Mensajes informativos
- Estado de procesos en curso
- Consejos y sugerencias

## Estructura del mensaje

```javascript
toastr.tipo(mensaje, título, opciones_adicionales);
```

- **mensaje** (string): Descripción de lo que ocurrió
- **título** (string): Título de la notificación
- **opciones_adicionales** (object, opcional): Configuración específica para esta notificación

## Ejemplos de uso en el proyecto

### ✅ Correcto - Con Toastr

```javascript
// Configuración al inicio
toastr.options = {
    closeButton: true,
    progressBar: true,
    positionClass: "toast-bottom-right"
};

// Uso en respuesta AJAX
fetch('/api/endpoint', {
    method: 'POST',
    // ...
})
.then(response => response.json())
.then(data => {
    if (data.success) {
        toastr.success(data.message, 'Operación exitosa');
    } else {
        toastr.error(data.message, 'Error en la operación');
    }
})
.catch(error => {
    toastr.error('Error en la solicitud: ' + error.message, 'Error');
});
```

### ❌ Incorrecto - Alertas HTML

```javascript
// NO USAR - Alertas HTML
const alertHtml = `
    <div class="alert alert-success">
        ${message}
    </div>
`;
document.getElementById('container').innerHTML = alertHtml;
```

## Opciones de configuración disponibles

### Opciones globales (establecer una vez)

```javascript
toastr.options = {
    // Opciones obligatorias del proyecto
    closeButton: true,          // Mostrar botón de cerrar
    progressBar: true,          // Mostrar barra de progreso
    positionClass: "toast-bottom-right",  // Posición fija

    // Opciones adicionales disponibles
    timeOut: 5000,             // Duración en ms (0 = permanente)
    extendedTimeOut: 1000,     // Tiempo extra al hover
    preventDuplicates: false,  // Prevenir notificaciones duplicadas
    newestOnTop: true,         // Nuevas notificaciones arriba
    showEasing: 'swing',       // Animación de entrada
    hideEasing: 'linear',      // Animación de salida
    showMethod: 'fadeIn',      // Método de mostrar
    hideMethod: 'fadeOut'      // Método de ocultar
};
```

### Opciones por notificación individual

```javascript
// Notificación que no se cierra automáticamente
toastr.info('Este mensaje permanece hasta que lo cierres', 'Importante', {
    timeOut: 0,
    extendedTimeOut: 0
});

// Notificación con callback al cerrar
toastr.success('Guardado correctamente', 'Éxito', {
    onHidden: function() {
        window.location.href = '/dashboard';
    }
});
```

## Posiciones disponibles

```javascript
positionClass: "toast-top-right"       // Arriba derecha
positionClass: "toast-top-left"        // Arriba izquierda
positionClass: "toast-bottom-right"    // Abajo derecha (OBLIGATORIA)
positionClass: "toast-bottom-left"     // Abajo izquierda
positionClass: "toast-top-center"      // Arriba centro
positionClass: "toast-bottom-center"   // Abajo centro
```

**Nota:** En este proyecto SIEMPRE usar `toast-bottom-right`

## Integración con formularios

```javascript
// Ejemplo completo de formulario con validación
const form = document.getElementById('myForm');

form.addEventListener('submit', function(e) {
    e.preventDefault();

    const submitButton = this.querySelector('button[type="submit"]');
    const originalContent = submitButton.innerHTML;

    // Deshabilitar botón y mostrar loading
    submitButton.disabled = true;
    submitButton.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Guardando...';

    const formData = new FormData(this);

    fetch('/api/save', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            toastr.success(data.message, 'Guardado exitoso');

            // Opcional: redireccionar después de mostrar mensaje
            setTimeout(() => {
                window.location.href = '/dashboard';
            }, 2000);
        } else {
            toastr.error(data.message, 'Error al guardar');
        }
    })
    .catch(error => {
        toastr.error('Error en la solicitud: ' + error.message, 'Error');
    })
    .finally(() => {
        // Restaurar botón
        submitButton.disabled = false;
        submitButton.innerHTML = originalContent;
    });
});
```

## Patrón para operaciones múltiples

Cuando ejecutas múltiples operaciones y quieres mostrar un resumen:

```javascript
function displayAllResults(data) {
    // Mensaje principal con toastr
    if (data.success) {
        toastr.success(data.message, 'Operación completada');
    } else {
        toastr.warning(data.message, 'Completado con advertencias');
    }

    // Detalles adicionales en una tabla o elemento HTML
    if (data.results) {
        const resultsContainer = document.getElementById('results-area');
        resultsContainer.innerHTML = generarTablaResultados(data.results);
        resultsContainer.style.display = 'block';
    }
}
```

## Mensajes de sesión Laravel

Para mensajes flash de sesión, mantener las alertas HTML estáticas en el blade:

```blade
@if ($message = session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fa fa-check me-2"></i> {{ $message }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
```

**Razón:** Estos mensajes son estáticos (no dinámicos) y aparecen al cargar la página.

## Migración de código existente

### Antes (HTML alerts)
```javascript
const container = document.getElementById('notifications-container');
container.innerHTML = `
    <div class="alert alert-success">
        <strong>Éxito</strong>
        <p>${message}</p>
    </div>
`;
```

### Después (Toastr)
```javascript
toastr.success(message, 'Éxito');
```

## Verificación

Lista de verificación antes de hacer commit:

1. ✅ Configuración de toastr al inicio del script
2. ✅ `closeButton: true`
3. ✅ `progressBar: true`
4. ✅ `positionClass: "toast-bottom-right"`
5. ✅ No hay alertas HTML dinámicas en JavaScript
6. ✅ Todos los fetch/AJAX usan toastr para notificaciones
7. ✅ Manejo correcto de errores con toastr.error()

## Archivos actualizados

- ✅ `resources/views/managers/views/settings/system/index.blade.php`
- ✅ `resources/views/managers/views/settings/maintenance/index.blade.php`

### Patrón aplicado en maintenance/index.blade.php

Este archivo implementa un patrón especial para operaciones múltiples:

1. **Configuración toastr** - Al inicio del script
2. **Formulario de mantenimiento** - Toastr para éxito/error + redirección
3. **Operaciones individuales de caché** - Toastr simple para cada operación
4. **Ejecutar todas las operaciones** - Toastr para mensaje principal + tabla HTML para detalles

```javascript
function displayAllResults(data) {
    // Mensaje principal con toastr
    if (data.success) {
        toastr.success(data.message, 'Operación completada');
    } else {
        toastr.warning(data.message, 'Operación completada con advertencias');
    }

    // Detalles en tabla HTML
    if (data.results) {
        const resultsContainer = document.getElementById('execute-all-results');
        resultsContainer.innerHTML = generarTablaResultados(data.results);
        resultsContainer.style.display = 'block';
    }
}
```

## Recursos

- Documentación oficial: https://github.com/CodeSeven/toastr
- Demo interactiva: https://codeseven.github.io/toastr/demo.html

## Última actualización

Fecha: 2025-12-08
Responsable: Claude Code
