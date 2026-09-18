<?php

namespace Modules\Core\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

class ValidMimeMagicBytes implements ValidationRule
{
    /**
     * @param  array<string>  $allowedMimeTypes
     */
    public function __construct(
        private readonly array $allowedMimeTypes
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile) {
            $fail('El campo :attribute debe ser un archivo válido.');

            return;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $detectedMime = finfo_file($finfo, $value->getRealPath());
        finfo_close($finfo);

        if ($detectedMime === false || ! in_array($detectedMime, $this->allowedMimeTypes, strict: true)) {
            $fail('El tipo de archivo detectado no está permitido.');
        }
    }
}
