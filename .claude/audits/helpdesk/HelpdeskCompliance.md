# Auditoría — HelpdeskCompliance

> Fecha: 2026-06-29 · Health score: 82/100 · Estado: solid-minor-issues

**Resumen:** Orquestador de cascada GDPR limpio y bien acotado que extiende el borrado de clientes del core hacia Tickets y sesiones de ChatFlow. La seguridad y las convenciones son sólidas; los huecos están en el manejo de fallos, una función de exportación a medio cablear y handlers sin tests. No se detectaron hallazgos critical ni high; 3 medium y 5 low, todos confirmados en la verificación estática.

## Tabla de hallazgos

| ID | Sev | Categoría | Archivo:línea | Estado verif. | Esfuerzo | Título |
|----|-----|-----------|---------------|---------------|----------|--------|
| HC-01 | medium | wiring | app/Listeners/RunComplianceCascade.php:27-50 | [CONFIRMADO] | M | El fallo de la cascada nunca se registra; el estado 'failed' es código muerto |
| HC-02 | medium | wiring | app/Models/ComplianceRequest.php:12 | [CONFIRMADO] | M | Tipo de solicitud 'export' a medio cablear (declarado en todas partes, producido en ninguna) |
| HC-03 | medium | tests | tests/Feature/ComplianceCascadeTest.php:1-66 | [CONFIRMADO] | M | Los handlers de cascada no tienen tests unitarios |
| HC-04 | low | quality | app/Listeners/RunComplianceCascade.php:27-50 | [CONFIRMADO] | S | El registro de auditoría se crea fuera de la transacción de la cascada |
| HC-05 | low | conventions | database/seeders/HelpdeskCompliancePermissionsSeeder.php:18 | [CONFIRMADO] | S | Permiso helpdeskcompliance.manage sembrado pero nunca usado |
| HC-06 | low | quality | app/Models/ComplianceRequest.php:46-49 | [CONFIRMADO] | S | Código muerto: scopeOfType y policy view() sin usar |
| HC-07 | low | conventions | app/Providers/HelpdeskComplianceServiceProvider.php:55-71 | [CONFIRMADO] | S | El prefijo/nombre de ruta se desvía de la convención settings pese a estar en el nav de settings |
| HC-08 | low | quality | app/Listeners/RunComplianceCascade.php:43 | [CONFIRMADO] | S | La atribución del solicitante se pierde silenciosamente en contextos no-HTTP |

## Hallazgos detallados

### Medium

#### HC-01 · [CONFIRMADO] El fallo de la cascada nunca se registra; el estado 'failed' es código muerto
- **Archivo:** `modules/HelpdeskCompliance/app/Listeners/RunComplianceCascade.php:27-50`
- **Evidencia:** La migración define `status` con valores `pending|processing|completed|failed` (migration línea 17), pero el listener hardcodea `'status' => 'completed'` (línea 42) sin `try/catch`. Si un handler lanza una excepción dentro de la transacción, esta se propaga después de que el borrado del core ya se ejecutó, y NO se escribe ninguna fila `ComplianceRequest` — se pierde el rastro de auditoría justo en el caso que más lo necesita.
- **Impacto:** Un borrado GDPR parcialmente fallido no deja registro trazable, lo que anula el propósito declarado del módulo (cascada auditable).
- **Recomendación:** Envolver la cascada en `try/catch`: persistir un `ComplianceRequest` con estado `'failed'` y el error en `result_summary` ante una excepción, y luego relanzar. Usar las constantes de estado existentes en lugar de magic strings.
- **Esfuerzo:** M

#### HC-02 · [CONFIRMADO] Tipo de solicitud 'export' a medio cablear (declarado en todas partes, producido en ninguna)
- **Archivo:** `modules/HelpdeskCompliance/app/Models/ComplianceRequest.php:12`
- **Evidencia:** `TYPE_EXPORT='export'` existe, `RequestIndexRequest` permite `in:export,delete_soft,delete_hard` (`RequestIndexRequest.php:17`) y el Blade ofrece una opción de filtro 'Exportacion' (`index.blade.php:17`). Pero el único escritor (`RunComplianceCascade`) solo emite `delete_soft`/`delete_hard` — ningún flujo de código crea jamás una solicitud de tipo export.
- **Impacto:** Los usuarios obtienen un filtro y un tipo que nunca puede devolver resultados; sugiere una funcionalidad inacabada.
- **Recomendación:** Implementar el registro de solicitudes de exportación (p. ej. escuchar el evento de exportación GDPR del core) o eliminar el tipo/filtro/validación de export hasta que se implemente.
- **Esfuerzo:** M

