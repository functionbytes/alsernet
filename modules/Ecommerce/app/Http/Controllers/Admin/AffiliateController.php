<?php

namespace Modules\Ecommerce\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Ecommerce\Models\Affiliate;
use Modules\Ecommerce\Models\AffiliateReferral;

class AffiliateController extends Controller
{
    public function index(Request $request): View
    {
        $affiliates = Affiliate::query()
            ->with('customer')
            ->when($request->input('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->input('search'), fn ($q, $s) => $q->whereHas('customer', fn ($q) => $q->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%")))
            ->latest()
            ->paginate(20);

        return view('ecommerce::affiliates.index', compact('affiliates'));
    }

    public function show(Affiliate $affiliate): View
    {
        $affiliate->load('customer');
        $referrals = $affiliate->referrals()->with('order', 'customer')->latest()->paginate(20);

        return view('ecommerce::affiliates.show', compact('affiliate', 'referrals'));
    }

    public function update(Request $request, Affiliate $affiliate): RedirectResponse
    {
        $validated = $request->validate([
            'commission_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'status' => ['required', 'in:active,suspended,terminated'],
        ]);

        $affiliate->update($validated);

        return back()->with('success', 'Afiliado actualizado.');
    }

    public function approveReferral(AffiliateReferral $referral): RedirectResponse
    {
        if ($referral->status !== 'pending') {
            return back()->with('error', 'Solo se pueden aprobar referidos pendientes.');
        }

        $referral->update(['status' => 'approved']);

        return back()->with('success', 'Referido aprobado.');
    }

    public function markPaid(Affiliate $affiliate): RedirectResponse
    {
        $pendingAmount = $affiliate->pendingPayout();

        if ($pendingAmount <= 0) {
            return back()->with('error', 'No hay comisiones pendientes de pago.');
        }

        $affiliate->referrals()
            ->where('status', 'approved')
            ->update(['status' => 'paid']);

        $affiliate->increment('total_paid', $pendingAmount);

        return back()->with('success', 'Pago registrado: $'.number_format($pendingAmount, 2));
    }
}
