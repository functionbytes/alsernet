# Mailing Database Agents

Los agentes de Mailing son comandos Artisan que permiten gestionar las tablas de la base de datos Acelle directamente desde la línea de comandos.

## Estructura

- **BaseMailingAgent.php**: Clase base que implementa la lógica CRUD
- **{Table}Agent.php**: Agentes específicos para cada tabla
- **GenerateAgents.php**: Comando para generar nuevos agentes automáticamente

## Tablas Prioritarias (Ya Generadas)

1. `mailing:mail-lists` - Gestiona listas de correo
2. `mailing:subscribers` - Gestiona suscriptores
3. `mailing:campaigns` - Gestiona campañas
4. `mailing:templates` - Gestiona plantillas de correo
5. `mailing:layouts` - Gestiona layouts
6. `mailing:languages` - Gestiona idiomas
7. `mailing:segments` - Gestiona segmentos
8. `mailing:fields` - Gestiona campos personalizados
9. `mailing:contacts` - Gestiona contactos
10. `mailing:users` - Gestiona usuarios

## Uso General

### Sintaxis

```bash
php artisan mailing:{tabla} {accion} [opciones]
```

### Acciones Disponibles

- **list** - Listar todos los registros
- **create** - Crear un nuevo registro
- **show** - Ver detalles de un registro
- **update** - Actualizar un registro
- **delete** - Eliminar un registro (con confirmación)

## Ejemplos

### Listar Registros

```bash
# Listar todas las listas de correo
php artisan mailing:mail-lists list

# Listar todos los suscriptores
php artisan mailing:subscribers list

# Listar todas las campañas
php artisan mailing:campaigns list
```

### Crear Registros

```bash
# Crear una nueva lista de correo (interactivo)
php artisan mailing:mail-lists create

# Crear una lista de correo con opciones
php artisan mailing:mail-lists create --name="Mi Lista" --from_email="no-reply@example.com" --from_name="Mi Empresa" --customer_id=1

# Crear un nuevo suscriptor
php artisan mailing:subscribers create --mail_list_id=1 --email="user@example.com" --status="subscribed"

# Crear una campaña
php artisan mailing:campaigns create --customer_id=1 --name="Campaña Q1" --type="regular" --subject="Mi Asunto"
```

### Ver Detalles

```bash
# Ver detalles de una lista de correo
php artisan mailing:mail-lists show --id=1

# Ver detalles de un suscriptor
php artisan mailing:subscribers show --id=5

# Ver detalles de una campaña
php artisan mailing:campaigns show --id=3
```

### Actualizar Registros

```bash
# Actualizar una lista de correo
php artisan mailing:mail-lists update --id=1 --name="Lista Actualizada"

# Actualizar estado de suscriptor
php artisan mailing:subscribers update --id=5 --status="unsubscribed"

# Actualizar campaña
php artisan mailing:campaigns update --id=3 --status="sent"
```

### Eliminar Registros

```bash
# Eliminar una lista de correo (con confirmación)
php artisan mailing:mail-lists delete --id=1

# Eliminar un suscriptor
php artisan mailing:subscribers delete --id=5

# Eliminar una campaña
php artisan mailing:campaigns delete --id=3
```

## Generar Agentes para Más Tablas

El comando `GenerateAgents.php` permite generar automáticamente agentes para todas las tablas mailing_ que aún no tengan agentes.

### Generar Todos los Agentes Faltantes

```bash
php artisan mailing:generate-agents
```

El comando:
- Obtiene todas las tablas de la conexión 'acelle'
- Identifica aquellas que comienzan con 'mailing_'
- Genera un agente para cada tabla que no tenga uno
- Inspecciona las columnas de cada tabla automáticamente
- Configura las claves foráneas correctamente

### Opciones

```bash
# Generar agentes solo para tablas específicas
php artisan mailing:generate-agents --tables="mailing_emails,mailing_admins,mailing_customers"
```

## Opciones Comunes para Todos los Comandos

### --id (Obligatorio para show, update, delete)

Especifica el ID del registro a operación.

```bash
php artisan mailing:subscribers show --id=10
php artisan mailing:subscribers update --id=10 --email="newemail@example.com"
```

### Opciones Dinámicas

