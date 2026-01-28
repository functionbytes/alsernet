<?php

namespace Modules\Mailing\Http\Controllers\Settings;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;
use Modules\Mailing\Http\Controllers\Controller;
use Modules\Mailing\Models\SendingServer;

class SendingServerController extends Controller
{
    /**
     * Display a listing of sending servers.
     */
    public function index(): View
    {
        Gate::authorize('mailing.settings.sending-servers.view');

        $sendingServers = SendingServer::where('user_id', auth()->id())
            ->orderBy('name')
            ->paginate(15);

        return view('mailing::settings.sending-servers.index', [
            'sendingServers' => $sendingServers,
        ]);
    }

    /**
     * Show the form for creating a new sending server.
     */
    public function create(): View
    {
        Gate::authorize('mailing.settings.sending-servers.create');

        return view('mailing::settings.sending-servers.create');
    }

    /**
     * Store a newly created sending server.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('mailing.settings.sending-servers.create');

        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'type' => 'required|in:smtp,sendgrid,mailgun,ses,postmark,sparkpost,mailjet',
                'status' => 'required|in:active,inactive,error',

                // SMTP fields
                'host' => 'required_if:type,smtp|nullable|string|max:255',
                'port' => 'required_if:type,smtp|nullable|integer|min:1|max:65535',
                'encryption' => 'nullable|in:tls,ssl,none',
                'username' => 'nullable|string|max:255',
                'password' => 'nullable|string',

                // API fields
                'api_key' => 'required_unless:type,smtp|nullable|string',
                'api_secret' => 'nullable|string',
                'api_region' => 'nullable|string|max:100',

                // Email configuration
                'from_email' => 'required|email|max:255',
                'from_name' => 'nullable|string|max:255',
                'reply_to_email' => 'nullable|email|max:255',

                // Rate limiting
                'sending_limit_per_minute' => 'nullable|integer|min:1|max:10000',
                'sending_limit_per_hour' => 'nullable|integer|min:1|max:100000',

                // Options
                'allow_verify_domain' => 'boolean',
                'allow_verify_email' => 'boolean',
                'allow_custom_return_path' => 'boolean',
            ]);

            // Add user_id
            $validated['user_id'] = auth()->id();
            $validated['status'] = $validated['status'] ?? 'inactive';

            SendingServer::create($validated);

            return redirect()
                ->route('settings.mailing.sending-servers.index')
                ->with('success', 'Servidor de envío creado correctamente.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error al crear el servidor: '.$e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified sending server.
     */
    public function edit(int $id): View
    {
        Gate::authorize('mailing.settings.sending-servers.edit');

        $sendingServer = SendingServer::where('user_id', auth()->id())
            ->findOrFail($id);

        return view('mailing::settings.sending-servers.edit', [
            'sendingServer' => $sendingServer,
        ]);
    }

    /**
     * Update the specified sending server.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        Gate::authorize('mailing.settings.sending-servers.edit');

        try {
            $sendingServer = SendingServer::where('user_id', auth()->id())
                ->findOrFail($id);

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'type' => 'required|in:smtp,sendgrid,mailgun,ses,postmark,sparkpost,mailjet',
                'status' => 'required|in:active,inactive,error',

                // SMTP fields
                'host' => 'required_if:type,smtp|nullable|string|max:255',
                'port' => 'required_if:type,smtp|nullable|integer|min:1|max:65535',
                'encryption' => 'nullable|in:tls,ssl,none',
                'username' => 'nullable|string|max:255',
                'password' => 'nullable|string',

                // API fields
                'api_key' => 'required_unless:type,smtp|nullable|string',
                'api_secret' => 'nullable|string',
                'api_region' => 'nullable|string|max:100',

                // Email configuration
                'from_email' => 'required|email|max:255',
                'from_name' => 'nullable|string|max:255',
                'reply_to_email' => 'nullable|email|max:255',

                // Rate limiting
                'sending_limit_per_minute' => 'nullable|integer|min:1|max:10000',
                'sending_limit_per_hour' => 'nullable|integer|min:1|max:100000',

                // Options
                'allow_verify_domain' => 'boolean',
                'allow_verify_email' => 'boolean',
                'allow_custom_return_path' => 'boolean',
            ]);

            $sendingServer->update($validated);

            return redirect()
                ->route('settings.mailing.sending-servers.index')
                ->with('success', 'Servidor de envío actualizado correctamente.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error al actualizar el servidor: '.$e->getMessage());
        }
    }

    /**
     * Remove the specified sending server.
     */
    public function destroy(int $id): RedirectResponse
    {
        Gate::authorize('mailing.settings.sending-servers.delete');

        try {
            $sendingServer = SendingServer::where('user_id', auth()->id())
                ->findOrFail($id);

            $sendingServer->delete();

            return redirect()
                ->route('settings.mailing.sending-servers.index')
                ->with('success', 'Servidor de envío eliminado correctamente.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Error al eliminar el servidor: '.$e->getMessage());
        }
    }

    /**
     * Test the connection to the sending server.
     */
    public function test(int $id): JsonResponse
    {
        Gate::authorize('mailing.settings.sending-servers.edit');

        try {
            $sendingServer = SendingServer::where('user_id', auth()->id())
                ->findOrFail($id);

            // Test connection based on server type
            if ($sendingServer->type === 'smtp') {
                $this->testSmtpConnection($sendingServer);
            } else {
                $this->testApiConnection($sendingServer);
            }

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
     * Toggle the status of a sending server.
     */
    public function toggleStatus(int $id): JsonResponse
    {
        Gate::authorize('mailing.settings.sending-servers.edit');

        try {
            $sendingServer = SendingServer::where('user_id', auth()->id())
                ->findOrFail($id);

            $newStatus = $sendingServer->status === 'active' ? 'inactive' : 'active';
            $sendingServer->update(['status' => $newStatus]);

            return response()->json([
                'success' => true,
                'message' => 'Estado actualizado correctamente.',
                'status' => $newStatus,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar el estado: '.$e->getMessage(),
            ], 422);
        }
    }

    /**
     * Test SMTP connection.
     */
    private function testSmtpConnection(SendingServer $server): void
    {
        $connection = fsockopen($server->host, $server->port, $errno, $errstr, 5);

        if (! $connection) {
            throw new \Exception("No se pudo conectar a {$server->host}:{$server->port}");
        }

        fclose($connection);
    }

    /**
     * Test API connection.
     */
    private function testApiConnection(SendingServer $server): void
    {
        $response = Http::timeout(10)
            ->withHeaders(['Authorization' => 'Bearer '.$server->api_key])
            ->get($server->api_url ?? 'https://api.example.com/v1/account');

        if (! $response->successful()) {
            throw new \Exception('Error en la API: '.$response->status());
        }
    }
}
