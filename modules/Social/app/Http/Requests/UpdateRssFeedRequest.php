<?php

namespace Modules\Social\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRssFeedRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('rss_feed'));
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'feed_url' => ['required', 'url', 'max:500'],
            'social_account_id' => ['nullable', 'exists:social_accounts,id'],
            'post_template' => ['nullable', 'string', 'max:1000'],
            'hashtags' => ['nullable', 'string'],
            'fetch_interval' => ['required', 'integer', 'min:15', 'max:1440'],
            'auto_publish' => ['boolean'],
            'active' => ['boolean'],
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

        $this->merge([
            'auto_publish' => $this->boolean('auto_publish'),
            'active' => $this->boolean('active'),
        ]);
    }
}
