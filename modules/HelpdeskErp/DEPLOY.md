# Despliegue de Gestión (ERP) en el chat

Guía para subir a producción la ronda del 24 al 26 de septiembre de 2026. Cubre dos proyectos:

- `webadmin`: el módulo `HelpdeskErp`, que pinta Gestión dentro del chat.
- `manager`: el módulo `Erp`, la API que lee Oracle.

Oracle es la base de **producción** y se accede con el usuario **`LECTURA`**, que es de solo lectura. Nada de lo que se describe aquí escribe en Oracle.

Los permisos que faltan en Oracle se piden en [`DBA_SOLICITUD.md`](DBA_SOLICITUD.md).

---

## 1. Orden recomendado

1. Despliega el **manager**, porque webadmin necesita sus campos nuevos y la ruta `resolve`. Consulta la sección 7.
2. Despliega el código de **webadmin**.
3. Ejecuta las migraciones de webadmin (sección 4).
4. Ejecuta el seeder de permisos (sección 5).
5. Copia los assets (sección 6). Solo hace falta si el despliegue no incluye `public/modules/helpdeskerp`.
6. Ejecuta `optimize:clear`, reinicia Horizon y los workers, y comprueba que Reverb está en marcha (sección 6).
7. Opcional: lanza el backfill de vínculos (sección 5).

El orden inverso también funciona, aunque se pierde algo:

- Con webadmin nuevo y el manager viejo, las descripciones de los códigos salen de los mapas de respaldo de `config.php`.
- El albarán pedido por id corto da "no encontrado".

---

## 2. Variables de entorno

### webadmin (`.env`)

| Variable | Por defecto | Para qué |
|---|---|---|
| `ERP_MANAGER_URL` | vacío | URL base del manager, sin barra final. Si está vacía, Gestión aparece como no configurada. En Docker local: `http://host.docker.internal:8080` |
| `ERP_BRIDGE_TOKEN` | vacío | Token Sanctum del manager. Solo hace falta si se activa la autenticación en `/api/erp/*` (`ERP_API_AUTH_ENABLED` en el manager) |
| `ERP_WEBHOOK_SECRET` | vacío | Secreto HMAC del webhook `POST /api/helpdeskErp/webhooks/orders-ready`, que el manager envía al terminar el escaneo de pedidos. Tiene que coincidir con `SYSTEM_WEBHOOK_SECRET` del manager |
| `HELPDESK_ERP_HTTP_TIMEOUT` | 15 | Timeout de cada llamada al manager, en segundos |
| `HELPDESK_ERP_CHAT_TTL_OK` | 300 | Caché de las secciones que responden bien, en segundos |
| `HELPDESK_ERP_CHAT_TTL_DETAIL` | 1800 | Caché del detalle de pedido, albarán y factura |
| `HELPDESK_ERP_CHAT_TTL_BLOCKED` | 600 | Caché de una sección sin GRANT en Oracle |
| `HELPDESK_ERP_CHAT_TTL_UNAVAILABLE` | 300 | Caché de un endpoint que no existe (404) |
| `HELPDESK_ERP_CHAT_TTL_DOWN` | 30 | Caché cuando el manager está caído o da timeout |
| `HELPDESK_ERP_CHAT_OVERVIEW_ORDERS` | 10 | Número de pedidos que trae el resumen del panel |
| `HELPDESK_ERP_CHAT_EXPIRY_DAYS` | 7 | Días de antelación con los que se avisa de que un vale o bono caduca |
| `HELPDESK_ERP_CIRCUIT_FAILURE_THRESHOLD` / `HELPDESK_ERP_CIRCUIT_OPEN_SECONDS` | 5 / 120 | Cortacircuitos del manager |
| `HELPDESK_ERP_CROSS_GESTION_URL` | vacío | Extensión "cross": enlace a la ficha en Gestión (`config/ext/cross.php`) |

Las demás variables (`HELPDESK_ERP_CACHE_TTL`, `HELPDESK_ERP_MISS_TTL`, `HELPDESK_ERP_STALE_GRACE`, `HELPDESK_ERP_SEARCH_TIMEOUT`, `HELPDESK_ERP_HEALTH_TIMEOUT`, `HELPDESK_ERP_LOOKUP_COOLDOWN`…) no cambian en esta ronda. Consulta `config/config.php`.

