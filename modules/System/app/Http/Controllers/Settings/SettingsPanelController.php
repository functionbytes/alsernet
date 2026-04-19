<?php

namespace Modules\System\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class SettingsPanelController extends Controller
{
    public function index(): View
    {
        $groups = $this->getSettingsGroups();

        return view('settings.panel.index', compact('groups'));
    }

    /** @return array<string, array{label: string, icon: string, color: string, items: list<array{label: string, description: string, route: string, icon: string, badge?: string}>}> */
    private function getSettingsGroups(): array
    {
        return [
            'general' => [
                'label' => 'General',
                'icon' => 'fas fa-cog',
                'color' => 'primary',
                'items' => [
                    ['label' => 'Perfil de usuario',     'description' => 'Edita tu perfil, contraseña y sesiones activas',      'route' => 'settings.auth.profile',         'icon' => 'fas fa-user-circle'],
                    ['label' => 'Doble factor (2FA)',     'description' => 'Configura la autenticación de dos factores',          'route' => 'settings.auth.two-factor',      'icon' => 'fas fa-shield-halved', 'badge' => 'Nuevo'],
                    ['label' => 'Opciones del tema',      'description' => 'Personaliza colores, logo y apariencia del sitio',    'route' => 'settings.theme.options',        'icon' => 'fas fa-palette'],
                    ['label' => 'HTML personalizado',     'description' => 'Inserta código HTML en cabecera y pie de página',     'route' => 'settings.theme.custom-html',    'icon' => 'fas fa-code'],
                    ['label' => 'JS personalizado',       'description' => 'Añade scripts JavaScript globales al sitio',          'route' => 'settings.theme.custom-js',      'icon' => 'fab fa-js'],
                    ['label' => 'CSS personalizado',      'description' => 'Aplica estilos CSS propios sobre el tema',            'route' => 'settings.templates.custom-css.edit', 'icon' => 'fab fa-css3-alt'],
                    ['label' => 'Menús de navegación',   'description' => 'Crea y edita los menús del sitio',                    'route' => 'settings.menus.index',          'icon' => 'fas fa-bars'],
                    ['label' => 'Páginas',                'description' => 'Ajustes de páginas, SEO por defecto y caché',         'route' => 'settings.pages',                'icon' => 'fas fa-file-alt'],
                    ['label' => 'Blog',                   'description' => 'Configuración del módulo de blog',                    'route' => 'settings.blog.index',           'icon' => 'fas fa-newspaper'],
                ],
            ],
            'email' => [
                'label' => 'Email y correo',
                'icon' => 'fas fa-envelope',
                'color' => 'info',
                'items' => [
                    ['label' => 'Correo saliente (SMTP)', 'description' => 'Servidor SMTP, credenciales y prueba de envío',      'route' => 'settings.outgoing-email.index', 'icon' => 'fas fa-paper-plane'],
                    ['label' => 'Correo entrante (IMAP)', 'description' => 'Conexiones IMAP, Gmail y Mailgun',                   'route' => 'settings.incoming-email.index', 'icon' => 'fas fa-inbox'],
                    ['label' => 'Configuración general',  'description' => 'Remitentes por defecto y firma de emails',           'route' => 'settings.email.index',          'icon' => 'fas fa-at'],
                    ['label' => 'Plantillas de email',    'description' => 'Gestiona plantillas HTML para notificaciones',       'route' => 'mailers.templates.index',       'icon' => 'fas fa-file-code'],
                    ['label' => 'Componentes de email',   'description' => 'Bloques reutilizables en plantillas',                'route' => 'mailers.components.index',      'icon' => 'fas fa-puzzle-piece'],
                    ['label' => 'Variables de email',     'description' => 'Variables dinámicas para personalizar emails',       'route' => 'mailers.variables.index',       'icon' => 'fas fa-tags'],
                    ['label' => 'Endpoints de email',     'description' => 'API endpoints para envío de emails',                 'route' => 'mailers.endpoints.index',       'icon' => 'fas fa-plug'],
                ],
            ],
            'content' => [
                'label' => 'Contenido',
                'icon' => 'fas fa-layer-group',
                'color' => 'success',
                'items' => [
                    ['label' => 'Plantillas de diseño',  'description' => 'Gestiona las plantillas de contenido del sitio',     'route' => 'settings.templates.index',      'icon' => 'fas fa-th-large'],
                    ['label' => 'Shortcodes',             'description' => 'Administra los shortcodes disponibles en el editor', 'route' => 'settings.shortcodes.index',     'icon' => 'fas fa-brackets-curly'],
                    ['label' => 'SEO - Metas',            'description' => 'Meta tags individuales por URL',                    'route' => 'setting.seo.metas.index',       'icon' => 'fas fa-search'],
                    ['label' => 'SEO - Redirecciones',    'description' => 'Gestiona redirecciones 301 y 302',                  'route' => 'setting.seo.redirects.index',   'icon' => 'fas fa-arrows-alt-h'],
                    ['label' => 'Robots.txt',             'description' => 'Configura las reglas del archivo robots.txt',       'route' => 'setting.seo.robots.edit',       'icon' => 'fas fa-robot'],
                    ['label' => 'Sitemap XML',            'description' => 'Genera y gestiona el sitemap del sitio',            'route' => 'setting.seo.sitemap.index',     'icon' => 'fas fa-sitemap'],
                ],
            ],
            'integrations' => [
                'label' => 'Integraciones',
                'icon' => 'fas fa-plug',
                'color' => 'warning',
                'items' => [
                    ['label' => 'Analytics',              'description' => 'Google Analytics, credenciales y reportes',          'route' => 'settings.analytics.index',      'icon' => 'fas fa-chart-bar'],
                    ['label' => 'Reportes programados',   'description' => 'Configura envío automático de informes',             'route' => 'settings.analytics.schedules.index', 'icon' => 'fas fa-calendar-alt'],
                    ['label' => 'Captcha',                'description' => 'Configura Google reCAPTCHA en formularios',          'route' => 'captcha.settings',              'icon' => 'fas fa-shield-alt'],
                    ['label' => 'Reviews Google',         'description' => 'Conexiones y ubicaciones de Google My Business',    'route' => 'settings.reviews.connections.index', 'icon' => 'fab fa-google'],
                    ['label' => 'Configuración reviews',  'description' => 'Ajustes generales del módulo de reseñas',           'route' => 'settings.reviews.config.index', 'icon' => 'fas fa-star'],
                    ['label' => 'IA para reviews',        'description' => 'Configura respuestas automáticas con IA',           'route' => 'settings.reviews.ai.index',     'icon' => 'fas fa-robot', 'badge' => 'Nuevo'],
                    ['label' => 'Cookies',                'description' => 'Texto y comportamiento del aviso de cookies',       'route' => 'settings.cookie.index',         'icon' => 'fas fa-cookie'],
                    ['label' => 'Formularios',            'description' => 'Gestiona formularios y sus configuraciones',         'route' => 'settings.forms.index',          'icon' => 'fas fa-wpforms'],
                ],
            ],
            'security' => [
                'label' => 'Seguridad',
                'icon' => 'fas fa-shield-alt',
                'color' => 'danger',
                'items' => [
                    ['label' => 'Roles',                  'description' => 'Gestiona los roles disponibles en el sistema',      'route' => 'settings.roles.index',          'icon' => 'fas fa-user-tag'],
                    ['label' => 'Permisos',               'description' => 'Administra los permisos del sistema',               'route' => 'settings.permissions.index',    'icon' => 'fas fa-key'],
                    ['label' => 'Matriz de permisos',     'description' => 'Vista consolidada de roles y permisos',             'route' => 'settings.roles.matrix',         'icon' => 'fas fa-table'],
                    ['label' => 'Notificaciones reviews', 'description' => 'Preferencias de alertas para nuevas reseñas',       'route' => 'settings.reviews.notifications.index', 'icon' => 'fas fa-bell', 'badge' => 'Nuevo'],
                    ['label' => 'Alertas del sistema',    'description' => 'Umbrales y notificaciones del sistema',             'route' => 'settings.alerts.index',         'icon' => 'fas fa-exclamation-triangle', 'badge' => 'Nuevo'],
                    ['label' => 'Notificaciones',         'description' => 'Preferencias globales de notificaciones',           'route' => 'settings.notifications.index',  'icon' => 'fas fa-bell'],
                ],
            ],
            'system' => [
                'label' => 'Sistema',
                'icon' => 'fas fa-server',
                'color' => 'secondary',
                'items' => [
                    ['label' => 'Usuarios',               'description' => 'Gestiona los usuarios del sistema',                 'route' => 'settings.users.index',          'icon' => 'fas fa-users'],
                    ['label' => 'Módulos',                'description' => 'Activa, desactiva e instala módulos',               'route' => 'settings.modules.index',        'icon' => 'fas fa-cubes'],
                    ['label' => 'Backups',                'description' => 'Crea y restaura copias de seguridad',               'route' => 'settings.backups.index',        'icon' => 'fas fa-database'],
                    ['label' => 'Programación backups',   'description' => 'Agenda backups automáticos',                        'route' => 'settings.backup.schedules.index', 'icon' => 'fas fa-clock'],
                    ['label' => 'Salud del sistema',      'description' => 'Monitoreo, colas y supervisor',                    'route' => 'settings.health.index',         'icon' => 'fas fa-heartbeat'],
                    ['label' => 'Caché',                  'description' => 'Limpia y configura la caché de la aplicación',     'route' => 'settings.cache.index',          'icon' => 'fas fa-bolt'],
                    ['label' => 'Base de datos',          'description' => 'Conexiones y limpieza de base de datos',           'route' => 'settings.database.index',       'icon' => 'fas fa-server'],
                    ['label' => 'Almacenamiento',         'description' => 'Discos y configuración de almacenamiento',         'route' => 'settings.storage',              'icon' => 'fas fa-hdd'],
                    ['label' => 'Actividad - Logs',       'description' => 'Registros de actividad del sistema',                'route' => 'activity.logs',                 'icon' => 'fas fa-list-alt'],
                    ['label' => 'Actividad - Auditoría',  'description' => 'Auditoría de cambios en entidades',                 'route' => 'activity.audit',                'icon' => 'fas fa-history'],
                    ['label' => 'Optimización',           'description' => 'Configuración del rendimiento del sistema',        'route' => 'settings.optimize.index',       'icon' => 'fas fa-tachometer-alt'],
                ],
            ],
        ];
    }
}
