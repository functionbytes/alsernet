<?php

namespace Modules\Helpdesk\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Group;

class MentionParser
{
    /**
     * Handles especiales con semántica fija — no son agentes ni equipos sino
     * macros que se expanden al resolver.
     */
    private const SPECIAL_HANDLES = ['all', 'here', 'team'];

    /**
     * Regex que captura @handle precedido por inicio de string, espacio o salto.
     * Permite letras, números, punto, guión y guión-bajo (Unicode).
     */
    private const PATTERN = '/(?:^|[\s])@([\p{L}0-9._-]+)/u';

    /**
     * Encuentra los handles de @ en un texto.
     *
     * @return array<int, string> handles únicos en lowercase, sin @
     */
    public function extractHandles(?string $body): array
    {
        if (! $body) {
            return [];
        }

        if (! preg_match_all(self::PATTERN, $body, $matches)) {
            return [];
        }

        return collect($matches[1])
            ->map(fn ($h) => mb_strtolower(trim($h)))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Resuelve handles a usuarios a notificar y a equipos mencionados.
     *
     * Reglas:
     * - Handles especiales: `@all`, `@here`, `@team` (ver expandSpecialHandle).
     * - Un handle puede ser username de agente (`carmen.lopez`) o key de equipo (`general_support`).
     * - Si es equipo se expande a todos sus miembros.
     * - Se excluye al autor (excludeUserId).
     * - Se devuelve sin duplicados.
     *
     * @param  Conversation|null  $conversation  Necesario para expandir `@team`.
     * @return array{users: Collection<int,User>, teams: Collection<int,Group>, handles: array<int,string>, special: array<int,string>}
     */
    public function resolve(?string $body, ?int $excludeUserId = null, ?Conversation $conversation = null): array
    {
        $handles = $this->extractHandles($body);

        if (empty($handles)) {
            return ['users' => collect(), 'teams' => collect(), 'handles' => [], 'special' => []];
        }

        // Separar especiales del resto
        $special = array_values(array_intersect($handles, self::SPECIAL_HANDLES));
        $regular = array_values(array_diff($handles, self::SPECIAL_HANDLES));

        // Resolver equipos por key exacta (entre los handles regulares)
        $teams = collect();
        if (! empty($regular)) {
            $teams = Group::query()
                ->where('is_active', true)
                ->whereIn('key', $regular)
                ->with(['users' => fn ($q) => $q->select('users.id', 'firstname', 'lastname', 'email')])
                ->get();
        }

        $teamHandles = $teams->pluck('key')->map(fn ($k) => mb_strtolower($k))->all();
        $userHandles = array_values(array_diff($regular, $teamHandles));

        // Resolver usuarios por handle ("firstname.lastname")
        $directUsers = collect();
        if (! empty($userHandles)) {
            $directUsers = User::query()
                ->select(['id', 'firstname', 'lastname', 'email'])
                ->whereRaw(
                    "LOWER(REPLACE(CONCAT(COALESCE(firstname, ''), '.', COALESCE(lastname, '')), ' ', '.')) IN (".
                    rtrim(str_repeat('?,', count($userHandles)), ',').')',
                    $userHandles
                )
                ->get();
        }

        // Expandir miembros de cada team mencionado
        $teamMemberUsers = $teams->pluck('users')->flatten()->unique('id');

        // Expandir handles especiales (@all, @here, @team)
        $specialUsers = collect();
        foreach ($special as $h) {
            $specialUsers = $specialUsers->concat($this->expandSpecialHandle($h, $conversation));
        }

        $allUsers = $directUsers
            ->concat($teamMemberUsers)
            ->concat($specialUsers)
            ->unique('id')
            ->when($excludeUserId, fn ($c) => $c->reject(fn ($u) => (int) $u->id === (int) $excludeUserId))
            ->values();

        return [
            'users' => $allUsers,
            'teams' => $teams,
            'handles' => $handles,
            'special' => $special,
        ];
    }

    /**
     * Expande un handle especial al conjunto de Users que debe notificar.
     *
     * - `@all`  → todos los usuarios del workspace.
     * - `@here` → usuarios online (sesión activa últimos 5 min).
     * - `@team` → miembros del equipo (group_id) asignado a la conversación.
     */
    private function expandSpecialHandle(string $handle, ?Conversation $conversation): Collection
    {
        $select = ['id', 'firstname', 'lastname', 'email'];

        return match ($handle) {
            'all' => User::query()->select($select)->get(),
            'here' => User::query()
                ->select($select)
                ->whereIn('id', \DB::table('sessions')
                    ->whereNotNull('user_id')
                    ->where('last_activity', '>=', now()->subMinutes(5)->timestamp)
                    ->pluck('user_id')
                    ->unique())
                ->get(),
            'team' => $conversation?->group_id
                ? Group::query()
                    ->where('id', $conversation->group_id)
                    ->with(['users' => fn ($q) => $q->select($select)])
                    ->first()
                    ?->users
                    ?? collect()
                : collect(),
            default => collect(),
        };
    }

    /**
     * Reemplaza handles en el body por una versión "limpia" para enviar al canal externo
     * (WhatsApp/Email/etc.) — el cliente no debería ver @username del equipo interno.
     *
     * Estrategia: sustituir `@carmen.lopez` por el nombre real del agente (si es team, por el nombre del equipo).
     */
    public function stripForExternal(string $body): string
    {
        $handles = $this->extractHandles($body);
        if (empty($handles)) {
            return $body;
        }

        $resolved = $this->resolve($body, null);

        $replacements = [];
        foreach ($resolved['users'] as $u) {
            $name = trim(($u->firstname ?? '').' '.($u->lastname ?? '')) ?: $u->email;
            $handle = mb_strtolower(trim(($u->firstname ?? '').'.'.($u->lastname ?? ''), '.'));
            $replacements['@'.$handle] = $name;
        }
        foreach ($resolved['teams'] as $g) {
            $replacements['@'.$g->key] = $g->name;
        }

        if (empty($replacements)) {
            return $body;
        }

        // Replace longest first to avoid partial overlap
        uksort($replacements, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));

        return strtr($body, $replacements);
    }
}