En Docker, `docker restart` **no** vuelve a leer el `env_file`. Tras cambiar el `.env`, recrea el contenedor:

```bash
docker compose up -d --force-recreate webadmin-app webadmin-horizon webadmin-scheduler
```

### Config sin variable (ronda "polish", `config/config.php`)

- `chat_codes`: descripciones de respaldo de almacén, origen, catálogo, estado de pedido y tipo de albarán.
  - Se usan solo si el manager no manda los campos `*_description`.
  - Son una copia de las tablas de Oracle a 24-sep-2026.
- `tracking.templates`, `tracking.carrier_ids` y `tracking.aliases`: plantillas de URL de seguimiento por transportista.
  - Transportistas incluidos: SEUR, MRW, Correos, Correos Express, GLS, CTT, DHL, UPS, Nacex, InPost y Schenker.
  - El mapa `carrier_ids` usa los códigos reales de la tabla `TRANSPORTISTA`.
  - Desde **Ajustes → Helpdesk · Gestión (ERP) → Seguimiento de envíos** se pueden añadir o sustituir plantillas. Lo guarda la extensión "admin" en `helpdesk_erp_settings`.
  - La URL de Nacex no se ha comprobado con un envío real.

### manager (`.env`)

| Variable | Para qué |
|---|---|
| `SYSTEM_WEBHOOK_URL` | URL del webhook de webadmin: `https://<webadmin>/api/helpdeskErp/webhooks/orders-ready` |
| `SYSTEM_WEBHOOK_SECRET` | Mismo valor que `ERP_WEBHOOK_SECRET` de webadmin |
| `SYSTEM_WEBHOOK_SKIP_TLS` | Solo en entornos con certificado propio |
| `ERP_API_AUTH_ENABLED` / `ERP_API_AUTH_GUARD` | Autenticación de `/api/erp/*`. Hoy está desactivada: las rutas quedan abiertas detrás del cortafuegos |

---

## 3. Rutas nuevas (webadmin)

Todas son `GET` y usan el middleware `web` + `auth`. `{customer}` es siempre el id del **cliente del helpdesk**.

| Ruta | Nombre | Fichero |
|---|---|---|
| `panel/helpdesk/customers/{customer}/erp/overview` | `manager.helpdesk.erp.chat.overview` | `routes/managers.php` |
| `…/erp/sections/{section}` | `manager.helpdesk.erp.chat.section` | idem |
| `…/erp/orders/{orderId}` | `manager.helpdesk.erp.chat.order` | idem |
| `…/erp/delivery-notes/{id}` | `manager.helpdesk.erp.chat.delivery-note` | idem. **Ahora admite también el id corto** (ver sección 7) |
| `…/erp/invoices/{id}` | `manager.helpdesk.erp.chat.invoice` | idem |
| `…/erp/overview/orders` | `manager.helpdesk.erp.polish.overview-orders` | `routes/managers.d/polish.php`. **Nueva**, ver más abajo |
| Rutas de las extensiones | `manager.helpdesk.erp.{admin,cross,invoice,tickets}.*` | `routes/managers.d/*.php` |

Las rutas de `routes/managers.d/*.php` las carga `ErpChatExtServiceProvider`. Usa el mismo grupo que `routes/managers.php`: prefijo `panel/helpdesk`, middleware `web` y `auth`. Con `route:cache` quedan incluidas en la caché.

La ruta `…/erp/overview/orders` es la recarga ligera que se usa cuando terminan de llegar los pedidos:

- Solo vuelve a pedir al manager la sección de pedidos.
- Las otras 7 secciones salen de la caché del servidor.
- Responde con el mismo formato que `overview`, así las alertas y estadísticas de pedidos salen recalculadas.
- Si la ruta no existe, `erp-chat.js` usa en su lugar un `overview` normal.

Canales de Reverb:

- `private-helpdesk.erp.customer.{id}`: canal nuevo por contacto del helpdesk. Lo autoriza `LinkErpCustomerChannel`, registrado en `HelpdeskErpServiceProvider`.
- `private-erp-orders-ready.{md5 email}`: canal histórico. Se sigue usando.

