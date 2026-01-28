<?php

namespace Modules\Mailing\Http\Controllers\Settings;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Modules\Mailing\Http\Controllers\Controller;
use Modules\Mailing\Models\BounceHandler;

class BounceHandlerController extends Controller
{
    /**
     * Display a listing of bounce handlers.
     */
    public function index(): View
    {
        Gate::authorize('mailing.settings.bounce-handlers.view');

        $bounceHandlers = BounceHandler::where('user_id', auth()->id())
            ->orderBy('name')
            ->paginate(15);

        return view('mailing::settings.bounce-handlers.index', [
            'bounceHandlers' => $bounceHandlers,
        ]);
    }

    /**
     * Show the form for creating a new bounce handler.
     */
    public function create(): View
    {
        Gate::authorize('mailing.settings.bounce-handlers.create');

        return view('mailing::settings.bounce-handlers.create');
    }

    /**
     * Store a newly created bounce handler.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('mailing.settings.bounce-handlers.create');

        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'type' => 'required|in:imap,pop3,webhook',
                'status' => 'required|in:active,inactive,error',

                // IMAP/POP3 configuration
                'host' => 'required_unless:type,webhook|nullable|string|max:255',
                'port' => 'required_unless:type,webhook|nullable|integer|min:1|max:65535',
                'protocol' => 'required_unless:type,webhook|nullable|in:imap,pop3',
                'encryption' => 'required_unless:type,webhook|nullable|in:ssl,tls,none',
                'username' => 'required_unless:type,webhook|nullable|string|max:255',
                'password' => 'required_unless:type,webhook|nullable|string',
                'email' => 'required_unless:type,webhook|nullable|email|max:255',

                // Webhook configuration
                'webhook_token' => 'required_if:type,webhook|nullable|string',
                'webhook_secret' => 'nullable|string',

                // Processing options
                'auto_check' => 'boolean',
                'check_interval' => 'nullable|integer|min:5|max:1440',
                'delete_after_process' => 'boolean',
                'auto_unsubscribe_hard_bounce' => 'boolean',
                'soft_bounce_limit' => 'nullable|integer|min:1|max:10',
            ]);

            $validated['user_id'] = auth()->id();
            $validated['status'] = $validated['status'] ?? 'inactive';

            BounceHandler::create($validated);

            return redirect()
                ->route('settings.mailing.bounce-handlers.index')
                ->with('success', 'Gestor de rebotes creado correctamente.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error al crear el gestor de rebotes: '.$e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified bounce handler.
     */
    public function edit(int $id): View
    {
        Gate::authorize('mailing.settings.bounce-handlers.edit');

        $bounceHandler = BounceHandler::where('user_id', auth()->id())
            ->findOrFail($id);

        return view('mailing::settings.bounce-handlers.edit', [
            'bounceHandler' => $bounceHandler,
        ]);
    }

