<?php

namespace Modules\GiftMessage\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Modules\GiftMessage\Models\GiftMessageConfig;
use Modules\GiftMessage\Services\GiftMessageConfigService;
use Modules\GiftMessage\Services\GiftMessageOrderService;

class GiftMessageController extends Controller
{
    private const AVAILABLE_FONTS = [
        'helvetica' => 'Helvetica',
        'times' => 'Times',
        'courier' => 'Courier',
        'dejavusans' => 'DejaVu Sans (Unicode)',
        'dejavuserif' => 'DejaVu Serif (Unicode)',
    ];

    public function __construct(
        private readonly GiftMessageConfigService $configService,
        private readonly GiftMessageOrderService $orderService
    ) {}

    public function index(): View
    {
        $this->authorize('view', GiftMessageConfig::class);

        return view('giftmessage::admin.index', [
            'pageTitle' => 'Mensaje regalo',
            'config' => $this->configService->current(),
            'orders' => $this->orderService->ordersWithGiftMessage(),
            'fonts' => self::AVAILABLE_FONTS,
        ]);
    }
}
