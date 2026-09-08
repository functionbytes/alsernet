<?php

namespace Tests;

use App\Models\User;
use Illuminate\Database\Events\ConnectionEstablished;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Modules\Core\Http\Middleware\VerifyCsrfToken;
use Modules\Helpdesk\Models\Setting;

abstract class TestCase extends BaseTestCase
{
    /**
     * Sin esto, TODA petición POST/PUT/PATCH/DELETE del cliente de test
     * recibe 419 (confirmado 29-ago-2026: el proyecto no desactivaba CSRF
     * para tests en ningún sitio central — varios archivos lo parcheaban
     * suelto, uno por uno, con esta misma llamada). El objetivo de un test
     * HTTP es la lógica del controller, no el intercambio real de token
     * CSRF; eso lo cubre el propio framework/JS del navegador.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);

        // phpunit.xml fuerza CACHE_STORE=array con force="true", pero eso no
        // gana en Docker (confirmado 8-sep-2026, mismo patrón que los "5
        // vars" de DB_* documentados aparte): getenv('CACHE_STORE') sí
        // reporta 'array' dentro del proceso de PHPUnit, pero
        // config('cache.default') resuelve 'redis' — el Redis REAL,
        // compartido con los workers y el propio entorno de desarrollo. Sin
        // este config(), Cache::flush() de aquí abajo vaciaba ese Redis
        // compartido en cada corrida de tests, y cada Setting::get()/set()
        // durante un test competía con lo que ese mismo Redis tuviera
        // cacheado en ese instante (confirmado con
        // ChatFlowIntegrationToggleTest/IntegrationsControllerTest: distintas
        // claves de integración salían en '1' al azar, según qué proceso
        // externo hubiera tocado esa clave último). Forzarlo aquí no cubre
        // el arranque de la propia aplicación (los ServiceProvider::boot()
        // que corren dentro de parent::setUp(), antes de esta línea, ya
        // leyeron del Redis real) pero sí aísla el resto del test.
        //
        // El mismo desajuste getenv()-vs-config() se confirmó también en
        // SESSION_DRIVER, MAIL_MAILER, APP_ENV y BROADCAST_CONNECTION —
        // deliberadamente NO tocados aquí: cada uno tiene su propio radio de
        // impacto sobre una suite de cientos de tests y merece su propia
        // revisión, no un cambio de golpe.
        config(['cache.default' => 'array']);

        // QUEUE_CONNECTION sufre el mismo desajuste y aquí SÍ hace falta: un
        // listener con ShouldQueue (ej. LogActivityOnConversationTagAdded)
        // se despachaba al Redis REAL — 'helpdesk-events', la cola que
        // atienden los workers en vivo — en vez de ejecutarse en el proceso
        // del test. El test nunca veía su efecto (ConversationsControllerTest
        // > adding tag creates activity item: el ConversationItem de
        // actividad salía null) y, aparte, el job llegaba a un worker real
        // apuntando a una conversación que la transacción del test ya había
        // revertido para cuando le tocara turno.
        config(['queue.default' => 'sync']);

        // El RateLimiter (throttle:*) usa el cache de arriba, así que sin
        // este flush los contadores se acumulan entre tests de toda la
        // suite y, al correrla completa, rutas públicas con throttle bajo
        // (helpdesk-feedback, ticket-forms, widget) empiezan a devolver 429
        // en tests que no tienen nada que ver con rate-limiting (confirmado
        // 30-ago-2026, 23 fallos en HelpdeskTickets desaparecieron con este
        // fix).
        Cache::flush();

        // Cache::flush() no llega a la memoria de PROCESO de
        // Helpdesk\Models\Setting::get() (self::$memo): un `??=` plano sin
        // TTL que sobrevive por diseño al Cache::forget() de Setting::set()
        // dentro del mismo proceso. En producción eso lo cubren los
        // listeners de terminating/cola de HelpdeskServiceProvider; aquí,
        // sin esos ganchos (ni request ni job real de por medio), un test
        // que llama Setting::set() deja el valor fijo para el resto de la
        // suite en el mismo proceso de PHPUnit — confirmado con
        // ChatFlowIntegrationToggleTest: el segundo test heredaba el "0"
        // que el primero acababa de fijar y revertir en su transacción.
        if (class_exists(Setting::class)) {
            Setting::forgetMemo();
        }

        // Ningún sitio del proyecto configura un timeout por defecto para
        // Http:: — cualquier test que olvide Http::fake() y golpee una URL
        // real puede colgarse indefinidamente (confirmado 30-ago-2026: una
        // corrida completa quedó colgada 14+ horas sin avisar). Esto es
        // solo una red de seguridad para tests: si el test SÍ fakea la
        // llamada, esto no aplica; si no la fakea, falla rápido (5s) en vez
        // de colgar el proceso entero.
        Http::globalOptions(['connect_timeout' => 3, 'timeout' => 5]);

        $this->guardAgainstMassDeletesOnRealDatabase();
    }

    /**
     * Bases de datos que NO son de test y que la suite no debe poder vaciar.
     *
     * phpunit.xml fuerza DB_CONNECTION=sqlite / DB_DATABASE=:memory:, pero eso
     * no cubre nada: (1) en Docker las variables del contenedor ganan al
     * force="true" del XML, y (2) la conexión 'helpdesk' se resuelve con sus
     * propias DB_*_HELPDESK y apunta a la base real pase lo que pase con
     * DB_CONNECTION. Cualquier test que use DB::connection('helpdesk')
     * escribe en datos reales.
     */
    private const PROTECTED_DATABASES = ['webadmin'];

