<?php

namespace Modules\CampaignSendingServers\Http\Controllers\Managers;

use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\CampaignSendingServers\Http\Requests\StoreSendingServerRequest;
use Modules\CampaignSendingServers\Http\Requests\UpdateSendingServerRequest;
use Modules\CampaignSendingServers\Models\SendingServer;

class SendingServerController extends Controller
{
    public function index(Request $request): View
    {
        $servers = SendingServer::query()
            ->search($request->query('q'))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('campaignsendingservers::manager.servers.index', [
            'servers' => $servers,
            'providers' => config('campaign_sending_servers.providers', []),
        ]);
    }

    public function create(Request $request): View
    {
        $type = $request->query('type', SendingServer::TYPE_SMTP);

        return view('campaignsendingservers::manager.servers.create', [
            'type' => $type,
            'providers' => config('campaign_sending_servers.providers', []),
        ]);
    }

    public function store(StoreSendingServerRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data += config('campaign_sending_servers.default_rate_limits', []);

        $server = SendingServer::create($data);

        return redirect()
            ->route('manager.sending-servers.show', $server->uid)
            ->with('success', 'Servidor de envío creado.');
    }

    public function show(string $uid): View
    {
        $server = SendingServer::where('uid', $uid)->firstOrFail();

        return view('campaignsendingservers::manager.servers.show', [
            'server' => $server,
        ]);
    }

    public function edit(string $uid): View
    {
        $server = SendingServer::where('uid', $uid)->firstOrFail();

        return view('campaignsendingservers::manager.servers.edit', [
            'server' => $server,
            'providers' => config('campaign_sending_servers.providers', []),
        ]);
    }

    public function update(UpdateSendingServerRequest $request, string $uid): RedirectResponse
    {
        $server = SendingServer::where('uid', $uid)->firstOrFail();
        $server->update($request->validated());

        return redirect()
            ->route('manager.sending-servers.show', $server->uid)
            ->with('success', 'Servidor actualizado.');
    }

    public function destroy(string $uid): RedirectResponse
    {
        $server = SendingServer::where('uid', $uid)->firstOrFail();
        $server->delete();

        return redirect()
            ->route('manager.sending-servers.index')
            ->with('success', 'Servidor eliminado.');
    }

    /**
     * Probar conexión: instancia el servidor concreto y llama test().
     */
    public function test(string $uid): RedirectResponse
    {
        $server = SendingServer::where('uid', $uid)->firstOrFail()->mapType();

        try {
            $server->test();

            return back()->with('success', 'Conexión exitosa.');
        } catch (Exception $e) {
            return back()->with('error', 'Conexión fallida: '.$e->getMessage());
        }
    }

    /**
     * Verificar identidades/dominios del servidor (placeholder Fase 1, real en Fase 2).
     */
    public function verify(string $uid): RedirectResponse
    {
        $server = SendingServer::where('uid', $uid)->firstOrFail();

        return back()->with('info', 'La verificación se implementa en Fase 2 (cron campaign-sending-servers:verify-senders).');
    }
}
