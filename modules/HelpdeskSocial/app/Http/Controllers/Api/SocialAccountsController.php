<?php

namespace Modules\HelpdeskSocial\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\HelpdeskSocial\Http\Requests\StoreSocialAccountRequest;
use Modules\HelpdeskSocial\Http\Requests\UpdateSocialAccountRequest;
use Modules\HelpdeskSocial\Http\Resources\SocialAccountResource;
use Modules\HelpdeskSocial\Models\SocialAccount;
use Modules\HelpdeskSocial\Services\AuditLogService;

class SocialAccountsController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLog,
    ) {}

    public function index(): JsonResponse
    {
        abort_if(! auth()->user()?->hasPermissionTo('helpdesksocial.view'), 403);
        $accounts = SocialAccount::orderBy('name')->paginate(20);

        return response()->json([
            'data' => SocialAccountResource::collection($accounts),
            'meta' => [
                'current_page' => $accounts->currentPage(),
                'last_page' => $accounts->lastPage(),
                'per_page' => $accounts->perPage(),
                'total' => $accounts->total(),
            ],
        ]);
    }

    public function store(StoreSocialAccountRequest $request): JsonResponse
    {
        abort_if(! auth()->user()?->hasPermissionTo('helpdesksocial.manage-accounts'), 403);
        $validated = $request->validated();
        $validated['connected_by_user_id'] = auth()->id();

        $account = SocialAccount::create($validated);
        $this->auditLog->log('create', $account, null, $account->toArray());

        return response()->json([
            'data' => new SocialAccountResource($account),
        ], 201);
    }

    public function show(SocialAccount $account): JsonResponse
    {
        abort_if(! auth()->user()?->hasPermissionTo('helpdesksocial.view'), 403);

        return response()->json([
            'data' => new SocialAccountResource($account),
        ]);
    }

    public function update(UpdateSocialAccountRequest $request, SocialAccount $account): JsonResponse
    {
        abort_if(! auth()->user()?->hasPermissionTo('helpdesksocial.manage-accounts'), 403);
        $oldValues = $account->toArray();
        $account->update($request->validated());
        $this->auditLog->log('update', $account, $oldValues, $account->toArray());

        return response()->json([
            'data' => new SocialAccountResource($account),
        ]);
    }

    public function destroy(SocialAccount $account): JsonResponse
    {
        abort_if(! auth()->user()?->hasPermissionTo('helpdesksocial.manage-accounts'), 403);
        $oldValues = $account->toArray();
        $account->delete();
        $this->auditLog->log('delete', $account, $oldValues);

        return response()->json(['message' => 'Cuenta eliminada correctamente']);
    }

    public function toggleActive(SocialAccount $account): JsonResponse
    {
        abort_if(! auth()->user()?->hasPermissionTo('helpdesksocial.manage-accounts'), 403);
        $oldValues = ['is_active' => $account->is_active];
        $account->update(['is_active' => ! $account->is_active]);
        $this->auditLog->log('update', $account, $oldValues, ['is_active' => $account->is_active]);

        return response()->json([
            'data' => new SocialAccountResource($account),
        ]);
    }
}
