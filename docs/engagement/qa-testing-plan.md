# Plan de Testing QA — Módulo Engagement

## Resumen del alcance

El módulo **Engagement** es un sistema de tracking, scoring, automatización y personalización para visitantes de e-commerce. Incluye:

- **SDK público** (JavaScript/TypeScript) para tracking de eventos, identificación, contexto y personalización.
- **API de gestión** (Managers) para analytics, perfiles de clientes, visitantes en vivo y exportación.
- **Panel de configuración** (Settings) para triggers, personalizaciones, integraciones de plataforma, automatizaciones, objetivos de conversión, logs de webhooks y auditoría.
- **Jobs y procesamiento** de eventos, webhooks, recálculo de scores y ejecución de flujos de automatización.
- **Conectores** a plataformas e-commerce (PrestaShop, Shopify, WooCommerce, ERP, Custom).

---

## 1. Testing de SDK (API pública)

### 1.1 Inicialización de sesión (`POST /eng/api/sdk/init`)
- [ ] **Token válido**: Retorna sesión, triggers iniciales, personalizaciones y score.
- [ ] **Token ausente**: Retorna 401 con mensaje en español.
- [ ] **Token inválido**: Retorna 401.
- [ ] **Canal inactivo**: Retorna 403.
- [ ] **Formato de respuesta**: Estructura JSON consistente (`success`, `data`, `meta`).
- [ ] **Compatibilidad**: Endpoint también disponible en `/hd/api/sdk/init`.

### 1.2 Identificación (`POST /eng/api/sdk/identify`)
- [ ] **Cliente nuevo**: Crea Customer y enlaza sesión.
- [ ] **Cliente existente**: Actualiza datos y reenlaza sesión.
- [ ] **Email inválido**: Retorna error de validación 422.
- [ ] **Merge de contexto**: Preserva contexto previo del visitante.
- [ ] **Transaccionalidad**: Rollback correcto si falla alguna operación.

### 1.3 Tracking de eventos (`POST /eng/api/sdk/track`)
- [ ] **Evento simple**: Se almacena correctamente.
- [ ] **Batch de eventos**: Bulk insert funciona, todos los eventos se guardan.
- [ ] **Evento con propiedades**: JSON de propiedades se guarda como array.
- [ ] **Evento antiguo (>24h)**: Se descarta silenciosamente.
- [ ] **Evento duplicado**: Manejo según lógica de deduplicación.
- [ ] **Throttling**: Límite de rate aplicado correctamente.

### 1.4 Contexto (`GET/POST /eng/api/sdk/context`)
- [ ] **GET contexto**: Retorna contexto acumulado de la sesión.
- [ ] **POST contexto**: Merge correcto de nuevos campos sin sobrescribir los previos.
- [ ] **Contexto vacío**: Manejo de array vacío/null.

### 1.5 Triggers (`GET /eng/api/sdk/triggers`)
- [ ] **Lista ordenada**: Retorna triggers activos ordenados por prioridad.
- [ ] **Filtrado por inbox**: Solo triggers del inbox correspondiente al token.
- [ ] **Variantes A/B**: Asignación determinística por sessionToken.

### 1.6 Personalizaciones (`GET /eng/api/sdk/personalizations`)
- [ ] **Reglas activas**: Solo retorna reglas con `is_active = true`.
- [ ] **Filtrado por inbox**: Correcto.

### 1.7 Recomendaciones (`GET /eng/api/sdk/recommendations`)
- [ ] **Perfil existente**: Retorna productos recomendados basados en historial.
- [ ] **Perfil nuevo**: Retorna fallback/recomendaciones por defecto.

### 1.8 Sincronización de catálogo (`POST /eng/api/sdk/catalog/sync`)
- [ ] **Upsert masivo**: Productos se crean/actualizan correctamente.
- [ ] **Throttle 10/min**: Se aplica el límite.
- [ ] **Validación de campos obligatorios**: SKU, nombre, precio.

### 1.9 Webhooks de plataforma (`POST /eng/api/sdk/webhook/{platform}/{id}`)
- [ ] **HMAC válido**: Webhook se procesa correctamente.
- [ ] **HMAC inválido**: Retorna 403.
- [ ] **Payload completo**: Crea Event y resuelve Customer.
- [ ] **Throttle 120/min**: Se aplica.
- [ ] **Plataforma no soportada**: Retorna 404 o error adecuado.

