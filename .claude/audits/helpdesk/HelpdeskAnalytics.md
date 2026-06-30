# Auditoría — HelpdeskAnalytics

> Fecha: 2026-06-29 · Health score: 78/100 · Estado: solid-minor-issues

**Resumen:** Pequeño módulo de dashboard analytics read-only, bien estructurado y seguro (triple capa de autorización, agregaciones SQL sin N+1, XSS escapado), pero con varias piezas a medio cablear: heatmap calculado y nunca renderizado, config/lang/permiso export sin uso, y un bug latente de Carbon en el health score. El diagnóstico general es sólido: 0 hallazgos critical/high, 4 medium y 6 low, todos confirmados en verificación estática. El trabajo pendiente es de pulido y cableado, no de arquitectura.

## Tabla de hallazgos

| ID | Sev | Categoría | Archivo:línea | Estado verif. | Esfuerzo | Título |
|----|-----|-----------|---------------|---------------|----------|--------|
| HA-01 | medium | quality | HealthScoreBatchService.php:73-75 | [CONFIRMADO] | S | Penalización por inactividad >6 meses nunca se aplica (Carbon 3 diff con signo) |
| HA-02 | medium | wiring | AnalyticsController.php:40 | [CONFIRMADO] | M | Heatmap calculado y expuesto por la API pero nunca renderizado |
| HA-03 | medium | quality | AnalyticsAggregatorService.php:20,202 | [CONFIRMADO] | S | Valores de config nunca usados (cache_ttl, customer_segment_limit hardcodeados) |
| HA-04 | medium | wiring | HelpdeskAnalyticsPermissionsSeeder.php:18 | [CONFIRMADO] | S | Permiso helpdeskanalytics.export sembrado sin ninguna feature de exportación |
| HA-05 | low | performance | AnalyticsRangeRequest.php:16-19 | [CONFIRMADO] | S | Rango de fechas sin límite superior: queries y arrays potencialmente grandes |
| HA-06 | low | conventions | dashboard/index.blade.php:7-83 | [CONFIRMADO] | M | Archivos de idioma definidos pero nunca usados; blade con cadenas hardcodeadas |
| HA-07 | low | tests | AnalyticsAggregatorTest.php:21-88 | [CONFIRMADO] | M | Sin test HTTP de ruta ni de autorización (403) |
| HA-08 | low | security | dashboard/index.blade.php:93 | [CONFIRMADO] | S | Chart.js cargado desde CDN externo sin SRI |
| HA-09 | low | performance | AnalyticsAggregatorService.php:43-47,226-231 | [CONFIRMADO] | S | Cache compartido entre usuarios por rango; 'open' no depende del rango pero se cachea por timestamps |
| HA-10 | low | performance | AnalyticsAggregatorService.php:32-188 | [CONFIRMADO] | M | Agregaciones dependen de índices en tablas de Helpdesk core |

## Hallazgos detallados

### Medium

#### HA-01 · [CONFIRMADO] Penalización por inactividad >6 meses nunca se aplica (Carbon 3 diff con signo)
- **Archivo:** `modules/HelpdeskAnalytics/app/Services/HealthScoreBatchService.php:73-75`
- **Evidencia:** `if ($last && now()->diffInMonths($last) > 6) { $score -= 20; }` — en Laravel 12 / Carbon 3 los métodos `diffIn*` son con signo: `now()->diffInMonths($fechaPasada)` devuelve un valor negativo, por lo que la condición `> 6` nunca es verdadera para fechas pasadas. La regla de health-score queda como código muerto y los clientes inactivos no pierden puntos.
- **Impacto:** El cálculo de salud de clientes (segmentos saludable/neutral/en riesgo) no degrada por inactividad, sesgando el dashboard hacia "más sanos" de lo real.
- **Recomendación:** Usar `abs()` o el orden correcto de operandos: `Carbon::parse($last)->diffInMonths(now())` o `abs(now()->diffInMonths($last)) > 6`. Cubrir con un test unitario que fije `last_at` a >6 meses.

#### HA-02 · [CONFIRMADO] Heatmap calculado y expuesto por la API pero nunca renderizado
- **Archivo:** `modules/HelpdeskAnalytics/app/Http/Controllers/Managers/AnalyticsController.php:40`
- **Evidencia:** `data()` devuelve `'heatmap' => $this->analytics->heatmap(...)` y el servicio ejecuta una query `GROUP BY WEEKDAY/HOUR` (líneas 175-188), pero `grep 'heatmap'` en `resources/views/dashboard/index.blade.php` → NONE. El blade `load()` nunca consume `res.heatmap`.
- **Impacto:** Trabajo de BD desperdiciado en cada request al endpoint `data` y feature a medio cablear; o falta la visualización prometida en la descripción del módulo.
- **Recomendación:** Añadir el canvas/render del heatmap en el blade, o eliminar la clave del controller y el método del servicio hasta implementarlo.

