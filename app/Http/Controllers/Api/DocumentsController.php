<?php
namespace App\Http\Controllers\Api;

use App\Events\Campaigns\GiftvoucherCreated;
use App\Http\Resources\V1\NewsletterResource;
use App\Models\Lang;
use App\Models\Newsletter\Newsletter;
use App\Models\Newsletter\NewsletterCategorie;
use App\Models\Newsletter\NewsletterCondition;
use App\Models\Order\Document;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DocumentsController extends ApiController
{

    public function process(Request $request)
    {


        $action = $request->input('action');
        $data = $request->all();
        switch ($action) {
            case 'verification':
                return $this->documentVerification($data);
            case 'validate':
                return $this->documentValidates($data);
            case 'request':
                return $this->documentRequests($data);
            case 'upload':
                return $this->documentUpload($request);
            default:
                return response()->json([
                    'status' => 'warning',
                    'message' => 'Invalid action type'
                ], 400);
        }
    }

    public function documentRequests($data)
    {
            $document = new Document;
            $document->order_id = $data['order'];
            $document->customer_id  =  $data['customer'];
            $document->cart_id  =  $data['cart'];
            $document->type =   $data['type'];
            $document->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Subscription successful',
                'data' => [
                    'uid' => $document->uid,
                ],
            ], 200);
    }


    public function documentVerification($data)
    {

        $document = Document::order($data['order']);

        return response()->json([
            'status' => 'success',
            'message' => 'You have document from general emails.',
            'data' => [
                'uid' => $document->uid,
                'label' => $document->label,
                'type' => $document->type
            ],

        ], 200);

    }

    public function documentValidates($data)
    {

        $document = Document::uid($data['uid']);

        return response()->json([
            'status' => 'success',
            'message' => 'You have document from general emails.',
            'data' => [
                'uid' => $document->uid,
                'type' => $document->type,
                'can_upload' => is_null($document->upload_at),
            ],

        ], 200);

    }

    public function documentUpload(Request $request)
    {


            $document = Document::uid($request->input('uid'));

            if (!$document) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'No document found with this UID.'
                ], 404);
            }

            $document->clearMediaCollection('documents');
            $document->addMediaFromRequest('file')->toMediaCollection('documents');

            $document->upload_at = Carbon::now()->setTimezone('Europe/Madrid');
            $document->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Document uploaded successfully.'
            ], 200);


    }



}
