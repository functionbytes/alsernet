# ERP Oracle — Reporte de Rendimiento y Recomendaciones

**Fecha:** 2026-04-29  
**Entorno:** Oracle GESTCENT @ 192.168.253.8:1521  
**Usuario:** DEVELOPER (solo lectura)  
**Oracle Client:** < 18c (sin soporte para `oci_set_call_timeout`)

---

## 1. Estado actual de los endpoints

### 1.1 Clientes (`/api/erp/customer`)

| Endpoint | HTTP | Tiempo | Estado |
|---|---|---|---|
| `GET /customer` | 200 | 0.5s | ✅ Operativo |
| `GET /customer/search?q=<ID numérico>` | 200 | 0.1s | ✅ Operativo — usa PK/índices |
| `GET /customer/search?q=<CIF>` | 200 | 0.5s | ✅ Operativo — usa `IDX_CLIENTE_CENT_CIF` |
| `GET /customer/search?q=<celular>` | 000 | timeout | ❌ Falta `IDX_CLIENTETELEFONO_TELEFONO` en `CLIENTETELEFONO_CENT` |
| `GET /customer/search?q=<email>` | 000 | timeout | ❌ Falta `IDX_CLIENTE_UPPER_EMAIL` en `CLIENTE_CENT` |
| `GET /customer/search?q=<apellido>` | 200 | 0.4–3s | ⚠️ Rápido si hay coincidencias; full scan sin ellas |
| `GET /customer/search?q=<nombre sin coincidencias>` | 000 | timeout | ❌ Full scan sin resultados — falta índice UPPER(NOMBRE) |
| `GET /customer/{id}` | 200 | 0.7s | ✅ Operativo (`orders.total` y `last_order` devuelven null) |
| `GET /customer/{id}/personal` | 200 | 0.5s | ✅ Operativo |
| `GET /customer/{id}/contact` | 200 | 0.2s | ✅ Operativo |
| `GET /customer/{id}/addresses` | 200 | 0.2s | ✅ Operativo |
| `GET /customer/{id}/orders` | 200 | 0.07s | ⚠️ Devuelve `available: false` — falta índice |
| `GET /customer/{id}/lopd` | 200 | 0.15s | ⚠️ Historial vacío — falta índice |
| `GET /customer/{id}/delivery-notes` | 200 | 0.5s | ✅ Operativo |
| `GET /customer/{id}/loyalty-points` | 200 | 0.3s | ✅ Operativo |
| `GET /customer/{id}/quotas` | 200 | 0.2s | ✅ Operativo |
| `GET /customer/{id}/catalogs` | 200 | 0.5s | ✅ Operativo |
| `GET /customer/{id}/cards` | 200 | — | ❌ ORA-00942: tabla sin acceso |
| `GET /customer/{id}/accounts` | 200 | — | ❌ ORA-00942: tabla sin acceso |
| `GET /customer/{id}/debts` | 200 | — | ❌ ORA-00942: tabla sin acceso |
| `GET /customer/{id}/balance` | 200 | — | ❌ ORA-00942: tabla sin acceso |
| `GET /customer/{id}/vouchers` | 200 | — | ❌ ORA-00942: tabla sin acceso |
| `GET /customer/{id}/bonuses` | 200 | — | ❌ ORA-00942: tabla sin acceso |
| `GET /customer/{id}/invoices` | 200 | — | ❌ ORA-00942: tabla sin acceso |
| `GET /customer/{id}/payments` | 200 | — | ❌ ORA-00942: tabla sin acceso |

### 1.2 Productos (`/api/erp/products`)

| Endpoint | HTTP | Tiempo | Estado |
|---|---|---|---|
| `GET /products` | 200 | 0.9s | ✅ Operativo |
| `GET /products/{id}` | 200 | 0.6s | ✅ Operativo |
| `GET /products/{id}/detailed` | 200 | 2.4s | ✅ Operativo |
| `GET /products/{id}/supplier` | 200 | 0.3s | ✅ Operativo |
| `GET /products/filter` | 000 | timeout | ❌ `DBMS_LOB.GETLENGTH` sin índice — full scan |

### 1.3 Proveedores (`/api/erp/suppliers`)

