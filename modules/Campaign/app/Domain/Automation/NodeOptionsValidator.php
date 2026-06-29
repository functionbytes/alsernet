<?php

namespace Modules\Campaign\Domain\Automation;

use Illuminate\Support\Facades\Validator;
use Modules\Campaign\Domain\Automation\Enum\ConditionKind;
use Modules\Campaign\Domain\Automation\Enum\NodeType;
use Modules\Campaign\Domain\Automation\Enum\OperationKind;
use Modules\Campaign\Domain\Automation\Enum\TriggerKey;

/**
 * Per-type schema enforcement for node `data` payloads.
 *
 * Lives in the domain layer because:
 *   - Validation rules ARE part of the node's contract
 *   - Future Phase-2 runtime can reuse this class to assert before execution
 *   - Tests can run validation without a live HTTP request
 *
 * Throws ValidationException-equivalent (FlowException) so HTTP layer maps to 422.
 */
final class NodeOptionsValidator
{
    /**
     * Validate `data` payload for a given non-trigger node type.
     * Returns the validated array (with unknown keys stripped).
     *
     * @throws FlowException
     */
    public static function validateForType(NodeType $type, array $data): array
    {
        $rules = self::rulesForType($type);
        $messages = self::messages();

        $validator = Validator::make($data, $rules, $messages);
        if ($validator->fails()) {
            throw new FlowException('Invalid node data: '.self::firstError($validator));
        }

        // Strip unknown keys to keep storage clean
        return self::onlyAllowedKeys($validator->validated(), $rules);
    }

    /**
     * Validate trigger options against its TriggerKey-specific schema.
     *
     * @throws FlowException
     */
    public static function validateTrigger(TriggerKey $key, array $data): array
    {
        $rules = self::triggerRulesFor($key);
        $messages = self::messages();

        $validator = Validator::make($data, $rules, $messages);
        if ($validator->fails()) {
            throw new FlowException('Invalid trigger data: '.self::firstError($validator));
        }

        return self::onlyAllowedKeys($validator->validated(), $rules);
    }

    private static function rulesForType(NodeType $type): array
    {
        $common = [
            'title' => 'sometimes|nullable|string|max:200',
        ];

        return match ($type) {
            NodeType::Trigger => throw new \LogicException('Use validateTrigger() for triggers'),

            NodeType::Send => $common + [
                'emailUid' => 'sometimes|nullable|string|max:50',
            ],

            NodeType::Wait => $common + [
                'duration' => 'sometimes|nullable|string|regex:/^P(?:\d+Y)?(?:\d+M)?(?:\d+W)?(?:\d+D)?(?:T(?:\d+H)?(?:\d+M)?(?:\d+S)?)?$/',
            ],

            NodeType::WaitUntil => $common + [
                'until' => 'sometimes|nullable|string|max:50',
            ],

            NodeType::Condition => $common + [
                'kind' => ['sometimes', 'nullable', 'in:'.self::implodeEnumValues(ConditionKind::cases())],
                'emailUid' => 'sometimes|nullable|string|max:50',
                'emailLinkUid' => 'sometimes|nullable|string|max:50',
                'timeout' => 'sometimes|nullable|string|regex:/^P(?:\d+Y)?(?:\d+M)?(?:\d+W)?(?:\d+D)?(?:T(?:\d+H)?(?:\d+M)?(?:\d+S)?)?$/',
            ],

            NodeType::Operation => $common + [
                'kind' => ['sometimes', 'nullable', 'in:'.self::implodeEnumValues(OperationKind::cases())],
                'tags' => 'sometimes|nullable|array',
                'tags.*' => 'string|max:60',
                'targetListUid' => 'sometimes|nullable|string|max:50',
                'fieldUid' => 'sometimes|nullable|string|max:50',
                'value' => 'sometimes|nullable|string|max:255',
                'operator' => 'sometimes|nullable|in:equals,not_equals,contains',
            ],

            NodeType::Webhook => $common + [
                'url' => 'sometimes|nullable|url|max:500',
                'method' => 'sometimes|nullable|in:GET,POST,PUT,PATCH,DELETE',
                'httpConfigUid' => 'sometimes|nullable|string|max:50',
            ],
        };
    }

    private static function triggerRulesFor(TriggerKey $key): array
    {
        $common = [
            'title' => 'sometimes|nullable|string|max:200',
            'key' => 'sometimes|string',  // Authoritative `key` is set by controller from URL
        ];

        return match ($key) {
            TriggerKey::WelcomeNewSubscriber, TriggerKey::SayGoodbyeSubscriber, TriggerKey::ApiV3 => $common,

            TriggerKey::SayHappyBirthday, TriggerKey::SubscriberAddedDate => $common + [
                'fieldUid' => 'sometimes|nullable|string|max:50',
                'before' => 'sometimes|nullable|string|regex:/^P(?:\d+Y)?(?:\d+M)?(?:\d+W)?(?:\d+D)?(?:T(?:\d+H)?(?:\d+M)?(?:\d+S)?)?$/',
                'at' => 'sometimes|nullable|date_format:H:i',
            ],

            TriggerKey::SpecificDate => $common + [
                'date' => 'sometimes|nullable|date_format:Y-m-d',
                'at' => 'sometimes|nullable|date_format:H:i',
            ],

            TriggerKey::WeeklyRecurring => $common + [
                'daysOfWeek' => 'sometimes|nullable|array',
                'daysOfWeek.*' => 'string|in:0,1,2,3,4,5,6',
                'at' => 'sometimes|nullable|date_format:H:i',
            ],

            TriggerKey::MonthlyRecurring => $common + [
                'daysOfMonth' => 'sometimes|nullable|array',
                'daysOfMonth.*' => 'string|regex:/^([1-9]|[12]\d|3[01])$/',
                'at' => 'sometimes|nullable|date_format:H:i',
            ],

            TriggerKey::TagBased, TriggerKey::RemoveTag => $common + [
                'tags' => 'sometimes|nullable|array',
                'tags.*' => 'string|max:60',
            ],

            TriggerKey::AttributeUpdate => $common + [
                'fieldUid' => 'sometimes|nullable|string|max:50',
                'operator' => 'sometimes|nullable|in:equals,not_equals,contains',
                'value' => 'sometimes|nullable|string|max:255',
            ],

            TriggerKey::WooAbandonedCart => $common + [
                'connectUrl' => 'sometimes|nullable|url|max:500',
            ],
        };
    }

    private static function messages(): array
    {
        return [
            'duration.regex' => 'duration must be ISO-8601 (e.g. PT5M, P1D, P2W).',
            'timeout.regex' => 'timeout must be ISO-8601 (e.g. PT5M, P1D, P2W).',
        ];
    }

    private static function firstError($validator): string
    {
        foreach ($validator->errors()->getMessages() as $field => $msgs) {
            return $field.': '.$msgs[0];
        }

        return 'unknown validation error';
    }

    /**
     * Strip keys not in $rules. Laravel's `validated()` already does this for
     * top-level keys, but nested array rules (e.g. `tags.*`) need explicit
     * preservation of the parent.
     */
    private static function onlyAllowedKeys(array $validated, array $rules): array
    {
        $topLevelKeys = [];
        foreach (array_keys($rules) as $rule) {
            $topLevelKeys[explode('.', $rule)[0]] = true;
        }

        return array_intersect_key($validated, $topLevelKeys);
    }

    private static function implodeEnumValues(array $cases): string
    {
        return implode(',', array_map(fn ($c) => $c->value, $cases));
    }
}
