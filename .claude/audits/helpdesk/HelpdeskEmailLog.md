# Auditoría — HelpdeskEmailLog

> Fecha: 2026-06-29 · Health score: 88/100 · Estado: solid-minor-issues

**Resumen:** Registro centralizado de auditoría de emails: captura via eventos MessageSending/MessageSent, redacción de cuerpos sensibles, preview XSS-safe en iframe sandbox, purga programada y reenvío encolado. Código maduro, seguro y bien testeado; solo defectos menores de correlación y cache. El diagnóstico confirma una base sólida: XSS mitigado en origen, búsqueda sin inyección, índices bien planificados y convenciones del proyecto respetadas. Los hallazgos se concentran en correlación ambigua de envíos (único riesgo de fiabilidad real del log) y detalles de invalidación de cache, búsqueda y contrato.

## Tabla de hallazgos

| ID | Sev | Categoría | Archivo:línea | Estado verif. | Esfuerzo | Título |
|----|-----|-----------|---------------|---------------|----------|--------|
| EL-1 | medium | quality | modules/HelpdeskEmailLog/app/Listeners/LogEmailSent.php:92-122 | [CONFIRMADO] | M | Correlación de envío puede marcar como 'failed' emails realmente enviados |
| EL-2 | low | performance | modules/HelpdeskEmailLog/app/Http/Controllers/EmailLogController.php:106-113 | [CONFIRMADO] | S | Borrado masivo y purga no invalidan los caches de stats/módulos |
| EL-3 | low | quality | modules/HelpdeskEmailLog/app/Listeners/Concerns/InspectsMailMessage.php:148-156 | [CONFIRMADO] | S | Listener encolado lee Auth para el causer en contexto de cola |
| EL-4 | low | performance | modules/HelpdeskEmailLog/app/Http/Controllers/EmailLogController.php:195-201 | [CONFIRMADO] | M | Búsqueda combina FULLTEXT MATCH con LIKE de comodín inicial sobre la misma columna |
| EL-5 | low | conventions | modules/HelpdeskEmailLog/routes/web.php:25 | [CONFIRMADO] | S | Ruta de borrado masivo se desvía del patrón bulk-action del proyecto |
| EL-6 | low | quality | modules/HelpdeskEmailLog/app/Mail/AddsEmailLogHeaders.php:24-28 | [CONFIRMADO] | S | AddsEmailLogHeaders puede pasar null a cabeceras de texto para entidades sin tipo/id |

## Hallazgos detallados

### EL-1 · [CONFIRMADO] · medium · quality

**Título:** Correlación de envío puede marcar como 'failed' emails realmente enviados
**Archivo:** modules/HelpdeskEmailLog/app/Listeners/LogEmailSent.php:92-122

**Evidencia:** `findQueued()` solo transiciona la fila queued→sent si encuentra EXACTAMENTE un candidato por `message_id` o por `subject`+`recipients`. Si `message_id` falta y hay >1 candidato, devuelve `null` → se crea una fila 'sent' nueva y la(s) fila(s) 'queued' originales quedan huérfanas. Luego `PruneEmailLogsCommand::markStaleQueuedAsFailed` las marca como 'failed' con "No sending confirmation received" aunque el email SÍ se envió.

**Impacto:** Auditoría engañosa: emails entregados aparecen como fallidos y/o se duplican filas, degradando la fiabilidad del log que es el propósito del módulo.

**Recomendación:** El camino normal (`LogEmailQueued` asigna Message-ID sincrónicamente) cubre el caso; documentar que el fallback es best-effort y/o, ante >1 candidato, marcar todos los queued coincidentes como sent en vez de crear fila nueva. Considerar no crear duplicado si existen candidatos ambiguos.

---

### EL-2 · [CONFIRMADO] · low · performance

**Título:** Borrado masivo y purga no invalidan los caches de stats/módulos
**Archivo:** modules/HelpdeskEmailLog/app/Http/Controllers/EmailLogController.php:106-113

