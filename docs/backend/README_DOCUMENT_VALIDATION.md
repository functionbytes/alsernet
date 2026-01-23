# 📋 Sistema de Validación de Documentos - Documentación Completa

## 🎯 Descripción General

El sistema de validación de documentos es una característica completa y flexible que permite:

✅ **Validación Multi-Etapa** - Documentos pasan por múltiples fases de validación consecutivas
✅ **Grupos Validadores** - Equipos con diferentes perfiles (Primary/Backup) y modos de asignación
✅ **Archivos Adicionales** - Carga de documentos complementarios durante el proceso
✅ **Configuraciones Granulares** - Control total sobre qué puede hacer cada grupo
✅ **Historial Completo** - Auditoría total de cambios de configuración y validaciones

---

## 📚 Documentación Disponible

### 1️⃣ **QUICK_START_VALIDATION.md** (¡EMPIEZA AQUÍ!)
   **Para:** Implementación rápida, ejemplos prácticos
   **Contiene:**
   - Flujo básico en 5 pasos
   - Consultas SQL rápidas
   - Casos de uso comunes
   - Tests básicos

   ⏱️ **Lectura:** 5-10 minutos

---

### 2️⃣ **document_validation_features.md** (COMPLETO)
   **Para:** Entender todas las funcionalidades en profundidad
   **Contiene:**
   - Validación por Etapas (Stages)
   - Perfiles de Validadores (3+ niveles)
   - Archivos Adicionales
   - Validaciones por Grupo
   - Estructura de base de datos
   - Flujos completos
   - API Endpoints recomendados

   ⏱️ **Lectura:** 15-20 minutos

---

### 3️⃣ **validator_groups_guide.md** (REFERENCIA)
   **Para:** Gestión detallada de Validator Groups
   **Contiene:**
   - Estructura completa de tablas
   - Modelos de Eloquent completos
   - Gestión de Validadores
   - Configuraciones por Grupo
   - Historial de Cambios
   - Ejemplos avanzados

   ⏱️ **Lectura:** 20-30 minutos

---

## 🏗️ Arquitectura del Sistema

```
┌─────────────────────────────────────────────────────────────┐
│                        DOCUMENTO (Document)                  │
│                                                               │
│  current_stage: 1  │  current_stage: 2  │  current_stage: 3 │
│  validation_status: pending                                   │
└─────────────────────────────────────────────────────────────┘
         ↓                    ↓                    ↓
┌──────────────────┐ ┌──────────────────┐ ┌──────────────────┐
│  VALIDATOR GROUP │ │  VALIDATOR GROUP │ │  VALIDATOR GROUP │
│  "documentacion" │ │"revisione_tecnica"│ │"aprobacion_final"│
├──────────────────┤ ├──────────────────┤ ├──────────────────┤
│ PRIMARY          │ │ PRIMARY          │ │ PRIMARY          │
│ • Usuario 1      │ │ • Usuario 6      │ │ • Usuario 8      │
│ • Usuario 2      │ │ • Usuario 7      │ │                  │
│ • Usuario 3      │ │                  │ │ BACKUP           │
│                  │ │ BACKUP           │ │ • Usuario 9      │
│ BACKUP           │ │ • Usuario 5      │ │                  │
│ • Usuario 4      │ │                  │ │ MODE: round_robin│
│ • Usuario 5      │ │ MODE: load_bal.  │ │                  │
│                  │ │                  │ │ CONFIGURATION:   │
│ MODE: round_robin│ │ CONFIGURATION:   │ │ • can_approve    │
│                  │ │ • can_approve    │ │ • final_authority│
│ CONFIGURATION:   │ │ • can_reject     │ │ • override_prev  │
│ • can_approve    │ │ • check_calcs    │ │                  │
│ • can_reject     │ │                  │ │ HISTORY: ✓       │
│ • check_complete │ │ HISTORY: ✓       │ │                  │
│                  │ │                  │ │                  │
│ HISTORY: ✓       │ │                  │ │                  │
└──────────────────┘ └──────────────────┘ └──────────────────┘
         ↓                    ↓                    ↓
    DocumentValidationHistory (Tabla de Auditoría)
    - stage_number: 1, 2, 3
    - validator_group: "documentacion", "revisione_tecnica", etc.
    - validator_user_id: ID del validador
    - action: approved, rejected, pending_revision
    - comments: Comentarios del validador
    - validated_at: Timestamp
```

---

## 🔄 Flujo Completo de Validación

