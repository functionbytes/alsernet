# Help Center - Instrucciones de Setup

## Paso 1: Ejecutar Migraciones

```bash
php artisan migrate
```

Esto creará las siguientes tablas:
- `helpdesk_helpcenter_categories` - Categorías y secciones
- `helpdesk_helpcenter_articles` - Artículos del help center
- `helpdesk_helpcenter_category_article` - Relación entre categorías y artículos

## Paso 2: Poblar con Datos de Ejemplo (Opcional)

```bash
php artisan db:seed --class=HelpCenterSeeder
```

Esto creará:
- ✓ 3 Categorías principales
- ✓ 4 Secciones
- ✓ 7 Artículos de ejemplo

## Paso 3: Acceder al Help Center

Visita: **https://alsernet.test/manager/helpdesk/helpcenter**

O desde el menú: **Settings > Helpdesk > Centro de Ayuda**

## Estructura Creada

```
📁 Primeros Pasos
  └─ 📂 Instalación
      └─ 📄 Cómo instalar el sistema
      └─ 📄 Configuración del servidor
  └─ 📂 Tutoriales Básicos
      └─ 📄 Primeros pasos con el sistema
      └─ 📄 Navegación básica

📁 Configuración
  └─ 📂 Configuración General
      └─ 📄 Ajustes básicos del sistema
  └─ 📂 Seguridad
      └─ 📄 Gestión de permisos
      └─ 📄 Configuración de autenticación (Borrador)

📁 Preguntas Frecuentes
  └─ (Vacía - para probar la creación manual)
```

## Funcionalidades Disponibles

1. **Drag & Drop** - Arrastra para reordenar categorías, secciones y artículos
2. **Navegación jerárquica** - Navega entre categorías → secciones → artículos
3. **CRUD completo** - Crear, editar y eliminar elementos
4. **Estados** - Artículos pueden ser borradores o publicados
5. **Breadcrumbs** - Navegación visual de la ubicación actual

## Solución de Problemas

Si ves errores:

1. **Error de ruta**: Limpia la caché de rutas
   ```bash
   php artisan route:clear
   php artisan cache:clear
   ```

2. **Error de tabla**: Asegúrate de ejecutar las migraciones
   ```bash
   php artisan migrate:fresh
   php artisan db:seed --class=HelpCenterSeeder
   ```

3. **Error 500**: Verifica los logs en `storage/logs/laravel.log`
