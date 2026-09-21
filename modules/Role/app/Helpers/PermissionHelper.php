<?php

namespace Modules\Role\Helpers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Modules\Theme\Services\NavService;
use Nwidart\Modules\Facades\Module;

class PermissionHelper
{
    /**
     * Paquetes Composer cuyo slug real no coincide con el id que ya usan en
     * NavService (p. ej. el módulo 'document' se registra en el menú como
     * 'documents'). Se excluyen de la lista "cruda" para no listar el mismo
     * módulo dos veces con dos ids distintos.
     */
    private const NAV_ALIASES = [
        'document' => 'documents',
        'mailer' => 'mailers',
        'user' => 'users',
        'forms' => 'forms-inbox',
        'supplier' => 'suppliers',
        'notification' => 'notifications',
        'helpdeskcontacts' => 'contacts',
    ];

    /**
     * Todos los módulos Composer instalados y habilitados, con la etiqueta
     * de NavService cuando el módulo tiene entrada propia en el menú (así
     * conserva su nombre curado), o una etiqueta generada a partir del slug
     * para el resto. Usado por RoleController::showModules() y por
     * ModulePermissionsSeeder para que ningún módulo instalado quede fuera
     * de la pantalla de "módulos visibles por rol".
     *
     * @return array<string, string>
     */
    public static function allModulesForVisibilityToggle(): array
    {
        $navModules = NavService::getMiniItems()
            ->mapWithKeys(fn (array $item) => [$item['id'] => $item['tooltip']]);

        $rawModules = collect(Module::all())
            ->filter(fn ($module) => $module->isEnabled())
            ->map(fn ($module) => strtolower($module->getName()))
            ->reject(fn (string $slug) => array_key_exists($slug, self::NAV_ALIASES))
            ->mapWithKeys(fn (string $slug) => [$slug => Str::ucfirst(self::label($slug))]);

        return $navModules->union($rawModules)->sortKeys()->toArray();
    }

    /**
     * Traduce los verbos de acción más comunes que aparecen en los slugs de
     * permisos (p. ej. "roles.view" → "view"), usados como último recurso
     * cuando el permiso no tiene `description` en BD.
     */
    private const ACTIONS = [
        'view' => 'Ver',
        'viewany' => 'Ver todos',
        'index' => 'Listar',
        'show' => 'Ver detalle',
        'create' => 'Crear',
        'store' => 'Crear',
        'update' => 'Actualizar',
        'edit' => 'Editar',
        'delete' => 'Eliminar',
        'destroy' => 'Eliminar',
        'remove' => 'Eliminar',
        'manage' => 'Gestionar',
        'assign' => 'Asignar',
        'unassign' => 'Desasignar',
        'export' => 'Exportar',
        'import' => 'Importar',
        'send' => 'Enviar',
        'resend' => 'Reenviar',
        'approve' => 'Aprobar',
        'reject' => 'Rechazar',
        'cancel' => 'Cancelar',
        'close' => 'Cerrar',
        'reopen' => 'Reabrir',
        'enable' => 'Habilitar',
        'disable' => 'Deshabilitar',
        'install' => 'Instalar',
        'uninstall' => 'Desinstalar',
        'upload' => 'Subir',
        'download' => 'Descargar',
        'sync' => 'Sincronizar',
        'resolve' => 'Resolver',
        'merge' => 'Fusionar',
        'moderate' => 'Moderar',
        'refresh' => 'Actualizar',
        'regenerate' => 'Regenerar',
        'configure' => 'Configurar',
        'test' => 'Probar',
        'reset' => 'Restablecer',
        'reply' => 'Responder',
        'embed' => 'Incrustar',
        'translate' => 'Traducir',
        'use' => 'Usar',
        'impersonate' => 'Suplantar',
        'activate' => 'Activar',
        'add' => 'Añadir',
        'change' => 'Cambiar',
        'clear' => 'Limpiar',
        'confirm' => 'Confirmar',
        'connect' => 'Conectar',
        'disconnect' => 'Desconectar',
        'duplicate' => 'Duplicar',
        'engage' => 'Participar',
        'execute' => 'Ejecutar',
        'publish' => 'Publicar',
        'restart' => 'Reiniciar',
        'restore' => 'Restaurar',
        'retry' => 'Reintentar',
        'run' => 'Ejecutar',
        'scan' => 'Escanear',
        'serve' => 'Servir',
        'set' => 'Establecer',
        'toggle' => 'Activar/desactivar',
        'trigger' => 'Disparar',
        'truncate' => 'Truncar',
        'validate' => 'Validar',
        'verify' => 'Verificar',
        'write' => 'Escribir',
        'read' => 'Leer',
        'pause' => 'Pausar',
        'review' => 'Revisar',
        'generate' => 'Generar',
    ];

