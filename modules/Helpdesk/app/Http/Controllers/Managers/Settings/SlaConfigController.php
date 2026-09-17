<?php

namespace Modules\Helpdesk\Http\Controllers\Managers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Helpdesk\Http\Requests\Managers\Settings\UpdateSlaConfigRequest;
use Modules\Helpdesk\Models\Setting;
use Modules\Helpdesk\Models\SlaPolicy;

/**
 * Adaptador entre el modal "sla-config" del inbox (#76 ve-sla-config) y el
 * sistema real de políticas SLA. El modal espera una fila plana por
 * prioridad ({label, value}) más 2 interruptores globales; el sistema real
 * (SlaPoliciesController, modules/Helpdesk/routes/settings.php) está pensado
 * para una pantalla de administración paginada con CRUD completo por
 * política. Este controller no duplica esa lógica de negocio: solo lee/
 * actualiza el campo `first_response_time_hours` ("tiempos de respuesta",
 * título del modal) de la política activa de cada prioridad.
 *
 * Los 2 flags NO viven en helpdesk_sla_policies:
 * - `business_hours_only` ya existe por política y varía a propósito entre
 *   ellas (SlaPoliciesSeeder: urgente/crítico = 0, baja/normal/alta = 1). Un
 *   checkbox único que sobrescribiera esa columna en bloque destruiría esa
 *   diferenciación real ya wireada en ConversationSlaService.
 * - Ningún listener/mailable "notifica a un supervisor" hoy;
 *   SlaWarningThreshold solo hace broadcast al canal del inbox.
 *
 * Por eso ambos se guardan como ajustes globales del módulo (helpdesk_settings,
 * grupo "sla"), reutilizando Modules\Helpdesk\Models\Setting — el mismo
 * mecanismo que FeaturesSettingsController/IntegrationsController.
 */
class SlaConfigController extends Controller
{
    private const SETTINGS_GROUP = 'sla';

    public function __construct()
    {
        $this->middleware('can:helpdesk.sla-policies.view')->only('show');
        $this->middleware('can:helpdesk.sla-policies.update')->only('update');
    }

    public function show(): JsonResponse
    {
        return response()->json([
            'data' => $this->rows(),
            'pause_off_hours' => (bool) Setting::get(self::SETTINGS_GROUP.'.pause_off_hours', true),
            'notify_supervisor' => (bool) Setting::get(self::SETTINGS_GROUP.'.notify_supervisor', true),
        ]);
    }

    public function update(UpdateSlaConfigRequest $request): JsonResponse
    {
        $this->applyRows($request->validated('sla'));

        Setting::setMany([
            'pause_off_hours' => $request->boolean('pause_off_hours'),
            'notify_supervisor' => $request->boolean('notify_supervisor'),
        ], self::SETTINGS_GROUP, 'settings.sla.updated');

        return response()->json(['success' => true]);
    }

    /**
     * @return array<int, array{label: string, value: string}>
     */
    private function rows(): array
    {
        $policiesByPriority = SlaPolicy::query()
            ->active()
            ->whereNotNull('priority_id')
            ->whereNull('category_id')
            ->get()
            ->keyBy('priority_id');

        return $this->priorities()
            ->map(fn (object $priority): array => [
                'label' => $priority->name,
                'value' => (string) ($policiesByPriority->get($priority->id)?->first_response_time_hours ?? ''),
            ])
            ->all();
    }

    /**
     * @param  array<int, array{label: string, value: int}>  $rows
     */
    private function applyRows(array $rows): void
    {
        $priorityIdsByName = $this->priorities()->pluck('id', 'name');

        foreach ($rows as $row) {
            $priorityId = $priorityIdsByName[$row['label']] ?? null;

            if ($priorityId === null) {
                continue;
            }

            // QA 18-sep-2026: antes era un update() sobre where('priority_id', ...),
            // que en cualquier BD sin una SlaPolicy ya vinculada a esa prioridad
            // afecta 0 filas en silencio — el modal respondía éxito sin guardar
            // nada. Si ya existe, solo tocamos first_response_time_hours (no
            // pisar name/resolution_time_hours que el admin pudo personalizar
            // desde la pantalla real de políticas SLA); si no existe, se crea
            // con los mismos valores por defecto que SlaPoliciesSeeder.
            $policy = SlaPolicy::query()
                ->active()
                ->where('priority_id', $priorityId)
                ->whereNull('category_id')
                ->first();

            if ($policy) {
                $policy->update(['first_response_time_hours' => $row['value']]);
            } else {
                SlaPolicy::create([
                    'name' => "SLA Prioridad {$row['label']}",
                    'priority_id' => $priorityId,
                    'first_response_time_hours' => $row['value'],
                    'resolution_time_hours' => max((int) $row['value'] * 3, 1),
                    'is_active' => true,
                ]);
            }
        }
    }

    /**
     * Prioridades activas en orden de severidad real (level: 1=Baja..5=Crítico).
     * Sin modelo Eloquent propio: helpdesk_priorities se consulta igual en
     * todo el módulo (ver ConversationSlaService::priorityIdForSlug).
     *
     * @return Collection<int, object{id: int, name: string}>
     */
    private function priorities(): Collection
    {
        return DB::connection('helpdesk')
            ->table('helpdesk_priorities')
            ->where('is_active', true)
            ->orderBy('level')
            ->get(['id', 'name']);
    }
}
