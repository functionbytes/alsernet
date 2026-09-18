<?php

namespace Modules\Supplier\Http\Controllers\Settings\Suppliers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Supplier\Http\Requests\SyncSchedule\StoreSyncScheduleRequest;
use Modules\Supplier\Http\Requests\SyncSchedule\UpdateSyncScheduleRequest;
use Modules\Supplier\Jobs\SyncModelsJob;
use Modules\Supplier\Jobs\SyncProviderProductsJob;
use Modules\Supplier\Models\Sync\SyncBatch;
use Modules\Supplier\Models\Sync\SyncSchedule;
use Modules\Supplier\Models\Sync\SyncSetting;

class SupplierSyncConfigController extends Controller
{
    public function index(): View
    {
        $this->authorize('can_sync_suppliers');

        $pageTitle = 'Configuración de sincronización';

        $routes = [
            'data' => route('settings.suppliers.sync.config.data'),
            'store' => route('settings.suppliers.sync.config.store'),
            'destroy' => route('settings.suppliers.sync.config.destroy', ':uid'),
            'toggle' => route('settings.suppliers.sync.config.toggle', ':uid'),
            'trigger' => route('settings.suppliers.sync.config.trigger', ':uid'),
            'update' => route('settings.suppliers.sync.config.update', ':uid'),
            'status' => route('settings.suppliers.sync.config.status'),
        ];

        return view('supplier::settings.suppliers.sync.config', compact('pageTitle', 'routes'));
    }

    public function httpConfig(): View
    {
        $this->authorize('can_sync_suppliers');

        $pageTitle = 'Configuración HTTP de sincronización';

        return view('supplier::settings.suppliers.sync.http-config', compact('pageTitle'));
    }

    public function getData(): JsonResponse
    {
        $schedules = SyncSchedule::query()
            ->orderBy('sync_type')
            ->orderBy('hour')
            ->orderBy('minute')
            ->get();

        $data = $schedules->map(fn (SyncSchedule $s) => [
            'uid' => $s->uid,
            'sync_type' => $s->sync_type,
            'label' => $s->label,
            'hour' => $s->hour,
            'minute' => $s->minute,
            'formatted_time' => $s->formatted_time,
            'is_enabled' => $s->is_enabled,
            'last_run_status' => $this->resolveStatus($s),
            'last_run_at' => $s->last_run_at?->toIso8601String(),
            'last_batch_id' => $s->last_batch_id,
        ]);

        return response()->json(['data' => $data]);
    }

    /**
     * If a schedule has been 'running' for more than 30 min it is considered stuck.
     */
    private function resolveStatus(SyncSchedule $s): ?string
    {
        if ($s->last_run_status === 'running'
            && $s->last_run_at
            && $s->last_run_at->lt(now()->subMinutes(30))
        ) {
            return 'stuck';
        }

        return $s->last_run_status;
    }

