# Plan de split: HelpdeskLivechat → HelpdeskLivechat + Engagement

> Documento de plan, no de implementación. Aprobar antes de ejecutar.

## 1. Motivación

`HelpdeskLivechat` actualmente combina dos responsabilidades:

1. **Chat widget** (UI de conversaciones, helpcenter, pre-chat forms, agentes) — específico del helpdesk
2. **Engagement / SDK** (eventos, scoring, segmentos, triggers, personalizations, automation, goals, integraciones e-commerce) — genérico, no es exclusivo del livechat

Esta mezcla crea fricción:
- Un cliente que sólo quiere chat tiene que aceptar GDPR de tracking aunque no lo use
- El módulo **Remarketing** (futuro) necesita las mismas capas (eventos, scoring, segmentos) pero NO necesita el widget de chat
- `HelpdeskLivechat` tiene 50+ archivos cuando solo 15 corresponden al chat puro

**Decisión**: extraer todo lo de engagement a un módulo nuevo `Engagement`, dejar `HelpdeskLivechat` enfocado en su core.

---

## 2. Diagrama final

```
┌──────────────────────────────────────────────────────────────────┐
│ Engagement (módulo nuevo — independiente)                        │
│                                                                  │
│  Schema (10 tablas):                                             │
│   engagement_visitor_sessions   engagement_visitor_scores        │
│   engagement_visitor_contexts   engagement_events                │
│   engagement_trigger_rules      engagement_personalization_rules │
│   engagement_recommendation_profiles  engagement_catalog_products│
│   engagement_platform_integrations    engagement_webhook_logs    │
│   engagement_automation_flows   engagement_automation_runs       │
│   engagement_conversion_goals   engagement_audit_logs            │
│                                                                  │
│  SDK público:                                                    │
│   /eng/api/sdk/init  /track  /identify  /context  /triggers      │
│   /personalizations  /recommendations  /catalog/sync             │
│   /webhook/{platform}/{integrationId}                            │
│                                                                  │
│  Admin UI:                                                       │
│   triggers · personalizations · platforms · automation           │
│   goals · webhook-logs · audit-logs · analytics                  │
│                                                                  │
│  Distribuciones nativas:                                         │
│   PrestaShop · Shopify · WooCommerce · Custom                    │
└──────────────────────────────────────────────────────────────────┘
        ↑                                          ↑
        │ (depende opcional)                       │ (depende opcional)
        │                                          │
┌────────────────────────────┐    ┌────────────────────────────────┐
│ HelpdeskLivechat           │    │ Remarketing (futuro)           │
│                            │    │                                │
│  Schema:                   │    │  Lee eventos, scores y         │
│   helpdesk_widget_sessions │    │  segments del Engagement       │
│   helpdesk_widget_page_views│   │                                │
│   helpdesk_channel_webs    │    │  Aporta:                       │
│   pre_chat_forms           │    │   campañas email/SMS/push      │
│                            │    │   audiencias por score         │
│  Code:                     │    │   schedule de envíos           │
│   widget React app         │    │                                │
│   conversation controllers │    │                                │
│   helpcenter widget API    │    │                                │
│   pre-chat forms           │    │                                │
│   widget settings          │    │                                │
│                            │    │                                │
│  Si Engagement está activo:│    │                                │
│   - acción 'open_chat'     │    │                                │
│     dispara apertura       │    │                                │
│   - widget pasa session    │    │                                │
│     token al SDK           │    │                                │
└────────────────────────────┘    └────────────────────────────────┘
```

---

## 3. Inventario detallado de movimientos

### 3.1 Migraciones (renombre con preservación de datos)

| Tabla actual | Nueva tabla |
|--------------|-------------|
| `helpdesk_livechat_events` | `engagement_events` |
| `helpdesk_livechat_visitor_scores` | `engagement_visitor_scores` |
| `helpdesk_livechat_visitor_contexts` | `engagement_visitor_contexts` |
| `helpdesk_livechat_trigger_rules` | `engagement_trigger_rules` |
| `helpdesk_livechat_personalization_rules` | `engagement_personalization_rules` |
| `helpdesk_livechat_recommendation_profiles` | `engagement_recommendation_profiles` |
| `helpdesk_livechat_catalog_products` | `engagement_catalog_products` |
| `helpdesk_livechat_platform_integrations` | `engagement_platform_integrations` |
| `helpdesk_livechat_webhook_logs` | `engagement_webhook_logs` |
| `helpdesk_livechat_automation_flows` | `engagement_automation_flows` |
| `helpdesk_livechat_automation_runs` | `engagement_automation_runs` |
| `helpdesk_livechat_conversion_goals` | `engagement_conversion_goals` |
| `helpdesk_livechat_audit_logs` | `engagement_audit_logs` |

