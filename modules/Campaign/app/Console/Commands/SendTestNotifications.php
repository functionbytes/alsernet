<?php

namespace Modules\Campaign\Console\Commands;

use App\Models\User;
use App\Notifications\OrderCreated;
use App\Notifications\TicketAssigned;
use App\Notifications\TicketCreated;
use App\Notifications\TicketStatusChanged;
use App\Notifications\UserMentioned;
use Illuminate\Console\Command;
use Modules\Campaign\Notifications\SystemAlert;
use Modules\Document\Notifications\DocumentApproved;
use Modules\Document\Notifications\DocumentRejected;
use Modules\Document\Notifications\DocumentStatusChanged;
use Modules\Document\Notifications\PaymentReceived;
use stdClass;

class SendTestNotifications extends Command
{
    protected $signature = 'notifications:test {user_id? : The ID of the user to notify}';

    protected $description = 'Send test notifications to a user';

    public function handle(): int
    {
        $userId = $this->argument('user_id');

        if (! $userId) {
            $userId = $this->ask('Ingresa el ID del usuario que recibirá las notificaciones');
        }

        $user = User::find($userId);

        if (! $user) {
            $this->error("Usuario con ID {$userId} no encontrado.");

            return Command::FAILURE;
        }

        $this->info("Enviando notificaciones de prueba a: {$user->name} ({$user->email})");
        $this->newLine();

        // Crear objetos mock para las notificaciones
        $mockTicket = $this->createMockTicket();
        $mockOrder = $this->createMockOrder();
        $mockDocument = $this->createMockDocument();
        $mockPayment = $this->createMockPayment();
        $mockMentionedBy = $this->createMockUser();

        $notifications = [
            ['name' => 'TicketCreated', 'notification' => new TicketCreated($mockTicket)],
            ['name' => 'TicketAssigned', 'notification' => new TicketAssigned($mockTicket, $mockMentionedBy)],
            ['name' => 'TicketStatusChanged', 'notification' => new TicketStatusChanged($mockTicket, 'abierto', 'resuelto')],
            ['name' => 'DocumentApproved', 'notification' => new DocumentApproved($mockDocument, $mockMentionedBy)],
            ['name' => 'DocumentRejected', 'notification' => new DocumentRejected($mockDocument, $mockMentionedBy, 'Formato incorrecto')],
            ['name' => 'DocumentStatusChanged', 'notification' => new DocumentStatusChanged($mockDocument, 'pendiente', 'aprobado')],
            ['name' => 'OrderCreated', 'notification' => new OrderCreated($mockOrder)],
            ['name' => 'PaymentReceived', 'notification' => new PaymentReceived($mockPayment, $mockOrder)],
            ['name' => 'UserMentioned', 'notification' => new UserMentioned($mockMentionedBy, 'un comentario', route('manager.dashboard'), '¿Puedes revisar esto?')],
            ['name' => 'SystemAlert (Info)', 'notification' => new SystemAlert('Mantenimiento programado', 'El sistema estará en mantenimiento el próximo sábado de 2-4 AM', 'info')],
            ['name' => 'SystemAlert (Warning)', 'notification' => new SystemAlert('Uso elevado de CPU', 'El servidor está experimentando alta carga', 'warning')],
            ['name' => 'SystemAlert (Critical)', 'notification' => new SystemAlert('Error crítico', 'Se detectó un error crítico en el sistema de pagos', 'critical')],
            ['name' => 'SystemAlert (Success)', 'notification' => new SystemAlert('Backup completado', 'El respaldo automático se completó exitosamente', 'success')],
        ];

        $bar = $this->output->createProgressBar(count($notifications));
        $bar->start();

        foreach ($notifications as $notificationData) {
            try {
                $user->notify($notificationData['notification']);
                $bar->advance();
                $this->newLine();
                $this->line("  ✓ {$notificationData['name']} enviada");
            } catch (\Exception $e) {
                $this->newLine();
                $this->error("  ✗ Error enviando {$notificationData['name']}: {$e->getMessage()}");
            }
        }

        $bar->finish();
        $this->newLine(2);

        $this->info('✓ Todas las notificaciones de prueba han sido enviadas!');
        $this->info('Total de notificaciones: '.count($notifications));
        $this->newLine();

        return Command::SUCCESS;
    }

    protected function createMockTicket(): object
    {
        $ticket = new stdClass;
        $ticket->id = 9999;
        $ticket->number = 'TICKET-'.rand(1000, 9999);

        return $ticket;
    }

    protected function createMockOrder(): object
    {
        $order = new stdClass;
        $order->id = 9999;
        $order->number = 'ORD-'.rand(1000, 9999);
        $order->total = rand(100, 1000);

        $customer = new stdClass;
        $customer->full_name = 'Cliente de Prueba';

        $order->customer = $customer;

        return $order;
    }

    protected function createMockDocument(): object
    {
        $document = new stdClass;
        $document->id = 9999;
        $document->number = 'DOC-'.rand(1000, 9999);

        return $document;
    }

    protected function createMockPayment(): object
    {
        $payment = new stdClass;
        $payment->id = 9999;
        $payment->amount = rand(50, 500);

        return $payment;
    }

    protected function createMockUser(): object
    {
        $mockUser = new stdClass;
        $mockUser->id = 1;
        $mockUser->name = 'Sistema de Pruebas';

        return $mockUser;
    }
}