### 1.10 Proxy de assets (`GET /eng/api/assets/{bundle}`)
- [ ] **Bundle JS**: Se sirve con CORS y MIME type correcto.
- [ ] **Bundle CSS**: Se sirve con CORS y MIME type correcto.
- [ ] **Bundle inexistente**: Retorna 404.

---

## 2. Testing de Managers (Panel de gestión)

### 2.1 Visitantes en vivo (`/panel/engagement/live-visitors`)
- [ ] **Lista de activos**: Muestra visitantes con sesión en los últimos 5 min.
- [ ] **Refresco AJAX**: Datos se actualizan cada 5 segundos sin recargar página.
- [ ] **Sin visitantes**: Muestra estado vacío amigable.
- [ ] **Permisos**: Requiere `engagement.events.view`.

### 2.2 Analytics (`/panel/engagement/analytics`)
- [ ] **KPIs**: Total de eventos, visitantes únicos, sesiones, tasa de conversión.
- [ ] **Filtro por inbox**: Datos filtrados correctamente.
- [ ] **Eventos por día**: Gráfico con datos de los últimos 30 días.
- [ ] **Distribución de segmentos**: Pie chart con segmentos (hot, warm, cold).
- [ ] **Top eventos**: Tabla ordenada por frecuencia.
- [ ] **Performance de triggers**: Tasa de activación por trigger.

### 2.3 Perfil de cliente (`/panel/engagement/customer-profile/{id}`)
- [ ] **Datos del cliente**: Información completa visible.
- [ ] **Historial de sesiones**: Lista cronológica.
- [ ] **Eventos del cliente**: Tabla paginada.
- [ ] **Scores**: Evolución del score.
- [ ] **Contextos**: Contextos acumulados.
- [ ] **Lookup de datos externos**: PrestaShop, ERP, etc. (vía IntegrationLookup).

### 2.4 Exportación (`/panel/engagement/export/*`)
- [ ] **Exportar eventos**: CSV/JSON con filtros de fecha e inbox.
- [ ] **Exportar scores**: CSV/JSON con segmentos.
- [ ] **Grandes volúmenes**: No timeout en exportación de >10k registros.

---

## 3. Testing de Settings (Configuración)

### 3.1 Triggers (`/panel/settings/engagement/triggers`)
- [ ] **Listar**: Tabla con paginación, filtros, ordenamiento.
- [ ] **Crear**: Modal/form con condiciones (score, eventos, contexto) y acciones.
- [ ] **Editar**: Actualización en place.
- [ ] **Eliminar**: Soft delete o hard delete con confirmación.
- [ ] **Bulk actions**: Activar/desactivar/eliminar múltiples.
- [ ] **Validación**: Nombre obligatorio, condiciones JSON válido, acción JSON válido.
- [ ] **Variantes**: Campos `variant_group` y `variant_weight` se guardan correctamente.
- [ ] **Permisos**: `engagement.triggers.*`.

### 3.2 Personalizaciones DOM (`/panel/settings/engagement/personalizations`)
- [ ] **CRUD completo**: Crear, leer, actualizar, eliminar reglas.
- [ ] **Selector CSS**: Validación de selector no vacío.
- [ ] **Mutación**: HTML o acción a aplicar.
- [ ] **Permisos**: `engagement.personalizations.*`.

### 3.3 Integraciones de plataforma (`/panel/settings/engagement/platforms`)
- [ ] **CRUD completo**.
- [ ] **Tipos soportados**: PrestaShop, Shopify, WooCommerce, ERP, Custom.
- [ ] **Generación automática de secretos**: `webhook_secret` se genera al crear.
- [ ] **Rotación de secretos**: Funciona sin romper webhooks en tránsito.
- [ ] **Verificación HMAC**: Firma correcta en webhooks entrantes.
- [ ] **Permisos**: `engagement.platforms.*`.

### 3.4 Automatización (`/panel/settings/engagement/automation`)
- [ ] **Editor de flujos**: Crear nodos (mensaje, pregunta, condición, delay, acción).
- [ ] **Conexiones entre nodos**: Edges JSON válido.
- [ ] **Activar/desactivar flujo**.
- [ ] **Ejecución**: Disparo por evento o manual.
- [ ] **Permisos**: `engagement.automation.*`.

