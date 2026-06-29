

## Resumen ejecutivo

Sin estas acciones, los siguientes endpoints del API del ERP están caídos o degradados:

| Endpoint | Comportamiento actual | Causa |
|---|---|---|
| `GET /customer/search?q=<email>` | timeout >20s | Sin índice en `EMAIL` |
| `GET /customer/search?q=<celular>` | timeout >20s | Sin índice en `TELEFONO` |
| `GET /customer/{id}/orders` | Primera carga ~28s (background), luego <1s desde caché | Sin índice en `IDCLIENTE` en `PEDIDOCLI_CENTRAL` |
| `GET /customer/{id}/lopd` (historial) | Historial vacío | Sin índice en `IDCLIENTE` en `CLIENTE_LOPD_HIST` |

> **Nota sobre pedidos**: se implementó carga asíncrona en background para evitar timeouts HTTP. La primera petición responde en <1s con `{loading: true}`; el proceso background corre el full scan Oracle (~28s) y cachea el resultado. Las peticiones siguientes dentro de 1 hora responden en <100ms. Con el índice `IDX_PEDIDOCLI_IDCLIENTE`, la primera carga también sería <1s.

---

## 1. Índices a crear

Ordenados por impacto. Ejecutar como DBA en el esquema `DEVELOPER`.

### 1.1 Búsqueda de clientes por teléfono/celular

**Tabla:** `DEVELOPER.CLIENTETELEFONO_CENT`  
**Impacto:** sin índice, la búsqueda por número de teléfono hace full scan (>20s → timeout).  
**Tiempo actual:** timeout · **Tiempo esperado tras índice:** <1s

```sql
CREATE INDEX IDX_CLIENTETELEFONO_TELEFONO
    ON DEVELOPER.CLIENTETELEFONO_CENT (TELEFONO);
```

### 1.2 Búsqueda de clientes por correo electrónico

**Tabla:** `DEVELOPER.CLIENTE_CENT`  
**Impacto:** sin índice, la búsqueda por email hace full scan (>20s → timeout).  
**Tiempo actual:** timeout · **Tiempo esperado tras índice:** <0.5s

```sql
CREATE INDEX IDX_CLIENTE_UPPER_EMAIL
    ON DEVELOPER.CLIENTE_CENT (UPPER(EMAIL));
```

### 1.3 Pedidos por cliente ⭐ MÁS CRÍTICO

**Tabla:** `DEVELOPER.PEDIDOCLI_CENTRAL`  
**Impacto:** sin índice, `WHERE IDCLIENTE = :id` hace full scan de toda la tabla.  
- Full scan medido: **~28s en buffer frío**, **~14s en buffer caliente**, **87s con PARALLEL(4)**  
- Actualmente mitigado con carga asíncrona (background, ~28s) + caché Redis 1 hora  
- **Con el índice**: primera carga pasará de 28s a <1s, eliminando la necesidad del caché workaround

```sql
CREATE INDEX IDX_PEDIDOCLI_IDCLIENTE
    ON DEVELOPER.PEDIDOCLI_CENTRAL (IDCLIENTE, FBAJA);
```

### 1.4 Historial LOPD por cliente

**Tabla:** `DEVELOPER.CLIENTE_LOPD_HIST`  
**Impacto:** sin índice, el historial de aceptaciones LOPD del cliente devuelve vacío.  
**Filas en tabla:** ~359.178

```sql
CREATE INDEX IDX_LOPDH_IDCLIENTE
    ON DEVELOPER.CLIENTE_LOPD_HIST (IDCLIENTE, FACEPTACION_LOPD);
```

### 1.5 Filtro de clientes por categoría

**Tabla:** `DEVELOPER.CLIENTE_CENT`  
**Impacto:** el endpoint `/categorias/clientes/{id}` hace full scan para cargar los clientes de una categoría.

```sql
CREATE INDEX IDX_CLIENTE_IDCATEGORIA
    ON DEVELOPER.CLIENTE_CENT (IDCATEGORIA_CLIENTE, FBAJA);
```

### 1.6 Búsqueda de clientes por apellidos y nombre

**Tabla:** `DEVELOPER.CLIENTE_CENT`  
**Impacto:** búsqueda por texto en el campo apellidos/nombre hace full scan cuando no hay coincidencias exactas (puede ser lenta).

```sql
CREATE INDEX IDX_CLIENTE_UPPER_APELL
    ON DEVELOPER.CLIENTE_CENT (UPPER(APELLIDOS), FBAJA);

CREATE INDEX IDX_CLIENTE_UPPER_NOMBRE
    ON DEVELOPER.CLIENTE_CENT (UPPER(NOMBRE), FBAJA);
```

---

## 3. Tabla de impacto por acción

| Prioridad | Acción | Endpoints desbloqueados | Tiempo actual → esperado |
|---|---|---|---|
| 🔴 Alta | `IDX_CLIENTETELEFONO_TELEFONO` | Búsqueda por celular | timeout → <1s |
| 🔴 Alta | `IDX_CLIENTE_UPPER_EMAIL` | Búsqueda por correo | timeout → <0.5s |
| 🔴 Alta | `IDX_PEDIDOCLI_IDCLIENTE` | `/customer/{id}/orders` | vacío → operativo |
| 🔴 Alta | GRANT SELECT (7 tablas) | tarjetas, cuentas, deudas, cobros, facturas, bonos, vales | error 500 → operativo |
| 🟡 Media | `IDX_LOPDH_IDCLIENTE` | `/customer/{id}/lopd` historial | vacío → operativo |
| 🟡 Media | `IDX_CLIENTE_IDCATEGORIA` | `/categorias/clientes/{id}` | lento → <1s |
| 🟢 Baja | `IDX_CLIENTE_UPPER_APELL` + `IDX_CLIENTE_UPPER_NOMBRE` | Búsqueda por nombre | 3–20s → <1s |
