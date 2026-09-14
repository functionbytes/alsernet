<?php

namespace Modules\HelpdeskSocial\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Helpdesk\Http\Responses\ApiResponse;
use Modules\HelpdeskSocial\Http\Requests\EnterCrisisModeRequest;
use Modules\HelpdeskSocial\Http\Requests\StoreSocialAccountRequest;
use Modules\HelpdeskSocial\Http\Requests\UpdateSocialAccountRequest;
use Modules\HelpdeskSocial\Http\Resources\SocialAccountResource;
use Modules\HelpdeskSocial\Models\SocialAccount;
use Modules\HelpdeskSocial\Services\AuditLogService;
use Modules\HelpdeskSocial\Services\CrisisModeService;

class SocialAccountsController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLog,
        private readonly CrisisModeService $crisisMode,
    ) {}

    public function index(): JsonResponse
    {
        abort_if(! auth()->user()?->can('helpdesksocial.view'), 403);
        $accounts = SocialAccount::orderBy('name')->paginate(20);

        return response()->json([
            'success' => true,
            'message' => 'OK',
            'data' => SocialAccountResource::collection($accounts),
            'meta' => [
                'currentPage' => $accounts->currentPage(),
                'lastPage' => $accounts->lastPage(),
                'perPage' => $accounts->perPage(),
                'total' => $accounts->total(),
            ],
        ]);
    }

    public function store(StoreSocialAccountRequest $request): JsonResponse
    {
        abort_if(! auth()->user()?->can('helpdesksocial.accounts.manage'), 403);
        $validated = $request->validated();
        $validated['connected_by_user_id'] = auth()->id();

        $account = SocialAccount::create($validated);
        $this->auditLog->log('create', $account, null, $account->toArray());

        return ApiResponse::created(new SocialAccountResource($account), 'Cuenta creada correctamente.');
    }

    public function show(SocialAccount $account): JsonResponse
    {
        abort_if(! auth()->user()?->can('helpdesksocial.view'), 403);

        return ApiResponse::success(new SocialAccountResource($account));
    }

    public function update(UpdateSocialAccountRequest $request, SocialAccount $account): JsonResponse
    {
        abort_if(! auth()->user()?->can('helpdesksocial.accounts.manage'), 403);
        $oldValues = $account->toArray();
        $account->update($request->validated());
        $this->auditLog->log('update', $account, $oldValues, $account->toArray());

        return ApiResponse::success(new SocialAccountResource($account), 'Cuenta actualizada correctamente.');
    }

    public function destroy(SocialAccount $account): JsonResponse
    {
        abort_if(! auth()->user()?->can('helpdesksocial.accounts.manage'), 403);
        $oldValues = $account->toArray();
        $account->delete();
        $this->auditLog->log('delete', $account, $oldValues);

        return ApiResponse::noContent();
    }

    public function toggleActive(SocialAccount $account): JsonResponse
    {
        abort_if(! auth()->user()?->can('helpdesksocial.accounts.manage'), 403);
        $oldValues = ['is_active' => $account->is_active];
        $account->update(['is_active' => ! $account->is_active]);
        $this->auditLog->log('update', $account, $oldValues, ['is_active' => $account->is_active]);

        return ApiResponse::success(new SocialAccountResource($account));
    }

    public function enterCrisisMode(EnterCrisisModeRequest $request, SocialAccount $account): JsonResponse
    {
        if ($account->isInCrisisMode()) {
            return ApiResponse::success(new SocialAccountResource($account), 'La cuenta ya está en modo crisis.');
        }

        $this->crisisMode->enterCrisisMode($account, $request->string('reason')->toString(), (int) auth()->id());

        return ApiResponse::success(new SocialAccountResource($account->refresh()), 'Modo crisis activado: las auto-respuestas quedan en pausa para esta cuenta.');
    }

    public function exitCrisisMode(SocialAccount $account): JsonResponse
    {
        abort_if(! auth()->user()?->can('helpdesksocial.accounts.manage'), 403);

        if (! $account->isInCrisisMode()) {
            return ApiResponse::success(new SocialAccountResource($account), 'La cuenta no está en modo crisis.');
        }

        $this->crisisMode->exitCrisisMode($account);

        return ApiResponse::success(new SocialAccountResource($account->refresh()), 'Modo crisis desactivado.');
    }
}
