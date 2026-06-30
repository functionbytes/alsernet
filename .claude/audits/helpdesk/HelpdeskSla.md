# Auditoría — HelpdeskSla

> Fecha: 2026-06-29 · Health score: 86/100 · Estado: solid-minor-issues

**Resumen:** Motor SLA central bien arquitecturado para conversaciones de Helpdesk; la seguridad y el cableado son sólidos, quedan únicamente problemas menores de completitud, convención y heurística de corrección. No se hallaron incidencias críticas ni altas; los 11 hallazgos restantes son de severidad media/baja y se concentran en settings huérfanos, exactitud del cálculo de horario laboral, atomicidad del registro de breaches y cobertura de tests.

## Tabla de hallazgos

| ID | Sev | Categoría | Archivo:línea | Estado verif. | Esfuerzo | Título |
|----|-----|-----------|---------------|---------------|----------|--------|
| SLA-01 | medium | wiring | database/seeders/HelpdeskSlaPermissionsSeeder.php:19-20 | [CONFIRMADO] | M | Permisos de settings sembrados pero nunca cableados |
| SLA-02 | medium | quality | app/Services/ConversationSlaService.php:352-363 | [CONFIRMADO] | M | percentUsed ignora horario laboral pero los vencimientos sí lo respetan |
| SLA-03 | medium | quality | app/Services/ConversationSlaService.php:133-159 | [CONFIRMADO] | S | Registro de breach + flag no atómicos: breaches duplicados ante fallo parcial |
| SLA-04 | medium | tests | tests/Feature/ConversationSlaTest.php:18-25 | [CONFIRMADO] | M | Sin cobertura HTTP/comando/policy; tests de servicio no ejecutables |
| SLA-05 | low | quality | app/Services/ConversationSlaService.php:232-246 | [CONFIRMADO] | S | recalculate() descarta la extensión por pausa acumulada |
| SLA-06 | low | wiring | app/Services/ConversationSlaService.php:253-271 | [CONFIRMADO] | S | finalize() registra breach de resolución pero no dispara SlaBreached |
| SLA-07 | low | performance | app/Services/ConversationSlaService.php:384-390 | [CONFIRMADO] | S | Caché de prioridades/horario nunca invalidada al cambiar la fuente |
| SLA-08 | low | performance | database/migrations/2026_06_23_000002_create_helpdesk_conversation_sla_breaches_table.php:29 | [CONFIRMADO] | S | Conteo de no-resueltos sin índice sobre `resolved` solo |
| SLA-09 | low | conventions | app/Services/ConversationSlaService.php:385-390 | [CONFIRMADO] | S | Query DB:: cruda para prioridades en vez de Eloquent |
| SLA-10 | low | conventions | app/Models/ConversationSlaBreach.php:68-75 | [CONFIRMADO] | S | Accessor legacy en vez de la clase Attribute |
| SLA-11 | low | conventions | app/Providers/HelpdeskSlaServiceProvider.php:62-78 | [CONFIRMADO] | S | Landing de breaches en sidebar de settings pero con prefijo de panel principal |

## Hallazgos detallados

### Medium

#### SLA-01 · [CONFIRMADO] · Permisos de settings sembrados pero nunca cableados (settings huérfanos / a medio cablear)
- **Archivo:** `modules/HelpdeskSla/database/seeders/HelpdeskSlaPermissionsSeeder.php:19-20`
- **Evidencia:** Se siembran `helpdesksla.settings.view` y `helpdesksla.settings.update`, pero no existe SettingsController, ni ruta de settings, ni vista que los referencie (grep de "settings" en `routes/` y `Controllers/` no devuelve nada). Los valores de `config/config.php` (`check_interval_minutes`, `warning_interval_minutes`) no son editables desde ninguna UI.
- **Impacto:** Los permisos muertos dan la falsa impresión de una pantalla de settings configurable; los intervalos solo pueden cambiarse editando config y limpiando caché.
- **Recomendación:** O bien eliminar los dos permisos de settings hasta que exista una pantalla, o implementar el controller/ruta/vista de settings bajo `panel/settings/helpdesksla` según `routes.md`.
- **Esfuerzo:** M

