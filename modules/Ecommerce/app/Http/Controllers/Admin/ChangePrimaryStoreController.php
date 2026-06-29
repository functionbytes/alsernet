<?php

namespace Modules\Ecommerce\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Modules\Ecommerce\Models\StoreLocator;

class ChangePrimaryStoreController extends Controller
{
    public function __invoke(StoreLocator $storeLocator): JsonResponse
    {
        DB::transaction(function () use ($storeLocator) {
            StoreLocator::query()->update(['is_primary' => false]);
            $storeLocator->update(['is_primary' => true]);
        });

        return response()->json(['success' => true]);
    }
}
