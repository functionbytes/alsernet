# Roadmap de Mejoras e Implementaciones — Módulo Engagement

> Documento vivo con mejoras aplicadas, pendientes y features nuevas propuestas.
> Última actualización: 2026-05-03

---

## 📋 Resumen ejecutivo

El módulo **Engagement** es un sistema de tracking, scoring, automatización y personalización para visitantes de e-commerce. Este documento prioriza mejoras por impacto y esfuerzo, diferenciando entre correcciones críticas (P0), mejoras de rendimiento y UX (P1), refactorizaciones arquitectónicas (P2), e innovaciones a largo plazo (P3).

---

## ✅ Mejoras YA implementadas (2026-05-03)

### 1. Corrección de permisos (P0 — Bloqueante)
**Problema**: Controladores y Form Requests usaban `can:helpdesk.livechat.*` pero el seeder migra a `engagement.*`, provocando 403.

**Solución aplicada**:
- 7 controladores de Settings → `engagement.*`
- 3 controladores de Managers → `engagement.events.view`
- 2 Form Requests → `engagement.platforms.create/update`

**Archivos modificados**: 12 PHP

---

### 2. `$fillable` en `TriggerRule` (P0 — Bloqueante)
**Problema**: Faltaban `variant_group` y `variant_weight` en `$fillable`. El `VariantAssigner` los leía pero el mass assignment fallaba.

**Solución aplicada**: Agregados ambos campos al modelo.

---

### 3. Rutas "Volver" en Blade (P1 — UX)
**Problema**: 6 vistas apuntaban a `settings.helpdesk-livechat.index` (módulo ajeno).

**Solución aplicada**: Corregidas a `settings.engagement.index`.

---

### 4. Landing page `settings.engagement.index` (P1 — UX)
**Problema**: No existía una home del módulo. Los usuarios entraban directo a subsecciones.

**Solución aplicada**:
- `SettingsIndexController` con KPIs de cada sección
- Vista Blade con cards, guía rápida en acordeón y enlaces directos
- Ruta `GET /panel/settings/engagement` registrada
- Menú sidebar actualizado con enlace "Inicio"

---

### 5. Cache para SDK (P1 — Rendimiento)
**Problema**: Cada `sdk/init` hacía queries a `trigger_rules` y `personalization_rules` sin cache.

**Solución aplicada**:
- `InitController`, `TriggerController`, `PersonalizationController` usan `Cache::remember()` con TTL 5 min
- Claves: `engagement:inbox:{id}:triggers` y `engagement:inbox:{id}:personalizations`
- Invalidación automática en `TriggerRuleController` y `PersonalizationRuleController` al crear/editar/eliminar/bulk

---

### 6. Índices compuestos en DB (P0 — Rendimiento)
**Problema**: Tablas grandes sin índices optimizados para queries frecuentes de analytics y SDK.

**Solución aplicada**:
- Migración `2026_05_07_000001_add_engagement_performance_indexes.php`
- 11 índices idempotentes en `engagement_events`, `engagement_visitor_sessions`, `engagement_visitor_scores`, `engagement_trigger_rules`, `engagement_personalization_rules`, `engagement_platform_integrations`

---

## 🔧 Mejoras pendientes — Alto impacto

### 7. Desacoplar `HelpdeskLivechat\Models\Channels\Web` (P2 — Arquitectura)
**Problema**: El middleware `EnsureWebsiteToken` y 12 archivos de tests dependen de `Modules\HelpdeskLivechat\Models\Channels\Web`. Acoplamiento fuerte entre módulos.

**Impacto**: Alto. Permite que Engagement funcione independientemente.

**Esfuerzo estimado**: 8h

**Implementación propuesta**:
1. Crear `Modules\Engagement\Models\WebsiteChannel` con tabla `engagement_website_channels`
2. Migración que copie datos desde `helpdesk_channel_webs`
3. Modificar `EnsureWebsiteToken` para usar el nuevo modelo
4. Actualizar tests (12 archivos)
5. Añadir fallback o comando de sincronización durante transición

**Breaking change**: Sí. Requiere coordinación con módulo HelpdeskLivechat.

---

### 8. Rate limiting por IP + token (P1 — Seguridad)
**Problema**: Throttle actual es global por endpoint. Un atacante puede agotar el límite para todos los sitios.

