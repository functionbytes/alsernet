<?php

namespace Modules\Ecommerce\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Modules\Ecommerce\Models\EmailCampaignRecipient;

class EmailTrackingController extends Controller
{
    public function open(string $token): Response
    {
        $recipient = EmailCampaignRecipient::query()->where('token', $token)->first();

        if ($recipient && ! $recipient->opened_at) {
            $recipient->update(['opened_at' => now()]);
            DB::table('ecommerce_email_campaigns')
                ->where('id', $recipient->campaign_id)
                ->increment('opens_count');
        }

        return response(
            base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7'),
            200,
            ['Content-Type' => 'image/gif']
        );
    }

    public function click(Request $request, string $token): RedirectResponse
    {
        $url = $request->input('url', url('/'));
        $url = filter_var($url, FILTER_VALIDATE_URL) ? $url : url('/');

        $recipient = EmailCampaignRecipient::query()->where('token', $token)->first();

        if ($recipient && ! $recipient->clicked_at) {
            $recipient->update(['clicked_at' => now()]);
            DB::table('ecommerce_email_campaigns')
                ->where('id', $recipient->campaign_id)
                ->increment('clicks_count');
        }

        return redirect()->away($url);
    }
}
