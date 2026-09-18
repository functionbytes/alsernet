<?php

namespace Modules\HelpdeskLivechat\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Inbox;
use Modules\HelpdeskLivechat\Concerns\VerifiesConversationToken;
use Modules\HelpdeskLivechat\Http\Requests\SubmitPreChatFormRequest;
use Modules\HelpdeskLivechat\Models\PreChatForm;

class PreChatFormApiController extends Controller
{
    use VerifiesConversationToken;

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

        // Autorización por el token de conversación del widget (widget_pubsub_token,
        // comparado con hash_equals), NO por customer_id/customer_email — que son
        // secuenciales/adivinables y permitían a un visitante sobrescribir el
        // pre-chat y el email de conversaciones ajenas iterando el id.
        if (! $this->conversationTokenValid($conversation, $request)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $formData = $request->input('data', []);

        $metadata = is_array($conversation->metadata) ? $conversation->metadata : [];
        $metadata['pre_chat'] = $formData;

        $conversation->update(['metadata' => $metadata]);

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
}
