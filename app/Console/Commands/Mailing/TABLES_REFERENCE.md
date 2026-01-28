# Referencia Completa de Tablas Mailing

Esta es una guía de referencia de todas las 83 tablas disponibles en la base de datos Acelle con prefijo `mailing_`.

## Categorías de Tablas

### Tablas Prioritarias (Ya Tienen Agentes)

Estas 10 tablas tienen agentes generados y listos para usar:

```bash
mailing:mail-lists     # Listas de correo
mailing:subscribers    # Suscriptores
mailing:campaigns      # Campañas de email
mailing:templates      # Plantillas de correo
mailing:layouts        # Layouts de email
mailing:languages      # Idiomas del sistema
mailing:segments       # Segmentos de audiencia
mailing:fields         # Campos personalizados
mailing:contacts       # Contactos
mailing:users          # Usuarios del sistema
```

### Tablas de Configuración

```
mailing_admin_groups           # Grupos de administrador
mailing_admins                 # Administradores
mailing_settings               # Configuración general
mailing_categories             # Categorías
mailing_countries              # Países
mailing_currencies             # Monedas
mailing_languages              # Idiomas (prioridad)
```

### Tablas de Clientes

```
mailing_customers                      # Clientes
mailing_customer_groups                # Grupos de clientes
mailing_customer_group_sending_servers # Servidores de envío por grupo
mailing_sub_accounts                   # Subcuentas
```

### Tablas de Email Marketing

```
mailing_mail_lists                # Listas de correo (prioridad)
mailing_mail_lists_sending_servers # Servidores de envío para listas
mailing_subscribers               # Suscriptores (prioridad)
mailing_subscriber_fields         # Valores de campos de suscriptores
mailing_fields                    # Campos personalizados (prioridad)
mailing_field_options             # Opciones de campos select
mailing_segments                  # Segmentos (prioridad)
mailing_segment_conditions        # Condiciones de segmentos
mailing_campaigns                 # Campañas (prioridad)
mailing_campaigns_lists_segments  # Relación campaña-lista-segmento
mailing_templates                 # Plantillas (prioridad)
mailing_templates_categories      # Categorías de plantillas
mailing_layouts                   # Layouts (prioridad)
mailing_contacts                  # Contactos (prioridad)
```

### Tablas de Emails Enviados

```
mailing_emails                    # Emails enviados (log)
mailing_email_webhooks            # Webhooks de email
mailing_email_links               # Enlaces en emails
mailing_open_logs                 # Log de aperturas
mailing_click_logs                # Log de clics
mailing_bounce_logs               # Log de rechazos
mailing_unsubscribe_logs          # Log de desuscripciones
mailing_feedback_logs             # Log de feedback
mailing_subscription_logs         # Log de suscripciones
mailing_tracking_logs             # Log de seguimiento
```

### Tablas de Automatización

```
mailing_auto_triggers             # Disparadores automáticos
mailing_automation2s              # Automatización avanzada
mailing_forms                     # Formularios
mailing_pages                     # Páginas
mailing_funnels                   # Funnels de conversión
```

### Tablas de Servidores de Envío

```
mailing_sending_servers           # Servidores SMTP
mailing_sending_domains           # Dominios de envío
mailing_tracking_domains          # Dominios de seguimiento
mailing_bounce_handlers           # Manejadores de rebotes
mailing_feedback_loop_handlers    # Manejadores de feedback loops
mailing_email_verification_servers # Servidores de verificación
mailing_plans_sending_servers     # Servidores por plan
mailing_plans_email_verification_servers # Verificadores por plan
mailing_senders                   # Remitentes
```

### Tablas de Facturación

```
mailing_invoices                  # Facturas
mailing_invoice_items             # Items de facturas
mailing_orders                    # Órdenes
mailing_plans                     # Planes de precios
mailing_billing_addresses         # Direcciones de facturación
mailing_transactions              # Transacciones
```

### Tablas de Productos

```
mailing_products                  # Productos
mailing_product_attributes        # Atributos de productos
```

### Tablas de Contenido

```
mailing_media                     # Archivos multimedia
mailing_attachments               # Archivos adjuntos
mailing_files                     # Archivos generales
mailing_websites                  # Websites
```

### Tablas del Sistema

```
mailing_jobs                      # Trabajos/Jobs
mailing_job_batches              # Lotes de trabajos
mailing_job_monitors             # Monitor de trabajos
mailing_failed_jobs              # Trabajos fallidos
mailing_migrations               # Migraciones ejecutadas
mailing_password_resets          # Resets de contraseña
mailing_user_activations         # Activaciones de usuarios
mailing_notifications            # Notificaciones
mailing_plugins                  # Plugins del sistema
mailing_logs                     # Logs generales
mailing_sources                  # Fuentes de datos
mailing_timelines                # Líneas de tiempo
mailing_ip_locations             # Ubicaciones de IP
mailing_attributes               # Atributos generales
```

### Tablas Legacy/Temporal

```
mailing__tmp_subscriptions        # Subscripciones temporales
```

## Comando para Generar Agentes para Todas las Tablas

