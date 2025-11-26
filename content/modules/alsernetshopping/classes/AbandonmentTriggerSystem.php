<?php

namespace AlsernetShopping;

use Context;
use Tools;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Sistema de triggers inteligentes para carritos abandonados
 * 
 * Maneja la lógica de cuándo, cómo y qué tipo de modal mostrar
 * basado en el comportamiento del usuario y contexto de la sesión
 */
class AbandonmentTriggerSystem
{
    /** @var AbandonedCartManager */
    private $cartManager;
    
    /** @var Context */
    private $context;
    
    /** @var array */
    private $config;
    
    /** @var array */
    private $triggerRules = [];
    
    public function __construct(AbandonedCartManager $cartManager, Context $context = null)
    {
        $this->cartManager = $cartManager;
        $this->context = $context ?: Context::getContext();
        $this->loadTriggerRules();
    }
    
    /**
     * Cargar reglas de triggers desde configuración
     */
    private function loadTriggerRules(): void
    {
        $this->triggerRules = [
            AbandonedCartManager::TRIGGER_EXIT => [
                'priority' => 1,
                'conditions' => [
                    'mouse_velocity' => ['operator' => '>', 'value' => 500],
                    'mouse_direction' => ['operator' => '=', 'value' => 'upward'],
                    'min_time_on_page' => ['operator' => '>', 'value' => 10]
                ],
                'cooldown' => 300, // 5 minutos entre triggers del mismo tipo
                'max_per_session' => 1
            ],
            
            AbandonedCartManager::TRIGGER_TIME => [
                'priority' => 2,
                'conditions' => [
                    'inactivity_duration' => ['operator' => '>', 'value' => 30],
                    'products_in_cart' => ['operator' => '>', 'value' => 0],
                    'not_on_checkout' => true
                ],
                'intervals' => [15, 30, 60, 1440], // minutos
                'max_per_day' => 5
            ],
            
            AbandonedCartManager::TRIGGER_SCROLL => [
                'priority' => 3,
                'conditions' => [
                    'scroll_percentage' => ['operator' => '>', 'value' => 80],
                    'time_on_page' => ['operator' => '>', 'value' => 60],
                    'page_type' => ['operator' => 'in', 'value' => ['product', 'category', 'cart']]
                ],
                'cooldown' => 600, // 10 minutos
                'max_per_session' => 2
            ],
            
            AbandonedCartManager::TRIGGER_BEHAVIOR => [
                'priority' => 4,
                'conditions' => [
                    'price_comparison_actions' => ['operator' => '>', 'value' => 3],
                    'product_removal_from_cart' => ['operator' => '>', 'value' => 0],
                    'hesitation_indicators' => ['operator' => '>', 'value' => 2]
                ],
                'immediate' => true,
                'max_per_session' => 1
            ]
        ];
    }
    
    /**
     * Evaluar si se debe disparar un trigger
     */
    public function evaluateTriggers(int $abandonmentId, array $behaviorData): array
    {
        $results = [];
        
        foreach ($this->triggerRules as $triggerType => $rules) {
            // Verificar si este trigger está habilitado en la configuración
            if (!$this->cartManager->isTriggerEnabled($triggerType)) {
                continue; // Saltar trigger deshabilitado
            }
            
            $evaluation = $this->evaluateTrigger($triggerType, $rules, $abandonmentId, $behaviorData);
            
            if ($evaluation['should_trigger']) {
                $results[] = [
                    'type' => $triggerType,
                    'priority' => $rules['priority'],
                    'delay' => $evaluation['delay'],
                    'conditions_met' => $evaluation['conditions_met'],
                    'modal_config' => $this->cartManager->determineOptimalModal($abandonmentId)
                ];
            }
        }
        
        // Ordenar por prioridad (menor número = mayor prioridad)
        usort($results, function($a, $b) {
            return $a['priority'] <=> $b['priority'];
        });
        
        return $results;
    }
    
