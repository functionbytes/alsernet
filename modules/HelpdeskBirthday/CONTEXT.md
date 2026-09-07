# HelpdeskBirthday — campaña diaria de cumpleaños

Cada día localiza en el ERP a los clientes que cumplen años, le pide a Gestión
**un bono para cada uno** y les envía su felicitación, repartida dentro de una
ventana horaria para no saturar el servidor de correo saliente.

---

## Arquitectura

```
scheduler ──06:00──> helpdeskbirthday:prepare
                         │
                         ├── BirthdayAudienceService ─HTTP──> API de clientes (/api/erp/customer)
                         │                                    quién cumple años hoy
                         ├── BirthdayScheduleCalculator
                         │        └── escribe scheduled_at por destinatario
                         └── BirthdayBonoGenerator ──HTTP──> gestión (/generacion-bono/)
                                  └── el bono de cada cliente, en su fila

scheduler ──cada min──> helpdeskbirthday:dispatch-due
                         └── reserva los vencidos ('sending') y encola
                                  └── SendBirthdayEmailJob (cola 'birthdays', throttle)
                                           └── Mail::send(BirthdayCouponMailable)
                                                    └── email_logs (estado, rebote, apertura)

scheduler ──cada hora──> helpdeskbirthday:finalize
                         └── cierra campañas terminadas, rescata encallados, purga antiguas
```

**Por qué un planificador por reloj y no `dispatch()->delay()`:** despachar miles de
jobs con retardo llenaría Redis, haría imposible pausar de verdad (habría que
rescatarlos de la cola) y no sobreviviría bien a un reinicio. Con el reparto en
`scheduled_at`, pausar la campaña es un `UPDATE` de una fila y surte efecto en la
siguiente pasada del comando, menos de un minuto después.

---

## Contratos externos

### 1. Cumpleañeros del día — manager ERP

El dato vive solo en Oracle (`DEVELOPER.CLIENTE_CENT.FNACIMIENTO`) y este
proyecto no llega a Oracle (ver `docker/laravel.env`, bloque Erp/Oracle: el
listener TNS da ORA-12170). Se consulta al **manager** vía HTTP, igual que hace
`Modules\HelpdeskErp\Services\ErpContextService`.

```
GET {ERP_MANAGER_URL}/api/erp/customer
    ?birthday=MM-DD[,MM-DD]   día y mes, sin importar el año (varios separados por coma)
    &commercial_optin=1       excluye NO_INFORMACION_COMERCIAL_LOPD
    &lopd_accepted=1          solo con FACEPTACION_LOPD informada
    &has_email=1              solo con email no vacío
    &limit=100&offset=0       el manager topa limit en 100
```

```json
{
  "success": true,
  "data": [
    {
      "id": 12345,
      "label": "Ana",
      "surnames": "García",
      "email": "ana@ejemplo.com",
      "birth_date": "1990-09-02",
      "lopd": { "accepted": true, "no_commercial_info": false }
    }
  ],
  "pagination": { "limit": 100, "offset": 0, "count": 1, "hasMore": false }
}
```

Los dados de baja (`FBAJA`) quedan siempre fuera, no hace falta pedirlo.

Implementado en `Modules\Erp\Http\Controllers\Api\CustomerController::list()`.
**Ojo:** el manager es un despliegue aparte — el filtro no funciona allí hasta
que se despliegue ese cambio. Mientras tanto, un manager antiguo ignora
`birthday` en silencio y devuelve clientes cualesquiera; por eso hay dos guardas
(ver más abajo).

### 2. El bono de cada cliente — gestión

**No hay un cupón del día.** Gestión emite un bono POR CLIENTE y ese es el
camino normal; el código único de campaña solo existe para las promociones
antiguas que repartían el mismo a todo el mundo.

Son dos llamadas, porque la generación devuelve el id del LOTE y no el de cada
bono:

```
POST {erp_api_url}/api-gestion/generacion-bono/     → idgeneracion_bono_promo
GET  {erp_api_url}/api-gestion/lgeneracion-bono/{id}/ → qué bono le tocó a cada cliente
```

