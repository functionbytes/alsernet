<?php

namespace Modules\Ecommerce\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Ecommerce\Models\Product;
use Modules\Ecommerce\Models\ProductQuestion;

class ProductQuestionController extends Controller
{
    public function store(Request $request, Product $product): RedirectResponse
    {
        $customer = auth('ecommerce')->user();

        $validated = $request->validate([
            'author_name' => [$customer ? 'nullable' : 'required', 'string', 'max:255'],
            'author_email' => ['nullable', 'email', 'max:255'],
            'question' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        ProductQuestion::query()->create([
            'product_id' => $product->id,
            'customer_id' => $customer?->id,
            'author_name' => $customer?->name ?? $validated['author_name'] ?? 'Anonimo',
            'author_email' => $customer?->email ?? $validated['author_email'] ?? null,
            'question' => $validated['question'],
            'is_published' => false,
        ]);

        return back()->with('success', 'Tu pregunta ha sido enviada. Sera publicada tras revision.');
    }
}