El evento es `.erp.orders.ready` (`ErpOrdersReady`). `erp-chat.js` escucha en los dos canales y mantiene el sondeo de respaldo: hasta 3 intentos, cada `retry_after` segundos.

---

## 4. Migraciones (ronda del 26-sep)

Las dos van en la conexión `helpdesk` y son de la extensión "admin":

- `2026_09_26_000001_admin_create_helpdesk_erp_settings_table.php` crea `helpdesk_erp_settings`, con los ajustes editables desde el panel (cachés, avisos, vinculación y seguimiento).
- `2026_09_26_000002_admin_create_helpdesk_erp_metrics_events_table.php` crea `helpdesk_erp_metrics_events`, con las métricas de uso del panel de Gestión. Se purga cada día a las 03:40 con `helpdeskerp:purge-metrics`.

```bash
php artisan migrate --path=modules/HelpdeskErp/database/migrations --force
```

Las dos migraciones comprueban `hasTable` antes de crear. **Nunca** uses `migrate:fresh`, `rollback` ni `db:wipe` en este proyecto.

La ronda "polish" **no añade migraciones**.

---

## 5. Permisos, seeder y backfill

### Permisos de Gestión en el chat

Los crea `HelpdeskErpPermissionsSeeder`:

| Permiso | Roles |
|---|---|
| `helpdeskerp.view` | todos los del helpdesk |
| `helpdeskerp.orders.view` | agentes y superiores |
| `helpdeskerp.addresses.view` | agentes y superiores |
| `helpdeskerp.finance.view` | agentes y superiores |
| `helpdeskerp.loyalty.view` | agentes y superiores |
| `helpdeskerp.sensitive.view` (tarjetas y cuentas) | solo helpdesk-manager, helpdesk-admin y administración |
| `helpdeskerp.settings.manage` (extensión admin) | helpdesk-manager, helpdesk-admin |
| `helpdeskerp.metrics.view` (extensión admin) | helpdesk-supervisor, helpdesk-manager, helpdesk-admin |

El seeder recoge además los permisos de `config/ext/*.php`. La ronda "polish" **no añade permisos**.

```bash
# Usa db:seed con la clase completa: `module:seed --class=FQCN` no hace nada y no avisa.
php artisan db:seed --class="Modules\\HelpdeskErp\\Database\\Seeders\\HelpdeskErpPermissionsSeeder" --force
php artisan permission:cache-reset
```

### Backfill de vínculos helpdesk ↔ Gestión (opcional)

```bash
php artisan helpdeskerp:backfill-links --limit=0 --chunk=100
# solo algunos:  --id=19457 --id=20001   ·   solo los vinculados a PrestaShop: --prestashop
# en línea en vez de encolar: --sync
```

Sin `--sync`, los trabajos van a la cola del linker. Comprueba que Horizon tiene un worker para esa cola.

Después, para vincular con la tienda a los contactos que ya están en Gestión (usa el `CODIGO_INTERNET` de la ficha del ERP; nunca pisa un vínculo existente):

```bash
php artisan helpdeskerp:backfill-links --store-from-erp
```

Desde ahora esto también ocurre solo al vincular un contacto con Gestión. Se desactiva con `helpdeskErp.link.store_from_erp = false`.

---

## 6. Assets, cachés y procesos

La fuente de los assets está en `modules/HelpdeskErp/public/{js,css}/`. La copia servida está en `public/modules/helpdeskerp/{js,css}/`, que también está en git. Si el despliegue no trae esa copia:

```bash
mkdir -p public/modules/helpdeskerp/js public/modules/helpdeskerp/css
cp modules/HelpdeskErp/public/js/*.js   public/modules/helpdeskerp/js/
cp modules/HelpdeskErp/public/css/*.css public/modules/helpdeskerp/css/
```

Los blades cargan cada fichero con `?v=filemtime`, así que no hace falta invalidar a mano la caché del navegador.

```bash
php artisan optimize:clear          # config, rutas, vistas y eventos
php artisan config:cache && php artisan route:cache   # si producción usa cachés
php artisan horizon:terminate       # Horizon se relanza solo (supervisor/Docker)
```

En Docker local:

```bash
docker exec webadmin-app php artisan optimize:clear
docker restart webadmin-horizon webadmin-scheduler
```

