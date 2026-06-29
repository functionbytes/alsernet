<?php

namespace Modules\Ecommerce\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use Illuminate\View\View;
use Modules\Ecommerce\Models\LegalPage;

class LegalPageController extends Controller
{
    public function show(string $slug): View
    {
        $page = LegalPage::query()->published()->where('slug', $slug)->first();

        abort_unless($page !== null, 404);

        return view('ecommerce::shop.legal.show', compact('page'));
    }
}
