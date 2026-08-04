<?php

namespace Modules\Helpdesk\Http\Requests\Settings;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Modules\Helpdesk\Models\Skill;

class UpdateSkillRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('helpdesk.skills.manage');
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('helpdesk.helpdesk_skills', 'name')->ignore($this->route('skill')),
            ],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede superar los 100 caracteres.',
            'name.unique' => 'Ya existe un skill con este nombre.',
            'description.max' => 'La descripcion no puede superar los 500 caracteres.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (! $this->filled('name')) {
                return;
            }

            $slug = Str::slug($this->name);
            $skillId = $this->route('skill') instanceof Skill
                ? $this->route('skill')->id
                : $this->route('skill');

            $exists = Skill::query()
                ->where('slug', $slug)
                ->when($skillId, fn ($query) => $query->whereKeyNot($skillId))
                ->exists();

            if ($exists) {
                $validator->errors()->add('name', 'Ya existe un skill con un nombre que genera el mismo slug.');
            }
        });
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'description' => 'descripcion',
        ];
    }
}
