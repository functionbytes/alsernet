<?php

namespace Modules\Campaign\Tests\Feature;

use Modules\Campaign\Services\SpamScoreChecker;
use Tests\TestCase;

class SpamScoreCheckerTest extends TestCase
{
    public function test_clean_template_gets_low_score(): void
    {
        $checker = new SpamScoreChecker;
        $result = $checker->score('<p>Hola, te invitamos a nuestro evento.</p><a href="https://example.com">Ver más</a>', 'Invitación al evento');

        $this->assertLessThan(20, $result['score']);
        $this->assertSame('none', $result['risk']);
    }

    public function test_spam_template_gets_high_score(): void
    {
        $checker = new SpamScoreChecker;
        $result = $checker->score(
            '<p>CONGRATULATIONS!!! You won FREE MONEY!!! Act now!!! <img src="x.jpg"><img src="y.jpg"></p><a href="https://bit.ly/xyz">Click here</a>',
            'URGENT: FREE MONEY ACT NOW!!!'
        );

        $this->assertGreaterThanOrEqual(40, $result['score']);
        $this->assertContains('Subject en MAYÚSCULAS', $result['reasons']);
    }

    public function test_missing_alt_tags_detected(): void
    {
        $checker = new SpamScoreChecker;
        $result = $checker->score('<img src="a.jpg"><img src="b.jpg" alt="ok">', 'Test');

        $this->assertContains('Imagen sin atributo alt', $result['reasons']);
    }
}
