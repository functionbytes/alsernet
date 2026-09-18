# Configuración de canales sociales (Helpdesk)

Guía operativa para conectar Facebook Messenger, Instagram DMs y WhatsApp Business
al módulo Helpdesk. Todo está hecho **por API** desde la terminal — sin entrar al
portal de Facebook Developers.

## Contexto

- **App de Meta usada**: `Functionbytes` (`app_id=847618268258098`)
- **Página de Facebook**: `Function Bytes` (`page_id=109442377559389`)
- **Cuenta de Instagram vinculada**: `instagram_business_account=17841405747428478`
- **Dominio público de webhooks**: `https://channels.functionbytes.com` (Cloudflare Tunnel)
- **Verify token compartido**: `823bc73b3e7ed39b47c838113d4b2fae0981d78fb43553c0`

Las URLs de webhook que el código sirve son:

| Canal | URL |
|---|---|
| Facebook Messenger | `https://channels.functionbytes.com/api/helpdesk/webhooks/facebook` |
| Instagram DMs | `https://channels.functionbytes.com/api/helpdesk/webhooks/instagram` |
| WhatsApp Business | `https://channels.functionbytes.com/api/helpdesk/webhooks/whatsapp` |

Las rutas se registran en `modules/Helpdesk/routes/webhooks.php` y `RouteServiceProvider`
las monta con prefijo `api/helpdesk`.

---

## Variables `.env`

```env
# Facebook Messenger
FACEBOOK_ENABLED=true
FACEBOOK_APP_ID=847618268258098
FACEBOOK_APP_SECRET=515ddfe07232cdb5da57338edb99da6f
FACEBOOK_PAGE_ACCESS_TOKEN=EAAMC...   # Page Access Token (Generar token en Facebook Login → Tokens de acceso)
FACEBOOK_VERIFY_TOKEN=823bc73b3e7ed39b47c838113d4b2fae0981d78fb43553c0

# Instagram (comparte App con Facebook)
INSTAGRAM_ENABLED=true
INSTAGRAM_APP_ID=2015402839313914
INSTAGRAM_APP_SECRET=51f0a872386384e7c7b35945f98d1cb4
INSTAGRAM_BUSINESS_ACCOUNT_ID=17841405747428478
INSTAGRAM_ACCESS_TOKEN=EAAMC...   # Mismo Page Access Token vale si la página tiene IG vinculado
INSTAGRAM_VERIFY_TOKEN=823bc73b3e7ed39b47c838113d4b2fae0981d78fb43553c0

# WhatsApp Business
WHATSAPP_ENABLED=true
WHATSAPP_PHONE_NUMBER_ID=...
WHATSAPP_BUSINESS_ACCOUNT_ID=...
WHATSAPP_ACCESS_TOKEN=...
WHATSAPP_VERIFY_TOKEN=823bc73b3e7ed39b47c838113d4b2fae0981d78fb43553c0
```

Después de cualquier cambio en `.env`:

```bash
php artisan config:clear
supervisorctl restart horizon:horizon_00
```

---

## Conceptos clave

### App access token
Token que se construye **sin llamar a la API**:

```
APP_ACCESS_TOKEN = "{APP_ID}|{APP_SECRET}"
```

Es lo que usás para administrar **suscripciones de webhooks** a nivel de app.

### Page Access Token
Token que generás en `developers.facebook.com → tu app → Generar tokens de acceso`.
Se usa para:
- Enviar mensajes desde la página
- Leer perfil de usuarios (`/{psid}?fields=name,profile_pic`)
- Suscribir la página a campos específicos

Inspeccionar un token desconocido:

```bash
TOKEN="EAA..."
curl -s "https://graph.facebook.com/debug_token?input_token=$TOKEN&access_token=$TOKEN"
```

Respuesta clave:
- `type`: `PAGE` | `USER` | `APP`
- `app_id`: ID de la app dueña
- `profile_id`: ID de la entidad (page id)
- `scopes`: permisos otorgados
- `expires_at`: 0 = no expira (page tokens generados con login NO expiran)

### Diferencia "Page subscription" vs "App subscription"
Hay DOS niveles de suscripción que deben coexistir:

