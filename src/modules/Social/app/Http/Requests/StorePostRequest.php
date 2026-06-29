<?php

namespace Modules\Social\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Social\Enums\PostType;
use Modules\Social\Models\Post;

class StorePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Post::class);
    }

    public function rules(): array
    {
        $typeValues = implode(',', array_column(PostType::cases(), 'value'));

        return [
            'account_id' => 'required|exists:chat_accounts,id',
            'created_by' => 'required|exists:users,id',
            'social_account_id' => 'required|exists:social_accounts,id',
            'campaign_id' => 'nullable|exists:social_campaigns,id',
            'type' => "required|in:{$typeValues}",
            'content' => 'required|string',
            'media' => 'nullable|array',
            'media.*' => 'file|mimes:jpg,jpeg,png,gif,mp4,mov,avi|max:20480',
            'link_url' => 'nullable|url|max:2048',
            'scheduled_at' => 'nullable|date|after:now',
            'labels' => 'nullable|array',
            'labels.*' => 'exists:social_labels,id',
        ];
    }

    public function messages(): array
    {
        return [
            'social_account_id.required' => 'Debes seleccionar una cuenta social',
            'social_account_id.exists' => 'La cuenta social seleccionada no existe',
            'campaign_id.exists' => 'La campaña seleccionada no existe',
            'type.required' => 'Debes seleccionar un tipo de publicación',
            'type.in' => 'El tipo de publicación no es válido',
            'content.required' => 'El contenido de la publicación es obligatorio',
            'media.*.file' => 'Cada archivo debe ser un archivo válido',
            'media.*.mimes' => 'Solo se permiten imágenes (jpg, png, gif) o videos (mp4, mov, avi)',
            'media.*.max' => 'El tamaño máximo por archivo es 20MB',
            'link_url.url' => 'La URL del enlace no es válida',
            'link_url.max' => 'La URL del enlace no puede exceder 2048 caracteres',
            'scheduled_at.date' => 'La fecha de programación no es válida',
            'scheduled_at.after' => 'La fecha de programación debe ser posterior al momento actual',
            'labels.*.exists' => 'Una o más etiquetas seleccionadas no existen',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'account_id' => auth()->user()->account_id,
            'created_by' => auth()->id(),
        ]);
    }
}
