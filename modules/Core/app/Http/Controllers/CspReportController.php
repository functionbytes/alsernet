<?php

namespace Modules\Core\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Receptor de informes Content-Security-Policy (29-sep-2026).
 *
 * POST /csp-report — sin sesión, cookies ni CSRF (la ruta NO está en el grupo
 * `web`), con throttle propio (`throttle:csp-report`). Acepta los dos formatos:
 *  - report-uri:  {"csp-report": {...}}             (application/csp-report)
 *  - report-to:   [{"type":"csp-violation","body":{...}}] (application/reports+json)
 * Registra un resumen truncado en el canal "csp" (storage/logs/csp-*.log).
 */
class CspReportController extends Controller
{
    private const FIELDS = [
        'document-uri', 'documentURL',
        'violated-directive', 'effectiveDirective', 'effective-directive',
        'blocked-uri', 'blockedURL',
        'source-file', 'sourceFile', 'line-number', 'lineNumber',
        'disposition', 'status-code', 'statusCode', 'sample',
    ];

    public function __invoke(Request $request): Response
    {
        $max = (int) config('security.csp.report_max_bytes', 16384);
        $raw = (string) $request->getContent();

        if ($raw === '' || strlen($raw) > $max) {
            return response('', 204);
        }

        $data = json_decode($raw, true);
        if (! is_array($data)) {
            return response('', 204);
        }

        $reports = [];
        if (isset($data['csp-report']) && is_array($data['csp-report'])) {
            $reports[] = $data['csp-report'];
        } elseif (array_is_list($data)) {
            foreach (array_slice($data, 0, 20) as $item) {
                if (is_array($item) && ($item['type'] ?? null) === 'csp-violation' && is_array($item['body'] ?? null)) {
                    $reports[] = $item['body'];
                }
            }
        }

        foreach ($reports as $report) {
            $entry = [];
            foreach (self::FIELDS as $field) {
                if (isset($report[$field]) && is_scalar($report[$field])) {
                    $entry[$field] = Str::limit((string) $report[$field], 300, '…');
                }
            }
            if ($entry === []) {
                continue;
            }
            $entry['ip'] = $request->ip();
            $entry['ua'] = Str::limit((string) $request->userAgent(), 200, '…');

            Log::channel('csp')->info('csp-violation', $entry);
        }

        return response('', 204);
    }
}
