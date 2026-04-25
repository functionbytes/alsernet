<?php

namespace Modules\Faqs\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\View\View;
use Modules\Faqs\Models\FaqCategory;

class FaqPublicController extends Controller
{
    public function index(): View
    {
        $categories = FaqCategory::query()
            ->published()
            ->with('publishedFaqs')
            ->orderBy('order')
            ->get();

        return view('faqs::public.faqs.index', compact('categories'));
    }
}
