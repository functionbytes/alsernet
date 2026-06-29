<?php

namespace Modules\Seo\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Modules\Seo\Http\Requests\SubmitIndexNowUrlsRequest;
use Modules\Seo\Services\IndexNowService;

/**
 * Admin UI for IndexNow: view configuration, submit URLs manually, and check
 * setup status. The actual settings (key, enabled flag, endpoint) are stored
 * in config/env — this UI surfaces them and exposes manual submission.
 */
class IndexNowController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:Seo.indexnow.index')->only('index');
        $this->middleware('can:Seo.indexnow.submit')->only('submit');
    }

    public function index(IndexNowService $service): View
    {
        $status = [
            'enabled' => $service->enabled(),
            'key' => $service->key(),
            'key_location' => $service->keyLocation(),
            'host' => parse_url((string) config('app.url', ''), PHP_URL_HOST),
            'endpoint' => (string) config('seohelper.indexnow.endpoint', 'https://api.indexnow.org/indexnow'),
            'auto_submit' => (bool) config('seohelper.indexnow.auto_submit', true),
            'batch_size' => (int) config('seohelper.indexnow.batch_size', 100),
        ];

        return view('Seo::settings.indexnow.index', compact('status'));
    }

    public function submit(SubmitIndexNowUrlsRequest $request, IndexNowService $service): RedirectResponse
    {
        $urls = array_values(array_filter(array_map('trim', preg_split('/\r?\n/', $request->input('urls', '')))));

        $result = $service->submit($urls);

        $message = "IndexNow: {$result['message']}";

        return redirect()->back()->with($result['ok'] ? 'success' : 'error', $message);
    }
}
