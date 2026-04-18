<?php

namespace Modules\Theme\Services;

use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Collection;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

/**
 * NavService - Servicio centralizado para gestionar la navegación del panel administrativo
 *
 * Permite que cada módulo registre dinámicamente sus items de navegación
 * sin necesidad de modificar vistas o configuraciones centrales.
 */
class NavService
{
    /**
     * Almacena todos los items de menú registrados
     */
    private static array $menus = [];

    /**
     * Registrar un item en el mini-nav (navegación lateral izquierda con iconos)
     */
    public static function registerMiniItem(string $moduleId, array $config): void
    {
        if (! isset(self::$menus['mini'])) {
            self::$menus['mini'] = [];
        }

        // Validar configuración requerida
        $required = ['icon', 'tooltip', 'sidebar_id'];
        foreach ($required as $field) {
            if (! isset($config[$field])) {
                throw new \InvalidArgumentException("Missing required field '{$field}' in mini-nav item '{$moduleId}'");
            }
        }

        // Asignar valores por defecto
        $config['id'] = $moduleId;
        $config['order'] = $config['order'] ?? 999;

        self::$menus['mini'][$moduleId] = $config;
    }

    /**
     * Registrar items en un sidebar (menú desplegable)
     * Si el sidebar ya existe, agrega una nueva sección con su propio título
     */
    public static function registerSidebar(string $sidebarId, array $config): void
    {
        if (! isset(self::$menus['sidebar'])) {
            self::$menus['sidebar'] = [];
        }

        // Validar configuración requerida
        if (! isset($config['title']) || ! isset($config['items'])) {
            throw new \InvalidArgumentException("Sidebar '{$sidebarId}' must have 'title' and 'items'");
        }

        // Si el sidebar ya existe, agregar una nueva sección en lugar de sobrescribir
        if (isset(self::$menus['sidebar'][$sidebarId])) {
            // Inicializar 'sections' si no existe
            if (! isset(self::$menus['sidebar'][$sidebarId]['sections'])) {
                // Si hay items legacy, convertir a sección
                $legacyItems = self::$menus['sidebar'][$sidebarId]['items'] ?? [];
                $legacyTitle = self::$menus['sidebar'][$sidebarId]['title'] ?? 'Menu';

                self::$menus['sidebar'][$sidebarId]['sections'] = [];

                if (! empty($legacyItems)) {
                    self::$menus['sidebar'][$sidebarId]['sections'][] = [
                        'title' => $legacyTitle,
                        'items' => $legacyItems,
                    ];
                }

                // Limpiar items y title legacy si existen
                if (isset(self::$menus['sidebar'][$sidebarId]['items'])) {
                    unset(self::$menus['sidebar'][$sidebarId]['items']);
                }
                if (isset(self::$menus['sidebar'][$sidebarId]['title'])) {
                    unset(self::$menus['sidebar'][$sidebarId]['title']);
                }
            }

            // Buscar sección con el mismo título para fusionar
            $merged = false;
            foreach (self::$menus['sidebar'][$sidebarId]['sections'] as &$section) {
                if ($section['title'] === $config['title']) {
                    $section['items'] = array_merge($section['items'], $config['items']);
                    $merged = true;
                    break;
                }
            }
            unset($section);

            // Si no existe sección con ese título, agregar una nueva
            if (! $merged) {
                self::$menus['sidebar'][$sidebarId]['sections'][] = [
                    'title' => $config['title'],
                    'items' => $config['items'],
                ];
            }
        } else {
            // Crear sidebar nuevo con estructura de secciones
            self::$menus['sidebar'][$sidebarId] = [
                'sections' => [
                    [
                        'title' => $config['title'],
                        'items' => $config['items'],
                    ],
                ],
            ];
        }
    }

    /**
     * Agregar items a un sidebar existente
     * Agrega items a la última sección. Útil cuando múltiples módulos quieren contribuir items al mismo sidebar
     */
    public static function addSidebarItems(string $sidebarId, array $items): void
    {
        if (! isset(self::$menus['sidebar'])) {
            self::$menus['sidebar'] = [];
        }

        if (! isset(self::$menus['sidebar'][$sidebarId])) {
            throw new \InvalidArgumentException("Sidebar '{$sidebarId}' does not exist. Register it first with registerSidebar().");
        }

        // Si usa estructura de secciones, agregar a la última sección
        if (isset(self::$menus['sidebar'][$sidebarId]['sections'])) {
            $lastSectionIndex = count(self::$menus['sidebar'][$sidebarId]['sections']) - 1;
            if ($lastSectionIndex >= 0) {
                self::$menus['sidebar'][$sidebarId]['sections'][$lastSectionIndex]['items'] = array_merge(
                    self::$menus['sidebar'][$sidebarId]['sections'][$lastSectionIndex]['items'],
                    $items
                );
            }
        } else {
            // Estructura legacy
            self::$menus['sidebar'][$sidebarId]['items'] = array_merge(
                self::$menus['sidebar'][$sidebarId]['items'] ?? [],
                $items
            );
        }
    }

