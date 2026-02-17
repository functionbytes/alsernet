<?php

namespace Modules\Attention\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Modules\Attention\Http\Requests\UpdateEmailSettingsRequest;
use Modules\Attention\Http\Requests\UpdateGlobalSettingsRequest;
use Modules\Attention\Http\Requests\UpdateSlaSettingsRequest;
use Modules\Attention\Models\Attention;
use Modules\Core\Models\Setting;
use Modules\Mailer\Models\MailerTemplate;

/**
 * AttentionConfigurationController
 * Handles all attention/PQRSF configuration and settings:
 * - Global settings (radicado prefix, auto-assignment)
 * - Email configuration and templates
 * - SLA policies and configurations
 */
class AttentionConfigurationController extends Controller
{
    /**
     * The settings prefix for attention-related settings.
     */
    private const SETTINGS_PREFIX = 'attention.';

    /**
     * Available setting groups/categories.
     *
     * @var array<string, array{label: string, description: string, icon: string}>
     */
    private const SETTING_GROUPS = [
        'general' => [
            'label' => 'General',
            'description' => 'Configuracion general del modulo de atencion al cliente',
            'icon' => 'fa-duotone fas fa-cog',
        ],
        'email' => [
            'label' => 'Notificaciones Email',
            'description' => 'Configuracion de envio de emails y notificaciones',
            'icon' => 'fa-duotone fas fa-envelope',
        ],
        'sla' => [
            'label' => 'Politicas SLA',
            'description' => 'Configuracion de politicas de nivel de servicio',
            'icon' => 'fa-duotone fas fa-clock',
        ],
    ];

    /**
     * Display the configuration hub index.
     */
    public function index(): View
    {
        return view('attention::settings.index');
    }

    /**
     * Display global configuration settings.
     */
    public function globalSettings(): View
    {
        $globalSettings = $this->getGlobalSettings();

        return view('attention::settings.configurations.index', [
            'globalSettings' => $globalSettings,
        ]);
    }

    /**
     * Update global configuration settings.
     */
    public function updateGlobalSettings(UpdateGlobalSettingsRequest $request): RedirectResponse
    {
        try {
            $validated = $request->validated();

            Setting::set('attention.radicado_prefix', $validated['radicado_prefix']);
            Setting::set('attention.auto_assign', $request->boolean('auto_assign') ? 'yes' : 'no');
            Setting::set('attention.require_attachments', $request->boolean('require_attachments') ? 'yes' : 'no');
            Setting::set('attention.enable_anonymous', $request->boolean('enable_anonymous') ? 'yes' : 'no');
            Setting::set('attention.max_attachments', (string) $validated['max_attachments']);
            Setting::set('attention.attachment_max_size', (string) $validated['attachment_max_size']);

            return redirect()
                ->back()
                ->with('success', 'Configuracion global actualizada correctamente');
        } catch (\Exception $e) {
            Log::error('Error updating attention global settings', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'Error al actualizar la configuracion: '.$e->getMessage());
        }
    }

    /**
     * Display email configuration settings.
     */
    public function emailSettings(): View
    {
        $settings = $this->getSettingsGroupedByCategory();
        $attentionStats = $this->calculateAttentionStatistics();

        return view('attention::settings.settings.sections.email', [
            'settings' => $settings,
            'attentionStats' => $attentionStats,
        ]);
    }

