<?php

namespace Modules\Campaign\Http\Controllers\Managers\Campaigns\Products;

use App\Http\Controllers\Controller;
use App\Models\Faq\FaqCategorie;
use App\Models\Faq\Template;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\Warehouse\Models\Product\Product;

class BarcodeController extends Controller
{
    public function all(Request $request)
    {

        $products = Product::all();

        return view('theme.views.backups.faqs.faqs.index')->with([
            'faqs' => $faqs,
            'available' => $available,
            'searchKey' => $searchKey,
        ]);
    }

    public function create()
    {

        $availables = collect([
            ['id' => '1', 'label' => 'Publico'],
            ['id' => '0', 'label' => 'Oculto'],
        ]);

        $availables = $availables->pluck('label', 'id');

        $categories = FaqCategorie::latest()->available()->get();
        $categories->prepend('', '');
        $categories = $categories->pluck('title', 'id');

        return view('theme.views.backups.faqs.faqs.create')->with([
            'availables' => $availables,
            'categories' => $categories,
        ]);

    }

    public function edit($uid)
    {

        $faq = Template::uid($uid);

        $availables = collect([
            ['id' => '1', 'label' => 'Publico'],
            ['id' => '0', 'label' => 'Oculto'],
        ]);

        $availables = $availables->pluck('label', 'id');

        $categories = FaqCategorie::latest()->available()->get();
        $categories = $categories->pluck('title', 'id');

        return view('theme.views.backups.faqs.faqs.edit')->with([
            'availables' => $availables,
            'categories' => $categories,
            'faq' => $faq,
        ]);
    }

    public function store(Request $request)
    {

        $faq = new Template;
        $faq->uid = $this->generate_uid('faqs');
        $faq->title = $request->title;
        $faq->description = $request->description;
        $faq->slug = Str::slug($request->title, '-');
        $faq->available = $request->available;
        $faq->category_id = $request->categorie;
        $faq->position = $request->position;
        $faq->save();

        $response = [
            'status' => true,
            'message' => 'Se ha creado correctamente',
        ];

        return response()->json($response);

    }

    public function update(Request $request)
    {

        $faq = Template::uid($request->uid);
        $faq->title = $request->title;
        $faq->description = $request->description;
        $faq->slug = Str::slug($request->title, '-');
        $faq->available = $request->available;
        $faq->category_id = $request->categorie;
        $faq->position = $request->position;
        $faq->update();

        $response = [
            'status' => true,
            'message' => 'Se ha editado correctamente',
        ];

        return response()->json($response);

    }

    public function destroy($uid)
    {

        $faq = Template::uid($uid);
        $faq->delete();

        return redirect()->route('manager.faqs');
    }
}
