<?php

namespace Modules\Ecommerce\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Ecommerce\Models\Review;

class ReviewController extends Controller
{
    public function index(): View
    {
        $reviews = Review::query()->with(['product', 'customer'])->latest()->paginate(20);

        return view('ecommerce::reviews.index', compact('reviews'));
    }

    public function show(Review $review): View
    {
        $review->load(['product', 'customer']);

        return view('ecommerce::reviews.show', compact('review'));
    }

    public function approve(Review $review): RedirectResponse
    {
        $review->update(['status' => 'approved']);

        return redirect()->back()->with('success', 'Resena aprobada.');
    }

    public function reply(Request $request, Review $review): RedirectResponse
    {
        $this->authorize('update', $review);

        $validated = $request->validate([
            'reply' => ['required', 'string', 'max:2000'],
        ]);

        $review->update([
            'reply' => $validated['reply'],
            'replied_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Respuesta enviada.');
    }

    public function destroy(Review $review): RedirectResponse
    {
        $review->delete();

        return redirect()->back()->with('success', 'Resena eliminada.');
    }
}
