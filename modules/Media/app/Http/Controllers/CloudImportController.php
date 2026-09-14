<?php

namespace Modules\Media\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Crypt;
use Modules\Media\Jobs\ImportCloudFileJob;
use Modules\Media\Models\MediaSetting;
use Modules\Media\Services\CloudImportFactory;

class CloudImportController extends Controller
{
    public function authorize(Request $request, string $provider): RedirectResponse
    {
        $driver = CloudImportFactory::make($provider);

        abort_unless($driver, 404, 'Proveedor no configurado');

        return redirect()->away($driver->authenticate((string) auth()->id()));
    }

    public function callback(Request $request, string $provider): RedirectResponse
    {
        $driver = CloudImportFactory::make($provider);

        abort_unless($driver, 404, 'Proveedor no configurado');

        $tokens = $driver->handleCallback($request->string('code'), (string) auth()->id());

        MediaSetting::updateOrCreate(
            ['user_id' => auth()->id(), 'key' => 'cloud_token_'.$provider],
            ['value' => [
                'access_token' => Crypt::encryptString($tokens['access_token'] ?? ''),
                'refresh_token' => isset($tokens['refresh_token']) ? Crypt::encryptString($tokens['refresh_token']) : null,
            ]]
        );

        return redirect('/panel/media')->with('success', 'Conectado a '.$provider);
    }

    public function listFiles(Request $request, string $provider): JsonResponse
    {
        $driver = CloudImportFactory::make($provider);

        abort_unless($driver, 404, 'Proveedor no configurado');

        $setting = MediaSetting::query()
            ->where('user_id', auth()->id())
            ->where('key', 'cloud_token_'.$provider)
            ->first();

        if (! $setting) {
            return response()->json(['message' => 'No autenticado con este proveedor'], 401);
        }

        $encryptedToken = $setting->value['access_token'] ?? null;
        $token = $encryptedToken ? Crypt::decryptString($encryptedToken) : null;
        $files = $driver->listFiles((string) $token, $request->input('folder'));

        return response()->json(['files' => $files]);
    }

    public function import(Request $request, string $provider): JsonResponse
    {
        abort_unless(CloudImportFactory::make($provider), 404, 'Proveedor no configurado');

        ImportCloudFileJob::dispatch(
            $provider,
            $request->string('path'),
            (int) auth()->id(),
            $request->integer('folder_id') ?: null
        );

        return response()->json(['success' => true, 'message' => 'Importacion en cola']);
    }
}
