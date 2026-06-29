<?php

namespace Modules\Seo\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Artisan;

/**
 * Exposes the seo:health command output over HTTP so the admin dashboard
 * widget can run it with a button click and render the results inline.
 */
class SeoHealthController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:Seo.metas.index');
    }

    public function run(): JsonResponse
    {
        $exitCode = Artisan::call('seo:health', ['--json' => true]);
        $output = trim(Artisan::output());

        $decoded = json_decode($output, true);

        if (! is_array($decoded) || ! isset($decoded['checks'])) {
            return response()->json([
                'ok' => false,
                'error' => 'No se pudo parsear el output del comando.',
                'raw' => $output,
            ], 500);
        }

        $summary = ['pass' => 0, 'warn' => 0, 'fail' => 0, 'skip' => 0];
        foreach ($decoded['checks'] as $check) {
            if (isset($summary[$check['status']])) {
                $summary[$check['status']]++;
            }
        }

        return response()->json([
            'ok' => true,
            'exit_code' => $exitCode,
            'generated_at' => $decoded['generated_at'] ?? now()->toIso8601String(),
            'checks' => $decoded['checks'],
            'summary' => $summary,
        ]);
    }
}
