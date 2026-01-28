# Mailing Agents - Documentación Completa

Bienvenido a la documentación de los Mailing Agents. Este es tu punto de partida para entender y usar los agentes de consola para gestionar la base de datos Acelle.

## Quick Start

Empezar es muy simple:

```bash
# Ver todos los comandos disponibles
php artisan list | grep mailing

# Listar registros de cualquier tabla
php artisan mailing:mail-lists list

# Crear un nuevo registro (interactivo)
php artisan mailing:mail-lists create

# Ver detalles de un registro
php artisan mailing:mail-lists show --id=1

# Actualizar un registro
php artisan mailing:mail-lists update --id=1 --name="Nuevo Nombre"

# Eliminar un registro
php artisan mailing:mail-lists delete --id=1
```

## Documentación

### 1. **README.md** (Lee primero)
   - Visión general de los agentes
   - Tabla de tablas prioritarias
   - Sintaxis general de uso
   - Ejemplos básicos
   - Características principales

### 2. **EXAMPLES.md** (Casos de uso prácticos)
   - Flujo completo: crear una campaña de email
   - Casos de uso comunes
   - Flujos interactivos
   - Operaciones batch
   - Combinación de agentes
   - Tips y trucos

### 3. **TABLES_REFERENCE.md** (Catálogo de tablas)
   - Todas las 83 tablas disponibles
   - Categorías de tablas
   - Patrones de nomenclatura
   - Recomendaciones de uso
   - Scripts de ejemplo

### 4. **CUSTOM_AGENTS.md** (Extensiones avanzadas)
   - Cómo personalizar agentes
   - Sobrescribir métodos
   - Validación personalizada
   - Búsqueda avanzada
   - Acciones personalizadas
   - Reportes y análisis

## Estructura de Archivos

```
app/Console/Commands/Mailing/
├── BaseMailingAgent.php          # Clase base (CRUD)
├── GenerateAgents.php            # Generador automático
├── {Tabla}Agent.php             # Agentes específicos (10 incluidos)
├── README.md                     # Guía principal
├── EXAMPLES.md                   # Casos de uso
├── TABLES_REFERENCE.md          # Catálogo completo
├── CUSTOM_AGENTS.md             # Extensiones
└── INDEX.md                     # Este archivo
```

## Agentes Disponibles

### Tablas Prioritarias (Con Agentes)

| Agente | Tabla | Comando |
|--------|-------|---------|
| MailListsAgent | mailing_mail_lists | `mailing:mail-lists` |
| SubscribersAgent | mailing_subscribers | `mailing:subscribers` |
| CampaignsAgent | mailing_campaigns | `mailing:campaigns` |
| TemplatesAgent | mailing_templates | `mailing:templates` |
| LayoutsAgent | mailing_layouts | `mailing:layouts` |
| LanguagesAgent | mailing_languages | `mailing:languages` |
| SegmentsAgent | mailing_segments | `mailing:segments` |
| FieldsAgent | mailing_fields | `mailing:fields` |
| ContactsAgent | mailing_contacts | `mailing:contacts` |
| UsersAgent | mailing_users | `mailing:users` |

## Flujo de Uso Recomendado

### Para Principiantes

1. **Lee README.md** - Entiende conceptos básicos
2. **Prueba comandos simples**:
   ```bash
   php artisan mailing:mail-lists list
   php artisan mailing:languages list
   ```
3. **Lee EXAMPLES.md** - Aprende casos de uso
4. **Intenta crear registros** - Usa modo interactivo

### Para Usuarios Intermedios

1. **Usa TABLES_REFERENCE.md** - Explora todas las tablas
2. **Genera agentes** para tablas específicas:
   ```bash
   php artisan mailing:generate-agents
   ```
3. **Crea scripts** que usen los agentes
4. **Automatiza** operaciones

### Para Desarrolladores

1. **Lee CUSTOM_AGENTS.md** - Personaliza agentes
2. **Extiende BaseMailingAgent** - Agrega funcionalidad
3. **Crea agentes especializados** para casos complejos
4. **Contribuye** mejoras

## Comandos Principales

### Listar

```bash
php artisan mailing:mail-lists list
php artisan mailing:subscribers list
php artisan mailing:campaigns list
# ... para cualquier tabla
```

### Crear

```bash
# Modo interactivo
php artisan mailing:mail-lists create

# Con opciones
php artisan mailing:mail-lists create \
  --name="Mi Lista" \
  --from_email="info@example.com" \
  --customer_id=1
```

### Ver

```bash
php artisan mailing:mail-lists show --id=1
php artisan mailing:subscribers show --id=5
```

### Actualizar

```bash
php artisan mailing:mail-lists update \
  --id=1 \
  --name="Nombre Actualizado"
```

