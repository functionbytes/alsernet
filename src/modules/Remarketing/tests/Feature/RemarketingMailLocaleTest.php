<?php

namespace Modules\Remarketing\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Remarketing\Mail\BackInStockMail;
use Modules\Remarketing\Mail\CartRecoveryMail;
use Modules\Remarketing\Mail\PriceDropMail;
use Modules\Remarketing\Mail\ReplenishmentMail;
use Modules\Remarketing\Models\Customer;
use Tests\TestCase;

/**
 * Regression: the four Remarketing mailables overrode `locale(): string` — an
 * incompatible signature vs `Mailable::locale($locale): $this` (a fluent
 * setter) — which fatals at class load ("Declaration must be compatible…").
 * They now set the recipient locale via the parent setter in the constructor.
 */
class RemarketingMailLocaleTest extends TestCase
{
    use DatabaseTransactions;

    /** @var string[] */
    protected $connectionsToTransact = ['mariadb', 'helpdesk'];

    public function test_mailables_set_the_customer_locale(): void
    {
        $customer = new Customer(['locale' => 'en']);

        $this->assertSame('en', (new CartRecoveryMail($customer, []))->locale);
        $this->assertSame('en', (new BackInStockMail($customer, 1, []))->locale);
        $this->assertSame('en', (new PriceDropMail($customer, 1, 9.99, 4.99, []))->locale);
        $this->assertSame('en', (new ReplenishmentMail($customer, 1))->locale);
    }

    public function test_mailable_falls_back_to_app_locale_without_customer_locale(): void
    {
        $customer = new Customer;

        $this->assertSame(config('app.locale'), (new CartRecoveryMail($customer, []))->locale);
    }
}
