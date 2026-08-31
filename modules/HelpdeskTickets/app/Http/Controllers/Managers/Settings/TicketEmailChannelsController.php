<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Managers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Modules\HelpdeskTickets\Http\Requests\Settings\BulkActionTicketEmailChannelRequest;
use Modules\HelpdeskTickets\Http\Requests\Settings\StoreTicketEmailChannelRequest;
use Modules\HelpdeskTickets\Http\Requests\Settings\UpdateTicketEmailChannelRequest;
use Modules\HelpdeskTickets\Jobs\Helpdesks\FetchTicketEmailsJob;
use Modules\HelpdeskTickets\Services\TicketEmailChannelsRepository;
use Modules\HelpdeskTickets\Support\TicketEmailChannelUrlGuard;

/**
 * Canales de correo que generan tickets (conexiones IMAP), autocontenido en
 * HelpdeskTickets: antes solo se administraban desde el módulo genérico
 * MailsSettings ("Correo entrante"), compartiendo pantalla con Pipe/API/
 * Gmail/Mailgun/phpList sin relación con tickets. Sigue escribiendo el mismo
 * setting `incoming_email` (única fuente que lee FetchTicketEmailsJob), así
 * que los canales ya configurados desde MailsSettings aparecen aquí tal cual.
 */
class TicketEmailChannelsController extends Controller
{
    public function __construct(private readonly TicketEmailChannelsRepository $channels)
    {
        $this->middleware('can:helpdesk.tickets.settings');
    }

    /**
     * Nº de canales por página. Los canales no son filas: viven dentro del
     * blob `incoming_email`, así que tanto el filtro como la paginación se
     * resuelven sobre el array en memoria (son decenas como mucho, no miles).
     */
    private const PER_PAGE = 15;

    public function index(Request $request): View
    {
        $all = $this->channels->all();

        // Las cifras son del total configurado, no de la página que se está
        // viendo ni del filtro aplicado: si no, buscar cambiaría los KPI.
        $stats = [
            'total' => count($all),
            'creating_tickets' => count(array_filter($all, fn ($c) => (bool) ($c['create_tickets'] ?? false))),
            'with_errors' => count(array_filter($all, fn ($c) => ! empty($c['last_error']))),
        ];

        $search = trim((string) $request->query('search', ''));
        $filtered = $search === '' ? $all : $this->filterBySearch($all, $search);

        $connections = $this->paginate($filtered, $request);

        return view('helpdesktickets::managers.settings.email-channels.index', [
            'connections' => $connections,
            'stats' => $stats,
            'search' => $search,
        ]);
    }

    /**
     * Busca por nombre, usuario y servidor (IMAP y SMTP). Nunca por password.
     *
     * @param  array<int, array<string, mixed>>  $connections
     * @return array<int, array<string, mixed>>
     */
    private function filterBySearch(array $connections, string $search): array
    {
        $needle = mb_strtolower($search);

        return array_values(array_filter($connections, function (array $c) use ($needle): bool {
            $haystack = mb_strtolower(implode(' ', array_filter([
                $c['name'] ?? '',
                $c['username'] ?? '',
                $c['host'] ?? '',
                $c['smtp_host'] ?? '',
                $c['folder'] ?? '',
            ])));

            return str_contains($haystack, $needle);
        }));
    }

    /**
     * Paginador sobre el array ya filtrado, para que la vista pueda usar
     * ->links() y ->hasPages() igual que las pantallas que sí van contra BD.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    private function paginate(array $items, Request $request): LengthAwarePaginator
    {
        $page = max(1, (int) $request->query('page', 1));

        return new LengthAwarePaginator(
            array_slice($items, ($page - 1) * self::PER_PAGE, self::PER_PAGE),
            count($items),
            self::PER_PAGE,
            $page,
            ['path' => $request->url(), 'query' => $request->query()],
        );
    }

    /**
     * Formulario de alta en pantalla completa (antes era un modal en el
     * index) — mismo patrón que el resto de ajustes de tickets
     * (categorías, grupos, macros...).
     */
    public function create(): View
    {
        return view('helpdesktickets::managers.settings.email-channels.create');
    }

    public function edit(string $channel): View
    {
        $connection = $this->channels->find($channel);

        abort_if($connection === null, 404);

        return view('helpdesktickets::managers.settings.email-channels.edit', [
            'channel' => $connection,
        ]);
    }

    public function store(StoreTicketEmailChannelRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['create_tickets'] = $request->boolean('create_tickets');
        $validated['create_replies'] = $request->boolean('create_replies');
        $validated['folder'] = $validated['folder'] ?: 'INBOX';
        $validated['encryption'] = $validated['encryption'] ?: 'ssl';
        $validated['smtp_port'] = $validated['smtp_port'] ?: 465;
        $validated['smtp_encryption'] = $validated['smtp_encryption'] ?: 'ssl';

        $this->channels->create($validated);

        return redirect()
            ->route('manager.helpdesk.settings.email-channels.index')
            ->with('success', __('helpdesktickets::helpdesktickets.settings.channel.created'));
    }

    public function update(UpdateTicketEmailChannelRequest $request, string $channel): RedirectResponse
    {
        $validated = $request->validated();
        $validated['create_tickets'] = $request->boolean('create_tickets');
        $validated['create_replies'] = $request->boolean('create_replies');
        $validated['folder'] = $validated['folder'] ?: 'INBOX';
        $validated['encryption'] = $validated['encryption'] ?: 'ssl';
        $validated['smtp_port'] = $validated['smtp_port'] ?: 465;
        $validated['smtp_encryption'] = $validated['smtp_encryption'] ?: 'ssl';

        $updated = $this->channels->update($channel, $validated);

        abort_if($updated === null, 404);

        return redirect()
            ->route('manager.helpdesk.settings.email-channels.index')
            ->with('success', __('helpdesktickets::helpdesktickets.settings.channel.updated'));
    }

    public function destroy(string $channel): RedirectResponse
    {
        $this->channels->delete($channel);

        return redirect()
            ->route('manager.helpdesk.settings.email-channels.index')
            ->with('success', __('helpdesktickets::helpdesktickets.settings.channel.deleted'));
    }

    /**
     * Acción masiva sobre los canales seleccionados.
     *
     * "activar"/"desactivar" se refiere a `create_tickets`, que es el
     * interruptor que decide si el buzón genera tickets — el resto de campos
     * del canal (credenciales, servidor) no tienen un estado que alternar.
     * Los ids son strings (uniqid), no enteros, así que este endpoint no puede
     * reutilizar BulkActionRequest, que valida `ids.*` como integer.
     */
    public function bulkAction(BulkActionTicketEmailChannelRequest $request): JsonResponse
    {
        $action = $request->validated('action');
        $ids = $request->validated('ids');

        $existing = array_column($this->channels->all(), 'id');
        $targets = array_values(array_intersect($ids, $existing));

        $count = 0;

        foreach ($targets as $id) {
            if ($action === 'delete') {
                $this->channels->delete($id);
                $count++;

                continue;
            }

            $this->channels->update($id, ['create_tickets' => $action === 'activate']);
            $count++;
        }

        $skipped = count($ids) - count($targets);

        $labels = [
            'delete' => 'eliminado(s)',
            'activate' => 'activado(s) para generar tickets',
            'deactivate' => 'desactivado(s) para generar tickets',
        ];

        $message = "{$count} canal(es) {$labels[$action]}.";

        if ($skipped > 0) {
            $message .= " {$skipped} omitido(s) por no existir ya.";
        }

        return response()->json(['success' => true, 'message' => $message]);
    }

