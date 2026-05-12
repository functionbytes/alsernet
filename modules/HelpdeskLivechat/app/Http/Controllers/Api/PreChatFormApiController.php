<?php

namespace Modules\HelpdeskLivechat\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Inbox;
use Modules\HelpdeskLivechat\Http\Requests\SubmitPreChatFormRequest;
use Modules\HelpdeskLivechat\Models\PreChatForm;

class PreChatFormApiController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $inboxId = $request->query('inbox_id') ? (int) $request->query('inbox_id') : null;

        if ($inboxId && ! Inbox::query()->whereKey($inboxId)->exists()) {
            return response()->json(['form' => null]);
        }

        $form = PreChatForm::findForInbox($inboxId);

        if (! $form) {
            return response()->json(['form' => null]);
        }

        return response()->json([
            'form' => [
                'id' => $form->id,
                'name' => $form->name,
                'fields' => $form->fields,
            ],
        ]);
    }

    public function submit(SubmitPreChatFormRequest $request): JsonResponse
    {
        $conversation = Conversation::with('customer')->findOrFail($request->conversation_id);

        if (! $this->ownsConversation($request, $conversation)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $formData = $request->input('data', []);

        $existing = $conversation->getRawOriginal('metadata');
        $metadata = $existing ? json_decode($existing, true) : [];
        $metadata['pre_chat'] = $formData;

        $conversation->update(['metadata' => json_encode($metadata)]);

        $customer = $conversation->customer;
        $customerUpdated = false;

        if ($customer) {
            $updates = [];

            $submittedEmail = $formData['email'] ?? null;
            if ($submittedEmail) {
                $isAnonymous = str_ends_with((string) $customer->email, '@anonymous.local');
                if (! $customer->email || $isAnonymous) {
                    $updates['email'] = $submittedEmail;
                }
            }

            if (! empty($formData['name']) && ! $customer->name) {
                $updates['name'] = $formData['name'];
            }

            if (! empty($formData['phone']) && ! $customer->phone) {
                $updates['phone'] = $formData['phone'];
            }

            if (! empty($updates)) {
                $customer->update($updates);
                $customerUpdated = true;
            }
        }

        return response()->json([
            'success' => true,
            'customer_updated' => $customerUpdated,
            'customer' => $customer ? [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
            ] : null,
        ]);
    }

    /**
     * Verify that the requester owns the conversation via customer_id or customer_email.
     * At least one must match; if neither is provided or both fail → false.
     */
    private function ownsConversation(SubmitPreChatFormRequest $request, Conversation $conversation): bool
    {
        $customer = $conversation->customer;

        if (! $customer) {
            return false;
        }

        $requestedId = $request->integer('customer_id') ?: null;
        if ($requestedId && $customer->id === $requestedId) {
            return true;
        }

        $requestedEmail = $request->input('customer_email');
        if ($requestedEmail && strcasecmp((string) $customer->email, $requestedEmail) === 0) {
            return true;
        }

        return false;
    }
}
