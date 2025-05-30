<?php

namespace App\Logging;

use Monolog\Formatter\LineFormatter;
use Monolog\LogRecord;

class ReturnLogFormatter extends LineFormatter
{
    /**
     * The format of the log message.
     */
    protected const SIMPLE_FORMAT = "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n";

    public function __construct()
    {
        parent::__construct(static::SIMPLE_FORMAT, 'Y-m-d H:i:s', true, true);
    }

    /**
     * Formats a log record.
     */
    public function format(LogRecord $record): string
    {
        // Extraer información específica de devoluciones del contexto
        $context = $record->context;
        $formatted = [];

        // Información básica siempre presente
        if (isset($context['return_id'])) {
            $formatted['return_id'] = $context['return_id'];
        }

        if (isset($context['order_id'])) {
            $formatted['order_id'] = $context['order_id'];
        }

        if (isset($context['customer_email'])) {
            $formatted['customer_email'] = $this->maskEmail($context['customer_email']);
        }

        // Información específica del evento
        if (isset($context['activity'])) {
            $formatted['activity'] = $context['activity'];
        }

        // Tags para filtrado
        if (isset($context['tags'])) {
            $formatted['tags'] = implode(',', $context['tags']);
        }

        // Métricas de performance si están disponibles
        if (isset($context['processing_time_days'])) {
            $formatted['processing_days'] = $context['processing_time_days'];
        }

        if (isset($context['transition_type'])) {
            $formatted['transition'] = $context['transition_type'];
        }

        // Información de usuario si está disponible
        if (isset($context['user_id'])) {
            $formatted['user_id'] = $context['user_id'];
        }

        if (isset($context['ip_address'])) {
            $formatted['ip'] = $context['ip_address'];
        }

        // Crear el contexto formateado
        $record = $record->with(context: $formatted);

        return parent::format($record);
    }

    /**
     * Enmascarar email para privacidad
     */
    private function maskEmail(string $email): string
    {
        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return '***@***.***';
        }

        $username = $parts[0];
        $domain = $parts[1];

        $maskedUsername = strlen($username) > 2
            ? substr($username, 0, 2) . str_repeat('*', strlen($username) - 2)
            : str_repeat('*', strlen($username));

        $domainParts = explode('.', $domain);
        $maskedDomain = count($domainParts) > 1
            ? str_repeat('*', strlen($domainParts[0])) . '.' . end($domainParts)
            : str_repeat('*', strlen($domain));

        return $maskedUsername . '@' . $maskedDomain;
    }
}