**Impacto**: Alto. Mitiga DoS y abuso de API.

**Esfuerzo estimado**: 2h

**Implementación propuesta**:
```php
// En routes/sdk.php
Route::middleware(['throttle:60,1,engagement:{website_token}'])
```
O usar un middleware custom que aplique throttle por `ip + website_token`.

---

### 9. Sanitización de selectores CSS (P1 — Seguridad)
**Problema**: `PersonalizationRule` permite cualquier selector CSS. Riesgo de XSS via DOM si se inyecta `javascript:` o `expression()`.

**Impacto**: Alto. Previene XSS reflejado.

**Esfuerzo estimado**: 3h

**Implementación propuesta**:
- Validar en Form Request que el selector no contenga `javascript:`, `expression()`, `onerror`, etc.
- Whitelist de propiedades/mutaciones permitidas
- Escapar contenido inyectado en el SDK antes de aplicar `innerHTML`

---

### 10. Soft deletes en entidades de configuración (P2 — Protección)
**Problema**: No hay `SoftDeletes` en `TriggerRule`, `PersonalizationRule`, `AutomationFlow`, `ConversionGoal`, `PlatformIntegration`. Borrar accidentalmente una regla activa elimina datos históricos.

**Impacto**: Medio. Protección contra errores humanos.

**Esfuerzo estimado**: 4h

**Implementación propuesta**:
1. Añadir `use SoftDeletes` a modelos de settings
2. Migraciones para agregar `deleted_at` a cada tabla
3. Modificar vistas para mostrar pestaña "Papelera" con opción de restaurar
4. Modificar queries de SDK para excluir `deleted_at IS NOT NULL`

---

### 11. Cachear score del visitante (P1 — Rendimiento)
**Problema**: `ScoringService` recalcula score desde cero en cada evento.

**Impacto**: Medio. Reduce carga de CPU en sitios con mucho tráfico.

**Esfuerzo estimado**: 3h

**Implementación propuesta**:
- Cachear score por `session_token` con TTL de 1h
- Invalidar solo cuando lleguen eventos relevantes (no todos)
- Usar `Cache::remember()` en `ScoringService::getOrCreate()`

---

### 12. Eager loading en AnalyticsController (P1 — Rendimiento)
**Problema**: Posibles N+1 en relaciones de Event, Customer, Inbox en endpoints de analytics.

**Impacto**: Medio.

**Esfuerzo estimado**: 2h

**Implementación propuesta**:
- Auditar con `Debugbar` o `Telescope`
- Agregar `with(['customer', 'inbox'])` en queries de `AnalyticsController` y `CustomerProfileController`

---

## 🚀 Features nuevos propuestos

### 13. Segmentación avanzada (P1 — Feature)
**Descripción**: Permitir crear segmentos personalizados con reglas combinadas (AND/OR) basadas en eventos, scores, contexto y atributos del cliente.

**Impacto**: Alto. Diferenciador competitivo.

**Esfuerzo estimado**: 16h

**Modelo de datos**:
```php
// engagement_segments
- id, inbox_id, name, conditions (JSON), is_active, created_at, updated_at

// engagement_segment_customers (pivot)
- segment_id, customer_id, matched_at
```

**Uso**: Segmentos disponibles en triggers, personalizaciones y automation.

---

### 14. AB Testing nativo mejorado (P1 — Feature)
**Descripción**: Mejorar el sistema actual de variantes para soportar métricas de conversión por variante, tamaño de muestra configurable y cierre automático.

**Impacto**: Alto.

**Esfuerzo estimado**: 12h

**Implementación propuesta**:
1. Tabla `engagement_ab_tests` (variant_group, start_date, end_date, winner_rule_id, status)
2. Registrar conversión por variante en `engagement_events` (propiedad `variantId`)
3. Dashboard de resultados con significancia estadística

---

### 15. Real-time dashboard con Reverb (P2 — UX)
**Descripción**: Usar Laravel Reverb para actualizar live-visitors y analytics sin polling cada 5s.

**Impacto**: Medio. Mejora UX y reduce carga de servidor.

**Esfuerzo estimado**: 8h

