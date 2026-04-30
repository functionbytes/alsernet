<?php

namespace Modules\Chat\Http\Controllers\Settings\Channels;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Chat\Http\Controllers\Controller;
use Modules\Chat\Models\Channels\Whatsapp;
use Modules\Chat\Models\Inbox\Inbox;
use Modules\Chat\Services\Channels\Whatsapp\Providers\CloudApiService;
use Modules\Chat\Services\Channels\Whatsapp\WhatsappProviderFactory;

class WhatsappController extends Controller
{
    /**
     * Display a listing of WhatsApp channels.
     */
    public function index(Request $request)
    {
        $accountId = $request->user()->account_id;

        $whatsapps = Whatsapp::where('account_id', $accountId)
            ->with('inbox')
            ->latest()
            ->get();

        return view('Chat::settings.channels.whatsapps.index', compact('whatsapps'));
    }

    /**
     * Show the form for creating a new WhatsApp channel.
     */
    public function create()
    {
        return view('Chat::settings.channels.whatsapps.create');
    }

    /**
     * Store a newly created WhatsApp channel.
     */
    public function store(Request $request)
    {
        $rules = [
            'inbox_name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:20',
            'provider' => 'required|in:whatsapp_cloud,evolution_api,360dialog',
        ];

        // Provider-specific validation
        $provider = $request->input('provider');
        if ($provider === 'whatsapp_cloud') {
            $rules = array_merge($rules, [
                'access_token' => 'required|string',
                'phone_number_id' => 'required|string',
                'business_account_id' => 'required|string',
            ]);
        } elseif ($provider === '360dialog') {
            $rules = array_merge($rules, [
                'api_key' => 'required|string',
                'client_id' => 'nullable|string',
            ]);
        } elseif ($provider === 'evolution_api') {
            $rules = array_merge($rules, [
                'instance_name' => 'required|string',
                'api_url' => 'nullable|string',
                'api_key' => 'nullable|string',
            ]);
        }

        $validated = $request->validate($rules);

        DB::beginTransaction();
        try {
            // Build provider_config based on provider
            $providerConfig = $this->buildProviderConfig($validated['provider'], $validated);

            $whatsapp = Whatsapp::create([
                'account_id' => $request->user()->account_id,
                'phone_number' => $validated['phone_number'],
                'provider' => $validated['provider'],
                'provider_config' => $providerConfig,
                'webhook_url' => null, // Will be generated
            ]);

            Inbox::create([
                'account_id' => $request->user()->account_id,
                'name' => $validated['inbox_name'],
                'channel_type' => Whatsapp::class,
                'channel_id' => $whatsapp->id,
                'website_token' => Str::random(20),
            ]);

            DB::commit();

            return redirect()->route('settings.chat.channels.whatsapps.index')
                ->with('success', 'WhatsApp channel created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withInput()->with('error', 'Failed to create WhatsApp channel: '.$e->getMessage());
        }
    }

    /**
     * Display the specified WhatsApp channel.
     */
    public function show(Whatsapp $whatsapp)
    {
        $whatsapp->load('inbox');

        return view('Chat::settings.channels.whatsapps.show', compact('whatsapp'));
    }

    /**
     * Show the form for editing the specified WhatsApp channel.
     */
    public function edit(Whatsapp $whatsapp)
    {
        $whatsapp->load('inbox');

        return view('Chat::settings.channels.whatsapps.edit', compact('whatsapp'));
    }

    /**
     * Update the specified WhatsApp channel.
     */
    public function update(Request $request, Whatsapp $whatsapp)
    {
        $rules = [
            'inbox_name' => 'required|string|max:255',
        ];

        // Provider-specific validation (phone_number and provider are immutable)
        $provider = $whatsapp->provider;
        if ($provider === 'whatsapp_cloud') {
            $rules = array_merge($rules, [
                'phone_number_id' => 'required|string',
                'business_account_id' => 'required|string',
                'access_token' => 'nullable|string',
            ]);
        } elseif ($provider === '360dialog') {
            $rules = array_merge($rules, [
                'api_key' => 'required|string',
                'client_id' => 'nullable|string',
            ]);
        } elseif ($provider === 'evolution_api') {
            $rules = array_merge($rules, [
                'instance_name' => 'required|string',
                'api_url' => 'nullable|string',
                'api_key' => 'nullable|string',
            ]);
        }

        $validated = $request->validate($rules);

        // Build provider_config merging with existing so empty fields don't erase saved values
        $newConfig = $this->buildProviderConfig($provider, $validated);
        $providerConfig = array_merge($whatsapp->provider_config ?? [], array_filter($newConfig, fn ($v) => $v !== null && $v !== ''));

        $whatsapp->update([
            'provider_config' => $providerConfig,
        ]);

        if ($whatsapp->inbox) {
            $whatsapp->inbox->update(['name' => $validated['inbox_name']]);
        }

        return redirect()->route('settings.chat.channels.whatsapps.index')
            ->with('success', 'WhatsApp channel updated successfully.');
    }

    /**
     * Remove the specified WhatsApp channel.
     */
    public function destroy(Whatsapp $whatsapp)
    {
        $whatsapp->inbox?->delete();
        $whatsapp->delete();

        return redirect()->route('settings.chat.channels.whatsapps.index')
            ->with('success', 'WhatsApp channel deleted successfully.');
    }

    /**
     * Sync WhatsApp message templates.
     */
    public function syncTemplates(Whatsapp $whatsapp): JsonResponse
    {
        try {
            // For Cloud API, fetch templates from Meta
            if ($whatsapp->isCloudApi()) {
                // Validate credentials first
                if (! $whatsapp->getApiCredential() || ! $whatsapp->getBusinessAccountId()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Missing API credentials. Please edit this channel and provide valid Facebook access token and business account ID.',
                    ], 400);
                }

                $service = app(CloudApiService::class);
                $templates = $service->getTemplates(
                    $whatsapp->getApiCredential(),
                    $whatsapp->getBusinessAccountId()
                );

                if ($templates !== []) {
                    $whatsapp->syncTemplates($templates);

                    return response()->json([
                        'success' => true,
                        'message' => 'Templates synced successfully',
                        'count' => count($templates),
                    ]);
                }

                return response()->json([
                    'success' => false,
                    'message' => 'No templates found or invalid credentials. Please verify your Facebook API token and business account ID are correct.',
                ], 400);
            }

            return response()->json([
                'success' => false,
                'message' => 'Templates sync only available for WhatsApp Cloud API provider',
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to sync WhatsApp templates', [
                'whatsapp_id' => $whatsapp->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to sync templates: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Verify WhatsApp API connection.
     */
    public function verifyConnection(Whatsapp $whatsapp): JsonResponse
    {
        try {
            if (! $whatsapp->isCloudApi()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Connection verification only available for WhatsApp Cloud API provider',
                ], 400);
            }

            // Validate credentials exist
            if (! $whatsapp->getApiCredential() || ! $whatsapp->getPhoneNumberId()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Missing API credentials. Please configure access token and phone number ID.',
                ], 400);
            }

            $service = app(CloudApiService::class);
            $phoneInfo = $service->getPhoneNumberInfo(
                $whatsapp->getApiCredential(),
                $whatsapp->getPhoneNumberId()
            );

            if (empty($phoneInfo)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to retrieve phone number info. Please verify your API token and phone number ID are correct.',
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Connection verified successfully! ✅',
                'data' => [
                    'phone_number' => $phoneInfo['display_phone_number'] ?? 'N/A',
                    'status' => $phoneInfo['status'] ?? 'N/A',
                    'quality_rating' => $phoneInfo['quality_rating'] ?? 'N/A',
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to verify WhatsApp connection', [
                'whatsapp_id' => $whatsapp->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to verify connection: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check WhatsApp connection status.
     */
    public function connectionStatus(Whatsapp $whatsapp): JsonResponse
    {
        try {
            $provider = WhatsappProviderFactory::make($whatsapp);

            $status = $provider->getConnectionStatus();

            return response()->json([
                'success' => true,
                'status' => $status,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to check connection status: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get QR code for WhatsApp Web connection (Evolution API only).
     */
    public function qrCode(Whatsapp $whatsapp): JsonResponse
    {
        try {
            if (! $whatsapp->isEvolutionApi()) {
                return response()->json([
                    'success' => false,
                    'message' => 'QR code only available for Evolution API provider',
                ]);
            }

            $provider = WhatsappProviderFactory::make($whatsapp);

            $qrCode = $provider->getQrCode();

            return response()->json([
                'success' => true,
                'qr' => $qrCode,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get QR code: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Build provider_config array based on provider type.
     */
    private function buildProviderConfig(string $provider, array $validated): array
    {
        $config = [];

        if ($provider === 'whatsapp_cloud') {
            // WhatsApp Cloud API (Meta)
            if ($validated['access_token'] ?? null) {
                $config['access_token'] = $validated['access_token'];
            }
            if ($validated['phone_number_id'] ?? null) {
                $config['phone_number_id'] = $validated['phone_number_id'];
            }
            if ($validated['business_account_id'] ?? null) {
                $config['business_account_id'] = $validated['business_account_id'];
            }
        } elseif ($provider === '360dialog') {
            // 360Dialog provider
            if ($validated['api_key'] ?? null) {
                $config['api_key'] = $validated['api_key'];
            }
            if ($validated['client_id'] ?? null) {
                $config['client_id'] = $validated['client_id'];
            }
        } elseif ($provider === 'evolution_api') {
            // Evolution API (Baileys)
            if ($validated['instance_name'] ?? null) {
                $config['instance_name'] = $validated['instance_name'];
            }
            if ($validated['api_url'] ?? null) {
                $config['api_url'] = $validated['api_url'];
            }
            if ($validated['api_key'] ?? null) {
                $config['api_key'] = $validated['api_key'];
            }
        }

        return $config;
    }
}
