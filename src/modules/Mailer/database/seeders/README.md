# Mailer Module Seeders

Esta carpeta contiene los seeders para el módulo Mailer. Los seeders se organizan en categorías funcionales y se ejecutan en un orden específico de dependencias.

## Estructura de Seeders

```
seeders/
├── MailerDatabaseSeeder.php                    # Seeder coordinador principal
├── Setup/
│   ├── SetupVariablesSeeder.php               # Sistema de variables para templates
│   └── SetupLayoutsSeeder.php                 # Layouts base (header, footer, wrapper)
├── Templates/
│   └── MailerTemplateLayoutSeeder.php         # Configuraciones específicas de templates
├── Examples/
│   └── ExampleTemplatesSeeder.php             # Plantillas de ejemplo de referencia
├── Migrations/
│   ├── MigrateTemplateTranslationsSeeder.php  # Migración de traducciones
│   └── MigrateDocumentTemplatesSeeder.php     # Migración de templates de documentos
└── README.md                                   # Este archivo
```

## Descripción de Seeders

### MailerDatabaseSeeder (Coordinador Principal)
**Archivo:** `MailerDatabaseSeeder.php`

Seeder coordinador que ejecuta todos los seeders en el orden correcto de dependencias.
Organiza los seeders en 4 fases principales:

1. **SETUP PHASE** - Datos fundacionales (variables y layouts base)
2. **TEMPLATES PHASE** - Configuración específica de templates
3. **EXAMPLES PHASE** - Plantillas de referencia
4. **MIGRATIONS PHASE** - Migración de datos legados

**Uso:**
```bash
php artisan db:seed --class="Modules\\Mailer\\Database\\Seeders\\MailerDatabaseSeeder"
```

---

## SETUP PHASE - Datos Fundacionales

### Setup/SetupVariablesSeeder
**Archivo:** `Setup/SetupVariablesSeeder.php`

Siembra las variables disponibles para usar en plantillas de email. Estas variables se reemplazan con valores reales cuando se envían emails.

**Variables sembradas (25+):**
- **User (5):** USER_NAME, USER_EMAIL, USER_FIRST_NAME, USER_LAST_NAME, USER_PHONE
- **Site (5):** SITE_NAME, SITE_URL, SITE_LOGO_URL, SUPPORT_EMAIL, SUPPORT_PHONE
- **Company (7):** COMPANY_NAME, COMPANY_ADDRESS, COMPANY_CITY, COMPANY_POSTAL_CODE, COMPANY_COUNTRY, COMPANY_PHONE, COMPANY_EMAIL
- **Date (3):** CURRENT_DATE, CURRENT_YEAR, CURRENT_MONTH
- **Links (5):** LOGIN_URL, RESET_PASSWORD_URL, VERIFY_EMAIL_URL, ACCOUNT_DASHBOARD_URL, UNSUBSCRIBE_URL

**Uso individual:**
```bash
php artisan db:seed --class="Modules\\Mailer\\Database\\Seeders\\Setup\\SetupVariablesSeeder"
```

### Setup/SetupLayoutsSeeder
**Archivo:** `Setup/SetupLayoutsSeeder.php`

Siembra los layouts base fundamentales que forman la estructura de todos los emails.

**Layouts creados:**
- `email_template_header` - Encabezado (componente parcial)
- `email_template_footer` - Pie de página (componente parcial)
- `email_template_wrapper` - Layout completo (header + content + footer)

**Uso individual:**
```bash
php artisan db:seed --class="Modules\\Mailer\\Database\\Seeders\\Setup\\SetupLayoutsSeeder"
```

---

## TEMPLATES PHASE - Configuración de Templates

### Templates/MailerTemplateLayoutSeeder
**Archivo:** `Templates/MailerTemplateLayoutSeeder.php`

Siembra configuraciones y layouts específicos que se aplican a plantillas individuales.

**Uso individual:**
```bash
php artisan db:seed --class="Modules\\Mailer\\Database\\Seeders\\Templates\\MailerTemplateLayoutSeeder"
```

---

## EXAMPLES PHASE - Plantillas de Referencia

### Examples/ExampleTemplatesSeeder
**Archivo:** `Examples/ExampleTemplatesSeeder.php`

Siembra plantillas de ejemplo que pueden usarse como referencia o punto de partida para crear nuevas plantillas.

**Plantillas de ejemplo:**
- `welcome_email` - Correo de bienvenida a nuevos usuarios
- `password_reset` - Email de restablecimiento de contraseña
- `email_verification` - Correo de verificación de dirección email

**Uso individual:**
```bash
php artisan db:seed --class="Modules\\Mailer\\Database\\Seeders\\Examples\\ExampleTemplatesSeeder"
```

---

## MIGRATIONS PHASE - Migración de Datos Legados

### Migrations/MigrateTemplateTranslationsSeeder
**Archivo:** `Migrations/MigrateTemplateTranslationsSeeder.php`

