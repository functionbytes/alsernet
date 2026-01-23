<?php

namespace Modules\HelpdeskChat\Http\Controllers\Admin\Settings\Channels;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\HelpdeskChat\Http\Controllers\Controller;
use Modules\HelpdeskChat\Models\Accounts\Inbox;
use Modules\HelpdeskChat\Models\Channels\Whatsapp;
use Modules\HelpdeskChat\Models\Contacts\Contact;
use Modules\HelpdeskChat\Models\Contacts\ContactInbox;
use Modules\HelpdeskChat\Models\Conversations\Conversation;
use Modules\HelpdeskChat\Models\Conversations\ConversationMessage;
use Modules\HelpdeskChat\Services\Channels\Whatsapp\Providers\CloudApiService;
use Modules\HelpdeskChat\Services\Channels\Whatsapp\Providers\Dialog360Service;
use Modules\HelpdeskChat\Services\Channels\Whatsapp\Providers\EvolutionApiService;

class WhatsappController extends Controller
{
    protected Dialog360Service $dialog360;

    protected CloudApiService $cloudApi;

    protected EvolutionApiService $evolutionApi;

    public function __construct(Dialog360Service $dialog360, CloudApiService $cloudApi, EvolutionApiService $evolutionApi)
    {
        $this->dialog360 = $dialog360;
        $this->cloudApi = $cloudApi;
        $this->evolutionApi = $evolutionApi;
    }

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

