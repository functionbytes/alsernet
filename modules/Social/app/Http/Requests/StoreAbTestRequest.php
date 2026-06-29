<?php

namespace Modules\Social\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Social\Models\AbTest;

class StoreAbTestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', AbTest::class);
    }

    public function rules(): array
    {
        return [
            'account_id' => ['required', 'exists:chat_accounts,id'],
            'name' => ['required', 'string', 'max:255'],
            'variant_a_post_id' => ['required', 'exists:social_posts,id', 'different:variant_b_post_id'],
            'variant_b_post_id' => ['required', 'exists:social_posts,id', 'different:variant_a_post_id'],
            'variant_a_score' => ['required', 'numeric'],
            'variant_b_score' => ['required', 'numeric'],
            'metric' => ['required', Rule::in(['engagement', 'likes', 'comments', 'shares', 'clicks'])],
        ];
    }

    public function messages(): array
    {
        return [
            'variant_a_post_id.required' => 'Debes seleccionar la variante A',
            'variant_b_post_id.required' => 'Debes seleccionar la variante B',
            'variant_a_post_id.different' => 'Las variantes deben ser publicaciones diferentes',
            'variant_b_post_id.different' => 'Las variantes deben ser publicaciones diferentes',
            'metric.required' => 'Debes seleccionar una métrica de comparación',
            'metric.in' => 'Métrica inválida',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'account_id' => auth()->user()->account_id,
            'variant_a_score' => 0,
            'variant_b_score' => 0,
        ]);
    }
}
