# Sistema de Traducciones para Templates y Componentes de Email

## Descripción General

El sistema de traducción de email templates permite crear y mantener plantillas de correo electrónico en múltiples idiomas. Cada template y componente está asociado a un idioma específico, permitiendo una gestión eficiente de contenido multilingüe.

## Conceptos Clave

### Templates (Plantillas)
Las plantillas de email son contenidos HTML editables que contienen:
- **Asunto** (subject) - asunto del email
- **Contenido HTML** - cuerpo del email
- **Variables** - placeholders como {CUSTOMER_NAME}, {ORDER_ID}, etc.

### Componentes
Los componentes son fragmentos reutilizables de HTML que se pueden incluir en múltiples templates:
- **Header** - encabezado que aparece en todos los emails
- **Footer** - pie que aparece en todos los emails
- **Parciales** - snippets de código reutilizables

### Idiomas (Lang)
Cada template y componente debe estar asociado a un idioma específico. Los idiomas disponibles se configuran en la tabla `langs`.

## Estructura de Base de Datos

### Tabla: email_templates
```sql
CREATE TABLE email_templates (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    uid VARCHAR(255) UNIQUE NOT NULL,
    key VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    content LONGTEXT NOT NULL,
    layout_id BIGINT NULLABLE,
    is_enabled BOOLEAN DEFAULT true,
    variables JSON NULLABLE,
    module VARCHAR(100) DEFAULT 'core',
    lang_id BIGINT NULLABLE,
    description TEXT NULLABLE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    CONSTRAINT UNIQUE (key, lang_id),
    CONSTRAINT FOREIGN KEY (layout_id) REFERENCES layouts(id) ON DELETE SET NULL,
    CONSTRAINT FOREIGN KEY (lang_id) REFERENCES langs(id) ON DELETE CASCADE
);
```

### Tabla: layouts
```sql
-- Los componentes se almacenan en la tabla layouts con group_name='email_templates'
ALTER TABLE layouts ADD COLUMN lang_id BIGINT NULLABLE;
ALTER TABLE layouts ADD CONSTRAINT FOREIGN KEY (lang_id) REFERENCES langs(id) ON DELETE CASCADE;
```

## Modelos Eloquent

### EmailTemplate
```php
class EmailTemplate extends Model {
    protected $fillable = ['uid', 'key', 'name', 'subject', 'content',
                           'layout_id', 'is_enabled', 'variables', 'module', 'lang_id'];

    // Relación con idioma
    public function lang(): BelongsTo {
        return $this->belongsTo('App\Models\Lang', 'lang_id', 'id');
    }

    // Filtrar por idioma
    public function scopeLang($query, $langId) {
        if (is_null($langId)) {
            return $query->whereNull('lang_id');
        }
        return $query->where('lang_id', $langId);
    }
}
```

### Layout (Componentes)
Los componentes se almacenan en la tabla layouts con una relación a Lang.

## Manejo de Traducciones - Flujo de Usuario

### 1. Crear un Template Nuevo

1. Acceder a `/manager/settings/mailers/templates`
2. Clic en "Nuevo Template"
3. Completar campos:
   - **Clave (Key)**: `document_uploaded` (debe ser única por idioma)
   - **Nombre**: Nombre descriptivo
   - **Asunto**: Asunto del email
   - **Módulo**: core, documents, orders, notifications
   - **Idioma**: Seleccionar idioma (requerido)
   - **Contenido HTML**: El contenido del template

4. Guardar - Se validará que no exista otro template con la misma clave en ese idioma

### 2. Traducir a Otro Idioma

Cuando necesites el mismo template en otro idioma:

1. En el template existente, ir a "Editar"
2. En la columna derecha, verás "Otras Traducciones" (si existen)
3. **Opción A**: Crear nuevo template manualmente
   - Ir a crear nuevo template
   - Usar la MISMA **clave** pero diferente idioma
   - Los sistemas reconocerán que son traduciones

4. **Opción B** (Recomendado): Duplicar y traducir
   - En edición del template, usar la opción de "copiar"
   - Cambiar el idioma al deseado
   - Traducir el contenido

### 3. Traducción en Componentes

El mismo proceso aplica para componentes (header, footer, etc.):

1. Ir a `/manager/settings/mailers/components`
2. Crear nuevo componente
3. Seleccionar idioma
4. Guardar
5. Las versiones en otros idiomas se gestionan de forma similar

## Variables Disponibles

### Variables Globales
```
{SITE_NAME}           - Nombre del sitio
{SITE_URL}            - URL del sitio
{SITE_EMAIL}          - Email del sitio
{LOGO_URL}            - URL del logo
{COMPANY_ADDRESS}     - Dirección
{COMPANY_PHONE}       - Teléfono
{COMPANY_EMAIL}       - Email empresa
{CURRENT_YEAR}        - Año actual
{CURRENT_MONTH}       - Mes actual
{CURRENT_DAY}         - Día actual
```

