<?php

namespace Modules\Helpdesk\Http\Controllers\Managers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Helpdesk\Http\Requests\Managers\Settings\UpdateIntegrationsSettingsRequest;
use Modules\Helpdesk\Models\Setting;
use Nwidart\Modules\Facades\Module;

/**
 * Settings page to enable/disable every Helpdesk-integrated module from the
 * UI. Each module below has business logic that reads its matching
 * `helpdesk_{key}_enabled()` helper (modules/Helpdesk/app/Helpers/helpers.php),
 * so every switch here is functional — not just informative.
 */
class IntegrationsController extends Controller
{
    /**
     * Modules with a functional Setting-backed toggle, keyed by module name.
     * `key` is the setting/form-field prefix consumed by the matching
     * `helpdesk_{key}_enabled()` helper in modules/Helpdesk/app/Helpers/helpers.php.
     *
     * @var array<string, array{key: string, description: string}>
     */
    public const array TOGGLEABLE_MODULES = [
        'HelpdeskTickets' => [
            'key' => 'tickets',
            'description' => 'Sistema completo de tickets con SLA, automatizaciones, tickets recurrentes y portal de cliente. '.
                'Cuando esta habilitado aparece la pestana Tickets en las conversaciones y el panel del agente.',
        ],
        'HelpdeskLivechat' => [
            'key' => 'livechat',
            'description' => 'Widget de chat en vivo (canal Web): sesiones, cola de espera y disponibilidad de agentes en tiempo real.',
        ],
        'HelpdeskChatFlow' => [
            'key' => 'chatflow',
            'description' => 'Constructor de flujos conversacionales automatizados (chatbot) multicanal.',
        ],
        'HelpdeskSla' => [
            'key' => 'sla',
            'description' => 'Motor de SLA: calcula vencimientos de primera respuesta y resolucion, y registra incumplimientos.',
        ],
        'HelpdeskCompliance' => [
            'key' => 'compliance',
            'description' => 'Orquesta el borrado GDPR del cliente en cascada hacia tickets y sesiones de chatbot.',
        ],
        'HelpdeskErp' => [
            'key' => 'erp',
            'description' => 'Datos del cliente en el ERP (facturacion, pedidos) en el panel lateral de la conversacion.',
        ],
        'Forms' => [
            'key' => 'forms',
            'description' => 'Recibe los formularios del sitio Alvarez (modulo alsernetforms) como tickets, uno por categoria de formulario.',
        ],
        'HelpdeskSocial' => [
            'key' => 'social',
            'description' => 'Gestiona comentarios y mensajes de redes sociales como conversaciones, con auto-asignacion y analisis de sentimiento.',
        ],
        'HelpdeskTranslate' => [
            'key' => 'translate',
            'description' => 'Traduce automaticamente los mensajes entrantes y salientes (LibreTranslate/DeepL).',
        ],
        'HelpdeskAgents' => [
            'key' => 'agents',
            'description' => 'Agentes de inteligencia artificial que responden automaticamente mensajes entrantes.',
        ],
        'HelpdeskCampaigns' => [
            'key' => 'campaigns',
            'description' => 'Mide impresiones y efectividad de campanas de marketing sobre clientes del helpdesk.',
        ],
        'HelpdeskContacts' => [
            'key' => 'contacts',
            'description' => 'Vista 360 del cliente: conversaciones, tickets, ERP, PrestaShop y actividad en un solo lugar.',
        ],
        'HelpdeskAnalytics' => [
            'key' => 'analytics',
            'description' => 'Dashboard de metricas cross-canal: volumen, CSAT, tiempos de respuesta y salud del cliente.',
        ],
        'HelpdeskDocument' => [
            'key' => 'document',
            'description' => 'Pestana de Documentos del cliente dentro de la conversacion, con carga y validacion de archivos.',
        ],
        'HelpdeskPrestashop' => [
            'key' => 'prestashop',
            'description' => 'Datos de tienda PrestaShop (pedidos, carritos asistidos) en el panel lateral de la conversacion.',
        ],
        'HelpdeskHelpcenter' => [
            'key' => 'helpcenter',
            'description' => 'Base de articulos de ayuda con busqueda semantica para agentes y bots.',
        ],
        'HelpdeskEmailLog' => [
            'key' => 'emaillog',
            'description' => 'Registra encabezados y trazabilidad de los correos entrantes y salientes del helpdesk.',
        ],
        'HelpdeskIntegration' => [
            'key' => 'integration',
            'description' => 'Catalogo configurable de proveedores externos (ERP, PrestaShop) para vincular clientes.',
        ],
    ];

    public function index(): View
    {
        $this->authorize('helpdesk.settings.view');

        return view('helpdesk::settings.integrations', [
            'toggleableModules' => $this->toggleableModulesStatus(),
        ]);
    }

    public function update(UpdateIntegrationsSettingsRequest $request): RedirectResponse
    {
        foreach (self::TOGGLEABLE_MODULES as $module) {
            $field = "{$module['key']}_integration_enabled";
            $value = $request->has($field) ? '1' : '0';

            Setting::set("{$module['key']}.integration_enabled", $value, 'integrations');
        }

        return back()->with('success', 'Integraciones actualizadas.');
    }

    /**
     * @return array<int, array{name: string, key: string, description: string, installed: bool, moduleEnabled: bool, configEnabled: ?bool, toggleEnabled: bool, canToggle: bool}>
     */
    private function toggleableModulesStatus(): array
    {
        return collect(self::TOGGLEABLE_MODULES)
            ->map(function (array $module, string $name): array {
                $found = Module::find($name);
                $installed = $found !== null;
                $moduleEnabled = $found?->isEnabled() ?? false;

                // Only HelpdeskTickets has an .env kill switch today.
                $configEnabled = $name === 'HelpdeskTickets'
                    ? (bool) config('helpdesk.tickets.enabled', true)
                    : null;

                return [
                    'name' => $name,
                    'key' => $module['key'],
                    'description' => $module['description'],
                    'installed' => $installed,
                    'moduleEnabled' => $moduleEnabled,
                    'configEnabled' => $configEnabled,
                    'toggleEnabled' => filter_var(
                        Setting::get("{$module['key']}.integration_enabled", '1'),
                        FILTER_VALIDATE_BOOLEAN
                    ),
                    'canToggle' => $installed && $moduleEnabled && ($configEnabled ?? true),
                ];
            })
            ->values()
            ->all();
    }
}