### 3.5 Objetivos de conversión (`/panel/settings/engagement/goals`)
- [ ] **CRUD de goals**.
- [ ] **Funnel**: Visualización de pasos y tasas de conversión.
- [ ] **Matching de eventos**: Un evento puede activar múltiples goals.
- [ ] **Permisos**: `engagement.goals.*`.

### 3.6 Webhook logs (`/panel/settings/engagement/webhook-logs`)
- [ ] **Lista con estados**: received, processed, failed, dead.
- [ ] **Filtros**: Por plataforma, estado, fecha.
- [ ] **Retry manual**: Reenvía webhook fallido.
- [ ] **Vista de detalle**: Payload completo, headers, respuesta.
- [ ] **Permisos**: `engagement.platforms.view` (lista), `engagement.platforms.update` (retry).

### 3.7 Audit logs (`/panel/settings/engagement/audit-logs`)
- [ ] **Registro de cambios**: Quién, qué, cuándo, valores previos/nuevos.
- [ ] **Filtros**: Por entidad, acción, usuario, fecha.
- [ ] **Permisos**: `engagement.manage`.

---

## 4. Testing de Servicios (Unit/Integration)

### 4.1 `SessionLinkService`
- [ ] **Nueva sesión**: Genera token de 64 chars único.
- [ ] **Reanudar sesión**: Recupera sesión existente por token.
- [ ] **Expiración**: Sesiones antiguas se marcan inactivas.

### 4.2 `TrackingIngestService`
- [ ] **Bulk insert**: Inserta batch de eventos en una sola query.
- [ ] **Descarte de antiguos**: Eventos >24h no se insertan.
- [ ] **Validación**: Campos obligatoros (session_id, event_type).

### 4.3 `ScoringService`
- [ ] **Recálculo**: Suma ponderada de eventos recientes + contexto.
- [ ] **Segmentación**: Hot (>=70), Warm (40-69), Cold (<40).
- [ ] **Evento threshold**: Emite `ScoreThresholdCrossed` al cambiar de segmento.
- [ ] **Job en cola**: `RecalculateScoreJob` se ejecuta correctamente.

### 4.4 `TriggerEvaluator`
- [ ] **Evaluación de condiciones**: Score, eventos recientes, contexto.
- [ ] **Límite por sesión**: No dispara más veces que `fires_per_session`.
- [ ] **Prioridad**: Evalúa en orden de prioridad descendente.
- [ ] **Variantes**: Asignación determinística por hash.

### 4.5 `VariantAssigner`
- [ ] **Selección por peso**: Mayor `variant_weight` tiene más probabilidad.
- [ ] **Determinismo**: Mismo sessionToken = mismo resultado.
- [ ] **Grupo único**: Solo 1 regla por `variant_group` activa.

### 4.6 `ConversionMatcher`
- [ ] **Matching exacto**: Evento coincide con goal.
- [ ] **Matching parcial**: Evento con propiedades específicas.
- [ ] **Funnel**: Cálculo correcto de tasas entre pasos.
- [ ] **Atribución**: Asocia conversión a sesión/cliente correcto.

### 4.7 `AutomationEngine`
- [ ] **Inicio de flujo**: Crea `AutomationRun` con estado `running`.
- [ ] **Ejecución de nodos**: Mensaje, pregunta, condición, delay, acción.
- [ ] **Condiciones**: Ramificación correcta según contexto/score.
- [ ] **Delay**: Respetar tiempo de espera.
- [ ] **Finalización**: Estado `completed` o `failed`.

### 4.8 `RecommenderService`
- [ ] **Basado en historial**: Productos vistos/comprados recientemente.
- [ ] **Basado en segmento**: Recomendaciones por score.
- [ ] **Fallback**: Productos populares si no hay historial.
- [ ] **Catálogo activo**: Solo productos `is_active = true`.

### 4.9 `CustomerDataOrchestrator`
- [ ] **Consulta ERP**: Llama a ERP connector con timeout.
- [ ] **Consulta PrestaShop**: Llama a PrestaShop connector con HMAC.
- [ ] **Agregación**: Combina resultados de múltiples plataformas.
- [ ] **Fallback**: Si un connector falla, los demás siguen funcionando.