    /**
     * Update email settings.
     */
    public function updateEmailSettings(UpdateEmailSettingsRequest $request): RedirectResponse
    {
        try {
            $validated = $request->validated();

            Setting::set('attention.enable_email_notifications', $request->boolean('enable_email_notifications') ? 'yes' : 'no');
            Setting::set('attention.notify_on_received', $request->boolean('notify_on_received') ? 'yes' : 'no');
            Setting::set('attention.notify_on_in_process', $request->boolean('notify_on_in_process') ? 'yes' : 'no');
            Setting::set('attention.notify_on_resolved', $request->boolean('notify_on_resolved') ? 'yes' : 'no');
            Setting::set('attention.notify_on_closed', $request->boolean('notify_on_closed') ? 'yes' : 'no');
            Setting::set('attention.notify_assigned_user', $request->boolean('notify_assigned_user') ? 'yes' : 'no');

            // Save template IDs
            Setting::set('attention.mail_template_received_id', (string) ($validated['mail_template_received_id'] ?? ''));
            Setting::set('attention.mail_template_in_process_id', (string) ($validated['mail_template_in_process_id'] ?? ''));
            Setting::set('attention.mail_template_resolved_id', (string) ($validated['mail_template_resolved_id'] ?? ''));
            Setting::set('attention.mail_template_closed_id', (string) ($validated['mail_template_closed_id'] ?? ''));
            Setting::set('attention.mail_template_assigned_id', (string) ($validated['mail_template_assigned_id'] ?? ''));

            return redirect()
                ->back()
                ->with('success', 'Configuracion de email actualizada correctamente');
        } catch (\Exception $e) {
            Log::error('Error updating attention email settings', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'Error al actualizar la configuracion: '.$e->getMessage());
        }
    }

    /**
     * Display SLA configuration settings.
     */
    public function slaSettings(): View
    {
        $settings = $this->getSettingsGroupedByCategory();
        $slaStats = $this->calculateSlaStatistics();

        return view('attention::settings.settings.sections.sla', [
            'settings' => $settings,
            'slaStats' => $slaStats,
        ]);
    }

    /**
     * Update SLA settings.
     */
    public function updateSlaSettings(UpdateSlaSettingsRequest $request): RedirectResponse
    {
        try {
            $validated = $request->validated();

            Setting::set('attention.sla_enabled', $request->boolean('sla_enabled') ? 'yes' : 'no');
            Setting::set('attention.sla_response_hours', (string) $validated['sla_response_hours']);
            Setting::set('attention.sla_resolution_hours', (string) $validated['sla_resolution_hours']);
            Setting::set('attention.sla_business_hours_only', $request->boolean('sla_business_hours_only') ? 'yes' : 'no');
            Setting::set('attention.sla_business_hours_start', $validated['sla_business_hours_start']);
            Setting::set('attention.sla_business_hours_end', $validated['sla_business_hours_end']);
            Setting::set('attention.sla_exclude_weekends', $request->boolean('sla_exclude_weekends') ? 'yes' : 'no');
            Setting::set('attention.sla_auto_escalate', $request->boolean('sla_auto_escalate') ? 'yes' : 'no');
            Setting::set('attention.sla_escalation_email', $validated['sla_escalation_email'] ?? '');

            return redirect()
                ->back()
                ->with('success', 'Configuracion de SLA actualizada correctamente');
        } catch (\Exception $e) {
            Log::error('Error updating attention SLA settings', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'Error al actualizar la configuracion: '.$e->getMessage());
        }
    }

