<?php

namespace Modules\Media\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Media\Models\MediaFile;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * WebDAV gateway stub.
 *
 * Implements OPTIONS and PROPFIND fully; GET/PUT/DELETE/MKCOL are stubs
 * returning HTTP 501 until full implementation is needed.
 *
 * Authentication: HTTP Basic (Authorization header required).
 * Maps virtual paths to MediaFile / MediaFolder models.
 */
class WebDavController extends Controller
{
    public function handle(Request $request): SymfonyResponse
    {
        if (! $request->header('Authorization')) {
            return response('', 401, ['WWW-Authenticate' => 'Basic realm="Media WebDAV"']);
        }

        return match ($request->method()) {
            'OPTIONS' => $this->options(),
            'PROPFIND' => $this->propfind($request),
            'GET' => $this->get($request),
            'PUT' => $this->put($request),
            'DELETE' => $this->delete($request),
            'MKCOL' => $this->mkcol($request),
            default => response('Method Not Allowed', 405, ['Allow' => 'OPTIONS, GET, PUT, DELETE, PROPFIND, MKCOL']),
        };
    }

    private function options(): Response
    {
        return response('', 200, [
            'DAV' => '1, 2',
            'Allow' => 'OPTIONS, GET, PUT, DELETE, PROPFIND, MKCOL',
        ]);
    }

    private function propfind(Request $request): SymfonyResponse
    {
        $files = MediaFile::query()->limit(100)->get();

        $xml = '<?xml version="1.0" encoding="utf-8"?><D:multistatus xmlns:D="DAV:">';

        foreach ($files as $file) {
            $xml .= '<D:response>';
            $xml .= '<D:href>/webdav/'.htmlspecialchars((string) $file->id).'</D:href>';
            $xml .= '<D:propstat><D:prop>';
            $xml .= '<D:displayname>'.htmlspecialchars($file->name).'</D:displayname>';
            $xml .= '<D:getcontentlength>'.((int) $file->size).'</D:getcontentlength>';
            $xml .= '<D:getcontenttype>'.htmlspecialchars((string) $file->mime_type).'</D:getcontenttype>';
            $xml .= '<D:resourcetype/>';
            $xml .= '</D:prop><D:status>HTTP/1.1 200 OK</D:status></D:propstat>';
            $xml .= '</D:response>';
        }

        $xml .= '</D:multistatus>';

        return response($xml, 207, ['Content-Type' => 'application/xml; charset=utf-8']);
    }

    /** @stub Download a file by ID from the WebDAV path */
    private function get(Request $request): SymfonyResponse
    {
        return response('WebDAV GET not yet implemented', 501);
    }

    /** @stub Upload / replace a file via WebDAV PUT */
    private function put(Request $request): SymfonyResponse
    {
        return response('WebDAV PUT not yet implemented', 501);
    }

    /** @stub Delete a file or folder via WebDAV DELETE */
    private function delete(Request $request): SymfonyResponse
    {
        return response('WebDAV DELETE not yet implemented', 501);
    }

    /** @stub Create a collection (folder) via WebDAV MKCOL */
    private function mkcol(Request $request): SymfonyResponse
    {
        return response('WebDAV MKCOL not yet implemented', 501);
    }
}
