# Análisis de Sincronización: Event Manager (Laravel ↔ PrestaShop)

## Estructura Actual

### PrestaShop (EventModel)
**Tabla:** `{prefix}alsernet_event_manager`

Campos:
- `id_event` (PRIMARY KEY)
- `title` (VARCHAR 255) - requerido
- `start_at` (DATE)
- `end_at` (DATE)
- `filter_tag` (VARCHAR) - etiqueta de filtro
- `management_tag` (VARCHAR) - etiqueta de gestión
- `color_buttom` (VARCHAR) - color del botón
- `hover_buttom` (VARCHAR) - color hover del botón
- `banners` (VARCHAR)
- `unique_banners` (VARCHAR)
- `cms` (VARCHAR)
- `featured` (BOOLEAN)
- `amazing` (BOOLEAN)
- `available` (BOOLEAN)
- `completed` (BOOLEAN)
- `iva` (FLOAT)
- `is_processing` (BOOLEAN)
- `processed` (BOOLEAN)
- `priority_flag` (INT)
- `color_flag` (VARCHAR)
- `banners_disabled` (BOOLEAN)
- `created_at` (DATETIME)
- `updated_at` (DATETIME)

**Tablas Relacionadas:**
- `{prefix}alsernet_event_manager_categories` (id_event, id_category)
- `{prefix}alsernet_event_manager_lang` (id_event, id_lang, title, special, url_special, title_special, buttom_all, buttom_one)

---

### Laravel (Event Model)
**Tabla:** `aalv_Alsernet_event_manager`

Campos:
- `id` (PRIMARY KEY, AUTO INCREMENT)
- `uid` (UUID) - identificador único
- `title` (VARCHAR 255)
- `color_flag` (VARCHAR)
- `filter_flag` (VARCHAR) ← corresponde a `filter_tag` en PS
- `management_flag` (VARCHAR) ← corresponde a `management_tag` en PS
- `priority_flag` (INT)
- `color_buttom` (VARCHAR)
- `hover_buttom` (VARCHAR)
- `cms` (VARCHAR)
- `featured` (BOOLEAN)
- `amazing` (BOOLEAN)
- `available` (BOOLEAN)
- `completed` (BOOLEAN)
- `iva` (FLOAT)
- `processing` (BOOLEAN) ← corresponde a `is_processing` en PS
- `processed` (BOOLEAN)
- `banners_unique` (VARCHAR)
- `banners` (VARCHAR)
- `banners_backup` (VARCHAR)
- `start_at` (DATETIME)
- `end_at` (DATETIME)
- `deleted_at` (DATETIME) - soft deletes
- `created_at` (TIMESTAMP)
- `updated_at` (TIMESTAMP)

**Modelos Relacionados:**
- `EventCategory` - tabla `aalv_Alsernet_event_manager_categories`
- `EventLang` - tabla `aalv_Alsernet_event_manager_lang`

---

## Diferencias Identificadas

### 1. Identificadores
| PrestaShop | Laravel |
|-----------|---------|
| `id_event` | `id` + `uid` |

**Implicación:** Laravel usa UUID para sincronización distribuida, PrestaShop usa ID secuencial.

### 2. Nomenclatura de Campos
| PrestaShop | Laravel | Descripción |
|-----------|---------|------------|
| `filter_tag` | `filter_flag` | Etiqueta/bandera de filtro |
| `management_tag` | `management_flag` | Etiqueta/bandera de gestión |
| `is_processing` | `processing` | Estado de procesamiento |
| `unique_banners` | `banners_unique` | Nombres invertidos |

### 3. Campos Adicionales
**Solo en PrestaShop:**
- `banners_disabled` - indica si los banners están deshabilitados

**Solo en Laravel:**
- `banners_backup` - copia de seguridad de banners
- `uid` - identificador único (UUID)
- `deleted_at` - soft deletes

### 4. Conexiones a Base de Datos
- **PrestaShop:** Tabla sin prefijo especial (configuración del módulo)
- **Laravel:** Tablas con prefijo `aalv_`

### 5. Nombres de Tablas
- **PrestaShop:** `alsernet_event_manager`
- **Laravel:** `aalv_Alsernet_event_manager` (con prefijo y capitalización diferente)

---

## Problemas Actuales

