<?php

namespace Modules\Document\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Document\Entities\DocumentValidatorGroup;
use Modules\Document\Entities\DocumentValidatorGroupConfiguration;
use Modules\Document\Entities\DocumentValidatorGroupConfigurationHistory;

class DocumentGroupsController extends Controller
{
    /**
     * Display a listing of validator groups (used for document validation).
     */
    public function index(Request $request)
    {
        $query = DocumentValidatorGroup::query();

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $groups = $query->with('users')->ordered()->paginate(20);

        // Calculate statistics
        $stats = [
            'total' => DocumentValidatorGroup::count(),
            'active' => DocumentValidatorGroup::where('is_active', true)->count(),
            'inactive' => DocumentValidatorGroup::where('is_active', false)->count(),
            'default' => DocumentValidatorGroup::where('is_default', true)->count(),
            'total_members' => DocumentValidatorGroup::query()
                ->join('document_validator_group_user', 'document_validator_groups.id', '=', 'document_validator_group_user.validator_group_id')
                ->distinct('document_validator_group_user.user_id')
                ->count('document_validator_group_user.user_id'),
        ];

        return view('documents::settings.groups.index', [
            'groups' => $groups,
            'stats' => $stats,
        ]);
    }

    /**
     * Show the form for creating a new group.
     */
    public function create()
    {
        $users = User::where('available', 1)
            ->orderBy('firstname')
            ->orderBy('lastname')
            ->get();

        return view('documents::settings.groups.create', [
            'users' => $users,
        ]);
    }

    /**
     * Store a newly created group.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:191',
            'key' => 'required|string|max:100|unique:document_validator_groups,key|regex:/^[a-z0-9_-]+$/',
            'description' => 'nullable|string|max:1000',
            'assignment_mode' => 'required|in:manual,round_robin,load_balanced',
            'is_default' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'users' => 'nullable|array',
            'users.*' => 'exists:users,id',
            'user_priorities' => 'nullable|array',
            'user_priorities.*' => 'in:primary,backup',
        ], [
            'key.unique' => 'Esta clave ya está en uso por otro grupo.',
            'key.regex' => 'La clave solo puede contener letras minúsculas, números, guiones y guiones bajos.',
        ]);

        $validated['is_default'] = $request->boolean('is_default');
        $validated['is_active'] = $request->boolean('is_active', true);

        $group = DocumentValidatorGroup::create($validated);

        // Attach users with priorities
        if ($request->filled('users')) {
            $usersData = [];
            foreach ($request->users as $index => $userId) {
                $usersData[$userId] = [
                    'priority' => $request->user_priorities[$index] ?? 'primary',
                ];
            }
            $group->users()->attach($usersData);
        }

        return redirect()->route('settings.documents.groups.index')
            ->with('success', 'Grupo de validación creado exitosamente.');
    }

    /**
     * Show the form for editing a group.
     */
    public function edit(DocumentValidatorGroup $group)
    {
        $group->load('users');
        $users = User::where('available', 1)
            ->orderBy('firstname')
            ->orderBy('lastname')
            ->get();

        return view('documents::settings.groups.edit', [
            'group' => $group,
            'users' => $users,
        ]);
    }

