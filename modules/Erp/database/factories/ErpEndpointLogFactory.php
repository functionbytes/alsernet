<?php

namespace Modules\Erp\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Erp\Models\ErpEndpoint;
use Modules\Erp\Models\ErpEndpointLog;

class ErpEndpointLogFactory extends Factory
{
    protected $model = ErpEndpointLog::class;

    public function definition(): array
    {
        $success = $this->faker->boolean(80);

        return [
            'endpoint_id' => ErpEndpoint::factory(),
            'method' => $this->faker->randomElement(['GET', 'POST']),
            'url' => $this->faker->url(),
            'status_code' => $success ? 200 : $this->faker->randomElement([400, 404, 500, 503]),
            'execution_time' => $this->faker->numberBetween(50, 4000),
            'success' => $success,
            'error_message' => $success ? null : $this->faker->sentence(),
            'ip_address' => $this->faker->ipv4(),
        ];
    }
}
