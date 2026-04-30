<?php

namespace Modules\Chat\Http\Controllers\Settings\Channels;

use Illuminate\Http\Request;
use Modules\Chat\Http\Controllers\Controller;
use Modules\Chat\Services\ChannelCredentialsValidator;

class ChannelsController extends Controller
{
    /**
     * Display the channels dashboard (selector).
     */
    public function dashboard(Request $request)
    {
        $channels = ChannelCredentialsValidator::getChannelStatus();

        return view('Chat::settings.channels.dashboard', compact('channels'));
    }
}