    /**
     * Update the specified group.
     */
    public function update(Request $request, DocumentValidatorGroup $group)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:191',
            'key' => 'required|string|max:100|regex:/^[a-z0-9_-]+$/|unique:document_validator_groups,key,'.$group->id,
            'description' => 'nullable|string|max:1000',
            'assignment_mode' => 'required|in:manual,round_robin,load_balanced',
            'is_default' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'users' => 'nullable|array',
            'users.*' => 'exists:users,id',
            'user_priorities' => 'nullable|array',
            'user_priorities.*' => 'in:primary,backup',
        ], [
            'key.unique' => 'Esta clave ya está en uso por otro grupo.',
            'key.regex' => 'La clave solo puede contener letras minúsculas, números, guiones y guiones bajos.',
        ]);

        $validated['is_default'] = $request->boolean('is_default');
        $validated['is_active'] = $request->boolean('is_active');

        $group->update($validated);

        // Sync users with priorities
        if ($request->has('users')) {
            $usersData = [];
            if ($request->filled('users')) {
                foreach ($request->users as $index => $userId) {
                    $usersData[$userId] = [
                        'priority' => $request->user_priorities[$index] ?? 'primary',
                    ];
                }
            }
            $group->users()->sync($usersData);
        }

        return redirect()->route('settings.documents.groups.index')
            ->with('success', 'Grupo de validación actualizado exitosamente.');
    }

    /**
     * Toggle the active status of a group.
     */
    public function toggle(DocumentValidatorGroup $group)
    {
        $group->update(['is_active' => ! $group->is_active]);

        return back()->with('success', 'Estado del grupo actualizado exitosamente.');
    }

    /**
     * Remove the specified group.
     */
    public function destroy(DocumentValidatorGroup $group)
    {
        // Check if group is default
        if ($group->is_default) {
            return back()->with('error', 'No se puede eliminar el grupo predeterminado.');
        }

        // Check if group has documents assigned (if applicable to your system)
        // Uncomment and adjust if you have documents assigned to groups
        // $documentsCount = \modules\Document\Entities\Document::where('group_id', $group->id)->count();
        // if ($documentsCount > 0) {
        //     return back()->with('error', 'No se puede eliminar un grupo que tiene documentos asignados.');
        // }

        $group->delete();

        return redirect()->route('settings.documents.groups.index')
            ->with('success', 'Grupo de documentos eliminado exitosamente.');
    }

    /**
     * Reorder groups via drag and drop.
     */
    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:document_validator_groups,id',
        ]);

        DocumentValidatorGroup::reorder($validated['ids']);

        return response()->json(['success' => true, 'message' => 'Orden actualizado exitosamente.']);
    }

    /**
     * Show the configuration panel for a group.
     */
    public function configuration(DocumentValidatorGroup $group)
    {
        $configurations = $group->configurations()
            ->where('is_active', true)
            ->orderBy('category')
            ->orderBy('order')
            ->get()
            ->groupBy('category');

        // Get default configurations if none exist
        if ($configurations->isEmpty()) {
            $this->initializeDefaultConfigurations($group);
            $configurations = $group->configurations()
                ->where('is_active', true)
                ->orderBy('category')
                ->orderBy('order')
                ->get()
                ->groupBy('category');
        }

        // Get recent configuration history
        $history = $group->configurationHistory()
            ->with('user')
            ->latest()
            ->limit(10)
            ->get();

        return view('documents::settings.groups.configuration', [
            'group' => $group,
            'configurations' => $configurations,
            'history' => $history,
        ]);
    }

    /**
     * Update configurations for a group.
     */
    public function updateConfiguration(Request $request, ValidatorGroup $group)
    {
        $validated = $request->validate([
            'configurations' => 'nullable|array',
            'configurations.*' => 'boolean',
        ]);

        // Get all configurations for this group
        $allConfigs = DocumentValidatorGroupConfiguration::where('validator_group_id', $group->id)->get();

        // Get submitted configuration IDs (only checked checkboxes are submitted)
        $submittedConfigIds = array_keys($request->input('configurations', []));

        // Update each configuration
        foreach ($allConfigs as $config) {
            $oldValue = $config->value;

            // Checkbox is checked if its ID is in the submitted data
            $newValue = in_array($config->id, $submittedConfigIds);

            // Only log and update if value actually changed
            if ((bool) $oldValue !== (bool) $newValue) {
                DocumentValidatorGroupConfigurationHistory::create([
                    'validator_group_id' => $group->id,
                    'configuration_id' => $config->id,
                    'user_id' => Auth::id(),
                    'configuration_key' => $config->key,
                    'configuration_label' => $config->label,
                    'old_value' => $oldValue,
                    'new_value' => $newValue,
                    'action' => 'update',
                ]);

                // Update configuration
                $config->update(['value' => $newValue]);
            }
        }

        return back()->with('success', 'Configuraciones actualizadas exitosamente.');
    }

    /**
     * Initialize default configurations for a group.
     */
    protected function initializeDefaultConfigurations(ValidatorGroup $group): void
    {
        $defaultConfigs = [
            // Email Actions
            [
                'key' => 'enable_initial_request',
                'label' => 'Solicitud inicial',
                'description' => 'Permitir enviar solicitud inicial de documentos al cliente',
                'category' => 'email_actions',
                'order' => 1,
                'value' => true,
            ],
            [
                'key' => 'enable_missing_docs',
                'label' => 'Documentos faltantes',
                'description' => 'Permitir solicitar documentos específicos que falten',
                'category' => 'email_actions',
                'order' => 2,
                'value' => true,
            ],
            [
                'key' => 'enable_reminder',
                'label' => 'Recordatorio',
                'description' => 'Permitir enviar recordatorios al cliente',
                'category' => 'email_actions',
                'order' => 3,
                'value' => true,
            ],
            [
                'key' => 'enable_upload_confirmation',
                'label' => 'Confirmación de subida',
                'description' => 'Permitir confirmar recepción de documentos',
                'category' => 'email_actions',
                'order' => 4,
                'value' => true,
            ],
            [
                'key' => 'enable_approval',
                'label' => 'Notificación de aprobación',
                'description' => 'Permitir notificar aprobación de documentos',
                'category' => 'email_actions',
                'order' => 5,
                'value' => true,
            ],
            [
                'key' => 'enable_rejection',
                'label' => 'Notificación de rechazo',
                'description' => 'Permitir notificar rechazo de documentos',
                'category' => 'email_actions',
                'order' => 6,
                'value' => true,
            ],
            [
                'key' => 'enable_custom_email',
                'label' => 'Correo personalizado',
                'description' => 'Permitir enviar correos personalizados',
                'category' => 'email_actions',
                'order' => 7,
                'value' => true,
            ],

            // Workflow
            [
                'key' => 'enable_auto_approval',
                'label' => 'Aprobación automática',
                'description' => 'Aprobar automáticamente cuando se cumplan condiciones',
                'category' => 'workflow',
                'order' => 1,
                'value' => false,
            ],
            [
                'key' => 'require_comments',
                'label' => 'Comentarios obligatorios',
                'description' => 'Requerir comentario al rechazar documentos',
                'category' => 'workflow',
                'order' => 2,
                'value' => false,
            ],
            [
                'key' => 'allow_parallel_validation',
                'label' => 'Validación paralela',
                'description' => 'Permitir validación simultanea en múltiples etapas',
                'category' => 'workflow',
                'order' => 3,
                'value' => false,
            ],

            // Notifications
            [
                'key' => 'notify_on_assignment',
                'label' => 'Notificar asignación',
                'description' => 'Notificar al usuario cuando se asigne un documento',
                'category' => 'notifications',
                'order' => 1,
                'value' => true,
            ],
            [
                'key' => 'notify_on_rejection',
                'label' => 'Notificar rechazo',
                'description' => 'Notificar al equipo cuando se rechace un documento',
                'category' => 'notifications',
                'order' => 2,
                'value' => true,
            ],
            [
                'key' => 'notify_sla_breach',
                'label' => 'Alertar incumplimiento SLA',
                'description' => 'Alertar cuando se incumple el SLA de validación',
                'category' => 'notifications',
                'order' => 3,
                'value' => true,
            ],
        ];

        foreach ($defaultConfigs as $config) {
            $config['validator_group_id'] = $group->id;
            ValidatorGroupConfiguration::firstOrCreate(
                [
                    'validator_group_id' => $group->id,
                    'key' => $config['key'],
                ],
                $config
            );
        }
    }
}
