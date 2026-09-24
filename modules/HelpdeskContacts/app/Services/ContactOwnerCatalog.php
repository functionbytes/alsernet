<?php

namespace Modules\HelpdeskContacts\Services;

use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Agentes que se pueden asignar como RESPONSABLE de un contacto.
 *
 * Reutiliza el catálogo que ya usa el CRUD de tickets
 * (CatalogCacheService::agents(): rol helpdesk-agent + disponible, cacheado 60 s)
 * en vez de filtrar por users.verified, que deja fuera a los agentes reales
 * (sus cuentas se crean directamente con verified=0). HelpdeskTickets es un
 * módulo opcional, así que se referencia por nombre y con class_exists(); si no
 * está, se aplica el mismo criterio directamente contra users.
 */
class ContactOwnerCatalog
{
    private const TICKETS_CATALOG = 'Modules\\HelpdeskTickets\\Services\\CatalogCacheService';

    /**
     * @return Collection<int, User>
     */
    public function agents(): Collection
    {
        if (class_exists(self::TICKETS_CATALOG)) {
            return collect(call_user_func([self::TICKETS_CATALOG, 'agents'])->all())->values();
        }

        return User::query()
            ->select(['id', 'firstname', 'lastname', 'email', 'last_login_at'])
            ->whereHas('roles', fn ($q) => $q->where('name', 'helpdesk-agent'))
            ->where('available', true)
            ->orderBy('firstname')
            ->get()
            ->values();
    }

    /**
     * @return array<int, int>
     */
    public function ids(): array
    {
        return $this->agents()->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    /**
     * Forma que consume la UI (desplegables de responsable).
     *
     * @return array<int, array{id: int, name: string}>
     */
    public function options(): array
    {
        return $this->agents()
            ->map(fn (User $u): array => ['id' => (int) $u->id, 'name' => trim((string) $u->full_name) ?: (string) $u->email])
            ->values()
            ->all();
    }

    public function isAssignable(int $userId): bool
    {
        return in_array($userId, $this->ids(), true);
    }
}
