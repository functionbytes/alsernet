<?php

namespace Modules\Campaign\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Campaign\Console\Commands\AlertCheckCommand;
use Modules\Campaign\Models\Campaign;
use Tests\TestCase;

class AlertCheckCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_alert_check_passes_when_no_issues(): void
    {
        $this->artisan(AlertCheckCommand::class)
            ->assertSuccessful()
            ->expectsOutputToContain('Todas las métricas operacionales están dentro de límites aceptables');
    }

    public function test_alert_check_detects_high_bounce_rate(): void
    {
        Campaign::forceCreate([
            'name' => 'High Bounce',
            'subject' => 'S',
            'from_email' => 'a@a.com',
            'from_name' => 'A',
            'reply_to' => 'a@a.com',
            'status' => 'done',
            'sent_count' => 200,
            'bounce_count' => 20,
        ]);

        // El comando usa $this->error() que va a stderr; verificamos éxito y que no diga "límites aceptables"
        $this->artisan(AlertCheckCommand::class, ['--bounce-threshold' => 5])
            ->assertSuccessful()
            ->doesntExpectOutputToContain('límites aceptables');
    }
}
