<?php

namespace App\Http\Controllers\Administratives\Orders;

use App\Models\Order\Document;
use App\Models\Prestashop\Order\OrderSendErp;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use App\Http\Controllers\Controller;
use App\Models\Product\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DocumentsController extends Controller
{
        public function index(Request $request){

            $searchKey = null ?? $request->search;
            $available = null ?? $request->available;

            $documents = Document::latest();

            if ($searchKey != null) {
                $documents = $documents->where('title', 'like', '%' . $searchKey . '%');
            }

            if ($available != null) {
                $documents = $documents->where('available', $available);
            }

            $documents = $documents->paginate(paginationNumber());

            return view('administratives.views.orders.documents.index')->with([
                'documents' => $documents
            ]);

        }

    public function edit($uid){

        $document = Document::uid($uid);

        //dd($document);
        $customer = $document->customer;
        $order = $document->order;
        $cart = $document->cart;
        $invoice = $cart->addressInvoice;
        $products  = $cart->products;

        return view('administratives.views.orders.documents.edit')->with([
            'customer' => $customer,
            'document' => $document,
            'cart' => $cart,
            'order' => $order,
            'products' => $products,
            'invoice' => $invoice,
        ]);
    }


      public function update(Request $request){

          $document = Document::uid($request->uid);
          $document->proccess  = $request->proccess;
          $document->save();

          if($request->proccess == 1){
              OrderSendErp::create([
                  'id_order' => $document->order->id_order,
                  'posible_enviar' => 1,
                  'motivo_no_enviar' => '',
                  'fecha_envio' => null,
                  'error_gestion' => '',
                  'id_pedido_gestion' => '',
                  'id_usuario_gestion' => '',
                  'force_type' => 0,
              ]);
          }

          return response()->json([
            'success' => true,
            'slack' => $document->uid,
            'message' => 'Se actualizo la clase correctamente',
          ]);

      }



    public function upload(Request $request)
    {

        $document = Document::uid($request->uid);
        $type = 'documents';

        $document->clearMediaCollection($type);

        $media = $document->addMediaFromRequest('file')->toMediaCollection($type);

        return response()->json([
            'status' => 'success',
            'statement_id' => $document->id,
            'media' => [
                'id' => $media->id,
                'uuid' => $media->uuid,
                'file' => $media->file_name,
                'size' => $media->size,
                'path' => $media->getUrl(),
            ]
        ]);

    }

    public function getFile($document, $type)
    {
        $document = Document::uid($document);;
        $media = $document->getMedia($type)->first();

        if (!$media) {
            return response()->json([]);
        }

        return response()->json([[
            'id' => $media->id,
            'uuid' => $media->uuid,
            'file' => $media->file_name,
            'size' => $media->size,
            'path' => $media->getUrl(),
        ]]);
    }

    public function deleteFile($id)
    {
        $media = Media::find($id);

        if ($media) {
            $media->delete();
            return response()->json(['status' => 'deleted']);
        }

        return response()->json(['status' => 'not_found'], 404);
    }

    public function destroy($uid){
        $document = Document::uid($uid);
        $document->delete();
        return redirect()->route('administrative.documents');
    }

}

