<?php

namespace App\Http\Controllers;

use App\Library\File;
use App\Library\StringHelper;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File as FileSystem;

class FileServeController extends Controller
{
    public function userFile(string $uid, string $name = ''): Response
    {
        $path = storage_path('app/users/'.$uid.'/home/files/'.$name);
        $mime = File::getFileType($path);

        if (FileSystem::exists($path)) {
            return response()->file($path, ['Content-Type' => $mime]);
        }

        abort(404);
    }

    public function userThumb(string $uid, string $name = ''): Response
    {
        $path = storage_path('app/users/'.$uid.'/home/thumbs/'.$name);

        if (FileSystem::exists($path)) {
            $mime = File::getFileType($path);

            return response()->file($path, ['Content-Type' => $mime]);
        }

        abort(404);
    }

    public function publicAssetDeprecated(string $token): Response
    {
        $decodedPath = StringHelper::base64UrlDecode($token);
        $absPath = storage_path($decodedPath);

        if (FileSystem::exists($absPath)) {
            return response()->file($absPath, [
                'Content-Type' => File::getFileType($absPath),
                'Content-Length' => filesize($absPath),
            ]);
        }

        abort(404);
    }

    public function publicAsset(string $dirname, string $basename): Response
    {
        $dirname = StringHelper::base64UrlDecode($dirname);
        $absPath = storage_path(join_paths($dirname, $basename));

        if (FileSystem::exists($absPath)) {
            return response()->file($absPath, [
                'Content-Type' => File::getFileType($absPath),
                'Content-Length' => filesize($absPath),
            ]);
        }

        abort(404);
    }

    public function clearCache(): string
    {
        Artisan::call('cache:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');
        Artisan::call('config:clear');
        Artisan::call('config:cache');

        return '<h1>Cache Borrado</h1>';
    }
}