**Implementación propuesta**:
1. Evento `VisitorActivityUpdated` con `ShouldBroadcast`
2. Canal privado `engagement.inbox.{id}`
3. Frontend escucha vía Echo y actualiza tarjetas en tiempo real

---

### 16. Geolocalización e idioma (P2 — Feature)
**Descripción**: Detectar geolocalización/IP del visitante para personalizaciones por país/idioma, horarios de negocio locales y moneda local.

**Impacto**: Medio.

**Esfuerzo estimado**: 8h

**Implementación propuesta**:
1. Añadir campos a `VisitorContext`: `country`, `city`, `language`, `timezone`
2. Usar servicio GeoIP (ej: `stevebauman/location` ya disponible en el proyecto)
3. Condiciones de triggers basadas en geolocalización

---

### 17. Integración con email marketing (P2 — Feature)
**Descripción**: Conectar con Mailchimp, SendGrid, Brevo para sincronizar segmentos y disparar campañas desde flujos de automatización.

**Impacto**: Medio.

**Esfuerzo estimado**: 16h

**Implementación propuesta**:
1. Nuevo tipo de nodo en Automation: `send_email`
2. Connector de Mailchimp/SendGrid en `Connectors/`
3. Sincronizar segmentos como listas/audiences

---

### 18. Machine Learning para recomendaciones (P3 — Innovación)
**Descripción**: Reemplazar `RecommenderService` basado en reglas con un modelo ML o filtrado colaborativo.

**Impacto**: Alto a largo plazo.

**Esfuerzo estimado**: 40h

**Implementación propuesta**:
1. Almacenar matriz de interacción usuario-producto
2. Job nocturno que entrena modelo (Python vía `spatie/laravel-python` o API externa)
3. Cachear recomendaciones por perfil

---

### 19. Mobile SDK (P3 — Expansión)
**Descripción**: SDK nativo para iOS/Android o wrapper React Native del JS SDK.

**Impacto**: Alto. Expandir cobertura.

**Esfuerzo estimado**: 80h+

**Implementación propuesta**:
1. Versión inicial: React Native bridge del SDK existente
2. Versión nativa: Swift/Kotlin con mismos endpoints (`/sdk/init`, `/sdk/track`, `/sdk/identify`)

---

### 20. Archivado de eventos (P2 — Mantenimiento)
**Descripción**: La tabla `engagement_events` crece indefinidamente. Necesita estrategia de archivado.

**Impacto**: Alto a largo plazo.

**Esfuerzo estimado**: 8h

**Implementación propuesta**:
1. Tabla `engagement_events_archive` con mismas columnas
2. Job mensual que mueve eventos >90 días a la tabla de archivo
3. AnalyticsController consulta ambas tablas con `UNION` para periodos largos
4. O particionamiento por mes en MariaDB

---

## 🎨 UX/UI mejoras

### 21. Breadcrumbs consistentes (P2)
**Descripción**: Implementar breadcrumbs en todas las vistas de settings y managers:
```
Inicio > Engagement > Reglas de activación
```

**Esfuerzo**: 3h

---

### 22. Empty states amigables (P2)
**Descripción**: Tablas vacías con ilustración/icono, mensaje contextual y CTA.

**Esfuerzo**: 4h

---

### 23. Builder visual de condiciones (P3)
**Descripción**: Reemplazar JSON manual de `conditions` en triggers por un builder drag-and-drop o formulario visual.

**Esfuerzo**: 20h

---

### 24. Dark mode completo (P2)
**Descripción**: Auditar todas las vistas de Engagement para soportar `dark:` variants de Tailwind/Bootstrap.

**Esfuerzo**: 4h

---

## 🔒 Seguridad adicional

### 25. Rotación automática de secrets (P2)
**Descripción**: `PlatformIntegration` genera secret una sola vez. Añadir campo `secret_rotated_at` y notificar rotación cada 90 días.

**Esfuerzo**: 4h

---

### 26. CSP headers para assets SDK (P2)
**Descripción**: Añadir `Content-Security-Policy` en respuestas de `AssetProxyController`.

**Esfuerzo**: 2h

---

### 27. Audit log completo (P2)
**Descripción**: Verificar que `RecordsAudit` capture TODOS los cambios con diff completo (valores previo/nuevo) en todas las entidades de settings.

**Esfuerzo**: 3h

---

## 🧪 Testing y calidad

