<?php

namespace Modules\HelpdeskChat\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\HelpdeskChat\Models\Channels\Web;
use Modules\HelpdeskChat\Models\Contacts\Contact;
use Modules\HelpdeskChat\Models\Customers\CustomerSession;

class SessionController extends Controller
{
    /**
     * Initialize a new widget session.
     *
     * POST /api/widget/session/init
     */
    public function init(Request $request): JsonResponse
    {
        // Validate website_token
        $websiteToken = $request->header('X-Website-Token') ?? $request->input('website_token');

        if (! $websiteToken) {
            return response()->json([
                'success' => false,
                'message' => 'Website token is required',
            ], 400);
        }

        $webWidget = Web::where('website_token', $websiteToken)->first();

        if (! $webWidget) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid website token',
            ], 404);
        }

        // Validate request data
        $validated = $request->validate([
            'email' => 'nullable|email|max:255',
            'name' => 'nullable|string|max:255',
            'phone_number' => 'nullable|string|max:50',
            'custom_attributes' => 'nullable|array',
        ]);

        // Create or find contact if email or phone provided
        $contact = null;
        if (! empty($validated['email']) || ! empty($validated['phone_number'])) {
            $contact = $this->findOrCreateContact($webWidget->account_id, $validated);
        }

        // Create customer session
        $session = CustomerSession::create([
            'customer_id' => $contact?->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'country' => $request->header('CF-IPCountry'), // Cloudflare header
            'session_token' => Str::random(40),
            'last_activity_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Session initialized successfully',
            'data' => [
                'session_token' => $session->session_token,
                'session_id' => $session->id,
                'contact' => $contact ? [
                    'id' => $contact->id,
                    'name' => $contact->name,
                    'email' => $contact->email,
                    'phone_number' => $contact->phone_number,
                ] : null,
                'widget_config' => $webWidget->getWidgetConfig(),
            ],
        ]);
    }

    /**
     * Sync existing session.
     *
     * POST /api/widget/session/sync
     */
    public function sync(Request $request): JsonResponse
    {
        $sessionToken = $request->header('X-Session-Token') ?? $request->input('session_token');

        if (! $sessionToken) {
            return response()->json([
                'success' => false,
                'message' => 'Session token is required',
            ], 400);
        }

        $session = CustomerSession::where('session_token', $sessionToken)->first();

        if (! $session) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid session token',
            ], 404);
        }

        // Update session activity
        $session->updateActivity();

        // Optionally update contact info if provided
        if ($request->filled(['email', 'name', 'phone_number']) && ! $session->customer_id) {
            $webWidget = Web::where('website_token', $request->header('X-Website-Token'))->first();

            if ($webWidget) {
                $contact = $this->findOrCreateContact($webWidget->account_id, $request->only(['email', 'name', 'phone_number']));
                $session->update(['customer_id' => $contact->id]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Session synced successfully',
            'data' => [
                'session_id' => $session->id,
                'is_active' => $session->isActive(),
                'last_activity_at' => $session->last_activity_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * Get session details.
     *
     * GET /api/widget/session
     */
    public function show(Request $request): JsonResponse
    {
        $sessionToken = $request->header('X-Session-Token') ?? $request->input('session_token');

        if (! $sessionToken) {
            return response()->json([
                'success' => false,
                'message' => 'Session token is required',
            ], 400);
        }

        $session = CustomerSession::where('session_token', $sessionToken)
            ->with(['customer', 'pageVisits'])
            ->first();

        if (! $session) {
            return response()->json([
                'success' => false,
                'message' => 'Session not found',
            ], 404);
        }

        // Update activity on every session check
        $session->updateActivity();

        return response()->json([
            'success' => true,
            'data' => [
                'session_id' => $session->id,
                'session_token' => $session->session_token,
                'is_active' => $session->isActive(),
                'contact' => $session->customer ? [
                    'id' => $session->customer->id,
                    'name' => $session->customer->name,
                    'email' => $session->customer->email,
                    'phone_number' => $session->customer->phone_number,
                ] : null,
                'device_type' => $session->device_type,
                'browser' => $session->browser,
                'country' => $session->country,
                'last_activity_at' => $session->last_activity_at->toIso8601String(),
                'page_visits_count' => $session->pageVisits->count(),
            ],
        ]);
    }

    /**
     * Find or create contact based on provided data.
     */
    protected function findOrCreateContact(int $accountId, array $data): Contact
    {
        $query = Contact::where('account_id', $accountId);

        // Try to find by email first
        if (! empty($data['email'])) {
            $contact = $query->where('email', $data['email'])->first();
            if ($contact) {
                // Update name if provided and not set
                if (! empty($data['name']) && empty($contact->name)) {
                    $contact->update(['name' => $data['name']]);
                }

                return $contact;
            }
        }

        // Try to find by phone
        if (! empty($data['phone_number'])) {
            $contact = Contact::where('account_id', $accountId)
                ->where('phone_number', $data['phone_number'])
                ->first();

            if ($contact) {
                return $contact;
            }
        }

        // Create new contact
        return Contact::create([
            'account_id' => $accountId,
            'name' => $data['name'] ?? 'Anonymous Visitor',
            'email' => $data['email'] ?? null,
            'phone_number' => $data['phone_number'] ?? null,
            'custom_attributes' => $data['custom_attributes'] ?? [],
            'last_activity_at' => now(),
        ]);
    }
}
