<?php

namespace Modules\User\Http\Controllers;

use App\Http\Controllers\Controller;
// use Modules\Catalog\Models\Shop; // Removed
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role as SpatieRole;

class UsersController extends Controller
{
    /**
     * Roles that require warehouse/shop assignment
     */
    protected array $rolesRequiringAssignment = [
        'inventory-manager',
        'inventory-staff',
        'shop-manager',
        'shop-staff',
    ];

    public function index(Request $request)
    {
        // Fix: Use correct null coalescing syntax
        $searchKey = $request->search ?? null;
        $roleFilter = $request->role ?? null;

        $users = User::latest();

        // Search by name, email, or identification
        if ($searchKey) {
            $users->where(function ($query) use ($searchKey) {
                $query->where('users.firstname', 'like', '%'.$searchKey.'%')
                    ->orWhere('users.lastname', 'like', '%'.$searchKey.'%')
                    ->orWhere(DB::raw("CONCAT(users.firstname, ' ', users.lastname)"), 'like', '%'.$searchKey.'%')
                    ->orWhere('users.email', 'like', '%'.$searchKey.'%')
                    ->orWhere('users.identification', 'like', '%'.$searchKey.'%');
            });
        }

        // Filter by role name (not by ID)
        if ($roleFilter) {
            $users->whereHas('roles', function ($query) use ($roleFilter) {
                $query->where('roles.name', $roleFilter);
            });
        }

        $users = $users->paginate(paginationNumber());

        return view('user::users.index')->with([
            'users' => $users,
            'roleFilter' => $roleFilter,
            'searchKey' => $searchKey,
            'availableRoles' => SpatieRole::pluck('name', 'name')->toArray(),
        ]);
    }

    public function create()
    {
        // Shop references removed
        // Get all roles from Spatie
        $roles = SpatieRole::orderBy('name')->pluck('name', 'id');

        return view('user::users.create')->with([
            'roles' => $roles,
            'rolesRequiringAssignment' => $this->rolesRequiringAssignment,
        ]);
    }

