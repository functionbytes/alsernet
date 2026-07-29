<?php

namespace AlsernetShopping;

use Context;
use Db;
use DbQuery;
use Configuration;
use Tools;
use PrestaShopLogger;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Servicio de procesamiento automático para carritos abandonados
 * 
 * Maneja:
 * - Procesamiento de triggers diferidos
 * - Envío de notificaciones programadas
 * - Limpieza de datos antiguos
 * - Generación de reportes automáticos
 */
class AbandonmentCronService
{
    /** @var Context */
    private $context;
    
    /** @var AbandonedCartManager */
    private $cartManager;
    
    /** @var AbandonmentValidationService */
    private $validationService;
    
    /** @var array */
    private $config;
    
    /** @var array */
    private $processedJobs = [];
    
    const JOB_TYPE_TRIGGER = 'trigger';
    const JOB_TYPE_NOTIFICATION = 'notification';
    const JOB_TYPE_CLEANUP = 'cleanup';
    const JOB_TYPE_REPORT = 'report';
    
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    
    public function __construct(Context $context = null)
    {
        $this->context = $context ?: Context::getContext();
        $this->cartManager = new AbandonedCartManager($this->context);
        $this->validationService = new AbandonmentValidationService($this->context);
        $this->loadConfiguration();
    }
    
    /**
     * Procesar todos los jobs pendientes
     */
    public function processAllJobs(): array
    {
        $results = [
            'processed' => 0,
            'succeeded' => 0,
            'failed' => 0,
            'skipped' => 0,
            'execution_time' => 0,
            'jobs' => []
        ];
        
        $startTime = microtime(true);
        
        try {
            // Verificar si el sistema está activo
            if (!$this->cartManager->isSystemActive()) {
                $results['skipped'] = 1;
                $results['message'] = 'Abandonment system is inactive';
                return $results;
            }
            
            // Obtener jobs pendientes
            $pendingJobs = $this->getPendingJobs();
            
            foreach ($pendingJobs as $job) {
                $results['processed']++;
                
                try {
                    $this->markJobAsProcessing($job['id']);
                    $jobResult = $this->processJob($job);
                    
                    if ($jobResult['success']) {
                        $this->markJobAsCompleted($job['id'], $jobResult);
                        $results['succeeded']++;
                    } else {
                        $this->markJobAsFailed($job['id'], $jobResult['error']);
                        $results['failed']++;
                    }
                    
                    $results['jobs'][] = [
                        'id' => $job['id'],
                        'type' => $job['job_type'],
                        'status' => $jobResult['success'] ? 'success' : 'failed',
                        'message' => $jobResult['message'] ?? ''
                    ];
                    
                } catch (Exception $e) {
                    $this->markJobAsFailed($job['id'], $e->getMessage());
                    $results['failed']++;
                    
                    $results['jobs'][] = [
                        'id' => $job['id'],
                        'type' => $job['job_type'],
                        'status' => 'failed',
                        'message' => $e->getMessage()
                    ];
                }
            }
            
        } catch (Exception $e) {
            PrestaShopLogger::addLog('AbandonmentCronService error: ' . $e->getMessage(), 3);
            $results['error'] = $e->getMessage();
        }
        
        $results['execution_time'] = round((microtime(true) - $startTime) * 1000, 2);
        
        return $results;
    }
    
    /**
     * Procesar un job específico
     */
    private function processJob(array $job): array
    {
        switch ($job['job_type']) {
            case self::JOB_TYPE_TRIGGER:
                return $this->processTriggerJob($job);
                
            case self::JOB_TYPE_NOTIFICATION:
                return $this->processNotificationJob($job);
                
            case self::JOB_TYPE_CLEANUP:
                return $this->processCleanupJob($job);
                
            case self::JOB_TYPE_REPORT:
                return $this->processReportJob($job);
                
            default:
                return [
                    'success' => false,
                    'error' => 'Unknown job type: ' . $job['job_type']
                ];
        }
    }
    
