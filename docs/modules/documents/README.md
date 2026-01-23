# Módulo Documents - Índice de Documentación

**Última actualización:** 2025-12-28
**Versión del módulo:** 2.0 (Laravel Modular)
**Estado:** En desarrollo activo

---

## 📋 Tabla de Contenidos

- [Visión General](#visión-general)
- [Arquitectura](#arquitectura)
- [Características Principales](#características-principales)
- [Configuración](#configuración)
- [API](#api)
- [Interfaz de Usuario](#interfaz-de-usuario)
- [Base de Datos](#base-de-datos)
- [Historial de Implementación](#historial-de-implementación)
- [Changelog](#changelog)

---

## Visión General

El **Módulo Documents** es un sistema completo de gestión documental integrado en Alsernet que maneja:

- ✅ **Validación multi-etapa** con workflow configurable
- ✅ **Sistema de permisos dual** (Spatie Permission + ValidatorGroup)
- ✅ **Gestión por perfiles** (Manager, Administrative, Accounting, etc.)
- ✅ **Integración con email** personalizado y notificaciones automáticas
- ✅ **SLA tracking** con monitoreo de incumplimientos
- ✅ **Almacenamiento configurable** (local, S3, FTP, etc.)
- ✅ **API REST** para integración con sistemas externos
- ✅ **Auditoría completa** de cambios y acciones

### Ubicación del módulo

```
Modules/Documents/
├── app/
│   ├── Entities/          # Modelos Eloquent
│   ├── Http/Controllers/  # Controladores por perfil
│   ├── Policies/          # Políticas de autorización
│   ├── Services/          # Lógica de negocio
│   └── ...
├── database/
│   ├── migrations/
│   └── seeders/
├── resources/views/       # Vistas Blade por perfil
└── routes/                # Rutas web y API
```

---

## Arquitectura

### Documentos Principales

| Documento | Descripción | Fecha | Ruta |
|-----------|-------------|-------|------|
| **System Overview** | Visión general de la arquitectura del sistema | 2025-12-10 | [`architecture/system-overview.md`](./architecture/system-overview.md) |
| **Permissions System** | Sistema de permisos dual (Spatie + ValidatorGroup) | 2025-12-21 | [`architecture/permissions.md`](./architecture/permissions.md) |
| **Validation Workflow** | Flujo de validación multi-etapa | 2025-12-21 | [`architecture/workflow.md`](./architecture/workflow.md) |
| **Quick Reference** | Referencia rápida de modelos, rutas y métodos | 2025-12-10 | [`architecture/quick-reference.md`](./architecture/quick-reference.md) |

### Diagrama de Arquitectura

```
┌─────────────────────────────────────────────────────────────┐
│                    MÓDULO DOCUMENTS                          │
├─────────────────────────────────────────────────────────────┤
│                                                               │
│  ┌─────────────┐   ┌─────────────┐   ┌─────────────┐       │
│  │  Frontend   │   │  API REST   │   │  ERP Sync   │       │
│  │  (Blade)    │   │  (JSON)     │   │  (Jobs)     │       │
│  └──────┬──────┘   └──────┬──────┘   └──────┬──────┘       │
│         │                  │                  │               │
│         └──────────┬───────┴──────────────────┘              │
│                    ▼                                          │
│         ┌──────────────────────┐                             │
│         │    Controllers       │                             │
│         │  (por perfil)        │                             │
│         └──────────┬───────────┘                             │
│                    ▼                                          │
│         ┌──────────────────────┐                             │
│         │   DocumentPolicy     │◄───── Spatie Permission    │
│         │   PermissionService  │◄───── ValidatorGroup       │
│         └──────────┬───────────┘                             │
│                    ▼                                          │
│    ┌───────────────┴──────────────┐                         │
│    │                               │                         │
│    ▼                               ▼                         │
│ ┌─────────────┐          ┌──────────────────┐              │
│ │  Services   │          │    Entities      │              │
│ │             │          │    (Models)      │              │
│ │ - Email     │          │                  │              │
│ │ - Workflow  │◄────────►│ - Document       │              │
│ │ - SLA       │          │ - DocumentType   │              │
│ │ - Storage   │          │ - DocumentStatus │              │
│ └─────────────┘          │ - ...            │              │
│                          └─────────┬────────┘              │
│                                    ▼                         │
│                          ┌──────────────────┐              │
│                          │    PostgreSQL    │              │
│                          │    + Redis       │              │
│                          └──────────────────┘              │
└─────────────────────────────────────────────────────────────┘
```

---

## Características Principales

### Documentos por Feature

| Feature | Descripción | Fecha | Ruta |
|---------|-------------|-------|------|
| **Sistema de Validación** | Workflow multi-etapa con condiciones | 2025-12-21 | [`features/validation.md`](./features/validation.md) |
| **Sistema de Emails** | Notificaciones automáticas y personalizadas | 2025-12-18 | [`features/email-system.md`](./features/email-system.md) |
| **SLA Policies** | Políticas de tiempo y alertas | 2025-12-XX | [`features/sla-policies.md`](./features/sla-policies.md) |
| **Storage Configuration** | Almacenamiento multi-disco | 2025-12-22 | [`features/storage.md`](./features/storage.md) |
| **Listado por Perfil** | Filtrado de documentos según rol | 2025-12-28 | [`features/profile-based-listing.md`](./features/profile-based-listing.md) |

### Capacidades Clave

- **Validación condicional**: Diferentes workflows según tipo de documento
- **Permisos granulares**: Control fino por acción y perfil
- **Email templating**: Variables dinámicas con preview en tiempo real
- **Tracking completo**: Historial de estados, acciones y cambios
- **Multi-tenant ready**: Soporte para grupos de validadores independientes

---

## Configuración

### Documentos de Configuración

| Documento | Descripción | Fecha | Ruta |
|-----------|-------------|-------|------|
| **Setup Guide** | Guía de instalación y configuración inicial | 2025-12-XX | [`configuration/setup.md`](./configuration/setup.md) |
| **Storage Configuration** | Configuración de discos de almacenamiento | 2025-12-22 | [`configuration/storage.md`](./configuration/storage.md) |
| **Mailers Integration** | Integración con sistema de correos | 2025-12-18 | [`configuration/mailers-integration.md`](./configuration/mailers-integration.md) |
| **Environment Variables** | Variables de entorno necesarias | 2025-12-XX | [`configuration/environment.md`](./configuration/environment.md) |

### Configuración Rápida

```bash
# 1. Migrar base de datos
php artisan migrate --path=modules/Document/database/migrations

# 2. Seed de datos iniciales
php artisan db:seed --class=modules\\Document\\database\\seeders\\DocumentsDatabaseSeeder

# 3. Publicar assets (si es necesario)
php artisan vendor:publish --tag=documents-config
```

---

## API

### Documentos de API

| Documento | Descripción | Fecha | Ruta |
|-----------|-------------|-------|------|
| **Endpoints Reference** | Listado completo de endpoints | 2025-12-16 | [`api/endpoints.md`](./api/endpoints.md) |
| **Testing Guide** | Guía de testing de la API | 2025-12-16 | [`api/testing.md`](./api/testing.md) |
| **Authentication** | Autenticación JWT para API | 2025-12-XX | [`api/authentication.md`](./api/authentication.md) |

### Endpoints Principales

```http
GET    /api/documents              # Listar documentos
POST   /api/documents              # Crear documento
GET    /api/documents/{id}         # Ver documento
PUT    /api/documents/{id}         # Actualizar documento
DELETE /api/documents/{id}         # Eliminar documento
POST   /api/documents/{id}/upload  # Subir archivo
```

---

## Interfaz de Usuario

### Documentos de UI

| Documento | Descripción | Fecha | Ruta |
|-----------|-------------|-------|------|
| **Administrative Views** | Vistas para perfil Administrative | 2025-12-21 | [`ui/administrative-views.md`](./ui/administrative-views.md) |
| **Manager Views** | Vistas para perfil Manager | 2025-12-XX | [`ui/manager-views.md`](./ui/manager-views.md) |
| **Shared Components** | Componentes reutilizables | 2025-12-XX | [`ui/components.md`](./ui/components.md) |
| **UI Changes Log** | Registro de cambios de interfaz | 2025-12-21 | [`ui/changes-log.md`](./ui/changes-log.md) |

### Perfiles Soportados

- **Manager**: Gestión completa de documentos
- **Administrative**: Validación y gestión operativa
- **Accounting**: Vista de documentos contables
- **Warehouse**: Documentos de inventario
- **Weapons**: Documentos de armamento

---

## Base de Datos

### Documentos de Base de Datos

| Documento | Descripción | Fecha | Ruta |
|-----------|-------------|-------|------|
| **Schema Overview** | Esquema completo de tablas | 2025-12-XX | [`database/schema.md`](./database/schema.md) |
| **Migrations Guide** | Guía de migraciones | 2025-12-XX | [`database/migrations.md`](./database/migrations.md) |
| **Relationships** | Relaciones entre modelos | 2025-12-XX | [`database/relationships.md`](./database/relationships.md) |

### Tablas Principales

- `documents` - Documentos principales
- `document_types` - Tipos de documentos
- `document_statuses` - Estados de documentos
- `document_status_transitions` - Transiciones permitidas
- `document_validation_conditions` - Condiciones de validación
- `document_sla_policies` - Políticas de SLA
- `document_validation_history` - Historial de validaciones
- Y muchas más...

---

## Historial de Implementación

Registro cronológico de implementaciones y cambios mayores.

### 2025-12

| Fecha | Documento | Descripción |
|-------|-----------|-------------|
| 2025-12-28 | [`implementation/2025-12-28-profile-based-listing.md`](./implementation/2025-12-28-profile-based-listing.md) | Implementación de listado de documentos por perfil |
| 2025-12-22 | [`implementation/2025-12-22-storage-configuration.md`](./implementation/2025-12-22-storage-configuration.md) | Sistema de configuración de almacenamiento |
| 2025-12-21 | [`implementation/2025-12-21-validation-workflow.md`](./implementation/2025-12-21-validation-workflow.md) | Workflow de validación multi-etapa |
| 2025-12-21 | [`implementation/2025-12-21-permissions-refactor.md`](./implementation/2025-12-21-permissions-refactor.md) | Refactorización del sistema de permisos |
| 2025-12-18 | [`implementation/2025-12-18-email-integration.md`](./implementation/2025-12-18-email-integration.md) | Integración completa con sistema de emails |
| 2025-12-17 | [`implementation/2025-12-17-status-logging.md`](./implementation/2025-12-17-status-logging.md) | Sistema de logging de cambios de estado |
| 2025-12-16 | [`implementation/2025-12-16-deployment-ready.md`](./implementation/2025-12-16-deployment-ready.md) | Preparación para deployment |
| 2025-12-15 | [`implementation/2025-12-15-email-actions.md`](./implementation/2025-12-15-email-actions.md) | Acciones de email configurables |

### 2025-11 y anteriores

Ver: [`implementation/archive/`](./implementation/archive/)

---

## Changelog

### Changelog Mensual

| Mes | Ruta |
|-----|------|
| Diciembre 2025 | [`changelog/2025-12.md`](./changelog/2025-12.md) |
| Noviembre 2025 | [`changelog/2025-11.md`](./changelog/2025-11.md) |

### Últimos Cambios (Diciembre 2025)

#### 2025-12-28
- ✅ Implementado listado de documentos filtrado por perfil
- ✅ Creada estructura organizada de documentación
- ✅ Reorganización completa de archivos de documentación

#### 2025-12-22
- ✅ Sistema de configuración de almacenamiento multi-disco
- ✅ Migración de columna `disk` a tablas de media

#### 2025-12-21
- ✅ Refactorización completa del sistema de permisos
- ✅ Workflow de validación con condiciones configurables
- ✅ Mejoras en UI de gestión de documentos

#### 2025-12-18
- ✅ Integración completa con sistema de mailers
- ✅ Variables dinámicas en templates de email
- ✅ Preview de emails con datos reales

---

## 📝 Convenciones de Documentación

### Nomenclatura de Archivos

**Para archivos de implementación:**
```
implementation/YYYY-MM-DD-descripcion-corta.md
Ejemplo: implementation/2025-12-28-profile-based-listing.md
```

**Para archivos generales:**
```
carpeta/nombre-descriptivo.md
Ejemplo: features/validation.md
```

### Estructura de Documentos

Cada documento debe incluir:

```markdown
# Título del Documento

**Fecha:** YYYY-MM-DD
**Autor:** Nombre o "Claude Code"
**Estado:** En desarrollo / Completado / Deprecado
**Relacionado con:** Enlaces a docs relacionados

## Contenido...
```

### Actualización de este README

Cada vez que se agregue nueva documentación o se realice un cambio mayor:

1. Actualizar la fecha en "Última actualización"
2. Agregar entrada en la tabla correspondiente
3. Actualizar la sección de Changelog
4. Commitear con mensaje: `docs(documents): descripción del cambio`

---

## 🔗 Enlaces Útiles

- [Módulo en código fuente](../../Modules/Documents/)
- [Tests del módulo](../../Modules/Documents/tests/)
- [Documentación de Laravel Modules](https://nwidart.com/laravel-modules/)
- [Context7 - Documentación indexada](../../README.md)

---

## 👥 Contribución

Para agregar o modificar documentación del módulo Documents:

1. Crear el archivo en la carpeta apropiada
2. Seguir las convenciones de nomenclatura
3. Actualizar este README.md con la nueva entrada
4. Actualizar el changelog del mes actual
5. Commit: `docs(documents): descripción`

---

**Nota:** Este README es el punto de entrada central para toda la documentación del Módulo Documents. Mantenerlo actualizado es crítico para Context7 y el equipo de desarrollo.
