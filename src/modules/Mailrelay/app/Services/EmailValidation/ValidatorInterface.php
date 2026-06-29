<?php

namespace Modules\Mailrelay\Services\EmailValidation;

interface ValidatorInterface
{
    /**
     * Validate an email address
     *
     * @return array ['valid' => bool, 'score' => int, 'details' => array]
     */
    public function validate(string $email): array;

    /**
     * Get the validator name
     */
    public function getName(): string;
}