**Nuevo en Engagement:**
- `engagement_visitor_sessions` (sustituye `helpdesk_widget_sessions` para tracking — la versión livechat se queda para el chat)

**Quedan en HelpdeskLivechat sin cambios:**
- `helpdesk_widget_sessions` (sólo para conversaciones del chat)
- `helpdesk_widget_page_views`
- `helpdesk_channel_webs`
- `pre_chat_forms`

### 3.2 Modelos a mover

```
modules/HelpdeskLivechat/app/Models/
├── ➡️  LivechatEvent            → Engagement\Models\Event
├── ➡️  VisitorScore             → Engagement\Models\VisitorScore
├── ➡️  VisitorContext           → Engagement\Models\VisitorContext
├── ➡️  TriggerRule              → Engagement\Models\TriggerRule
├── ➡️  PersonalizationRule      → Engagement\Models\PersonalizationRule
├── ➡️  RecommendationProfile    → Engagement\Models\RecommendationProfile
├── ➡️  CatalogProduct           → Engagement\Models\CatalogProduct
├── ➡️  PlatformIntegration      → Engagement\Models\PlatformIntegration
├── ➡️  WebhookLog               → Engagement\Models\WebhookLog
├── ➡️  AutomationFlow           → Engagement\Models\AutomationFlow
├── ➡️  AutomationRun            → Engagement\Models\AutomationRun
├── ➡️  ConversionGoal           → Engagement\Models\ConversionGoal
├── ➡️  AuditLog                 → Engagement\Models\AuditLog
├── 🔧 WidgetSession             → SE QUEDA (versión chat-only) +
│                                  COPIA en Engagement\Models\VisitorSession
├── ✅ WidgetPageView            → SE QUEDA
└── ✅ Channels\Web              → SE QUEDA
```

### 3.3 Servicios a mover

```
modules/HelpdeskLivechat/app/Services/
├── ➡️  ScoringService           → Engagement
├── ➡️  TrackingIngestService    → Engagement
├── ➡️  TriggerEvaluator         → Engagement
├── ➡️  RecommenderService       → Engagement
├── ➡️  AutomationEngine         → Engagement
├── ➡️  ConversionMatcher        → Engagement
├── ➡️  VariantAssigner          → Engagement
├── ➡️  PlatformWebhookHandler   → Engagement
├── ✅ SessionLinkService        → SE QUEDA (versión chat) +
│                                  COPIA en Engagement (versión genérica)
├── ✅ Widget\WidgetConversationService  → SE QUEDA
└── ✅ WidgetSessionService      → SE QUEDA
```

### 3.4 Controllers a mover

```
Sdk/* (todos)                 → Engagement\Http\Controllers\Api\Sdk\
├── InitController             → Engagement
├── IdentifyController         → Engagement
├── TrackController            → Engagement
├── ContextController          → Engagement
├── TriggerController          → Engagement
├── PersonalizationController  → Engagement
├── RecommendationController   → Engagement
├── CatalogController          → Engagement
└── PlatformWebhookController  → Engagement

Settings/
├── ➡️  TriggerRuleController   → Engagement
├── ➡️  PersonalizationRuleController → Engagement
├── ➡️  PlatformIntegrationController → Engagement
├── ➡️  AutomationFlowController → Engagement
├── ➡️  ConversionGoalController → Engagement
├── ➡️  WebhookLogController    → Engagement
├── ➡️  AuditLogController      → Engagement
├── ✅ LivechatSettingsController → SE QUEDA
└── ✅ PreChatFormsController   → SE QUEDA

Managers/
├── ➡️  AnalyticsController     → Engagement
├── ➡️  ExportController        → Engagement
├── ➡️  CustomerProfileController → Engagement (engagement-specific data) +
│                                   STUB en Livechat (chat history)
└── ✅ LiveVisitorsController   → SE QUEDA (depende de WidgetSession)

Pages/
└── ✅ Widget*                   → SE QUEDAN

Api/
└── ✅ WidgetConversationController → SE QUEDA
```

### 3.5 Jobs / Events / Notifications

```
Jobs/
├── ➡️  ProcessLivechatBatchJob → Engagement (renombrar a ProcessEventBatchJob)
├── ➡️  ProcessWebhookJob       → Engagement
└── ➡️  RecalculateScoreJob     → Engagement

Events/
├── ➡️  ScoreThresholdCrossed   → Engagement
└── ➡️  TriggerFired            → Engagement

Notifications/
└── ➡️  IntegrationHealthAlert  → Engagement

Console/Commands/
└── ➡️  CheckIntegrationHealthCommand → Engagement
```

