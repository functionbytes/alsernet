<?php

namespace App\Http\Api\V1\Resources;

use App\Http\Api\V1\BaseResource;
use App\Http\Api\V1\Manifest\MobileModuleRegistry;
use Illuminate\Http\Request;
use Modules\Ecommerce\Models\Customer;

class MeResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        /** @var Customer $customer */
        $customer = $this->resource;

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

        return [
            'user' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'emailVerifiedAt' => $this->iso($customer->email_verified_at),
                'phone' => $customer->phone,
                'avatarUrl' => $this->mediaUrl($customer->avatar),
                'status' => $customer->status?->value,
            ],
            'abilities' => $customer->abilities(),
            'modules' => $modules,
            'settings' => [
                'currency' => config('ecommerce.currency', 'COP'),
                'locale' => app()->getLocale(),
                'country' => config('ecommerce.default_country', 'CO'),
            ],
        ];
    }
}
