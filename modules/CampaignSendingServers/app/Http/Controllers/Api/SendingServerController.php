<?php

namespace Modules\CampaignSendingServers\Http\Controllers\Api;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Modules\CampaignSendingServers\Http\Requests\StoreSendingServerRequest;
use Modules\CampaignSendingServers\Http\Requests\UpdateSendingServerRequest;
use Modules\CampaignSendingServers\Http\Resources\SendingServerResource;
use Modules\CampaignSendingServers\Models\SendingServer;

class SendingServerController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $servers = SendingServer::query()
            ->search($request->query('q'))
            ->when($request->boolean('active_only'), fn ($q) => $q->active())
            ->latest('id')
            ->paginate((int) $request->query('per_page', 20));

        return SendingServerResource::collection($servers);
    }

    public function store(StoreSendingServerRequest $request): JsonResponse
    {
        $server = SendingServer::create($request->validated());

        return (new SendingServerResource($server))
            ->response()
            ->setStatusCode(201);
    }

    public function show(string $uid): SendingServerResource
    {
        $server = SendingServer::where('uid', $uid)->firstOrFail();

        return new SendingServerResource($server);
    }

    public function update(UpdateSendingServerRequest $request, string $uid): SendingServerResource
    {
        $server = SendingServer::where('uid', $uid)->firstOrFail();
        $server->update($request->validated());

        return new SendingServerResource($server->fresh());
    }

    public function destroy(string $uid): JsonResponse
    {
        $server = SendingServer::where('uid', $uid)->firstOrFail();
        $server->delete();

        return response()->json(null, 204);
    }

    public function test(string $uid): JsonResponse
    {
        $server = SendingServer::where('uid', $uid)->firstOrFail()->mapType();

        try {
            $server->test();

            return response()->json(['ok' => true, 'message' => 'Conexión exitosa']);
        } catch (Exception $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
    }
}