La segunda es imprescindible: el id del lote **cae en el mismo rango numérico
que los ids de bono**, así que consultarlo como bono responde 200 con los datos
de otra persona (comprobado el 4-sep-2026: dos lotes resolvieron a bonos de 2018
caducados). Por eso el id del lote nunca se guarda como `coupon_code`.

Lo hace `BirthdayBonoGenerator`, y lo llama `BirthdayCampaignService::prepare()`
justo después de insertar los destinatarios y **fuera de la transacción**: son
varias llamadas HTTP (una por cada 100 clientes) y tenerlas dentro bloquearía la
tabla durante minutos.

Quien se queda sin bono pasa a `skipped/no_coupon` con el motivo en
`coupon_error`, y se recupera con «Generar los N bonos que faltan» en el panel.
Dejarlo `pending` era un bucle: dispatch-due lo reservaba, el job lo devolvía a
pendiente, y vuelta a empezar cada minuto sin que la campaña cerrara jamás.

Sin `bono_type_id` configurado (Ajustes → El bono de cumpleaños) no se puede emitir
nada: la campaña se prepara igual —la audiencia del día no se puede
reconstruir mañana— pero queda **en pausa** y avisa.

**No hay cupón del día ni pantalla para configurarlo.** Ajustes solo pide el
tipo de bono; el importe, la validez y la compra mínima los decide Gestión al
emitirlo, y son los que se ven luego en la campaña y en cada destinatario. El
código único de campaña sobrevive únicamente en las columnas `coupon_*` de
`helpdesk_birthday_campaigns`, sin escritor: se dejaron para no perder el
histórico de las promociones antiguas.

### 2b. Consultar un bono ya emitido

Contrato que hasta hace poco solo existía en el PHP legacy de PrestaShop
(`AlvarezERP::consultabono()`) y en un método sin usar de `ErpService`. La
respuesta es **XML**, no JSON.

```
GET {erp_api_url}/api-gestion/bono/{idbono}/
    ?codigo_verificacion={cv}&importe_venta={importe}&origen=gestion
```

| Campo de la respuesta | Uso |
|---|---|
| `fvalidez_desde` | `coupon_valid_from` |
| `fvalidez_hasta` | `coupon_valid_to` |
| `importe` | `coupon_amount` |
| `importeminimoventa` | `coupon_min_purchase` |
| `descripcion_tipo` | `coupon_meta.type` |
| `estado_extendido` | `coupon_meta.state` |

El código que ve el cliente es `{idbono}-{codigo_verificacion}`, exactamente el
formato con el que PrestaShop crea el `cart_rule`
(`CartRule::createCartRuleAlvarez`). Si no coincide, el cliente no puede
canjearlo. Lo compone `BirthdayRecipient::publicCode()`, que es la única fuente
de ese formato.

Se usa al emitir el bono y en `BirthdayBonoGenerator::syncDetails()`, para
refrescar lo que Gestión dice de un bono ya entregado.

---

## Guardas de seguridad

Las dos abortan la campaña entera antes de enviar nada:

1. **Volumen** — si el ERP devuelve más de `max_recipients` (2.000 por defecto),
   se aborta. Cubre el caso de que el manager devuelva la base entera.
2. **Filtro no aplicado** — se comprueba que la `birth_date` de cada fila caiga
   en el día pedido. Si alguna no cuadra, el manager está ignorando `birthday` y
   se aborta con un mensaje explícito. Es la detección directa; la de volumen es
   la red por si el manager tampoco devuelve `birth_date`.

Además, `email_suppressions` se cruza antes de encolar (bajas, rebotes duros,
quejas). El listener `EnforceEmailSuppression` los bloquearía igualmente en el
envío, pero filtrarlos antes evita encolar jobs destinados a morir y deja el
recuento correcto en el panel.

---

## Zona horaria

**La app corre en UTC pero las horas de este módulo son horas de oficina.**
`helpdeskbirthday.timezone` (por defecto `Europe/Madrid`) es la zona en la que
se interpretan `window_start`, `window_end` y `prepare_at`.

Sin eso, «enviar de 9 a 2» significaba 09:00 UTC y los correos salían en España
a las 11:00 en verano, y el `prepare` de las 06:00 corría a las 08:00. El
scheduler declara la zona con `->timezone()` y `BirthdayScheduleCalculator`
convierte la ventana antes de repartir.

