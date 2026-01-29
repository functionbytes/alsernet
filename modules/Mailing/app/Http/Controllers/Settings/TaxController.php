<?php

namespace Modules\Mailing\Http\Controllers\Settings;

use Illuminate\Http\Request;
use Modules\Mailing\Http\Controllers\Controller;

class TaxController extends Controller
{
    public function settings(Request $request)
    {
        if ($request->isMethod('post')) {
            \Modules\Mailing\Models\Setting::setTaxSettings($request->tax);

            return response()->json([
                'status' => 'success',
                'message' => trans('messages.tax.settings.updated'),
            ]);
        }

        return view('admin.taxes.settings');
    }

    public function countries(Request $request)
    {
        return view('admin.taxes.countries');
    }

    public function addTax(Request $request)
    {
        $country = \Modules\Mailing\Models\Country::find($request->country_id);

        if ($request->isMethod('post')) {
            \Modules\Mailing\Models\Setting::setTaxSettings($request->tax);

            return response()->json([
                'status' => 'success',
                'message' => trans('messages.tax.settings.updated'),
            ]);
        }

        return view('admin.taxes.addTax', [
            'country' => $country,
        ]);
    }

    public function removeCountry(Request $request)
    {
        \Modules\Mailing\Models\Setting::removeTaxCountryByCode($request->code);

        return response()->json([
            'status' => 'success',
            'message' => trans('messages.tax.settings.updated'),
        ]);
    }
}
