# HelpdeskLivechat

Widget de chat en vivo embebible para sitios web. Extiende el módulo `Helpdesk` con un canal `web` que permite a los visitantes anónimos iniciar conversaciones que llegan al panel del agente en tiempo real.

## Dependencias

- `Helpdesk` (módulo base — Conversation, Customer, Inbox, broadcasting)
- Laravel Reverb (WebSockets)
- Redis (queues + broadcasting)

## Stack frontend del widget

> **Excepción documentada al estándar del proyecto:** el widget vive en un bundle aislado que se inyecta en sitios de terceros donde no se puede asumir jQuery del host. Por eso usa **React 19 + Zustand + TypeScript + Vite**, en lugar del stack estándar (Bootstrap 5.3 + jQuery). El panel admin del helpdesk sigue siendo Blade + jQuery.

## Instalación del widget en un sitio externo

Pegar antes del cierre de `</body>`:

```html
<script src="https://your-helpdesk.com/widget/helpdesk/script/{websiteToken}"></script>
```

Donde `{websiteToken}` se obtiene al crear un inbox de canal `web` en `Settings > Helpdesk > Bandejas > canal Web`.

Alternativa equivalente (alias):
```html
<script src="https://your-helpdesk.com/hd/widget-loader.js"></script>
<script>window.HELPDESK_WEBSITE_TOKEN = '...';</script>
```

## API pública del widget (`/hd/api/`)

| Método | Endpoint | Descripción | Rate limit |
|---|---|---|---|
| POST | `/conversation` | Crear o reusar conversación | 10/min |
| GET | `/conversation/{id}` | Detalle | 60/min |
| POST | `/conversation/{id}/messages` | Enviar mensaje | 30/min |
| GET | `/conversation/{id}/messages` | Listar mensajes | 60/min |
| POST | `/conversation/{id}/typing` | Indicador de escritura | 30/min |
| POST | `/conversation/{id}/read` | Marcar como leído | 60/min |
| POST | `/conversation/{id}/close` | Cerrar (con CSAT opcional) | 30/min |
| POST | `/conversation/{id}/email-transcript` | Enviar transcript por email | 5/min |
| POST | `/session/heartbeat` | Tracking de sesión + geoip (requiere `website_token` válido) | 120/min |
| GET | `/settings` | Config del widget | 120/min |
| GET | `/helpcenter` | Artículos del help center | 120/min |
| POST | `/livestream/{conversation}/events` | Ingesta de eventos rrweb (live view); persiste en background vía `StoreLivestreamBatchJob`, payload máx. 256 KB | 200/min |
| POST | `/webrtc/{conversation}/offer` | Señalización WebRTC: oferta SDP | 60/min |
| POST | `/webrtc/{conversation}/ice` | Señalización WebRTC: candidato ICE | 60/min |
| POST | `/webrtc/{conversation}/end` | Señalización WebRTC: fin de sesión | 60/min |

Todas las rutas pasan por `ValidateTrustedOrigin` (valida el header `Origin`/`Referer` contra `Web::trusted_domains`; si hay dominios configurados y no llega ningún header de origen, la petición se rechaza con 403) y por `VerifyWidgetHmac` (valida `X-Identifier-Hash` cuando el inbox tiene `enforce_identity_verification`). Las acciones sobre una conversación concreta verifican que el visitante sea el propietario del `customer` asociado.

## Eventos broadcast

| Evento | Canal | Cuando |
|---|---|---|
| `ConversationCreated` | `helpdesk.inbox.{inboxId}` | Visitante crea conversación nueva |
| `ConversationMessageCreated` | `helpdesk-widget-conversation.{conversationId}` | Mensaje del agente o visitante |
| `WidgetTyping` | `helpdesk.conversation.{conversationId}.typing` | Visitante escribe |
| `ConversationMessageRead` | `helpdesk.conversation.{conversationId}` | Visitante marca como leído |