1. **Nomenclatura inconsistente:** Los campos tienen nombres diferentes entre ambos sistemas
2. **Identificadores diferentes:** PrestaShop usa ID secuencial, Laravel usa UUID
3. **Estructura de conexión:** EventCategory y EventLang en Laravel usan conexión 'prestashop', Event usa default
4. **Campos faltantes:**
   - PrestaShop carece de `uid`, `banners_backup`, `deleted_at`
   - Laravel carece de `banners_disabled`
5. **Falta observaciones:** Ninguno de los dos sistemas tiene campo para observaciones/notas

---

## Plan de Sincronización

### Fase 1: Alineación de Estructura
1. Crear migración en Laravel para:
   - Agregar campo `observations` (TEXT) a la tabla principal
   - Asegurar que `uid` sea único
   - Sincronizar nombres de campos (convertir `filter_flag` → `filter_tag`, etc.)

2. Actualizar modelos Laravel:
   - Sincronizar nombres de atributos con PrestaShop
   - Agregar `banners_disabled`
   - Crear métodos de conversión/mapeo

3. Actualizar EventCategory y EventLang:
   - Asegurar que usen la misma conexión que Event
   - Validar relaciones

### Fase 2: API de Sincronización
1. Crear endpoints REST en Laravel para:
   - GET `/api/events` - listar eventos
   - POST `/api/events` - crear evento
   - PUT `/api/events/{uid}` - actualizar evento
   - DELETE `/api/events/{uid}` - eliminar evento

2. Crear webhook handlers en Laravel para recibir cambios desde PrestaShop

3. Crear servicio de sincronización que:
   - Mapee campos entre sistemas
   - Resuelva conflictos (último en escribir gana)
   - Registre historial de sincronización

### Fase 3: Actualizar Vistas y Controladores
1. Agregar campo de observaciones a:
   - create.blade.php
   - edit.blade.php
   - show.blade.php

2. Mejorar validación y manejo de errores

3. Agregar notificaciones de sincronización

---

## Recomendaciones

1. **Usar UUID como identificador principal** en ambos sistemas para sincronización distribuida
2. **Standarizar nombres de campos** - elegir convención consistente (ej: usar `_flag` para flags)
3. **Crear tabla de sincronización** para registrar qué eventos se han sincronizado
4. **Implementar versionamiento** de eventos para resolver conflictos
5. **Usar timestamp compatible** (UNIX) para sincronización precisa
6. **Agregar campo `external_id`** para mapeo entre sistemas

---

## Implementación Propuesta

### Estructura Unificada (Recomendada)

**Tabla Principal:** `aalv_alsernet_event_manager`

```
id                INT UNSIGNED PRIMARY KEY AUTO_INCREMENT
uid               VARCHAR(36) UNIQUE NOT NULL
title             VARCHAR(255) NOT NULL
start_at          DATETIME NOT NULL
end_at            DATETIME NOT NULL
filter_flag       VARCHAR(255)  # unificado: filter_tag → filter_flag
management_flag   VARCHAR(255)  # unificado: management_tag → management_flag
priority_flag     INT
color_flag        VARCHAR(255)
color_buttom      VARCHAR(255)
hover_buttom      VARCHAR(255)
cms               VARCHAR(255)
featured          BOOLEAN DEFAULT FALSE
amazing           BOOLEAN DEFAULT FALSE
available         BOOLEAN DEFAULT TRUE
completed         BOOLEAN DEFAULT FALSE
iva               DECIMAL(10,2)
processing        BOOLEAN DEFAULT FALSE
processed         BOOLEAN DEFAULT FALSE
banners_unique    VARCHAR(255)
banners           TEXT
banners_backup    TEXT
banners_disabled  BOOLEAN DEFAULT FALSE
observations      TEXT  # NUEVO: para notas/observaciones
external_id       VARCHAR(255)  # para mapeo con PrestaShop
sync_status       ENUM('pending','synced','failed')
sync_timestamp    TIMESTAMP
created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP
updated_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
deleted_at        TIMESTAMP NULL  # soft deletes
```

**Ventajas:**
- Campo único para sincronización (`uid`)
- Nomenclatura consistente
- Rastreo de sincronización
- Observaciones incluidas
- Compatible con ambos sistemas

---

## Próximos Pasos

1. Crear migración de alineación
2. Actualizar modelos
3. Implementar controladores para observaciones
4. Crear API REST de sincronización
5. Implementar webhook handlers
6. Pruebas de sincronización end-to-end