1. **App-level** (`/{app_id}/subscriptions`) — define `object` (page, instagram, whatsapp_business_account), `callback_url`, `verify_token` y `fields` globales. Se hace UNA vez.
2. **Page-level** (`/{page_id}/subscribed_apps`) — autoriza a la app específica a recibir webhooks de ESA página.

Si falta una de las dos, los mensajes nunca llegan.

---

## 1. Cloudflare Tunnel (webhook público)

Necesario porque Meta exige HTTPS público con cert válido. `manager.test`/`system.test`
son self-signed y Meta no los acepta.

### Login (una vez)

```bash
cloudflared tunnel login
```

Abre el navegador, autoriza la zona `functionbytes.com`. Esto guarda el cert en
`~/.cloudflared/cert.pem`.

### Configuración del túnel

`~/.cloudflared/config.yml`:

```yaml
tunnel: <tunnel-id>
credentials-file: /Users/developerts/.cloudflared/<tunnel-id>.json

ingress:
  - hostname: channels.functionbytes.com
    service: https://system.test:443
    originRequest:
      noTLSVerify: true
  - service: http_status:404
```

### Iniciar / mantener vivo

```bash
cloudflared tunnel run <tunnel-name>
# o como servicio
sudo cloudflared service install
```

### Verificar accesibilidad

```bash
curl -sk "https://channels.functionbytes.com/api/helpdesk/webhooks/facebook?hub.mode=subscribe&hub.challenge=test&hub.verify_token=823bc73b3e7ed39b47c838113d4b2fae0981d78fb43553c0"
# Esperado: test
```

Si responde 200 con el challenge, Meta podrá validar la URL.

---

## 2. Configurar Facebook Messenger

### a) Page Access Token

En `developers.facebook.com → app 847618268258098 → Messenger → Configuración →
Tokens de acceso`:

1. Seleccionar la página `Function Bytes`
2. Copiar el token generado
3. Guardar en `FACEBOOK_PAGE_ACCESS_TOKEN`

### b) Suscribir App al webhook

```bash
APP_ID="847618268258098"
APP_SECRET="515ddfe07232cdb5da57338edb99da6f"
APP_TOKEN="${APP_ID}|${APP_SECRET}"

curl -X POST "https://graph.facebook.com/v19.0/${APP_ID}/subscriptions" \
  -d "object=page" \
  -d "callback_url=https://channels.functionbytes.com/api/helpdesk/webhooks/facebook" \
  -d "fields=messages,messaging_postbacks,message_deliveries,message_reads,message_reactions" \
  -d "verify_token=823bc73b3e7ed39b47c838113d4b2fae0981d78fb43553c0" \
  -d "include_values=true" \
  -d "access_token=${APP_TOKEN}"
```

Esperado: `{"success":true}`. Meta hace un GET al callback para validar el verify_token
y solo guarda la suscripción si responde correctamente con el challenge.

### c) Suscribir la página a la app

```bash
PAGE_ID="109442377559389"
PAGE_TOKEN="EAAMC..."   # FACEBOOK_PAGE_ACCESS_TOKEN

curl -X POST "https://graph.facebook.com/v19.0/${PAGE_ID}/subscribed_apps" \
  -d "subscribed_fields=messages,messaging_postbacks,message_deliveries,message_reads,message_reactions" \
  -d "access_token=${PAGE_TOKEN}"
```

Esperado: `{"success":true}`.

### d) Verificar

```bash
# Apps suscritas a la página
curl -s "https://graph.facebook.com/v19.0/${PAGE_ID}/subscribed_apps?access_token=${PAGE_TOKEN}"

# Webhooks suscritos por la app
curl -s "https://graph.facebook.com/v19.0/${APP_ID}/subscriptions?access_token=${APP_TOKEN}"
```

Tras esto, mandar un mensaje al Messenger de la página debería disparar
`POST /api/helpdesk/webhooks/facebook`.

---

## 3. Configurar Instagram

Instagram comparte la misma App de Meta, pero el `object` del webhook es distinto.

### a) Encontrar el Instagram Business Account ID

Necesario tener la cuenta de Instagram **convertida a Business o Creator** y
**vinculada a la página de Facebook**.

