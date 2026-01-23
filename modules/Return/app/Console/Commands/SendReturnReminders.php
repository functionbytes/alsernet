<?php

namespace Modules\Returns\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Returns\Models\ReturnRequest;
use Modules\Returns\Services\ReturnNotificationService;

class SendReturnReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'returns:send-reminders
                            {--days=7 : Días de antigüedad para enviar recordatorio}
                            {--dry-run : Ejecutar sin enviar emails reales}
                            {--status=* : Estados específicos a procesar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enviar recordatorios automáticos para devoluciones pendientes';

    private ?ReturnNotificationService $notificationService;

    public function __construct(?ReturnNotificationService $notificationService = null)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔔 Iniciando envío de recordatorios de devoluciones...');

        $days = $this->option('days');
        $dryRun = $this->option('dry-run');
        $statuses = $this->option('status') ?: ['pending', 'approved'];

        // Obtener devoluciones que necesitan recordatorio
        $returns = $this->getReturnsForReminder($days, $statuses);

        if ($returns->isEmpty()) {
            $this->info('✅ No hay devoluciones que requieran recordatorio.');

            return Command::SUCCESS;
        }

        $this->info("📊 Encontradas {$returns->count()} devoluciones para recordatorio.");

        if ($dryRun) {
            $this->warn('⚠️  Modo DRY RUN activado - No se enviarán emails reales');
        }

        $progressBar = $this->output->createProgressBar($returns->count());
        $progressBar->start();

        $sent = 0;
        $failed = 0;

        foreach ($returns as $return) {
            try {
                if ($this->shouldSendReminder($return)) {
                    if (! $dryRun) {
                        $this->notificationService->sendReminder($return);
                    }

                    $sent++;
                    $this->logSuccess($return);
                }
            } catch (\Exception $e) {
                $failed++;
                $this->logError($return, $e);
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Mostrar resumen
        $this->displaySummary($sent, $failed, $dryRun);

        return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Obtener devoluciones candidatas para recordatorio
     */
    private function getReturnsForReminder(int $days, array $statuses)
    {
        return ReturnRequest::whereIn('status', $statuses)
            ->where('created_at', '<=', Carbon::now()->subDays($days))
            ->whereDoesntHave('communications', function ($query) {
                $query->where('template_used', 'reminder')
                    ->where('created_at', '>', Carbon::now()->subHours(24));
            })
            ->with(['customer', 'communications'])
            ->get();
    }

    /**
     * Verificar si se debe enviar recordatorio
     */
    private function shouldSendReminder(ReturnRequest $return): bool
    {
        // Verificar condiciones específicas del negocio
        $conditions = [
            // No enviar si ya se envió un recordatorio recientemente
            ! $this->hasRecentReminder($return),

            // No enviar si el cliente ya tomó acción
            ! $this->customerTookAction($return),

            // Verificar que el email sea válido
            filter_var($return->customer_email, FILTER_VALIDATE_EMAIL),

            // Otras condiciones del negocio
            $this->isWithinReminderWindow($return),
        ];

        return ! in_array(false, $conditions, true);
    }

    private function hasRecentReminder(ReturnRequest $return): bool
    {
        return $return->communications()
            ->where('template_used', 'reminder')
            ->where('created_at', '>', Carbon::now()->subHours(24))
            ->exists();
    }

    private function customerTookAction(ReturnRequest $return): bool
    {
        // Si el estado es 'approved' y aún no ha enviado el paquete
        if ($return->status === 'approved') {
            return $return->tracking_number !== null;
        }

        return false;
    }

    private function isWithinReminderWindow(ReturnRequest $return): bool
    {
        $daysSinceCreated = $return->created_at->diffInDays(now());

        // No enviar recordatorios después de 30 días
        return $daysSinceCreated <= 30;
    }

    private function logSuccess(ReturnRequest $return): void
    {
        Log::info('Return reminder sent', [
            'return_id' => $return->id,
            'return_number' => $return->number,
            'customer_email' => $return->customer_email,
            'status' => $return->status,
        ]);
    }

    private function logError(ReturnRequest $return, \Exception $e): void
    {
        Log::error('Failed to send return reminder', [
            'return_id' => $return->id,
            'return_number' => $return->number,
            'error' => $e->getMessage(),
        ]);

        $this->error("❌ Error enviando recordatorio para devolución #{$return->number}: {$e->getMessage()}");
    }

    private function displaySummary(int $sent, int $failed, bool $dryRun): void
    {
        $this->info('═══════════════════════════════════════');
        $this->info('📊 RESUMEN DE ENVÍO DE RECORDATORIOS');
        $this->info('═══════════════════════════════════════');

        if ($dryRun) {
            $this->warn('🔍 MODO DRY RUN - Simulación completada');
            $this->info("📧 Emails que se enviarían: {$sent}");
        } else {
            $this->info("✅ Recordatorios enviados: {$sent}");
        }

        if ($failed > 0) {
            $this->error("❌ Recordatorios fallidos: {$failed}");
        }

        $this->info('═══════════════════════════════════════');
    }
}
