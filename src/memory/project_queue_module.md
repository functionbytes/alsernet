---
name: Queue module implementation
description: Complete refactor and implementation of the Queue module — what was built and key decisions
type: project
---

El módulo Queue fue completamente refactorizado e implementado desde cero.

**Why:** El módulo estaba casi vacío (solo un ServiceProvider que sobreescribía el config de queue con el driver incorrecto `database` en lugar de `redis`).

**What was built:**
- `QueueServiceProvider` reescrito (patrón Helpdesk: vistas, config, comandos, navegación)
- `RouteServiceProvider` nuevo — rutas bajo `settings/queue`, nombre `settings.queue.*`, middleware `web,auth,role:super-admin`
- `config/queue_module.php` — cargado bajo clave `queue_module` (NO `queue`) para evitar sobreescribir el config principal
- `BaseJob` en `Modules\Queue\Jobs\BaseJob` — extiende `Core\Jobs\Base`, tries=3, backoff=30, helper `throttle()` para Spatie RateLimited
- Comandos: `queue:retry-all`, `queue:purge-failed`, `queue:list`
- `QueueDashboardController` con endpoints: stats, failedJobs, pendingJobs, retryJob, deleteFailedJob, retryAll
- Dashboard en `/settings/queue` con DevExpress DataGrids y auto-refresh
- NavService registrado bajo "Gestión de colas"
- `config/horizon.php` actualizado: supervisores `supervisor-sla` y `supervisor-helpdesk` para colas no cubiertas, waits para 6 colas
- Tests: 8/8 pasando

**How to apply:** Al crear nuevos jobs en cualquier módulo, pueden extender `Modules\Queue\Jobs\BaseJob` para obtener tries=3, backoff y helper throttle. El dashboard está en `/settings/queue`.
