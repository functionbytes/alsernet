# Plan de Mejoras — Módulo Engagement

> Documento generado a partir del análisis de código estático, arquitectura y dependencias.
> Dividido por categorías: Arquitectura, Seguridad, Rendimiento, UX/UI, DX (Developer Experience), y Features.

---

## 1. Arquitectura y Dependencias

### 1.1 Desacoplar `HelpdeskLivechat\Models\Channels\Web`
**Problema**: El middleware `EnsureWebsiteToken` y 12 archivos de tests dependen de `Modules\HelpdeskLivechat\Models\Channels\Web`. Esto crea un acoplamiento fuerte entre Engagement y HelpdeskLivechat.

**Mejora**: Crear un modelo propio `Modules\Engagement\Models\WebsiteChannel` (o similar) que gestione sus propios tokens y relación con Inbox, o mover la lógica de autenticación a un servicio compartido en `Helpdesk`.

**Impacto**: Alto. Permite que Engagement funcione independientemente de HelpdeskLivechat.

### 1.2 Unificar namespace de permisos
**Problema**: Controladores de Settings usan `can:helpdesk.livechat.*` pero el seeder migra a `engagement.*`. Esto es inconsistente.

**Mejora**: Migrar TODOS los controladores y Form Requests a `engagement.*`. Eliminar referencias a `helpdesk.livechat.*` del módulo Engagement.

**Impacto**: Alto. Bloquea acceso a usuarios con permisos nuevos.

### 1.3 Consolidar rutas de "Volver"
**Problema**: 6 vistas Blade apuntan a `route('settings.helpdesk-livechat.index')` en vez de una ruta propia de Engagement.

**Mejora**: Crear una ruta índice de Engagement (`settings.engagement.index`) que actúe como landing page del módulo, o apuntar cada "Volver" a la sección anterior lógica (ej: desde webhook-logs volver a platforms).

**Impacto**: Medio. Mejora UX pero no es bloqueante.

### 1.4 Separar concerns en `EnsureWebsiteToken`
**Problema**: El middleware hace demasiado: valida token, busca canal, verifica inbox activo, y setea atributos con nombres legacy (`livechat_channel`, `livechat_inbox`).

**Mejora**: Renombrar atributos a `engagement_channel` / `engagement_inbox` (o generic `website_channel` / `website_inbox`). Extraer la búsqueda del canal a un service/repository.

**Impacto**: Medio. Limpieza de código y preparación para futuro desacoplamiento.

### 1.5 Revisar migraciones de ENUM
**Problema**: La migración `2026_05_06_000001_add_erp_to_platform_integrations_enum.php` tiene un nombre cronológicamente anterior a la base (`000003_create_engagement_tables_if_missing.php`). En instalaciones frescas el orden depende del timestamp, lo cual es frágil.

**Mejora**: Incluir `'erp'` directamente en el ENUM de la migración base, o hacer que la migración de adición sea idempotente (verificar si ya existe antes de modificar).

**Impacto**: Medio. Puede causar errores en `migrate:fresh`.

---

## 2. Modelos y Base de Datos

### 2.1 Completar `$fillable` en `TriggerRule`
**Problema**: Faltan `variant_group` y `variant_weight` en `$fillable` a pesar de que existen en la tabla y el `VariantAssigner` los usa.

**Mejora**: Agregar ambos campos a `$fillable`.

**Impacto**: Alto. A/B testing no funciona con asignación masiva.

### 2.2 Añadir índices compuestos faltantes
**Problema**: Tablas como `engagement_events`, `engagement_visitor_sessions`, `engagement_visitor_scores` probablemente hacen lookups frecuentes por `inbox_id + created_at`, `session_token`, `customer_id`.

