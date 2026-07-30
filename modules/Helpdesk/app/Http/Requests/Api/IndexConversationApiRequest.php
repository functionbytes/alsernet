<?php

namespace Modules\Helpdesk\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @queryParam status string Filter by status slug. Example: open
 * @queryParam priority string Filter by priority. Enum: low,normal,high,urgent. Example: high
 * @queryParam assignee_id integer Filter by assignee user ID. Example: 5
 * @queryParam q string Search in subject or customer name. Example: soporte
 * @queryParam per_page integer Number of results per page (default 15, max 100). Example: 20
 */
class IndexConversationApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('helpdesk.conversations.view');
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', 'string', 'max:50'],
            'priority' => ['nullable', 'string', 'in:low,normal,high,urgent'],
            'assignee_id' => ['nullable', 'integer'],
            'q' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