    /**
     * Reset settings to default values.
     */
    public function resetToDefaults(Request $request): JsonResponse
    {
        try {
            $group = $request->input('group');

            $defaultSettings = $this->getDefaultSettings();

            if ($group !== null) {
                $defaultSettings = array_filter($defaultSettings, function ($setting) use ($group) {
                    return ($setting['group'] ?? 'general') === $group;
                });
            }

            DB::beginTransaction();

            foreach ($defaultSettings as $key => $config) {
                $fullKey = self::SETTINGS_PREFIX.$key;
                $oldValue = Setting::get($fullKey);
                Setting::set($fullKey, $config['default']);
                $this->logSettingChange($fullKey, $oldValue, $config['default'], 'reset_to_default');
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Configuraciones restauradas a valores por defecto',
                'count' => count($defaultSettings),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error resetting attention settings to defaults', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al restaurar configuraciones: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Search email templates for Select2 AJAX.
     */
    public function searchTemplates(Request $request): JsonResponse
    {
        try {
            $query = $request->get('q', '');

            // Search templates from attention module that are enabled
            $templates = MailerTemplate::module('attention')
                ->enabled()
                ->when($query, function ($q) use ($query) {
                    return $q->search($query);
                })
                ->select(['id', 'name', 'key'])
                ->with(['translations' => function ($q) {
                    $q->where('lang_id', 1)->select('id', 'mailer_template_id', 'lang_id');
                }])
                ->orderBy('name')
                ->limit(50)
                ->get()
                ->map(function ($template) {
                    return [
                        'id' => (int) $template->id,
                        'text' => sprintf(
                            '%s [Lang: %s]',
                            $template->name,
                            'Default'
                        ),
                        'name' => $template->name,
                        'key' => $template->key,
                    ];
                })
                ->values()
                ->toArray();

            return response()->json([
                'results' => $templates,
                'pagination' => ['more' => false],
            ]);
        } catch (\Exception $e) {
            Log::error('Error searching attention templates', [
                'error' => $e->getMessage(),
                'query' => $query ?? null,
            ]);

            return response()->json([
                'results' => [],
                'error' => 'Error al buscar templates: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Test email connection.
     */
    public function testEmailConnection(Request $request): JsonResponse
    {
        $startTime = microtime(true);

        try {
            $testEmail = $request->input('test_email', Auth::user()->email ?? config('mail.from.address'));

            if (! filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email invalido',
                    'response_time' => 0,
                ], 400);
            }

            // Send test email
            Mail::raw('Este es un email de prueba del modulo de Atencion al Cliente.', function ($message) use ($testEmail) {
                $message->to($testEmail)
                    ->subject('Test de Conexion - Modulo Atencion');
            });

            $responseTime = round((microtime(true) - $startTime) * 1000);

            return response()->json([
                'success' => true,
                'message' => 'Email de prueba enviado correctamente a '.$testEmail,
                'response_time' => $responseTime,
            ]);
        } catch (\Exception $e) {
            Log::error('Error testing attention email connection', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al enviar email de prueba: '.$e->getMessage(),
                'response_time' => round((microtime(true) - $startTime) * 1000),
            ], 500);
        }
    }

    /**
     * Get global settings configuration.
     */
    private function getGlobalSettings(): array
    {
        // Get all available templates for Select2
        $availableTemplates = MailerTemplate::module('attention')
            ->enabled()
            ->select(['id', 'name', 'key'])
            ->with(['translations' => function ($q) {
                $q->where('lang_id', 1)->select('id', 'mailer_template_id', 'lang_id');
            }])
            ->orderBy('name')
            ->get()
            ->map(function ($template) {
                return [
                    'id' => (int) $template->id,
                    'text' => sprintf(
                        '%s [Lang: %s]',
                        $template->name,
                        'Default'
                    ),
                    'name' => $template->name,
                    'key' => $template->key,
                ];
            })
            ->toArray();

        return [
            'radicado_prefix' => Setting::get('attention.radicado_prefix', config('attention.radicado_prefix', 'PQRSF')),
            'auto_assign' => Setting::get('attention.auto_assign', 'no') === 'yes',
            'require_attachments' => Setting::get('attention.require_attachments', 'no') === 'yes',
            'enable_anonymous' => Setting::get('attention.enable_anonymous', 'yes') === 'yes',
            'max_attachments' => (int) Setting::get('attention.max_attachments', '5'),
            'attachment_max_size' => (int) Setting::get('attention.attachment_max_size', '10240'),
            'available_templates' => $availableTemplates,
        ];
    }

    /**
     * Retrieve all attention settings grouped by category.
     *
     * @return array<string, array>
     */
    private function getSettingsGroupedByCategory(): array
    {
        // Get all attention settings from database
        $settings = Setting::where('key', 'like', self::SETTINGS_PREFIX.'%')
            ->get()
            ->keyBy('key');

        // Get default settings with metadata
        $defaultSettings = $this->getDefaultSettings();

        $grouped = [];

        foreach ($defaultSettings as $key => $config) {
            $fullKey = self::SETTINGS_PREFIX.$key;
            $group = $config['group'] ?? 'general';

            // Get current value from database or use default
            $currentValue = $settings->has($fullKey)
                ? $settings[$fullKey]->value
                : $config['default'];

            $grouped[$group][$key] = [
                'key' => $fullKey,
                'value' => $currentValue,
                'default' => $config['default'],
                'type' => $config['type'],
                'label' => $config['label'],
                'description' => $config['description'] ?? '',
                'options' => $config['options'] ?? null,
                'validation' => $config['validation'] ?? null,
            ];
        }

        return $grouped;
    }

    /**
     * Get default settings configuration.
     *
     * @return array<string, array>
     */
    private function getDefaultSettings(): array
    {
        return [
            // General Settings
            'enabled' => [
                'group' => 'general',
                'type' => 'boolean',
                'default' => 'yes',
                'label' => 'Modulo habilitado',
                'description' => 'Habilita o deshabilita el modulo de atencion al cliente',
            ],
            'radicado_prefix' => [
                'group' => 'general',
                'type' => 'string',
                'default' => config('attention.radicado_prefix', 'PQRSF'),
                'label' => 'Prefijo de radicado',
                'description' => 'Prefijo para los numeros de radicado',
            ],
            'auto_assign' => [
                'group' => 'general',
                'type' => 'boolean',
                'default' => 'no',
                'label' => 'Asignacion automatica',
                'description' => 'Asigna automaticamente los casos a usuarios disponibles',
            ],
            'require_attachments' => [
                'group' => 'general',
                'type' => 'boolean',
                'default' => 'no',
                'label' => 'Requerir archivos adjuntos',
                'description' => 'Hace obligatorio adjuntar archivos al crear un caso',
            ],
            'enable_anonymous' => [
                'group' => 'general',
                'type' => 'boolean',
                'default' => 'yes',
                'label' => 'Permitir casos anonimos',
                'description' => 'Permite crear casos sin estar registrado',
            ],
            'max_attachments' => [
                'group' => 'general',
                'type' => 'integer',
                'default' => '5',
                'label' => 'Maximo de archivos adjuntos',
                'description' => 'Numero maximo de archivos que se pueden adjuntar',
                'validation' => ['integer', 'min:1', 'max:20'],
            ],
            'attachment_max_size' => [
                'group' => 'general',
                'type' => 'integer',
                'default' => '10240',
                'label' => 'Tamano maximo por archivo (KB)',
                'description' => 'Tamano maximo permitido por archivo en kilobytes',
                'validation' => ['integer', 'min:1024', 'max:102400'],
            ],

            // Email Settings
            'enable_email_notifications' => [
                'group' => 'email',
                'type' => 'boolean',
                'default' => 'yes',
                'label' => 'Notificaciones por email habilitadas',
                'description' => 'Habilita el envio de notificaciones por email',
            ],
            'notify_on_received' => [
                'group' => 'email',
                'type' => 'boolean',
                'default' => 'yes',
                'label' => 'Notificar al recibir',
                'description' => 'Envia email cuando se recibe un nuevo caso',
            ],
            'notify_on_in_process' => [
                'group' => 'email',
                'type' => 'boolean',
                'default' => 'yes',
                'label' => 'Notificar al procesar',
                'description' => 'Envia email cuando un caso pasa a estado en proceso',
            ],
            'notify_on_resolved' => [
                'group' => 'email',
                'type' => 'boolean',
                'default' => 'yes',
                'label' => 'Notificar al resolver',
                'description' => 'Envia email cuando un caso es resuelto',
            ],
            'notify_on_closed' => [
                'group' => 'email',
                'type' => 'boolean',
                'default' => 'yes',
                'label' => 'Notificar al cerrar',
                'description' => 'Envia email cuando un caso es cerrado',
            ],
            'notify_assigned_user' => [
                'group' => 'email',
                'type' => 'boolean',
                'default' => 'yes',
                'label' => 'Notificar al usuario asignado',
                'description' => 'Envia email al usuario cuando se le asigna un caso',
            ],
            'mail_template_received_id' => [
                'group' => 'email',
                'type' => 'integer',
                'default' => '',
                'label' => 'Template: Caso recibido',
                'description' => 'Plantilla de email para cuando se recibe un caso',
            ],
            'mail_template_in_process_id' => [
                'group' => 'email',
                'type' => 'integer',
                'default' => '',
                'label' => 'Template: En proceso',
                'description' => 'Plantilla de email para cuando un caso esta en proceso',
            ],
            'mail_template_resolved_id' => [
                'group' => 'email',
                'type' => 'integer',
                'default' => '',
                'label' => 'Template: Resuelto',
                'description' => 'Plantilla de email para cuando un caso es resuelto',
            ],
            'mail_template_closed_id' => [
                'group' => 'email',
                'type' => 'integer',
                'default' => '',
                'label' => 'Template: Cerrado',
                'description' => 'Plantilla de email para cuando un caso es cerrado',
            ],
            'mail_template_assigned_id' => [
                'group' => 'email',
                'type' => 'integer',
                'default' => '',
                'label' => 'Template: Asignado',
                'description' => 'Plantilla de email para cuando se asigna un caso',
            ],

            // SLA Settings
            'sla_enabled' => [
                'group' => 'sla',
                'type' => 'boolean',
                'default' => 'yes',
                'label' => 'SLA habilitado',
                'description' => 'Habilita el seguimiento de acuerdos de nivel de servicio',
            ],
            'sla_response_hours' => [
                'group' => 'sla',
                'type' => 'integer',
                'default' => '24',
                'label' => 'Horas para primera respuesta',
                'description' => 'Numero de horas maximo para dar primera respuesta',
                'validation' => ['integer', 'min:1', 'max:720'],
            ],
            'sla_resolution_hours' => [
                'group' => 'sla',
                'type' => 'integer',
                'default' => '72',
                'label' => 'Horas para resolucion',
                'description' => 'Numero de horas maximo para resolver el caso',
                'validation' => ['integer', 'min:1', 'max:720'],
            ],
            'sla_business_hours_only' => [
                'group' => 'sla',
                'type' => 'boolean',
                'default' => 'yes',
                'label' => 'Solo horario laboral',
                'description' => 'Calcula SLA solo en horario laboral',
            ],
            'sla_business_hours_start' => [
                'group' => 'sla',
                'type' => 'string',
                'default' => '09:00',
                'label' => 'Inicio horario laboral',
                'description' => 'Hora de inicio del horario laboral (HH:MM)',
            ],
            'sla_business_hours_end' => [
                'group' => 'sla',
                'type' => 'string',
                'default' => '17:00',
                'label' => 'Fin horario laboral',
                'description' => 'Hora de fin del horario laboral (HH:MM)',
            ],
            'sla_exclude_weekends' => [
                'group' => 'sla',
                'type' => 'boolean',
                'default' => 'yes',
                'label' => 'Excluir fines de semana',
                'description' => 'No cuenta fines de semana para el calculo de SLA',
            ],
            'sla_auto_escalate' => [
                'group' => 'sla',
                'type' => 'boolean',
                'default' => 'yes',
                'label' => 'Escalamiento automatico',
                'description' => 'Escala automaticamente cuando se excede el SLA',
            ],
            'sla_escalation_email' => [
                'group' => 'sla',
                'type' => 'string',
                'default' => '',
                'label' => 'Email de escalamiento',
                'description' => 'Direccion de email para notificaciones de escalamiento',
                'validation' => ['nullable', 'email'],
            ],
        ];
    }

    /**
     * Calculate attention statistics.
     */
    private function calculateAttentionStatistics(): array
    {
        try {
            $totalCases = Attention::count();
            $casesReceived = Attention::where('status_id', config('attention.statuses.received', 1))->count();
            $casesInProcess = Attention::where('status_id', config('attention.statuses.in_process', 2))->count();
            $casesResolved = Attention::where('status_id', config('attention.statuses.resolved', 3))->count();
            $casesClosed = Attention::where('status_id', config('attention.statuses.closed', 4))->count();

            // Cases by type
            $casesByType = Attention::select('type', DB::raw('COUNT(*) as count'))
                ->groupBy('type')
                ->pluck('count', 'type')
                ->toArray();

            // Recent cases (last 30 days)
            $recentCases = Attention::where('created_at', '>=', now()->subDays(30))->count();

            // Cases with assigned user
            $assignedCases = Attention::whereNotNull('assigned_to')->count();

            return [
                'total_cases' => $totalCases,
                'cases_received' => $casesReceived,
                'cases_in_process' => $casesInProcess,
                'cases_resolved' => $casesResolved,
                'cases_closed' => $casesClosed,
                'cases_by_type' => $casesByType,
                'recent_cases' => $recentCases,
                'assigned_cases' => $assignedCases,
                'resolution_rate' => $totalCases > 0
                    ? round((($casesResolved + $casesClosed) / $totalCases) * 100, 2)
                    : 0,
            ];
        } catch (\Exception $e) {
            Log::error('Error calculating attention statistics', ['error' => $e->getMessage()]);

            return [
                'total_cases' => 0,
                'cases_received' => 0,
                'cases_in_process' => 0,
                'cases_resolved' => 0,
                'cases_closed' => 0,
                'cases_by_type' => [],
                'recent_cases' => 0,
                'assigned_cases' => 0,
                'resolution_rate' => 0,
            ];
        }
    }

    /**
     * Calculate SLA compliance statistics.
     */
    private function calculateSlaStatistics(): array
    {
        try {
            $slaEnabled = Setting::get('attention.sla_enabled', 'yes') === 'yes';
            $responseHours = (int) Setting::get('attention.sla_response_hours', '24');
            $resolutionHours = (int) Setting::get('attention.sla_resolution_hours', '72');

            if (! $slaEnabled) {
                return [
                    'sla_enabled' => false,
                    'message' => 'SLA no habilitado',
                ];
            }

            $totalCases = Attention::count();
            $casesWithinResponseSla = Attention::whereNotNull('first_response_at')
                ->whereRaw('TIMESTAMPDIFF(HOUR, created_at, first_response_at) <= ?', [$responseHours])
                ->count();

            $casesWithinResolutionSla = Attention::whereIn('status_id', [
                config('attention.statuses.resolved', 3),
                config('attention.statuses.closed', 4),
            ])
                ->whereNotNull('resolved_at')
                ->whereRaw('TIMESTAMPDIFF(HOUR, created_at, resolved_at) <= ?', [$resolutionHours])
                ->count();

            $resolvedCases = Attention::whereIn('status_id', [
                config('attention.statuses.resolved', 3),
                config('attention.statuses.closed', 4),
            ])->count();

            return [
                'sla_enabled' => true,
                'total_cases' => $totalCases,
                'response_sla_compliance' => $totalCases > 0
                    ? round(($casesWithinResponseSla / $totalCases) * 100, 2)
                    : 100,
                'resolution_sla_compliance' => $resolvedCases > 0
                    ? round(($casesWithinResolutionSla / $resolvedCases) * 100, 2)
                    : 100,
                'response_hours_target' => $responseHours,
                'resolution_hours_target' => $resolutionHours,
            ];
        } catch (\Exception $e) {
            Log::error('Error calculating attention SLA statistics', ['error' => $e->getMessage()]);

            return [
                'sla_enabled' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Log a setting change to the activity log.
     */
    private function logSettingChange(string $key, mixed $oldValue, mixed $newValue, string $action = 'updated'): void
    {
        if ($oldValue === $newValue) {
            return;
        }

        try {
            activity()
                ->causedBy(auth()->user())
                ->withProperties([
                    'key' => $key,
                    'old_value' => $oldValue,
                    'new_value' => $newValue,
                    'action' => $action,
                ])
                ->log("Attention setting {$action}: {$key}");
        } catch (\Exception $e) {
            Log::error('Error logging attention setting change', [
                'error' => $e->getMessage(),
                'key' => $key,
            ]);
        }
    }
}
