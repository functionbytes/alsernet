<?php

namespace Modules\Reverb\Channels;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Http\Request;

/**
 * Handles authentication of private and presence channels for Reverb.
 *
 * This class manages which users can subscribe to which channels,
 * particularly for private and presence channels that require authentication.
 */
class ChannelAuthenticator
{
    /**
     * Authenticate private channel subscription.
     *
     * @param  Request  $request
     * @param  string  $channel
     * @return bool
     */
    public static function authenticatePrivateChannel(Request $request, string $channel): bool
    {
        $user = $request->user();

        if (! $user) {
            return false;
        }

        // Default: Allow users to subscribe to their own channels
        if ($channel === "private-user.{$user->id}") {
            return true;
        }

        // Check for model-based channels (e.g., private-User.1)
        if (preg_match('/private-([A-Za-z]+)\.(\d+)/', $channel, $matches)) {
            [, $model, $id] = $matches;

            // Allow if user owns the model
            if ($model === 'User' && (int) $id === $user->id) {
                return true;
            }
        }

        return false;
    }

    /**
     * Authenticate presence channel subscription.
     *
     * @param  Request  $request
     * @param  string  $channel
     * @return array|bool
     */
    public static function authenticatePresenceChannel(Request $request, string $channel): array|bool
    {
        $user = $request->user();

        if (! $user) {
            return false;
        }

        // Return user presence data
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ];
    }
}