#### SLA-02 · [CONFIRMADO] · Warning percentUsed ignora horario laboral mientras los vencimientos sí lo respetan
- **Archivo:** `modules/HelpdeskSla/app/Services/ConversationSlaService.php:352-363`
- **Evidencia:** `percentUsed()` calcula elapsed/total vía `diffInSeconds(created, due)` lineal de reloj de pared, pero `addBusinessHours()` construye vencimientos sobre un calendario de horario laboral. Para una política `business_hours_only` que cruza noches/fines de semana, el porcentaje lineal diverge del consumo real de SLA.
- **Impacto:** Para políticas de horario laboral, las advertencias pueden dispararse demasiado pronto o demasiado tarde respecto al presupuesto SLA realmente consumido, socavando la semántica del umbral.
- **Recomendación:** Calcular el tiempo consumido usando el mismo calendario de horario laboral (minutos laborales transcurridos / minutos laborales totales) cuando la política sea `business_hours_only`, o documentar la aproximación lineal como intencionada.
- **Esfuerzo:** M

#### SLA-03 · [CONFIRMADO] · Registro de breach + actualización de flag no son atómicos, permitiendo breaches duplicados ante fallo parcial
- **Archivo:** `modules/HelpdeskSla/app/Services/ConversationSlaService.php:133-159`
- **Evidencia:** Dentro de `checkBreaches`, cada iteración llama a `recordBreach()` (INSERT) y luego `conversation->updateQuietly([...breached=>true])`. Si el update falla tras el insert, el flag `breached` queda en false y la siguiente ejecución programada (cada 5 min) inserta otra fila de breach para la misma conversación/tipo.
- **Impacto:** Filas de historial de breach duplicadas y broadcasts `SlaBreached` duplicados ante errores transitorios de BD.
- **Recomendación:** Envolver el `recordBreach` + `updateQuietly` por conversación en `DB::transaction()`, o fijar el flag `breached` antes/junto al insert; opcionalmente añadir un guard único en `(conversation_id, sla_type)` para breaches activos.
- **Esfuerzo:** S

#### SLA-04 · [CONFIRMADO] · Sin cobertura de tests HTTP/comando/policy; los tests de servicio existentes no pueden ejecutarse
- **Archivo:** `modules/HelpdeskSla/tests/Feature/ConversationSlaTest.php:18-25`
- **Evidencia:** Un único archivo de test Feature cubre el servicio (8 casos) pero la cabecera del archivo indica que está verificado solo estáticamente porque la BD de test compartida está bloqueada. No hay tests para `SlaBreachesController` (index/data/resolve), los dos comandos Artisan, ni `ConversationSlaBreachPolicy`. No existe factory para `ConversationSlaBreach` (sin `HasFactory`).
- **Impacto:** La autorización del controller, la forma del JSON, la validación de filtros y las rutas de salida de los comandos no están verificadas; las regresiones podrían pasar desapercibidas.
- **Recomendación:** Añadir tests Feature para los endpoints del controller y la policy, y un test Unit para los códigos de salida de los comandos; añadir factory de `ConversationSlaBreach`. Re-ejecutar cuando se reconstruya la BD de test.
- **Esfuerzo:** M

### Low

#### SLA-05 · [CONFIRMADO] · recalculate() recomputa vencimientos desde created_at, descartando la extensión por pausa acumulada
- **Archivo:** `modules/HelpdeskSla/app/Services/ConversationSlaService.php:232-246`
- **Evidencia:** Ante un cambio de prioridad, `sla_resolution_due_at` se recomputa como `addBusinessHours(created_at, hours)` sin volver a sumar `sla_paused_duration_minutes`, por lo que cualquier extensión de pausa previa se pierde.
- **Impacto:** Una conversación previamente pausada (snoozed) y luego re-priorizada pierde su extensión SLA ganada, ajustando injustamente la fecha límite.
- **Recomendación:** Tras recomputar los vencimientos en `recalculate()`, volver a sumar `sla_paused_duration_minutes` a los nuevos timestamps de vencimiento.
- **Esfuerzo:** S

