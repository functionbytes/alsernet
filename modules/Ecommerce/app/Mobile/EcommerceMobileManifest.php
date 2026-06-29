<?php

namespace Modules\Ecommerce\Mobile;

use App\Http\Api\V1\Manifest\MobileModuleManifest;

class EcommerceMobileManifest implements MobileModuleManifest
{
    public function alias(): string
    {
        return 'ecommerce';
    }

    public function name(): string
    {
        return 'Tienda';
    }

    public function version(): string
    {
        return 'v1';
    }

    public function audiences(): array
    {
        return ['customer'];
    }

    public function endpoints(): array
    {
        return [
            'catalog' => '/api/v1/ecommerce/products',
            'cart' => '/api/v1/ecommerce/cart',
            'orders' => '/api/v1/ecommerce/orders',
            'wishlist' => '/api/v1/ecommerce/wishlist',
            'addresses' => '/api/v1/ecommerce/addresses',
            'profile' => '/api/v1/me',
        ];
    }

    public function requiresAbilities(): array
    {
        return [];
    }

    public function featureFlags(): array
    {
        return [];
    }
}
