<?php

namespace Modules\Newsletter\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Core\Models\Setting;

class PopupController extends Controller
{
    public function show(): JsonResponse
    {
        if (Setting::get('newsletter.popup_enabled') !== '1') {
            return response()->json(['data' => ['show_popup' => false, 'message' => 'Popup disabled']]);
        }

        $html = view('newsletter::popup.index')->render();

        return response()->json(['data' => ['show_popup' => true, 'html' => $html]]);
    }
}
