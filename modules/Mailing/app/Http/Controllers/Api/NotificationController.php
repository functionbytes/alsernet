<?php

namespace Modules\Mailing\Http\Controllers\Api;

use Auth;
use Illuminate\Http\Request;
use Modules\Mailing\Http\Controllers\Controller;
use Modules\Mailing\Models\BounceLog;
use Response;

class NotificationController extends Controller
{
    /**
     * Display all notifications.
     *
     * GET /api/v1/plans
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user = Auth::guard('api')->user();

        return Response::json(['message' => 'Comming...'], 200);
    }

    public function store(Request $request)
    {
        $user = Auth::guard('api')->user();
        $params = $request->all();

        [$log, $validator] = BounceLog::createFromRequest($request->all());

        if ($validator->fails()) {
            return Response::json($validator->errors(), 400);
        } else {
            return Response::json($log, 200);
        }
    }
}
