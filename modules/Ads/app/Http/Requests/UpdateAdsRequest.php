<?php

namespace Modules\Ads\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Ads\Enums\AdsStatus;
use Modules\Ads\Enums\AdsType;

class UpdateAdsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('ads.update');
    }

    public function rules(): array
    {
        $adsId = $this->route('ad')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'key' => ['required', 'string', 'max:120', Rule::unique('ads', 'key')->ignore($adsId)],
            'status' => ['required', 'string', Rule::in(array_map(fn ($s) => $s->value, AdsStatus::cases()))],
            'ads_type' => ['required', 'string', Rule::in(array_map(fn ($t) => $t->value, AdsType::cases()))],
            'url' => ['nullable', 'url', 'max:500'],
            'location' => ['nullable', 'string', 'max:120'],
            'image' => ['nullable', 'string', 'max:500'],
            'tablet_image' => ['nullable', 'string', 'max:500'],
            'mobile_image' => ['nullable', 'string', 'max:500'],
            'google_adsense_slot_id' => ['nullable', 'string', 'max:255'],
            'order' => ['nullable', 'integer', 'min:0'],
            'expired_at' => ['nullable', 'date'],
            'open_in_new_tab' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del anuncio es obligatorio.',
            'key.required' => 'La clave única es obligatoria.',
            'key.unique' => 'Esta clave ya está en uso.',
            'url.url' => 'La URL debe ser válida.',
        ];
    }
}
