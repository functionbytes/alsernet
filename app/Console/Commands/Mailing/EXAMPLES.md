# Mailing Agents - Ejemplos de Uso

## Flujo Completo: Crear Una Campaña de Email

Este ejemplo muestra un flujo realista de uso de los agentes.

### Paso 1: Crear una Lista de Correo

```bash
php artisan mailing:mail-lists create \
  --name="Newsletter Mensual" \
  --from_email="newsletter@miempresa.com" \
  --from_name="Mi Empresa" \
  --customer_id=1 \
  --status="active"
```

**Salida esperada:**
```
Record created successfully in mailing_mail_lists
```

### Paso 2: Listar las Listas Creadas para Obtener el ID

```bash
php artisan mailing:mail-lists list
```

**Salida esperada:**
```
+----+------+--------+-----------+-------------------+----------+
| id | name | status | customer_id | from_email      | from_name|
+----+------+--------+-----------+-------------------+----------+
| 1  |Newsl | active | 1         | newsletter@...    | Mi Emp...
+----+------+--------+-----------+-------------------+----------+
```

### Paso 3: Crear Campos Personalizados para la Lista

```bash
# Campo: Nombre
php artisan mailing:fields create \
  --mail_list_id=1 \
  --label="Nombre Completo" \
  --type="text" \
  --tag="full_name" \
  --visible=1 \
  --required=1

# Campo: Empresa
php artisan mailing:fields create \
  --mail_list_id=1 \
  --label="Empresa" \
  --type="text" \
  --tag="company" \
  --visible=1 \
  --required=0
```

### Paso 4: Crear Suscriptores

```bash
# Suscriptor 1
php artisan mailing:subscribers create \
  --mail_list_id=1 \
  --email="juan@example.com" \
  --status="subscribed" \
  --subscription_type="confirmed"

# Suscriptor 2
php artisan mailing:subscribers create \
  --mail_list_id=1 \
  --email="maria@example.com" \
  --status="subscribed" \
  --subscription_type="confirmed"

# Suscriptor 3
php artisan mailing:subscribers create \
  --mail_list_id=1 \
  --email="carlos@example.com" \
  --status="pending" \
  --subscription_type="unconfirmed"
```

### Paso 5: Crear un Segmento

```bash
php artisan mailing:segments create \
  --mail_list_id=1 \
  --name="Suscriptores Activos" \
  --matching="subscribed"
```

### Paso 6: Crear una Plantilla de Email

```bash
php artisan mailing:templates create \
  --customer_id=1 \
  --name="Newsletter Template Q1 2026" \
  --type="notification" \
  --builder=0 \
  --is_default=0 \
  --is_private=0
```

### Paso 7: Crear una Campaña

```bash
php artisan mailing:campaigns create \
  --customer_id=1 \
  --name="Campaña Q1 2026" \
  --type="regular" \
  --subject="Noticias de Enero" \
  --from_email="newsletter@miempresa.com" \
  --from_name="Mi Empresa" \
  --default_mail_list_id=1 \
  --template_id=1 \
  --status="draft"
```

### Paso 8: Ver los Detalles de la Campaña

```bash
php artisan mailing:campaigns show --id=1
```

**Salida esperada:**
```
Record details:
id: 1
uid: a1b2c3d4-e5f6-7g8h-9i0j-k1l2m3n4o5p6
customer_id: 1
name: Campaña Q1 2026
type: regular
subject: Noticias de Enero
from_email: newsletter@miempresa.com
from_name: Mi Empresa
status: draft
...
```

### Paso 9: Actualizar la Campaña

```bash
php artisan mailing:campaigns update \
  --id=1 \
  --status="ready" \
  --run_at="2026-02-01 10:00:00"
```

## Casos de Uso Comunes

### 1. Migrar Suscriptores Desde CSV

```bash
# Primero crear la lista
php artisan mailing:mail-lists create --name="Importados" --customer_id=1

# Luego crear suscriptores programáticamente (requeriría un script)
php artisan mailing:subscribers create --mail_list_id=1 --email="...@..."
```

### 2. Cambiar el Estado de un Suscriptor

```bash
# De pendiente a confirmado
php artisan mailing:subscribers update \
  --id=3 \
  --status="subscribed" \
  --subscription_type="confirmed"
```

### 3. Listar Todos los Idiomas Disponibles

```bash
php artisan mailing:languages list
```

### 4. Crear Campos Dinámicos

```bash
# Crear campo de selección para País
php artisan mailing:fields create \
  --mail_list_id=1 \
  --label="País" \
  --type="select" \
  --tag="country" \
  --visible=1 \
  --required=0

# Crear campo de checkbox para Aceptar Términos
php artisan mailing:fields create \
  --mail_list_id=1 \
  --label="Acepto los términos" \
  --type="checkbox" \
  --tag="accept_terms" \
  --visible=1 \
  --required=1
```

### 5. Limpiar Suscriptores Sin Confirmar

