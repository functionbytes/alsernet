<?php

namespace Modules\Locations\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Locations\Models\City;
use Modules\Locations\Models\Country;
use Modules\Locations\Models\State;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LocationImportController extends Controller
{
    public function index(): View
    {
        $this->authorize('locations.countries.create');

        return view('locations::import.index');
    }

    public function import(Request $request): RedirectResponse
    {
        $this->authorize('locations.countries.create');

        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
        ], [
            'file.required' => 'El archivo CSV es obligatorio.',
            'file.mimes' => 'El archivo debe ser de tipo CSV.',
            'file.max' => 'El archivo no puede superar los 10 MB.',
        ]);

        $path = $request->file('file')->getRealPath();
        $handle = fopen($path, 'r');

        if ($handle === false) {
            return redirect()->route('locations.import')
                ->with('error', 'No se pudo leer el archivo.');
        }

        $created = 0;
        $firstRow = true;

        while (($row = fgetcsv($handle)) !== false) {
            if ($firstRow) {
                $firstRow = false;

                continue;
            }

            if (count($row) < 5) {
                continue;
            }

            [$countryCode, $countryName, $stateName, $stateCode, $cityName] = $row;

            $countryCode = trim($countryCode);
            $countryName = trim($countryName);
            $stateName = trim($stateName);
            $stateCode = trim($stateCode);
            $cityName = trim($cityName);

            if (empty($countryCode) || empty($cityName)) {
                continue;
            }

            $country = Country::query()->firstOrCreate(
                ['code' => $countryCode],
                ['name' => $countryName ?: $countryCode, 'is_active' => true, 'order' => 0]
            );

            $state = State::query()->firstOrCreate(
                ['country_id' => $country->id, 'name' => $stateName],
                ['code' => $stateCode ?: null, 'is_active' => true, 'order' => 0]
            );

            $city = City::query()->firstOrCreate(
                ['state_id' => $state->id, 'name' => $cityName],
                ['country_id' => $country->id, 'is_active' => true, 'order' => 0]
            );

            if ($city->wasRecentlyCreated) {
                $created++;
            }
        }

        fclose($handle);

        return redirect()->route('locations.import')
            ->with('success', "Importación completada. Se crearon {$created} ciudades nuevas.");
    }

    public function export(): StreamedResponse
    {
        $this->authorize('locations.countries.view');

        $cities = City::query()
            ->with(['country', 'state'])
            ->where('is_active', true)
            ->orderBy('country_id')
            ->orderBy('state_id')
            ->orderBy('name')
            ->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="locations_export.csv"',
        ];

        $callback = function () use ($cities) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['country_code', 'country_name', 'state_name', 'state_code', 'city_name']);

            foreach ($cities as $city) {
                fputcsv($handle, [
                    $city->country?->code ?? '',
                    $city->country?->name ?? '',
                    $city->state?->name ?? '',
                    $city->state?->code ?? '',
                    $city->name,
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