### 3.6 SDK + distribuciones

**Todo se mueve íntegro:**
```
modules/HelpdeskLivechat/resources/assets/sdk/        → modules/Engagement/resources/assets/sdk/
modules/HelpdeskLivechat/resources/assets/sdk-worker/ → modules/Engagement/resources/assets/sdk-worker/
modules/HelpdeskLivechat/distributions/               → modules/Engagement/distributions/
```

### 3.7 Rutas

```
modules/HelpdeskLivechat/routes/
├── ✅ widget.php (queda con conversation/helpcenter routes; quitar SDK)
├── ✅ web-widget.php
├── ✅ channels.php
├── managers.php (split: live-visitors queda; analytics + export se mueven)
└── settings.php (split: livechat + pre-chat-forms quedan; resto se mueve)

modules/Engagement/routes/
├── 🆕 sdk.php          (todos los endpoints SDK + webhook)
├── 🆕 settings.php     (triggers, personalizations, platforms, automation, goals, webhook-logs, audit-logs)
├── 🆕 managers.php     (analytics, export, customer-profile)
└── 🆕 channels.php     (broadcast widget-session.{token})
```

**URLs:**
- `/hd/api/sdk/*` → `/eng/api/sdk/*` (limpio para futuro)
- `/panel/settings/helpdesk/livechat/triggers/*` → `/panel/settings/engagement/triggers/*`
- `/panel/helpdesk/livechat/analytics` → `/panel/engagement/analytics`

> **Compatibilidad**: dejar redirects 301 desde URLs antiguas hacia las nuevas durante 1 release.

### 3.8 Permisos (renombre)

| Actual | Nuevo |
|--------|-------|
| `helpdesk.livechat.events.view` | `engagement.events.view` |
| `helpdesk.livechat.scores.view` | `engagement.scores.view` |
| `helpdesk.livechat.triggers.*` | `engagement.triggers.*` |
| `helpdesk.livechat.personalizations.*` | `engagement.personalizations.*` |
| `helpdesk.livechat.platforms.*` | `engagement.platforms.*` |
| `helpdesk.livechat.automation.*` | `engagement.automation.*` |
| `helpdesk.livechat.goals.*` | `engagement.goals.*` |
| `helpdesk.livechat.manage` | `engagement.manage` |

**Quedan en livechat:**
| Actual | Sin cambios |
|--------|-------------|
| `helpdesk.livechat.settings.{view,update}` | (configuración del widget) |

**Migración**: seeder migrará `helpdesk.livechat.X.Y` existentes a sus equivalentes `engagement.X.Y` preservando asignaciones a roles.

### 3.9 Vistas Blade

```
resources/views/settings/livechat/
├── index.blade.php                    ✅ se queda (config del widget)
├── triggers.blade.php                 ➡️  Engagement
├── personalizations.blade.php         ➡️  Engagement
├── platforms.blade.php                ➡️  Engagement
├── automation.blade.php               ➡️  Engagement
├── goals.blade.php                    ➡️  Engagement
├── webhook-logs.blade.php             ➡️  Engagement
└── audit-logs.blade.php               ➡️  Engagement

resources/views/managers/
├── live-visitors/                     ✅ se queda
├── analytics/                         ➡️  Engagement
└── customer-profile/                  ➡️  Engagement
```

### 3.10 Tests

```
tests/Feature/
├── ✅ WidgetConversationSecurityTest  (queda)
├── ✅ WidgetSpaTest                   (queda)
├── ✅ InboxChannelGateTest            (queda)
├── ➡️  SdkEndpointsTest               → Engagement
├── ➡️  PlatformWebhookTest            → Engagement
└── ➡️  Services/*                     → Engagement
```

---

## 4. Conexión entre módulos

### 4.1 Dependencia opcional

`modules/HelpdeskLivechat/module.json`:
```json
{
  "name": "HelpdeskLivechat",
  "requires": ["Helpdesk"],
  "optional": ["Engagement"]
}
```

`modules/Remarketing/module.json` (futuro):
```json
{
  "name": "Remarketing",
  "requires": ["Engagement"]
}
```

### 4.2 Detección runtime en Livechat

`HelpdeskLivechatServiceProvider::boot()`:
```php
if (Module::find('Engagement')?->isEnabled()) {
    $this->registerEngagementBridge();
}
```