Cada comando genera opciones automáticamente basadas en las columnas de su tabla.

```bash
# Para mailing_subscribers
php artisan mailing:subscribers create --mail_list_id=1 --email="test@example.com"

# Para mailing_templates
php artisan mailing:templates create --name="Mi Plantilla" --type="notification"
```

## Columnas Omitidas (Automáticamente Manejadas)

Las siguientes columnas se omiten automáticamente en operaciones CRUD:

- **id** - Generado automáticamente
- **uid** - UUID generado automáticamente
- **created_at** - Establecido automáticamente
- **updated_at** - Establecido automáticamente

Otras columnas que pueden ser omitidas en agentes específicos:

- `plain` (en campaigns)
- `template_source` (en campaigns)
- `content` (en templates y layouts)
- `password_hash` (campos de contraseña)
- Tokens de autenticación

## Claves Foráneas

Los agentes incluyen validación para claves foráneas. Cuando crea o actualiza registros, el agente:

1. Identifica las columnas que son claves foráneas
2. Solicita el ID del registro relacionado
3. Verifica que el registro exista (si es posible)

Ejemplo:

```bash
# Para crear un suscriptor, necesita una lista válida
php artisan mailing:subscribers create
# Se le pedirá: Enter mail_list_id
# Ingrese un ID de una lista existente
```

## Modo Interactivo vs Opciones

### Modo Interactivo (Recomendado para Testing)

```bash
php artisan mailing:mail-lists create
# El sistema solicitará cada campo uno a uno
```

### Modo Opciones (Para Scripts/Automatización)

```bash
php artisan mailing:mail-lists create --name="Mi Lista" --from_email="from@example.com" --customer_id=1
```

## Manejo de Errores

Los agentes incluyen manejo robusto de errores:

```bash
# Si la tabla no existe
php artisan mailing:mail-lists list
# Error: SQLSTATE[42S02]: Base table or view not found

# Si el ID no existe
php artisan mailing:mail-lists show --id=999
# Error: Record with ID 999 not found

# Si falta un parámetro obligatorio
php artisan mailing:subscribers show
# Error: --id option is required for show action
```

## Patrón de Implementación

### Estructura de un Agente

```php
<?php

namespace App\Console\Commands\Mailing;

class MiTablaAgent extends BaseMailingAgent
{
    // Nombre de la tabla
    protected string $table = 'mailing_mi_tabla';

    // Firma del comando
    protected $signature = 'mailing:mi-tabla {action} {--opciones}';

    // Descripción
    protected $description = 'Manage mailing_mi_tabla table';

    // Columnas a ignorar
    protected array $skipColumns = ['id', 'uid', 'created_at', 'updated_at'];

    // Claves foráneas
    protected array $foreignKeys = ['tabla_id', 'otra_id'];
}
```

## Métodos Disponibles en BaseMailingAgent

### Públicos

- `handle()` - Punto de entrada del comando
- `list()` - Listar registros
- `create()` - Crear registro
- `show()` - Ver detalles
- `update()` - Actualizar registro
- `delete()` - Eliminar registro

### Protegidos

- `getTableColumns()` - Obtiene columnas y tipos de la tabla

## Características de BaseMailingAgent

- Conexión automática a 'acelle'
- Manejo robusto de errores con try/catch
- Validación de parámetros obligatorios
- Confirmación antes de eliminar
- Visualización de resultados en tablas formateadas
- Soporte para entrada interactiva y opciones CLI

## Logging y Debugging

Para debug, puede activar las consultas SQL:

```php
// En BaseMailingAgent.php, descomenta para debug
DB::enableQueryLog();
// ... operación ...
dd(DB::getQueryLog());
```

## Próximos Pasos

1. Ejecutar migraciones: `php artisan migrate`
2. Generar agentes faltantes: `php artisan mailing:generate-agents`
3. Usar agentes para CRUD: `php artisan mailing:{tabla} {accion}`

## Notas Importantes

- Los agentes usan la conexión 'acelle' por defecto
- Se recomienda usar en desarrollo/testing primero
- Las operaciones de delete requieren confirmación
- Los UIDs se generan automáticamente por la base de datos
- Las timestamps se manejan automáticamente