**Evidencia:** `bulkDestroy()` usa `EmailLog::query()->whereIn('uid',...)->delete()` (mass delete) y `PruneEmailLogsCommand` usa `->delete()`/`->update()` en query builder; estas operaciones NO disparan los eventos de modelo created/updated/deleted que invalidan `helpdeskemaillog:stats` (TTL 60s) y `helpdeskemaillog:modules` (TTL 10min) en `EmailLog::booting()`.

**Impacto:** Tras un borrado masivo o purga, las stats y el desplegable de módulos muestran datos obsoletos hasta que expira el TTL (hasta 10 min para módulos). Solo cosmético, se auto-corrige.

**Recomendación:** Llamar `Cache::forget('helpdeskemaillog:stats')` y `Cache::forget('helpdeskemaillog:modules')` explícitamente tras `bulkDestroy()` y en el comando de purga.

---

### EL-3 · [CONFIRMADO] · low · quality

**Título:** Listener encolado lee Auth para el causer en contexto de cola
**Archivo:** modules/HelpdeskEmailLog/app/Listeners/Concerns/InspectsMailMessage.php:148-156

**Evidencia:** `currentCauser()` usa `Auth::user()`/`Auth::id()`, pero `LogEmailSent` implementa `ShouldQueue` y se ejecuta en worker sin sesión. En el camino fallback (crear fila 'sent' nueva) el causer siempre queda `null`.

**Impacto:** Pérdida del autor en filas creadas por el fallback de `LogEmailSent`. El camino normal (`LogEmailQueued`, sincrónico) sí captura el causer, por lo que el impacto es marginal.

**Recomendación:** Capturar `causer_id` en `LogEmailQueued` (ya lo hace) y no recalcularlo en la cola; o pasar el causer serializado al listener si se necesita en el fallback.

---

### EL-4 · [CONFIRMADO] · low · performance

**Título:** Búsqueda combina FULLTEXT MATCH con LIKE de comodín inicial sobre la misma columna
**Archivo:** modules/HelpdeskEmailLog/app/Http/Controllers/EmailLogController.php:195-201

**Evidencia:** El WHERE de búsqueda hace `orWhereRaw('MATCH(recipients_index) AGAINST (?)')` OR `orWhere('recipients_index','like','%...%')`. El LIKE con comodín inicial es no-sargable y, al estar en OR con el resto de columnas, neutraliza la ventaja del índice FULLTEXT, forzando escaneo.

**Impacto:** En tablas grandes la búsqueda libre puede ser lenta; mitigado por paginación y por `LIST_COLUMNS`, pero el FULLTEXT añadido casi no aporta dado el OR LIKE redundante.

**Recomendación:** Decidir una sola estrategia para `recipients_index` (FULLTEXT booleano para tokens completos, o LIKE), y reservar FULLTEXT para términos de palabra completa; o separar la búsqueda por destinatario del resto.

---

### EL-5 · [CONFIRMADO] · low · conventions

**Título:** Ruta de borrado masivo se desvía del patrón bulk-action del proyecto
**Archivo:** modules/HelpdeskEmailLog/routes/web.php:25

**Evidencia:** Las reglas de rutas indican "SIEMPRE incluir ruta bulk-action" como POST `/bulk-action` con payload `{action, ids}`. Aquí es DELETE `/bulk` name('bulk-destroy') con payload `{uids}`.

**Impacto:** Inconsistencia con el resto de módulos; ninguna implicación funcional o de seguridad (es RESTful y está protegida por FormRequest manage).

**Recomendación:** Opcional: alinear con el estándar o documentar la excepción si se prefiere el verbo DELETE semántico.

---

### EL-6 · [CONFIRMADO] · low · quality

**Título:** AddsEmailLogHeaders puede pasar null a cabeceras de texto para entidades sin tipo/id
**Archivo:** modules/HelpdeskEmailLog/app/Mail/AddsEmailLogHeaders.php:24-28

**Evidencia:** El contrato `TracksEmailLog` declara `getEmailLogEntityType(): string` y `getEmailLogEntityId(): int|string` (no nullables), pero un Mailable que represente un envío sin entidad concreta no tiene un valor natural; se fuerza `(string)` sobre el id y se asigna `entity_type` aunque no aplique.

