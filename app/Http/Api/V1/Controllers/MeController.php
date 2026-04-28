<?php

namespace App\Http\Api\V1\Controllers;

use App\Http\Api\V1\BaseApiController;
use App\Http\Api\V1\Manifest\MobileModuleRegistry;
use App\Http\Api\V1\Resources\MeResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeController extends BaseApiController
{
    public function me(Request $request): JsonResponse
    {
        $customer = $request->user();

        return $this->ok(new MeResource($customer));
    }

    public function modules(Request $request): JsonResponse
    {
        $registry = app(MobileModuleRegistry::class);

        $modules = array_map(
            fn ($manifest) => [
                'alias' => $manifest->alias(),
                'name' => $manifest->name(),
                'version' => $manifest->version(),
                'endpoints' => $manifest->endpoints(),
                'requiresAbilities' => $manifest->requiresAbilities(),
                'featureFlags' => $manifest->featureFlags(),
            ],
            $registry->forAudience('customer')
        );

        return $this->ok($modules);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30'],
        ]);

        $request->user()->update($validated);

        return $this->ok(new MeResource($request->user()->fresh()));
    }
}
