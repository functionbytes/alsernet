<?php

namespace Modules\Reviews\Console\Commands;

use Illuminate\Console\Command;
use Modules\Reviews\Models\ReviewReplyTemplate;

class InstallReviewsCommand extends Command
{
    protected $signature = 'reviews:install';

    protected $description = 'Instalar el módulo Reviews (migraciones + seeders)';

    public function handle(): int
    {
        $this->info('Iniciando instalación del módulo Reviews...');
        $this->newLine();

        // Ejecutar migraciones
        $this->info('Ejecutando migraciones...');
        $this->call('migrate', ['--path' => 'modules/Reviews/database/migrations']);

        // Crear plantillas por defecto si no existen
        $this->info('Creando plantillas de respuesta por defecto...');
        $this->createDefaultTemplates();

        // Crear permisos si no existen
        $this->info('Creando permisos...');
        $this->createDefaultPermissions();

        $this->newLine();
        $this->info('✅ Instalación completada correctamente');
        $this->newLine();

        $this->info('Próximos pasos:');
        $this->line('1. Configura las credenciales de Google en config/reviews/google.php');
        $this->line('2. Accede a la configuración en /settings/reviews');
        $this->line('3. Conecta tu cuenta de Google para sincronizar reseñas');

        return self::SUCCESS;
    }

    private function createDefaultTemplates(): void
    {
        $templates = [
            [
                'name' => 'Agradecimiento por reseña positiva',
                'body' => 'Hola {reviewer_name}, gracias por tu excelente reseña en {location_name}. Nos alegra mucho que hayas tenido una buena experiencia. ¡Esperamos verte pronto!',
                'category' => 'positive',
            ],
            [
                'name' => 'Respuesta a reseña negativa',
                'body' => 'Hola {reviewer_name}, lamentamos que tu experiencia no haya sido la esperada en {location_name}. Nos gustaría resolver esto. Por favor, contáctanos directamente para ayudarte.',
                'category' => 'negative',
            ],
            [
                'name' => 'Respuesta neutral',
                'body' => 'Hola {reviewer_name}, gracias por tu comentario en {location_name}. Apreciamos tu retroalimentación y continuaremos mejorando nuestros servicios.',
                'category' => 'neutral',
            ],
        ];

        foreach ($templates as $template) {
            ReviewReplyTemplate::query()
                ->firstOrCreate(
                    ['name' => $template['name']],
                    array_merge($template, ['is_active' => true])
                );
        }

        $this->line('Plantillas creadas: '.count($templates));
    }

    private function createDefaultPermissions(): void
    {
        if (! function_exists('permission')) {
            return;
        }

        $permissions = [
            'reviews.connections.create' => 'Crear conexiones de reseñas',
            'reviews.connections.manage' => 'Administrar ubicaciones',
            'reviews.moderate' => 'Moderar reseñas',
            'reviews.replies.create' => 'Crear respuestas a reseñas',
            'reviews.replies.update' => 'Actualizar respuestas',
            'reviews.replies.approve' => 'Aprobar respuestas',
            'reviews.replies.publish' => 'Publicar respuestas',
            'reviews.templates.create' => 'Crear plantillas de respuesta',
            'reviews.templates.update' => 'Actualizar plantillas',
            'reviews.settings.manage' => 'Administrar configuración',
        ];

        foreach ($permissions as $permission => $description) {
            permission($permission);
        }

        $this->line('Permisos creados: '.count($permissions));
    }
}