`registerEngagementBridge()`:
- Inyecta `<script src="/build-engagement/sdk.js">` cuando se renderiza el widget
- Pasa el `widget-session-token` al SDK para enlazar con `engagement_visitor_sessions`
- Escucha `Engagement\Events\TriggerFired` con action `open_chat` → llama a `chat.open()`

Si Engagement NO está activo: el widget funciona en modo standalone, sin tracking.

### 4.3 Trigger `open_chat` action

Engagement mantiene la acción genérica `open_chat`. Livechat la consume vía:

```js
// SDK fires event
chat.on('trigger:fired', (e) => {
    if (e.action.type === 'open_chat' && window.HelpdeskWidget) {
        window.HelpdeskWidget.open();
    }
});
```

---

## 5. Plan de ejecución (orden importa)

### Fase 1 — Preparación (sin cambios destructivos, ~30 min)
1. Crear estructura `modules/Engagement/` con `module.json`, ServiceProvider vacío, composer.json autoloader
2. Registrar Engagement en `modules_statuses.json` con `enabled: false`
3. `composer dump-autoload`

### Fase 2 — Mover schema (con renames preservadores, ~45 min)
4. Crear migraciones en Engagement con `Schema::rename('helpdesk_livechat_X', 'engagement_X')` por cada tabla
5. Las migraciones del módulo Livechat marcamos como `--pretend` (Laravel skip si la tabla ya existe en Engagement)
6. Las migraciones de **adición** de columnas (UTM, A/B, platform) se movieron también — declaradas en Engagement
7. **NO ejecutar migrations todavía** — esperar fase 5

### Fase 3 — Mover código PHP (3-4h)
8. Mover modelos uno a uno con namespace nuevo
9. Mover servicios — actualizar todos los `use` statements (~50 archivos)
10. Mover controllers — actualizar route file references
11. Mover jobs, events, notifications, comando
12. Renombrar clases: `LivechatEvent` → `Event`, `ProcessLivechatBatchJob` → `ProcessEventBatchJob`
13. Mover/copiar tests

### Fase 4 — Mover SDK + distribuciones (~30 min)
14. `git mv resources/assets/sdk/ → modules/Engagement/resources/assets/sdk/`
15. `git mv resources/assets/sdk-worker/`
16. `git mv distributions/`
17. Actualizar `vite.config.js` de Engagement (build a `public/build-engagement/`)
18. Actualizar las distribuciones para apuntar a `/build-engagement/sdk.js`

### Fase 5 — Cablear rutas y permisos (~45 min)
19. Crear `routes/sdk.php` `routes/settings.php` `routes/managers.php` `routes/channels.php` en Engagement
20. Eliminar/limpiar las correspondientes en Livechat
21. Crear `EngagementServiceProvider` con todo el load
22. Migrar permisos en seeder + script de migración de roles existentes
23. Actualizar NavService entries

### Fase 6 — Bridge Livechat ↔ Engagement (~30 min)
24. Implementar `registerEngagementBridge()` en LivechatServiceProvider
25. Añadir listener `chat.on('trigger:fired')` en widget React para `open_chat` action

### Fase 7 — Activar y verificar (~30 min)
26. `composer dump-autoload`
27. `php artisan optimize:clear`
28. `php artisan module:enable Engagement`
29. `php artisan module:migrate Engagement` ← aquí ocurren los renames de tablas
30. `php artisan module:seed Engagement --class=EngagementPermissionsSeeder`
31. `cd modules/Engagement && npm install && npm run build`
32. Tests: `php artisan test --filter=Engagement` + `--filter=HelpdeskLivechat`

### Fase 8 — Compatibilidad (opcional, ~15 min)
33. Añadir redirects 301 de URLs antiguas → nuevas en `routes/web.php` de Livechat

---

## 6. Riesgos y mitigaciones

| Riesgo | Mitigación |
|--------|------------|
| Permisos asignados a roles existentes se pierden con el rename | Seeder con `Permission::where('name', 'helpdesk.livechat.X')->update(['name' => 'engagement.X'])` (preserva role_has_permissions) |
| Webhooks de plataformas en producción dejan de funcionar (URL cambia) | Mantener route alias `/hd/api/sdk/webhook/*` redirigiendo a `/eng/api/sdk/webhook/*` durante 1 mes |
| SDK ya cargado en sitios de clientes apunta a `/hd/api/sdk/*` | Mantener route alias durante 1 mes; clientes deben actualizar distribución |
| Datos en tablas se pierden con migrate | `Schema::rename` preserva todo; añadir test en CI |
| `use` statements rotos en código que dependía de los movidos | grep masivo + IDE refactor + tests pasan |
| Build de Vite tiene 2 manifiestos ahora | Consolidar en `public/build-engagement/` y `public/build-helpdesklivechat/` separados |

