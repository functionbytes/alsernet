<?php

namespace Modules\HelpdeskPrestashop\Services\Ext;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Helpdesk\Models\ConversationStatus;
use Modules\HelpdeskPrestashop\Services\PrestashopContextService;

/**
 * Mapeo de estados de pedido de PrestaShop → estado de la conversación
 * (pieza 39, "ps-state-map"). Guarda en la tabla helpdesk_ps_state_map:
 * una fila por estado de PS con acción, y la fila ps_state_id = 0 con el
 * ajuste "crear nota". Se lee sin caché a propósito: es una consulta por
 * webhook de cambio de estado, y así un cambio en la pantalla vale al
 * instante sin arrastrar los problemas de caché de Setting.
 */
class OpsmapStateMapService
{
    public const TABLE = 'helpdesk_ps_state_map';

    /** Conexión de la tabla: la misma que la migración y que Helpdesk. */
    public const CONNECTION = 'helpdesk';

    public const SETTINGS_ROW = 0;

    /**
     * Acciones posibles, en el orden del desplegable. La clave es el slug
     * del estado de conversación que se aplica (el mismo vocabulario que
     * ChangeStatusAction de las automatizaciones).
     */
    public const ACTIONS = [
        'none' => 'Sin acción',
        'open' => 'Abierto',
        'pending' => 'Pendiente',
        'resolved' => 'Resuelto',
        'closed' => 'Cerrado',
    ];

    private bool $ready = false;

    public function __construct(
        private readonly PrestashopContextService $prestashop
    ) {}

