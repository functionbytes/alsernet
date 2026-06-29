<?php

namespace Modules\HelpdeskSocial\Tests\Unit\Services;

use Modules\HelpdeskSocial\Services\Classifiers\RulesIntentClassifier;
use Modules\HelpdeskSocial\Tests\TestCase;

class IntentClassifierTest extends TestCase
{
    private RulesIntentClassifier $classifier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->classifier = new RulesIntentClassifier;
    }

    public function test_classifies_complaint_correctly(): void
    {
        $result = $this->classifier->classifyText('Este producto es terrible y roto, quiero una queja');

        $this->assertEquals('complaint', $result['intent']);
        $this->assertGreaterThan(0.5, $result['confidence']);
        $this->assertEquals('high', $result['urgency']);
    }

    public function test_classifies_purchase_interest_correctly(): void
    {
        $result = $this->classifier->classifyText('Cuanto cuesta este producto? Me interesa comprar');

        $this->assertEquals('purchase_interest', $result['intent']);
        $this->assertGreaterThan(0.5, $result['confidence']);
    }

    public function test_classifies_query_correctly(): void
    {
        $result = $this->classifier->classifyText('Como funciona este servicio? Tengo una consulta');

        $this->assertEquals('query', $result['intent']);
    }

    public function test_classifies_positive_correctly(): void
    {
        $result = $this->classifier->classifyText('Excelente servicio, muy bueno, me encanta');

        $this->assertEquals('positive', $result['intent']);
    }

    public function test_classifies_spam_correctly(): void
    {
        $result = $this->classifier->classifyText('Gana dinero facil click aqui gratis bitcoin');

        $this->assertEquals('spam', $result['intent']);
    }

    public function test_defaults_to_neutral_for_unclear_text(): void
    {
        $result = $this->classifier->classifyText('Hola');

        $this->assertEquals('neutral', $result['intent']);
    }
}