    /**
     * Evaluar un trigger específico
     */
    private function evaluateTrigger(string $triggerType, array $rules, int $abandonmentId, array $behaviorData): array
    {
        $result = [
            'should_trigger' => false,
            'delay' => 0,
            'conditions_met' => [],
            'reason' => ''
        ];
        
        // Verificar límites de frecuencia
        if (!$this->checkFrequencyLimits($triggerType, $rules, $abandonmentId)) {
            $result['reason'] = 'frequency_limit_exceeded';
            return $result;
        }
        
        // Evaluar condiciones según el tipo de trigger
        switch ($triggerType) {
            case AbandonedCartManager::TRIGGER_EXIT:
                return $this->evaluateExitIntentTrigger($rules, $behaviorData);
                
            case AbandonedCartManager::TRIGGER_TIME:
                return $this->evaluateTimeTrigger($rules, $abandonmentId, $behaviorData);
                
            case AbandonedCartManager::TRIGGER_SCROLL:
                return $this->evaluateScrollTrigger($rules, $behaviorData);
                
            case AbandonedCartManager::TRIGGER_BEHAVIOR:
                return $this->evaluateBehaviorTrigger($rules, $behaviorData);
                
            default:
                $result['reason'] = 'unknown_trigger_type';
                return $result;
        }
    }
    
    /**
     * Evaluar trigger de intención de salida
     */
    private function evaluateExitIntentTrigger(array $rules, array $behaviorData): array
    {
        $result = [
            'should_trigger' => false,
            'delay' => 2, // 2 segundos de delay para suavizar la experiencia
            'conditions_met' => []
        ];
        
        $conditions = $rules['conditions'];
        $conditionsMet = 0;
        
        // Verificar velocidad del mouse (simulado - en JS real)
        if (isset($behaviorData['mouse_velocity']) && 
            $behaviorData['mouse_velocity'] > $conditions['mouse_velocity']['value']) {
            $result['conditions_met'][] = 'mouse_velocity';
            $conditionsMet++;
        }
        
        // Verificar dirección del mouse
        if (isset($behaviorData['mouse_direction']) && 
            $behaviorData['mouse_direction'] === $conditions['mouse_direction']['value']) {
            $result['conditions_met'][] = 'mouse_direction';
            $conditionsMet++;
        }
        
        // Verificar tiempo mínimo en página
        $timeOnPage = $behaviorData['time_on_current_page'] ?? 0;
        if ($timeOnPage > $conditions['min_time_on_page']['value']) {
            $result['conditions_met'][] = 'min_time_on_page';
            $conditionsMet++;
        }
        
        // Verificar que hay productos en carrito
        if (($behaviorData['cart_products_count'] ?? 0) > 0) {
            $result['conditions_met'][] = 'has_products_in_cart';
            $conditionsMet++;
        }
        
        // Se necesitan al menos 3 condiciones para activar
        $result['should_trigger'] = $conditionsMet >= 3;
        
        return $result;
    }
    
    /**
     * Evaluar trigger basado en tiempo
     */
    private function evaluateTimeTrigger(array $rules, int $abandonmentId, array $behaviorData): array
    {
        $result = [
            'should_trigger' => false,
            'delay' => 0,
            'conditions_met' => []
        ];
        
        // Obtener datos del abandono
        $abandonment = $this->getAbandonmentData($abandonmentId);
        if (!$abandonment) {
            return $result;
        }
        
        $conditions = $rules['conditions'];
        $conditionsMet = 0;
        
        // Verificar inactividad
        $inactivityTime = time() - strtotime($abandonment['last_activity']);
        if ($inactivityTime > $conditions['inactivity_duration']['value']) {
            $result['conditions_met'][] = 'inactivity_duration';
            $conditionsMet++;
        }
        
        // Verificar que hay productos en carrito
        if ($abandonment['products_count'] > 0) {
            $result['conditions_met'][] = 'products_in_cart';
            $conditionsMet++;
        }
        
        // Verificar que no está en checkout
        $currentPage = $behaviorData['current_page_type'] ?? '';
        if ($currentPage !== 'checkout') {
            $result['conditions_met'][] = 'not_on_checkout';
            $conditionsMet++;
        }
        
        // Calcular delay basado en el intento
        $attempt = $abandonment['recovery_attempts'];
        $intervals = $rules['intervals'];
        $delayMinutes = $intervals[$attempt] ?? end($intervals);
        
        $result['delay'] = $delayMinutes * 60; // convertir a segundos
        $result['should_trigger'] = $conditionsMet >= 2;
        
        return $result;
    }
    
