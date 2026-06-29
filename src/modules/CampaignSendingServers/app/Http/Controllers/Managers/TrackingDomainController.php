<?php

namespace Modules\CampaignSendingServers\Http\Controllers\Managers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\View\View;
use Modules\CampaignSendingServers\Models\TrackingDomain;

class TrackingDomainController extends Controller
{
    public function index(): View
    {
        $domains = TrackingDomain::latest('id')->paginate(20);

        return view('campaignsendingservers::manager.tracking_domains.index', compact('domains'));
    }

    public function create(): View
    {
        return view('campaignsendingservers::manager.tracking_domains.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'regex:/^[a-z0-9.\-]+$/i', 'unique:campaign_sending_server_tracking_domains,name'],
            'verification_method' => ['nullable', 'in:cname,host,caddy'],
        ]);

        $domain = TrackingDomain::create($data);

        return redirect()
            ->route('manager.sending-servers.tracking-domains.show', $domain->uid)
            ->with('success', 'Dominio de tracking creado.');
    }

    public function show(string $uid): View
    {
        $domain = TrackingDomain::where('uid', $uid)->firstOrFail();

        return view('campaignsendingservers::manager.tracking_domains.show', compact('domain'));
    }

    public function destroy(string $uid): RedirectResponse
    {
        TrackingDomain::where('uid', $uid)->firstOrFail()->delete();

        return redirect()
            ->route('manager.sending-servers.tracking-domains.index')
            ->with('success', 'Dominio de tracking eliminado.');
    }

    public function verify(string $uid): RedirectResponse
    {
        $domain = TrackingDomain::where('uid', $uid)->firstOrFail();

        Artisan::call('campaign-sending-servers:verify-tracking-domains', [
            '--uid' => $domain->uid,
        ]);

        $fresh = $domain->fresh();

        return back()->with(
            $fresh->isVerified() ? 'success' : 'error',
            $fresh->isVerified()
                ? "Dominio verificado vía {$fresh->verification_method}."
                : 'Verificación fallida. Comprueba CNAME / A record en DNS.',
        );
    }
}
