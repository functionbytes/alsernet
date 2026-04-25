<?php

namespace Modules\Ads\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Modules\Ads\Models\Ads;
use Modules\Ads\Models\AdsClick;

class AdsClickController extends Controller
{
    public function click(string $key): RedirectResponse
    {
        $ad = Ads::query()->where('key', $key)->first();

        if (! $ad || ! $ad->url) {
            return redirect()->to('/');
        }

        $ad->increment('clicked');

        AdsClick::query()->create([
            'ads_id' => $ad->id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'clicked_at' => now(),
        ]);

        return redirect()->away($ad->url);
    }
}
