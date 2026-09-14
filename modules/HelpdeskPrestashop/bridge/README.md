# Bridge de PrestaShop — copia versionada

Los ficheros del módulo `alsernetbridge` que webadmin necesita, guardados aquí
porque **el proyecto de PrestaShop (`Herd/alvarez`) no está bajo control de
versiones**: no es un repositorio git, así que lo que se toca allí vive sólo en
el disco de quien lo tocó, sin historial y sin forma de revertir.

Esta carpeta no se ejecuta: es la fuente de la que copiar al desplegar, y el
único sitio donde queda constancia de qué se cambió y por qué.

## v1.2.4 — acciones de bono

Añade dos lecturas para que el panel de cumpleaños (`HelpdeskBirthday`) pueda
medir el canje de los bonos sin conectarse a la base de datos de la tienda.

| Acción | Qué devuelve |
|---|---|
| `voucher.redemptions` | Canjes de un rango de fechas: pedido, importe, descuento, cliente y qué contestó gestión. Filtra por `name_like` (el nombre del cupón) y opcionalmente por `codes`. Pagina con `limit`/`offset`, tope 500. |
| `voucher.status` | Estado en la tienda de unos códigos concretos: si la regla existe, su validez y si le quedan usos. |

Las dos son de **lectura**: no entran en `$writeActions` ni piden clave de
idempotencia.

### Por qué no valía `customer.vouchers`

Filtra por `cart_rule.id_customer`, y los bonos de gestión se crean con
`id_customer = 0`: devolvía una lista vacía siempre, para todos los clientes.

### Lo que condiciona la consulta

**PrestaShop borra la `cart_rule` al consumirla.** Medido sobre la tienda real:
de los 1.116 cheques de cumpleaños canjeados, sólo **4** conservan su fila. Por
eso el ancla es `order_cart_rule` (que no se borra), `cart_rule` y `marcarbono`
van con LEFT JOIN, y el código se reconstruye con
`COALESCE(cr.code, CONCAT(mb.bono, '-', mb.codigo_verificacion))`.

Dos detalles que costaron encontrar:

- `marcarbono` se une por **`id_cart_rule`**, nunca por `id_order`: por pedido,
  cada línea de descuento se cruza con cada registro de gestión e infla 1.116
  canjes a 1.132.
- La identidad de un canje es **`ocr.id_order_cart_rule`** (la línea), no el
  pedido: un pedido puede llevar dos bonos.

## Ficheros

| Fichero | Estado |
|---|---|
| `v1.2.4/helpers/voucher.php` | **Nuevo.** Las dos acciones. |
| `v1.2.4/upgrade/upgrade-1.2.4.php` | **Nuevo.** Sube la versión de caché. |
| `v1.2.4/api.php` | Modificado: `require_once` del helper, dos `case`, y la condición de caché (ver abajo). |
| `v1.2.4/helpers/log.php` | Modificado: TTL de las dos acciones (60 s y 30 s). |

`alsernetbridge.php` sube su `$this->version` a `'1.2.4'`; es el único cambio en
ese fichero y por eso no se copia entero aquí.

### El cambio no evidente de `api.php`

La caché sólo se aplicaba `if ($cacheTtl > 0 && $idCustomer)`, y estas acciones
no van sobre un cliente: no se habrían cacheado nunca. Guardarlas bajo el
prefijo `cust:0` habría sido peor —ningún hook invalida ese prefijo y la entrada
se quedaría colgada—, así que usan un prefijo propio `voucher:`.

Y un aviso: si el helper devolviera `null`, `api.php` responde **404 “unknown
action”**. Cuando no hay canjes hay que devolver `['redemptions' => []]`.

## Desplegar

1. Copiar `v1.2.4/helpers/voucher.php` y `v1.2.4/upgrade/upgrade-1.2.4.php` al
   módulo, respetando las rutas.
2. Aplicar `api.php` y `helpers/log.php` (o copiarlos, si no se han tocado por
   otro motivo desde la 1.2.3).
3. Subir `$this->version` a `'1.2.4'` en `alsernetbridge.php`.
4. Pasar el upgrade desde el back-office, o a mano:
   `upgrade_module_1_2_4($module)` — sube `ALSERNETBRIDGE_CACHE_VERSION`, que es
   lo que invalida las entradas con el esquema de clave anterior.
5. Comprobar: `voucher.redemptions` con `{"name_like":"cumplea","limit":1}` debe
   responder `ok: true` y un `total` distinto de cero.

Mientras no esté desplegado, webadmin cae solo a la lectura SQL directa
(`helpdeskbirthday.redemption_source = auto`), que funciona si comparte MariaDB
con la tienda.
