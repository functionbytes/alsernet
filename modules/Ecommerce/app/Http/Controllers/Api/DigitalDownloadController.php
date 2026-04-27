<?php

namespace Modules\Ecommerce\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Modules\Ecommerce\Models\OrderItem;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DigitalDownloadController extends Controller
{
    public function download(Request $request, OrderItem $item): JsonResponse|StreamedResponse
    {
        $customer = $request->user('sanctum');

        $item->loadMissing(['order', 'product.files']);

        if ($item->order->customer_id !== $customer->id) {
            return response()->json(['message' => 'No tienes acceso a este archivo.'], 403);
        }

        if ($item->product_type !== 'digital') {
            return response()->json(['message' => 'Este producto no es descargable.'], 422);
        }

        if ($item->order->payment_status !== 'paid') {
            return response()->json(['message' => 'El pago de este pedido no ha sido confirmado.'], 403);
        }

        $file = $item->product->files()->orderBy('sort_order')->first();

        if (! $file) {
            return response()->json(['message' => 'No se encontró el archivo para este producto.'], 404);
        }

        $item->increment('times_downloaded');

        $filePath = $file->file ?? $file->url;

        if ($file->file && Storage::exists($file->file)) {
            return Storage::download($file->file, $file->name ?? basename($file->file));
        }

        return response()->json(['downloadUrl' => $filePath]);
    }
}
