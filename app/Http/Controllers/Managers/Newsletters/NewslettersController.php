<?php

namespace App\Http\Controllers\Managers\Newsletters;

use App\Exports\Managers\Orders\OrdersExport;
use App\Http\Controllers\Controller;
use App\Models\Categorie;
use App\Models\Inventarie\EventCategorie;
use App\Models\Inventarie\InventarieCondition;
use App\Models\Inventarie\InventarieLocation;
use App\Models\Inventarie\InventarieLocationItem;
use App\Models\Lang;
use App\Models\Newsletter\Newsletter;
use App\Models\Newsletter\NewsletterCategorie;
use App\Models\Order\OrderCondition;
use App\Models\Order\OrderMethod;
use App\Models\Order\OrderType;
use App\Models\Product\Product;
use App\Models\Product\ProductLocation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Calculation\Category;

class NewslettersController extends Controller
{
    public function indexss(Request $request)
    {
        // Obtener los registros que necesitan un slack
        $newsletters = Newsletter::whereNull('slack')
            ->orWhere('slack', '')
            ->orWhereRaw('LENGTH(slack) = 6')
            ->get();

        foreach ($newsletters as $newsletter) {
            // Generar un slack único para cada newsletter
            $slack = $this->generate_uuid('newsletters');

            // Asegurar que el slack sea único en la base de datos
            while (Newsletter::where('slack', $slack)->exists()) {
                // Generar un nuevo slack si ya existe
                $slack = $this->generate_uuid('newsletters');
            }

            // Asignar el slack único
            $newsletter->uid = $slack;
            $newsletter->save(); // Usamos save() para actualizar sin necesidad de llamar a update()

            // Mostrar el ID del registro procesado
            dump($newsletter->id);
        }

        // Mensaje final después de procesar todos los registros
        dd('Se han generado slacks únicos para todos los registros de newsletters.');
    }



    public function index(Request $request){

        $searchKey = null ?? $request->search;
        $newsletters = Newsletter::latest();

        if ($searchKey) {
            $newsletters->when(!strpos($searchKey, '-'), function ($query) use ($searchKey) {
                $query->where('newsletters.firstname', 'like', '%' . $searchKey . '%')
                    ->orWhere('newsletters.lastname', 'like', '%' . $searchKey . '%')
                    ->orWhere(DB::raw("CONCAT(newsletters.firstname, ' ', newsletters.lastname)"), 'like', '%' . $searchKey . '%')
                    ->orWhere('newsletters.email', 'like', '%' . $searchKey . '%');
            });
        }

        $newsletters = $newsletters->paginate(100);

        return view('managers.views.newsletters.newsletters.index')->with([
            'newsletters' => $newsletters,
            'searchKey' => $searchKey,
        ]);

    }
    public function edit($slack){

        $newsletter = Newsletter::uid($slack);

        $categories = Categorie::available()->get();
        $categories->prepend('' , '');
        $categories = $categories->pluck('title','id');

        $langs = Lang::available()->get();
        $langs->prepend('' , '');
        $langs = $langs->pluck('title','id');

        $availables = collect([
            ['id' => '1', 'title' => 'Si'],
            ['id' => '0', 'title' => 'No'],
        ]);

        $availables->prepend('' , '');
        $availables = $availables->pluck('title','id');


        return view('managers.views.newsletters.newsletters.edit')->with([
            'newsletter' => $newsletter,
            'categories' => $categories,
            'availables' => $availables,
            'langs' => $langs,
        ]);

    }

    public function create(){

        $categories = Categorie::available()->get();
        $categories = $categories->pluck('title','id');

        $langs = Lang::available()->get();
        $langs->prepend('' , '');
        $langs = $langs->pluck('title','id');

        $availables = collect([
            ['id' => '1', 'title' => 'Si'],
            ['id' => '0', 'title' => 'No'],
        ]);

        $availables->prepend('' , '');
        $availables = $availables->pluck('title','id');

        return view('managers.views.newsletters.newsletters.create')->with([
            'categories' => $categories,
            'availables' => $availables,
            'langs' => $langs,
        ]);

    }

    public function update(Request $request){

        $newsletter = Newsletter::uid($request->uid);
        $newsletter->firstname = Str::upper($request->firstname);
        $newsletter->lastname  =  Str::upper($request->lastname);
        $newsletter->email = Str::upper($request->email);
        $newsletter->erp = $request->erp;
        $newsletter->lopd = $request->lopd;
        $newsletter->none = $request->none;
        $newsletter->sports = $request->sports;
        $newsletter->parties = $request->parties;
        $newsletter->suscribe = $request->suscribe;
        $newsletter->lang_id = $request->lang;
        $newsletter->check_at = $request->check_at;
        $newsletter->update();

        if ($request->has('categories')) {
            $categoriesIds = array_filter(explode(',', $request->categories));
            $newsletter->categories()->sync($categoriesIds);
        } else {
            $newsletter->categories()->detach();
        }


        return response()->json([
            'success' => true,
            'slack' => $newsletter->uid,
            'message' => 'Se actualizo el producto correctamente',
        ]);

    }

    public function store(Request $request){

        $newsletter = new Newsletter;
        $newsletter->uid = $this->generate_uid('newsletters');
        $newsletter->firstname = Str::upper($request->firstname);
        $newsletter->lastname  =  Str::upper($request->lastname);
        $newsletter->email = Str::upper($request->email);
        $newsletter->erp = $request->erp;
        $newsletter->lopd = $request->lopd;
        $newsletter->none = $request->none;
        $newsletter->sports = $request->sports;
        $newsletter->parties = $request->parties;
        $newsletter->suscribe = $request->suscribe;
        $newsletter->lang_id = $request->lang;
        $newsletter->check_at = $request->check_at;
        $newsletter->update();

        if ($request->has('categories')) {
            $categoriesIds = array_filter(explode(',', $request->categories));
            $newsletter->categories()->attach($categoriesIds);
        }

        return response()->json([
            'success' => true,
            'slack' => $newsletter->uid,
            'message' => 'Se creo el producto correctamente',
        ]);

    }

    public function destroy($slack){
        $newsletter = Product::uid($slack);
        $newsletter->delete();
        return redirect()->route('manager.products');
    }





}
