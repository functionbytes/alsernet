<?php

namespace Modules\Ecommerce\Http\Controllers\Api\V1\Customer;

use App\Http\Api\V1\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Modules\Ecommerce\Http\Requests\Api\V1\Profile\UploadAvatarRequest;

class AvatarController extends BaseApiController
{
    public function update(UploadAvatarRequest $request): JsonResponse
    {
        $customer = $request->user();

        if ($customer->avatar) {
            Storage::disk('public')->delete($customer->avatar);
        }

        $path = $request->file('file')->store('ecommerce/avatars', 'public');
        $customer->forceFill(['avatar' => $path])->save();

        return $this->ok([
            'avatarUrl' => Storage::disk('public')->url($path),
        ], 'Avatar actualizado.');
    }

    public function destroy(Request $request): JsonResponse
    {
        $customer = $request->user();

        if ($customer->avatar) {
            Storage::disk('public')->delete($customer->avatar);
            $customer->forceFill(['avatar' => null])->save();
        }

        return $this->noContent('Avatar eliminado.');
    }
}