    /**
     * Agregar items a una sección específica de un sidebar, buscando por título.
     *
     * Si el sidebar no existe, lo crea. Si la sección con ese título no existe, la crea.
     * Si ya existe una sección con ese título, fusiona los items en ella.
     *
     * Uso recomendado cuando un módulo quiere aportar items a una sección ya definida
     * por otro módulo, sin depender del orden de carga de los ServiceProviders.
     *
     * Ejemplo:
     *   NavService::addItemsToSection('settings', 'Configuraciones', [
     *       ['label' => 'Almacenamiento', 'route' => 'settings.storage'],
     *   ]);
     */
    public static function addItemsToSection(string $sidebarId, string $sectionTitle, array $items): void
    {
        if (! isset(self::$menus['sidebar'])) {
            self::$menus['sidebar'] = [];
        }

        // Si el sidebar no existe, crearlo con la sección directamente
        if (! isset(self::$menus['sidebar'][$sidebarId])) {
            self::$menus['sidebar'][$sidebarId] = [
                'sections' => [
                    ['title' => $sectionTitle, 'items' => $items],
                ],
            ];

            return;
        }

        $sidebar = &self::$menus['sidebar'][$sidebarId];

        // Normalizar a estructura de secciones si está en formato legacy
        if (! isset($sidebar['sections'])) {
            $legacyItems = $sidebar['items'] ?? [];
            $legacyTitle = $sidebar['title'] ?? 'Menu';
            $sidebar['sections'] = [['title' => $legacyTitle, 'items' => $legacyItems]];
            unset($sidebar['items'], $sidebar['title']);
        }

        // Buscar una sección existente con el mismo título
        foreach ($sidebar['sections'] as &$section) {
            if ($section['title'] === $sectionTitle) {
                $section['items'] = array_merge($section['items'], $items);

                return;
            }
        }

        // No se encontró la sección: crear una nueva
        $sidebar['sections'][] = ['title' => $sectionTitle, 'items' => $items];
    }

    /**
     * Obtener todos los items del mini-nav ordenados
     */
    public static function getMiniItems(): Collection
    {
        $items = collect(self::$menus['mini'] ?? [])
            ->sortBy('order')
            ->values();

        return $items;
    }

    /**
     * Obtener un item específico del mini-nav
     */
    public static function getMiniItem(string $moduleId): ?array
    {
        return self::$menus['mini'][$moduleId] ?? null;
    }

    /**
     * Obtener un sidebar específico
     */
    public static function getSidebar(string $sidebarId): ?array
    {
        return self::$menus['sidebar'][$sidebarId] ?? null;
    }

    /**
     * Obtener todos los sidebars registrados
     */
    public static function getAllSidebars(): array
    {
        return self::$menus['sidebar'] ?? [];
    }

    /**
     * Verificar si un módulo está registrado
     */
    public static function hasMiniItem(string $moduleId): bool
    {
        return isset(self::$menus['mini'][$moduleId]);
    }

    /**
     * Verificar si un sidebar está registrado
     */
    public static function hasSidebar(string $sidebarId): bool
    {
        return isset(self::$menus['sidebar'][$sidebarId]);
    }

    /**
     * Obtener toda la estructura de menús (debugging)
     */
    public static function getAll(): array
    {
        return self::$menus;
    }

    /**
     * Reset all static navigation state (useful for testing and module reloading)
     */
    public static function flush(): void
    {
        self::$menus = [];
    }

    /**
     * Obtener mini-items que el usuario autenticado puede ver
     * Filtra por permisos de módulos (modules.view.{moduleId})
     */
    public static function getMiniItemsForUser(): Collection
    {
        $user = auth()->user();

        if (! $user) {
            return collect([]);
        }

        return self::getMiniItems()->filter(function ($item) use ($user) {
            $moduleId = $item['id'] ?? null;
            if (! $moduleId) {
                return true; // Si no tiene ID, mostrar
            }

            $permissionName = "modules.view.{$moduleId}";

            // Si el usuario es super-settings, mostrar siempre
            if ($user->hasRole('super-settings')) {
                return true;
            }

            // Verificar si el usuario tiene permiso para este módulo
            try {
                return $user->hasPermissionTo($permissionName);
            } catch (PermissionDoesNotExist $e) {
                logger()->warning("NavService: permission '{$permissionName}' not found. Run the module seeder.");

                return false;
            }
        });
    }

