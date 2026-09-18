<?php

namespace Modules\HelpdeskContacts\Http\Requests\Managers;

use Modules\HelpdeskPrestashop\Http\Requests\Managers\ApplyCartDiscountRequest;

/**
 * Shares the PrestaShop discount validation rules; only the permission gate
 * differs (reached through the Contacts UI).
 */
class ApplyContactCartDiscountRequest extends ApplyCartDiscountRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('contacts.update');
    }
}
