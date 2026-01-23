# Estructura Organizada de Migraciones

## Overview

Las 196 migraciones del proyecto han sido organizadas en 8 categorías temáticas para mejorar la navegación y mantenibilidad del código.

## Estructura de Carpetas

```
database/migrations/
├── core/          (20 archivos) - Núcleo de la aplicación
├── auth/          ( 2 archivos) - Autenticación y autorización
├── documents/     (42 archivos) - Gestión de documentos
├── products/      (47 archivos) - Productos y suppliers
├── returns/       (25 archivos) - Devoluciones
├── helpdesk/      (38 archivos) - Sistema de soporte/tickets
├── mail/          (12 archivos) - Templates de email
└── webhooks/      (10 archivos) - Webhooks e integraciones
```

## Detalles por Categoría

### 1. Core (20 migraciones)
Tablas fundamentales para el funcionamiento de la aplicación:
- Users, Roles, Permissions
- Categories, Shops, Languages
- Settings, Notifications
- Media (folders, files)
- Application logs
- Sessions

**Ubicación**: `/database/migrations/core/`

### 2. Auth (2 migraciones)
Gestión de autenticación y autorización:
- Role management
- Group management

**Ubicación**: `/database/migrations/auth/`

### 3. Documents (42 migraciones)
Sistema de gestión de documentos:
- Document, Document Types
- Document Status, Status History, Status Transitions
- Document Validation (stages, conditions, history)
- Document Requirements & Translations
- Document Actions, Notes, Products
- Document SLA Policies & Breaches
- Document Sources, Loads, Syncs
- Document Storage Configuration
- Stage Email Actions
- Document Upload Types

**Ubicación**: `/database/migrations/documents/`

### 4. Products (47 migraciones)
Productos y gestión de suppliers:
- Products, Categories, Components
- Suppliers & Supplier Sources
- Supplier Automation (workflows, executions, triggers, etc.)
- Supplier AI Content & Validation
- Supplier Prompts & Experiments
- Supplier Categories & Product Images
- Supplier Extraction (mappings, results, batches)
- Warranties & Warranty Claims
- Returns (related product data)
- Manufacturers, Countries
- Product Locations, Store Locations
- Product Return Rules
- IP Locations

**Ubicación**: `/database/migrations/products/`

### 5. Returns (25 migraciones)
Sistema de devoluciones:
- Return States, Status, Types, Reasons
- Return Requests & Request Products
- Return Status History
- Return Costs, Communications, Barcodes
- Return Documents, Attachments, Payments
- Return Discussions, History
- Return Exceptions, Inspections
- Return Policies, Validations
- Return PDF Documents
- Order Components (related)
- Component Shipments (related)

**Ubicación**: `/database/migrations/returns/`

### 6. Helpdesk (38 migraciones)
Sistema de soporte/tickets:
- Helpdesk Customers
- Ticket Statuses, Categories, Groups
- Tickets, Ticket Items, Comments, Notes
- Ticket Mails, Histories, Watchers, Reads
- Ticket SLA Policies & Breaches
- Ticket Views, Groups, Canned Replies
- Conversations, Conversation Items
- Conversation Statuses, Reads, Tags, Views
- Helpcenter Categories, Articles, Tags
- Campaigns, Campaign Templates, Impressions
- Customer Sessions, Page Visits
- Attributes, Agent Settings
- AI Agents

**Ubicación**: `/database/migrations/helpdesk/`

### 7. Mail (12 migraciones)
Sistema de templates de email:
- Mail Templates, Layouts
- Mail Variables, Endpoints
- Mail Language Translations
- Mail Endpoint Logs
- FAQ Tables
- Template Tables
- Layout Tables

**Ubicación**: `/database/migrations/mail/`

### 8. Webhooks (10 migraciones)
Integraciones y webhooks:
- Webhook Integrations, Events
- Webhook API Keys, Subscriptions
- Webhook Deliveries, Delivery Logs
- Webhook Event Catalog
- Webhook Subscription Rules
- PrestaShop Category Mappings
- Category Hierarchy & Sync

**Ubicación**: `/database/migrations/webhooks/`

## Cómo Funciona

### Symlinks

Todas las migraciones están disponibles en `/database/migrations/` mediante symlinks (enlaces simbólicos):

```
/database/migrations/2025_12_20_010000_create_documents_table.php
    └── symlink apunta a: documents/2025_12_20_010000_create_documents_table.php
```

Esto permite que:
1. Laravel encuentre las migraciones automáticamente
2. Las migraciones estén organizadas por función
3. No sea necesario cambiar el código de la aplicación

### Ejecución de Migraciones

Laravel sigue funcionando normalmente:

```bash
# Ejecutar todas las migraciones
php artisan migrate

# Ejecutar una migración específica
php artisan migrate --path=/database/migrations/documents/

# Ver estado
php artisan migrate:status
```

## Beneficios

1. **Mejor Navegación**: Fácil encontrar migraciones relacionadas
2. **Mantenibilidad**: Agrupación lógica por función
3. **Escalabilidad**: Estructura que permite crecer
4. **Sin Cambios de Código**: Laravel sigue funcionando igual
5. **Portabilidad**: Symlinks relativos funcionan en cualquier ubicación

## Notas Importantes

- NO mover o cambiar el nombre de las migraciones
- Los symlinks son relativos, no absolutos (portables)
- Laravel continúa ejecutando las migraciones en orden cronológico
- La estructura es transparente para la aplicación

## Gestión de Nuevas Migraciones

Cuando crees nuevas migraciones:

```bash
# Crea la migración normalmente
php artisan make:migration create_users_table

# Se creará en /database/migrations/
# Luego muévela manualmente a su categoría:
mv database/migrations/YYYY_MM_DD_*.php database/migrations/CATEGORIA/
ln -s CATEGORIA/YYYY_MM_DD_*.php database/migrations/YYYY_MM_DD_*.php
```

O crea el symlink automáticamente en tu workflow CI/CD.

---

**Última actualización**: 29 Diciembre 2025
**Total de migraciones**: 196
**Categorías**: 8