    /**
     * Impide que un test borre tablas enteras de la base real.
     *
     * El 2-sep-2026 la suite de HelpdeskTickets dejó a cero
     * helpdesk_tickets, _ticket_mails, _ticket_history, _ticket_notes y
     * _ticket_links. El patrón que lo provoca es un `DELETE FROM tabla` sin
     * WHERE en el setUp() de un test para "empezar limpio": dentro de una
     * transacción de DatabaseTransactions se revierte, pero basta con que la
     * transacción no llegue a cubrir esa conexión —o con que algo haga commit
     * implícito— para que el borrado quede confirmado.
     *
     * Esta guarda no juzga si el test está bien escrito: bloquea la operación
     * concreta que destruye datos (DELETE sin WHERE y TRUNCATE) cuando se
     * lanza contra una base protegida SIN transacción abierta. Un test que sí
     * abre transacción sigue funcionando igual que hasta ahora.
     */
    protected function guardAgainstMassDeletesOnRealDatabase(): void
    {
        $guard = function (string $query, array $bindings, $connection): void {
            if (! in_array($connection->getDatabaseName(), self::PROTECTED_DATABASES, true)) {
                return;
            }

            $normalized = ltrim(preg_replace('/\s+/', ' ', $query) ?? '');

            $isTruncate = (bool) preg_match('/^truncate\b/i', $normalized);
            // DELETE sin WHERE: lo que vacía una tabla de una vez. Un DELETE
            // con WHERE es lo que hace cualquier test normal al limpiar lo suyo.
            $isMassDelete = (bool) preg_match('/^delete from\b/i', $normalized)
                && ! preg_match('/\bwhere\b/i', $normalized);

            if (! $isTruncate && ! $isMassDelete) {
                return;
            }

            // Con una transacción abierta el borrado se revierte al terminar
            // el test: es el uso legítimo y se deja pasar.
            if ($connection->transactionLevel() > 0) {
                return;
            }

            throw new \RuntimeException(
                'Test bloqueado: iba a vaciar una tabla de la base real "'
                .$connection->getDatabaseName().'" sin transacción abierta.'
                .PHP_EOL.'Consulta: '.mb_substr($normalized, 0, 200)
                .PHP_EOL.'Añade `use DatabaseTransactions;` y '
                .'`protected array $connectionsToTransact = [\'mariadb\', \'helpdesk\'];` '
                .'al test, o borra solo las filas que el propio test creó (DELETE ... WHERE).'
            );
        };

        // DB::beforeExecuting() a través de la fachada solo engancha la
        // conexión POR DEFECTO (el DatabaseManager reenvía la llamada a esa),
        // que es justo la que no importa: el daño lo hacen los tests que
        // escriben en 'helpdesk'. Hay que registrar el callback en cada
        // conexión, incluidas las que todavía no se han abierto.
        Event::listen(ConnectionEstablished::class, static function (ConnectionEstablished $event) use ($guard): void {
            $event->connection->beforeExecuting($guard);
        });

        foreach (DB::getConnections() as $connection) {
            $connection->beforeExecuting($guard);
        }
    }

    /**
     * Create and authenticate a manager user
     */
    protected function actingAsManager(?User $user = null): self
    {
        $user ??= User::factory()->create();

        return $this->actingAs($user);
    }
}
