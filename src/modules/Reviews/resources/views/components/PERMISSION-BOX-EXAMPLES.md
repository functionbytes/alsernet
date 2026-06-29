# Componente Permission Box

Componente Blade reutilizable para mostrar cajas de información con permisos, características o listas.

## Ubicación
`modules/Reviews/resources/views/components/permission-box.blade.php`

## Props disponibles

| Prop | Tipo | Default | Descripción |
|------|------|---------|-------------|
| `title` | string | "¿Qué permisos se solicitan?" | Título de la caja |
| `icon` | string | "fas fa-shield-alt" | Clase de icono FontAwesome |
| `iconColor` | string | "text-info" | Color del icono |
| `permissions` | array | [] | Array de textos a mostrar como lista |
| `variant` | string | "light" | Color de fondo: light, primary, success, warning, info |

## Ejemplos de uso

### 1. Uso básico (permisos por defecto)
```blade
<x-reviews::permission-box
    :permissions="[
        'Lectura de ubicaciones de Google My Business',
        'Lectura de reseñas de clientes',
        'Capacidad de responder a reseñas'
    ]"
/>
```

### 2. Con título e ícono personalizados
```blade
<x-reviews::permission-box
    title="Características incluidas"
    icon="fas fa-star"
    iconColor="text-warning"
    :permissions="[
        'Sincronización automática',
        'Respuestas personalizadas',
        'Análisis avanzado'
    ]"
/>
```

### 3. Con variante de color diferente
```blade
<x-reviews::permission-box
    title="Configuración requerida"
    icon="fas fa-cog"
    iconColor="text-primary"
    variant="primary"
    :permissions="[
        'Credenciales de Google Cloud',
        'URL de redirección autorizada',
        'APIs habilitadas'
    ]"
/>
```

### 4. Sin ícono
```blade
<x-reviews::permission-box
    title="Pasos a seguir"
    :icon="false"
    :permissions="[
        'Completa el formulario',
        'Autoriza el acceso',
        'Selecciona ubicaciones'
    ]"
/>
```

### 5. Con contenido personalizado (usando slot)
```blade
<x-reviews::permission-box
    title="Nota importante"
    icon="fas fa-exclamation-triangle"
    iconColor="text-warning"
    variant="warning"
>
    <p class="mb-0">
        Asegúrate de tener los permisos adecuados en tu cuenta de Google
        antes de continuar con el proceso de conexión.
    </p>
</x-reviews::permission-box>
```

### 6. Con clases adicionales
```blade
<x-reviews::permission-box
    class="shadow-sm border"
    :permissions="['Permiso 1', 'Permiso 2']"
/>
```

### 7. Variantes de colores
```blade
<!-- Fondo claro (default) -->
<x-reviews::permission-box variant="light" :permissions="[...]" />

<!-- Fondo primario -->
<x-reviews::permission-box variant="primary" :permissions="[...]" />

<!-- Fondo success -->
<x-reviews::permission-box variant="success" :permissions="[...]" />

<!-- Fondo warning -->
<x-reviews::permission-box variant="warning" :permissions="[...]" />

<!-- Fondo info -->
<x-reviews::permission-box variant="info" :permissions="[...]" />
```

## Iconos comunes de FontAwesome

```blade
<!-- Seguridad -->
icon="fas fa-shield-alt"

<!-- Configuración -->
icon="fas fa-cog"

<!-- Información -->
icon="fas fa-info-circle"

<!-- Advertencia -->
icon="fas fa-exclamation-triangle"

<!-- Check/Éxito -->
icon="fas fa-check-circle"

<!-- Estrella/Premium -->
icon="fas fa-star"

<!-- Lista -->
icon="fas fa-list-ul"

<!-- Ubicación -->
icon="fas fa-map-marker-alt"

<!-- Usuario -->
icon="fas fa-user-shield"
```

## Combinaciones recomendadas

### Permisos de seguridad
```blade
<x-reviews::permission-box
    title="Permisos necesarios"
    icon="fas fa-shield-alt"
    iconColor="text-info"
    variant="light"
/>
```

### Características premium
```blade
<x-reviews::permission-box
    title="Características premium"
    icon="fas fa-star"
    iconColor="text-warning"
    variant="warning"
/>
```

### Requisitos técnicos
```blade
<x-reviews::permission-box
    title="Requisitos técnicos"
    icon="fas fa-cog"
    iconColor="text-primary"
    variant="primary"
/>
```

### Advertencias importantes
```blade
<x-reviews::permission-box
    title="Advertencia"
    icon="fas fa-exclamation-triangle"
    iconColor="text-danger"
    variant="light"
/>
```