### 4.10 `PlatformWebhookHandler`
- [ ] **PrestaShop**: Parsea payload y crea Event correcto.
- [ ] **Shopify**: Parsea webhook de orden/producto/cliente.
- [ ] **WooCommerce**: Parsea webhook de orden.
- [ ] **Custom**: Payload JSON genérico.
- [ ] **Resolución de cliente**: Por email, id externo, o creación nueva.

---

## 5. Testing de Jobs y Colas

### 5.1 `ProcessEventBatchJob`
- [ ] **Procesamiento**: Batch de eventos se inserta correctamente.
- [ ] **Reintentos**: Fallo parcial reintenta solo los fallidos.
- [ ] **Timeout**: No excede tiempo límite.

### 5.2 `ProcessWebhookJob`
- [ ] **Procesamiento**: Webhook se parsea y crea Event.
- [ ] **Retry automático**: Fallos reintentan con backoff exponencial.
- [ ] **Dead letter**: Después de N intentos, marca como `dead`.

### 5.3 `RecalculateScoreJob`
- [ ] **Recálculo**: Score actualizado tras eventos nuevos.
- [ ] **Evento threshold**: Emite evento si cambia segmento.
- [ ] **Idempotencia**: Múltiples jobs para mismo cliente no corrompen score.

---

## 6. Testing de Conectores

### 6.1 `PrestaShopCustomerConnector`
- [ ] **HMAC válido**: Firma de la petición correcta.
- [ ] **Endpoint del plugin**: Llama a `api.php` del plugin PrestaShop.
- [ ] **Timeout**: Maneja timeout de la API externa.
- [ ] **Cliente encontrado**: Retorna datos del cliente.
- [ ] **Cliente no encontrado**: Retorna null/array vacío.

### 6.2 `ErpCustomerConnector`
- [ ] **Endpoint interno**: Llama a `/api/erp/customer/{id}`.
- [ ] **Autenticación**: Token de servicio válido.
- [ ] **Timeout**: Maneja timeout.

### 6.3 `ConnectorFactory`
- [ ] **Resolución por plataforma**: Retorna instancia correcta.
- [ ] **Cache de instancias**: Misma instancia para múltiples llamadas.
- [ ] **Plataforma no soportada**: Lanza excepción clara.

---

## 7. Testing de Broadcasting y Canales

### 7.1 Canal privado (`widget-session.{token}`)
- [ ] **Autorización**: Solo el visitante con el token puede suscribirse.
- [ ] **Eventos**: `TriggerFired`, `ScoreThresholdCrossed` se broadcastean.
- [ ] **Presencia**: No aplica (canal privado, no presence).

---

## 8. Testing de Seguridad

- [ ] **XSS en SDK**: Propiedades de eventos escapan HTML en respuestas.
- [ ] **XSS en admin**: Inputs de settings escapan en Blade (`{{ }}`).
- [ ] **CSRF**: Formularios web incluyen token CSRF.
- [ ] **SQL Injection**: Uso de Eloquent/Query Builder, no concatenación.
- [ ] **HMAC**: Webhooks verifican firma antes de procesar.
- [ ] **Token exposure**: `website_token` no se expone en logs de error.
- [ ] **Rate limiting**: Endpoints públicos tienen throttle.
- [ ] **Autorización**: Gates/policies verifican permisos Spatie.

---

## 9. Testing de Rendimiento

- [ ] **Bulk insert**: 1000 eventos en <1s.
- [ ] **SDK init**: <100ms con 50 triggers/personalizaciones.
- [ ] **Live visitors**: <200ms para 500 visitantes activos.
- [ ] **Analytics**: <500ms para KPIs de 30 días.
- [ ] **Exportación**: Streaming de CSV para >50k registros.
- [ ] **N+1 queries**: Uso de eager loading en todas las relaciones.

---

## 10. Testing de Notificaciones

### 10.1 `IntegrationHealthAlert`
- [ ] **Canal email**: Se envía cuando una integración falla repetidamente.
- [ ] **Canal database**: Notificación en panel de admin.
- [ ] **Frecuencia**: No spamea (throttle por integración).

---