```bash
# Ver todos los suscriptores
php artisan mailing:subscribers list

# Eliminar los no confirmados (requeriría verificación manual)
php artisan mailing:subscribers delete --id=3
# Confirmar: Are you sure you want to delete record 3? (yes/no)
# yes
```

### 6. Crear Múltiples Contactos para una Empresa

```bash
php artisan mailing:contacts create \
  --first_name="Juan" \
  --last_name="Pérez" \
  --email="juan.perez@empresa.com" \
  --company="Empresa S.A." \
  --address_1="Calle Principal 123" \
  --city="Madrid" \
  --country_id=34

php artisan mailing:contacts create \
  --first_name="María" \
  --last_name="García" \
  --email="maria.garcia@empresa.com" \
  --company="Empresa S.A." \
  --address_1="Calle Principal 123" \
  --city="Madrid" \
  --country_id=34
```

### 7. Crear Usuarios del Sistema

```bash
# Crear usuario administrador
php artisan mailing:users create \
  --email="admin@miempresa.com" \
  --password="contraseña_segura" \
  --first_name="Administrador" \
  --last_name="Sistema" \
  --status="active" \
  --activated=1 \
  --customer_id=1

# Crear usuario operador
php artisan mailing:users create \
  --email="operador@miempresa.com" \
  --password="otra_contraseña" \
  --first_name="Juan" \
  --last_name="Operador" \
  --status="active" \
  --activated=1 \
  --customer_id=1
```

## Flujos Interactivos

### Crear sin Especificar Opciones

```bash
php artisan mailing:mail-lists create
# El sistema solicitará cada campo:
# Enter name: Newsletter de Primavera
# Enter from_email: spring@example.com
# Enter from_name: Mi Empresa
# ...
```

### Ver y Luego Editar

```bash
# Ver el registro
php artisan mailing:campaigns show --id=1

# Luego actualizar algún campo
php artisan mailing:campaigns update --id=1 --subject="Nuevo Asunto"
```

## Operaciones Batch

### Script para Crear Múltiples Listas

```bash
#!/bin/bash

LISTS=("Clientes" "Prospectors" "Newsletter" "Blog Subscribers")

for list_name in "${LISTS[@]}"; do
  php artisan mailing:mail-lists create \
    --name="$list_name" \
    --from_email="noreply@example.com" \
    --customer_id=1 \
    --status="active"
  echo "Created list: $list_name"
done
```

### Script para Listar Todo

```bash
#!/bin/bash

echo "=== LISTAS DE CORREO ==="
php artisan mailing:mail-lists list

echo -e "\n=== SUSCRIPTORES ==="
php artisan mailing:subscribers list

echo -e "\n=== CAMPAÑAS ==="
php artisan mailing:campaigns list

echo -e "\n=== PLANTILLAS ==="
php artisan mailing:templates list
```

## Combinando Agentes

### Workflow Completo en Scripts

```php
<?php
// Script PHP para crear estructura completa

$commands = [
    // Crear lista
    'php artisan mailing:mail-lists create --name="Test" --customer_id=1',

    // Crear suscriptores (requiere el ID de la lista anterior)
    'php artisan mailing:subscribers create --mail_list_id=1 --email="test1@example.com"',
    'php artisan mailing:subscribers create --mail_list_id=1 --email="test2@example.com"',

    // Crear plantilla
    'php artisan mailing:templates create --name="Test Template" --customer_id=1 --type="notification"',

    // Crear campaña
    'php artisan mailing:campaigns create --name="Test Campaign" --customer_id=1 --type="regular" --default_mail_list_id=1',
];

foreach ($commands as $command) {
    echo "Executing: $command\n";
    shell_exec($command);
    echo "Done.\n\n";
}
```

## Validaciones y Errores

### Error: ID no existe

```bash
php artisan mailing:subscribers show --id=99999
# Output: Record with ID 99999 not found
```

### Error: Falta parámetro obligatorio

```bash
php artisan mailing:subscribers show
# Output: Error: --id option is required for show action
```

### Error: Tabla no existe

```bash
php artisan mailing:nonexistent list
# Output: Error listing records: SQLSTATE[42S02]: Base table...
```

## Tips y Trucos

### 1. Redireccionar Salida a Archivo

```bash
php artisan mailing:mail-lists list > lista_emails.txt
```

### 2. Listar en Formato JSON (mediante piping)

```bash
php artisan mailing:subscribers list | jq .
```

### 3. Contar Registros

```bash
php artisan mailing:subscribers list | wc -l
```

### 4. Buscar en Resultados

```bash
php artisan mailing:subscribers list | grep "subscribed"
```

### 5. Crear con Confirmación

```bash
php artisan mailing:mail-lists create \
  --name="Nueva Lista" \
  --customer_id=1 \
  && echo "Éxito!" \
  || echo "Error"
```

## Performance y Limitaciones

- **Limit**: Sin límite explícito en listados
- **Batch Operations**: Para muchos registros, considerar scripts PHP
- **Large Tables**: Para >10000 registros, usar paginación manual
- **Memory**: Los agentes cargan todo en memoria para listar

Para operaciones muy grandes, considerar usar Eloquent directamente en Tinker.
