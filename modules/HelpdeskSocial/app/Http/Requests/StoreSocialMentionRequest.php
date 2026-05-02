<?php

namespace Modules\HelpdeskSocial\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSocialMentionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermissionTo('helpdesksocial.manage-rules') ?? false;
    }

    public function rules(): array
    {
        return [
            'platform' => 'required|string|in:facebook,instagram,tiktok,x,linkedin',
            'external_id' => 'required|string|max:255',
            'author_name' => 'nullable|string|max:255',
            'author_username' => 'nullable|string|max:255',
            'body' => 'required|string',
            'url' => 'nullable|url|max:500',
            'matched_keywords' => 'nullable|array',
            'sentiment' => 'nullable|string|in:positive,neutral,negative',
            'sentiment_score' => 'nullable|numeric|between:-1,1',
            'engagement_count' => 'nullable|integer|min:0',
            'status' => 'nullable|string|in:new,reviewed,archived,dismissed',
            'discovered_at' => 'nullable|date',
            'posted_at' => 'nullable|date',
        ];
    }

    public function messages(): array
    {
        return [
            'platform.required' => 'La plataforma es obligatoria.',
            'external_id.required' => 'El ID externo es obligatorio.',
            'body.required' => 'El contenido es obligatorio.',
        ];
    }
}
