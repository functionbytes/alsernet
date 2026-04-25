<?php

namespace Modules\EcommercePayment\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\EcommercePayment\Enums\PaymentStatus;
use Modules\EcommercePayment\Models\Payment;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'charge_id' => 'WP'.time().'_'.$this->faker->regexify('[A-Z0-9]{8}'),
            'amount' => $this->faker->randomFloat(2, 10, 10000),
            'currency' => 'COP',
            'payment_channel' => 'wompi',
            'status' => $this->faker->randomElement(PaymentStatus::cases()),
            'payment_type' => 'wompi_checkout',
        ];
    }

    public function pending(): self
    {
        return $this->state(fn () => ['status' => PaymentStatus::PENDING]);
    }

    public function completed(): self
    {
        return $this->state(fn () => ['status' => PaymentStatus::COMPLETED]);
    }

    public function failed(): self
    {
        return $this->state(fn () => ['status' => PaymentStatus::FAILED]);
    }
}
