<?php
namespace App\Http\Controllers\Api;

    use App\Events\Campaigns\GiftvoucherCreated;
    use App\Http\Resources\V1\NewsletterResource;
    use App\Models\Comparator\Comparator;
    use App\Models\Lang;
    use App\Models\Newsletter\Newsletter;
    use App\Models\Newsletter\NewsletterCategorie;
    use App\Models\Newsletter\NewsletterCondition;
    use App\Models\Order\Document;
    use App\Services\MinderestService;
    use Carbon\Carbon;
    use Illuminate\Database\Eloquent\ModelNotFoundException;
    use Illuminate\Http\Request;
    use Illuminate\Support\Str;

class ComparatorController extends ApiController
{

    public function process(Request $request)
    {

    }

    public function get(Request $request, $uid , $iso)
    {

        $comparator = Comparator::withApiKeyByLangIso('es')->where('uid', $uid)->first();
        $apiKey = optional($comparator->comparatorLangs->first())->api_key;
        $service = new MinderestService($apiKey);
        $data = $service->getData($comparator);

        $rawData = $data['csv'] ?? null;

        if (!$rawData) {
            return response()->json(['error' => 'No data provided'], 422);
        }

        $rawData = preg_replace('/^\xEF\xBB\xBF/', '', $rawData);
        $lines = preg_split('/\r\n|\r|\n/', trim($rawData));

        array_shift($lines); // 👈 Aquí eliminamos la cabecera


        $rows = collect($lines)->map(function ($line) {
            $parts = str_getcsv($line, ';');
            return [
                'product_code' => $parts[0] ?? null,
                'marketplace'  => $parts[1] ?? null,
                'seller'       => $parts[2] ?? null,
                'price'        => isset($parts[3]) ? floatval(str_replace(',', '.', $parts[3])) : 0,
                'quantity'     => isset($parts[4]) ? (int) $parts[4] : 1,
                'url'          => $parts[5] ?? null,
                'shipping'     => isset($parts[6]) ? floatval(str_replace(',', '.', $parts[6])) : 0,
                'date'         => isset($parts[7]) && strlen(trim($parts[7])) > 0
                    ? Carbon::createFromFormat('y/m/d H:i', trim($parts[7]))->toDateTimeString()
                    : null,
            ];
        })->filter(fn($row) => !empty($row['product_code']));

        $grouped = $rows->groupBy('product_code')->map(function ($items, $code) {
            return [
                'product_code' => $code,
                'competitors' => $items->values()->toArray()
            ];
        });

        dd($grouped->take(10));
        foreach ($grouped as $entry) {
            DB::connection('mongodb')
                ->collection('comparador_es')
                ->updateOne(
                    ['product_code' => $entry['product_code']],
                    ['$set' => $entry],
                    ['upsert' => true]
                );
        }

        return response()->json([
            'success' => true,
            'inserted' => $grouped->count()
        ]);

    }


}

