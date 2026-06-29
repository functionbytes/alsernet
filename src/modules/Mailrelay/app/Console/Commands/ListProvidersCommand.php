<?php

namespace Modules\Mailrelay\Console\Commands;

use Illuminate\Console\Command;
use Modules\Mailrelay\Entities\MailProvider;

class ListProvidersCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'mailrelay:list-providers
                            {--inactive : Include inactive providers}
                            {--json : Output as JSON}';

    /**
     * The console command description.
     */
    protected $description = 'List all configured mail providers';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $query = MailProvider::query();

        if (! $this->option('inactive')) {
            $query->where('is_active', true);
        }

        $providers = $query->withCount('campaigns')
            ->orderBy('is_default', 'desc')
            ->orderBy('priority', 'desc')
            ->orderBy('name')
            ->get();

        if ($providers->isEmpty()) {
            $this->warn('No providers configured');
            $this->line('Run: php artisan db:seed --class=Modules\\\\Mailrelay\\\\Database\\\\Seeders\\\\MailProviderSeeder');

            return self::SUCCESS;
        }

        if ($this->option('json')) {
            $this->line($providers->toJson(JSON_PRETTY_PRINT));

            return self::SUCCESS;
        }

        $this->displayProvidersTable($providers);

        return self::SUCCESS;
    }

    /**
     * Display providers in a table
     */
    protected function displayProvidersTable($providers): void
    {
        $this->info("Mail Providers ({$providers->count()})");
        $this->newLine();

        $rows = $providers->map(function ($provider) {
            $status = $this->getStatusBadge($provider);
            $default = $provider->is_default ? '⭐ YES' : 'No';
            $connection = $provider->connection_ok ? '✓ OK' : ($provider->last_tested_at ? '✗ Failed' : '? Not tested');
            $campaigns = $provider->campaigns_count;

            return [
                $provider->id,
                $provider->name,
                $provider->driver,
                $status,
                $default,
                $provider->priority,
                $connection,
                $campaigns,
                $provider->last_tested_at?->format('Y-m-d H:i') ?? 'Never',
            ];
        });

        $this->table(
            ['ID', 'Name', 'Driver', 'Status', 'Default', 'Priority', 'Connection', 'Campaigns', 'Last Tested'],
            $rows->toArray()
        );

        // Show default provider
        $default = $providers->where('is_default', true)->first();
        if ($default) {
            $this->newLine();
            $this->components->info("Default Provider: {$default->name} ({$default->driver})");
        }

        // Show statistics
        $this->newLine();
        $this->line('Statistics:');
        $this->line("  Active: {$providers->where('is_active', true)->count()}");
        $this->line("  Inactive: {$providers->where('is_active', false)->count()}");
        $this->line("  Connected: {$providers->where('connection_ok', true)->count()}");
        $this->line("  Total campaigns: {$providers->sum(fn ($p) => $p->campaigns()->count())}");
    }

    /**
     * Get status badge for provider
     */
    protected function getStatusBadge(MailProvider $provider): string
    {
        if (! $provider->is_active) {
            return '⚫ Inactive';
        }

        if ($provider->connection_ok) {
            return '🟢 Active';
        }

        if ($provider->last_tested_at) {
            return '🔴 Disconnected';
        }

        return '🟡 Not tested';
    }
}
