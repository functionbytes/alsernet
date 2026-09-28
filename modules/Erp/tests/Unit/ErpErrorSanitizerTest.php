<?php

namespace Modules\Erp\Tests\Unit;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Erp\Models\ErpEndpointLog;
use Modules\Erp\Support\ErpErrorSanitizer;
use Tests\TestCase;

class ErpErrorSanitizerTest extends TestCase
{
    public function test_hides_internal_details_when_not_debugging(): void
    {
        config(['app.debug' => false]);

        $e = new \RuntimeException('SQLSTATE[HY000] [2002] Connection refused (host 10.0.0.5, user erp)');

        $this->assertSame(ErpErrorSanitizer::GENERIC, ErpErrorSanitizer::forClient($e));
    }

    public function test_keeps_only_the_ora_code_and_text(): void
    {
        $msg = "Error Code : 904\nError Message : ORA-00904: \"FOO\": invalid identifier (SQL: select FOO from DEVELOPER.CLIENTE_CENT where id = 5)";

        $this->assertSame('ORA-00904: "FOO": invalid identifier', ErpErrorSanitizer::forClient($msg));
    }

    public function test_translates_missing_grant(): void
    {
        $this->assertStringContainsString('GRANT', ErpErrorSanitizer::forClient('ORA-00942: table or view does not exist'));
    }

    public function test_model_not_found_does_not_leak_class_name(): void
    {
        $e = (new ModelNotFoundException)->setModel('Modules\\Erp\\Models\\Oracle\\Cliente\\ClienteCent', [5]);

        $this->assertSame('Recurso no encontrado', ErpErrorSanitizer::forClient($e));
    }

    public function test_endpoint_log_redacts_credential_headers(): void
    {
        $redacted = ErpEndpointLog::redactHeaders([
            'Authorization' => ['Bearer x'],
            'x-erp-token' => ['y'],
            'X-Refresh-Token' => ['z'],
            'Accept' => ['application/json'],
        ]);

        $this->assertSame('[REDACTED]', $redacted['Authorization']);
        $this->assertSame('[REDACTED]', $redacted['x-erp-token']);
        $this->assertSame('[REDACTED]', $redacted['X-Refresh-Token']);
        $this->assertSame(['application/json'], $redacted['Accept']);
    }
}