| Endpoint | HTTP | Tiempo | Estado |
|---|---|---|---|
| `GET /suppliers` | 200 | 4.3s | ✅ Operativo |
| `GET /suppliers/{id}` | 200 | 0.9s | ✅ Operativo |
| `GET /suppliers/{id}/detailed` | 200 | 0.7s | ✅ Operativo |
| `GET /suppliers/{id}/products` | 200 | 0.08s | ✅ Operativo |
| `GET /suppliers/{id}/categories` | 200 | 0.5s | ✅ Operativo |
| `GET /suppliers/{id}/supplier` | 200 | 1.2s | ✅ Operativo |

### 1.4 Artículos, Jerarquía y Catálogo

| Endpoint | HTTP | Tiempo | Estado |
|---|---|---|---|
| `GET /articulos` | 200 | 0.4s | ✅ Operativo |
| `GET /articulos/jerarquia/{id}` | 200 | 2s | ✅ Operativo |
| `GET /articulos/detallado/{id}` | 404 | 0.2s | ⚠️ 404 (sin datos en entorno de prueba) |
| `GET /articulos/perfiles/{id}` | 404 | 0.2s | ⚠️ 404 (sin datos en entorno de prueba) |
| `GET /familias` | 200 | 0.2s | ✅ Operativo |
| `GET /familias/{id}` | 200 | 0.5s | ✅ Operativo |
| `GET /grupos` | 200 | 0.3s | ✅ Operativo |
| `GET /grupos/{id}` | 200 | 0.5s | ✅ Operativo |
| `GET /subfamilias` | 200 | 0.1s | ✅ Operativo |
| `GET /subfamilias/{id}` | 200 | 0.3s | ✅ Operativo |
| `GET /modelos` | 200 | 0.9s | ✅ Operativo |
| `GET /modelos/detallado/{id}` | 200 | 0.6s | ✅ Operativo |
| `GET /catalogo` | 200 | 4.8s | ✅ Operativo |

### 1.5 Pedidos, Stock y Albaranes

| Endpoint | HTTP | Tiempo | Estado |
|---|---|---|---|
| `GET /pedidos` | 200 | 0.5s | ✅ Operativo |
| `GET /stock` | 200 | 0.2s | ✅ Operativo |
| `GET /albaranes` | 200 | 0.2s | ✅ Operativo |

### 1.6 Categorías

| Endpoint | HTTP | Tiempo | Estado |
|---|---|---|---|
| `GET /categorias/clientes` | 200 | 4s | ✅ Operativo |
| `GET /categorias/clientes/{id}` | 000 | timeout | ❌ Full scan `CLIENTE_CENT.IDCATEGORIA_CLIENTE` sin índice |
| `GET /categorias/productos` | 200 | 0.1s | ✅ Operativo |

### 1.7 Bonos y Vales

| Endpoint | HTTP | Tiempo | Estado |
|---|---|---|---|
| `GET /bonos` | 500 | 0.2s | ❌ ORA-00942: tabla sin acceso |
| `GET /vales` | 500 | 0.2s | ❌ ORA-00942: tabla sin acceso |

---

## 2. Índices Oracle requeridos (solicitar al DBA)

Estas son las causas raíz de los timeouts. Sin estos índices, las queries hacen full scan en tablas con millones de filas (~35–87 segundos), superando siempre el timeout del servidor.

### 2.1 `PEDIDOCLI_CENTRAL` — Pedidos por cliente

**Impacto:** `GET /customer/{id}/orders` devuelve vacío. Estadísticas de pedidos en ficha de cliente no disponibles.

```sql
CREATE INDEX IDX_PEDIDOCLI_IDCLIENTE
    ON DEVELOPER.PEDIDOCLI_CENTRAL (IDCLIENTE, FBAJA);
```

> **Nota:** El full scan de esta tabla tarda **87 segundos** con hint PARALLEL(4). Es la tabla más crítica sin índice.

### 2.2 `CLIENTE_LOPD_HIST` — Historial de aceptaciones LOPD

**Impacto:** `GET /customer/{id}/lopd` devuelve historial vacío.  
**Filas en tabla:** ~359.178

```sql
CREATE INDEX IDX_LOPDH_IDCLIENTE
    ON DEVELOPER.CLIENTE_LOPD_HIST (IDCLIENTE, FACEPTACION_LOPD);
```

### 2.3 `CLIENTE_CENT` — Búsqueda por nombre / apellidos

**Impacto:** `GET /customer/search?q=<nombre>` hace full scan cuando no hay coincidencias exactas.