    /**
     * Sustantivos/calificadores comunes que aparecen en slugs de permisos de
     * varios módulos. Solo palabras inequívocas: nunca se adivina el sentido
     * de un término específico de un módulo que no se conoce con certeza.
     */
    private const RESOURCES = [
        'users' => 'usuarios',
        'user' => 'usuario',
        'roles' => 'roles',
        'role' => 'rol',
        'permissions' => 'permisos',
        'permission' => 'permiso',
        'modules' => 'módulos',
        'module' => 'módulo',
        'documents' => 'documentos',
        'document' => 'documento',
        'blockades' => 'bloqueos',
        'blockade' => 'bloqueo',
        'conditions' => 'condiciones',
        'condition' => 'condición',
        'groups' => 'grupos',
        'group' => 'grupo',
        'settings' => 'configuración',
        'setting' => 'configuración',
        'policies' => 'políticas',
        'policy' => 'política',
        'storage' => 'almacenamiento',
        'types' => 'tipos',
        'type' => 'tipo',
        'tickets' => 'tickets',
        'ticket' => 'ticket',
        'dashboard' => 'panel',
        'campaigns' => 'campañas',
        'campaign' => 'campaña',
        'suppliers' => 'proveedores',
        'supplier' => 'proveedor',
        'returns' => 'devoluciones',
        'return' => 'devolución',
        'automations' => 'automatizaciones',
        'automation' => 'automatización',
        'subscribers' => 'suscriptores',
        'subscriber' => 'suscriptor',
        'shops' => 'tiendas',
        'shop' => 'tienda',
        'notes' => 'notas',
        'note' => 'nota',
        'files' => 'archivos',
        'file' => 'archivo',
        'attachments' => 'adjuntos',
        'attachment' => 'adjunto',
        'history' => 'historial',
        'own' => 'propios',
        'all' => 'todos',
        'assigned' => 'asignados',
        'emails' => 'correos',
        'email' => 'correo',
        'pricelabels' => 'etiquetas de precio',
        'questions' => 'preguntas',
        'reviews' => 'reseñas',
        'activity' => 'actividad',
        'auth' => 'autenticación',
        'backups' => 'idiomas',
        'cache' => 'caché',
        'chatflow' => 'flujos de chat',
        'contacts' => 'contactos',
        'cookie' => 'cookies',
        'erp' => 'ERP',
        'helpdesk' => 'mesa de ayuda',
        'backup' => 'copias de seguridad',
        'database' => 'base de datos',
        'locale' => 'idioma',
        'notification' => 'notificaciones',
        'queue' => 'colas',
        'theme' => 'tema',
        'action' => 'acción',
        'sending' => 'envío',
        'servers' => 'servidores',
        'server' => 'servidor',
        'financing' => 'financiación',
        'load' => 'carga',
        'source' => 'origen',
        'mails' => 'correos',
        'can' => '',
        // Nombres de rol
        'admin' => 'administrador',
        'accounting' => 'contabilidad',
        'administrative' => 'administrativo',
        'callcenter' => 'centro de llamadas',
        'license' => 'licencia',
        'documentation' => 'documentación',
        'restricted' => 'restringido',
        'manager' => 'gestor',
        // Sustantivos comunes de categorías de permisos
        'alerts' => 'alertas',
        'alert' => 'alerta',
        'api' => 'API',
        'assets' => 'recursos',
        'barcode' => 'código de barras',
        'blacklist' => 'lista negra',
        'canneds' => 'predefinidas',
        'carrier' => 'transportista',
        'comments' => 'comentarios',
        'comment' => 'comentario',
        'config' => 'configuración',
        'content' => 'contenido',
        'core' => 'núcleo',
        'devices' => 'dispositivos',
        'device' => 'dispositivo',
        'discussion' => 'discusión',
        'domains' => 'dominios',
        'domain' => 'dominio',
        'events' => 'eventos',
        'event' => 'evento',
        'failures' => 'fallos',
        'failure' => 'fallo',
        'folders' => 'carpetas',
        'folder' => 'carpeta',
        'handlers' => 'gestores',
        'handler' => 'gestor',
        'hours' => 'horas',
        'incoming' => 'entrante',
        'outgoing' => 'saliente',
        'info' => 'información',
        'lists' => 'listas',
        'localization' => 'localización',
        'locations' => 'ubicaciones',
        'location' => 'ubicación',
        'maillists' => 'listas de correo',
        'maintenance' => 'mantenimiento',
        'mass' => 'masivo',
        'monitoring' => 'monitorización',
        'movements' => 'movimientos',
        'movement' => 'movimiento',
        'operational' => 'operativo',
        'operators' => 'operadores',
        'operator' => 'operador',
        'password' => 'contraseña',
        'payments' => 'pagos',
        'payment' => 'pago',
        'pickup' => 'recogida',
        'premium' => 'premium',
        'priorities' => 'prioridades',
        'priority' => 'prioridad',
        'prompts' => 'prompts',
        'prompt' => 'prompt',
        'resources' => 'recursos',
        'resource' => 'recurso',
        'route' => 'ruta',
        'security' => 'seguridad',
        'sessions' => 'sesiones',
        'session' => 'sesión',
        'sources' => 'fuentes',
        'statistics' => 'estadísticas',
        'success' => 'éxito',
        'supervisor' => 'supervisor',
        'tracking' => 'seguimiento',
        'translations' => 'traducciones',
        'uploading' => 'carga',
        'any' => '',
        'to' => '',
        'only' => 'solo',
        'available' => 'disponible',
        'cleanup' => 'limpieza',
        'get' => 'obtener',
        'table' => 'tabla',
        'count' => 'conteo',
        'global' => 'global',
        'search' => 'búsqueda',
        'langs' => 'idiomas',
        'connection' => 'conexión',
        'notifications' => 'notificaciones',
        'togglestatus' => 'cambiar estado',
        'imap' => 'IMAP',
        'inpost' => 'InPost',
        'phplist' => 'phpList',
        'faqs' => 'preguntas frecuentes',
        'forms' => 'formularios',
        'giftmessage' => 'mensaje de regalo',
        'helpdeskanalytics' => 'analítica',
        'helpdeskemailactivity' => 'actividad de correo',
        'helpdeskerp' => 'ERP',
        'helpdeskintegration' => 'integraciones',
        'helpdeskprestashop' => 'PrestaShop',
        'helpdesksla' => 'SLA',
        'inventaries' => 'productos',
        'inventory' => 'inventario',
        'livechat' => 'chat en vivo',
        'mailer' => 'correo',
        'mailers' => 'correos',
        'media' => 'medios',
        'system' => 'sistema',
        'warehouse' => 'almacén',
        'helpcenter' => 'centro de ayuda',
        'templates' => 'plantillas',
        'template' => 'plantilla',
        'helpdesksocial' => 'redes sociales',
        'helpdeskbirthday' => 'cumpleaños',
        'helpdeskcompliance' => 'cumplimiento normativo',
        'endpoints' => 'endpoints',
        'endpoint' => 'endpoint',
        'sla' => 'SLA',
        'conversations' => 'conversaciones',
        'conversation' => 'conversación',
        'articles' => 'artículos',
        'article' => 'artículo',
        'customers' => 'clientes',
        'customer' => 'cliente',
        'rules' => 'reglas',
        'rule' => 'regla',
        'components' => 'componentes',
        'component' => 'componente',
        'macros' => 'macros',
        'categories' => 'categorías',
        'category' => 'categoría',
        'canned' => 'predefinida',
        'replies' => 'respuestas',
        'reply' => 'respuesta',
        'tags' => 'etiquetas',
        'tag' => 'etiqueta',
        'statuses' => 'estados',
        'status' => 'estado',
        'views' => 'vistas',
        'view' => 'vista',
        'webhooks' => 'webhooks',
        'webhook' => 'webhook',
        'schedule' => 'horario',
        'providers' => 'proveedores',
        'provider' => 'proveedor',
        'attributes' => 'atributos',
        'attribute' => 'atributo',
        'logs' => 'registros',
        'log' => 'registro',
        'orders' => 'pedidos',
        'order' => 'pedido',
        'submissions' => 'envíos',
        'submission' => 'envío',
        'preview' => 'vista previa',
        'stage' => 'etapa',
        'link' => 'vínculo',
        'insights' => 'estadísticas',
        'companies' => 'empresas',
        'company' => 'empresa',
        'skills' => 'habilidades',
        'skill' => 'habilidad',
        'custom' => 'personalizado',
        'fields' => 'campos',
        'field' => 'campo',
        'workflows' => 'flujos de trabajo',
        'workflow' => 'flujo de trabajo',
        'drip' => 'goteo',
        'broadcasts' => 'difusiones',
        'broadcast' => 'difusión',
        'whatsapp' => 'WhatsApp',
        'brands' => 'marcas',
        'brand' => 'marca',
        'slack' => 'Slack',
        'metrics' => 'métricas',
        'metric' => 'métrica',
        'audit' => 'auditoría',
        'prospect' => 'prospecto',
        'analytics' => 'analítica',
        'token' => 'token',
        'tokens' => 'tokens',
        'bulk' => 'masivo',
        'participants' => 'participantes',
        'participant' => 'participante',
        'agents' => 'agentes',
        'agent' => 'agente',
        'banners' => 'banners',
        'banner' => 'banner',
        'surveys' => 'encuestas',
        'survey' => 'encuesta',
        'reports' => 'informes',
        'report' => 'informe',
        'exports' => 'exportaciones',
        'presence' => 'presencia',
        'ai' => 'IA',
        'commerce' => 'comercio',
        'health' => 'salud',
        'detail' => 'detalle',
        'vote' => 'voto',
        'integrations' => 'integraciones',
        'integration' => 'integración',
        'chat' => 'chat',
        'approver' => 'aprobador',
        'accounts' => 'cuentas',
        'account' => 'cuenta',
        'mentions' => 'menciones',
        'mention' => 'mención',
        'force' => 'forzado',
        'competitors' => 'competidores',
        'competitor' => 'competidor',
        'inbox' => 'bandeja de entrada',
        'follow' => 'seguimiento',
        'access' => 'acceso',
        'products' => 'productos',
        'product' => 'producto',
        'carts' => 'carritos',
        'cart' => 'carrito',
    ];

