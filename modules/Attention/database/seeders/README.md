# Attention Module - Seeders Documentation

Este documento describe todos los seeders disponibles en el módulo de Atención (PQRSF).

## Estructura de Seeders

### Seeders de Configuración Base

Estos seeders crean los datos esenciales para el funcionamiento del módulo:

#### 1. AttentionTypesSeeder
**Archivo:** `AttentionTypesSeeder.php`
**Propósito:** Crea los 5 tipos principales de PQRSF
**Datos creados:**
- P - Petición
- Q - Queja
- R - Reclamo
- S - Sugerencia
- F - Felicitación

#### 2. AttentionCategoriesSeeder
**Archivo:** `AttentionCategoriesSeeder.php`
**Propósito:** Crea las categorías temáticas para clasificar PQRSF
**Datos creados:**
- Atención al Cliente
- Facturación
- Productos y Servicios
- Quejas de Calidad
- Otros

#### 3. AttentionDepartmentsSeeder
**Archivo:** `AttentionDepartmentsSeeder.php`
**Propósito:** Crea los departamentos que gestionarán PQRSF
**Datos creados:**
- Atención al Ciudadano
- Jurídica
- Contabilidad
- Talento Humano
- Sistemas

#### 4. AttentionSedesSeeder
**Archivo:** `AttentionSedesSeeder.php`
**Propósito:** Crea las sedes donde se pueden radicar PQRSF
**Datos creados:**
- Sede Principal (Bogotá)
- Portal Web (Online)

#### 5. AttentionSlaPoliciesSeeder
**Archivo:** `AttentionSlaPoliciesSeeder.php`
**Propósito:** Crea políticas de SLA (Service Level Agreement) con tiempos de respuesta
**Datos creados:**
- PQRSF Estándar (2/10/15 días)
- PQRSF Prioritario (1/5/7.5 días)
- PQRSF Emergencia (8h/2/3 días)
- PQRSF Extendido (3/20/30 días)

#### 6. AttentionPermissionsSeeder
**Archivo:** `AttentionPermissionsSeeder.php`
**Propósito:** Crea permisos y roles usando Spatie Laravel Permission
**Roles creados:**
- super-admin: Acceso completo
- attention-admin: Administrador del módulo
- attention-manager: Supervisor de PQRSF
- attention-agent: Agente de atención
- attention-user: Usuario básico

**Permisos creados:**
- attention.view / attention.view-all
- attention.create / attention.update / attention.delete
- attention.manage / attention.assign
- attention.change-status / attention.resolve / attention.close
- attention.send-email / attention.manage-notes
- attention.view-history / attention.view-reports
- attention.manage-departments / attention.manage-types / attention.manage-settings

### Seeders Documentales

#### 7. AttentionStatusSeeder
**Archivo:** `AttentionStatusSeeder.php`
**Propósito:** Documenta los estados disponibles (manejados por enum)
**Estados (vía enum AttentionStatus):**
- RECEIVED (Recibido)
- IN_PROCESS (En Proceso)
- RESOLVED (Resuelto)
- CLOSED (Cerrado)

**Nota:** Este seeder solo muestra información, no inserta datos.

### Seeders de Datos de Prueba

#### 8. AttentionDemoDataSeeder
**Archivo:** `AttentionDemoDataSeeder.php`
**Propósito:** Crea datos de demostración para desarrollo y testing
**Datos creados:**
- 50 PQRSF de ejemplo con estados variados
- 2-5 notas internas por PQRSF
- 3-8 acciones de historial por PQRSF

**Requisitos previos:**
- Debe ejecutarse después de los seeders base
- Requiere usuarios existentes en la tabla `users`

## Seeder Principal

### AttentionDatabaseSeeder
**Archivo:** `AttentionDatabaseSeeder.php`
**Propósito:** Orquesta la ejecución de todos los seeders

