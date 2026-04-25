<?php

namespace Modules\Ecommerce\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Ecommerce\Models\Review;

class ReviewController extends Controller
{
    public function index(): View
    {
        $reviews = Review::query()->with(['product', 'customer'])->latest()->paginate(20);

        return view('ecommerce::admin.reviews.index', compact('reviews'));
    }

    public function approve(Review $review): RedirectResponse
    {
        $review->update(['status' => 'approved']);

        return redirect()->back()->with('success', 'Resena aprobada.');
    }

    public function destroy(Review $review): RedirectResponse
    {
        $review->delete();

        return redirect()->back()->with('success', 'Resena eliminada.');
    }
}
