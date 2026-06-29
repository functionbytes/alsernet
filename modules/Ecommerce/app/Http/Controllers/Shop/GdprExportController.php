<?php

namespace Modules\Ecommerce\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Modules\Ecommerce\Jobs\GenerateGdprExportJob;

class GdprExportController extends Controller
{
    public function request(): RedirectResponse
    {
        $customer = auth('ecommerce')->user();

        if (Cache::has("gdpr_export_pending_{$customer->id}")) {
            return back()->with('warning', 'Ya tienes una exportación en proceso. Espera unos minutos.');
        }

        Cache::put("gdpr_export_pending_{$customer->id}", true, now()->addMinutes(15));

        GenerateGdprExportJob::dispatch($customer->id);

        return back()->with('success', 'Solicitud recibida. Te enviaremos un correo con el enlace de descarga en unos minutos.');
    }

    public function download(Request $request, string $token, int $customer): Response|RedirectResponse
    {
        if (! $request->hasValidSignature()) {
            abort(403, 'Enlace inválido o expirado.');
        }

        $path = Cache::get("gdpr_export_{$customer}_{$token}");

        if (! $path || ! file_exists($path)) {
            abort(404, 'Archivo no encontrado o expirado.');
        }

        return response()->download($path, 'mis-datos-personales.zip', [
            'Content-Type' => 'application/zip',
        ]);
    }
}
