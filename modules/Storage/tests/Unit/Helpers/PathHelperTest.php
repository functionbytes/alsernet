<?php

namespace Modules\Storage\Tests\Unit\Helpers;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PathHelperTest extends TestCase
{
    // -------------------------------------------------------------------------
    // join_paths()
    // -------------------------------------------------------------------------

    #[Test]
    public function join_paths_combines_segments(): void
    {
        $this->assertEquals('/var/www/files', join_paths('/var/www', 'files'));
    }

    #[Test]
    public function join_paths_removes_duplicate_slashes(): void
    {
        $this->assertEquals('/a/b/c', join_paths('/a/', '/b/', '/c'));
    }

    #[Test]
    public function join_paths_skips_null_segments(): void
    {
        $this->assertEquals('/a/b', join_paths('/a', null, 'b'));
    }

    #[Test]
    public function join_paths_skips_empty_string_segments(): void
    {
        $this->assertEquals('/a/b', join_paths('/a', '', 'b'));
    }

    #[Test]
    public function join_paths_throws_on_http_url(): void
    {
        $this->expectException(\Exception::class);

        join_paths('/local/path', 'http://example.com/file');
    }

    #[Test]
    public function join_paths_throws_on_https_url(): void
    {
        $this->expectException(\Exception::class);

        join_paths('/local/path', 'https://example.com/file');
    }

    #[Test]
    public function join_paths_returns_single_segment_unchanged(): void
    {
        $this->assertEquals('/var/www', join_paths('/var/www'));
    }

    // -------------------------------------------------------------------------
    // getAppSubdirectory()
    // -------------------------------------------------------------------------

    #[Test]
    public function get_app_subdirectory_returns_null_for_root_installation(): void
    {
        config(['app.url' => 'https://example.com']);

        $this->assertNull(getAppSubdirectory());
    }

    #[Test]
    public function get_app_subdirectory_returns_subpath(): void
    {
        config(['app.url' => 'https://example.com/myapp']);

        $this->assertEquals('myapp', getAppSubdirectory());
    }

    #[Test]
    public function get_app_subdirectory_strips_leading_trailing_slashes(): void
    {
        config(['app.url' => 'https://example.com/myapp/']);

        $this->assertEquals('myapp', getAppSubdirectory());
    }

    #[Test]
    public function get_app_subdirectory_returns_null_when_no_path(): void
    {
        config(['app.url' => 'https://example.com']);

        $this->assertNull(getAppSubdirectory());
    }

    #[Test]
    public function get_app_subdirectory_returns_nested_path(): void
    {
        config(['app.url' => 'https://example.com/app/v2']);

        $this->assertEquals('app/v2', getAppSubdirectory());
    }

    // -------------------------------------------------------------------------
    // getAppHost()
    // -------------------------------------------------------------------------

    #[Test]
    public function get_app_host_returns_scheme_and_host(): void
    {
        config(['app.url' => 'https://example.com/app']);

        $this->assertEquals('https://example.com', getAppHost());
    }

    #[Test]
    public function get_app_host_includes_port_when_present(): void
    {
        config(['app.url' => 'https://example.com:8443/app']);

        $this->assertEquals('https://example.com:8443', getAppHost());
    }

    #[Test]
    public function get_app_host_throws_for_invalid_url(): void
    {
        config(['app.url' => 'not-a-valid-url']);

        $this->expectException(\Exception::class);

        getAppHost();
    }

    #[Test]
    public function get_app_host_handles_http(): void
    {
        config(['app.url' => 'http://localhost']);

        $this->assertEquals('http://localhost', getAppHost());
    }
}