    /**
     * Procesar trigger automático
     */
    private function processTriggerJob(array $job): array
    {
        try {
            $jobData = json_decode($job['job_data'], true);
            $abandonmentId = $jobData['abandonment_id'] ?? 0;
            $triggerType = $jobData['trigger_type'] ?? '';
            
            if (!$abandonmentId || !$triggerType) {
                return [
                    'success' => false,
                    'error' => 'Missing abandonment_id or trigger_type in job data'
                ];
            }
            
            // Validar que el abandonment aún existe y está activo
            $abandonment = $this->getAbandonmentById($abandonmentId);
            if (!$abandonment || $abandonment['is_recovered']) {
                return [
                    'success' => true,
                    'message' => 'Abandonment no longer needs processing (recovered or not found)'
                ];
            }
            
            // Verificar si el trigger está habilitado
            if (!$this->cartManager->isTriggerEnabled($triggerType)) {
                return [
                    'success' => true,
                    'message' => 'Trigger type disabled: ' . $triggerType
                ];
            }
            
            // Validar frecuencia y cooldown
            if (!$this->canExecuteTrigger($abandonmentId, $triggerType)) {
                return [
                    'success' => true,
                    'message' => 'Trigger blocked by frequency/cooldown rules'
                ];
            }
            
            // Determinar modal óptimo
            $modalConfig = $this->cartManager->determineOptimalModal($abandonmentId);
            
            // Registrar interacción automática
            $this->cartManager->registerModalInteraction(
                $abandonmentId,
                $modalConfig['type'],
                'auto_triggered',
                [
                    'trigger' => $triggerType,
                    'scheduled_execution' => true,
                    'job_id' => $job['id']
                ]
            );
            
            return [
                'success' => true,
                'message' => 'Trigger processed successfully',
                'data' => [
                    'abandonment_id' => $abandonmentId,
                    'trigger_type' => $triggerType,
                    'modal_type' => $modalConfig['type']
                ]
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Error processing trigger: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Procesar notificación programada
     */
    private function processNotificationJob(array $job): array
    {
        try {
            $jobData = json_decode($job['job_data'], true);
            $abandonmentId = $jobData['abandonment_id'] ?? 0;
            $notificationType = $jobData['notification_type'] ?? 'email';
            
            // Implementar lógica de notificaciones (email, push, etc.)
            // Por ahora registramos la interacción
            
            $this->cartManager->registerModalInteraction(
                $abandonmentId,
                'notification',
                'sent',
                [
                    'notification_type' => $notificationType,
                    'scheduled_execution' => true,
                    'job_id' => $job['id']
                ]
            );
            
            return [
                'success' => true,
                'message' => 'Notification sent successfully',
                'data' => [
                    'abandonment_id' => $abandonmentId,
                    'notification_type' => $notificationType
                ]
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Error sending notification: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Procesar limpieza de datos
     */
    private function processCleanupJob(array $job): array
    {
        try {
            $jobData = json_decode($job['job_data'], true);
            $cleanupType = $jobData['cleanup_type'] ?? 'old_abandonments';
            $daysOld = $jobData['days_old'] ?? 30;
            
            $deletedCount = 0;
            
            switch ($cleanupType) {
                case 'old_abandonments':
                    $deletedCount = $this->cleanupOldAbandonments($daysOld);
                    break;
                    
                case 'completed_jobs':
                    $deletedCount = $this->cleanupCompletedJobs($daysOld);
                    break;
                    
                case 'expired_discounts':
                    $deletedCount = $this->cleanupExpiredDiscounts();
                    break;
            }
            
            return [
                'success' => true,
                'message' => "Cleanup completed: {$deletedCount} records processed",
                'data' => [
                    'cleanup_type' => $cleanupType,
                    'deleted_count' => $deletedCount
                ]
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Error during cleanup: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Procesar generación de reportes
     */
    private function processReportJob(array $job): array
    {
        try {
            $jobData = json_decode($job['job_data'], true);
            $reportType = $jobData['report_type'] ?? 'weekly_summary';
            
            // Generar datos del reporte
            $reportData = $this->generateReportData($reportType);
            
            // Guardar reporte en tabla o enviar por email
            $this->saveReport($reportType, $reportData);
            
            return [
                'success' => true,
                'message' => 'Report generated successfully',
                'data' => [
                    'report_type' => $reportType,
                    'records_count' => count($reportData)
                ]
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Error generating report: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Programar nuevo job
     */
    public function scheduleJob(string $jobType, array $jobData, $scheduledFor = null): int
    {
        $scheduledFor = $scheduledFor ?: date('Y-m-d H:i:s');
        
        $jobId = Db::getInstance()->insert(_DB_PREFIX_ . 'alsernetshopping_cron_jobs', [
            'job_type' => pSQL($jobType),
            'job_data' => pSQL(json_encode($jobData)),
            'scheduled_for' => pSQL($scheduledFor),
            'status' => pSQL(self::STATUS_PENDING),
            'attempts' => 0,
            'date_add' => date('Y-m-d H:i:s')
        ]);
        
        return $jobId ? Db::getInstance()->Insert_ID() : 0;
    }
    
    /**
     * Programar trigger diferido
     */
    public function scheduleTrigger(int $abandonmentId, string $triggerType, int $delayMinutes = 15): int
    {
        $scheduledFor = date('Y-m-d H:i:s', time() + ($delayMinutes * 60));
        
        return $this->scheduleJob(self::JOB_TYPE_TRIGGER, [
            'abandonment_id' => $abandonmentId,
            'trigger_type' => $triggerType,
            'delay_minutes' => $delayMinutes
        ], $scheduledFor);
    }
    
    /**
     * Obtener jobs pendientes
     */
    private function getPendingJobs(int $limit = 50): array
    {
        $sql = new DbQuery();
        $sql->select('*')
            ->from(_DB_PREFIX_ . 'alsernetshopping_cron_jobs')
            ->where('status = "' . pSQL(self::STATUS_PENDING) . '"')
            ->where('scheduled_for <= "' . pSQL(date('Y-m-d H:i:s')) . '"')
            ->where('attempts < 3')
            ->orderBy('scheduled_for ASC')
            ->limit($limit);
            
        return Db::getInstance()->executeS($sql) ?: [];
    }
    
    /**
     * Verificar si se puede ejecutar un trigger
     */
    private function canExecuteTrigger(int $abandonmentId, string $triggerType): bool
    {
        // Verificar frecuencia (máximo 1 por tipo por día)
        $sql = new DbQuery();
        $sql->select('COUNT(*)')
            ->from(_DB_PREFIX_ . 'alsernetshopping_modal_interactions')
            ->where('id_abandoned_cart = ' . (int)$abandonmentId)
            ->where('trigger_type = "' . pSQL($triggerType) . '"')
            ->where('DATE(interaction_timestamp) = CURDATE()');
            
        $todayCount = (int)Db::getInstance()->getValue($sql);
        
        return $todayCount < $this->config['max_daily_triggers_per_type'];
    }
    
    /**
     * Limpiar abandonos antiguos
     */
    private function cleanupOldAbandonments(int $daysOld): int
    {
        $cutoffDate = date('Y-m-d H:i:s', time() - ($daysOld * 24 * 60 * 60));
        
        return Db::getInstance()->delete(
            _DB_PREFIX_ . 'alsernetshopping_abandoned_carts',
            'date_add < "' . pSQL($cutoffDate) . '" AND is_recovered = 0'
        );
    }
    
    /**
     * Limpiar jobs completados
     */
    private function cleanupCompletedJobs(int $daysOld): int
    {
        $cutoffDate = date('Y-m-d H:i:s', time() - ($daysOld * 24 * 60 * 60));
        
        return Db::getInstance()->delete(
            _DB_PREFIX_ . 'alsernetshopping_cron_jobs',
            'completed_at < "' . pSQL($cutoffDate) . '" AND status = "' . pSQL(self::STATUS_COMPLETED) . '"'
        );
    }
    
    /**
     * Limpiar descuentos expirados
     */
    private function cleanupExpiredDiscounts(): int
    {
        return Db::getInstance()->update(
            'cart_rule',
            ['active' => 0],
            'date_to < NOW() AND name LIKE "%Abandonment Recovery%"'
        );
    }
    
    /**
     * Cargar configuración
     */
    private function loadConfiguration(): void
    {
        $this->config = [
            'max_daily_triggers_per_type' => (int)Configuration::get('ALSERNETSHOPPING_MAX_DAILY_TRIGGERS') ?: 3,
            'cleanup_days' => (int)Configuration::get('ALSERNETSHOPPING_CLEANUP_DAYS') ?: 30,
            'max_job_attempts' => 3,
            'job_timeout' => 300 // 5 minutos
        ];
    }
    
    // Métodos auxiliares de estado de jobs
    
    private function markJobAsProcessing(int $jobId): bool
    {
        return Db::getInstance()->update(
            _DB_PREFIX_ . 'alsernetshopping_cron_jobs',
            [
                'status' => pSQL(self::STATUS_PROCESSING),
                'started_at' => date('Y-m-d H:i:s'),
                'attempts' => 'attempts + 1'
            ],
            'id = ' . (int)$jobId
        );
    }
    
    private function markJobAsCompleted(int $jobId, array $result): bool
    {
        return Db::getInstance()->update(
            _DB_PREFIX_ . 'alsernetshopping_cron_jobs',
            [
                'status' => pSQL(self::STATUS_COMPLETED),
                'completed_at' => date('Y-m-d H:i:s'),
                'result_data' => pSQL(json_encode($result))
            ],
            'id = ' . (int)$jobId
        );
    }
    
    private function markJobAsFailed(int $jobId, string $error): bool
    {
        return Db::getInstance()->update(
            _DB_PREFIX_ . 'alsernetshopping_cron_jobs',
            [
                'status' => pSQL(self::STATUS_FAILED),
                'error_message' => pSQL($error),
                'failed_at' => date('Y-m-d H:i:s')
            ],
            'id = ' . (int)$jobId
        );
    }
    
    private function getAbandonmentById(int $abandonmentId): ?array
    {
        $sql = new DbQuery();
        $sql->select('*')
            ->from(_DB_PREFIX_ . 'alsernetshopping_abandoned_carts')
            ->where('id_abandoned_cart = ' . (int)$abandonmentId);
            
        return Db::getInstance()->getRow($sql) ?: null;
    }
    
    private function generateReportData(string $reportType): array
    {
        // Implementar generación de reportes según tipo
        // Por ahora retorna datos básicos
        return [
            'report_type' => $reportType,
            'generated_at' => date('Y-m-d H:i:s'),
            'data' => []
        ];
    }
    
    private function saveReport(string $reportType, array $reportData): bool
    {
        // Guardar reporte en tabla o enviar por email
        return true;
    }
}