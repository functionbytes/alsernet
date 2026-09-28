<?php

namespace Modules\HelpdeskAiPrompts\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\HelpdeskAiPrompts\Models\AiPromptBlock;
use Modules\HelpdeskAiPrompts\Models\AiPromptCase;
use Modules\HelpdeskAiPrompts\Models\AiPromptRun;
use Modules\HelpdeskAiPrompts\Models\AiPromptVersion;
use Modules\HelpdeskAiPrompts\Tests\TestCase;

abstract class HelpdeskAiPromptsTestCase extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mysql', 'mariadb', 'helpdesk'];

    protected function setUp(): void
    {
        parent::setUp();

        // AiPromptLibrarySeeder's rows are real, committed data in the shared
        // dev database (outside this test's transaction). Clearing them here
        // happens inside the transaction, so it rolls back after the test —
        // the real library is untouched, but each test starts from a blank
        // slate instead of racing the seeded content.
        AiPromptVersion::query()->delete();
        AiPromptRun::query()->delete();
        AiPromptCase::query()->delete();
        AiPromptBlock::query()->delete();
    }
}