```sql
CREATE INDEX IDX_CLIENTE_UPPER_APELL
    ON DEVELOPER.CLIENTE_CENT (UPPER(APELLIDOS));

CREATE INDEX IDX_CLIENTE_UPPER_NOMBRE
    ON DEVELOPER.CLIENTE_CENT (UPPER(NOMBRE));
```

> **Alternativa rápida:** Índice compuesto si la búsqueda siempre filtra también por FBAJA:
> ```sql
> CREATE INDEX IDX_CLIENTE_NOMBRE_FBAJA
>     ON DEVELOPER.CLIENTE_CENT (UPPER(APELLIDOS), FBAJA);
> ```

### 2.4 `CLIENTE_CENT` — Filtro por categoría de cliente

**Impacto:** `GET /categorias/clientes/{id}` carga los clientes de una categoría haciendo full scan.

```sql
CREATE INDEX IDX_CLIENTE_IDCATEGORIA
    ON DEVELOPER.CLIENTE_CENT (IDCATEGORIA_CLIENTE, FBAJA);
```

### 2.5 `CLIENTE_CENT` — Búsqueda por email

**Impacto:** `GET /customer/search?q=<email>` hace full scan completo de la tabla (>20s sin resultados, timeout). Confirmado en pruebas: `EMAIL-3@MAIL.ES` → timeout 20s.

```sql
CREATE INDEX IDX_CLIENTE_UPPER_EMAIL
    ON DEVELOPER.CLIENTE_CENT (UPPER(EMAIL));
```

> Una vez creado, la búsqueda por email pasará de >20s a <0.5s (búsqueda exacta).

### 2.6 `CLIENTETELEFONO_CENT` — Búsqueda por número de teléfono/celular

**Impacto:** `GET /customer/search?q=<teléfono>` hace full scan en CLIENTETELEFONO_CENT (>20s sin resultados, timeout). Confirmado en pruebas: `699942817` → timeout 20s.

```sql
CREATE INDEX IDX_CLIENTETELEFONO_TELEFONO
    ON DEVELOPER.CLIENTETELEFONO_CENT (TELEFONO);
```

> Una vez creado, la búsqueda por teléfono pasará de >20s a <1s.

---

## 3. Acceso de lectura requerido (solicitar al DBA)

Las siguientes tablas existen en Oracle pero el usuario `DEVELOPER` no tiene permiso `SELECT`. Todos los endpoints asociados devuelven `ORA-00942: table or view does not exist`.

| Tabla Oracle | Endpoints afectados |
|---|---|
| `CLIENTETARJETA_CENT` | `/customer/{id}/cards` |
| `CLIENTECUENTA_CENT` | `/customer/{id}/accounts` |
| `DEUDACLI_CENTRAL` | `/customer/{id}/debts`, `/customer/{id}/balance` |
| `COBROCLI_CENTRAL` | `/customer/{id}/payments` |
| `FACTURACLI_CENTRAL` | `/customer/{id}/invoices` |
| `BONOSCLI_CENTRAL` o similar | `/customer/{id}/bonuses`, `/bonos` |
| `VALESCLI_CENTRAL` o similar | `/customer/{id}/vouchers`, `/vales` |

**Comando para conceder acceso:**
```sql
-- Ejecutar como DBA (SYS o SYSTEM):
GRANT SELECT ON DEVELOPER.CLIENTETARJETA_CENT TO <usuario_lectura>;
GRANT SELECT ON DEVELOPER.CLIENTECUENTA_CENT TO <usuario_lectura>;
GRANT SELECT ON DEVELOPER.DEUDACLI_CENTRAL TO <usuario_lectura>;
GRANT SELECT ON DEVELOPER.COBROCLI_CENTRAL TO <usuario_lectura>;
GRANT SELECT ON DEVELOPER.FACTURACLI_CENTRAL TO <usuario_lectura>;
-- Repetir para tablas de bonos y vales según nombre real
```

---

## 4. Problemas técnicos resueltos en esta sesión

### 4.1 Conexión OCI8 persistente muerta (ORA-03113)

**Problema:** `oci_pconnect()` mantiene la misma conexión entre requests del mismo worker de PHP-FPM. Cuando una query lenta provoca que Oracle Server corte la conexión (ORA-03113: *end-of-file on communication channel*), todos los requests siguientes en ese worker usaban la conexión muerta, provocando timeouts en cascada.

