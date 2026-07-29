<?php

namespace AlsernetShopping\Carriers;

use Context;
use Configuration;

class CarrierRegistry
{
    private static $instance = null;
    private $handlers = [];
    private $cache = [];
    private $cacheEnabled = true;

    private function __construct()
    {
        $this->cacheEnabled = (bool)Configuration::get('ALSERNET_CARRIER_CACHE', true);
        $this->loadDefaultHandlers();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function registerHandler(CarrierHandlerInterface $handler): bool
    {
        try {
            $carrierId = $handler->getId();

            if (isset($this->handlers[$carrierId])) {
                error_log("CarrierRegistry: Handler for carrier ID {$carrierId} already registered, overwriting");
            }

            $this->handlers[$carrierId] = $handler;

            // Limpiar cache para este carrier
            if (isset($this->cache[$carrierId])) {
                unset($this->cache[$carrierId]);
            }

            return true;
        } catch (\Exception $e) {
            error_log("CarrierRegistry: Error registering handler - " . $e->getMessage());
            return false;
        }
    }

    public function getHandler(int $carrierId): ?CarrierHandlerInterface
    {
        // error_log("CarrierRegistry: Looking for handler for carrier {$carrierId}");
        // error_log("CarrierRegistry: Available handlers: " . implode(', ', array_keys($this->handlers)));

        $startTime = microtime(true);
        $fromCache = false;

        // Verificar cache primero
        if ($this->cacheEnabled && isset($this->cache[$carrierId])) {
            $handler = $this->cache[$carrierId];
            $fromCache = true;
            // error_log("CarrierRegistry: Handler for carrier {$carrierId} found in CACHE");
        } else {
            $handler = $this->handlers[$carrierId] ?? null;
            // error_log("CarrierRegistry: Handler for carrier {$carrierId} " . ($handler ? 'FOUND' : 'NOT FOUND'));

            // Guardar en cache si existe
            if ($handler && $this->cacheEnabled) {
                $this->cache[$carrierId] = $handler;
            }
        }

        // Registrar metricas si el monitor esta disponible
        if (class_exists('\AlsernetShopping\Carriers\CarrierPerformanceMonitor')) {
            try {
                $monitor = \AlsernetShopping\Carriers\CarrierPerformanceMonitor::getInstance();
                $executionTime = microtime(true) - $startTime;

                if ($fromCache) {
                    $monitor->recordMetric('cache_hit', []);
                } else {
                    $monitor->recordMetric('cache_miss', []);
                }

                if ($handler) {
                    $monitor->recordCarrierLoad($carrierId, $executionTime, $fromCache);
                }
            } catch (\Exception $e) {
                // Silenciosamente continuar si el monitor falla
                error_log("CarrierRegistry: Monitor error - " . $e->getMessage());
            }
        }

        return $handler;
    }

    public function getAllHandlers(): array
    {
        return $this->handlers;
    }

    public function getActiveHandlers(): array
    {
        return array_filter($this->handlers, function(CarrierHandlerInterface $handler) {
            return $handler->isEnabled();
        });
    }

    public function hasHandler(int $carrierId): bool
    {
        return isset($this->handlers[$carrierId]);
    }

    public function unregisterHandler(int $carrierId): bool
    {
        if (isset($this->handlers[$carrierId])) {
            // Ejecutar cleanup del handler
            $this->handlers[$carrierId]->cleanup();

            // Remover del registry y cache
            unset($this->handlers[$carrierId]);
            unset($this->cache[$carrierId]);

            return true;
        }
        return false;
    }

    public function getStats(): array
    {
        $active = 0;
        $inactive = 0;

        foreach ($this->handlers as $handler) {
            if ($handler->isEnabled()) {
                $active++;
            } else {
                $inactive++;
            }
        }

        return [
            'total_handlers' => count($this->handlers),
            'active_handlers' => $active,
            'inactive_handlers' => $inactive,
            'cache_enabled' => $this->cacheEnabled,
            'cache_entries' => count($this->cache),
            'registered_carriers' => array_keys($this->handlers)
        ];
    }

    public function clearCache(): void
    {
        $this->cache = [];
    }

    public function cleanupAll(): void
    {
        foreach ($this->handlers as $handler) {
            try {
                $handler->cleanup();
            } catch (\Exception $e) {
                error_log("CarrierRegistry: Error during cleanup - " . $e->getMessage());
            }
        }
    }

    // Carrier type configuration
    private const STANDARD_CARRIERS = [98, 100, 107, 108, 109, 110, 111]; // All go to setdelivery but only hide/show content
    private const CUSTOM_CARRIERS = [39, 101, 78, 66]; // Process dynamic HTML from server
    private const SKIP_SETDELIVERY = []; // Currently no carriers skip setdelivery

    private function loadDefaultHandlers(): void
    {
        // Load handlers here if needed
    }

    public function getCarrierType(int $carrierId): string
    {
        if (in_array($carrierId, self::CUSTOM_CARRIERS)) {
            return 'custom';
        } elseif (in_array($carrierId, self::STANDARD_CARRIERS)) {
            return 'standard';
        }
        return 'unknown';
    }

    public function needsSetdelivery(int $carrierId): bool
    {
        return !in_array($carrierId, self::SKIP_SETDELIVERY);
    }

    public function getCarrierConfig(): array
    {
        return [
            'standard' => self::STANDARD_CARRIERS,
            'custom' => self::CUSTOM_CARRIERS,
            'skip_setdelivery' => self::SKIP_SETDELIVERY
        ];
    }

    public function validateCarrierAvailability(int $carrierId, Context $context = null): array
    {
        $context = $context ?: Context::getContext();
        $handler = $this->getHandler($carrierId);

        if (!$handler) {
            return [
                'available' => false,
                'handler' => null,
                'message' => "No handler found for carrier ID {$carrierId}"
            ];
        }

        $validation = $handler->validateAvailability($context);

        return [
            'available' => $validation['valid'],
            'handler' => $handler,
            'message' => $validation['message']
        ];
    }

    private function __clone() {}

    public function __wakeup()
    {
        throw new \Exception("Cannot unserialize singleton");
    }
}