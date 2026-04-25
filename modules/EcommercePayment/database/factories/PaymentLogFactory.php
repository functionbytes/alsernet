<?php

namespace Modules\EcommercePayment\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\EcommercePayment\Models\Payment;
use Modules\EcommercePayment\Models\PaymentLog;

class PaymentLogFactory extends Factory
{
    protected $model = PaymentLog::class;

    public function definition(): array
    {
        return [
            'payment_id' => Payment::factory(),
            'payment_method' => 'wompi',
            'request' => ['action' => 'test'],
            'response' => ['status' => 'ok'],
            'ip_address' => $this->faker->ipv4(),
        ];
    }
}