    public function store(StoreSyncScheduleRequest $request): JsonResponse
    {
        $schedule = SyncSchedule::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Horario creado',
            'uid' => $schedule->uid,
        ], 201);
    }

    public function update(UpdateSyncScheduleRequest $request, string $uid): JsonResponse
    {
        $schedule = SyncSchedule::where('uid', $uid)->firstOrFail();

        $schedule->update($request->validated());

        return response()->json(['success' => true, 'message' => 'Horario actualizado']);
    }

    public function destroy(string $uid): JsonResponse
    {
        SyncSchedule::where('uid', $uid)->firstOrFail()->delete();

        return response()->json(['success' => true, 'message' => 'Horario eliminado']);
    }

    public function toggle(string $uid): JsonResponse
    {
        $schedule = SyncSchedule::where('uid', $uid)->firstOrFail();
        $schedule->update(['is_enabled' => ! $schedule->is_enabled]);

        return response()->json(['success' => true, 'is_enabled' => $schedule->is_enabled]);
    }

    public function bulkToggle(Request $request): JsonResponse
    {
        $this->authorize('can_sync_suppliers');

        $data = $request->validate([
            'uids' => ['required', 'array', 'min:1'],
            'uids.*' => ['string'],
            'enabled' => ['required', 'boolean'],
        ]);

        $count = SyncSchedule::whereIn('uid', $data['uids'])->update(['is_enabled' => $data['enabled']]);

        return response()->json([
            'success' => true,
            'message' => $data['enabled']
                ? "{$count} horario(s) habilitado(s)."
                : "{$count} horario(s) pausado(s).",
        ]);
    }

    public function bulkDestroy(Request $request): JsonResponse
    {
        $this->authorize('can_sync_suppliers');

        $data = $request->validate([
            'uids' => ['required', 'array', 'min:1'],
            'uids.*' => ['string'],
        ]);

        $deleted = SyncSchedule::whereIn('uid', $data['uids'])->delete();

        return response()->json([
            'success' => true,
            'message' => "{$deleted} horario(s) eliminado(s).",
            'deleted' => $deleted,
        ]);
    }

    public function triggerNow(string $uid): JsonResponse
    {
        $schedule = SyncSchedule::where('uid', $uid)->firstOrFail();

        // Leer TODOS los settings globales de sync
        $storedSettings = SyncSetting::query()->pluck('value', 'key')->toArray();

        $globalDateFrom       = $storedSettings['filter_date_from']       ?? config('supplier.erp_sync.filter_date_from');
        $globalLimit          = (int) ($storedSettings['default_limit']   ?? 0);
        $globalMode           = $storedSettings['default_mode']           ?? 'filter';
        $globalSkipAi         = (bool) ($storedSettings['default_skip_ai']         ?? false);
        $globalDescEmpty      = ($storedSettings['default_description_empty'] ?? '1') !== '0';
        $globalWebFilter      = $storedSettings['default_web_filter']     ?? '2';
        $globalForce          = ($storedSettings['default_force']         ?? '0') === '1';
        $globalRegisterOnly   = (bool) ($storedSettings['default_register_only']   ?? false);
        $globalDryRun         = (bool) ($storedSettings['default_dry_run']         ?? false);

        // Merge con metadata del schedule (permite override por horario)
        $meta             = $schedule->metadata ?? [];
        $dateFrom         = ($meta['date_from'] ?? null) ?: ($globalDateFrom ?: null);
        $dateField        = $meta['date_field']        ?? 'creation';
        $mode             = $meta['mode']              ?? $globalMode;
        $descriptionEmpty = array_key_exists('description_empty', $meta) ? (bool) $meta['description_empty'] : $globalDescEmpty;
        $webFilter        = $meta['web_filter']        ?? $globalWebFilter;
        $skipAi           = array_key_exists('skip_ai', $meta)  ? (bool) $meta['skip_ai']  : $globalSkipAi;
        $force            = array_key_exists('force', $meta)    ? (bool) $meta['force']    : $globalForce;
        $registerOnly     = array_key_exists('register_only', $meta) ? (bool) $meta['register_only'] : $globalRegisterOnly;
        $dryRun           = array_key_exists('dry_run', $meta)  ? (bool) $meta['dry_run']  : $globalDryRun;
        $limit            = isset($meta['limit']) && $meta['limit'] > 0
                            ? (int) $meta['limit']
                            : ($globalLimit > 0 ? $globalLimit : null);

        $batchName = $schedule->sync_type === 'model'
            ? 'Sincronización manual de modelos'
            : 'Sincronización manual de productos';

        $batch = SyncBatch::create([
            'batch_name'      => $batchName,
            'sync_type'       => $schedule->sync_type,
            'status'          => 'pending',
            'priority'        => 'normal',
            'triggered_by'    => 'manual',
            'filter_criteria' => [
                'date_from'         => $dateFrom,
                'date_field'        => $dateField,
                'description_empty' => $descriptionEmpty,
                'web_filter'        => $webFilter,
                'skip_ai'           => $skipAi,
                'force'             => $force,
                'register_only'     => $registerOnly,
                'dry_run'           => $dryRun,
                'limit'             => $limit,
            ],
        ]);

        $schedule->update([
            'last_run_at'      => now(),
            'last_run_status'  => 'running',
            'last_batch_id'    => $batch->id,
        ]);

        if ($schedule->sync_type === 'model') {
            SyncModelsJob::dispatch(
                $batch,
                null,         // erpProviderId
                $limit,
                $force,
                $mode,
                $dateFrom,
                $skipAi,
                $dryRun,
                null,         // erpModelId
                $descriptionEmpty,
                $webFilter,
                $registerOnly,
                $dateField,
            )->onQueue('sync');
        } else {
            SyncProviderProductsJob::dispatch($batch, null, $limit, $batch->filter_criteria)->onQueue('sync');
        }

        return response()->json([
            'success'  => true,
            'batch_id' => $batch->id,
            'message'  => 'Sincronización iniciada',
        ]);
    }

    public function getStatus(): JsonResponse
    {
        $statuses = SyncSchedule::all()
            ->keyBy('uid')
            ->map(fn (SyncSchedule $s) => [
                'last_run_status' => $this->resolveStatus($s),
                'last_run_at' => $s->last_run_at?->toIso8601String(),
                'last_batch_id' => $s->last_batch_id,
            ])
            ->toArray();

        return response()->json($statuses);
    }

    /**
     * Reset a stuck schedule back to null status.
     */
    public function resetStatus(string $uid): JsonResponse
    {
        $schedule = SyncSchedule::where('uid', $uid)->firstOrFail();

        $schedule->update(['last_run_status' => null]);

        return response()->json(['success' => true, 'message' => 'Estado reiniciado']);
    }
}