- `webadmin-reverb` tiene que estar en marcha para recibir los eventos en vivo. Sin Reverb, el sondeo de respaldo cubre el caso: tarda más, pero funciona.
- Tras cambiar código de módulos, reinicia también los workers de colas. Un worker viejo mantiene el autoload anterior.

---

## 7. Manager (proyecto `manager`, módulo `Erp`)

### Ya en git: commit `a35d46aa`

- Reconexión a Oracle cuando se pierde la sesión (ORA-03113/03114/03135/12537…): reintenta una vez con una sesión no persistente.
- `GET customer/{id}/orders/{orderId}/history` responde `not_available`, porque el usuario de lectura no ve ningún histórico de estados.
- `GET customer/{id}/orders/{orderId}/shipping` devuelve los albaranes del pedido. Transportista y seguimiento salen `null` porque la tabla de envíos no tiene GRANT.
- `GET customer/{id}/returns` devuelve devoluciones (TIPO 3) y abonos (TIPO 4).
- El detalle de pedido incluye `status_code` y `status_code_description`.

### Esta ronda: sin commitear, solo añade campos y una ruta

Ficheros tocados:

- `modules/Erp/app/Http/Controllers/Api/CustomerController.php`
- `modules/Erp/routes/api.php`

**Descripciones de códigos.** Los endpoints devuelven campos `*_description` al lado de cada código, resueltos contra las tablas de códigos:

- Tablas usadas: `ALMACEN`, `ORIGENPEDIDOCLI`, `CATALOGO`, `PEDIDOCLIESTADO`, `TIPOALBARANCLI`, `IDIOMA`, `PAIS`, `REGFISCAL`, `REGPAIS`, `CATEGORIA_CLIENTE` y `TIPOCLIENTE`. Todas son legibles para `LECTURA` (comprobado el 24-sep).
- Cada tabla se lee con un `SELECT código, DESCRIPCION` acotado y se cachea 12 h en Redis, con independencia de `oracle_enable_cache`. Si una tabla no se puede leer, se cachea vacía 10 min y la descripción sale `null`.
- Campos nuevos por endpoint:

| Endpoint | Campos nuevos |
|---|---|
| `/orders` | `status_description`, `warehouse_description`, `origin_description`, `catalog`, `catalog_description` (se añade `IDCATALOGO` al SELECT) |
| `/personal` | `language_description`, `category_description`, `customer_type_description`, `fiscal_regime_description`, `country_regime_description`, `nationality_description` |
| `/{id}` (resumen) | `language_description`, `category_description` |
| `/catalogs` | `catalogs[].catalog_description` |
| `/delivery-notes` (lista y detalle) | `warehouse_description`, `catalog_description`, `type_description` |

- La lista de pedidos cacheada antes del despliegue no trae `catalog`. Sale `null` durante como mucho 1 hora.
- `TIPOALBARANCLI` no contiene los tipos 1, 3 y 4 (venta, devolución y abono), así que `type_description` sale `null` para ellos. webadmin los completa con `chat_codes.delivery_type`.

**Ruta nueva `GET customer/{id}/delivery-notes/resolve/{ref}`.** Convierte el id corto de un albarán en su id central.

- Los movimientos de puntos (`PUNTOFIDELIZACION.IDALBARANCLI`) y `DEUDACLI_CENTRAL.IDALBARANCLI` guardan el id corto del albarán, por ejemplo `101961890`. El detalle se pide por el id central, por ejemplo `10101961890`.
- Hoy `/debts` ya devuelve el id central en `delivery_id`. La ruta acepta los dos ids, así que no importa cuál llegue.
- La ruta busca por `IDALBARANCLI_CENTRAL` y por `IDALBARANCLI`, acotando por `IDCLIENTE`, que tiene índice: unos 100 ms.
- Devuelve todas las coincidencias. Si hay más de una, webadmin la trata como ambigua y no la usa.
- webadmin llama a esta ruta cuando `delivery-notes/{id}` da "no encontrado". Ya no se adivina el prefijo `10`.

Comprobado con `curl` contra Oracle real, el 24-sep, con el cliente 101544116:

- `/orders`: `"origin_description":"INTERNET","warehouse_description":"POCOMACO","catalog_description":"Caza","status_description":"Servido"`.
- `/personal`: `Castellano`, `ESPAÑA`, `NORMAL` y `Basica`.
- `/catalogs`: `Pesca` y `Caza`.
- `/delivery-notes/resolve/101961890` y `/delivery-notes/resolve/10101961890`: los dos devuelven `10101961890`.

Despliegue del manager:

```bash
php artisan optimize:clear     # en el contenedor del manager
# PHP-FPM relee el código sin reiniciar (sin opcache.validate_timestamps=0); si lo hay:
docker restart app worker
```

### Caché de Oracle del manager (`oracle_enable_cache`): **se deja desactivada**

El ajuste vive en la tabla `settings` de la base del manager (`managerchat`). Hoy vale `"0"`.

**Por qué no se activa:**

1. **TTL de 1 hora en todos los endpoints de cliente.** `ApiController::cachedResult()` usa 3600 s por defecto y ningún endpoint de cliente pasa otro valor. Afecta al resumen, las direcciones, el contacto, la ficha personal, la LOPD, las tarjetas, las cuentas, los catálogos, las cuotas, las deudas, el saldo, los vales, los bonos, los puntos y los detalles de pedido, albarán y factura.
   - Una hora es demasiado para las deudas y el riesgo: un cliente que paga seguiría viéndose "con deuda" hasta una hora.
   - También es demasiado para el saldo, los vales y bonos (uno canjeado aparece como vigente), el estado de un pedido o un cambio de LOPD (volver a ofrecer promociones a quien acaba de darse de baja).
2. **"Actualizar" dejaría de servir.** `force=1` salta la caché de webadmin (5 min), pero no la del manager. Además, `DELETE /customer/{id}/cache` (`forgetCache`) no borra las claves de detalle (`customer:order:*`, `customer:delivery:*`, `customer:invoice:*`, `customer:order-shipping:*`), que solo caducan por tiempo.
3. **El ajuste es global.** También activa la caché de productos, proveedores y `fastPaginate` (`UsesOCI8Performance`), que quedan fuera de esta tarea.
4. webadmin ya cachea cada sección de 30 s a 30 min según su estado (`chat_ttl`). La caché del manager solo sumaría retraso.

**Qué haría falta para activarla sin riesgo:**

- Pasar un TTL por endpoint en `cachedResult()`: unos 300 s para el resumen, las deudas, el saldo, los vales, los bonos y los puntos, y unos 1800 s para los detalles.
- Que `forgetCache()` borre también las claves de detalle, o versionarlas por cliente.
- Un `?fresh=1` que salte la caché del manager, para que el "Actualizar" de webadmin llegue hasta Oracle.

**Aparte:** la lista de pedidos (`/orders`) se cachea **siempre** 1 hora, con independencia de `oracle_enable_cache`. Es el apaño para el escaneo completo de `PEDIDOCLI_CENTRAL` (unos 35 s) que se hace mientras falta el índice `IDX_PEDIDOCLI_IDCLIENTE`. Hasta que el DBA cree el índice, un pedido nuevo puede tardar hasta una hora en aparecer en el chat. Con el índice creado, esa caché puede bajar a unos minutos.

---

## 8. Comprobación rápida tras desplegar

```bash
# manager
curl -s "$MANAGER/api/erp/customer/101544116/personal" | grep -o '"language_description":"[^"]*"'
curl -s "$MANAGER/api/erp/customer/101544116/delivery-notes/resolve/101961890"

# webadmin
php artisan route:list --name=manager.helpdesk.erp.polish
```

En el navegador, abre una conversación de un cliente vinculado. En la pestaña Gestión, los pedidos tienen que mostrar "Internet · Caza" y el estado real ("Servido").

- En el primer escaneo, la lista tiene que aparecer sola al terminar.
- En la pestaña Red, al terminar el escaneo solo tiene que verse una llamada a `…/erp/overview/orders`, no un `overview?force=1`.

Clientes de prueba:

- ERP 101544116, que en PrestaShop es 911230. Pedidos 10102138690 y 10102142050; albarán 10101961890, id corto 101961890.
- Cliente helpdesk 19457, conversación 11693. Está vinculado al ERP 100724261, que no tiene pedidos.

Lo que sigue bloqueado por permisos de Oracle está en [`DBA_SOLICITUD.md`](DBA_SOLICITUD.md).