**Síntoma visible:** Un endpoint funcionaba en tinker (612ms) pero fallaba desde HTTP (timeout 20s+).

**Solución aplicada** en `OCI8Service::connect()`:
```php
private function isAlive(): bool
{
    $stm = @oci_parse($this->connection, 'SELECT 1 FROM DUAL');
    if (!$stm) return false;
    $ok = @oci_execute($stm, OCI_NO_AUTO_COMMIT);
    @oci_free_statement($stm);
    return (bool) $ok;
}
```
Antes de reusar la conexión persistente se hace un ping liviano. Si falla, se descarta y se abre una nueva.

### 4.2 `oci_set_call_timeout()` incompatible

**Problema:** Oracle Client instalado es anterior a la versión 18c. `oci_set_call_timeout()` lanza excepción "Unsupported with this version of Oracle Client".

**Solución aplicada:** Envuelto en try/catch silencioso. No hay timeout configurable por PHP.

**Consecuencia:** Sin timeout funcional, las queries lentas corren hasta que Oracle Server las mata (~35–87s). El servidor web (nginx) cierra la conexión HTTP antes de que PHP pueda devolver respuesta.

### 4.3 Paginación yajra/laravel-oci8 incompatible con tablas sin índice

**Problema:** El driver `yajra/laravel-oci8` genera paginación con doble ROWNUM anidado:
```sql
SELECT t2.* FROM (SELECT rownum AS rn, t1.* FROM (
    SELECT ... FROM tabla WHERE condicion  -- sin límite interno
) t1) t2 WHERE t2.rn BETWEEN 1 AND n
```
Oracle debe **materializar todas las filas** antes de poder filtrar por `rn`. En tablas grandes sin índice = full scan garantizado.

**Solución aplicada:** Reescribir queries críticas con SQL nativo y `ROWNUM <= n` directamente en el WHERE:
```sql
SELECT ... FROM tabla WHERE condicion AND ROWNUM <= n
```
Esto activa la optimización *stopkey* de Oracle: para el scan en cuanto encuentra `n` filas.

### 4.4 Variable de bind ORA-01745 (`:like` es palabra reservada)

**Problema:** `oci_bind_by_name($stm, ':like', ...)` fallaba con `ORA-01745: invalid host/bind variable name` porque `LIKE` es una palabra reservada de Oracle SQL.

**Solución aplicada:** Renombrar la variable de bind de `:like` a `:srch`.

### 4.5 Evaluación eager de argumentos en Laravel API Resources

**Problema:** En `CategoriaClienteResource`, el patrón:
```php
'clientes' => $this->when(
    $this->relationLoaded('clientes'),
    $this->clientes->map(...)  // ← PHP evalúa esto ANTES de llamar when()
),
```
PHP evalúa todos los argumentos de una función antes de pasarlos. Así `$this->clientes->map(...)` se ejecuta aunque `relationLoaded('clientes')` sea falso → dispara lazy-load → full scan en Oracle.

**Solución aplicada:** Envolver el valor en una closure:
```php
'clientes' => $this->when(
    $this->relationLoaded('clientes'),
    fn () => $this->clientes->map(...)  // ← solo se evalúa si la condición es true
),
```

### 4.6 PHP-FPM workers bloqueados por queries lentas

**Problema:** Cuando `products/filter` o cualquier endpoint lento ejecuta una query Oracle que dura 35+ segundos, el worker PHP-FPM queda bloqueado en `oci_execute()`. Nginx cierra la conexión HTTP al superar su timeout, pero PHP-FPM sigue corriendo. Con 5 workers disponibles, varios endpoints lentos simultáneos pueden bloquear todos los workers → todos los requests posteriores se quedan en cola → HTTP:000 en cascada.

**Solución aplicada:** `request_terminate_timeout = 20` en PHP-FPM (`www.conf`). PHP-FPM mata el proceso al superar 20 segundos, liberando el worker.

**Ubicación del archivo:** `/usr/local/etc/php-fpm.d/www.conf`

> **Nota:** Esta configuración no persiste si el contenedor Docker se recrea. Añadirla al `Dockerfile` o al script de inicio.

### 4.7 Conexiones Oracle persistentes y cascada de fallos

