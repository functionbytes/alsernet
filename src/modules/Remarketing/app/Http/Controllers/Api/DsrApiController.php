<?php

namespace Modules\Remarketing\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Modules\Remarketing\Http\Requests\Api\DsrDeleteRequest;
use Modules\Remarketing\Http\Requests\Api\DsrExportRequest;
use Modules\Remarketing\Models\ConsentEvent;
use Modules\Remarketing\Models\Customer;
use Modules\Remarketing\Models\Suppression;

class DsrApiController extends Controller
{
    public function export(DsrExportRequest $request): JsonResponse
    {
        if (! $request->user()->can('remarketing.dsr.export')) {
            abort(403);
        }

        $customer = Customer::with(['orders.items', 'events', 'messages', 'consentEvents', 'carts'])
            ->findOrFail($request->input('customer_id'));

        return response()->json([
            'customer' => $customer->toArray(),
            'orders' => $customer->orders->toArray(),
            'events' => $customer->events->toArray(),
            'messages' => $customer->messages->toArray(),
            'consent_events' => $customer->consentEvents->toArray(),
            'carts' => $customer->carts->toArray(),
            'exported_at' => now()->toIso8601String(),
        ]);
    }

    public function delete(DsrDeleteRequest $request): JsonResponse
    {
        if (! $request->user()->can('remarketing.dsr.delete')) {
            abort(403);
        }

        $customer = Customer::findOrFail($request->input('customer_id'));

        DB::transaction(function () use ($customer, $request) {
            ConsentEvent::create([
                'store_id' => $customer->store_id,
                'customer_id' => $customer->id,
                'email' => $customer->email,
                'event_type' => 'withdrawn',
                'source' => 'admin',
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'occurred_at' => now(),
            ]);

            Suppression::firstOrCreate(
                ['store_id' => $customer->store_id, 'email' => $customer->email],
                ['reason' => 'gdpr_delete']
            );

            $customer->forceFill([
                'email' => 'deleted-'.$customer->id.'@gdpr.request',
                'first_name' => null,
                'last_name' => null,
                'phone' => null,
                'birthday' => null,
                'tags' => null,
                'attributes' => null,
            ])->save();

            $customer->delete();
        });

        return response()->json(['ok' => true, 'message' => 'Customer data anonymized and soft-deleted']);
    }
}
