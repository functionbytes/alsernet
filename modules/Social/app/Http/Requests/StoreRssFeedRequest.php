<?php

namespace Modules\Social\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Social\Models\RssFeed;

class StoreRssFeedRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', RssFeed::class);
    }

    public function rules(): array
    {
        return [
            'account_id' => ['required', 'exists:chat_accounts,id'],
            'name' => ['required', 'string', 'max:255'],
            'feed_url' => ['required', 'url', 'max:500'],
            'social_account_id' => ['nullable', 'exists:social_accounts,id'],
            'campaign_id' => ['nullable', 'exists:social_campaigns,id'],
            'post_template' => ['nullable', 'string', 'max:1000'],
            'hashtags' => ['nullable', 'array'],
            'fetch_interval' => ['required', 'integer', 'min:15', 'max:1440'],
            'auto_publish' => ['boolean'],
            'active' => ['boolean'],
            'next_fetch_at' => ['required', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del feed es obligatorio',
            'feed_url.required' => 'La URL del feed es obligatoria',
            'feed_url.url' => 'Debe ser una URL válida',
            'fetch_interval.min' => 'El intervalo mínimo es de 15 minutos',
            'fetch_interval.max' => 'El intervalo máximo es de 1440 minutos (24 horas)',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Convert hashtags string to array
        if ($this->has('hashtags') && is_string($this->hashtags)) {
            $hashtags = array_filter(
                array_map('trim', explode(' ', $this->hashtags)),
                fn ($tag) => ! empty($tag)
            );

            $this->merge([
                'hashtags' => ! empty($hashtags) ? $hashtags : null,
            ]);
        }

        // Calculate next fetch time
        $fetchInterval = $this->fetch_interval ?? 60;

        $this->merge([
            'account_id' => auth()->user()->account_id,
            'auto_publish' => $this->boolean('auto_publish'),
            'active' => $this->boolean('active', true),
            'next_fetch_at' => now()->addMinutes($fetchInterval),
        ]);
    }
}