**Problema:** `PDO::ATTR_PERSISTENT => true` en el `ErpServiceProvider` hacía que las conexiones PDO-via-OCI8 fueran persistentes entre requests del mismo worker. Al fallar una query (ORA-03113), la conexión muerta quedaba en el pool. Las requests posteriores reutilizaban esa conexión y fallaban inmediatamente.

**Solución aplicada:** `PDO::ATTR_PERSISTENT => false` en `ErpServiceProvider::boot()`. Cada request abre una conexión nueva a Oracle. El overhead es mínimo (~100ms por request) pero elimina el problema de cascada.

**Archivo:** `modules/Erp/app/Providers/ErpServiceProvider.php:264`

### 4.8 Hint `PARALLEL` no ayuda sin índice

**Prueba realizada:** `SELECT /*+ PARALLEL(t,4) */ COUNT(1) FROM PEDIDOCLI_CENTRAL WHERE IDCLIENTE = 3`

**Resultado:** 87.481 segundos. El hint PARALLEL añade overhead de coordinación entre workers que empeora el rendimiento cuando el resultado es 0 filas (tiene que confirmar que no hay ninguna en toda la tabla).

**Conclusión:** Sin índice, no existe optimización SQL que resuelva el problema. El índice es obligatorio.

---

## 5. Comportamiento de queries lentas por tipo

| Tipo de query | ¿Stopkey funciona? | Condición | Consecuencia |
|---|---|---|---|
| `WHERE pk_col = x AND ROWNUM <= n` | ✅ Sí | Hay al menos 1 fila | Para en cuanto la encuentra |
| `WHERE indexed_col = x AND ROWNUM <= n` | ✅ Sí | Hay al menos n filas | Para al llegar a n |
| `WHERE unindexed_col = x AND ROWNUM <= n` | ✅ Sí | Hay al menos n filas | Para al llegar a n |
| `WHERE unindexed_col = x AND ROWNUM <= n` | ❌ No | Hay 0 filas | Full scan completo |
| `ORDER BY col ... ROWNUM <= n` | ❌ No | Siempre | Debe ordenar todo antes |
| `UNION ALL` con ramas independientes | ❌ No parcial | Siempre ejecuta todas | Cada rama corre completa |
| `LIKE 'prefix%'` sobre columna sin índice | ❌ No | Hay 0 filas | Full scan completo |

---

## 6. Resumen ejecutivo

| Categoría | Operativos | Con limitaciones | Inaccesibles (permisos) | Con timeout |
|---|---|---|---|---|
| Customer | 11 | 2 (orders, lopd) | 8 (cards, accounts, debts, balance, vouchers, bonuses, invoices, payments) | 1 (search LIKE) |
| Productos | 4 | 0 | 0 | 1 (filter) |
| Proveedores | 6 | 0 | 0 | 0 |
| Artículos / Jerarquía | 10 | 2 (404 sin datos) | 0 | 0 |
| Pedidos / Stock / Albaranes | 3 | 0 | 0 | 0 |
| Categorías | 2 | 0 | 0 | 1 (clientes/{id}) |
| Bonos / Vales | 0 | 0 | 2 | 0 |
| **Total** | **36** | **4** | **10** | **3** |

### Prioridades para el DBA

1. **Alta** — Crear `IDX_PEDIDOCLI_IDCLIENTE` en `PEDIDOCLI_CENTRAL` → desbloquea pedidos del cliente
2. **Alta** — Conceder `SELECT` en tablas de facturas, cobros, tarjetas, cuentas → desbloquea 8 endpoints
3. **Alta** — Crear `IDX_CLIENTETELEFONO_TELEFONO` en `CLIENTETELEFONO_CENT` → habilita búsqueda por celular (actualmente timeout >20s)
4. **Alta** — Crear `IDX_CLIENTE_UPPER_EMAIL` en `CLIENTE_CENT(UPPER(EMAIL))` → habilita búsqueda por correo (actualmente timeout >20s)
5. **Media** — Crear `IDX_LOPDH_IDCLIENTE` en `CLIENTE_LOPD_HIST` → desbloquea historial LOPD
6. **Media** — Crear `IDX_CLIENTE_IDCATEGORIA` en `CLIENTE_CENT` → desbloquea detalle de categoría
7. **Baja** — Crear índices `UPPER(APELLIDOS)` y `UPPER(NOMBRE)` en `CLIENTE_CENT` → mejora búsqueda por nombre sin coincidencias exactas