    /**
     * Prueba credenciales/host antes de guardar (o para un canal ya
     * guardado) — mismo mecanismo que MailsSettingsController::testImapConnection,
     * replicado aquí para no depender de ese módulo.
     */
    public function test(Request $request): JsonResponse
    {
        // Solo host y puerto. Antes exigía username y password como required
        // y no los usaba en ningún sitio: la prueba es un fsockopen, un
        // chequeo TCP que no autentica nada. Pedirlos mandaba la contraseña
        // por la red para descartarla, y sobre todo dejaba el botón inservible
        // en la pantalla de edición, donde el campo de contraseña se sirve
        // vacío a propósito — el aviso "ingresa la contraseña para probar"
        // pedía algo que la prueba nunca iba a mirar. testSmtp() ya lo hacía
        // bien: solo host y puerto.
        $validated = $request->validate([
            'host' => ['required', 'string'],
            'port' => ['required', 'integer'],
        ]);

        $host = $validated['host'];
        $port = (int) $validated['port'];

        if (! TicketEmailChannelUrlGuard::isHostAllowed($host)) {
            return response()->json([
                'success' => false,
                'message' => 'El servidor IMAP no está permitido.',
            ], 422);
        }

        $startTime = microtime(true);
        $connection = @fsockopen($host, $port, $errno, $errstr, 10);
        $responseTime = round((microtime(true) - $startTime) * 1000, 2);

        if (! $connection) {
            return response()->json([
                'success' => false,
                'message' => "No se pudo conectar al servidor IMAP: {$errstr} (código {$errno}).",
            ], 400);
        }

        fclose($connection);

        // El mensaje dice lo que se ha comprobado de verdad — que el servidor
        // acepta la conexión — y no da a entender que las credenciales sean
        // válidas, que es lo que sugería un "responde correctamente" tras
        // haber pedido usuario y contraseña.
        return response()->json([
            'success' => true,
            'message' => "El servidor {$host}:{$port} acepta conexiones ({$responseTime}ms). Las credenciales se comprueban al sincronizar.",
        ]);
    }

    /**
     * Prueba host:puerto del servidor SMTP saliente — mismo chequeo TCP que
     * test(), separado para no mezclar el mensaje "IMAP" con "SMTP" en la UI.
     */
    public function testSmtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'smtp_host' => ['required', 'string'],
            'smtp_port' => ['required', 'integer'],
        ]);

        $host = $validated['smtp_host'];
        $port = (int) $validated['smtp_port'];

        if (! TicketEmailChannelUrlGuard::isHostAllowed($host)) {
            return response()->json([
                'success' => false,
                'message' => 'El servidor SMTP no está permitido.',
            ], 422);
        }

        $startTime = microtime(true);
        $connection = @fsockopen($host, $port, $errno, $errstr, 10);
        $responseTime = round((microtime(true) - $startTime) * 1000, 2);

        if (! $connection) {
            return response()->json([
                'success' => false,
                'message' => "No se pudo conectar al servidor SMTP: {$errstr} (código {$errno}).",
            ], 400);
        }

        fclose($connection);

        return response()->json([
            'success' => true,
            'message' => "Servidor {$host}:{$port} responde correctamente ({$responseTime}ms).",
        ]);
    }

    /**
     * Dispara un fetch inmediato acotado a este canal (mismo código que la
     * corrida agendada de imap:emailticket, vía FetchTicketEmailsJob) — para
     * validar la configuración justo después de guardarla, sin esperar al
     * cron. Se ejecuta en la propia request (no se encola): un buzón lento
     * puede tardar, por eso el timeout del job es de 600s y esto es una
     * acción explícita del administrador, no algo que corra en cada carga.
     */
    public function sync(string $channel): JsonResponse
    {
        $connection = $this->channels->find($channel);

        abort_if($connection === null, 404);

        $createTickets = (bool) ($connection['create_tickets'] ?? false);
        $createReplies = (bool) ($connection['create_replies'] ?? false);

        if (! $createTickets && ! $createReplies) {
            return response()->json([
                'success' => false,
                'message' => 'Activa "Crear tickets" o "Crear respuestas" antes de sincronizar.',
            ], 422);
        }

        try {
            (new FetchTicketEmailsJob(onlyConnectionId: $channel))->handle();
        } catch (\Throwable $e) {
            Log::error('TicketEmailChannelsController::sync — error al sincronizar canal', [
                'channel' => $channel,
                'error' => $e->getMessage(),
            ]);
        }

        // El propio job ya dejó el resultado (éxito/último error) en el
        // canal vía TicketEmailChannelsRepository::recordHealth() — se relee
        // en vez de intentar propagar el resultado desde handle() (void).
        $refreshed = $this->channels->find($channel);

        if ($refreshed && empty($refreshed['last_error'])) {
            return response()->json([
                'success' => true,
                'message' => 'Sincronización completada sin errores.',
                'last_checked_at' => $refreshed['last_checked_at'] ?? null,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $refreshed['last_error'] ?? 'La sincronización falló. Revisa las credenciales del canal.',
            'last_checked_at' => $refreshed['last_checked_at'] ?? null,
        ], 422);
    }
}