#### HA-03 · [CONFIRMADO] Valores de config nunca usados (cache_ttl, customer_segment_limit hardcodeados)
- **Archivo:** `modules/HelpdeskAnalytics/app/Services/AnalyticsAggregatorService.php:20,202`
- **Evidencia:** `config/config.php` define `cache_ttl=300` y `customer_segment_limit=500`, pero el servicio usa `const CACHE_TTL = 300` y `->limit(500)` hardcodeados; `grep 'config('` en `app/` → NO config() calls. El config queda inerte.
- **Impacto:** Los operadores que ajusten el config no ven efecto; documentación de config engañosa.
- **Recomendación:** Reemplazar la constante y el `limit()` por `config('helpdeskanalytics.cache_ttl')` y `config('helpdeskanalytics.customer_segment_limit')`.

#### HA-04 · [CONFIRMADO] Permiso helpdeskanalytics.export sembrado sin ninguna feature de exportación
- **Archivo:** `modules/HelpdeskAnalytics/database/seeders/HelpdeskAnalyticsPermissionsSeeder.php:18`
- **Evidencia:** Se crea y asigna `'helpdeskanalytics.export'` pero `grep 'export'` en `app/` y `routes/` → solo aparece en el seeder. No hay ruta, controller ni botón de export.
- **Impacto:** Permiso muerto que confunde la matriz de permisos y sugiere una capacidad inexistente.
- **Recomendación:** Eliminar el permiso export hasta implementar la exportación (CSV/Excel/PDF), o implementar la acción y su ruta `export` GET.

### Low

#### HA-05 · [CONFIRMADO] Rango de fechas sin límite superior: queries y arrays potencialmente grandes
- **Archivo:** `modules/HelpdeskAnalytics/app/Http/Requests/Managers/AnalyticsRangeRequest.php:16-19`
- **Evidencia:** `rules()` valida `'from'`/`'to'` como `date` y `after_or_equal` sin tope de amplitud. `trends()` itera día a día construyendo un array (líneas 156-167) y `heatmap`/`overview` agregan sobre todo el rango; un rango de varios años fuerza queries pesadas y arrays grandes cacheados.
- **Impacto:** Un usuario autenticado puede disparar agregaciones costosas sobre toda la historia; bajo riesgo por TTL de cache pero degradable.
- **Recomendación:** Añadir una regla de amplitud máxima (p.ej. `before_or_equal` y validación de diff <= 366 días) o recortar el rango en el servicio.

#### HA-06 · [CONFIRMADO] Archivos de idioma definidos pero nunca usados; blade con cadenas en español hardcodeadas
- **Archivo:** `modules/HelpdeskAnalytics/resources/views/dashboard/index.blade.php:7-83`
- **Evidencia:** `lang/es/messages.php` y `lang/en/messages.php` existen, pero `grep '__('` / `'@lang'` / `'trans('` en `resources/` y `app/` → NO lang usage. Todos los títulos del dashboard ('Conversaciones', 'Rendimiento por agente'...) están hardcodeados.
- **Impacto:** El módulo no es multi-idioma pese a tener los recursos; inconsistencia con la convención de i18n.
- **Recomendación:** Sustituir las cadenas del blade por `__('helpdeskanalytics::messages.X')` o eliminar los archivos lang si no se requiere i18n.

#### HA-07 · [CONFIRMADO] Sin test HTTP de ruta ni de autorización (403)
- **Archivo:** `modules/HelpdeskAnalytics/tests/Feature/AnalyticsAggregatorTest.php:21-88`
- **Evidencia:** Solo hay tests del servicio (overview, segments, health score). No se prueba el endpoint `helpdeskanalytics.data` (200 con permiso / 403 sin permiso) ni la vista index ni la validación del Form Request.
- **Impacto:** La capa de autorización y el contrato JSON del controller no están cubiertos por pruebas automatizadas.
- **Recomendación:** Añadir tests Feature: `actingAs(usuario con permiso)->getJson(route('helpdeskanalytics.data'))` assertOk; sin permiso assertForbidden; rango inválido assertUnprocessable.

#### HA-08 · [CONFIRMADO] Chart.js cargado desde CDN externo sin SRI
- **Archivo:** `modules/HelpdeskAnalytics/resources/views/dashboard/index.blade.php:93`
- **Evidencia:** `<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>` sin atributo `integrity`/`crossorigin`.
- **Impacto:** Dependencia de terceros sin verificación de integridad; superficie de supply-chain en un panel admin.
- **Recomendación:** Añadir SRI (`integrity` + `crossorigin`) o servir Chart.js localmente vía Vite/`asset()` como el resto del stack del proyecto.

#### HA-09 · [CONFIRMADO] Cache compartido entre usuarios por rango; 'open' no depende del rango pero se cachea por timestamps
- **Archivo:** `modules/HelpdeskAnalytics/app/Services/AnalyticsAggregatorService.php:43-47,226-231`
- **Evidencia:** `remember()` clava la key en `helpdeskanalytics:{key}:{from.ts}:{to.ts}`. `overview()` incluye `'open'` (conversaciones abiertas AHORA, no acotado por rango) dentro del mismo bloque cacheado 300s por rango, por lo que el contador de abiertas puede quedar hasta 5 min desfasado y duplicado por cada rango distinto.
- **Impacto:** Inconsistencia menor del KPI 'Abiertas (ahora)' y duplicación de cache para una métrica global.
- **Recomendación:** Cachear `'open'` en su propia key independiente del rango (o documentar el desfase de 5 min como aceptable).

