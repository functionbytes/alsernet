<?php

namespace App\Http\Controllers\Administratives\Orders;

use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\Prestashop\Order\OrderSendErp;
use App\Http\Controllers\Controller;
use Illuminate\Support\Collection;
use App\Models\Product\Product;
use App\Models\Order\Document;
use Illuminate\Http\Request;
use setasign\Fpdi\PdfReader;
use Illuminate\Support\Str;
use setasign\Fpdi\Fpdi;

class DocumentsController extends Controller
{

public function index(Request $request)
{
    $search = trim(strtolower($request->get('search')));
    $available = $request->get('available');
    $documents = Document::with('customer');


    if (!is_null($available)) {
        $documents->whereRaw("EXISTS (
            SELECT 1 FROM media
            WHERE media.model_id = request_documents.id
              AND media.model_type = ?
        ) = ?", [Document::class, $available == 1 ? 1 : 0]);
    }


    $documents = $documents->get();

    if (!empty($search)) {
        $documents = $documents->filter(function ($doc) use ($search) {
            $firstname = strtolower(optional($doc->customer)->firstname ?? '');
            $lastname  = strtolower(optional($doc->customer)->lastname ?? '');
            $orderId   = strtolower($doc->order_id ?? '');

            return str_contains($firstname, $search)
                || str_contains($lastname, $search)
                || str_contains($orderId, $search);
        });
    }


    // Ordenar por prioridad: upload_at no nulo + media asociada
 $documents = $documents->sortBy(function ($doc) {
    return [
        $doc->hasMedia() ? 0 : 1, // primero los que SÍ tienen media
        $doc->upload_at ?? now()->addYears(100), // orden por fecha, valores nulos al final
    ];
});


    // Paginar manualmente
    $page = $request->get('page', 1);
    $perPage = paginationNumber();
    $offset = ($page - 1) * $perPage;

    $paginatedDocuments = new LengthAwarePaginator(
        $documents->slice($offset, $perPage)->values(),
        $documents->count(),
        $perPage,
        $page,
        ['path' => $request->url(), 'query' => $request->query()]
    );

    return view('administratives.views.orders.documents.index')->with([
        'documents' => $paginatedDocuments,
        'searchKey' => $search,
        'available' => $available
    ]);
}


    public function edit($uid){

        $document = Document::uid($uid);
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

    public function summary($uid)
    {
        $document = Document::uid($uid);

        $mediaItems = $document->media;

        $pdfDocs = $mediaItems->filter(fn($media) => $media->mime_type === 'application/pdf');

        $imageDocs = $mediaItems->filter(function ($media) {
            if (!Str::startsWith($media->mime_type, 'image/')) return false;
            $path = $media->getPath();
            return file_exists($path) && is_readable($path) && filesize($path) > 0;
        });

         $pdf = new Fpdi();

        // Insertar imágenes
        foreach ($imageDocs as $media) {
            $path = $media->getPath();

            // 🛡️ Verificamos y preparamos la imagen
            try {
                $safePath = $this->prepareImageForPDF($path); // <- helper que normaliza a PNG válido
            } catch (Exception $e) {
                // Si hay error, saltamos la imagen
                $pdf->AddPage();
                $pdf->Cell(0, 10, 'Error con imagen: ' . basename($path));
                continue;
            }

            // ✅ Ahora trabajamos con una imagen válida
            [$width, $height] = getimagesize($safePath);
            $orientation = $width > $height ? 'L' : 'P';

            $pdf->AddPage($orientation);

            // Márgenes: 10px de borde
            $maxWidth  = $orientation === 'L' ? 277 : 190;
            $maxHeight = $orientation === 'L' ? 190 : 277;

            // Escalamos manteniendo proporción
            $ratio = min($maxWidth / $width, $maxHeight / $height);

            $newWidth  = $width * $ratio;
            $newHeight = $height * $ratio;

            // Centrar en la página
            $x = ( ($orientation === 'L' ? 297 : 210) - $newWidth ) / 2;
            $y = ( ($orientation === 'L' ? 210 : 297) - $newHeight ) / 2;

            $pdf->Image($safePath, $x, $y, $newWidth, $newHeight);
        }

        // Insertar PDFs
        foreach ($pdfDocs as $media) {
            $filePath = $media->getPath();
            if (!file_exists($filePath) || !is_readable($filePath)) continue;

            try {
                $pageCount = $pdf->setSourceFile($filePath);
                for ($i = 1; $i <= $pageCount; $i++) {
                    $tpl = $pdf->importPage($i);
                    $size = $pdf->getTemplateSize($tpl);

                    $orientation = $size['width'] > $size['height'] ? 'L' : 'P';
                    $pdf->AddPage($orientation, [$size['width'], $size['height']]);
                    $pdf->useTemplate($tpl);
                }
            } catch (\Exception $e) {
                //\Log::warning("Error al procesar PDF: {$filePath} — {$e->getMessage()}");
            }
        }

        return response($pdf->Output('S'), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename=documento_' . $document->uid . '.pdf');
    }


    private function prepareImageForPDF($srcPath, $destDir = __DIR__ . '/../../../../../storage/app/imageDocs')
    {
        if (!file_exists($srcPath) || filesize($srcPath) === 0) {
            throw new \Exception("Imagen no encontrada o vacía: " . $srcPath);
        }

        if (!is_dir($destDir)) {
            mkdir($destDir, 0777, true);
        }

        $info = getimagesize($srcPath);
        if ($info === false) {
            throw new \Exception("Archivo no es una imagen válida: " . $srcPath);
        }

        $mime = $info['mime'];

        switch ($mime) {
            case 'image/png':
                return $srcPath; // ya es PNG válido
            case 'image/jpeg':
                $image = imagecreatefromjpeg($srcPath);
                break;
            case 'image/gif':
                $image = imagecreatefromgif($srcPath);
                break;
            default:
                throw new \Exception("Formato no soportado: " . $mime);
        }

        $fileName = pathinfo($srcPath, PATHINFO_FILENAME) . '_fixed.png';
        $destPath = $destDir . '/' . $fileName;

        imagepng($image, $destPath);
        imagedestroy($image);

        return $destPath;
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