### Eliminar

```bash
php artisan mailing:mail-lists delete --id=1
# Solicitará confirmación
```

## Generar Agentes para Todas las Tablas

El sistema incluye **83 tablas** de Acelle. Las 10 principales ya tienen agentes. Para las demás:

```bash
# Generar agentes para todas las tablas faltantes
php artisan mailing:generate-agents

# Esto creará agentes automáticamente para:
# - mailing_sending_servers
# - mailing_invoices
# - mailing_products
# - ... y 70+ más
```

Luego podrás usar:

```bash
php artisan mailing:sending-servers list
php artisan mailing:invoices show --id=1
```

## Características Principales

### CRUD Completo
- Listar registros
- Crear registros (interactivo u opciones)
- Ver detalles
- Actualizar campos
- Eliminar (con confirmación)

### Validación Inteligente
- Detecta campos requeridos
- Maneja claves foráneas
- Omite columnas automáticas (id, timestamps)

### Interfaz Amigable
- Tablas formateadas
- Mensajes claros
- Confirmaciones antes de operaciones destructivas

### Extensible
- Heredar de BaseMailingAgent
- Sobrescribir métodos
- Agregar acciones personalizadas

## Ejemplos Rápidos

### Crear una campaña completa

```bash
# 1. Crear lista
php artisan mailing:mail-lists create \
  --name="Newsletter 2026" \
  --customer_id=1

# 2. Crear suscriptores (con ID de lista = 1)
php artisan mailing:subscribers create \
  --mail_list_id=1 \
  --email="user@example.com"

# 3. Crear plantilla
php artisan mailing:templates create \
  --name="Newsletter Template" \
  --type="notification" \
  --customer_id=1

# 4. Crear campaña
php artisan mailing:campaigns create \
  --name="Febrero 2026" \
  --customer_id=1 \
  --type="regular" \
  --default_mail_list_id=1
```

### Gestionar suscriptores

```bash
# Listar con filtro (en agentes extendidos)
php artisan mailing:subscribers list --status=subscribed

# Ver detalles
php artisan mailing:subscribers show --id=1

# Cambiar estado
php artisan mailing:subscribers update --id=1 --status="unsubscribed"

# Eliminar
php artisan mailing:subscribers delete --id=1
```

## Conexión a la Base de Datos

Todos los agentes usan automáticamente:
- **Conexión**: `acelle` (base de datos Acelle)
- **Tabla**: Definida en cada agente
- **Prefijo**: `mailing_` (todas las tablas)

No necesitas cambiar configuración.

## Errores Comunes

### "Table not found"
La tabla no existe aún (migraciones no ejecutadas).

### "--id option is required"
Necesitas especificar `--id` para show/update/delete.

### "Record with ID X not found"
El registro no existe. Verifica el ID con `list`.

### "Field X is required"
El campo no puede estar vacío.

## Performance

- **list**: Carga todos los registros en memoria
- **create**: Inserta un registro
- **show**: Recupera un registro
- **update**: Actualiza campos específicos
- **delete**: Elimina con confirmación

Para operaciones con >10,000 registros, considera usar Eloquent directamente.

## Debugging

### Ver estructura de tabla

```bash
php artisan tinker
> DB::connection('acelle')->getSchemaBuilder()->getColumns('mailing_mail_lists')
```

### Ver logs de queries

```bash
# En BaseMailingAgent.php, descomenta:
DB::enableQueryLog();
```

## Contacto y Soporte

Para problemas o sugerencias:
1. Revisa la documentación relevante
2. Consulta ejemplos en EXAMPLES.md
3. Explora CUSTOM_AGENTS.md para extensiones

## Resumen de Rutas de Documentación

```
Principiante?
  ↓
  Leer: README.md
  ↓
  Experimentar: comandos simples
  ↓
  Leer: EXAMPLES.md
  ↓
  Crear registros

Necesitas tablas específicas?
  ↓
  Leer: TABLES_REFERENCE.md
  ↓
  Ejecutar: mailing:generate-agents
  ↓
  Usar: nuevos agentes

Desarrollador?
  ↓
  Leer: CUSTOM_AGENTS.md
  ↓
  Extender: BaseMailingAgent
  ↓
  Crear: agentes personalizados
```

## ¿Qué Sigue?

1. **Lee README.md** para entender los conceptos
2. **Prueba los comandos** con tus datos
3. **Genera agentes** para todas las tablas
4. **Automatiza** procesos usando scripts
5. **Personaliza** agentes si es necesario

¡Listo! Ahora estás preparado para usar los Mailing Agents.

---

**Última actualización**: 2026-01-28
**Versión**: 1.0
**Agentes**: 10 prioritarios + generador para 73 adicionales
**Total de tablas soportadas**: 83