### Variables de Documentos
```
{CUSTOMER_NAME}       - Nombre del cliente
{CUSTOMER_EMAIL}      - Email del cliente
{ORDER_ID}            - ID de la orden
{ORDER_REFERENCE}     - Referencia de orden
{DOCUMENT_TYPE}       - Tipo de documento
{UPLOAD_LINK}         - Link para subir
{EXPIRATION_DATE}     - Fecha de expiración
```

### Variables de Órdenes
```
{ORDER_ID}            - ID de la orden
{ORDER_NUMBER}        - Número de orden
{ORDER_DATE}          - Fecha de orden
{ORDER_TOTAL}         - Total de orden
{ORDER_STATUS}        - Estado de orden
```

## Filtrado y Búsqueda

### En Listado de Templates

En la página de listado puedes filtrar por:

- **Búsqueda**: Por nombre, clave o descripción
- **Módulo**: Filtrar por tipo (documents, orders, notifications, core)
- **Idioma**: Filtrar por idioma específico

```
GET /manager/settings/mailers/templates?search=document&module=documents&lang_id=1
```

### En Listado de Componentes

Similar al de templates:

```
GET /manager/settings/mailers/components?search=header&type=layout&lang_id=1
```

## API REST

### Obtener Templates por Idioma

```php
// En controladores
$spanishTemplates = EmailTemplate::lang(1)->get(); // 1 = ID idioma

// Obtener templates con todas sus traducciones
$allVersions = EmailTemplate::where('key', 'document_uploaded')->get();
```

### Enviar Email en Idioma Específico

```php
use App\Models\Mail\MailTemplate;

$user = User::find(1);
$lang = Lang::where('iso_code', $user->language)->first();

$template = MailTemplate::where('key', 'document_uploaded')
    ->where('lang_id', $lang->id)
    ->firstOrFail();

// Usar $template para enviar email en idioma del usuario
```

## Migración de Datos

Si tienes templates existentes sin idioma asignado:

```php
// Migration/Seeder
$defaultLang = Lang::where('iso_code', 'es')->first(); // Español por defecto

EmailTemplate::whereNull('lang_id')->update([
    'lang_id' => $defaultLang->id
]);

Layout::where('group_name', 'email_templates')
    ->whereNull('lang_id')
    ->update([
        'lang_id' => $defaultLang->id
    ]);
```

## Mejores Prácticas

### 1. Claves Consistentes
- Usa claves consistentes para todas las versiones en diferentes idiomas
- Ejemplo: `document_uploaded` (es, en, pt, etc.)

### 2. Actualizaciones Sincronizadas
- Si cambias una variable en una versión, actualiza en las demás también
- Mantén la estructura HTML similar entre traducciones

### 3. Nomenclatura Clara
- Template name: "Documento Subido - Confirmación (Español)"
- Template name: "Document Uploaded - Confirmation (English)"

### 4. Control de Cambios
- Usa timestamps para saber cuándo se actualizó cada versión
- Considera versioning si necesitas historial completo

## Ejemplo: Crear Template Multiidioma

### Paso 1: Template en Español
```
Key: document_uploaded
Name: Documento Subido - Confirmación
Subject: Tu documento ha sido recibido
Language: Español
Module: documents
Content: <h1>Hola {CUSTOMER_NAME}</h1>
         <p>Hemos recibido tu documento...</p>
```

### Paso 2: Template en Inglés
```
Key: document_uploaded        // MISMA clave
Name: Document Uploaded - Confirmation
Subject: Your document has been received
Language: English              // Diferente idioma
Module: documents
Content: <h1>Hello {CUSTOMER_NAME}</h1>
         <p>We have received your document...</p>
```

### Paso 3: Usar en Código
```php
// El sistema seleccionará automáticamente el idioma
public function sendDocumentNotification($document, $user) {
    $template = EmailTemplate::where('key', 'document_uploaded')
        ->where('lang_id', $user->lang_id)
        ->first();

    // Enviar usando $template
}
```

## Troubleshooting

### Template no aparece al filtrar por idioma
- Verifica que el template tenga `lang_id` asignado
- Comprueba que el idioma exista en la tabla `langs` y esté disponible

### Referencia a traducción no funciona
- Asegúrate de usar la MISMA clave en todas las versiones
- Verifica que cada versión tenga su `lang_id` diferente

### Variables no se reemplazan
- Verifica que las variables estén en mayúsculas: `{CUSTOMER_NAME}`
- Asegúrate de que la variable sea válida para ese módulo

## Límites y Consideraciones

- Máximo una versión por idioma por clave
- El idioma no puede modificarse una vez creado el template
- Se recomienda usar máximo 10-20 idiomas diferentes
- Para idiomas muy similares (es-MX, es-ES), considera usar componentes separados

## Roadmap Futuro

- [ ] Importación/exportación de templates multiidioma
- [ ] Sistema de aprobación para traducciones
- [ ] Detección automática de idioma del usuario
- [ ] Plantillas con soporte para RTL (derecha a izquierda)
- [ ] Versionado de templates