        return view('helpdeskchat::admin.settings.channels.whatsapps.index', compact('whatsapps'));
    }

    /**
     * Show the form for creating a new WhatsApp channel.
     */
    public function create()
    {
        return view('helpdeskchat::admin.settings.channels.whatsapps.create');
    }

    /**
     * Store a newly created WhatsApp channel.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'inbox_name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:20',
            'provider' => 'required|in:360dialog,whatsapp_cloud,evolution_api',

            // 360Dialog fields
            'dialog360_api_key' => 'required_if:provider,360dialog',
            'dialog360_client_id' => 'nullable|string',

            // WhatsApp Cloud API fields
            'cloud_phone_number_id' => 'required_if:provider,whatsapp_cloud',
            'cloud_business_account_id' => 'required_if:provider,whatsapp_cloud',
            'cloud_access_token' => 'required_if:provider,whatsapp_cloud',

            // Evolution API fields
            'evolution_instance_name' => 'required_if:provider,evolution_api',
            'evolution_api_url' => 'nullable|url',
            'evolution_api_key' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Build provider config based on selected provider
            $providerConfig = [];

            if ($validated['provider'] === '360dialog') {
                $providerConfig = [
                    'api_key' => $validated['dialog360_api_key'],
                    'client_id' => $validated['dialog360_client_id'] ?? null,
                ];
            } elseif ($validated['provider'] === 'whatsapp_cloud') {
                $providerConfig = [
                    'phone_number_id' => $validated['cloud_phone_number_id'],
                    'business_account_id' => $validated['cloud_business_account_id'],
                    'access_token' => $validated['cloud_access_token'],
                ];
            } elseif ($validated['provider'] === 'evolution_api') {
                $providerConfig = [
                    'instance_name' => $validated['evolution_instance_name'],
                    'api_url' => $validated['evolution_api_url'] ?? config('channels.whatsapp.providers.evolution_api.api_url'),
                    'api_key' => $validated['evolution_api_key'] ?? config('channels.whatsapp.providers.evolution_api.api_key'),
                ];
            }

            // Create the WhatsApp channel
            $whatsapp = Whatsapp::create([
                'account_id' => $request->user()->account_id,
                'phone_number' => $validated['phone_number'],
                'provider' => $validated['provider'],
                'provider_config' => $providerConfig,
            ]);

            // Create the inbox for this channel
            Inbox::create([
                'account_id' => $request->user()->account_id,
                'channel_id' => $whatsapp->id,
                'channel_type' => Whatsapp::class,
                'name' => $validated['inbox_name'],
                'timezone' => config('app.timezone'),
            ]);

            // Create Evolution API instance if provider is evolution_api
            if ($validated['provider'] === 'evolution_api') {
                $webhookUrl = url("/api/webhooks/evolution/{$whatsapp->id}");
                $this->evolutionApi->createInstance(
                    $providerConfig['api_url'],
                    $providerConfig['api_key'],
                    $providerConfig['instance_name'],
                    $webhookUrl
                );
            }

            // Sync templates (not applicable for Evolution API)
            if ($validated['provider'] !== 'evolution_api') {
                $this->syncTemplates($whatsapp);
            }

            DB::commit();

            return redirect()
                ->route('admin.helpdesk.channels.whatsapps.show', $whatsapp)
                ->with('success', 'WhatsApp channel created successfully!');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()
                ->withInput()
                ->with('error', 'Failed to create WhatsApp channel: '.$e->getMessage());
        }
    }

    /**
     * Display the specified WhatsApp channel.
     */
    public function show(Whatsapp $whatsapp)
    {
        $whatsapp->load('inbox');

        return view('helpdeskchat::admin.settings.channels.whatsapps.show', compact('whatsapp'));
    }

    /**
     * Show the form for editing the WhatsApp channel.
     */
    public function edit(Whatsapp $whatsapp)
    {
        $whatsapp->load('inbox');

        return view('helpdeskchat::admin.settings.channels.whatsapps.edit', compact('whatsapp'));
    }

    /**
     * Update the specified WhatsApp channel.
     */
    public function update(Request $request, Whatsapp $whatsapp)
    {
        $validated = $request->validate([
            'inbox_name' => 'required|string|max:255',
        ]);

        DB::beginTransaction();
        try {
            // Update inbox name
            if ($whatsapp->inbox) {
                $whatsapp->inbox->update(['name' => $validated['inbox_name']]);
            }

            DB::commit();

            return redirect()
                ->route('admin.helpdesk.channels.whatsapps.show', $whatsapp)
                ->with('success', 'WhatsApp channel updated successfully!');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()
                ->withInput()
                ->with('error', 'Failed to update WhatsApp channel: '.$e->getMessage());
        }
    }

    /**
     * Remove the specified WhatsApp channel.
     */
    public function destroy(Whatsapp $whatsapp)
    {
        try {
            $whatsapp->delete();

            return redirect()
                ->route('admin.helpdesk.channels.whatsapps.index')
                ->with('success', 'WhatsApp channel deleted successfully!');
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Failed to delete WhatsApp channel: '.$e->getMessage());
        }
    }

    /**
     * Sync message templates from provider.
     */
    public function syncTemplates(Whatsapp $whatsapp)
    {
        try {
            $templates = [];

            if ($whatsapp->is360Dialog()) {
                $templates = $this->dialog360->getTemplates($whatsapp->getApiCredential());
            } elseif ($whatsapp->isCloudApi()) {
                $config = $whatsapp->provider_config;
                $templates = $this->cloudApi->getTemplates(
                    $whatsapp->getApiCredential(),
                    $config['business_account_id']
                );
            }

            $whatsapp->syncTemplates($templates);

            return back()->with('success', 'Templates synced successfully! Found '.count($templates).' templates.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to sync templates: '.$e->getMessage());
        }
    }

    /**
     * Get Evolution API instance connection status.
     */
    public function connectionStatus(Whatsapp $whatsapp)
    {
        if (! $whatsapp->isEvolutionApi()) {
            return response()->json(['error' => 'Not an Evolution API instance'], 400);
        }

        try {
            $status = $this->evolutionApi->getConnectionState(
                $whatsapp->getApiUrl(),
                $whatsapp->getApiCredential(),
                $whatsapp->getInstanceName()
            );

            return response()->json($status);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage(), 'state' => 'error'], 500);
        }
    }

    /**
     * Get Evolution API instance QR code.
     */
    public function qrCode(Whatsapp $whatsapp)
    {
        if (! $whatsapp->isEvolutionApi()) {
            return response()->json(['error' => 'Not an Evolution API instance'], 400);
        }

        try {
            $result = $this->evolutionApi->connect(
                $whatsapp->getApiUrl(),
                $whatsapp->getApiCredential(),
                $whatsapp->getInstanceName()
            );

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Show Evolution API instance settings.
     */
    public function settings(Whatsapp $whatsapp)
    {
        if (! $whatsapp->isEvolutionApi()) {
            return redirect()->route('admin.helpdesk.channels.whatsapps.show', $whatsapp)
                ->with('error', 'Advanced settings are only available for Evolution API instances.');
        }

        $whatsapp->load('inbox');

        try {
            // Get instance settings
            $settings = $this->evolutionApi->getSettings(
                $whatsapp->getApiUrl(),
                $whatsapp->getApiCredential(),
                $whatsapp->getInstanceName()
            );

            // Get Chatwoot integration settings
            $chatwootSettings = $this->evolutionApi->getChatwootSettings(
                $whatsapp->getApiUrl(),
                $whatsapp->getApiCredential(),
                $whatsapp->getInstanceName()
            );

            return view('helpdeskchat::admin.settings.channels.whatsapps.settings', compact('whatsapp', 'settings', 'chatwootSettings'));
        } catch (\Exception $e) {
            return redirect()->route('admin.helpdesk.channels.whatsapps.show', $whatsapp)
                ->with('error', 'Failed to load settings: '.$e->getMessage());
        }
    }

    /**
     * Logout Evolution API instance.
     */
    public function logout(Whatsapp $whatsapp)
    {
        if (! $whatsapp->isEvolutionApi()) {
            return back()->with('error', 'Logout is only available for Evolution API instances.');
        }

        try {
            $this->evolutionApi->logout(
                $whatsapp->getApiUrl(),
                $whatsapp->getApiCredential(),
                $whatsapp->getInstanceName()
            );

            return back()->with('success', 'Instance disconnected successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to logout: '.$e->getMessage());
        }
    }

    /**
     * Restart Evolution API instance.
     */
    public function restart(Whatsapp $whatsapp)
    {
        if (! $whatsapp->isEvolutionApi()) {
            return back()->with('error', 'Restart is only available for Evolution API instances.');
        }

        try {
            $this->evolutionApi->restart(
                $whatsapp->getApiUrl(),
                $whatsapp->getApiCredential(),
                $whatsapp->getInstanceName()
            );

            return back()->with('success', 'Instance restarted successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to restart: '.$e->getMessage());
        }
    }

    /**
     * Sync contacts from Evolution API instance.
     */
    public function syncContacts(Whatsapp $whatsapp)
    {
        if (! $whatsapp->isEvolutionApi()) {
            return back()->with('error', 'Sync contacts is only available for Evolution API instances.');
        }

        try {
            $whatsapp->load('inbox');

            $contactsData = $this->evolutionApi->fetchContacts(
                $whatsapp->getApiUrl(),
                $whatsapp->getApiCredential(),
                $whatsapp->getInstanceName()
            );

            $importedCount = 0;

            foreach ($contactsData as $contactData) {
                // Skip groups
                if (($contactData['isGroup'] ?? false) === true) {
                    continue;
                }

                // Extract phone number from remoteJid (format: 34604407939@s.whatsapp.net)
                $remoteJid = $contactData['remoteJid'] ?? null;

                if (! $remoteJid || ! str_contains($remoteJid, '@')) {
                    \Log::warning('Invalid remoteJid for contact', $contactData);

                    continue;
                }

                // Skip broadcast lists (@lid) and group mentions (@g.us)
                if (str_contains($remoteJid, '@lid') || str_contains($remoteJid, '@g.us')) {
                    continue;
                }

                // Only process individual contacts (@s.whatsapp.net or @c.us)
                if (! str_contains($remoteJid, '@s.whatsapp.net') && ! str_contains($remoteJid, '@c.us')) {
                    \Log::warning('Skipping non-individual contact', ['remoteJid' => $remoteJid]);

                    continue;
                }

                // Extract phone number
                $phoneNumber = str_replace(['@s.whatsapp.net', '@c.us'], '', $remoteJid);

                // Get contact name
                $contactName = $contactData['pushName'] ?? $contactData['name'] ?? $phoneNumber;

                // Create or update contact (only contact, no inbox relationship yet)
                Contact::updateOrCreate(
                    [
                        'account_id' => $whatsapp->account_id,
                        'phone_number' => $phoneNumber,
                    ],
                    [
                        'name' => $contactName,
                        'additional_attributes' => [
                            'whatsapp' => true,
                            'profile_picture' => $contactData['profilePicUrl'] ?? null,
                        ],
                    ]
                );

                $importedCount++;
            }

            return back()->with('success', "Synced and imported {$importedCount} contacts successfully!");
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to sync contacts: '.$e->getMessage());
        }
    }

    /**
     * Sync chats from Evolution API instance.
     */
    public function syncChats(Whatsapp $whatsapp)
    {
        if (! $whatsapp->isEvolutionApi()) {
            return back()->with('error', 'Sync chats is only available for Evolution API instances.');
        }

        try {
            $whatsapp->load('inbox');

            $chatsData = $this->evolutionApi->fetchChats(
                $whatsapp->getApiUrl(),
                $whatsapp->getApiCredential(),
                $whatsapp->getInstanceName()
            );

            $importedCount = 0;

            foreach ($chatsData as $chatData) {
                // Skip groups if desired (optional - you might want to keep groups)
                // Uncomment the next 3 lines if you don't want to import group chats
                // if (($chatData['isGroup'] ?? false) === true) {
                //     continue;
                // }

                // Extract phone number from remoteJid
                $remoteJid = $chatData['remoteJid'] ?? null;

                if (! $remoteJid || ! str_contains($remoteJid, '@')) {
                    \Log::warning('Invalid remoteJid for chat', $chatData);

                    continue;
                }

                // Skip broadcast lists (@lid)
                if (str_contains($remoteJid, '@lid')) {
                    continue;
                }

                // Only process individual contacts and groups
                if (! str_contains($remoteJid, '@s.whatsapp.net') && ! str_contains($remoteJid, '@c.us') && ! str_contains($remoteJid, '@g.us')) {
                    \Log::warning('Skipping non-chat remoteJid', ['remoteJid' => $remoteJid]);

                    continue;
                }

                $phoneNumber = str_replace(['@s.whatsapp.net', '@c.us', '@g.us'], '', $remoteJid);

                // Find or create contact
                $contact = Contact::firstOrCreate(
                    [
                        'account_id' => $whatsapp->account_id,
                        'phone_number' => $phoneNumber,
                    ],
                    [
                        'name' => $chatData['name'] ?? $phoneNumber,
                        'additional_attributes' => ['whatsapp' => true],
                    ]
                );

                // Find or create ContactInbox
                $contactInbox = ContactInbox::firstOrCreate(
                    [
                        'inbox_id' => $whatsapp->inbox->id,
                        'source_id' => $phoneNumber,
                    ],
                    [
                        'contact_id' => $contact->id,
                    ]
                );

                // Create or update conversation
                $conversation = Conversation::firstOrCreate(
                    [
                        'account_id' => $whatsapp->account_id,
                        'inbox_id' => $whatsapp->inbox->id,
                        'contact_id' => $contact->id,
                        'contact_inbox_id' => $contactInbox->id,
                    ],
                    [
                        'status' => 'open',
                        'priority' => 'medium',
                        'last_activity_at' => now(),
                        'additional_attributes' => [
                            'unread_count' => $chatData['unreadCount'] ?? 0,
                            'archived' => $chatData['archived'] ?? false,
                        ],
                    ]
                );

                // Update last_activity_at if chat has timestamp
                if (isset($chatData['lastMessageTimestamp'])) {
                    $conversation->update([
                        'last_activity_at' => \Carbon\Carbon::createFromTimestamp($chatData['lastMessageTimestamp']),
                    ]);
                }

                $importedCount++;
            }

            return back()->with('success', "Synced and imported {$importedCount} chats successfully!");
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to sync chats: '.$e->getMessage());
        }
    }

    /**
     * Sync messages from Evolution API instance.
     */
    public function syncMessages(Request $request, Whatsapp $whatsapp)
    {
        if (! $whatsapp->isEvolutionApi()) {
            return back()->with('error', 'Sync messages is only available for Evolution API instances.');
        }

        try {
            $whatsapp->load('inbox');

            // Get limit from request (default: 50 messages per chat)
            $limit = (int) $request->input('limit', 50);

            // Get default user for outgoing messages
            $defaultUser = User::where('account_id', $whatsapp->account_id)->first();
            if (! $defaultUser) {
                return back()->with('error', 'No user found in account to assign outgoing messages.');
            }

            // Get all conversation for this inbox
            $conversations = Conversation::where('inbox_id', $whatsapp->inbox->id)
                ->with('contact')
                ->get();

            $totalMessages = 0;
            $conversationsProcessed = 0;

            foreach ($conversations as $conversation) {
                // Get contact inbox to get remoteJid
                $contactInbox = $conversation->contactInbox;
                if (! $contactInbox) {
                    continue;
                }

                // Build remoteJid from source_id
                $remoteJid = $contactInbox->source_id.'@s.whatsapp.net';

                try {
                    // Fetch messages from Evolution API
                    $response = \Http::withHeaders([
                        'apikey' => $whatsapp->getApiCredential(),
                    ])->post("{$whatsapp->getApiUrl()}/chat/findMessages/{$whatsapp->getInstanceName()}", [
                        'where' => [
                            'key' => [
                                'remoteJid' => $remoteJid,
                            ],
                        ],
                        'limit' => $limit,
                    ]);

                    if ($response->failed()) {
                        \Log::warning("Failed to fetch messages for {$remoteJid}", [
                            'response' => $response->body(),
                        ]);

                        continue;
                    }

                    // Evolution API returns messages in nested structure: { messages: { records: [...] } }
                    $data = $response->json();
                    $messages = $data['messages']['records'] ?? [];

                    foreach ($messages as $messageData) {
                        $key = $messageData['key'] ?? [];
                        $message = $messageData['message'] ?? [];

                        // Skip if no message ID
                        if (! isset($key['id'])) {
                            continue;
                        }

                        // Determine message type (incoming/outgoing)
                        $messageType = ($key['fromMe'] ?? false) ? 'outgoing' : 'incoming';

                        // Determine sender
                        if ($messageType === 'outgoing') {
                            $senderId = $defaultUser->id;
                            $senderType = User::class;
                        } else {
                            $senderId = $conversation->contact_id;
                            $senderType = Contact::class;
                        }

                        // Extract message content and type
                        $messageContent = $this->extractMessageContent($message);
                        $contentType = $this->getMessageType($message);

                        // Get message timestamp
                        $timestamp = isset($messageData['messageTimestamp'])
                            ? \Carbon\Carbon::createFromTimestamp($messageData['messageTimestamp'])
                            : now();

                        // Create message if it doesn't exist
                        $created = ConversationMessage::firstOrCreate(
                            [
                                'source_id' => $key['id'],
                                'inbox_id' => $whatsapp->inbox->id,
                            ],
                            [
                                'account_id' => $whatsapp->account_id,
                                'conversation_id' => $conversation->id,
                                'sender_id' => $senderId,
                                'sender_type' => $senderType,
                                'message_type' => $messageType,
                                'content_type' => $contentType,
                                'content' => $messageContent,
                                'status' => 'sent',
                                'created_at' => $timestamp,
                            ]
                        );

                        if ($created->wasRecentlyCreated) {
                            $totalMessages++;
                        }
                    }

                    $conversationsProcessed++;
                } catch (\Exception $e) {
                    \Log::error("Error syncing messages for conversation {$conversation->id}", [
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            return back()->with('success', "Synced {$totalMessages} messages from {$conversationsProcessed} conversation!");
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to sync messages: '.$e->getMessage());
        }
    }

    /**
     * Extract message content from Evolution API message format.
     */
    protected function extractMessageContent(array $message): string
    {
        if (isset($message['conversation'])) {
            return $message['conversation'];
        }

        if (isset($message['extendedTextMessage']['text'])) {
            return $message['extendedTextMessage']['text'];
        }

        if (isset($message['imageMessage'])) {
            return $message['imageMessage']['caption'] ?? '[Image]';
        }

        if (isset($message['videoMessage'])) {
            return $message['videoMessage']['caption'] ?? '[Video]';
        }

        if (isset($message['audioMessage'])) {
            return '[Audio]';
        }

        if (isset($message['documentMessage'])) {
            return $message['documentMessage']['caption'] ?? '[Document]';
        }

        if (isset($message['locationMessage'])) {
            return '[Location]';
        }

        if (isset($message['contactMessage'])) {
            return '[Contact]';
        }

        return '[Unsupported message]';
    }

    /**
     * Get message content type from Evolution API message format.
     */
    protected function getMessageType(array $message): string
    {
        if (isset($message['conversation']) || isset($message['extendedTextMessage'])) {
            return 'text';
        }

        if (isset($message['imageMessage'])) {
            return 'image';
        }

        if (isset($message['videoMessage'])) {
            return 'video';
        }

        if (isset($message['audioMessage'])) {
            return 'audio';
        }

        if (isset($message['documentMessage'])) {
            return 'document';
        }

        if (isset($message['locationMessage'])) {
            return 'location';
        }

        if (isset($message['contactMessage'])) {
            return 'contact';
        }

        return 'text';
    }

    /**
     * Update Evolution API instance settings.
     */
    public function updateSettings(Request $request, Whatsapp $whatsapp)
    {
        if (! $whatsapp->isEvolutionApi()) {
            return redirect()->route('admin.helpdesk.channels.whatsapps.show', $whatsapp)
                ->with('error', 'Advanced settings are only available for Evolution API instances.');
        }

        $validated = $request->validate([
            // Instance Settings
            'reject_call' => 'boolean',
            'msg_call' => 'nullable|string',
            'groups_ignore' => 'boolean',
            'always_online' => 'boolean',
            'read_messages' => 'boolean',
            'read_status' => 'boolean',
            'sync_full_history' => 'boolean',

            // Chatwoot Settings
            'chatwoot_enabled' => 'boolean',
            'chatwoot_account_id' => 'nullable|string',
            'chatwoot_token' => 'nullable|string',
            'chatwoot_url' => 'nullable|url',
            'chatwoot_sign_msg' => 'boolean',
            'chatwoot_reopen_conversation' => 'boolean',
            'chatwoot_conversation_pending' => 'boolean',
            'chatwoot_import_contacts' => 'boolean',
            'chatwoot_name_inbox' => 'nullable|string',
            'chatwoot_merge_brazil_contacts' => 'boolean',
            'chatwoot_import_messages' => 'boolean',
            'chatwoot_days_limit_import_messages' => 'nullable|integer',
            'chatwoot_organization' => 'nullable|string',
            'chatwoot_logo' => 'nullable|url',
        ]);

        try {
            // Update instance settings
            $instanceSettings = [
                'rejectCall' => $validated['reject_call'] ?? false,
                'msgCall' => $validated['msg_call'] ?? '',
                'groupsIgnore' => $validated['groups_ignore'] ?? false,
                'alwaysOnline' => $validated['always_online'] ?? false,
                'readMessages' => $validated['read_messages'] ?? false,
                'readStatus' => $validated['read_status'] ?? false,
                'syncFullHistory' => $validated['sync_full_history'] ?? false,
            ];

            $this->evolutionApi->updateSettings(
                $whatsapp->getApiUrl(),
                $whatsapp->getApiCredential(),
                $whatsapp->getInstanceName(),
                $instanceSettings
            );

            // Update Chatwoot settings if enabled
            if ($validated['chatwoot_enabled'] ?? false) {
                $chatwootSettings = [
                    'enabled' => true,
                    'accountId' => $validated['chatwoot_account_id'] ?? '',
                    'token' => $validated['chatwoot_token'] ?? '',
                    'url' => $validated['chatwoot_url'] ?? '',
                    'signMsg' => $validated['chatwoot_sign_msg'] ?? false,
                    'reopenConversation' => $validated['chatwoot_reopen_conversation'] ?? false,
                    'conversationPending' => $validated['chatwoot_conversation_pending'] ?? false,
                    'importContacts' => $validated['chatwoot_import_contacts'] ?? false,
                    'nameInbox' => $validated['chatwoot_name_inbox'] ?? '',
                    'mergeBrazilContacts' => $validated['chatwoot_merge_brazil_contacts'] ?? false,
                    'importMessages' => $validated['chatwoot_import_messages'] ?? false,
                    'daysLimitImportMessages' => $validated['chatwoot_days_limit_import_messages'] ?? 60,
                    'organization' => $validated['chatwoot_organization'] ?? '',
                    'logo' => $validated['chatwoot_logo'] ?? '',
                ];

                $this->evolutionApi->updateChatwootSettings(
                    $whatsapp->getApiUrl(),
                    $whatsapp->getApiCredential(),
                    $whatsapp->getInstanceName(),
                    $chatwootSettings
                );
            }

            return redirect()
                ->route('admin.helpdesk.channels.whatsapps.settings', $whatsapp)
                ->with('success', 'Settings updated successfully!');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to update settings: '.$e->getMessage());
        }
    }
}
