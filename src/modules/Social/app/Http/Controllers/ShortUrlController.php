<?php

namespace Modules\Social\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Social\Http\Requests\StoreShortUrlRequest;
use Modules\Social\Models\ShortUrl;

class ShortUrlController extends Controller
{
    public function index()
    {
        $shortUrls = ShortUrl::where('account_id', auth()->user()->account_id)
            ->with('post')
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return view('social::short-urls.index', compact('shortUrls'));
    }

    public function store(StoreShortUrlRequest $request)
    {
        $shortUrl = ShortUrl::create($request->validated());

        return response()->json([
            'success' => true,
            'short_url' => $shortUrl->short_url,
            'data' => $shortUrl,
        ]);
    }

    public function destroy(ShortUrl $shortUrl)
    {
        $this->authorize('delete', $shortUrl);

        $shortUrl->delete();

        return redirect()
            ->route('admin.social.short-urls.index')
            ->with('success', 'URL acortada eliminada exitosamente');
    }

    public function redirect(Request $request, $code)
    {
        $shortUrl = ShortUrl::where('short_code', $code)
            ->orWhere('custom_alias', $code)
            ->firstOrFail();

        $shortUrl->trackClick();

        return redirect($shortUrl->full_url);
    }
}
