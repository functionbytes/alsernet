<?php

namespace Modules\HelpdeskChat\Http\Controllers\Admin\Settings\Channels;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\HelpdeskChat\Http\Controllers\Controller;
use Modules\HelpdeskChat\Models\Accounts\Inbox;
use Modules\HelpdeskChat\Models\Channels\Sms;
use Modules\HelpdeskChat\Services\Sms\SmsService;

class SmsController extends Controller
{
    /**
     * Display a listing of SMS channels.
     */
    public function index(Request $request)
    {
        $accountId = $request->user()->account_id;

        $smsChannels = Sms::where('account_id', $accountId)
            ->with('inbox')
            ->latest()
            ->get();

        return view('helpdeskchat::admin.settings.channels.sms.index', compact('smsChannels'));
    }

    /**
     * Show the form for creating a new SMS channel.
     */
    public function create()
    {
        return view('helpdeskchat::admin.settings.channels.sms.create');
    }

    /**
     * Store a newly created SMS channel.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'inbox_name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:20|regex:/^\+[1-9]\d{1,14}$/',
            'provider' => 'required|in:bandwidth,twilio,telnyx',
            'api_key' => 'required|string',
            'api_secret' => 'required|string',
            'application_id' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Create SMS channel
            $sms = Sms::create([
                'account_id' => $request->user()->account_id,
                'provider' => $validated['provider'],
                'phone_number' => $validated['phone_number'],
                'api_key' => $validated['api_key'],
                'api_secret' => $validated['api_secret'],
                'application_id' => $validated['application_id'] ?? null,
                'webhook_verify_token' => Str::random(32),
                'active' => true,
            ]);

            // Create inbox for this channel
            $inbox = Inbox::create([
                'account_id' => $request->user()->account_id,
                'name' => $validated['inbox_name'],
                'channel_type' => Sms::class,
                'channel_id' => $sms->id,
            ]);

            DB::commit();

            return redirect()
                ->route('admin.helpdesk.channels.sms.index')
                ->with('success', __('SMS channel created successfully!'));
        } catch (\Exception $e) {
            DB::rollBack();

            return back()
                ->withInput()
                ->with('error', __('Failed to create SMS channel: :error', ['error' => $e->getMessage()]));
        }
    }

    /**
     * Display the specified SMS channel.
     */
    public function show(Sms $sms)
    {
        $sms->load('inbox', 'account');

        $webhookUrl = route('webhooks.sms.receive', [
            'provider' => $sms->provider,
            'phone' => $sms->phone_number,
        ]);

        return view('helpdeskchat::admin.settings.channels.sms.show', compact('sms', 'webhookUrl'));
    }

    /**
     * Show the form for editing the specified SMS channel.
     */
    public function edit(Sms $sms)
    {
        return view('helpdeskchat::admin.settings.channels.sms.edit', compact('sms'));
    }

    /**
     * Update the specified SMS channel.
     */
    public function update(Request $request, Sms $sms)
    {
        $validated = $request->validate([
            'inbox_name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:20|regex:/^\+[1-9]\d{1,14}$/',
            'api_key' => 'nullable|string',
            'api_secret' => 'nullable|string',
            'application_id' => 'nullable|string',
            'active' => 'nullable|boolean',
        ]);

        DB::beginTransaction();
        try {
            // Update SMS channel
            $sms->update([
                'phone_number' => $validated['phone_number'],
                'api_key' => $validated['api_key'] ?? $sms->api_key,
                'api_secret' => $validated['api_secret'] ?? $sms->api_secret,
                'application_id' => $validated['application_id'] ?? $sms->application_id,
                'active' => $validated['active'] ?? $sms->active,
            ]);

            // Update inbox name
            if ($sms->inbox) {
                $sms->inbox->update([
                    'name' => $validated['inbox_name'],
                ]);
            }

            DB::commit();

            return redirect()
                ->route('admin.helpdesk.channels.sms.index')
                ->with('success', __('SMS channel updated successfully!'));
        } catch (\Exception $e) {
            DB::rollBack();

            return back()
                ->withInput()
                ->with('error', __('Failed to update SMS channel: :error', ['error' => $e->getMessage()]));
        }
    }

    /**
     * Remove the specified SMS channel.
     */
    public function destroy(Sms $sms)
    {
        try {
            // Delete inbox (will cascade delete conversation)
            if ($sms->inbox) {
                $sms->inbox->delete();
            }

            $sms->delete();

            return redirect()
                ->route('admin.helpdesk.channels.sms.index')
                ->with('success', __('SMS channel deleted successfully!'));
        } catch (\Exception $e) {
            return back()->with('error', __('Failed to delete SMS channel: :error', ['error' => $e->getMessage()]));
        }
    }

    /**
     * Test SMS channel connection.
     */
    public function testConnection(Request $request, Sms $sms, SmsService $smsService)
    {
        $validated = $request->validate([
            'test_number' => 'required|string|regex:/^\+[1-9]\d{1,14}$/',
            'test_message' => 'nullable|string|max:1000',
        ]);

        try {
            if (! $sms->isConfigured()) {
                return response()->json([
                    'success' => false,
                    'message' => __('SMS channel is not properly configured.'),
                ], 400);
            }

            // Send test SMS
            $result = $smsService->sendTestMessage(
                $sms,
                $validated['test_number'],
                $validated['test_message'] ?? null
            );

            return response()->json($result, $result['success'] ? 200 : 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Connection test failed: :error', ['error' => $e->getMessage()]),
            ], 500);
        }
    }
}