```bash
PAGE_ID="109442377559389"
PAGE_TOKEN="EAAMC..."

curl -s "https://graph.facebook.com/v19.0/${PAGE_ID}?fields=instagram_business_account&access_token=${PAGE_TOKEN}"
```

Respuesta:
```json
{"instagram_business_account":{"id":"17841405747428478"},"id":"109442377559389"}
```

Guardá ese `id` en `INSTAGRAM_BUSINESS_ACCOUNT_ID`.

Si retorna `connected_instagram_account` en lugar de `instagram_business_account`,
la cuenta es personal — convertirla a Business desde la app de Instagram primero.

### b) Suscribir App al webhook de Instagram

```bash
APP_ID="847618268258098"
APP_SECRET="515ddfe07232cdb5da57338edb99da6f"
APP_TOKEN="${APP_ID}|${APP_SECRET}"

curl -X POST "https://graph.facebook.com/v19.0/${APP_ID}/subscriptions" \
  -d "object=instagram" \
  -d "callback_url=https://channels.functionbytes.com/api/helpdesk/webhooks/instagram" \
  -d "fields=messages,messaging_postbacks,message_reactions" \
  -d "verify_token=823bc73b3e7ed39b47c838113d4b2fae0981d78fb43553c0" \
  -d "include_values=true" \
  -d "access_token=${APP_TOKEN}"
```

> ⚠️ Si incluís campos como `messaging_seen` o `messaging_referrals` y la app no
> tiene esos permisos aprobados, retorna `Invalid Permissions`. Empezar con
> `messages,messaging_postbacks,message_reactions` y agregar más cuando se
> aprueben.

### c) Verificar

```bash
curl -s "https://graph.facebook.com/v19.0/${APP_ID}/subscriptions?access_token=${APP_TOKEN}" \
  | python3 -m json.tool
```

Tiene que aparecer `"object":"instagram"` con `"active":true`.

### d) Probar end-to-end

Enviar un DM al perfil de Instagram **desde otra cuenta** (no la propia, los mensajes
con `is_echo:true` se filtran en el job). El job `ProcessSocialWebhookJob` debe
crear una conversación con `channel=instagram`.

---

## 4. Permisos y scopes requeridos

El Page Access Token debe tener estos scopes (visibles en `debug_token`):

- `pages_show_list`
- `pages_messaging`
- `pages_manage_metadata`
- `pages_read_engagement`
- `instagram_basic`
- `instagram_manage_messages`
- `whatsapp_business_management` (si se usa WhatsApp)
- `whatsapp_business_messaging` (si se usa WhatsApp)
- `public_profile`

Si falta alguno, regenerar el Page Access Token después de pedir el permiso adicional
en `App Review → Permissions and Features`.

---

## 5. Troubleshooting

### `Signature verification failed`
La signature `X-Hub-Signature-256` se valida con `APP_SECRET` (HMAC-SHA256 del raw
body). Si falla:
- `FACEBOOK_APP_SECRET` (o `INSTAGRAM_APP_SECRET`) en `.env` no coincide con la app
- Hay un proxy que altera el body (Cloudflare por defecto NO altera, pero algunos
  middlewares de Laravel sí)
- `php artisan config:clear` después de cambiar `.env`

### Webhook devuelve 200 pero no llega ningún POST
- App no está suscrita al `object` correcto (verificar con `/subscriptions`)
- Página no está suscrita a la app (`/{page_id}/subscribed_apps`)
- La cuenta de Instagram no es Business / no está vinculada a la página
- El callback URL en Meta apunta a un dominio que ya no resuelve (Cloudflare tunnel
  caído → respuesta 530)

### `Tried accessing nonexisting field (instagram_business_account.id)`
La sintaxis de subfield con punto no funciona en algunos endpoints. Usar:
```
?fields=instagram_business_account
```
y leer el `id` del objeto retornado, en lugar de `instagram_business_account.id`.

### `Invalid OAuth access token - Cannot parse access token` al consultar `/me`
El token es de tipo `PAGE`, no de usuario. `/me` no aplica. Inspeccionar con:
```bash
curl "https://graph.facebook.com/debug_token?input_token=$TOKEN&access_token=$TOKEN"
```

