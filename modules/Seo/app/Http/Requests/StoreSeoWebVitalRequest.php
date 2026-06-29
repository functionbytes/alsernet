<?php

namespace Modules\Seo\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSeoWebVitalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'url' => ['required', 'url', 'max:500'],
            'metric' => ['required', 'string', 'in:LCP,INP,CLS,FCP,TTFB'],
            'value' => ['required', 'numeric', 'min:0', 'max:1000000'],
            'rating' => ['nullable', 'string', 'in:good,needs-improvement,poor'],
            'device' => ['nullable', 'string', 'max:16'],
            'connection' => ['nullable', 'string', 'max:16'],
            'navigation_type' => ['nullable', 'string', 'max:32'],
        ];
    }
}
