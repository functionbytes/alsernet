# E2E staging — tienda PrestaShop (bridge) ⇄ panel helpdesk

Entorno `docker compose` mínimo para ejecutar el **contrato E2E** entre el
módulo `alsernetbridge` de la tienda PrestaShop (repo `alvarez`) y el módulo
`HelpdeskPrestashop` del panel Laravel (este repo), en ambas direcciones:

- **panel → tienda (pull):** peticiones firmadas HMAC contra
  `modules/alsernetbridge/api.php` (+ `health.php`, `metrics.php`).
- **tienda → panel (push):** webhooks firmados contra
  `POST /api/helpdeskprestashop/webhooks/event` (middleware `VerifyAlsernetHmac`).

## Decisión de alcance: stub de PrestaShop, no PS completo

Levantar la tienda alvarez real (o un PrestaShop limpio + instalación del
módulo) en CI es caro y frágil: BD enorme, instalador interactivo, overrides
propios. En su lugar, el servicio `bridge` es **PHP 7.4 + Apache sirviendo el
módulo real (montado de solo lectura)** sobre un **stub de PrestaShop**
(`bridge/ps-stub/config/config.inc.php`) que implementa únicamente la
superficie PS que el módulo usa de verdad (auditada):

- `Db` (PDO → MariaDB con las tablas mínimas + tablas del módulo), `pSQL()`
- `Configuration` (tabla `aalv_configuration`), `Context` (language/shop/
  currency/link), `Tools`, `Validate`, `PrestaShopLogger`
- `Customer` (carga + `getStats()`), `Cart` (ruta *fallback*: el
  `CartPresenter` no existe a propósito y el módulo cae en su rama
  `Cart::getProducts()`), `Currency`, `Order`/`OrderState`/`OrderHistory`/
  `OrderCarrier`/`Address` mínimos y `Mail` no-op.

Con esto la suite oficial del módulo (`tests/run-e2e.sh`, 21 asserts: HMAC,
anti-replay, idempotencia, cache, health público/privado, metrics) pasa al
100 % contra el stub. **No cubierto por el stub:** `cron.php` (sync de
catálogo — necesita la clase `Alsernetbridge`/`Module` completa) y la salida
"rica" de `CartPresenter`. Si algún día el contrato necesita eso, la
alternativa es la imagen oficial `prestashop/prestashop` con instalación
limpia y el módulo montado como volumen (mucho más lenta: ~5-10 min de boot).

## Estructura

```
deployment/e2e/
├── docker-compose.yml        # db (MariaDB 11) + bridge (stub) + panel + panel-nginx
├── run-contract-e2e.sh       # orquestador: levanta, espera health, ejecuta el contrato
├── panel.env                 # env autocontenido del panel (BD propia, cache file, queue sync)
├── panel-nginx.conf          # nginx mínimo para el panel (sin Reverb)
├── bridge/
│   ├── Dockerfile            # php:7.4-apache + pdo_mysql
│   └── ps-stub/config/config.inc.php   # stub de PrestaShop
├── fixtures/                 # init de MariaDB (se ejecutan en orden)
│   ├── 00-databases.sql      # crea el esquema `panel`
│   ├── 10-bridge-schema.sql  # tablas PS mínimas + tablas del módulo (prefijo aalv_)
│   ├── 20-bridge-data.sql    # cliente test@test.com, pedido, carrito abandonado, config
│   └── 30-panel-bootstrap.sql# tablas que el panel necesita ANTES de migrar (ver Hallazgos)
└── scripts/
    └── send-webhook.php      # dispara un webhook usando AlsernetWebhookSender (código real)
```

Puertos host: **8093** (bridge stub) y **8094** (panel). No chocan con los
entornos de desarrollo existentes (8090 system-nginx, 8091 alvarez_app).

## Ejecución local

Requisitos: Docker, y el repo de la tienda en `../../../alvarez/src`
(configurable con `ALVAREZ_SRC`). La imagen `system` se reutiliza si ya está
construida (la misma de `docker/docker-compose.yml`); si no, se construye.

```bash
cd deployment/e2e
./run-contract-e2e.sh          # levanta todo, ejecuta el contrato, deja el entorno arriba
./run-contract-e2e.sh --down   # igual + docker compose down -v al terminar
```

