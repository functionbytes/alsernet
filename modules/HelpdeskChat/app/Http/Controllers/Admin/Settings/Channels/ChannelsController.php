<?php

namespace Modules\HelpdeskChat\Http\Controllers\Admin\Settings\Channels;

use Illuminate\Http\Request;
use Modules\HelpdeskChat\Http\Controllers\Controller;
use Modules\HelpdeskChat\Services\ChannelCredentialsValidator;

class ChannelsController extends Controller
{
    /**
     * Display the channels dashboard (selector).
     */
    public function dashboard(Request $request)
    {
        $channels = ChannelCredentialsValidator::getChannelStatus();

        return view('helpdeskchat::admin.settings.channels.dashboard', compact('channels'));
    }
}
