# Índices Oracle pendientes — Integración ERP / Helpdesk

**Fecha**: 2026-05-20  
**Schema**: `DEVELOPER`  
**Base de datos**: Oracle 11g · `192.168.253.8:1521` · servicio `GESTCENT`  
**Usuario de lectura**: `lectura`  
**Nota**: los índices los debe crear un usuario con privilegio DBA (no el usuario `lectura`).

---

## Prioridad ALTA — Afectan al flujo de atención al cliente

### 1. Búsqueda de cliente por email

**Problema**: Cuando un cliente escribe un correo al Helpdesk, el sistema busca su ficha en el ERP por email. Sin este índice hace un full scan de toda la tabla `CLIENTE_CENT` (~5 segundos por búsqueda).

**Tabla afectada**: `DEVELOPER.CLIENTE_CENT`  
**Columna**: `EMAIL` (se usa con `UPPER()` para ignorar mayúsculas)

```sql
CREATE INDEX IDX_CLIENTE_CENT_EMAIL
ON DEVELOPER.CLIENTE_CENT(UPPER(EMAIL))
TABLESPACE INDX;
```

**Uso en código** (`CustomerController@search`):
```sql
WHERE FBAJA IS NULL AND UPPER(EMAIL) = :q
```

**Impacto esperado**: de ~5.000 ms → < 50 ms por consulta.

---

### 2. Búsqueda de cliente por teléfono

**Problema**: Cuando un cliente contacta por WhatsApp, el sistema intenta encontrarle en el ERP por número de teléfono. Los teléfonos están en la tabla `CLIENTETELEFONO_CENT`. Sin índice hace full scan (~5 segundos).

**Tabla afectada**: `DEVELOPER.CLIENTETELEFONO_CENT`  
**Columna**: `TELEFONO`

```sql
CREATE INDEX IDX_CLIENTETELEFONO_TELEFONO
ON DEVELOPER.CLIENTETELEFONO_CENT(TELEFONO)
TABLESPACE INDX;
```

**Uso en código** (`CustomerController@search`):
```sql
WHERE TELEFONO = :q AND ROWNUM <= :n
```

**Impacto esperado**: de ~5.000 ms → < 20 ms por consulta.

---

## Prioridad ALTA — Eliminan el modo "loading" en pedidos

### 3. Pedidos de cliente (causa el spinner "Cargando desde Oracle…")

**Problema**: La tabla `PEDIDOCLI_CENTRAL` no tiene índice en `IDCLIENTE`. Cada vez que se abren los pedidos de un cliente en el Helpdesk, Oracle hace un full scan de millones de filas (~35 segundos). Por eso la primera llamada devuelve `loading: true` y los pedidos llegan en diferido.

**Tabla afectada**: `DEVELOPER.PEDIDOCLI_CENTRAL`  
**Columnas**: `IDCLIENTE` + `FBAJA` (compuesto para filtrar bajas)

```sql
CREATE INDEX IDX_PEDIDOCLI_IDCLIENTE
ON DEVELOPER.PEDIDOCLI_CENTRAL(IDCLIENTE, FBAJA)
TABLESPACE INDX;
```

**Uso en código** (`CustomerController@orders`):
```sql
WHERE IDCLIENTE = :id AND FBAJA IS NULL
```

**Impacto esperado**: de ~35.000 ms → < 100 ms. Se elimina el modo `loading: true` y los pedidos aparecen inmediatamente en la primera carga.

---

## Prioridad MEDIA — Funcionalidad actualmente desactivada

### 4. Histórico LOPD de cliente

**Problema**: La tabla `CLIENTE_LOPD_HIST` no tiene índice en `IDCLIENTE`. El endpoint `/lopd` actualmente devuelve el histórico vacío (`history: []`) porque se desactivó la consulta para evitar un full scan de ~35 segundos.

**Tabla afectada**: `DEVELOPER.CLIENTE_LOPD_HIST`  
**Columnas**: `IDCLIENTE` + `FACEPTACION_LOPD` (para ordenar cronológicamente)

```sql
CREATE INDEX IDX_LOPDH_IDCLIENTE
ON DEVELOPER.CLIENTE_LOPD_HIST(IDCLIENTE, FACEPTACION_LOPD)
TABLESPACE INDX;
```

**Uso en código** (`CustomerController@lopd`) — actualmente comentado hasta tener el índice:
```sql
WHERE IDCLIENTE = :id ORDER BY FACEPTACION_LOPD DESC
```

