<?php

namespace Modules\HelpdeskAgents\Tests\Unit;

use Modules\HelpdeskAgents\Models\AiAgentTool;
use Modules\HelpdeskAgents\Services\ToolExecutionService;
use Tests\TestCase;

/**
 * Endurecimiento de los "database tools": aunque estén habilitados por config
 * (allow_database=true) y quien crea la tool tenga el permiso, un SELECT solo
 * puede leer tablas de la allowlist — nunca `users`/`password_reset_tokens`.
 * Se ejercita el método privado por reflexión para aislar la lógica de guardas
 * de toda la maquinaria de sesión/logging.
 */
class DatabaseToolAllowlistTest extends TestCase
{
    private function runDbTool(string $sql): array
    {
        $service = new ToolExecutionService;
        $tool = new AiAgentTool(['type' => 'database', 'implementation' => $sql]);

        $method = new \ReflectionMethod($service, 'executeDatabaseTool');
        $method->setAccessible(true);

        return $method->invoke($service, $tool, []);
    }

    protected function setUp(): void
    {
        parent::setUp();
        config(['helpdeskagents.tools.allow_database' => true]);
    }

    public function test_database_tools_are_disabled_by_default(): void
    {
        config(['helpdeskagents.tools.allow_database' => false]);

        $this->expectExceptionMessage('Database tools are disabled by configuration');
        $this->runDbTool('SELECT 1 FROM migrations');
    }

    public function test_rejects_query_on_a_table_not_in_the_allowlist(): void
    {
        config(['helpdeskagents.tools.allowed_tables' => ['migrations']]);

        $this->expectExceptionMessage('not in the allow-list');
        $this->runDbTool('SELECT * FROM users');
    }

    public function test_rejects_everything_when_allowlist_is_empty_failclosed(): void
    {
        config(['helpdeskagents.tools.allowed_tables' => []]);

        $this->expectExceptionMessage('not in the allow-list');
        $this->runDbTool('SELECT 1 FROM migrations');
    }

    public function test_rejects_union_that_reaches_a_forbidden_table(): void
    {
        config(['helpdeskagents.tools.allowed_tables' => ['migrations']]);

        $this->expectExceptionMessage('not in the allow-list');
        $this->runDbTool('SELECT migration FROM migrations UNION SELECT email FROM users');
    }

    public function test_rejects_comma_join_that_reaches_a_forbidden_table(): void
    {
        // Regresión: `FROM migrations, users` colaba `users` porque el extractor
        // solo veía la primera tabla tras FROM. Ahora se rechaza el coma-join.
        config(['helpdeskagents.tools.allowed_tables' => ['migrations']]);

        $this->expectExceptionMessage('comma-separated (implicit) joins');
        $this->runDbTool('SELECT users.email FROM migrations, users WHERE users.id = 1');
    }

    public function test_rejects_multi_statement_and_comments(): void
    {
        config(['helpdeskagents.tools.allowed_tables' => ['migrations']]);

        $this->expectExceptionMessage('multiple statements or comments');
        $this->runDbTool('SELECT 1 FROM migrations; DROP TABLE users');
    }

    public function test_rejects_non_select(): void
    {
        config(['helpdeskagents.tools.allowed_tables' => ['migrations']]);

        $this->expectExceptionMessage('Only SELECT queries are allowed');
        $this->runDbTool('DELETE FROM migrations');
    }

    public function test_allows_a_select_on_an_allowlisted_table(): void
    {
        config(['helpdeskagents.tools.allowed_tables' => ['migrations']]);

        $rows = $this->runDbTool('SELECT COUNT(*) as c FROM migrations');

        $this->assertIsArray($rows);
        $this->assertArrayHasKey('c', $rows[0]);
    }
}
