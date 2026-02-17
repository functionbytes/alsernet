<?php

namespace Tests\Unit\Console\Commands;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class MigrateMailingNamespacesTest extends TestCase
{
    protected string $testDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        // Create temporary test directory
        $this->testDirectory = storage_path('testing/namespace-migration');
        File::ensureDirectoryExists($this->testDirectory);
    }

    protected function tearDown(): void
    {
        // Clean up test directory
        if (File::exists($this->testDirectory)) {
            File::deleteDirectory($this->testDirectory);
        }

        parent::tearDown();
    }

    public function test_dry_run_does_not_modify_files(): void
    {
        $testFile = $this->createTestFile('TestController.php', <<<'PHP'
        <?php
        use Acelle\Model\Campaign;
        use Acelle\Library\Tool;

        class TestController
        {
            public function index()
            {
                $campaign = \Acelle\Model\Campaign::first();
                $tool = new Acelle\Library\Tool();
            }
        }
        PHP);

        $originalContent = File::get($testFile);

        $this->artisan('mailing:migrate-namespaces', [
            '--dry-run' => true,
            '--files' => str_replace(base_path().'/', '', $testFile),
        ])->assertSuccessful();

        $this->assertEquals($originalContent, File::get($testFile));
        $this->assertFileDoesNotExist($testFile.'.bak-namespace');
    }

    public function test_migrates_use_statements_correctly(): void
    {
        $testFile = $this->createTestFile('TestController.php', <<<'PHP'
        <?php
        use Acelle\Model\Campaign;
        use Acelle\Model\MailList;
        use Acelle\Library\Tool;

        class TestController {}
        PHP);

        $this->artisan('mailing:migrate-namespaces', [
            '--files' => str_replace(base_path().'/', '', $testFile),
            '--no-backup' => true,
        ])->assertSuccessful();

        $content = File::get($testFile);

        $this->assertStringContainsString('use Modules\Mailing\Models\Campaign\Campaign;', $content);
        $this->assertStringContainsString('use Modules\Mailing\Models\MailList\MailList;', $content);
        $this->assertStringContainsString('use Modules\Mailing\Library\Tool;', $content);
        $this->assertStringNotContainsString('Acelle\\', $content);
    }

    public function test_migrates_inline_namespaces(): void
    {
        $testFile = $this->createTestFile('TestController.php', <<<'PHP'
        <?php
        class TestController
        {
            public function index()
            {
                $campaign = \Acelle\Model\Campaign::first();
                $setting = Acelle\Model\Setting::get('key');
                event(new \Acelle\Events\CampaignUpdated($campaign));
            }
        }
        PHP);

        $this->artisan('mailing:migrate-namespaces', [
            '--files' => str_replace(base_path().'/', '', $testFile),
            '--no-backup' => true,
        ])->assertSuccessful();

        $content = File::get($testFile);

        $this->assertStringContainsString('\Modules\Mailing\Models\Campaign\Campaign::first()', $content);
        $this->assertStringContainsString('\Modules\Mailing\Models\Core\Setting::get(', $content);
        $this->assertStringContainsString('\Modules\Mailing\Events\CampaignUpdated', $content);
        $this->assertStringNotContainsString('Acelle\\', $content);
    }

    public function test_migrates_blade_templates(): void
    {
        $testFile = $this->createTestFile('test.blade.php', <<<'BLADE'
        @if(Acelle\Model\Setting::isYes('feature'))
            {{ Acelle\Library\Tool::format($value) }}
        @endif

        @can('create', new Acelle\Model\Campaign())
            Create Campaign
        @endcan
        BLADE);

        $this->artisan('mailing:migrate-namespaces', [
            '--files' => str_replace(base_path().'/', '', $testFile),
            '--no-backup' => true,
        ])->assertSuccessful();

        $content = File::get($testFile);

        $this->assertStringContainsString('\Modules\Mailing\Models\Core\Setting::isYes', $content);
        $this->assertStringContainsString('\Modules\Mailing\Library\Tool::format', $content);
        $this->assertStringContainsString('\Modules\Mailing\Models\Campaign\Campaign()', $content);
        $this->assertStringNotContainsString('Acelle\\', $content);
    }

    public function test_creates_backup_by_default(): void
    {
        $testFile = $this->createTestFile('TestController.php', <<<'PHP'
        <?php
        use Acelle\Model\Campaign;
        PHP);

        $originalContent = File::get($testFile);

        $this->artisan('mailing:migrate-namespaces', [
            '--files' => str_replace(base_path().'/', '', $testFile),
        ])->assertSuccessful();

        $backupFile = $testFile.'.bak-namespace';

        $this->assertFileExists($backupFile);
        $this->assertEquals($originalContent, File::get($backupFile));
    }

    public function test_no_backup_option_skips_backup(): void
    {
        $testFile = $this->createTestFile('TestController.php', <<<'PHP'
        <?php
        use Acelle\Model\Campaign;
        PHP);

        $this->artisan('mailing:migrate-namespaces', [
            '--files' => str_replace(base_path().'/', '', $testFile),
            '--no-backup' => true,
        ])->assertSuccessful();

        $this->assertFileDoesNotExist($testFile.'.bak-namespace');
    }

    public function test_reports_correct_statistics(): void
    {
        $testFile1 = $this->createTestFile('File1.php', 'use Acelle\Model\Campaign;');
        $testFile2 = $this->createTestFile('File2.php', 'use Acelle\Library\Tool;');
        $testFile3 = $this->createTestFile('File3.php', 'class NoChanges {}');

        $files = implode(',', array_map(
            fn ($f) => str_replace(base_path().'/', '', $f),
            [$testFile1, $testFile2, $testFile3]
        ));

        $this->artisan('mailing:migrate-namespaces', [
            '--files' => $files,
            '--no-backup' => true,
        ])
            ->expectsOutputToContain('Files Processed')
            ->expectsOutputToContain('Files Modified')
            ->expectsOutputToContain('Files Unchanged')
            ->assertSuccessful();
    }

    public function test_logs_changes_to_file(): void
    {
        $testFile = $this->createTestFile('TestController.php', <<<'PHP'
        <?php
        use Acelle\Model\Campaign;
        PHP);

        $this->artisan('mailing:migrate-namespaces', [
            '--files' => str_replace(base_path().'/', '', $testFile),
            '--no-backup' => true,
        ])->assertSuccessful();

        $logPath = storage_path('logs/namespace-migration.log');
        $this->assertFileExists($logPath);

        $logContent = File::get($logPath);
        $this->assertStringContainsString('TestController.php', $logContent);
    }

    public function test_handles_multiple_replacements_in_single_file(): void
    {
        $testFile = $this->createTestFile('TestController.php', <<<'PHP'
        <?php
        use Acelle\Model\Campaign;
        use Acelle\Model\MailList;
        use Acelle\Model\Subscriber;

        class TestController
        {
            public function index()
            {
                $campaign = Acelle\Model\Campaign::first();
                $list = \Acelle\Model\MailList::find(1);
                $subscriber = new Acelle\Model\Subscriber();
            }
        }
        PHP);

        $this->artisan('mailing:migrate-namespaces', [
            '--files' => str_replace(base_path().'/', '', $testFile),
            '--no-backup' => true,
        ])->assertSuccessful();

        $content = File::get($testFile);

        $this->assertStringNotContainsString('Acelle\\', $content);
        $this->assertStringContainsString('Modules\Mailing\Models\Campaign\Campaign', $content);
        $this->assertStringContainsString('Modules\Mailing\Models\MailList\MailList', $content);
        $this->assertStringContainsString('Modules\Mailing\Models\MailList\Subscriber', $content);
    }

    public function test_idempotent_execution(): void
    {
        $testFile = $this->createTestFile('TestController.php', <<<'PHP'
        <?php
        use Acelle\Model\Campaign;
        PHP);

        // First migration
        $this->artisan('mailing:migrate-namespaces', [
            '--files' => str_replace(base_path().'/', '', $testFile),
            '--no-backup' => true,
        ])->assertSuccessful();

        $contentAfterFirst = File::get($testFile);

        // Second migration (should make no changes)
        $this->artisan('mailing:migrate-namespaces', [
            '--files' => str_replace(base_path().'/', '', $testFile),
            '--no-backup' => true,
        ])->assertSuccessful();

        $contentAfterSecond = File::get($testFile);

        $this->assertEquals($contentAfterFirst, $contentAfterSecond);
    }

    protected function createTestFile(string $name, string $content): string
    {
        $filePath = $this->testDirectory.'/'.$name;
        File::put($filePath, $content);

        return $filePath;
    }
}
