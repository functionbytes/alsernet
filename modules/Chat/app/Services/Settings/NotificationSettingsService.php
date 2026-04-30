<?php

namespace Modules\Chat\Services\Settings;

/**
 * Normalises raw notification form data into a clean boolean settings array.
 *
 * HTML checkboxes are absent from the request when unchecked, so we must
 * explicitly map "present in the array" to true and absence to false.
 */
class NotificationSettingsService
{
    /**
     * Convert the raw validated notification payload into a persistable array.
     *
     * @param  array<string, mixed>  $validated
     * @return array{
     *     email: array{conversation_assigned: bool, new_message: bool, conversation_mention: bool},
     *     browser: array{conversation_assigned: bool, new_message: bool, conversation_mention: bool},
     *     sound: bool,
     * }
     */
    public function normalize(array $validated): array
    {
        $channels = ['email', 'browser'];
        $events = ['conversation_assigned', 'new_message', 'conversation_mention'];

        $result = [];

        foreach ($channels as $channel) {
            foreach ($events as $event) {
                $result[$channel][$event] = isset($validated[$channel][$event]);
            }
        }

        $result['sound'] = isset($validated['sound']);

        return $result;
    }
}