Qué se guarda dónde:

| Campo | Zona |
|---|---|
| `window_start` / `window_end` | hora **local** (es la que se enseña en el panel) |
| `scheduled_at`, `sent_at`, `started_at`… | UTC, como el resto de la app |

Las vistas convierten a la zona de negocio al mostrar. Cubierto por dos tests
que incluyen el cambio de hora (julio +2, enero +1).

---

## Reparto y ritmo

```
interval = max( ventana / N , 3600 / throttle_per_hour )
scheduled_at[i] = window_start + i * interval
```

Los dos términos cubren casos distintos: `ventana/N` estira los envíos para
ocupar la franja (20 correos en 5 horas no deben salir en el primer minuto), y
`3600/tope` es el suelo duro que protege al servidor de correo. **Cuando chocan
gana el tope** y la campaña termina más tarde que la ventana: se prefiere acabar
tarde a que nos bloqueen el envío. El panel lo avisa.

`SendBirthdayEmailJob` añade un segundo cinturón con
`Spatie\RateLimitedMiddleware\RateLimited` (vía `Modules\Queue\Jobs\BaseJob::throttle()`).
Se usa el de Spatie y **no** `Illuminate\Queue\Middleware\RateLimited` porque
este último necesita un limiter registrado con `RateLimiter::for()` y, si no
existe, deja pasar los jobs sin limitar nada — que es exactamente lo que le pasa
hoy al `helpdesk-meta-outbound` de Helpdesk.

`BirthdayCouponMailable` **no** es `ShouldQueue`, a diferencia de
`TicketMailable`: quien envía ya es un job encolado con throttle, y encolar
además el Mailable sacaría el envío real en un segundo job sin throttle,
perdiendo el ritmo.

---

## Estados

**Campaña:** `draft → scheduled → sending → completed`, con `paused` (ida y
vuelta desde `scheduled`/`sending`), `failed` (sin cupón o guarda disparada) y
`cancelled`.

**Destinatario:** `pending → sending → sent | failed`, o `skipped` (suprimido,
sin email válido, campaña cancelada, **sin bono emitido** o **día pasado**). El
paso por `sending` es una reserva atómica (`UPDATE … WHERE status = 'pending'`):
si dos pasadas se solapan o corren en dos nodos, solo una se lleva cada fila y
nadie recibe el correo dos veces.

Un destinatario que lleve más de una hora en `sending` (worker muerto a medio
job) lo rescata `finalize` devolviéndolo a `pending`.

**La campaña caduca.** Pasado el día del cumpleaños más
`expire_after_hours` (6 por defecto), lo que no haya salido ya no sale:
`finalize` y `dispatch-due` marcan esos destinatarios `skipped/expired` y
cierran la campaña. Una felicitación con dos días de retraso, y con un bono que
lleva ese tiempo corriendo, es peor que ninguna. Sin este corte los pendientes
de ayer salían hoy mezclados con los de hoy y ninguna campaña cerraba.

---

## Panel

`panel/helpdeskbirthday/campaigns` es a la vez listado y cuadro de mando:

- **Campaña de hoy** arriba del todo, con progreso y hora del siguiente envío.
- **KPIs** de 7/30/90 días: campañas, cumpleañeros, correos enviados, tasa de
  apertura, tasa de clic y rebotes. Las tres últimas NO se calculan aquí — se
  piden a `EmailDeliveryLookupService::statsForModule('HelpdeskBirthday')`, que
  es la fuente única para todos los módulos (ver
  `modules/HelpdeskEmailActivity/docs/DELIVERY_LOOKUP_API.md`).
- **Envíos por día** y **motivos de omisión**, que explican la diferencia entre
  cumpleañeros encontrados y correos realmente enviados.

En el detalle de una campaña, cada destinatario muestra su estado real de
entrega (**Entregado / Abierto / Clic**), resuelto con `forRecipients()` en una
sola consulta para toda la página, y un menú de acciones:

| Acción | Qué hace |
|---|---|
| Ver el correo | Abre en un modal el HTML que se le envió a esa persona (`body_html` del log; si ya se purgó, re-renderiza la plantilla) |
| Abrirlo en otra pestaña | Lo mismo como documento suelto |
| Ver la trazabilidad en el log | Salta al visor de `HelpdeskEmailActivity` filtrado por ese correo |
| Reintentar el envío | Solo para los fallidos: los devuelve a `pending`. Nunca reencola un enviado — sería felicitar dos veces |
| Dar de baja de cumpleaños | Añade la dirección a `email_suppressions` acotada al módulo (no corta los correos de sus tickets) |

---

## Idioma del cliente

El ERP devuelve su id interno de idioma (`CLIENTE_CENT.IDIDIOMA`), que no
coincide con los ids de `langs` del módulo Mailer. La correspondencia se
declara en `helpdeskbirthday.erp_language_map` (id del ERP → ISO).

**Está vacío a propósito**: mientras nadie confirme los ids reales del ERP todo
el mundo recibe el correo en `fallback_language` (español), que es el
comportamiento anterior. Rellenar ese mapa es lo único que hace falta para
escribir en portugués o inglés — la plantilla ya es multi-idioma vía
`mailer_template_langs`, solo hay que traducirla en el admin de Mailer.

El idioma resuelto se guarda en `helpdesk_birthday_recipients.lang` (el ISO, no
el id) para que el histórico siga siendo legible aunque cambie el mapeo. Un ISO
mapeado a un idioma que no existe en Mailer cae al de por defecto: sin
traducción el correo saldría en blanco.

---

## GDPR

`AnonymizeBirthdayRecipients` escucha `CustomerGdprDeleted` y **anonimiza** los
destinatarios de esa dirección: email hasheado, nombre y **fecha de nacimiento**
a null. Esta tabla guarda dato personal sensible, y la cascada de
HelpdeskCompliance cubre core/Tickets/ChatFlow/EmailLog pero no conoce este
módulo — es el satélite quien se suscribe, no al revés.

Se anonimiza y no se borra la fila: borrarla descuadraría los contadores de la
campaña y perdería la trazabilidad de un envío que sí ocurrió. Desaparece la
persona, no el hecho.

El listener se registra **antes** del early-return de módulo deshabilitado: es
una obligación legal, y con el módulo apagado el dato sigue en la tabla.

---

## Cuando el envío se atasca

`BirthdayQueueHealthService` detecta que los correos se encolan y no sale
ninguno, y el panel lo avisa en rojo… en verde, arriba del todo. Dos señales:
destinatarios reservados (`sending`) sin resolver desde hace más de 15 minutos,
y pendientes cuya hora ya pasó. La primera es la fiable, porque no depende de
poder hablar con Redis.

Es el fallo que más veces se ha repetido en este repo: un worker caído sin que
nadie se entere.

---

## Métricas de negocio: el canje

Abrir el correo no es comprar. `BirthdayRedemptionService` mide cuánta gente
usó el cupón consultando **PrestaShop** (`order_cart_rule` × `cart_rule` ×
`orders` × `customer`, con el mismo patrón de BD cualificada que
`HelpdeskPrestashop\Services\PrestashopProductQueryService`, porque la conexión
`prestashop` de `config/database.php` no está configurada en este entorno).

Aparece como «Cupones usados» y «Facturado» en el panel, como columna
**Canjeado** por destinatario, y con detalle completo en
`panel/helpdeskbirthday/campaigns/{id}/redemptions`: por cada pedido, su
referencia, estado, importe, descuento aplicado, si es de un destinatario y
**qué contestó gestión al marcar el bono**.

Esa última columna sale de `marcarbono`, la tabla que PrestaShop escribe al
canjear un bono contra el ERP (ver `Marcarbono.php` del override). Su
`erp_response` es lo que permite detectar el caso feo: un cupón consumido en la
tienda que **no** llegó a descontarse en gestión. Tres estados posibles:
«Marcado» (respuesta `ok`), «Revisar» (gestión contestó otra cosa) y «Sin
registro» (el pedido no pasó por ese flujo).

