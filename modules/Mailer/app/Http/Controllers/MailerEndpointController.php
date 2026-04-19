<?php

namespace Modules\Mailer\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Mailer\Enums\EndpointLogStatus;
use Modules\Mailer\Jobs\SendEndpointEmailJob;
use Modules\Mailer\Models\MailerEndpoint;
use Modules\Mailer\Models\MailerEndpointLog;
use Modules\Mailer\Models\MailerLang;
use Modules\Mailer\Models\MailerTemplate;
use Modules\Mailer\Traits\BuildsJsonResponses;

class MailerEndpointController extends Controller
{
    use BuildsJsonResponses;

    /**
     * Display documentation for email endpoints
     * GET /settings/mailers/endpoints/documentation
     */
    public function documentation(): View
    {
        $this->authorize('viewAny', MailerEndpoint::class);

        $endpoints = MailerEndpoint::with('template', 'language')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        $appUrl = config('app.url');

        return view('mailer::endpoints.documentation', compact('endpoints', 'appUrl'));
    }

    /**
     * Display a listing of all email endpoints
     * GET /settings/mailers/endpoints
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', MailerEndpoint::class);

        $query = MailerEndpoint::with('template', 'language')
            ->withCount([
                'logs as success_logs_count' => function ($q) {
                    $q->where('status', EndpointLogStatus::Success);
                },
                'logs as failed_logs_count' => function ($q) {
                    $q->where('status', EndpointLogStatus::Failed);
                },
            ]);

        // Filter by status if provided
        $status = $request->input('status');
        if ($status === 'active') {
            $query->active();
        } elseif ($status === 'inactive') {
            $query->inactive();
        }

        // Filter by source if provided
        $source = $request->input('source');
        if ($source) {
            $query->bySource($source);
        }

        $endpoints = $query->orderBy('name')->paginate(paginationNumber());

        // Get unique sources from endpoints for filtering
        $sources = MailerEndpoint::distinct('source')
            ->pluck('source')
            ->filter()
            ->values()
            ->toArray();

        // Pre-calculate stats for header cards (across ALL endpoints, not just current page)
        $totalEndpoints = MailerEndpoint::count();
        $activeCount = MailerEndpoint::active()->count();
        $inactiveCount = MailerEndpoint::inactive()->count();
        $totalRequests = MailerEndpoint::sum('requests_count');

        return view('mailer::endpoints.index', compact(
            'endpoints', 'sources', 'status', 'source',
            'totalEndpoints', 'activeCount', 'inactiveCount', 'totalRequests',
        ));
    }

    /**
     * Show the form for creating a new email endpoint
     * GET /settings/mailers/endpoints/create
     */
    public function create(): View
    {
        $this->authorize('create', MailerEndpoint::class);

        $templates = MailerTemplate::enabled()
            ->orderBy('name')
            ->get();

        $langs = MailerLang::available()->get();

        return view('mailer::endpoints.create', compact('templates', 'langs'));
    }

    /**
     * Store a newly created email endpoint in database
     * POST /settings/mailers/endpoints
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', MailerEndpoint::class);

        $reserved = config('mailer-module.reserved_slugs', ['send', 'info', 'status', 'logs']);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9][a-z0-9_-]*$/', 'unique:mailer_endpoints,slug', 'not_in:'.implode(',', $reserved)],
            'source' => 'required|string|in:internal,webhook,api',
            'type' => 'required|string|in:transactional,notification',
            'description' => 'nullable|string',
            'mailer_template_id' => 'required|exists:mailer_templates,id',
            'lang_id' => 'required|exists:langs,id',
            'expected_variables' => 'nullable|array',
            'required_variables' => 'nullable|array',
            'variable_mappings' => 'nullable|array',
            'variable_mappings.*' => 'required|string|max:255',
            'is_active' => 'boolean',
        ]);

        $endpoint = MailerEndpoint::create($validated);

        return redirect()
            ->route('mailers.endpoints.edit', $endpoint)
            ->with('success', 'Email endpoint created successfully');
    }

    /**
     * Show the form for editing the specified email endpoint
     * GET /settings/mailers/endpoints/edit/{emailEndpoint}
     */
    public function edit(MailerEndpoint $emailEndpoint): View
    {
        $this->authorize('view', $emailEndpoint);

        $templates = MailerTemplate::enabled()
            ->orderBy('name')
            ->get();

        $langs = MailerLang::available()->get();

        $logs = $emailEndpoint->logs()
            ->latest()
            ->limit(10)
            ->get();

        // Pre-calculate stats
        $endpoint = $emailEndpoint;
        $successCount = $emailEndpoint->successLogs()->count();
        $failedCount = $emailEndpoint->failedLogs()->count();
        $last24h = $emailEndpoint->logs()->where('created_at', '>=', now()->subDay())->count();
        $successRate = $emailEndpoint->successRate($successCount);

        return view('mailer::endpoints.edit', compact(
            'emailEndpoint', 'endpoint', 'templates', 'langs', 'logs',
            'successCount', 'failedCount', 'last24h', 'successRate',
        ));
    }

