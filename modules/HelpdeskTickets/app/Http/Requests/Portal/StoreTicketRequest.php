<?php

namespace Modules\HelpdeskTickets\Http\Requests\Portal;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Core\Rules\ValidMimeMagicBytes;
use Modules\Helpdesk\Models\Setting;
use Modules\Helpdesk\Services\HelpdeskSettings;

class StoreTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $settings = app(HelpdeskSettings::class);

        return [
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'category_id' => ['nullable', 'integer'],
            // Sin 'urgent': el cliente no puede autoasignarse la prioridad más
            // alta (la escala el agente o el escalado automático). Antes
            // cualquier cadena pasaba y acababa en la columna tal cual.
            'priority' => ['nullable', Rule::in(['low', 'normal', 'high'])],
            // Hasta 5 correos en copia, separados por coma (se validan uno a
            // uno en ccEmails()).
            'cc' => ['nullable', 'string', 'max:500'],
            'attachments' => [
                'nullable',
                'array',
                'max:10',
                Rule::prohibitedIf(fn () => ! filter_var(
                    Setting::get('tickets.user_file_upload_enable', true),
                    FILTER_VALIDATE_BOOLEAN,
                )),
            ],
            // ValidMimeMagicBytes además de mimes: comprueba la firma binaria
            // real del fichero, no solo lo que declara la extensión. El alta
            // interna (StoreTicketRequest) ya lo hacía; el portal — que es la
            // entrada abierta a cualquiera con un enlace mágico — se quedaba
            // en la comprobación más débil de las dos.
            'attachments.*' => [
                'nullable',
                'file',
                'max:'.$settings->attachmentMaxKilobytes(),
                'mimes:'.implode(',', $settings->attachmentExtensions()),
                new ValidMimeMagicBytes($settings->attachmentMimeTypes()),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'subject.required' => 'El asunto es obligatorio.',
            'subject.max' => 'El asunto no puede superar los 255 caracteres.',
            'description.required' => 'La descripcion es obligatoria.',
            'description.max' => 'La descripcion no puede superar los 5000 caracteres.',
            'attachments.*.file' => 'El archivo adjunto debe ser un archivo valido.',
            'attachments.*.max' => 'El archivo adjunto no puede superar el límite configurado en Helpdesk.',
            'attachments.*.mimes' => 'El formato del archivo adjunto no es valido.',
            'attachments.max' => 'Puedes adjuntar como máximo 10 archivos.',
        ];
    }

    public function attributes(): array
    {
        return [
            'subject' => 'asunto',
            'description' => 'descripcion',
            'category_id' => 'categoria',
            'priority' => 'prioridad',
            'attachments.*' => 'archivo adjunto',
        ];
    }

    /**
     * Correos en copia normalizados y validados (máx. 5, sin duplicar el del
     * propio cliente).
     *
     * @return array<int, string>
     */
    public function ccEmails(?string $customerEmail): array
    {
        return collect(preg_split('/[,;\s]+/', (string) $this->input('cc')))
            ->map(fn ($e) => mb_strtolower(trim((string) $e)))
            ->filter(fn ($e) => $e !== '' && filter_var($e, FILTER_VALIDATE_EMAIL))
            ->reject(fn ($e) => $customerEmail && $e === mb_strtolower($customerEmail))
            ->unique()
            ->take(5)
            ->values()
            ->all();
    }
}
