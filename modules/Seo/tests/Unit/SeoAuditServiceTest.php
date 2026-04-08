<?php

namespace Modules\Seo\Tests\Unit;

use Modules\Seo\Services\SeoAuditService;
use Tests\TestCase;

class SeoAuditServiceTest extends TestCase
{
    private SeoAuditService $auditService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->auditService = new SeoAuditService;
    }

    private function invokePrivate(string $method, array $args = []): mixed
    {
        $reflection = new \ReflectionMethod(SeoAuditService::class, $method);

        return $reflection->invoke($this->auditService, ...$args);
    }

    public function test_score_to_grade_returns_a_for_90_plus(): void
    {
        $this->assertEquals('A', $this->invokePrivate('scoreToGrade', [95]));
        $this->assertEquals('A', $this->invokePrivate('scoreToGrade', [90]));
    }

    public function test_score_to_grade_returns_b_for_75_to_89(): void
    {
        $this->assertEquals('B', $this->invokePrivate('scoreToGrade', [80]));
        $this->assertEquals('B', $this->invokePrivate('scoreToGrade', [75]));
    }

    public function test_score_to_grade_returns_f_below_40(): void
    {
        $this->assertEquals('F', $this->invokePrivate('scoreToGrade', [30]));
        $this->assertEquals('F', $this->invokePrivate('scoreToGrade', [0]));
    }

    public function test_check_title_detects_missing_title(): void
    {
        $result = $this->invokePrivate('checkTitle', ['<html><body>no title here</body></html>']);
        $this->assertCount(1, $result);
        $this->assertEquals('error', $result[0]['status']);
        $this->assertEquals('title_missing', $result[0]['code']);
    }

    public function test_check_title_detects_short_title(): void
    {
        $html = '<title>Short</title>';
        $result = $this->invokePrivate('checkTitle', [$html]);
        $this->assertEquals('warning', $result[0]['status']);
        $this->assertEquals('title_short', $result[0]['code']);
    }

    public function test_check_title_detects_long_title(): void
    {
        $html = '<title>'.str_repeat('a', 65).'</title>';
        $result = $this->invokePrivate('checkTitle', [$html]);
        $this->assertEquals('warning', $result[0]['status']);
        $this->assertEquals('title_long', $result[0]['code']);
    }

    public function test_check_title_ok_for_ideal_length(): void
    {
        $html = '<title>'.str_repeat('a', 45).'</title>';
        $result = $this->invokePrivate('checkTitle', [$html]);
        $this->assertEquals('ok', $result[0]['status']);
    }

    public function test_check_h1_detects_missing(): void
    {
        $result = $this->invokePrivate('checkH1', ['<html><body><p>no h1</p></body></html>']);
        $this->assertEquals('error', $result[0]['status']);
        $this->assertEquals('h1_missing', $result[0]['code']);
    }

    public function test_check_h1_detects_multiple(): void
    {
        $html = '<h1>First</h1><h1>Second</h1>';
        $result = $this->invokePrivate('checkH1', [$html]);
        $this->assertEquals('warning', $result[0]['status']);
        $this->assertEquals('h1_multiple', $result[0]['code']);
    }

    public function test_check_https_passes_for_https_url(): void
    {
        $result = $this->invokePrivate('checkHttps', ['https://example.com/page']);
        $this->assertEquals('ok', $result[0]['status']);
    }

    public function test_check_https_fails_for_http_url(): void
    {
        $result = $this->invokePrivate('checkHttps', ['http://example.com/page']);
        $this->assertEquals('error', $result[0]['status']);
    }

    public function test_check_viewport_detects_missing(): void
    {
        $result = $this->invokePrivate('checkViewport', ['<html><head></head></html>']);
        $this->assertEquals('error', $result[0]['status']);
        $this->assertEquals('viewport_missing', $result[0]['code']);
    }

    public function test_check_viewport_passes_when_present(): void
    {
        $html = '<meta name="viewport" content="width=device-width, initial-scale=1">';
        $result = $this->invokePrivate('checkViewport', [$html]);
        $this->assertEquals('ok', $result[0]['status']);
    }

    public function test_build_result_score_starts_at_100(): void
    {
        $checks = [
            ['status' => 'ok', 'code' => 'test', 'message' => 'ok', 'recommendation' => '', 'weight' => 0],
        ];
        // Use reflection to test buildResult
        $reflection = new \ReflectionMethod(SeoAuditService::class, 'buildResult');
        $result = $reflection->invoke($this->auditService, null, $checks);
        $this->assertEquals(100, $result['score']);
        $this->assertEquals('A', $result['grade']);
    }

    public function test_build_result_deducts_error_weight(): void
    {
        $checks = [
            ['status' => 'error', 'code' => 'test', 'message' => 'error', 'recommendation' => '', 'weight' => 20],
        ];
        $reflection = new \ReflectionMethod(SeoAuditService::class, 'buildResult');
        $result = $reflection->invoke($this->auditService, null, $checks);
        $this->assertEquals(80, $result['score']);
    }

    public function test_build_result_deducts_half_weight_for_warnings(): void
    {
        $checks = [
            ['status' => 'warning', 'code' => 'test', 'message' => 'warning', 'recommendation' => '', 'weight' => 10],
        ];
        $reflection = new \ReflectionMethod(SeoAuditService::class, 'buildResult');
        $result = $reflection->invoke($this->auditService, null, $checks);
        $this->assertEquals(95, $result['score']); // deducts 5 (half of 10)
    }
}
