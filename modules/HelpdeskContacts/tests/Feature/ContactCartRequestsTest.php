<?php

namespace Modules\HelpdeskContacts\Tests\Feature;

use Modules\HelpdeskContacts\Http\Requests\Managers\ApplyContactCartDiscountRequest;
use Modules\HelpdeskContacts\Http\Requests\Managers\GenerateContactOrderRequest;
use Modules\HelpdeskContacts\Http\Requests\Managers\StoreContactCartItemRequest;
use Modules\HelpdeskPrestashop\Http\Requests\Managers\ApplyCartDiscountRequest;
use Modules\HelpdeskPrestashop\Http\Requests\Managers\GenerateOrderRequest;
use Modules\HelpdeskPrestashop\Http\Requests\Managers\StoreAssistedCartItemRequest;
use Tests\TestCase;

/**
 * The Contacts assisted-cart requests reuse the PrestaShop cart validation
 * (single source of rules) and only override the permission gate.
 */
class ContactCartRequestsTest extends TestCase
{
    public function test_store_item_request_inherits_prestashop_rules(): void
    {
        $this->assertInstanceOf(StoreAssistedCartItemRequest::class, new StoreContactCartItemRequest);

        // Rules come straight from the PrestaShop request — not duplicated.
        $this->assertSame(
            (new StoreAssistedCartItemRequest)->rules(),
            (new StoreContactCartItemRequest)->rules(),
        );
    }

    public function test_discount_request_inherits_prestashop_rules(): void
    {
        $this->assertInstanceOf(ApplyCartDiscountRequest::class, new ApplyContactCartDiscountRequest);

        $this->assertSame(
            (new ApplyCartDiscountRequest)->rules(),
            (new ApplyContactCartDiscountRequest)->rules(),
        );
    }

    public function test_generate_order_request_inherits_strict_prestashop_rules(): void
    {
        $this->assertInstanceOf(GenerateOrderRequest::class, new GenerateContactOrderRequest);

        // Reglas heredadas del flujo nativo (fuente única), no las inline laxas
        // de antes que hacían nullable name/email/address/city/country.
        $this->assertSame(
            (new GenerateOrderRequest)->rules(),
            (new GenerateContactOrderRequest)->rules(),
        );

        // Regresión: los campos críticos deben ser obligatorios para no generar
        // un pedido / link de pago real con datos de envío incompletos.
        $rules = (new GenerateContactOrderRequest)->rules();
        foreach (['name', 'email', 'address', 'city', 'country'] as $field) {
            $this->assertContains('required', $rules[$field], "El campo {$field} debe ser obligatorio.");
        }
    }
}
