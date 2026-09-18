<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Modules\HelpdeskTickets\Http\Requests\Managers\UpdateTicketMailboxBehaviorRequest;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Services\TicketEmailChannelsRepository;
use Modules\HelpdeskTickets\Support\TicketEmailChannelUrlGuard;

/**
 * Modal "Buzones" del riel de operación de la pantalla de tickets: ver los
 * buzones IMAP con su estado, cambiar los dos interruptores de comportamiento
 * (crear tickets / añadir respuestas) y lanzar una prueba de conexión, sin
 * salir del listado de tickets.
 *
 * Es un controlador aparte y NO toca TicketEmailChannelsController: aquel
 * sirve la pantalla completa de ajustes (alta, credenciales, SMTP, borrado,
 * acciones masivas) y responde con redirects; éste solo habla JSON y expone
 * la superficie mínima que el modal necesita. Escriben el mismo sitio —
 * `imap.connections` dentro del setting `incoming_email`, vía
 * TicketEmailChannelsRepository — así que lo que se cambie aquí se ve allí y
 * al revés.
 */
class TicketOpsMailboxesController extends Controller
{
    /**
     * Puertos que puede marcar la prueba de conexión. Aunque aquí el host y
     * el puerto salen de la configuración guardada (no del request, a
     * diferencia de TicketEmailChannelsController::test), la lista se
     * mantiene: sin ella, un buzón guardado con un puerto arbitrario
     * convertiría este botón en un comprobador de puertos de la red interna
     * a un clic de distancia. Misma lista que la pantalla de ajustes.
     */
    private const ALLOWED_MAIL_PORTS = [25, 143, 465, 587, 993];

    /**
     * Igual que en la pantalla de ajustes: es un chequeo TCP, no una espera
     * de protocolo completo, y acota cuánto puede colgar la request contra un
     * puerto filtrado (drop silencioso).
     */
    private const CONNECT_TIMEOUT_SECONDS = 3;

    public function __construct(private readonly TicketEmailChannelsRepository $channels)
    {
        // Leer la lista la puede hacer cualquier agente que ya ve el listado
        // de tickets (el mismo dato que ya devolvía settings-snapshot);
        // cambiar el comportamiento de un buzón o abrir una conexión de
        // prueba es configuración y exige el permiso de ajustes.
        $this->middleware('can:helpdesk.tickets.settings')->only(['behavior', 'test']);
    }

    /**
     * Buzones configurados con su estado de salud. Sin credenciales: ni
     * contraseña ni ningún campo del que se pueda derivar.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Ticket::class);

        return response()->json([
            // El modal esconde los interruptores y el botón de prueba cuando
            // esto es false, en vez de dejarlos puestos para que el servidor
            // devuelva 403 al primer clic.
            'can_manage' => (bool) $request->user()?->can('helpdesk.tickets.settings'),
            'mailboxes' => array_map(
                fn (array $connection) => $this->present($connection),
                $this->channels->all()
            ),
        ]);
    }

    /**
     * Cambia los dos interruptores de comportamiento del buzón. No se puede
     * tocar nada más desde aquí (ver UpdateTicketMailboxBehaviorRequest).
     */
    public function behavior(UpdateTicketMailboxBehaviorRequest $request, string $channel): JsonResponse
    {
        $updated = $this->channels->update($channel, [
            'create_tickets' => $request->boolean('create_tickets'),
            'create_replies' => $request->boolean('create_replies'),
        ]);

        abort_if($updated === null, 404);

        // Se relee con find() en vez de devolver lo que retorna update():
        // update() no mezcla la salud (vive en caché aparte, ver el
        // repositorio) y el modal repinta la tarjeta con esta respuesta.
        $fresh = $this->channels->find($channel) ?? $updated;

        return response()->json([
            'success' => true,
            'message' => $this->behaviorMessage($fresh),
            'mailbox' => $this->present($fresh),
        ]);
    }

