<?php

namespace Modules\Optimize\Tests\Unit;

use Modules\Optimize\Http\Middleware\RewriteMinAssets;
use Tests\TestCase;

class RewriteMinAssetsTest extends TestCase
{
    private RewriteMinAssets $mw;

    private string $cssPath;

    private string $cssMinPath;

    private string $jsPath;

    private string $jsMinPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mw = new RewriteMinAssets;

        $this->cssPath = public_path('test-rewrite-sample.css');
        $this->cssMinPath = public_path('test-rewrite-sample.min.css');
        $this->jsPath = public_path('test-rewrite-sample.js');
        $this->jsMinPath = public_path('test-rewrite-sample.min.js');

        // Create .min versions only; the middleware decides based on their
        // existence.
        @file_put_contents($this->cssMinPath, 'body{color:red}');
        @file_put_contents($this->jsMinPath, 'console.log(1)');
    }

    protected function tearDown(): void
    {
        @unlink($this->cssMinPath);
        @unlink($this->jsMinPath);
        parent::tearDown();
    }

    public function test_rewrites_css_link_to_min_version_when_min_exists(): void
    {
        $in = '<link rel="stylesheet" href="/test-rewrite-sample.css">';
        $out = $this->mw->apply($in);

        $this->assertStringContainsString('/test-rewrite-sample.min.css', $out);
    }

    public function test_leaves_url_alone_when_min_file_missing(): void
    {
        @unlink($this->cssMinPath);
        $in = '<link rel="stylesheet" href="/test-rewrite-sample.css">';
        $out = $this->mw->apply($in);

        $this->assertStringContainsString('/test-rewrite-sample.css', $out);
        $this->assertStringNotContainsString('.min.css', $out);
    }

    public function test_rewrites_script_src_to_min_version(): void
    {
        $in = '<script src="/test-rewrite-sample.js"></script>';
        $out = $this->mw->apply($in);

        $this->assertStringContainsString('/test-rewrite-sample.min.js', $out);
    }

    public function test_skips_external_hosts(): void
    {
        $in = '<link rel="stylesheet" href="https://cdn.example.com/foo.css">';
        $out = $this->mw->apply($in);

        $this->assertSame($in, $out);
    }

    public function test_does_not_double_rewrite_already_min(): void
    {
        $in = '<link rel="stylesheet" href="/test-rewrite-sample.min.css">';
        $out = $this->mw->apply($in);

        $this->assertStringNotContainsString('.min.min.css', $out);
        $this->assertSame($in, $out);
    }
}
