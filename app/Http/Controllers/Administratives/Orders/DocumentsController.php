<?php

namespace App\Http\Controllers\Administratives\Orders;

use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\Prestashop\Order\OrderSendErp;
use App\Http\Controllers\Controller;
use Illuminate\Support\Collection;
use App\Models\Product\Product;
use App\Models\Order\Document;
use App\Models\Prestashop\Order\Order as PrestashopOrder;
use Illuminate\Http\Request;
use setasign\Fpdi\PdfReader;
use Illuminate\Support\Str;
use setasign\Fpdi\Fpdi;
use Carbon\Carbon;

class DocumentsController extends Controller
{
    /**
     * Sincroniza un documento con los datos de su orden e importa productos
     * Método helper reutilizable para sincronización de datos y productos
     *
     * @param Document $document
     * @param PrestashopOrder $order
     * @return bool
     */
    private function syncDocumentWithOrder(Document $document, PrestashopOrder $order): bool
    {
        $customer = $order->customer;

        if (!$customer) {
            return false;
        }

        $document->order_reference = $order->reference ?? $document->order_reference;
        $document->order_date = $order->date_add ?? $document->order_date;

        $document->customer_id = $customer->id_customer;
        $document->customer_firstname = $customer->firstname;
        $document->customer_lastname = $customer->lastname;
        $document->customer_email = $customer->email;
        $document->customer_dni = $customer->siret ?? null;
        $document->customer_company = $customer->company ?? null;

        $document->save();
        $document->captureProducts();

        return true;
    }

    public function index(Request $request)
    {
        $search = trim(strtolower($request->get('search')));
        $proccess = $request->get('proccess');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        $perPage = paginationNumber();

        $query = Document::filterListing($search, $proccess);

        // Filtrar por rango de fechas si se proporciona
        if ($dateFrom) {
            $query->whereDate('created_at', '>=', Carbon::createFromFormat('Y-m-d', $dateFrom));
        }
        if ($dateTo) {
            $query->whereDate('created_at', '<=', Carbon::createFromFormat('Y-m-d', $dateTo));
        }

        $documents = $query->paginate($perPage);

        return view('administratives.views.orders.documents.index')->with([
            'documents' => $documents,
            'searchKey' => $search,
            'proccess' => $proccess,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]);
    }


    public function import()
    {
        return view('administratives.views.orders.documents.import');
    }

    public function edit($uid){

        $document = Document::uid($uid);
        $products = $document->products;
        $sources = ['email', 'api', 'whatsapp', 'wp', 'manual'];

        return view('administratives.views.orders.documents.edit')->with([
            'document' => $document,
            'products' => $products,
            'sources' => $sources,
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
        $document->proccess = $request->proccess;

        // Actualizar source si se proporciona
        if ($request->has('source')) {
            $document->source = $request->source;
        }

        $document->save();

        if($request->proccess == 1){
            OrderSendErp::create([
                'id_order' => $document->order_id,
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

    public function resendReminderEmail($uid)
    {
        $document = Document::uid($uid);

        if (!$document) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Document not found.'
            ], 404);
        }

        event(new \App\Events\Documents\DocumentReminderRequested($document));

        $document->reminder_at = now();
        $document->save();

        return response()->json([
            'success' => true,
            'message' => 'Email de recordatorio enviado correctamente'
        ]);
    }

    public function confirmDocumentUpload($uid)
    {
        $document = Document::uid($uid);

        if (!$document) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Document not found.'
            ], 404);
        }

        if (!$document->confirmed_at || $document->media->count() === 0) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Document has not been uploaded yet.'
            ], 400);
        }

        $document->confirmed_at = now();
        $document->proccess = 1;
        $document->save();

        return response()->json([
            'success' => true,
            'message' => 'Carga de documento confirmada correctamente'
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

    /**
     * Sincroniza todos los documentos con los datos de sus órdenes
     * Incluye importación de productos
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function syncAllDocuments()
    {
        try {
            $synced = 0;
            $failed = 0;
            $errors = [];

            $documents = Document::get();

            if ($documents->isEmpty()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'No documents to synchronize.',
                    'data' => [
                        'synced' => 0,
                        'failed' => 0,
                        'total' => 0,
                    ],
                ], 200);
            }

            foreach ($documents as $document) {
                try {
                    $order = PrestashopOrder::find($document->order_id);

                    if (!$order) {
                        $failed++;
                        $errors[] = [
                            'uid' => $document->uid,
                            'order_id' => $document->order_id,
                            'reason' => 'Order not found in Prestashop'
                        ];
                        continue;
                    }

                    if (!$this->syncDocumentWithOrder($document, $order)) {
                        $failed++;
                        $errors[] = [
                            'uid' => $document->uid,
                            'order_id' => $document->order_id,
                            'reason' => 'Customer not found'
                        ];
                        continue;
                    }

                    $synced++;

                } catch (\Exception $e) {
                    $failed++;
                    $errors[] = [
                        'uid' => $document->uid,
                        'order_id' => $document->order_id,
                        'reason' => $e->getMessage()
                    ];
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => "Synchronization completed. {$synced} documents synced, {$failed} failed.",
                'data' => [
                    'synced' => $synced,
                    'failed' => $failed,
                    'total' => $documents->count(),
                    'errors' => $failed > 0 ? $errors : [],
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Synchronization failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Sincroniza documentos de una orden específica
     * Incluye importación de productos
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function syncByOrderId(Request $request)
    {
        $orderId = $request->input('order_id') ?? $request->query('order_id');

        if (!$orderId) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Missing order_id parameter'
            ], 400);
        }

        try {
            $documents = Document::where('order_id', $orderId)->get();

            if ($documents->isEmpty()) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'No documents found for this order ID.'
                ], 404);
            }

            $order = PrestashopOrder::find($orderId);

            if (!$order) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Order not found in Prestashop.'
                ], 404);
            }

            $synced = 0;
            $failed = 0;
            $errors = [];

            foreach ($documents as $document) {
                try {
                    if (!$this->syncDocumentWithOrder($document, $order)) {
                        $failed++;
                        $errors[] = [
                            'uid' => $document->uid,
                            'reason' => 'Customer not found'
                        ];
                        continue;
                    }
                    $synced++;
                } catch (\Exception $e) {
                    $failed++;
                    $errors[] = [
                        'uid' => $document->uid,
                        'reason' => $e->getMessage()
                    ];
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => "Successfully synced {$synced} document(s) for order {$orderId}.",
                'data' => [
                    'order_id' => $orderId,
                    'synced' => $synced,
                    'failed' => $failed,
                    'total' => $documents->count(),
                    'order_reference' => $order->reference,
                    'customer_name' => $order->customer ? "{$order->customer->firstname} {$order->customer->lastname}" : null,
                    'errors' => $failed > 0 ? $errors : [],
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Synchronization failed: ' . $e->getMessage(),
            ], 500);
        }
    }

}



