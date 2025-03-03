<?php
namespace App\Http\Controllers\Api\V1;

use App\Events\Campaigns\GiftvoucherCreated;
use App\Http\Resources\V1\SubscriberResource;
use App\Models\Lang;
use App\Models\Subscriber\Subscriber;
use App\Models\Subscriber\SubscriberCategorie;
use App\Models\Subscriber\SubscriberCondition;
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
        dd($request->all());

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

        $item = Subscriber::checkWithBTree($data['email']);

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

        dd($data);
        // Validar y procesar la suscripción
        // Aquí va la lógica para suscribir al usuario
        // Ejemplo:

//        $lang = Lang::iso($data['iso']);
//        dd($lang);
//        $item = Subscriber::checkWithBTree($data['email']);
//        dd($item['exists']);

//        if($item['exists']){
//            $newsletter = $item["data"];
//            $newsletter->firstname = Str::upper($data['firstname']);
//            $newsletter->lastname  =  Str::upper($data['lastname']);
//            $newsletter->erp = 1;
//            $newsletter->lopd = 1;
//            $newsletter->none = 1;
//            $newsletter->sports = 1;
//            $newsletter->parties = 1;
//            $newsletter->check = 1;
//            $newsletter->suscribe = 1;
//            $newsletter->check_at = Carbon::now()->setTimezone('Europe/Madrid');
//            $newsletter->update();
//        }else{
//
//
//            $newsletter = new Subscriber;
//            $newsletter->uid = $this->generate_uid('newsletters');
//            $newsletter->firstname = Str::upper($request->firstname);
//            $newsletter->lastname  =  Str::upper($request->lastname);
//            $newsletter->email = Str::upper($request->email);
//            $newsletter->erp = $request->erp;
//            $newsletter->lopd = $request->lopd;
//            $newsletter->none = $request->none;
//            $newsletter->sports = 1;
//            $newsletter->parties = 1;
//            $newsletter->suscribe = 1;
//            $newsletter->lang_id = $lang->id;
//            $newsletter->check_at = $request->check_at;
//            $newsletter->update();
//
//            if ($request->has('categories')) {
//                $categoriesIds = array_filter(explode(',', $request->categories));
//                $newsletter->categories()->attach($categoriesIds);
//            }
//        }

        return response()->json([
            'status' => 'success',
            'message' => 'Subscription successful'
        ], 200);
    }


    public function newsletterDischargersNone($data)
    {


        $data = Subscriber::checkWithBTree($data['email']);

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

        $data = Subscriber::checkWithBTree($data['email']);

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
        return SubscriberResource::collection(Subscriber::paginate());
    }

    /**
     * Crear una nueva newsletter.
     *
     * @group Managing Newsletters
     */
    public function store(StoreNewsletterRequest $request)
    {
        $newsletter = Subscriber::create($request->validated());

        return new SubscriberResource($newsletter);
    }

    /**
     * Mostrar una newsletter específica.
     *
     * @group Managing Newsletters
     */
    public function show(Subscriber $newsletter)
    {
        return new SubscriberResource($newsletter);
    }

    /**
     * Actualizar una newsletter.
     *
     * @group Managing Newsletters
     */
    public function update(UpdateNewsletterRequest $request, Subscriber $newsletter)
    {
        $newsletter->update($request->validated());

        return new SubscriberResource($newsletter);
    }

    /**
     * Eliminar una newsletter.
     *
     * @group Managing Newsletters
     */
    public function destroy(Subscriber $newsletter)
    {
        $newsletter->delete();

        return $this->ok('Newsletter successfully deleted');
    }
}
