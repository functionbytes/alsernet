<?php

namespace Modules\Mailrelay\Enums;

enum EmailValidationStatus: string
{
    case VALID = 'valid';
    case INVALID = 'invalid';
    case RISKY = 'risky';
    case DISPOSABLE = 'disposable';
    case SUSPICIOUS = 'suspicious';
    case PENDING = 'pending';
    case FAILED = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::VALID => 'Válido',
            self::INVALID => 'Inválido',
            self::RISKY => 'Riesgoso',
            self::DISPOSABLE => 'Desechable',
            self::SUSPICIOUS => 'Sospechoso',
            self::PENDING => 'Pendiente',
            self::FAILED => 'Fallido',
        };
    }
}
