<?php

namespace Modules\Newsletter\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Modules\Newsletter\Http\Requests\PublicSubscribeRequest;
use Modules\Newsletter\Services\SubscriberService;

class PublicSubscribeController extends Controller
{
    public function __construct(
        private readonly SubscriberService $service
    ) {}

    public function store(PublicSubscribeRequest $request): JsonResponse
    {
        try {
            $this->service->subscribe(
                $request->validated('email'),
                $request->validated('name'),
                $request->ip()
            );

            return response()->json(['success' => true, 'message' => 'Te has suscrito correctamente.']);
        } catch (QueryException) {
            return response()->json(['success' => true, 'message' => 'Ya estás suscrito.']);
        }
    }
}
