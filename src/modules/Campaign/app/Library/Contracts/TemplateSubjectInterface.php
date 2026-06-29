<?php

namespace Modules\Campaign\Library\Contracts;

use Modules\Campaign\Models\Template\Template;

/**
 * Contrato para entidades que "poseen" un Template 1:1 (PageTemplate, y en el
 * futuro FormTemplate / SystemEmailTemplate). Permite a TemplateService operar
 * de forma genérica sobre el subject y su template asociado.
 *
 * Portado de acellemail (App\Library\Contracts\TemplateSubjectInterface).
 */
interface TemplateSubjectInterface
{
    /** Relación BelongsTo hacia el Template asociado. */
    public function template();

    /** Identificador público. */
    public function getKey();
}