`HELPDESK_BIRTHDAY_PS_ADMIN_URL` habilita el enlace al pedido en el back-office
de PrestaShop. Ojo: PrestaShop exige un token por controlador y usuario que no
se puede generar desde aquí, así que el enlace lleva al pedido pero pedirá
confirmar el token.

**Qué se busca en PrestaShop:** los códigos de los DESTINATARIOS
(`BirthdayRedemptionService::codesFor()`), porque el bono es de cada cliente. El
código de campaña se sigue incluyendo para las promociones antiguas de código
único. Buscar solo por el de la campaña dejaba todas las cifras de canje a cero
—«Cupones usados», «Facturado», el paso «Compraron» del embudo y
`check-unmarked`— sin que nada avisara de que estaban midiendo un campo vacío.

Con un bono por cliente la lista de códigos es larga (577 en un día cualquiera,
más de 50.000 en 90 días), así que se consulta por bloques de 2.000.

La atribución sigue cruzando el email del pedido con los destinatarios: quien
reenvíe su código a un amigo cuenta como canje del bono pero no como canje
atribuido.

Si `HELPDESK_PS_DB` no está configurada, las tarjetas de canje **se ocultan** en
vez de mostrar 0: un cero ahí parecería un mal resultado cuando en realidad es
«no lo sabemos».

`helpdeskbirthday:check-unmarked` corre a diario (07:30) y avisa de los cupones
que el cliente gastó en la tienda pero que **no se descontaron en gestión**: el
descuento ya está hecho y el bono sigue vivo, así que se puede volver a usar.
Desde la pantalla de canjes se puede reintentar el marcado, que llama a
`ErpService::marcarBono()` con `operacion = 2` (consumir), el mismo valor que
usa el override de PrestaShop. **Eso escribe en el ERP.**

El panel también muestra el embudo completo: cumpleañeros → enviados → abiertos
→ con clic → compraron, con la caída porcentual entre pasos.

---

## Trazabilidad: HelpdeskEmailActivity

Todo lo que sale de aquí queda registrado en `email_logs`. El vínculo tiene tres
piezas y las tres hacen falta:

1. `BirthdayCouponMailable implements TracksEmailLog` — declara módulo
   (`HelpdeskBirthday`), entidad (`BirthdayRecipient` + id) y el id de cliente
   del ERP. Sin eso el listener registra el correo pero no sabe de quién es.
2. `helpdeskemailactivity.tracked_modules` debe incluir el módulo (ya está).
   Era una comparación literal contra `HelpdeskTickets` dentro de
   `LogEmailQueued`, así que cualquier otro módulo que midiera aperturas
   mostraba **0% para siempre** sin ninguna pista de por qué.
3. `helpdesk_birthday_recipients.email_log_id`, que escribe
   `SendBirthdayEmailJob` tras enviar. Es el enlace de vuelta: sin él la columna
   se quedaba a null, el menú de la fila nunca ofrecía «ver la trazabilidad en
   el log» y «ver el correo» re-renderizaba la plantilla en vez de enseñar el
   HTML que de verdad salió — que es el que vale cuando un cliente reclama.

Con eso, el panel resuelve entregado/abierto/clic por destinatario con
`EmailDeliveryLookupService::forRecipients()` (una consulta por página) y los
KPIs con `statsForModule()`.

---

## Antes de enviar de verdad

`helpdeskbirthday:test-send correo@empresa.com` (o el bloque «Enviar una
prueba» en Ajustes) manda la felicitación a direcciones internas con el cupón
configurado, **sin tocar la campaña ni sus destinatarios**: el destinatario de
prueba es un modelo en memoria. El asunto lleva el prefijo `[PRUEBA]`.

---

## Cuando algo va mal

Si `prepare` aborta la campaña, `BirthdayCampaignFailedNotification` avisa a
quien pueda gestionarlas (notificación de base de datos + broadcast). Sin ese
aviso el fallo era mudo: el comando corre a las 06:00 sin nadie delante y nadie
se enteraba hasta abrir el panel, con un día entero de cumpleaños sin felicitar.

