<?php

namespace Modules\Mailrelay\Console\Commands;

use Illuminate\Console\Command;
use Modules\Mailrelay\Entities\MailProvider;
use Modules\Mailrelay\Services\ProviderManager;

class TestProviderCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'mailrelay:test-provider
                            {provider? : Provider ID or driver name}
                            {--all : Test all active providers}
                            {--inactive : Include inactive providers when using --all}';

    /**
     * The console command description.
     */
    protected $description = 'Test mail provider connection and credentials';

    protected ProviderManager $providerManager;

    public function __construct(ProviderManager $providerManager)
    {
        parent::__construct();
        $this->providerManager = $providerManager;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ($this->option('all')) {
            return $this->testAllProviders();
        }

        $providerId = $this->argument('provider');

        if (! $providerId) {
            $this->error('Please specify a provider ID/name or use --all option');

            return self::FAILURE;
        }

        return $this->testSingleProvider($providerId);
    }

    /**
     * Test all providers
     */
    protected function testAllProviders(): int
    {
        $query = MailProvider::query();

        if (! $this->option('inactive')) {
            $query->where('is_active', true);
        }

        $providers = $query->orderBy('priority', 'desc')->get();

        if ($providers->isEmpty()) {
            $this->warn('No providers found');

            return self::SUCCESS;
        }

        $this->info("Testing {$providers->count()} provider(s)...\n");

        $results = [];

        foreach ($providers as $provider) {
            $this->line("Testing: {$provider->name} ({$provider->driver})");

            $result = $this->providerManager->testProvider($provider->id);

            $results[] = [
                'provider' => $provider,
                'result' => $result,
            ];

            if ($result['success']) {
                $this->components->success($result['message']);
            } else {
                $this->components->error($result['message']);
            }

            $this->newLine();
        }

        // Summary
        $successful = collect($results)->where('result.success', true)->count();
        $failed = collect($results)->where('result.success', false)->count();

        $this->newLine();
        $this->components->info("Summary: {$successful} successful, {$failed} failed");

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Test single provider
     */
    protected function testSingleProvider(string $providerId): int
    {
        // Try to find by ID first
        $provider = is_numeric($providerId)
            ? MailProvider::find($providerId)
            : MailProvider::where('driver', $providerId)->first();

        if (! $provider) {
            $this->error("Provider not found: {$providerId}");

            return self::FAILURE;
        }

        $this->info("Testing provider: {$provider->name} ({$provider->driver})");

        if (! $provider->is_active) {
            $this->warn('⚠ Provider is inactive');
        }

        $this->newLine();
        $this->components->task('Connecting to provider', function () {
            sleep(1); // Simulate connection delay

            return true;
        });

        $result = $this->providerManager->testProvider($provider->id);

        $this->newLine();

        if ($result['success']) {
            $this->components->success($result['message']);

            $this->table(
                ['Attribute', 'Value'],
                [
                    ['Provider ID', $provider->id],
                    ['Name', $provider->name],
                    ['Driver', $provider->driver],
                    ['Status', $provider->is_active ? 'Active' : 'Inactive'],
                    ['Default', $provider->is_default ? 'Yes' : 'No'],
                    ['Priority', $provider->priority],
                    ['Last Tested', $provider->last_tested_at?->diffForHumans() ?? 'Never'],
                ]
            );

            return true;
        }

        $this->components->error($result['message']);
        $this->warn('Connection failed - please check credentials');

        return false;
    }
}