#### HC-03 · [CONFIRMADO] Los handlers de cascada no tienen tests unitarios
- **Archivo:** `modules/HelpdeskCompliance/tests/Feature/ComplianceCascadeTest.php:1-66`
- **Evidencia:** Solo existen 3 tests Feature (dispatch del evento + el listener registra la solicitud), y están bloqueados por DB según la propia NOTE del archivo. La lógica de borrado del core en `TicketComplianceHandler` (redact/forceDelete) y `ChatflowComplianceHandler` (sanitizar contexto vs eliminar sessions+executions) tiene cobertura cero — y son las rutas críticas para GDPR.
- **Impacto:** El comportamiento real de borrado de datos (la parte con consecuencias legales) está sin verificar.
- **Recomendación:** Agregar tests unitarios para ambos handlers cubriendo los modos hard vs soft y el corto-circuito de `conversationIds` vacío; afirmar la forma del summary devuelto y los efectos en BD con fakes/in-memory donde la BD compartida no esté disponible.
- **Esfuerzo:** M

### Low

#### HC-04 · [CONFIRMADO] El registro de auditoría se crea fuera de la transacción de la cascada
- **Archivo:** `modules/HelpdeskCompliance/app/Listeners/RunComplianceCascade.php:27-50`
- **Evidencia:** `DB::connection('helpdesk')->transaction()` (línea 27) envuelve solo las llamadas a los handlers; `ComplianceRequest::create()` (línea 39) y `AuditLogService::record()` (línea 52) corren después. Si `create()` falla, la cascada ya está committeada sin registro.
- **Impacto:** Hueco de atomicidad en caso límite entre el borrado y su fila de auditoría.
- **Recomendación:** Mover el `ComplianceRequest::create()` dentro de la transacción (o registrar el estado atómicamente junto a la cascada).
- **Esfuerzo:** S

#### HC-05 · [CONFIRMADO] Permiso helpdeskcompliance.manage sembrado pero nunca usado
- **Archivo:** `modules/HelpdeskCompliance/database/seeders/HelpdeskCompliancePermissionsSeeder.php:18`
- **Evidencia:** El seeder crea `helpdeskcompliance.manage`, pero la policy (`ComplianceRequestPolicy`) y las rutas solo verifican `helpdeskcompliance.view`. Ningún código referencia `'manage'`.
- **Impacto:** Permiso muerto; confusión menor sobre las capacidades.
- **Recomendación:** Eliminarlo o cablearlo a una acción manage real (p. ej. reintentar/reproducir la cascada).
- **Esfuerzo:** S

#### HC-06 · [CONFIRMADO] Código muerto: scopeOfType y policy view() sin usar
- **Archivo:** `modules/HelpdeskCompliance/app/Models/ComplianceRequest.php:46-49`
- **Evidencia:** `scopeOfType()` nunca se llama — el controller filtra con un `where('type', ...)` inline (`ComplianceController.php:30`). `ComplianceRequestPolicy::view()` (`Policy.php:18`) también está sin usar ya que no hay ruta show.
- **Impacto:** Ruido de mantenimiento / duplicación menor.
- **Recomendación:** Usar `scopeOfType()` en el filtro del controller (o eliminarlo); eliminar `view()` hasta que exista una ruta de registro único.
- **Esfuerzo:** S

#### HC-07 · [CONFIRMADO] El prefijo/nombre de ruta se desvía de la convención settings pese a estar en el nav de settings
- **Archivo:** `modules/HelpdeskCompliance/app/Providers/HelpdeskComplianceServiceProvider.php:55-71`
- **Evidencia:** `registerNav()` agrega el enlace bajo la barra lateral 'settings' (línea 66), pero las rutas se montan con prefijo `panel/helpdeskcompliance` y nombre `helpdeskcompliance.` (líneas 56-57 + `routes/web.php:12`), no la convención settings del proyecto `panel/settings/{alias}` + `settings.{alias}` (según `.claude/rules/routes.md`).
- **Impacto:** Inconsistente con la convención documentada de rutas settings.
- **Recomendación:** Mover las rutas a `panel/settings/helpdeskcompliance` con nombre `settings.helpdeskcompliance.`, o tratarla como vista de auditoría de manager (aceptable) y documentar la excepción.
- **Esfuerzo:** S

