<?php

namespace Modules\Document\Console\Commands;

use Illuminate\Console\Command;

class ValidateDocumentsComplete extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'documents:validate-complete
                            {--fix : Automatically fix all issues found}
                            {--type= : Filter by document type}
                            {--status= : Filter by validation status}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run all document validations in sequence: stages, aspects, and media';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->newLine(3);
        $this->info('╔══════════════════════════════════════════════════════════════════╗');
        $this->info('║                                                                  ║');
        $this->info('║          COMPLETE DOCUMENT VALIDATION & REPAIR SUITE             ║');
        $this->info('║                                                                  ║');
        $this->info('╚══════════════════════════════════════════════════════════════════╝');
        $this->newLine();

        $fix = $this->option('fix');
        $type = $this->option('type');
        $status = $this->option('status');

        if ($fix) {
            $this->info('🔧 MODE: AUTO-FIX ENABLED - All detected issues will be automatically corrected');
        } else {
            $this->info('📊 MODE: DRY-RUN (ANALYSIS ONLY) - No changes will be made');
        }

        $this->newLine();
        $this->info('Applying filters:');
        if ($type) {
            $this->info("  • Document type: <fg=cyan>$type</fg=cyan>");
        }
        if ($status) {
            $this->info("  • Validation status: <fg=cyan>$status</fg=cyan>");
        }
        if (! $type && ! $status) {
            $this->info('  • No filters (all documents)');
        }

        $this->newLine(2);

        // Array to store all results
        $allResults = [];

        // 1. Validate Stages
        $this->info('┌─ STEP 1: STAGE VALIDATION ─────────────────────────────────────┐');
        $this->newLine();

        $command = 'documents:validate-stages';
        $args = [];
        if ($fix) {
            $args['--fix'] = true;
        }
        if ($type) {
            $args['--type'] = $type;
        }
        if ($status) {
            $args['--status'] = $status;
        }

        try {
            $exitCode = $this->call($command, $args);
            $allResults['stages'] = $exitCode === 0 ? 'success' : 'failed';
        } catch (\Exception $e) {
            $this->error("Error in stage validation: {$e->getMessage()}");
            $allResults['stages'] = 'error';
        }

        $this->newLine(2);

        // 2. Validate All Aspects
        $this->info('┌─ STEP 2: COMPLETE ASPECT VALIDATION ─────────────────────────────┐');
        $this->newLine();

        $command = 'documents:validate-all';
        $args = [];
        if ($fix) {
            $args['--fix'] = true;
        }
        if ($type) {
            $args['--type'] = $type;
        }
        if ($status) {
            $args['--status'] = $status;
        }

        try {
            $exitCode = $this->call($command, $args);
            $allResults['aspects'] = $exitCode === 0 ? 'success' : 'failed';
        } catch (\Exception $e) {
            $this->error("Error in aspect validation: {$e->getMessage()}");
            $allResults['aspects'] = 'error';
        }

        $this->newLine(2);

        // 3. Validate Media
        $this->info('┌─ STEP 3: MEDIA & DATA CONSISTENCY VALIDATION ──────────────────────┐');
        $this->newLine();

        $command = 'documents:validate-media';
        $args = [];
        if ($fix) {
            $args['--fix'] = true;
        }
        if ($type) {
            $args['--type'] = $type;
        }
        if ($status) {
            $args['--status'] = $status;
        }

        try {
            $exitCode = $this->call($command, $args);
            $allResults['media'] = $exitCode === 0 ? 'success' : 'failed';
        } catch (\Exception $e) {
            $this->error("Error in media validation: {$e->getMessage()}");
            $allResults['media'] = 'error';
        }

        $this->newLine(3);

        // Final Summary
        $this->displayFinalSummary($allResults, $fix);

        return 0;
    }

    /**
     * Display final summary
     */
    protected function displayFinalSummary(array $results, bool $fix): void
    {
        $this->info('╔══════════════════════════════════════════════════════════════════╗');
        $this->info('║                    VALIDATION SUITE COMPLETE                      ║');
        $this->info('╚══════════════════════════════════════════════════════════════════╝');
        $this->newLine();

        $this->info('Validation Results:');
        $this->newLine();

        $allSuccess = true;
        foreach ($results as $check => $result) {
            $label = match ($check) {
                'stages' => 'Validation Stages',
                'aspects' => 'Document Aspects',
                'media' => 'Media & Consistency',
                default => ucfirst($check),
            };

            if ($result === 'success') {
                $this->line("  ✓ <fg=green>$label</fg=green> .......................... PASSED");
            } else {
                $this->line("  ✗ <fg=red>$label</fg=red> .......................... " . strtoupper($result));
                $allSuccess = false;
            }
        }

        $this->newLine(2);

        if ($allSuccess) {
            $this->info('╔══════════════════════════════════════════════════════════════════╗');
            $this->info('║                                                                  ║');
            if ($fix) {
                $this->info('║         ✓ ALL VALIDATIONS PASSED - REPAIRS COMPLETED!           ║');
            } else {
                $this->info('║              ✓ ALL VALIDATIONS PASSED - NO ISSUES!               ║');
            }
            $this->info('║                                                                  ║');
            $this->info('║    Your documents are in excellent condition! 🎉                ║');
            $this->info('║                                                                  ║');
            $this->info('╚══════════════════════════════════════════════════════════════════╝');
        } else {
            $this->newLine();
            $this->warn('Some validations encountered issues. Please review the results above.');
        }

        $this->newLine(2);

        // Helpful tips
        $this->info('Useful commands for monitoring documents:');
        $this->newLine();
        $this->line('  # Validate specific document type');
        $this->line('  $ php artisan documents:validate-complete --type=dni');
        $this->newLine();
        $this->line('  # Validate documents by status');
        $this->line('  $ php artisan documents:validate-complete --status=pending');
        $this->newLine();
        $this->line('  # Run specific validation');
        $this->line('  $ php artisan documents:validate-stages --fix');
        $this->line('  $ php artisan documents:validate-all --fix');
        $this->line('  $ php artisan documents:validate-media --fix');
        $this->newLine();
        $this->line('  # View document details');
        $this->line('  $ php artisan documents:initialize-workflows --help');
        $this->newLine();
    }
}
