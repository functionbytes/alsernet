<?php

namespace Modules\Forms\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Forms\Models\Form;
use Modules\Forms\Models\FormSubmission;

/**
 * Evento público disparado tras un envío exitoso de formulario.
 * Escuchable desde otros módulos (Pages, Analytics, integrations, etc.).
 *
 * Para broadcasting en tiempo real usar NewFormSubmission.
 */
class FormSubmitted
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly Form $form,
        public readonly FormSubmission $submission,
    ) {}
}