**Impacto:** Riesgo bajo: si una implementación devuelve cadena vacía se registra `entity_type=''` y un enlace de entidad inservible. Solo afecta a Mailables que implementen el contrato.

**Recomendación:** Permitir tipos nullable en el contrato y omitir las cabeceras X-Entity-* cuando falten, o documentar que el contrato exige siempre entidad real.

## Plan de ataque priorizado

1. **EL-1 (medium)** — Resolver la correlación ambigua de `LogEmailSent` para que emails enviados no acaben marcados 'failed' por la purga. Es el único hallazgo que afecta la fiabilidad del log, que es el propósito del módulo.
2. **EL-2 (low)** — Invalidar caches en borrado masivo y purga (`Cache::forget` de stats/modules).
3. **EL-4 (low)** — Limpiar la estrategia de búsqueda sobre `recipients_index` para no neutralizar el FULLTEXT.
4. **EL-3, EL-6, EL-5 (low)** — Pulir captura de causer en cola, nullabilidad del contrato y, opcionalmente, alineación de la ruta de bulk delete.

## Quick wins

- Invalidar `Cache::forget('helpdeskemaillog:stats'/'helpdeskemaillog:modules')` tras `bulkDestroy()` y en `PruneEmailLogsCommand` (EL-2).
- Eliminar el `orWhere` LIKE redundante sobre `recipients_index` o el MATCH AGAINST, evitando neutralizar el FULLTEXT (EL-4).

## Fortalezas

- **XSS mitigado correctamente:** el cuerpo del email se renderiza en iframe con `sandbox` SIN `allow-scripts` ni `allow-same-origin` + `referrerpolicy` no-referrer (preview.blade.php:33-36), evitando ejecución de JS de emails maliciosos sin depender de sanitización.
- **Redacción de cuerpos sensibles bien diseñada** (3 mecanismos: contrato `RedactsEmailLogBody`, lista de clases, lista de módulos) en InspectsMailMessage.php:64-82; cabeceras internas X-* se eliminan ANTES de cualquier op de BD para que nunca lleguen al destinatario (LogEmailQueued.php:35-40).
- **Búsqueda LIKE con `addcslashes($search,'%_\\')`** (EmailLogController.php:193) y MATCH AGAINST parametrizado: sin inyección; `selectRaw` usa solo literales fijos + binding para fechas.
- **Cobertura de tests amplia:** 5 ficheros Feature, ~40 métodos cubriendo auth, permisos, filtros, export CSV, resend, bulk delete, tracking, redacción y purga.
- **Índices bien planificados:** módulo, status, sent_at, created_at, (entity_type,entity_id), (module,created_at) compuesto y FULLTEXT en recipients_index; listados con select de `LIST_COLUMNS` excluyendo columnas body pesadas.
- **Convenciones respetadas:** rutas `panel/helpdeskemaillog` y `panel/settings/helpdeskemaillog`, permisos `{alias}.{action}` en minúsculas, `casts()` método, fillable explícito, Policy registrada con `Gate::policy`, Form Requests con `messages()`/`attributes()` en español, migraciones idempotentes con `down()`.

## Cobertura de la auditoría

Cobertura completa: leídos los 17 ficheros PHP de `app/` (ServiceProvider, 2 controllers, model, 2 listeners + trait, job, command, mail trait, 2 contracts, enum, policy, 2 form requests), las 3 migraciones, config, los 2 seeders, factory (cabecera), los 3 blades y `routes/web.php`; verificada existencia/cobertura de tests (5 ficheros Feature, ~40 métodos) y nombres de métodos. NO se ejecutó la suite (BD de test bloqueada por orden de migraciones cruzadas); análisis 100% estático. No se inspeccionaron en profundidad `lang/` ni los cuerpos completos de factory/tests más allá de su firma.

Verificación de hallazgos: sin hallazgos critical/high que verificar. Los 6 hallazgos del cuerpo quedan **[CONFIRMADO]** con su severidad original tras revisión estática.

## Descartados en verificación

Ninguno. Todos los hallazgos identificados fueron confirmados en la verificación; no hubo hallazgos refutados ni ajustes de severidad.
