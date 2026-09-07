<?php

namespace Modules\HelpdeskEmailActivity\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Modules\HelpdeskEmailActivity\Models\EmailLog;
use Throwable;

/**
 * Jobs fallidos de la cola de correo (modal "Jobs fallidos" del listado).
 *
 * TODO lo que hace este controlador está acotado a self::QUEUE: la tabla
 * failed_jobs es compartida por toda la aplicación (hoy ~15.000 filas, la
 * mayoría de otros módulos) y este módulo no tiene por qué reintentar ni
 * purgar trabajo ajeno. Cada consulta y cada acción filtra por cola, y las
 * acciones por id comprueban además que ese id pertenece a la cola antes de
 * tocarlo — sin esa comprobación, un id manipulado en la petición podría
 * purgar el job de otro módulo.
 */
class EmailQueueController extends Controller
{
    /**
     * Cola donde este módulo despacha su propio trabajo — ver
     * ResendEmailLogJob::__construct() y LogEmailSent::$queue.
     */
    private const QUEUE = 'emails';

    /** Jobs que se listan en el modal; el resto queda tras el contador. */
    private const LIST_LIMIT = 15;

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', EmailLog::class);

        $jobs = DB::table('failed_jobs')
            ->where('queue', self::QUEUE)
            ->orderByDesc('id')
            ->limit(self::LIST_LIMIT)
            ->get()
            ->map(fn (object $job): array => $this->present($job))
            ->all();

        return response()->json([
            'success' => true,
            'jobs' => $jobs,
            'failed' => DB::table('failed_jobs')->where('queue', self::QUEUE)->count(),
            'pending' => $this->pendingSize(),
            'queue' => self::QUEUE,
        ]);
    }

    public function retry(Request $request, string $uuid): JsonResponse
    {
        $this->authorize('manage', EmailLog::class);

        // Comprobar la cola ANTES de reintentar: el uuid llega de la petición
        // y sin esto se podría relanzar el job de cualquier otro módulo.
        if (! $this->belongsToQueue($uuid)) {
            return response()->json(['success' => false, 'message' => __('helpdeskemailactivity::emaillog.queue.not_found')], 404);
        }

        if (! $this->retryOne($uuid)) {
            // 409, no 500: no es un fallo del servidor sino un job que ya no se
            // puede reintentar (ver retryOne()).
            return response()->json([
                'success' => false,
                'message' => __('helpdeskemailactivity::emaillog.queue.unrestorable'),
            ], 409);
        }

        return response()->json(['success' => true, 'message' => __('helpdeskemailactivity::emaillog.queue.retried_one')]);
    }

    public function retryAll(Request $request): JsonResponse
    {
        $this->authorize('manage', EmailLog::class);

        // queue:retry acepta "all", pero eso reintentaría TODA la tabla, también
        // la de otros módulos. Se pasan los uuid de esta cola.
        $uuids = DB::table('failed_jobs')->where('queue', self::QUEUE)->pluck('uuid')->all();

        if ($uuids === []) {
            return response()->json(['success' => true, 'message' => __('helpdeskemailactivity::emaillog.queue.nothing_to_retry')]);
        }

        // Uno a uno, y no en una sola llamada con todos los ids: basta un job
        // irreparable para que la llamada conjunta aborte y los demás se
        // queden sin reintentar. Así un job roto solo se cuenta como omitido.
        $retried = 0;
        $skipped = 0;

        foreach ($uuids as $uuid) {
            $this->retryOne($uuid) ? $retried++ : $skipped++;
        }

        return response()->json([
            'success' => true,
            'message' => $skipped > 0
                ? __('helpdeskemailactivity::emaillog.queue.retried_partial', ['count' => $retried, 'skipped' => $skipped])
                : trans_choice('helpdeskemailactivity::emaillog.queue.retried_many', $retried, ['count' => $retried]),
        ]);
    }

    public function flush(Request $request): JsonResponse
    {
        $this->authorize('manage', EmailLog::class);

        // Borrado directo en vez de queue:flush, que vacía la tabla entera.
        $deleted = DB::table('failed_jobs')->where('queue', self::QUEUE)->delete();

        return response()->json([
            'success' => true,
            'message' => trans_choice('helpdeskemailactivity::emaillog.queue.flushed', $deleted, ['count' => $deleted]),
        ]);
    }

    /**
     * Reencola un job fallido. Devuelve false cuando ya no se puede reintentar.
     *
     * queue:retry DESERIALIZA el payload, y un Mailable con SerializesModels
     * vuelve a buscar sus modelos: si el registro al que apuntaba se borró
     * entre medias, lanza ModelNotFoundException y ese job es irrecuperable —
     * ninguna cantidad de reintentos lo va a arreglar. No es un error del
     * servidor, así que se distingue del resto de fallos y se deja que la
     * vista lo cuente como omitido; lo único que cabe hacer con él es
     * descartarlo.
     */
    private function retryOne(string $uuid): bool
    {
        try {
            Artisan::call('queue:retry', ['id' => [$uuid]]);
        } catch (Throwable) {
            return false;
        }

        return true;
    }

    private function belongsToQueue(string $uuid): bool
    {
        return DB::table('failed_jobs')->where('uuid', $uuid)->where('queue', self::QUEUE)->exists();
    }

    /**
     * Trabajos aún pendientes en la cola (no fallidos). Redis puede no estar
     * disponible o la cola no existir todavía: eso no debe tumbar el modal,
     * así que se degrada a null y la vista muestra "—".
     */
    private function pendingSize(): ?int
    {
        try {
            return Queue::size(self::QUEUE);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @return array{uuid: string, name: string, error: string, failed_at: ?string}
     */
    private function present(object $job): array
    {
        $payload = json_decode((string) $job->payload, true);
        $name = is_array($payload) ? ($payload['displayName'] ?? null) : null;

        return [
            'uuid' => (string) $job->uuid,
            'name' => $name ? class_basename($name) : __('helpdeskemailactivity::emaillog.queue.unknown_job'),
            // Solo la primera línea de la excepción: el stack trace completo
            // son miles de caracteres y no cabe (ni aporta) en el modal.
            'error' => Str::limit(strtok((string) $job->exception, "\n") ?: '', 140),
            'failed_at' => $job->failed_at ? (string) $job->failed_at : null,
        ];
    }
}
