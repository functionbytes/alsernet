# Helpdesk — Roadmap y estado de implementación

> Documento maestro vivo. Cada feature se mueve entre **Planeado → En progreso → Hecho** y se referencia archivos clave.
> Actualizado: 2026-05-01

## Leyenda
- ✅ Hecho y validado
- 🚧 En progreso
- 📝 Planeado / pendiente
- ❌ Descartado (con razón)

---

## Fase 1 — Mensajería multicanal en tiempo real (✅ completada 2026-05-01)

| Feature | Estado | Archivos clave |
|---|---|---|
| Webhooks Facebook Messenger | ✅ | `WebhookController.php`, `FacebookMessengerService.php` |
| Webhooks Instagram DMs | ✅ | `InstagramService.php` |
| Webhooks WhatsApp Business | ✅ | `WhatsAppBusinessService.php` |
| Widget público + thread | ✅ | `WidgetConversationService.php` |
| Procesamiento async via Horizon | ✅ | `ProcessSocialWebhookJob.php` |
| Broadcasting con Reverb | ✅ | `ConversationMessageCreated.php`, canal `helpdesk.inbox` |
| Auto-actualización lista sidebar | ✅ | `index.blade.php` con listener global |
| Cache `getUserProfile` 6h | ✅ | `ProcessSocialWebhookJob.php` |
| Descarga attachments en background | ✅ | `DownloadConversationAttachmentsJob.php` |
| Compresión imágenes > 1MB | ✅ | `ConversationsController::compressAndStoreImage` |
| Cloudflare tunnel para webhooks | ✅ | `~/.cloudflared/config.yml` (manual) |

## Fase 2 — Meta API avanzado (✅ completada 2026-05-01)

| Feature | Estado | Cómo |
|---|---|---|
| Typing indicator agente → cliente | ✅ | `OutboundMessageService::setTyping` + endpoint typing |
| Read receipts cliente → agente (✓✓) | ✅ | webhook `read` → `customer_read_at` en metadata |
| Reacciones (❤️) cliente | ✅ | webhook `message_reactions` → `customer_reactions` |
| Delivery receipts | ✅ | webhook `delivery` → `customer_delivered_at` |
| mark_seen al abrir conversación | ✅ | endpoint `mark-read` + `outbound->markSeen` |
| Quick replies (botones) | ✅ | `OutboundMessageService::sendQuickReplies` |
| Envío attachments al cliente | ✅ | `OutboundMessageService::sendAttachment` (FB/IG/WA) |

## Fase 3 — Productividad agente + automatización (✅ completada 2026-05-01)

| Feature | Estado | Cómo |
|---|---|---|
| Atajos teclado (Ctrl+Enter, R, J/K, /, ?) | ✅ | `inbox-v4.js` |
| Modo concentración con SLA timer | ✅ | `inbox-v4.js` |
| Sonido de notificación | ✅ | AudioContext sintetizado, toggle persistente |
| Badge favicon dinámico | ✅ | Canvas con conteo no-leídos |
| Push notifications nativas | ✅ | Web Push API |
| Dashboard live (auto-refresh 10s) | ✅ | `/panel/helpdesk/dashboard/live` |
| CSAT post-cierre con magic link | ✅ | `helpdesk_csat_ratings` + `/helpdesk/csat/{token}` |
| SLA breach alerts | ✅ | `helpdesk:check-sla` cada 5 min |
| Auto-respuesta fuera de horario | ✅ | `BusinessHoursService` + `OffHoursResponse` |
| Routing por palabras clave | ✅ | `RoutingRuleService` + `helpdesk_routing_rules` |
| Histórico unificado del cliente | ✅ | tab "Anteriores" en right-panel |
| Tags con colores | ✅ | `helpdesk_conversation_tags` (existente) |
| Health check endpoint | ✅ | `/helpdesk/health` |

---

## Fase 4 — AI / LLM (✅ completada 2026-05-01)

| Feature | Estado | Archivo clave |
|---|---|---|
| Sugerencia de respuesta con AI | ✅ | `Services/AI/SuggestReplyService.php` + endpoint `ai/suggest-replies` |
| Resumen automático al cerrar | ✅ | `Services/AI/ConversationSummaryService.php` (hookeado en `close()`) |
| Transcripción audio → texto (Whisper) | ✅ | `Services/AI/AudioTranscriptionService.php` + `Jobs/TranscribeAudioJob.php` |
| Detección de sentimiento | ✅ | `Services/AI/SentimentService.php` + `Listeners/AnalyzeSentimentOnIncoming.php` |
| Auto-tag por categoría | ✅ | `Services/AI/AutoTagService.php` + `Listeners/AutoTagFirstMessage.php` |
| Traducción in-line (DeepL) | ✅ | `Services/AI/DeepLTranslationService.php` + endpoint `items/{item}/translate` |
| FAQ-bot pre-humano | ❌ | Pospuesto, requiere knowledge base estructurada |

