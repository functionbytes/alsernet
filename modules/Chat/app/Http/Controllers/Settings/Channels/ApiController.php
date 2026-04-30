<?php

namespace Modules\Chat\Http\Controllers\Settings\Channels;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Chat\Http\Controllers\Controller;
use Modules\Chat\Models\Channels\Api;
use Modules\Chat\Models\Inbox\Inbox;

class ApiController extends Controller
{
    /**
     * Display a listing of API channels.
     */
    public function index(Request $request)
    {
        $accountId = $request->user()->account_id;

        $apiChannels = Api::where('account_id', $accountId)
            ->with('inbox')
            ->latest()
            ->get();

        return view('Chat::settings.channels.api.index', compact('apiChannels'));
    }

    /**
     * Show the form for creating a new API channel.
     */
    public function create()
    {
        return view('Chat::settings.channels.api.create');
    }

    /**
     * Store a newly created API channel.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'inbox_name' => 'required|string|max:255',
            'identifier' => 'required|string|max:255|unique:chat_channel_apis,identifier',
            'webhook_url' => 'nullable|url|max:500',
            'additional_settings' => 'nullable|json',
        ]);

        DB::beginTransaction();
        try {
            // Create API channel
            $api = Api::create([
                'account_id' => $request->user()->account_id,
                'identifier' => $validated['identifier'],
                'webhook_url' => $validated['webhook_url'] ?? null,
                'hmac_token' => Str::random(64),
                'webhook_verify_token' => Str::random(32),
                'active' => true,
                'additional_settings' => $validated['additional_settings'] ?? null,
            ]);

            // Create inbox for this channel
            $inbox = Inbox::create([
                'account_id' => $request->user()->account_id,
                'name' => $validated['inbox_name'],
                'channel_type' => Api::class,
                'channel_id' => $api->id,
            ]);

            DB::commit();

            return redirect()
                ->route('settings.chat.channels.api.index')
                ->with('success', __('API channel created successfully!'));
        } catch (\Exception $e) {
            DB::rollBack();

            return back()
                ->withInput()
                ->with('error', __('Failed to create API channel: :error', ['error' => $e->getMessage()]));
        }
    }

    /**
     * Display the specified API channel.
     */
    public function show(Api $api)
    {
        $api->load('inbox', 'account');

        $incomingWebhookUrl = route('webhooks.api.receive', [
            'identifier' => $api->identifier,
        ]);

        return view('Chat::settings.channels.api.show', compact('api', 'incomingWebhookUrl'));
    }

    /**
     * Show the form for editing the specified API channel.
     */
    public function edit(Api $api)
    {
        return view('Chat::settings.channels.api.edit', compact('api'));
    }

    /**
     * Update the specified API channel.
     */
    public function update(Request $request, Api $api)
    {
        $validated = $request->validate([
            'inbox_name' => 'required|string|max:255',
            'identifier' => 'required|string|max:255|unique:chat_channel_apis,identifier,'.$api->id,
            'webhook_url' => 'nullable|url|max:500',
            'additional_settings' => 'nullable|json',
            'active' => 'nullable|boolean',
        ]);

        DB::beginTransaction();
        try {
            // Update API channel
            $api->update([
                'identifier' => $validated['identifier'],
                'webhook_url' => $validated['webhook_url'] ?? $api->webhook_url,
                'additional_settings' => $validated['additional_settings'] ?? $api->additional_settings,
                'active' => $validated['active'] ?? $api->active,
            ]);

            // Update inbox name
            if ($api->inbox) {
                $api->inbox->update([
                    'name' => $validated['inbox_name'],
                ]);
            }

            DB::commit();

            return redirect()
                ->route('settings.chat.channels.api.index')
                ->with('success', __('API channel updated successfully!'));
        } catch (\Exception $e) {
            DB::rollBack();

            return back()
                ->withInput()
                ->with('error', __('Failed to update API channel: :error', ['error' => $e->getMessage()]));
        }
    }

    /**
     * Remove the specified API channel.
     */
    public function destroy(Api $api)
    {
        try {
            // Delete inbox (will cascade delete conversation)
            if ($api->inbox) {
                $api->inbox->delete();
            }

            $api->delete();

            return redirect()
                ->route('settings.chat.channels.api.index')
                ->with('success', __('API channel deleted successfully!'));
        } catch (\Exception $e) {
            return back()->with('error', __('Failed to delete API channel: :error', ['error' => $e->getMessage()]));
        }
    }

    /**
     * Regenerate HMAC token for the API channel.
     */
    public function regenerateHmacToken(Request $request, Api $api)
    {
        try {
            $api->update([
                'hmac_token' => Str::random(64),
            ]);

            return response()->json([
                'success' => true,
                'message' => __('HMAC token regenerated successfully!'),
                'hmac_token' => $api->hmac_token,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Failed to regenerate HMAC token: :error', ['error' => $e->getMessage()]),
            ], 500);
        }
    }

    /**
     * Test API channel connection.
     */
    public function testConnection(Request $request, Api $api)
    {
        try {
            if (! $api->isConfigured()) {
                return response()->json([
                    'success' => false,
                    'message' => __('API channel is not properly configured.'),
                ], 400);
            }

            // If webhook URL is configured, we could optionally ping it here
            // For now, just validate configuration
            return response()->json([
                'success' => true,
                'message' => __('API channel configuration is valid!'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Connection test failed: :error', ['error' => $e->getMessage()]),
            ], 500);
        }
    }
}