Migra traducciones de plantillas desde la tabla principal `mailer_templates` a la tabla dedicada `mailer_template_langs` para mejor soporte multi-idioma.

**Flujo:**
1. Lee todas las plantillas con subject/content
2. Crea registros en `mailer_template_langs` si no existen
3. Mantiene integridad referencial y evita duplicados

**Uso individual:**
```bash
php artisan db:seed --class="Modules\\Mailer\\Database\\Seeders\\Migrations\\MigrateTemplateTranslationsSeeder"
```

### Migrations/MigrateDocumentTemplatesSeeder
**Archivo:** `Migrations/MigrateDocumentTemplatesSeeder.php`

Migra plantillas de email relacionadas con documentos desde el sistema legado.

**Plantillas migradas:**
- `document_upload_notification` - Solicitud inicial de documentos
- `document_missing_notification` - Documentos faltantes o incorrectos
- `document_upload_reminder` - Recordatorio de carga
- `document_upload_confirmation` - Confirmación de recepción
- `document_custom_email` - Template base para emails personalizados

**Uso individual:**
```bash
php artisan db:seed --class="Modules\\Mailer\\Database\\Seeders\\Migrations\\MigrateDocumentTemplatesSeeder"
```

---

## Orden de Ejecución Recomendado

Los seeders deben ejecutarse en este orden específico para mantener integridad de datos:

1. **SetupVariablesSeeder** (primero, las plantillas las necesitan)
2. **SetupLayoutsSeeder** (layouts base para la estructura)
3. **MailerTemplateLayoutSeeder** (layouts específicos de templates)
4. **ExampleTemplatesSeeder** (plantillas de ejemplo)
5. **MigrateTemplateTranslationsSeeder** (migración de datos)
6. **MigrateDocumentTemplatesSeeder** (templates de documentos)

Este orden se respeta automáticamente al usar **MailerDatabaseSeeder**.

## Cómo Ejecutar

### Ejecutar todos los seeders del módulo
```bash
php artisan db:seed --class="Modules\\Mailer\\Database\\Seeders\\MailerDatabaseSeeder"
```

### Ejecutar un seeder específico
```bash
# Setup Phase
php artisan db:seed --class="Modules\\Mailer\\Database\\Seeders\\Setup\\SetupVariablesSeeder"
php artisan db:seed --class="Modules\\Mailer\\Database\\Seeders\\Setup\\SetupLayoutsSeeder"

# Templates Phase
php artisan db:seed --class="Modules\\Mailer\\Database\\Seeders\\Templates\\MailerTemplateLayoutSeeder"

# Examples Phase
php artisan db:seed --class="Modules\\Mailer\\Database\\Seeders\\Examples\\ExampleTemplatesSeeder"

# Migrations Phase
php artisan db:seed --class="Modules\\Mailer\\Database\\Seeders\\Migrations\\MigrateTemplateTranslationsSeeder"
php artisan db:seed --class="Modules\\Mailer\\Database\\Seeders\\Migrations\\MigrateDocumentTemplatesSeeder"
```

### Ejecutar todos los seeders (incluyendo raíz)
```bash
php artisan db:seed
```

## Dependencias

Los seeders asumen que existen las siguientes tablas:
- `mailer_variables`
- `mailer_layouts`
- `mailer_layout_langs`
- `mailer_templates`
- `mailer_template_langs`
- `langs`

Ejecuta las migraciones antes de los seeders:
```bash
php artisan migrate --path=modules/Mailer/database/migrations
```

## Modificar Seeders

Para agregar nuevas variables, layouts o templates:

### Agregar Variables
1. Edita `Setup/SetupVariablesSeeder.php`
2. Agrega al array `$variables` una nueva entrada
3. Ejecuta el seeder individual o el coordinador

### Agregar Layouts
1. Edita `Setup/SetupLayoutsSeeder.php`
2. Crea un nuevo método `getXxxContent()` con el HTML
3. Llama al método en el `run()` usando `MailerLayout::updateOrCreate()`

### Agregar Templates
1. Edita `Examples/ExampleTemplatesSeeder.php` para ejemplos
2. O crea un nuevo seeder en `Migrations/` para templates legados
3. Agrega al array `$templates` una nueva entrada

## Características

- ✅ Seeders idempotentes - ejecutarlos múltiples veces no causa duplicados
- ✅ Usan `updateOrCreate()` para permitir actualizaciones seguras
- ✅ Transacciones manejadas automáticamente por Laravel
- ✅ Mensajes de progreso detallados en la consola
- ✅ Organizados por tipo/categoría para fácil navegación
- ✅ Dependencias respetadas automáticamente por coordinador

## Notas Importantes

- Los seeders usan UIDs (Str::ulid()) para identificadores únicos
- Se respetan los idiomas existentes en la tabla `langs`
- Las traducciones se almacenan en tablas separadas para mejor escalabilidad
- Todos los templates incluyen variables que pueden ser reemplazadas dinámicamente
- Los layouts pueden componerse unos dentro de otros (header/footer/wrapper)