    /**
     * Obtener sidebars que el usuario autenticado puede ver
     * Filtra por permisos de módulos (modules.view.{moduleId})
     */
    public static function getSidebarsForUser(): array
    {
        $user = auth()->user();

        if (! $user) {
            return [];
        }

        $sidebars = [];

        foreach (self::getAllSidebars() as $sidebarId => $sidebar) {
            $permissionName = "modules.view.{$sidebarId}";

            // Si el usuario es super-settings, mostrar todos
            if ($user->hasRole('super-settings')) {
                $sidebars[$sidebarId] = $sidebar;

                continue;
            }

            // Verificar si el usuario tiene permiso para este módulo
            try {
                if ($user->hasPermissionTo($permissionName)) {
                    $sidebars[$sidebarId] = $sidebar;
                }
            } catch (PermissionDoesNotExist $e) {
                logger()->warning("NavService: permission '{$permissionName}' not found. Run the module seeder.");
            }
        }

        return $sidebars;
    }

    /**
     * Verificar si un usuario puede ver un módulo específico
     */
    public static function userCanAccessModule(string $moduleId, ?User $user = null): bool
    {
        $user ??= auth()->user();

        if (! $user) {
            return false;
        }

        // Super-settings siempre tiene acceso
        if ($user->hasRole('super-settings')) {
            return true;
        }

        $permissionName = "modules.view.{$moduleId}";

        return $user->hasPermissionTo($permissionName);
    }

    /**
     * Obtener datos completos de navegación para renderizar en el template
     *
     * Esto retorna:
     * - miniItems: items del mini-nav filtrados por permisos
     * - sidebars: sidebars filtrados por permisos
     * - activeSidebarId: el ID del sidebar que debería estar activo basándose en la ruta actual
     *
     * Este método centraliza toda la lógica que solía estar en el template Blade,
     * mejorando la claridad y permitiendo reutilización en otros contextos.
     *
     * @return array{miniItems: Collection, sidebars: array, activeSidebarId: string|null}
     */
    public static function getNavDataForUser(): array
    {
        $user = auth()->user();
        $miniItems = self::getMiniItemsForUser();
        $sidebars = self::getSidebarsForUser();

        // Sidebar candidato por ruta (exacto primero, luego prefijo)
        $candidateSidebarId = self::findActiveSidebarForUser($sidebars, $user)
            ?? self::findSidebarByRoutePrefix($sidebars);

        // Ítem activo dentro del sidebar candidato
        $activeItemRoute = $candidateSidebarId
            ? self::findBestMatchingItemRoute($sidebars[$candidateSidebarId] ?? [])
            : null;

        // Panel solo se abre si hay ítem coincidente
        $activeSidebarId = $activeItemRoute ? $candidateSidebarId : null;

        // El ícono mini-nav se resalta con el candidato (aunque no abra panel)
        // Ej: settings.users.index resalta Settings aunque no abra su panel
        $activeMiniId = $candidateSidebarId;

        return [
            'miniItems' => $miniItems,
            'sidebars' => $sidebars,
            'activeSidebarId' => $activeSidebarId,
            'activeMiniId' => $activeMiniId,
            'activeItemRoute' => $activeItemRoute,
        ];
    }

    /**
     * Encontrar el sidebar activo basándose en la ruta actual
     *
     * Busca a través de todos los sidebars y sus items para determinar
     * cuál debería estar activo basándose en la ruta que se está viendo.
     *
     * @param  array  $sidebars  Sidebars filtrados por permisos
     * @param  User|null  $user  Usuario autenticado
     * @return string|null ID del sidebar activo, o null si no se encuentra
     */
    private static function findActiveSidebarForUser(array $sidebars, ?User $user = null): ?string
    {
        if (! $user) {
            return null;
        }

        foreach ($sidebars as $sidebarId => $sidebar) {
            // Si el mini-item correspondiente tiene URL directa, no activar este sidebar
            $miniItem = self::getMiniItem($sidebarId);
            if ($miniItem && ! empty($miniItem['url'])) {
                continue; // Saltar sidebars con URL directa
            }

            // Soportar nueva estructura de secciones
            if (isset($sidebar['sections'])) {
                foreach ($sidebar['sections'] as $section) {
                    foreach ($section['items'] ?? [] as $item) {
                        // Validar que el usuario tenga permisos para este item
                        if (self::userCanAccessItem($item, $user)) {
                            // Verificar si la ruta actual coincide
                            if (request()->routeIs($item['route'].'*')) {
                                return $sidebarId;
                            }
                        }
                    }
                }
            } else {
                // Estructura legacy con items directos
                foreach ($sidebar['items'] ?? [] as $item) {
                    // Validar que el usuario tenga permisos para este item
                    if (self::userCanAccessItem($item, $user)) {
                        // Verificar si la ruta actual coincide
                        if (request()->routeIs($item['route'].'*')) {
                            return $sidebarId;
                        }
                    }
                }
            }
        }

        return null;
    }

