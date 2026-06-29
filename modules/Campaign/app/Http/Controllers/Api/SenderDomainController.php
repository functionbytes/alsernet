<?php

namespace Modules\Campaign\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\Campaign\Models\CampaignSenderDomain;
use Modules\Campaign\Services\DeliverabilityTester;

class SenderDomainController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => CampaignSenderDomain::orderBy('created_at', 'desc')->paginate(50),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'domain' => ['required', 'string', 'max:255', 'unique:campaign_sender_domains,domain'],
        ]);

        $domain = CampaignSenderDomain::create([
            'uid' => (string) Str::uuid(),
            'domain' => $data['domain'],
            'status' => 'pending',
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $domain,
        ], 201);
    }

    public function show(string $uid): JsonResponse
    {
        $domain = CampaignSenderDomain::where('uid', $uid)->firstOrFail();

        return response()->json(['data' => $domain]);
    }

    public function verify(string $uid): JsonResponse
    {
        $domain = CampaignSenderDomain::where('uid', $uid)->firstOrFail();

        $tester = new DeliverabilityTester;
        $results = $tester->check($domain->domain);

        $dkim = $tester->checkDkim($domain->domain, 'default');

        $domain->update([
            'spf_valid' => $results['spf']['valid'] ?? false,
            'dmarc_valid' => $results['dmarc']['present'] ?? false,
            'dkim_valid' => $dkim['present'] ?? false,
            'mx_valid' => $results['mx']['present'] ?? false,
            'score' => $results['score'] ?? 0,
            'status' => ($results['score'] ?? 0) >= 80 ? 'verified' : 'failed',
            'verified_at' => ($results['score'] ?? 0) >= 80 ? now() : null,
            'last_checked_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'data' => [
                'domain' => $domain,
                'dns_results' => $results,
                'dkim' => $dkim,
            ],
        ]);
    }

    public function delete(string $uid): JsonResponse
    {
        $domain = CampaignSenderDomain::where('uid', $uid)->firstOrFail();
        $domain->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Domain removed.',
        ]);
    }
}
