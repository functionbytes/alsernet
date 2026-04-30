<?php

namespace Modules\Campaign\Tests\Feature;

use Modules\Campaign\Services\BounceClassifier;
use Tests\TestCase;

class BounceClassifierTest extends TestCase
{
    public function test_classifies_hard_bounce(): void
    {
        $classifier = new BounceClassifier;
        $result = $classifier->classify('550 5.1.1 User unknown');

        $this->assertSame('hard', $result['type']);
        $this->assertSame('bad_mailbox', $result['category']);
    }

    public function test_classifies_soft_bounce(): void
    {
        $classifier = new BounceClassifier;
        $result = $classifier->classify('452 4.2.2 Mailbox full');

        $this->assertSame('soft', $result['type']);
        $this->assertSame('mailbox_full', $result['category']);
    }

    public function test_classifies_block(): void
    {
        $classifier = new BounceClassifier;
        $result = $classifier->classify('550 5.7.1 Message rejected due to spam');

        $this->assertSame('block', $result['type']);
        $this->assertSame('spam_block', $result['category']);
    }

    public function test_soft_should_retry(): void
    {
        $classifier = new BounceClassifier;
        $this->assertTrue($classifier->shouldRetry('soft'));
    }

    public function test_hard_should_not_retry(): void
    {
        $classifier = new BounceClassifier;
        $this->assertFalse($classifier->shouldRetry('hard'));
    }
}
