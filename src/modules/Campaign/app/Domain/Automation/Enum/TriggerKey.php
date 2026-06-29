<?php

namespace Modules\Campaign\Domain\Automation\Enum;

/**
 * Mirror of legacy App\Model\Automation2::TRIGGER_* constants.
 * Kept as an enum so the runtime fail-fast guard + new-flow validation share a single source.
 */
enum TriggerKey: string
{
    case WelcomeNewSubscriber = 'welcome-new-subscriber';
    case SayGoodbyeSubscriber = 'say-goodbye-subscriber';
    case SayHappyBirthday = 'say-happy-birthday';
    case SubscriberAddedDate = 'subscriber-added-date';
    case SpecificDate = 'specific-date';
    case ApiV3 = 'api-3-0';
    case WeeklyRecurring = 'weekly-recurring';
    case MonthlyRecurring = 'monthly-recurring';
    case TagBased = 'tag-based';
    case RemoveTag = 'remove-tag';
    case AttributeUpdate = 'attribute-update';
    case WooAbandonedCart = 'woo-abandoned-cart';
}
