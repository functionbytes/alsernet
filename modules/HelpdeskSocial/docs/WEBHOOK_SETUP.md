# Configuración de Webhooks de Meta (Facebook / Instagram)

Guía paso a paso para conectar el módulo HelpdeskSocial con los webhooks de Meta.

---

## 1. Requisitos previos

- Tener una **Meta App** creada en [developers.facebook.com](https://developers.facebook.com)
- La app debe tener los productos **Webhooks** y **Graph API** añadidos
- Tener una **Página de Facebook** o **cuenta de Instagram Business** conectada

---

## 2. Variables de entorno

Añade estas variables al `.env`:

```env
HELPDESK_SOCIAL_META_ENABLED=true
HELPDESK_SOCIAL_META_APP_ID=tu_app_id
HELPDESK_SOCIAL_META_APP_SECRET=tu_app_secret
HELPDESK_SOCIAL_META_API_VERSION=v25.0
HELPDESK_SOCIAL_META_VERIFY_TOKEN=un_token_seguro_aleatorio
```

> **Importante:** `HELPDESK_SOCIAL_META_VERIFY_TOKEN` es un string secreto que tú eliges. Meta lo enviará en cada solicitud de verificación y el sistema lo validará.

---

## 3. URL del webhook

La URL pública a configurar en Meta es:

```
https://tu-dominio.com/webhooks/meta
```

Si usas Laravel Herd localmente para pruebas, puedes usar ngrok:

```bash
ngrok http https://system.test
```

Y luego usar la URL HTTPS que ngrok te dé:

```
https://abc123.ngrok.io/webhooks/meta
```

---

## 4. Configuración en Meta Developers

### 4.1 Ir a Webhooks

1. Abre tu app en [developers.facebook.com](https://developers.facebook.com)
2. En el menú lateral, selecciona **Webhooks** → **Page**

### 4.2 Suscribirse a eventos

En la sección **Page**, suscríbete a estos campos:

| Campo | Descripción |
|-------|-------------|
| `feed` | Comentarios en publicaciones de la página |
| `mention` | Menciones de la página en comentarios |

Si también usas Instagram Business, suscríbete a **Instagram** → `mention`.

### 4.3 Configurar la URL de callback

1. Haz clic en **Editar suscripción**
2. **URL de callback:** `https://tu-dominio.com/webhooks/meta`
3. **Token de verificación:** el mismo valor que pusiste en `HELPDESK_SOCIAL_META_VERIFY_TOKEN`
4. Haz clic en **Verificar y guardar**

Si todo está correcto, Meta enviará un `GET` a tu URL con:

```
hub.mode=subscribe
hub.verify_token=<tu_token>
hub.challenge=<numero_aleatorio>
```

Y tu aplicación responderá con el `hub_challenge`, confirmando la suscripción.

---

## 5. Obtener Page Access Token

El webhook solo notifica que hay actividad. Para leer y responder comentarios, necesitas un **Page Access Token**.

### 5.1 Desde Meta Developers

1. Ve a **Herramientas** → **Graph API Explorer**
2. Selecciona tu app y genera un token de usuario
3. Añade el permiso `pages_read_engagement`
4. Cambia el endpoint a `/{page-id}?fields=access_token`
5. El token devuelto es tu **Page Access Token**

### 5.2 Registrar en HelpdeskSocial

1. Ve al panel: `/panel/helpdesk-social/accounts`
2. Crea una nueva cuenta con:
   - **Plataforma:** `facebook`
   - **Page Access Token:** el token obtenido
   - **External ID:** el ID numérico de tu página
   - **Activar:** Sí

---

## 6. Flujo de datos cuando llega un comentario

```
┌─────────────┐     ┌──────────────┐     ┌─────────────────────┐
│   Meta      │────▶│  Webhook     │────▶│ ProcessSocialComment│
│  (comentario)│     │  /webhooks/meta│     │        Job          │
└─────────────┘     └──────────────┘     └─────────────────────┘
                                                   │
                    ┌──────────────────────────────┼──────────────┐
                    ▼                              ▼              ▼
            ┌─────────────┐              ┌────────────────┐  ┌──────────────┐
            │ SocialComment│              │  IntentClassify │  │ AutoReplyEngine│
            │   (persiste) │              │     Job         │  │   (reglas)     │
            └─────────────┘              └────────────────┘  └──────────────┘
                    │                              │                  │
                    ▼                              ▼                  ▼
            ┌─────────────┐              ┌────────────────┐  ┌──────────────┐
            │Broadcast    │              │ SocialIntent   │  │ SocialComment│
            │(real-time)  │              │   (persiste)   │  │   (replied)  │
            └─────────────┘              └────────────────┘  └──────────────┘
```

### Pasos detallados

1. **Meta envía POST** a `/webhooks/meta` con payload del comentario
2. **`MetaWebhookController::handle()`** recibe el payload
3. **`ProcessSocialCommentJob`** se encola en `helpdesk-social-processing`
4. El job:
   - Crea `SocialComment` en estado `pending`
   - Dispara `SocialCommentReceived` (broadcast a agentes en tiempo real)
   - Llama a `IntentClassificationService::classify()`
   - Llama a `RuleBasedAutoReplyEngine::evaluate()`
   - Si no hay auto-reply, crea conversación en Helpdesk
5. **Broadcast** llega a la bandeja social vía Laravel Reverb + Echo

---

## 7. Troubleshooting

### "Forbidden" al verificar webhook

- Verifica que `HELPDESK_SOCIAL_META_VERIFY_TOKEN` coincida exactamente
- Revisa que la URL sea accesible públicamente (no localhost)
- Verifica los logs: `tail -f storage/logs/laravel.log`

### No llegan comentarios

- Confirma que la suscripción a `feed` está activa en Meta Developers
- Verifica que la página tenga el webhook suscrito (no solo la app)
- Revisa `helpdesk_social_accounts` → `comments_enabled = 1`
- Revisa `failed_jobs` si hay errores en el procesamiento

### Token expirado

- El `health-check` diario detecta tokens a 7 días de expirar
- Renueva el token desde Graph API Explorer o implementa exchange automático

### Broadcasting no funciona

- Confirma que Reverb está corriendo: `php artisan reverb:start`
- Verifica que el usuario tenga rol `helpdesk-agent`, `administrative`, `manager` o `super-admin`
- Revisa la consola del navegador por errores de conexión WebSocket

---

## 8. Comandos útiles para diagnóstico

```bash
# Verificar salud de cuentas
php artisan helpdesk-social:health-check --notify

# Sincronizar comentarios manualmente
php artisan helpdesk-social:sync-comments {account_id} --post-id={post_id}

# Ver trabajos fallidos
php artisan queue:failed

# Reintentar un trabajo fallido
php artisan queue:retry {id}
```
