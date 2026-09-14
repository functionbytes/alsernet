<?php

namespace Modules\Erp\Console\Commands\Supplier;

use Illuminate\Console\Command;
use Modules\Erp\Services\CircuitBreaker;

class CircuitBreakerStatus extends Command
{
    /**
     * The signature of the console command.
     *
     * @var string
     */
    protected $signature = 'erp:circuit-breaker
                            {service=oracle : Service to check (oracle, redis, etc.)}
                            {--reset : Reset the circuit breaker for the service}';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Check or reset the circuit breaker status for ERP services';

    /**
     * Circuit Breaker instance
     */
    protected CircuitBreaker $circuitBreaker;

    /**
     * Create a new command instance
     */
    public function __construct(CircuitBreaker $circuitBreaker)
    {
        parent::__construct();
        $this->circuitBreaker = $circuitBreaker;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $service = $this->argument('service');

        // Reset if requested
        if ($this->option('reset')) {
            return $this->resetCircuitBreaker($service);
        }

        // Show status
        return $this->showStatus($service);
    }

    /**
     * Show circuit breaker status
     */
    protected function showStatus(string $service): int
    {
        $state = $this->circuitBreaker->getState($service);

        $this->info('🔌 Circuit Breaker Status');
        $this->newLine();

        // State indicator with color
        $stateIcon = match ($state['state']) {
            'CLOSED' => '<fg=green>✅ CLOSED</> (Normal operation)',
            'HALF_OPEN' => '<fg=yellow>🟡 HALF_OPEN</> (Testing recovery)',
            'OPEN' => '<fg=red>🔴 OPEN</> (Service failing - requests blocked)',
            default => '❓ UNKNOWN',
        };

        $this->table(
            ['Property', 'Value'],
            [
                ['Service', $state['service']],
                ['State', $stateIcon],
                ['Failures', $state['failures'].' / '.$state['threshold']],
                ['Opened At', $state['opened_at'] ?? 'N/A'],
                ['Recovery Timeout', $state['recovery_timeout'].' seconds'],
                ['Next Attempt At', $state['next_attempt_at'] ?? 'N/A'],
            ]
        );

        $this->newLine();

        // Recommendations based on state
        if ($state['state'] === 'OPEN') {
            $this->warn('⚠️  The circuit breaker is OPEN - service requests are being blocked');
            $this->newLine();
            $this->info('💡 Actions:');
            $this->line('  1. Check if '.$service.' service is operational');
            $this->line('  2. Wait for automatic recovery ('.$state['recovery_timeout'].'s)');
            $this->line('  3. Or manually reset: <fg=cyan>php artisan erp:circuit-breaker '.$service.' --reset</>');
        } elseif ($state['state'] === 'HALF_OPEN') {
            $this->info('🟡 Circuit breaker is in HALF_OPEN state - testing recovery');
            $this->line('The next request will determine if the circuit closes (success) or reopens (failure)');
        } else {
            $this->info('✅ Circuit breaker is CLOSED - service is operational');
        }

        return self::SUCCESS;
    }

    /**
     * Reset circuit breaker
     */
    protected function resetCircuitBreaker(string $service): int
    {
        if (! $this->confirm("Are you sure you want to reset the circuit breaker for {$service}?", true)) {
            $this->info('Reset cancelled');

            return self::SUCCESS;
        }

        $this->info('🔧 Resetting circuit breaker for '.$service.'...');

        $this->circuitBreaker->reset($service);

        $this->newLine();
        $this->info('✅ Circuit breaker reset successfully');
        $this->newLine();

        // Show new status
        return $this->showStatus($service);
    }
}
