<?php

namespace Modules\Campaign\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;
use Modules\Campaign\Notifications\SystemAlert;

class SendGroupNotification extends Command
{
    protected $signature = 'notifications:send-group
                            {--role= : Filter by role (e.g., admin, manager)}
                            {--department= : Filter by department ID}
                            {--users= : Comma-separated user IDs (e.g., 1,2,3,4)}
                            {--all : Send to all users}
                            {--title= : Notification title}
                            {--message= : Notification message}
                            {--priority=normal : Priority (low, normal, high, critical)}';

    protected $description = 'Send a notification to a group of users';

    public function handle(): int
    {
        // Validar que se proporcionó título y mensaje
        $title = $this->option('title') ?: $this->ask('Título de la notificación');
        $message = $this->option('message') ?: $this->ask('Mensaje de la notificación');

        if (empty($title) || empty($message)) {
            $this->error('Se requiere un título y un mensaje para la notificación.');

            return 1;
        }

        // Obtener usuarios según los filtros
        $users = $this->getUsersToNotify();

        if ($users->isEmpty()) {
            $this->error('No se encontraron usuarios que cumplan con los criterios especificados.');

            return 1;
        }

        // Confirmar envío
        $this->info("Se enviará la notificación a {$users->count()} usuario(s):");
        $this->table(
            ['ID', 'Email', 'Nombre'],
            $users->map(fn ($u) => [$u->id, $u->email, $u->firstname.' '.$u->lastname])->toArray()
        );

        if (! $this->confirm('¿Desea continuar con el envío?', true)) {
            $this->info('Envío cancelado.');

            return 0;
        }

        // Enviar notificación a todos los usuarios
        $priority = $this->option('priority');

        Notification::send($users, new SystemAlert(
            title: $title,
            message: $message,
            severity: $priority,
            actionUrl: null,
            actionText: null
        ));

        $this->info("✅ Notificación enviada exitosamente a {$users->count()} usuario(s)!");

        return 0;
    }

    protected function getUsersToNotify()
    {
        $query = User::query();

        // Filtrar por role
        if ($role = $this->option('role')) {
            $query->role($role);
        }

        // Filtrar por department
        if ($department = $this->option('department')) {
            $query->where('department_id', $department);
        }

        // Filtrar por IDs específicos
        if ($userIds = $this->option('users')) {
            $ids = explode(',', $userIds);
            $query->whereIn('id', $ids);
        }

        // Enviar a todos
        if ($this->option('all')) {
            // No aplicar filtros adicionales
        }

        return $query->get();
    }
}
