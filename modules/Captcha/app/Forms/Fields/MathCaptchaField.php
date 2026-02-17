<?php

namespace Modules\Captcha\Forms\Fields;

use Modules\Core\Forms\FormField;

class MathCaptchaField extends FormField
{
    protected function getTemplate(): string
    {
        return 'captcha::forms.fields.math-captcha';
    }
}