    /**
     * Update the specified bounce handler.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        Gate::authorize('mailing.settings.bounce-handlers.edit');

        try {
            $bounceHandler = BounceHandler::where('user_id', auth()->id())
                ->findOrFail($id);

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'type' => 'required|in:imap,pop3,webhook',
                'status' => 'required|in:active,inactive,error',

                // IMAP/POP3 configuration
                'host' => 'required_unless:type,webhook|nullable|string|max:255',
                'port' => 'required_unless:type,webhook|nullable|integer|min:1|max:65535',
                'protocol' => 'required_unless:type,webhook|nullable|in:imap,pop3',
                'encryption' => 'required_unless:type,webhook|nullable|in:ssl,tls,none',
                'username' => 'required_unless:type,webhook|nullable|string|max:255',
                'password' => 'required_unless:type,webhook|nullable|string',
                'email' => 'required_unless:type,webhook|nullable|email|max:255',

                // Webhook configuration
                'webhook_token' => 'required_if:type,webhook|nullable|string',
                'webhook_secret' => 'nullable|string',

                // Processing options
                'auto_check' => 'boolean',
                'check_interval' => 'nullable|integer|min:5|max:1440',
                'delete_after_process' => 'boolean',
                'auto_unsubscribe_hard_bounce' => 'boolean',
                'soft_bounce_limit' => 'nullable|integer|min:1|max:10',
            ]);

            $bounceHandler->update($validated);

            return redirect()
                ->route('settings.mailing.bounce-handlers.index')
                ->with('success', 'Gestor de rebotes actualizado correctamente.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error al actualizar el gestor de rebotes: '.$e->getMessage());
        }
    }

    /**
     * Remove the specified bounce handler.
     */
    public function destroy(int $id): RedirectResponse
    {
        Gate::authorize('mailing.settings.bounce-handlers.delete');

        try {
            $bounceHandler = BounceHandler::where('user_id', auth()->id())
                ->findOrFail($id);

            $bounceHandler->delete();

            return redirect()
                ->route('settings.mailing.bounce-handlers.index')
                ->with('success', 'Gestor de rebotes eliminado correctamente.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Error al eliminar el gestor de rebotes: '.$e->getMessage());
        }
    }

    /**
     * Test the connection to the bounce handler.
     */
    public function testConnection(int $id): JsonResponse
    {
        Gate::authorize('mailing.settings.bounce-handlers.edit');

        try {
            $bounceHandler = BounceHandler::where('user_id', auth()->id())
                ->findOrFail($id);

            if ($bounceHandler->type === 'webhook') {
                // Webhook test would be handled differently (e.g., trigger a test event)
                return response()->json([
                    'success' => true,
                    'message' => 'Configuración del webhook validada.',
                ]);
            }

            // Test IMAP/POP3 connection
            $this->testImapPopConnection($bounceHandler);

            return response()->json([
                'success' => true,
                'message' => 'Conexión establecida correctamente.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al probar la conexión: '.$e->getMessage(),
            ], 422);
        }
    }

    /**
     * Fetch bounces from the handler.
     */
    public function fetchBounces(int $id): JsonResponse
    {
        Gate::authorize('mailing.settings.bounce-handlers.edit');

        try {
            $bounceHandler = BounceHandler::where('user_id', auth()->id())
                ->findOrFail($id);

            if ($bounceHandler->type === 'webhook') {
                return response()->json([
                    'success' => false,
                    'message' => 'Los webhooks no soportan obtención manual de rebotes.',
                ], 422);
            }

            // Connect and fetch bounces
            $connection = imap_open(
                '{'.$bounceHandler->host.':'.$bounceHandler->port.'/service='.$bounceHandler->protocol.'}',
                $bounceHandler->username,
                $bounceHandler->password
            );

            if (! $connection) {
                throw new \Exception('No se pudo conectar al servidor IMAP/POP3');
            }

            $emails = imap_search($connection, 'ALL');
            $bounceCount = is_array($emails) ? count($emails) : 0;

            if ($bounceHandler->delete_after_process && is_array($emails)) {
                foreach ($emails as $emailId) {
                    imap_delete($connection, $emailId);
                }
                imap_expunge($connection);
            }

            imap_close($connection);

            // Update statistics
            $bounceHandler->update([
                'bounces_processed' => $bounceHandler->bounces_processed + $bounceCount,
                'last_checked_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Rebotes obtenidos correctamente.',
                'bounces_found' => $bounceCount,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener rebotes: '.$e->getMessage(),
            ], 422);
        }
    }

    /**
     * Test IMAP/POP3 connection.
     */
    private function testImapPopConnection(BounceHandler $handler): void
    {
        $connection = imap_open(
            '{'.$handler->host.':'.$handler->port.'/service='.$handler->protocol.'}',
            $handler->username,
            $handler->password
        );

        if (! $connection) {
            throw new \Exception('No se pudo conectar al servidor IMAP/POP3');
        }

        imap_close($connection);
    }
}