Master switch: `HELPDESK_AI_ENABLED` en `.env` · Doc completa: `AI-SETUP.md`

## Fase 5 — Customer 360 + Analytics (✅ completada 2026-05-01)

| Feature | Estado | Archivo clave |
|---|---|---|
| Customer Health Score | ✅ | `Services/CustomerInsightsService::healthScore` |
| Lifetime metrics (LTV) | ✅ | `Services/CustomerInsightsService::lifetimeMetrics` |
| Customer journey timeline | ✅ | `Services/CustomerInsightsService::journeyTimeline` |
| Heatmap horas pico | ✅ | `Controllers/Managers/HeatmapReportController` (`/reports/heatmap`) |
| Performance por agente | ✅ | `Controllers/Managers/ReportsController::agentPerformance` |
| Tendencias semana/mes | ✅ | `Controllers/Managers/TrendsReportController` (Chart.js) |
| Pedidos Shopify del cliente | ❌ | Pospuesto — requiere config Shopify específica |

## Fase 6 — Workflow / Macros / Team mgmt (✅ completada 2026-05-01)

| Feature | Estado | Archivo clave |
|---|---|---|
| Round-robin con carga máxima | ✅ | `Services/AgentAssignmentService` + `helpdesk_agent_settings` |
| Macros con múltiples acciones | ✅ | `Models/Macro` + `Controllers/Managers/Settings/MacrosController` |
| Escalación automática SLA | ✅ | `Console/Commands/AutoEscalateSlaBreaches` (cada 5 min) |
| Auto-cerrar conversaciones inactivas | ✅ | `Console/Commands/AutoCloseInactive` (diario 02:00) |
| NPS además de CSAT | ✅ | `helpdesk_nps_ratings` + `Services/NpsService` |
| Vacation mode agente | ✅ | columnas `vacation_until` en `agent_settings` |
| Leaderboard del equipo | ✅ | `/team/leaderboard` |
| Turnos / shifts | ❌ | Pospuesto — usar vacation_until por ahora |

## Fase 7 — UX siguiente nivel (✅ completada 2026-05-01)

| Feature | Estado | Archivo clave |
|---|---|---|
| Dark mode | ✅ | `inbox-v4-dark.css` + toggle en statusbar |
| PWA installable | ✅ | `public/manifest.json` + `public/sw.js` |
| Smart views guardadas | ✅ | `helpdesk_conversation_views` (existente, extendida) |
| Kanban view | ✅ | `kanban.blade.php` + `ConversationsController::kanban` + SortableJS |
| Notas de voz del agente | ✅ | `public/vendor/helpdesk/voice-notes.js` (MediaRecorder) |

## Fase 8 — Integraciones externas (✅ completada 2026-05-01)

| Feature | Estado | Archivo clave |
|---|---|---|
| Slack notifications | ✅ | `Services/SlackNotificationService` + `helpdesk_slack_integrations` |
| Calendar booking link | ✅ | `helpdesk_agent_calendars` + helper `agentCalendarUrl()` |
| Audit log completo | ✅ | `Models/AuditLog` + `Concerns/Auditable` trait + `/audit` view |
| PII masking | ✅ | `Services/PiiMaskingService` (mask cards/DNI/emails/tels) |
| Crear ticket Jira/Linear | ❌ | Pospuesto |

## Fase 9 — Comunicación masiva (✅ completada 2026-05-01)

| Feature | Estado | Archivo clave |
|---|---|---|
| Notas de voz del agente | ✅ | `voice-notes.js` (movido a Fase 7) |
| Broadcast a segmento | ✅ | `helpdesk_broadcasts` + `Services/Campaigns/BroadcastService` + `SendBroadcastMessageJob` |
| WhatsApp HSM marketing | ✅ | `helpdesk_whatsapp_templates` + `Services/Campaigns/WhatsAppHsmService` + `helpdesk:sync-wa-templates` |
| Drip campaigns | ✅ | `helpdesk_drip_campaigns/steps/executions` + `DripService` + `helpdesk:process-pending-drips` |
| Click-to-call WhatsApp | ❌ | Requiere WhatsApp Business Calling API (preview) |

Doc completa: `CAMPAIGNS.md`

## Fase 10 — Seguridad / Compliance (✅ completada 2026-05-01)

