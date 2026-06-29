<?php

namespace Modules\Ecommerce\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Ecommerce\Models\GiftCard;
use Tests\TestCase;

class GiftCardTest extends TestCase
{
    use RefreshDatabase;

    public function test_generate_code_creates_unique_code(): void
    {
        $code = GiftCard::generateCode();

        $this->assertStringStartsWith('GIFT-', $code);
        $this->assertEquals(15, strlen($code));
    }

    public function test_is_usable_when_active_with_balance(): void
    {
        $card = GiftCard::query()->create([
            'code' => GiftCard::generateCode(),
            'amount' => 100,
            'balance' => 100,
            'status' => 'active',
        ]);

        $this->assertTrue($card->isUsable());
    }

    public function test_is_not_usable_when_zero_balance(): void
    {
        $card = GiftCard::query()->create([
            'code' => GiftCard::generateCode(),
            'amount' => 100,
            'balance' => 0,
            'status' => 'active',
        ]);

        $this->assertFalse($card->isUsable());
    }

    public function test_is_not_usable_when_expired(): void
    {
        $card = GiftCard::query()->create([
            'code' => GiftCard::generateCode(),
            'amount' => 100,
            'balance' => 100,
            'status' => 'active',
            'expires_at' => now()->subDay(),
        ]);

        $this->assertFalse($card->isUsable());
    }

    public function test_is_not_usable_when_status_is_not_active(): void
    {
        $card = GiftCard::query()->create([
            'code' => GiftCard::generateCode(),
            'amount' => 100,
            'balance' => 100,
            'status' => 'used',
        ]);

        $this->assertFalse($card->isUsable());
    }

    public function test_redeem_decreases_balance(): void
    {
        $card = GiftCard::query()->create([
            'code' => GiftCard::generateCode(),
            'amount' => 100,
            'balance' => 100,
            'status' => 'active',
        ]);

        $applied = $card->redeem(30);

        $this->assertEquals(30, $applied);
        $this->assertEquals(70, $card->fresh()->balance);
    }

    public function test_redeem_caps_at_available_balance(): void
    {
        $card = GiftCard::query()->create([
            'code' => GiftCard::generateCode(),
            'amount' => 50,
            'balance' => 20,
            'status' => 'active',
        ]);

        $applied = $card->redeem(100);

        $this->assertEquals(20, $applied);
        $this->assertEquals(0, $card->fresh()->balance);
    }

    public function test_redeem_marks_used_when_balance_reaches_zero(): void
    {
        $card = GiftCard::query()->create([
            'code' => GiftCard::generateCode(),
            'amount' => 50,
            'balance' => 50,
            'status' => 'active',
        ]);

        $card->redeem(50);

        $this->assertEquals('used', $card->fresh()->status);
    }
}