#### HC-08 · [CONFIRMADO] La atribución del solicitante se pierde silenciosamente en contextos no-HTTP
- **Archivo:** `modules/HelpdeskCompliance/app/Listeners/RunComplianceCascade.php:43`
- **Evidencia:** `'requested_by' => auth()->id()` — cuando el borrado GDPR de origen corre desde un comando de consola, el scheduler o un job del sistema, `auth()->id()` es null y el actor se pierde (la columna es nullable).
- **Impacto:** El rastro de auditoría puede omitir quién disparó el borrado en flujos no-web.
- **Recomendación:** Llevar el actor en el evento `CustomerGdprDeleted` (o usar un identificador de sistema como fallback) en lugar de depender del `auth()` scopeado a la request dentro de un listener.
- **Esfuerzo:** S

## Plan de ataque priorizado

1. **HC-01 (medium):** Agregar `try/catch` en `RunComplianceCascade` para persistir un `ComplianceRequest` 'failed' ante error de un handler.
2. **HC-02 (medium):** Terminar o eliminar el tipo de solicitud export que está declarado en modelo/validación/UI pero nunca producido.
3. **HC-03 (medium):** Agregar tests unitarios para `TicketComplianceHandler` y `ChatflowComplianceHandler` cubriendo las rutas de borrado hard/soft.
4. **HC-04, HC-05, HC-06, HC-07, HC-08 (low):** Limpiezas de atomicidad, permiso muerto, código muerto, convención de rutas y atribución de actor.

## Quick wins

- Mover `ComplianceRequest::create()` dentro de la transacción de BD (HC-04).
- Eliminar o cablear el permiso `helpdeskcompliance.manage` sin usar (HC-05).
- Usar `scopeOfType()` en el controller y eliminar el `view()` de la policy sin usar (HC-06).

## Fortalezas

- **Seguridad correcta:** el controller llama a `authorize()`, las rutas llevan `web`+`auth` más `can:helpdeskcompliance.view`, la policy está registrada vía `Gate::policy` en el ServiceProvider, y los permisos siguen la convención lowercase `{alias}.{action}`.
- **Sin XSS:** todo valor derivado de usuario/cliente en el Blade se escapa con `$('<div>').text(x).html()` antes de insertarse; solo iconos FA6, sin estilos inline, sin select2 mal configurado.
- **Performance sano:** `data()` hace eager-load de `customer` (sin N+1), pagina con un `per_page` capado (`max:100`), y la migración añade índices en `customer_id`, `[type,status]` y `created_at`.
- **Arquitectura correcta:** desacoplado vía el evento del core `CustomerGdprDeleted`; los handlers están gateados por `Module::isEnabled()`+`class_exists()` para que la cascada degrade con gracia; la ejecución síncrona es intencional y documentada (el cliente puede haber sido hard-deleted, así que un re-fetch en cola fallaría).
- **Modelo conforme a reglas:** `$fillable` explícito, método `casts()`, relación `BelongsTo` tipada, conexión `helpdesk` dedicada.

## Cobertura de la auditoría

Revisión estática completa de los 16 archivos PHP + 1 Blade (controller, form request, listener, modelo, policy, service provider, 2 handlers, migración, seeder, rutas, config, module.json, composer.json, test) más la única vista. Los contratos cross-módulo se verificaron leyendo la firma del evento del core `CustomerGdprDeleted` y greppeando los modelos `ChatFlowSession`/`ChatFlowExecution`/`Ticket` (confirmado: `ChatFlowSession`/`Execution` no usan SoftDeletes, así que el hard delete elimina filas de verdad — sin falso positivo; `Ticket` usa SoftDeletes y el handler usa correctamente `withTrashed()`+`forceDelete` para el modo hard). Los tests NO se ejecutaron (BD de test bloqueada según instrucciones); la cobertura se evaluó leyendo el archivo de test. La omisión de FKs en `customer_id`/`requested_by` en la migración es intencional (la fila del cliente desaparece tras el hard delete), por lo que no se marca.

## Descartados en verificación

Ninguno. No hubo hallazgos refutados; tampoco hallazgos critical/high que verificar (verificación adversarial no requerida).