**Mejora**: Revisar queries frecuentes (Analytics, LiveVisitors, Scoring) y añadir índices compuestos:
```sql
-- engagement_events
CREATE INDEX idx_events_inbox_type_created ON engagement_events(inbox_id, event_type, created_at);
CREATE INDEX idx_events_session_created ON engagement_events(session_id, created_at);

-- engagement_visitor_sessions
CREATE INDEX idx_sessions_active ON engagement_visitor_sessions(updated_at, is_active);
CREATE INDEX idx_sessions_token ON engagement_visitor_sessions(session_token);

-- engagement_visitor_scores
CREATE INDEX idx_scores_customer_inbox ON engagement_visitor_scores(customer_id, inbox_id);
```

**Impacto**: Alto. Mejora drástica en analytics y live visitors.

### 2.3 Considerar particionamiento de `engagement_events`
**Problema**: La tabla de eventos puede crecer rápidamente (millones de filas en sitios con tráfico alto).

**Mejora**: Particionar por `created_at` (mensual) o usar una estrategia de archivado a tabla fría (`engagement_events_archive`).

**Impacto**: Alto a largo plazo. Previene degradación de queries.

### 2.4 Soft deletes en entidades clave
**Problema**: No hay `SoftDeletes` en `TriggerRule`, `PersonalizationRule`, `AutomationFlow`, etc. Eliminar accidentalmente una regla activa borra datos históricos.

**Mejora**: Añadir `use SoftDeletes` a entidades de configuración y migrar tablas para agregar `deleted_at`.

**Impacto**: Medio. Protección contra errores humanos.

### 2.5 Validación de JSON en base de datos
**Problema**: Campos JSON (`conditions`, `action`, `nodes`, `edges`) no tienen validación a nivel de DB.

**Mejora**: Usar constraints de MariaDB/MySQL para validar estructura mínima, o validar en el modelo con `saving` event.

**Impacto**: Bajo. Defensa en profundidad.

---

## 3. Rendimiento

### 3.1 Cachear triggers y personalizaciones activas
**Problema**: Cada `sdk/init` hace queries a `trigger_rules` y `personalization_rules`.

**Mejora**: Cachear en Redis por `inbox_id` con TTL de 5 min. Invalidar al guardar/eliminar reglas.

```php
// En TriggerRuleController@store/update/destroy
Cache::tags(['engagement', "inbox:{$inboxId}"])->forget('triggers');
```

**Impacto**: Alto. Reduce latencia de init significativamente.

### 3.2 Cachear score del visitante
**Problema**: `ScoringService` recalcula score desde cero en cada evento.

**Mejora**: Cachear score por `session_token` con TTL de 1h. Invalidar solo cuando lleguen eventos relevantes.

**Impacto**: Medio. Reduce carga de CPU.

### 3.3 Eager loading en AnalyticsController
**Problema**: Posibles N+1 en relaciones de Event, Customer, Inbox.

**Mejora**: Auditar todos los queries con `Debugbar` o `Telescope` y agregar `with(['customer', 'inbox'])` donde sea necesario.

**Impacto**: Medio.

### 3.4 Paginación en ExportController
**Problema**: Exportación de eventos/scores podría cargar miles de modelos en memoria.

**Mejora**: Usar `cursor()` o `lazyById()` para streaming, y generar CSV en chunks.

**Impacto**: Alto para exportaciones grandes.

### 3.5 Batch size configurable en jobs
**Problema**: `ProcessEventBatchJob` y `ProcessWebhookJob` pueden tener batch sizes fijos no optimizados.

**Mejora**: Hacer configurable vía `config/engagement.php` o env var.

**Impacto**: Bajo-Medio.

---

## 4. Seguridad

### 4.1 Rate limiting por IP + token
**Problema**: El throttle actual es global por endpoint. Un atacante podría agotar el límite para todos los sitios.

**Mejora**: Aplicar throttle por combinación `IP + website_token` en endpoints SDK.

**Impacto**: Alto. Mitiga DoS y abuso de API.

### 4.2 Sanitización de selectores CSS en personalizaciones
**Problema**: `PersonalizationRule` permite guardar cualquier selector CSS que luego se inyecta en el DOM del cliente.

