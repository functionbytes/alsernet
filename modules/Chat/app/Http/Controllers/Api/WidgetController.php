<?php

namespace Modules\Chat\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Modules\Chat\Http\Controllers\Controller;
use Modules\Chat\Http\Requests\Widget\SendWidgetMessageRequest;
use Modules\Chat\Http\Requests\Widget\UploadWidgetFileRequest;
use Modules\Chat\Services\Widget\WidgetConfigService;
use Modules\Chat\Services\Widget\WidgetContactService;
use Modules\Chat\Services\Widget\WidgetMessageService;

class WidgetController extends Controller
{
    public function __construct(
        private readonly WidgetConfigService $configService,
        private readonly WidgetContactService $contactService,
        private readonly WidgetMessageService $messageService
    ) {}

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

        $config = $this->configService->getConfig($request->website_token);

        if (! $config) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid widget token',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $config,
        ]);
    }

    /**
     * Get widget translations.
     *
     * GET /api/widget/translations/{lang?}
     */
    public function getTranslations(Request $request, ?string $lang = 'en'): JsonResponse
    {
        $translations = $this->configService->getTranslations($lang);

        return response()->json([
            'success' => true,
            'data' => [
                'locale' => $lang,
                'translations' => $translations,
            ],
        ]);
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

        try {
            $data = $this->contactService->createContact(
                $request->website_token,
                $request->only(['name', 'email', 'phone', 'custom_attributes'])
            );

            return response()->json([
                'success' => true,
                'message' => 'Customers created successfully',
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create contact: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get messages for a conversation.
     *
     * GET /api/widget/conversation/{conversation}/messages
     */
    public function getMessages(Request $request, $conversation): JsonResponse
    {
        try {
            $data = $this->messageService->getMessages($conversation);

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Send a message in a conversation.
     *
     * POST /api/widget/conversation/{conversation}/messages
     */
    public function sendMessage(SendWidgetMessageRequest $request, $conversation): JsonResponse
    {
        try {
            $data = $this->messageService->sendMessage($conversation, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Message sent successfully',
                'data' => $data,
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
    public function uploadFile(UploadWidgetFileRequest $request, $conversation): JsonResponse
    {
        try {
            $data = $this->messageService->uploadFile($conversation, $request->file('file'));

            return response()->json([
                'success' => true,
                'message' => 'File uploaded successfully',
                'data' => $data,
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
        try {
            $this->messageService->markAsRead($conversation);

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
}
