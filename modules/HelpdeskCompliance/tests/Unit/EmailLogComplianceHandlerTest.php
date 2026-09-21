<?php

namespace Modules\HelpdeskCompliance\Tests\Unit;

use Modules\HelpdeskCompliance\Services\Handlers\EmailLogComplianceHandler;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * La cascada GDPR hace un LIKE '%email%' sobre recipients_index como prefiltro;
 * mentionsRecipient() es lo que evita que, en modo hard, se borren de forma
 * irreversible los logs de otras direcciones que solo contienen la del cliente
 * como subcadena.
 */
class EmailLogComplianceHandlerTest extends TestCase
{
    /**
     * @return array<string, array{0: ?string, 1: string, 2: bool}>
     */
    public static function cases(): array
    {
        return [
            'dirección única' => ['ana@x.com', 'ana@x.com', true],
            'entre varias, separadas por espacio' => ['bob@y.com ana@x.com carl@z.com', 'ana@x.com', true],
            'mayúsculas distintas' => ['Ana@X.com', 'ana@x.com', true],
            'formato con nombre' => ['Ana Pérez <ana@x.com>', 'ana@x.com', true],
            'subcadena por la izquierda (joana@)' => ['joana@x.com', 'ana@x.com', false],
            'subcadena por la derecha (dominio más largo)' => ['ana@x.com.mx', 'ana@x.com', false],
            'subcadena por la derecha (subdominio)' => ['ana@x.community', 'ana@x.com', false],
            'alias con + no es la misma dirección' => ['ana+news@x.com', 'ana@x.com', false],
            'punto pegado por la izquierda' => ['maria.ana@x.com', 'ana@x.com', false],
            'coincide una y otra no' => ['joana@x.com ana@x.com', 'ana@x.com', true],
            'índice nulo' => [null, 'ana@x.com', false],
            'índice vacío' => ['', 'ana@x.com', false],
            'email vacío' => ['ana@x.com', '', false],
            'caracteres de regex en la dirección' => ['a.b+c@x.com', 'a.b+c@x.com', true],
        ];
    }

    #[DataProvider('cases')]
    public function test_mentions_recipient(?string $index, string $email, bool $expected): void
    {
        $this->assertSame($expected, EmailLogComplianceHandler::mentionsRecipient($index, $email));
    }
}