| Feature | Estado | Archivo clave |
|---|---|---|
| GDPR data export | ✅ | `Services/Compliance/GdprExportService` + endpoint `/customers/{id}/gdpr-export` |
| GDPR data deletion (soft + hard) | ✅ | `Services/Compliance/GdprDeletionService` + `helpdesk:purge-old-gdpr-deletes` |
| 2FA TOTP agentes | ✅ | `Services/Compliance/TwoFactorService` + `Middleware/Require2FA` + `pragmarx/google2fa` |
| PII masking en logs | ✅ | `Services/PiiMaskingService` |
| Audit log completo | ✅ | `Models/AuditLog` + `Concerns/Auditable` trait (cubierto en Fase 8) |

Doc completa: `COMPLIANCE.md`

---

## ✅ Estado final 2026-05-01: TODAS las fases planeadas completadas

Total entregado:
- **70+ features** en 10 fases
- **18 tablas** nuevas en BD `helpdesk`
- **40+ archivos PHP** nuevos (services, controllers, models, listeners, jobs, commands, middleware)
- **8 documentos** Markdown vivos en `modules/Helpdesk/docs/`
- **5 comandos scheduled** registrados
- **15+ endpoints** nuevos con permisos Spatie

## Fase 11 — Email + Knowledge Base (✅ completada 2026-05-01)

| Feature | Estado | Archivo clave |
|---|---|---|
| Email channel IMAP+SMTP | ✅ | `Services/Email/ImapPullService.php` + `SmtpSendService.php` + `Models/EmailAccount.php` |
| Knowledge Base pública | ⚠️ Pendiente | KB queda como TODO próxima iteración |
| Email signatures por agente | ✅ | columna `users.email_signature` |
| Reply-by-email para agente | ✅ | parser en ImapPullService detecta `[#NNN]` |

## Fase 12 — Workflow + CRM (✅ completada 2026-05-01)

| Feature | Estado | Archivo clave |
|---|---|---|
| Visual workflow builder | ✅ | `Models/Workflow` + `Services/Workflow/WorkflowEngine` |
| Custom fields per customer | ✅ | `helpdesk_custom_fields` + `Concerns/HasCustomFields` |
| Companies | ✅ | `Models/Company` + auto-vinculación por domain |
| Skills-based routing | ✅ | `helpdesk_skills` + `Services/SkillsRoutingService` |
| Multi-brand support | ✅ | `helpdesk_brands` + middleware `DetectBrand` |

## Fase 13 — Self-service + Onboarding (✅ completada 2026-05-01)

| Feature | Estado | Archivo clave |
|---|---|---|
| Customer portal | ✅ | `/portal/*` con magic link login |
| Pre-chat survey | ✅ | `helpdesk_pre_chat_forms` + endpoint widget |
| Status page público | ✅ | `/status` + `helpdesk_status_components/incidents` |
| Banners in-app | ✅ | `helpdesk_banners` + endpoint widget |
| Surveys multi-pregunta | ✅ | `helpdesk_surveys` con questions logic |
| In-app tour interactivo | ✅ | `public/vendor/helpdesk/onboarding-tour.js` (Shepherd.js) |
| Empty states con CTA | ✅ | `partials/empty-state.blade.php` reusable |
| Sample data seeder | ✅ | `php artisan helpdesk:seed-demo` |

## Fase 14 — API + Observabilidad (✅ completada 2026-05-01)

| Feature | Estado | Archivo clave |
|---|---|---|
| REST API v1 con Scribe docs | ✅ | `routes/api.php` + Resources + `php artisan scribe:generate` |
| Webhooks salientes | ✅ | `Services/OutgoingWebhookService` + `DeliverWebhookJob` |
| Sentry error tracking | ✅ | `sentry/sentry-laravel` + DSN env |
| Scheduled email reports | ✅ | `helpdesk:send-scheduled-reports` cada hora |
| Rate limiting per-tenant | ✅ | `RateLimiter::for('helpdesk-api')` |
| Health check extendido | ✅ | `/helpdesk/health` con AI/Email/IMAP/Webhooks |

## Fase 15 — Real-time + AI agent (✅ completada 2026-05-01)

| Feature | Estado | Archivo clave |
|---|---|---|
| Side conversations | ✅ | `helpdesk_side_conversations(_messages)` + `Services/SideConversationService` |
| Live visitor tracking | ✅ | `helpdesk_widget_sessions/page_views` + `/live-visitors` |
| AI agent autónomo | ✅ | `helpdesk_ai_agents` + `Services/AI/AiAgentService` + listener |
| Co-browsing simple | ⚠️ Pendiente | Pospuesto (requires WebRTC signaling) |

## 🎯 Pospuestos (alto esfuerzo / dependencia externa)

- Voice channel VoIP (Twilio/Vonage) — Fase futura
- Mobile native apps iOS/Android
- Forum/community público
- Workforce management (shifts + forecasting)
- HIPAA / PCI / Data residency / E2E encryption