```
┌─ ETAPA 1: DOCUMENTACIÓN ─────────────────────────────────────┐
│                                                                │
│ 1. Cliente sube documentos requeridos                        │
│    └─ Document.current_stage = 1                             │
│    └─ Document.validation_status = 'pending'                 │
│                                                               │
│ 2. Sistema asigna a Grupo "documentacion"                    │
│    └─ Obtiene próximo validador (round_robin)               │
│    └─ Document.assigned_user_id = validador_id              │
│    └─ Document.validation_status = 'in_progress'            │
│                                                               │
│ 3. Validador puede:                                          │
│    ✓ Cargar archivos adicionales (additional_attachments)   │
│    ✓ Revisar documentos                                      │
│    ✓ Validar según configuración del grupo                  │
│       (check_completeness, verify_signatures, etc.)         │
│                                                               │
│ 4. Validador toma decisión:                                  │
│                                                               │
│    ├─ APROBADO ──────────────────────────────┐              │
│    │  • DocumentValidationHistory.create()    │              │
│    │    (stage=1, action='approved')          │              │
│    │  • Avanzar a siguiente etapa             │              │
│    │    (current_stage = 2)                   │              │
│    │                                          │              │
│    └─────────────────────────────────────────┘              │
│                                                               │
│    ├─ RECHAZADO ─────────────────────────────┐              │
│    │  • DocumentValidationHistory.create()    │              │
│    │    (stage=1, action='rejected')          │              │
│    │  • validation_status = 'awaiting_revision'│             │
│    │  • Cliente sube documentos faltantes     │              │
│    │  • Vuelve a cola para etapa 1            │              │
│    │                                          │              │
│    └─────────────────────────────────────────┘              │
│                                                               │
└────────────────────────────────────────────────────────────────┘

┌─ ETAPA 2: REVISIÓN TÉCNICA ──────────────────────────────────┐
│ (Repetir proceso similar con grupo "revisione_tecnica")     │
│ • Validador diferente                                        │
│ • Configuraciones diferentes                                │
│ • Puede cargar archivos adicionales                         │
│ • Historial registrado con stage_number = 2                │
└────────────────────────────────────────────────────────────────┘

┌─ ETAPA 3: APROBACIÓN FINAL ──────────────────────────────────┐
│ (Última etapa con grupo "aprobacion_final")                 │
│ • Aprobación final                                           │
│ • validation_status = 'completed'                            │
│ • validation_completed_at = now()                            │
└────────────────────────────────────────────────────────────────┘
```

---

## 📊 Estructuras de Base de Datos

### Tabla: `documents`
```sql
current_stage           INT         -- Etapa actual (1, 2, 3...)
total_stages            INT         -- Total de etapas
current_validator_group VARCHAR     -- Clave del grupo actual
assigned_user_id        BIGINT      -- Validador asignado
validation_status       VARCHAR     -- pending, in_progress, completed, etc.
validation_started_at   TIMESTAMP   -- Cuándo inició validación
validation_completed_at TIMESTAMP   -- Cuándo completó
additional_attachments  JSON        -- Metadatos de archivos adicionales
```

### Tabla: `document_types`
```sql
validation_stages       JSON        -- Array de etapas configuradas
                                    [
                                      {
                                        "key": "documentacion",
                                        "order": 1,
                                        "conditions": {}
                                      },
                                      ...
                                    ]
```

### Tabla: `document_validation_history`
```sql
document_id             BIGINT      -- FK documents
stage_number            INT         -- Etapa (1, 2, 3...)
validator_group         VARCHAR     -- Grupo que validó
validator_user_id       BIGINT      -- Usuario que validó
action                  VARCHAR     -- approved, rejected, pending_revision
comments                TEXT        -- Comentarios del validador
validated_at            TIMESTAMP   -- Cuándo se validó
```

### Tabla: `validator_groups`
```sql
uid                     VARCHAR     -- Identificador único
name                    VARCHAR     -- Nombre del grupo
key                     VARCHAR     -- Clave única (documentacion, etc.)
assignment_mode         VARCHAR     -- round_robin, load_balanced, first_available
is_default              BOOLEAN     -- Es grupo por defecto
is_active               BOOLEAN     -- Activo
sort_order              INT         -- Orden
```

### Tabla: `validator_group_user` (Pivote)
```sql
validator_group_id      BIGINT      -- FK validator_groups
user_id                 BIGINT      -- FK users
priority                VARCHAR     -- primary, backup
```