    /**
     * Fallback: busca sidebar cuyo prefijo de ruta coincida con el prefijo de la ruta actual.
     * Útil para rutas como `pages.edit` que no están registradas en el nav pero pertenecen
     * al mismo módulo que un sidebar (prefijo `pages`).
     */
    private static function findSidebarByRoutePrefix(array $sidebars): ?string
    {
        $currentRoute = request()->route()?->getName();

        if (! $currentRoute) {
            return null;
        }

        $prefix = explode('.', $currentRoute)[0];

        foreach ($sidebars as $sidebarId => $sidebar) {
            $miniItem = self::getMiniItem($sidebarId);
            if ($miniItem && ! empty($miniItem['url'])) {
                continue;
            }

            $items = isset($sidebar['sections'])
                ? collect($sidebar['sections'])->flatMap(fn ($s) => $s['items'] ?? [])->all()
                : ($sidebar['items'] ?? []);

            foreach ($items as $item) {
                if (isset($item['route']) && str_starts_with($item['route'], $prefix.'.')) {
                    return $sidebarId;
                }
            }
        }

        return null;
    }

    /**
     * Encuentra el ítem del sidebar que mejor coincide con la ruta actual.
     *
     * Solo acepta dos tipos de relación válida:
     *  - Ancestro: todos los segmentos del ítem coinciden al inicio de la ruta actual
     *    (el ítem es padre/abuelo de la ruta). Ej: pages.categories → pages.categories.edit
     *  - Hermano: misma profundidad y todos los segmentos excepto el último coinciden.
     *    Ej: pages.index ↔ pages.edit (ambos bajo "pages.")
     *
     * Esto evita falsos positivos como settings.storage activándose para settings.users.index.
     */
    private static function findBestMatchingItemRoute(array $sidebar): ?string
    {
        $currentRoute = request()->route()?->getName();

        if (! $currentRoute) {
            return null;
        }

        $currentParts = explode('.', $currentRoute);
        $currentDepth = count($currentParts);
        $bestRoute = null;
        $bestMatchDepth = 0;

        $items = isset($sidebar['sections'])
            ? collect($sidebar['sections'])->flatMap(fn ($s) => $s['items'] ?? [])->all()
            : ($sidebar['items'] ?? []);

        foreach ($items as $item) {
            $itemRoute = $item['route'] ?? '';

            if (! $itemRoute) {
                continue;
            }

            // Coincidencia exacta: prioridad máxima
            if (request()->routeIs($itemRoute.'*')) {
                return $itemRoute;
            }

            $itemParts = explode('.', $itemRoute);
            $itemDepth = count($itemParts);

            // Contar segmentos iniciales coincidentes
            $matching = 0;
            foreach ($itemParts as $i => $seg) {
                if (($currentParts[$i] ?? null) === $seg) {
                    $matching++;
                } else {
                    break;
                }
            }

            // Ancestro: todos los segmentos del ítem coinciden y la ruta es más profunda
            $isAncestor = $matching === $itemDepth && $currentDepth > $itemDepth;

            // Hermano: misma profundidad, todos los segmentos excepto el último coinciden
            $isSibling = $currentDepth > 1
                && $itemDepth === $currentDepth
                && $matching === $itemDepth - 1;

            if (! $isAncestor && ! $isSibling) {
                continue;
            }

            // Preferir el match más profundo (más específico)
            if ($matching > $bestMatchDepth) {
                $bestMatchDepth = $matching;
                $bestRoute = $itemRoute;
            }
        }

        return $bestRoute;
    }

    /**
     * Verificar si un usuario puede acceder a un item específico del menú
     *
     * Maneja tanto permisos simples como múltiples (separadas por |)
     *
     * @param  array  $item  Item del menú con opcional campo 'permission'
     * @param  User  $user  Usuario autenticado
     * @return bool True si el usuario puede acceder, false en caso contrario
     */
    public static function userCanAccessItem(array $item, User $user): bool
    {
        // Si no hay requerimiento de permiso, permitir acceso
        if (empty($item['permission'])) {
            return true;
        }

        // Parsear permisos separados por | (OR logic)
        $permissions = array_map('trim', explode('|', $item['permission']));

        // Verificar si el usuario tiene al menos uno de los permisos
        foreach ($permissions as $permission) {
            if ($user->can($permission)) {
                return true;
            }
        }

        return false;
    }
}