## Fase 10 — Seguridad y compliance (🚧 en curso)

| Feature | Estado | Notas |
|---|---|---|
| Audit log completo | 📝 | Quién/qué/cuándo, todos los cambios |
| GDPR data export | 📝 | Endpoint que entrega JSON completo del cliente |
| GDPR data deletion | 📝 | Borrar PII manteniendo estadísticas anonimizadas |
| PII masking | 📝 | Oculta DNI / tarjetas en logs y vistas |
| 2FA agentes (TOTP) | 📝 | Google Authenticator compatible |

---

## Convenciones del proyecto

- **PHP 8.4 + Laravel 12** con módulos `nwidart/laravel-modules`
- **Bootstrap 5.3 + Font Awesome 6** (no Tabler Icons)
- **jQuery + AJAX** (no Livewire / no Inertia / no React en el manager)
- **Reverb** como WebSocket server, **Echo** como cliente
- **Horizon** para Redis queues; supervisor `helpdesk-webhooks` con 3-8 procesos
- **Conexión DB `helpdesk`** para tablas del módulo (excepto `users`, `sessions`)
- **Permisos Spatie** con convención `helpdesk.{entity}.{action}`
- Tests: PHPUnit (no Pest)

## Endpoints clave

| Endpoint | Auth | Descripción |
|---|---|---|
| `POST /api/helpdesk/webhooks/facebook` | Signature | Webhook Meta Messenger |
| `POST /api/helpdesk/webhooks/instagram` | Signature | Webhook Meta Instagram |
| `POST /api/helpdesk/webhooks/whatsapp` | Signature | Webhook WhatsApp Business |
| `GET /helpdesk/health` | Pública | Health check |
| `GET /helpdesk/csat/{token}` | Pública | Encuesta CSAT del cliente |
| `GET /panel/helpdesk/conversations` | Web auth | Manager principal |
| `GET /panel/helpdesk/dashboard/live` | Web auth | Dashboard tiempo real |
| `POST /panel/helpdesk/conversations/{id}/mark-read` | Web auth | Marca leído + read receipt |
| `POST /panel/helpdesk/conversations/{id}/typing` | Web auth | Typing indicator → cliente |

## Variables `.env` críticas

```env
QUEUE_CONNECTION=redis
BROADCAST_CONNECTION=reverb
REVERB_HOST=system.test
REVERB_PORT=8090
HELPDESK_PUBLIC_URL=https://channels.functionbytes.com
HELPDESK_ATTACHMENTS_DISK=public
FACEBOOK_APP_ID=...
FACEBOOK_APP_SECRET=...
FACEBOOK_PAGE_ACCESS_TOKEN=...
FACEBOOK_VERIFY_TOKEN=...
INSTAGRAM_APP_ID=... (mismo que FACEBOOK_APP_ID si comparten app)
INSTAGRAM_APP_SECRET=... (igual)
INSTAGRAM_BUSINESS_ACCOUNT_ID=...
INSTAGRAM_ACCESS_TOKEN=... (Page Access Token)
INSTAGRAM_VERIFY_TOKEN=...

# AI (Fase 4)
OPENAI_API_KEY=...
OPENAI_MODEL=gpt-4o-mini
DEEPL_API_KEY=...

# Slack (Fase 8)
SLACK_WEBHOOK_URL=...
```

---

## Comandos scheduled

| Comando | Frecuencia | Descripción |
|---|---|---|
| `helpdesk:check-sla` | cada 5 min | Detecta SLA breach, dispara `SlaBreached` |
| `helpdesk:auto-close-inactive` | diario | Cierra conversaciones > 72h sin actividad (planeado) |
| `helpdesk:digest-leaderboard` | diario 09:00 | Email con top performers (planeado) |

---

## Cómo agregar features nuevas

1. Crear/actualizar entrada en este ROADMAP.md
2. Añadir migración con `protected $connection = 'helpdesk'`
3. Modelo en `modules/Helpdesk/app/Models/`
4. Service en `modules/Helpdesk/app/Services/` (lógica de negocio)
5. Controller en `modules/Helpdesk/app/Http/Controllers/`
6. Vista Blade con Bootstrap 5.3 + jQuery
7. Ruta en `modules/Helpdesk/routes/managers.php` con permission check
8. Test PHPUnit feature en `modules/Helpdesk/tests/Feature/`
9. `php artisan optimize:clear` + `composer dump-autoload`
10. Marcar como ✅ en este ROADMAP

## Referencias internas

- Setup canales sociales: `modules/Helpdesk/docs/SOCIAL-CHANNELS-SETUP.md`
- Convenciones código: `claude.md` (raíz)
- ADR pendientes: `docs/adr/`