    /**
     * Prueba de conexión del buzón ya guardado: comprueba que su servidor
     * IMAP acepta conexiones en el puerto configurado.
     *
     * NO autentica (es un fsockopen, igual que el botón de la pantalla de
     * ajustes) y NO escribe el estado de salud del canal: `last_error` y
     * `last_checked_at` describen la última corrida real de
     * FetchTicketEmailsJob, y pisarlos con el resultado de un chequeo TCP
     * haría pasar por "leído sin errores" un buzón cuyas credenciales
     * caducaron.
     */
    public function test(string $channel): JsonResponse
    {
        $connection = $this->channels->find($channel);

        abort_if($connection === null, 404);

        $host = trim((string) ($connection['host'] ?? ''));
        $port = (int) ($connection['port'] ?? 0);

        if ($host === '' || ! in_array($port, self::ALLOWED_MAIL_PORTS, true)) {
            return response()->json([
                'success' => false,
                'message' => 'El buzón no tiene un servidor y un puerto de correo válidos configurados.',
            ], 422);
        }

        if (! TicketEmailChannelUrlGuard::isHostAllowed($host)) {
            return response()->json([
                'success' => false,
                'message' => 'El servidor IMAP de este buzón no está permitido.',
            ], 422);
        }

        $socket = @fsockopen($host, $port, $errno, $errstr, self::CONNECT_TIMEOUT_SECONDS);

        if (! $socket) {
            // Sin errno/errstr ni tiempo de respuesta, igual que en la
            // pantalla de ajustes: distinguir puerto cerrado de filtrado es
            // justo lo que busca un escaneo de la red interna.
            return response()->json([
                'success' => false,
                'message' => "No se pudo conectar con {$host}:{$port}.",
            ], 400);
        }

        fclose($socket);

        return response()->json([
            'success' => true,
            'message' => "El servidor {$host}:{$port} acepta conexiones. Las credenciales se comprueban al sincronizar.",
        ]);
    }

    /**
     * Resumen en castellano de lo que hace el buzón tras el cambio. Con los
     * dos interruptores apagados FetchTicketEmailsJob se salta el buzón
     * entero, así que eso se dice explícitamente: desde el modal parece que
     * solo se ha desmarcado una casilla.
     *
     * @param  array<string, mixed>  $connection
     */
    private function behaviorMessage(array $connection): string
    {
        $creates = (bool) ($connection['create_tickets'] ?? false);
        $replies = (bool) ($connection['create_replies'] ?? false);

        if (! $creates && ! $replies) {
            return 'Buzón guardado. Ya no se leerá: no crea tickets ni añade respuestas.';
        }

        $does = implode(' y ', array_filter([
            $creates ? 'crea tickets' : null,
            $replies ? 'añade respuestas al ticket existente' : null,
        ]));

        return "Buzón guardado: {$does}.";
    }

    /**
     * Lo que el modal necesita para pintar una tarjeta de buzón. Mantiene los
     * mismos nombres de campo que ya devolvía TicketOpsController::
     * settingsSnapshot para los buzones, para que el modal pueda seguir
     * pintándose con aquella respuesta si esta ruta no está disponible.
     *
     * @param  array<string, mixed>  $connection
     * @return array<string, mixed>
     */
    private function present(array $connection): array
    {
        $lastChecked = $this->toIso($connection['last_checked_at'] ?? null);

        return [
            'id' => $connection['id'] ?? null,
            'name' => $connection['name'] ?? null,
            'username' => $connection['username'] ?? null,
            'host' => $connection['host'] ?? null,
            'port' => $connection['port'] ?? null,
            'encryption' => $connection['encryption'] ?? null,
            'folder' => $connection['folder'] ?? null,
            // Servidor de salida del canal (el mockup enseña la fila SMTP
            // junto a la IMAP). Sin credenciales: usuario y contraseña de
            // SMTP son los mismos del buzón y no se envían.
            'smtp_host' => $connection['smtp_host'] ?? null,
            'smtp_port' => $connection['smtp_port'] ?? null,
            'smtp_encryption' => $connection['smtp_encryption'] ?? null,
            'create_tickets' => (bool) ($connection['create_tickets'] ?? false),
            'create_replies' => (bool) ($connection['create_replies'] ?? false),
            'last_checked_at' => $lastChecked,
            'last_success_at' => $this->toIso($connection['last_success_at'] ?? null),
            'last_error' => $connection['last_error'] ?? null,
            // El mockup enseña además "8 mensajes" junto a la última lectura:
            // no se envía porque nadie guarda cuántos correos trajo la última
            // corrida (FetchTicketEmailsJob solo registra éxito/error).
        ];
    }

    /**
     * Las marcas de tiempo de salud se guardan como string ISO, pero un canal
     * viejo pudo dejarlas escritas en otro formato dentro del blob. Se
     * normaliza para que el modal reciba siempre ISO o null.
     */
    private function toIso(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->toIso8601String();
        } catch (\Throwable) {
            return null;
        }
    }
}
