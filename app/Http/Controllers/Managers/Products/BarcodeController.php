<?php


namespace App\Http\Controllers\Managers\Products;


use App\Http\Controllers\Controller;
use App\Models\Faq\FaqCategorie;
use App\Models\Product\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Faq\Faq;

class BarcodeController extends Controller
{
    public function all(Request $request){

        $products = Product::all();

        return view('managers.views.settings.faqs.faqs.index')->with([
            'faqs' => $faqs,
            'available' => $available,
            'searchKey' => $searchKey,
        ]);
    }

    public function create(){

        $availables = collect([
            ['id' => '1', 'label' => 'Publico'],
            ['id' => '0', 'label' => 'Oculto'],
        ]);

        $availables = $availables->pluck('label','id');

        $categories = FaqCategorie::latest()->available()->get();
        $categories->prepend('' , '');
        $categories = $categories->pluck('title','id');

        return view('managers.views.settings.faqs.faqs.create')->with([
            'availables' => $availables,
            'categories' => $categories
        ]);

    }

    public function edit($slack){

        $faq = Faq::slack($slack);

        $availables = collect([
            ['id' => '1', 'label' => 'Publico'],
            ['id' => '0', 'label' => 'Oculto'],
        ]);

        $availables = $availables->pluck('label','id');

        $categories = FaqCategorie::latest()->available()->get();
        $categories = $categories->pluck('title','id');

        return view('managers.views.settings.faqs.faqs.edit')->with([
            'availables' => $availables,
            'categories' => $categories,
            'faq' => $faq,
        ]);
    }

    public function store(Request $request){

        $faq = new Faq;
        $faq->slack = $this->generate_slack('faqs');
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

    public function update(Request $request){

        $faq = Faq::slack($request->slack);
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

    public function destroy($slack){

       $faq = Faq::slack($slack);
       $faq->delete();

       return redirect()->route('manager.faqs');
    }

}
