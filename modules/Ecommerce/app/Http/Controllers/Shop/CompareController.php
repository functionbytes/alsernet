<?php

namespace Modules\Ecommerce\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Ecommerce\Models\Product;

class CompareController extends Controller
{
    public function index(): View
    {
        $compareList = session()->get('ecommerce_compare', []);
        $products = Product::query()
            ->with('brand')
            ->whereIn('id', $compareList)
            ->get();

        return view('ecommerce::shop.compare.index', compact('products'));
    }

    public function store(Request $request, Product $product): RedirectResponse
    {
        $compareList = session()->get('ecommerce_compare', []);

        if (! in_array($product->id, $compareList)) {
            $compareList[] = $product->id;
            session()->put('ecommerce_compare', $compareList);
        }

        return back()->with('success', 'Producto agregado a comparar.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $compareList = session()->get('ecommerce_compare', []);
        $compareList = array_diff($compareList, [$product->id]);
        session()->put('ecommerce_compare', array_values($compareList));

        return back()->with('success', 'Producto eliminado de comparar.');
    }

    public function clear(): RedirectResponse
    {
        session()->forget('ecommerce_compare');

        return back()->with('success', 'Lista de comparacion vaciada.');
    }
}