### Mensajes lentos o se pierden
- Antes había `WithoutOverlapping->dontRelease()` en `ProcessSocialWebhookJob`. Eliminado
  porque descartaba jobs concurrentes con el mismo lock id. La protección contra
  duplicados se hace ahora con `isDuplicate()` por `external_id`.
- Cache `getUserProfile` con TTL 6h evita 200-400ms de Graph API por mensaje.
- `ConversationMessageCreated`, `ConversationItemCreated`, `HelpdeskInboxUpdated`
  son `ShouldBroadcastNow` (no pasan por cola de broadcasting).

---

## 6. Comandos de referencia rápida

```bash
# Estado actual de subscripciones de la app
APP_TOKEN="847618268258098|515ddfe07232cdb5da57338edb99da6f"
curl -s "https://graph.facebook.com/v19.0/847618268258098/subscriptions?access_token=${APP_TOKEN}" | python3 -m json.tool

# Apps suscritas a la página
PAGE_TOKEN="EAAMC..."
curl -s "https://graph.facebook.com/v19.0/109442377559389/subscribed_apps?access_token=${PAGE_TOKEN}"

# Borrar todas las conversaciones (testing)
php artisan tinker --execute='
DB::connection("helpdesk")->statement("SET FOREIGN_KEY_CHECKS=0");
DB::connection("helpdesk")->table("helpdesk_conversation_items")->delete();
DB::connection("helpdesk")->table("helpdesk_conversations")->delete();
DB::connection("helpdesk")->table("helpdesk_customers")->where(function($q) {
    $q->whereNotNull("facebook_psid")->orWhereNotNull("instagram_id")->orWhereNotNull("whatsapp_phone");
})->delete();
DB::connection("helpdesk")->statement("ALTER TABLE helpdesk_conversations AUTO_INCREMENT = 1");
DB::connection("helpdesk")->statement("ALTER TABLE helpdesk_conversation_items AUTO_INCREMENT = 1");
DB::connection("helpdesk")->statement("SET FOREIGN_KEY_CHECKS=1");
'

# Tail logs
tail -f storage/logs/horizon.log
tail -f storage/logs/reverb.log
tail -f storage/logs/laravel.log
```

---

## 7. Cambios al código aplicados

Trabajo del 2026-05-01 que hizo todo esto funcionar:

- `app/Events/ConversationMessageCreated.php`: canal `helpdesk.conversation.{id}`,
  `broadcastAs('item.created')`, payload envuelto en `{message: {...}}`,
  `ShouldBroadcastNow`.
- `app/Events/ConversationItemCreated.php` (widget): mismo formato unificado.
- `app/Events/HelpdeskInboxUpdated.php` (nuevo): canal `helpdesk.inbox`,
  `broadcastAs('inbox.updated')`, alimenta el sidebar.
- `routes/channels.php`: autorización del canal `helpdesk.inbox`.
- `app/Jobs/ProcessSocialWebhookJob.php`:
  - `event(...)` → `broadcast(...)` para forzar broadcasting cuando se usa
    `ShouldBroadcastNow`
  - `WithoutOverlapping` removido (descartaba jobs)
  - `getUserProfile` cacheado 6h por PSID
  - `attachment_urls` rellenado con formato `{url, name, size, mime_type}` para
    que el thread renderice imágenes/audios/videos correctamente
  - `inbox_id` asignado automáticamente desde `helpdesk_inboxes` por canal
- `app/Services/Widget/WidgetConversationService.php`: usa `broadcast()` y dispara
  `HelpdeskInboxUpdated` también.
- Migraciones: `email` y `subject` ahora `nullable` (los webhooks de redes sociales
  no proveen email ni asunto).
- `config/horizon.php`: nuevo supervisor `supervisor-helpdesk-webhooks` con 3-8
  procesos dedicados a la cola `helpdesk-webhooks`.
- `resources/views/managers/inbox/index.blade.php`: listener global
  `helpdesk.inbox` con update optimista del DOM (sin AJAX para conversaciones
  existentes).
- `.env`: `QUEUE_CONNECTION=redis` (antes era `database` y Horizon no procesaba),
  `REVERB_HOST=system.test` (antes era `manager.test`, mismo cert que el sitio
  evita problemas de WebSocket cross-origin).
