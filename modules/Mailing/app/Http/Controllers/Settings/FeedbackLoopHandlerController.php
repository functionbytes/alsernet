<?php

namespace Modules\Mailing\Http\Controllers\Settings;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Modules\Mailing\Http\Controllers\Controller;
use Modules\Mailing\Models\FeedbackLoopHandler;

class FeedbackLoopHandlerController extends Controller
{
    /**
     * Display a listing of feedback loop handlers.
     */
    public function index(): View
    {
        Gate::authorize('mailing.settings.feedback-loop-handlers.view');

        $feedbackHandlers = FeedbackLoopHandler::where('user_id', auth()->id())
            ->orderBy('name')
            ->paginate(15);

        return view('mailing::settings.feedback-loop-handlers.index', [
            'feedbackHandlers' => $feedbackHandlers,
        ]);
    }

    /**
     * Show the form for creating a new feedback loop handler.
     */
    public function create(): View
    {
        Gate::authorize('mailing.settings.feedback-loop-handlers.create');

        return view('mailing::settings.feedback-loop-handlers.create');
    }

    /**
     * Store a newly created feedback loop handler.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('mailing.settings.feedback-loop-handlers.create');

        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'type' => 'required|in:imap,pop3,webhook,api',
                'status' => 'required|in:active,inactive,error',

                // IMAP/POP3 configuration
                'host' => 'required_unless:type,webhook,api|nullable|string|max:255',
                'port' => 'required_unless:type,webhook,api|nullable|integer|min:1|max:65535',
                'protocol' => 'required_unless:type,webhook,api|nullable|in:imap,pop3',
                'encryption' => 'required_unless:type,webhook,api|nullable|in:ssl,tls,none',
                'username' => 'required_unless:type,webhook,api|nullable|string|max:255',
                'password' => 'required_unless:type,webhook,api|nullable|string',
                'email' => 'required_unless:type,webhook,api|nullable|email|max:255',

                // Webhook/API configuration
                'webhook_token' => 'required_if:type,webhook|nullable|string',
                'webhook_secret' => 'nullable|string',
                'provider' => 'nullable|in:gmail,yahoo,aol,outlook,custom',
                'feedback_type' => 'nullable|in:abuse,arf',

                // Processing options
                'auto_check' => 'boolean',
                'check_interval' => 'nullable|integer|min:5|max:1440',
                'delete_after_process' => 'boolean',
                'auto_unsubscribe' => 'boolean',
                'notify_admin' => 'boolean',
            ]);

            $validated['user_id'] = auth()->id();
            $validated['status'] = $validated['status'] ?? 'inactive';

            FeedbackLoopHandler::create($validated);

            return redirect()
                ->route('settings.mailing.feedback-handlers.index')
                ->with('success', 'Gestor de bucle de retroalimentación creado correctamente.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error al crear el gestor: '.$e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified feedback loop handler.
     */
    public function edit(int $id): View
    {
        Gate::authorize('mailing.settings.feedback-loop-handlers.edit');

        $feedbackHandler = FeedbackLoopHandler::where('user_id', auth()->id())
            ->findOrFail($id);

        return view('mailing::settings.feedback-loop-handlers.edit', [
            'feedbackHandler' => $feedbackHandler,
        ]);
    }

