<?php

namespace Modules\Ecommerce\Http\Controllers\Api\V1\Customer;

use App\Http\Api\V1\BaseApiController;
use Illuminate\Http\JsonResponse;
use Modules\Ecommerce\Http\Requests\Api\V1\Device\RegisterDeviceRequest;
use Modules\Ecommerce\Http\Resources\Api\V1\DeviceResource;
use Modules\Ecommerce\Models\CustomerPushToken;

class DeviceController extends BaseApiController
{
    public function store(RegisterDeviceRequest $request): JsonResponse
    {
        $data = $request->validated();
        $customerId = $request->user()->id;

        $token = CustomerPushToken::query()->updateOrCreate(
            [
                'customer_id' => $customerId,
                'device_id' => $data['device_id'] ?? null,
            ],
            [
                'token' => $data['token'],
                'platform' => $data['platform'],
                'app_version' => $data['app_version'] ?? null,
                'locale' => $data['locale'] ?? app()->getLocale(),
                'last_used_at' => now(),
                'is_active' => true,
            ]
        );

        return $this->created(new DeviceResource($token), 'Dispositivo registrado.');
    }

    public function destroy(string $device): JsonResponse
    {
        $token = CustomerPushToken::query()
            ->where('customer_id', auth()->id())
            ->where(fn ($q) => $q->where('device_id', $device)->orWhere('id', $device))
            ->first();

        if (! $token) {
            return $this->errorResponse('Dispositivo no encontrado.', 'NOT_FOUND', 404);
        }

        $token->delete();

        return $this->noContent('Dispositivo eliminado.');
    }
}