#### HA-10 · [CONFIRMADO] Agregaciones dependen de índices en tablas de Helpdesk core
- **Archivo:** `modules/HelpdeskAnalytics/app/Services/AnalyticsAggregatorService.php:32-188`
- **Evidencia:** Todas las queries filtran/agrupan `helpdesk_conversations` y `helpdesk_conversation_items` por `created_at`/`closed_at`/`deleted_at`/`channel`/`assignee_id`/`type`. Este módulo no tiene migraciones; el rendimiento depende de índices definidos en el módulo Helpdesk.
- **Impacto:** Si faltan índices en columnas de filtro/agrupación, las agregaciones pueden ser lentas en tablas grandes.
- **Recomendación:** Verificar (en el módulo Helpdesk) índices en `helpdesk_conversations(created_at)`, `(closed_at)`, `(channel)`, `(assignee_id, closed_at)` y `helpdesk_conversation_items(user_id, type, created_at)`.

## Plan de ataque priorizado

1. **HA-01 (medium, S)** — Corregir el bug de Carbon en `HealthScoreBatchService::scoresFor`: la penalización por inactividad >6 meses nunca se aplica. Es el único hallazgo que produce datos incorrectos. Acompañar de test unitario.
2. **HA-02 (medium, M)** — Resolver el heatmap huérfano: renderizarlo en el blade o eliminar su cálculo del controller/servicio.
3. **HA-03 + HA-04 (medium, S)** — Conectar `config()` o eliminar las claves inertes; eliminar (o implementar) el permiso `export`. Limpieza de superficie a medio cablear.
4. **HA-07 (low, M)** — Añadir tests HTTP de ruta y autorización para blindar la triple capa de autorización ya existente.
5. **HA-05, HA-08, HA-09 (low, S)** — Endurecimientos: tope de rango, SRI en Chart.js, cache independiente para 'open'.
6. **HA-06 (low, M)** — Decidir i18n: cablear lang o eliminar los archivos.
7. **HA-10 (low, M)** — Verificar índices en el módulo Helpdesk core (fuera del alcance directo de este módulo).

## Quick wins

- Renderizar el heatmap en el blade o dejar de calcularlo/devolverlo en el controller (HA-02).
- Usar `config('helpdeskanalytics.cache_ttl')` y `config('helpdeskanalytics.customer_segment_limit')` en vez de las constantes hardcodeadas (HA-03).
- Eliminar el permiso `helpdeskanalytics.export` del seeder (no hay feature de export) o implementar la exportación (HA-04).
- Añadir un test HTTP de ruta + autorización (200 con permiso, 403 sin él) (HA-07).

## Fortalezas

- **Autorización robusta y redundante:** middleware en constructor del controller (`can:helpdeskanalytics.view`), middleware en el grupo de rutas, y `authorize()` en el Form Request — sin endpoints expuestos.
- **Agregaciones diseñadas explícitamente sin N+1:** `HealthScoreBatchService` resuelve 4 agregados agrupados independientemente del número de clientes (con test que lo verifica), y `agentPerformance` precarga CSAT/mensajes/usuarios por lotes.
- **XSS mitigado en el blade:** el nombre de agente se escapa con `$('<div>').text(a.name).html()` antes de inyectarlo en la tabla.
- **Convenciones de rutas y permisos correctas:** prefix `panel/helpdeskanalytics`, name `helpdeskanalytics.`, middleware `web`+`auth`, permisos `{alias}.{action}` en minúsculas.
- **Form Request con `messages()`/`attributes()` en español;** ServiceProvider con guard de módulo deshabilitado y registro de NavService con Font Awesome 6.
- **Tests usan `DatabaseTransactions`** (no `RefreshDatabase`), alineado con la restricción de BD de test del proyecto.

## Cobertura de la auditoría

Cobertura completa: los 14 archivos del módulo fueron leídos (controller, 2 services, Form Request, ServiceProvider, ruta, seeder, config, blade, test, lang x2, module.json, composer.json). Verificadas conexiones de modelos (`CsatRating` y `Conversation` usan connection `helpdesk`; `User` en conexión default — joins separados correctos). No se ejecutó la suite de tests por la restricción de BD de test del entorno (análisis estático con Read/Grep). El bug de Carbon (HA-01) se basa en el comportamiento con signo de `diffInMonths` en Carbon 3 (Laravel 12); recomendable confirmar con un test unitario al rehabilitarse la BD. Las recomendaciones de índices (HA-10) aplican al módulo Helpdesk core, fuera del alcance de este módulo.

No hubo hallazgos critical/high que verificar adicionalmente.

## Descartados en verificación

Ninguno. Todos los hallazgos del análisis fueron confirmados; no se refutó ningún hallazgo durante la verificación.