Fases (exit code agregado: 0 = todo OK):

1. `db` + `bridge` + `panel` arriba, espera a `health.php == 200`.
2. `php artisan migrate --force` sobre el esquema `panel` limpio.
3. Suite del módulo: `modules/alsernetbridge/tests/run-e2e.sh` contra el stub.
4. Webhook tienda→panel con el código real (`AlsernetWebhookSender`),
   más entrega firmada manualmente (200 `{"ok":true}`) y **replay de la misma
   firma → 401** (verifica el anti-replay de `VerifyAlsernetHmac` y su efecto
   en cache).
5. Pull panel→tienda: `php artisan helpdeskprestashop:test-connection`.

Los secretos E2E viven por triplicado y deben rotarse juntos:
`fixtures/20-bridge-data.sql` (aalv_configuration), `panel.env`
(`ALSERNETBRIDGE_WEBHOOK_SECRET`) y los defaults de `run-contract-e2e.sh`.
Son valores exclusivos de este entorno, no los de desarrollo/producción.

## Hallazgos sobre el repo (documentados, no corregidos aquí)

- `chat_accounts` y `chat_accounts_user` **no las crea ninguna migración** del
  repo, pero varias migraciones les añaden FKs o insertan en ellas
  (`2026_04_30_010510_add_account_id_to_users_for_chat`, Social/Chat). En una
  BD limpia `php artisan migrate` falla; `fixtures/30-panel-bootstrap.sql` las
  pre-crea como workaround.
- Varios service providers (p. ej. `HelpdeskAnalyticsServiceProvider`)
  consultan `helpdesk_settings` **durante el boot**, así que artisan ni
  siquiera arranca contra una BD vacía; el mismo fixture pre-crea esa tabla y
  marca su migración como aplicada.

## Encaje como job de CI (propuesta — NO añadido a .github/workflows/ci.yml)

El workflow actual lo usa otro flujo; este sería un job nuevo. Necesita ambos
repos en el runner. La imagen `system` es pesada de construir (Oracle client);
para CI conviene publicarla en un registry y hacer `pull` en lugar de `build`.

```yaml
  e2e-contract:
    runs-on: ubuntu-latest
    timeout-minutes: 30
    steps:
      - uses: actions/checkout@v4            # repo del panel (system)
      - uses: actions/checkout@v4
        with:
          repository: <org>/alvarez          # repo de la tienda
          path: alvarez-src                  # solo se usa modules/alsernetbridge
          sparse-checkout: src/modules/alsernetbridge
      - name: Pull imagen del panel
        run: docker pull <registry>/system:latest && docker tag <registry>/system:latest system
      - name: Contrato E2E
        env:
          ALVAREZ_SRC: ${{ github.workspace }}/alvarez-src/src
        run: |
          cd deployment/e2e
          ./run-contract-e2e.sh --down
      - name: Logs si falla
        if: failure()
        run: cd deployment/e2e && docker compose logs --tail 100
```

Notas para CI:

- El paso más lento es `migrate --force` (~900 migraciones, 1-2 min) y el
  primer `up` de MariaDB con fixtures (~20 s). El stub del bridge se
  construye en segundos.
- `run-contract-e2e.sh` ya devuelve exit code agregado, por lo que el job no
  necesita parsear salida.
- El compose no publica la BD al host y usa el proyecto `alsernet-e2e`, así
  que puede convivir con otros jobs docker del mismo runner.

## Limitaciones conocidas

- El panel monta `../../src` (igual que el compose de dev): el cache de
  Laravel es `file` y escribe en `storage/framework/cache` compartido con el
  entorno local. En CI no afecta (workspace limpio); en local, los nonces
  anti-replay conviven con el cache de dev sin conflicto de claves.
- `QUEUE_CONNECTION=sync`: los eventos `Ps*` se despachan inline. Hoy solo
  `PsPriceDropped`/`PsBackInStock` tienen listener (`InvalidateCatalogCache`),
  el resto se valida por respuesta HTTP + anti-replay.
- El stub asume IVA 21 % en la rama fallback del carrito (solo afecta a los
  importes del carrito abandonado de fixtures, no al contrato).
