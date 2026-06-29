<?php

namespace Modules\User\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class AssignUserUids extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:assign-uids';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign UUIDs to users that don\'t have one';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Checking users without UID...');

        $users = User::whereNull('uid')->get();

        if ($users->isEmpty()) {
            $this->info('✅ All users already have UIDs assigned!');

            return Command::SUCCESS;
        }

        $this->info("📝 Found {$users->count()} users without UID");

        $bar = $this->output->createProgressBar($users->count());
        $bar->start();

        $updated = 0;

        foreach ($users as $user) {
            // Generate UUID (same as HasUid trait)
            $user->uid = (string) Str::uuid();

            // Save without triggering events (to avoid activity log issues)
            $user->saveQuietly();

            $updated++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("✅ Successfully assigned UIDs to {$updated} users!");

        // Show sample of assigned UIDs
        $samples = User::whereNotNull('uid')->limit(3)->get(['id', 'firstname', 'lastname', 'uid']);
        if ($samples->isNotEmpty()) {
            $this->newLine();
            $this->info('📋 Sample of assigned UIDs:');
            foreach ($samples as $sample) {
                $this->line("  • {$sample->firstname} {$sample->lastname}: {$sample->uid}");
            }
        }

        return Command::SUCCESS;
    }
}
