<?php

namespace Modules\HelpdeskEmailActivity\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Modules\HelpdeskEmailActivity\Models\EmailLinkCheck;
use Modules\HelpdeskEmailActivity\Models\EmailLog;
use Modules\HelpdeskEmailActivity\Services\EmailHtmlCheckService;
use Modules\HelpdeskEmailActivity\Services\EmailLinkCheckService;
use Modules\HelpdeskEmailActivity\Support\CanIEmailDataset;

/**
 * Las dos comprobaciones del inspector de mensajes que no se pueden resolver
 * con lo que ya está en la fila: compatibilidad del HTML con los clientes de
 * correo, y estado real de los enlaces del mensaje.
 *
 * Ambas van por AJAX y no en el render del detalle a propósito. La primera
 * cuesta un parseo del dataset de caniemail (~640 KB) más ~190 comprobaciones,
 * demasiado para pagarlo en cada clic de fila del listado; la segunda hace
 * peticiones salientes reales y solo debe salir cuando el operador la pide.
 */
class EmailInspectionController extends Controller
{
    public function __construct(
        private readonly EmailHtmlCheckService $htmlCheck,
        private readonly EmailLinkCheckService $linkCheck,
    ) {
        $this->middleware('can:helpdeskemailactivity.view');
    }

    /**
     * Compatibilidad del HTML. El resultado depende solo del cuerpo guardado,
     * que es inmutable una vez enviado el correo, así que se cachea por
     * registro: reabrir el mismo email no vuelve a pagar el análisis.
     */
    public function htmlCheck(EmailLog $emailLog): JsonResponse
    {
        $this->authorize('view', $emailLog);

        if (! $emailLog->body_html) {
            return response()->json([
                'available' => false,
                // "No hay HTML" y "el HTML no se guardó" mandan a mirar sitios
                // distintos: uno a la plantilla, el otro a los ajustes.
                'reason' => $emailLog->htmlBodyWasDiscarded()
                    ? __('helpdeskemailactivity::emaillog.preview.inspector.html_check.discarded')
                    : __('helpdeskemailactivity::emaillog.preview.inspector.html_check.no_html'),
            ]);
        }

        $result = Cache::remember(
            EmailHtmlCheckService::cacheKey($emailLog->uid),
            now()->addDay(),
            fn () => $this->htmlCheck->run($emailLog->body_html),
        );

        return response()->json([
            'available' => true,
            'dataset_updated_at' => CanIEmailDataset::lastUpdate(),
        ] + $result);
    }

    /**
     * Estado de los enlaces. POST y no GET: dispara tráfico saliente hacia
     * terceros, no es una lectura idempotente del registro.
     */
    public function linkCheck(EmailLog $emailLog, Request $request): JsonResponse
    {
        $this->authorize('view', $emailLog);

        $result = $this->linkCheck->run(
            $emailLog->body_html,
            $emailLog->body_text,
            $request->boolean('follow'),
        );

        $this->record($emailLog, $result['Links'], $request);

        return response()->json($result + ['CheckedAt' => now()->toIso8601String()]);
    }

    /**
     * Última comprobación guardada, para no enseñar la pestaña en blanco cuando
     * ya se pidió antes: un enlace roto que alguien comprobó la semana pasada
     * sigue siendo información útil sin volver a llamar a nadie.
     */
    public function linkCheckHistory(EmailLog $emailLog): JsonResponse
    {
        $this->authorize('view', $emailLog);

        $last = EmailLinkCheck::query()
            ->where('email_log_id', $emailLog->id)
            ->max('checked_at');

        if ($last === null) {
            return response()->json(['Links' => [], 'Errors' => 0, 'Skipped' => 0, 'CheckedAt' => null]);
        }

        $rows = EmailLinkCheck::query()
            ->where('email_log_id', $emailLog->id)
            ->where('checked_at', $last)
            ->orderBy('status_code')
            ->orderBy('url')
            ->get();

        return response()->json([
            'Links' => $rows->map(fn (EmailLinkCheck $row) => [
                'URL' => $row->url,
                'StatusCode' => $row->status_code,
                'Status' => $row->status,
            ])->all(),
            'Errors' => $rows->filter(fn (EmailLinkCheck $row) => $row->failed())->count(),
            'Skipped' => $rows->where('status_code', 0)->count(),
            'CheckedAt' => $rows->first()?->checked_at?->toIso8601String(),
        ]);
    }

    /**
     * @param  list<array{URL: string, StatusCode: int, Status: string}>  $links
     */
    private function record(EmailLog $emailLog, array $links, Request $request): void
    {
        if ($links === []) {
            return;
        }

        $checkedAt = now();

        EmailLinkCheck::insert(array_map(fn (array $link) => [
            'email_log_id' => $emailLog->id,
            'url' => $link['URL'],
            'url_hash' => EmailLinkCheck::hashUrl($link['URL']),
            'status_code' => $link['StatusCode'],
            'status' => $link['Status'],
            'checked_by' => $request->user()?->id,
            'checked_at' => $checkedAt,
        ], $links));
    }
}
