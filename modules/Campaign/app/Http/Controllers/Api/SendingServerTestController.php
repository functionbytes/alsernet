<?php

namespace Modules\Campaign\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\CampaignSendingServers\Models\SendingServer;

class SendingServerTestController extends Controller
{
    public function test(string $uid): JsonResponse
    {
        $server = SendingServer::where('uid', $uid)->firstOrFail();

        try {
            $ok = $server->test();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'server_uid' => $server->uid,
                    'server_name' => $server->name,
                    'server_type' => $server->type,
                    'connected' => $ok,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'data' => [
                    'server_uid' => $server->uid,
                    'server_name' => $server->name,
                    'server_type' => $server->type,
                    'connected' => false,
                    'error' => $e->getMessage(),
                ],
            ], 422);
        }
    }
}
