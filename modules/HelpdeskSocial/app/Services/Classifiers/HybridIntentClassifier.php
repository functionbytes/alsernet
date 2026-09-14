<?php

namespace Modules\HelpdeskSocial\Services\Classifiers;

use Modules\HelpdeskSocial\Contracts\IntentClassifierInterface;
use Modules\HelpdeskSocial\Models\SocialComment;

class HybridIntentClassifier implements IntentClassifierInterface
{
    public function __construct(
        private readonly RulesIntentClassifier $rulesClassifier,
        private readonly OpenAiIntentClassifier $openAiClassifier,
    ) {}

    public function getIdentifier(): string
    {
        return 'hybrid';
    }

    public function classify(SocialComment $comment): array
    {
        $rulesResult = $this->rulesClassifier->classify($comment);

        if ($rulesResult['confidence'] >= $this->getConfidenceThreshold()) {
            return $rulesResult;
        }

        if (! $this->openAiClassifier->isAvailable()) {
            return $rulesResult;
        }

        $openAiResult = $this->openAiClassifier->classify($comment);
        $openAiResult['classifier'] = $this->getIdentifier();

        return $openAiResult;
    }

    public function isAvailable(): bool
    {
        return $this->rulesClassifier->isAvailable();
    }

    public function getConfidenceThreshold(): float
    {
        return config('helpdesksocial.intent_classification.confidence_threshold', 0.75);
    }
}
