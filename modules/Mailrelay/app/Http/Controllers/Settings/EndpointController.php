<?php

namespace Modules\Mailrelay\Http\Controllers\Settings;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Modules\Mailrelay\Entities\EmailTemplate;
use Modules\Mailrelay\Entities\MailrelayEndpoint;
use Modules\Mailrelay\Http\Controllers\Controller;
use Modules\Mailrelay\Services\EndpointExecutionService;

class EndpointController extends Controller
{
    protected EndpointExecutionService $executionService;

    public function __construct(EndpointExecutionService $executionService)
    {
        $this->executionService = $executionService;
    }

    /**
     * Display a listing of endpoints.
     */
    public function index(Request $request): View
    {
        Gate::authorize('mailrelay.settings.endpoints.view');

        $query = MailrelayEndpoint::with('template');

        // Filter by search
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('endpoint_key', 'ilike', "%{$search}%")
                    ->orWhere('description', 'ilike', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filter by template
        if ($request->filled('template_id')) {
            $query->where('template_id', $request->input('template_id'));
        }

        $endpoints = $query->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('mailrelay::settings.endpoints.index', [
            'endpoints' => $endpoints,
        ]);
    }

    /**
     * Show the form for creating a new endpoint.
     */
    public function create(): View
    {
        Gate::authorize('mailrelay.settings.endpoints.create');

        $templates = EmailTemplate::orderBy('name')->get(['id', 'name']);

        return view('mailrelay::settings.endpoints.create', [
            'templates' => $templates,
        ]);
    }

    /**
     * Store a newly created endpoint.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('mailrelay.settings.endpoints.create');

        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'endpoint_key' => 'required|string|max:255|unique:mailrelay_endpoints,endpoint_key|alpha_dash',
                'description' => 'nullable|string',
                'template_id' => 'required|exists:mails_email_templates,id',
                'status' => 'in:active,inactive',
                'rate_limit' => 'nullable|integer|min:1|max:1000',
                'allowed_ips' => 'nullable|string',
                'webhook_url' => 'nullable|url|max:500',
            ]);

            // Process allowed_ips (convert comma-separated string to array)
            if (! empty($validated['allowed_ips'])) {
                $ips = array_map('trim', explode(',', $validated['allowed_ips']));
                $validated['allowed_ips'] = array_filter($ips);
            } else {
                $validated['allowed_ips'] = null;
            }

            $endpoint = MailrelayEndpoint::create($validated);

            // Generate API key
            $apiKey = $endpoint->generateApiKey();

            return redirect()
                ->route('managers.settings.mailrelay.endpoints.edit', $endpoint->id)
                ->with('success', 'Endpoint creado correctamente.')
                ->with('api_key', $apiKey); // Show API key once
        } catch (\Exception $e) {
            Log::error('Mailrelay endpoint create failed', ['error' => $e->getMessage()]);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error al crear el endpoint. Por favor, inténtalo de nuevo.');
        }
    }

    /**
     * Show the form for editing the specified endpoint.
     */
    public function edit(int $id): View
    {
        Gate::authorize('mailrelay.settings.endpoints.edit');

        $endpoint = MailrelayEndpoint::with('template')->findOrFail($id);
        $templates = EmailTemplate::orderBy('name')->get(['id', 'name']);

        // Get statistics
        $statistics = $this->executionService->getStatistics($endpoint, 30);

        return view('mailrelay::settings.endpoints.edit', [
            'endpoint' => $endpoint,
            'templates' => $templates,
            'statistics' => $statistics,
        ]);
    }

    /**
     * Update the specified endpoint.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        Gate::authorize('mailrelay.settings.endpoints.edit');

        try {
            $endpoint = MailrelayEndpoint::findOrFail($id);

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'endpoint_key' => 'required|string|max:255|alpha_dash|unique:mailrelay_endpoints,endpoint_key,'.$id,
                'description' => 'nullable|string',
                'template_id' => 'required|exists:mails_email_templates,id',
                'status' => 'in:active,inactive',
                'rate_limit' => 'nullable|integer|min:1|max:1000',
                'allowed_ips' => 'nullable|string',
                'webhook_url' => 'nullable|url|max:500',
            ]);

            // Process allowed_ips
            if (! empty($validated['allowed_ips'])) {
                $ips = array_map('trim', explode(',', $validated['allowed_ips']));
                $validated['allowed_ips'] = array_filter($ips);
            } else {
                $validated['allowed_ips'] = null;
            }

            $endpoint->update($validated);

            return redirect()
                ->route('managers.settings.mailrelay.endpoints.index')
                ->with('success', 'Endpoint actualizado correctamente.');
        } catch (\Exception $e) {
            Log::error('Mailrelay endpoint update failed', ['error' => $e->getMessage()]);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error al actualizar el endpoint. Por favor, inténtalo de nuevo.');
        }
    }

    /**
     * Remove the specified endpoint.
     */
    public function destroy(int $id): RedirectResponse
    {
        Gate::authorize('mailrelay.settings.endpoints.delete');

        try {
            $endpoint = MailrelayEndpoint::findOrFail($id);
            $endpoint->delete();

            return redirect()
                ->route('managers.settings.mailrelay.endpoints.index')
                ->with('success', 'Endpoint eliminado correctamente.');
        } catch (\Exception $e) {
            Log::error('Mailrelay endpoint delete failed', ['error' => $e->getMessage()]);

            return redirect()
                ->back()
                ->with('error', 'Error al eliminar el endpoint. Por favor, inténtalo de nuevo.');
        }
    }

    /**
     * Regenerate API key for the endpoint.
     */
    public function regenerateKey(int $id): JsonResponse
    {
        Gate::authorize('mailrelay.settings.endpoints.edit');

        try {
            $endpoint = MailrelayEndpoint::findOrFail($id);
            $newKey = $endpoint->generateApiKey();

            return response()->json([
                'success' => true,
                'api_key' => $newKey,
                'message' => 'API key regenerada correctamente. Guarda esta clave, no se mostrará de nuevo.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Ha ocurrido un error. Por favor, inténtalo de nuevo.',
            ], 500);
        }
    }

    /**
     * View logs for the endpoint.
     */
    public function logs(Request $request, int $id): View
    {
        Gate::authorize('mailrelay.settings.endpoints.view');

        $endpoint = MailrelayEndpoint::findOrFail($id);

        $logsQuery = $endpoint->logs()->orderBy('executed_at', 'desc');

        // Filter by status
        if ($request->filled('status')) {
            $status = $request->input('status');
            if ($status === 'success') {
                $logsQuery->successful();
            } elseif ($status === 'failed') {
                $logsQuery->failed();
            }
        }

        // Filter by date range
        if ($request->filled('from') && $request->filled('to')) {
            $logsQuery->dateRange($request->input('from'), $request->input('to'));
        }

        $logs = $logsQuery->paginate(50)->withQueryString();

        // Get statistics
        $statistics = $this->executionService->getStatistics($endpoint, 30);

        return view('mailrelay::settings.endpoints.logs', [
            'endpoint' => $endpoint,
            'logs' => $logs,
            'statistics' => $statistics,
        ]);
    }

    /**
     * Clear logs for the endpoint.
     */
    public function clearLogs(int $id): RedirectResponse
    {
        Gate::authorize('mailrelay.settings.endpoints.edit');

        try {
            $endpoint = MailrelayEndpoint::findOrFail($id);
            $count = $endpoint->logs()->count();
            $endpoint->logs()->delete();

            return redirect()
                ->back()
                ->with('success', "{$count} registros de log eliminados correctamente.");
        } catch (\Exception $e) {
            Log::error('Mailrelay endpoint logs delete failed', ['error' => $e->getMessage()]);

            return redirect()
                ->back()
                ->with('error', 'Error al eliminar los logs. Por favor, inténtalo de nuevo.');
        }
    }

    /**
     * Toggle endpoint status (active/inactive).
     */
    public function toggleStatus(int $id): JsonResponse
    {
        Gate::authorize('mailrelay.settings.endpoints.edit');

        try {
            $endpoint = MailrelayEndpoint::findOrFail($id);

            $newStatus = $endpoint->status === 'active' ? 'inactive' : 'active';
            $endpoint->status = $newStatus;
            $endpoint->save();

            return response()->json([
                'success' => true,
                'status' => $newStatus,
                'message' => $newStatus === 'active'
                    ? 'Endpoint activado correctamente'
                    : 'Endpoint desactivado correctamente',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Ha ocurrido un error. Por favor, inténtalo de nuevo.',
            ], 500);
        }
    }

    /**
     * Test endpoint with sample data.
     */
    public function test(Request $request, int $id): JsonResponse
    {
        Gate::authorize('mailrelay.settings.endpoints.edit');

        try {
            $endpoint = MailrelayEndpoint::findOrFail($id);

            if ($endpoint->status !== 'active') {
                return response()->json([
                    'success' => false,
                    'error' => 'El endpoint debe estar activo para probar',
                ], 400);
            }

            // Execute with test data
            $testRequest = new Request([
                'to' => $request->input('test_email', 'test@example.com'),
            ]);
            $testRequest->headers->set('X-API-Key', $endpoint->api_key);

            $response = $this->executionService->execute($endpoint, $testRequest);

            return response()->json([
                'success' => $response->getStatusCode() === 200,
                'response' => json_decode($response->getContent(), true),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Ha ocurrido un error. Por favor, inténtalo de nuevo.',
            ], 500);
        }
    }
}
