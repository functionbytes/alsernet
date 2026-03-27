<?php

namespace Modules\Attention\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Attention\Http\Requests\CreateAttentionDepartmentRequest;
use Modules\Attention\Http\Requests\UpdateAttentionDepartmentRequest;
use Modules\Attention\Models\AttentionDepartment;

class AttentionDepartmentsController extends Controller
{
    /**
     * Display a listing of departments.
     */
    public function index(Request $request): View
    {
        $query = AttentionDepartment::query();

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $departments = $query->withCount(['users', 'attentions'])->orderBy('name')->paginate(paginationNumber());

        $counts = AttentionDepartment::query()
            ->selectRaw('COUNT(*) as total, SUM(is_active = 1) as active, SUM(is_active = 0) as inactive')
            ->first();

        $stats = [
            'total' => (int) $counts->total,
            'active' => (int) $counts->active,
            'inactive' => (int) $counts->inactive,
            'total_members' => AttentionDepartment::query()
                ->join('attention_department_user', 'attention_departments.id', '=', 'attention_department_user.department_id')
                ->distinct('attention_department_user.user_id')
                ->count('attention_department_user.user_id'),
        ];

        return view('attention::settings.departments.index', [
            'departments' => $departments,
            'stats' => $stats,
        ]);
    }

    /**
     * Show the form for creating a new department.
     */
    public function create(): View
    {
        $users = User::where('available', 1)
            ->orderBy('firstname')
            ->orderBy('lastname')
            ->get();

        return view('attention::settings.departments.create', [
            'users' => $users,
        ]);
    }

    /**
     * Store a newly created department.
     */
    public function store(CreateAttentionDepartmentRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['is_active'] = $request->boolean('is_active', true);

        $department = AttentionDepartment::create($validated);

        // Attach users
        if ($request->filled('users')) {
            $department->users()->attach($request->users);
        }

        return redirect()->route('settings.attention.departments.index')
            ->with('success', 'Departamento creado exitosamente.');
    }

    /**
     * Display the specified department.
     */
    public function show(AttentionDepartment $department): View
    {
        $department->load('users', 'attentions');

        return view('attention::settings.departments.show', [
            'department' => $department,
        ]);
    }

    /**
     * Show the form for editing a department.
     */
    public function edit(AttentionDepartment $department): View
    {
        $department->load('users');
        $users = User::where('available', 1)
            ->orderBy('firstname')
            ->orderBy('lastname')
            ->get();

        return view('attention::settings.departments.edit', [
            'department' => $department,
            'users' => $users,
        ]);
    }

    /**
     * Update the specified department.
     */
    public function update(UpdateAttentionDepartmentRequest $request, AttentionDepartment $department): RedirectResponse
    {
        $validated = $request->validated();
        $validated['is_active'] = $request->boolean('is_active');

        $department->update($validated);

        // Sync users
        if ($request->has('users')) {
            $department->users()->sync($request->filled('users') ? $request->users : []);
        }

        return redirect()->route('settings.attention.departments.index')
            ->with('success', 'Departamento actualizado exitosamente.');
    }

    /**
     * Toggle the active status of a department.
     */
    public function toggle(AttentionDepartment $department): RedirectResponse
    {
        $department->update(['is_active' => ! $department->is_active]);

        return back()->with('success', 'Estado del departamento actualizado exitosamente.');
    }

    /**
     * Remove the specified department.
     */
    public function destroy(AttentionDepartment $department): RedirectResponse
    {
        // Check if department has assigned attentions
        if ($department->attentions()->count() > 0) {
            return back()->with('error', 'No se puede eliminar el departamento porque tiene PQRSF asignados.');
        }

        $department->delete();

        return redirect()->route('settings.attention.departments.index')
            ->with('success', 'Departamento eliminado exitosamente.');
    }
}