#### SLA-06 · [CONFIRMADO] · finalize() registra un breach de resolución pero no dispara SlaBreached
- **Archivo:** `modules/HelpdeskSla/app/Services/ConversationSlaService.php:253-271`
- **Evidencia:** `checkBreaches()` dispara `SlaBreached` al detectar breach, pero `finalize()` (ruta de cierre tardío) inserta una fila de breach y fija `sla_resolution_breached` sin disparar `SlaBreached`, por lo que los listeners/notificaciones downstream pierden los breaches al cierre.
- **Impacto:** Los breaches detectados solo al cierre son silenciosos para cualquier suscriptor de `SlaBreached` (notificaciones, analítica).
- **Recomendación:** Disparar `SlaBreached::dispatch($conversation)` en `finalize()` cuando se registre un breach de resolución, salvo supresión intencionada (documentar si es el caso).
- **Esfuerzo:** S

#### SLA-07 · [CONFIRMADO] · Caché de mapa de prioridades / horario laboral nunca invalidada al cambiar la fuente
- **Archivo:** `modules/HelpdeskSla/app/Services/ConversationSlaService.php:384-390`
- **Evidencia:** `priorityIdForSlug` cachea `helpdesk_priorities` 300s y `businessHoursSchedule` cachea las filas de `BusinessHour` 300s, sin hook de invalidación cuando se editan prioridades u horarios laborales.
- **Impacto:** Hasta 5 minutos de mapeo de horario laboral/prioridades obsoleto tras ediciones de admin; aceptable pero no documentado.
- **Recomendación:** O bien bajar el TTL donde la corrección importe, o olvidar estas claves de caché desde el flujo de guardado de settings relevante / un observer de `BusinessHour`.
- **Esfuerzo:** S

#### SLA-08 · [CONFIRMADO] · El conteo de no-resueltos y el filtro `resolved` no están cubiertos por un índice sobre `resolved` solo
- **Archivo:** `modules/HelpdeskSla/database/migrations/2026_06_23_000002_create_helpdesk_conversation_sla_breaches_table.php:29`
- **Evidencia:** Solo existe el índice `['sla_type','resolved']` (leftmost `sla_type`). El meta del controller ejecuta `ConversationSlaBreach::unresolved()->count()` (WHERE `resolved=false`) en cada request a `data()` y el scope `unresolved()` filtra por `resolved` solo, lo que no puede usar el índice compuesto.
- **Impacto:** Full-scan en el conteo en cada request de listado; despreciable mientras la tabla sea pequeña, crece con el historial de breaches.
- **Recomendación:** Añadir un índice independiente sobre `resolved` (o reordenar el compuesto a `['resolved','sla_type']`) si el volumen de breaches crece.
- **Esfuerzo:** S

#### SLA-09 · [CONFIRMADO] · Query DB:: cruda para prioridades en vez de modelo Eloquent
- **Archivo:** `modules/HelpdeskSla/app/Services/ConversationSlaService.php:385-390`
- **Evidencia:** `DB::connection('helpdesk')->table('helpdesk_priorities')->pluck('id','slug')` evita la capa de modelo; las reglas del proyecto prefieren `Model::query()` sobre `DB::`.
- **Impacto:** Desviación de convención menor; funciona correctamente.
- **Recomendación:** Usar el query del modelo Priority de Helpdesk si está disponible; de lo contrario, dejarlo como lookup ligero documentado.
- **Esfuerzo:** S

#### SLA-10 · [CONFIRMADO] · Estilo de accessor legacy en vez de la clase Attribute
- **Archivo:** `modules/HelpdeskSla/app/Models/ConversationSlaBreach.php:68-75`
- **Evidencia:** `getTypeLabelAttribute()` usa el accessor antiguo `getXAttribute()`; `models.md` requiere la sintaxis de la clase `Attribute` de Laravel 11+ para modelos nuevos.
- **Impacto:** Inconsistencia de estilo únicamente.
- **Recomendación:** Convertir a `protected function typeLabel(): Attribute` usando `Attribute::get()`.
- **Esfuerzo:** S

