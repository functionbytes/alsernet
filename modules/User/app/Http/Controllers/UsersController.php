<?php

namespace Modules\User\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Modules\User\Events\UserCreated;
use Modules\User\Events\UserDeleted;
use Modules\User\Events\UserUpdated;
use Modules\User\Http\Requests\StoreUserRequest;
use Modules\User\Http\Requests\UpdateUserRequest;
use Spatie\Permission\Models\Role as SpatieRole;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UsersController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $searchKey = $request->search ?? null;
        $roleFilter = $request->role ?? null;

        $users = User::with('roles')->latest();

        if ($searchKey) {
            $users->where(function ($query) use ($searchKey) {
                $query->where('users.firstname', 'like', '%'.$searchKey.'%')
                    ->orWhere('users.lastname', 'like', '%'.$searchKey.'%')
                    ->orWhere(DB::raw("CONCAT(users.firstname, ' ', users.lastname)"), 'like', '%'.$searchKey.'%')
                    ->orWhere('users.email', 'like', '%'.$searchKey.'%')
                    ->orWhere('users.identification', 'like', '%'.$searchKey.'%');
            });
        }

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

    public function create(): View
    {
        $this->authorize('create', User::class);

        $roles = SpatieRole::orderBy('name')->pluck('name', 'id');

        return view('user::users.create')->with([
            'roles' => $roles,
        ]);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $this->authorize('create', User::class);

        try {
            $user = new User;
            $user->firstname = $request->firstname;
            $user->lastname = $request->lastname;
            $user->email = $request->email;
            $user->password = Hash::make($request->password);
            $user->available = (bool) $request->available;
            $user->identification = $request->identification;
            $user->cellphone = $request->cellphone;
            $user->address = $request->address;
            $user->company = $request->company;
            $user->timezone = $request->timezone ?? 'UTC';

            if ($request->verified === '1' || $request->verified === 1) {
                $user->mail_verified_at = now();
            }

            $user->save();
            $user->assignRole($request->role);

            event(new UserCreated($user, auth()->user()));

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

    public function view($uid): View
    {
        $user = User::where('uid', $uid)->firstOrFail();
        $this->authorize('view', $user);

        $roles = SpatieRole::orderBy('name')->pluck('name', 'id');

        return view('user::users.view')->with([
            'user' => $user,
            'roles' => $roles,
            'userRoles' => $user->getRoleNames()->toArray(),
        ]);
    }

    public function edit($uid): View
    {
        $user = User::where('uid', $uid)->firstOrFail();
        $this->authorize('update', $user);

        $roles = SpatieRole::orderBy('name')->pluck('name', 'id');

        return view('user::users.edit')->with([
            'user' => $user,
            'roles' => $roles,
            'userRoles' => $user->getRoleNames()->toArray(),
        ]);
    }

    public function update(UpdateUserRequest $request): JsonResponse
    {
        $user = User::where('uid', $request->uid)->first();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado.',
            ], 404);
        }

        $this->authorize('update', $user);

        try {
            $user->firstname = $request->firstname;
            $user->lastname = $request->lastname;
            $user->email = $request->email;
            $user->available = (bool) $request->available;
            $user->identification = $request->identification;
            $user->cellphone = $request->cellphone;
            $user->address = $request->address;
            $user->company = $request->company;
            $user->timezone = $request->timezone ?? 'UTC';

            if ($request->filled('password')) {
                $user->password = Hash::make($request->password);
            }

            if ($request->verified === '1' || $request->verified === 1) {
                $user->mail_verified_at ??= now();
            } else {
                $user->mail_verified_at = null;
            }

            $user->save();

            $user->syncRoles([$request->role]);

            event(new UserUpdated($user, auth()->user()));

            Log::info('Usuario actualizado exitosamente', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'assigned_role' => $request->role,
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

    public function destroy($uid): RedirectResponse
    {
        $user = User::uid($uid);

        if (! $user) {
            abort(404, 'Usuario no encontrado');
        }

        $this->authorize('delete', $user);

        try {
            Log::info('Usuario eliminado', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'assigned_roles' => $user->getRoleNames()->toArray(),
            ]);

            event(new UserDeleted($user, auth()->user()));

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

    public function bulkAction(Request $request): JsonResponse
    {
        $this->authorize('bulkAction', User::class);

        $request->validate([
            'action' => 'required|in:activate,deactivate,assign_role,delete',
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:users,id',
            'value' => 'nullable|string|exists:roles,name',
        ]);

        $users = User::query()
            ->whereIn('id', $request->ids)
            ->where('id', '!=', auth()->id())
            ->get();

        match ($request->action) {
            'activate' => $users->each->update(['available' => 1]),
            'deactivate' => $users->each->update(['available' => 0]),
            'assign_role' => $users->each->syncRoles([$request->value]),
            'delete' => $users->each->delete(),
        };

        Log::info('Bulk user action performed', [
            'action' => $request->action,
            'count' => $users->count(),
            'performed_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'count' => $users->count(),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('export', User::class);

        $query = User::query()
            ->with('roles')
            ->when($request->role, fn ($q) => $q->whereHas('roles', fn ($r) => $r->where('name', $request->role)))
            ->when($request->filled('available'), fn ($q) => $q->where('available', $request->available));

        $filename = 'usuarios_'.now()->format('Y-m-d_H-i').'.csv';

        return response()->streamDownload(function () use ($query) {
            $output = fopen('php://output', 'w');
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($output, ['ID', 'Nombre', 'Email', 'Roles', 'Estado', 'Creado']);

            $query->chunk(500, function ($users) use ($output) {
                foreach ($users as $user) {
                    fputcsv($output, [
                        $user->id,
                        trim("{$user->firstname} {$user->lastname}"),
                        $user->email,
                        $user->roles->pluck('name')->join(', '),
                        $user->available ? 'Activo' : 'Inactivo',
                        $user->created_at->format('d/m/Y'),
                    ]);
                }
            });

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Search users for Select2 dropdown
     */
    public function search(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

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

        $results = $users->map(fn ($user) => [
            'id' => $user->id,
            'text' => "{$user->firstname} {$user->lastname}",
            'first_name' => $user->firstname,
            'last_name' => $user->lastname,
            'email' => $user->email,
        ]);

        return response()->json($results);
    }
}
