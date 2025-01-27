<?php
namespace App\Http\Controllers\Api\V1;

use App\Events\Campaigns\GiftvoucherCreated;
use App\Http\Resources\V1\NewsletterResource;
use App\Models\Lang;
use App\Models\Newsletter\Newsletter;
use App\Models\Newsletter\NewsletterCategorie;
use App\Models\Newsletter\NewsletterCondition;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class NewslettersController extends ApiController
{




    public function campaigns(Request $request)
    {
        $action = $request->input('action');
        $data = $request->all();



        switch ($action) {
            case 'campaigns':
                return $this->newsletterCampaigns($data);
            default:
                return response()->json([
                    'status' => 'warning',
                    'message' => 'Invalid action type'
                ], 400);
        }
    }

    public function process(Request $request)
    {
        $action = $request->input('action');
        $data = $request->all();

        switch ($action) {
            case 'checkat':
                return $this->newsletterCheckat($data);
            case 'subscribe':
                return $this->newsletterSubscribe($data);
            case 'unsubscribe_none':
                return $this->newsletterDischargersNone($data);
            case 'unsubscribe_parties':
                return $this->newsletterDischargersParties($data);
            case 'unsubscribe_sports':
                return $this->newsletterDischargersSports($data);
            default:
                return response()->json([
                    'status' => 'warning',
                    'message' => 'Invalid action type'
                ], 400);
        }
    }

    public function newsletterCampaigns($data)
    {

        $item = Newsletter::checkWithBTree($data['email']);

        if ($item["exists"]) {
            if (!$item["data"]->send) {
                $newsletter = $item["data"];
                $newsletter->firstname = Str::upper($data['firstname']);
                $newsletter->lastname  =  Str::upper($data['lastname']);
                $newsletter->erp = 1;
                $newsletter->lopd = 1;
                $newsletter->none = 1;
                $newsletter->sports = 1;
                $newsletter->parties = 1;
                $newsletter->check = 1;
                $newsletter->suscribe = 1;
                $newsletter->send = 1;
                $newsletter->check_at = Carbon::now()->setTimezone('Europe/Madrid');
                $newsletter->update();

                if ($data['categories']) {
                    $categoriesIds = array_filter(explode(',', $data['categories']));
                    $newsletter->categories()->sync($categoriesIds);
                } else {
                    $newsletter->categories()->detach();
                }

                //GiftvoucherCreated::dispatch($newsletter);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Subscription successful'
                ], 200);
            }else{
                return response()->json([
                    'status' => 'warning',
                    'type' => 'send',
                    'message' => 'Subscription warning'
                ], 200);
            }
        }else{
            return response()->json([
                'status' => 'warning',
                'type' => 'exist',
                'message' => 'Subscription warning'
            ], 200);
        }

    }

    /**
     * Crear una nueva suscripción.
     *
     * @group Managing Newsletters
     */
    public function newsletterSubscribe($data)
    {
        // Validar y procesar la suscripción
        // Aquí va la lógica para suscribir al usuario
        // Ejemplo:

        $lang = Lang::iso($data['iso']);
        dd($lang);
        $item = Newsletter::checkWithBTree($data['email']);
        dd($item['exists']);

        if($item['exists']){
            $newsletter = $item["data"];
            $newsletter->firstname = Str::upper($data['firstname']);
            $newsletter->lastname  =  Str::upper($data['lastname']);
            $newsletter->erp = 1;
            $newsletter->lopd = 1;
            $newsletter->none = 1;
            $newsletter->sports = 1;
            $newsletter->parties = 1;
            $newsletter->check = 1;
            $newsletter->suscribe = 1;
            $newsletter->check_at = Carbon::now()->setTimezone('Europe/Madrid');
            $newsletter->update();
        }else{


            $newsletter = new Newsletter;
            $newsletter->uid = $this->generate_uid('newsletters');
            $newsletter->firstname = Str::upper($request->firstname);
            $newsletter->lastname  =  Str::upper($request->lastname);
            $newsletter->email = Str::upper($request->email);
            $newsletter->erp = $request->erp;
            $newsletter->lopd = $request->lopd;
            $newsletter->none = $request->none;
            $newsletter->sports = 1;
            $newsletter->parties = 1;
            $newsletter->suscribe = 1;
            $newsletter->lang_id = $lang->id;
            $newsletter->check_at = $request->check_at;
            $newsletter->update();

            if ($request->has('categories')) {
                $categoriesIds = array_filter(explode(',', $request->categories));
                $newsletter->categories()->attach($categoriesIds);
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Subscription successful'
        ], 200);
    }


    public function newsletterDischargersNone($data)
    {


        $data = Newsletter::checkWithBTree($data['email']);

        $newsletter = $data["data"];
        $newsletter->none = 1;
        $newsletter->update();
        dd($newsletter);
        // Lógica para desuscribirse de los correos generales
        // ...

        return response()->json([
            'status' => 'success',
            'message' => 'You have unsubscribed from general emails.'
        ], 200);
    }


    /**
     * Desuscribirse de los correos generales.
     *
     * @group Managing Newsletters
     */
    public function newsletterCheckat($data)
    {

        $data = Newsletter::checkWithBTree($data['email']);

        if($data['exists']){

            $newsletter = $data["data"];
            $newsletter->check_at = Carbon::now()->setTimezone('Europe/Madrid');
            $newsletter->update();

            return response()->json([
                'status' => 'success',
                'message' => 'You have confirmed your emails.'
            ], 200);

        }else{
            return response()->json([
                'status' => 'failed',
                'message' => 'We did not find the email in our system.'
            ], 200);

        }



    }

    /**
     * Desuscribirse de las notificaciones de fiestas.
     *
     * @group Managing Newsletters
     */
    public function newsletterDischargersParties($data)
    {
        // Lógica para desuscribirse de las notificaciones de fiestas
        // ...

        return response()->json([
            'status' => 'success',
            'message' => 'You have unsubscribed from party notifications.'
        ], 200);
    }

    /**
     * Desuscribirse de las notificaciones de deportes.
     *
     * @group Managing Newsletters
     */
    public function newsletterDischargersSports($data)
    {
        // Lógica para desuscribirse de las notificaciones de deportes
        // ...

        return response()->json([
            'status' => 'success',
            'message' => 'You have unsubscribed from sports notifications.'
        ], 200);
    }

    /**
     * Mostrar todas las newsletters.
     *
     * @group Managing Newsletters
     */
    public function index()
    {
        return NewsletterResource::collection(Newsletter::paginate());
    }

    /**
     * Crear una nueva newsletter.
     *
     * @group Managing Newsletters
     */
    public function store(StoreNewsletterRequest $request)
    {
        $newsletter = Newsletter::create($request->validated());

        return new NewsletterResource($newsletter);
    }

    /**
     * Mostrar una newsletter específica.
     *
     * @group Managing Newsletters
     */
    public function show(Newsletter $newsletter)
    {
        return new NewsletterResource($newsletter);
    }

    /**
     * Actualizar una newsletter.
     *
     * @group Managing Newsletters
     */
    public function update(UpdateNewsletterRequest $request, Newsletter $newsletter)
    {
        $newsletter->update($request->validated());

        return new NewsletterResource($newsletter);
    }

    /**
     * Eliminar una newsletter.
     *
     * @group Managing Newsletters
     */
    public function destroy(Newsletter $newsletter)
    {
        $newsletter->delete();

        return $this->ok('Newsletter successfully deleted');
    }
}
