<?php

namespace Modules\Ecommerce\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Ecommerce\Models\Affiliate;

class AffiliateController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:ecommerce');
    }

    public function dashboard(): View
    {
        $affiliate = Affiliate::query()->firstOrCreate(
            ['customer_id' => auth('ecommerce')->id()],
            [
                'code' => Affiliate::generateCode(),
                'commission_rate' => 5.00,
                'status' => 'active',
            ]
        );

        $referrals = $affiliate->referrals()->with('order')->latest()->paginate(20);

        return view('ecommerce::shop.account.affiliate', compact('affiliate', 'referrals'));
    }

    public function enroll(): RedirectResponse
    {
        Affiliate::query()->firstOrCreate(
            ['customer_id' => auth('ecommerce')->id()],
            [
                'code' => Affiliate::generateCode(),
                'commission_rate' => 5.00,
                'status' => 'active',
            ]
        );

        return back()->with('success', 'Programa de referidos activado.');
    }
}
