<?php
namespace App\Http\Controllers\Api\V1;

use App\Events\Campaigns\GiftvoucherCreated;
use App\Http\Resources\V1\SubscriberResource;
use App\Jobs\SubscriberCategoriesJob;
use App\Jobs\UpdateSubscriberCategoriesJob;
use App\Models\Lang;
use App\Models\Subscriber\Subscriber;
use App\Models\Subscriber\SubscriberCategorie;
use App\Models\Subscriber\SubscriberCondition;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SubscribersController extends ApiController
{




    public function campaigns(Request $request)
    {
        $action = $request->input('action');
        $data = $request->all();



        switch ($action) {
            case 'campaigns':
                return $this->suscriberCampaigns($data);
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
                return $this->suscriberCheckat($data);
            case 'doubleat':
                return $this->suscriberCheckat($data);
            case 'subscribe':
                return $this->suscriberSubscribe($data);
            case 'unsubscribe_none':
                return $this->suscriberDischargersNone($data);
            case 'unsubscribe_parties':
                return $this->suscriberDischargersParties($data);
            case 'unsubscribe_sports':
                return $this->suscriberDischargersSports($data);
            default:
                return response()->json([
                    'status' => 'warning',
                    'message' => 'Invalid action type'
                ], 400);
        }
    }

    public function suscriberCampaigns($data)
    {

        $item = Subscriber::checkWithBTree($data['email']);

        if ($item["exists"]) {
            if (!$item["data"]->send) {
                $suscriber = $item["data"];
                $suscriber->firstname = Str::upper($data['firstname']);
                $suscriber->lastname  =  Str::upper($data['lastname']);
                $suscriber->erp = 1;
                $suscriber->lopd = 1;
                $suscriber->none = 1;
                $suscriber->sports = 1;
                $suscriber->parties = 1;
                $suscriber->check = 1;
                $suscriber->suscribe = 1;
                $suscriber->send = 1;
                $suscriber->check_at = Carbon::now()->setTimezone('Europe/Madrid');
                $suscriber->update();

                if ($data['categories']) {
                    $categoriesIds = array_filter(explode(',', $data['categories']));
                    $suscriber->categories()->sync($categoriesIds);
                } else {
                    $suscriber->categories()->detach();
                }

                //GiftvoucherCreated::dispatch($suscriber);

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

    public function suscriberSubscribe($data)
    {

        $categoriesRequest = $data['sports'];
        $lang = Lang::locate($data['lang']);

        $validation = Subscriber::checkWithPartition($data['email']);

        if ($validation["exists"]) {
            $subscriber = $validation["data"];

            // Actualizar los datos del suscriptor
            $subscriber->update([
                'email' => $data['email'],
                'firstname' => Str::upper($data['firstname']),
                'lastname' => Str::upper($data['lastname']),
            ]);

            // Si se recibe el campo 'sports', validar las categorías
            if ($validation["exists"]) {
                $subscriber = $validation["data"];

                // Actualizar los datos del suscriptor
                $subscriber->update([
                    'email' => $data['email'],
                    'firstname' => Str::upper($data['firstname']),
                    'lastname' => Str::upper($data['lastname']),
                ]);

                // Si se recibe el campo 'sports', validar las categorías
                if (isset($data['sports'])) {
                    $categoryIds = [];
                    $categoriesIds = array_filter(explode(',', $data['sports']));
                    $currentCategoryIds = $subscriber->categories()->pluck('categories.id')->toArray();

                    if (empty($currentCategoryIds)) {
                        $categoryIds  = $categoriesIds;
                        //$subscriber->categories()->syncWithoutDetaching($categoryIds);
                    } else {
                        $categoryIds = array_diff($categoriesIds, $currentCategoryIds);
                        if (!empty($categoryIds)) {
                           // $subscriber->categories()->syncWithoutDetaching($categoryIds);
                        }
                    }

                    SubscriberCategoriesJob::dispatch(
                        $subscriber,
                        $categoryIds,
                    );
                }

                return response()->json([
                    'status' => 'success',
                    'message' => 'Subscription successful',
                    'subscriber' => $subscriber,
                ], 200);
            } }

        else{

            $suscriber = Subscriber::create([
                'email' => $data['email'],
                'firstname' => Str::upper($data['firstname']),
                'lastname' => Str::upper($data['lastname']),
                'lang_id' => $lang->id ?? null,
            ]);

            if (isset($data['sports'])) {
                $categoriesIds = array_filter(explode(',', $data['sports']));
                $suscriber->categories()->sync($categoriesIds);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Subscription successful',
                'suscriber' => $suscriber,
            ], 200);

        }

        return response()->json([
            'status' => 'success',
            'message' => 'Subscription successful',
            'suscriber' => $validation,
        ], 200);

    }


    public function suscriberDischargersNone($data)
    {


        $data = Subscriber::checkWithBTree($data['email']);

        $suscriber = $data["data"];
        $suscriber->none = 1;
        $suscriber->update();
        dd($suscriber);
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
     * @group Managing suscribers
     */
    public function suscriberCheckat($data)
    {

        $data = Subscriber::checkWithBTree($data['email']);

        if($data['exists']){

            $suscriber = $data["data"];
            $suscriber->check_at = Carbon::now()->setTimezone('Europe/Madrid');
            $suscriber->update();

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
     * @group Managing suscribers
     */
    public function suscriberDischargersParties($data)
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
     * @group Managing suscribers
     */
    public function suscriberDischargersSports($data)
    {
        // Lógica para desuscribirse de las notificaciones de deportes
        // ...

        return response()->json([
            'status' => 'success',
            'message' => 'You have unsubscribed from sports notifications.'
        ], 200);
    }

    /**
     * Mostrar todas las suscribers.
     *
     * @group Managing suscribers
     */
    public function index()
    {
        return SubscriberResource::collection(Subscriber::paginate());
    }

    /**
     * Crear una nueva suscriber.
     *
     * @group Managing suscribers
     */
    public function store(Request $request)
    {
        dd($request->all());
        $suscriber = Subscriber::create($request->validated());

        return new SubscriberResource($suscriber);
    }

    /**
     * Mostrar una suscriber específica.
     *
     * @group Managing suscribers
     */
    public function show(Subscriber $suscriber)
    {
        return new SubscriberResource($suscriber);
    }

    /**
     * Actualizar una suscriber.
     *
     * @group Managing suscribers
     */
    public function update(UpdatesuscriberRequest $request, Subscriber $suscriber)
    {
        $suscriber->update($request->validated());

        return new SubscriberResource($suscriber);
    }

    /**
     * Eliminar una suscriber.
     *
     * @group Managing suscribers
     */
    public function destroy(Subscriber $suscriber)
    {
        $suscriber->delete();

        return $this->ok('suscriber successfully deleted');
    }
}