    /**
     * Etiqueta legible en español para un permiso: usa la `description`
     * sembrada en BD cuando existe (es la traducción curada por el módulo
     * dueño del permiso) y, si no hay, traduce el verbo de acción y los
     * sustantivos comunes del slug como último recurso. Cualquier token que
     * no se reconoce (p. ej. el prefijo de un módulo) se deja tal cual, en
     * vez de arriesgar una traducción incorrecta.
     */
    public static function label(string $name, ?string $description = null): string
    {
        if (filled($description)) {
            return $description;
        }

        $tokens = preg_split('/[.\-_]+/', $name, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $actionIndex = null;
        foreach ($tokens as $index => $token) {
            if (array_key_exists(strtolower($token), self::ACTIONS)) {
                $actionIndex = $index;
                break;
            }
        }

        $action = null;
        if ($actionIndex !== null) {
            $action = self::ACTIONS[strtolower($tokens[$actionIndex])];
            unset($tokens[$actionIndex]);
        }

        // Si el nombre trae un segundo verbo (p. ej. "translate.settings.update"),
        // solo el primero se usa como acción principal; el resto cae aquí. Se
        // busca también en ACTIONS para que ese segundo verbo no se quede sin
        // traducir, aunque quede en mitad de la frase en vez de al principio.
        $rest = implode(' ', array_filter(array_map(
            function (string $token) {
                $lower = strtolower($token);

                if (array_key_exists($lower, self::RESOURCES)) {
                    return self::RESOURCES[$lower];
                }

                if (array_key_exists($lower, self::ACTIONS)) {
                    return Str::lcfirst(self::ACTIONS[$lower]);
                }

                return Str::title($token);
            },
            $tokens
        ), fn (string $word) => $word !== ''));

        $result = trim(($action ?? '')." {$rest}");

        return $result === '' ? $name : Str::ucfirst($result);
    }

    /**
     * Verificar si el usuario tiene alguno de los roles especificados
     */
    public static function hasAnyRole(string|array $roles): bool
    {
        if (! Auth::check()) {
            return false;
        }

        $roles = is_array($roles) ? $roles : explode('|', $roles);

        return Auth::user()->hasAnyRole($roles);
    }

    /**
     * Verificar si el usuario tiene todos los roles especificados
     */
    public static function hasAllRoles(string|array $roles): bool
    {
        if (! Auth::check()) {
            return false;
        }

        $roles = is_array($roles) ? $roles : explode('|', $roles);

        return Auth::user()->hasAllRoles($roles);
    }

    /**
     * Verificar si el usuario tiene alguno de los permisos especificados
     */
    public static function hasAnyPermission(string|array $permissions): bool
    {
        if (! Auth::check()) {
            return false;
        }

        $permissions = is_array($permissions) ? $permissions : explode('|', $permissions);

        return Auth::user()->hasAnyPermission($permissions);
    }

    /**
     * Verificar si el usuario tiene un permiso específico
     */
    public static function can(string $permission): bool
    {
        if (! Auth::check()) {
            return false;
        }

        return Auth::user()->can($permission);
    }

    /**
     * Verificar si el usuario puede acceder a un módulo específico
     */
    public static function canAccessModule(string $module): bool
    {
        if (! Auth::check()) {
            return false;
        }

        $modulePermissions = [
            'theme' => ['super-settings', 'settings', 'manager'],
            'callcenters' => ['super-settings', 'settings', 'callcenter-manager', 'callcenter-agent'],
            'inventaries' => ['super-settings', 'settings', 'inventory-manager', 'inventory-staff'],
            'shops' => ['super-settings', 'settings', 'shop-manager', 'shop-staff'],
            'administratives' => ['super-settings', 'settings', 'administrative'],
            'returns' => ['super-settings', 'settings', 'manager', 'administrative', 'customer'],
        ];

        if (! isset($modulePermissions[$module])) {
            return false;
        }

        return Auth::user()->hasAnyRole($modulePermissions[$module]);
    }

    /**
     * Obtener el módulo principal al que tiene acceso el usuario
     */
    public static function getDefaultModule(): ?string
    {
        if (! Auth::check()) {
            return null;
        }

        $user = Auth::user();

        if ($user->hasAnyRole(['super-settings', 'settings', 'manager'])) {
            return 'manager';
        }

        if ($user->hasAnyRole(['callcenter-manager', 'callcenter-agent'])) {
            return 'callcenter';
        }

        if ($user->hasAnyRole(['inventory-manager', 'inventory-staff'])) {
            return 'inventarie';
        }

        if ($user->hasAnyRole(['shop-manager', 'shop-staff'])) {
            return 'shop';
        }

        if ($user->hasRole('administrative')) {
            return 'administrative';
        }

        return 'home'; // Default para customers
    }

    /**
     * Verificar si el usuario puede realizar una acción específica en una devolución
     */
    public static function canManageReturn(mixed $return, string $action = 'view'): bool
    {
        if (! Auth::check()) {
            return false;
        }

        $user = Auth::user();

        if ($user->hasRole('super-settings')) {
            return true;
        }

        return match ($action) {
            'view' => $user->canAccessReturn($return),
            'update' => $user->can('returns.update') && $user->canAccessReturn($return),
            'delete' => $user->can('returns.delete'),
            'approve' => $user->can('returns.status.approve'),
            'reject' => $user->can('returns.status.reject'),
            'assign' => $user->can('returns.assign'),
            default => false,
        };
    }

    /**
     * Verificar si el usuario puede ver un módulo específico
     *
     * @param  string  $moduleId  ID del módulo (ej: 'documents', 'users', 'warehouse')
     */
    public static function canViewModule($moduleId): bool
    {
        if (! Auth::check()) {
            return false;
        }

        $user = Auth::user();

        // Super-settings siempre ve todos los módulos
        if ($user->hasRole('super-settings')) {
            return true;
        }

        // Verificar permiso específico del módulo
        $permissionName = "modules.view.{$moduleId}";

        return $user->hasPermissionTo($permissionName);
    }

    /**
     * Obtener todos los módulos visibles para el usuario actual
     *
     * @return array Array con IDs de módulos [' documents', 'users', ...]
     */
    public static function getVisibleModules(): array
    {
        if (! Auth::check()) {
            return [];
        }

        $user = Auth::user();

        // Si es super-settings, retornar todos los módulos
        if ($user->hasRole('super-settings')) {
            return [
                'documents', 'mailers', 'media', 'users', 'events',
                'warehouse', 'webhooks', 'roles', 'auth', 'notifications',
                'backups', 'settings', 'helpdesk', 'campaigns', 'suppliers', 'analytics',
            ];
        }

        return Cache::remember("user_{$user->id}_visible_modules", now()->addHours(1), function () use ($user) {
            return $user->getPermissionsViaRoles()
                ->where('name', 'like', 'modules.view.%')
                ->pluck('name')
                ->map(fn (string $name) => str_replace('modules.view.', '', $name))
                ->values()
                ->all();
        });
    }
}