**Impacto esperado**: permite mostrar el historial de consentimientos LOPD del cliente en la ficha.

---

## Prioridad MEDIA — Permisos de acceso (GRANT)

Estos no son índices sino GRANTs que faltan al usuario `lectura`. Sin ellos los endpoints de balance y facturas devuelven error 403 Oracle.

### 5. Balance financiero del cliente

```sql
-- Ejecutar como usuario propietario de las tablas (DEVELOPER o DBA)
GRANT SELECT ON DEVELOPER.FACTURACLI_CENTRAL TO lectura;
GRANT SELECT ON DEVELOPER.COBROCLI_CENTRAL    TO lectura;
GRANT SELECT ON DEVELOPER.DEUDACLI_CENTRAL    TO lectura;
```

**Afecta a**: endpoint `/balance` → `credit_limit`, `balance_pending`, `balance_invoiced` (ahora aparecen como `null`).

### 6. Facturas del cliente

```sql
GRANT SELECT ON DEVELOPER.FACTURACLI_CENTRAL  TO lectura;
GRANT SELECT ON DEVELOPER.LFACTURACLI_CENTRAL TO lectura;
```

**Afecta a**: endpoint `/invoices` → actualmente devuelve error 403.

---

## Resumen de impacto

| # | SQL | Tabla | Columna(s) | Tiempo actual | Tiempo esperado |
|---|-----|-------|------------|--------------|----------------|
| 1 | `CREATE INDEX IDX_CLIENTE_CENT_EMAIL` | `CLIENTE_CENT` | `UPPER(EMAIL)` | ~5.000 ms | < 50 ms |
| 2 | `CREATE INDEX IDX_CLIENTETELEFONO_TELEFONO` | `CLIENTETELEFONO_CENT` | `TELEFONO` | ~5.000 ms | < 20 ms |
| 3 | `CREATE INDEX IDX_PEDIDOCLI_IDCLIENTE` | `PEDIDOCLI_CENTRAL` | `IDCLIENTE, FBAJA` | ~35.000 ms | < 100 ms |
| 4 | `CREATE INDEX IDX_LOPDH_IDCLIENTE` | `CLIENTE_LOPD_HIST` | `IDCLIENTE, FACEPTACION_LOPD` | ~35.000 ms | < 50 ms |
| 5 | `GRANT SELECT` | `FACTURACLI_CENTRAL` + cobros | — | Error 403 | Funcional |
| 6 | `GRANT SELECT` | `LFACTURACLI_CENTRAL` | — | Error 403 | Funcional |

---

## Verificación después de crear los índices

Ejecutar en SQL*Plus o SQL Developer para confirmar que los índices existen:

```sql
SELECT index_name, table_name, uniqueness, status
FROM   all_indexes
WHERE  owner = 'DEVELOPER'
  AND  index_name IN (
    'IDX_CLIENTE_CENT_EMAIL',
    'IDX_CLIENTETELEFONO_TELEFONO',
    'IDX_PEDIDOCLI_IDCLIENTE',
    'IDX_LOPDH_IDCLIENTE'
  )
ORDER BY table_name;
```

Para verificar que los índices se usan (EXPLAIN PLAN):

```sql
EXPLAIN PLAN FOR
SELECT IDCLIENTE, NOMBRE, EMAIL
FROM   DEVELOPER.CLIENTE_CENT
WHERE  FBAJA IS NULL
  AND  UPPER(EMAIL) = 'CLIENTE@EJEMPLO.COM';

SELECT * FROM TABLE(DBMS_XPLAN.DISPLAY);
-- Debe aparecer "INDEX RANGE SCAN" o "INDEX UNIQUE SCAN", NO "TABLE ACCESS FULL"
```

---

## Notas para el DBA

- `TABLESPACE INDX` — ajustar al tablespace de índices que use la instalación (puede ser `USERS` u otro).
- Los índices `FUNCTION-BASED` (como el de `UPPER(EMAIL)`) requieren que el parámetro `QUERY_REWRITE_ENABLED = TRUE` esté activo (normalmente lo está por defecto en Oracle 11g).
- La creación de índices en tablas grandes puede tardar varios minutos y consumir recursos. Se recomienda ejecutarlos en horario de baja carga.
- No se necesita reiniciar la base de datos ni el manager Docker.
