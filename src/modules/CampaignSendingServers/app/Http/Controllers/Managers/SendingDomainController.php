<?php

namespace Modules\CampaignSendingServers\Http\Controllers\Managers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\View\View;
use Modules\CampaignSendingServers\Library\DkimGenerator;
use Modules\CampaignSendingServers\Models\SendingDomain;
use Modules\CampaignSendingServers\Models\SendingServer;

class SendingDomainController extends Controller
{
    public function index(): View
    {
        $domains = SendingDomain::with('sendingServer')->latest('id')->paginate(20);

        return view('campaignsendingservers::manager.domains.index', compact('domains'));
    }

    public function create(): View
    {
        return view('campaignsendingservers::manager.domains.create', [
            'servers' => SendingServer::active()->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'regex:/^[a-z0-9.\-]+$/i'],
            'sending_server_id' => ['nullable', 'exists:campaign_sending_servers,id'],
            'signing_enabled' => ['nullable', 'boolean'],
            'dkim_selector' => ['nullable', 'string'],
        ]);

        $data['signing_enabled'] = (bool) ($data['signing_enabled'] ?? false);

        // Si activa firma DKIM, genera el par RSA al crear y guarda
        // selector + public_key + private_key (este último cifrado).
        if ($data['signing_enabled']) {
            $selector = $data['dkim_selector'] ?? 'mail';
            $keys = DkimGenerator::generate($selector);
            $data['dkim_selector'] = $keys['selector'];
            $data['dkim_public_key'] = $keys['public_key'];
            $data['dkim_private_key'] = $keys['private_key'];
        }

        $domain = SendingDomain::create($data);

        return redirect()
            ->route('manager.sending-servers.domains.show', $domain->uid)
            ->with('success', $data['signing_enabled']
                ? 'Dominio creado y par DKIM generado. Publica el TXT en tu DNS y luego pulsa Verificar.'
                : 'Dominio creado.');
    }

    public function show(string $uid): View
    {
        $domain = SendingDomain::where('uid', $uid)->firstOrFail();

        return view('campaignsendingservers::manager.domains.show', compact('domain'));
    }

    public function destroy(string $uid): RedirectResponse
    {
        SendingDomain::where('uid', $uid)->firstOrFail()->delete();

        return redirect()
            ->route('manager.sending-servers.domains.index')
            ->with('success', 'Dominio eliminado.');
    }

    /**
     * Lanza el command de verificación DKIM para este dominio en concreto.
     */
    public function verify(string $uid): RedirectResponse
    {
        $domain = SendingDomain::where('uid', $uid)->firstOrFail();

        $exit = Artisan::call('campaign-sending-servers:verify-dkim', [
            '--uid' => $domain->uid,
        ]);

        return back()->with(
            $exit === 0 && $domain->fresh()->isVerified() ? 'success' : 'error',
            $domain->fresh()->isVerified()
                ? 'Dominio verificado correctamente.'
                : 'Verificación fallida. Comprueba que el TXT DKIM esté publicado en DNS.'
        );
    }
}
