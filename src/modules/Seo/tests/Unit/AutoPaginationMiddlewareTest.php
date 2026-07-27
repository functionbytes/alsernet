<?php

namespace Modules\Seo\Tests\Unit;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Modules\Seo\Http\Middleware\AutoPaginationMiddleware;
use Tests\TestCase;

/**
 * Covers AutoPaginationMiddleware — registrado globalmente en el grupo 'web'
 * (SeoServiceProvider::registerAutoPaginationMiddleware), así que corre en
 * TODAS las requests, incluidas subidas de archivo.
 *
 * Regresión: comprobar $request->has('page') después de $next($request)
 * forzaba un re-parseo completo de $request->all() (incluye allFiles()) del
 * archivo que el controlador de destino YA había movido a su ubicación final
 * (ej. Spatie MediaLibrary) — el path temporal original ya no existía,
 * lanzando FileNotFoundException y devolviendo 500 aunque la subida se
 * hubiera guardado correctamente.
 */
class AutoPaginationMiddlewareTest extends TestCase
{
    private AutoPaginationMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();

        $this->middleware = new AutoPaginationMiddleware;
    }

    public function test_does_not_crash_when_uploaded_file_was_already_moved_by_the_controller(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'php');
        file_put_contents($path, 'fake-image-content');
        $file = new UploadedFile($path, 'test.png', 'image/png', null, true);

        $request = Request::create('/panel/helpdesk/conversations/1/documents/import-from-device', 'POST');
        $request->files->set('files', [$file]);

        // Simula al controlador de destino: mueve/borra el archivo temporal
        // ANTES de que el middleware retome el control tras $next($request).
        $next = function ($req) use ($path) {
            @unlink($path);

            return response('ok');
        };

        $response = $this->middleware->handle($request, $next);

        $this->assertSame('ok', $response->getContent());
    }

    public function test_still_sets_pagination_links_when_page_query_param_is_present(): void
    {
        $request = Request::create('/alguna-pagina', 'GET', ['page' => '2']);

        $response = $this->middleware->handle($request, fn ($req) => response('ok'));

        $this->assertSame('ok', $response->getContent());

        $html = app('seo')->render();
        $this->assertStringContainsString('rel="prev"', $html);
        $this->assertStringContainsString('rel="next"', $html);
    }

    public function test_skips_pagination_when_page_query_param_is_absent(): void
    {
        $request = Request::create('/alguna-pagina', 'GET');

        $response = $this->middleware->handle($request, fn ($req) => response('ok'));

        $this->assertSame('ok', $response->getContent());

        $html = app('seo')->render();
        $this->assertStringNotContainsString('rel="prev"', $html);
        $this->assertStringNotContainsString('rel="next"', $html);
    }
}
