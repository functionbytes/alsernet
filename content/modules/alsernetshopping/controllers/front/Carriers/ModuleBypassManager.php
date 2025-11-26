<?php

namespace AlsernetShopping\Carriers;

use Hook;
use Module;
use Configuration;

/**
 * Gestor de bypass para módulos externos
 * Evita ejecución duplicada desactivando hooks específicos
 *
 * @package AlsernetShopping\Carriers
 * @version 1.0.0
 * @since 2025-08-16
 */
class ModuleBypassManager
{
    private static $bypassedModules = [];
    private static $originalHooks = [];

    /**
     * Desactiva temporalmente hooks de un módulo externo
     */
    public static function bypassModuleHooks(string $moduleName, array $hooks = []): bool
    {
        try {
            if (!Module::isEnabled($moduleName)) {
                return false;
            }

            // Hooks por defecto a desactivar
            $defaultHooks = [
                'displayCarrierExtraContent',
                'displayOrderConfirmation',
                'displayBeforeCarrier',
                'displayAfterCarrier',
                'actionCarrierUpdate',
                'displayCarrierList'
            ];

            $hooksToBypass = empty($hooks) ? $defaultHooks : $hooks;

            foreach ($hooksToBypass as $hookName) {
                // Guardar estado original
                $moduleHooks = Hook::getModulesFromHook($hookName);

                foreach ($moduleHooks as $moduleHook) {
                    if ($moduleHook['name'] === $moduleName) {
                        self::$originalHooks[$moduleName][$hookName] = $moduleHook;

                        // Desactivar hook temporalmente
                        $sql = 'UPDATE `' . _DB_PREFIX_ . 'hook_module` 
                                SET `active` = 0 
                                WHERE `id_module` = ' . (int)$moduleHook['id_module'] . ' 
                                AND `id_hook` = ' . (int)$moduleHook['id_hook'];

                        \Db::getInstance()->execute($sql);
                    }
                }
            }

            self::$bypassedModules[$moduleName] = $hooksToBypass;

            // Limpiar cache de hooks
            \Cache::clean('hook_module_list');

            return true;

        } catch (\Exception $e) {
            error_log("ModuleBypassManager: Error bypassing {$moduleName} - " . $e->getMessage());
            return false;
        }
    }

    /**
     * Restaura hooks de un módulo
     */
    public static function restoreModuleHooks(string $moduleName): bool
    {
        try {
            if (!isset(self::$bypassedModules[$moduleName])) {
                return true; // No estaba bypassed
            }

            $originalHooks = self::$originalHooks[$moduleName] ?? [];

            foreach ($originalHooks as $hookName => $hookData) {
                $sql = 'UPDATE `' . _DB_PREFIX_ . 'hook_module` 
                        SET `active` = 1 
                        WHERE `id_module` = ' . (int)$hookData['id_module'] . ' 
                        AND `id_hook` = ' . (int)$hookData['id_hook'];

                \Db::getInstance()->execute($sql);
            }

            unset(self::$bypassedModules[$moduleName]);
            unset(self::$originalHooks[$moduleName]);

            // Limpiar cache
            \Cache::clean('hook_module_list');

            return true;

        } catch (\Exception $e) {
            error_log("ModuleBypassManager: Error restoring {$moduleName} - " . $e->getMessage());
            return false;
        }
    }

    /**
     * Bypass condicional - solo si alsernetshopping toma control
     */
    public static function conditionalBypass(string $moduleName, int $carrierId): bool
    {
        // Solo bypass si nuestro handler está habilitado para este carrier
        $registry = CarrierRegistry::getInstance();
        $handler = $registry->getHandler($carrierId);

        if ($handler && $handler->isEnabled()) {
            return self::bypassModuleHooks($moduleName);
        }

        return false;
    }

    /**
     * Restaura todos los módulos bypassed
     */
    public static function restoreAllModules(): void
    {
        foreach (array_keys(self::$bypassedModules) as $moduleName) {
            self::restoreModuleHooks($moduleName);
        }
    }

    /**
     * Obtiene estado de bypass
     */
    public static function getBypassStatus(): array
    {
        return [
            'bypassed_modules' => self::$bypassedModules,
            'original_hooks' => self::$originalHooks,
            'active_bypasses' => count(self::$bypassedModules)
        ];
    }
}