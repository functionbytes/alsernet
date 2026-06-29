<?php

namespace Modules\HelpdeskLivechat\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\Response;

class WidgetAssetController extends Controller
{
    /**
     * Serve the widget embed entry point.
     * GET /hd/assets/embed.js
     */
    public function embed(Request $request): Response
    {
        return $this->serve($request, 'build-helpdesklivechat/widget.js', 'application/javascript');
    }

    /**
     * Serve the main widget bundle.
     * GET /hd/assets/main.js
     */
    public function bundle(Request $request): Response
    {
        return $this->serve($request, 'build-helpdesklivechat/widget/main.js', 'application/javascript');
    }

    /**
     * Serve the main widget stylesheet.
     * GET /hd/assets/main.css
     */
    public function style(Request $request): Response
    {
        return $this->serve($request, 'build-helpdesklivechat/widget/main.css', 'text/css');
    }

    /**
     * Serve a lazy-loaded chunk (rrweb, etc.) with CORS headers.
     * GET /hd/assets/chunks/{file}
     */
    public function chunk(Request $request, string $file): Response
    {
        if (! preg_match('/^[A-Za-z0-9_-]+\.(js|css)$/', $file)) {
            abort(404);
        }

        $contentType = str_ends_with($file, '.css') ? 'text/css' : 'application/javascript';

        return $this->serve($request, "build-helpdesklivechat/widget/chunks/{$file}", $contentType);
    }

    /**
     * Serve the widget loader script alias — the URL shown in installation instructions.
     * GET /hd/widget-loader.js
     */
    public function loader(Request $request): Response
    {
        $full = public_path('build-helpdesklivechat/widget.js');

        if (! File::exists($full)) {
            return response(
                '// Widget bundle not built. Run: cd modules/HelpdeskLivechat && npm run widget:build',
                503
            )->header('Content-Type', 'application/javascript; charset=UTF-8');
        }

        return response(File::get($full), 200)
            ->header('Content-Type', 'application/javascript; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=3600')
            ->header('Access-Control-Allow-Origin', '*');
    }

    /**
     * Serve a public widget asset file with CORS and ETag headers.
     *
     * If the client sends an `If-None-Match` header matching the current ETag
     * (derived from the file's mtime), returns 304 without re-reading the file.
     *
     * Returns a 503 when the bundle has not been compiled yet.
     */
    private function serve(Request $request, string $publicPath, string $contentType): Response
    {
        $full = public_path($publicPath);

        if (! File::exists($full)) {
            return response(
                '// Widget bundle not built — run: cd modules/HelpdeskLivechat && npm run widget:build',
                503
            )->header('Content-Type', 'application/javascript; charset=UTF-8')
                ->header('Access-Control-Allow-Origin', '*');
        }

        $etag = '"'.substr(md5((string) filemtime($full)), 0, 16).'"';

        if ($request->header('If-None-Match') === $etag) {
            return response('', 304)->header('ETag', $etag);
        }

        return response(File::get($full), 200)
            ->header('Content-Type', $contentType.'; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=0, must-revalidate')
            ->header('ETag', $etag)
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
            ->header('Cross-Origin-Resource-Policy', 'cross-origin');
    }
}
