<?php

namespace Modules\Campaign\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Modules\Campaign\Models\Campaign;
use Modules\Campaign\Models\CampaignListsSegment;

class CampaignDuplicateController extends Controller
{
    public function duplicate(string $uid): JsonResponse
    {
        $original = Campaign::where('uid', $uid)->firstOrFail();

        $clone = $original->replicate([
            'uid',
            'created_at',
            'updated_at',
            'status',
            'delivery_at',
            'run_at',
            'running_pid',
            'last_error',
        ]);
        $clone->uid = (string) Str::uuid();
        $clone->name = $original->name.' (copia)';
        $clone->status = 'new';
        $clone->save();

        // Copiar relaciones: listas/segmentos
        $original->listsSegments()->get()->each(function ($ls) use ($clone): void {
            CampaignListsSegment::create([
                'campaign_id' => $clone->id,
                'mail_list_id' => $ls->mail_list_id,
                'segment_id' => $ls->segment_id,
            ]);
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Campaign duplicated',
            'data' => [
                'uid' => $clone->uid,
                'name' => $clone->name,
            ],
        ]);
    }
}
