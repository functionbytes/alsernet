<?php

namespace Modules\CampaignSendingServers\Library\Everification;

interface EmailVerifierContract
{
    /**
     * Verifica un email contra el servicio externo y devuelve uno de:
     *   valid | invalid | risky | unknown
     */
    public function verify(string $email): string;

    public const RESULT_VALID = 'valid';

    public const RESULT_INVALID = 'invalid';

    public const RESULT_RISKY = 'risky';

    public const RESULT_UNKNOWN = 'unknown';
}