    /**
     * Update the specified feedback loop handler.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        Gate::authorize('mailing.settings.feedback-loop-handlers.edit');

        try {
            $feedbackHandler = FeedbackLoopHandler::where('user_id', auth()->id())
                ->findOrFail($id);

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'type' => 'required|in:imap,pop3,webhook,api',
                'status' => 'required|in:active,inactive,error',

                // IMAP/POP3 configuration
                'host' => 'required_unless:type,webhook,api|nullable|string|max:255',
                'port' => 'required_unless:type,webhook,api|nullable|integer|min:1|max:65535',
                'protocol' => 'required_unless:type,webhook,api|nullable|in:imap,pop3',
                'encryption' => 'required_unless:type,webhook,api|nullable|in:ssl,tls,none',
                'username' => 'required_unless:type,webhook,api|nullable|string|max:255',
                'password' => 'required_unless:type,webhook,api|nullable|string',
                'email' => 'required_unless:type,webhook,api|nullable|email|max:255',

                // Webhook/API configuration
                'webhook_token' => 'required_if:type,webhook|nullable|string',
                'webhook_secret' => 'nullable|string',
                'provider' => 'nullable|in:gmail,yahoo,aol,outlook,custom',
                'feedback_type' => 'nullable|in:abuse,arf',

                // Processing options
                'auto_check' => 'boolean',
                'check_interval' => 'nullable|integer|min:5|max:1440',
                'delete_after_process' => 'boolean',
                'auto_unsubscribe' => 'boolean',
                'notify_admin' => 'boolean',
            ]);

            $feedbackHandler->update($validated);

            return redirect()
                ->route('settings.mailing.feedback-handlers.index')
                ->with('success', 'Gestor de bucle de retroalimentación actualizado correctamente.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error al actualizar el gestor: '.$e->getMessage());
        }
    }

    /**
     * Remove the specified feedback loop handler.
     */
    public function destroy(int $id): RedirectResponse
    {
        Gate::authorize('mailing.settings.feedback-loop-handlers.delete');

        try {
            $feedbackHandler = FeedbackLoopHandler::where('user_id', auth()->id())
                ->findOrFail($id);

            $feedbackHandler->delete();

            return redirect()
                ->route('settings.mailing.feedback-handlers.index')
                ->with('success', 'Gestor de bucle de retroalimentación eliminado correctamente.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Error al eliminar el gestor: '.$e->getMessage());
        }
    }

    /**
     * Test the connection to the feedback loop handler.
     */
    public function testConnection(int $id): JsonResponse
    {
        Gate::authorize('mailing.settings.feedback-loop-handlers.edit');

        try {
            $feedbackHandler = FeedbackLoopHandler::where('user_id', auth()->id())
                ->findOrFail($id);

            if (in_array($feedbackHandler->type, ['webhook', 'api'])) {
                return response()->json([
                    'success' => true,
                    'message' => 'Configuración del '.$feedbackHandler->type.' validada.',
                ]);
            }

            // Test IMAP/POP3 connection
            $this->testImapPopConnection($feedbackHandler);

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
     * Fetch feedback/complaint reports from the handler.
     */
    public function fetchReports(int $id): JsonResponse
    {
        Gate::authorize('mailing.settings.feedback-loop-handlers.edit');

        try {
            $feedbackHandler = FeedbackLoopHandler::where('user_id', auth()->id())
                ->findOrFail($id);

            if (in_array($feedbackHandler->type, ['webhook', 'api'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este tipo de gestor no soporta obtención manual de reportes.',
                ], 422);
            }

            // Connect and fetch complaints
            $connection = imap_open(
                '{'.$feedbackHandler->host.':'.$feedbackHandler->port.'/service='.$feedbackHandler->protocol.'}',
                $feedbackHandler->username,
                $feedbackHandler->password
            );

            if (! $connection) {
                throw new \Exception('No se pudo conectar al servidor IMAP/POP3');
            }

            $emails = imap_search($connection, 'ALL');
            $complaintCount = is_array($emails) ? count($emails) : 0;

            if ($feedbackHandler->delete_after_process && is_array($emails)) {
                foreach ($emails as $emailId) {
                    imap_delete($connection, $emailId);
                }
                imap_expunge($connection);
            }

            imap_close($connection);

            // Update statistics
            $feedbackHandler->update([
                'complaints_processed' => $feedbackHandler->complaints_processed + $complaintCount,
                'last_checked_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Reportes obtenidos correctamente.',
                'complaints_found' => $complaintCount,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener reportes: '.$e->getMessage(),
            ], 422);
        }
    }

    /**
     * Test IMAP/POP3 connection.
     */
    private function testImapPopConnection(FeedbackLoopHandler $handler): void
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