```bash
# Generar agentes para todas las tablas que falten
php artisan mailing:generate-agents

# Esto generará automáticamente los agentes para:
# - Todas las tablas con prefijo mailing_
# - Que aún no tengan un agente
# - Con todas las opciones basadas en columnas
```

## Patrones de Nomenclatura de Agentes

Cuando se genere un agente, la nomenclatura será:

| Tabla | Comando | Clase |
|-------|---------|-------|
| `mailing_mail_lists` | `mailing:mail-lists` | `MailListsAgent` |
| `mailing_subscribers` | `mailing:subscribers` | `SubscribersAgent` |
| `mailing_campaigns` | `mailing:campaigns` | `CampaignsAgent` |
| `mailing_sending_servers` | `mailing:sending-servers` | `SendingServersAgent` |
| `mailing_email_verification_servers` | `mailing:email-verification-servers` | `EmailVerificationServersAgent` |
| `mailing_bounce_handlers` | `mailing:bounce-handlers` | `BounceHandlersAgent` |

## Flujo de Generación

### Paso 1: Ver Agentes Disponibles

```bash
php artisan list | grep mailing
```

### Paso 2: Generar Faltantes

```bash
php artisan mailing:generate-agents
```

Esto outputeará:
```
Found 82 mailing tables
✓ Generated agent for mailing_sending_servers
✓ Generated agent for mailing_jobs
✓ Generated agent for mailing_invoices
... (más tablas)
Successfully generated X agents!
```

### Paso 3: Usar los Agentes

```bash
# Generar agentes para una tabla específica
php artisan mailing:generate-agents --tables="mailing_sending_servers,mailing_invoices"

# Usar un agente recién generado
php artisan mailing:sending-servers list
php artisan mailing:invoices list
```

## Tabla de Características por Categoría

### Email Marketing Completo

| Tabla | Propósito | Status |
|-------|-----------|--------|
| mail_lists | Crear listas de email | ✓ Agente |
| subscribers | Gestionar suscriptores | ✓ Agente |
| fields | Campos personalizados | ✓ Agente |
| segments | Segmentar audiencia | ✓ Agente |
| campaigns | Crear campañas | ✓ Agente |
| templates | Plantillas de email | ✓ Agente |
| layouts | Layouts de diseño | ✓ Agente |

### Automatización

| Tabla | Propósito | Status |
|-------|-----------|--------|
| auto_triggers | Disparadores automáticos | Generar |
| automation2s | Automatización avanzada | Generar |
| forms | Formularios de suscripción | Generar |
| pages | Páginas de aterrizaje | Generar |
| funnels | Funnels de conversión | Generar |

### Análisis y Seguimiento

| Tabla | Propósito | Status |
|-------|-----------|--------|
| open_logs | Seguimiento de aperturas | Generar |
| click_logs | Seguimiento de clics | Generar |
| bounce_logs | Seguimiento de rebotes | Generar |
| unsubscribe_logs | Log de desuscripciones | Generar |
| tracking_logs | Logs de seguimiento | Generar |

## Recomendaciones de Uso

### Para Testing/Desarrollo

1. Comienza con las tablas prioritarias
2. Genera agentes para otras tablas conforme las necesites
3. Usa modo interactivo para entender la estructura

### Para Automatización

1. Crea scripts que usen los agentes
2. Usa opciones en lugar del modo interactivo
3. Captura salidas para logging

### Para Importación de Datos

1. Crea scripts que leen archivos CSV/JSON
2. Usa agentes para insertar en batch
3. Valida después de cada inserción

## Scripts de Ejemplo para Tablas Comunes

### Configurar Servidores SMTP

```bash
php artisan mailing:sending-servers create \
  --name="SendGrid SMTP" \
  --type="smtp" \
  --host="smtp.sendgrid.net" \
  --port=587 \
  --username="apikey" \
  --password="SG.xxxxx"
```

### Gestionar Facturas

```bash
php artisan mailing:invoices list
php artisan mailing:invoices show --id=1
php artisan mailing:invoice-items list
```

### Monitorear Trabajos

```bash
php artisan mailing:jobs list
php artisan mailing:job-monitors list
php artisan mailing:failed-jobs list
```

## Próximos Pasos

1. Ejecutar: `php artisan mailing:generate-agents`
2. Listar agentes: `php artisan list | grep mailing`
3. Usar agentes: `php artisan mailing:{tabla} list`
4. Consultar: `app/Console/Commands/Mailing/README.md` para más info

## Notas Importantes

- Los agentes se generan automáticamente basado en la estructura real de la tabla
- Las claves foráneas se detectan automáticamente
- Las columnas timestamp se omiten automáticamente
- La conexión por defecto es 'acelle'
- Todos los agentes soportan: list, create, show, update, delete

## Soporte

Si necesitas información sobre una tabla específica:

```bash
# Ver estructura de tabla
php artisan tinker
> DB::connection('acelle')->getSchemaBuilder()->getColumns('mailing_tabla_nombre')

# O usar el agente una vez generado
php artisan mailing:tabla list
```
