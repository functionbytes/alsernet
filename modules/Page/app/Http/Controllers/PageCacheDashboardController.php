<?php

namespace Modules\Page\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class PageCacheDashboardController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', \Modules\Page\Models\Page::class);

        return view('page::cache.dashboard');
    }
}