## Health check

```bash
php artisan helpdesk-livechat:check-health
```

Verifica: DB del helpdesk conectada, bundle JS compilado, config de Reverb, conteo de canales web.

## Tests

```bash
# Todos los tests del módulo
vendor/bin/phpunit modules/HelpdeskLivechat/tests/ --no-coverage

# Solo Feature
vendor/bin/phpunit modules/HelpdeskLivechat/tests/Feature/

# Solo Unit
vendor/bin/phpunit modules/HelpdeskLivechat/tests/Unit/
```

Suite incluye: flujo de conversación, seguridad (origin, XSS, MIME), business hours, gating del canal por módulo activo.

## Build del widget

```bash
cd modules/HelpdeskLivechat
npm install
npm run widget:build
```

El bundle compilado va a `public/build-helpdesklivechat/widget.js`.

## Configuración

La config del widget vive en el modelo `Modules\HelpdeskLivechat\Models\Channels\Web` (tabla `helpdesk_channel_webs`). Cada inbox de canal `web` tiene su propia fila con:

- Identidad: `website_token`, `hmac_token`, `website_url`, `trusted_domains`
- Visual: `widget_color`, `widget_position`, `welcome_title`, `welcome_tagline`, `logo_url`
- Comportamiento: `pre_chat_form_enabled`, `offline_message_enabled`, `business_hours`
- Feature flags: `show_avatars`, `show_help_center`, `enable_send_message`, `enable_create_ticket`, `enable_search_help`, `enable_email_transcripts`, `typing_indicator`, `sound_notifications`, `team_avatars`, `show_timestamps`

La página `Settings > Helpdesk > Bandejas > Web > Configurar livechat` (route `settings.helpdesk-livechat.index`) escribe directamente sobre esos campos. Cache de 5 min por `Web` invalidado en `saved()`.

## Estructura del módulo

```
modules/HelpdeskLivechat/
├── app/
│   ├── Console/Commands/        # CheckIntegrationHealthCommand
│   ├── Events/                  # WidgetTyping, etc.
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/             # WidgetConversationController, WidgetSessionController, PreChatFormApiController
│   │   │   ├── Pages/           # WidgetScriptController, WidgetController
│   │   │   └── Settings/        # LivechatSettingsController, PreChatFormsController
│   │   ├── Middleware/          # ValidateTrustedOrigin
│   │   └── Requests/Widget/     # Form requests del widget
│   ├── Mail/                    # ConversationTranscriptMail
│   ├── Models/
│   │   ├── Channels/Web.php
│   │   ├── PreChatForm.php
│   │   ├── WidgetSession.php
│   │   └── WidgetPageView.php
│   ├── Providers/
│   └── Services/
│       ├── Widget/WidgetConversationService.php
│       └── WidgetSessionService.php
├── database/
│   ├── factories/               # WebFactory, PreChatFormFactory, WidgetSessionFactory
│   ├── migrations/
│   └── seeders/                 # HelpdeskLivechatPermissionsSeeder
├── resources/
│   ├── assets/js/widget/        # React + Zustand source
│   └── views/
│       ├── emails/
│       ├── public/widget/       # SPA host page
│       └── settings/
├── routes/
│   ├── api.php                  # /api/v1/helpdesk-livechat/*
│   ├── channels.php             # broadcast auth
│   ├── settings.php             # /panel/settings/helpdesk/livechat
│   ├── web-widget.php           # /widget/helpdesk/*, /hd/widget-loader.js
│   └── widget.php               # /hd/api/* (público)
└── tests/
    ├── Feature/
    └── Unit/
```

## Permisos (Spatie)

Seedados por `HelpdeskLivechatPermissionsSeeder`:

- `helpdesk.livechat.settings.view` — ver settings
- `helpdesk.livechat.settings.update` — actualizar settings
- `helpdesk.pre-chat.manage` — CRUD de pre-chat forms