**Cualquier excepción cuenta como fallo de campaña**, no solo
`BirthdayAudienceException`. Antes un timeout HTTP subía sin capturar, mataba el
comando y dejaba la campaña en `draft` para siempre: sin estado de error, sin
aviso y sin reintento. Ocurrió el 6-sep-2026 (`cURL error 28` contra
`/api/erp/customer/birthday-stats`) y ese día no se felicitó a nadie.

Tres cosas lo cubren ahora:

1. El `catch (Throwable)` marca la campaña `failed` con el mensaje y avisa.
2. `prepare` se reintenta cada hora entre `prepare_at` y `window_end` — es
   idempotente y no hace nada si la campaña del día ya está lista. Una campaña
   `failed` y todavía sin destinatarios se vuelve a intentar el mismo día.
3. `BirthdayQueueHealthService` marca `missing_today` cuando pasada la hora de
   preparación no hay campaña del día. Sin esa señal la ausencia total era
   invisible: no hay nada atascado porque no hay nada.

El `withoutOverlapping()` de las tareas lleva minutos explícitos (30 en
`prepare`, 5 en `dispatch-due`). Por defecto el candado dura 24 h: un proceso
muerto de golpe lo dejaba puesto y la tarea no volvía a correr en todo el día.

Los envíos fallidos se devuelven a la cola de uno en uno desde el menú de la
fila, o todos a la vez con «Reintentar los N fallidos». El reintento masivo
reabre la campaña si estaba cerrada — si no, los destinatarios se quedarían en
`pending` sin que nadie los recoja.

---

## Operación

```bash
# Ver a quién se enviaría hoy, sin crear nada
php artisan helpdeskbirthday:prepare --dry-run
php artisan helpdeskbirthday:prepare --date=2026-09-02

php artisan helpdeskbirthday:dispatch-due --limit=10
php artisan helpdeskbirthday:finalize

# Probar la plantilla en un cliente de correo real
php artisan helpdeskbirthday:test-send yo@empresa.com
```

Panel en `panel/helpdeskbirthday/campaigns` y ajustes en
`panel/helpdeskbirthday/settings` (ventana, tope por hora, cupón, exclusiones,
política del 29-feb).

**Cola `birthdays`** — declarada en `docker/docker-compose.yml` (servicio
`worker-helpdesk`) y en `devops/supervisor/laravel-queue-helpdesk.conf`. Si se
cambia el nombre en la config hay que tocar ambos sitios o los correos se
encolan y no sale ninguno, en silencio. Cola propia y no `emails` porque esa la
comparten IA, embeddings y ERP con solo 2 procesos.

**Mailpit de este proyecto: `localhost:8027`** (el :8026 es el de `system` y el
:8025 es un tercero — es fácil mirar el que no toca y creer que no llega nada).

---

## Puesta en marcha

```bash
php artisan migrate --path=modules/HelpdeskBirthday/database/migrations --database=helpdesk
php artisan db:seed --class='Modules\HelpdeskBirthday\Database\Seeders\HelpdeskBirthdayPermissionsSeeder'
php artisan db:seed --class='Modules\HelpdeskBirthday\Database\Seeders\BirthdayTemplatesSeeder'
cp modules/HelpdeskBirthday/public/css/birthday.css public/modules/helpdeskbirthday/css/
```

(`module:seed --class FQCN` es un no-op silencioso en este repo; usar `db:seed`.)

El interruptor del módulo es `helpdesk_birthday_enabled()`, que combina el estado
en `modules_statuses.json` con el ajuste `birthday.integration_enabled` de
Settings → Integraciones.

---

## Pendiente

- Configurar `HELPDESK_BIRTHDAY_SHOP_URL`: el botón «Usar mi regalo» apunta por
  defecto a la propia app, que no es la tienda.
- Confirmar el tipo de bono (`bono_type_id`, hoy 4) con Álvarez antes de emitir
  bonos de verdad: es lo que decide importe y validez de lo que se regala.
- Rellenar `erp_language_map` con los ids reales de idioma del ERP.

La audiencia ya no está bloqueada: el filtro `birthday` se resuelve contra la
API de clientes de este mismo panel (`audience_source = 'api'`), no contra el
manager externo. Comprobado el 7-sep-2026 con `--dry-run`: 577 cumpleañeros.
