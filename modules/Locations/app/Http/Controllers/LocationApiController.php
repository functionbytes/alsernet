<?php

namespace Modules\Locations\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Locations\Models\City;
use Modules\Locations\Models\Country;
use Modules\Locations\Models\State;

class LocationApiController extends Controller
{
    public function countries(): JsonResponse
    {
        $countries = Country::query()
            ->active()
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($countries);
    }

    public function states(Request $request): JsonResponse
    {
        $request->validate([
            'country_id' => ['required', 'integer'],
        ]);

        $states = State::query()
            ->active()
            ->ordered()
            ->where('country_id', $request->input('country_id'))
            ->get(['id', 'name']);

        return response()->json($states);
    }

    public function cities(Request $request): JsonResponse
    {
        $request->validate([
            'state_id' => ['required', 'integer'],
        ]);

        $cities = City::query()
            ->active()
            ->ordered()
            ->where('state_id', $request->input('state_id'))
            ->get(['id', 'name']);

        return response()->json($cities);
    }
}
