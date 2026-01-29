<?php

namespace Modules\Mailing\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Mailing\Models\Setting;

class SettingController extends Controller
{
    /**
     * Render uploaded file.
     *
     *
     * @return \Illuminate\Http\Response
     **/
    public function file(Request $request, $filename)
    {
        $path = Setting::getUploadFilePath($filename);
        $type = mime_content_type($path);
        if ($type == 'image/svg') {
            $type = 'image/svg+xml';
        }

        return response()->file($path, ['Content-Type' => $type]);
    }
}