    /**
     * La tabla llega por migración: hasta que se ejecute, la pantalla avisa
     * y el listener no hace nada, en vez de reventar el webhook.
     */
    public function ready(): bool
    {
        // Solo se memoriza el «sí»: un webhook consulta ready() varias veces,
        // pero si la tabla aún no existe se vuelve a mirar en la siguiente.
        if ($this->ready) {
            return true;
        }

        try {
            return $this->ready = Schema::connection(self::CONNECTION)->hasTable(self::TABLE);
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Acciones que esta instalación puede aplicar: solo se ofrecen los
     * estados de conversación que existen de verdad (por slug), para no
     * guardar un mapeo que luego no tiene a qué estado ir.
     *
     * @return array<string, string>
     */
    public function availableActions(): array
    {
        $slugs = ConversationStatus::query()->pluck('slug')->filter()->all();
        $hasOpen = ConversationStatus::query()->where('is_open', true)->exists();

        return array_filter(self::ACTIONS, function (string $label, string $key) use ($slugs, $hasOpen) {
            return match ($key) {
                'none' => true,
                'open' => $hasOpen,
                default => in_array($key, $slugs, true),
            };
        }, ARRAY_FILTER_USE_BOTH);
    }

    /**
     * @return array{map: array<int, string>, names: array<int, string>, create_note: bool, updated_at: ?string, updated_by: ?int}
     */
    public function load(): array
    {
        $empty = ['map' => [], 'names' => [], 'create_note' => false, 'updated_at' => null, 'updated_by' => null];

        if (! $this->ready()) {
            return $empty;
        }

        $rows = $this->table()->get();
        $result = $empty;

        foreach ($rows as $row) {
            if ((int) $row->ps_state_id === self::SETTINGS_ROW) {
                $result['create_note'] = (bool) $row->create_note;
            } elseif (isset(self::ACTIONS[$row->action]) && $row->action !== 'none') {
                $result['map'][(int) $row->ps_state_id] = (string) $row->action;
                $result['names'][(int) $row->ps_state_id] = (string) ($row->ps_state_name ?? '');
            }

            if ($result['updated_at'] === null || (string) $row->updated_at > $result['updated_at']) {
                $result['updated_at'] = (string) $row->updated_at;
                $result['updated_by'] = $row->updated_by !== null ? (int) $row->updated_by : null;
            }
        }

        return $result;
    }

    public function actionFor(int $psStateId): string
    {
        if (! $this->ready()) {
            return 'none';
        }

        $action = $this->table()->where('ps_state_id', $psStateId)->value('action');

        return is_string($action) && isset(self::ACTIONS[$action]) ? $action : 'none';
    }

    public function createNote(): bool
    {
        if (! $this->ready()) {
            return false;
        }

        return (bool) $this->table()->where('ps_state_id', self::SETTINGS_ROW)->value('create_note');
    }

    /**
     * Reemplaza el mapeo completo. Solo se guardan las filas con acción: un
     * estado "Sin acción" es la ausencia de fila, así un estado nuevo creado
     * en PrestaShop nace sin efecto en el helpdesk.
     *
     * @param  array<int, string>  $map  ps_state_id => acción
     * @param  array<int, string>  $names  ps_state_id => nombre visible
     * @return array{before: array<int, string>, after: array<int, string>, create_note: bool}
     */
    public function save(array $map, array $names, bool $createNote, ?int $userId): array
    {
        $before = $this->load();
        $now = now();

        $rows = [];
        foreach ($map as $stateId => $action) {
            $stateId = (int) $stateId;
            if ($stateId <= 0 || $action === 'none' || ! isset(self::ACTIONS[$action])) {
                continue;
            }
            $rows[] = [
                'ps_state_id' => $stateId,
                'ps_state_name' => mb_substr((string) ($names[$stateId] ?? $before['names'][$stateId] ?? ''), 0, 255) ?: null,
                'action' => $action,
                'create_note' => false,
                'updated_by' => $userId,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::connection(self::CONNECTION)->transaction(function () use ($rows, $createNote, $userId, $now) {
            $this->table()->where('ps_state_id', '>', self::SETTINGS_ROW)->delete();

            if ($rows !== []) {
                $this->table()->insert($rows);
            }

            $this->table()->updateOrInsert(
                ['ps_state_id' => self::SETTINGS_ROW],
                ['ps_state_name' => null, 'action' => 'none', 'create_note' => $createNote, 'updated_by' => $userId, 'created_at' => $now, 'updated_at' => $now],
            );
        });

        $after = [];
        foreach ($rows as $row) {
            $after[$row['ps_state_id']] = $row['action'];
        }

        return ['before' => $before['map'], 'after' => $after, 'create_note' => $createNote];
    }

    private function table(): Builder
    {
        return DB::connection(self::CONNECTION)->table(self::TABLE);
    }

    /**
     * Estados de pedido de PrestaShop para la pantalla, con los ya
     * guardados aunque el bridge no responda (nombre guardado al mapear).
     *
     * @return array{states: array<int, array{id:int, name:string}>, live: bool}
     */
    public function orderStates(): array
    {
        $live = [];
        try {
            $live = $this->prestashop->getOrderStates();
        } catch (\Throwable) {
            $live = [];
        }

        $states = [];
        foreach ($live as $state) {
            $id = (int) ($state['id'] ?? 0);
            if ($id > 0) {
                $states[$id] = ['id' => $id, 'name' => trim((string) ($state['name'] ?? '')) ?: 'Estado '.$id];
            }
        }

        $isLive = $states !== [];

        foreach ($this->load()['names'] as $id => $name) {
            $states[$id] ??= ['id' => $id, 'name' => $name !== '' ? $name : 'Estado '.$id];
        }

        uasort($states, fn ($a, $b) => strcasecmp($a['name'], $b['name']));

        return ['states' => array_values($states), 'live' => $isLive];
    }

    /**
     * Nombre visible de un estado de PS: catálogo del bridge (cacheado 1 h
     * por PrestashopContextService) o el guardado en el mapeo.
     */
    public function stateName(?int $psStateId): ?string
    {
        if (! $psStateId) {
            return null;
        }

        try {
            foreach ($this->prestashop->getOrderStates() as $state) {
                if ((int) ($state['id'] ?? 0) === $psStateId) {
                    return (string) $state['name'];
                }
            }
        } catch (\Throwable) {
            // Sin catálogo: se cae al nombre guardado.
        }

        if ($this->ready()) {
            $saved = $this->table()->where('ps_state_id', $psStateId)->value('ps_state_name');
            if (is_string($saved) && $saved !== '') {
                return $saved;
            }
        }

        return null;
    }
}