    /**
     * Update the specified email endpoint in database
     * PATCH /settings/mailers/endpoints/{emailEndpoint}
     */
    public function update(Request $request, MailerEndpoint $emailEndpoint): RedirectResponse
    {
        $this->authorize('update', $emailEndpoint);

        $reserved = config('mailer-module.reserved_slugs', ['send', 'info', 'status', 'logs']);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9][a-z0-9_-]*$/', 'unique:mailer_endpoints,slug,'.$emailEndpoint->id, 'not_in:'.implode(',', $reserved)],
            'source' => 'required|string|in:internal,webhook,api',
            'type' => 'required|string|in:transactional,notification',
            'description' => 'nullable|string',
            'mailer_template_id' => 'required|exists:mailer_templates,id',
            'lang_id' => 'required|exists:langs,id',
            'expected_variables' => 'nullable|array',
            'required_variables' => 'nullable|array',
            'variable_mappings' => 'nullable|array',
            'variable_mappings.*' => 'required|string|max:255',
            'is_active' => 'boolean',
        ]);

        $emailEndpoint->update($validated);

        return redirect()
            ->route('mailers.endpoints.edit', $emailEndpoint)
            ->with('success', 'Email endpoint updated successfully');
    }

    /**
     * Delete the specified email endpoint
     * DELETE /settings/mailers/endpoints/{emailEndpoint}
     */
    public function destroy(MailerEndpoint $emailEndpoint): RedirectResponse
    {
        $this->authorize('delete', $emailEndpoint);

        $emailEndpoint->delete();

        return redirect()
            ->route('mailers.endpoints.index')
            ->with('success', 'Email endpoint deleted successfully');
    }

    /**
     * Display logs for the specified email endpoint
     * GET /settings/mailers/endpoints/logs/{emailEndpoint}
     */
    public function logs(Request $request, MailerEndpoint $emailEndpoint): View
    {
        $this->authorize('viewLogs', $emailEndpoint);

        $search = $request->input('search');
        $statusFilter = $request->input('status');
        $period = $request->input('period');

        $query = $emailEndpoint->logs()->latest();

        if ($search) {
            $query->searchEmail($search);
        }

        if ($statusFilter) {
            $query->where('status', $statusFilter);
        }

        if ($period) {
            $query->period($period);
        }

        $logs = $query->paginate(20)->withQueryString();

        // Pre-calculate statistics to avoid expensive queries in the view
        $totalCount = $emailEndpoint->logs()->count();
        $successCount = $emailEndpoint->successLogs()->count();
        $failedCount = $emailEndpoint->failedLogs()->count();
        $successRate = $emailEndpoint->successRate($successCount, $totalCount);
        $last24h = $emailEndpoint->logs()->where('created_at', '>=', now()->subDay())->count();

        $endpoint = $emailEndpoint;

        return view('mailer::endpoints.logs', compact(
            'endpoint',
            'emailEndpoint',
            'logs',
            'search',
            'statusFilter',
            'period',
            'totalCount',
            'successCount',
            'failedCount',
            'successRate',
            'last24h',
        ));
    }

    /**
     * Regenerate API token for the specified endpoint
     * POST /settings/mailers/endpoints/regenerate-token/{emailEndpoint}
     */
    public function regenerateToken(MailerEndpoint $emailEndpoint): RedirectResponse
    {
        $this->authorize('regenerateToken', $emailEndpoint);

        $emailEndpoint->update([
            'api_token' => MailerEndpoint::generateToken(),
        ]);

        return redirect()
            ->route('mailers.endpoints.edit', $emailEndpoint)
            ->with('success', 'API token regenerated successfully');
    }

    /**
     * Send email via endpoint
     * POST /api/email-endpoints/{slug}/send
     */
    public function send(Request $request, string $slug): JsonResponse
    {
        $payloadMax = (int) config('mailer-module.limits.payload_max_bytes', 262144);

        $endpoint = MailerEndpoint::where('slug', $slug)->first();

        if (! $endpoint) {
            return $this->jsonError('endpoint_not_found', 'Endpoint not found', 404);
        }

        if (! $endpoint->is_active) {
            return $this->jsonError('endpoint_inactive', 'Endpoint is inactive', 403);
        }

        $providedToken = $request->header('X-API-Token');
        if (! $providedToken || ! hash_equals($endpoint->api_token, $providedToken)) {
            return $this->jsonError('invalid_token', 'Invalid API token', 401);
        }

        if (strlen((string) $request->getContent()) > $payloadMax) {
            return $this->jsonError('payload_too_large', 'Payload exceeds maximum size', 413);
        }

        $payload = $request->json()->all();
        if (! is_array($payload) || array_is_list($payload)) {
            return $this->jsonError('invalid_payload', 'Payload must be a JSON object', 422);
        }

        if ($endpoint->required_variables) {
            $missingVars = [];

            foreach ($endpoint->required_variables as $var) {
                if (! data_get($payload, $var)) {
                    $missingVars[] = $var;
                }
            }

            if (! empty($missingVars)) {
                return $this->jsonError(
                    'missing_variables',
                    'Missing required variables: '.implode(', ', $missingVars),
                    422,
                    ['missing_variables' => $missingVars]
                );
            }
        }

        $log = MailerEndpointLog::create([
            'mailer_endpoint_id' => $endpoint->id,
            'payload' => $payload,
            'status' => EndpointLogStatus::Pending,
        ]);

        SendEndpointEmailJob::dispatch($log);

        $endpoint->increment('requests_count');

        return $this->jsonSuccess(
            [
                'log_id' => $log->id,
                'endpoint' => $endpoint->slug,
            ],
            202,
            ['message' => 'Email queued for sending']
        );
    }

    /**
     * Get endpoint info
     * GET /api/email-endpoints/{slug}/info
     * Requires X-API-Token header (same token as send())
     */
    public function info(Request $request, string $slug): JsonResponse
    {
        $endpoint = $this->findAuthenticatedEndpoint($request, $slug);

        if ($endpoint instanceof JsonResponse) {
            return $endpoint;
        }

        $endpoint->loadMissing('template', 'language');

        return $this->jsonSuccess([
            'slug' => $endpoint->slug,
            'name' => $endpoint->name,
            'type' => $endpoint->type,
            'source' => $endpoint->source,
            'expected_variables' => $endpoint->expected_variables ?? [],
            'required_variables' => $endpoint->required_variables ?? [],
            'template' => $endpoint->template ? [
                'subject' => $endpoint->template->subject,
                'preview' => substr((string) $endpoint->template->content, 0, 200),
            ] : null,
            'is_active' => $endpoint->is_active,
        ]);
    }

    /**
     * Get endpoint status and logs
     * GET /api/email-endpoints/{slug}/status
     * Requires X-API-Token header (same token as send())
     */
    public function status(Request $request, string $slug): JsonResponse
    {
        $endpoint = $this->findAuthenticatedEndpoint($request, $slug);

        if ($endpoint instanceof JsonResponse) {
            return $endpoint;
        }

        $successCount = $endpoint->successLogs()->count();
        $failedCount = $endpoint->failedLogs()->count();
        $totalCount = $endpoint->requests_count;

        return $this->jsonSuccess([
            'slug' => $endpoint->slug,
            'is_active' => $endpoint->is_active,
            'total_requests' => $totalCount,
            'successful_emails' => $successCount,
            'failed_emails' => $failedCount,
            'success_rate' => $endpoint->successRate($successCount, $totalCount),
            'last_request_at' => $endpoint->last_request_at?->toIso8601String(),
        ]);
    }

    /**
     * Localiza el endpoint y valida el token de API.
     * Devuelve JsonResponse si hay error (401/404) para que el caller lo retorne.
     */
    private function findAuthenticatedEndpoint(Request $request, string $slug): MailerEndpoint|JsonResponse
    {
        $endpoint = MailerEndpoint::where('slug', $slug)->first();

        // Respuesta uniforme para prevenir enumeración de slugs
        $unauthorized = $this->jsonError('invalid_token', 'Invalid API token', 401);

        if (! $endpoint) {
            return $unauthorized;
        }

        $providedToken = (string) $request->header('X-API-Token');

        if ($providedToken === '' || ! hash_equals($endpoint->api_token, $providedToken)) {
            return $unauthorized;
        }

        return $endpoint;
    }
}
