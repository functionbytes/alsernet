<?php

namespace Modules\Social\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Modules\Social\Models\SocialAccount;

class VerifySystemCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'social:verify';

    /**
     * The console command description.
     */
    protected $description = 'Verify Social Media system configuration and requirements';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔍 Verifying Social Media System Configuration...');
        $this->newLine();

        $checks = [
            'Database Connection' => $this->checkDatabase(),
            'Redis Connection' => $this->checkRedis(),
            'Queue Configuration' => $this->checkQueue(),
            'Environment Variables' => $this->checkEnvVariables(),
            'OAuth Credentials' => $this->checkOAuthCredentials(),
            'Social Accounts' => $this->checkSocialAccounts(),
            'Console Commands' => $this->checkCommands(),
            'Scheduled Tasks' => $this->checkScheduler(),
            'Migrations' => $this->checkMigrations(),
            'File Permissions' => $this->checkPermissions(),
        ];

        $passed = 0;
        $failed = 0;
        $warnings = 0;

        foreach ($checks as $name => $result) {
            $this->displayCheckResult($name, $result);

            match ($result['status']) {
                'pass' => $passed++,
                'fail' => $failed++,
                'warning' => $warnings++,
                default => null,
            };
        }

        $this->newLine();
        $this->displaySummary($passed, $failed, $warnings);

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Display check result with appropriate styling
     */
    protected function displayCheckResult(string $name, array $result): void
    {
        $icon = match ($result['status']) {
            'pass' => '<fg=green>✓</>',
            'fail' => '<fg=red>✗</>',
            'warning' => '<fg=yellow>⚠</>',
            default => '○',
        };

        $this->line("{$icon} {$name}: {$result['message']}");

        if (isset($result['details'])) {
            foreach ($result['details'] as $detail) {
                $this->line("  → {$detail}");
            }
        }
    }

    /**
     * Display summary table
     */
    protected function displaySummary(int $passed, int $failed, int $warnings): void
    {
        $this->info('📊 Summary:');
        $this->table(
            ['Status', 'Count'],
            [
                ['✓ Passed', $passed],
                ['⚠ Warnings', $warnings],
                ['✗ Failed', $failed],
            ]
        );

        if ($failed === 0 && $warnings === 0) {
            $this->info('🎉 All checks passed! System is ready for production.');
        } elseif ($failed === 0) {
            $this->warn('⚠️  System is functional but has warnings. Review recommended.');
        } else {
            $this->error('❌ System has critical issues. Please fix before deploying.');
        }
    }

    /**
     * Check database connection
     */
    protected function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();

            return [
                'status' => 'pass',
                'message' => 'Connected successfully',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'fail',
                'message' => 'Connection failed: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Check Redis connection
     */
    protected function checkRedis(): array
    {
        try {
            Redis::ping();

            return [
                'status' => 'pass',
                'message' => 'Connected successfully',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'fail',
                'message' => 'Connection failed: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Check queue configuration
     */
    protected function checkQueue(): array
    {
        $driver = config('queue.default');

        if ($driver === 'sync') {
            return [
                'status' => 'warning',
                'message' => 'Using sync driver (not recommended for production)',
                'details' => ['Set QUEUE_CONNECTION=redis in .env'],
            ];
        }

        return [
            'status' => 'pass',
            'message' => "Using {$driver} driver",
        ];
    }

    /**
     * Check environment variables
     */
    protected function checkEnvVariables(): array
    {
        $required = ['APP_NAME', 'APP_ENV', 'APP_KEY', 'DB_DATABASE'];
        $missing = [];

        foreach ($required as $var) {
            if (empty(env($var))) {
                $missing[] = $var;
            }
        }

        if (! empty($missing)) {
            return [
                'status' => 'fail',
                'message' => 'Missing required variables',
                'details' => $missing,
            ];
        }

        return [
            'status' => 'pass',
            'message' => 'All required variables set',
        ];
    }

    /**
     * Check OAuth credentials
     */
    protected function checkOAuthCredentials(): array
    {
        $credentials = [
            'FACEBOOK_APP_ID' => env('FACEBOOK_APP_ID'),
            'FACEBOOK_APP_SECRET' => env('FACEBOOK_APP_SECRET'),
            'TWITTER_CONSUMER_KEY' => env('TWITTER_CONSUMER_KEY'),
            'TWITTER_CONSUMER_SECRET' => env('TWITTER_CONSUMER_SECRET'),
            'LINKEDIN_CLIENT_ID' => env('LINKEDIN_CLIENT_ID'),
            'LINKEDIN_CLIENT_SECRET' => env('LINKEDIN_CLIENT_SECRET'),
        ];

        $missing = array_keys(array_filter($credentials, fn ($value) => empty($value)));

        if (count($missing) === count($credentials)) {
            return [
                'status' => 'warning',
                'message' => 'No OAuth credentials configured',
                'details' => ['System will work but cannot connect to social networks'],
            ];
        }

        if (! empty($missing)) {
            return [
                'status' => 'warning',
                'message' => 'Some OAuth credentials missing',
                'details' => $missing,
            ];
        }

        return [
            'status' => 'pass',
            'message' => 'All OAuth credentials configured',
        ];
    }

    /**
     * Check social accounts
     */
    protected function checkSocialAccounts(): array
    {
        $count = SocialAccount::where('status', 1)->count();

        if ($count === 0) {
            return [
                'status' => 'warning',
                'message' => 'No active social accounts connected',
                'details' => ['Connect accounts via OAuth to start publishing'],
            ];
        }

        return [
            'status' => 'pass',
            'message' => "{$count} active account(s) connected",
        ];
    }

    /**
     * Check console commands
     */
    protected function checkCommands(): array
    {
        $commands = [
            'social:publish-scheduled',
            'social:sync-stats',
            'social:scan-listening',
        ];

        $missing = [];

        foreach ($commands as $command) {
            if (! $this->getApplication()->has($command)) {
                $missing[] = $command;
            }
        }

        if (! empty($missing)) {
            return [
                'status' => 'fail',
                'message' => 'Missing commands',
                'details' => $missing,
            ];
        }

        return [
            'status' => 'pass',
            'message' => 'All commands registered',
        ];
    }

    /**
     * Check scheduled tasks
     */
    protected function checkScheduler(): array
    {
        try {
            // Check if scheduler commands exist
            $commands = ['social:publish-scheduled', 'social:sync-stats', 'social:scan-listening'];
            $allExist = true;

            foreach ($commands as $command) {
                if (! $this->getApplication()->has($command)) {
                    $allExist = false;
                    break;
                }
            }

            if (! $allExist) {
                return [
                    'status' => 'warning',
                    'message' => 'Scheduler may not be configured',
                    'details' => ['Ensure cron job is set: * * * * * php artisan schedule:run'],
                ];
            }

            return [
                'status' => 'pass',
                'message' => 'Scheduled commands configured',
                'details' => ['Remember to setup cron: * * * * * php artisan schedule:run'],
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'warning',
                'message' => 'Could not verify scheduler',
            ];
        }
    }

    /**
     * Check migrations
     */
    protected function checkMigrations(): array
    {
        try {
            $pending = \Artisan::call('migrate:status', ['--pending' => true]);

            if ($pending) {
                return [
                    'status' => 'warning',
                    'message' => 'Pending migrations found',
                    'details' => ['Run: php artisan migrate'],
                ];
            }

            return [
                'status' => 'pass',
                'message' => 'All migrations applied',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'fail',
                'message' => 'Cannot check migrations: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Check file permissions
     */
    protected function checkPermissions(): array
    {
        $directories = [
            storage_path('app'),
            storage_path('logs'),
            storage_path('framework'),
        ];

        $unwritable = [];

        foreach ($directories as $dir) {
            if (! is_writable($dir)) {
                $unwritable[] = $dir;
            }
        }

        if (! empty($unwritable)) {
            return [
                'status' => 'fail',
                'message' => 'Storage directories not writable',
                'details' => $unwritable,
            ];
        }

        return [
            'status' => 'pass',
            'message' => 'Storage directories writable',
        ];
    }
}
