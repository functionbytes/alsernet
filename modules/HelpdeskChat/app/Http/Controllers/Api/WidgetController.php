<?php

namespace Modules\HelpdeskChat\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Modules\HelpdeskChat\Models\Channels\Web;
use Modules\HelpdeskChat\Models\Contacts\Contact;
use Modules\HelpdeskChat\Models\Contacts\ContactInbox;
use Modules\HelpdeskChat\Models\Conversations\Conversation;
use Modules\HelpdeskChat\Models\Conversations\ConversationMessage;

class WidgetController extends Controller
{
    /**
     * Get widget configuration.
     *
     * GET /api/widget/config?website_token=xxx
     */
    public function config(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'website_token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $webWidget = Web::where('website_token', $request->website_token)
            ->with('inbox')
            ->first();

        if (! $webWidget) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid widget token',
            ], 404);
        }

        $config = $webWidget->getWidgetConfig();

        return response()->json([
            'success' => true,
            'data' => [
                'website_token' => $webWidget->website_token,
                'inbox_id' => $webWidget->inbox_id,
                'widget_color' => $config['widgetColor'] ?? '#1f93ff',
                'widget_position' => $config['widgetPosition'] ?? 'right',
                'welcome_title' => $config['welcomeTitle'] ?? 'Hello! 👋',
                'welcome_tagline' => $config['welcomeTagline'] ?? 'How can we help you today?',
                'pre_chat_form_enabled' => $config['preChatFormEnabled'] ?? false,
                'pre_chat_form_options' => $config['preChatFormOptions'] ?? [],
                'business_hours_enabled' => $webWidget->inbox?->working_hours_enabled ?? false,
                'reply_time' => $config['replyTime'] ?? null,
                'show_powered_by' => $config['showPoweredBy'] ?? true,
            ],
        ]);
    }

    /**
     * Get widget translations.
     *
     * GET /api/widget/translations/{lang?}
     */
    public function getTranslations(Request $request, ?string $lang = 'en'): JsonResponse
    {
        $supportedLocales = ['en', 'es', 'fr', 'de', 'pt', 'it'];

        if (! in_array($lang, $supportedLocales)) {
            $lang = 'en';
        }

        $translations = $this->getWidgetTranslations($lang);

        return response()->json([
            'success' => true,
            'data' => [
                'locale' => $lang,
                'translations' => $translations,
            ],
        ]);
    }

    /**
     * Get widget translation strings.
     */
    protected function getWidgetTranslations(string $lang): array
    {
        $translations = [
            'en' => [
                'send' => 'Send',
                'type_message' => 'Type your message...',
                'online' => 'We\'re online',
                'offline' => 'We\'re offline',
                'chat_with_us' => 'Chat with us',
                'start_conversation' => 'Start a conversation',
                'email' => 'Email',
                'name' => 'Name',
                'phone' => 'Phone (optional)',
                'message' => 'Message',
                'file_upload' => 'Upload file',
                'powered_by' => 'Powered by',
                'conversations' => 'Conversations',
                'new_conversation' => 'New conversation',
                'end_conversation' => 'End conversation',
                'agent_typing' => 'Agent is typing...',
            ],
            'es' => [
                'send' => 'Enviar',
                'type_message' => 'Escribe tu mensaje...',
                'online' => 'Estamos en línea',
                'offline' => 'Estamos desconectados',
                'chat_with_us' => 'Chatea con nosotros',
                'start_conversation' => 'Iniciar conversación',
                'email' => 'Correo electrónico',
                'name' => 'Nombre',
                'phone' => 'Teléfono (opcional)',
                'message' => 'Mensaje',
                'file_upload' => 'Subir archivo',
                'powered_by' => 'Desarrollado por',
                'conversations' => 'Conversaciones',
                'new_conversation' => 'Nueva conversación',
                'end_conversation' => 'Finalizar conversación',
                'agent_typing' => 'El agente está escribiendo...',
            ],
        ];

        return $translations[$lang] ?? $translations['en'];
    }

    /**
     * Create a new contact from widget.
     *
     * POST /api/widget/contact
     */
    public function createContact(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'website_token' => 'required|string',
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'custom_attributes' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $webWidget = Web::where('website_token', $request->website_token)->first();

        if (! $webWidget) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid widget token',
            ], 404);
        }

        try {
            DB::beginTransaction();

            // Find or create contact
            $contact = $this->findOrCreateContact(
                $webWidget->account_id,
                $request->only(['name', 'email', 'phone', 'custom_attributes'])
            );

            // Create contact inbox relationship
            $contactInbox = ContactInbox::firstOrCreate([
                'contact_id' => $contact->id,
                'inbox_id' => $webWidget->inbox_id,
            ], [
                'source_id' => $contact->id,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Contact created successfully',
                'data' => [
                    'contact_id' => $contact->id,
                    'name' => $contact->name,
                    'email' => $contact->email,
                    'phone' => $contact->phone_number,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create contact: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Find or create contact.
     */
    protected function findOrCreateContact(int $accountId, array $data): Contact
    {
        $query = Contact::where('account_id', $accountId);

        if (! empty($data['email'])) {
            $contact = $query->where('email', $data['email'])->first();
            if ($contact) {
                return $contact;
            }
        }

        if (! empty($data['phone'])) {
            $contact = $query->where('phone_number', $data['phone'])->first();
            if ($contact) {
                return $contact;
            }
        }

        return Contact::create([
            'account_id' => $accountId,
            'name' => $data['name'] ?? 'Anonymous',
            'email' => $data['email'] ?? null,
            'phone_number' => $data['phone'] ?? null,
            'custom_attributes' => $data['custom_attributes'] ?? [],
            'last_activity_at' => now(),
        ]);
    }

    /**
     * Get messages for a conversation.
     *
     * GET /api/widget/conversation/{conversation}/messages
     */
    public function getMessages(Request $request, $conversation): JsonResponse
    {
        $conv = Conversation::find($conversation);

        if (! $conv) {
            return response()->json([
                'success' => false,
                'message' => 'Conversation not found',
            ], 404);
        }

        $messages = ConversationMessage::where('conversation_id', $conversation)
            ->with('sender')
            ->orderBy('created_at', 'asc')
            ->limit(100)
            ->get()
            ->map(function ($message) {
                return [
                    'id' => $message->id,
                    'content' => $message->content,
                    'message_type' => $message->message_type,
                    'content_type' => $message->content_type,
                    'content_attributes' => $message->content_attributes,
                    'created_at' => $message->created_at->toIso8601String(),
                    'sender' => [
                        'id' => $message->sender_id,
                        'type' => class_basename($message->sender_type),
                        'name' => $message->sender?->name ?? 'Unknown',
                    ],
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'conversation_id' => $conversation,
                'messages' => $messages,
                'total' => $messages->count(),
            ],
        ]);
    }

    /**
     * Send a message in a conversation.
     *
     * POST /api/widget/conversation/{conversation}/messages
     */
    public function sendMessage(Request $request, $conversation): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'content' => 'required|string|max:10000',
            'content_type' => 'nullable|in:text,image,file,audio,video',
            'attachments' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $conv = Conversation::with(['contact', 'inbox'])->find($conversation);

        if (! $conv) {
            return response()->json([
                'success' => false,
                'message' => 'Conversation not found',
            ], 404);
        }

        try {
            // Create message
            $message = ConversationMessage::create([
                'conversation_id' => $conv->id,
                'inbox_id' => $conv->inbox_id,
                'account_id' => $conv->account_id,
                'sender_id' => $conv->contact_id,
                'sender_type' => Contact::class,
                'message_type' => 'incoming',
                'content' => $request->content,
                'content_type' => $request->input('content_type', 'text'),
                'content_attributes' => $request->input('attachments', []),
            ]);

            // Update conversation last activity
            $conv->update([
                'last_activity_at' => now(),
                'last_message_at' => now(),
            ]);

            // Broadcast to agents via WebSocket
            event(new \Modules\HelpdeskChat\Events\NewMessageEvent($message));

            return response()->json([
                'success' => true,
                'message' => 'Message sent successfully',
                'data' => [
                    'message_id' => $message->id,
                    'conversation_id' => $conv->id,
                    'created_at' => $message->created_at->toIso8601String(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send message: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload a file attachment.
     *
     * POST /api/widget/conversation/{conversation}/upload
     */
    public function uploadFile(Request $request, $conversation): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|max:10240', // Max 10MB
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $conv = Conversation::find($conversation);

        if (! $conv) {
            return response()->json([
                'success' => false,
                'message' => 'Conversation not found',
            ], 404);
        }

        try {
            $file = $request->file('file');
            $filename = time().'_'.$file->getClientOriginalName();
            $path = $file->storeAs('widget-uploads/'.$conv->account_id, $filename, 'public');

            $fileUrl = Storage::url($path);
            $fileType = $this->getFileType($file->getMimeType());

            // Create message with file attachment
            $message = ConversationMessage::create([
                'conversation_id' => $conv->id,
                'inbox_id' => $conv->inbox_id,
                'account_id' => $conv->account_id,
                'sender_id' => $conv->contact_id,
                'sender_type' => Contact::class,
                'message_type' => 'incoming',
                'content' => $file->getClientOriginalName(),
                'content_type' => $fileType,
                'content_attributes' => [
                    'file_url' => $fileUrl,
                    'file_name' => $file->getClientOriginalName(),
                    'file_size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                ],
            ]);

            $conv->update(['last_activity_at' => now()]);

            return response()->json([
                'success' => true,
                'message' => 'File uploaded successfully',
                'data' => [
                    'message_id' => $message->id,
                    'file_url' => $fileUrl,
                    'file_name' => $file->getClientOriginalName(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload file: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mark conversation as read.
     *
     * POST /api/widget/conversation/{conversation}/read
     */
    public function markAsRead(Request $request, $conversation): JsonResponse
    {
        $conv = Conversation::find($conversation);

        if (! $conv) {
            return response()->json([
                'success' => false,
                'message' => 'Conversation not found',
            ], 404);
        }

        try {
            // Mark all messages as read
            ConversationMessage::where('conversation_id', $conv->id)
                ->where('message_type', 'outgoing')
                ->whereNull('read_at')
                ->update(['read_at' => now()]);

            return response()->json([
                'success' => true,
                'message' => 'Conversation marked as read',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark as read: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Determine file type from MIME type.
     */
    protected function getFileType(string $mimeType): string
    {
        if (str_starts_with($mimeType, 'image/')) {
            return 'image';
        }

        if (str_starts_with($mimeType, 'video/')) {
            return 'video';
        }

        if (str_starts_with($mimeType, 'audio/')) {
            return 'audio';
        }

        return 'file';
    }
}