### 28. Tests E2E con Playwright/Cypress (P1)
**Descripción**: La carpeta `tests/E2E/specs/` está vacía.

**Specs propuestas**:
- Flujo SDK completo: init → track → identify → trigger
- CRUD de triggers en settings
- Webhook PrestaShop → evento → score

**Esfuerzo**: 16h

---

### 29. Documentación del SDK (P1)
**Descripción**: Crear `docs/engagement/sdk-integration.md` con:
- Cómo incluir el script
- Métodos disponibles (`eng.track`, `eng.identify`, `eng.context`)
- Ejemplos por plataforma (PrestaShop, Shopify, WooCommerce)
- Eventos recomendados (page_view, add_to_cart, purchase)

**Esfuerzo**: 4h

---

### 30. Health check ampliado (P2)
**Descripción**: `engagement:check-health` actual verifica integraciones. Ampliar con:
- Conectividad a DB `helpdesk`
- Redis (si está configurado)
- Queue worker activo
- Espacio en disco

**Esfuerzo**: 3h

---

## 📊 Roadmap sugerido por trimestre

### Q2 2026 (Ahora — Junio)
| # | Mejora | Esfuerzo | Prioridad |
|---|---|---|---|
| ✅ | Permisos unificados | 2h | P0 |
| ✅ | `$fillable` TriggerRule | 5min | P0 |
| ✅ | Landing page | 3h | P1 |
| ✅ | Cache SDK | 3h | P1 |
| ✅ | Índices DB | 2h | P0 |
| 8 | Rate limit por IP+token | 2h | P1 |
| 9 | Sanitización CSS | 3h | P1 |
| 29 | Documentación SDK | 4h | P1 |
| 10 | Soft deletes | 4h | P2 |
| 11 | Cachear score | 3h | P1 |

### Q3 2026 (Julio — Septiembre)
| # | Mejora | Esfuerzo | Prioridad |
|---|---|---|---|
| 7 | Desacoplar `Web` de HelpdeskLivechat | 8h | P2 |
| 13 | Segmentación avanzada | 16h | P1 |
| 14 | AB Testing mejorado | 12h | P1 |
| 15 | Real-time dashboard | 8h | P2 |
| 16 | Geolocalización | 8h | P2 |
| 28 | Tests E2E | 16h | P1 |
| 20 | Archivado de eventos | 8h | P2 |

### Q4 2026 (Octubre — Diciembre)
| # | Mejora | Esfuerzo | Prioridad |
|---|---|---|---|
| 17 | Integración email marketing | 16h | P2 |
| 23 | Builder visual de condiciones | 20h | P3 |
| 25 | Rotación automática de secrets | 4h | P2 |
| 30 | Health check ampliado | 3h | P2 |
| 18 | ML recomendaciones (v1) | 40h | P3 |

### 2027
| # | Mejora | Esfuerzo | Prioridad |
|---|---|---|---|
| 19 | Mobile SDK | 80h+ | P3 |
| 18 | ML recomendaciones (producción) | 40h | P3 |

---

## 📁 Archivos relevantes

### Para implementar mejoras
| Área | Archivos clave |
|---|---|
| SDK | `modules/Engagement/routes/sdk.php`, `InitController`, `TrackController`, `EnsureWebsiteToken` |
| Settings | `modules/Engagement/routes/settings.php`, controllers en `Settings/` |
| Managers | `modules/Engagement/routes/managers.php`, controllers en `Managers/` |
| Modelos | `modules/Engagement/app/Models/` |
| Servicios | `modules/Engagement/app/Services/` |
| Vistas | `modules/Engagement/resources/views/` |
| Jobs | `modules/Engagement/app/Jobs/` |
| Tests | `modules/Engagement/tests/` |

---

## 📝 Notas para desarrolladores

- Seguir `.kimi/rules/` correspondiente según tipo de archivo
- Usar `Cache::` sin tags para compatibilidad con driver `file`
- Preferir `Model::query()` sobre `DB::`
- Siempre invalidar cache al modificar datos cacheados
- Las migraciones deben ser idempotentes cuando sea posible
- Ejecutar `vendor/bin/pint --dirty` antes de finalizar
- Escribir tests para cualquier feature nueva

---

*Generado automáticamente a partir del análisis del módulo Engagement.*