## 11. Testing de UI/UX (Chrome DevTools)

### 11.1 Responsive
- [ ] **Desktop**: Layout correcto en 1920x1080.
- [ ] **Tablet**: Layout correcto en 768x1024.
- [ ] **Mobile**: Layout usable en 375x667 (aunque no sea prio, no debe romperse).

### 11.2 Chrome DevTools — Network
- [ ] **SDK requests**: Headers correctos (`X-Website-Token`, `Content-Type: application/json`).
- [ ] **CORS**: Respuestas incluyen headers CORS adecuados.
- [ ] **Caching**: Assets con cache-control apropiado.
- [ ] **Payload size**: Respuestas JSON <50KB para init.
- [ ] **No 404s**: Todos los recursos (JS, CSS, API) cargan correctamente.

### 11.3 Chrome DevTools — Console
- [ ] **Sin errores JS**: No hay `Uncaught ReferenceError` ni `TypeError`.
- [ ] **Sin warnings**: No hay deprecaciones de jQuery o DevExpress.
- [ ] **Sin logs de debug**: No hay `console.log` de desarrollo en producción.

### 11.4 Chrome DevTools — Performance
- [ ] **LCP**: Largest Contentful Paint <2.5s en páginas de settings.
- [ ] **INP**: Interaction to Next Paint <200ms.
- [ ] **CLS**: Cumulative Layout Shift <0.1.
- [ ] **Memory**: Sin leaks de memoria en refresco de live-visitors.

### 11.5 Chrome DevTools — Lighthouse
- [ ] **Accessibility**: Score >= 90.
- [ ] **Best Practices**: Score >= 90.
- [ ] **SEO**: Score >= 90 (aunque sea panel interno).

---

## 12. Regresión y Edge Cases

- [ ] **Módulo desactivado**: Engagement no rompe el resto de la app.
- [ ] **Base de datos vacía**: Settings muestra estado vacío sin errores.
- [ ] **Inbox eliminado**: Foreign keys con `onDelete` correcto.
- [ ] **Token rotado**: Sesiones antiguas requieren re-inicialización.
- [ ] **Job fallido**: Retry con backoff, luego dead letter.
- [ ] **Webhook malformado**: No crashea, loguea error.
- [ ] **Catálogo vacío**: Recomendaciones retornan fallback.

---

## Priorización

| Prioridad | Área | Riesgo si falla |
|---|---|---|
| **P0 — Bloqueante** | Permisos (`helpdesk.livechat.*` vs `engagement.*`) | Usuarios no pueden acceder a settings |
| **P0 — Bloqueante** | SDK init/track/identify | Tracking completo no funciona |
| **P0 — Bloqueante** | `Web` namespace / token validation | SDK rechaza todos los requests |
| **P1 — Alto** | `$fillable` variantes en TriggerRule | A/B testing no funciona |
| **P1 — Alto** | Webhooks HMAC | Datos de e-commerce no entran |
| **P1 — Alto** | Scoring/RecalculateScoreJob | Segmentación de visitantes rota |
| **P2 — Medio** | Rutas "Volver" en Blade | UX confusa, pero no bloqueante |
| **P2 — Medio** | Exportación CSV | Funcionalidad secundaria |
| **P3 — Bajo** | UI responsive en mobile | Panel principalmente desktop |
| **P3 — Bajo** | Mejoras de rendimiento | Optimizaciones incrementales |

---

## Herramientas de testing

- **PHPUnit**: Tests unitarios y de integración (`php artisan test --filter Engagement`).
- **Chrome DevTools**: Network, Console, Performance, Lighthouse.
- **Artisan**: `engagement:check-health` para diagnóstico rápido.
- **Postman/curl**: Pruebas manuales de endpoints SDK.
- **tinker**: Verificación de modelos y relaciones.

---

## Checklist de ejecución

- [ ] Documento creado y revisado.
- [ ] Correcciones críticas (P0) aplicadas.
- [ ] Tests unitarios pasan.
- [ ] Tests de integración pasan.
- [ ] Pruebas manuales en Chrome DevTools completadas.
- [ ] Lighthouse audit >= 90 en accessibility y best practices.
- [ ] Sin errores en logs (`storage/logs/`).
- [ ] `vendor/bin/pint --dirty` ejecutado.