    public function store(Request $request)
    {
        // Validate request data
        $request->validate([
            'firstname' => 'required|string|min:2|max:100',
            'lastname' => 'required|string|min:2|max:100',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'available' => 'required|in:0,1',
            'role' => 'required|string|exists:roles,name',
            'identification' => 'nullable|string|max:50',
            'cellphone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'company' => 'nullable|string|max:100',
            'timezone' => 'nullable|string|timezone',
            'verified' => 'nullable|in:0,1',
        ], [
            'email.unique' => 'El correo electrónico ya está registrado en el sistema.',
            'email.required' => 'El correo electrónico es requerido.',
            'role.exists' => 'El rol seleccionado no existe.',
            'timezone.timezone' => 'La zona horaria seleccionada no es válida.',
        ]);

        try {
            $user = new User;
            $user->firstname = $request->firstname;
            $user->lastname = $request->lastname;
            $user->email = $request->email;
            $user->password = bcrypt($request->password);
            $user->available = (bool) $request->available;
            $user->identification = $request->identification;
            $user->cellphone = $request->cellphone;
            $user->address = $request->address;
            $user->company = $request->company;
            $user->timezone = $request->timezone ?? 'UTC';

            // Set mail_verified_at if verified is true
            if ($request->verified === '1' || $request->verified === 1) {
                $user->mail_verified_at = now();
            }

            $user->save();

            // Assign role using Spatie relationship (using role name)
            $user->assignRole($request->role);

            Log::info('Usuario creado exitosamente', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'assigned_role' => $request->role,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Usuario creado exitosamente.',
            ]);
        } catch (\Exception $e) {
            Log::error('Error al crear usuario', [
                'error' => $e->getMessage(),
                'email' => $request->email,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al crear el usuario: '.$e->getMessage(),
            ], 500);
        }
    }

    public function view($uid)
    {
        $user = User::where('uid', $uid)->first();

        if (! $user) {
            abort(404, 'Usuario no encontrado');
        }

        // Shop references removed
        $roles = SpatieRole::orderBy('name')->pluck('name', 'id');

        return view('user::users.view')->with([
            'user' => $user,
            'roles' => $roles,
            'userRoles' => $user->getRoleNames()->toArray(),
            'rolesRequiringAssignment' => $this->rolesRequiringAssignment,
        ]);
    }

    public function edit($uid)
    {
        $user = User::where('uid', $uid)->first();

        if (! $user) {
            abort(404, 'Usuario no encontrado');
        }

        // Shop references removed
        $roles = SpatieRole::orderBy('name')->pluck('name', 'id');

        return view('user::users.edit')->with([
            'user' => $user,
            'roles' => $roles,
            'userRoles' => $user->getRoleNames()->toArray(),
            'rolesRequiringAssignment' => $this->rolesRequiringAssignment,
        ]);
    }

    public function update(Request $request)
    {
        $user = User::where('uid', $request->uid)->first();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado.',
            ], 404);
        }

        // Validate request data
        $request->validate([
            'firstname' => 'required|string|min:2|max:100',
            'lastname' => 'required|string|min:2|max:100',
            'email' => "required|email|unique:users,email,{$user->id}",
            'available' => 'required|in:0,1',
            'role' => 'required|numeric|exists:roles,id',
            'password' => 'nullable|string|min:8',
            'identification' => 'nullable|string|max:50',
            'cellphone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'company' => 'nullable|string|max:100',
            'timezone' => 'nullable|string|timezone',
            'verified' => 'nullable|in:0,1',
        ], [
            'email.unique' => 'El correo electrónico ya está registrado en otro usuario.',
            'role.exists' => 'El rol seleccionado no existe.',
            'timezone.timezone' => 'La zona horaria seleccionada no es válida.',
        ]);

        try {
            // Update basic user data
            $user->firstname = $request->firstname;
            $user->lastname = $request->lastname;
            $user->email = $request->email;
            $user->available = (bool) $request->available;
            $user->identification = $request->identification;
            $user->cellphone = $request->cellphone;
            $user->address = $request->address;
            $user->company = $request->company;
            $user->timezone = $request->timezone ?? 'UTC';

            // Update password if provided
            if ($request->filled('password')) {
                $user->password = bcrypt($request->password);
            }

            // Update email verification status
            if ($request->verified === '1' || $request->verified === 1) {
                if (! $user->mail_verified_at) {
                    $user->mail_verified_at = now();
                }
            } else {
                $user->mail_verified_at = null;
            }

            $user->save();

            // Update role
            $role = SpatieRole::findOrFail($request->role);
            $user->syncRoles([$role->name]);

            Log::info('Usuario actualizado exitosamente', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'assigned_role' => $role->name,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Usuario actualizado correctamente.',
            ]);
        } catch (\Exception $e) {
            Log::error('Error al actualizar usuario', [
                'user_id' => $user->id ?? null,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el usuario: '.$e->getMessage(),
            ], 500);
        }
    }

    public function destroy($uid)
    {
        $user = User::uid($uid);

        if (! $user) {
            abort(404, 'Usuario no encontrado');
        }

        try {
            Log::info('Usuario eliminado', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'assigned_roles' => $user->getRoleNames()->toArray(),
            ]);

            $user->delete();

            return redirect()->route('settings.users.index')
                ->with('success', 'Usuario eliminado exitosamente.');
        } catch (\Exception $e) {
            Log::error('Error al eliminar usuario', [
                'user_id' => $user->id ?? null,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('settings.users.index')
                ->with('error', 'Error al eliminar el usuario.');
        }
    }

    /**
     * Search users for Select2 dropdown
     */
    public function search(Request $request)
    {
        $query = $request->get('q', '');

        $users = User::where('available', true)
            ->where(function ($q) use ($query) {
                $q->where('firstname', 'like', "%{$query}%")
                    ->orWhere('lastname', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%")
                    ->orWhereRaw("CONCAT(firstname, ' ', lastname) LIKE ?", ["%{$query}%"]);
            })
            ->select('id', 'firstname', 'lastname', 'email')
            ->limit(20)
            ->get();

        $results = $users->map(function ($user) {
            return [
                'id' => $user->id,
                'text' => "{$user->firstname} {$user->lastname}",
                'first_name' => $user->firstname,
                'last_name' => $user->lastname,
                'email' => $user->email,
            ];
        });

        return response()->json($results);
    }

    /**
     * Determine if a shop should be assigned to a user based on their role
     */
    private function shouldAssignShop($roleId): bool
    {
        $role = SpatieRole::find($roleId);

        if (! $role) {
            return false;
        }

        return in_array($role->name, $this->rolesRequiringAssignment);
    }
}