**Mejora**: Validar que los selectores no contengan `javascript:`, `expression()`, o scripts inline. Usar una whitelist de propiedades seguras.

**Impacto**: Alto. Previene XSS reflejado via DOM manipulation.

### 4.3 Rotación automática de secrets
**Problema**: `PlatformIntegration` genera secret una sola vez. No hay rotación automática.

**Mejora**: Añadir campo `secret_rotated_at` y notificar/administrar rotación periódica (90 días).

**Impacto**: Medio. Buena práctica de seguridad.

### 4.4 Audit log para cambios de configuración
**Problema**: `AuditLog` existe pero hay que verificar que capture TODOS los cambios (quién, qué, valores previo/nuevo).

**Mejora**: Usar el trait `RecordsAudit` en todos los modelos de settings. Asegurar que guarde diff completo.

**Impacto**: Medio. Cumplimiento y trazabilidad.

### 4.5 CSP headers para assets del SDK
**Problema**: Los bundles JS del SDK se sirven desde `/eng/api/assets/`. Podrían beneficiarse de headers CSP.

**Mejora**: Añadir `Content-Security-Policy` en respuestas del AssetProxyController.

**Impacto**: Bajo.

---

## 5. UX/UI

### 5.1 Landing page de Engagement
**Problema**: No existe una página índice del módulo. El usuario entra directamente a subsecciones.

**Mejora**: Crear `settings.engagement.index` con cards/links a cada subsección (triggers, platforms, goals, etc.) y KPIs rápidos.

**Impacto**: Medio. Mejora navegación.

### 5.2 Breadcrumbs consistentes
**Problema**: Las vistas no tienen breadcrumbs o apuntan al módulo equivocado.

**Mejora**: Implementar breadcrumbs en todas las vistas de settings y managers:
```
Inicio > Engagement > Triggers
Inicio > Engagement > Analytics
```

**Impacto**: Medio.

### 5.3 Empty states amigables
**Problema**: Tablas vacías probablemente muestran "No hay datos" genérico.

**Mejora**: Diseñar empty states con ilustración/icono, mensaje contextual y CTA (ej: "Crear tu primer trigger").

**Impacto**: Bajo-Medio.

### 5.4 Tooltips de ayuda en formularios complejos
**Problema**: Campos como `conditions` (JSON) y `action` (JSON) pueden ser confusos.

**Mejora**: Añadir tooltips, ejemplos precargados, o un builder visual de condiciones.

**Impacto**: Medio. Reduce curva de aprendizaje.

### 5.5 Dark mode en settings
**Problema**: El tema base soporta dark mode pero hay que verificar que las vistas de Engagement también lo soporten.

**Mejora**: Auditar clases CSS y asegurar `dark:` variants.

**Impacto**: Bajo.

---

## 6. Developer Experience (DX)

### 6.1 Documentación del SDK
**Problema**: No hay documentación clara de cómo integrar el SDK en un sitio cliente.

**Mejora**: Crear `docs/engagement/sdk-integration.md` con:
- Cómo incluir el script.
- Métodos disponibles (`eng.track`, `eng.identify`, `eng.context`).
- Ejemplos por plataforma (PrestaShop, Shopify, WooCommerce).
- Eventos recomendados (page_view, add_to_cart, purchase).

**Impacto**: Alto. Reduce soporte y facilita adopción.

### 6.2 Tests E2E con Playwright/Cypress
**Problema**: La carpeta `tests/E2E/specs/` está vacía.

**Mejora**: Escribir specs para:
- Flujo completo SDK (init → track → identify → trigger).
- CRUD de triggers en settings.
- Flujo de webhook (PrestaShop → webhook → evento → score).

**Impacto**: Alto. Previbe regresiones.

### 6.3 .gitignore en módulo
**Problema**: `modules/Engagement/node_modules/` y `.DS_Store` están versionados.

