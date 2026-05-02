<?php

namespace Modules\Engagement\Http\Controllers\Api\Sdk;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Engagement\Http\Requests\Sdk\IdentifyRequest;
use Modules\Engagement\Services\RecommenderService;
use Modules\Engagement\Services\ScoringService;
use Modules\Engagement\Services\SessionLinkService;
use Modules\Helpdesk\Models\Customer;

class IdentifyController extends Controller
{
    public function __construct(
        private readonly SessionLinkService $sessionLinkService,
        private readonly ScoringService $scoringService,
        private readonly RecommenderService $recommenderService,
    ) {}

    public function __invoke(IdentifyRequest $request): JsonResponse
    {
        $inbox = $request->attributes->get('livechat_inbox');
        $sessionToken = $request->header('X-Session-Token');

        $customer = DB::transaction(function () use ($request) {
            $customer = $this->findOrCreateCustomer($request);

            $attributes = $customer->custom_attributes ?? [];
            if ($request->filled('id')) {
                $attributes['external_id'] = $request->input('id');
            }
            if ($request->filled('attributes')) {
                $attributes = array_merge($attributes, $request->input('attributes'));
            }

            $customer->update([
                'name' => $request->input('name', $customer->name),
                'phone' => $request->input('phone', $customer->phone),
                'custom_attributes' => $attributes,
                'last_seen_at' => now(),
            ]);

            return $customer;
        });

        if ($sessionToken) {
            $this->sessionLinkService->linkCustomer($sessionToken, $customer->id);
        }

        $visitorScore = $sessionToken
            ? $this->scoringService->recalculate($sessionToken, $inbox->id)
            : null;

        $this->recommenderService->updateProfile($sessionToken ?? '', $customer->id);

        return response()->json([
            'success' => true,
            'data' => [
                'customerId' => $customer->id,
                'linked' => true,
                'score' => $visitorScore?->score ?? 0,
                'segment' => $visitorScore?->segment ?? 'cold',
            ],
        ]);
    }

    private function findOrCreateCustomer(IdentifyRequest $request): Customer
    {
        if ($request->filled('email')) {
            return Customer::query()->firstOrCreate(
                ['email' => $request->input('email')],
                [
                    'name' => $request->input('name', 'Visitor'),
                    'phone' => $request->input('phone'),
                    'custom_attributes' => [],
                ],
            );
        }

        return Customer::query()->create([
            'name' => $request->input('name', 'Visitor'),
            'phone' => $request->input('phone'),
            'custom_attributes' => [],
        ]);
    }
}