---

## 7. Validación post-split

### Smoke tests manuales
- [ ] Cargar widget en una web de prueba sin Engagement activo → solo abre chat, no trackea
- [ ] Activar Engagement → SDK aparece, trackea eventos, score se calcula
- [ ] Crear trigger `open_chat` en Engagement → al cumplirse, abre el widget
- [ ] Webhook PrestaShop → llega a `/eng/api/sdk/webhook/prestashop/{id}` → procesado
- [ ] URL legacy `/hd/api/sdk/init` → redirige a `/eng/api/sdk/init`
- [ ] Roles existentes con `helpdesk.livechat.triggers.view` ven la nueva sección de Engagement

### Tests automáticos
- [ ] Todos los tests Feature pasan en HelpdeskLivechat
- [ ] Todos los tests Feature pasan en Engagement
- [ ] Tests E2E Playwright pasan

### Performance
- [ ] Widget standalone (sin Engagement) carga `<150KB`
- [ ] SDK Engagement carga `<80KB`

---

## 8. Rollback

Si algo va mal en producción:
1. `php artisan module:disable Engagement`
2. Revertir migraciones de rename: `Schema::rename('engagement_X', 'helpdesk_livechat_X')`
3. `git revert` del PR del split
4. `composer dump-autoload`

---

## 9. Estimación final

| Fase | Tiempo |
|------|--------|
| 1 — Preparación | 30 min |
| 2 — Schema rename | 45 min |
| 3 — Código PHP (50+ archivos) | 3 h |
| 4 — SDK + distribuciones | 30 min |
| 5 — Rutas + permisos | 45 min |
| 6 — Bridge Livechat-Engagement | 30 min |
| 7 — Activar + verificar | 30 min |
| 8 — Compatibilidad URLs | 15 min |
| **Total estimado** | **~6.5 horas** |

---

## 10. Decisiones a confirmar antes de ejecutar

- [ ] **Renombre de tablas**: ¿`engagement_*` o prefijo distinto (ej. `eng_*`)?
- [ ] **WidgetSession split**: ¿queda en Livechat con copia en Engagement, o se mueve completo a Engagement?
- [ ] **`LivechatEvent` rename**: ¿`Engagement\Models\Event` o conservar `LivechatEvent`?
- [ ] **URLs SDK**: ¿`/eng/api/sdk/*` o mantener `/hd/api/sdk/*` por compatibilidad para siempre?
- [ ] **Permisos**: ¿prefijo `engagement.*` o `eng.*`?
- [ ] **Compatibilidad legacy**: ¿1 mes, 6 meses, indefinido?
- [ ] **Connection BD**: ¿Engagement usa `helpdesk` también o `engagement` connection nueva?

---

## 11. Estado actual (snapshot)

```
HelpdeskLivechat:
  20 migraciones · 16 modelos · 10 servicios · 27 controllers
  17 vistas Blade · 11 tests · 32 archivos SDK TS
  12 archivos distribuciones
```

```
HelpdeskLivechat (después del split):
  6 migraciones · 4 modelos · 3 servicios · 8 controllers
  4 vistas Blade · 3 tests
  (widget React queda intacto)

Engagement (nuevo):
  14 migraciones · 13 modelos · 8 servicios · 21 controllers
  13 vistas Blade · 8 tests · 32 archivos SDK TS
  12 archivos distribuciones · 1 trait · 1 notification · 1 command
```

---

## 12. Pasos siguientes

Espero tu confirmación sobre las **7 decisiones del punto 10**. Una vez confirmadas, ejecuto en el orden de la sección 5.

Sugerencias por defecto si quieres que decida yo:

| Decisión | Default propuesto |
|----------|-------------------|
| Prefijo tablas | `engagement_*` (legible y claro) |
| WidgetSession | Mover completo a Engagement (`engagement_visitor_sessions`); Livechat la lee vía relación |
| LivechatEvent rename | `Engagement\Models\Event` (más limpio para futuro) |
| URLs SDK | `/eng/api/sdk/*` como canónica + redirect 301 de `/hd/api/sdk/*` durante 6 meses |
| Permisos | `engagement.*` (claro y legible) |
| Compatibilidad | 6 meses redirects, luego eliminar |
| Connection BD | Engagement usa connection `helpdesk` también (mismo servidor, no añadir complejidad) |
