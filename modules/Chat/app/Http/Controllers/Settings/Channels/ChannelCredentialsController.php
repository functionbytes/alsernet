<?php

namespace Modules\Chat\Http\Controllers\Settings\Channels;

use Modules\Chat\Http\Controllers\Controller;
use Modules\Chat\Services\ChannelCredentialsValidator;

class ChannelCredentialsController extends Controller
{
    /**
     * Display the channel credentials management page.
     */
    public function index()
    {
        $channels = ChannelCredentialsValidator::getChannelStatus();

        return view('Chat::settings.channel-credentials.index', compact('channels'));
    }

    /**
     * Display the setup guide for a specific channel.
     */
    public function show(string $channel)
    {
        if (! ChannelCredentialsValidator::getChannelStatus()->has($channel)) {
            abort(404, 'Channel not found');
        }

        $channelStatus = ChannelCredentialsValidator::getChannelStatus()->get($channel);
        $isConfigured = $channelStatus['configured'];
        $missing = ChannelCredentialsValidator::getMissingCredentials($channel);

        return view('Chat::settings.channel-credentials.show', compact(
            'channel',
            'channelStatus',
            'isConfigured',
            'missing'
        ));
    }

    /**
     * Check if a channel is properly configured (AJAX).
     */
    public function checkConfiguration(string $channel)
    {
        $isConfigured = ChannelCredentialsValidator::isConfigured($channel);
        $missing = ChannelCredentialsValidator::getMissingCredentials($channel);

        return response()->json([
            'configured' => $isConfigured,
            'missing' => $missing,
            'message' => $isConfigured
                ? "✓ {$channel} is properly configured"
                : 'Missing: '.implode(', ', $missing),
        ]);
    }
}
