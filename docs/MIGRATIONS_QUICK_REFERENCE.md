# Referencia Rápida - Migraciones Organizadas

## Estructura Visual

```
database/migrations/
├── core/         → Usuarios, roles, settings, idiomas, categorías
├── auth/         → Autenticación y control de acceso
├── documents/    → Documentos, validación, SLA, fuentes
├── products/     → Productos, suppliers, inventario
├── returns/      → Devoluciones, políticas, inspecciones
├── helpdesk/     → Tickets, conversaciones, soporte
├── mail/         → Templates, layouts, variables de email
└── webhooks/     → Integraciones, webhooks, PrestaShop
```

## Búsqueda Rápida

### Necesito encontrar migraciones de...

| Qué necesito | Carpeta | Ejemplo |
|---|---|---|
| Usuarios, roles, lenguajes | `core/` | `create_users_table.php` |
| Documentos y validación | `documents/` | `create_documents_table.php` |
| Suppliers y productos | `products/` | `create_suppliers_table.php` |
| Devoluciones | `returns/` | `create_return_requests_table.php` |
| Tickets y soporte | `helpdesk/` | `create_helpdesk_tickets_table.php` |
| Emails | `mail/` | `create_mail_templates_table.php` |
| Webhooks | `webhooks/` | `create_webhook_integrations_table.php` |

## Comandos Útiles

### Ver migraciones de una categoría
```bash
# Documentos
ls -la database/migrations/documents/

# Devoluciones
ls -la database/migrations/returns/

# Helpdesk
ls -la database/migrations/helpdesk/
```

### Ejecutar migraciones de una categoría
```bash
# Solo documentos
php artisan migrate --path=database/migrations/documents/

# Solo productos
php artisan migrate --path=database/migrations/inventaries/

# Solo returns
php artisan migrate --path=database/migrations/returns/
```

### Ver estado de migraciones
```bash
php artisan migrate:status
```

### Rollback de una categoría
```bash
# Deshacer solo documentos
php artisan migrate:rollback --path=database/migrations/documents/

# Deshacer todo
php artisan migrate:rollback
```

## Estadísticas

```
Total de migraciones: 196

Por categoría:
- documents:  42 migraciones
- products:   47 migraciones
- returns:    25 migraciones
- helpdesk:   38 migraciones
- core:       20 migraciones
- mail:       12 migraciones
- webhooks:   10 migraciones
- auth:        2 migraciones
```

## Notas Importantes

- Las migraciones se ejecutan en orden cronológico (Laravel las ordena por timestamp)
- Los symlinks son relativos, permitiendo mover el proyecto completo
- No necesitas cambiar el código de la aplicación - Laravel las encuentra automáticamente
- Puedes ver el destino de un symlink con: `readlink database/migrations/YYYY_*`

## Troubleshooting

### ¿Migración no se encuentra?
```bash
# Verificar que existe
ls -la database/migrations/categoria/YYYY_*.php

# Verificar symlink
ls -la database/migrations/YYYY_*.php

# Verificar permisos
chmod 644 database/migrations/categoria/YYYY_*.php
```

### ¿Error de symlink roto?
```bash
# Verificar destino
readlink database/migrations/YYYY_*.php

# Recrear symlink
rm database/migrations/YYYY_*.php
cd database/migrations
ln -s categoria/YYYY_*.php .
cd ../..
```

---

Para más detalles, ver `../MIGRATIONS_STRUCTURE.md`