**Mejora**: Añadir `.gitignore` al módulo con `node_modules/`, `.DS_Store`, `*.log`.

**Impacto**: Bajo. Limpieza de repo.

### 6.4 TypeScript strict en SDK
**Problema**: El SDK en `resources/assets/sdk/` podría no tener `strict: true`.

**Mejora**: Activar `strict` en `tsconfig.json` y corregir errores de tipo.

**Impacto**: Medio. Reduce bugs en runtime del SDK.

### 6.5 Health check más completo
**Problema**: `engagement:check-health` verifica integraciones pero podría hacer más.

**Mejora**: Añadir checks de:
- Conectividad a DB `helpdesk`.
- Redis (si está configurado).
- Queue worker activo.
- Espacio en disco para logs.

**Impacto**: Medio. Mejora observabilidad.

---

## 7. Features Nuevas (Roadmap)

### 7.1 Segmentación avanzada
**Mejora**: Permitir crear segmentos personalizados con reglas combinadas (AND/OR) basadas en eventos, scores, contexto y atributos del cliente. Guardar segmentos y usarlos en triggers/personalizaciones.

**Impacto**: Alto. Diferenciador competitivo.

### 7.2 AB Testing nativo
**Mejora**: Mejorar el sistema de variantes actual para soportar:
- Métricas de conversión por variante.
- Tamaño de muestra configurable.
- Cierre automático cuando hay ganador estadísticamente significativo.

**Impacto**: Alto.

### 7.3 Machine Learning para recomendaciones
**Mejora**: Reemplazar `RecommenderService` basado en reglas con un modelo ML (o algoritmo de filtrado colaborativo) para recomendaciones más precisas.

**Impacto**: Alto a largo plazo.

### 7.4 Geolocalización y idioma
**Mejora**: Añadir detección de geolocalización/IP al contexto del visitante. Usar para:
- Personalizaciones por país/idioma.
- Horarios de negocio locales.
- Precios en moneda local.

**Impacto**: Medio.

### 7.5 Integración con email marketing
**Mejora**: Conectar con Mailchimp, SendGrid, etc. para:
- Sincronizar segmentos.
- Disparar campañas desde flujos de automatización.
- Tracking de aperturas/clicks como eventos.

**Impacto**: Medio.

### 7.6 Real-time dashboard
**Mejora**: Usar Laravel Reverb para actualizar live-visitors y analytics en tiempo real sin polling cada 5s.

**Impacto**: Medio. Mejora UX y reduce carga de servidor.

### 7.7 Mobile SDK
**Mejora**: Crear SDK nativo para iOS/Android o React Native wrapper del JS SDK.

**Impacto**: Alto. Expandir cobertura.

---

## Resumen de prioridades

| Prioridad | Mejora | Esfuerzo | Impacto |
|---|---|---|---|
| **P0** | Unificar permisos a `engagement.*` | 2h | Bloqueante |
| **P0** | Completar `$fillable` TriggerRule | 5min | Bloqueante |
| **P0** | Índices compuestos en DB | 2h | Alto rendimiento |
| **P1** | Cachear triggers/personalizations | 4h | Alto rendimiento |
| **P1** | Rate limit por IP+token | 2h | Seguridad |
| **P1** | Landing page + breadcrumbs | 4h | UX |
| **P1** | Documentación SDK | 4h | Adopción |
| **P2** | Desacoplar `Web` de HelpdeskLivechat | 8h | Arquitectura |
| **P2** | Soft deletes en settings | 4h | Protección |
| **P2** | E2E tests | 8h | Calidad |
| **P3** | ML recomendaciones | 40h | Innovación |
| **P3** | Mobile SDK | 80h | Expansión |

---

## Notas de implementación

- Todas las mejoras deben seguir las convenciones del proyecto (`.kimi/rules/`).
- Las migraciones deben ser idempotentes y manejar `down()` correctamente.
- Las mejoras de DB deben probarse con `EXPLAIN` antes y después.
- Documentar cualquier breaking change.