**Orden de ejecución:**
1. AttentionTypesSeeder
2. AttentionCategoriesSeeder
3. AttentionDepartmentsSeeder
4. AttentionSedesSeeder
5. AttentionSlaPoliciesSeeder
6. AttentionPermissionsSeeder
7. AttentionDemoDataSeeder (opcional, con confirmación)

## Factories Disponibles

Las siguientes factories están disponibles para crear datos de prueba:

- **AttentionFactory:** Crea PQRSF
- **AttentionTypeFactory:** Crea tipos de atención
- **AttentionCategoryFactory:** Crea categorías
- **AttentionDepartmentFactory:** Crea departamentos
- **AttentionSedeFactory:** Crea sedes
- **AttentionNoteFactory:** Crea notas internas
- **AttentionActionFactory:** Crea acciones de historial

## Uso

### Ejecutar todos los seeders del módulo

```bash
php artisan module:seed Attention
```

### Ejecutar un seeder específico

```bash
php artisan db:seed --class="Modules\\Attention\\Database\\Seeders\\AttentionTypesSeeder"
```

### Ejecutar solo seeders base (sin datos demo)

```bash
php artisan module:seed Attention
# Responder "no" cuando pregunte por datos demo
```

### Ejecutar con datos demo

```bash
php artisan module:seed Attention
# Responder "yes" cuando pregunte por datos demo
```

### En ambiente de producción

**IMPORTANTE:** En producción, ejecutar solo los seeders base:

```bash
php artisan db:seed --class="Modules\\Attention\\Database\\Seeders\\AttentionTypesSeeder"
php artisan db:seed --class="Modules\\Attention\\Database\\Seeders\\AttentionCategoriesSeeder"
php artisan db:seed --class="Modules\\Attention\\Database\\Seeders\\AttentionDepartmentsSeeder"
php artisan db:seed --class="Modules\\Attention\\Database\\Seeders\\AttentionSedesSeeder"
php artisan db:seed --class="Modules\\Attention\\Database\\Seeders\\AttentionSlaPoliciesSeeder"
php artisan db:seed --class="Modules\\Attention\\Database\\Seeders\\AttentionPermissionsSeeder"
```

**NUNCA ejecutar AttentionDemoDataSeeder en producción.**

## Verificación

Después de ejecutar los seeders, verificar que los datos se crearon correctamente:

```bash
# Verificar tipos
php artisan tinker
>>> \Modules\Attention\Models\AttentionType::count()

# Verificar categorías
>>> \Modules\Attention\Models\AttentionCategory::count()

# Verificar roles
>>> \Spatie\Permission\Models\Role::where('name', 'like', 'attention-%')->get()

# Verificar permisos
>>> \Spatie\Permission\Models\Permission::where('name', 'like', 'attention.%')->count()
```

## Personalización

Los seeders base pueden ser personalizados editando los archivos correspondientes para ajustar:

- Nombres de departamentos y sedes según la organización
- Políticas SLA según normativa local
- Categorías adicionales según necesidades del negocio
- Emails y contactos de departamentos

## Notas Importantes

1. **Permisos:** El seeder de permisos usa Spatie Laravel Permission. Asegurar que el paquete esté instalado y configurado.

2. **Datos existentes:** Los seeders usan `insertOrIgnore()` o `updateOrCreate()` para evitar duplicados.

3. **Orden de ejecución:** Es importante respetar el orden de los seeders debido a las relaciones entre tablas.

4. **Datos demo:** Solo usar datos demo en desarrollo/staging. Nunca en producción.

5. **Status:** Los status se manejan mediante enum, no requieren tabla ni seeder de base de datos.

## Mantenimiento

Al agregar nuevas categorías, tipos o departamentos:

1. Editar el seeder correspondiente
2. Ejecutar el seeder específico
3. Documentar los cambios en este README

---

**Última actualización:** 2026-02-08
**Versión del módulo:** 1.0.0
