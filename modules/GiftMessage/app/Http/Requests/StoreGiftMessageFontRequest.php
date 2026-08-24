<?php

namespace Modules\GiftMessage\Http\Requests;

use FontLib\Font;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Modules\GiftMessage\Models\GiftMessageFont;
use Modules\GiftMessage\Services\GiftMessageFontService;

class StoreGiftMessageFontRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('giftmessage.update');
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'family' => Str::slug((string) $this->input('name'), '_'),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'family' => [
                'required',
                'string',
                'max:100',
                Rule::notIn(array_keys(GiftMessageFontService::BUILTIN_LABELS)),
            ],
            'weight' => ['required', 'string', Rule::in(['normal', 'bold'])],
            'style' => ['required', 'string', Rule::in(['normal', 'italic'])],
            'font_file' => [
                'required',
                'file',
                'max:5120',
                'extensions:ttf,otf',
                function (string $attribute, mixed $value, callable $fail): void {
                    if (! $this->isParsableFont($value->getPathname())) {
                        $fail('El archivo no es una fuente TTF/OTF valida.');
                    }
                },
            ],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $exists = GiftMessageFont::query()
                ->where('family', $this->input('family'))
                ->where('weight', $this->input('weight'))
                ->where('style', $this->input('style'))
                ->exists();

            if ($exists) {
                $validator->errors()->add('font_file', 'Ya existe esa variante para esta fuente. Eliminala antes de volver a subirla.');
            }
        });
    }

    /**
     * DomPDF fallaria al generar el PDF si la fuente no se puede parsear, asi que
     * se descarta en la subida en vez de romper la generacion mas tarde.
     */
    private function isParsableFont(string $path): bool
    {
        try {
            $font = Font::load($path);

            if ($font === null) {
                return false;
            }

            $font->parse();
            $font->close();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la fuente es obligatorio.',
            'name.max' => 'El nombre no puede superar los 100 caracteres.',
            'family.not_in' => 'Ese nombre esta reservado para una fuente del sistema. Usa otro.',
            'weight.in' => 'El grosor debe ser normal o negrita.',
            'style.in' => 'El estilo debe ser normal o cursiva.',
            'font_file.required' => 'Debes seleccionar un archivo de fuente.',
            'font_file.extensions' => 'La fuente debe ser un archivo TTF u OTF.',
            'font_file.max' => 'La fuente no puede superar los 5 MB.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'family' => 'familia',
            'weight' => 'grosor',
            'style' => 'estilo',
            'font_file' => 'archivo de fuente',
        ];
    }
}
