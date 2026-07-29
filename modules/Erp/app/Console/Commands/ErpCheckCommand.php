<?php

namespace Modules\Erp\Console\Commands;

use Illuminate\Console\Command;
use Modules\Core\Models\Setting;
use Modules\Supplier\Services\Integrations\ErpService;

class ErpCheckCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'erp:check
                            {--update-status : Update the connection status in database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify connection to the ERP system';

    protected ErpService $erpService;

    /**
     * Create a new command instance.
     */
    public function __construct(ErpService $erpService)
    {
        parent::__construct();
        $this->erpService = $erpService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('🔍 Checking ERP connection...');
        $this->newLine();

        // Get backups
        $settings = Setting::getErpSettings();

        if (! $settings) {
            $this->error('❌ No ERP backups found. Please configure the ERP first.');

            return self::FAILURE;
        }

        if ($settings['erp_is_active'] !== 'yes') {
            $this->warn('⚠️  ERP service is currently inactive.');
        }

        // Display current configuration
        $this->table(
            ['Setting', 'Value'],
            [
                ['API URL', $settings['erp_api_url']],
                ['Sync URL', $settings['erp_sync_url']],
                ['XML-RPC URL', $settings['erp_xmlrpc_url']],
                ['SMS URL', $settings['erp_sms_url']],
                ['Status', $settings['erp_is_active'] === 'yes' ? '✅ Active' : '❌ Inactive'],
                ['Timeout', $settings['erp_timeout'].'s'],
                ['Retry Attempts', $settings['erp_retry_attempts']],
            ]
        );

        $this->newLine();

        // Test connection
        try {
            $this->info('Testing connection to ERP API...');

            $result = $this->erpService->checkConnection();

            if ($result['success']) {
                $this->info('✅ Connection successful!');

                if (isset($result['response_time_ms'])) {
                    $this->info("   Response time: {$result['response_time_ms']}ms");
                }

                // Update status if requested
                if ($this->option('update-status')) {
                    Setting::updateErpConnectionStatus('online');
                    $this->info('   Status updated in database');
                }

                $this->newLine();
                $this->displayStatistics($settings);

                return self::SUCCESS;
            } else {
                $this->error('❌ Connection failed!');
                $this->error("   Error: {$result['message']}");

                if ($this->option('update-status')) {
                    Setting::updateErpConnectionStatus('offline');
                    $this->warn('   Status updated in database');
                }

                return self::FAILURE;
            }

        } catch (\Exception $e) {
            $this->error('❌ Exception occurred:');
            $this->error("   {$e->getMessage()}");

            if ($this->option('update-status')) {
                Setting::updateErpConnectionStatus('error');
            }

            return self::FAILURE;
        }
    }

    /**
     * Display usage statistics
     */
    protected function displayStatistics(array $settings): void
    {
        $this->info('📊 Statistics:');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Requests', number_format((int) $settings['erp_total_requests'])],
                ['Failed Requests', number_format((int) $settings['erp_failed_requests'])],
                ['Success Rate', number_format((float) $settings['erp_success_rate'] ?? 100.0, 2).'%'],
                ['Last Check', $settings['erp_last_connection_check'] ? \Carbon\Carbon::parse($settings['erp_last_connection_check'])->diffForHumans() : 'Never'],
                ['Last Status', ucfirst($settings['erp_last_connection_status'] ?? 'unknown')],
            ]
        );
    }
}
