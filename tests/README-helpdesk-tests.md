# Tests del ecosistema Helpdesk

## Cómo correrlos

```bash
# Todos los módulos Helpdesk*, por módulo, con resumen y exit code agregado:
composer test:helpdesk            # = bash scripts/run-helpdesk-tests.sh

# Un solo módulo:
scripts/run-helpdesk-tests.sh Tickets Campaigns   # acepta con o sin prefijo "Helpdesk"

# Directo con phpunit:
vendor/bin/phpunit modules/HelpdeskTickets/tests --no-coverage
```

En CI (`.github/workflows/ci.yml`, job `helpdesk-tests`) cada módulo corre como
step separado para que un fatal en uno no oculte el resto.

## Base de datos de test

- `phpunit.xml` fuerza `DB_DATABASE=system_test_pristine` (MariaDB local, root)
  tanto para la conexión por defecto (`mariadb`) como para `helpdesk`.
- El archivo `system_test_clean` en la raíz es un **snapshot SQLite antiguo y no
  se usa** por phpunit.
- La BD NO se migra en cada run: los tests usan `DatabaseTransactions` (no
  `RefreshDatabase`, que rompe por el orden de FKs entre módulos) contra el
  snapshot pre-migrado. Todo dato que un test necesite debe crearlo el propio
  test (o su TestCase base) de forma idempotente — nunca asumir filas
  pre-sembradas.

## Convenciones

- **Transacciones**: `use DatabaseTransactions;` +
  `protected array $connectionsToTransact = ['mariadb', 'helpdesk'];` para que
  el rollback cubra ambas conexiones.
- **Lock wait timeouts (~50s) por FKs entre conexiones**: `mariadb` y
  `helpdesk` apuntan a la MISMA BD; si el test transacciona ambas, un FK de una
  tabla "helpdesk" hacia una fila creada por la otra conexión espera un commit
  que nunca llega (auto-interbloqueo). Para tests que crean filas en una
  conexión y las referencian por FK desde la otra, usa
  `Modules\HelpdeskTickets\Tests\Concerns\SharesHelpdeskPdo` (comparte el PDO y
  transacciona solo `mariadb`). Ojo: con ese trait el código bajo test NO debe
  abrir transacciones explícitas sobre `helpdesk` (haría commit implícito de la
  transacción compartida).
- **Roles**: la tabla `roles` del snapshot está vacía. Usa
  `Tests\Concerns\SeedsHelpdeskRoles` (`$this->seedHelpdeskRoles()` en `setUp()`)
  en vez de `Role::firstOrCreate` ad-hoc. `Modules\Helpdesk\Tests\HelpdeskTestCase`
  ya lo hace, además de sembrar `PermissionsSeeder` del módulo Helpdesk.
- **Permisos core**: `Tests\Concerns\SeedsCorePermissions` siembra el mínimo que
  consultan las policies (`hasPermissionTo` lanza si el permiso no existe).
- **Sanctum**: el snapshot no traía `personal_access_tokens`;
  `Tests\Concerns\EnsuresSanctumTable` la crea si falta (patrón CREATE TABLE IF
  NOT EXISTS + resincronización de la transacción tras el COMMIT implícito del
  DDL en MariaDB).
- **DDL en tests**: cualquier DDL sobre MariaDB hace COMMIT implícito y
  desincroniza `DatabaseTransactions`; tras el DDL hay que `DB::purge()` +
  `beginTransaction()` (ver `ensurePermissionTables()` en CampaignsFeatureTest).
- **Datos residuales**: la BD es compartida entre runs; los asserts deben ser
  robustos a filas preexistentes (filtrar por ids creados en el test, no
  contar totales globales).

## Fallos conocidos (WIP de otros desarrolladores — no "arreglar")

- `modules/HelpdeskSocial/app/Jobs/**`: en refactor; sus tests con TypeError
  quedan rojos a propósito.
- `Managers/CustomersController` y `Managers/ConversationsController` del core
  Helpdesk: en curso; los tests que dependen de ellos pueden fallar.
- Asignación automática y features de AI (HelpdeskAgents/AiAgent*): otros
  agentes trabajan ahí.