    /**
     * Evaluar trigger basado en scroll
     */
    private function evaluateScrollTrigger(array $rules, array $behaviorData): array
    {
        $result = [
            'should_trigger' => false,
            'delay' => 3, // 3 segundos para permitir que termine el scroll
            'conditions_met' => []
        ];
        
        $conditions = $rules['conditions'];
        $conditionsMet = 0;
        
        // Verificar porcentaje de scroll
        $scrollPercentage = $behaviorData['scroll_percentage'] ?? 0;
        if ($scrollPercentage > $conditions['scroll_percentage']['value']) {
            $result['conditions_met'][] = 'scroll_percentage';
            $conditionsMet++;
        }
        
        // Verificar tiempo en página
        $timeOnPage = $behaviorData['time_on_current_page'] ?? 0;
        if ($timeOnPage > $conditions['time_on_page']['value']) {
            $result['conditions_met'][] = 'time_on_page';
            $conditionsMet++;
        }
        
        // Verificar tipo de página
        $pageType = $behaviorData['current_page_type'] ?? '';
        if (in_array($pageType, $conditions['page_type']['value'])) {
            $result['conditions_met'][] = 'page_type';
            $conditionsMet++;
        }
        
        // Verificar que tiene productos en carrito
        if (($behaviorData['cart_products_count'] ?? 0) > 0) {
            $result['conditions_met'][] = 'has_products_in_cart';
            $conditionsMet++;
        }
        
        $result['should_trigger'] = $conditionsMet >= 3;
        
        return $result;
    }
    
    /**
     * Evaluar trigger basado en comportamiento
     */
    private function evaluateBehaviorTrigger(array $rules, array $behaviorData): array
    {
        $result = [
            'should_trigger' => false,
            'delay' => 0, // Inmediato
            'conditions_met' => []
        ];
        
        $conditions = $rules['conditions'];
        $conditionsMet = 0;
        
        // Verificar acciones de comparación de precios
        $priceComparisons = $behaviorData['price_comparison_actions'] ?? 0;
        if ($priceComparisons > $conditions['price_comparison_actions']['value']) {
            $result['conditions_met'][] = 'price_comparison_actions';
            $conditionsMet++;
        }
        
        // Verificar productos removidos del carrito
        $productsRemoved = $behaviorData['products_removed_count'] ?? 0;
        if ($productsRemoved > $conditions['product_removal_from_cart']['value']) {
            $result['conditions_met'][] = 'product_removal_from_cart';
            $conditionsMet++;
        }
        
        // Verificar indicadores de vacilación
        $hesitationIndicators = $this->calculateHesitationScore($behaviorData);
        if ($hesitationIndicators > $conditions['hesitation_indicators']['value']) {
            $result['conditions_met'][] = 'hesitation_indicators';
            $conditionsMet++;
        }
        
        $result['should_trigger'] = $conditionsMet >= 2;
        
        return $result;
    }
    
