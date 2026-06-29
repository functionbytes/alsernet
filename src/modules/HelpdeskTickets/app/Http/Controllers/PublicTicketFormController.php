<?php

namespace Modules\HelpdeskTickets\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskLivechat\Models\Channels\Web;
use Modules\HelpdeskTickets\Models\TicketAttachment;
use Modules\HelpdeskTickets\Models\TicketCategory;
use Modules\HelpdeskTickets\Models\TicketMessage;
use Modules\HelpdeskTickets\Services\TicketCategoryValidationBuilder;
use Modules\HelpdeskTickets\Services\TicketService;

class PublicTicketFormController extends Controller
{
    public function __construct(
        private readonly TicketService $ticketService,
        private readonly TicketCategoryValidationBuilder $validationBuilder,
    ) {}

    public function show(string $slug): JsonResponse
    {
        $category = TicketCategory::where('slug', $slug)
            ->where('active', true)
            ->firstOrFail();

        $fields = $category->fields()
            ->where('is_visible', true)
            ->ordered()
            ->get()
            ->map(fn ($field) => [
                'key' => $field->key,
                'type' => $field->type,
                'label' => $field->label,
                'placeholder' => $field->placeholder,
                'help_text' => $field->help_text,
                'is_required' => $field->is_required,
                'width' => $field->width,
                'options' => $field->options ?? [],
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
                'icon' => $category->icon,
                'color' => $category->color,
                'fields' => $fields,
            ],
        ]);
    }

    public function submit(Request $request, string $slug): JsonResponse
    {
        $category = TicketCategory::where('slug', $slug)
            ->where('active', true)
            ->with(['fields' => fn ($q) => $q->where('is_visible', true)->ordered()])
            ->firstOrFail();

        $built = $this->validationBuilder->buildForSubmission($category);
        $validated = $request->validate($built['rules'], [], $built['attributes']);

        if ($request->filled('website_token')) {
            $web = Web::where('website_token', $validated['website_token'])->first();

            if (! $web) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token de widget no válido.',
                ], 422);
            }
        }

        $fileFieldKeys = $category->fields
            ->where('type', 'file')
            ->where('is_visible', true)
            ->pluck('key')
            ->all();

        $baseKeys = ['subject', 'description', 'customer_email', 'customer_name', 'website_token'];
        $customFields = collect($validated)
            ->except([...$baseKeys, ...$fileFieldKeys])
            ->all();

        $customer = Customer::firstOrCreate(
            ['email' => $validated['customer_email']],
            ['name' => $validated['customer_name'] ?? $validated['customer_email']],
        );

        $ticket = $this->ticketService->createTicket([
            'subject' => $validated['subject'],
            'description' => $validated['description'],
            'customer_id' => $customer->id,
            'category_id' => $category->id,
            'source' => 'web_form',
            'custom_fields' => $customFields,
        ]);

        $uploadedFiles = collect($fileFieldKeys)
            ->filter(fn ($key) => $request->hasFile($key))
            ->map(fn ($key) => $request->file($key))
            ->filter(fn ($file) => $file instanceof UploadedFile && $file->isValid())
            ->values()
            ->all();

        if (! empty($uploadedFiles)) {
            $this->storeAttachments($uploadedFiles, $ticket->id);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number,
            ],
        ], 201);
    }

    /**
     * @param  array<int, UploadedFile>  $files
     */
    private function storeAttachments(array $files, int $ticketId): void
    {
        $message = TicketMessage::create([
            'ticket_id' => $ticketId,
            'is_internal' => false,
            'message' => '',
        ]);

        $disk = config('helpdesk.attachments.disk', 'local');
        $path = config('helpdesk.attachments.path', 'helpdesk/attachments');

        foreach ($files as $file) {
            $stored = $file->store($path, $disk);

            TicketAttachment::create([
                'ticket_message_id' => $message->id,
                'filename' => $file->getClientOriginalName(),
                'original_filename' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'path' => $stored,
            ]);
        }
    }
}
