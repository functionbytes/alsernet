<?php

namespace Modules\HelpdeskContacts\Http\Requests\Managers;

use Modules\HelpdeskPrestashop\Http\Requests\Managers\StoreAssistedCartItemRequest;

/**
 * The Contacts assisted-cart shares the PrestaShop cart's validation rules
 * (same AssistedCartService underneath) but is reached through the Contacts UI,
 * so only the permission gate differs — everything else is inherited.
 */
class StoreContactCartItemRequest extends StoreAssistedCartItemRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('contacts.update');
    }
}