### Tabla: `validator_group_configurations`
```sql
validator_group_id      BIGINT      -- FK validator_groups
key                     VARCHAR     -- can_approve, can_reject, etc.
value                   BOOLEAN     -- true/false
category                VARCHAR     -- permissions, validations, limits
```

### Tabla: `validator_group_configuration_histories`
```sql
validator_group_id      BIGINT      -- FK validator_groups
user_id                 BIGINT      -- FK users (quién hizo cambio)
key                     VARCHAR     -- Configuración que cambió
change_type             VARCHAR     -- created, updated, deleted
old_value               JSON        -- Valor anterior
new_value               JSON        -- Valor nuevo
changed_at              TIMESTAMP   -- Cuándo cambió
```

---

## 🎯 Casos de Uso Principales

### 1. Validación de Documentos Financieros
- **Etapas:** Documentación → Revisión Técnica → Aprobación Final
- **Grupos:** 3 equipos especializados
- **Requisito:** Archivos adicionales frecuentes
- **Ver:** `QUICK_START_VALIDATION.md` → Caso 1

### 2. Control de Procesos
- **Validación multi-usuario** en equipos
- **Rotación de validadores** (Round Robin)
- **Historial completo** de cambios
- **Ver:** `validator_groups_guide.md` → Ejemplos Prácticos

### 3. Escalado Automático
- **Load Balancing** entre validadores
- **Asignación inteligente** según carga
- **Backup automático** si no disponible
- **Ver:** `document_validation_features.md` → Modos de Asignación

---

## 🔧 Implementación Rápida (Checklist)

- [ ] Leer `QUICK_START_VALIDATION.md`
- [ ] Crear `DocumentType` con `validation_stages`
- [ ] Crear `ValidatorGroup` para cada etapa
- [ ] Agregar `users` a grupos (PRIMARY + BACKUP)
- [ ] Crear `ValidatorGroupConfiguration` por grupo
- [ ] Implementar controlador de documentos
- [ ] Crear vistas para validadores
- [ ] Hacer tests
- [ ] Desplegar

---

## 📖 Buscar por Tema

| Quiero... | Leer |
|-----------|------|
| Implementar rápido | `QUICK_START_VALIDATION.md` |
| Entender todas las funcionalidades | `document_validation_features.md` |
| Gestionar ValidatorGroups | `validator_groups_guide.md` |
| Consultas SQL útiles | `QUICK_START_VALIDATION.md` → Consultas Rápidas |
| Crear configuraciones de grupo | `validator_groups_guide.md` → Configuraciones por Grupo |
| Ver historial de cambios | `validator_groups_guide.md` → Historial de Cambios |
| Tests completos | `document_validation_features.md` → Tests |
| Arquitectura del sistema | Este archivo → Arquitectura |

---

## 🚀 Próximos Pasos

1. **Implementación Frontend**
   - UI para validadores (tabla de documentos pendientes)
   - Formulario de validación
   - Carga de archivos adicionales
   - Historial de validación

2. **Integraciones**
   - Notificaciones por email cuando se asigna documento
   - Recordatorios automáticos
   - Webhooks para sistemas externos

3. **Reportes**
   - Tiempo promedio de validación por grupo
   - Tasa de aprobación/rechazo
   - Carga de trabajo por validador
   - Historial de cambios de configuración

4. **Optimizaciones**
   - Caché de configuraciones
   - Índices de base de datos
   - Archivado de historiales viejos

---

## 📞 Soporte

- **¿Preguntas sobre implementación?** → Ver `QUICK_START_VALIDATION.md`
- **¿Detalles técnicos?** → Ver `document_validation_features.md`
- **¿Gestión de ValidatorGroups?** → Ver `validator_groups_guide.md`
- **¿Modelos de Eloquent?** → Ver código directamente en `app/Models/`

---

## 📝 Cambios Recientes

**Fecha:** 21 de Diciembre de 2025

### Nuevas Funcionalidades
✅ Validación por etapas (1-N etapas configurables)
✅ Perfiles de validadores (Primary + Backup + custom modes)
✅ Carga de archivos adicionales por etapa
✅ Configuraciones granulares por grupo validador
✅ Historial completo de cambios de configuración
✅ Asignación inteligente (Round Robin, Load Balanced, etc.)

### Migración
- Agregado campo `label` a `document_types`
- Agregados campos de validación a `documents`
- Nuevas tablas: `validator_groups`, `validator_group_user`, `validator_group_configurations`, `validator_group_configuration_histories`

---

**Última actualización:** 21 de Diciembre de 2025
**Versión:** 1.0
**Autor:** Sistema Alsernet
