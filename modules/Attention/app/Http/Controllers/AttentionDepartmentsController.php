<?php

namespace Modules\Attention\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Modules\Attention\Models\AttentionDepartment;

class AttentionDepartmentsController extends Controller
{
    /**
     * Display a listing of departments.
     */
    public function index(Request $request)
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

        $departments = $query->withCount(['users', 'attentions'])->orderBy('name')->paginate(20);

        // Calculate statistics
        $stats = [
            'total' => AttentionDepartment::count(),
            'active' => AttentionDepartment::where('is_active', true)->count(),
            'inactive' => AttentionDepartment::where('is_active', false)->count(),
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
    public function create()
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
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'description' => 'nullable|string|max:1000',
            'email' => 'nullable|email|max:150',
            'responsible_name' => 'nullable|string|max:150',
            'responsible_email' => 'nullable|email|max:150',
            'phone' => 'nullable|string|max:20',
            'is_active' => 'nullable|boolean',
            'users' => 'nullable|array',
            'users.*' => 'exists:users,id',
        ]);

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
    public function show(AttentionDepartment $department)
    {
        $department->load('users', 'attentions');

        return view('attention::settings.departments.show', [
            'department' => $department,
        ]);
    }

    /**
     * Show the form for editing a department.
     */
    public function edit(AttentionDepartment $department)
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
    public function update(Request $request, AttentionDepartment $department)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'description' => 'nullable|string|max:1000',
            'email' => 'nullable|email|max:150',
            'responsible_name' => 'nullable|string|max:150',
            'responsible_email' => 'nullable|email|max:150',
            'phone' => 'nullable|string|max:20',
            'is_active' => 'nullable|boolean',
            'users' => 'nullable|array',
            'users.*' => 'exists:users,id',
        ]);

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
    public function toggle(AttentionDepartment $department)
    {
        $department->update(['is_active' => ! $department->is_active]);

        return back()->with('success', 'Estado del departamento actualizado exitosamente.');
    }

    /**
     * Remove the specified department.
     */
    public function destroy(AttentionDepartment $department)
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