#### SLA-11 · [CONFIRMADO] · La landing de breaches vive en el sidebar de settings pero usa el prefijo de panel principal
- **Archivo:** `modules/HelpdeskSla/app/Providers/HelpdeskSlaServiceProvider.php:62-78`
- **Evidencia:** `registerNav()` añade el enlace bajo el grupo de sidebar `settings`, pero la ruta está montada en el prefijo `panel/helpdesksla` con nombre `helpdesksla.` (convención de panel principal), no `panel/settings/helpdesksla` / `settings.helpdesksla.`.
- **Impacto:** Inconsistencia leve de navegación/agrupación de rutas; funcionalmente correcto.
- **Recomendación:** O bien mover el enlace al grupo de sidebar del panel principal, o reubicar la ruta bajo `panel/settings/helpdesksla` con el naming `settings.*` si pretende ser una pantalla de settings.
- **Esfuerzo:** S

## Plan de ataque priorizado

1. **Decidir la historia de settings:** implementar `panel/settings/helpdesksla` o eliminar los permisos de settings (SLA-01).
2. **Hacer consistente el porcentaje de advertencia de horario laboral** con la matemática de vencimientos por horario laboral (SLA-02).
3. **Añadir tests de controller/comando/policy + factory de breach** para que el motor quede protegido contra regresiones una vez reconstruida la BD de test (SLA-04).
4. Atomicidad del registro de breach y consistencia de eventos (SLA-03, SLA-06).

## Quick wins

- Envolver `recordBreach` + actualización del flag `breached` en `DB::transaction()` (SLA-03).
- Disparar `SlaBreached` en `finalize()` para breaches al cierre (SLA-06).
- Volver a sumar `sla_paused_duration_minutes` en `recalculate()` (SLA-05).
- Eliminar o implementar los permisos huérfanos `helpdesksla.settings.*` (SLA-01).

## Fortalezas

- Separación limpia: controller delgado delega en `ConversationSlaService`; observer/listener cableados vía class-string `Event::listen` (seguro para `event:cache`), sin closures.
- Postura de seguridad fuerte: todas las rutas tras `web+auth` más middleware `can:`, `authorize()` del controller + policy, `FormRequest authorize()` con permiso Spatie real (lowercase `{alias}.{action}`); el modelo de breach nunca se asigna masivamente desde input de request.
- Blade XSS-safe (escapado jQuery `.text().html()` para subject/customer), Font Awesome 6 únicamente, sin estilos inline, patrón de acciones por dropdown, toastr, header CSRF en AJAX.
- Las migraciones incluyen índices compuestos adecuados para los WHERE de detección de breaches, FK `cascadeOnDelete`, y `down()` completo; `$fillable`/`casts()` de Conversation incluyen correctamente todas las columnas `sla_*` (evita el gotcha conocido de `update()` que descarta campos).
- El servicio usa `updateQuietly` para evitar recursión de observer, `update()` de query builder en `markFirstResponse` para saltar eventos de modelo, `cursor()` para escaneo batch eficiente en memoria, y cachea el mapa de prioridades y el horario laboral.

## Cobertura de la auditoría

Se leyeron todos los archivos fuente PHP (20), la única vista Blade, ambas migraciones, seeder, config, refs de lang, `module.json`/`composer.json` y el archivo de test. Se verificó cruzadamente el cableado contra el módulo Helpdesk (`$fillable`/`casts()` de Conversation incluyen todas las columnas `sla_*`; los eventos `SlaBreached` y `ConversationMessageCreated` existen con las formas esperadas; `scopeOpen` existe). Los tests NO se ejecutaron (BD de test bloqueada según instrucciones) — todos los hallazgos provienen de análisis estático. No se encontraron incidencias críticas/altas; los ítems restantes son preocupaciones de completitud, convención y heurística de corrección de severidad media/baja.

## Descartados en verificación

Ninguno. No hubo hallazgos refutados durante la verificación. No había hallazgos critical/high que verificar.
