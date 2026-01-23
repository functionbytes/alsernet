<?php

namespace Modules\Mailer\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Mailer\Jobs\SendEndpointEmailJob;
use Modules\Mailer\Models\MailerEndpoint;
use Modules\Mailer\Models\MailerEndpointLog;
use Modules\Mailer\Models\MailerLang;
use Modules\Mailer\Models\MailerTemplate;

class MailerEndpointController extends Controller
{
    /**
     * Display documentation for email endpoints
     * GET /settings/mailers/endpoints/documentation
     */
    public function documentation()
    {
        return view('mailer::endpoints.documentation');
    }

    /**
     * Display a listing of all email endpoints
     * GET /settings/mailers/endpoints
     */
    public function index(Request $request)
    {
        $query = MailerEndpoint::with('template', 'language');

        // Filter by status if provided
        $status = $request->input('status');
        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        // Filter by source if provided
        $source = $request->input('source');
        if ($source) {
            $query->where('source', $source);
        }

        $endpoints = $query->orderBy('name')->paginate(15);

        // Get unique sources from endpoints for filtering
        $sources = MailerEndpoint::distinct('source')
            ->pluck('source')
            ->filter()
            ->values()
            ->toArray();

        return view('mailer::endpoints.index', compact('endpoints', 'sources', 'status', 'source'));
    }

    /**
     * Show the form for creating a new email endpoint
     * GET /settings/mailers/endpoints/create
     */
    public function create()
    {
        $templates = MailerTemplate::enabled()
            ->orderBy('name')
            ->get();

        $langs = \App\Models\Lang::all();

        return view('mailer::endpoints.create', compact('templates', 'langs'));
    }

    /**
     * Store a newly created email endpoint in database
     * POST /settings/mailers/endpoints
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:mailer_endpoints,slug',
            'source' => 'required|string|in:internal,webhook,api',
            'type' => 'required|string|in:transactional,notification',
            'description' => 'nullable|string',
            'mailer_template_id' => 'required|exists:mailer_templates,id',
            'lang_id' => 'required|exists:langs,id',
            'expected_variables' => 'nullable|array',
            'required_variables' => 'nullable|array',
            'variable_mappings' => 'nullable|array',
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
    public function edit(MailerEndpoint $emailEndpoint)
    {
        $templates = MailerTemplate::enabled()
            ->orderBy('name')
            ->get();

        $langs = MailerLang::all();

        $logs = $emailEndpoint->logs()
            ->latest()
            ->limit(50)
            ->get();

        return view('mailer::endpoints.edit', compact('emailEndpoint', 'templates', 'langs', 'logs'));
    }

    /**
     * Update the specified email endpoint in database
     * PATCH /settings/mailers/endpoints/{emailEndpoint}
     */
    public function update(Request $request, MailerEndpoint $emailEndpoint)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:mailer_endpoints,slug,'.$emailEndpoint->id,
            'source' => 'required|string|in:internal,webhook,api',
            'type' => 'required|string|in:transactional,notification',
            'description' => 'nullable|string',
            'mailer_template_id' => 'required|exists:mailer_templates,id',
            'lang_id' => 'required|exists:langs,id',
            'expected_variables' => 'nullable|array',
            'required_variables' => 'nullable|array',
            'variable_mappings' => 'nullable|array',
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
    public function destroy(MailerEndpoint $emailEndpoint)
    {
        $emailEndpoint->delete();

        return redirect()
            ->route('mailers.endpoints.index')
            ->with('success', 'Email endpoint deleted successfully');
    }

    /**
     * Display logs for the specified email endpoint
     * GET /settings/mailers/endpoints/logs/{emailEndpoint}
     */
    public function logs(MailerEndpoint $emailEndpoint)
    {
        $logs = $emailEndpoint->logs()
            ->latest()
            ->paginate(20);

        return view('mailer::endpoints.logs', compact('emailEndpoint', 'logs'));
    }

    /**
     * Regenerate API token for the specified endpoint
     * POST /settings/mailers/endpoints/regenerate-token/{emailEndpoint}
     */
    public function regenerateToken(MailerEndpoint $emailEndpoint)
    {
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
        // Find endpoint by slug
        $endpoint = MailerEndpoint::where('slug', $slug)->first();

        if (! $endpoint) {
            return response()->json([
                'success' => false,
                'message' => 'Endpoint not found',
            ], 404);
        }

        if (! $endpoint->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Endpoint is inactive',
            ], 403);
        }

        // Validate API token if provided in header
        $providedToken = $request->header('X-API-Token');
        if ($providedToken && $providedToken !== $endpoint->api_token) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid API token',
            ], 401);
        }

        // Validate required variables
        if ($endpoint->required_variables) {
            $payload = $request->json()->all();
            $missingVars = [];

            foreach ($endpoint->required_variables as $var) {
                if (! data_get($payload, $var)) {
                    $missingVars[] = $var;
                }
            }

            if (! empty($missingVars)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Missing required variables: '.implode(', ', $missingVars),
                    'missing_variables' => $missingVars,
                ], 422);
            }
        }

        // Create endpoint log
        $log = MailerEndpointLog::create([
            'mailer_endpoint_id' => $endpoint->id,
            'payload' => $request->json()->all(),
            'status' => 'pending',
        ]);

        // Dispatch job to send email
        SendEndpointEmailJob::dispatch($log);

        // Update request count
        $endpoint->increment('requests_count');

        return response()->json([
            'success' => true,
            'message' => 'Email queued for sending',
            'log_id' => $log->id,
            'endpoint' => $endpoint->slug,
        ], 202);
    }

    /**
     * Get endpoint info
     * GET /api/email-endpoints/{slug}/info
     */
    public function info(string $slug): JsonResponse
    {
        $endpoint = MailerEndpoint::where('slug', $slug)
            ->with('template', 'language')
            ->first();

        if (! $endpoint) {
            return response()->json([
                'success' => false,
                'message' => 'Endpoint not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'slug' => $endpoint->slug,
                'name' => $endpoint->name,
                'type' => $endpoint->type,
                'source' => $endpoint->source,
                'expected_variables' => $endpoint->expected_variables ?? [],
                'required_variables' => $endpoint->required_variables ?? [],
                'template' => $endpoint->template ? [
                    'subject' => $endpoint->template->subject,
                    'preview' => substr($endpoint->template->content, 0, 200),
                ] : null,
                'is_active' => $endpoint->is_active,
            ],
        ]);
    }

    /**
     * Get endpoint status and logs
     * GET /api/email-endpoints/{slug}/status
     */
    public function status(string $slug): JsonResponse
    {
        $endpoint = MailerEndpoint::where('slug', $slug)->first();

        if (! $endpoint) {
            return response()->json([
                'success' => false,
                'message' => 'Endpoint not found',
            ], 404);
        }

        $successCount = $endpoint->successLogs()->count();
        $failedCount = $endpoint->failedLogs()->count();
        $totalCount = $endpoint->requests_count;

        return response()->json([
            'success' => true,
            'data' => [
                'slug' => $endpoint->slug,
                'is_active' => $endpoint->is_active,
                'total_requests' => $totalCount,
                'successful_emails' => $successCount,
                'failed_emails' => $failedCount,
                'success_rate' => $totalCount > 0 ? round(($successCount / $totalCount) * 100, 2) : 0,
                'last_request_at' => $endpoint->last_request_at?->toIso8601String(),
            ],
        ]);
    }
}
