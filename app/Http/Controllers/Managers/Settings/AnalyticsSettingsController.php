<?php

namespace App\Http\Controllers\Managers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AnalyticsSettingsController extends Controller
{

   public function index()
      {
        return view('managers.views.settings.analytics.index')->with([
        ]);

      }


    public function update(Request $request)
    {

        $data['google_analytics_enable']  =  $request->google_analytics_enable;
        $data['google_analytics'] = $request->google_analytics;

        updateSettings($data);

        $response = [
            'status' => true,
            'error' => 'Se actualizo correctamente las configuración',
        ];

        return response()->json($response);

    }

}
