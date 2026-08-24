<?php

namespace Modules\GiftMessage\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Modules\GiftMessage\Models\GiftMessageConfig;
use Modules\GiftMessage\Services\GiftMessageOrderService;

class GiftMessageController extends Controller
{
    public function __construct(
        private readonly GiftMessageOrderService $orderService
    ) {}

    public function index(): View
    {
        $this->authorize('view', GiftMessageConfig::class);

        return view('giftmessage::admin.index', [
            'pageTitle' => 'Mensaje regalo',
            'orders' => $this->orderService->ordersWithGiftMessage(),
        ]);
    }
}
