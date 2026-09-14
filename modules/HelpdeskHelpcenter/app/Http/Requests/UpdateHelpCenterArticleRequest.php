<?php

namespace Modules\HelpdeskHelpcenter\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateHelpCenterArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.helpcenter.articles.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'id' => ['required', 'integer', 'exists:helpdesk.helpdesk_helpcenter_articles,id'],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'section_id' => ['required', 'integer', 'exists:helpdesk.helpdesk_helpcenter_categories,id,is_section,1'],
            'position' => ['nullable', 'integer', 'min:0'],
            'draft' => ['boolean'],
            'hide_from_structure' => ['boolean'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
            'featured_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => 'El identificador del artículo es obligatorio.',
            'id.exists' => 'El artículo seleccionado no existe.',
            'title.required' => 'El título es obligatorio.',
            'title.max' => 'El título no puede superar los 255 caracteres.',
            'meta_description.max' => 'La meta descripción no puede superar los 500 caracteres.',
            'section_id.required' => 'La sección es obligatoria.',
            'section_id.exists' => 'La sección seleccionada no existe.',
            'position.integer' => 'La posición debe ser un número entero.',
            'position.min' => 'La posición no puede ser negativa.',
            'tags.*.max' => 'Cada etiqueta no puede superar los 50 caracteres.',
            'featured_image.image' => 'El archivo debe ser una imagen.',
            'featured_image.mimes' => 'La imagen debe ser de tipo: jpeg, png, jpg, gif o webp.',
            'featured_image.max' => 'La imagen no puede superar los 2 MB.',
        ];
    }

    public function attributes(): array
    {
        return [
            'id' => 'artículo',
            'title' => 'título',
            'body' => 'contenido',
            'description' => 'descripción',
            'meta_description' => 'meta descripción',
            'section_id' => 'sección',
            'position' => 'posición',
            'draft' => 'borrador',
            'hide_from_structure' => 'ocultar de la estructura',
            'tags' => 'etiquetas',
            'featured_image' => 'imagen destacada',
        ];
    }
}