    /**
     * Calcular puntuación de vacilación basada en comportamiento
     */
    private function calculateHesitationScore(array $behaviorData): int
    {
        $score = 0;
        
        // Múltiples visitas a la página del carrito sin comprar
        $cartVisits = $behaviorData['cart_page_visits'] ?? 0;
        if ($cartVisits > 3) $score++;
        
        // Tiempo excesivo en páginas de producto
        $avgTimeOnProduct = $behaviorData['avg_time_on_product_pages'] ?? 0;
        if ($avgTimeOnProduct > 180) $score++; // 3 minutos
        
        // Múltiples cambios de cantidad en carrito
        $quantityChanges = $behaviorData['quantity_changes'] ?? 0;
        if ($quantityChanges > 2) $score++;
        
        // Búsquedas relacionadas con precios o descuentos
        $priceSearches = $behaviorData['price_related_searches'] ?? 0;
        if ($priceSearches > 0) $score++;
        
        // Visitas a páginas de productos similares
        $similarProductViews = $behaviorData['similar_product_views'] ?? 0;
        if ($similarProductViews > 3) $score++;
        
        // Abandonó checkout y regresó al carrito
        if ($behaviorData['checkout_abandonment'] ?? false) $score += 2;
        
        return $score;
    }
    
    /**
     * Verificar límites de frecuencia para evitar spam
     */
    private function checkFrequencyLimits(string $triggerType, array $rules, int $abandonmentId): bool
    {
        // Verificar cooldown
        if (isset($rules['cooldown'])) {
            $lastTrigger = $this->getLastTriggerTime($triggerType, $abandonmentId);
            if ($lastTrigger && (time() - $lastTrigger) < $rules['cooldown']) {
                return false;
            }
        }
        
        // Verificar límite por sesión
        if (isset($rules['max_per_session'])) {
            $sessionCount = $this->getTriggerCountInSession($triggerType, $abandonmentId);
            if ($sessionCount >= $rules['max_per_session']) {
                return false;
            }
        }
        
        // Verificar límite por día
        if (isset($rules['max_per_day'])) {
            $dailyCount = $this->getTriggerCountToday($triggerType, $abandonmentId);
            if ($dailyCount >= $rules['max_per_day']) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Programar trigger para ejecución futura
     */
    public function scheduleTrigger(int $abandonmentId, string $triggerType, int $delay): bool
    {
        // En una implementación real, esto podría usar un sistema de colas como Redis
        // Por ahora, guardamos en base de datos para procesamiento posterior
        
        $scheduleData = [
            'id_abandoned_cart' => $abandonmentId,
            'trigger_type' => $triggerType,
            'scheduled_time' => date('Y-m-d H:i:s', time() + $delay),
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        // Crear tabla de triggers programados si no existe
        $this->ensureScheduledTriggersTable();
        
        return \Db::getInstance()->insert(_DB_PREFIX_ . _DB_PREFIX_ . 'alsernetshopping_scheduled_triggers', $scheduleData);
    }
    
    /**
     * Procesar triggers programados (para ejecutar via cron)
     */
    public function processScheduledTriggers(): array
    {
        $sql = new \DbQuery();
        $sql->select('*')
            ->from(_DB_PREFIX_ . 'alsernetshopping_scheduled_triggers')
            ->where('status = "pending"')
            ->where('scheduled_time <= NOW()')
            ->orderBy('scheduled_time ASC')
            ->limit(50); // Procesar máximo 50 por ejecución
        
        $triggers = \Db::getInstance()->executeS($sql);
        $processed = [];
        
        foreach ($triggers as $trigger) {
            $result = $this->executeTrigger($trigger);
            $processed[] = [
                'id' => $trigger['id'],
                'abandonment_id' => $trigger['id_abandoned_cart'],
                'trigger_type' => $trigger['trigger_type'],
                'result' => $result
            ];
            
            // Marcar como procesado
            $this->markTriggerAsProcessed($trigger['id'], $result['status']);
        }
        
        return $processed;
    }
    
    /**
     * Ejecutar un trigger específico
     */
    private function executeTrigger(array $triggerData): array
    {
        try {
            $modalConfig = $this->cartManager->determineOptimalModal($triggerData['id_abandoned_cart']);
            
            // Aquí se enviaría la notificación al frontend
            // Esto podría ser via WebSocket, Server-Sent Events, o almacenado para próxima visita
            
            $result = [
                'status' => 'executed',
                'modal_type' => $modalConfig['type'],
                'trigger_type' => $triggerData['trigger_type'],
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
            // Registrar la interacción
            $this->cartManager->registerModalInteraction(
                $triggerData['id_abandoned_cart'],
                $modalConfig['type'],
                'shown',
                [
                    'trigger' => $triggerData['trigger_type'],
                    'variant' => $modalConfig['variant'],
                    'discount' => $modalConfig['discount']
                ]
            );
            
            return $result;
            
        } catch (\Exception $e) {
            error_log('AbandonmentTriggerSystem Error: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }
    
    // Métodos auxiliares privados
    
    private function getAbandonmentData(int $abandonmentId): ?array
    {
        $sql = new \DbQuery();
        $sql->select('*')
            ->from(_DB_PREFIX_ . 'alsernetshopping_abandoned_carts')
            ->where('id_abandoned_cart = ' . (int)$abandonmentId);
        
        return \Db::getInstance()->getRow($sql) ?: null;
    }
    
    private function getLastTriggerTime(string $triggerType, int $abandonmentId): ?int
    {
        $sql = new \DbQuery();
        $sql->select('UNIX_TIMESTAMP(trigger_timestamp) as timestamp')
            ->from(_DB_PREFIX_ . 'alsernetshopping_modal_interactions')
            ->where('id_abandoned_cart = ' . (int)$abandonmentId)
            ->where('trigger_type = "' . pSQL($triggerType) . '"')
            ->orderBy('trigger_timestamp DESC')
            ->limit(1);
        
        $result = \Db::getInstance()->getValue($sql);
        return $result ? (int)$result : null;
    }
    
    private function getTriggerCountInSession(string $triggerType, int $abandonmentId): int
    {
        $sessionId = session_id();
        
        $sql = new \DbQuery();
        $sql->select('COUNT(*)')
            ->from(_DB_PREFIX_ . 'alsernetshopping_modal_interactions mi')
            ->innerJoin(_DB_PREFIX_ . 'alsernetshopping_abandoned_carts ac ON (mi.id_abandoned_cart = ac.id_abandoned_cart)')
            ->where('ac.session_id = "' . pSQL($sessionId) . '"')
            ->where('mi.trigger_type = "' . pSQL($triggerType) . '"')
            ->where('mi.interaction_type = "shown"');
        
        return (int)\Db::getInstance()->getValue($sql);
    }
    
    private function getTriggerCountToday(string $triggerType, int $abandonmentId): int
    {
        $sql = new \DbQuery();
        $sql->select('COUNT(*)')
            ->from(_DB_PREFIX_ . 'alsernetshopping_modal_interactions')
            ->where('id_abandoned_cart = ' . (int)$abandonmentId)
            ->where('trigger_type = "' . pSQL($triggerType) . '"')
            ->where('interaction_type = "shown"')
            ->where('DATE(trigger_timestamp) = CURDATE()');
        
        return (int)\Db::getInstance()->getValue($sql);
    }
    
    private function ensureScheduledTriggersTable(): void
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'alsernetshopping_scheduled_triggers` (
            `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `id_abandoned_cart` int(11) unsigned NOT NULL,
            `trigger_type` varchar(50) NOT NULL,
            `scheduled_time` datetime NOT NULL,
            `status` enum("pending","executed","failed") NOT NULL DEFAULT "pending",
            `created_at` datetime NOT NULL,
            `executed_at` datetime DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `idx_scheduled_time` (`scheduled_time`),
            KEY `idx_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
        
        \Db::getInstance()->execute($sql);
    }
    
    private function markTriggerAsProcessed(int $triggerId, string $status): bool
    {
        return \Db::getInstance()->update(
            _DB_PREFIX_ . 'alsernetshopping_scheduled_triggers',
            [
                'status' => $status,
                'executed_at' => date('Y-m-d H:i:s')
            ],
            'id = ' . (int)$triggerId
        );
    }
}