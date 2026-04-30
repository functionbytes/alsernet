<?php

namespace Modules\Chat\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Modules\Chat\Models\Accounts\Account;
use Modules\Chat\Services\Analytics\AgentPerformanceService;

class GenerateAgentPerformanceSnapshots extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'snapshots:generate-agent-performance
                            {--date= : The date to generate snapshots for (Y-m-d format, defaults to yesterday)}
                            {--account= : Generate snapshots for a specific account ID}
                            {--force : Force regenerate snapshots even if they already exist}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate daily agent performance snapshots for all accounts';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $date = $this->option('date')
            ? Carbon::parse($this->option('date'))
            : Carbon::yesterday();

        $this->info("Generating agent performance snapshots for {$date->toDateString()}...");

        $accounts = $this->option('account')
            ? Account::where('id', $this->option('account'))->get()
            : Account::all();

        if ($accounts->isEmpty()) {
            $this->error('No accounts found!');

            return Command::FAILURE;
        }

        $totalSnapshots = 0;
        $progressBar = $this->output->createProgressBar($accounts->count());

        foreach ($accounts as $account) {
            $service = new AgentPerformanceService($account->id);

            try {
                $count = $service->generateSnapshotsForAllAgents($date);
                $totalSnapshots += $count;

                $this->newLine();
                $this->info("  Account #{$account->id} ({$account->name}): {$count} snapshots generated");
            } catch (\Exception $e) {
                $this->newLine();
                $this->error("  Account #{$account->id} failed: {$e->getMessage()}");
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info("✓ Generated {$totalSnapshots} total snapshots for {$accounts->count()} accounts");

        return Command::SUCCESS;
    }
}
